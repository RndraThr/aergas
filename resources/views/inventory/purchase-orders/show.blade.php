@extends('layouts.app')

@section('title', 'Purchase Order Details - AERGAS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Purchase Order Details</h1>
            <p class="text-gray-600 mt-1">View detailed information about this purchase order</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.purchase-orders.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i>Back to POs
            </a>
            @if($purchaseOrder->status === 'approved')
            <a href="{{ route('inventory.goods-receipts.create', ['po_id' => $purchaseOrder->id]) }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                <i class="fas fa-clipboard-check mr-2"></i>Create Goods Receipt
            </a>
            @endif
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <!-- PO Header -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-xl p-6 border border-blue-200">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $purchaseOrder->po_number }}</h2>
                <p class="text-sm text-gray-600 mt-1">Created on {{ $purchaseOrder->created_at->format('d F Y, H:i') }}</p>
            </div>
            <span class="px-4 py-2 text-sm font-medium rounded-full
                @if($purchaseOrder->status === 'draft') bg-gray-100 text-gray-800
                @elseif($purchaseOrder->status === 'submitted') bg-blue-100 text-blue-800
                @elseif($purchaseOrder->status === 'approved') bg-green-100 text-green-800
                @elseif($purchaseOrder->status === 'received') bg-purple-100 text-purple-800
                @else bg-red-100 text-red-800
                @endif">
                {{ ucfirst($purchaseOrder->status) }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <div class="text-sm text-blue-600 font-medium mb-1">Supplier</div>
                <div class="font-semibold text-gray-900">{{ $purchaseOrder->supplier->name ?? '-' }}</div>
                <div class="text-xs text-gray-600">{{ $purchaseOrder->supplier->code ?? '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-blue-600 font-medium mb-1">Warehouse</div>
                <div class="font-semibold text-gray-900">{{ $purchaseOrder->warehouse->name ?? '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-blue-600 font-medium mb-1">Order Date</div>
                <div class="font-semibold text-gray-900">{{ $purchaseOrder->po_date ? $purchaseOrder->po_date->format('d M Y') : '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-blue-600 font-medium mb-1">Expected Delivery</div>
                <div class="font-semibold text-gray-900">{{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('d M Y') : '-' }}</div>
            </div>
        </div>
    </div>

    <!-- PO Items -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-list text-green-600 mr-2"></i>Order Items
        </h2>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($purchaseOrder->details as $detail)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $detail->item->name ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $detail->item->code ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ number_format($detail->quantity_ordered, 0, ',', '.') }} {{ $detail->item->unit ?? '' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            Rp {{ number_format($detail->unit_price, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                            Rp {{ number_format($detail->total_price, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="3" class="px-4 py-4 text-right font-semibold text-gray-900">Grand Total:</td>
                        <td class="px-4 py-4 font-bold text-lg text-gray-900">
                            Rp {{ number_format($purchaseOrder->total_amount ?? 0, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Notes -->
    @if($purchaseOrder->notes)
    <div class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-sticky-note text-purple-600 mr-2"></i>Notes
        </h2>
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-gray-700">{{ $purchaseOrder->notes }}</p>
        </div>
    </div>
    @endif

    <!-- Approval Section -->
    @if(in_array($purchaseOrder->status, ['draft', 'submitted']))
    <div class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-check-circle text-green-600 mr-2"></i>Approval Required
        </h2>
        <div class="flex items-center justify-between bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div>
                <p class="font-medium text-gray-900">This purchase order is waiting for approval</p>
                <p class="text-sm text-gray-600 mt-1">Review the order details and approve to proceed</p>
            </div>
            <form action="{{ route('inventory.purchase-orders.approve', $purchaseOrder) }}" method="POST">
                @csrf
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-check mr-2"></i>Approve PO
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- Cancel PO -->
    @if($purchaseOrder->status !== 'received' && $purchaseOrder->status !== 'cancelled')
    <div class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-times-circle text-red-600 mr-2"></i>Cancel Purchase Order
        </h2>
        <div class="flex items-center justify-between bg-red-50 border border-red-200 rounded-lg p-4">
            <div>
                <p class="font-medium text-gray-900">Cancel this purchase order</p>
                <p class="text-sm text-gray-600 mt-1">This action cannot be undone. The PO will be marked as cancelled.</p>
            </div>
            <form action="{{ route('inventory.purchase-orders.cancel', $purchaseOrder) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this purchase order?')">
                @csrf
                <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-times mr-2"></i>Cancel PO
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
                <span class="text-gray-600">Created By:</span>
                <span class="font-semibold text-gray-900">{{ $purchaseOrder->creator->name ?? '-' }}</span>
            </div>
            @if($purchaseOrder->approved_by)
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Approved By:</span>
                <span class="font-semibold text-gray-900">{{ $purchaseOrder->approver->name ?? '-' }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Approved At:</span>
                <span class="font-semibold text-gray-900">{{ $purchaseOrder->approved_at ? $purchaseOrder->approved_at->format('d M Y H:i') : '-' }}</span>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
