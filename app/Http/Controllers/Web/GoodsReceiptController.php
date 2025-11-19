<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{GoodsReceipt, GoodsReceiptDetail, PurchaseOrder, Warehouse, Supplier, Item};
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Validator, DB};

class GoodsReceiptController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    public function index()
    {
        $goodsReceipts = GoodsReceipt::with(['warehouse', 'supplier', 'purchaseOrder', 'receiver'])->latest()->paginate(15);
        return view('inventory.goods-receipts.index', compact('goodsReceipts'));
    }

    public function create()
    {
        $approvedPOs = PurchaseOrder::with(['supplier', 'warehouse'])
                                    ->where('status', 'approved')
                                    ->get();
        $warehouses = Warehouse::active()->get();
        $suppliers = Supplier::active()->get();
        $items = Item::active()->get();
        return view('inventory.goods-receipts.create', compact('approvedPOs', 'warehouses', 'suppliers', 'items'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'received_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.received_quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $gr = GoodsReceipt::create([
                'purchase_order_id' => $request->purchase_order_id,
                'warehouse_id' => $request->warehouse_id,
                'supplier_id' => $request->supplier_id,
                'receipt_date' => $request->received_date,
                'notes' => $request->notes,
                'status' => 'draft',
                'received_by' => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                $receivedQty = $item['received_quantity'];
                $unitPrice = $item['unit_price'] ?? 0;

                GoodsReceiptDetail::create([
                    'goods_receipt_id' => $gr->id,
                    'item_id' => $item['item_id'],
                    'ordered_quantity' => $item['ordered_quantity'] ?? $receivedQty,
                    'received_quantity' => $receivedQty,
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $receivedQty,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();
            return redirect()->route('inventory.goods-receipts.show', $gr)->with('success', 'Goods Receipt created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to create GR: ' . $e->getMessage())->withInput();
        }
    }

    public function show(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load(['warehouse', 'supplier', 'purchaseOrder', 'details.item', 'receiver', 'approver']);
        return view('inventory.goods-receipts.show', compact('goodsReceipt'));
    }

    public function approve(GoodsReceipt $goodsReceipt)
    {
        if ($goodsReceipt->status !== 'draft') {
            return redirect()->back()->with('error', 'Only draft GR can be approved.');
        }

        DB::beginTransaction();
        try {
            // Record stock in for each item
            foreach ($goodsReceipt->details as $detail) {
                $this->inventoryService->recordStockIn(
                    warehouseId: $goodsReceipt->warehouse_id,
                    itemId: $detail->item_id,
                    quantity: $detail->received_quantity,
                    unitPrice: $detail->unit_price,
                    referenceType: GoodsReceipt::class,
                    referenceId: $goodsReceipt->id,
                    notes: "GR: {$goodsReceipt->receipt_number}",
                    performedBy: auth()->id()
                );

                // Update PO detail quantity_received if linked to PO
                if ($goodsReceipt->purchase_order_id) {
                    $poDetail = \App\Models\PurchaseOrderDetail::where([
                        'purchase_order_id' => $goodsReceipt->purchase_order_id,
                        'item_id' => $detail->item_id
                    ])->first();

                    if ($poDetail) {
                        $poDetail->quantity_received += $detail->received_quantity;
                        $poDetail->save();
                    }
                }
            }

            $goodsReceipt->update([
                'status' => 'completed',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Update PO status based on received quantities
            if ($goodsReceipt->purchaseOrder) {
                $po = $goodsReceipt->purchaseOrder;

                // Check if all items are fully received
                $fullyReceived = true;
                foreach ($po->details as $poDetail) {
                    if ($poDetail->quantity_received < $poDetail->quantity_ordered) {
                        $fullyReceived = false;
                        break;
                    }
                }

                if ($fullyReceived) {
                    $po->status = 'received';
                } else {
                    $po->status = 'partial_received';
                }
                $po->save();
            }

            DB::commit();
            return redirect()->back()->with('success', 'Goods Receipt approved and stock updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to approve GR: ' . $e->getMessage());
        }
    }
}
