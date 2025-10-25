@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-green-50 to-emerald-50" x-data="clustersData()" x-init="init()">
    <div class="container-fluid px-4 py-8">
        {{-- Header Section --}}
        <div class="mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">
                                    Pilih Cluster Jalur
                                </h1>
                                <p class="text-gray-600 mt-1">
                                    CGP Final Approval - Jalur Lowering Management
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Stats Cards Row --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-blue-600 mb-1">Total Cluster</div>
                                <div class="text-3xl font-bold text-blue-900" x-text="stats.total_clusters"></div>
                            </div>
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-4 border border-orange-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-orange-600 mb-1">Pending Review</div>
                                <div class="text-3xl font-bold text-orange-900" x-text="stats.pending_review"></div>
                            </div>
                            <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-4 border border-red-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-red-600 mb-1">Pending Photos</div>
                                <div class="text-3xl font-bold text-red-900" x-text="stats.pending_photos"></div>
                            </div>
                            <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-green-600 mb-1">Approved Photos</div>
                                <div class="text-3xl font-bold text-green-900" x-text="stats.approved_photos"></div>
                            </div>
                            <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters Section --}}
        <div class="mb-6">
            <div class="bg-white rounded-xl shadow-md p-5 border border-gray-100">
                <div class="flex flex-col md:flex-row gap-4">
                    {{-- Search --}}
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
                                @input.debounce.500ms="fetchClusters(true)"
                                placeholder="Cari nama cluster atau kode..."
                                class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            >
                        </div>
                    </div>

                    {{-- Filter --}}
                    <div class="w-full md:w-56">
                        <select
                            x-model="filters.filter"
                            @change="fetchClusters(true)"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        >
                            <option value="all">🔍 Semua Cluster</option>
                            <option value="pending">⚠️ Pending Review</option>
                            <option value="approved">✅ All Approved</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button
                            @click="resetFilters()"
                            x-show="filters.search || filters.filter !== 'all'"
                            class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all font-semibold">
                            Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Clusters Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <template x-for="cluster in clusters" :key="cluster.id">
                <div class="group bg-white rounded-2xl shadow-md hover:shadow-2xl transition-all duration-300 border border-gray-100 overflow-hidden transform hover:-translate-y-1">
                    <a :href="`{{ route('approvals.cgp.jalur.clusters') }}/${cluster.id}/lines`" class="block">
                        {{-- Card Header --}}
                        <div class="bg-gradient-to-r from-gray-50 to-blue-50 px-6 py-5 border-b border-gray-200">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-md">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-900 group-hover:text-blue-600 transition" x-text="cluster.nama_cluster"></h3>
                                            <span class="inline-block mt-1 px-3 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-700" x-text="cluster.code_cluster"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Status Badge --}}
                                <div class="ml-4">
                                    <span x-show="cluster.approval_stats.pending_photos > 0" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold rounded-full bg-orange-100 text-orange-800 border border-orange-200">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                        Pending
                                    </span>
                                    <span x-show="cluster.approval_stats.pending_photos === 0 && cluster.approval_stats.total_photos > 0" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold rounded-full bg-green-100 text-green-800 border border-green-200">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Approved
                                    </span>
                                    <span x-show="cluster.approval_stats.total_photos === 0" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold rounded-full bg-gray-100 text-gray-600 border border-gray-200">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                        </svg>
                                        No Evidence
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Card Body --}}
                        <div class="p-6">
                            {{-- Stats Grid --}}
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
                                    <div class="text-xs font-medium text-blue-600 mb-1">Total Lines</div>
                                    <div class="text-2xl font-bold text-blue-900" x-text="cluster.approval_stats.total_lines"></div>
                                </div>
                                <div class="bg-orange-50 rounded-lg p-4 border border-orange-100">
                                    <div class="text-xs font-medium text-orange-600 mb-1">Lines Pending</div>
                                    <div class="text-2xl font-bold text-orange-900" x-text="cluster.approval_stats.lines_with_pending"></div>
                                </div>
                                <div class="bg-red-50 rounded-lg p-4 border border-red-100">
                                    <div class="text-xs font-medium text-red-600 mb-1">Pending Photos</div>
                                    <div class="text-2xl font-bold text-red-900" x-text="cluster.approval_stats.pending_photos"></div>
                                </div>
                                <div class="bg-green-50 rounded-lg p-4 border border-green-100">
                                    <div class="text-xs font-medium text-green-600 mb-1">Approved Photos</div>
                                    <div class="text-2xl font-bold text-green-900" x-text="cluster.approval_stats.approved_photos"></div>
                                </div>
                            </div>

                            <div x-show="cluster.approval_stats.rejected_photos > 0" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-center gap-2 text-sm text-red-700">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="font-semibold" x-text="cluster.approval_stats.rejected_photos + ' foto ditolak'"></span>
                                    <span class="text-red-600">(perlu re-upload)</span>
                                </div>
                            </div>
                        </div>

                        {{-- Card Footer --}}
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 font-medium">Klik untuk melihat lines</span>
                                <svg class="w-5 h-5 text-blue-600 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </div>
                        </div>
                    </a>
                </div>
            </template>

            {{-- Empty State --}}
            <div x-show="clusters.length === 0" class="col-span-2">
                <div class="bg-white rounded-2xl shadow-md p-16 text-center border border-gray-100">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-700 mb-3">Tidak Ada Cluster</h3>
                    <p class="text-gray-500 text-lg">
                        <span x-show="filters.search">Tidak ditemukan cluster dengan kata kunci "<span x-text="filters.search"></span>"</span>
                        <span x-show="!filters.search && filters.filter !== 'all'">Tidak ada cluster dengan filter yang dipilih</span>
                        <span x-show="!filters.search && filters.filter === 'all'">Belum ada cluster yang terdaftar dalam sistem</span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        <div x-show="pagination.total > 0" class="mt-8 bg-white px-4 py-3 border border-gray-200 rounded-xl sm:px-6">
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

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .group {
        animation: fadeIn 0.5s ease-out;
    }
</style>

@push('scripts')
<script>
function clustersData() {
    return {
        clusters: @json($clusters->items() ?? []),
        pagination: {
            current_page: @json($clusters->currentPage() ?? 1),
            last_page: @json($clusters->lastPage() ?? 1),
            per_page: @json($clusters->perPage() ?? 12),
            total: @json($clusters->total() ?? 0),
            from: @json($clusters->firstItem() ?? 0),
            to: @json($clusters->lastItem() ?? 0)
        },
        stats: {
            total_clusters: @json($clusters->total() ?? 0),
            pending_review: @json($clusters->filter(fn($c) => $c->approval_stats['pending_photos'] > 0)->count() ?? 0),
            pending_photos: @json($clusters->sum(fn($c) => $c->approval_stats['pending_photos']) ?? 0),
            approved_photos: @json($clusters->sum(fn($c) => $c->approval_stats['approved_photos']) ?? 0)
        },
        filters: {
            search: '{{ $search ?? '' }}',
            filter: '{{ $filter ?? 'all' }}'
        },
        loading: false,

        init() {
            // Initialization complete
        },

        async fetchClusters(resetPage = false) {
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

                const response = await fetch(`{{ route('approvals.cgp.jalur.clusters') }}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.clusters = data.data.data || [];
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
                        this.fetchClusters();
                        return;
                    }
                }
            } catch (error) {
                console.error('Error fetching clusters:', error);
            } finally {
                this.loading = false;
            }
        },

        resetFilters() {
            this.filters = {
                search: '',
                filter: 'all'
            };
            this.fetchClusters(true);
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
            this.fetchClusters();
        },

        previousPage() {
            if (this.pagination.current_page > 1) {
                this.pagination.current_page--;
                this.fetchClusters();
            }
        },

        nextPage() {
            if (this.pagination.current_page < this.pagination.last_page) {
                this.pagination.current_page++;
                this.fetchClusters();
            }
        }
    }
}
</script>
@endpush
@endsection
