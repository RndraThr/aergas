<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\OpenAIService;
use App\Services\PhotoRuleEvaluator;
use App\Models\SkData;
use App\Services\PhotoApprovalService;
use App\Services\BeritaAcaraService;

class SkDataController extends Controller
{
    public function __construct(
        private ?PhotoApprovalService $photoSvc = null,
    ) {}

    public function index(Request $r)
    {
        $q = SkData::with('calonPelanggan')
            ->withCount([
                'photoApprovals as rejected_photos_count' => function($query) {
                    $query->where(function($q) {
                        $q->whereNotNull('tracer_rejected_at')
                          ->orWhereNotNull('cgp_rejected_at');
                    });
                }
            ])
            ->latest('id');

        if ($r->filled('q')) {
            $term = trim((string) $r->get('q'));
            $q->where(function($w) use ($term) {
                $w->where('reff_id_pelanggan','like',"%{$term}%")
                  ->orWhere('status','like',"%{$term}%")
                  ->orWhere('module_status','like',"%{$term}%");
            });
        }

        if ($r->filled('module_status')) {
            $q->where('module_status', $r->get('module_status'));
        }

        if ($r->filled('tanggal_dari')) {
            $q->whereDate('tanggal_instalasi', '>=', $r->get('tanggal_dari'));
        }

        if ($r->filled('tanggal_sampai')) {
            $q->whereDate('tanggal_instalasi', '<=', $r->get('tanggal_sampai'));
        }

        $sk = $q->paginate((int) $r->get('per_page', 15))->withQueryString();

        // Load related data for each SK
        $sk->load('createdBy:id,name', 'photoApprovals');

        // Add photo status details for each SK
        $sk->getCollection()->transform(function ($item) {
            $item->photo_status_details = $this->getPhotoStatusDetails($item);
            return $item;
        });

        if ($r->wantsJson() || $r->ajax()) {
            // Calculate stats
            $allSk = SkData::all();
            $stats = [
                'total' => $sk->total(),
                'draft' => $allSk->where('module_status', 'draft')->count(),
                'ready' => $allSk->where('module_status', 'tracer_review')->count(),
                'completed' => $allSk->where('module_status', 'completed')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $sk,
                'stats' => $stats
            ]);
        }

        return view('sk.index', compact('sk'));
    }

    public function create()
    {
        $photoDefs = $this->buildPhotoDefs('SK');
        return view('sk.create', compact('photoDefs'));
    }

    public function edit(SkData $sk)
    {
        $sk->load(['calonPelanggan','photoApprovals','files']);
        $photoDefs = $this->buildPhotoDefs('SK');
        return view('sk.edit', compact('sk','photoDefs'));
    }

    public function show(Request $r, SkData $sk)
    {
        $sk->load(['calonPelanggan', 'photoApprovals.tracerUser', 'photoApprovals.cgpUser', 'files']);

        if ($r->wantsJson() || $r->ajax()) {
            return response()->json($sk);
        }

        return view('sk.show', compact('sk'));
    }

    public function store(Request $r)
    {
        // Check if customer status is batal
        $customer = \App\Models\CalonPelanggan::where('reff_id_pelanggan', $r->reff_id_pelanggan)->first();
        if ($customer && $customer->status === 'batal') {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat membuat SK untuk customer dengan status batal. Customer ini sudah dibatalkan.'
            ], 422);
        }

        $materialRules = (new SkData())->getMaterialValidationRules();

        $v = Validator::make($r->all(), array_merge([
            'reff_id_pelanggan' => [
                'required',
                'string',
                'max:50',
                Rule::exists('calon_pelanggan','reff_id_pelanggan'),
                Rule::unique('sk_data','reff_id_pelanggan')->whereNull('deleted_at')
            ],
            'tanggal_instalasi' => ['required','date'],
            'notes' => ['nullable','string'],
        ], $materialRules), [
            'reff_id_pelanggan.unique' => 'SK untuk reff_id ini sudah ada. Tidak boleh membuat SK duplikat.'
        ]);

        if ($v->fails()) {
            return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
        }

        // Double check to prevent race condition (exclude soft deleted records)
        $existingSk = SkData::where('reff_id_pelanggan', $r->reff_id_pelanggan)
                            ->whereNull('deleted_at')
                            ->first();
        if ($existingSk) {
            return response()->json([
                'success' => false,
                'message' => 'SK untuk reff_id ' . $r->reff_id_pelanggan . ' sudah ada.',
                'existing_id' => $existingSk->id,
                'existing_status' => $existingSk->status
            ], 422);
        }

        $data = $v->validated();
        $data['status'] = SkData::STATUS_DRAFT;
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $sk = SkData::create($data);
        $this->audit('create', $sk, [], $sk->toArray());

        return response()->json(['success'=>true,'data'=>$sk], 201);
    }

    public function update(Request $r, SkData $sk)
    {
        if (!$sk->canEdit()) {
            return response()->json([
                'success'=>false,
                'message'=>'Tidak diizinkan untuk mengedit data ini. Status: ' . ($sk->module_status ?? $sk->status)
            ], 422);
        }

        $materialRules = $sk->getMaterialValidationRules();

        $v = Validator::make($r->all(), array_merge([
            'tanggal_instalasi' => ['required','date'],
            'notes' => ['nullable','string'],
            'created_by' => ['nullable','integer','exists:users,id'],
        ], $materialRules));

        if ($v->fails()) {
            return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
        }

        $old = $sk->getOriginal();
        $sk->fill($v->validated());
        $sk->updated_by = Auth::id();
        $sk->save();

        $this->audit('update', $sk, $old, $sk->toArray());
        return response()->json(['success'=>true,'data'=>$sk]);
    }

    public function destroy(SkData $sk)
    {
        $old = $sk->toArray();

        try {
            // Get all related photos/files
            $photoApprovals = $sk->photoApprovals;

            // Track folder paths for deletion
            $folderPathsToDelete = [];

            foreach ($photoApprovals as $photo) {
                // Collect folder paths (extract folder path from storage_path)
                if ($photo->storage_path) {
                    // Extract folder path (everything before the filename)
                    $folderPath = dirname($photo->storage_path);
                    if ($folderPath && $folderPath !== '.' && !in_array($folderPath, $folderPathsToDelete)) {
                        $folderPathsToDelete[] = $folderPath;
                    }
                }
            }

            // Delete the database record first
            $sk->delete();

            // Delete folders from Google Drive (this will delete all files in the folders)
            $deletedFolders = 0;
            if (!empty($folderPathsToDelete)) {
                $deletedFolders = $this->deleteFoldersFromDrive($folderPathsToDelete);
            }

            $this->audit('delete', $sk, $old, [
                'deleted_folders' => $deletedFolders,
                'attempted_folders' => count($folderPathsToDelete)
            ]);

            return response()->json([
                'success' => true,
                'deleted' => true,
                'files_deleted' => $deletedFolders > 0 ? 'All files in folders' : 0,
                'folders_deleted' => $deletedFolders
            ]);

        } catch (\Exception $e) {
            Log::error('SK delete error', [
                'sk_id' => $sk->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data dan file terkait.'
            ], 500);
        }
    }

    public function redirectByReff(string $reffId)
    {
        try {
            $normalizedReff = strtoupper(trim($reffId));
            $sk = SkData::where('reff_id_pelanggan', $normalizedReff)
                        ->whereNull('deleted_at')
                        ->first();

            if (!$sk && ctype_digit($normalizedReff)) {
                $sk = SkData::whereRaw('CAST(reff_id_pelanggan AS UNSIGNED) = ?', [(int)$normalizedReff])
                            ->whereNull('deleted_at')
                            ->first();
            }

            if (!$sk) {
                return redirect()->route('sk.index')
                    ->with('error', "SK dengan Reference ID '{$reffId}' tidak ditemukan.");
            }

            return redirect()->route('sk.show', $sk->id);

        } catch (\Exception $e) {
            Log::error('SK redirectByReff failed', [
                'reff_id' => $reffId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('sk.index')
                ->with('error', 'Terjadi kesalahan saat mencari SK.');
        }
    }

    public function precheckGeneric(Request $r)
    {
        $v = Validator::make($r->all(), [
            'file'      => ['required','file','mimes:jpg,jpeg,png,webp,pdf','max:35840'],
            'slot_type' => ['required','string','max:100'],
            'module'    => ['nullable','in:SK'],
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $module    = (string) $r->input('module', 'SK');
        $slotInput = (string) $r->input('slot_type');

        $cfgAll     = config('aergas_photos') ?: [];
        $moduleKey  = strtoupper($module);
        $slotCfg    = (array) (data_get($cfgAll, "modules.$moduleKey.slots.$slotInput") ?? []);

        if (!$slotCfg) {
            return response()->json(['success'=>false,'message'=>"Slot tidak dikenal: {$slotInput}"], 422);
        }

        $customPrompt = $slotCfg['prompt'] ?? null;
        if (!$customPrompt) {
            return response()->json([
                'success' => false,
                'message' => "Prompt validasi tidak dikonfigurasi untuk slot: {$slotInput}"
            ], 422);
        }

        try {
            $openAIService = app(OpenAIService::class);

            $result = $openAIService->validatePhotoWithPrompt(
                imagePath: $r->file('file')->getRealPath(),
                customPrompt: $customPrompt,
                context: [
                    'module' => $moduleKey,
                    'slot' => $slotInput,
                    'label' => $slotCfg['label'] ?? $slotInput
                ]
            );

            return response()->json([
                'success' => true,
                'ai' => [
                    'passed'     => $result['passed'],
                    'score'      => $result['confidence'] * 100,
                    'reason'     => $result['reason'],
                    'messages'   => [$result['reason']],
                    'objects'    => [],
                    'rules'      => [$slotInput],
                    'confidence' => $result['confidence'],
                ],
                'message' => $result['passed']
                    ? 'Validasi AI berhasil - foto diterima'
                    : 'Validasi AI gagal - ' . $result['reason'],
                'debug' => [
                    'prompt_used' => $customPrompt,
                    'raw_response' => $result['raw_response'] ?? null,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Photo precheck failed', [
                'slot' => $slotInput,
                'module' => $moduleKey,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validasi AI gagal: ' . $e->getMessage(),
                'ai' => [
                    'passed' => false,
                    'score' => 0,
                    'reason' => 'Error during AI validation',
                    'messages' => ['Terjadi kesalahan saat validasi AI'],
                    'objects' => [],
                    'rules' => [$slotInput],
                ]
            ], 500);
        }
    }

    public function uploadAndValidate(Request $r, SkData $sk)
    {
        $v = Validator::make($r->all(), [
            'file'       => ['required','file','mimes:jpg,jpeg,png,webp,pdf','max:35840'],
            'slot_type'  => ['required','string','max:100'],
            'ai_passed'  => ['nullable','boolean'],
            'ai_score'   => ['nullable','numeric'],
            'ai_reason'  => ['nullable','string'],
            'ai_notes'   => ['nullable','array'],
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $slotParam = (string) $r->input('slot_type');
        $slotSlug  = Str::slug($slotParam, '_');

        $ext  = strtolower($r->file('file')->getClientOriginalExtension() ?: $r->file('file')->extension());
        $ts   = now()->format('Ymd_His');
        $targetName = "{$sk->reff_id_pelanggan}_{$slotSlug}_{$ts}.{$ext}";

        $svc = $this->photoSvc ?? app(PhotoApprovalService::class);

        // TAMBAH: Meta data untuk customer name
        $meta = [];
        if ($sk->calonPelanggan) {
            $meta['customer_name'] = $sk->calonPelanggan->nama_pelanggan;
        }

        $res = $svc->storeWithoutAi(
            module: 'SK',
            reffId: $sk->reff_id_pelanggan,
            slotIncoming: $slotParam,
            file: $r->file('file'),
            uploadedBy: Auth::id(),
            targetFileName: $targetName,
            precheck: [
                'ai_passed'  => (bool) $r->boolean('ai_passed'),
                'ai_score'   => $r->input('ai_score'),
                'ai_reason'  => $r->input('ai_reason'),
                'ai_notes'   => $r->input('ai_notes', []),
            ],
            meta: $meta // TAMBAH parameter meta
        );

        $this->recalcSkStatus($sk);

        return response()->json([
            'success'   => true,
            'photo_id'  => $res['photo_id'] ?? null,
            'filename'  => $targetName,
            'message'   => 'Upload berhasil, foto lama telah diganti', // UBAH pesan
            'ai_result' => [
                'passed' => (bool) $r->boolean('ai_passed'),
                'reason' => $r->input('ai_reason', 'No reason provided'),
                'score'  => $r->input('ai_score', 0),
            ]
        ], 201);
    }

    public function uploadDraft(Request $r, SkData $sk)
    {
        // Log request data untuk debugging
        Log::info('SkData uploadDraft attempt', [
            'sk_id' => $sk->id,
            'reff_id' => $sk->reff_id_pelanggan,
            'has_file' => $r->hasFile('file'),
            'file_valid' => $r->hasFile('file') ? $r->file('file')->isValid() : false,
            'file_size' => $r->hasFile('file') ? $r->file('file')->getSize() : null,
            'file_mime' => $r->hasFile('file') ? $r->file('file')->getMimeType() : null,
            'slot_type' => $r->input('slot_type'),
            'request_data' => array_keys($r->all())
        ]);

        $v = Validator::make($r->all(), [
            'file' => ['required','file','mimes:jpg,jpeg,png,webp,pdf','max:35840'],
            'slot_type' => ['required','string','max:100'],
        ]);

        if ($v->fails()) {
            Log::warning('SkData uploadDraft validation failed', [
                'sk_id' => $sk->id,
                'reff_id' => $sk->reff_id_pelanggan,
                'errors' => $v->errors()->toArray()
            ]);
            return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
        }

        $slotParam = $r->input('slot_type');
        $slotSlug = Str::slug($slotParam, '_');
        $ext = strtolower($r->file('file')->getClientOriginalExtension() ?: $r->file('file')->extension());
        $ts = now()->format('Ymd_His');
        $targetName = "{$sk->reff_id_pelanggan}_{$slotSlug}_{$ts}.{$ext}";

        $svc = $this->photoSvc ?? app(PhotoApprovalService::class);

        $meta = [];
        if ($sk->calonPelanggan) {
            $meta['customer_name'] = $sk->calonPelanggan->nama_pelanggan;
        }

        try {
            $res = $svc->uploadDraftOnly(
                module: 'SK',
                reffId: $sk->reff_id_pelanggan,
                slotIncoming: $slotParam,
                file: $r->file('file'),
                uploadedBy: Auth::id(),
                targetFileName: $targetName,
                meta: $meta
            );

            if (!$res['success']) {
                Log::warning('SkData uploadDraftOnly service failed', [
                    'sk_id' => $sk->id,
                    'reff_id' => $sk->reff_id_pelanggan,
                    'slot_type' => $slotParam,
                    'service_message' => $res['message'] ?? 'Unknown error'
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $res['message'] ?? 'Upload service failed'
                ], 422);
            }

            Log::info('SkData uploadDraft success', [
                'sk_id' => $sk->id,
                'reff_id' => $sk->reff_id_pelanggan,
                'photo_id' => $res['photo_id'] ?? null,
                'filename' => $targetName
            ]);

            return response()->json([
                'success' => true,
                'photo_id' => $res['photo_id'] ?? null,
                'filename' => $targetName,
                'message' => 'Upload berhasil tersimpan sebagai draft'
            ], 201);

        } catch (\Exception $e) {
            Log::error('SkData uploadDraft exception', [
                'sk_id' => $sk->id,
                'reff_id' => $sk->reff_id_pelanggan,
                'slot_type' => $slotParam,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat upload: ' . $e->getMessage()
            ], 500);
        }
    }

    public function readyStatus(SkData $sk)
    {
        return response()->json([
            'all_passed' => $sk->isAllPhotosPassed(),
            'material_complete' => $sk->isMaterialComplete(),
            'can_submit' => $sk->canSubmit(),
            'ai_overall' => $sk->ai_overall_status ?? null,
            'status' => $sk->status,
            'material_summary' => $sk->material_summary,
        ]);
    }

    public function approveTracer(Request $r, SkData $sk)
    {
        if (!$sk->canApproveTracer()) {
            return response()->json(['success'=>false,'message' => 'Belum siap untuk approval tracer.'], 422);
        }
        $old = $sk->getOriginal();
        $sk->status = SkData::STATUS_TRACER_APPROVED;
        $sk->tracer_approved_at = now();
        $sk->tracer_approved_by = Auth::id();
        $sk->tracer_notes = $r->input('notes');
        $sk->save();
        $this->audit('approve', $sk, $old, $sk->toArray());
        return response()->json(['success'=>true,'data'=>$sk]);
    }

    public function rejectTracer(Request $r, SkData $sk)
    {
        if ($sk->status !== SkData::STATUS_READY_FOR_TRACER) {
            return response()->json(['success'=>false,'message' => 'Status tidak valid untuk reject tracer.'], 422);
        }
        $old = $sk->getOriginal();
        $sk->status = SkData::STATUS_TRACER_REJECTED;
        $sk->tracer_notes = $r->input('notes');
        $sk->save();
        $this->audit('reject', $sk, $old, $sk->toArray());
        return response()->json(['success'=>true,'data'=>$sk]);
    }

    public function approveCgp(Request $r, SkData $sk)
    {
        if (!$sk->canApproveCgp()) {
            return response()->json(['success'=>false,'message' => 'Belum siap untuk approval CGP.'], 422);
        }
        $old = $sk->getOriginal();
        $sk->status = SkData::STATUS_CGP_APPROVED;
        $sk->cgp_approved_at = now();
        $sk->cgp_approved_by = Auth::id();
        $sk->cgp_notes = $r->input('notes');
        $sk->save();

        // Update customer progress_status to next module (SR)
        $customer = $sk->calonPelanggan;
        if ($customer && $customer->progress_status === 'sk') {
            $customer->progress_status = 'sr';
            $customer->save();
        }

        $this->audit('approve', $sk, $old, $sk->toArray());
        return response()->json(['success'=>true,'data'=>$sk]);
    }

    public function rejectCgp(Request $r, SkData $sk)
    {
        if ($sk->status !== SkData::STATUS_TRACER_APPROVED) {
            return response()->json(['success'=>false,'message' => 'Status tidak valid untuk reject CGP.'], 422);
        }
        $old = $sk->getOriginal();
        $sk->status = SkData::STATUS_CGP_REJECTED;
        $sk->cgp_notes = $r->input('notes');
        $sk->save();
        $this->audit('reject', $sk, $old, $sk->toArray());
        return response()->json(['success'=>true,'data'=>$sk]);
    }

    public function schedule(Request $r, SkData $sk)
    {
        if (!$sk->canSchedule()) {
            return response()->json(['success'=>false,'message' => 'Belum bisa dijadwalkan.'], 422);
        }

        $v = Validator::make($r->all(), [
            'tanggal_instalasi' => ['required','date'],
            'nomor_sk'          => ['nullable','string','max:100','unique:sk_data,nomor_sk'],
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $old = $sk->getOriginal();
        $sk->fill($v->validated());
        if (!$sk->nomor_sk) $sk->nomor_sk = $this->makeNomor('SK');
        $sk->status = SkData::STATUS_SCHEDULED;
        $sk->save();

        $this->audit('update', $sk, $old, $sk->toArray());
        return response()->json(['success'=>true,'data'=>$sk]);
    }

    public function complete(Request $r, SkData $sk)
    {
        if (!$sk->canComplete()) {
            return response()->json(['success'=>false,'message' => 'Belum bisa diselesaikan.'], 422);
        }
        $old = $sk->getOriginal();
        $sk->status = SkData::STATUS_COMPLETED;
        $sk->module_status = 'completed';
        $sk->save();

        // Update customer progress_status to SR (if still on SK)
        $customer = $sk->calonPelanggan;
        if ($customer && $customer->progress_status === 'sk') {
            $customer->progress_status = 'sr';
            $customer->save();
        }

        $this->audit('update', $sk, $old, $sk->toArray());
        return response()->json(['success'=>true,'data'=>$sk]);
    }

    public function generateBeritaAcara(SkData $sk, BeritaAcaraService $beritaAcaraService)
    {
        try {
            $result = $beritaAcaraService->generateSkBeritaAcara($sk);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }

            return $result['pdf']->download($result['filename']);

        } catch (\Exception $e) {
            Log::error('Generate SK Berita Acara failed', [
                'sk_id' => $sk->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate Berita Acara: ' . $e->getMessage()
            ], 500);
        }
    }

    private function buildPhotoDefs(string $module): array
    {
        $cfgAll    = config('aergas_photos') ?: [];
        $moduleKey = strtoupper((string) $module);
        $cfgSlots  = (array) (
            data_get($cfgAll, "modules.$moduleKey.slots")
            ?? data_get($cfgAll, 'modules.'.strtolower($module).'.slots', [])
        );

        $defs = [];
        foreach ($cfgSlots as $key => $rule) {
            $accept = $rule['accept'] ?? ['image/*'];
            if (is_string($accept)) $accept = [$accept];

            $checks = collect($rule['checks'] ?? [])
                ->map(fn($c) => $c['label'] ?? $c['id'] ?? '')
                ->filter()->values()->all();

            $defs[] = [
                'field' => $key,
                'label' => $rule['label'] ?? $key,
                'accept' => $accept,
                'required_objects' => $checks,
            ];
        }
        return $defs;
    }

    private function recalcSkStatus(SkData $sk): void
    {
        try {
            if (method_exists($sk, 'syncModuleStatusFromPhotos')) {
                $sk->syncModuleStatusFromPhotos();
            } else {
                if (method_exists($sk, 'recomputeAiOverallStatus')) {
                    $sk->recomputeAiOverallStatus();
                }
                if ($sk->status === SkData::STATUS_DRAFT && $sk->canSubmit()) {
                    $sk->status = SkData::STATUS_READY_FOR_TRACER;
                    $sk->save();
                }
            }
        } catch (\Throwable $e) {
            Log::info('recalcSkStatus soft-failed', ['err' => $e->getMessage()]);
        }
    }

    private function makeNomor(string $prefix): string
    {
        return sprintf('%s-%s-%04d', strtoupper($prefix), now()->format('Ym'), random_int(1, 9999));
    }

    private function audit(string $action, $model, array $old = [], array $new = []): void
    {
        try {
            $serviceKey = 'App\Services\AergasAuditService';
            if (app()->bound($serviceKey)) {
                app($serviceKey)->logModel($action, $model, $old, $new);
            } else {
                Log::info('audit', [
                    'action' => $action,
                    'model'  => class_basename($model),
                    'id'     => $model->getKey(),
                    'old'    => $old,
                    'new'    => $new,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('audit-failed: '.$e->getMessage());
        }
    }

    /**
     * Delete folders from Google Drive
     */
    private function deleteFoldersFromDrive(array $folderPaths): int
    {
        $deletedCount = 0;

        try {
            $googleDriveService = app(\App\Services\GoogleDriveService::class);

            foreach ($folderPaths as $folderPath) {
                try {
                    if ($googleDriveService->deleteFolder($folderPath)) {
                        $deletedCount++;
                        Log::info('Deleted folder from Google Drive', ['folder_path' => $folderPath]);
                    } else {
                        Log::warning('Failed to delete folder from Google Drive', [
                            'folder_path' => $folderPath,
                            'reason' => 'Service returned false'
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to delete folder from Google Drive', [
                        'folder_path' => $folderPath,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Google Drive folder deletion error', [
                'folder_paths' => $folderPaths,
                'error' => $e->getMessage()
            ]);
        }

        return $deletedCount;
    }

    public function getRejectionDetails(SkData $sk)
    {
        try {
            $rejectedPhotos = $sk->photoApprovals()
                ->where(function($q) {
                    $q->whereNotNull('tracer_rejected_at')
                      ->orWhereNotNull('cgp_rejected_at');
                })
                ->with(['tracerUser', 'cgpUser'])
                ->get();

            $rejections = $rejectedPhotos->map(function($photo) {
                $rejectedByType = null;
                $rejectedByName = null;
                $reason = null;
                $rejectedDate = null;
                $category = null;

                if ($photo->tracer_rejected_at) {
                    $rejectedByType = 'tracer';
                    $rejectedByName = $photo->tracerUser->name ?? 'Unknown';
                    $reason = $photo->tracer_notes;
                    $rejectedDate = $photo->tracer_rejected_at->format('d/m/Y H:i');
                    $category = $photo->tracer_rejection_category;
                } elseif ($photo->cgp_rejected_at) {
                    $rejectedByType = 'cgp';
                    $rejectedByName = $photo->cgpUser->name ?? 'Unknown';
                    $reason = $photo->cgp_notes;
                    $rejectedDate = $photo->cgp_rejected_at->format('d/m/Y H:i');
                    $category = $photo->cgp_rejection_category;
                }

                return [
                    'photo_field' => $photo->photo_field_name,
                    'slot_label' => $photo->slot_label ?? null,
                    'rejected_by_type' => $rejectedByType,
                    'rejected_by_name' => $rejectedByName,
                    'reason' => $reason,
                    'rejected_date' => $rejectedDate,
                    'category' => $category,
                ];
            });

            return response()->json([
                'success' => true,
                'rejections' => $rejections
            ]);

        } catch (\Exception $e) {
            Log::error('Get SK rejection details failed', [
                'sk_id' => $sk->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load rejection details'
            ], 500);
        }
    }

    /**
     * Get photo status details for each required photo field
     * Returns status: uploaded, missing, or corrupted/failed
     */
    private function getPhotoStatusDetails(SkData $sk): array
    {
        $requiredPhotos = $sk->getRequiredPhotos();
        $photoApprovals = $sk->photoApprovals;
        $statusDetails = [];

        foreach ($requiredPhotos as $photoField) {
            // Find photo approval for this field
            $photo = $photoApprovals->firstWhere('photo_field_name', $photoField);

            if (!$photo) {
                // Photo not uploaded yet
                $statusDetails[] = [
                    'field' => $photoField,
                    'label' => $this->getPhotoLabel($photoField),
                    'status' => 'missing',
                    'icon' => 'fa-times-circle',
                    'color' => 'text-red-600',
                    'bg' => 'bg-red-50'
                ];
            } else {
                // Check if photo has valid storage_path with filename
                $storagePath = $photo->storage_path ?? '';
                $basename = basename($storagePath);

                // Consider as valid file if:
                // 1. Has file extension (e.g., .jpg, .png, .pdf)
                // 2. Has dot in basename (even without extension, e.g., "file.")
                // 3. Has timestamp pattern (e.g., _20251004_131955)
                $hasValidFile = !empty($storagePath) && (
                    pathinfo($storagePath, PATHINFO_EXTENSION) !== '' ||
                    strpos($basename, '.') !== false ||
                    preg_match('/_\d{8}_\d{6}/', $basename)
                );

                if ($hasValidFile) {
                    // Photo uploaded and accessible
                    $statusDetails[] = [
                        'field' => $photoField,
                        'label' => $this->getPhotoLabel($photoField),
                        'status' => 'uploaded',
                        'icon' => 'fa-check-circle',
                        'color' => 'text-green-600',
                        'bg' => 'bg-green-50',
                        'ai_status' => $photo->ai_status ?? null
                    ];
                } else {
                    // Photo record exists but file is corrupted/missing (only folder path or no extension)
                    $statusDetails[] = [
                        'field' => $photoField,
                        'label' => $this->getPhotoLabel($photoField),
                        'status' => 'corrupted',
                        'icon' => 'fa-exclamation-triangle',
                        'color' => 'text-yellow-600',
                        'bg' => 'bg-yellow-50'
                    ];
                }
            }
        }

        return $statusDetails;
    }

    /**
     * Get human-readable label for photo field
     */
    private function getPhotoLabel(string $fieldName): string
    {
        $labels = [
            'pneumatic_start' => 'Pneumatic Start',
            'pneumatic_finish' => 'Pneumatic Finish',
            'valve' => 'Valve',
            'isometrik_scan' => 'Isometrik Scan',
            'berita_acara' => 'Berita Acara',
        ];

        return $labels[$fieldName] ?? ucwords(str_replace('_', ' ', $fieldName));
    }

}
