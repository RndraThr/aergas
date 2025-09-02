@extends('layouts.app')

@section('title', 'Data Pelanggan - AERGAS')
@section('page-title', 'Data Pelanggan')

@section('content')
<div class="space-y-6" x-data="customersData()">

    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center space-y-4 lg:space-y-0">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Data Pelanggan</h1>
            <p class="text-gray-600 mt-1">Kelola data calon pelanggan gas AERGAS</p>
        </div>

        <div class="flex items-center space-x-3">
            @if(in_array(auth()->user()->role, ['admin', 'tracer', 'super_admin']))
                <button @click="exportData()"
                        class="flex items-center space-x-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-download"></i>
                    <span>Export</span>
                </button>

                {{-- TODO: Implement import functionality
                <a href="#"
                   class="flex items-center space-x-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-file-excel"></i>
                    <span>Import Excel</span>
                </a>
                --}}

                <a href="{{ route('customers.create') }}"
                   class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-aergas-navy to-aergas-orange text-white rounded-lg hover:shadow-lg transition-all duration-300">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Pelanggan</span>
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <x-stat-card
            title="Total Pelanggan"
            :value="$stats['total_customers'] ?? 0"
            icon="fas fa-users"
            color="blue"
        />
        <x-stat-card
            title="Perlu Validasi"
            :value="$stats['pending_validation'] ?? 0"
            icon="fas fa-user-clock"
            color="yellow"
            description="Pelanggan menunggu validasi admin/tracer"
        />
        <x-stat-card
            title="Tervalidasi"
            :value="$stats['validated_customers'] ?? 0"
            icon="fas fa-user-check"
            color="green"
            description="Pelanggan sudah divalidasi dan dapat melanjutkan"
        />
        <x-stat-card
            title="Dalam Proses"
            :value="$stats['in_progress_customers'] ?? 0"
            icon="fas fa-tasks"
            color="purple"
            description="Pelanggan sedang dalam tahap SK/SR/Gas In"
        />
        <x-stat-card
            title="Selesai/Batal"
            :value="$stats['completed_cancelled'] ?? 0"
            icon="fas fa-flag-checkered"
            color="gray"
            description="Pelanggan selesai atau dibatalkan"
        />
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Cari Pelanggan</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text"
                           x-model="filters.search"
                           @input.debounce.500ms="fetchCustomers()"
                           placeholder="Nama, Reff ID, alamat, atau telepon..."
                           class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select x-model="filters.status" @change="fetchCustomers()"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="lanjut">Lanjut</option>
                    <option value="in_progress">In Progress</option>
                    <option value="batal">Batal</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Progress</label>
                <select x-model="filters.progress_status" @change="fetchCustomers()"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                    <option value="">Semua Progress</option>
                    <option value="validasi">Validasi</option>
                    <option value="sk">SK</option>
                    <option value="sr">SR</option>
                    <option value="gas_in">Gas In</option>
                    <option value="done">Done</option>
                    <option value="batal">Batal</option>
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <button @click="setQuickFilter('pending_validation')"
                    :class="quickFilter === 'pending_validation' ? 'bg-aergas-orange text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-3 py-1 rounded-full text-sm font-medium transition-colors">
                <i class="fas fa-user-clock mr-1"></i> Perlu Validasi
            </button>
            <button @click="setQuickFilter('lanjut')"
                    :class="quickFilter === 'lanjut' ? 'bg-aergas-orange text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-3 py-1 rounded-full text-sm font-medium transition-colors">
                <i class="fas fa-user-check mr-1"></i> Tervalidasi
            </button>
            <button @click="setQuickFilter('in_progress')"
                    :class="quickFilter === 'in_progress' ? 'bg-aergas-orange text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-3 py-1 rounded-full text-sm font-medium transition-colors">
                <i class="fas fa-tasks mr-1"></i> Dalam Proses
            </button>
            <button @click="setQuickFilter('today')"
                    :class="quickFilter === 'today' ? 'bg-aergas-orange text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-3 py-1 rounded-full text-sm font-medium transition-colors">
                <i class="fas fa-calendar-day mr-1"></i> Hari Ini
            </button>
            <button @click="resetFilters()"
                    class="px-3 py-1 rounded-full text-sm font-medium bg-gray-200 text-gray-600 hover:bg-gray-300 transition-colors">
                <i class="fas fa-times mr-1"></i> Reset
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">
                    Daftar Pelanggan
                    <span class="text-sm font-normal text-gray-500" x-text="'(' + pagination.total + ' total)'"></span>
                </h3>

                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500">Show:</span>
                    <select x-model="pagination.per_page" @change="fetchCustomers()"
                            class="border border-gray-300 rounded px-2 py-1 text-sm">
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        <div x-show="loading" class="p-8 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-aergas-orange mx-auto"></div>
            <p class="text-gray-500 mt-2">Loading customers...</p>
        </div>

        <div x-show="!loading" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <button @click="sortBy('reff_id_pelanggan')" class="flex items-center space-x-1 hover:text-gray-700">
                                <span>Reff ID</span>
                                <i class="fas fa-sort text-xs"></i>
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <button @click="sortBy('nama_pelanggan')" class="flex items-center space-x-1 hover:text-gray-700">
                                <span>Pelanggan</span>
                                <i class="fas fa-sort text-xs"></i>
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Validasi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <button @click="sortBy('tanggal_registrasi')" class="flex items-center space-x-1 hover:text-gray-700">
                                <span>Tgl Registrasi</span>
                                <i class="fas fa-sort text-xs"></i>
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="customer in customers" :key="customer.reff_id_pelanggan">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900" x-text="customer.reff_id_pelanggan"></div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-aergas-navy to-aergas-orange rounded-full flex items-center justify-center text-white font-medium mr-3">
                                        <span x-text="customer.nama_pelanggan ? customer.nama_pelanggan.charAt(0).toUpperCase() : 'U'"></span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900" x-text="customer.nama_pelanggan"></div>
                                        <div class="text-sm text-gray-500" x-text="customer.jenis_pelanggan || 'residensial'"></div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900" x-text="customer.no_telepon"></div>
                                <div class="text-sm text-gray-500" x-text="customer.wilayah_area || '-'"></div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="{
                                    'bg-green-100 text-green-800': customer.status === 'lanjut',
                                    'bg-blue-100 text-blue-800': customer.status === 'in_progress',
                                    'bg-yellow-100 text-yellow-800': customer.status === 'pending',
                                    'bg-red-100 text-red-800': customer.status === 'batal',
                                    'bg-gray-100 text-gray-800': !customer.status
                                }" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                    <span x-text="customer.status || 'unknown'"></span>
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between text-sm">
                                            <span x-text="customer.progress_status || 'validasi'"></span>
                                            <span x-text="(customer.progress_percentage || 0) + '%'"></span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                            <div class="bg-gradient-to-r from-aergas-navy to-aergas-orange h-2 rounded-full transition-all duration-300"
                                                 :style="'width: ' + (customer.progress_percentage || 0) + '%'"></div>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm">
                                    <template x-if="customer.validated_at">
                                        <div>
                                            <div class="flex items-center text-green-600 mb-1">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                <span class="text-xs font-medium">Validated</span>
                                            </div>
                                            <div class="text-xs text-gray-500" x-text="customer.validated_at ? new Date(customer.validated_at).toLocaleDateString('id-ID') : ''"></div>
                                            <div class="text-xs text-gray-400" x-text="customer.validated_by_name ? 'by ' + customer.validated_by_name : ''"></div>
                                        </div>
                                    </template>
                                    <template x-if="!customer.validated_at && customer.status === 'pending'">
                                        <div class="flex items-center text-yellow-600">
                                            <i class="fas fa-clock mr-1"></i>
                                            <span class="text-xs font-medium">Menunggu Validasi</span>
                                        </div>
                                    </template>
                                    <template x-if="!customer.validated_at && customer.status === 'batal'">
                                        <div class="flex items-center text-red-600">
                                            <i class="fas fa-times-circle mr-1"></i>
                                            <span class="text-xs font-medium">Ditolak</span>
                                        </div>
                                    </template>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div x-text="customer.tanggal_registrasi ? new Date(customer.tanggal_registrasi).toLocaleDateString('id-ID') : '-'"></div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <a :href="'/customers/' + customer.reff_id_pelanggan"
                                       class="text-aergas-orange hover:text-aergas-navy transition-colors">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    @if(in_array(auth()->user()->role, ['admin', 'tracer', 'super_admin']))
                                        <a :href="'/customers/' + customer.reff_id_pelanggan + '/edit'"
                                           class="text-blue-600 hover:text-blue-800 transition-colors">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif

                                    <div class="relative" x-data="{ open: false }">
                                        <button @click="open = !open"
                                                class="text-gray-400 hover:text-gray-600 transition-colors">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>

                                        <div x-show="open" @click.away="open = false" x-cloak
                                             class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-10">
                                            <div class="py-1">
                                                <template x-if="customer.next_available_module">
                                                    <a :href="getModuleUrl(customer.next_available_module, customer.reff_id_pelanggan)"
                                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-play mr-2"></i>
                                                        <span x-text="'Start ' + (customer.next_available_module || '').toUpperCase()"></span>
                                                    </a>
                                                </template>

                                                <a :href="'/customers/' + customer.reff_id_pelanggan"
                                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <i class="fas fa-info-circle mr-2"></i>
                                                    Detail
                                                </a>

                                                <button @click="validateCustomer(customer.reff_id_pelanggan)"
                                                        x-show="customer.status === 'pending'"
                                                        class="block w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-gray-100">
                                                    <i class="fas fa-check mr-2"></i>
                                                    Validate
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <div x-show="customers.length === 0" class="text-center py-12">
                <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No customers found</h3>
                <p class="text-gray-500 mb-6">Try adjusting your search or filter criteria.</p>
                @if(in_array(auth()->user()->role, ['admin', 'tracer', 'super_admin']))
                    <a href="{{ route('customers.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-aergas-orange text-white rounded-lg hover:bg-aergas-navy transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Add First Customer
                    </a>
                @endif
            </div>
        </div>

        <div x-show="pagination.total > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
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
                                :class="page === pagination.current_page ? 'bg-aergas-orange text-white' : 'text-gray-700 hover:bg-gray-100'"
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
function customersData() {
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
            search: '',
            status: '',
            progress_status: '',
            kelurahan: '',
            padukuhan: ''
        },

        sorting: {
            field: 'created_at',
            direction: 'desc'
        },

        quickFilter: '',
        loading: false,

        init() {
            this.addProgressPercentage();
        },

        addProgressPercentage() {
            this.customers.forEach(customer => {
                customer.progress_percentage = this.calculateProgressPercentage(customer.progress_status);
                customer.next_available_module = this.getNextAvailableModule(customer);
            });
        },

        calculateProgressPercentage(progressStatus) {
            const steps = ['validasi', 'sk', 'sr', 'gas_in', 'done'];
            const currentIndex = steps.indexOf(progressStatus);
            if (currentIndex === -1) return 0;
            if (progressStatus === 'done') return 100;
            return Math.round((currentIndex / (steps.length - 1)) * 100);
        },

        getNextAvailableModule(customer) {
            const modules = ['sk', 'sr', 'gas_in'];
            return modules[0];
        },

        async fetchCustomers() {
            this.loading = true;

            try {
                const params = new URLSearchParams({
                    ...this.filters,
                    sort_by: this.sorting.field,
                    sort_direction: this.sorting.direction,
                    per_page: this.pagination.per_page,
                    page: this.pagination.current_page,
                    ajax: 1
                });

                const response = await fetch(`{{ route('customers.index') }}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
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
                    this.addProgressPercentage();
                }
            } catch (error) {
                console.error('Error fetching customers:', error);
                window.showToast('error', 'Failed to fetch customers');
            } finally {
                this.loading = false;
            }
        },

        sortBy(field) {
            if (this.sorting.field === field) {
                this.sorting.direction = this.sorting.direction === 'asc' ? 'desc' : 'asc';
            } else {
                this.sorting.field = field;
                this.sorting.direction = 'asc';
            }
            this.fetchCustomers();
        },

        setQuickFilter(filter) {
            this.quickFilter = filter;
            
            // Reset filters first
            this.filters.status = '';
            this.filters.progress_status = '';
            this.filters.date_from = '';
            this.filters.date_to = '';

            switch(filter) {
                case 'pending_validation':
                    this.filters.status = 'pending';
                    break;
                case 'lanjut':
                    this.filters.status = 'lanjut';
                    break;
                case 'in_progress':
                    this.filters.status = 'in_progress';
                    break;
                case 'today':
                    this.filters.date_from = new Date().toISOString().split('T')[0];
                    this.filters.date_to = new Date().toISOString().split('T')[0];
                    break;
                case 'week':
                    const weekAgo = new Date();
                    weekAgo.setDate(weekAgo.getDate() - 7);
                    this.filters.date_from = weekAgo.toISOString().split('T')[0];
                    this.filters.date_to = new Date().toISOString().split('T')[0];
                    break;
            }

            this.fetchCustomers();
        },

        resetFilters() {
            this.filters = {
                search: '',
                status: '',
                progress_status: '',
                kelurahan: '',
                padukuhan: ''
            };
            this.quickFilter = '';
            this.fetchCustomers();
        },

        getModuleUrl(module, reffId) {
            const moduleRoutes = {
                'sk': `/sk/create?reff_id=${reffId}`,
                'sr': `/sr/create?reff_id=${reffId}`,
                'gas_in': `/gas-in/create?reff_id=${reffId}`
            };
            return moduleRoutes[module] || '#';
        },

        async validateCustomer(reffId) {
            if (!confirm('Validate this customer?')) return;

            try {
                const response = await fetch(`/customers/${reffId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    body: JSON.stringify({
                        status: 'lanjut',
                        progress_status: 'sk'
                    })
                });

                if (response.ok) {
                    window.showToast('success', 'Customer validated successfully');
                    this.fetchCustomers();
                } else {
                    throw new Error('Validation failed');
                }
            } catch (error) {
                window.showToast('error', 'Failed to validate customer');
            }
        },

        exportData() {
            window.showToast('info', 'Export feature coming soon');
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
            this.fetchCustomers();
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
