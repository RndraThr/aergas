@extends('layouts.app')

@section('title', 'Tracer - Customer List')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Customer Review List</h1>
            <p class="text-gray-600 mt-1">Pilih pelanggan untuk review foto</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('approvals.tracer.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                ← Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('approvals.tracer.customers') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" 
                               placeholder="Reff ID, Nama, atau Alamat"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Status</option>
                            <option value="sk_pending" {{ request('status') === 'sk_pending' ? 'selected' : '' }}>SK Pending</option>
                            <option value="sr_pending" {{ request('status') === 'sr_pending' ? 'selected' : '' }}>SR Pending</option>
                            <option value="gas_in_pending" {{ request('status') === 'gas_in_pending' ? 'selected' : '' }}>Gas In Pending</option>
                        </select>
                    </div>
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                            🔍 Filter
                        </button>
                        <a href="{{ route('approvals.tracer.customers') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Customer List -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">Daftar Pelanggan</h2>
                <span class="text-sm text-gray-500">{{ $customers->total() }} pelanggan ditemukan</span>
            </div>
        </div>

        @if($customers->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($customers as $customer)
                <div class="p-6 hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-4">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">
                                        {{ $customer->reff_id_pelanggan }}
                                    </h3>
                                    <p class="text-sm text-gray-600">{{ $customer->nama_pelanggan }}</p>
                                    <p class="text-sm text-gray-500">{{ $customer->alamat }}</p>
                                </div>
                            </div>

                            <!-- Sequential Progress -->
                            <div class="mt-4">
                                <div class="flex items-center space-x-6">
                                    <!-- SK Status -->
                                    <div class="flex items-center">
                                        @if($customer->sequential_status['sk_completed'])
                                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-2">
                                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center mr-2">
                                                <span class="text-yellow-600 font-bold text-xs">SK</span>
                                            </div>
                                        @endif
                                        <span class="text-sm font-medium {{ $customer->sequential_status['sk_completed'] ? 'text-green-600' : 'text-yellow-600' }}">
                                            SK {{ $customer->sequential_status['sk_completed'] ? 'Completed' : 'Pending' }}
                                        </span>
                                    </div>

                                    <!-- Arrow -->
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>

                                    <!-- SR Status -->
                                    <div class="flex items-center">
                                        @if($customer->sequential_status['sr_completed'])
                                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-2">
                                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        @elseif($customer->sequential_status['sr_available'])
                                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-2">
                                                <span class="text-blue-600 font-bold text-xs">SR</span>
                                            </div>
                                        @else
                                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mr-2 relative">
                                                <span class="text-gray-400 font-bold text-xs">SR</span>
                                                <i class="fas fa-lock absolute -top-1 -right-1 text-xs text-red-500 bg-white rounded-full p-0.5"></i>
                                            </div>
                                        @endif
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium {{ $customer->sequential_status['sr_completed'] ? 'text-green-600' : ($customer->sequential_status['sr_available'] ? 'text-blue-600' : 'text-gray-400') }}">
                                                SR 
                                                @if($customer->sequential_status['sr_completed'])
                                                    Completed
                                                @elseif($customer->sequential_status['sr_available'])
                                                    Available
                                                @else
                                                    Locked
                                                @endif
                                            </span>
                                            @if(isset($customer->sequential_status['modules']['sr']))
                                                <span class="text-xs text-gray-500">
                                                    {{ $customer->sequential_status['modules']['sr']['status_text'] }}
                                                    @if($customer->sequential_status['modules']['sr']['pending_count'] > 0)
                                                        ({{ $customer->sequential_status['modules']['sr']['pending_count'] }} pending)
                                                    @endif
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Arrow -->
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>

                                    <!-- Gas In Status -->
                                    <div class="flex items-center">
                                        @if($customer->sequential_status['gas_in_completed'])
                                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-2">
                                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        @elseif($customer->sequential_status['gas_in_available'])
                                            <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center mr-2">
                                                <span class="text-orange-600 font-bold text-xs">GI</span>
                                            </div>
                                        @else
                                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mr-2 relative">
                                                <span class="text-gray-400 font-bold text-xs">GI</span>
                                                <i class="fas fa-lock absolute -top-1 -right-1 text-xs text-red-500 bg-white rounded-full p-0.5"></i>
                                            </div>
                                        @endif
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium {{ $customer->sequential_status['gas_in_completed'] ? 'text-green-600' : ($customer->sequential_status['gas_in_available'] ? 'text-orange-600' : 'text-gray-400') }}">
                                                Gas In 
                                                @if($customer->sequential_status['gas_in_completed'])
                                                    Completed
                                                @elseif($customer->sequential_status['gas_in_available'])
                                                    Available
                                                @else
                                                    Locked
                                                @endif
                                            </span>
                                            @if(isset($customer->sequential_status['modules']['gas_in']))
                                                <span class="text-xs text-gray-500">
                                                    {{ $customer->sequential_status['modules']['gas_in']['status_text'] }}
                                                    @if($customer->sequential_status['modules']['gas_in']['pending_count'] > 0)
                                                        ({{ $customer->sequential_status['modules']['gas_in']['pending_count'] }} pending)
                                                    @endif
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Current Step Indicator -->
                                @if($customer->sequential_status['current_step'] !== 'completed')
                                    <div class="mt-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            📍 Current Step: {{ strtoupper($customer->sequential_status['current_step']) }}
                                        </span>
                                    </div>
                                @else
                                    <div class="mt-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            ✅ All Steps Completed
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div>
                            <a href="{{ route('approvals.tracer.photos', $customer->reff_id_pelanggan) }}" 
                               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium inline-flex items-center">
                                📸 Review Photos
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $customers->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada pelanggan ditemukan</h3>
                <p class="mt-1 text-sm text-gray-500">Coba ubah filter atau kriteria pencarian Anda</p>
            </div>
        @endif
    </div>
</div>
@endsection