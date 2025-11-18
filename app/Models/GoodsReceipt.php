<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    protected $fillable = [
        'receipt_number', 'purchase_order_id', 'warehouse_id', 'receipt_date', 'supplier_id',
        'notes', 'received_by', 'approved_by', 'approved_at', 'status',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(GoodsReceiptDetail::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($gr) {
            if (!$gr->receipt_number) {
                $date = now()->format('Ymd');
                $last = self::whereDate('created_at', now())->latest('id')->first();
                $seq = $last ? (int)substr($last->receipt_number, -3) + 1 : 1;
                $gr->receipt_number = "GR-{$date}-" . str_pad($seq, 3, '0', STR_PAD_LEFT);
            }
        });
    }
}
