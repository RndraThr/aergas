<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number', 'supplier_id', 'warehouse_id', 'po_date', 'expected_delivery_date',
        'total_amount', 'status', 'notes', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'po_date' => 'date',
        'expected_delivery_date' => 'date',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($po) {
            if (!$po->po_number) {
                $date = now()->format('Ymd');
                $last = self::whereDate('created_at', now())->latest('id')->first();
                $seq = $last ? (int)substr($last->po_number, -3) + 1 : 1;
                $po->po_number = "PO-{$date}-" . str_pad($seq, 3, '0', STR_PAD_LEFT);
            }
        });
    }
}
