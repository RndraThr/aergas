@extends('layouts.app')

@section('title', 'Stock Opnames - AERGAS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Stock Opnames</h1>
            <p class="text-gray-600 mt-1">Manage all stock opname activities</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.dashboard') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i>Dashboard
            </a>
            <a href="{{ route('inventory.stock-opnames.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>New Stock Opname
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
        <form method="GET" action="{{ route('inventory.stock-opnames.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search opname number..."
                       class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <select name="status" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="planned" {{ request('status') === 'planned' ? 'selected' : '' }}>Planned</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
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
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <i class="fas fa-search mr-1"></i>Search
                </button>
                <a href="{{ route('inventory.stock-opnames.index') }}" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl card-shadow overflow-hidden">
        @if($stockOpnames->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opname Number</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Warehouse</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedule Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($stockOpnames as $opname)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $opname->opname_number }}</td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900">{{ $opname->warehouse->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $opname->warehouse->code ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $opname->schedule_date ? $opname->schedule_date->format('d M Y') : '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($opname->status === 'planned') bg-blue-100 text-blue-800
                                    @elseif($opname->status === 'in_progress') bg-yellow-100 text-yellow-800
                                    @elseif($opname->status === 'completed') bg-green-100 text-green-800
                                    @else bg-purple-100 text-purple-800
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $opname->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('inventory.stock-opnames.show', $opname) }}" class="px-3 py-1.5 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200" title="View">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </a>
                                    @if($opname->status === 'in_progress')
                                    <form action="{{ route('inventory.stock-opnames.complete', $opname) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 text-xs bg-green-100 text-green-700 rounded hover:bg-green-200" title="Complete">
                                            <i class="fas fa-check mr-1"></i>Complete
                                        </button>
                                    </form>
                                    @endif
                                    @if($opname->status === 'completed')
                                    <form action="{{ route('inventory.stock-opnames.approve', $opname) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 text-xs bg-purple-100 text-purple-700 rounded hover:bg-purple-200" title="Approve">
                                            <i class="fas fa-check-double mr-1"></i>Approve
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $stockOpnames->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500 text-lg font-medium mb-2">No stock opnames found</p>
                <p class="text-gray-400 text-sm mb-4">Get started by creating your first stock opname</p>
                <a href="{{ route('inventory.stock-opnames.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>Create First Stock Opname
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
