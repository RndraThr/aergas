@extends('layouts.app')

@section('title', 'Create Stock Opname - AERGAS')

@section('content')
<div class="space-y-6" x-data="opnameData()">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Create Stock Opname</h1>
            <p class="text-gray-600 mt-1">Schedule and perform a stock opname (physical stock count)</p>
        </div>
        <a href="{{ route('inventory.stock-opnames.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Back to Stock Opnames
        </a>
    </div>

    <!-- Form -->
    <form action="{{ route('inventory.stock-opnames.store') }}" method="POST">
        @csrf

        <!-- Opname Information -->
        <div class="bg-white rounded-xl card-shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>Stock Opname Information
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Warehouse <span class="text-red-500">*</span>
                    </label>
                    <select name="warehouse_id" required @change="loadWarehouseItems($event.target.value)"
                            class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('warehouse_id') border-red-500 @enderror">
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
                    <p class="text-xs text-gray-500 mt-1">Select warehouse to load items for counting</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Schedule Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="schedule_date" required value="{{ old('schedule_date', date('Y-m-d')) }}"
                           class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('schedule_date') border-red-500 @enderror">
                    @error('schedule_date')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Description <span class="text-gray-400 text-xs">(Optional)</span>
                    </label>
                    <textarea name="description" rows="3"
                              class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                              placeholder="Purpose and description of this stock opname...">{{ old('description') }}</textarea>
                    @error('description')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Items Section -->
        <div class="bg-white rounded-xl card-shadow p-6 mb-6" x-show="items.length > 0">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                <i class="fas fa-clipboard-check text-green-600 mr-2"></i>Items to Count
            </h2>
            <p class="text-sm text-gray-600 mb-4">Enter the actual physical quantity for each item. The system will calculate the difference automatically.</p>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">System Qty</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Physical Qty</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Difference</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variance %</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(item, index) in items" :key="index">
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900" x-text="item.name"></div>
                                    <div class="text-xs text-gray-500" x-text="item.code"></div>
                                    <input type="hidden" :name="'items[' + index + '][item_id]'" :value="item.item_id">
                                    <input type="hidden" :name="'items[' + index + '][system_qty]'" :value="item.system_qty">
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    <span x-text="item.system_qty"></span> <span x-text="item.unit"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" :name="'items[' + index + '][physical_qty]'" required
                                           min="0" step="1"
                                           x-model="item.physical_qty"
                                           @input="calculateDifference(item)"
                                           class="w-32 px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                                           placeholder="0">
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold"
                                    :class="item.difference < 0 ? 'text-red-600' : (item.difference > 0 ? 'text-green-600' : 'text-gray-900')">
                                    <span x-text="item.difference"></span> <span x-text="item.unit"></span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium"
                                    :class="Math.abs(item.variance_pct) > 5 ? 'text-red-600' : 'text-gray-900'">
                                    <span x-text="item.variance_pct.toFixed(2) + '%'"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" :name="'items[' + index + '][notes]'"
                                           class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                                           placeholder="Optional notes...">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Summary -->
            <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <h3 class="font-semibold text-gray-800 mb-3">Summary</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Total Items:</span>
                        <span class="font-semibold text-gray-900 ml-2" x-text="items.length"></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Items with Discrepancy:</span>
                        <span class="font-semibold text-gray-900 ml-2" x-text="itemsWithDiscrepancy()"></span>
                    </div>
                    <div>
                        <span class="text-gray-600">High Variance (>5%):</span>
                        <span class="font-semibold text-red-600 ml-2" x-text="highVarianceItems()"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="flex gap-3">
                <button type="submit" name="action" value="start" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-play mr-2"></i>Start Opname
                </button>
                <a href="{{ route('inventory.stock-opnames.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </div>
    </form>
</div>

<script>
function opnameData() {
    return {
        items: [],
        loadWarehouseItems(warehouseId) {
            if (!warehouseId) {
                this.items = [];
                return;
            }
            // This would typically be an AJAX call to fetch warehouse items
            // For demonstration, using server-side data
            // In production, implement AJAX to dynamically load items
            @if(isset($warehouseItems))
                this.items = @json($warehouseItems);
                this.items.forEach(item => {
                    item.physical_qty = item.system_qty;
                    this.calculateDifference(item);
                });
            @endif
        },
        calculateDifference(item) {
            item.physical_qty = parseFloat(item.physical_qty) || 0;
            item.difference = item.physical_qty - item.system_qty;
            item.variance_pct = item.system_qty > 0
                ? ((item.difference / item.system_qty) * 100)
                : 0;
        },
        itemsWithDiscrepancy() {
            return this.items.filter(item => item.difference !== 0).length;
        },
        highVarianceItems() {
            return this.items.filter(item => Math.abs(item.variance_pct) > 5).length;
        }
    }
}
</script>
@endsection
