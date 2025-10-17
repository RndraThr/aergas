@extends('layouts.app')

@section('title', 'Data Joint/Sambungan')

@section('content')
<div class="container mx-auto px-6 py-8" id="jointIndexPage">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Data Joint/Sambungan</h1>
            <p class="text-gray-600">Kelola data joint/sambungan jalur pipa</p>
        </div>
        <a href="{{ route('jalur.joint.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Input Joint Baru
        </a>
    </div>

    <!-- Filter & Search -->
    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Nomor joint, lokasi..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cluster</label>
                <select name="cluster_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
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
                <select name="line_number_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Semua Line</option>
                    @foreach($lineNumbers as $lineNumber)
                        <option value="{{ $lineNumber->id }}" {{ request('line_number_id') == $lineNumber->id ? 'selected' : '' }}>
                            {{ $lineNumber->line_number }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Fitting</label>
                <select name="fitting_type_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Semua Tipe</option>
                    @foreach($fittingTypes as $fittingType)
                        <option value="{{ $fittingType->id }}" {{ request('fitting_type_id') == $fittingType->id ? 'selected' : '' }}>
                            {{ $fittingType->nama_fitting }} ({{ $fittingType->code_fitting }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="acc_tracer" {{ request('status') === 'acc_tracer' ? 'selected' : '' }}>ACC Tracer</option>
                    <option value="acc_cgp" {{ request('status') === 'acc_cgp' ? 'selected' : '' }}>ACC CGP</option>
                    <option value="revisi_tracer" {{ request('status') === 'revisi_tracer' ? 'selected' : '' }}>Revisi Tracer</option>
                    <option value="revisi_cgp" {{ request('status') === 'revisi_cgp' ? 'selected' : '' }}>Revisi CGP</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                    Filter
                </button>
            </div>
        </form>
        <div class="flex justify-end mt-4">
            <a href="{{ route('jalur.joint.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-center">
                Reset
            </a>
        </div>
    </div>

    <!-- Joint Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nomor Joint & Line
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Fitting & Lokasi
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tanggal & Dimensi
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
                    @forelse($jointData as $joint)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 font-mono">{{ $joint->nomor_joint }}</div>
                                    <div class="text-sm text-gray-500">{{ $joint->formatted_joint_line }}</div>
                                    @if($joint->joint_line_optional && $joint->isEqualTee())
                                        <div class="text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded inline-block">3-Way Connection</div>
                                    @endif
                                    <div class="text-xs text-gray-400">{{ $joint->cluster->nama_cluster }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm text-gray-900">{{ $joint->fittingType->nama_fitting }}</div>
                                    <div class="text-sm text-gray-500">{{ $joint->lokasi_joint ?: '-' }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm text-gray-900">{{ $joint->tanggal_joint->format('d/m/Y') }}</div>
                                    <div class="text-sm text-gray-500">{{ $joint->tipe_penyambungan ?: 'Tipe belum ditentukan' }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs rounded-full
                                    @if($joint->status_laporan === 'acc_cgp') bg-green-100 text-green-800
                                    @elseif($joint->status_laporan === 'acc_tracer') bg-blue-100 text-blue-800
                                    @elseif(in_array($joint->status_laporan, ['revisi_tracer', 'revisi_cgp'])) bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $joint->status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="space-y-1">
                                    @if($joint->tracer_approved_at)
                                        <div class="flex items-center text-blue-600">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-xs">Tracer: {{ $joint->tracer_approved_at->format('d/m/Y') }}</span>
                                        </div>
                                    @endif
                                    @if($joint->cgp_approved_at)
                                        <div class="flex items-center text-green-600">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-xs">CGP: {{ $joint->cgp_approved_at->format('d/m/Y') }}</span>
                                        </div>
                                    @endif
                                    @if(!$joint->tracer_approved_at && !$joint->cgp_approved_at)
                                        <span class="text-xs text-gray-400">Belum ada approval</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <a href="{{ route('jalur.joint.show', $joint) }}"
                                       onclick="savePageState()"
                                       class="text-blue-600 hover:text-blue-900">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    @if(in_array($joint->status_laporan, ['draft', 'revisi_tracer', 'revisi_cgp']))
                                        <a href="{{ route('jalur.joint.edit', $joint) }}"
                                           onclick="savePageState()"
                                           class="text-indigo-600 hover:text-indigo-900">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                    @endif
                                    @if($joint->status_laporan === 'draft')
                                        <form method="POST" action="{{ route('jalur.joint.destroy', $joint) }}" class="inline"
                                              onsubmit="return confirm('Yakin ingin menghapus data joint ini?')">
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="text-lg font-medium mb-2">Belum ada data joint</p>
                                    <p>Mulai dengan input data joint pertama</p>
                                    <a href="{{ route('jalur.joint.create') }}" 
                                       class="mt-4 inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Input Joint Baru
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($jointData->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $jointData->appends(request()->query())->links('vendor.pagination.alpine-style') }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Pagination state persistence for joint/sambungan index
    function savePageState() {
        const currentUrl = new URL(window.location.href);
        const state = {
            page: currentUrl.searchParams.get('page') || 1,
            search: currentUrl.searchParams.get('search') || '',
            cluster_id: currentUrl.searchParams.get('cluster_id') || '',
            line_number_id: currentUrl.searchParams.get('line_number_id') || '',
            fitting_type_id: currentUrl.searchParams.get('fitting_type_id') || '',
            status: currentUrl.searchParams.get('status') || '',
            timestamp: Date.now()
        };
        sessionStorage.setItem('jalurJointPageState', JSON.stringify(state));
    }

    function restorePageState() {
        const savedState = sessionStorage.getItem('jalurJointPageState');
        if (savedState) {
            const state = JSON.parse(savedState);
            // Only restore if saved within last 30 minutes
            if (Date.now() - state.timestamp < 30 * 60 * 1000) {
                const currentUrl = new URL(window.location.href);

                // Check if we already have query parameters (user manually navigated)
                if (!currentUrl.searchParams.has('page') &&
                    !currentUrl.searchParams.has('search') &&
                    !currentUrl.searchParams.has('cluster_id') &&
                    !currentUrl.searchParams.has('line_number_id') &&
                    !currentUrl.searchParams.has('fitting_type_id') &&
                    !currentUrl.searchParams.has('status')) {

                    // Restore state only if no manual navigation
                    const params = new URLSearchParams();
                    if (state.page && state.page !== '1') params.set('page', state.page);
                    if (state.search) params.set('search', state.search);
                    if (state.cluster_id) params.set('cluster_id', state.cluster_id);
                    if (state.line_number_id) params.set('line_number_id', state.line_number_id);
                    if (state.fitting_type_id) params.set('fitting_type_id', state.fitting_type_id);
                    if (state.status) params.set('status', state.status);

                    if (params.toString()) {
                        window.location.href = currentUrl.pathname + '?' + params.toString();
                    }
                }

                // Clear the saved state after restoring
                sessionStorage.removeItem('jalurJointPageState');
            }
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        restorePageState();
    });
</script>
@endpush

@endsection