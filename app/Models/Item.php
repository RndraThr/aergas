<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category_id',
        'unit',
        'description',
        'specification',
        'minimum_stock',
        'maximum_stock',
        'reorder_point',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'specification' => 'array',
        'minimum_stock' => 'decimal:2',
        'maximum_stock' => 'decimal:2',
        'reorder_point' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = ['total_stock'];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
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

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereHas('stocks', function($q) {
            $q->whereColumn('warehouse_stocks.quantity_available', '<=', 'items.reorder_point');
        });
    }

    // Accessors
    public function getTotalStockAttribute(): float
    {
        return $this->stocks()->sum('quantity_available');
    }

    public function getTotalReservedAttribute(): float
    {
        return $this->stocks()->sum('quantity_reserved');
    }

    public function getTotalInTransitAttribute(): float
    {
        return $this->stocks()->sum('quantity_in_transit');
    }

    public function getStockStatusAttribute(): string
    {
        $totalStock = $this->total_stock;

        if ($totalStock <= 0) {
            return '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Out of Stock</span>';
        } elseif ($totalStock <= $this->reorder_point) {
            return '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Low Stock</span>';
        } elseif ($this->maximum_stock && $totalStock >= $this->maximum_stock) {
            return '<span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Overstock</span>';
        } else {
            return '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">In Stock</span>';
        }
    }

    // Helper methods
    public function needsReorder(): bool
    {
        return $this->total_stock <= $this->reorder_point;
    }

    public function getAvailableStockInWarehouse(int $warehouseId): float
    {
        return $this->stocks()
            ->where('warehouse_id', $warehouseId)
            ->value('quantity_available') ?? 0;
    }

    public function getLastTransactionPrice(): ?float
    {
        return $this->transactions()
            ->whereNotNull('unit_price')
            ->latest('transaction_date')
            ->value('unit_price');
    }
}
