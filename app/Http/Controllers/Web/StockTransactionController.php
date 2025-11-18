<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{StockTransaction, Warehouse, Item, WarehouseStock};
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StockTransactionController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    public function index(Request $request)
    {
        $query = StockTransaction::with(['warehouse', 'item', 'performer']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('transaction_date', [$request->date_from, $request->date_to]);
        }

        $transactions = $query->latest('transaction_date')->latest('id')->paginate(15);
        $warehouses = Warehouse::active()->get();
        $items = Item::active()->get();

        return view('inventory.transactions.index', compact('transactions', 'warehouses', 'items'));
    }

    public function create()
    {
        $warehouses = Warehouse::active()->get();
        $items = Item::active()->get();
        $transactionTypes = config('inventory.transaction_types');

        return view('inventory.transactions.create', compact('warehouses', 'items', 'transactionTypes'));
    }

    public function show(StockTransaction $transaction)
    {
        $transaction->load(['warehouse', 'item', 'performer', 'approver', 'reference']);
        return view('inventory.transactions.show', compact('transaction'));
    }

    /**
     * Universal store method that routes to specific transaction methods
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_type' => 'required|in:in,out,transfer,adjustment',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Route to specific method based on transaction type
        return match($request->transaction_type) {
            'in' => $this->stockIn($request),
            'out' => $this->stockOut($request),
            'transfer' => $this->transfer($request),
            'adjustment' => $this->adjustment($request),
            default => redirect()->back()->with('error', 'Invalid transaction type')->withInput(),
        };
    }

    public function stockIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $this->inventoryService->recordStockIn(
                warehouseId: $request->warehouse_id,
                itemId: $request->item_id,
                quantity: $request->quantity,
                unitPrice: $request->unit_price,
                referenceType: null,
                referenceId: null,
                notes: $request->notes,
                performedBy: auth()->id()
            );

            return redirect()->route('inventory.transactions.index')
                           ->with('success', 'Stock in recorded successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Failed to record stock in: ' . $e->getMessage())
                           ->withInput();
        }
    }

    public function stockOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $this->inventoryService->recordStockOut(
                warehouseId: $request->warehouse_id,
                itemId: $request->item_id,
                quantity: $request->quantity,
                referenceType: null,
                referenceId: null,
                notes: $request->notes,
                performedBy: auth()->id()
            );

            return redirect()->route('inventory.transactions.index')
                           ->with('success', 'Stock out recorded successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Failed to record stock out: ' . $e->getMessage())
                           ->withInput();
        }
    }

    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'destination_warehouse_id' => 'required|exists:warehouses,id|different:warehouse_id',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $this->inventoryService->transferStock(
                sourceWarehouseId: $request->warehouse_id,
                destinationWarehouseId: $request->destination_warehouse_id,
                itemId: $request->item_id,
                quantity: $request->quantity,
                performedBy: auth()->id(),
                notes: $request->notes
            );

            return redirect()->route('inventory.transactions.index')
                           ->with('success', 'Stock transfer completed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Failed to transfer stock: ' . $e->getMessage())
                           ->withInput();
        }
    }

    public function adjustment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'item_id' => 'required|exists:items,id',
            'adjustment_type' => 'required|in:increase,decrease',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            // Get current stock to calculate new quantity
            $stock = WarehouseStock::where('warehouse_id', $request->warehouse_id)
                                   ->where('item_id', $request->item_id)
                                   ->firstOrFail();

            $currentQty = $stock->quantity_available;

            // Calculate new quantity based on adjustment type
            $newQuantity = $request->adjustment_type === 'increase'
                         ? $currentQty + $request->quantity
                         : $currentQty - $request->quantity;

            // Ensure new quantity is not negative
            if ($newQuantity < 0) {
                return redirect()->back()
                               ->with('error', 'Adjustment would result in negative stock. Current: ' . $currentQty . ', Decrease: ' . $request->quantity)
                               ->withInput();
            }

            $this->inventoryService->adjustStock(
                warehouseId: $request->warehouse_id,
                itemId: $request->item_id,
                newQuantity: $newQuantity,
                reason: $request->notes,
                performedBy: auth()->id()
            );

            return redirect()->route('inventory.transactions.index')
                           ->with('success', 'Stock adjustment completed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'Failed to adjust stock: ' . $e->getMessage())
                           ->withInput();
        }
    }

    public function stockSummary()
    {
        $warehouses = Warehouse::with(['stocks.item.category'])->active()->get();
        return view('inventory.reports.stock-summary', compact('warehouses'));
    }

    public function lowStock()
    {
        $lowStockItems = $this->inventoryService->getItemsNeedingReorder();
        return view('inventory.reports.low-stock', compact('lowStockItems'));
    }

    public function stockValue()
    {
        $warehouses = Warehouse::active()->get();
        $stockValues = [];

        foreach ($warehouses as $warehouse) {
            $stockValues[$warehouse->id] = [
                'warehouse' => $warehouse,
                'value' => $this->inventoryService->getStockValueByWarehouse($warehouse->id),
            ];
        }

        $totalValue = $this->inventoryService->getStockValueByWarehouse();

        return view('inventory.reports.stock-value', compact('stockValues', 'totalValue'));
    }
}
