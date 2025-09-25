<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

/**
 * Class GasInData
 *
 * @property int $id
 * @property string $reff_id_pelanggan
 * @property string $status
 * @property string|null $module_status
 * @property string|null $overall_photo_status
 * @property \Carbon\Carbon|null $tanggal_gas_in
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
 */
class GasInData extends BaseModuleModel
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

   protected $table = 'gas_in_data';

   protected $fillable = [
       'reff_id_pelanggan',
       'status',
       'module_status',
       'overall_photo_status',
       'tanggal_gas_in',
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
   ];

   protected $appends = ['status_badge'];

   public function getModuleName(): string
   {
       return 'gas_in';
   }

   public function getRequiredPhotos(): array
   {
       return ['ba_gas_in', 'foto_bubble_test', 'foto_regulator', 'foto_kompor_menyala'];
   }

   public function getCasts(): array
   {
       return array_merge(parent::getCasts(), [
           'tanggal_gas_in' => 'date',
           'ai_checked_at' => 'datetime',
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
       if (Schema::hasColumn('file_storages', 'gas_in_data_id')) {
           return $this->hasMany(FileStorage::class, 'gas_in_data_id');
       }
       return $this->hasMany(FileStorage::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
   }

   public function photoApprovals(): HasMany
   {
       if (Schema::hasColumn('photo_approvals', 'gas_in_data_id')) {
           return $this->hasMany(PhotoApproval::class, 'gas_in_data_id');
       }
       return $this->hasMany(PhotoApproval::class, 'reff_id_pelanggan', 'reff_id_pelanggan')
           ->where('module_name', 'gas_in');
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
           && $this->isAllPhotosPassed();
   }

   public function canApproveTracer(): bool
   {
       return $this->status === self::STATUS_READY_FOR_TRACER;
   }

   public function canApproveCgp(): bool
   {
       return $this->status === self::STATUS_TRACER_APPROVED;
   }

   /**
    * Check if this Gas In data can be edited by current user
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

       // User role gas_in hanya bisa edit draft dan rejected
       if ($user->hasAnyRole(['gas_in'])) {
           return in_array($this->module_status, ['draft', 'rejected']);
       }

       return false;
   }

   public function canSchedule(): bool
   {
       return $this->status === self::STATUS_CGP_APPROVED;
   }

   public function canComplete(): bool
   {
       return $this->status === self::STATUS_SCHEDULED && !empty($this->tanggal_gas_in);
   }
}
