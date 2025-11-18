@extends('layouts.app')

@section('title', 'Create Item - AERGAS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Create New Item</h1>
            <p class="text-gray-600 mt-1">Add a new inventory item</p>
        </div>
        <a href="{{ route('inventory.items.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Back to Items
        </a>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <form action="{{ route('inventory.items.store') }}" method="POST">
            @csrf

            <!-- Basic Information -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>Basic Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Code -->
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                            Item Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="code" id="code" value="{{ old('code') }}" required
                               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('code') border-red-500 @enderror"
                               placeholder="e.g., ITM001">
                        @error('code')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Item Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                               placeholder="e.g., PVC Pipe 1 inch">
                        @error('name')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select name="category_id" id="category_id" required
                                class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('category_id') border-red-500 @enderror">
                            <option value="">-- Select Category --</option>
                            @foreach($categories ?? [] as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Unit -->
                    <div>
                        <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">
                            Unit <span class="text-red-500">*</span>
                        </label>
                        <select name="unit" id="unit" required
                                class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('unit') border-red-500 @enderror">
                            <option value="">-- Select Unit --</option>
                            <option value="m" {{ old('unit') == 'm' ? 'selected' : '' }}>Meter (m)</option>
                            <option value="pcs" {{ old('unit') == 'pcs' ? 'selected' : '' }}>Pieces (pcs)</option>
                            <option value="kg" {{ old('unit') == 'kg' ? 'selected' : '' }}>Kilogram (kg)</option>
                            <option value="liter" {{ old('unit') == 'liter' ? 'selected' : '' }}>Liter (liter)</option>
                            <option value="roll" {{ old('unit') == 'roll' ? 'selected' : '' }}>Roll (roll)</option>
                            <option value="set" {{ old('unit') == 'set' ? 'selected' : '' }}>Set (set)</option>
                        </select>
                        @error('unit')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Stock Settings -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-boxes text-green-600 mr-2"></i>Stock Settings
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Minimum Stock -->
                    <div>
                        <label for="minimum_stock" class="block text-sm font-medium text-gray-700 mb-1">
                            Minimum Stock <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <input type="number" name="minimum_stock" id="minimum_stock" value="{{ old('minimum_stock', 0) }}" min="0" step="1"
                               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('minimum_stock') border-red-500 @enderror"
                               placeholder="0">
                        @error('minimum_stock')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">Alert threshold for low stock</p>
                    </div>

                    <!-- Reorder Point -->
                    <div>
                        <label for="reorder_point" class="block text-sm font-medium text-gray-700 mb-1">
                            Reorder Point <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <input type="number" name="reorder_point" id="reorder_point" value="{{ old('reorder_point', 0) }}" min="0" step="1"
                               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('reorder_point') border-red-500 @enderror"
                               placeholder="0">
                        @error('reorder_point')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">Stock level that triggers reorder</p>
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-tag text-orange-600 mr-2"></i>Pricing
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Last Unit Price -->
                    <div>
                        <label for="last_unit_price" class="block text-sm font-medium text-gray-700 mb-1">
                            Last Unit Price (Rp) <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <input type="number" name="last_unit_price" id="last_unit_price" value="{{ old('last_unit_price', 0) }}" min="0" step="0.01"
                               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('last_unit_price') border-red-500 @enderror"
                               placeholder="0.00">
                        @error('last_unit_price')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                    <i class="fas fa-clipboard text-purple-600 mr-2"></i>Additional Information
                </h2>
                <div class="space-y-4">
                    <!-- Specification -->
                    <div>
                        <label for="specification" class="block text-sm font-medium text-gray-700 mb-1">
                            Specification <span class="text-gray-400 text-xs">(Optional - JSON format)</span>
                        </label>
                        <textarea name="specification" id="specification" rows="4"
                                  class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('specification') border-red-500 @enderror"
                                  placeholder='{"diameter": "1 inch", "material": "PVC", "color": "gray"}'>{{ old('specification') }}</textarea>
                        @error('specification')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">Enter specifications in JSON format or key-value pairs</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                            Description <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <textarea name="description" id="description" rows="3"
                                  class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                                  placeholder="Brief description of the item...">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Is Active -->
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">
                            Item is Active
                        </label>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 pt-6 border-t border-gray-200">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Save Item
                </button>
                <a href="{{ route('inventory.items.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
