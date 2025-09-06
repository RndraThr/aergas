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
use App\Models\SrData;
use App\Services\PhotoApprovalService;
use App\Services\BeritaAcaraService;

class SrDataController extends Controller
{
    public function __construct(
        private ?PhotoApprovalService $photoSvc = null,
    ) {}

    public function index(Request $r)
    {
        $q = SrData::with('calonPelanggan')->latest('id');

        if ($r->filled('q')) {
            $term = trim((string) $r->get('q'));
            $q->where(function($w) use ($term) {
                $w->where('reff_id_pelanggan','like',"%{$term}%")
                  ->orWhere('status','like',"%{$term}%");
            });
        }

        $sr = $q->paginate((int) $r->get('per_page', 15))->withQueryString();

        if ($r->wantsJson() || $r->ajax()) {
            return response()->json($sr);
        }

        return view('sr.index', compact('sr'));
    }

    public function create()
    {
        $photoDefs = $this->buildPhotoDefs('SR');
        return view('sr.create', compact('photoDefs'));
    }

    public function edit(SrData $sr)
    {
        $sr->load(['calonPelanggan','photoApprovals','files']);
        $photoDefs = $this->buildPhotoDefs('SR');
        return view('sr.edit', compact('sr','photoDefs'));
    }

    public function show(Request $r, SrData $sr)
    {
        $sr->load(['calonPelanggan', 'photoApprovals', 'files']);

        if ($r->wantsJson() || $r->ajax()) {
            return response()->json($sr);
        }

        return view('sr.show', compact('sr'));
    }

    public function store(Request $r)
    {
        $materialRules = (new SrData())->getMaterialValidationRules();

        $v = Validator::make($r->all(), array_merge([
            'reff_id_pelanggan' => ['required','string','max:50', Rule::exists('calon_pelanggan','reff_id_pelanggan')],
            'tanggal_pemasangan' => ['required','date'],
            'notes' => ['nullable','string'],
        ], $materialRules));

        if ($v->fails()) {
            return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
        }

        $data = $v->validated();
        $data['status'] = SrData::STATUS_DRAFT;
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $sr = SrData::create($data);
        $this->audit('create', $sr, [], $sr->toArray());

        return response()->json(['success'=>true,'data'=>$sr], 201);
    }

    public function update(Request $r, SrData $sr)
    {
        if ($sr->status !== SrData::STATUS_DRAFT) {
            return response()->json([
                'success'=>false,
                'message'=>'Hanya boleh edit saat status draft.'
            ], 422);
        }

        $materialRules = $sr->getMaterialValidationRules();

        $v = Validator::make($r->all(), array_merge([
            'tanggal_pemasangan' => ['required','date'],
            'notes' => ['nullable','string'],
            'created_by' => ['nullable','integer','exists:users,id'],
        ], $materialRules));

        if ($v->fails()) {
            return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
        }

        $old = $sr->getOriginal();
        $sr->fill($v->validated());
        $sr->updated_by = Auth::id();
        $sr->save();

        $this->audit('update', $sr, $old, $sr->toArray());
        return response()->json(['success'=>true,'data'=>$sr]);
    }

    public function destroy(SrData $sr)
    {
        $old = $sr->toArray();
        $sr->delete();
        $this->audit('delete', $sr, $old, []);
        return response()->json(['success'=>true, 'deleted'=>true]);
    }

    public function redirectByReff(string $reffId)
    {
        try {
            $normalizedReff = strtoupper(trim($reffId));
            $sr = SrData::where('reff_id_pelanggan', $normalizedReff)->first();

            if (!$sr && ctype_digit($normalizedReff)) {
                $sr = SrData::whereRaw('CAST(reff_id_pelanggan AS UNSIGNED) = ?', [(int)$normalizedReff])->first();
            }

            if (!$sr) {
                return redirect()->route('sr.index')
                    ->with('error', "SR dengan Reference ID '{$reffId}' tidak ditemukan.");
            }

            return redirect()->route('sr.show', $sr->id);

        } catch (\Exception $e) {
            Log::error('SR redirectByReff failed', [
                'reff_id' => $reffId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('sr.index')
                ->with('error', 'Terjadi kesalahan saat mencari SR.');
        }
    }

    public function precheckGeneric(Request $r)
    {
        $v = Validator::make($r->all(), [
            'file'      => ['required','file','mimes:jpg,jpeg,png,webp,pdf','max:20480'],
            'slot_type' => ['required','string','max:100'],
            'module'    => ['nullable','in:SR'],
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $module    = (string) $r->input('module', 'SR');
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

    public function uploadDraft(Request $r, SrData $sr)
    {
        $v = Validator::make($r->all(), [
            'file' => ['required','file','mimes:jpg,jpeg,png,webp,pdf','max:20480'],
            'slot_type' => ['required','string','max:100'],
        ]);

        if ($v->fails()) {
            return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
        }

        $slotParam = $r->input('slot_type');
        $slotSlug = Str::slug($slotParam, '_');
        $ext = strtolower($r->file('file')->getClientOriginalExtension() ?: $r->file('file')->extension());
        $ts = now()->format('Ymd_His');
        $targetName = "{$sr->reff_id_pelanggan}_{$slotSlug}_{$ts}.{$ext}";

        $svc = $this->photoSvc ?? app(PhotoApprovalService::class);

        $meta = [];
        if ($sr->calonPelanggan) {
            $meta['customer_name'] = $sr->calonPelanggan->nama_pelanggan;
        }

        $res = $svc->uploadDraftOnly(
            module: 'SR',
            reffId: $sr->reff_id_pelanggan,
            slotIncoming: $slotParam,
            file: $r->file('file'),
            uploadedBy: Auth::id(),
            targetFileName: $targetName,
            meta: $meta
        );

        return response()->json([
            'success' => true,
            'photo_id' => $res['photo_id'] ?? null,
            'filename' => $targetName,
            'message' => 'Upload berhasil tersimpan sebagai draft'
        ], 201);
    }

    public function uploadAndValidate(Request $r, SrData $sr)
    {
        $v = Validator::make($r->all(), [
            'file'       => ['required','file','mimes:jpg,jpeg,png,webp,pdf','max:20480'],
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
        $targetName = "{$sr->reff_id_pelanggan}_{$slotSlug}_{$ts}.{$ext}";

        $svc = $this->photoSvc ?? app(PhotoApprovalService::class);

        $res = $svc->storeWithoutAi(
            module: 'SR',
            reffId: $sr->reff_id_pelanggan,
            slotIncoming: $slotParam,
            file: $r->file('file'),
            uploadedBy: Auth::id(),
            targetFileName: $targetName,
            precheck: [
                'ai_passed'  => (bool) $r->boolean('ai_passed'),
                'ai_score'   => $r->input('ai_score'),
                'ai_reason'  => $r->input('ai_reason'),
                'ai_notes'   => $r->input('ai_notes', []),
            ]
        );

        $this->recalcSrStatus($sr);

        return response()->json([
            'success'   => true,
            'photo_id'  => $res['photo_id'] ?? null,
            'filename'  => $targetName,
            'message'   => 'Upload berhasil dengan hasil AI precheck',
            'ai_result' => [
                'passed' => (bool) $r->boolean('ai_passed'),
                'reason' => $r->input('ai_reason', 'No reason provided'),
                'score'  => $r->input('ai_score', 0),
            ]
        ], 201);
    }

    public function readyStatus(SrData $sr)
    {
        return response()->json([
            'all_passed' => $sr->isAllPhotosPassed(),
            'material_complete' => $sr->isMaterialComplete(),
            'can_submit' => $sr->canSubmit(),
            'ai_overall' => $sr->ai_overall_status ?? null,
            'status' => $sr->status,
            'material_summary' => $sr->material_summary,
        ]);
    }

    public function approveTracer(Request $r, SrData $sr)
    {
        if (!$sr->canApproveTracer()) {
            return response()->json(['success'=>false,'message' => 'Belum siap untuk approval tracer.'], 422);
        }
        $old = $sr->getOriginal();
        $sr->status = SrData::STATUS_TRACER_APPROVED;
        $sr->tracer_approved_at = now();
        $sr->tracer_approved_by = Auth::id();
        $sr->tracer_notes = $r->input('notes');
        $sr->save();
        $this->audit('approve', $sr, $old, $sr->toArray());
        return response()->json(['success'=>true,'data'=>$sr]);
    }

    public function rejectTracer(Request $r, SrData $sr)
    {
        if ($sr->status !== SrData::STATUS_READY_FOR_TRACER) {
            return response()->json(['success'=>false,'message' => 'Status tidak valid untuk reject tracer.'], 422);
        }
        $old = $sr->getOriginal();
        $sr->status = SrData::STATUS_TRACER_REJECTED;
        $sr->tracer_notes = $r->input('notes');
        $sr->save();
        $this->audit('reject', $sr, $old, $sr->toArray());
        return response()->json(['success'=>true,'data'=>$sr]);
    }

    public function approveCgp(Request $r, SrData $sr)
    {
        if (!$sr->canApproveCgp()) {
            return response()->json(['success'=>false,'message' => 'Belum siap untuk approval CGP.'], 422);
        }
        $old = $sr->getOriginal();
        $sr->status = SrData::STATUS_CGP_APPROVED;
        $sr->cgp_approved_at = now();
        $sr->cgp_approved_by = Auth::id();
        $sr->cgp_notes = $r->input('notes');
        $sr->save();
        $this->audit('approve', $sr, $old, $sr->toArray());
        return response()->json(['success'=>true,'data'=>$sr]);
    }

    public function rejectCgp(Request $r, SrData $sr)
    {
        if ($sr->status !== SrData::STATUS_TRACER_APPROVED) {
            return response()->json(['success'=>false,'message' => 'Status tidak valid untuk reject CGP.'], 422);
        }
        $old = $sr->getOriginal();
        $sr->status = SrData::STATUS_CGP_REJECTED;
        $sr->cgp_notes = $r->input('notes');
        $sr->save();
        $this->audit('reject', $sr, $old, $sr->toArray());
        return response()->json(['success'=>true,'data'=>$sr]);
    }

    public function schedule(Request $r, SrData $sr)
    {
        if (!$sr->canSchedule()) {
            return response()->json(['success'=>false,'message' => 'Belum bisa dijadwalkan.'], 422);
        }

        $v = Validator::make($r->all(), [
            'tanggal_pemasangan' => ['required','date'],
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $old = $sr->getOriginal();
        $sr->fill($v->validated());
        $sr->status = SrData::STATUS_SCHEDULED;
        $sr->save();

        $this->audit('update', $sr, $old, $sr->toArray());
        return response()->json(['success'=>true,'data'=>$sr]);
    }

    public function complete(Request $r, SrData $sr)
    {
        if (!$sr->canComplete()) {
            return response()->json(['success'=>false,'message' => 'Belum bisa diselesaikan.'], 422);
        }
        $old = $sr->getOriginal();
        $sr->status = SrData::STATUS_COMPLETED;
        $sr->save();
        $this->audit('update', $sr, $old, $sr->toArray());
        return response()->json(['success'=>true,'data'=>$sr]);
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

    private function recalcSrStatus(SrData $sr): void
    {
        try {
            if (method_exists($sr, 'syncModuleStatusFromPhotos')) {
                $sr->syncModuleStatusFromPhotos();
            } else {
                if (method_exists($sr, 'recomputeAiOverallStatus')) {
                    $sr->recomputeAiOverallStatus();
                }
                if ($sr->status === SrData::STATUS_DRAFT && $sr->canSubmit()) {
                    $sr->status = SrData::STATUS_READY_FOR_TRACER;
                    $sr->save();
                }
            }
        } catch (\Throwable $e) {
            Log::info('recalcSrStatus soft-failed', ['err' => $e->getMessage()]);
        }
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

    public function generateBeritaAcara(SrData $sr, BeritaAcaraService $beritaAcaraService)
    {
        try {
            $result = $beritaAcaraService->generateSrBeritaAcara($sr);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }

            return $result['pdf']->download($result['filename']);

        } catch (\Exception $e) {
            Log::error('Generate SR Berita Acara failed', [
                'sr_id' => $sr->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate Berita Acara: ' . $e->getMessage()
            ], 500);
        }
    }
}
