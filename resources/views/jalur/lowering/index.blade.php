@extends('layouts.app')

@section('title', 'Data Lowering')

@section('content')
<div class="container mx-auto px-6 py-8" x-data="jalurLoweringPageData()" x-init="restorePageState()">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Data Lowering</h1>
            <p class="text-gray-600">Kelola data lowering jalur pipa harian</p>
        </div>
        <a href="{{ route('jalur.lowering.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Input Lowering Baru
        </a>
    </div>

    <!-- Filter & Search -->
    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Line number, nama jalan..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cluster</label>
                <select name="cluster_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Semua Cluster</option>
                    @foreach($clusters as $cluster)
                        <option value="{{ $cluster->id }}" {{ request('cluster_id') == $cluster->id ? 'selected' : '' }}>
                            {{ $cluster->nama_cluster }} ({{ $cluster->code_cluster }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Line Number</label>
                <select name="line_number_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Semua Line</option>
                    @foreach($lineNumbers as $lineNumber)
                        <option value="{{ $lineNumber->id }}" {{ request('line_number_id') == $lineNumber->id ? 'selected' : '' }}>
                            {{ $lineNumber->line_number }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="acc_tracer" {{ request('status') === 'acc_tracer' ? 'selected' : '' }}>ACC Tracer</option>
                    <option value="acc_cgp" {{ request('status') === 'acc_cgp' ? 'selected' : '' }}>ACC CGP</option>
                    <option value="revisi_tracer" {{ request('status') === 'revisi_tracer' ? 'selected' : '' }}>Revisi Tracer</option>
                    <option value="revisi_cgp" {{ request('status') === 'revisi_cgp' ? 'selected' : '' }}>Revisi CGP</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Material</label>
                <select name="tipe_material" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Semua Material</option>
                    <option value="Aspal" {{ request('tipe_material') === 'Aspal' ? 'selected' : '' }}>Aspal</option>
                    <option value="Tanah" {{ request('tipe_material') === 'Tanah' ? 'selected' : '' }}>Tanah</option>
                    <option value="Paving" {{ request('tipe_material') === 'Paving' ? 'selected' : '' }}>Paving</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                    Filter
                </button>
            </div>
            <div class="flex items-end">
                <a href="{{ route('jalur.lowering.index') }}" class="w-full bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Lowering Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Line Number & Lokasi
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tanggal & Tipe
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Progress (m)
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Approval
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($loweringData as $lowering)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $lowering->lineNumber->line_number }}</div>
                                    <div class="text-sm text-gray-500">{{ $lowering->nama_jalan }}</div>
                                    <div class="text-xs text-gray-400">{{ $lowering->lineNumber->cluster->nama_cluster }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm text-gray-900">{{ $lowering->tanggal_jalur->format('d/m/Y') }}</div>
                                    <div class="text-sm text-gray-500">{{ $lowering->tipe_bongkaran }}</div>
                                    @if($lowering->tipe_material)
                                        <div class="text-xs text-gray-400">Material: {{ $lowering->tipe_material }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <span class="font-semibold">{{ number_format($lowering->penggelaran, 1) }}m</span> lowering
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ number_format($lowering->bongkaran, 1) }}m 
                                    @if(in_array($lowering->tipe_bongkaran, ['Manual Boring', 'Manual Boring - PK']))
                                        manual boring
                                    @else
                                        bongkaran
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $lowering->kedalaman_lowering }}cm kedalaman
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs rounded-full
                                    @if($lowering->status_laporan === 'acc_cgp') bg-green-100 text-green-800
                                    @elseif($lowering->status_laporan === 'acc_tracer') bg-blue-100 text-blue-800
                                    @elseif(in_array($lowering->status_laporan, ['revisi_tracer', 'revisi_cgp'])) bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $lowering->status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="space-y-1">
                                    @if($lowering->tracer_approved_at)
                                        <div class="flex items-center text-blue-600">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-xs">Tracer: {{ $lowering->tracer_approved_at->format('d/m/Y') }}</span>
                                        </div>
                                    @endif
                                    @if($lowering->cgp_approved_at)
                                        <div class="flex items-center text-green-600">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-xs">CGP: {{ $lowering->cgp_approved_at->format('d/m/Y') }}</span>
                                        </div>
                                    @endif
                                    @if(!$lowering->tracer_approved_at && !$lowering->cgp_approved_at)
                                        <span class="text-xs text-gray-400">Belum ada approval</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <a href="{{ route('jalur.lowering.show', $lowering) }}"
                                       @click="savePageState()"
                                       class="text-blue-600 hover:text-blue-900">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    @if(in_array($lowering->status_laporan, ['draft', 'revisi_tracer', 'revisi_cgp']))
                                        <a href="{{ route('jalur.lowering.edit', $lowering) }}"
                                           @click="savePageState()"
                                           class="text-indigo-600 hover:text-indigo-900">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                    @endif
                                    @if($lowering->status_laporan === 'draft')
                                        <form method="POST" action="{{ route('jalur.lowering.destroy', $lowering) }}" class="inline"
                                              onsubmit="return confirm('Yakin ingin menghapus data lowering ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <svg class="mx-auto w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    <p class="text-lg font-medium mb-2">Belum ada data lowering</p>
                                    <p>Mulai dengan input data lowering pertama</p>
                                    <a href="{{ route('jalur.lowering.create') }}" 
                                       class="mt-4 inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Input Lowering Baru
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($loweringData->hasPages())
            <div class="px-6 py-4">
                {{ $loweringData->appends(request()->query())->links('vendor.pagination.alpine-style') }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function jalurLoweringPageData() {
    return {
        savePageState() {
            // Save current page and filter parameters to sessionStorage
            const currentUrl = new URL(window.location.href);
            const state = {
                page: currentUrl.searchParams.get('page') || '1',
                search: currentUrl.search,
                timestamp: Date.now()
            };
            sessionStorage.setItem('jalurLoweringPageState', JSON.stringify(state));
        },

        restorePageState() {
            // Restore page state from sessionStorage when returning from detail/edit page
            const savedState = sessionStorage.getItem('jalurLoweringPageState');

            if (savedState) {
                try {
                    const state = JSON.parse(savedState);

                    // Only restore if saved within last 30 minutes (1800000 ms)
                    if (Date.now() - state.timestamp < 1800000) {
                        const currentUrl = new URL(window.location.href);
                        const hasPageParam = currentUrl.searchParams.has('page');

                        // Only redirect if we don't already have pagination parameters
                        // This prevents infinite loops
                        if (!hasPageParam && state.search) {
                            const baseUrl = window.location.origin + window.location.pathname;
                            window.location.href = baseUrl + state.search;
                        }
                    }

                    // Clear the saved state after restoring to prevent unwanted redirects
                    sessionStorage.removeItem('jalurLoweringPageState');
                } catch (error) {
                    console.error('Failed to restore pagination state:', error);
                    sessionStorage.removeItem('jalurLoweringPageState');
                }
            }
        }
    }
}
</script>
@endpush

@endsection