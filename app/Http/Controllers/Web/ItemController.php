<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{Item, ItemCategory};
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    public function index()
    {
        $items = Item::with(['category', 'stocks.warehouse'])
                    ->withCount('stocks')
                    ->latest()
                    ->paginate(15);

        return view('inventory.items.index', compact('items'));
    }

    public function create()
    {
        $categories = ItemCategory::active()->get();
        $units = config('inventory.item_units');

        return view('inventory.items.create', compact('categories', 'units'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:items,code',
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:item_categories,id',
            'unit' => 'required|in:m,pcs,unit,kg,liter,roll,set',
            'description' => 'nullable|string',
            'specification' => 'nullable|array',
            'minimum_stock' => 'required|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0',
            'reorder_point' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Item::create(array_merge($validator->validated(), [
            'created_by' => auth()->id(),
        ]));

        return redirect()->route('inventory.items.index')
                       ->with('success', 'Item created successfully.');
    }

    public function show(Item $item)
    {
        $item->load(['category', 'stocks.warehouse', 'transactions' => function($q) {
            $q->latest()->limit(50);
        }]);

        return view('inventory.items.show', compact('item'));
    }

    public function edit(Item $item)
    {
        $categories = ItemCategory::active()->get();
        $units = config('inventory.item_units');

        return view('inventory.items.edit', compact('item', 'categories', 'units'));
    }

    public function update(Request $request, Item $item)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:items,code,' . $item->id,
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:item_categories,id',
            'unit' => 'required|in:m,pcs,unit,kg,liter,roll,set',
            'description' => 'nullable|string',
            'specification' => 'nullable|array',
            'minimum_stock' => 'required|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0',
            'reorder_point' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $item->update(array_merge($validator->validated(), [
            'updated_by' => auth()->id(),
        ]));

        return redirect()->route('inventory.items.index')
                       ->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item)
    {
        if ($item->stocks()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete item with existing stock records.');
        }

        $item->delete();

        return redirect()->route('inventory.items.index')
                       ->with('success', 'Item deleted successfully.');
    }

    public function stockHistory(Item $item)
    {
        $history = $this->inventoryService->getStockMovementHistory($item->id);

        return view('inventory.items.stock-history', compact('item', 'history'));
    }
}
