<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'calon_pelanggan_id','reff_id_pelanggan','nomor_sr','status',
        'panjang_pipa_pe','panjang_casing_crossing_sr',
        'tanggal_pemasangan','notes','ai_overall_status','ai_checked_at',
        'tracer_approved_at','tracer_approved_by','tracer_notes',
        'cgp_approved_at','cgp_approved_by','cgp_notes',
        'created_by','updated_by',
    ];


    protected $appends = ['status_badge'];

    // === BaseModuleModel abstract implementation ===
    public function getModuleName(): string
    {
        return 'sr';
    }

    public function getRequiredPhotos(): array
    {
        // slot wajib untuk SR (bisa kamu sesuaikan lagi)
        return ['lokasi_meter','jalur_pipa','sambungan','valve','lainnya'];
    }

    // === Casts (HARUS public & kompatibel) ===
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            'panjang_pipa_pe'            => 'decimal:2',
            'panjang_casing_crossing_sr' => 'decimal:2',
            'tanggal_pemasangan'         => 'date',
            'ai_checked_at'              => 'datetime',
        ]);
    }

    // === RELATIONS ===
    public function calonPelanggan()
    {
        return $this->belongsTo(CalonPelanggan::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function tracerApprovedBy(): BelongsTo { return $this->belongsTo(User::class, 'tracer_approved_by'); }
    public function cgpApprovedBy(): BelongsTo { return $this->belongsTo(User::class, 'cgp_approved_by'); }

    public function files(): HasMany { return $this->hasMany(FileStorage::class, 'sr_data_id'); }

    public function photoApprovals(): HasMany
    {
        return $this->hasMany(PhotoApproval::class, 'sr_data_id');
    }

    public function auditLogs() { return $this->morphMany(AuditLog::class, 'auditable'); }

    // === SCOPES ===
    public function scopeReadyForTracer($q) { return $q->where('status', self::STATUS_READY_FOR_TRACER); }

    // === HELPERS ===
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

    public function recomputeAiOverallStatus(): void
    {
        $counts = $this->photoApprovals()
            ->selectRaw("SUM(ai_status='passed') as passed, SUM(ai_status='flagged') as flagged, COUNT(*) as total")
            ->first();

        if (!$counts || $counts->total == 0) {
            $this->ai_overall_status = 'pending';
        } elseif ((int)$counts->flagged > 0) {
            $this->ai_overall_status = 'flagged';
        } elseif ((int)$counts->passed === (int)$counts->total) {
            $this->ai_overall_status = 'passed';
        } else {
            $this->ai_overall_status = 'pending';
        }
        $this->ai_checked_at = now();
    }

    public function isAllPhotosPassed(): bool
    {
        return $this->photoApprovals()->where('ai_status', '!=', 'passed')->count() === 0
            && $this->photoApprovals()->exists();
    }

    public function canSubmit(): bool         { return $this->status === self::STATUS_DRAFT && $this->isAllPhotosPassed(); }
    public function canApproveTracer(): bool   { return $this->status === self::STATUS_READY_FOR_TRACER; }
    public function canApproveCgp(): bool      { return $this->status === self::STATUS_TRACER_APPROVED; }
    public function canSchedule(): bool        { return $this->status === self::STATUS_CGP_APPROVED; }
    public function canComplete(): bool        { return $this->status === self::STATUS_SCHEDULED && !empty($this->tanggal_pemasangan); }
}
