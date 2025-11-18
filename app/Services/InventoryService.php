<?php

namespace App\Services;

use App\Models\Item;
use App\Models\WarehouseStock;
use App\Models\StockTransaction;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class InventoryService
{
    /**
     * Record stock IN transaction (receiving goods)
     */
    public function recordStockIn(
        int $warehouseId,
        int $itemId,
        float $quantity,
        ?float $unitPrice = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        int $performedBy
    ): StockTransaction {
        DB::beginTransaction();
        try {
            // Get or create warehouse stock
            $stock = WarehouseStock::firstOrCreate(
                ['warehouse_id' => $warehouseId, 'item_id' => $itemId],
                ['quantity_available' => 0, 'quantity_reserved' => 0, 'quantity_in_transit' => 0]
            );

            $qtyBefore = $stock->quantity_available;
            $stock->quantity_available += $quantity;
            $stock->last_restock_date = now();
            $stock->save();

            // Create transaction record
            $trx = StockTransaction::create([
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'transaction_type' => 'in',
                'transaction_date' => now(),
                'quantity' => $quantity,
                'quantity_before' => $qtyBefore,
                'quantity_after' => $stock->quantity_available,
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice ? $quantity * $unitPrice : null,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'performed_by' => $performedBy,
                'status' => 'completed',
            ]);

            DB::commit();
            return $trx;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Record stock OUT transaction (usage/allocation)
     */
    public function recordStockOut(
        int $warehouseId,
        int $itemId,
        float $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        int $performedBy
    ): StockTransaction {
        DB::beginTransaction();
        try {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                                   ->where('item_id', $itemId)
                                   ->lockForUpdate()
                                   ->firstOrFail();

            // Check availability
            if ($stock->quantity_available < $quantity) {
                throw new \Exception("Insufficient stock. Available: {$stock->quantity_available}, Requested: {$quantity}");
            }

            $qtyBefore = $stock->quantity_available;
            $stock->quantity_available -= $quantity;
            $stock->last_usage_date = now();
            $stock->save();

            // Create transaction record
            $trx = StockTransaction::create([
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'transaction_type' => 'out',
                'transaction_date' => now(),
                'quantity' => -$quantity, // Negative for OUT
                'quantity_before' => $qtyBefore,
                'quantity_after' => $stock->quantity_available,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'performed_by' => $performedBy,
                'status' => 'completed',
            ]);

            DB::commit();
            return $trx;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Transfer stock between warehouses
     */
    public function transferStock(
        int $sourceWarehouseId,
        int $destinationWarehouseId,
        int $itemId,
        float $quantity,
        int $performedBy,
        ?string $notes = null
    ): array {
        DB::beginTransaction();
        try {
            // Record OUT from source
            $outTrx = $this->recordStockOut(
                $sourceWarehouseId,
                $itemId,
                $quantity,
                StockTransaction::class,
                null,
                "Transfer to Warehouse #{$destinationWarehouseId}: {$notes}",
                $performedBy
            );

            // Record IN to destination
            $inTrx = $this->recordStockIn(
                $destinationWarehouseId,
                $itemId,
                $quantity,
                null,
                StockTransaction::class,
                null,
                "Transfer from Warehouse #{$sourceWarehouseId}: {$notes}",
                $performedBy
            );

            // Link transactions
            $outTrx->update([
                'destination_warehouse_id' => $destinationWarehouseId,
                'transaction_type' => 'transfer'
            ]);
            $inTrx->update([
                'source_warehouse_id' => $sourceWarehouseId,
                'transaction_type' => 'transfer'
            ]);

            DB::commit();
            return ['out' => $outTrx, 'in' => $inTrx];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Stock adjustment (for corrections)
     */
    public function adjustStock(
        int $warehouseId,
        int $itemId,
        float $newQuantity,
        string $reason,
        int $performedBy
    ): StockTransaction {
        DB::beginTransaction();
        try {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                                   ->where('item_id', $itemId)
                                   ->lockForUpdate()
                                   ->firstOrFail();

            $qtyBefore = $stock->quantity_available;
            $difference = $newQuantity - $qtyBefore;

            $stock->quantity_available = $newQuantity;
            $stock->save();

            // Create adjustment transaction
            $trx = StockTransaction::create([
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'transaction_type' => 'adjustment',
                'transaction_date' => now(),
                'quantity' => $difference,
                'quantity_before' => $qtyBefore,
                'quantity_after' => $newQuantity,
                'notes' => $reason,
                'performed_by' => $performedBy,
                'status' => 'completed',
            ]);

            DB::commit();
            return $trx;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reserve stock for a project/module
     */
    public function reserveStock(
        int $warehouseId,
        int $itemId,
        float $quantity,
        string $referenceType,
        int $referenceId,
        int $performedBy
    ): bool {
        DB::beginTransaction();
        try {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                                   ->where('item_id', $itemId)
                                   ->lockForUpdate()
                                   ->firstOrFail();

            if (!$stock->reserve($quantity)) {
                throw new \Exception("Insufficient stock to reserve");
            }

            // Log the reservation
            StockTransaction::create([
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'transaction_type' => 'out',
                'transaction_date' => now(),
                'quantity' => 0, // No actual movement yet
                'quantity_before' => $stock->quantity_available + $quantity,
                'quantity_after' => $stock->quantity_available,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => "Reserved {$quantity} units for {$referenceType}#{$referenceId}",
                'performed_by' => $performedBy,
                'status' => 'pending',
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get items needing reorder
     */
    public function getItemsNeedingReorder(): Collection
    {
        return Item::with(['stocks', 'category'])
                   ->active()
                   ->get()
                   ->filter(function($item) {
                       return $item->needsReorder();
                   });
    }

    /**
     * Get stock report for a warehouse
     */
    public function getWarehouseStockReport(int $warehouseId): Collection
    {
        return WarehouseStock::with(['item.category'])
                             ->where('warehouse_id', $warehouseId)
                             ->get()
                             ->map(function($stock) {
                                 return [
                                     'item_code' => $stock->item->code,
                                     'item_name' => $stock->item->name,
                                     'category' => $stock->item->category->name,
                                     'unit' => $stock->item->unit,
                                     'available' => $stock->quantity_available,
                                     'reserved' => $stock->quantity_reserved,
                                     'in_transit' => $stock->quantity_in_transit,
                                     'minimum_stock' => $stock->item->minimum_stock,
                                     'reorder_point' => $stock->item->reorder_point,
                                     'status' => $stock->quantity_available <= $stock->item->reorder_point
                                                 ? 'Low Stock'
                                                 : 'OK',
                                 ];
                             });
    }

    /**
     * Get stock movement history
     */
    public function getStockMovementHistory(
        int $itemId,
        ?int $warehouseId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): Collection {
        $query = StockTransaction::with(['warehouse', 'item', 'performer'])
                                 ->where('item_id', $itemId);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($startDate && $endDate) {
            $query->inDateRange($startDate, $endDate);
        }

        return $query->orderBy('transaction_date', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Get stock value by warehouse
     */
    public function getStockValueByWarehouse(?int $warehouseId = null): float
    {
        $query = StockTransaction::whereNotNull('unit_price')
                                 ->where('status', 'completed');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        // Calculate based on latest transaction prices
        $stockValues = WarehouseStock::with(['item'])
            ->when($warehouseId, function($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            })
            ->get()
            ->map(function($stock) {
                $latestPrice = $stock->item->getLastTransactionPrice() ?? 0;
                return $stock->quantity_available * $latestPrice;
            });

        return $stockValues->sum();
    }

    /**
     * Check if sufficient stock available
     */
    public function hasSufficientStock(int $warehouseId, int $itemId, float $requiredQty): bool
    {
        $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                               ->where('item_id', $itemId)
                               ->first();

        return $stock && $stock->quantity_available >= $requiredQty;
    }

    /**
     * Get total available stock for an item across all warehouses
     */
    public function getTotalAvailableStock(int $itemId): float
    {
        return WarehouseStock::where('item_id', $itemId)
                             ->sum('quantity_available');
    }
}
