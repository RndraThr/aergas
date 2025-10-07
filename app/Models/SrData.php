<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

/**
 * Class SrData
 *
 * @property string $reff_id_pelanggan
 * @property string $status
 * @property string|null $module_status
 * @property string|null $overall_photo_status
 * @property \Carbon\Carbon|null $tanggal_pemasangan
 * @property string|null $notes
 * @property string|null $ai_overall_status
 * @property \Carbon\Carbon|null $ai_checked_at
 * @property \Carbon\Carbon|null $tracer_approved_at
 * @property int|null $tracer_approved_by
 * @property string|null $tracer_notes
 * @property \Carbon\Carbon|null $cgp_approved_at
 * @property int|null $cgp_approved_by
 * @property string|null $cgp_notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property string|null $jenis_tapping
 * @property int|null $qty_tapping_saddle
 * @property int|null $qty_coupler_20mm
 * @property float|null $panjang_pipa_pe_20mm_m
 * @property int|null $qty_elbow_90x20
 * @property int|null $qty_transition_fitting
 * @property float|null $panjang_pondasi_tiang_sr_m
 * @property float|null $panjang_pipa_galvanize_3_4_m
 * @property int|null $qty_klem_pipa
 * @property int|null $qty_ball_valve_3_4
 * @property int|null $qty_double_nipple_3_4
 * @property int|null $qty_long_elbow_3_4
 * @property int|null $qty_regulator_service
 * @property int|null $qty_coupling_mgrt
 * @property int|null $qty_meter_gas_rumah_tangga
 * @property float|null $panjang_casing_1_inch_m
 * @property int|null $qty_sealtape
 * @property string|null $no_seri_mgrt
 * @property string|null $merk_brand_mgrt
 * @property float|null $panjang_pipa_pe
 * @property float|null $panjang_casing_crossing_sr
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read \App\Models\CalonPelanggan|null $calonPelanggan
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\User|null $updatedBy
 * @property-read \App\Models\User|null $tracerApprovedBy
 * @property-read \App\Models\User|null $cgpApprovedBy
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PhotoApproval[] $photoApprovals
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FileStorage[] $files
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AuditLog[] $auditLogs
 * @property-read string $status_badge
 * @property-read array $material_summary
 */
class SrData extends BaseModuleModel
{
    use SoftDeletes;

    public const STATUS_DRAFT            = 'draft';
    public const STATUS_READY_FOR_TRACER = 'ready_for_tracer';
    public const STATUS_TRACER_APPROVED  = 'tracer_approved';
    public const STATUS_TRACER_REJECTED  = 'tracer_rejected';
    public const STATUS_CGP_APPROVED     = 'cgp_approved';
    public const STATUS_CGP_REJECTED     = 'cgp_rejected';
    public const STATUS_SCHEDULED        = 'approved_scheduled';
    public const STATUS_COMPLETED        = 'completed';
    public const STATUS_CANCELED         = 'canceled';

    protected $table = 'sr_data';

    protected $fillable = [
        'reff_id_pelanggan',
        'status',
        'module_status',
        'overall_photo_status',
        'tanggal_pemasangan',
        'notes',
        'ai_overall_status',
        'ai_checked_at',
        'tracer_approved_at',
        'tracer_approved_by',
        'tracer_notes',
        'cgp_approved_at',
        'cgp_approved_by',
        'cgp_notes',
        'created_by',
        'updated_by',
        'jenis_tapping',
        'qty_tapping_saddle',
        'qty_coupler_20mm',
        'panjang_pipa_pe_20mm_m',
        'qty_elbow_90x20',
        'qty_transition_fitting',
        'panjang_pondasi_tiang_sr_m',
        'panjang_pipa_galvanize_3_4_m',
        'qty_klem_pipa',
        'qty_ball_valve_3_4',
        'qty_double_nipple_3_4',
        'qty_long_elbow_3_4',
        'qty_regulator_service',
        'qty_coupling_mgrt',
        'qty_meter_gas_rumah_tangga',
        'panjang_casing_1_inch_m',
        'qty_sealtape',
        'no_seri_mgrt',
        'merk_brand_mgrt',
        'panjang_pipa_pe',
        'panjang_casing_crossing_sr',
    ];

    protected $appends = ['status_badge', 'material_summary'];

    public function getModuleName(): string
    {
        return 'sr';
    }

    public function getRequiredPhotos(): array
    {
        return ['pneumatic_start', 'pneumatic_finish', 'jenis_tapping', 'mgrt', 'pondasi', 'isometrik_scan'];
    }

    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            'tanggal_pemasangan' => 'date',
            'ai_checked_at' => 'datetime',
            'panjang_pipa_pe_20mm_m' => 'decimal:2',
            'panjang_pondasi_tiang_sr_m' => 'decimal:2',
            'panjang_pipa_galvanize_3_4_m' => 'decimal:2',
            'panjang_casing_1_inch_m' => 'decimal:2',
            'panjang_pipa_pe_m' => 'decimal:2',
            'panjang_casing_crossing_m' => 'decimal:2',
            'panjang_pipa_pe' => 'decimal:2',
            'panjang_casing_crossing_sr' => 'decimal:2',
            'qty_tapping_saddle' => 'integer',
            'qty_coupler_20mm' => 'integer',
            'qty_elbow_90x20' => 'integer',
            'qty_transition_fitting' => 'integer',
            'qty_klem_pipa' => 'integer',
            'qty_ball_valve_3_4' => 'integer',
            'qty_double_nipple_3_4' => 'integer',
            'qty_long_elbow_3_4' => 'integer',
            'qty_regulator_service' => 'integer',
            'qty_coupling_mgrt' => 'integer',
            'qty_meter_gas_rumah_tangga' => 'integer',
            'qty_sealtape' => 'integer',
        ]);
    }

    public function calonPelanggan()
    {
        return $this->belongsTo(CalonPelanggan::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function tracerApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tracer_approved_by');
    }

    public function cgpApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cgp_approved_by');
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function files(): HasMany
    {
        if (Schema::hasColumn('file_storages', 'sr_data_id')) {
            return $this->hasMany(FileStorage::class, 'sr_data_id');
        }
        return $this->hasMany(FileStorage::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function photoApprovals(): HasMany
    {
        if (Schema::hasColumn('photo_approvals', 'sr_data_id')) {
            return $this->hasMany(PhotoApproval::class, 'sr_data_id');
        }
        return $this->hasMany(PhotoApproval::class, 'reff_id_pelanggan', 'reff_id_pelanggan')
            ->where('module_name', 'sr');
    }

    public function scopeReadyForTracer($q)
    {
        return $q->where('status', self::STATUS_READY_FOR_TRACER);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT            => 'secondary',
            self::STATUS_READY_FOR_TRACER => 'info',
            self::STATUS_TRACER_APPROVED  => 'primary',
            self::STATUS_TRACER_REJECTED  => 'danger',
            self::STATUS_CGP_APPROVED     => 'success',
            self::STATUS_CGP_REJECTED     => 'danger',
            self::STATUS_SCHEDULED        => 'warning',
            self::STATUS_COMPLETED        => 'success',
            self::STATUS_CANCELED         => 'dark',
            default                       => 'secondary',
        };
    }

    public function getMaterialSummaryAttribute(): array
    {
        return [
            'total_items' => $this->getTotalItemQty(),
            'total_lengths' => $this->getTotalLengthsQty(),
            'required_items' => $this->getRequiredMaterialItems(),
            'optional_items' => $this->getOptionalMaterialItems(),
            'is_complete' => $this->isMaterialComplete(),
            'details' => $this->getFormattedMaterialDetails(),
        ];
    }

    /**
     * Get formatted material details for reports
     */
    public function getFormattedMaterialDetails(): array
    {
        $labels = $this->getMaterialLabels();
        $items = $this->getRequiredMaterialItems();

        $details = [];
        foreach ($items as $key => $value) {
            // Skip if value is null, 0, 0.00, or empty
            if (is_null($value) || $value === 0 || $value === 0.00 || $value === '' || (float)$value == 0) {
                continue;
            }

            $label = $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
            $details[] = [
                'label' => $label,
                'value' => $value,
                'unit' => str_contains($key, 'panjang_') ? 'm' : 'pcs'
            ];
        }

        // Add optional non-empty items
        $optionalItems = $this->getOptionalMaterialItems();
        foreach ($optionalItems as $key => $value) {
            if (!is_null($value) && $value !== '' && $value !== 0) {
                $label = $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
                $details[] = [
                    'label' => $label,
                    'value' => $value,
                    'unit' => '' // Text fields like jenis_tapping, no_seri, merk
                ];
            }
        }

        return $details;
    }

    public function getRequiredMaterialItems(): array
    {
        return [
            'qty_tapping_saddle' => $this->qty_tapping_saddle,
            'qty_coupler_20mm' => $this->qty_coupler_20mm,
            'panjang_pipa_pe_20mm_m' => $this->panjang_pipa_pe_20mm_m,
            'qty_elbow_90x20' => $this->qty_elbow_90x20,
            'qty_transition_fitting' => $this->qty_transition_fitting,
            'panjang_pondasi_tiang_sr_m' => $this->panjang_pondasi_tiang_sr_m,
            'panjang_pipa_galvanize_3_4_m' => $this->panjang_pipa_galvanize_3_4_m,
            'qty_klem_pipa' => $this->qty_klem_pipa,
            'qty_ball_valve_3_4' => $this->qty_ball_valve_3_4,
            'qty_double_nipple_3_4' => $this->qty_double_nipple_3_4,
            'qty_long_elbow_3_4' => $this->qty_long_elbow_3_4,
            'qty_regulator_service' => $this->qty_regulator_service,
            'qty_coupling_mgrt' => $this->qty_coupling_mgrt,
            'qty_meter_gas_rumah_tangga' => $this->qty_meter_gas_rumah_tangga,
            'panjang_casing_1_inch_m' => $this->panjang_casing_1_inch_m,
            'qty_sealtape' => $this->qty_sealtape,
        ];
    }

    public function getOptionalMaterialItems(): array
    {
        return [
            'jenis_tapping' => $this->jenis_tapping,
            'no_seri_mgrt' => $this->no_seri_mgrt,
            'merk_brand_mgrt' => $this->merk_brand_mgrt,
        ];
    }

    public function getTotalItemQty(): int
    {
        return collect($this->getRequiredMaterialItems())
            ->filter(fn($value, $key) => !str_contains($key, 'panjang_'))
            ->sum();
    }

    public function getTotalLengthsQty(): float
    {
        return collect($this->getRequiredMaterialItems())
            ->filter(fn($value, $key) => str_contains($key, 'panjang_'))
            ->sum();
    }

    public function isMaterialComplete(): bool
    {
        foreach ($this->getRequiredMaterialItems() as $key => $value) {
            if (str_contains($key, 'panjang_')) {
                // ✅ Ubah dari $value <= 0 menjadi $value < 0
                if (is_null($value) || $value < 0) {
                    return false;
                }
            } else {
                if (is_null($value) || $value < 0) {
                    return false;
                }
            }
        }
        return true;
    }

    public function getMaterialValidationRules(): array
    {
        return [
            'qty_tapping_saddle' => 'required|integer|min:0|max:100',
            'qty_coupler_20mm' => 'required|integer|min:0|max:100',
            'panjang_pipa_pe_20mm_m' => 'required|numeric|min:0|max:1000', // ✅ Ubah dari min:0.1
            'qty_elbow_90x20' => 'required|integer|min:0|max:100',
            'qty_transition_fitting' => 'required|integer|min:0|max:100',
            'panjang_pondasi_tiang_sr_m' => 'required|numeric|min:0|max:100', // ✅ Ubah dari min:0.1
            'panjang_pipa_galvanize_3_4_m' => 'required|numeric|min:0|max:100', // ✅ Ubah dari min:0.1
            'qty_klem_pipa' => 'required|integer|min:0|max:100',
            'qty_ball_valve_3_4' => 'required|integer|min:0|max:100',
            'qty_double_nipple_3_4' => 'required|integer|min:0|max:100',
            'qty_long_elbow_3_4' => 'required|integer|min:0|max:100',
            'qty_regulator_service' => 'required|integer|min:0|max:100',
            'qty_coupling_mgrt' => 'required|integer|min:0|max:100',
            'qty_meter_gas_rumah_tangga' => 'required|integer|min:0|max:100',
            'panjang_casing_1_inch_m' => 'required|numeric|min:0|max:100', // ✅ Ubah dari min:0.1
            'qty_sealtape' => 'required|integer|min:0|max:100',
            'jenis_tapping' => 'nullable|in:63x20,90x20,63x32,180x90,180x63,125x63,90x63,180x32,125x32,90x32',
            'no_seri_mgrt' => 'nullable|string|max:50',
            'merk_brand_mgrt' => 'nullable|string|max:50',
            'panjang_pipa_pe_m' => 'nullable|numeric|min:0|max:1000',
            'panjang_casing_crossing_m' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function getMaterialLabels(): array
    {
        return [
            'qty_tapping_saddle' => 'Tapping Saddle (Pcs)',
            'qty_coupler_20mm' => 'Coupler 20 mm (Pcs)',
            'panjang_pipa_pe_20mm_m' => 'Pipa PE 20 mm (meter)',
            'qty_elbow_90x20' => 'Elbow 90 x 20 mm (Pcs)',
            'qty_transition_fitting' => 'Transition Fitting (Pcs)',
            'panjang_pondasi_tiang_sr_m' => 'Pondasi Tiang SR (meter)',
            'panjang_pipa_galvanize_3_4_m' => 'Pipa Galvanize 3/4" (meter)',
            'qty_klem_pipa' => 'Klem Pipa (Pcs)',
            'qty_ball_valve_3_4' => 'Ball Valve 3/4" (Pcs)',
            'qty_double_nipple_3_4' => 'Double Nipple 3/4" (Pcs)',
            'qty_long_elbow_3_4' => 'Long Elbow 3/4" (Pcs)',
            'qty_regulator_service' => 'Regulator Service (Pcs)',
            'qty_coupling_mgrt' => 'Coupling MGRT (Pcs)',
            'qty_meter_gas_rumah_tangga' => 'Meter Gas Rumah Tangga (Pcs)',
            'panjang_casing_1_inch_m' => 'Casing 1" (meter)',
            'qty_sealtape' => 'Sealtape (Pcs)',
            'jenis_tapping' => 'Jenis Tapping',
            'no_seri_mgrt' => 'No Seri MGRT',
            'merk_brand_mgrt' => 'Merk/Brand MGRT',
        ];
    }

    public function recomputeAiOverallStatus(): void
    {
        $module = strtoupper($this->getModuleName());
        $min = (int) config('aergas_photos.modules.' . $module . '.min_required_slots', 0);

        $passed = $this->photoApprovals()
            ->where('ai_status', 'passed')
            ->count();

        $this->ai_overall_status = $passed >= $min ? 'ready' : 'pending';
    }

    public function isAllPhotosPassed(): bool
    {
        $module = strtoupper($this->getModuleName());
        $min = (int) config('aergas_photos.modules.' . $module . '.min_required_slots', 0);

        $passed = $this->photoApprovals()
            ->where('ai_status', 'passed')
            ->count();

        return $passed >= $min;
    }

    public function canSubmit(): bool
    {
        return $this->status === self::STATUS_DRAFT
            && $this->isAllPhotosPassed()
            && $this->isMaterialComplete();
    }

    public function canApproveTracer(): bool
    {
        return $this->status === self::STATUS_READY_FOR_TRACER;
    }

    public function canApproveCgp(): bool
    {
        return $this->status === self::STATUS_TRACER_APPROVED;
    }

    public function canSchedule(): bool
    {
        return $this->status === self::STATUS_CGP_APPROVED;
    }

    /**
     * Check if this SR data can be edited by current user
     */
    public function canEdit(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // Super admin dan admin selalu bisa edit (kecuali yang sudah completed)
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return !in_array($this->module_status, ['completed']);
        }

        // Tracer bisa edit yang rejected untuk perbaikan
        if ($user->hasAnyRole(['tracer'])) {
            return in_array($this->module_status, ['draft', 'ai_validation', 'tracer_review', 'rejected']);
        }

        // User role SR hanya bisa edit draft dan rejected
        if ($user->hasAnyRole(['sr'])) {
            return in_array($this->module_status, ['draft', 'rejected']);
        }

        return false;
    }

    public function canComplete(): bool
    {
        return $this->status === self::STATUS_SCHEDULED && !empty($this->tanggal_pemasangan);
    }
}
