<?php

namespace App\Services;

use App\Models\PhotoApproval;
use App\Models\CalonPelanggan;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\SrData; // perbaiki case (bukan SRData)
use App\Models\SkData;
use App\Models\GasInData;
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
                // Reset rejection fields when photo is replaced
                'tracer_rejected_at' => null,
                'tracer_user_id' => null,
                'tracer_approved_at' => null,
                'tracer_notes' => null,
                'cgp_approved_at' => null,
                'cgp_user_id' => null,
                'cgp_notes' => null,
                'rejection_reason' => null,
            ]
        );

        $ai = $this->runAiValidationWithPrompt($up['url'], $slotKey, $moduleKey);

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
        $pa->ai_score           = $ai['score'] / 100; // Store as decimal (0-1)
        $pa->ai_notes           = $ai['reason']; // Store the specific reason
        $pa->ai_checks          = $ai['checks'] ?? [];
        $pa->ai_last_checked_at = now();
        $pa->photo_status       = $photoStatus;
        $pa->rejection_reason   = $ai['status'] === 'failed' ? $ai['reason'] : null;
        $pa->save();

        // ---------- 8) Recalc status modul, notifikasi, audit ----------
        $this->recalcModule($reffId, $moduleSlug);

        $this->createAuditLog($uid, 'ai_validation_completed', 'PhotoApproval', $pa->id, $reffId, [
            'module'      => $moduleKey,
            'slot'        => $slotKey,
            'result'      => $ai['status'],
            'ai_score'    => $ai['score'],
            'ai_reason'   => $ai['reason'],
            'photoStatus' => $photoStatus,
        ]);

        if ($ai['status'] === 'passed') {
            try { $this->notificationService->notifyTracerPhotoPending($reffId, $moduleSlug); } catch (\Throwable) {}
        } else {
            try {
                $this->handlePhotoRejection($pa, 'AI System', $ai['reason']);
            } catch (\Throwable) {}
        }

        // ---------- 9) Return payload ke controller ----------
        if ($ai['status'] === 'failed') {
            return [
                'success'        => false,
                'message'        => $ai['reason'],
                'ai_status'      => $ai['status'],
                'ai_reason'      => $ai['reason'],
                'preview_url'    => $up['url'] ?? '',
                'file_storage_id'=> $up['file_storage_id'] ?? null,
                'module'         => $moduleKey,
                'reff_id'        => $reffId,
                'slot'           => $slotKey,
            ];
        }

        return [
            'success'        => true,
            'photo_id'       => $pa->id,
            'ai_status'      => $pa->ai_status,
            'ai_score'       => $pa->ai_score * 100, // Convert back to percentage
            'ai_reason'      => $pa->ai_notes,
            'preview_url'    => $up['url'] ?? '',
            'file'           => ['disk' => $up['disk'] ?? null, 'path' => $up['path'] ?? null, 'drive_file_id' => $up['drive_file_id'] ?? null],
            'module'         => $moduleKey,
            'reff_id'        => $reffId,
            'slot'           => $slotKey,
        ];
    }

    public function uploadDraftOnly(
        string $module,
        string $reffId,
        string $slotIncoming,
        UploadedFile $file,
        int $uploadedBy,
        ?string $targetFileName = null,
        array $meta = []
    ): array {
        $moduleKey = strtoupper($module);
        $moduleSlug = strtolower($moduleKey);

        $cfg = (array) config('aergas_photos', []);
        $aliases = (array) ($cfg['aliases'][$moduleKey] ?? []);
        $slotKey = $aliases[$slotIncoming] ?? $slotIncoming;

        $slotCfg = $cfg['modules'][$moduleKey]['slots'][$slotKey] ?? null;
        
        // Log detailed slot configuration for debugging
        Log::info('PhotoApprovalService uploadDraftOnly slot check', [
            'module' => $moduleKey,
            'slotIncoming' => $slotIncoming,
            'slotKey' => $slotKey,
            'slotCfg_exists' => !is_null($slotCfg),
            'available_slots' => array_keys($cfg['modules'][$moduleKey]['slots'] ?? []),
            'available_aliases' => $aliases
        ]);
        
        if (!$slotCfg) {
            Log::error('PhotoApprovalService slot not found', [
                'module' => $moduleKey,
                'slotIncoming' => $slotIncoming,
                'slotKey' => $slotKey,
                'available_slots' => array_keys($cfg['modules'][$moduleKey]['slots'] ?? [])
            ]);
            return ['success' => false, 'message' => "Slot tidak dikenal: {$slotIncoming} → {$slotKey}"];
        }

        $replaceSameSlot = (bool) ($cfg['modules'][$moduleKey]['replace_same_slot'] ?? true);
        $uploader = $this->uploader ?? app(FileUploadService::class);

        if ($replaceSameSlot) {
            try {
                $uploader->deleteExistingPhoto($reffId, $moduleKey, $slotKey);
            } catch (\Throwable $e) {
                Log::info('deleteExistingPhoto non-fatal', ['err' => $e->getMessage()]);
            }
        }

        $customerName = $meta['customer_name'] ?? $this->getCustomerName($reffId);
        $up = $uploader->uploadPhoto(
            file: $file,
            reffId: $reffId,
            module: $moduleKey,
            fieldName: $slotKey,
            uploadedBy: $uploadedBy,
            customerName: $customerName,
            options: ['target_name' => $targetFileName]
        );

        if (!$up || empty($up['url'])) {
            return ['success' => false, 'message' => 'Upload gagal'];
        }

        $pa = PhotoApproval::updateOrCreate(
            [
                'reff_id_pelanggan' => $reffId,
                'module_name' => $moduleSlug,
                'photo_field_name' => $slotKey,
            ],
            [
                'photo_url' => $up['url'] ?? '',
                'storage_disk' => $up['disk'] ?? config('filesystems.default', 'public'),
                'storage_path' => $up['path'] ?? '',
                'drive_file_id' => $up['drive_file_id'] ?? null,
                'drive_link' => $up['drive_link'] ?? null,
                'uploaded_by' => $uploadedBy,
                'uploaded_at' => now(),
                'ai_status' => 'pending',
                'photo_status' => 'draft',
                'ai_score' => null,
                'ai_checks' => null,
                'ai_notes' => null,
                'ai_last_checked_at' => null,
                'rejection_reason' => null,
            ]
        );

        if (!empty($up['file_storage_id'])) {
            try {
                $fs = \App\Models\FileStorage::find($up['file_storage_id']);
                if ($fs) {
                    $fs->ai_status = 'pending';
                    $fs->save();
                }
            } catch (\Throwable $e) {
                Log::warning('file_storage update ai_status failed: '.$e->getMessage());
            }
        }

        $this->createAuditLog($uploadedBy, 'draft_uploaded', 'PhotoApproval', $pa->id, $reffId, [
            'module' => $moduleKey,
            'slot' => $slotKey,
            'status' => 'draft',
            'filename' => $targetFileName,
        ]);

        return [
            'success' => true,
            'photo_id' => $pa->id,
            'url' => $up['url'] ?? '',
            'disk' => $up['disk'] ?? null,
            'path' => $up['path'] ?? null,
            'drive_file_id' => $up['drive_file_id'] ?? null,
            'drive_link' => $up['drive_link'] ?? null,
            'filename' => $targetFileName,
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

            if (!$user->isAdminLike()) throw new Exception('Unauthorized: Only Admin/Super Admin can perform CGP approval');
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

            if (!$user->isAdminLike()) throw new Exception('Unauthorized: Only Admin/Super Admin can perform CGP rejection');
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
            try {
                // GANTI BAGIAN INI:
                PhotoApproval::where([
                    'reff_id_pelanggan' => $reffId,
                    'module_name' => $moduleSlug,
                    'photo_field_name' => $slotKey,
                ])->delete();

                // Hapus file fisik
                $uploader->deleteExistingPhoto($reffId, $moduleKey, $slotKey);
            }
            catch (\Throwable $e) {
                Log::info('deleteExistingPhoto non-fatal', ['err' => $e->getMessage()]);
            }
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
        $aiReason  = $precheck['ai_reason'] ?? 'No reason provided'; // CHANGED: from ai_objects to ai_reason
        $aiNotes   = $precheck['ai_notes'] ?? [];

        // Susun struktur ai_checks minimal (boleh menyesuaikan kebutuhan)
        $aiChecks = [
            [
                'id' => $slotKey,
                'passed' => $aiPassed,
                'confidence' => ($aiScore ?? 0) / 100,
                'reason' => $aiReason
            ]
        ];

        $photoStatus = $aiPassed ? 'tracer_pending' : 'ai_rejected';
        $rejection   = $aiPassed ? null : $aiReason;

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
                'ai_score'      => ($aiScore ?? 0) / 100, // Store as decimal
                'ai_checks' => is_array($aiChecks) ? json_encode($aiChecks) : $aiChecks, // ✅ JSON string
                'ai_notes'      => $aiReason, // CHANGED: Store reason instead of notes array
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
            // 'objects'     => $aiObjects,
        ]);

        // Notifikasi ringan (opsional)
        if ($aiPassed) {
            try { $this->notificationService->notifyTracerPhotoPending($reffId, $moduleSlug); } catch (\Throwable) {}
        } else {
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
    private function runAiValidationWithPrompt(string $imageUrl, string $slotKey, string $moduleKey): array
    {
        try {
            // Get slot configuration and custom prompt
            $cfg = config('aergas_photos.modules.' . $moduleKey . '.slots.' . $slotKey);
            if (!$cfg || empty($cfg['prompt'])) {
                Log::warning('No custom prompt found for slot', ['module' => $moduleKey, 'slot' => $slotKey]);
                return [
                    'status' => 'failed',
                    'score' => 0,
                    'reason' => 'Prompt konfigurasi tidak ditemukan untuk slot ini',
                    'notes' => ['No prompt configured'],
                    'failed' => [$slotKey]
                ];
            }

            // PDF auto-pass (manual review required)
            if (preg_match('/\.pdf(\?.*)?$/i', $imageUrl)) {
                return [
                    'status' => 'passed',
                    'score' => 100,
                    'reason' => 'PDF file - manual review required',
                    'notes' => ['PDF auto-pass'],
                    'failed' => []
                ];
            }

            // Use OpenAI service with custom prompt
            $result = $this->openAIService->validatePhotoWithPrompt(
                imagePath: $imageUrl,
                customPrompt: $cfg['prompt'],
                context: [
                    'module' => $moduleKey,
                    'slot' => $slotKey,
                    'label' => $cfg['label'] ?? $slotKey
                ]
            );

            // Convert to expected format for compatibility
            return [
                'status' => $result['passed'] ? 'passed' : 'failed',
                'score' => round($result['confidence'] * 100), // Convert to percentage
                'reason' => $result['reason'],
                'notes' => [$result['reason']],
                'failed' => $result['passed'] ? [] : [$slotKey],
                'checks' => [
                    [
                        'id' => $slotKey,
                        'passed' => $result['passed'],
                        'confidence' => $result['confidence'],
                        'reason' => $result['reason']
                    ]
                ]
            ];

        } catch (Exception $e) {
            Log::error('AI validation with prompt failed', [
                'module' => $moduleKey,
                'slot' => $slotKey,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'failed',
                'score' => 0,
                'reason' => 'AI validation error: ' . $e->getMessage(),
                'notes' => ['AI validation error'],
                'failed' => [$slotKey]
            ];
        }
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
                'sr' => 'gas_in',
                'gas_in' => 'done',
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
            'gas_in'       => GasInData::class,
            'jalur_pipa'   => JalurPipaData::class,
            'penyambungan' => PenyambunganPipaData::class,
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


    // --- LEGACY STUB: supaya tidak memutus pemanggil lama ---
    public function processAIValidation(string $reffId, string $module, string $photoField, string $photoUrl, ?int $uploadedBy = null): PhotoApproval
    {
        // Arahkan ke flow baru: buat PA minimal lalu tandai failed + alasan
        return PhotoApproval::updateOrCreate(
            ['reff_id_pelanggan' => $reffId, 'module_name' => strtolower($module), 'photo_field_name' => $photoField],
            ['photo_url' => $photoUrl, 'photo_status' => 'ai_rejected', 'rejection_reason' => 'Use handleUploadAndValidate flow']
        );
    }

    /* =====================================================================================
     |  NEW METHODS FOR TRACER APPROVAL INTERFACE
     * ==================================================================================== */

    /**
     * Run AI Review for all photos in a module
     */
    public function runAIReviewForModule($moduleData, string $module): array
    {
        if (!$moduleData) {
            throw new Exception("Module data not found");
        }

        $photos = PhotoApproval::where('module_name', strtolower($module))
            ->where('reff_id_pelanggan', $moduleData->reff_id_pelanggan)
            ->whereNull('ai_approved_at')
            ->get();

        $results = [];
        $processed = 0;

        foreach ($photos as $photo) {
            try {
                // Run AI analysis on photo
                $aiResult = $this->runAIAnalysis($photo);
                
                $photo->update([
                    'ai_approved_at' => now(),
                    'ai_confidence_score' => $aiResult['confidence'] ?? 0,
                    'ai_notes' => $aiResult['notes'] ?? null,
                    'ai_analysis_result' => $aiResult['detailed_analysis'] ?? null
                ]);

                $results[] = [
                    'photo_id' => $photo->id,
                    'photo_type' => $photo->photo_field_name,
                    'success' => true,
                    'confidence' => $aiResult['confidence'] ?? 0,
                    'notes' => $aiResult['notes'] ?? null
                ];

                $processed++;

            } catch (Exception $e) {
                Log::error('AI Review failed for photo', [
                    'photo_id' => $photo->id,
                    'error' => $e->getMessage()
                ]);

                $results[] = [
                    'photo_id' => $photo->id,
                    'photo_type' => $photo->photo_field_name,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'total_photos' => $photos->count(),
            'processed' => $processed,
            'results' => $results
        ];
    }

    /**
     * Approve photo by tracer (new interface)
     */
    public function approvePhotoByTracer(PhotoApproval $photo, int $tracerId, ?string $notes = null): PhotoApproval
    {
        DB::beginTransaction();
        try {
            $tracer = User::findOrFail($tracerId);

            if (!$tracer->isTracer() && !$tracer->isSuperAdmin()) {
                throw new Exception('Unauthorized: Only Tracer can perform this action');
            }

            $oldStatus = $photo->photo_status;
            
            $photo->update([
                'tracer_user_id' => $tracerId,
                'tracer_approved_at' => now(),
                'tracer_notes' => $notes,
                'photo_status' => 'cgp_pending' // Change to cgp_pending so it appears in CGP queue
            ]);

            $this->createAuditLog($tracerId, 'tracer_approved', 'PhotoApproval', $photo->id, $photo->reff_id_pelanggan, [
                'old_status' => $oldStatus,
                'new_status' => 'cgp_pending',
                'notes' => $notes
            ]);

            DB::commit();
            
            // Update module status
            $this->recalcModule($photo->reff_id_pelanggan, $photo->module_name);
            
            return $photo->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Approve by tracer failed', [
                'photo_id' => $photo->id,
                'tracer_id' => $tracerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Reject photo by tracer (new interface)
     */
    public function rejectPhotoByTracer(PhotoApproval $photo, int $tracerId, ?string $notes = null): PhotoApproval
    {
        DB::beginTransaction();
        try {
            $tracer = User::findOrFail($tracerId);

            if (!$tracer->isTracer() && !$tracer->isSuperAdmin()) {
                throw new Exception('Unauthorized: Only Tracer can perform this action');
            }

            $oldStatus = $photo->photo_status;
            
            $photo->update([
                'tracer_user_id' => $tracerId,
                'tracer_approved_at' => null,
                'tracer_rejected_at' => now(),
                'tracer_notes' => $notes,
                'photo_status' => 'tracer_rejected',
                'rejection_reason' => $notes
            ]);

            $this->createAuditLog($tracerId, 'tracer_rejected', 'PhotoApproval', $photo->id, $photo->reff_id_pelanggan, [
                'old_status' => $oldStatus,
                'new_status' => 'tracer_rejected',
                'rejection_reason' => $notes
            ]);

            // Handle rejection (notifications, etc.)
            $this->handlePhotoRejection($photo, $tracer->full_name . ' (Tracer)', $notes ?? '');

            DB::commit();
            
            // Update module status
            $this->recalcModule($photo->reff_id_pelanggan, $photo->module_name);
            
            return $photo->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Reject by tracer failed', [
                'photo_id' => $photo->id,
                'tracer_id' => $tracerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Approve all photos in a module by tracer
     */
    public function approveModuleByTracer($moduleData, string $module, int $tracerId, ?string $notes = null): array
    {
        DB::beginTransaction();
        try {
            $tracer = User::findOrFail($tracerId);

            if (!$tracer->isTracer() && !$tracer->isSuperAdmin()) {
                throw new Exception('Unauthorized: Only Tracer can perform this action');
            }

            $photos = PhotoApproval::where('module_name', strtolower($module))
                ->where('reff_id_pelanggan', $moduleData->reff_id_pelanggan)
                ->whereNull('tracer_approved_at')
                ->get();

            if ($photos->count() === 0) {
                throw new Exception('No photos found to approve');
            }

            $approved = [];

            foreach ($photos as $photo) {
                $oldStatus = $photo->photo_status;
                
                $photo->update([
                    'tracer_user_id' => $tracerId,
                    'tracer_approved_at' => now(),
                    'tracer_notes' => $notes,
                    'photo_status' => 'cgp_pending' // Change to cgp_pending so it appears in CGP queue
                ]);

                $this->createAuditLog($tracerId, 'tracer_approved', 'PhotoApproval', $photo->id, $photo->reff_id_pelanggan, [
                    'old_status' => $oldStatus,
                    'new_status' => 'cgp_pending',
                    'notes' => $notes,
                    'batch_approval' => true
                ]);

                $approved[] = $photo->id;
            }

            // Update module status
            $moduleData->update([
                'tracer_approved_at' => now(),
                'tracer_approved_by' => $tracerId,
                'tracer_notes' => $notes,
                'module_status' => 'tracer_approved'
            ]);

            DB::commit();
            
            // Recalc overall status
            $this->recalcModule($moduleData->reff_id_pelanggan, $module);

            return [
                'approved_photos' => $approved,
                'total_approved' => count($approved),
                'module_status' => 'tracer_approved'
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Approve module by tracer failed', [
                'module' => $module,
                'module_id' => $moduleData->id ?? null,
                'tracer_id' => $tracerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Run AI analysis on a single photo
     */
    private function runAIAnalysis(PhotoApproval $photo): array
    {
        // This is a placeholder for actual AI analysis
        // You would integrate with your OpenAI service or other AI provider
        
        try {
            // Example AI analysis call
            $result = $this->openAIService->analyzePhoto($photo->photo_url, [
                'photo_type' => $photo->photo_field_name,
                'module_type' => $photo->module_name,
                'analysis_prompt' => $this->getAnalysisPrompt($photo->photo_field_name, $photo->module_name)
            ]);

            return [
                'confidence' => $result['confidence'] ?? 75,
                'notes' => $result['analysis'] ?? 'AI analysis completed',
                'detailed_analysis' => $result['detailed'] ?? null
            ];

        } catch (Exception $e) {
            Log::warning('AI Analysis failed, using fallback', [
                'photo_id' => $photo->id,
                'error' => $e->getMessage()
            ]);

            // Fallback analysis
            return [
                'confidence' => 70,
                'notes' => 'Photo appears valid (AI service unavailable)',
                'detailed_analysis' => null
            ];
        }
    }

    /**
     * Get analysis prompt for AI based on photo type and module
     */
    private function getAnalysisPrompt(string $photoType, string $moduleType): string
    {
        $prompts = [
            'sk' => [
                'foto_sebelum_pekerjaan' => 'Analyze if the photo shows the area before SK work begins, looking for gas pipes, safety conditions, and workspace clarity.',
                'foto_material_sk' => 'Verify that all SK materials are visible and properly arranged in the photo.',
                'foto_hasil_pekerjaan' => 'Check if the completed SK work meets installation standards and safety requirements.'
            ],
            'sr' => [
                'foto_sebelum_pekerjaan' => 'Analyze if the photo shows the area before SR work begins, checking for existing installations and work area.',
                'foto_material_sr' => 'Verify that all SR materials are visible and properly arranged in the photo.',
                'foto_hasil_pekerjaan' => 'Check if the completed SR work meets installation standards and connection requirements.'
            ],
            'gas_in' => [
                'foto_sebelum_pekerjaan' => 'Analyze if the photo shows the area before gas-in work begins.',
                'foto_material_gas_in' => 'Verify that all gas-in materials and equipment are visible in the photo.',
                'foto_hasil_pekerjaan' => 'Check if the completed gas-in work meets safety and operational standards.'
            ]
        ];

        return $prompts[$moduleType][$photoType] ?? "Analyze this {$photoType} photo for {$moduleType} module to ensure it meets quality and safety standards.";
    }
}
