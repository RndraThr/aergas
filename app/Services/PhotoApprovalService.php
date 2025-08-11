<?php

namespace App\Services;

use App\Models\PhotoApproval;
use App\Models\CalonPelanggan;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\SRData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use App\Services\PhotoRuleEvaluator;
use Illuminate\Support\Facades\Auth;
use App\Services\OpenAIService;


class PhotoApprovalService
{
    public function __construct(
        private TelegramService $telegramService,
        private OpenAIService $openAIService,
        private NotificationService $notificationService
    ) {}

    public function processAIValidation(
        string $reffId,
        string $module,
        string $photoField,
        string $photoUrl,
        ?int $uploadedBy = null
    ): PhotoApproval {
        DB::beginTransaction();
        try {
            $customer = CalonPelanggan::find($reffId);
            if (!$customer) throw new Exception("Customer not found: {$reffId}");

            // Konversi URL -> path relatif sesuai disk default
            $disk = config('filesystems.default', 'public');
            // untuk public, url biasanya /storage/{path}; kita buang prefix itu
            $relative = $this->urlToRelativePath($disk, $photoUrl);
            $fullPath = Storage::disk($disk)->path($relative);
            if (!file_exists($fullPath)) throw new Exception("Photo file not found: {$fullPath}");

            $pa = PhotoApproval::updateOrCreate(
                ['reff_id_pelanggan' => $reffId, 'module_name' => strtolower($module), 'photo_field_name' => $photoField],
                [
                    'photo_url'            => $photoUrl,
                    'photo_status'         => 'ai_pending',
                    'ai_confidence_score'  => null,
                    'ai_validation_result' => null,
                    'ai_approved_at'       => null,
                    'rejection_reason'     => null,
                ]
            );

            $this->createAuditLog($uploadedBy, 'ai_validation_started', 'PhotoApproval', $pa->id, $reffId, [
                'module' => $module, 'photo_field' => $photoField, 'status' => 'ai_pending',
            ]);

            DB::commit();

            $this->runAIValidation($pa, $fullPath);

            return $pa->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('AI validation init failed', ['reff_id' => $reffId, 'module' => $module, 'field' => $photoField, 'err' => $e->getMessage()]);
            return PhotoApproval::updateOrCreate(
                ['reff_id_pelanggan' => $reffId, 'module_name' => strtolower($module), 'photo_field_name' => $photoField],
                ['photo_url' => $photoUrl, 'photo_status' => 'ai_rejected', 'rejection_reason' => 'Technical error during AI validation: '.$e->getMessage()]
            );
        }
    }

    /**
     * Statistik umum photo approval dengan filter opsional.
     *
     * @param array $filters ['module'|'module_name' => string, 'status' => string, 'reff_id_pelanggan' => string, 'date_from' => Y-m-d, 'date_to' => Y-m-d]
     * @return array
     */
    public function getPhotoApprovalStats(array $filters = []): array
    {
        $q = PhotoApproval::query();

        // Terapkan filter
        $module = $filters['module_name'] ?? $filters['module'] ?? null;
        if (!empty($module)) {
            $q->where('module_name', $module);
        }
        if (!empty($filters['status'])) {
            $q->where('photo_status', $filters['status']);
        }
        if (!empty($filters['reff_id_pelanggan'])) {
            $q->where('reff_id_pelanggan', 'like', '%'.$filters['reff_id_pelanggan'].'%');
        }

        // rentang tanggal pakai created_at (bisa diubah sesuai kebutuhan)
        $from = !empty($filters['date_from']) ? Carbon::parse($filters['date_from'])->startOfDay() : null;
        $to   = !empty($filters['date_to'])   ? Carbon::parse($filters['date_to'])->endOfDay()   : null;
        if ($from) $q->where('created_at', '>=', $from);
        if ($to)   $q->where('created_at', '<=', $to);

        // ringkasan
        $total          = (clone $q)->count();
        $pendingAi      = (clone $q)->where('photo_status', 'ai_pending')->count();
        $tracerPending  = (clone $q)->where('photo_status', 'tracer_pending')->count();
        $cgpPending     = (clone $q)->where('photo_status', 'cgp_pending')->count();
        $approved       = (clone $q)->where('photo_status', 'cgp_approved')->count();
        $rejected       = (clone $q)->whereIn('photo_status', ['ai_rejected','tracer_rejected','cgp_rejected'])->count();
        $avgConfidence  = round((float) (clone $q)->avg('ai_confidence_score'), 2);
        $todayCompleted = (clone $q)->where('photo_status', 'cgp_approved')->whereDate('cgp_approved_at', Carbon::today())->count();

        // by status & module
        $byStatus = (clone $q)->select('photo_status', DB::raw('COUNT(*) as c'))
            ->groupBy('photo_status')->pluck('c','photo_status')->toArray();

        $byModule = (clone $q)->select('module_name', DB::raw('COUNT(*) as c'))
            ->groupBy('module_name')->pluck('c','module_name')->toArray();

        // SLA (menghormati filter dasar di atas)
        $slaTracerViolation = (clone $q)->where('photo_status','tracer_pending')
            ->where('ai_approved_at','<', Carbon::now()->subHours(24))->count();

        $slaTracerWarning = (clone $q)->where('photo_status','tracer_pending')
            ->where('ai_approved_at','<', Carbon::now()->subHours(20))
            ->where('ai_approved_at','>=', Carbon::now()->subHours(24))->count();

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

    /**
     * Orkestra: upload file → jalankan AI → simpan PhotoApproval → recalc status modul.
     *
     * @return array{
     *   success:bool, photo_id:int, ai_status:string, ai_score: int|null,
     *   ai_notes:?string, preview_url:string, file:array, module:string, reff_id:string
     * }
     */
    public function handleUploadAndValidate(
        string $module,            // 'SK' | 'SR'
        string $reffId,
        string $slotIncoming,      // boleh alias (mis. foto_pneumatic_start_sr_url)
        UploadedFile $file,
        ?int $userId = null
    ): array {
        $module = strtoupper($module);
        $uid    = (int) ($userId ?? Auth::id());

        // 1) ambil config sekali, map alias → slot key
        $cfg     = config('aergas_photos', []);
        $aliases = $cfg['aliases'][$module] ?? [];
        $slotKey = $aliases[$slotIncoming] ?? $slotIncoming;

        $rules = $cfg['modules'][$module]['slots'][$slotKey] ?? null;
        if (!$rules) {
            return ['success'=>false,'message'=>"Slot tidak dikenal: $slotIncoming → $slotKey"];
        }

        // 2) enforce dependency (contoh: SR.tapping_saddle butuh jenis_tapping)
        $requires = $rules['requires']['fields'] ?? [];
        if ($requires) {
            if ($module === 'SR') {
                $sr = SrData::where('reff_id_pelanggan', $reffId)->first();
                foreach ($requires as $fieldName) {
                    if (!$sr || is_null($sr->{$fieldName}) || $sr->{$fieldName} === '') {
                        return [
                            'success'=>false,
                            'message'=>"Field '$fieldName' wajib diisi sebelum upload foto '{$rules['label']}'."
                        ];
                    }
                }
            }
            // (kalau ada modul lain, tambahkan di sini)
        }

        // 3) upload file (Drive/lokal) via FileUploadService
        /** @var FileUploadService $uploader */
        $uploader = app(FileUploadService::class);
        $u = $uploader->uploadPhoto($file, $reffId, $module, $slotIncoming, $uid);
        // $u: ['url','disk','path','drive_file_id','drive_link']

        $disk = $u['disk'] ?? config('filesystems.default','public');
        $path = $u['path'] ?? null;
        if (!$path) throw new \RuntimeException('Upload gagal: path kosong');

        // 4) simpan/update PhotoApproval (kunci: reff + module + slotKey)
        $pa = PhotoApproval::updateOrCreate(
            [
                'reff_id_pelanggan' => $reffId,
                'module_name'       => strtolower($module),
                'photo_field_name'  => $slotKey,
            ],
            [
                'photo_url'    => $u['url'] ?? '',
                'storage_disk' => $disk,
                'storage_path' => $path,
                'drive_file_id'=> $u['drive_file_id'] ?? null,
                'drive_link'   => $u['drive_link']    ?? null,
                'uploaded_by'  => $uid,
                'uploaded_at'  => now(),
                'ai_status'    => 'pending',
            ]
        );

        // 5) jalankan AI (pakai OpenAIService yang sudah ada di service ini)
        $fullPath = Storage::disk($disk)->path($path);
        $aiRaw    = $this->openAIService->validatePhoto($fullPath, $slotKey, $module);

        // 6) normalisasi output AI → evaluator
        $ai = [
            'score'   => $aiRaw['confidence']       ?? null,
            'notes'   => $aiRaw['rejection_reason'] ?? null,
            'objects' => $aiRaw['objects']          ?? ($aiRaw['labels'] ?? []),
            'image'   => $aiRaw['image']            ?? [],
        ];
        if ($ai['objects'] && is_string($ai['objects'][0] ?? null)) {
            $ai['objects'] = array_map(fn($n)=>['name'=>$n,'confidence'=>null], $ai['objects']);
        }

        $verdict = app(PhotoRuleEvaluator::class)->evaluate($rules, $ai);

        // 7) persist hasil & recalc modul
        $pa->ai_status          = $verdict['status'];
        $pa->ai_score           = $verdict['score'];
        $pa->ai_checks          = $verdict['checks'];
        $pa->ai_notes           = $verdict['notes'];
        $pa->ai_last_checked_at = now();
        $pa->save();

        $this->recalcModule($reffId, $module);

        return [
            'success'      => true,
            'photo_id'     => $pa->id,
            'ai_status'    => $pa->ai_status,
            'ai_score'     => $pa->ai_score,
            'ai_notes'     => $pa->ai_notes,
            'checks'       => $pa->ai_checks,
            'preview_url'  => $u['url'] ?? '',
            'file'         => ['disk'=>$disk,'path'=>$path,'drive_file_id'=>$u['drive_file_id'] ?? null],
            'module'       => $module,
            'reff_id'      => $reffId,
            'slot'         => $slotKey,
        ];
    }



    private function runAIValidation(PhotoApproval $pa, string $fullPath): void
    {
        try {
            $ai = $this->openAIService->validatePhoto($fullPath, $pa->photo_field_name, $pa->module_name);

            $pa->update([
                'ai_confidence_score'  => $ai['confidence'] ?? null,
                'ai_validation_result' => $ai,
                'ai_approved_at'       => !empty($ai['validation_passed']) ? now() : null,
                'photo_status'         => !empty($ai['validation_passed']) ? 'tracer_pending' : 'ai_rejected',
                'rejection_reason'     => $ai['rejection_reason'] ?? null,
            ]);

            $this->recalcModule($pa->reff_id_pelanggan, $pa->module_name);

            if (!empty($ai['validation_passed'])) {
                $this->notificationService->notifyTracerPhotoPending($pa->reff_id_pelanggan, $pa->module_name);
            } else {
                $this->handlePhotoRejection($pa, 'AI System', $ai['rejection_reason'] ?? 'Failed AI validation');
            }

            $this->createAuditLog(null, 'ai_validation_completed', 'PhotoApproval', $pa->id, $pa->reff_id_pelanggan, [
                'result' => !empty($ai['validation_passed']) ? 'passed' : 'rejected',
                'confidence' => $ai['confidence'] ?? null,
                'reason' => $ai['rejection_reason'] ?? null,
            ]);
        } catch (Exception $e) {
            Log::error('AI validation exec failed', ['id' => $pa->id, 'err' => $e->getMessage()]);
            $pa->update(['photo_status' => 'ai_rejected', 'rejection_reason' => 'AI validation system error: '.$e->getMessage()]);
            $this->recalcModule($pa->reff_id_pelanggan, $pa->module_name);
        }
    }

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
                        throw new \Exception('Unsupported action: '.$action);
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

    // --- utils ---

    private function urlToRelativePath(string $disk, string $url): string
    {
        // untuk disk public, /storage/{path}; untuk disk lain, coba langsung treat sebagai path
        if ($disk === 'public' && str_starts_with($url, '/storage/')) {
            return ltrim(substr($url, strlen('/storage/')), '/');
        }
        // fallback: kalau URL adalah absolute path, coba trim sampai storage path
        return ltrim($url, '/');
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

    private function checkModuleCompletion(string $reffId, string $module): void
    {
        try {
            $class = $this->resolveModuleModelClass($module);
            if (!$class) return;

            /** @var \App\Models\BaseModuleModel|null $mod */
            $mod = $class::where('reff_id_pelanggan', $reffId)->first();
            if (!$mod) return;

            $required = $mod->getRequiredPhotos();
            $done = PhotoApproval::where('reff_id_pelanggan', $reffId)
                ->where('module_name', $module)
                ->where('photo_status', 'cgp_approved')
                ->count();

            if ($done >= count($required)) {
                $mod->update(['module_status' => 'completed', 'overall_photo_status' => 'completed']);
                $this->notificationService->notifyModuleCompletion($reffId, $module);
                $this->updateCustomerProgress($reffId, $module);
            }
        } catch (Exception $e) {
            Log::error('checkModuleCompletion error', ['reff' => $reffId, 'module' => $module, 'err' => $e->getMessage()]);
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

    private function resolveModuleModelClass(string $module): ?string
    {
        return match (strtolower($module)) {
            'sk'           => \App\Models\SkData::class,
            'sr'           => \App\Models\SrData::class,
            'mgrt'         => \App\Models\MgrtData::class,
            'gas_in'       => \App\Models\GasInData::class,
            'jalur_pipa'   => \App\Models\JalurPipaData::class,
            'penyambungan' => \App\Models\PenyambunganPipaData::class,
            default        => null,
        };
    }

    private function recalcModule(string $reffId, string $module): void
    {
        try {
            $class = $this->resolveModuleModelClass($module);
            if (!$class) return;

            /** @var \App\Models\BaseModuleModel|null $m */
            $m = $class::where('reff_id_pelanggan', $reffId)->first();
            if ($m) $m->syncModuleStatusFromPhotos();
        } catch (Exception $e) {
            Log::info('recalcModule soft-failed', ['err' => $e->getMessage()]);
        }
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
}
