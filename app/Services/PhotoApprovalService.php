<?php

namespace App\Services;

use App\Models\PhotoApproval;
use App\Models\CalonPelanggan;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\SrData; // perbaiki case (bukan SRData)
use App\Models\SkData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;
use Exception;

class PhotoApprovalService
{
    public function __construct(
        private TelegramService $telegramService,
        private OpenAIService $openAIService,
        private NotificationService $notificationService,
        private ?FileUploadService $uploader = null
    ) {}

    /* =========================================================================================
     |  ENDPOINT ORKESTRA: Upload → AI Checks → Simpan PhotoApproval → Recalc status modul
     |  Controller sebaiknya: return json($res, $res['success'] ? 201 : 422);
     * =======================================================================================*/
    public function handleUploadAndValidate(
        string $module,            // 'SK' | 'SR'
        string $reffId,
        string $slotIncoming,      // boleh alias (mis. foto_pneumatic_start_sk_url)
        UploadedFile $file,
        ?int $userId = null,
        array $meta = []           // ['customer_name' => '...']
    ): array {
        $moduleKey  = strtoupper($module);
        $moduleSlug = strtolower($moduleKey);
        $uid        = (int) ($userId ?? Auth::id());

        // ---------- 1) Baca config & normalisasi slot ----------
        $cfg     = (array) config('aergas_photos', []);
        $aliases = (array) ($cfg['aliases'][$moduleKey] ?? []);
        $slotKey = $aliases[$slotIncoming] ?? $slotIncoming;

        $slotCfg = $cfg['modules'][$moduleKey]['slots'][$slotKey] ?? null;
        if (!$slotCfg) {
            return ['success' => false, 'message' => "Slot tidak dikenal: {$slotIncoming} → {$slotKey}"];
        }

        // ---------- 2) Validasi field dependency (contoh SR: tapping_saddle butuh jenis_tapping) ----------
        $requires = (array) ($slotCfg['requires']['fields'] ?? []);
        if ($requires) {
            if ($moduleKey === 'SR') {
                $sr = SrData::where('reff_id_pelanggan', $reffId)->first();
                foreach ($requires as $fieldName) {
                    if (!$sr || is_null($sr->{$fieldName}) || $sr->{$fieldName} === '') {
                        return [
                            'success' => false,
                            'message' => "Field '{$fieldName}' wajib diisi sebelum upload foto '{$slotCfg['label']}'."
                        ];
                    }
                }
            }
            // modul lain bisa ditambah di sini
        }

        // ---------- 3) (Opsional) hapus foto lama di slot yang sama ----------
        $replaceSameSlot = (bool) ($cfg['modules'][$moduleKey]['replace_same_slot'] ?? true);
        $uploader = $this->uploader ?? app(FileUploadService::class);
        if ($replaceSameSlot) {
            try {
                $uploader->deleteExistingPhoto($reffId, $moduleKey, $slotKey);
            } catch (\Throwable $e) {
                Log::info('deleteExistingPhoto non-fatal', ['err' => $e->getMessage()]);
            }
        }

        // ---------- 4) Upload (prioritas Drive; fallback lokal) ----------
        $customerName = $meta['customer_name'] ?? $this->getCustomerName($reffId);
        $up = $uploader->uploadPhoto(
            file:        $file,
            reffId:      $reffId,
            module:      $moduleKey,
            fieldName:   $slotKey,
            uploadedBy:  $uid,
            customerName:$customerName
        );

        if (!$up || empty($up['url'])) {
            return ['success' => false, 'message' => 'Upload gagal'];
        }

        // ---------- 5) Simpan/Update PhotoApproval utama ----------
        $pa = PhotoApproval::updateOrCreate(
            [
                'reff_id_pelanggan' => $reffId,
                'module_name'       => $moduleSlug,
                'photo_field_name'  => $slotKey,
            ],
            [
                'photo_url'     => $up['url'] ?? '',
                'storage_disk'  => $up['disk'] ?? config('filesystems.default', 'public'),
                'storage_path'  => $up['path'] ?? '',
                'drive_file_id' => $up['drive_file_id'] ?? null,
                'drive_link'    => $up['drive_link'] ?? null,
                'uploaded_by'   => $uid,
                'uploaded_at'   => now(),
                'ai_status'     => 'pending',
                'photo_status'  => 'ai_pending',
            ]
        );

        // ---------- 6) Jalankan AI checks berbasis config ----------
        $checksSpec = (array) ($slotCfg['checks'] ?? []);
        $ai         = $this->runAiChecks($up['url'], $checksSpec, $slotKey, $moduleKey);

        // Update FileStorage.ai_status (kalau ada)
        try {
            if (!empty($up['file_storage_id'])) {
                $fs = \App\Models\FileStorage::find($up['file_storage_id']);
                if ($fs) {
                    $fs->ai_status = $ai['status'];
                    $fs->save();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('file_storage update ai_status failed: '.$e->getMessage());
        }

        // ---------- 7) Persist hasil AI + transisi status ----------
        $photoStatus = $ai['status'] === 'passed' ? 'tracer_pending' : 'ai_rejected';
        $pa->ai_status          = $ai['status'];
        $pa->ai_score           = $ai['score'];
        $pa->ai_checks          = $ai['checks'];
        $pa->ai_notes           = $ai['notes'];
        $pa->ai_last_checked_at = now();
        $pa->photo_status       = $photoStatus;
        $pa->rejection_reason   = $ai['status'] === 'failed'
            ? $this->composeFailedMessage($checksSpec, $ai['failed'])
            : null;
        $pa->save();

        // ---------- 8) Recalc status modul, notifikasi, audit ----------
        $this->recalcModule($reffId, $moduleSlug);

        $this->createAuditLog($uid, 'ai_validation_completed', 'PhotoApproval', $pa->id, $reffId, [
            'module'      => $moduleKey,
            'slot'        => $slotKey,
            'result'      => $ai['status'],
            'ai_score'    => $ai['score'],
            'failed'      => $ai['failed'],
            'photoStatus' => $photoStatus,
        ]);

        if ($ai['status'] === 'passed') {
            // opsi: kirim notifikasi ke Tracer bahwa ada foto pending
            try { $this->notificationService->notifyTracerPhotoPending($reffId, $moduleSlug); } catch (\Throwable) {}
        } else {
            // beri tahu alasan ke channel
            try {
                $msg = $pa->rejection_reason ?: 'AI validation failed';
                $this->handlePhotoRejection($pa, 'AI System', $msg);
            } catch (\Throwable) {}
        }

        // ---------- 9) Return payload ke controller ----------
        if (!empty($ai['failed'])) {
            return [
                'success'        => false,
                'message'        => $pa->rejection_reason,
                'failed_checks'  => $ai['failed'],
                'preview_url'    => $up['url'] ?? '',
                'file_storage_id'=> $up['file_storage_id'] ?? null,
                'ai_status'      => $ai['status'],
                'ai_checks'      => $ai['checks'],
                'module'         => $moduleKey,
                'reff_id'        => $reffId,
                'slot'           => $slotKey,
            ];
        }

        return [
            'success'        => true,
            'photo_id'       => $pa->id,
            'ai_status'      => $pa->ai_status,
            'ai_score'       => $pa->ai_score,
            'ai_notes'       => $pa->ai_notes,
            'checks'         => $pa->ai_checks,
            'preview_url'    => $up['url'] ?? '',
            'file'           => ['disk' => $up['disk'] ?? null, 'path' => $up['path'] ?? null, 'drive_file_id' => $up['drive_file_id'] ?? null],
            'module'         => $moduleKey,
            'reff_id'        => $reffId,
            'slot'           => $slotKey,
        ];
    }

    /* =============================================
     |    APPROVAL FLOW (TRACER & CGP) – tetap
     * ===========================================*/
    public function approveByTracer(int $photoApprovalId, int $userId, ?string $notes = null): PhotoApproval
    {
        DB::beginTransaction();
        try {
            $pa = PhotoApproval::findOrFail($photoApprovalId);
            $user = User::findOrFail($userId);

            if (!$user->isTracer() && !$user->isAdmin()) throw new Exception('Unauthorized: Only Tracer or Admin can approve photos');
            if ($pa->photo_status !== 'tracer_pending') throw new Exception("Photo is not in tracer_pending status. Current: {$pa->photo_status}");

            $old = $pa->photo_status;
            $pa->update([
                'tracer_user_id'     => $userId,
                'tracer_approved_at' => now(),
                'tracer_notes'       => $notes,
                'photo_status'       => 'cgp_pending',
            ]);

            $this->createAuditLog($userId, 'tracer_approved', 'PhotoApproval', $pa->id, $pa->reff_id_pelanggan, [
                'old_status' => $old, 'new_status' => 'cgp_pending', 'notes' => $notes
            ]);

            $this->notificationService->notifyAdminCgpReview($pa->reff_id_pelanggan, $pa->module_name);
            $this->telegramService->sendModuleStatusAlert($pa->reff_id_pelanggan, $pa->module_name, 'tracer_review', 'cgp_pending', $user->full_name);

            DB::commit();
            $this->recalcModule($pa->reff_id_pelanggan, $pa->module_name);

            return $pa->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('approveByTracer failed', ['id' => $photoApprovalId, 'err' => $e->getMessage()]);
            throw $e;
        }
    }

    public function rejectByTracer(int $photoApprovalId, int $userId, string $reason): PhotoApproval
    {
        DB::beginTransaction();
        try {
            $pa = PhotoApproval::findOrFail($photoApprovalId);
            $user = User::findOrFail($userId);

            if (!$user->isTracer() && !$user->isAdmin()) throw new Exception('Unauthorized: Only Tracer or Admin can reject photos');
            if ($pa->photo_status !== 'tracer_pending') throw new Exception("Photo is not in tracer_pending status. Current: {$pa->photo_status}");

            $old = $pa->photo_status;
            $pa->update([
                'tracer_user_id'     => $userId,
                'tracer_approved_at' => null,
                'tracer_notes'       => $reason,
                'photo_status'       => 'tracer_rejected',
                'rejection_reason'   => $reason,
            ]);

            $this->createAuditLog($userId, 'tracer_rejected', 'PhotoApproval', $pa->id, $pa->reff_id_pelanggan, [
                'old_status' => $old, 'new_status' => 'tracer_rejected', 'rejection_reason' => $reason
            ]);

            $this->handlePhotoRejection($pa, $user->full_name, $reason);

            DB::commit();
            $this->recalcModule($pa->reff_id_pelanggan, $pa->module_name);

            return $pa->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('rejectByTracer failed', ['id' => $photoApprovalId, 'err' => $e->getMessage()]);
            throw $e;
        }
    }

    public function approveByCgp(int $photoApprovalId, int $userId, ?string $notes = null): PhotoApproval
    {
        DB::beginTransaction();
        try {
            $pa = PhotoApproval::findOrFail($photoApprovalId);
            $user = User::findOrFail($userId);

            if (!$user->isAdmin()) throw new Exception('Unauthorized: Only Admin can perform CGP approval');
            if ($pa->photo_status !== 'cgp_pending') throw new Exception("Photo is not in cgp_pending status. Current: {$pa->photo_status}");

            $old = $pa->photo_status;
            $pa->update([
                'cgp_user_id'     => $userId,
                'cgp_approved_at' => now(),
                'cgp_notes'       => $notes,
                'photo_status'    => 'cgp_approved',
            ]);

            $this->createAuditLog($userId, 'cgp_approved', 'PhotoApproval', $pa->id, $pa->reff_id_pelanggan, [
                'old_status' => $old, 'new_status' => 'cgp_approved', 'notes' => $notes
            ]);

            $this->checkModuleCompletion($pa->reff_id_pelanggan, $pa->module_name);
            $this->notificationService->notifyPhotoApproved($pa->reff_id_pelanggan, $pa->module_name, $pa->photo_field_name);
            $this->telegramService->sendModuleStatusAlert($pa->reff_id_pelanggan, $pa->module_name, 'cgp_review', 'completed', $user->full_name);

            DB::commit();
            $this->recalcModule($pa->reff_id_pelanggan, $pa->module_name);

            return $pa->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('approveByCgp failed', ['id' => $photoApprovalId, 'err' => $e->getMessage()]);
            throw $e;
        }
    }

    public function rejectByCgp(int $photoApprovalId, int $userId, string $reason): PhotoApproval
    {
        DB::beginTransaction();
        try {
            $pa = PhotoApproval::findOrFail($photoApprovalId);
            $user = User::findOrFail($userId);

            if (!$user->isAdmin()) throw new Exception('Unauthorized: Only Admin can perform CGP rejection');
            if ($pa->photo_status !== 'cgp_pending') throw new Exception("Photo is not in cgp_pending status. Current: {$pa->photo_status}");

            $old = $pa->photo_status;
            $pa->update([
                'cgp_user_id'     => $userId,
                'cgp_approved_at' => null,
                'cgp_notes'       => $reason,
                'photo_status'    => 'cgp_rejected',
                'rejection_reason'=> $reason,
            ]);

            $this->createAuditLog($userId, 'cgp_rejected', 'PhotoApproval', $pa->id, $pa->reff_id_pelanggan, [
                'old_status' => $old, 'new_status' => 'cgp_rejected', 'rejection_reason' => $reason
            ]);

            $this->handlePhotoRejection($pa, $user->full_name.' (CGP)', $reason);

            DB::commit();
            $this->recalcModule($pa->reff_id_pelanggan, $pa->module_name);

            return $pa->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('rejectByCgp failed', ['id' => $photoApprovalId, 'err' => $e->getMessage()]);
            throw $e;
        }
    }

    /* ===================== Batch helper (tetap) ===================== */
    public function batchProcessPhotos(array $photoIds, string $action, int $actorUserId, array $payload = []): array
    {
        $results = [];
        $ok = 0;
        $notes  = $payload['notes']  ?? null;
        $reason = $payload['reason'] ?? null;

        foreach ($photoIds as $id) {
            try {
                switch ($action) {
                    case 'tracer_approve':
                        $this->approveByTracer((int)$id, $actorUserId, $notes);
                        $results[] = ['id' => (int)$id, 'success' => true, 'message' => 'Tracer approved'];
                        $ok++;
                        break;

                    case 'tracer_reject':
                        $this->rejectByTracer((int)$id, $actorUserId, (string)($reason ?: 'Rejected by Tracer'));
                        $results[] = ['id' => (int)$id, 'success' => true, 'message' => 'Tracer rejected'];
                        $ok++;
                        break;

                    case 'cgp_approve':
                        $this->approveByCgp((int)$id, $actorUserId, $notes);
                        $results[] = ['id' => (int)$id, 'success' => true, 'message' => 'CGP approved'];
                        $ok++;
                        break;

                    case 'cgp_reject':
                        $this->rejectByCgp((int)$id, $actorUserId, (string)($reason ?: 'Rejected by CGP'));
                        $results[] = ['id' => (int)$id, 'success' => true, 'message' => 'CGP rejected'];
                        $ok++;
                        break;

                    default:
                        throw new Exception('Unsupported action: '.$action);
                }
            } catch (\Throwable $e) {
                $results[] = ['id' => (int)$id, 'success' => false, 'message' => $e->getMessage()];
            }
        }

        return [
            'total'      => count($photoIds),
            'successful' => $ok,
            'failed'     => count($photoIds) - $ok,
            'results'    => $results,
        ];
    }

    public function storeWithoutAi(
        string $module,             // 'SK' | 'SR'
        string $reffId,
        string $slotIncoming,
        UploadedFile $file,
        ?int $uploadedBy = null,
        ?string $targetFileName = null,
        array $precheck = [         // dari frontend (hasil precheck)
            'ai_passed'  => null,   // bool
            'ai_score'   => null,   // float|null
            'ai_objects' => [],     // array of string
            'ai_notes'   => [],     // array of string
        ],
        array $meta = []            // ex: ['customer_name' => '...']
    ): array {
        $moduleKey  = strtoupper($module);
        $moduleSlug = strtolower($moduleKey);
        $uid        = (int) ($uploadedBy ?? Auth::id());

        // 1) Baca config & normalisasi slot
        $cfg     = (array) config('aergas_photos', []);
        $aliases = (array) ($cfg['aliases'][$moduleKey] ?? []);
        $slotKey = $aliases[$slotIncoming] ?? $slotIncoming;

        $slotCfg = $cfg['modules'][$moduleKey]['slots'][$slotKey] ?? null;
        if (!$slotCfg) {
            return ['success' => false, 'message' => "Slot tidak dikenal: {$slotIncoming} → {$slotKey}"];
        }

        // 2) (opsional) hapus foto lama di slot yang sama
        $replaceSameSlot = (bool) ($cfg['modules'][$moduleKey]['replace_same_slot'] ?? true);
        $uploader = $this->uploader ?? app(\App\Services\FileUploadService::class);
        if ($replaceSameSlot) {
            try { $uploader->deleteExistingPhoto($reffId, $moduleKey, $slotKey); }
            catch (\Throwable $e) { Log::info('deleteExistingPhoto non-fatal', ['err' => $e->getMessage()]); }
        }

        // 3) Upload ke Drive (pakai nama target bila diberikan)
        $customerName = $meta['customer_name'] ?? $this->getCustomerName($reffId);

        // ==== Penting: pastikan FileUploadService mendukung target filename ====
        // Opsi A (disarankan): tambahkan argumen opsional $options = ['target_name' => $targetFileName]
        $up = $uploader->uploadPhoto(
            file:         $file,
            reffId:       $reffId,
            module:       $moduleKey,
            fieldName:    $slotKey,
            uploadedBy:   $uid,
            customerName: $customerName,
            options:      ['target_name' => $targetFileName] // ← tambahkan di service uploader
        );

        if (!$up || empty($up['url'])) {
            return ['success' => false, 'message' => 'Upload gagal'];
        }

        // 4) Simpan/Update PhotoApproval (pakai hasil precheck dari client)
        $aiPassed  = (bool) ($precheck['ai_passed'] ?? false);
        $aiScore   = $precheck['ai_score'] ?? null;
        $aiObjects = $precheck['ai_objects'] ?? [];
        $aiNotes   = $precheck['ai_notes'] ?? [];

        // Susun struktur ai_checks minimal (boleh menyesuaikan kebutuhan)
        $aiChecks = [
            'detected' => array_values($aiObjects),
            'source'   => 'precheck-client',
        ];

        $photoStatus = $aiPassed ? 'tracer_pending' : 'ai_rejected';
        $rejection   = $aiPassed ? null : 'Precheck AI tidak lulus. Periksa catatan.';

        $pa = PhotoApproval::updateOrCreate(
            [
                'reff_id_pelanggan' => $reffId,
                'module_name'       => $moduleSlug,
                'photo_field_name'  => $slotKey,
            ],
            [
                'photo_url'     => $up['url'] ?? '',
                'storage_disk'  => $up['disk'] ?? config('filesystems.default','public'),
                'storage_path'  => $up['path'] ?? '',
                'drive_file_id' => $up['drive_file_id'] ?? null,
                'drive_link'    => $up['drive_link'] ?? null,
                'uploaded_by'   => $uid,
                'uploaded_at'   => now(),
                'ai_status'     => $aiPassed ? 'passed' : 'failed',
                'ai_score'      => $aiScore,
                'ai_checks'     => $aiChecks,
                'ai_notes'      => $aiNotes,
                'ai_last_checked_at' => now(),
                'photo_status'  => $photoStatus,
                'rejection_reason' => $rejection,
            ]
        );

        // Update FileStorage.ai_status (kalau ada)
        try {
            if (!empty($up['file_storage_id'])) {
                $fs = \App\Models\FileStorage::find($up['file_storage_id']);
                if ($fs) {
                    $fs->ai_status = $aiPassed ? 'passed' : 'failed';
                    $fs->save();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('file_storage update ai_status failed: '.$e->getMessage());
        }

        // 5) Recalc status modul & audit
        $this->recalcModule($reffId, $moduleSlug);
        $this->createAuditLog($uid, 'upload_saved_without_ai', 'PhotoApproval', $pa->id, $reffId, [
            'module'      => $moduleKey,
            'slot'        => $slotKey,
            'from'        => 'precheck-only',
            'ai_passed'   => $aiPassed,
            'ai_score'    => $aiScore,
            'objects'     => $aiObjects,
        ]);

        // Notifikasi ringan (opsional)
        if ($aiPassed) {
            try { $this->notificationService->notifyTracerPhotoPending($reffId, $moduleSlug); } catch (\Throwable) {}
        } else {
            try { $this->handlePhotoRejection($pa, 'Precheck', $rejection ?? 'AI gagal'); } catch (\Throwable) {}
        }

        return [
            'success'        => true,
            'photo_id'       => $pa->id,
            'ai_status'      => $pa->ai_status,
            'ai_score'       => $pa->ai_score,
            'ai_notes'       => $pa->ai_notes,
            'checks'         => $pa->ai_checks,
            'preview_url'    => $up['url'] ?? '',
            'file'           => ['disk' => $up['disk'] ?? null, 'path' => $up['path'] ?? null, 'drive_file_id' => $up['drive_file_id'] ?? null],
            'module'         => $moduleKey,
            'reff_id'        => $reffId,
            'slot'           => $slotKey,
        ];
    }


    /* ===================== Stats (tetap) ===================== */
    public function getPhotoApprovalStats(array $filters = []): array
    {
        $q = PhotoApproval::query();

        $module = $filters['module_name'] ?? $filters['module'] ?? null;
        if (!empty($module)) $q->where('module_name', $module);
        if (!empty($filters['status'])) $q->where('photo_status', $filters['status']);
        if (!empty($filters['reff_id_pelanggan'])) $q->where('reff_id_pelanggan', 'like', '%'.$filters['reff_id_pelanggan'].'%');

        $from = !empty($filters['date_from']) ? Carbon::parse($filters['date_from'])->startOfDay() : null;
        $to   = !empty($filters['date_to'])   ? Carbon::parse($filters['date_to'])->endOfDay()   : null;
        if ($from) $q->where('created_at', '>=', $from);
        if ($to)   $q->where('created_at', '<=', $to);

        $total          = (clone $q)->count();
        $pendingAi      = (clone $q)->where('photo_status', 'ai_pending')->count();
        $tracerPending  = (clone $q)->where('photo_status', 'tracer_pending')->count();
        $cgpPending     = (clone $q)->where('photo_status', 'cgp_pending')->count();
        $approved       = (clone $q)->where('photo_status', 'cgp_approved')->count();
        $rejected       = (clone $q)->whereIn('photo_status', ['ai_rejected','tracer_rejected','cgp_rejected'])->count();
        $avgConfidence  = round((float) (clone $q)->avg('ai_score'), 2);
        $todayCompleted = (clone $q)->where('photo_status', 'cgp_approved')->whereDate('cgp_approved_at', Carbon::today())->count();

        $byStatus = (clone $q)->select('photo_status', DB::raw('COUNT(*) as c'))
            ->groupBy('photo_status')->pluck('c','photo_status')->toArray();

        $byModule = (clone $q)->select('module_name', DB::raw('COUNT(*) as c'))
            ->groupBy('module_name')->pluck('c','module_name')->toArray();

        // SLA sederhana (bisa dihubungkan ke config)
        $slaTracerViolation = (clone $q)->where('photo_status','tracer_pending')
            ->where('ai_last_checked_at','<', Carbon::now()->subHours(24))->count();

        $slaTracerWarning = (clone $q)->where('photo_status','tracer_pending')
            ->where('ai_last_checked_at','<', Carbon::now()->subHours(20))
            ->where('ai_last_checked_at','>=', Carbon::now()->subHours(24))->count();

        $slaCgpViolation = (clone $q)->where('photo_status','cgp_pending')
            ->where('tracer_approved_at','<', Carbon::now()->subHours(48))->count();

        $slaCgpWarning = (clone $q)->where('photo_status','cgp_pending')
            ->where('tracer_approved_at','<', Carbon::now()->subHours(40))
            ->where('tracer_approved_at','>=', Carbon::now()->subHours(48))->count();

        return [
            'summary' => [
                'total'            => $total,
                'pending_ai'       => $pendingAi,
                'tracer_pending'   => $tracerPending,
                'cgp_pending'      => $cgpPending,
                'approved'         => $approved,
                'rejected'         => $rejected,
                'avg_ai_confidence'=> $avgConfidence,
                'today_completed'  => $todayCompleted,
            ],
            'by_status' => $byStatus,
            'by_module' => $byModule,
            'sla' => [
                'tracer' => ['violations' => $slaTracerViolation, 'warnings' => $slaTracerWarning, 'limit_hours' => 24],
                'cgp'    => ['violations' => $slaCgpViolation,    'warnings' => $slaCgpWarning,    'limit_hours' => 48],
                'total_violations' => $slaTracerViolation + $slaCgpViolation,
                'total_warnings'   => $slaTracerWarning  + $slaCgpWarning,
            ],
            'filters_applied' => [
                'module' => $module,
                'status' => $filters['status'] ?? null,
                'reff_id_pelanggan' => $filters['reff_id_pelanggan'] ?? null,
                'date_from' => $from?->toDateString(),
                'date_to'   => $to?->toDateString(),
            ],
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }

    /* ===================== Util internal ===================== */

    /** Jalankan cek AI per-slot menggunakan OpenAIService::analyzeImageChecks */
    private function runAiChecks(string $imageUrl, array $checksSpec, string $slotKey, string $moduleKey): array
    {
        // Keyed-by-id
        $checksSpecKeyed = collect($checksSpec)->mapWithKeys(function ($c, $k) {
            if (is_string($k)) return [$k => (array)$c];
            $id = is_array($c) ? ($c['id'] ?? null) : null;
            return $id ? [$id => (array)$c] : [];
        })->all();

        if (empty($checksSpecKeyed)) {
            return ['status'=>'passed','score'=>100,'checks'=>[],'failed'=>[],'notes'=>['No rules']];
        }

        // PDF heuristik
        if (preg_match('/\.pdf(\?.*)?$/i', $imageUrl)) {
            $checks = collect(array_keys($checksSpecKeyed))->map(fn($id) => [
                'id'=>$id,'passed'=>true,'confidence'=>1.0,'reason'=>'pdf-auto-pass'
            ])->all();
            return ['status'=>'passed','score'=>100,'checks'=>$checks,'failed'=>[],'notes'=>['PDF auto-pass']];
        }

        if (!$this->openAIService) {
            $checks = collect(array_keys($checksSpecKeyed))->map(fn($id) => [
                'id'=>$id,'passed'=>true,'confidence'=>1.0,'reason'=>'ai-disabled'
            ])->all();
            return ['status'=>'passed','score'=>100,'checks'=>$checks,'failed'=>[],'notes'=>null];
        }

        // Payload AI harus indexed array
        $checksPayload = collect($checksSpecKeyed)->map(function ($cfg, $id) {
            $cfg = (array) $cfg; $cfg['id'] = $cfg['id'] ?? $id; return $cfg;
        })->values()->all();

        $res = $this->openAIService->analyzeImageChecks($imageUrl, $checksPayload, [
            'module' => strtoupper($moduleKey),
            'slot'   => $slotKey,
        ]);

        // Jika AI tak balikan per-check → lulus dengan warning agar tidak nge-block
        if (empty($res['checks'])) {
            $checks = collect(array_keys($checksSpecKeyed))->map(fn($id) => [
                'id'=>$id,'passed'=>true,'confidence'=>0.7,'reason'=>'no-ai-checks'
            ])->all();
            return [
                'status'=>'passed','score'=>70,'checks'=>$checks,'failed'=>[],
                'notes'=>['AI tidak mengembalikan hasil per-cek; melewati dengan peringatan.']
            ];
        }

        $checks = collect($res['checks'])->map(function ($c) {
            return [
                'id'         => (string) ($c['id'] ?? ''),
                'passed'     => (bool)   ($c['passed'] ?? false),
                'confidence' => (float)  ($c['confidence'] ?? 0),
                'reason'     => (string) ($c['reason'] ?? ''),
            ];
        })->values()->all();

        $failed = collect($checks)->where('passed', false)->pluck('id')->values()->all();
        $status = empty($failed) ? 'passed' : 'failed';
        $score  = count($checks) ? round(collect($checks)->avg('confidence') * 100) : 0;

        $notes  = $res['notes'] ?? null;
        if (is_string($notes)) $notes = [$notes];

        return compact('status','score','checks','failed','notes');
    }



    /** Susun kalimat ringkas dari daftar id cek yang gagal */
    private function composeFailedMessage(array $checksSpec, array $failedIds): string
    {
        $labels = collect($checksSpec)->keyBy('id')->map(fn($c) => $c['label'] ?? ($c['id'] ?? ''));
        $failedLabels = collect($failedIds)->map(fn($id) => (string) ($labels[$id] ?? $id))->values()->all();
        return 'Cek wajib belum terpenuhi: ' . implode(', ', $failedLabels);
    }

    private function handlePhotoRejection(PhotoApproval $pa, string $by, string $reason): void
    {
        try {
            $this->telegramService->sendPhotoRejectionAlert($pa->reff_id_pelanggan, $pa->module_name, $pa->photo_field_name, $by, $reason);
            $this->notificationService->notifyPhotoRejection($pa->reff_id_pelanggan, $pa->module_name, $pa->photo_field_name, $reason);
        } catch (\Throwable $e) {
            // non-fatal
        }
    }

    private function recalcModule(string $reffId, string $moduleSlug): void
    {
        try {
            $class = $this->resolveModuleModelClass($moduleSlug);
            if (!$class) return;

            $m = $class::where('reff_id_pelanggan', $reffId)->first();
            if ($m && method_exists($m, 'syncModuleStatusFromPhotos')) {
                $m->syncModuleStatusFromPhotos();
            }
        } catch (Exception $e) {
            Log::info('recalcModule soft-failed', ['err' => $e->getMessage()]);
        }
    }

    private function checkModuleCompletion(string $reffId, string $moduleSlug): void
    {
        try {
            $class = $this->resolveModuleModelClass($moduleSlug);
            if (!$class) return;

            $mod = $class::where('reff_id_pelanggan', $reffId)->first();
            if (!$mod || !method_exists($mod, 'getRequiredPhotos')) return;

            $required = (array) $mod->getRequiredPhotos();
            $done     = PhotoApproval::where('reff_id_pelanggan', $reffId)
                ->where('module_name', $moduleSlug)
                ->where('photo_status', 'cgp_approved')
                ->count();

            if ($done >= count($required)) {
                $mod->update(['module_status' => 'completed', 'overall_photo_status' => 'completed']);
                try { $this->notificationService->notifyModuleCompletion($reffId, $moduleSlug); } catch (\Throwable) {}
                $this->updateCustomerProgress($reffId, $moduleSlug);
            }
        } catch (Exception $e) {
            Log::error('checkModuleCompletion error', ['reff' => $reffId, 'module' => $moduleSlug, 'err' => $e->getMessage()]);
        }
    }

    private function updateCustomerProgress(string $reffId, string $completedModule): void
    {
        try {
            $c = CalonPelanggan::find($reffId);
            if (!$c) return;

            $next = [
                'sk' => 'sr',
                'sr' => 'mgrt',
                'mgrt' => 'gas_in',
                'gas_in' => 'jalur_pipa',
                'jalur_pipa' => 'penyambungan',
                'penyambungan' => 'done',
            ][$completedModule] ?? null;

            if ($next) {
                $c->update([
                    'progress_status' => $next,
                    'status' => $next === 'done' ? 'lanjut' : 'in_progress',
                ]);
            }
        } catch (Exception $e) {
            Log::warning('updateCustomerProgress failed', ['err' => $e->getMessage()]);
        }
    }

    private function resolveModuleModelClass(string $moduleSlug): ?string
    {
        return match (strtolower($moduleSlug)) {
            'sk'           => SkData::class,
            'sr'           => SrData::class,
            'mgrt'         => \App\Models\MgrtData::class,
            'gas_in'       => \App\Models\GasInData::class,
            'jalur_pipa'   => \App\Models\JalurPipaData::class,
            'penyambungan' => \App\Models\PenyambunganPipaData::class,
            default        => null,
        };
    }

    private function createAuditLog(
        ?int $userId, string $action, string $modelType, ?int $modelId, ?string $reffId, array $data = []
    ): void {
        try {
            AuditLog::create([
                'user_id'           => $userId,
                'action'            => $action,
                'model_type'        => $modelType,
                'model_id'          => $modelId,
                'reff_id_pelanggan' => $reffId,
                'old_values'        => $data['old_values'] ?? null,
                'new_values'        => $data,
                'ip_address'        => request()->ip(),
                'user_agent'        => request()->userAgent(),
                'description'       => ucwords(str_replace('_', ' ', $action)),
            ]);
        } catch (\Throwable) {
            // non-fatal
        }
    }

    private function getCustomerName(string $reffId): ?string
    {
        try {
            return CalonPelanggan::where('reff_id_pelanggan', $reffId)->value('nama_pelanggan');
        } catch (\Throwable) {
            return null;
        }
    }

    /* ===================== (Legacy) =====================
     * Method lama di code kamu seperti processAIValidation()/runAIValidation
     * sebenarnya sudah tergantikan oleh handleUploadAndValidate + runAiChecks.
     * Kalau masih ada yang manggil, disarankan dihapus atau diarahkan ke flow baru.
     * ==================================================== */

    // --- LEGACY STUB: supaya tidak memutus pemanggil lama ---
    public function processAIValidation(string $reffId, string $module, string $photoField, string $photoUrl, ?int $uploadedBy = null): PhotoApproval
    {
        // Arahkan ke flow baru: buat PA minimal lalu tandai failed + alasan
        return PhotoApproval::updateOrCreate(
            ['reff_id_pelanggan' => $reffId, 'module_name' => strtolower($module), 'photo_field_name' => $photoField],
            ['photo_url' => $photoUrl, 'photo_status' => 'ai_rejected', 'rejection_reason' => 'Use handleUploadAndValidate flow']
        );
    }
}
