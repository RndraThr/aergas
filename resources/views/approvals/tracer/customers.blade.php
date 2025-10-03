@extends('layouts.app')

@section('title', 'Tracer - Customer List')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="tracerCustomersData()">
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
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" x-model="filters.search" @input.debounce.500ms="fetchCustomers()"
                           placeholder="Reff ID, Nama, atau Alamat"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select x-model="filters.status" @change="fetchCustomers()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="sk_pending">SK Pending</option>
                        <option value="sr_pending">SR Pending</option>
                        <option value="gas_in_pending">Gas In Pending</option>
                    </select>
                </div>
                <div class="flex items-end space-x-2">
                    <button @click="resetFilters()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium">
                        Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer List -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">Daftar Pelanggan</h2>
                <span class="text-sm text-gray-500" x-text="pagination.total + ' pelanggan ditemukan'"></span>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="p-8 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p class="text-gray-500 mt-2">Loading customers...</p>
        </div>

        <!-- Customer List -->
        <div x-show="!loading && customers.length > 0" class="divide-y divide-gray-200">
            <template x-for="customer in customers" :key="customer.reff_id_pelanggan">
                <div class="p-6 hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-4">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900" x-text="customer.reff_id_pelanggan"></h3>
                                    <p class="text-sm text-gray-600" x-text="customer.nama_pelanggan"></p>
                                    <p class="text-sm text-gray-500" x-text="customer.alamat"></p>
                                </div>
                            </div>

                            <!-- Sequential Progress -->
                            <div class="mt-4">
                                <div class="flex items-center space-x-6">
                                    <!-- SK Status -->
                                    <div class="flex items-center">
                                        <template x-if="customer.sequential_status?.sk_completed">
                                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-2">
                                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        </template>
                                        <template x-if="customer.sequential_status?.sk_rejected && !customer.sequential_status?.sk_completed">
                                            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mr-2">
                                                <i class="fas fa-exclamation-circle text-red-600 text-sm"></i>
                                            </div>
                                        </template>
                                        <template x-if="!customer.sequential_status?.sk_completed && !customer.sequential_status?.sk_rejected">
                                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center mr-2">
                                                <span class="text-yellow-600 font-bold text-xs">SK</span>
                                            </div>
                                        </template>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium"
                                                  :class="{
                                                      'text-green-600': customer.sequential_status?.sk_completed,
                                                      'text-red-600': customer.sequential_status?.sk_rejected && !customer.sequential_status?.sk_completed,
                                                      'text-yellow-600': !customer.sequential_status?.sk_completed && !customer.sequential_status?.sk_rejected
                                                  }">
                                                SK
                                                <span x-text="customer.sequential_status?.sk_completed ? 'Completed' : (customer.sequential_status?.sk_rejected ? 'Has Rejections' : 'Pending')"></span>
                                            </span>
                                            <span x-show="customer.sequential_status?.modules?.sk?.rejected_count > 0"
                                                  class="text-xs text-red-500"
                                                  x-text="customer.sequential_status?.modules?.sk?.rejected_count + ' rejected'"></span>
                                        </div>
                                    </div>

                                    <!-- Arrow -->
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>

                                    <!-- SR Status -->
                                    <div class="flex items-center">
                                        <template x-if="customer.sequential_status?.sr_completed">
                                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-2">
                                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        </template>
                                        <template x-if="customer.sequential_status?.sr_rejected && !customer.sequential_status?.sr_completed">
                                            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mr-2">
                                                <i class="fas fa-exclamation-circle text-red-600 text-sm"></i>
                                            </div>
                                        </template>
                                        <template x-if="customer.sequential_status?.sr_available && !customer.sequential_status?.sr_completed && !customer.sequential_status?.sr_rejected">
                                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-2">
                                                <span class="text-blue-600 font-bold text-xs">SR</span>
                                            </div>
                                        </template>
                                        <template x-if="!customer.sequential_status?.sr_available && !customer.sequential_status?.sr_completed && !customer.sequential_status?.sr_rejected">
                                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mr-2 relative">
                                                <span class="text-gray-400 font-bold text-xs">SR</span>
                                                <i class="fas fa-lock absolute -top-1 -right-1 text-xs text-red-500 bg-white rounded-full p-0.5"></i>
                                            </div>
                                        </template>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium"
                                                  :class="{
                                                      'text-green-600': customer.sequential_status?.sr_completed,
                                                      'text-red-600': customer.sequential_status?.sr_rejected && !customer.sequential_status?.sr_completed,
                                                      'text-blue-600': customer.sequential_status?.sr_available && !customer.sequential_status?.sr_completed && !customer.sequential_status?.sr_rejected,
                                                      'text-gray-400': !customer.sequential_status?.sr_available && !customer.sequential_status?.sr_completed && !customer.sequential_status?.sr_rejected
                                                  }">
                                                SR
                                                <span x-text="customer.sequential_status?.sr_completed ? 'Completed' : (customer.sequential_status?.sr_rejected ? 'Has Rejections' : (customer.sequential_status?.sr_available ? 'Available' : 'Locked'))"></span>
                                            </span>
                                            <template x-if="customer.sequential_status?.modules?.sr">
                                                <span class="text-xs"
                                                      :class="customer.sequential_status?.modules?.sr?.rejected_count > 0 ? 'text-red-500' : 'text-gray-500'"
                                                      x-text="customer.sequential_status?.modules?.sr?.status_text + (customer.sequential_status?.modules?.sr?.rejected_count > 0 ? ' (' + customer.sequential_status?.modules?.sr?.rejected_count + ' rejected)' : (customer.sequential_status?.modules?.sr?.pending_count > 0 ? ' (' + customer.sequential_status?.modules?.sr?.pending_count + ' pending)' : ''))"></span>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Arrow -->
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>

                                    <!-- Gas In Status -->
                                    <div class="flex items-center">
                                        <template x-if="customer.sequential_status?.gas_in_completed">
                                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-2">
                                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        </template>
                                        <template x-if="customer.sequential_status?.gas_in_rejected && !customer.sequential_status?.gas_in_completed">
                                            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mr-2">
                                                <i class="fas fa-exclamation-circle text-red-600 text-sm"></i>
                                            </div>
                                        </template>
                                        <template x-if="customer.sequential_status?.gas_in_available && !customer.sequential_status?.gas_in_completed && !customer.sequential_status?.gas_in_rejected">
                                            <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center mr-2">
                                                <span class="text-orange-600 font-bold text-xs">GI</span>
                                            </div>
                                        </template>
                                        <template x-if="!customer.sequential_status?.gas_in_available && !customer.sequential_status?.gas_in_completed && !customer.sequential_status?.gas_in_rejected">
                                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mr-2 relative">
                                                <span class="text-gray-400 font-bold text-xs">GI</span>
                                                <i class="fas fa-lock absolute -top-1 -right-1 text-xs text-red-500 bg-white rounded-full p-0.5"></i>
                                            </div>
                                        </template>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium"
                                                  :class="{
                                                      'text-green-600': customer.sequential_status?.gas_in_completed,
                                                      'text-red-600': customer.sequential_status?.gas_in_rejected && !customer.sequential_status?.gas_in_completed,
                                                      'text-orange-600': customer.sequential_status?.gas_in_available && !customer.sequential_status?.gas_in_completed && !customer.sequential_status?.gas_in_rejected,
                                                      'text-gray-400': !customer.sequential_status?.gas_in_available && !customer.sequential_status?.gas_in_completed && !customer.sequential_status?.gas_in_rejected
                                                  }">
                                                Gas In
                                                <span x-text="customer.sequential_status?.gas_in_completed ? 'Completed' : (customer.sequential_status?.gas_in_rejected ? 'Has Rejections' : (customer.sequential_status?.gas_in_available ? 'Available' : 'Locked'))"></span>
                                            </span>
                                            <template x-if="customer.sequential_status?.modules?.gas_in">
                                                <span class="text-xs"
                                                      :class="customer.sequential_status?.modules?.gas_in?.rejected_count > 0 ? 'text-red-500' : 'text-gray-500'"
                                                      x-text="customer.sequential_status?.modules?.gas_in?.status_text + (customer.sequential_status?.modules?.gas_in?.rejected_count > 0 ? ' (' + customer.sequential_status?.modules?.gas_in?.rejected_count + ' rejected)' : (customer.sequential_status?.modules?.gas_in?.pending_count > 0 ? ' (' + customer.sequential_status?.modules?.gas_in?.pending_count + ' pending)' : ''))"></span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Current Step Indicator -->
                                <template x-if="customer.sequential_status?.current_step !== 'completed'">
                                    <div class="mt-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            📍 Current Step: <span x-text="(customer.sequential_status?.current_step || '').toUpperCase()"></span>
                                        </span>
                                    </div>
                                </template>
                                <template x-if="customer.sequential_status?.current_step === 'completed'">
                                    <div class="mt-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            ✅ All Steps Completed
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div>
                            <a :href="`/approvals/tracer/customers/${customer.reff_id_pelanggan}/photos`"
                               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium inline-flex items-center">
                                📸 Review Photos
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && customers.length === 0" class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada pelanggan ditemukan</h3>
            <p class="mt-1 text-sm text-gray-500">Coba ubah filter atau kriteria pencarian Anda</p>
        </div>

        <!-- Pagination -->
        <x-pagination />
    </div>
</div>

@push('scripts')
<script>
function tracerCustomersData() {
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

        async fetchCustomers() {
            this.loading = true;

            try {
                const params = new URLSearchParams({
                    search: this.filters.search,
                    status: this.filters.status,
                    page: this.pagination.current_page,
                    ajax: 1
                });

                const response = await fetch(`{{ route('approvals.tracer.customers') }}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.customers = data.data.data || [];
                    this.pagination = {
                        current_page: data.data.current_page,
                        last_page: data.data.last_page,
                        per_page: data.data.per_page,
                        total: data.data.total,
                        from: data.data.from,
                        to: data.data.to
                    };
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
            this.pagination.current_page = 1;
            this.fetchCustomers();
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
        }
    }
}
</script>
@endpush
@endsection
