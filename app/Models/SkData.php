<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use App\Models\FileStorage;
use App\Models\PhotoApproval;

/**
 * Class SkData
 *
 * @property int $id
 * @property string $reff_id_pelanggan
 * @property string|null $nomor_sk
 * @property string $status
 * @property string|null $module_status
 * @property string|null $overall_photo_status
 * @property \Carbon\Carbon|null $tanggal_instalasi
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
 * @property float|null $panjang_pipa_gl_medium_m
 * @property int|null $qty_elbow_1_2_galvanis
 * @property int|null $qty_sockdraft_galvanis_1_2
 * @property int|null $qty_ball_valve_1_2
 * @property int|null $qty_nipel_selang_1_2
 * @property int|null $qty_elbow_reduce_3_4_1_2
 * @property int|null $qty_long_elbow_3_4_male_female
 * @property int|null $qty_klem_pipa_1_2
 * @property int|null $qty_double_nipple_1_2
 * @property int|null $qty_seal_tape
 * @property int|null $qty_tee_1_2
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
class SkData extends BaseModuleModel
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

    protected $table = 'sk_data';

    protected $fillable = [
        'calon_pelanggan_id',
        'reff_id_pelanggan',
        'nomor_sk',
        'status',
        'module_status',
        'overall_photo_status',
        'tanggal_instalasi',
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
        'panjang_pipa_gl_medium_m',
        'qty_elbow_1_2_galvanis',
        'qty_sockdraft_galvanis_1_2',
        'qty_ball_valve_1_2',
        'qty_nipel_selang_1_2',
        'qty_elbow_reduce_3_4_1_2',
        'qty_long_elbow_3_4_male_female',
        'qty_klem_pipa_1_2',
        'qty_double_nipple_1_2',
        'qty_seal_tape',
        'qty_tee_1_2',
    ];

    protected $appends = ['status_badge', 'material_summary'];

    public function getModuleName(): string
    {
        return 'sk';
    }

    public function getRequiredPhotos(): array
    {
        return ['pneumatic_start', 'pneumatic_finish', 'valve', 'isometrik_scan', 'berita_acara'];
    }

    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            'tanggal_instalasi' => 'date',
            'ai_checked_at' => 'datetime',
            'panjang_pipa_gl_medium_m' => 'decimal:2',
            'qty_elbow_1_2_galvanis' => 'integer',
            'qty_sockdraft_galvanis_1_2' => 'integer',
            'qty_ball_valve_1_2' => 'integer',
            'qty_nipel_selang_1_2' => 'integer',
            'qty_elbow_reduce_3_4_1_2' => 'integer',
            'qty_long_elbow_3_4_male_female' => 'integer',
            'qty_klem_pipa_1_2' => 'integer',
            'qty_double_nipple_1_2' => 'integer',
            'qty_seal_tape' => 'integer',
            'qty_tee_1_2' => 'integer',
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
        if (Schema::hasColumn('file_storages', 'sk_data_id')) {
            return $this->hasMany(FileStorage::class, 'sk_data_id');
        }
        return $this->hasMany(FileStorage::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function photoApprovals(): HasMany
    {
        if (Schema::hasColumn('photo_approvals', 'sk_data_id')) {
            return $this->hasMany(PhotoApproval::class, 'sk_data_id');
        }
        return $this->hasMany(PhotoApproval::class, 'reff_id_pelanggan', 'reff_id_pelanggan')
            ->where('module_name', 'sk');
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
            'pipa_gl_medium' => $this->panjang_pipa_gl_medium_m ?? 0,
            'total_fitting' => $this->getTotalFittingQty(),
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
        $items = array_merge($this->getRequiredMaterialItems(), $this->getOptionalMaterialItems());

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

        return $details;
    }

    public function getRequiredMaterialItems(): array
    {
        return [
            'panjang_pipa_gl_medium_m' => $this->panjang_pipa_gl_medium_m,
            'qty_elbow_1_2_galvanis' => $this->qty_elbow_1_2_galvanis,
            'qty_sockdraft_galvanis_1_2' => $this->qty_sockdraft_galvanis_1_2,
            'qty_ball_valve_1_2' => $this->qty_ball_valve_1_2,
            'qty_nipel_selang_1_2' => $this->qty_nipel_selang_1_2,
            'qty_elbow_reduce_3_4_1_2' => $this->qty_elbow_reduce_3_4_1_2,
            'qty_long_elbow_3_4_male_female' => $this->qty_long_elbow_3_4_male_female,
            'qty_klem_pipa_1_2' => $this->qty_klem_pipa_1_2,
            'qty_double_nipple_1_2' => $this->qty_double_nipple_1_2,
            'qty_seal_tape' => $this->qty_seal_tape,
        ];
    }

    public function getOptionalMaterialItems(): array
    {
        return [
            'qty_tee_1_2' => $this->qty_tee_1_2,
        ];
    }

    public function getTotalFittingQty(): int
    {
        return collect($this->getRequiredMaterialItems())
            ->except('panjang_pipa_gl_medium_m')
            ->sum() + ($this->qty_tee_1_2 ?? 0);
    }

    public function isMaterialComplete(): bool
    {
        foreach ($this->getRequiredMaterialItems() as $key => $value) {
            if ($key === 'panjang_pipa_gl_medium_m') {
                if (is_null($value) || $value <= 0) {
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
            'panjang_pipa_gl_medium_m' => 'required|numeric|min:0|max:1000',
            'qty_elbow_1_2_galvanis' => 'required|integer|min:0|max:100',
            'qty_sockdraft_galvanis_1_2' => 'required|integer|min:0|max:100',
            'qty_ball_valve_1_2' => 'required|integer|min:0|max:100',
            'qty_nipel_selang_1_2' => 'required|integer|min:0|max:100',
            'qty_elbow_reduce_3_4_1_2' => 'required|integer|min:0|max:100',
            'qty_long_elbow_3_4_male_female' => 'required|integer|min:0|max:100',
            'qty_klem_pipa_1_2' => 'required|integer|min:0|max:100',
            'qty_double_nipple_1_2' => 'required|integer|min:0|max:100',
            'qty_seal_tape' => 'required|integer|min:0|max:100',
            'qty_tee_1_2' => 'nullable|integer|min:0|max:100',
        ];
    }

    public function getMaterialLabels(): array
    {
        return [
            'panjang_pipa_gl_medium_m' => 'Panjang Pipa 1/2" GL Medium (meter)',
            'qty_elbow_1_2_galvanis' => 'Elbow 1/2" Galvanis (Pcs)',
            'qty_sockdraft_galvanis_1_2' => 'SockDraft Galvanis Dia 1/2" (Pcs)',
            'qty_ball_valve_1_2' => 'Ball Valve 1/2" (Pcs)',
            'qty_nipel_selang_1_2' => 'Nipel Selang 1/2" (Pcs)',
            'qty_elbow_reduce_3_4_1_2' => 'Elbow Reduce 3/4" x 1/2" (Pcs)',
            'qty_long_elbow_3_4_male_female' => 'Long Elbow 3/4" Male Female (Pcs)',
            'qty_klem_pipa_1_2' => 'Klem Pipa 1/2" (Pcs)',
            'qty_double_nipple_1_2' => 'Double Nipple 1/2" (Pcs)',
            'qty_seal_tape' => 'Seal Tape (Pcs)',
            'qty_tee_1_2' => 'Tee 1/2" (Pcs) - Opsional',
        ];
    }

    /**
     * Check if this SK data can be edited by current user
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

        // User role SK hanya bisa edit draft dan rejected
        if ($user->hasAnyRole(['sk'])) {
            return in_array($this->module_status, ['draft', 'rejected']);
        }

        return false;
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

    public function canComplete(): bool
    {
        return $this->status === self::STATUS_SCHEDULED && !empty($this->tanggal_instalasi);
    }
}
