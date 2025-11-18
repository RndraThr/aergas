@extends('layouts.app')

@section('title', 'Warehouse Details - AERGAS')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Warehouse Details</h1>
            <p class="text-gray-600 mt-1">{{ $warehouse->code }} - {{ $warehouse->name }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('inventory.warehouses.edit', $warehouse) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <a href="{{ route('inventory.warehouses.index') }}" class="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    <!-- Main Card: Warehouse Information -->
    <div class="bg-white rounded-xl card-shadow overflow-hidden">
        <!-- Gradient Header -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 px-6 py-4 border-b">
            <div class="flex items-center gap-3">
                <i class="fas fa-warehouse text-blue-600 text-2xl"></i>
                <h2 class="text-xl font-semibold text-gray-800">Warehouse Information</h2>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Code -->
                <div>
                    <label class="text-sm font-medium text-gray-500">Warehouse Code</label>
                    <p class="text-gray-800 font-semibold mt-1">{{ $warehouse->code }}</p>
                </div>

                <!-- Name -->
                <div>
                    <label class="text-sm font-medium text-gray-500">Warehouse Name</label>
                    <p class="text-gray-800 font-semibold mt-1">{{ $warehouse->name }}</p>
                </div>

                <!-- Type -->
                <div>
                    <label class="text-sm font-medium text-gray-500">Warehouse Type</label>
                    <div class="mt-1">
                        @if($warehouse->warehouse_type == 'pusat')
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-medium rounded-full">
                                <i class="fas fa-building mr-1"></i>Gudang Pusat
                            </span>
                        @elseif($warehouse->warehouse_type == 'cabang')
                            <span class="px-3 py-1 bg-green-100 text-green-700 text-sm font-medium rounded-full">
                                <i class="fas fa-store mr-1"></i>Gudang Cabang
                            </span>
                        @elseif($warehouse->warehouse_type == 'proyek')
                            <span class="px-3 py-1 bg-orange-100 text-orange-700 text-sm font-medium rounded-full">
                                <i class="fas fa-hard-hat mr-1"></i>Gudang Proyek
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="text-sm font-medium text-gray-500">Status</label>
                    <div class="mt-1">
                        @if($warehouse->is_active)
                            <span class="px-3 py-1 bg-green-100 text-green-700 text-sm font-medium rounded-full">
                                <i class="fas fa-check-circle mr-1"></i>Active
                            </span>
                        @else
                            <span class="px-3 py-1 bg-red-100 text-red-700 text-sm font-medium rounded-full">
                                <i class="fas fa-times-circle mr-1"></i>Inactive
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Location Section -->
            <div class="mt-8 pt-6 border-t">
                <div class="flex items-center gap-3 mb-4">
                    <i class="fas fa-map-marker-alt text-green-600 text-xl"></i>
                    <h3 class="text-lg font-semibold text-gray-800">Location Details</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Address -->
                    @if($warehouse->address)
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium text-gray-500">Address</label>
                            <p class="text-gray-800 mt-1">{{ $warehouse->address }}</p>
                        </div>
                    @endif

                    <!-- Location -->
                    @if($warehouse->location)
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium text-gray-500">Location</label>
                            <p class="text-gray-800 mt-1">{{ $warehouse->location }}</p>
                        </div>
                    @endif

                    <!-- City -->
                    @if($warehouse->city)
                        <div>
                            <label class="text-sm font-medium text-gray-500">City</label>
                            <p class="text-gray-800 mt-1">{{ $warehouse->city }}</p>
                        </div>
                    @endif

                    <!-- Province -->
                    @if($warehouse->province)
                        <div>
                            <label class="text-sm font-medium text-gray-500">Province</label>
                            <p class="text-gray-800 mt-1">{{ $warehouse->province }}</p>
                        </div>
                    @endif

                    <!-- Postal Code -->
                    @if($warehouse->postal_code)
                        <div>
                            <label class="text-sm font-medium text-gray-500">Postal Code</label>
                            <p class="text-gray-800 mt-1">{{ $warehouse->postal_code }}</p>
                        </div>
                    @endif

                    <!-- GPS Coordinates -->
                    @if($warehouse->latitude && $warehouse->longitude)
                        <div>
                            <label class="text-sm font-medium text-gray-500">GPS Coordinates</label>
                            <p class="text-gray-800 mt-1">
                                <i class="fas fa-map-pin mr-1 text-red-500"></i>
                                {{ $warehouse->latitude }}, {{ $warehouse->longitude }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- PIC Section -->
            <div class="mt-8 pt-6 border-t">
                <div class="flex items-center gap-3 mb-4">
                    <i class="fas fa-user-tie text-purple-600 text-xl"></i>
                    <h3 class="text-lg font-semibold text-gray-800">Person In Charge</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- PIC Name -->
                    @if($warehouse->pic_name)
                        <div>
                            <label class="text-sm font-medium text-gray-500">Name</label>
                            <p class="text-gray-800 mt-1">{{ $warehouse->pic_name }}</p>
                        </div>
                    @endif

                    <!-- PIC Phone -->
                    @if($warehouse->pic_phone)
                        <div>
                            <label class="text-sm font-medium text-gray-500">Phone</label>
                            <p class="text-gray-800 mt-1">
                                <i class="fas fa-phone mr-1 text-blue-500"></i>{{ $warehouse->pic_phone }}
                            </p>
                        </div>
                    @endif

                    <!-- PIC Email -->
                    @if($warehouse->pic_email)
                        <div>
                            <label class="text-sm font-medium text-gray-500">Email</label>
                            <p class="text-gray-800 mt-1">
                                <i class="fas fa-envelope mr-1 text-blue-500"></i>{{ $warehouse->pic_email }}
                            </p>
                        </div>
                    @endif
                </div>

                @if(!$warehouse->pic_name && !$warehouse->pic_phone && !$warehouse->pic_email)
                    <p class="text-gray-500 italic">No PIC information available</p>
                @endif
            </div>

            <!-- Description -->
            @if($warehouse->description)
                <div class="mt-8 pt-6 border-t">
                    <div class="flex items-center gap-3 mb-4">
                        <i class="fas fa-info-circle text-orange-600 text-xl"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Description</h3>
                    </div>
                    <p class="text-gray-800">{{ $warehouse->description }}</p>
                </div>
            @endif

            <!-- Metadata -->
            <div class="mt-8 pt-6 border-t">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <!-- Created By -->
                    @if($warehouse->created_by)
                        <div>
                            <label class="text-gray-500">Created By</label>
                            <p class="text-gray-800 mt-1">{{ $warehouse->creator->name ?? 'N/A' }}</p>
                        </div>
                    @endif

                    <!-- Updated By -->
                    @if($warehouse->updated_by)
                        <div>
                            <label class="text-gray-500">Updated By</label>
                            <p class="text-gray-800 mt-1">{{ $warehouse->updater->name ?? 'N/A' }}</p>
                        </div>
                    @endif

                    <!-- Created At -->
                    <div>
                        <label class="text-gray-500">Created At</label>
                        <p class="text-gray-800 mt-1">{{ $warehouse->created_at->format('d M Y, H:i') }}</p>
                    </div>

                    <!-- Updated At -->
                    <div>
                        <label class="text-gray-500">Last Updated</label>
                        <p class="text-gray-800 mt-1">{{ $warehouse->updated_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Summary Card -->
    <div class="bg-white rounded-xl card-shadow overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 px-6 py-4 border-b">
            <div class="flex items-center gap-3">
                <i class="fas fa-boxes text-blue-600 text-2xl"></i>
                <h2 class="text-xl font-semibold text-gray-800">Stock in This Warehouse</h2>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            @if($warehouse->stocks && $warehouse->stocks->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item Code</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Available</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Reserved</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">In-Transit</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Unit</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($warehouse->stocks as $stock)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $stock->item->code ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-800">
                                        {{ $stock->item->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $stock->item->category->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold text-green-600">
                                        {{ number_format($stock->available_quantity, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-orange-600">
                                        {{ number_format($stock->reserved_quantity, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-blue-600">
                                        {{ number_format($stock->in_transit_quantity, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center text-gray-600">
                                        {{ $stock->item->unit ?? 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-500 text-lg">No stock available in this warehouse</p>
                    <p class="text-gray-400 text-sm mt-2">Items will appear here once stock is added</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Transactions Card -->
    <div class="bg-white rounded-xl card-shadow overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 px-6 py-4 border-b">
            <div class="flex items-center gap-3">
                <i class="fas fa-exchange-alt text-blue-600 text-2xl"></i>
                <h2 class="text-xl font-semibold text-gray-800">Recent Transactions</h2>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            @if($warehouse->transactions && $warehouse->transactions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Transaction #</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($warehouse->transactions->take(10) as $transaction)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $transaction->transaction_number ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($transaction->type == 'in')
                                            <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded">
                                                <i class="fas fa-arrow-down mr-1"></i>Stock In
                                            </span>
                                        @elseif($transaction->type == 'out')
                                            <span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-medium rounded">
                                                <i class="fas fa-arrow-up mr-1"></i>Stock Out
                                            </span>
                                        @elseif($transaction->type == 'transfer')
                                            <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded">
                                                <i class="fas fa-exchange-alt mr-1"></i>Transfer
                                            </span>
                                        @elseif($transaction->type == 'adjustment')
                                            <span class="px-2 py-1 bg-orange-100 text-orange-700 text-xs font-medium rounded">
                                                <i class="fas fa-sliders-h mr-1"></i>Adjustment
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-800">
                                        {{ $transaction->item->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">
                                        {{ number_format($transaction->quantity, 2) }} {{ $transaction->item->unit ?? '' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $transaction->created_at->format('d M Y, H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-500 text-lg">No transactions recorded</p>
                    <p class="text-gray-400 text-sm mt-2">Transaction history will appear here</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
