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

class SkDataController extends Controller
{
    public function __construct(
        private ?PhotoApprovalService $photoSvc = null,
    ) {}

    public function index(Request $r)
    {
        $q = SkData::with('calonPelanggan')->latest('id');

        if ($r->filled('q')) {
            $term = trim((string) $r->get('q'));
            $q->where(function($w) use ($term) {
                $w->where('reff_id_pelanggan','like',"%{$term}%")
                  ->orWhere('nomor_sk','like',"%{$term}%")
                  ->orWhere('status','like',"%{$term}%");
            });
        }

        $sk = $q->paginate((int) $r->get('per_page', 15))->withQueryString();

        if ($r->wantsJson() || $r->ajax()) {
            return response()->json($sk);
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
        $sk->load(['calonPelanggan', 'photoApprovals', 'files']);

        if ($r->wantsJson() || $r->ajax()) {
            return response()->json($sk);
        }

        return view('sk.show', compact('sk'));
    }

    public function store(Request $r)
    {
        $materialRules = (new SkData())->getMaterialValidationRules();

        $v = Validator::make($r->all(), array_merge([
            'reff_id_pelanggan' => ['required','string','max:50', Rule::exists('calon_pelanggan','reff_id_pelanggan')],
            'tanggal_instalasi' => ['required','date'],
            'notes' => ['nullable','string'],
        ], $materialRules));

        if ($v->fails()) {
            return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
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
        if ($sk->status !== SkData::STATUS_DRAFT) {
            return response()->json([
                'success'=>false,
                'message'=>'Hanya boleh edit saat status draft.'
            ], 422);
        }

        $materialRules = $sk->getMaterialValidationRules();

        $v = Validator::make($r->all(), array_merge([
            'tanggal_instalasi' => ['nullable','date'],
            'notes' => ['nullable','string'],
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
        $sk->delete();
        $this->audit('delete', $sk, $old, []);
        return response()->json(['success'=>true, 'deleted'=>true]);
    }

    public function redirectByReff(string $reffId)
    {
        try {
            $normalizedReff = strtoupper(trim($reffId));
            $sk = SkData::where('reff_id_pelanggan', $normalizedReff)->first();

            if (!$sk && ctype_digit($normalizedReff)) {
                $sk = SkData::whereRaw('CAST(reff_id_pelanggan AS UNSIGNED) = ?', [(int)$normalizedReff])->first();
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
            'file'      => ['required','file','mimes:jpg,jpeg,png,webp,pdf','max:10240'],
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
            'file'       => ['required','file','mimes:jpg,jpeg,png,webp,pdf','max:10240'],
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
        $sk->save();
        $this->audit('update', $sk, $old, $sk->toArray());
        return response()->json(['success'=>true,'data'=>$sk]);
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
}
