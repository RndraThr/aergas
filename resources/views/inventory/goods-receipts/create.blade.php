@extends('layouts.app')

@section('title', 'Create Goods Receipt - AERGAS')

@section('content')
<div class="space-y-6" x-data="grData()">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Create Goods Receipt</h1>
            <p class="text-gray-600 mt-1">Record receipt of goods from a purchase order</p>
        </div>
        <a href="{{ route('inventory.goods-receipts.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Back to Goods Receipts
        </a>
    </div>

    <!-- Form -->
    <form action="{{ route('inventory.goods-receipts.store') }}" method="POST">
        @csrf

        <!-- GR Information -->
        <div class="bg-white rounded-xl card-shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>Goods Receipt Information
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Purchase Order <span class="text-red-500">*</span>
                    </label>
                    <select name="purchase_order_id" required @change="loadPODetails($event.target.value)"
                            class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('purchase_order_id') border-red-500 @enderror">
                        <option value="">-- Select Approved PO --</option>
                        @foreach($approvedPOs ?? [] as $po)
                            <option value="{{ $po->id }}" {{ old('purchase_order_id', request('po_id')) == $po->id ? 'selected' : '' }}>
                                {{ $po->po_number }} - {{ $po->supplier->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('purchase_order_id')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">Only approved purchase orders are shown</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Received Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="received_date" required value="{{ old('received_date', date('Y-m-d')) }}"
                           class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('received_date') border-red-500 @enderror">
                    @error('received_date')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Received By
                    </label>
                    <input type="text" readonly value="{{ auth()->user()->name ?? '' }}"
                           class="w-full px-3 py-2 border rounded bg-gray-50 text-gray-600">
                    <p class="text-xs text-gray-500 mt-1">Current logged-in user</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Warehouse
                    </label>
                    <input type="text" readonly x-model="warehouseName"
                           class="w-full px-3 py-2 border rounded bg-gray-50 text-gray-600">
                    <p class="text-xs text-gray-500 mt-1">From selected PO</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Notes <span class="text-gray-400 text-xs">(Optional)</span>
                    </label>
                    <textarea name="notes" rows="3"
                              class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 @error('notes') border-red-500 @enderror"
                              placeholder="General notes about this goods receipt...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Received Items -->
        <div class="bg-white rounded-xl card-shadow p-6 mb-6" x-show="items.length > 0">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                <i class="fas fa-boxes text-green-600 mr-2"></i>Received Items
            </h2>
            <p class="text-sm text-gray-600 mb-4">Enter the actual received quantities. Partial receipts are allowed (max = ordered quantity).</p>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ordered Qty</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Received Qty</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(item, index) in items" :key="index">
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900" x-text="item.name"></div>
                                    <div class="text-xs text-gray-500" x-text="item.code"></div>
                                    <input type="hidden" :name="'items[' + index + '][po_item_id]'" :value="item.po_item_id">
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <span x-text="item.ordered_qty"></span> <span x-text="item.unit"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" :name="'items[' + index + '][received_qty]'" required
                                           min="0" :max="item.ordered_qty" step="1"
                                           x-model="item.received_qty"
                                           class="w-32 px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                                           placeholder="0">
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
        </div>

        <!-- Actions -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Submit Goods Receipt
                </button>
                <a href="{{ route('inventory.goods-receipts.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </div>
    </form>
</div>

<script>
function grData() {
    return {
        warehouseName: '',
        items: [],
        async loadPODetails(poId) {
            if (!poId) {
                this.items = [];
                this.warehouseName = '';
                return;
            }

            try {
                // Fetch PO details via AJAX
                const response = await fetch(`/inventory/purchase-orders/${poId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    this.warehouseName = data.warehouse_name || '';
                    this.items = data.items.map(item => ({
                        po_item_id: item.id,
                        name: item.item_name,
                        code: item.item_code,
                        ordered_qty: item.quantity,
                        received_qty: item.quantity,
                        unit: item.unit
                    }));
                }
            } catch (error) {
                console.error('Error loading PO details:', error);
                alert('Failed to load purchase order details. Please try again.');
            }
        }
    }
}
</script>
@endsection
