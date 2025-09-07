<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Base untuk semua modul lapangan (SK, SR, MGRT, GasIn, JalurPipa, Penyambungan).
 * - Relasi standar: pelanggan, approver, photoApprovals
 * - Scopes umum: byReff, completed, pendingApproval, status, overallStatus
 * - Agregasi status foto → overall_photo_status → sync ke module_status
 *
 * Catatan:
 * - Child WAJIB implement getModuleName() & getRequiredPhotos() (nama kanonik TANPA suffix _url)
 * - Jika masih ada field lama pakai *_url, override photoFieldAliases() di child
 */
abstract class BaseModuleModel extends Model
{
    use HasFactory;

    protected $guarded = [];

    public const MODULE_STATUSES = [
        'not_started','draft','ai_validation','tracer_review','cgp_review','completed','rejected',
    ];

    public const OVERALL_PHOTO_STATUSES = [
        'draft','ai_validation','tracer_review','cgp_review','completed','rejected',
    ];

    protected array $approvalCasts = [
        'tracer_approved_at' => 'datetime',
        'cgp_approved_at'    => 'datetime',
    ];

    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), $this->approvalCasts, [
            'module_status'        => 'string',
            'overall_photo_status' => 'string',
        ]);
    }

    // -------------------- Relations --------------------

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(CalonPelanggan::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function tracerApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tracer_approved_by');
    }

    public function cgpApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cgp_approved_by');
    }

    /**
     * Relasi ke detail approval foto untuk modul ini.
     * Dibatasi oleh module_name sesuai implementasi child::getModuleName().
     */
    public function photoApprovals(): HasMany
    {
        return $this->hasMany(PhotoApproval::class, 'reff_id_pelanggan', 'reff_id_pelanggan')
            ->where('module_name', $this->getModuleName());
    }

    // -------------------- Scopes --------------------

    public function scopeByReff($q, string $reff) { return $q->where('reff_id_pelanggan', $reff); }

    public function scopeCompleted($q) { return $q->where('module_status', 'completed'); }

    public function scopePendingApproval($q) { return $q->whereIn('module_status', ['tracer_review','cgp_review']); }

    public function scopeStatus($q, string $status) { return $q->where('module_status', $status); }

    public function scopeOverallStatus($q, string $status) { return $q->where('overall_photo_status', $status); }

    // -------------------- Foto helpers --------------------

    /**
     * Map alias nama foto lama → nama kanonik (override di child jika perlu).
     * Contoh di SK: ['foto_berita_acara_url' => 'foto_berita_acara', ...]
     */
    protected function photoFieldAliases(): array { return []; }

    /**
     * Hitung ulang overall_photo_status dari tabel photo_approvals sesuai required photos.
     */
    public function recalcOverallPhotoStatus(): string
    {
        $required = collect($this->getRequiredPhotos())->filter()->values();
        if ($required->isEmpty()) return 'completed';

        $aliases = $this->photoFieldAliases();

        $rows = $this->photoApprovals()
            ->get(['photo_field_name','photo_status'])
            ->mapWithKeys(function ($r) use ($aliases) {
                $key = $aliases[$r->photo_field_name] ?? $r->photo_field_name;
                return [$key => $r->photo_status];
            });

        // ada foto wajib yang belum pernah diupload
        if ($required->some(fn($f) => !isset($rows[$f]))) return 'draft';

        $statuses = $rows->only(...$required)->values();

        if ($statuses->contains('ai_rejected') || $statuses->contains('tracer_rejected') || $statuses->contains('cgp_rejected')) {
            return 'rejected';
        }
        if ($statuses->every(fn($s) => $s === 'cgp_approved')) {
            return 'completed';
        }
        if ($statuses->contains('cgp_pending') || $statuses->contains('tracer_approved')) {
            return 'cgp_review';
        }
        if ($statuses->contains('tracer_pending') || $statuses->contains('ai_approved')) {
            return 'tracer_review';
        }
        if ($statuses->contains('ai_pending')) {
            return 'ai_validation';
        }

        return 'draft';
    }

    /**
     * Semua required foto boleh submit jika min. sudah AI approved (or lebih).
     */
    public function canSubmit(): bool
    {
        $statuses = collect($this->getAllPhotosStatus())->values();
        if ($statuses->isEmpty()) return true;

        $ok = ['ai_approved','tracer_pending','tracer_approved','cgp_pending','cgp_approved'];
        return $statuses->every(fn($s) => in_array($s, $ok, true));
    }

    /**
     * Sinkron module_status dari overall_photo_status (1:1 mapping).
     */
    public function syncModuleStatusFromPhotos(bool $save = true): string
    {
        $overall = $this->recalcOverallPhotoStatus();

        $map = [
            'draft'         => 'draft',
            'ai_validation' => 'ai_validation',
            'tracer_review' => 'tracer_review',
            'cgp_review'    => 'cgp_review',
            'completed'     => 'completed',
            'rejected'      => 'rejected',
        ];

        $this->overall_photo_status = $overall;
        $this->module_status        = $map[$overall] ?? 'draft';

        if ($save) $this->save();

        return $this->module_status;
    }

    public function getModuleStatusLabelAttribute(): string
    {
        return match ($this->module_status) {
            'not_started'   => 'Belum Mulai',
            'draft'         => 'Draft',
            'ai_validation' => 'Validasi AI',
            'tracer_review' => 'Menunggu Tracer',
            'cgp_review'    => 'Menunggu CGP',
            'completed'     => 'Selesai',
            'rejected'      => 'Ditolak',
            default         => ucfirst(str_replace('_', ' ', (string) $this->module_status)),
        };
    }

    public function getOverallPhotoStatusLabelAttribute(): string
    {
        return match ($this->overall_photo_status) {
            'draft'         => 'Draft',
            'ai_validation' => 'Validasi AI',
            'tracer_review' => 'Menunggu Tracer',
            'cgp_review'    => 'Menunggu CGP',
            'completed'     => 'Selesai',
            'rejected'      => 'Ditolak',
            default         => ucfirst(str_replace('_', ' ', (string) $this->overall_photo_status)),
        };
    }

    /**
     * Ambil status tiap foto wajib (key = nama kanonik).
     */
    public function getAllPhotosStatus(): array
    {
        $required = collect($this->getRequiredPhotos())->filter()->values();
        if ($required->isEmpty()) return [];

        $aliases = $this->photoFieldAliases();

        $rows = $this->photoApprovals()
            ->get(['photo_field_name','photo_status'])
            ->mapWithKeys(function ($r) use ($aliases) {
                $key = $aliases[$r->photo_field_name] ?? $r->photo_field_name;
                return [$key => $r->photo_status];
            });

        $out = [];
        foreach ($required as $name) {
            $out[$name] = $rows[$name] ?? 'draft';
        }
        return $out;
    }

    /**
     * Get missing required slots for this module instance
     */
    public function getMissingRequiredSlots(): array
    {
        $moduleUpper = strtoupper($this->getModuleName());
        $configSlots = config("aergas_photos.modules.{$moduleUpper}.slots", []);
        $requiredSlots = collect($configSlots)->filter(fn($slot) => $slot['required'] ?? false)->keys();
        
        $existingSlots = $this->photoApprovals()
            ->pluck('photo_field_name');
        
        return $requiredSlots->diff($existingSlots)->values()->toArray();
    }

    /**
     * Get completion status for all slots (required + optional)
     */
    public function getSlotCompletionStatus(): array
    {
        $moduleUpper = strtoupper($this->getModuleName());
        $configSlots = config("aergas_photos.modules.{$moduleUpper}.slots", []);
        
        $existingPhotos = $this->photoApprovals()
            ->get()
            ->keyBy('photo_field_name');
        
        $result = [];
        foreach ($configSlots as $slotKey => $slotConfig) {
            $photo = $existingPhotos->get($slotKey);
            
            $result[$slotKey] = [
                'label' => $slotConfig['label'],
                'required' => $slotConfig['required'] ?? false,
                'uploaded' => !is_null($photo),
                'status' => $photo?->photo_status ?? 'missing',
                'photo_id' => $photo?->id,
                'photo_url' => $photo?->photo_url,
                'uploaded_at' => $photo?->uploaded_at?->format('d/m/Y H:i'),
                'approved_by_tracer' => !is_null($photo?->tracer_approved_at),
                'approved_by_cgp' => !is_null($photo?->cgp_approved_at),
            ];
        }
        
        return $result;
    }

    /**
     * Check if all required slots are uploaded
     */
    public function isRequiredSlotsComplete(): bool
    {
        return empty($this->getMissingRequiredSlots());
    }

    /**
     * Get summary completion info
     */
    public function getCompletionSummary(): array
    {
        $moduleUpper = strtoupper($this->getModuleName());
        $configSlots = config("aergas_photos.modules.{$moduleUpper}.slots", []);
        $requiredSlots = collect($configSlots)->filter(fn($slot) => $slot['required'] ?? false);
        $uploadedSlots = $this->photoApprovals()->count();
        $approvedSlots = $this->photoApprovals()->whereNotNull('cgp_approved_at')->count();
        $missingRequired = $this->getMissingRequiredSlots();
        
        return [
            'total_slots' => count($configSlots),
            'required_slots' => $requiredSlots->count(),
            'optional_slots' => count($configSlots) - $requiredSlots->count(),
            'uploaded_slots' => $uploadedSlots,
            'approved_slots' => $approvedSlots,
            'missing_required' => $missingRequired,
            'is_complete' => empty($missingRequired),
            'completion_percentage' => $requiredSlots->count() > 0 
                ? round((($requiredSlots->count() - count($missingRequired)) / $requiredSlots->count()) * 100, 1)
                : 100,
        ];
    }

    // Kontrak yang wajib diimplement oleh child:
    abstract public function getModuleName(): string;
    abstract public function getRequiredPhotos(): array;
}
