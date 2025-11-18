<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockTransaction extends Model
{
    protected $fillable = [
        'transaction_number',
        'warehouse_id',
        'item_id',
        'transaction_type',
        'transaction_date',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_price',
        'total_price',
        'reference_type',
        'reference_id',
        'source_warehouse_id',
        'destination_warehouse_id',
        'notes',
        'performed_by',
        'approved_by',
        'approved_at',
        'status',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'quantity' => 'decimal:2',
        'quantity_before' => 'decimal:2',
        'quantity_after' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    // Polymorphic relation to source (SK, SR, Jalur, etc.)
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // Auto-generate transaction number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($trx) {
            if (!$trx->transaction_number) {
                $type = strtoupper(str_replace('_', '-', $trx->transaction_type));
                $date = now()->format('Ymd');
                $last = self::where('transaction_type', $trx->transaction_type)
                           ->whereDate('created_at', now())
                           ->latest('id')
                           ->first();
                $seq = $last ? (int)substr($last->transaction_number, -3) + 1 : 1;
                $trx->transaction_number = "TRX-{$type}-{$date}-" . str_pad($seq, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeByWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByItem($query, int $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getTypeBadgeAttribute(): string
    {
        return match($this->transaction_type) {
            'in' => '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">In</span>',
            'out' => '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Out</span>',
            'transfer' => '<span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Transfer</span>',
            'adjustment' => '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Adjustment</span>',
            'return' => '<span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">Return</span>',
            default => '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Unknown</span>',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'draft' => '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Draft</span>',
            'pending' => '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Pending</span>',
            'approved' => '<span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Approved</span>',
            'rejected' => '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Rejected</span>',
            'completed' => '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Completed</span>',
            default => '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Unknown</span>',
        };
    }
}
