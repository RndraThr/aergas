<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;        // <— tambah
use App\Models\FileStorage;                   // pastikan sudah ada
use App\Models\PhotoApproval;

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
        'calon_pelanggan_id','reff_id_pelanggan','nomor_sk','status',
        'tanggal_instalasi','notes','ai_overall_status','ai_checked_at',
        'tracer_approved_at','tracer_approved_by','tracer_notes',
        'cgp_approved_at','cgp_approved_by','cgp_notes',
        'created_by','updated_by',
    ];

    protected $appends = ['status_badge'];

    // === BaseModuleModel abstract implementation ===
    public function getModuleName(): string
    {
        return 'sk';
    }

    public function getRequiredPhotos(): array
    {
        // slot wajib untuk SK
        return ['lokasi_meter','jalur_pipa','kompor','regulator','lainnya'];
    }

    // === Casts (HARUS public & kompatibel) ===
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            'tanggal_instalasi' => 'date',
            'ai_checked_at'     => 'datetime',
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

    /** ================= Relations ================= */

    public function files(): HasMany
    {
        // Kalau tabel file_storages punya sk_data_id, pakai itu.
        if (Schema::hasColumn('file_storages', 'sk_data_id')) {
            return $this->hasMany(FileStorage::class, 'sk_data_id');
        }
        // Fallback: pakai reff_id_pelanggan
        return $this->hasMany(FileStorage::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function photoApprovals(): HasMany
    {
        // Kalau tabel photo_approvals punya sk_data_id, pakai itu (kompat).
        if (Schema::hasColumn('photo_approvals', 'sk_data_id')) {
            return $this->hasMany(PhotoApproval::class, 'sk_data_id');
        }
        // Fallback: reff_id_pelanggan + filter module 'sk'
        return $this->hasMany(PhotoApproval::class, 'reff_id_pelanggan', 'reff_id_pelanggan')
            ->where('module_name', 'sk');
    }

    /** ================= Helpers ================= */

    public function recomputeAiOverallStatus(): void
    {
        $module = strtoupper($this->getModuleName()); // 'SK'
        $min = (int) config('aergas_photos.modules.' . $module . '.min_required_slots', 0);

        $passed = $this->photoApprovals()
            ->where('ai_status', 'passed')
            ->count();

        $this->ai_overall_status = $passed >= $min ? 'ready' : 'pending';
    }

    public function isAllPhotosPassed(): bool
    {
        $module = strtoupper($this->getModuleName()); // 'SK'
        $min = (int) config('aergas_photos.modules.' . $module . '.min_required_slots', 0);

        $passed = $this->photoApprovals()
            ->where('ai_status', 'passed')
            ->count();

        return $passed >= $min;
    }


}
