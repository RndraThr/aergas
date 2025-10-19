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
                                    <template x-if="customer.cgp_status?.sk_in_progress && !customer.cgp_status?.sk_ready && !customer.cgp_status?.sk_completed">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            🔄 SK In Progress
                                        </span>
                                    </template>
                                    <template x-if="customer.cgp_status?.sk_waiting_tracer && !customer.cgp_status?.sk_ready && !customer.cgp_status?.sk_in_progress && !customer.cgp_status?.sk_completed">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            ⏱️ SK Waiting Tracer
                                        </span>
                                    </template>
                                    <!-- SK Rejection Info -->
                                    <template x-if="customer.cgp_status?.sk_rejections?.has_rejections">
                                        <div class="mt-1.5 space-y-1">
                                            <template x-for="rejection in customer.cgp_status.sk_rejections.all" :key="rejection.field_name">
                                                <div class="bg-red-50 border border-red-200 rounded px-2 py-1.5 text-xs">
                                                    <div class="flex items-start gap-1.5">
                                                        <svg class="w-3 h-3 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                        </svg>
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-start justify-between gap-2 mb-0.5">
                                                                <div class="font-medium text-red-800" x-text="rejection.label"></div>
                                                                <div class="text-red-500 text-[10px] flex items-center gap-1 flex-shrink-0">
                                                                    <span x-text="rejection.user_name"></span>
                                                                    <span>•</span>
                                                                    <span x-text="rejection.rejected_at"></span>
                                                                </div>
                                                            </div>
                                                            <div class="text-red-600" x-show="rejection.notes" x-text="rejection.notes"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- SR Status -->
                                    <template x-if="customer.cgp_status?.sr_completed">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            ✅ SR Approved by CGP
                                        </span>
                                    </template>
                                    <template x-if="customer.cgp_status?.sr_ready && !customer.cgp_status?.sr_completed">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            ⏳ SR Ready for CGP
                                        </span>
                                    </template>
                                    <template x-if="customer.cgp_status?.sr_in_progress && !customer.cgp_status?.sr_ready && !customer.cgp_status?.sr_completed">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            🔄 SR In Progress
                                        </span>
                                    </template>
                                    <template x-if="customer.cgp_status?.sr_waiting_tracer && !customer.cgp_status?.sr_ready && !customer.cgp_status?.sr_in_progress && !customer.cgp_status?.sr_completed">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            ⏱️ SR Waiting Tracer
                                        </span>
                                    </template>
                                    <!-- SR Rejection Info -->
                                    <template x-if="customer.cgp_status?.sr_rejections?.has_rejections">
                                        <div class="mt-1.5 space-y-1">
                                            <template x-for="rejection in customer.cgp_status.sr_rejections.all" :key="rejection.field_name">
                                                <div class="bg-red-50 border border-red-200 rounded px-2 py-1.5 text-xs">
                                                    <div class="flex items-start gap-1.5">
                                                        <svg class="w-3 h-3 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                        </svg>
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-start justify-between gap-2 mb-0.5">
                                                                <div class="font-medium text-red-800" x-text="rejection.label"></div>
                                                                <div class="text-red-500 text-[10px] flex items-center gap-1 flex-shrink-0">
                                                                    <span x-text="rejection.user_name"></span>
                                                                    <span>•</span>
                                                                    <span x-text="rejection.rejected_at"></span>
                                                                </div>
                                                            </div>
                                                            <div class="text-red-600" x-show="rejection.notes" x-text="rejection.notes"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Gas In Status -->
                                    <template x-if="customer.cgp_status?.gas_in_completed">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            ✅ Gas In Approved by CGP
                                        </span>
                                    </template>
                                    <template x-if="customer.cgp_status?.gas_in_ready && !customer.cgp_status?.gas_in_completed">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            ⏳ Gas In Ready for CGP
                                        </span>
                                    </template>
                                    <template x-if="customer.cgp_status?.gas_in_in_progress && !customer.cgp_status?.gas_in_ready && !customer.cgp_status?.gas_in_completed">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            🔄 Gas In In Progress
                                        </span>
                                    </template>
                                    <template x-if="customer.cgp_status?.gas_in_waiting_tracer && !customer.cgp_status?.gas_in_ready && !customer.cgp_status?.gas_in_in_progress && !customer.cgp_status?.gas_in_completed">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            ⏱️ Gas In Waiting Tracer
                                        </span>
                                    </template>
                                    <!-- Gas In Rejection Info -->
                                    <template x-if="customer.cgp_status?.gas_in_rejections?.has_rejections">
                                        <div class="mt-1.5 space-y-1">
                                            <template x-for="rejection in customer.cgp_status.gas_in_rejections.all" :key="rejection.field_name">
                                                <div class="bg-red-50 border border-red-200 rounded px-2 py-1.5 text-xs">
                                                    <div class="flex items-start gap-1.5">
                                                        <svg class="w-3 h-3 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                        </svg>
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-start justify-between gap-2 mb-0.5">
                                                                <div class="font-medium text-red-800" x-text="rejection.label"></div>
                                                                <div class="text-red-500 text-[10px] flex items-center gap-1 flex-shrink-0">
                                                                    <span x-text="rejection.user_name"></span>
                                                                    <span>•</span>
                                                                    <span x-text="rejection.rejected_at"></span>
                                                                </div>
                                                            </div>
                                                            <div class="text-red-600" x-show="rejection.notes" x-text="rejection.notes"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-1">
                                    <!-- SK Progress -->
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs"
                                         :class="customer.cgp_status?.sk_completed ? 'bg-green-100 text-green-600' : (customer.cgp_status?.sk_ready ? 'bg-yellow-100 text-yellow-600' : (customer.cgp_status?.sk_in_progress ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-400'))">
                                        <span x-text="customer.cgp_status?.sk_completed ? '✓' : 'SK'"></span>
                                    </div>
                                    <div class="w-4 h-px"
                                         :class="customer.cgp_status?.sk_completed ? 'bg-green-400' : 'bg-gray-200'"></div>

                                    <!-- SR Progress -->
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs"
                                         :class="customer.cgp_status?.sr_completed ? 'bg-green-100 text-green-600' : (customer.cgp_status?.sr_ready ? 'bg-yellow-100 text-yellow-600' : (customer.cgp_status?.sr_in_progress ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-400'))">
                                        <span x-text="customer.cgp_status?.sr_completed ? '✓' : 'SR'"></span>
                                    </div>
                                    <div class="w-4 h-px"
                                         :class="customer.cgp_status?.sr_completed ? 'bg-green-400' : 'bg-gray-200'"></div>

                                    <!-- Gas In Progress -->
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs"
                                         :class="customer.cgp_status?.gas_in_completed ? 'bg-green-100 text-green-600' : (customer.cgp_status?.gas_in_ready ? 'bg-yellow-100 text-yellow-600' : (customer.cgp_status?.gas_in_in_progress ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-400'))">
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

    <!-- Rejection Details Modal -->
    <div x-data="rejectionModal()" x-show="isOpen" @keydown.escape.window="closeModal()"
         class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeModal()"></div>

        <!-- Modal -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-hidden">
                <!-- Header -->
                <div class="bg-red-50 border-b border-red-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900" x-text="moduleTitle + ' Rejections'"></h3>
                                <p class="text-sm text-gray-600" x-text="`${rejectionData.count} photo(s) rejected`"></p>
                            </div>
                        </div>
                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-180px)]">
                    <!-- Group by User -->
                    <template x-for="userRejections in rejectionData.by_user" :key="userRejections.user_id">
                        <div class="mb-6 last:mb-0">
                            <!-- User Header -->
                            <div class="flex items-center mb-3 pb-2 border-b border-gray-200">
                                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mr-2">
                                    <span class="text-white text-xs font-medium" x-text="userRejections.user_name.charAt(0).toUpperCase()"></span>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900" x-text="userRejections.user_name"></div>
                                    <div class="text-xs text-gray-500" x-text="`Rejected ${userRejections.count} photo(s)`"></div>
                                </div>
                            </div>

                            <!-- Photos List -->
                            <div class="space-y-3">
                                <template x-for="photo in userRejections.photos" :key="photo.field_name">
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <div class="font-medium text-gray-900" x-text="photo.label"></div>
                                                <div class="text-xs text-gray-500" x-text="photo.rejected_at"></div>
                                            </div>
                                        </div>
                                        <div class="mt-2" x-show="photo.notes">
                                            <div class="text-xs font-medium text-gray-500 mb-1">Rejection Notes:</div>
                                            <div class="text-sm text-gray-700 bg-white rounded p-2 border border-gray-200" x-text="photo.notes || '-'"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 border-t border-gray-200 px-6 py-4">
                    <button @click="closeModal()"
                            class="w-full px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
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
        },

        showRejectionDetails(module, rejections) {
            window.dispatchEvent(new CustomEvent('open-rejection-modal', {
                detail: { module, rejections }
            }));
        }
    }
}

// Rejection Modal Component
function rejectionModal() {
    return {
        isOpen: false,
        moduleTitle: '',
        rejectionData: {
            count: 0,
            by_user: []
        },

        init() {
            window.addEventListener('open-rejection-modal', (event) => {
                this.openModal(event.detail.module, event.detail.rejections);
            });
        },

        openModal(module, rejections) {
            this.moduleTitle = module;
            this.rejectionData = rejections;
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            this.isOpen = false;
            document.body.style.overflow = '';
        }
    }
}
</script>
@endpush
@endsection
