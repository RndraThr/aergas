@extends('layouts.app')

@section('title', 'Create Transaction - AERGAS')

@section('content')
<div class="space-y-6" x-data="transactionForm()">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Create Stock Transaction</h1>
            <p class="text-gray-600 mt-1">Record a new inventory transaction</p>
        </div>
        <a href="{{ route('inventory.transactions.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Back to Transactions
        </a>
    </div>

    <!-- Transaction Type Selection -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Select Transaction Type</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <button type="button" @click="selectedType = 'in'"
                    :class="selectedType === 'in' ? 'bg-green-100 border-green-500' : 'bg-white border-gray-300'"
                    class="p-4 border-2 rounded-lg hover:border-green-400 transition-colors">
                <div class="flex flex-col items-center">
                    <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mb-2">
                        <i class="fas fa-arrow-down text-white text-xl"></i>
                    </div>
                    <span class="font-semibold text-gray-800">Stock In</span>
                    <span class="text-xs text-gray-500">Add inventory</span>
                </div>
            </button>

            <button type="button" @click="selectedType = 'out'"
                    :class="selectedType === 'out' ? 'bg-red-100 border-red-500' : 'bg-white border-gray-300'"
                    class="p-4 border-2 rounded-lg hover:border-red-400 transition-colors">
                <div class="flex flex-col items-center">
                    <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center mb-2">
                        <i class="fas fa-arrow-up text-white text-xl"></i>
                    </div>
                    <span class="font-semibold text-gray-800">Stock Out</span>
                    <span class="text-xs text-gray-500">Remove inventory</span>
                </div>
            </button>

            <button type="button" @click="selectedType = 'transfer'"
                    :class="selectedType === 'transfer' ? 'bg-blue-100 border-blue-500' : 'bg-white border-gray-300'"
                    class="p-4 border-2 rounded-lg hover:border-blue-400 transition-colors">
                <div class="flex flex-col items-center">
                    <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mb-2">
                        <i class="fas fa-exchange-alt text-white text-xl"></i>
                    </div>
                    <span class="font-semibold text-gray-800">Transfer</span>
                    <span class="text-xs text-gray-500">Move between warehouses</span>
                </div>
            </button>

            <button type="button" @click="selectedType = 'adjustment'"
                    :class="selectedType === 'adjustment' ? 'bg-yellow-100 border-yellow-500' : 'bg-white border-gray-300'"
                    class="p-4 border-2 rounded-lg hover:border-yellow-400 transition-colors">
                <div class="flex flex-col items-center">
                    <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center mb-2">
                        <i class="fas fa-adjust text-white text-xl"></i>
                    </div>
                    <span class="font-semibold text-gray-800">Adjustment</span>
                    <span class="text-xs text-gray-500">Correct stock levels</span>
                </div>
            </button>
        </div>
    </div>

    <!-- Stock In Form -->
    <div x-show="selectedType === 'in'" class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-arrow-down text-green-600 mr-2"></i>Stock In Form
        </h2>
        <form action="{{ route('inventory.transactions.store') }}" method="POST">
            @csrf
            <input type="hidden" name="transaction_type" value="in">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Warehouse <span class="text-red-500">*</span>
                    </label>
                    <select name="warehouse_id" required class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Warehouse --</option>
                        @foreach($warehouses ?? [] as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Item <span class="text-red-500">*</span>
                    </label>
                    <select name="item_id" required class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Item --</option>
                        @foreach($items ?? [] as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Quantity <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="quantity" required min="1" step="1"
                           class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                           placeholder="0">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Unit Price (Rp) <span class="text-gray-400 text-xs">(Optional)</span>
                    </label>
                    <input type="number" name="unit_price" min="0" step="0.01"
                           class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                           placeholder="0.00">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Notes <span class="text-gray-400 text-xs">(Optional)</span>
                    </label>
                    <textarea name="notes" rows="3"
                              class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                              placeholder="Additional notes..."></textarea>
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-6 border-t border-gray-200">
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-save mr-2"></i>Record Stock In
                </button>
                <a href="{{ route('inventory.transactions.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>

    <!-- Stock Out Form -->
    <div x-show="selectedType === 'out'" class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-arrow-up text-red-600 mr-2"></i>Stock Out Form
        </h2>
        <form action="{{ route('inventory.transactions.store') }}" method="POST">
            @csrf
            <input type="hidden" name="transaction_type" value="out">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Warehouse <span class="text-red-500">*</span>
                    </label>
                    <select name="warehouse_id" required class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Warehouse --</option>
                        @foreach($warehouses ?? [] as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Item <span class="text-red-500">*</span>
                    </label>
                    <select name="item_id" required class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Item --</option>
                        @foreach($items ?? [] as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Quantity <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="quantity" required min="1" step="1"
                           class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                           placeholder="0">
                    <p class="text-xs text-gray-500 mt-1">Check stock availability before submitting</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Purpose / Notes <span class="text-gray-400 text-xs">(Optional)</span>
                    </label>
                    <textarea name="notes" rows="3"
                              class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                              placeholder="Purpose of stock out..."></textarea>
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-6 border-t border-gray-200">
                <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-save mr-2"></i>Record Stock Out
                </button>
                <a href="{{ route('inventory.transactions.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>

    <!-- Transfer Form -->
    <div x-show="selectedType === 'transfer'" class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-exchange-alt text-blue-600 mr-2"></i>Stock Transfer Form
        </h2>
        <form action="{{ route('inventory.transactions.store') }}" method="POST">
            @csrf
            <input type="hidden" name="transaction_type" value="transfer">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Source Warehouse <span class="text-red-500">*</span>
                    </label>
                    <select name="warehouse_id" required class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Source Warehouse --</option>
                        @foreach($warehouses ?? [] as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Destination Warehouse <span class="text-red-500">*</span>
                    </label>
                    <select name="destination_warehouse_id" required class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Destination Warehouse --</option>
                        @foreach($warehouses ?? [] as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Must be different from source warehouse</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Item <span class="text-red-500">*</span>
                    </label>
                    <select name="item_id" required class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Item --</option>
                        @foreach($items ?? [] as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Quantity <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="quantity" required min="1" step="1"
                           class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                           placeholder="0">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Notes <span class="text-gray-400 text-xs">(Optional)</span>
                    </label>
                    <textarea name="notes" rows="3"
                              class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                              placeholder="Reason for transfer..."></textarea>
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-6 border-t border-gray-200">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Record Transfer
                </button>
                <a href="{{ route('inventory.transactions.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>

    <!-- Adjustment Form -->
    <div x-show="selectedType === 'adjustment'" class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-adjust text-yellow-600 mr-2"></i>Stock Adjustment Form
        </h2>
        <form action="{{ route('inventory.transactions.store') }}" method="POST">
            @csrf
            <input type="hidden" name="transaction_type" value="adjustment">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Warehouse <span class="text-red-500">*</span>
                    </label>
                    <select name="warehouse_id" required class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Warehouse --</option>
                        @foreach($warehouses ?? [] as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Item <span class="text-red-500">*</span>
                    </label>
                    <select name="item_id" required class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Item --</option>
                        @foreach($items ?? [] as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Adjustment Type <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-4 mt-2">
                        <label class="flex items-center">
                            <input type="radio" name="adjustment_type" value="increase" required class="mr-2">
                            <span class="text-sm text-gray-700">Increase</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="adjustment_type" value="decrease" required class="mr-2">
                            <span class="text-sm text-gray-700">Decrease</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Quantity <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="quantity" required min="1" step="1"
                           class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                           placeholder="0">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Reason <span class="text-red-500">* (Required for audit)</span>
                    </label>
                    <textarea name="notes" rows="3" required
                              class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                              placeholder="Detailed reason for stock adjustment..."></textarea>
                </div>
            </div>

            <div class="flex gap-3 mt-6 pt-6 border-t border-gray-200">
                <button type="submit" class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
                    <i class="fas fa-save mr-2"></i>Record Adjustment
                </button>
                <a href="{{ route('inventory.transactions.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function transactionForm() {
    return {
        selectedType: 'in'
    }
}
</script>
@endsection
