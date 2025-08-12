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
        // optional DI; biar analyzer aman walau belum di-bind
        private ?PhotoApprovalService $photoSvc = null,
    ) {}

    /** LIST */
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

        // JSON for AJAX/DataTables
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

    /** Tampilkan form edit SK */
    public function edit(SkData $sk)
    {
        $sk->load(['calonPelanggan','photoApprovals','files']);
        $photoDefs = $this->buildPhotoDefs('SK');
        return view('sk.edit', compact('sk','photoDefs'));
    }

    /** Helper: ambil definisi slot foto dari config untuk dikirim ke view */
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


    /** DETAIL */
    public function show(Request $r, SkData $sk)
    {
        $sk->load(['calonPelanggan', 'photoApprovals', 'files']);

        if ($r->wantsJson() || $r->ajax()) {
            return response()->json($sk);
        }

        return view('sk.show', compact('sk'));
    }

    /** CREATE (store draft SK) */
    public function store(Request $r)
    {
        $v = Validator::make($r->all(), [
            'reff_id_pelanggan' => ['required','string','max:50', Rule::exists('calon_pelanggan','reff_id_pelanggan')],
            'tanggal_instalasi' => ['nullable','date'],
            'notes'             => ['nullable','string'],
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $data = $v->validated();
        $data['status']     = SkData::STATUS_DRAFT;
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $sk = SkData::create($data);
        $this->audit('create', $sk, [], $sk->toArray());

        // konsisten: bungkus dengan {data}
        return response()->json(['success'=>true,'data'=>$sk], 201);
    }

    /** UPDATE (catatan saat draft) */
    public function update(Request $r, SkData $sk)
    {
        $v = Validator::make($r->all(), [
            'notes' => ['nullable','string'],
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        if ($sk->status !== SkData::STATUS_DRAFT) {
            return response()->json(['success'=>false,'message'=>'Hanya boleh edit catatan saat status draft.'], 422);
        }

        $old = $sk->getOriginal();
        $sk->fill($v->validated());
        $sk->updated_by = Auth::id();
        $sk->save();

        $this->audit('update', $sk, $old, $sk->toArray());
        return response()->json(['success'=>true,'data'=>$sk]);
    }

    /** DELETE */
    public function destroy(SkData $sk)
    {
        $old = $sk->toArray();
        $sk->delete();
        $this->audit('delete', $sk, $old, []);
        return response()->json(['success'=>true, 'deleted'=>true]);
    }

    /**
     * UPLOAD + AI VALIDATE (realtime per-foto)
     * request:
     *  - file: jpg/png/pdf (max 10MB)
     *  - slot_type: key slot (boleh alias) dari config aergas_photos
     */
    // App\Http\Controllers\Web\SkDataController.php

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
        $aliases    = (array) (data_get($cfgAll, "aliases.$moduleKey", []));
        $slotKey    = $aliases[$slotInput] ?? $slotInput;

        $rulesAll   = (array) (data_get($cfgAll, "modules.$moduleKey.slots.$slotKey")
                        ?? data_get($cfgAll, 'modules.'.strtolower($module).'.slots.'.$slotKey, []));
        if (!$rulesAll) {
            return response()->json(['success'=>false,'message'=>"Slot tidak dikenal: {$slotInput} → {$slotKey}"], 422);
        }

        // Normalisasi checks: indexed → keyed-by-id
        $checksSpecKeyed = collect((array) ($rulesAll['checks'] ?? []))
            ->mapWithKeys(function ($c, $k) {
                if (is_string($k)) return [$k => (array)$c];
                $id = is_array($c) ? ($c['id'] ?? null) : null;
                return $id ? [$id => (array)$c] : [];
            })->all();

        // Jika tak ada rule → lulus
        if (empty($checksSpecKeyed)) {
            return response()->json([
                'success' => true,
                'ai' => [
                    'passed'   => true,
                    'score'    => 100,
                    'objects'  => [],
                    'messages' => ['Tidak ada rule AI untuk slot ini.'],
                    'rules'    => [],
                ],
                'message' => 'Precheck complete (no rules).',
            ]);
        }

        // PDF → auto-pass dengan catatan
        $mime = $r->file('file')->getMimeType();
        if ($mime === 'application/pdf') {
            return response()->json([
                'success' => true,
                'ai' => [
                    'passed'   => true,
                    'score'    => null,
                    'objects'  => array_keys($checksSpecKeyed),
                    'messages' => ['Berkas PDF: konten tidak dianalisis oleh AI, akan diperiksa manual.'],
                    'rules'    => array_keys($checksSpecKeyed),
                ],
                'message' => 'Precheck complete (PDF auto-pass).',
            ]);
        }

        // Payload untuk AI harus array berindeks
        $checksPayload = collect($checksSpecKeyed)->map(function ($cfg, $id) {
            $cfg = (array) $cfg; $cfg['id'] = $cfg['id'] ?? $id; return $cfg;
        })->values()->all();

        $aiRaw = app(OpenAIService::class)->analyzeImageChecks(
            $r->file('file')->getRealPath(),
            $checksPayload,
            ['module'=>$moduleKey,'slot'=>$slotKey]
        );

        $verdict = app(PhotoRuleEvaluator::class)->evaluate(
            ['checks' => $checksSpecKeyed],
            $aiRaw
        );

        // Fallback objek terdeteksi
        $objects = $verdict['checks']['detected'] ?? collect($verdict['checks']['items'] ?? $verdict['checks'] ?? [])
            ->filter(fn($c) => is_array($c) && !empty($c['passed']))
            ->pluck('id')->filter()->values()->all();

        $messages = $verdict['notes'] ?? [];
        if (is_string($messages)) $messages = [$messages];

        return response()->json([
            'success' => true,
            'ai' => [
                'passed'   => ($verdict['status'] ?? '') === 'passed',
                'score'    => $verdict['score'] ?? null,
                'objects'  => $objects,
                'messages' => $messages,
                'rules'    => array_keys($checksSpecKeyed),
            ],
            'message' => 'Precheck complete',
        ]);
    }



    public function uploadAndValidate(Request $r, SkData $sk)
    {
        $v = Validator::make($r->all(), [
            'file'       => ['required','file','mimes:jpg,jpeg,png,webp,pdf','max:10240'],
            'slot_type'  => ['required','string','max:100'],
            'ai_passed'  => ['nullable','boolean'],
            'ai_score'   => ['nullable','numeric'],
            'ai_objects' => ['nullable','array'],
            'ai_notes'   => ['nullable','array'],
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        // ✅ pastikan string biasa, bukan Stringable
        $slotParam = (string) $r->input('slot_type');
        $slotSlug  = Str::slug($slotParam, '_');

        $ext  = strtolower($r->file('file')->getClientOriginalExtension() ?: $r->file('file')->extension());
        $ts   = now()->format('Ymd_His');
        $targetName = "{$sk->reff_id_pelanggan}_{$slotSlug}_{$ts}.{$ext}";

        /** @var PhotoApprovalService $svc */
        $svc = $this->photoSvc ?? app(PhotoApprovalService::class);

        // ❗ GANTI slotType: → slotIncoming:
        $res = $svc->storeWithoutAi(
            module: 'SK',
            reffId: $sk->reff_id_pelanggan,
            slotIncoming: $slotParam,     // ← ini yang benar
            file: $r->file('file'),
            uploadedBy: Auth::id(),
            targetFileName: $targetName,
            precheck: [
                'ai_passed'  => (bool) $r->boolean('ai_passed'),
                'ai_score'   => $r->input('ai_score'),
                'ai_objects' => $r->input('ai_objects', []),
                'ai_notes'   => $r->input('ai_notes', []),
            ]
        );

        $this->recalcSkStatus($sk);

        return response()->json([
            'success'   => true,
            'photo_id'  => $res['photo_id'] ?? null,
            'filename'  => $targetName,
            'message'   => 'Upload berhasil (tanpa AI ulang)',
        ], 201);
    }


    /** READY STATUS (untuk polling) */
    public function readyStatus(SkData $sk)
    {
        return response()->json([
            'all_passed' => method_exists($sk, 'isAllPhotosPassed') ? $sk->isAllPhotosPassed() : null,
            'ai_overall' => $sk->ai_overall_status ?? null,
            'status'     => $sk->status,
        ]);
    }

    /** APPROVALS */
    public function approveTracer(Request $r, SkData $sk)
    {
        if (method_exists($sk, 'canApproveTracer') && !$sk->canApproveTracer()) {
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
        if (method_exists($sk, 'canApproveCgp') && !$sk->canApproveCgp()) {
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

    /** SCHEDULE & COMPLETE */
    public function schedule(Request $r, SkData $sk)
    {
        if (method_exists($sk, 'canSchedule') && !$sk->canSchedule()) {
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
        if (method_exists($sk, 'canComplete') && !$sk->canComplete()) {
            return response()->json(['success'=>false,'message' => 'Belum bisa diselesaikan.'], 422);
        }
        $old = $sk->getOriginal();
        $sk->status = SkData::STATUS_COMPLETED;
        $sk->save();
        $this->audit('update', $sk, $old, $sk->toArray());
        return response()->json(['success'=>true,'data'=>$sk]);
    }

    /* ================= Helpers ================= */

    private function recalcSkStatus(SkData $sk): void
    {
        try {
            if (method_exists($sk, 'syncModuleStatusFromPhotos')) {
                $sk->syncModuleStatusFromPhotos();
            } else {
                // fallback pola lama
                if (method_exists($sk, 'recomputeAiOverallStatus')) {
                    $sk->recomputeAiOverallStatus();
                }
                if ($sk->status === SkData::STATUS_DRAFT && method_exists($sk, 'isAllPhotosPassed') && $sk->isAllPhotosPassed()) {
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
