<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    protected $fillable = [
        'opname_number', 'warehouse_id', 'opname_date', 'status', 'notes',
        'performed_by', 'reviewed_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'opname_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(StockOpnameDetail::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($opname) {
            if (!$opname->opname_number) {
                $date = now()->format('Ymd');
                $last = self::whereDate('created_at', now())->latest('id')->first();
                $seq = $last ? (int)substr($last->opname_number, -3) + 1 : 1;
                $opname->opname_number = "OPN-{$date}-" . str_pad($seq, 3, '0', STR_PAD_LEFT);
            }
        });
    }
}
