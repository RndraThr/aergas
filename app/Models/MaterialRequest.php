<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialRequest extends Model
{
    use SoftDeletes;

    protected $table = 'material_requests';

    public const S_DRAFT     = 'draft';
    public const S_SUBMITTED = 'submitted';
    public const S_APPROVED  = 'approved';
    public const S_ISSUED    = 'issued';
    public const S_CLOSED    = 'closed';
    public const S_CANCELED  = 'canceled';

    protected $fillable = [
        'module_type','module_id',
        'reff_id_pelanggan',
        'request_no','status','notes',
        'created_by','updated_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* RELATIONS */
    public function module(): MorphTo { return $this->morphTo(__FUNCTION__, 'module_type', 'module_id'); }

    public function calonPelanggan(): BelongsTo
    {
        return $this->belongsTo(CalonPelanggan::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function items(): HasMany { return $this->hasMany(MaterialRequestItem::class); }

    /* SCOPES */
    public function scopeStatus($q, string|array $status) { return $q->whereIn('status', (array)$status); }

    /* HELPERS (state machine ringan) */
    public function canSubmit(): bool   { return $this->status === self::S_DRAFT && $this->items()->exists(); }
    public function canApprove(): bool  { return $this->status === self::S_SUBMITTED; }
    public function canIssue(): bool    { return in_array($this->status, [self::S_APPROVED, self::S_ISSUED]); }
    public function canClose(): bool    { return $this->status === self::S_ISSUED; }

    public function markSubmitted(): void
    {
        $this->status = self::S_SUBMITTED;
        $this->save();
    }
    public function markApproved(): void
    {
        $this->status = self::S_APPROVED;
        $this->save();
    }
    public function markIssued(): void
    {
        $this->status = self::S_ISSUED;
        $this->save();
    }
    public function markClosed(): void
    {
        $this->status = self::S_CLOSED;
        $this->save();
    }
    public function cancel(string $reason = null): void
    {
        $this->status = self::S_CANCELED;
        if ($reason) $this->notes = trim($this->notes."\n".$reason);
        $this->save();
    }

    public static function makeNumber(string $prefix = 'MR'): string
    {
        return sprintf('%s-%s-%04d', $prefix, now()->format('Ym'), random_int(1, 9999));
    }

    /* AGGREGATES */
    public function getTotalRequestedAttribute(): float
    {
        return (float) $this->items()->sum('qty_requested');
    }
    public function getTotalApprovedAttribute(): float
    {
        return (float) $this->items()->sum('qty_approved');
    }
    public function getTotalIssuedAttribute(): float
    {
        return (float) $this->items()->sum('qty_issued');
    }
}
