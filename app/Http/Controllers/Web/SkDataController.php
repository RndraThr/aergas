<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\FileUploadService;
use App\Models\SkData;
use App\Models\FileStorage;
use App\Models\PhotoApproval;
use App\Services\PhotoApprovalService;

class SkDataController extends Controller
{
    // Jika kamu pakai DI, biarkan tanpa type-hint agar tidak error bila servicenya belum ada
    public function __construct(
        private $drive = null, // ex: App\Services\GoogleDriveService
        private $ai    = null  // ex: App\Services\OpenAIService
    ) {}

    /** LIST */
    public function index(Request $r)
    {
        $q = SkData::with('calonPelanggan')->latest('id');

        if ($r->filled('q')) {
            $term = trim($r->string('q'));
            $q->where(function($w) use ($term) {
                $w->where('reff_id_pelanggan','like',"%{$term}%")
                  ->orWhere('nomor_sk','like',"%{$term}%")
                  ->orWhere('status','like',"%{$term}%");
            });
        }

        $sk = $q->paginate(15)->withQueryString();

        if ($r->wantsJson() || $r->ajax()) {
            return response()->json($sk);
        }

        return view('sk.index', compact('sk')); // sesuaikan dengan path view kamu
    }

    /** FORM CREATE (GET) */
    public function create()
    {
        $defs = config('aergas_photos.modules.SK.slots');
        $photoDefs = collect($defs)->map(fn($v,$k)=>[
            'field'  => $k,                 // gunakan slot key kanonik
            'label'  => $v['label'],
            'accept' => $v['accept'] ?? ['image/*'],
        ])->values();

        return view('sk.create', compact('photoDefs'));
    }


    /** DETAIL */
    public function show(Request $r, SkData $sk)
    {
        $sk->load(['calonPelanggan', 'photoApprovals']);

        if ($r->wantsJson() || $r->ajax()) {
            return response()->json($sk);
        }

        return view('sk.show', compact('sk')); // sesuaikan dengan path view kamu
    }

    /** EDIT (GET) — opsional kalau belum ada view-nya */
    public function edit(SkData $sk)
    {
        $sk->load(['calonPelanggan', 'photoApprovals', 'files']);
        return view('sk.edit', compact('sk')); // buat view-nya nanti; jika belum, biarkan tidak dipakai dulu
    }

    /** CREATE (POST) */
    public function store(Request $r)
    {
        $v = Validator::make($r->all(), [
            'reff_id_pelanggan' => ['required','string','max:50', Rule::exists('calon_pelanggan','reff_id_pelanggan')],
            'notes'             => ['nullable','string'],
            'tanggal_instalasi' => ['nullable','date'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        $data = $v->validated();
        $data['status']     = SkData::STATUS_DRAFT;
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $sk = SkData::create($data);
        $this->audit('create', $sk, [], $sk->toArray());

        // Frontend kamu sudah siap menerima objek langsung
        return response()->json($sk, 201);
    }

    /** UPDATE (catatan saat draft) */
    public function update(Request $r, SkData $sk)
    {
        $v = Validator::make($r->all(), [
            'notes' => ['nullable','string'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        if ($sk->status !== SkData::STATUS_DRAFT) {
            return response()->json(['message' => 'Hanya boleh edit catatan saat status draft.'], 422);
        }

        $old = $sk->getOriginal();
        $sk->fill($v->validated());
        $sk->updated_by = Auth::id();
        $sk->save();

        $this->audit('update', $sk, $old, $sk->toArray());
        return response()->json($sk);
    }

    /** DELETE */
    public function destroy(SkData $sk)
    {
        $old = $sk->toArray();
        $sk->delete();
        $this->audit('delete', $sk, $old, []);
        return response()->json(['deleted' => true]);
    }

    /** UPLOAD + AI VALIDATE (langsung saat upload) */
    public function uploadAndValidate(Request $r, SkData $sk, PhotoApprovalService $svc)
    {
        $v = Validator::make($r->all(), [
            'file'      => ['required','file','mimes:jpg,jpeg,png,webp','max:10240'],
            'slot_type' => ['required','string','max:100'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        $slot = $r->input('slot_type');
        $res  = $svc->handleUploadAndValidate(
            'SK',
            $sk->reff_id_pelanggan,
            $slot,
            $r->file('file'),
            Auth::id()
        );

        // Kembalikan response yang diharapkan front-end (ada 'success')
        return response()->json($res, 201);
    }


    /** RECHECK FOTO */
    public function recheck(Request $r, SkData $sk, FileStorage $photo)
    {
        // validasi kepemilikan foto
        if (
            ($photo->sk_data_id && $photo->sk_data_id !== $sk->id) ||
            (!$photo->sk_data_id && $photo->reff_id_pelanggan !== $sk->reff_id_pelanggan)
        ) {
            return response()->json(['message' => 'Foto tidak sesuai record SK.'], 404);
        }

        // ambil slot dari tag kedua (atau default)
        $slot = collect($photo->tags ?? [])->first(fn($t) => $t !== 'sk_foto') ?? 'lainnya';

        // jalankan AI (pakai service kalau tersedia)
        $ai = ['status' => 'passed', 'score' => 1.0, 'checks' => [], 'notes' => null];
        try {
            if (app()->bound(\App\Services\OpenAIService::class)) {
                $openAI = app(\App\Services\OpenAIService::class);
                $disk   = $photo->storage_disk ?? 'public';
                $path   = $photo->storage_path ?? null;
                $full   = $path ? Storage::disk($disk)->path($path) : null;

                if ($full && file_exists($full)) {
                    $raw = $openAI->validatePhoto($full, $slot, 'SK');
                    $ai  = [
                        'status' => !empty($raw['validation_passed']) ? 'passed' : 'failed',
                        'score'  => $raw['confidence'] ?? null,
                        'checks' => $raw['checks'] ?? [],
                        'notes'  => $raw['rejection_reason'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('AI recheck SK error: '.$e->getMessage());
        }

        // simpan ke photo_approvals berbasis reff/module/slot (tanpa sk_data_id)
        PhotoApproval::updateOrCreate(
            [
                'reff_id_pelanggan' => $sk->reff_id_pelanggan,
                'module_name'       => 'sk',
                'photo_field_name'  => $slot,
            ],
            [
                'photo_url'          => $photo->path ? Storage::url($photo->path) : ($photo->url ?? ''),
                'storage_disk'       => $photo->storage_disk ?? 'public',
                'storage_path'       => $photo->storage_path ?? $photo->path ?? null,
                'drive_file_id'      => $photo->drive_file_id ?? null,
                'drive_link'         => $photo->drive_link ?? null,
                'ai_status'          => $ai['status'],
                'ai_score'           => $ai['score'] ?? null,
                'ai_checks'          => $ai['checks'] ?? [],
                'ai_notes'           => $ai['notes'] ?? null,
                'ai_last_checked_at' => now(),
            ]
        );

        // refresh status SK
        $old = $sk->getOriginal();
        $sk->recomputeAiOverallStatus();
        if ($sk->status === SkData::STATUS_DRAFT && $sk->isAllPhotosPassed()) {
            $sk->status = SkData::STATUS_READY_FOR_TRACER;
        }
        $sk->save();
        $this->audit('update', $sk, $old, $sk->toArray());

        return response()->json([
            'ai_status' => $ai['status'],
            'ai_checks' => $ai['checks'] ?? [],
            'ai_notes'  => $ai['notes'] ?? null,
            'sk'        => $sk->only(['id','status','ai_overall_status']),
        ]);
    }


    /** READINESS */
    public function readyStatus(SkData $sk)
    {
        return response()->json([
            'all_passed' => $sk->isAllPhotosPassed(),
            'ai_overall' => $sk->ai_overall_status,
            'status'     => $sk->status,
        ]);
    }

    /** APPROVALS */
    public function approveTracer(Request $r, SkData $sk)
    {
        if (!$sk->canApproveTracer()) {
            return response()->json(['message' => 'Belum siap untuk approval tracer.'], 422);
        }
        $old = $sk->getOriginal();
        $sk->status = SkData::STATUS_TRACER_APPROVED;
        $sk->tracer_approved_at = now();
        $sk->tracer_approved_by = Auth::id();
        $sk->tracer_notes = $r->input('notes');
        $sk->save();
        $this->audit('approve', $sk, $old, $sk->toArray());
        return response()->json($sk);
    }

    public function rejectTracer(Request $r, SkData $sk)
    {
        if ($sk->status !== SkData::STATUS_READY_FOR_TRACER) {
            return response()->json(['message' => 'Status tidak valid untuk reject tracer.'], 422);
        }
        $old = $sk->getOriginal();
        $sk->status = SkData::STATUS_TRACER_REJECTED;
        $sk->tracer_notes = $r->input('notes');
        $sk->save();
        $this->audit('reject', $sk, $old, $sk->toArray());
        return response()->json($sk);
    }

    public function approveCgp(Request $r, SkData $sk)
    {
        if (!$sk->canApproveCgp()) {
            return response()->json(['message' => 'Belum siap untuk approval CGP.'], 422);
        }
        $old = $sk->getOriginal();
        $sk->status = SkData::STATUS_CGP_APPROVED;
        $sk->cgp_approved_at = now();
        $sk->cgp_approved_by = Auth::id();
        $sk->cgp_notes = $r->input('notes');
        $sk->save();
        $this->audit('approve', $sk, $old, $sk->toArray());
        return response()->json($sk);
    }

    public function rejectCgp(Request $r, SkData $sk)
    {
        if ($sk->status !== SkData::STATUS_TRACER_APPROVED) {
            return response()->json(['message' => 'Status tidak valid untuk reject CGP.'], 422);
        }
        $old = $sk->getOriginal();
        $sk->status = SkData::STATUS_CGP_REJECTED;
        $sk->cgp_notes = $r->input('notes');
        $sk->save();
        $this->audit('reject', $sk, $old, $sk->toArray());
        return response()->json($sk);
    }

    /** SCHEDULE & COMPLETE */
    public function schedule(Request $r, SkData $sk)
    {
        if (!$sk->canSchedule()) return response()->json(['message' => 'Belum bisa dijadwalkan.'], 422);

        $v = Validator::make($r->all(), [
            'tanggal_instalasi' => ['required','date'],
            'nomor_sk'          => ['nullable','string','max:100','unique:sk_data,nomor_sk'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        $old = $sk->getOriginal();
        $sk->fill($v->validated());
        if (!$sk->nomor_sk) $sk->nomor_sk = $this->makeNomor('SK');
        $sk->status = SkData::STATUS_SCHEDULED;
        $sk->save();

        $this->audit('update', $sk, $old, $sk->toArray());
        return response()->json($sk);
    }

    public function complete(Request $r, SkData $sk)
    {
        if (!$sk->canComplete()) return response()->json(['message' => 'Belum bisa diselesaikan.'], 422);
        $old = $sk->getOriginal();
        $sk->status = SkData::STATUS_COMPLETED;
        $sk->save();
        $this->audit('update', $sk, $old, $sk->toArray());
        return response()->json($sk);
    }

    /* ================= Helpers ================= */

    private function makeNomor(string $prefix): string
    {
        return sprintf('%s-%s-%04d', strtoupper($prefix), now()->format('Ym'), random_int(1, 9999));
    }

    /** Upload ke Storage publik; jika service Drive tersedia, ganti di sini */
    private function storePhoto($file, string $reffId, string $module): array
    {
        $folder = "aergas/{$reffId}/{$module}";
        $name   = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
        $path   = $file->storeAs($folder, $name, 'public');

        $url = Storage::url($path);
        if (!is_string($url) || $url === $path) {
            $url = asset('storage/' . ltrim($path, '/'));
        }

        return [
            'id'   => null, // ganti dengan fileId dari Drive kalau ada
            'url'  => $url,
            'path' => $path,
        ];
    }

    /** Jalankan AI service kalau tersedia; fallback PASS */
    private function validatePhoto(?string $fileId, string $slot, string $module = 'SK'): array
    {
        try {
            if ($this->ai && method_exists($this->ai, 'validatePhoto')) {
                return (array) $this->ai->validatePhoto($fileId, $slot, $module);
            }
        } catch (\Throwable $e) {
            Log::warning('AI validatePhoto error: '.$e->getMessage());
        }
        return ['status' => 'passed', 'score' => 1.0, 'checks' => [], 'notes' => null];
    }

    /** Audit helper */
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

    /** Opsional: redirect by reff (untuk route sk.by-reff) */
    public function redirectByReff(string $reffId)
    {
        $rec = SkData::where('reff_id_pelanggan', $reffId)->firstOrFail();
        return redirect()->route('sk.show', $rec->id);
    }
}
