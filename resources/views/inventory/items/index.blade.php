@extends('layouts.app')

@section('title', 'Items - AERGAS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Inventory Items</h1>
            <p class="text-gray-600 mt-1">Manage all inventory items</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.dashboard') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i>Dashboard
            </a>
            <a href="{{ route('inventory.items.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Add New Item
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
        <form method="GET" action="{{ route('inventory.items.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by code, name..."
                       class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <select name="category_id" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    @foreach($categories ?? [] as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <i class="fas fa-search mr-1"></i>Search
                </button>
                <a href="{{ route('inventory.items.index') }}" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl card-shadow overflow-hidden">
        @if($items->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Stock</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reorder Point</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($items as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item->code }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $item->name }}</div>
                                @if($item->description)
                                <div class="text-xs text-gray-500">{{ Str::limit($item->description, 40) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($item->category)
                                    <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded">
                                        {{ $item->category->name }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ strtoupper($item->unit ?? '-') }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $totalStock = $item->stocks_sum_available_quantity ?? 0;
                                    $minStock = $item->minimum_stock ?? 0;
                                    $reorderPoint = $item->reorder_point ?? 0;

                                    if ($totalStock <= 0) {
                                        $statusColor = 'red';
                                        $statusText = 'Out of Stock';
                                    } elseif ($totalStock <= $reorderPoint) {
                                        $statusColor = 'yellow';
                                        $statusText = 'Low Stock';
                                    } else {
                                        $statusColor = 'green';
                                        $statusText = 'Adequate';
                                    }
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800">
                                    {{ $statusText }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                {{ number_format($item->stocks_sum_available_quantity ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ number_format($item->reorder_point ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('inventory.items.show', $item) }}" class="px-3 py-1.5 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200" title="View">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </a>
                                    <a href="{{ route('inventory.items.edit', $item) }}" class="px-3 py-1.5 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200" title="Edit">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </a>
                                    <form action="{{ route('inventory.items.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this item?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200" title="Delete">
                                            <i class="fas fa-trash mr-1"></i>Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $items->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500 text-lg font-medium mb-2">No items found</p>
                <p class="text-gray-400 text-sm mb-4">Get started by creating your first item</p>
                <a href="{{ route('inventory.items.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>Add First Item
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
