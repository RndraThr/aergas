@extends('layouts.app')

@section('title', 'CGP - Customer Review List')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="cgpCustomersData()" x-init="initPaginationState()">
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
                    <select x-model="filters.status" @change="fetchCustomers(true)"
                            class="border border-gray-300 rounded-md px-3 py-1 text-sm">
                        <option value="">Semua Status</option>
                        <option value="sk_ready">SK Ready for CGP</option>
                        <option value="sr_ready">SR Ready for CGP</option>
                        <option value="gas_in_ready">Gas In Ready for CGP</option>
                    </select>
                </div>

                <!-- Search -->
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Search:</label>
                    <input type="text" x-model="filters.search" @input.debounce.500ms="fetchCustomers(true)"
                           placeholder="Reff ID, Nama, Alamat..."
                           class="border border-gray-300 rounded-md px-3 py-1 text-sm w-64">
                </div>

                <button @click="resetFilters()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-1 rounded-md text-sm">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Customer List -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Customer List</h2>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="p-8 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p class="text-gray-500 mt-2">Loading customers...</p>
        </div>

        <div x-show="!loading" class="overflow-x-auto">
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
                    <template x-for="customer in customers" :key="customer.reff_id_pelanggan">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900" x-text="customer.reff_id_pelanggan"></div>
                                    <div class="text-sm text-gray-600" x-text="customer.nama_pelanggan"></div>
                                    <div class="text-xs text-gray-500" x-text="customer.alamat?.substring(0, 50) + (customer.alamat?.length > 50 ? '...' : '')"></div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <!-- SK Status -->
                                    <template x-if="customer.cgp_status?.sk_completed">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            ✅ SK Approved by CGP
                                        </span>
                                    </template>
                                    <template x-if="customer.cgp_status?.sk_ready && !customer.cgp_status?.sk_completed">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            ⏳ SK Ready for CGP
                                        </span>
                                    </template>

                                    <!-- SR Status -->
                                    <template x-if="customer.cgp_status?.sr_completed">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            ✅ SR Approved by CGP
                                        </span>
                                    </template>
                                    <template x-if="customer.cgp_status?.sr_ready && !customer.cgp_status?.sr_completed">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            ⏳ SR Ready for CGP
                                        </span>
                                    </template>

                                    <!-- Gas In Status -->
                                    <template x-if="customer.cgp_status?.gas_in_completed">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            ✅ Gas In Approved by CGP
                                        </span>
                                    </template>
                                    <template x-if="customer.cgp_status?.gas_in_ready && !customer.cgp_status?.gas_in_completed">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            ⏳ Gas In Ready for CGP
                                        </span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-1">
                                    <!-- SK Progress -->
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs"
                                         :class="customer.cgp_status?.sk_completed ? 'bg-green-100 text-green-600' : (customer.cgp_status?.sk_ready ? 'bg-yellow-100 text-yellow-600' : 'bg-gray-100 text-gray-400')">
                                        <span x-text="customer.cgp_status?.sk_completed ? '✓' : 'SK'"></span>
                                    </div>
                                    <div class="w-4 h-px"
                                         :class="customer.cgp_status?.sk_completed ? 'bg-green-400' : 'bg-gray-200'"></div>

                                    <!-- SR Progress -->
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs"
                                         :class="customer.cgp_status?.sr_completed ? 'bg-green-100 text-green-600' : (customer.cgp_status?.sr_ready ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-400')">
                                        <span x-text="customer.cgp_status?.sr_completed ? '✓' : 'SR'"></span>
                                    </div>
                                    <div class="w-4 h-px"
                                         :class="customer.cgp_status?.sr_completed ? 'bg-green-400' : 'bg-gray-200'"></div>

                                    <!-- Gas In Progress -->
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs"
                                         :class="customer.cgp_status?.gas_in_completed ? 'bg-green-100 text-green-600' : (customer.cgp_status?.gas_in_ready ? 'bg-purple-100 text-purple-600' : 'bg-gray-100 text-gray-400')">
                                        <span x-text="customer.cgp_status?.gas_in_completed ? '✓' : 'GI'"></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <a :href="`/approvals/cgp/customers/${customer.reff_id_pelanggan}/photos`"
                                   @click="savePageState()"
                                   class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                                    📸 Review Photos
                                </a>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State -->
                    <tr x-show="customers.length === 0">
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
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <x-pagination />
    </div>
</div>

@push('scripts')
<script>
function cgpCustomersData() {
    return {
        customers: @json($customers->items() ?? []),
        pagination: {
            current_page: @json($customers->currentPage() ?? 1),
            last_page: @json($customers->lastPage() ?? 1),
            per_page: @json($customers->perPage() ?? 15),
            total: @json($customers->total() ?? 0),
            from: @json($customers->firstItem() ?? 0),
            to: @json($customers->lastItem() ?? 0)
        },
        filters: {
            search: '{{ request("search") }}',
            status: '{{ request("status") }}'
        },
        loading: false,

        async fetchCustomers(resetPage = false) {
            this.loading = true;

            // Auto-reset: jika current page > 1 dan resetPage = true, reset ke page 1
            if (resetPage && this.pagination.current_page > 1) {
                this.pagination.current_page = 1;
            }

            try {
                const params = new URLSearchParams({
                    search: this.filters.search,
                    status: this.filters.status,
                    page: this.pagination.current_page,
                    ajax: 1
                });

                const response = await fetch(`{{ route('approvals.cgp.customers') }}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.customers = data.data.data || [];
                    const newPagination = {
                        current_page: data.data.current_page,
                        last_page: data.data.last_page,
                        per_page: data.data.per_page,
                        total: data.data.total,
                        from: data.data.from,
                        to: data.data.to
                    };

                    // Smart pagination: jika current page > last page, fetch ulang dari page terakhir
                    if (newPagination.current_page > newPagination.last_page && newPagination.last_page > 0) {
                        this.pagination.current_page = newPagination.last_page;
                        this.fetchCustomers(false); // fetch ulang tanpa reset
                        return;
                    }

                    this.pagination = newPagination;
                }
            } catch (error) {
                console.error('Error fetching customers:', error);
            } finally {
                this.loading = false;
            }
        },

        resetFilters() {
            this.filters = {
                search: '',
                status: ''
            };
            this.fetchCustomers(true);
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
            if (page >= 1 && page <= this.pagination.last_page) {
                this.pagination.current_page = page;
                this.fetchCustomers();
            }
        },

        previousPage() {
            if (this.pagination.current_page > 1) {
                this.pagination.current_page--;
                this.fetchCustomers();
            }
        },

        nextPage() {
            if (this.pagination.current_page < this.pagination.last_page) {
                this.pagination.current_page++;
                this.fetchCustomers();
            }
        },

        savePageState() {
            const state = {
                page: this.pagination.current_page,
                filters: this.filters,
                timestamp: Date.now()
            };
            sessionStorage.setItem('cgpCustomersPageState', JSON.stringify(state));
        },

        restorePageState() {
            const savedState = sessionStorage.getItem('cgpCustomersPageState');
            if (savedState) {
                const state = JSON.parse(savedState);
                if (Date.now() - state.timestamp < 30 * 60 * 1000) {
                    this.pagination.current_page = state.page || 1;
                    this.filters = state.filters || this.filters;
                    this.fetchCustomers();
                    sessionStorage.removeItem('cgpCustomersPageState');
                }
            }
        },

        initPaginationState() {
            this.restorePageState();
        }
    }
}
</script>
@endpush
@endsection
