@extends('layouts.app')

@section('title', 'Stock Transactions - AERGAS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Stock Transactions</h1>
            <p class="text-gray-600 mt-1">View all inventory transactions</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.dashboard') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i>Dashboard
            </a>
            <a href="{{ route('inventory.transactions.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>New Transaction
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('error') }}</span>
    </div>
    @endif

    <!-- Search/Filter -->
    <div class="bg-white p-4 rounded-xl card-shadow">
        <form method="GET" action="{{ route('inventory.transactions.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search transaction..."
                       class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <select name="type" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>Stock In</option>
                    <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Stock Out</option>
                    <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                    <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                </select>
            </div>
            <div>
                <select name="warehouse_id" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                    <option value="">All Warehouses</option>
                    @foreach($warehouses ?? [] as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       placeholder="From Date"
                       class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <i class="fas fa-search mr-1"></i>Search
                </button>
                <a href="{{ route('inventory.transactions.index') }}" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl card-shadow overflow-hidden">
        @if($transactions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction #</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Warehouse</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Before → After</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performed By</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($transactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                {{ $transaction->transaction_number ?? '#' . $transaction->id }}
                            </td>
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
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900">{{ $transaction->warehouse->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $transaction->warehouse->code ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $transaction->item->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $transaction->item->code ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                {{ number_format($transaction->quantity, 0, ',', '.') }} {{ $transaction->item->unit ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ number_format($transaction->quantity_before ?? 0, 0, ',', '.') }} → {{ number_format($transaction->quantity_after ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $transaction->created_at->format('d M Y') }}<br>
                                <span class="text-xs text-gray-500">{{ $transaction->created_at->format('H:i') }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $transaction->user->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('inventory.transactions.show', $transaction) }}" class="px-3 py-1.5 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200" title="View">
                                    <i class="fas fa-eye mr-1"></i>View
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $transactions->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500 text-lg font-medium mb-2">No transactions found</p>
                <p class="text-gray-400 text-sm mb-4">Get started by creating your first transaction</p>
                <a href="{{ route('inventory.transactions.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>Add First Transaction
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
