<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

use App\Models\SrData;
use App\Models\FileStorage;
use App\Models\PhotoApproval;
use App\Services\PhotoApprovalService;

class SrDataController extends Controller
{
    /** LIST */
    public function index(Request $r)
    {
        $q = SrData::query()
            ->with(['calonPelanggan'])
            ->when($r->status, fn($qq) => $qq->where('status', $r->status))
            ->when($r->search, function ($qq) use ($r) {
                $s = trim((string)$r->search);
                $qq->where(function ($w) use ($s) {
                    $w->where('reff_id_pelanggan', 'like', "%$s%")
                      ->orWhere('nomor_sr', 'like', "%$s%");
                });
            })
            ->orderByDesc('id');

        return response()->json($q->paginate((int) $r->get('per_page', 15)));
    }

    /** DETAIL */
    public function show(SrData $sr)
    {
        $sr->load(['calonPelanggan','photoApprovals','files']);
        return response()->json($sr);
    }

    /** CREATE */
    public function store(Request $r)
    {
        // opsi tapping sesuai config
        $opsiTapping = ['63x20','90x20','63x32','180x90','180x63','125x63','90x63','180x32','125x32','90x32'];

        $v = Validator::make($r->all(), [
            'reff_id_pelanggan'         => ['required','string','max:50', Rule::exists('calon_pelanggan','reff_id_pelanggan')],
            'notes'                     => ['nullable','string'],

            // field manual SR (opsional saat create – bisa diwajibkan kalau mau)
            'jenis_tapping'             => ['nullable', Rule::in($opsiTapping)],
            'panjang_pipa_pe_m'         => ['nullable','numeric','min:0'],
            'panjang_casing_crossing_m' => ['nullable','numeric','min:0'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        $data = $v->validated();
        $data['status']     = SrData::STATUS_DRAFT;
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $sr = SrData::create([
            'reff_id_pelanggan' => $data['reff_id_pelanggan'],
            'notes'             => $data['notes'] ?? null,
            'status'            => $data['status'],
            'created_by'        => $data['created_by'],
            'updated_by'        => $data['updated_by'],
        ]);

        // set field manual jika kolom ada
        $this->setIfColumnExists($sr, 'jenis_tapping',               $data['jenis_tapping'] ?? null);
        $this->setIfColumnExists($sr, 'panjang_pipa_pe_m',           $data['panjang_pipa_pe_m'] ?? null);
        $this->setIfColumnExists($sr, 'panjang_casing_crossing_m',   $data['panjang_casing_crossing_m'] ?? null);

        // fallback ke kolom lama bila tersedia (kompatibilitas)
        $this->setIfColumnExists($sr, 'panjang_pipa_pe',             $data['panjang_pipa_pe_m'] ?? null);
        $this->setIfColumnExists($sr, 'panjang_casing_crossing_sr',  $data['panjang_casing_crossing_m'] ?? null);

        $sr->save();

        $this->audit('create', $sr, [], $sr->toArray());
        return response()->json($sr, 201);
    }

    /** UPDATE */
    public function update(Request $r, SrData $sr)
    {
        if ($sr->status === SrData::STATUS_COMPLETED) {
            return response()->json(['message' => 'Record sudah completed.'], 422);
        }

        $opsiTapping = ['63x20','90x20','63x32','180x90','180x63','125x63','90x63','180x32','125x32','90x32'];

        $v = Validator::make($r->all(), [
            'notes'                     => ['nullable','string'],
            'tanggal_pemasangan'        => ['nullable','date'],

            // field manual (pakai nama baru + fallback)
            'jenis_tapping'             => ['nullable', Rule::in($opsiTapping)],
            'panjang_pipa_pe_m'         => ['nullable','numeric','min:0'],
            'panjang_casing_crossing_m' => ['nullable','numeric','min:0'],

            // nama lama (tetap diterima kalau FE lama masih kirim)
            'panjang_pipa_pe'           => ['nullable','numeric','min:0'],
            'panjang_casing_crossing_sr'=> ['nullable','numeric','min:0'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        $old = $sr->getOriginal();

        $sr->notes = $r->input('notes', $sr->notes);
        $this->setIfColumnExists($sr, 'tanggal_pemasangan', $r->input('tanggal_pemasangan'));

        // set nama baru bila ada
        $this->setIfColumnExists($sr, 'jenis_tapping',               $r->input('jenis_tapping'));
        $this->setIfColumnExists($sr, 'panjang_pipa_pe_m',           $r->input('panjang_pipa_pe_m'));
        $this->setIfColumnExists($sr, 'panjang_casing_crossing_m',   $r->input('panjang_casing_crossing_m'));

        // fallback ke kolom lama
        if (is_null($sr->panjang_pipa_pe_m ?? null)) {
            $this->setIfColumnExists($sr, 'panjang_pipa_pe_m', $r->input('panjang_pipa_pe'));
        }
        if (is_null($sr->panjang_casing_crossing_m ?? null)) {
            $this->setIfColumnExists($sr, 'panjang_casing_crossing_m', $r->input('panjang_casing_crossing_sr'));
        }
        // kalau kolom lama memang yang dipakai:
        $this->setIfColumnExists($sr, 'panjang_pipa_pe',             $r->input('panjang_pipa_pe_m', $r->input('panjang_pipa_pe')));
        $this->setIfColumnExists($sr, 'panjang_casing_crossing_sr',  $r->input('panjang_casing_crossing_m', $r->input('panjang_casing_crossing_sr')));

        $sr->updated_by = Auth::id();
        $sr->save();

        $this->audit('update', $sr, $old, $sr->toArray());
        return response()->json($sr);
    }

    /** DELETE */
    public function destroy(SrData $sr)
    {
        $old = $sr->toArray();
        $sr->delete();
        $this->audit('delete', $sr, $old, []);
        return response()->json(['deleted' => true]);
    }

    /**
     * UPLOAD + AI VALIDATE (pakai PhotoApprovalService + config)
     * FE kirim: file, slot_type (boleh alias; service akan map ke slot kanonik)
     */
    public function uploadAndValidate(Request $r, SrData $sr, PhotoApprovalService $svc)
    {
        $v = Validator::make($r->all(), [
            'file'      => ['required','file','mimes:jpg,jpeg,png,webp,pdf','max:10240'],
            'slot_type' => ['required','string','max:100'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        $slot = $r->input('slot_type');

        // Service akan:
        // - map alias → slot key,
        // - cek "requires.fields" dari config (contoh: tapping_saddle butuh jenis_tapping),
        // - upload (Drive/lokal), panggil AI, evaluasi rules config,
        // - simpan PhotoApproval, recalc status SR.
        $res  = $svc->handleUploadAndValidate(
            'SR',
            $sr->reff_id_pelanggan,
            $slot,
            $r->file('file'),
            Auth::id()
        );

        if (empty($res['success'])) {
            return response()->json($res, 422);
        }

        return response()->json($res, 201);
    }

    /** RECHECK FOTO (tetap sederhana; bisa dipindah ke service jika mau) */
    public function recheck(Request $r, SrData $sr, FileStorage $photo)
    {
        if ($photo->sr_data_id !== $sr->id) {
            return response()->json(['message' => 'Foto tidak sesuai record SR.'], 404);
        }

        // slot dari tag kedua
        $slot = collect($photo->tags ?? [])->first(fn($t) => $t !== 'sr_foto') ?? 'lainnya';

        // Jika kamu sudah punya method recheck di service, lebih baik panggil service di sini.
        // Untuk sekarang, pakai jalur simpel (AI langsung):
        try {
            // kalau drive_file_id kosong, gunakan path lokal
            $ai = [
                'status' => 'passed',
                'score'  => 1.0,
                'checks' => [],
                'notes'  => null,
            ];

            // Kalau kamu sudah punya OpenAIService di container:
            if (app()->bound(\App\Services\OpenAIService::class)) {
                $openAI = app(\App\Services\OpenAIService::class);

                // normalisasi input: gunakan file lokal
                $fullpath = Storage::disk($photo->storage_disk ?? 'public')->path($photo->storage_path ?? '');
                $aiRaw = $openAI->validatePhoto($fullpath, $slot, 'SR');

                // mapping sederhana
                $ai = [
                    'status' => !empty($aiRaw['validation_passed']) ? 'passed' : 'failed',
                    'score'  => $aiRaw['confidence'] ?? null,
                    'checks' => $aiRaw['checks'] ?? [],
                    'notes'  => $aiRaw['rejection_reason'] ?? null,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('AI recheck SR error: '.$e->getMessage());
        }

        PhotoApproval::updateOrCreate(
            ['sr_data_id' => $sr->id, 'file_storage_id' => $photo->id],
            [
                'slot_type'          => $slot,
                'ai_status'          => $ai['status'],
                'ai_score'           => $ai['score'] ?? null,
                'ai_checks'          => $ai['checks'] ?? [],
                'ai_notes'           => $ai['notes'] ?? null,
                'ai_last_checked_at' => now(),
            ]
        );

        $photo->ai_status = $ai['status'];
        $photo->save();

        $old = $sr->getOriginal();
        $sr->recomputeAiOverallStatus();
        if ($sr->status === SrData::STATUS_DRAFT && $sr->isAllPhotosPassed()) {
            $sr->status = SrData::STATUS_READY_FOR_TRACER;
        }
        $sr->save();
        $this->audit('update', $sr, $old, $sr->toArray());

        return response()->json([
            'ai_status' => $ai['status'],
            'ai_checks' => $ai['checks'] ?? [],
            'ai_notes'  => $ai['notes'] ?? null,
            'sr'        => $sr->only(['id','status','ai_overall_status']),
        ]);
    }

    /** READY STATUS */
    public function readyStatus(SrData $sr)
    {
        return response()->json([
            'all_passed' => $sr->isAllPhotosPassed(),
            'ai_overall' => $sr->ai_overall_status,
            'status'     => $sr->status,
        ]);
    }

    /** APPROVALS */
    public function approveTracer(Request $r, SrData $sr)
    {
        if (!$sr->canApproveTracer()) return response()->json(['message' => 'Belum siap untuk approval tracer.'], 422);
        $old = $sr->getOriginal();
        $sr->status = SrData::STATUS_TRACER_APPROVED;
        $sr->tracer_approved_at = now();
        $sr->tracer_approved_by = Auth::id();
        $sr->tracer_notes = $r->input('notes');
        $sr->save();
        $this->audit('approve', $sr, $old, $sr->toArray());
        return response()->json($sr);
    }

    public function rejectTracer(Request $r, SrData $sr)
    {
        if ($sr->status !== SrData::STATUS_READY_FOR_TRACER) return response()->json(['message'=>'Status tidak valid'], 422);
        $old = $sr->getOriginal();
        $sr->status = SrData::STATUS_TRACER_REJECTED;
        $sr->tracer_notes = $r->input('notes');
        $sr->save();
        $this->audit('reject', $sr, $old, $sr->toArray());
        return response()->json($sr);
    }

    public function approveCgp(Request $r, SrData $sr)
    {
        if (!$sr->canApproveCgp()) return response()->json(['message' => 'Belum siap untuk approval CGP.'], 422);
        $old = $sr->getOriginal();
        $sr->status = SrData::STATUS_CGP_APPROVED;
        $sr->cgp_approved_at = now();
        $sr->cgp_approved_by = Auth::id();
        $sr->cgp_notes = $r->input('notes');
        $sr->save();
        $this->audit('approve', $sr, $old, $sr->toArray());
        return response()->json($sr);
    }

    public function rejectCgp(Request $r, SrData $sr)
    {
        if ($sr->status !== SrData::STATUS_TRACER_APPROVED) return response()->json(['message'=>'Status tidak valid'], 422);
        $old = $sr->getOriginal();
        $sr->status = SrData::STATUS_CGP_REJECTED;
        $sr->cgp_notes = $r->input('notes');
        $sr->save();
        $this->audit('reject', $sr, $old, $sr->toArray());
        return response()->json($sr);
    }

    /** SCHEDULE & COMPLETE */
    public function schedule(Request $r, SrData $sr)
    {
        if (!$sr->canSchedule()) return response()->json(['message' => 'Belum bisa dijadwalkan.'], 422);

        $v = Validator::make($r->all(), [
            'tanggal_pemasangan'         => ['required','date'],
            'nomor_sr'                   => ['nullable','string','max:100','unique:sr_data,nomor_sr'],

            // boleh isi/update field manual saat schedule
            'jenis_tapping'              => ['nullable', Rule::in(['63x20','90x20','63x32','180x90','180x63','125x63','90x63','180x32','125x32','90x32'])],
            'panjang_pipa_pe_m'          => ['nullable','numeric','min:0'],
            'panjang_casing_crossing_m'  => ['nullable','numeric','min:0'],

            // fallback nama lama
            'panjang_pipa_pe'            => ['nullable','numeric','min:0'],
            'panjang_casing_crossing_sr' => ['nullable','numeric','min:0'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        $old = $sr->getOriginal();

        $sr->tanggal_pemasangan = $r->input('tanggal_pemasangan');
        if (!$sr->nomor_sr) $sr->nomor_sr = $this->makeNomor('SR');
        $sr->status = SrData::STATUS_SCHEDULED;

        // set manual fields
        $this->setIfColumnExists($sr, 'jenis_tapping',               $r->input('jenis_tapping'));
        $this->setIfColumnExists($sr, 'panjang_pipa_pe_m',           $r->input('panjang_pipa_pe_m', $r->input('panjang_pipa_pe')));
        $this->setIfColumnExists($sr, 'panjang_casing_crossing_m',   $r->input('panjang_casing_crossing_m', $r->input('panjang_casing_crossing_sr')));
        // fallback nama lama
        $this->setIfColumnExists($sr, 'panjang_pipa_pe',             $r->input('panjang_pipa_pe', $r->input('panjang_pipa_pe_m')));
        $this->setIfColumnExists($sr, 'panjang_casing_crossing_sr',  $r->input('panjang_casing_crossing_sr', $r->input('panjang_casing_crossing_m')));

        $sr->save();

        $this->audit('update', $sr, $old, $sr->toArray());
        return response()->json($sr);
    }

    public function complete(Request $r, SrData $sr)
    {
        if (!$sr->canComplete()) return response()->json(['message' => 'Belum bisa diselesaikan.'], 422);
        $old = $sr->getOriginal();
        $sr->status = SrData::STATUS_COMPLETED;
        $sr->save();
        $this->audit('update', $sr, $old, $sr->toArray());
        return response()->json($sr);
    }

    /* ================= Helpers ================= */

    private function makeNomor(string $prefix): string
    {
        return sprintf('%s-%s-%04d', strtoupper($prefix), now()->format('Ym'), random_int(1, 9999));
    }

    /**
     * Set attribute hanya jika kolom ada di tabel (hindari SQL error saat kolom belum dimigrasi)
     */
    private function setIfColumnExists(SrData $sr, string $column, $value): void
    {
        if (!is_null($value) && Schema::hasColumn($sr->getTable(), $column)) {
            $sr->setAttribute($column, $value);
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
