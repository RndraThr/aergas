@extends('layouts.app')

@section('title', 'CGP - Customer Review List')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">CGP Customer Review</h1>
            <p class="text-gray-600 mt-1">Review dan approve foto yang sudah disetujui Tracer</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('approvals.cgp.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                ← Dashboard CGP
            </a>
        </div>
    </div>

    <!-- Filter Cards -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6">
            <div class="flex flex-wrap items-center gap-4">
                <!-- Status Filter -->
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Filter Status:</label>
                    <select id="statusFilter" class="border border-gray-300 rounded-md px-3 py-1 text-sm">
                        <option value="">Semua Status</option>
                        <option value="sk_ready" {{ request('status') === 'sk_ready' ? 'selected' : '' }}>SK Ready for CGP</option>
                        <option value="sr_ready" {{ request('status') === 'sr_ready' ? 'selected' : '' }}>SR Ready for CGP</option>
                        <option value="gas_in_ready" {{ request('status') === 'gas_in_ready' ? 'selected' : '' }}>Gas In Ready for CGP</option>
                    </select>
                </div>

                <!-- Search -->
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Search:</label>
                    <input type="text" id="searchInput" placeholder="Reff ID, Nama, Alamat..." 
                           class="border border-gray-300 rounded-md px-3 py-1 text-sm w-64"
                           value="{{ request('search') }}">
                </div>

                <button id="applyFilter" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1 rounded-md text-sm">
                    Apply Filter
                </button>
            </div>
        </div>
    </div>

    <!-- Customer List -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Customer List</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer Info</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CGP Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($customers as $customer)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $customer->reff_id_pelanggan }}</div>
                                <div class="text-sm text-gray-600">{{ $customer->nama_pelanggan }}</div>
                                <div class="text-xs text-gray-500">{{ Str::limit($customer->alamat, 50) }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="space-y-1">
                                <!-- SK Status -->
                                @if($customer->cgp_status['sk_completed'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        ✅ SK Approved by CGP
                                    </span>
                                @elseif($customer->cgp_status['sk_ready'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        ⏳ SK Ready for CGP
                                    </span>
                                @endif

                                <!-- SR Status -->
                                @if($customer->cgp_status['sr_completed'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        ✅ SR Approved by CGP
                                    </span>
                                @elseif($customer->cgp_status['sr_ready'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        ⏳ SR Ready for CGP
                                    </span>
                                @endif

                                <!-- Gas In Status -->
                                @if($customer->cgp_status['gas_in_completed'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        ✅ Gas In Approved by CGP
                                    </span>
                                @elseif($customer->cgp_status['gas_in_ready'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        ⏳ Gas In Ready for CGP
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-1">
                                <!-- SK Progress -->
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs {{ $customer->cgp_status['sk_completed'] ? 'bg-green-100 text-green-600' : ($customer->cgp_status['sk_ready'] ? 'bg-yellow-100 text-yellow-600' : 'bg-gray-100 text-gray-400') }}">
                                    @if($customer->cgp_status['sk_completed'])
                                        ✓
                                    @else
                                        SK
                                    @endif
                                </div>
                                <div class="w-4 h-px {{ $customer->cgp_status['sk_completed'] ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                                
                                <!-- SR Progress -->
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs {{ $customer->cgp_status['sr_completed'] ? 'bg-green-100 text-green-600' : ($customer->cgp_status['sr_ready'] ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-400') }}">
                                    @if($customer->cgp_status['sr_completed'])
                                        ✓
                                    @else
                                        SR
                                    @endif
                                </div>
                                <div class="w-4 h-px {{ $customer->cgp_status['sr_completed'] ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                                
                                <!-- Gas In Progress -->
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs {{ $customer->cgp_status['gas_in_completed'] ? 'bg-green-100 text-green-600' : ($customer->cgp_status['gas_in_ready'] ? 'bg-purple-100 text-purple-600' : 'bg-gray-100 text-gray-400') }}">
                                    @if($customer->cgp_status['gas_in_completed'])
                                        ✓
                                    @else
                                        GI
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('approvals.cgp.customer-photos', $customer->reff_id_pelanggan) }}" 
                               class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                                📸 Review Photos
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada pelanggan</h3>
                                <p class="mt-1 text-sm text-gray-500">Belum ada pelanggan yang ready untuk CGP review</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($customers->hasPages())
        <div class="px-6 py-3 border-t border-gray-200">
            {{ $customers->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
// Filter functionality
document.getElementById('applyFilter').addEventListener('click', function() {
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchInput').value;
    
    const url = new URL(window.location);
    
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    
    if (search) {
        url.searchParams.set('search', search);
    } else {
        url.searchParams.delete('search');
    }
    
    window.location.href = url.toString();
});

// Enter key support for search
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('applyFilter').click();
    }
});
</script>
@endpush