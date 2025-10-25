@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50" x-data="linesData()" x-init="init()">
    <div class="container-fluid px-4 py-8">
        {{-- Breadcrumb --}}
        <div class="mb-6">
            <a href="{{ route('approvals.tracer.jalur.clusters') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-50 transition-all shadow-sm hover:shadow-md group">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span class="font-semibold">Kembali ke Clusters</span>
            </a>
        </div>

        {{-- Header Section --}}
        <div class="mb-6">
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">
                                {{ $cluster->nama_cluster }}
                            </h1>
                            <p class="text-gray-600 mt-1">
                                Pilih line untuk review • Code: <span class="font-semibold">{{ $cluster->code_cluster }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Stats Cards Row --}}
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-3 border border-blue-200">
                        <div class="text-xs font-medium text-blue-600 mb-1">Total Lines</div>
                        <div class="text-xl font-bold text-blue-900" x-text="stats.total_lines"></div>
                    </div>
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-3 border border-orange-200">
                        <div class="text-xs font-medium text-orange-600 mb-1">Pending</div>
                        <div class="text-xl font-bold text-orange-900" x-text="stats.pending_lines"></div>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-3 border border-green-200">
                        <div class="text-xs font-medium text-green-600 mb-1">Approved</div>
                        <div class="text-xl font-bold text-green-900" x-text="stats.approved_lines"></div>
                    </div>
                    <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-3 border border-red-200">
                        <div class="text-xs font-medium text-red-600 mb-1">Rejected</div>
                        <div class="text-xl font-bold text-red-900" x-text="stats.rejected_lines"></div>
                    </div>
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-3 border border-gray-200">
                        <div class="text-xs font-medium text-gray-600 mb-1">No Evidence</div>
                        <div class="text-xl font-bold text-gray-700" x-text="stats.no_evidence_lines"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="mb-6">
            <div class="bg-white rounded-xl shadow-md p-4 border border-gray-100">
                <div class="flex flex-col md:flex-row gap-3">
                    <div class="flex-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                x-model="filters.search"
                                @input.debounce.500ms="fetchLines(true)"
                                placeholder="Cari line number..."
                                class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            >
                        </div>
                    </div>

                    <div class="w-full md:w-48">
                        <select
                            x-model="filters.filter"
                            @change="fetchLines(true)"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="all">Semua Line</option>
                            <option value="pending">Pending Review</option>
                            <option value="approved">All Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="no_evidence">No Evidence</option>
                        </select>
                    </div>

                    <button
                        @click="resetFilters()"
                        x-show="filters.search || filters.filter !== 'all'"
                        class="px-5 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all font-semibold">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        {{-- Lines Grid - 2 Columns Compact --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <template x-for="line in lines" :key="line.id">
                <div class="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden flex flex-col">
                    <a :href="`/approvals/tracer/jalur/lines/${line.id}/evidence`" class="flex flex-col flex-1">
                        {{-- Card Header - Compact --}}
                        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-5 py-3 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center shadow-md">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition" x-text="line.line_number"></h3>
                                        <span class="text-xs font-medium text-gray-600">
                                            <span x-text="line.status_label"></span> (<span x-text="Math.round(line.progress_percentage || 0)"></span>%)
                                        </span>
                                    </div>
                                </div>

                                {{-- Status Badge --}}
                                <span x-show="line.approval_stats.status === 'approved'" class="px-3 py-1 text-xs font-bold rounded-full bg-green-100 text-green-800 border border-green-300">
                                    ✓ Approved
                                </span>
                                <span x-show="line.approval_stats.status === 'rejected'" class="px-3 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800 border border-red-300">
                                    ✗ Rejected
                                </span>
                                <span x-show="line.approval_stats.status === 'pending'" class="px-3 py-1 text-xs font-bold rounded-full bg-orange-100 text-orange-800 border border-orange-300">
                                    ⏳ Pending
                                </span>
                                <span x-show="!line.approval_stats.status || line.approval_stats.status === 'no_data'" class="px-3 py-1 text-xs font-bold rounded-full bg-gray-100 text-gray-600">
                                    No Data
                                </span>
                            </div>
                        </div>

                        {{-- Card Body - Compact --}}
                        <div class="p-4 flex-1 flex flex-col">
                            {{-- Line Detail Information --}}
                            <div class="mb-3 p-3 bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg border border-gray-200">
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span class="text-gray-500">Diameter:</span>
                                        <span class="font-semibold text-gray-900">Ø<span x-text="line.diameter"></span>mm</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Status:</span>
                                        <span class="font-semibold text-gray-900" x-text="line.status_line"></span>
                                    </div>
                                    <div class="col-span-2" x-show="line.nama_jalan">
                                        <span class="text-gray-500">Jalan:</span>
                                        <span class="font-semibold text-gray-900" x-text="line.nama_jalan || '-'"></span>
                                    </div>
                                </div>
                            </div>

                            {{-- Metrics - Compact 3 columns --}}
                            <div class="grid grid-cols-3 gap-2 mb-3">
                                <div class="text-center bg-blue-50 rounded-lg p-2 border border-blue-100">
                                    <div class="text-xs text-blue-600 font-medium">MC-0</div>
                                    <div class="text-sm font-bold text-blue-900" x-text="Number(line.estimasi_panjang || 0).toFixed(1) + 'm'"></div>
                                </div>
                                <div class="text-center bg-indigo-50 rounded-lg p-2 border border-indigo-100">
                                    <div class="text-xs text-indigo-600 font-medium">Actual</div>
                                    <div class="text-sm font-bold text-indigo-900" x-text="Number(line.total_penggelaran || 0).toFixed(1) + 'm'"></div>
                                </div>
                                <div class="text-center bg-green-50 rounded-lg p-2 border border-green-100">
                                    <div class="text-xs text-green-600 font-medium">MC-100</div>
                                    <div class="text-sm font-bold"
                                         :class="line.actual_mc100 ? 'text-green-900' : 'text-gray-400'"
                                         x-text="line.actual_mc100 ? Number(line.actual_mc100).toFixed(1) + 'm' : '-'"></div>
                                </div>
                            </div>

                            {{-- Work Dates with Penggelaran - Flex Grow untuk mengisi space --}}
                            <div class="mb-3 flex-1 flex flex-col">
                                <div class="text-xs text-gray-600 mb-2 font-semibold">
                                    <span x-text="line.approval_stats.work_dates_count || 0"></span> Tanggal Pekerjaan
                                </div>
                                <div class="flex-1 max-h-32 overflow-y-auto space-y-1" x-show="line.approval_stats.work_dates_detail && line.approval_stats.work_dates_detail.length > 0">
                                    <template x-for="dateDetail in (line.approval_stats.work_dates_detail || [])" :key="dateDetail.date">
                                        <div class="flex items-center justify-between px-2 py-1 bg-indigo-50 rounded border border-indigo-200">
                                            <span class="text-xs font-medium text-indigo-800" x-text="new Date(dateDetail.date).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'})"></span>
                                            <span class="text-xs font-bold text-indigo-900" x-text="Number(dateDetail.penggelaran || 0).toFixed(1) + 'm'"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Approval Stats - Always at bottom --}}
                            <div class="mt-auto">
                                <template x-if="line.approval_stats.total_photos > 0">
                                    <div>
                                        <div class="flex items-center justify-between text-xs mb-2">
                                            <span class="text-gray-600">
                                                Photos: <span class="font-semibold text-gray-900"><span x-text="line.approval_stats.approved_photos"></span>/<span x-text="line.approval_stats.total_photos"></span></span>
                                            </span>
                                            <span class="font-bold text-gray-900" x-text="Math.round(line.approval_stats.percentage || 0) + '%'"></span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                            <div class="h-2 rounded-full transition-all"
                                                 :class="line.approval_stats.percentage === 100 ? 'bg-green-500' : 'bg-indigo-500'"
                                                 :style="`width: ${line.approval_stats.percentage || 0}%`">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!line.approval_stats.total_photos || line.approval_stats.total_photos === 0">
                                    <div class="text-center text-xs text-gray-400 py-2">
                                        Belum ada evidence
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Card Footer - Always at bottom --}}
                        <div class="px-4 py-2 bg-gray-50 border-t border-gray-200 flex items-center justify-between mt-auto">
                            <span class="text-xs text-gray-600">Klik untuk review</span>
                            <svg class="w-4 h-4 text-indigo-600 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </div>
                    </a>
                </div>
            </template>

            {{-- Empty State --}}
            <div x-show="lines.length === 0" class="col-span-2">
                <div class="bg-white rounded-xl shadow-md p-12 text-center border border-gray-100">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-700 mb-2">Tidak Ada Line</h3>
                    <p class="text-gray-500">
                        <span x-show="filters.search">Tidak ditemukan line dengan kata kunci "<span x-text="filters.search"></span>"</span>
                        <span x-show="!filters.search && filters.filter !== 'all'">Tidak ada line dengan filter yang dipilih</span>
                        <span x-show="!filters.search && filters.filter === 'all'">Belum ada line dalam cluster ini</span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        <div x-show="pagination.total > 0" class="mt-6 bg-white px-4 py-3 border border-gray-200 rounded-xl sm:px-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <span class="text-sm text-gray-700">
                        Showing
                        <span class="font-medium" x-text="pagination.from || 0"></span>
                        to
                        <span class="font-medium" x-text="pagination.to || 0"></span>
                        of
                        <span class="font-medium" x-text="pagination.total || 0"></span>
                        results
                    </span>
                </div>

                <div class="flex items-center space-x-2">
                    <button @click="previousPage()"
                            :disabled="pagination.current_page <= 1"
                            :class="pagination.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 transition-colors">
                        Previous
                    </button>

                    <template x-for="page in paginationPages" :key="page">
                        <button @click="goToPage(page)"
                                :class="page === pagination.current_page ? 'bg-orange-600 text-white' : 'text-gray-700 hover:bg-orange-50'"
                                class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium transition-colors">
                            <span x-text="page"></span>
                        </button>
                    </template>

                    <button @click="nextPage()"
                            :disabled="pagination.current_page >= pagination.last_page"
                            :class="pagination.current_page >= pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 transition-colors">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function linesData() {
    return {
        lines: @json($lines->items() ?? []),
        pagination: {
            current_page: @json($lines->currentPage() ?? 1),
            last_page: @json($lines->lastPage() ?? 1),
            per_page: @json($lines->perPage() ?? 20),
            total: @json($lines->total() ?? 0),
            from: @json($lines->firstItem() ?? 0),
            to: @json($lines->lastItem() ?? 0)
        },
        stats: {
            total_lines: @json($lines->total() ?? 0),
            pending_lines: @json($lines->filter(fn($l) => $l->approval_stats['status'] === 'pending')->count() ?? 0),
            approved_lines: @json($lines->filter(fn($l) => $l->approval_stats['status'] === 'approved')->count() ?? 0),
            rejected_lines: @json($lines->filter(fn($l) => $l->approval_stats['status'] === 'rejected')->count() ?? 0),
            no_evidence_lines: @json($lines->filter(fn($l) => $l->approval_stats['total_photos'] === 0)->count() ?? 0)
        },
        filters: {
            search: '{{ $search ?? '' }}',
            filter: '{{ $filter ?? 'all' }}'
        },
        clusterId: {{ $cluster->id }},
        loading: false,

        init() {
            // Initialization complete
        },

        async fetchLines(resetPage = false) {
            this.loading = true;

            if (resetPage) {
                this.pagination.current_page = 1;
            }

            try {
                const params = new URLSearchParams({
                    search: this.filters.search || '',
                    filter: this.filters.filter || 'all',
                    per_page: this.pagination.per_page,
                    page: this.pagination.current_page,
                    ajax: 1
                });

                const response = await fetch(`/approvals/tracer/jalur/clusters/${this.clusterId}/lines?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.lines = data.data.data || [];
                    this.pagination = {
                        current_page: data.data.current_page,
                        last_page: data.data.last_page,
                        per_page: data.data.per_page,
                        total: data.data.total,
                        from: data.data.from,
                        to: data.data.to
                    };
                    this.stats = data.stats || this.stats;

                    if (this.pagination.current_page > this.pagination.last_page && this.pagination.last_page > 0) {
                        this.pagination.current_page = this.pagination.last_page;
                        this.fetchLines();
                        return;
                    }
                }
            } catch (error) {
                console.error('Error fetching lines:', error);
            } finally {
                this.loading = false;
            }
        },

        resetFilters() {
            this.filters = {
                search: '',
                filter: 'all'
            };
            this.fetchLines(true);
        },

        get paginationPages() {
            const pages = [];
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;

            let start = Math.max(1, current - 2);
            let end = Math.min(last, current + 2);

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            return pages;
        },

        goToPage(page) {
            this.pagination.current_page = page;
            this.fetchLines();
        },

        previousPage() {
            if (this.pagination.current_page > 1) {
                this.pagination.current_page--;
                this.fetchLines();
            }
        },

        nextPage() {
            if (this.pagination.current_page < this.pagination.last_page) {
                this.pagination.current_page++;
                this.fetchLines();
            }
        }
    }
}
</script>
@endpush
@endsection
