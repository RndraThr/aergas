@extends('layouts.app')

@section('title', 'Stock Opname Details - AERGAS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Stock Opname Details</h1>
            <p class="text-gray-600 mt-1">View detailed information about this stock opname</p>
        </div>
        <a href="{{ route('inventory.stock-opnames.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Back to Stock Opnames
        </a>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Opname Header -->
    <div class="bg-gradient-to-br from-yellow-50 to-orange-100 rounded-xl p-6 border border-yellow-200">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $stockOpname->opname_number }}</h2>
                <p class="text-sm text-gray-600 mt-1">Created on {{ $stockOpname->created_at->format('d F Y, H:i') }}</p>
            </div>
            <span class="px-4 py-2 text-sm font-medium rounded-full
                @if($stockOpname->status === 'planned') bg-blue-100 text-blue-800
                @elseif($stockOpname->status === 'in_progress') bg-yellow-100 text-yellow-800
                @elseif($stockOpname->status === 'completed') bg-green-100 text-green-800
                @else bg-purple-100 text-purple-800
                @endif">
                {{ ucfirst(str_replace('_', ' ', $stockOpname->status)) }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <div class="text-sm text-orange-600 font-medium mb-1">Warehouse</div>
                <div class="font-semibold text-gray-900">{{ $stockOpname->warehouse->name ?? '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-orange-600 font-medium mb-1">Schedule Date</div>
                <div class="font-semibold text-gray-900">{{ $stockOpname->schedule_date ? $stockOpname->schedule_date->format('d M Y') : '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-orange-600 font-medium mb-1">Performed By</div>
                <div class="font-semibold text-gray-900">{{ $stockOpname->performedBy->name ?? '-' }}</div>
            </div>
        </div>

        @if($stockOpname->description)
        <div class="mt-4 pt-4 border-t border-orange-200">
            <div class="text-sm text-orange-600 font-medium mb-1">Description</div>
            <div class="text-gray-700">{{ $stockOpname->description }}</div>
        </div>
        @endif
    </div>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @php
            $totalItems = $stockOpname->items->count();
            $itemsWithDiscrepancy = $stockOpname->items->filter(function($item) {
                return ($item->physical_qty - $item->system_qty) != 0;
            })->count();
            $totalValueDifference = $stockOpname->items->sum(function($item) {
                $diff = $item->physical_qty - $item->system_qty;
                return $diff * ($item->item->last_unit_price ?? 0);
            });
        @endphp

        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="text-sm text-gray-600 mb-1">Total Items Counted</div>
            <div class="text-3xl font-bold text-gray-900">{{ $totalItems }}</div>
        </div>

        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="text-sm text-gray-600 mb-1">Items with Discrepancy</div>
            <div class="text-3xl font-bold text-orange-600">{{ $itemsWithDiscrepancy }}</div>
        </div>

        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="text-sm text-gray-600 mb-1">Accuracy Rate</div>
            <div class="text-3xl font-bold text-green-600">
                {{ $totalItems > 0 ? number_format((($totalItems - $itemsWithDiscrepancy) / $totalItems) * 100, 1) : 0 }}%
            </div>
        </div>

        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="text-sm text-gray-600 mb-1">Total Value Difference</div>
            <div class="text-xl font-bold {{ $totalValueDifference < 0 ? 'text-red-600' : ($totalValueDifference > 0 ? 'text-green-600' : 'text-gray-900') }}">
                Rp {{ number_format(abs($totalValueDifference), 0, ',', '.') }}
                @if($totalValueDifference < 0)
                    <span class="text-xs">(Loss)</span>
                @elseif($totalValueDifference > 0)
                    <span class="text-xs">(Gain)</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Counted Items -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-clipboard-list text-green-600 mr-2"></i>Counted Items
        </h2>

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
                    @foreach($stockOpname->items as $opnameItem)
                    @php
                        $difference = $opnameItem->physical_qty - $opnameItem->system_qty;
                        $variancePct = $opnameItem->system_qty > 0 ? ($difference / $opnameItem->system_qty) * 100 : 0;
                    @endphp
                    <tr class="hover:bg-gray-50 {{ abs($variancePct) > 5 ? 'bg-red-50' : '' }}">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $opnameItem->item->name ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $opnameItem->item->code ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ number_format($opnameItem->system_qty, 0, ',', '.') }} {{ $opnameItem->item->unit ?? '' }}
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            {{ number_format($opnameItem->physical_qty, 0, ',', '.') }} {{ $opnameItem->item->unit ?? '' }}
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold {{ $difference < 0 ? 'text-red-600' : ($difference > 0 ? 'text-green-600' : 'text-gray-900') }}">
                            {{ $difference > 0 ? '+' : '' }}{{ number_format($difference, 0, ',', '.') }} {{ $opnameItem->item->unit ?? '' }}
                        </td>
                        <td class="px-4 py-3 text-sm font-medium {{ abs($variancePct) > 5 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ number_format($variancePct, 2) }}%
                            @if(abs($variancePct) > 5)
                                <i class="fas fa-exclamation-triangle ml-1"></i>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $opnameItem->notes ?? '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Actions -->
    @if($stockOpname->status === 'completed')
    <div class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-check-circle text-purple-600 mr-2"></i>Approve & Create Adjustments
        </h2>
        <div class="flex items-center justify-between bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div>
                <p class="font-medium text-gray-900">Approve this stock opname and create adjustment transactions</p>
                <p class="text-sm text-gray-600 mt-1">This will create stock adjustment transactions for all items with discrepancies to match the physical count.</p>
                @if($itemsWithDiscrepancy > 0)
                <p class="text-sm font-semibold text-orange-600 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>{{ $itemsWithDiscrepancy }} adjustment(s) will be created
                </p>
                @endif
            </div>
            <form action="{{ route('inventory.stock-opnames.approve', $stockOpname) }}" method="POST">
                @csrf
                <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    <i class="fas fa-check-double mr-2"></i>Approve Opname
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- Audit Trail -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-history text-blue-600 mr-2"></i>Audit Trail
        </h2>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Performed By:</span>
                <span class="font-semibold text-gray-900">{{ $stockOpname->performedBy->name ?? '-' }}</span>
            </div>
            @if($stockOpname->approved_by)
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Approved By:</span>
                <span class="font-semibold text-gray-900">{{ $stockOpname->approvedBy->name ?? '-' }}</span>
            </div>
            @endif
            @if($stockOpname->approved_at)
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Approved At:</span>
                <span class="font-semibold text-gray-900">{{ $stockOpname->approved_at->format('d M Y H:i') }}</span>
            </div>
            @endif
            @if($stockOpname->completed_at)
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Completed At:</span>
                <span class="font-semibold text-gray-900">{{ $stockOpname->completed_at->format('d M Y H:i') }}</span>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
