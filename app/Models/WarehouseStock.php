<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseStock extends Model
{
    protected $fillable = [
        'warehouse_id',
        'item_id',
        'quantity_available',
        'quantity_reserved',
        'quantity_in_transit',
        'last_restock_date',
        'last_usage_date',
    ];

    protected $casts = [
        'quantity_available' => 'decimal:2',
        'quantity_reserved' => 'decimal:2',
        'quantity_in_transit' => 'decimal:2',
        'last_restock_date' => 'date',
        'last_usage_date' => 'date',
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

    // Reserve stock for a project
    public function reserve(float $qty): bool
    {
        if ($this->quantity_available < $qty) {
            return false;
        }

        $this->quantity_available -= $qty;
        $this->quantity_reserved += $qty;
        return $this->save();
    }

    // Release reserved stock (on usage/cancellation)
    public function release(float $qty): bool
    {
        if ($this->quantity_reserved < $qty) {
            return false;
        }

        $this->quantity_reserved -= $qty;
        return $this->save();
    }

    // Use reserved stock (convert reserved to used)
    public function use(float $qty): bool
    {
        if ($this->quantity_reserved < $qty) {
            return false;
        }

        $this->quantity_reserved -= $qty;
        $this->last_usage_date = now();
        return $this->save();
    }

    // Add stock
    public function addStock(float $qty): bool
    {
        $this->quantity_available += $qty;
        $this->last_restock_date = now();
        return $this->save();
    }

    // Remove stock
    public function removeStock(float $qty): bool
    {
        if ($this->quantity_available < $qty) {
            return false;
        }

        $this->quantity_available -= $qty;
        $this->last_usage_date = now();
        return $this->save();
    }

    // Accessors
    public function getTotalQuantityAttribute(): float
    {
        return $this->quantity_available + $this->quantity_reserved + $this->quantity_in_transit;
    }
}
