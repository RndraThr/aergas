@extends('layouts.app')

@section('title', 'Transaction Details - AERGAS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Transaction Details</h1>
            <p class="text-gray-600 mt-1">View detailed information about this transaction</p>
        </div>
        <a href="{{ route('inventory.transactions.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Back to Transactions
        </a>
    </div>

    <!-- Transaction Details Card -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $transaction->transaction_number ?? '#' . $transaction->id }}</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $transaction->created_at->format('d F Y, H:i') }}</p>
            </div>
            <span class="px-4 py-2 text-sm font-medium rounded-full
                @if($transaction->transaction_type === 'in') bg-green-100 text-green-800
                @elseif($transaction->transaction_type === 'out') bg-red-100 text-red-800
                @elseif($transaction->transaction_type === 'transfer') bg-blue-100 text-blue-800
                @else bg-yellow-100 text-yellow-800
                @endif">
                {{ ucfirst($transaction->transaction_type) }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Transaction Information -->
            <div class="space-y-4">
                <h3 class="font-semibold text-gray-800 text-lg mb-3">Transaction Information</h3>

                <div>
                    <div class="text-sm text-gray-600 mb-1">Warehouse</div>
                    <div class="font-medium text-gray-900">{{ $transaction->warehouse->name ?? '-' }}</div>
                    <div class="text-xs text-gray-500">{{ $transaction->warehouse->code ?? '-' }}</div>
                </div>

                @if($transaction->transaction_type === 'transfer' && $transaction->destinationWarehouse)
                <div>
                    <div class="text-sm text-gray-600 mb-1">Destination Warehouse</div>
                    <div class="font-medium text-gray-900">{{ $transaction->destinationWarehouse->name ?? '-' }}</div>
                    <div class="text-xs text-gray-500">{{ $transaction->destinationWarehouse->code ?? '-' }}</div>
                </div>
                @endif

                <div>
                    <div class="text-sm text-gray-600 mb-1">Item</div>
                    <div class="font-medium text-gray-900">{{ $transaction->item->name ?? '-' }}</div>
                    <div class="text-xs text-gray-500">{{ $transaction->item->code ?? '-' }}</div>
                </div>

                <div>
                    <div class="text-sm text-gray-600 mb-1">Performed By</div>
                    <div class="font-medium text-gray-900">{{ $transaction->user->name ?? '-' }}</div>
                    <div class="text-xs text-gray-500">{{ $transaction->user->email ?? '-' }}</div>
                </div>
            </div>

            <!-- Quantity Information -->
            <div class="space-y-4">
                <h3 class="font-semibold text-gray-800 text-lg mb-3">Quantity Information</h3>

                <div>
                    <div class="text-sm text-gray-600 mb-1">Transaction Quantity</div>
                    <div class="text-2xl font-bold text-gray-900">
                        {{ number_format($transaction->quantity, 0, ',', '.') }} {{ $transaction->item->unit ?? '' }}
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-600">Quantity Before:</span>
                        <span class="font-semibold text-gray-900">{{ number_format($transaction->quantity_before ?? 0, 0, ',', '.') }} {{ $transaction->item->unit ?? '' }}</span>
                    </div>
                    <div class="flex items-center justify-center my-2">
                        <i class="fas fa-arrow-down text-gray-400"></i>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Quantity After:</span>
                        <span class="font-bold text-lg
                            @if($transaction->transaction_type === 'in' || ($transaction->transaction_type === 'adjustment' && $transaction->quantity_after > $transaction->quantity_before)) text-green-600
                            @elseif($transaction->transaction_type === 'out' || ($transaction->transaction_type === 'adjustment' && $transaction->quantity_after < $transaction->quantity_before)) text-red-600
                            @else text-blue-600
                            @endif">
                            {{ number_format($transaction->quantity_after ?? 0, 0, ',', '.') }} {{ $transaction->item->unit ?? '' }}
                        </span>
                    </div>
                </div>

                @if($transaction->unit_price)
                <div>
                    <div class="text-sm text-gray-600 mb-1">Unit Price</div>
                    <div class="font-medium text-gray-900">Rp {{ number_format($transaction->unit_price, 2, ',', '.') }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Notes -->
        @if($transaction->notes)
        <div class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="font-semibold text-gray-800 text-lg mb-3">Notes</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-700">{{ $transaction->notes }}</p>
            </div>
        </div>
        @endif

        <!-- Reference Information -->
        @if($transaction->reference_type || $transaction->reference_id)
        <div class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="font-semibold text-gray-800 text-lg mb-3">Reference</h3>
            <div class="grid grid-cols-2 gap-4">
                @if($transaction->reference_type)
                <div>
                    <div class="text-sm text-gray-600 mb-1">Reference Type</div>
                    <div class="font-medium text-gray-900">{{ $transaction->reference_type }}</div>
                </div>
                @endif
                @if($transaction->reference_id)
                <div>
                    <div class="text-sm text-gray-600 mb-1">Reference ID</div>
                    <div class="font-medium text-gray-900">#{{ $transaction->reference_id }}</div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
