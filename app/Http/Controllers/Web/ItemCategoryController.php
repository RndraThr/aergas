<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemCategoryController extends Controller
{
    public function index()
    {
        $categories = ItemCategory::with(['parent', 'items'])->latest()->paginate(15);
        return view('inventory.categories.index', compact('categories'));
    }

    public function create()
    {
        $parentCategories = ItemCategory::whereNull('parent_id')->active()->get();
        return view('inventory.categories.create', compact('parentCategories'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:item_categories,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:item_categories,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        ItemCategory::create(array_merge($validator->validated(), [
            'created_by' => auth()->id(),
        ]));

        return redirect()->route('inventory.categories.index')
                       ->with('success', 'Category created successfully.');
    }

    public function edit(ItemCategory $category)
    {
        $parentCategories = ItemCategory::whereNull('parent_id')
                                       ->where('id', '!=', $category->id)
                                       ->active()
                                       ->get();
        return view('inventory.categories.edit', compact('category', 'parentCategories'));
    }

    public function update(Request $request, ItemCategory $category)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:item_categories,code,' . $category->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:item_categories,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $category->update(array_merge($validator->validated(), [
            'updated_by' => auth()->id(),
        ]));

        return redirect()->route('inventory.categories.index')
                       ->with('success', 'Category updated successfully.');
    }

    public function destroy(ItemCategory $category)
    {
        if ($category->items()->exists() || $category->children()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete category with items or sub-categories.');
        }

        $category->delete();
        return redirect()->route('inventory.categories.index')
                       ->with('success', 'Category deleted successfully.');
    }
}
