<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{PurchaseOrder, PurchaseOrderDetail, Supplier, Warehouse, Item};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Validator, DB};

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['supplier', 'warehouse', 'creator'])->latest()->paginate(15);
        return view('inventory.purchase-orders.index', compact('purchaseOrders'));
    }

    public function create()
    {
        $suppliers = Supplier::active()->get();
        $warehouses = Warehouse::active()->get();
        $items = Item::active()->get();
        return view('inventory.purchase-orders.create', compact('suppliers', 'warehouses', 'items'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $totalAmount = collect($request->items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);

            $po = PurchaseOrder::create([
                'supplier_id' => $request->supplier_id,
                'warehouse_id' => $request->warehouse_id,
                'po_date' => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
                'status' => $request->status ?? 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                PurchaseOrderDetail::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $item['item_id'],
                    'quantity_ordered' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();
            return redirect()->route('inventory.purchase-orders.show', $po)->with('success', 'Purchase Order created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to create PO: ' . $e->getMessage())->withInput();
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'warehouse', 'details.item', 'creator', 'approver', 'goodsReceipts']);

        // Return JSON for AJAX requests
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'warehouse_name' => $purchaseOrder->warehouse->name ?? '',
                'supplier_name' => $purchaseOrder->supplier->name ?? '',
                'items' => $purchaseOrder->details->map(function($detail) {
                    return [
                        'id' => $detail->id,
                        'item_id' => $detail->item_id,
                        'item_name' => $detail->item->name ?? '',
                        'item_code' => $detail->item->code ?? '',
                        'quantity' => $detail->quantity_ordered,
                        'unit' => $detail->item->unit ?? '',
                        'unit_price' => $detail->unit_price,
                    ];
                })->values(),
            ]);
        }

        return view('inventory.purchase-orders.show', compact('purchaseOrder'));
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        if (!in_array($purchaseOrder->status, ['draft', 'submitted'])) {
            return redirect()->back()->with('error', 'Only draft or submitted PO can be approved.');
        }

        $purchaseOrder->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Purchase Order approved successfully.');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            return redirect()->back()->with('error', 'Cannot cancel this PO.');
        }

        $purchaseOrder->update(['status' => 'cancelled']);
        return redirect()->back()->with('success', 'Purchase Order cancelled.');
    }
}
