@extends('layouts.app')

@section('title', 'Supplier Details - AERGAS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Supplier Details</h1>
            <p class="text-gray-600 mt-1">View detailed information about this supplier</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.suppliers.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i>Back to Suppliers
            </a>
            <a href="{{ route('inventory.suppliers.edit', $supplier) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                <i class="fas fa-edit mr-2"></i>Edit Supplier
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Supplier Details Card -->
    <div class="bg-gradient-to-br from-indigo-50 to-purple-100 rounded-xl p-6 border border-indigo-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <div class="text-sm text-indigo-600 font-medium mb-1">Supplier Code</div>
                <div class="text-xl font-bold text-gray-900">{{ $supplier->code }}</div>
            </div>
            <div>
                <div class="text-sm text-indigo-600 font-medium mb-1">Supplier Name</div>
                <div class="text-xl font-bold text-gray-900">{{ $supplier->name }}</div>
            </div>
            <div>
                <div class="text-sm text-indigo-600 font-medium mb-1">Status</div>
                <div>
                    <span class="px-3 py-1 text-sm font-medium rounded-full {{ $supplier->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Contact Information -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                <i class="fas fa-phone text-green-600 mr-2"></i>Contact Information
            </h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Contact Person:</span>
                    <span class="font-semibold text-gray-900">{{ $supplier->contact_person ?? '-' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Phone:</span>
                    <span class="font-semibold text-gray-900">{{ $supplier->phone ?? '-' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Email:</span>
                    <span class="font-semibold text-gray-900">{{ $supplier->email ?? '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Address -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                <i class="fas fa-map-marker-alt text-red-600 mr-2"></i>Address
            </h2>
            <div class="space-y-2">
                @if($supplier->address)
                <p class="text-gray-700">{{ $supplier->address }}</p>
                @endif
                <div class="text-gray-700">
                    @if($supplier->city || $supplier->province)
                        {{ $supplier->city ?? '' }}{{ $supplier->city && $supplier->province ? ', ' : '' }}{{ $supplier->province ?? '' }}
                    @else
                        -
                    @endif
                </div>
                @if($supplier->postal_code)
                <div class="text-gray-700">{{ $supplier->postal_code }}</div>
                @endif
                @if(!$supplier->address && !$supplier->city && !$supplier->province && !$supplier->postal_code)
                <p class="text-gray-400">No address available</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Financial Information -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-dollar-sign text-orange-600 mr-2"></i>Financial Information
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">NPWP:</span>
                <span class="font-semibold text-gray-900">{{ $supplier->npwp ?? '-' }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Payment Terms:</span>
                <span class="font-semibold text-gray-900">{{ $supplier->payment_terms ?? '-' }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Bank Account Name:</span>
                <span class="font-semibold text-gray-900">{{ $supplier->bank_account_name ?? '-' }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Bank Account Number:</span>
                <span class="font-semibold text-gray-900">{{ $supplier->bank_account_number ?? '-' }}</span>
            </div>
        </div>
    </div>

    <!-- Notes -->
    @if($supplier->notes)
    <div class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">
            <i class="fas fa-sticky-note text-purple-600 mr-2"></i>Notes
        </h2>
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-gray-700">{{ $supplier->notes }}</p>
        </div>
    </div>
    @endif

    <!-- Recent Purchase Orders -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-shopping-cart text-blue-600 mr-2"></i>Recent Purchase Orders
            </h2>
            <a href="{{ route('inventory.purchase-orders.index', ['supplier_id' => $supplier->id]) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        @if($supplier->purchaseOrders && $supplier->purchaseOrders->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO Number</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($supplier->purchaseOrders->take(10) as $po)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $po->po_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $po->order_date ? $po->order_date->format('d M Y') : '-' }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-900">Rp {{ number_format($po->total_amount ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($po->status === 'draft') bg-gray-100 text-gray-800
                                    @elseif($po->status === 'submitted') bg-blue-100 text-blue-800
                                    @elseif($po->status === 'approved') bg-green-100 text-green-800
                                    @elseif($po->status === 'received') bg-purple-100 text-purple-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst($po->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('inventory.purchase-orders.show', $po) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-eye mr-1"></i>View
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500 text-sm">No purchase orders from this supplier yet</p>
            </div>
        @endif
    </div>
</div>
@endsection
