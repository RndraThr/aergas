@extends('layouts.app')

@section('title', 'Goods Receipt Details - AERGAS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Goods Receipt Details</h1>
            <p class="text-gray-600 mt-1">View detailed information about this goods receipt</p>
        </div>
        <a href="{{ route('inventory.goods-receipts.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Back to Goods Receipts
        </a>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <!-- GR Header -->
    <div class="bg-gradient-to-br from-green-50 to-emerald-100 rounded-xl p-6 border border-green-200">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $goodsReceipt->receipt_number }}</h2>
                <p class="text-sm text-gray-600 mt-1">Created on {{ $goodsReceipt->created_at->format('d F Y, H:i') }}</p>
            </div>
            <span class="px-4 py-2 text-sm font-medium rounded-full
                @if($goodsReceipt->status === 'draft') bg-gray-100 text-gray-800
                @else bg-green-100 text-green-800
                @endif">
                {{ ucfirst($goodsReceipt->status) }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <div class="text-sm text-green-600 font-medium mb-1">Purchase Order</div>
                <a href="{{ route('inventory.purchase-orders.show', $goodsReceipt->purchaseOrder) }}" class="font-semibold text-blue-600 hover:text-blue-800">
                    {{ $goodsReceipt->purchaseOrder->po_number ?? '-' }}
                </a>
            </div>
            <div>
                <div class="text-sm text-green-600 font-medium mb-1">Warehouse</div>
                <div class="font-semibold text-gray-900">{{ $goodsReceipt->warehouse->name ?? '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-green-600 font-medium mb-1">Received Date</div>
                <div class="font-semibold text-gray-900">{{ $goodsReceipt->received_date ? $goodsReceipt->received_date->format('d M Y') : '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-green-600 font-medium mb-1">Received By</div>
                <div class="font-semibold text-gray-900">{{ $goodsReceipt->receivedBy->name ?? '-' }}</div>
            </div>
        </div>
    </div>

    <!-- Received Items -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-boxes text-green-600 mr-2"></i>Received Items
        </h2>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ordered Qty</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Received Qty</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($goodsReceipt->items as $grItem)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $grItem->poItem->item->name ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $grItem->poItem->item->code ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ number_format($grItem->poItem->quantity ?? 0, 0, ',', '.') }} {{ $grItem->poItem->item->unit ?? '' }}
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                            {{ number_format($grItem->received_qty, 0, ',', '.') }} {{ $grItem->poItem->item->unit ?? '' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($grItem->received_qty >= $grItem->poItem->quantity)
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                    Complete
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                    Partial
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $grItem->notes ?? '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Notes -->
    @if($goodsReceipt->notes)
    <div class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-sticky-note text-purple-600 mr-2"></i>Notes
        </h2>
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-gray-700">{{ $goodsReceipt->notes }}</p>
        </div>
    </div>
    @endif

    <!-- Approve & Update Stock -->
    @if($goodsReceipt->status === 'draft')
    <div class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-check-circle text-green-600 mr-2"></i>Approve & Update Stock
        </h2>
        <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg p-4">
            <div>
                <p class="font-medium text-gray-900">Approve this goods receipt and update stock levels</p>
                <p class="text-sm text-gray-600 mt-1">This will add the received quantities to the warehouse stock and mark the receipt as completed.</p>
            </div>
            <form action="{{ route('inventory.goods-receipts.approve', $goodsReceipt) }}" method="POST">
                @csrf
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-check mr-2"></i>Approve & Update Stock
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- Stock Updated Info -->
    @if($goodsReceipt->status === 'completed')
    <div class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-check-double text-green-600 mr-2"></i>Stock Updated
        </h2>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Stock Updated:</span>
                <span class="font-semibold text-green-700">Yes</span>
            </div>
            @if($goodsReceipt->approved_by)
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Approved By:</span>
                <span class="font-semibold text-gray-900">{{ $goodsReceipt->approvedBy->name ?? '-' }}</span>
            </div>
            @endif
            @if($goodsReceipt->approved_at)
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Approved At:</span>
                <span class="font-semibold text-gray-900">{{ $goodsReceipt->approved_at->format('d M Y H:i') }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
