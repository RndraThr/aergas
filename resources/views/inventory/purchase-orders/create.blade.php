@extends('layouts.app')

@section('title', 'Create Purchase Order - AERGAS')

@section('content')
<div class="space-y-6" x-data="poData()">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Create Purchase Order</h1>
            <p class="text-gray-600 mt-1">Create a new purchase order for inventory items</p>
        </div>
        <a href="{{ route('inventory.purchase-orders.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Back to POs
        </a>
    </div>

    <!-- Form -->
    <form action="{{ route('inventory.purchase-orders.store') }}" method="POST">
        @csrf

        <!-- PO Information -->
        <div class="bg-white rounded-xl card-shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>Purchase Order Information
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Supplier <span class="text-red-500">*</span>
                    </label>
                    <select name="supplier_id" required class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('supplier_id') border-red-500 @enderror">
                        <option value="">-- Select Supplier --</option>
                        @foreach($suppliers ?? [] as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Warehouse <span class="text-red-500">*</span>
                    </label>
                    <select name="warehouse_id" required class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('warehouse_id') border-red-500 @enderror">
                        <option value="">-- Select Warehouse --</option>
                        @foreach($warehouses ?? [] as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('warehouse_id')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Order Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="order_date" required value="{{ old('order_date', date('Y-m-d')) }}"
                           class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('order_date') border-red-500 @enderror">
                    @error('order_date')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Expected Delivery Date <span class="text-gray-400 text-xs">(Optional)</span>
                    </label>
                    <input type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date') }}"
                           class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('expected_delivery_date') border-red-500 @enderror">
                    @error('expected_delivery_date')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Notes <span class="text-gray-400 text-xs">(Optional)</span>
                    </label>
                    <textarea name="notes" rows="3"
                              class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('notes') border-red-500 @enderror"
                              placeholder="Additional notes for this purchase order...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <!-- PO Items -->
        <div class="bg-white rounded-xl card-shadow p-6 mb-6">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-list text-green-600 mr-2"></i>Purchase Order Items
                </h2>
                <button type="button" @click="addItem()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    <i class="fas fa-plus mr-2"></i>Add Item
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price (Rp)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal (Rp)</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(item, index) in items" :key="index">
                            <tr>
                                <td class="px-4 py-3">
                                    <select :name="'items[' + index + '][item_id]'" required x-model="item.item_id"
                                            class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                                        <option value="">-- Select Item --</option>
                                        @foreach($items ?? [] as $availableItem)
                                            <option value="{{ $availableItem->id }}">{{ $availableItem->name }} ({{ $availableItem->code }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" :name="'items[' + index + '][quantity]'" required min="1" step="1"
                                           x-model="item.quantity"
                                           class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                                           placeholder="0">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" :name="'items[' + index + '][unit_price]'" required min="0" step="0.01"
                                           x-model="item.unit_price"
                                           class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                                           placeholder="0.00">
                                </td>
                                <td class="px-4 py-3 font-semibold text-gray-900">
                                    <span x-text="formatCurrency(subtotal(item))"></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button" @click="removeItem(index)"
                                            class="px-3 py-1.5 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200"
                                            :disabled="items.length === 1">
                                        <i class="fas fa-trash mr-1"></i>Remove
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right font-semibold text-gray-900">Grand Total:</td>
                            <td class="px-4 py-3 font-bold text-lg text-gray-900">
                                <span x-text="formatCurrency(grandTotal())"></span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="flex gap-3">
                <button type="submit" name="status" value="draft" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    <i class="fas fa-save mr-2"></i>Save as Draft
                </button>
                <button type="submit" name="status" value="submitted" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-paper-plane mr-2"></i>Submit for Approval
                </button>
                <a href="{{ route('inventory.purchase-orders.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </div>
    </form>
</div>

<script>
function poData() {
    return {
        items: [{item_id: '', quantity: 0, unit_price: 0}],
        addItem() {
            this.items.push({item_id: '', quantity: 0, unit_price: 0});
        },
        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },
        subtotal(item) {
            return parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0);
        },
        grandTotal() {
            return this.items.reduce((sum, item) => sum + this.subtotal(item), 0);
        },
        formatCurrency(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(value);
        }
    }
}
</script>
@endsection
