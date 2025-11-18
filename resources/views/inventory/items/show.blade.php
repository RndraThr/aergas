@extends('layouts.app')

@section('title', 'Item Details - AERGAS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Item Details</h1>
            <p class="text-gray-600 mt-1">View detailed information about this item</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.items.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i>Back to Items
            </a>
            <a href="{{ route('inventory.items.edit', $item) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                <i class="fas fa-edit mr-2"></i>Edit Item
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Item Details Card -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-xl p-6 border border-blue-200">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
                <div class="text-sm text-blue-600 font-medium mb-1">Item Code</div>
                <div class="text-xl font-bold text-gray-900">{{ $item->code }}</div>
            </div>
            <div>
                <div class="text-sm text-blue-600 font-medium mb-1">Item Name</div>
                <div class="text-xl font-bold text-gray-900">{{ $item->name }}</div>
            </div>
            <div>
                <div class="text-sm text-blue-600 font-medium mb-1">Category</div>
                <div class="text-lg font-semibold text-gray-800">
                    @if($item->category)
                        <span class="px-3 py-1 bg-white rounded-lg">{{ $item->category->name }}</span>
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </div>
            </div>
            <div>
                <div class="text-sm text-blue-600 font-medium mb-1">Unit</div>
                <div class="text-lg font-semibold text-gray-800">{{ strtoupper($item->unit) }}</div>
            </div>
        </div>

        @if($item->description)
        <div class="mt-4 pt-4 border-t border-blue-200">
            <div class="text-sm text-blue-600 font-medium mb-1">Description</div>
            <div class="text-gray-700">{{ $item->description }}</div>
        </div>
        @endif
    </div>

    <!-- Stock Status Alert -->
    @php
        $totalStock = $item->stocks->sum('available_quantity') ?? 0;
        $reorderPoint = $item->reorder_point ?? 0;
    @endphp

    @if($totalStock <= $reorderPoint && $reorderPoint > 0)
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded relative" role="alert">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle mr-3 text-xl"></i>
            <div>
                <strong class="font-bold">Reorder Alert!</strong>
                <span class="block sm:inline ml-2">This item has reached or fallen below the reorder point. Current stock: {{ number_format($totalStock, 0, ',', '.') }} {{ $item->unit }}. Reorder point: {{ number_format($reorderPoint, 0, ',', '.') }} {{ $item->unit }}.</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Stock Summary -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-boxes text-green-600 mr-2"></i>Stock Summary by Warehouse
        </h2>

        @if($item->stocks && $item->stocks->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Warehouse</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Available Quantity</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reserved Quantity</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">In-Transit Quantity</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($item->stocks as $stock)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $stock->warehouse->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $stock->warehouse->code ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-green-700">
                                {{ number_format($stock->available_quantity ?? 0, 0, ',', '.') }} {{ $item->unit }}
                            </td>
                            <td class="px-4 py-3 text-sm text-yellow-700">
                                {{ number_format($stock->reserved_quantity ?? 0, 0, ',', '.') }} {{ $item->unit }}
                            </td>
                            <td class="px-4 py-3 text-sm text-blue-700">
                                {{ number_format($stock->in_transit_quantity ?? 0, 0, ',', '.') }} {{ $item->unit }}
                            </td>
                            <td class="px-4 py-3 text-sm font-bold text-gray-900">
                                {{ number_format(($stock->available_quantity ?? 0) + ($stock->reserved_quantity ?? 0) + ($stock->in_transit_quantity ?? 0), 0, ',', '.') }} {{ $item->unit }}
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-semibold">
                            <td class="px-4 py-3 text-sm text-gray-900">TOTAL</td>
                            <td class="px-4 py-3 text-sm font-bold text-green-700">
                                {{ number_format($item->stocks->sum('available_quantity'), 0, ',', '.') }} {{ $item->unit }}
                            </td>
                            <td class="px-4 py-3 text-sm font-bold text-yellow-700">
                                {{ number_format($item->stocks->sum('reserved_quantity'), 0, ',', '.') }} {{ $item->unit }}
                            </td>
                            <td class="px-4 py-3 text-sm font-bold text-blue-700">
                                {{ number_format($item->stocks->sum('in_transit_quantity'), 0, ',', '.') }} {{ $item->unit }}
                            </td>
                            <td class="px-4 py-3 text-sm font-bold text-gray-900">
                                {{ number_format($item->stocks->sum('available_quantity') + $item->stocks->sum('reserved_quantity') + $item->stocks->sum('in_transit_quantity'), 0, ',', '.') }} {{ $item->unit }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500 text-sm">No stock available in any warehouse</p>
            </div>
        @endif
    </div>

    <!-- Stock Settings & Pricing -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Stock Settings -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                <i class="fas fa-cog text-blue-600 mr-2"></i>Stock Settings
            </h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Minimum Stock:</span>
                    <span class="font-semibold text-gray-900">{{ number_format($item->minimum_stock ?? 0, 0, ',', '.') }} {{ $item->unit }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Reorder Point:</span>
                    <span class="font-semibold text-gray-900">{{ number_format($item->reorder_point ?? 0, 0, ',', '.') }} {{ $item->unit }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Status:</span>
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $item->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $item->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Pricing -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                <i class="fas fa-tag text-orange-600 mr-2"></i>Pricing Information
            </h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Last Unit Price:</span>
                    <span class="font-semibold text-gray-900">Rp {{ number_format($item->last_unit_price ?? 0, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Specifications -->
    @if($item->specification)
    <div class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-clipboard-list text-purple-600 mr-2"></i>Specifications
        </h2>
        <div class="bg-gray-50 rounded p-4">
            <pre class="text-sm text-gray-700 whitespace-pre-wrap">{{ $item->specification }}</pre>
        </div>
    </div>
    @endif

    <!-- Recent Transactions -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-history text-green-600 mr-2"></i>Recent Stock Transactions (Last 20)
            </h2>
            <a href="{{ route('inventory.transactions.index', ['item_id' => $item->id]) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        @if($item->transactions && $item->transactions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Warehouse</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Before → After</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">By</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($item->transactions->take(20) as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $transaction->created_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($transaction->transaction_type === 'in') bg-green-100 text-green-800
                                    @elseif($transaction->transaction_type === 'out') bg-red-100 text-red-800
                                    @elseif($transaction->transaction_type === 'transfer') bg-blue-100 text-blue-800
                                    @else bg-yellow-100 text-yellow-800
                                    @endif">
                                    {{ ucfirst($transaction->transaction_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $transaction->warehouse->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ number_format($transaction->quantity, 0, ',', '.') }} {{ $item->unit }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ number_format($transaction->quantity_before ?? 0, 0, ',', '.') }} → {{ number_format($transaction->quantity_after ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $transaction->user->name ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500 text-sm">No transactions found for this item</p>
            </div>
        @endif
    </div>
</div>
@endsection
