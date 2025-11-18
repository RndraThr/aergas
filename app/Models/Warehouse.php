<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'code',
        'name',
        'location',
        'address',
        'latitude',
        'longitude',
        'warehouse_type',
        'is_active',
        'pic_name',
        'pic_phone',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function opnames(): HasMany
    {
        return $this->hasMany(StockOpname::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('warehouse_type', $type);
    }

    // Accessors
    public function getTypeBadgeAttribute(): string
    {
        return match($this->warehouse_type) {
            'pusat' => '<span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">Pusat</span>',
            'cabang' => '<span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Cabang</span>',
            'proyek' => '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Proyek</span>',
            default => '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Unknown</span>',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->is_active
            ? '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>'
            : '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>';
    }

    // Helper methods
    public function getTotalStockValue(): float
    {
        return $this->stocks()
            ->join('items', 'warehouse_stocks.item_id', '=', 'items.id')
            ->join('stock_transactions', function($join) {
                $join->on('warehouse_stocks.item_id', '=', 'stock_transactions.item_id')
                     ->on('warehouse_stocks.warehouse_id', '=', 'stock_transactions.warehouse_id')
                     ->whereNotNull('stock_transactions.unit_price');
            })
            ->selectRaw('SUM(warehouse_stocks.quantity_available * COALESCE(stock_transactions.unit_price, 0)) as total')
            ->value('total') ?? 0;
    }

    public function getTotalItemsCount(): int
    {
        return $this->stocks()->count();
    }

    public function getLowStockItemsCount(): int
    {
        return $this->stocks()
            ->join('items', 'warehouse_stocks.item_id', '=', 'items.id')
            ->whereColumn('warehouse_stocks.quantity_available', '<=', 'items.reorder_point')
            ->count();
    }
}
