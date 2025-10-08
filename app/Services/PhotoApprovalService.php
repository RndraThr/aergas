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
                // Reset ALL approval fields when photo is replaced
                'tracer_rejected_at' => null,
                'tracer_user_id' => null,
                'tracer_approved_at' => null,
                'tracer_notes' => null,
                'cgp_approved_at' => null,
                'cgp_user_id' => null,
                'cgp_notes' => null,
                'cgp_rejected_at' => null,
                'rejection_reason' => null,
                'ai_score' => null,
                'ai_checks' => null,
                'ai_notes' => null,
                'ai_last_checked_at' => null,
            ]
        );

        // Clear module approvals immediately after photo upload
        $this->clearModuleApprovals($reffId, $moduleSlug);

        // ---------- 7) Set photo status to tracer_pending (AI Review is optional) ----------
        // AI validation is now ON-DEMAND via tracer interface, not automatic
        $pa->photo_status = 'tracer_pending';
        $pa->save();

        // ---------- 8) Recalc status modul after upload ----------
        $this->recalcModule($reffId, $moduleSlug);

        $this->createAuditLog($uid, 'photo_uploaded', 'PhotoApproval', $pa->id, $reffId, [
            'module'      => $moduleKey,
            'slot'        => $slotKey,
            'photo_url'   => $up['url'] ?? '',
            'drive_id'    => $up['drive_file_id'] ?? null,
        ]);

        // Notify tracer that photo is ready for review
        try {
            $this->notificationService->notifyTracerPhotoPending($reffId, $moduleSlug);
        } catch (\Throwable) {}

        // ---------- 9) Return payload ke controller ----------
        return [
            'success'        => true,
            'message'        => "Foto {$slotCfg['label']} berhasil di-upload. Menunggu review tracer.",
            'photo_id'       => $pa->id,
            'photo_status'   => $pa->photo_status,
            'preview_url'    => $up['url'] ?? '',
            'file'           => ['disk' => $up['disk'] ?? null, 'path' => $up['path'] ?? null, 'drive_file_id' => $up['drive_file_id'] ?? null],
            'module'         => $moduleKey,
            'reff_id'        => $reffId,
            'slot'           => $slotKey,
            'slot_label'     => $slotCfg['label'],
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
                // Reset ALL approval fields when photo is replaced
                'tracer_rejected_at' => null,
                'tracer_user_id' => null,
                'tracer_approved_at' => null,
                'tracer_notes' => null,
                'cgp_approved_at' => null,
                'cgp_user_id' => null,
                'cgp_notes' => null,
                'cgp_rejected_at' => null,
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

        // Clear module approvals and recalc status when photo is re-uploaded
        $this->clearModuleApprovals($reffId, $moduleSlug);
        $this->recalcModule($reffId, $moduleSlug);

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

            // Skip notifications for jalur modules as they don't have customer association
            if (!in_array($pa->module_name, ['jalur_lowering', 'jalur_joint'])) {
                $this->notificationService->notifyAdminCgpReview($pa->reff_id_pelanggan, $pa->module_name);
                $this->telegramService->sendModuleStatusAlert($pa->reff_id_pelanggan, $pa->module_name, 'tracer_review', 'cgp_pending', $user->full_name);
            }

            DB::commit();
            // Skip recalc for jalur modules as they don't have customer association
            if (!in_array($pa->module_name, ['jalur_lowering', 'jalur_joint'])) {
                $this->recalcModule($pa->reff_id_pelanggan, $pa->module_name);
            }

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
            // Skip recalc for jalur modules as they don't have customer association
            if (!in_array($pa->module_name, ['jalur_lowering', 'jalur_joint'])) {
                $this->recalcModule($pa->reff_id_pelanggan, $pa->module_name);
            }

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

            if (!$user->isAdminLike() && !$user->isCgp()) throw new Exception('Unauthorized: Only Admin/Super Admin can perform CGP approval');
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

            // Skip completion check and notifications for jalur modules as they don't have customer association
            if (!in_array($pa->module_name, ['jalur_lowering', 'jalur_joint'])) {
                $this->checkModuleCompletion($pa->reff_id_pelanggan, $pa->module_name);
                $this->notificationService->notifyPhotoApproved($pa->reff_id_pelanggan, $pa->module_name, $pa->photo_field_name);
                $this->telegramService->sendModuleStatusAlert($pa->reff_id_pelanggan, $pa->module_name, 'cgp_review', 'completed', $user->full_name);

                // Update customer incremental progress after CGP approval
                $customer = CalonPelanggan::where('reff_id_pelanggan', $pa->reff_id_pelanggan)->first();
                if ($customer) {
                    $this->updateCustomerProgressIncremental($customer);
                }
            }

            DB::commit();
            // Skip recalc for jalur modules as they don't have customer association
            if (!in_array($pa->module_name, ['jalur_lowering', 'jalur_joint'])) {
                $this->recalcModule($pa->reff_id_pelanggan, $pa->module_name);
            }

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

            if (!$user->isAdminLike() && !$user->isCgp()) throw new Exception('Unauthorized: Only Admin/Super Admin can perform CGP rejection');
            if ($pa->photo_status !== 'cgp_pending') throw new Exception("Photo is not in cgp_pending status. Current: {$pa->photo_status}");

            $old = $pa->photo_status;
            $pa->update([
                'cgp_user_id'     => $userId,
                'cgp_approved_at' => null,
                'cgp_rejected_at' => now(),
                'cgp_notes'       => $reason,
                'photo_status'    => 'cgp_rejected',
                'rejection_reason'=> $reason,
            ]);

            $this->createAuditLog($userId, 'cgp_rejected', 'PhotoApproval', $pa->id, $pa->reff_id_pelanggan, [
                'old_status' => $old, 'new_status' => 'cgp_rejected', 'rejection_reason' => $reason
            ]);

            $this->handlePhotoRejection($pa, $user->full_name.' (CGP)', $reason);

            DB::commit();
            // Skip recalc for jalur modules as they don't have customer association
            if (!in_array($pa->module_name, ['jalur_lowering', 'jalur_joint'])) {
                $this->recalcModule($pa->reff_id_pelanggan, $pa->module_name);
            }

            return $pa->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('rejectByCgp failed', ['id' => $photoApprovalId, 'err' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Approve all cgp_pending photos for a module (CGP batch approval)
     */
    public function approveModuleByCgp($moduleData, string $module, int $cgpId, ?string $notes = null): array
    {
        DB::beginTransaction();
        try {
            $cgp = User::findOrFail($cgpId);

            if (!$cgp->isAdmin() && !$cgp->isSuperAdmin() && !$cgp->isCgp()) {
                throw new Exception('Unauthorized: Only CGP can perform this action');
            }

            // Get photos with cgp_pending status
            $photos = PhotoApproval::where('module_name', strtolower($module))
                ->where('reff_id_pelanggan', $moduleData->reff_id_pelanggan)
                ->where('photo_status', 'cgp_pending')
                ->get();

            if ($photos->count() === 0) {
                throw new Exception('Tidak ada foto yang perlu di-approve');
            }

            $approved = [];

            foreach ($photos as $photo) {
                $oldStatus = $photo->photo_status;

                $photo->update([
                    'cgp_user_id' => $cgpId,
                    'cgp_approved_at' => now(),
                    'cgp_notes' => $notes,
                    'photo_status' => 'cgp_approved'
                ]);

                $this->createAuditLog($cgpId, 'cgp_approved', 'PhotoApproval', $photo->id, $photo->reff_id_pelanggan, [
                    'old_status' => $oldStatus,
                    'new_status' => 'cgp_approved',
                    'notes' => $notes,
                    'batch_approval' => true
                ]);

                $approved[] = $photo->id;
            }

            // Update module-level CGP approval
            $moduleData->update([
                'cgp_approved_at' => now(),
                'cgp_approved_by' => $cgpId,
                'cgp_notes' => $notes,
            ]);

            // Update customer incremental progress
            $customer = $moduleData->calonPelanggan;
            if ($customer) {
                $this->updateCustomerProgressIncremental($customer);
            }

            DB::commit();

            // Recalc module status
            $this->recalcModule($moduleData->reff_id_pelanggan, $module);

            // Refresh module and customer data to get latest status
            $moduleData->refresh();
            $customer = $moduleData->calonPelanggan;
            if ($customer) {
                $customer->refresh();
            }

            return [
                'approved_photos' => $approved,
                'total_approved' => count($approved),
                'module_status' => $moduleData->module_status,
                'customer_progress_status' => $customer?->progress_status,
                'customer_status' => $customer?->status,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Approve module by CGP failed', [
                'module' => $module,
                'module_id' => $moduleData->id ?? null,
                'cgp_id' => $cgpId,
                'error' => $e->getMessage()
            ]);
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
                // Reset ALL approval fields when photo is replaced
                'tracer_rejected_at' => null,
                'tracer_user_id' => null,
                'tracer_approved_at' => null,
                'tracer_notes' => null,
                'cgp_approved_at' => null,
                'cgp_user_id' => null,
                'cgp_notes' => null,
                'cgp_rejected_at' => null,
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

        // 5) Clear module approvals when photo is re-uploaded
        $this->clearModuleApprovals($reffId, $moduleSlug);

        // 6) Recalc status modul & audit
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
            // Skip notifications for jalur modules as they don't have customer association
            if (!in_array($pa->module_name, ['jalur_lowering', 'jalur_joint'])) {
                $this->telegramService->sendPhotoRejectionAlert($pa->reff_id_pelanggan, $pa->module_name, $pa->photo_field_name, $by, $reason);
                $this->notificationService->notifyPhotoRejection($pa->reff_id_pelanggan, $pa->module_name, $pa->photo_field_name, $reason);
            }
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

    private function clearModuleApprovals(string $reffId, string $moduleSlug): void
    {
        try {
            $class = $this->resolveModuleModelClass($moduleSlug);
            if (!$class) return;

            $m = $class::where('reff_id_pelanggan', $reffId)->first();
            if ($m && method_exists($m, 'clearModuleApprovals')) {
                $m->clearModuleApprovals();
            }
        } catch (Exception $e) {
            Log::info('clearModuleApprovals soft-failed', ['err' => $e->getMessage()]);
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
                $mod->update([
                    'status' => 'completed',
                    'module_status' => 'completed',
                    'overall_photo_status' => 'completed'
                ]);
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
        $moduleKey = strtoupper($module);
        $moduleSlug = strtolower($moduleKey);
        $uid = (int) ($uploadedBy ?? Auth::id());

        // For JALUR modules, skip AI and go directly to tracer_pending
        if (in_array($moduleKey, ['JALUR_LOWERING', 'JALUR_JOINT'])) {
            return PhotoApproval::updateOrCreate(
                [
                    'reff_id_pelanggan' => $reffId,
                    'module_name' => $moduleSlug,
                    'photo_field_name' => $photoField,
                ],
                [
                    'photo_url' => $photoUrl,
                    'uploaded_by' => $uid,
                    'uploaded_at' => now(),
                    'ai_status' => 'passed', // Skip AI - direct pass
                    'photo_status' => 'tracer_pending', // Direct to tracer review
                    'ai_notes' => 'Jalur modules skip AI validation',
                    'ai_last_checked_at' => now(),
                ]
            );
        }

        // For other modules, run normal AI validation
        $pa = PhotoApproval::updateOrCreate(
            [
                'reff_id_pelanggan' => $reffId,
                'module_name' => $moduleSlug,
                'photo_field_name' => $photoField,
            ],
            [
                'photo_url' => $photoUrl,
                'uploaded_by' => $uid,
                'uploaded_at' => now(),
                'ai_status' => 'pending',
                'photo_status' => 'ai_pending',
            ]
        );

        // Run AI validation for non-jalur modules
        try {
            $ai = $this->runAiValidationWithPrompt($photoUrl, $photoField, $moduleKey);
            
            // Update PhotoApproval with AI result
            $photoStatus = $ai['status'] === 'passed' ? 'tracer_pending' : 'ai_rejected';
            $pa->update([
                'ai_status' => $ai['status'],
                'ai_score' => $ai['score'] / 100,
                'ai_notes' => $ai['reason'],
                'ai_checks' => $ai['checks'] ?? [],
                'ai_last_checked_at' => now(),
                'photo_status' => $photoStatus,
                'rejection_reason' => $ai['status'] === 'failed' ? $ai['reason'] : null,
            ]);
        } catch (\Exception $e) {
            Log::error("AI validation failed for {$moduleSlug}: " . $e->getMessage());
            // Keep as ai_pending if AI validation fails
        }

        return $pa;
    }

    /* =====================================================================================
     |  NEW METHODS FOR TRACER APPROVAL INTERFACE
     * ==================================================================================== */

    /**
     * Run AI Review for all photos in a module
     */
    /**
     * Run AI Review for module (on-demand, advisory only)
     * Does NOT change photo_status - only provides AI insights
     */
    public function runAIReviewForModule($moduleData, string $module): array
    {
        if (!$moduleData) {
            throw new Exception("Module data not found");
        }

        // Get all uploaded photos for this module (regardless of status)
        // AI Review is advisory, so we can analyze any uploaded photo
        $photos = PhotoApproval::where('module_name', strtolower($module))
            ->where('reff_id_pelanggan', $moduleData->reff_id_pelanggan)
            ->whereNotNull('photo_url') // Must have uploaded photo
            ->where('photo_url', '!=', '') // Must not be empty
            ->get();

        $results = [];
        $processed = 0;

        foreach ($photos as $photo) {
            try {
                // Run AI analysis on photo
                $aiResult = $this->runAIAnalysis($photo);

                // Update AI fields only - DO NOT change photo_status
                $photo->update([
                    'ai_status' => $aiResult['status'] ?? 'passed',
                    'ai_score' => ($aiResult['confidence'] ?? 70) / 100, // Store as decimal 0-1
                    'ai_notes' => $aiResult['notes'] ?? null,
                    'ai_checks' => $aiResult['checks'] ?? [],
                    'ai_last_checked_at' => now(),
                    'ai_approved_at' => now(),
                    'ai_confidence_score' => $aiResult['confidence'] ?? 0,
                    'ai_validation_result' => $aiResult['detailed_analysis'] ?? null, // Fixed: was ai_analysis_result
                    // photo_status remains unchanged - tracer still needs to decide
                ]);

                $results[] = [
                    'photo_id' => $photo->id,
                    'photo_field_name' => $photo->photo_field_name,
                    'success' => true,
                    'ai_status' => $aiResult['status'] ?? 'passed',
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
                    'photo_field_name' => $photo->photo_field_name,
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

            if (!$tracer->isAdmin() && !$tracer->isSuperAdmin() && !$tracer->isTracer()) {
                throw new Exception('Unauthorized: Only Admin can perform this action');
            }

            $oldStatus = $photo->photo_status;

            // Set to tracer_approved first (will be promoted to cgp_pending only if ALL required photos are uploaded and approved)
            $photo->update([
                'tracer_user_id' => $tracerId,
                'tracer_approved_at' => now(),
                'tracer_notes' => $notes,
                'photo_status' => 'tracer_approved'
            ]);

            $this->createAuditLog($tracerId, 'tracer_approved', 'PhotoApproval', $photo->id, $photo->reff_id_pelanggan, [
                'old_status' => $oldStatus,
                'new_status' => 'tracer_approved',
                'notes' => $notes
            ]);

            DB::commit();

            // Update module status and auto-promote to cgp_pending if all required photos are approved
            if (!in_array($photo->module_name, ['jalur_lowering', 'jalur_joint'])) {
                $this->recalcModule($photo->reff_id_pelanggan, $photo->module_name);

                // Check if ALL required photos are now tracer-approved and uploaded
                $moduleData = $this->getModuleDataByReffAndType($photo->reff_id_pelanggan, $photo->module_name);
                if ($moduleData) {
                    $this->promoteToCgpPendingIfReady($moduleData, $photo->module_name);

                    // Update customer progress_status if all photos approved
                    $this->updateCustomerProgressAfterTracerApproval($moduleData, $photo->module_name);
                }
            }

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

            if (!$tracer->isAdmin() && !$tracer->isSuperAdmin() && !$tracer->isTracer()) {
                throw new Exception('Unauthorized: Only Admin can perform this action');
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
            
            // Update module status (skip for jalur modules as they don't have customer association)
            if (!in_array($photo->module_name, ['jalur_lowering', 'jalur_joint'])) {
                $this->recalcModule($photo->reff_id_pelanggan, $photo->module_name);
            }
            
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

            if (!$tracer->isAdmin() && !$tracer->isSuperAdmin() && !$tracer->isTracer()) {
                throw new Exception('Unauthorized: Only Admin can perform this action');
            }

            // Get photos that need to be approved (not yet tracer-approved)
            $photos = PhotoApproval::where('module_name', strtolower($module))
                ->where('reff_id_pelanggan', $moduleData->reff_id_pelanggan)
                ->whereNull('tracer_approved_at')
                ->get();

            if ($photos->count() === 0) {
                throw new Exception('Semua foto sudah di-approve');
            }

            $approved = [];

            foreach ($photos as $photo) {
                $oldStatus = $photo->photo_status;

                $photo->update([
                    'tracer_user_id' => $tracerId,
                    'tracer_approved_at' => now(),
                    'tracer_notes' => $notes,
                    'photo_status' => 'tracer_approved' // Set to tracer_approved first
                ]);

                $this->createAuditLog($tracerId, 'tracer_approved', 'PhotoApproval', $photo->id, $photo->reff_id_pelanggan, [
                    'old_status' => $oldStatus,
                    'new_status' => 'tracer_approved',
                    'notes' => $notes,
                    'batch_approval' => true
                ]);

                $approved[] = $photo->id;
            }

            DB::commit();

            // Recalc module status
            $this->recalcModule($moduleData->reff_id_pelanggan, $module);

            // Auto-promote to cgp_pending if all required photos are now approved
            $this->promoteToCgpPendingIfReady($moduleData, $module);

            // Progress update removed - only CGP approval affects progress

            return [
                'approved_photos' => $approved,
                'total_approved' => count($approved),
                'module_status' => $moduleData->fresh()->module_status,
                'customer_progress_status' => $customer?->fresh()->progress_status
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
     * Reject entire module by tracer (for incomplete/missing required photos)
     */
    public function rejectModuleByTracer($moduleData, string $module, int $tracerId, ?string $notes = null): array
    {
        DB::beginTransaction();
        try {
            $tracer = User::findOrFail($tracerId);

            if (!$tracer->isAdmin() && !$tracer->isSuperAdmin() && !$tracer->isTracer()) {
                throw new Exception('Unauthorized: Only Admin/Tracer can perform this action');
            }

            // Mark module as rejected
            $moduleData->update([
                'tracer_approved_at' => null,
                'tracer_approved_by' => null,
                'tracer_notes' => $notes,
                'module_status' => 'rejected',
                'overall_photo_status' => 'rejected'
            ]);

            // Create audit log for module rejection
            $this->createAuditLog($tracerId, 'module_rejected_by_tracer', get_class($moduleData), $moduleData->id, $moduleData->reff_id_pelanggan, [
                'module' => $module,
                'reason' => 'incomplete_photos',
                'notes' => $notes
            ]);

            DB::commit();

            return [
                'success' => true,
                'module_status' => 'rejected',
                'message' => 'Module rejected due to incomplete/missing required photos'
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Reject module by tracer failed', [
                'module' => $module,
                'module_id' => $moduleData->id ?? null,
                'tracer_id' => $tracerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Auto-promote all photos to cgp_pending if ALL required photos are uploaded and tracer-approved
     */
    private function promoteToCgpPendingIfReady($moduleData, string $module): void
    {
        try {
            $requiredPhotos = $moduleData->getRequiredPhotos();

            // Get all photos for this module
            $photos = PhotoApproval::where('module_name', strtolower($module))
                ->where('reff_id_pelanggan', $moduleData->reff_id_pelanggan)
                ->get();

            // Check only REQUIRED photos
            $requiredPhotosUploaded = $photos->whereIn('photo_field_name', $requiredPhotos);

            // Get list of required photos that have tracer_approved_at (regardless of current status)
            $requiredPhotosWithTracerApproval = $requiredPhotosUploaded
                ->whereNotNull('tracer_approved_at')
                ->pluck('photo_field_name')
                ->toArray();

            // Check if ALL required photos are uploaded
            $uploadedRequiredPhotos = $requiredPhotosUploaded->pluck('photo_field_name')->toArray();
            $allRequiredUploaded = empty(array_diff($requiredPhotos, $uploadedRequiredPhotos));

            // Check if ALL required photos have tracer approval
            $allRequiredTracerApproved = empty(array_diff($requiredPhotos, $requiredPhotosWithTracerApproval));

            Log::info('promoteToCgpPendingIfReady check', [
                'module' => $module,
                'reff_id' => $moduleData->reff_id_pelanggan,
                'required_photos' => $requiredPhotos,
                'uploaded_required' => $uploadedRequiredPhotos,
                'tracer_approved_required' => $requiredPhotosWithTracerApproval,
                'all_uploaded' => $allRequiredUploaded,
                'all_tracer_approved' => $allRequiredTracerApproved,
            ]);

            if ($allRequiredUploaded && $allRequiredTracerApproved) {
                // Promote all tracer_approved photos to cgp_pending (including optional photos if any)
                $updated = PhotoApproval::where('module_name', strtolower($module))
                    ->where('reff_id_pelanggan', $moduleData->reff_id_pelanggan)
                    ->where('photo_status', 'tracer_approved')
                    ->update(['photo_status' => 'cgp_pending']);

                Log::info('Auto-promoted to cgp_pending', [
                    'module' => $module,
                    'reff_id' => $moduleData->reff_id_pelanggan,
                    'photos_updated' => $updated
                ]);

                // Recalc module status to update to cgp_review
                $this->recalcModule($moduleData->reff_id_pelanggan, $module);
            } else {
                Log::info('Not ready for cgp_pending promotion', [
                    'module' => $module,
                    'reff_id' => $moduleData->reff_id_pelanggan,
                    'reason' => !$allRequiredUploaded ? 'missing_uploads' : 'missing_approvals'
                ]);
            }
        } catch (Exception $e) {
            Log::warning('Failed to auto-promote to cgp_pending', [
                'module' => $module,
                'reff_id' => $moduleData->reff_id_pelanggan ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate incremental progress percentage based on CGP-approved photos
     * Total 15 required photos across all modules (SK=5, SR=6, Gas_In=4)
     * Each photo contributes: 100% / 15 = 6.67%
     */
    private function calculateIncrementalProgress(string $reffId): float
    {
        try {
            // Count total CGP-approved photos across all modules
            $cgpApprovedCount = PhotoApproval::where('reff_id_pelanggan', $reffId)
                ->where('photo_status', 'cgp_approved')
                ->whereIn('module_name', ['sk', 'sr', 'gas_in'])
                ->count();

            // Total required photos: SK(5) + SR(6) + Gas_In(4) = 15
            $totalRequiredPhotos = 15;

            // Calculate percentage (each photo = 6.67%)
            $progress = ($cgpApprovedCount / $totalRequiredPhotos) * 100;

            // Round to 2 decimal places
            return round($progress, 2);

        } catch (Exception $e) {
            Log::error('Failed to calculate incremental progress', [
                'reff_id' => $reffId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Update customer progress based on incremental photo approvals
     * Progress updates happen per CGP-approved photo
     * progress_status changes at milestones: 33.33% (SK done), 73.33% (SR done), 100% (Gas In done)
     */
    private function updateCustomerProgressIncremental(CalonPelanggan $customer): void
    {
        try {
            $reffId = $customer->reff_id_pelanggan;

            // Calculate current progress percentage
            $progressPercentage = $this->calculateIncrementalProgress($reffId);

            // Count approved photos per module
            $skApproved = PhotoApproval::where('reff_id_pelanggan', $reffId)
                ->where('module_name', 'sk')
                ->where('photo_status', 'cgp_approved')
                ->count();

            $srApproved = PhotoApproval::where('reff_id_pelanggan', $reffId)
                ->where('module_name', 'sr')
                ->where('photo_status', 'cgp_approved')
                ->count();

            $gasInApproved = PhotoApproval::where('reff_id_pelanggan', $reffId)
                ->where('module_name', 'gas_in')
                ->where('photo_status', 'cgp_approved')
                ->count();

            // Determine progress_status based on milestone completion
            $oldProgressStatus = $customer->progress_status;
            $newProgressStatus = $oldProgressStatus;

            // Milestones:
            // - SK complete (5/5 photos): progress_status = 'sr'  (33.33%)
            // - SR complete (6/6 photos): progress_status = 'gas_in' (73.33%)
            // - Gas In complete (4/4 photos): progress_status = 'done' (100%)

            if ($gasInApproved >= 4 && $srApproved >= 6 && $skApproved >= 5) {
                // All modules complete
                $newProgressStatus = 'done';
                $customer->status = 'lanjut'; // Mark as successfully completed
            } elseif ($srApproved >= 6 && $skApproved >= 5) {
                // SK and SR complete, working on Gas In
                $newProgressStatus = 'gas_in';
            } elseif ($skApproved >= 5) {
                // SK complete, working on SR
                $newProgressStatus = 'sr';
            } elseif ($customer->validated_at) {
                // Customer validated, working on SK
                $newProgressStatus = 'sk';
            } else {
                // Not yet validated
                $newProgressStatus = 'validasi';
            }

            // Update customer
            $customer->progress_status = $newProgressStatus;
            $customer->progress_percentage = $progressPercentage;
            $customer->save();

            // Log progress update
            if ($oldProgressStatus !== $newProgressStatus) {
                Log::info('Customer progress updated after CGP approval', [
                    'reff_id' => $reffId,
                    'old_progress_status' => $oldProgressStatus,
                    'new_progress_status' => $newProgressStatus,
                    'progress_percentage' => $progressPercentage,
                    'sk_approved' => $skApproved . '/5',
                    'sr_approved' => $srApproved . '/6',
                    'gas_in_approved' => $gasInApproved . '/4'
                ]);
            }

        } catch (Exception $e) {
            Log::error('Failed to update customer incremental progress', [
                'reff_id' => $customer->reff_id_pelanggan ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get module data by reff_id and module type
     */
    private function getModuleDataByReffAndType(string $reffId, string $module)
    {
        try {
            return match(strtolower($module)) {
                'sk' => \App\Models\SkData::where('reff_id_pelanggan', $reffId)->whereNull('deleted_at')->first(),
                'sr' => \App\Models\SrData::where('reff_id_pelanggan', $reffId)->whereNull('deleted_at')->first(),
                'gas_in' => \App\Models\GasInData::where('reff_id_pelanggan', $reffId)->whereNull('deleted_at')->first(),
                default => null
            };
        } catch (Exception $e) {
            Log::error('Failed to get module data', ['module' => $module, 'reff_id' => $reffId]);
            return null;
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
            // Get analysis prompt for this photo type
            $customPrompt = $this->getAnalysisPrompt($photo->photo_field_name, $photo->module_name);
            
            // Call OpenAI service with correct method
            $result = $this->openAIService->validatePhotoWithPrompt(
                $photo->photo_url,
                $customPrompt,
                [
                    'module' => $photo->module_name,
                    'slot' => $photo->photo_field_name
                ]
            );

            // Map OpenAI result to our format
            $score = isset($result['score']) ? $result['score'] : (($result['confidence'] ?? 0.75) * 100);

            return [
                'status' => $result['status'] ?? 'passed', // passed/failed
                'confidence' => $score,
                'notes' => $result['reason'] ?? 'AI analysis completed',
                'checks' => $result['checks'] ?? [],
                'detailed_analysis' => $result['raw_response'] ?? null
            ];

        } catch (Exception $e) {
            Log::warning('AI Analysis failed, using fallback', [
                'photo_id' => $photo->id,
                'error' => $e->getMessage()
            ]);

            // Fallback: assume passed with moderate confidence
            return [
                'status' => 'passed',
                'confidence' => 70,
                'notes' => 'AI service unavailable - manual review recommended',
                'checks' => [],
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
