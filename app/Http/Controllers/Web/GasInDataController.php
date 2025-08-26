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
use App\Models\GasInData;
use App\Services\PhotoApprovalService;

class GasInDataController extends Controller
{
   public function __construct(
       private ?PhotoApprovalService $photoSvc = null,
   ) {}

   public function index(Request $r)
   {
       $q = GasInData::with('calonPelanggan')->latest('id');

       if ($r->filled('q')) {
           $term = trim((string) $r->get('q'));
           $q->where(function($w) use ($term) {
               $w->where('reff_id_pelanggan','like',"%{$term}%")
                 ->orWhere('status','like',"%{$term}%");
           });
       }

       $gasIn = $q->paginate((int) $r->get('per_page', 15))->withQueryString();

       if ($r->wantsJson() || $r->ajax()) {
           return response()->json($gasIn);
       }

       return view('gas-in.index', compact('gasIn'));
   }

   public function create()
   {
       $photoDefs = $this->buildPhotoDefs('GAS_IN');
       return view('gas-in.create', compact('photoDefs'));
   }

   public function edit(GasInData $gasIn)
   {
       $gasIn->load(['calonPelanggan','photoApprovals','files']);
       $photoDefs = $this->buildPhotoDefs('GAS_IN');
       return view('gas-in.edit', compact('gasIn','photoDefs'));
   }

   public function show(Request $r, GasInData $gasIn)
   {
       $gasIn->load(['calonPelanggan', 'photoApprovals', 'files']);

       if ($r->wantsJson() || $r->ajax()) {
           return response()->json($gasIn);
       }

       return view('gas-in.show', compact('gasIn'));
   }

   public function store(Request $r)
   {
       $v = Validator::make($r->all(), [
           'reff_id_pelanggan' => ['required','string','max:50', Rule::exists('calon_pelanggan','reff_id_pelanggan')],
           'tanggal_gas_in' => ['required','date'],
           'notes' => ['nullable','string'],
       ]);

       if ($v->fails()) {
           return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
       }

       $data = $v->validated();
       $data['status'] = GasInData::STATUS_DRAFT;
       $data['created_by'] = Auth::id();
       $data['updated_by'] = Auth::id();

       $gasIn = GasInData::create($data);
       $this->audit('create', $gasIn, [], $gasIn->toArray());

       return response()->json(['success'=>true,'data'=>$gasIn], 201);
   }

   public function update(Request $r, GasInData $gasIn)
   {
       if ($gasIn->status !== GasInData::STATUS_DRAFT) {
           return response()->json([
               'success'=>false,
               'message'=>'Hanya boleh edit saat status draft.'
           ], 422);
       }

       $v = Validator::make($r->all(), [
           'tanggal_gas_in' => ['nullable','date'],
           'notes' => ['nullable','string'],
       ]);

       if ($v->fails()) {
           return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
       }

       $old = $gasIn->getOriginal();
       $gasIn->fill($v->validated());
       $gasIn->updated_by = Auth::id();
       $gasIn->save();

       $this->audit('update', $gasIn, $old, $gasIn->toArray());
       return response()->json(['success'=>true,'data'=>$gasIn]);
   }

   public function destroy(GasInData $gasIn)
   {
       $old = $gasIn->toArray();
       $gasIn->delete();
       $this->audit('delete', $gasIn, $old, []);
       return response()->json(['success'=>true, 'deleted'=>true]);
   }

   public function redirectByReff(string $reffId)
   {
       try {
           $normalizedReff = strtoupper(trim($reffId));
           $gasIn = GasInData::where('reff_id_pelanggan', $normalizedReff)->first();

           if (!$gasIn && ctype_digit($normalizedReff)) {
               $gasIn = GasInData::whereRaw('CAST(reff_id_pelanggan AS UNSIGNED) = ?', [(int)$normalizedReff])->first();
           }

           if (!$gasIn) {
               return redirect()->route('gas-in.index')
                   ->with('error', "Gas In dengan Reference ID '{$reffId}' tidak ditemukan.");
           }

           return redirect()->route('gas-in.show', $gasIn->id);

       } catch (\Exception $e) {
           Log::error('Gas In redirectByReff failed', [
               'reff_id' => $reffId,
               'error' => $e->getMessage()
           ]);

           return redirect()->route('gas-in.index')
               ->with('error', 'Terjadi kesalahan saat mencari Gas In.');
       }
   }

   public function precheckGeneric(Request $r)
   {
       $v = Validator::make($r->all(), [
           'file'      => ['required','file','mimes:jpg,jpeg,png,webp,pdf','max:10240'],
           'slot_type' => ['required','string','max:100'],
           'module'    => ['nullable','in:GAS_IN'],
       ]);
       if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

       $module    = (string) $r->input('module', 'GAS_IN');
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

   public function uploadAndValidate(Request $r, GasInData $gasIn)
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
       $targetName = "{$gasIn->reff_id_pelanggan}_{$slotSlug}_{$ts}.{$ext}";

       $svc = $this->photoSvc ?? app(PhotoApprovalService::class);

       $meta = [];
       if ($gasIn->calonPelanggan) {
           $meta['customer_name'] = $gasIn->calonPelanggan->nama_pelanggan;
       }

       $res = $svc->storeWithoutAi(
           module: 'GAS_IN',
           reffId: $gasIn->reff_id_pelanggan,
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
           meta: $meta
       );

       $this->recalcGasInStatus($gasIn);

       return response()->json([
           'success'   => true,
           'photo_id'  => $res['photo_id'] ?? null,
           'filename'  => $targetName,
           'message'   => 'Upload berhasil, foto lama telah diganti',
           'ai_result' => [
               'passed' => (bool) $r->boolean('ai_passed'),
               'reason' => $r->input('ai_reason', 'No reason provided'),
               'score'  => $r->input('ai_score', 0),
           ]
       ], 201);
   }

   public function readyStatus(GasInData $gasIn)
   {
       return response()->json([
           'all_passed' => $gasIn->isAllPhotosPassed(),
           'can_submit' => $gasIn->canSubmit(),
           'ai_overall' => $gasIn->ai_overall_status ?? null,
           'status' => $gasIn->status,
       ]);
   }

   public function approveTracer(Request $r, GasInData $gasIn)
   {
       if (!$gasIn->canApproveTracer()) {
           return response()->json(['success'=>false,'message' => 'Belum siap untuk approval tracer.'], 422);
       }
       $old = $gasIn->getOriginal();
       $gasIn->status = GasInData::STATUS_TRACER_APPROVED;
       $gasIn->tracer_approved_at = now();
       $gasIn->tracer_approved_by = Auth::id();
       $gasIn->tracer_notes = $r->input('notes');
       $gasIn->save();
       $this->audit('approve', $gasIn, $old, $gasIn->toArray());
       return response()->json(['success'=>true,'data'=>$gasIn]);
   }

   public function rejectTracer(Request $r, GasInData $gasIn)
   {
       if ($gasIn->status !== GasInData::STATUS_READY_FOR_TRACER) {
           return response()->json(['success'=>false,'message' => 'Status tidak valid untuk reject tracer.'], 422);
       }
       $old = $gasIn->getOriginal();
       $gasIn->status = GasInData::STATUS_TRACER_REJECTED;
       $gasIn->tracer_notes = $r->input('notes');
       $gasIn->save();
       $this->audit('reject', $gasIn, $old, $gasIn->toArray());
       return response()->json(['success'=>true,'data'=>$gasIn]);
   }

   public function approveCgp(Request $r, GasInData $gasIn)
   {
       if (!$gasIn->canApproveCgp()) {
           return response()->json(['success'=>false,'message' => 'Belum siap untuk approval CGP.'], 422);
       }
       $old = $gasIn->getOriginal();
       $gasIn->status = GasInData::STATUS_CGP_APPROVED;
       $gasIn->cgp_approved_at = now();
       $gasIn->cgp_approved_by = Auth::id();
       $gasIn->cgp_notes = $r->input('notes');
       $gasIn->save();
       $this->audit('approve', $gasIn, $old, $gasIn->toArray());
       return response()->json(['success'=>true,'data'=>$gasIn]);
   }

   public function rejectCgp(Request $r, GasInData $gasIn)
   {
       if ($gasIn->status !== GasInData::STATUS_TRACER_APPROVED) {
           return response()->json(['success'=>false,'message' => 'Status tidak valid untuk reject CGP.'], 422);
       }
       $old = $gasIn->getOriginal();
       $gasIn->status = GasInData::STATUS_CGP_REJECTED;
       $gasIn->cgp_notes = $r->input('notes');
       $gasIn->save();
       $this->audit('reject', $gasIn, $old, $gasIn->toArray());
       return response()->json(['success'=>true,'data'=>$gasIn]);
   }

   public function schedule(Request $r, GasInData $gasIn)
   {
       if (!$gasIn->canSchedule()) {
           return response()->json(['success'=>false,'message' => 'Belum bisa dijadwalkan.'], 422);
       }

       $v = Validator::make($r->all(), [
           'tanggal_gas_in' => ['required','date'],
       ]);
       if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

       $old = $gasIn->getOriginal();
       $gasIn->fill($v->validated());
       $gasIn->status = GasInData::STATUS_SCHEDULED;
       $gasIn->save();

       $this->audit('update', $gasIn, $old, $gasIn->toArray());
       return response()->json(['success'=>true,'data'=>$gasIn]);
   }

   public function uploadDraft(Request $r, GasInData $gasIn)
    {
        $v = Validator::make($r->all(), [
            'file' => ['required','file','mimes:jpg,jpeg,png,webp,pdf','max:10240'],
            'slot_type' => ['required','string','max:100'],
        ]);

        if ($v->fails()) {
            return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
        }

        $slotParam = $r->input('slot_type');
        $slotSlug = Str::slug($slotParam, '_');
        $ext = strtolower($r->file('file')->getClientOriginalExtension() ?: $r->file('file')->extension());
        $ts = now()->format('Ymd_His');
        $targetName = "{$gasIn->reff_id_pelanggan}_{$slotSlug}_{$ts}.{$ext}";

        $svc = $this->photoSvc ?? app(PhotoApprovalService::class);

        $meta = [];
        if ($gasIn->calonPelanggan) {
            $meta['customer_name'] = $gasIn->calonPelanggan->nama_pelanggan;
        }

        $res = $svc->uploadDraftOnly(
            module: 'GAS_IN',
            reffId: $gasIn->reff_id_pelanggan,
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

   public function complete(Request $r, GasInData $gasIn)
   {
       if (!$gasIn->canComplete()) {
           return response()->json(['success'=>false,'message' => 'Belum bisa diselesaikan.'], 422);
       }
       $old = $gasIn->getOriginal();
       $gasIn->status = GasInData::STATUS_COMPLETED;
       $gasIn->save();
       $this->audit('update', $gasIn, $old, $gasIn->toArray());
       return response()->json(['success'=>true,'data'=>$gasIn]);
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

   private function recalcGasInStatus(GasInData $gasIn): void
   {
       try {
           if (method_exists($gasIn, 'syncModuleStatusFromPhotos')) {
               $gasIn->syncModuleStatusFromPhotos();
           } else {
               if (method_exists($gasIn, 'recomputeAiOverallStatus')) {
                   $gasIn->recomputeAiOverallStatus();
               }
               if ($gasIn->status === GasInData::STATUS_DRAFT && $gasIn->canSubmit()) {
                   $gasIn->status = GasInData::STATUS_READY_FOR_TRACER;
                   $gasIn->save();
               }
           }
       } catch (\Throwable $e) {
           Log::info('recalcGasInStatus soft-failed', ['err' => $e->getMessage()]);
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
}
