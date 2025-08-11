<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GudangTransaction extends Model
{
    protected $table = 'gudang_transactions';

    public const T_IN        = 'IN';
    public const T_OUT       = 'OUT';
    public const T_RETURN    = 'RETURN';
    public const T_REJECT    = 'REJECT';
    public const T_INSTALLED = 'INSTALLED';
    public const T_ADJUST    = 'ADJUST';

    protected $fillable = [
        'gudang_item_id','type','qty','unit','ref_no',
        'sourceable_type','sourceable_id','notes','transacted_at','created_by',
    ];

    protected $casts = [
        'qty'           => 'decimal:3',
        'transacted_at' => 'datetime',
    ];

    // RELATIONS
    public function item(): BelongsTo { return $this->belongsTo(GudangItem::class, 'gudang_item_id'); }
    public function sourceable(): MorphTo { return $this->morphTo(); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    // SCOPES
    public function scopeType(Builder $q, string|array $types) { return $q->whereIn('type', (array) $types); }
    public function scopeBetween(Builder $q, $start, $end) { return $q->whereBetween('transacted_at', [$start, $end]); }
    public function scopeItemCode(Builder $q, string $code)
    {
        return $q->whereHas('item', fn($qq) => $qq->where('code', $code));
    }

    // HELPERS
    public static function recordIn(int $itemId, float $qty, array $opt = []): self
    {
        return static::create(array_merge([
            'gudang_item_id' => $itemId,
            'type'           => self::T_IN,
            'qty'            => $qty,
            'transacted_at'  => now(),
        ], $opt));
    }

    public static function recordOut(int $itemId, float $qty, array $opt = []): self
    {
        return static::create(array_merge([
            'gudang_item_id' => $itemId,
            'type'           => self::T_OUT,
            'qty'            => $qty,
            'transacted_at'  => now(),
        ], $opt));
    }
}
