<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{StockOpname, StockOpnameDetail, Warehouse, WarehouseStock};
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Validator, DB};

class StockOpnameController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    public function index()
    {
        $stockOpnames = StockOpname::with(['warehouse', 'performer'])->latest()->paginate(15);
        return view('inventory.stock-opnames.index', compact('stockOpnames'));
    }

    public function create()
    {
        $warehouses = Warehouse::active()->get();
        return view('inventory.stock-opnames.create', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'opname_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $opname = StockOpname::create([
                'warehouse_id' => $request->warehouse_id,
                'opname_date' => $request->opname_date,
                'status' => 'in_progress',
                'notes' => $request->notes,
                'performed_by' => auth()->id(),
            ]);

            // Get all stocks in warehouse
            $stocks = WarehouseStock::where('warehouse_id', $request->warehouse_id)->get();

            foreach ($stocks as $stock) {
                StockOpnameDetail::create([
                    'stock_opname_id' => $opname->id,
                    'item_id' => $stock->item_id,
                    'system_quantity' => $stock->quantity_available,
                    'physical_quantity' => 0, // Will be filled manually
                    'difference' => 0,
                ]);
            }

            DB::commit();
            return redirect()->route('inventory.stock-opnames.show', $opname)->with('success', 'Stock Opname created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to create opname: ' . $e->getMessage())->withInput();
        }
    }

    public function show(StockOpname $stockOpname)
    {
        $stockOpname->load(['warehouse', 'details.item', 'performer', 'approver']);
        return view('inventory.stock-opnames.show', compact('stockOpname'));
    }

    public function complete(Request $request, StockOpname $stockOpname)
    {
        if ($stockOpname->status !== 'in_progress') {
            return redirect()->back()->with('error', 'Only in-progress opname can be completed.');
        }

        $validator = Validator::make($request->all(), [
            'details' => 'required|array',
            'details.*.id' => 'required|exists:stock_opname_details,id',
            'details.*.physical_quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            foreach ($request->details as $detailData) {
                $detail = StockOpnameDetail::find($detailData['id']);
                $detail->physical_quantity = $detailData['physical_quantity'];
                $detail->difference = $detailData['physical_quantity'] - $detail->system_quantity;
                $detail->save();
            }

            $stockOpname->update(['status' => 'completed']);

            DB::commit();
            return redirect()->back()->with('success', 'Stock Opname completed.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to complete opname: ' . $e->getMessage());
        }
    }

    public function approve(Request $request, StockOpname $stockOpname)
    {
        if ($stockOpname->status !== 'completed') {
            return redirect()->back()->with('error', 'Only completed opname can be approved.');
        }

        DB::beginTransaction();
        try {
            // Apply adjustments for differences
            foreach ($stockOpname->details as $detail) {
                if ($detail->difference != 0 && $detail->adjustment_approved) {
                    $this->inventoryService->adjustStock(
                        warehouseId: $stockOpname->warehouse_id,
                        itemId: $detail->item_id,
                        newQuantity: $detail->physical_quantity,
                        reason: "Stock Opname #{$stockOpname->opname_number}: Adjustment approved",
                        performedBy: auth()->id()
                    );
                }
            }

            $stockOpname->update([
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Stock Opname approved and adjustments applied.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to approve opname: ' . $e->getMessage());
        }
    }
}
