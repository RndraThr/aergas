{{-- resources/views/customers/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Data Pelanggan - AERGAS')

@section('content')
@php
    // Seed data awal untuk Alpine
    $paginationData = [
        'current_page' => $customers->currentPage() ?? 1,
        'last_page'    => $customers->lastPage() ?? 1,
        'from'         => $customers->firstItem() ?? 0,
        'to'           => $customers->lastItem() ?? 0,
        'total'        => $customers->total() ?? 0,
    ];

    $customersInit  = $customers->items() ?? [];
    $statsInit      = $stats ?? [];
    $sortFieldInit  = request('sort_by','created_at');
    $sortDirInit    = request('sort_direction','desc');
@endphp

<div class="space-y-6" x-data="customerIndex()" x-init="init()">

    {{-- Header --}}
    @php($u = auth()->user())
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Data Pelanggan</h1>
            <p class="text-gray-600 mt-1">Kelola data calon pelanggan AERGAS</p>
        </div>

        @if($u && ($u->isAdminLike() || $u->isTracer()))
            <a href="{{ route('customers.create') }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i> Tambah Pelanggan
            </a>
        @endif
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-800" x-text="stats.total_customers">{{ $stats['total_customers'] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Total Pelanggan</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-800" x-text="stats.active_customers">{{ $stats['active_customers'] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Aktif</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-800" x-text="stats.pending_validation">{{ $stats['pending_validation'] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Pending</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-star text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-800" x-text="stats.completed_customers">{{ $stats['completed_customers'] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Selesai</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search & Filter --}}
    <div class="bg-white rounded-xl card-shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            {{-- Search --}}
            <div class="md:col-span-2">
                <div class="relative">
                    <input type="text"
                           x-model="search"
                           @input.debounce.300ms="onSearchInput()"
                           placeholder="Cari nama, alamat, atau reference ID..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            {{-- Status --}}
            <div>
                <select x-model="filters.status"
                        @change="onFilterChange()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="validated">Validated</option>
                    <option value="in_progress">In Progress</option>
                    <option value="lanjut">Lanjut</option>
                    <option value="batal">Batal</option>
                </select>
            </div>

            {{-- Progress --}}
            <div>
                <select x-model="filters.progress_status"
                        @change="onFilterChange()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Semua Progress</option>
                    <option value="validasi">Validasi</option>
                    <option value="sk">SK</option>
                    <option value="sr">SR</option>
                    <option value="mgrt">MGRT</option>
                    <option value="gas_in">Gas In</option>
                    <option value="jalur_pipa">Jalur Pipa</option>
                    <option value="penyambungan">Penyambungan</option>
                    <option value="done">Selesai</option>
                    <option value="batal">Batal</option>
                </select>
            </div>

            <!-- Kelurahan -->
            <div>
            <input type="text" x-model="filters.kelurahan" @keyup.enter="onFilterChange()"
                    placeholder="Filter kelurahan (opsional)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Padukuhan -->
            <div>
            <input type="text" x-model="filters.padukuhan" @keyup.enter="onFilterChange()"
                    placeholder="Filter padukuhan (opsional)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl card-shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Daftar Pelanggan</h2>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600">Menampilkan</span>
                    <select x-model.number="perPage"
                            @change="onFilterChange()"
                            class="px-2 py-1 border border-gray-300 rounded text-sm">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="text-sm text-gray-600">data</span>
                </div>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="p-8 text-center">
            <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-2"></i>
            <p class="text-gray-600">Memuat data...</p>
        </div>

        {{-- Body --}}
        <div x-show="!loading" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelurahan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Padukuhan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="c in customers" :key="c.reff_id_pelanggan">
                        <tr class="hover:bg-gray-50">
                            {{-- Customer --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-blue-600" x-text="(c.nama_pelanggan || '?').charAt(0)"></span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900" x-text="c.nama_pelanggan || '-'"></div>
                                        <div class="text-sm text-gray-500" x-text="c.reff_id_pelanggan"></div>
                                        <div class="text-xs text-gray-400" x-text="c.no_telepon || '-'"></div>
                                    </div>
                                </div>
                            </td>

                            {{-- Alamat --}}
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900" x-text="(c.alamat || '-').length > 50 ? (c.alamat || '').substring(0,50)+'…' : (c.alamat || '-')"></div>
                                <div class="text-xs text-gray-500" x-text="c.kelurahan || '-'"></div>
                                <div class="text-xs text-gray-500" x-text="c.padukuhan || '-'"></div>

                            {{-- Status --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="getStatusColor(c.status)"
                                      class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                      x-text="getStatusText(c.status)"></span>
                            </td>

                            {{-- Progress --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" :style="`width: ${(c.progress_percentage||0)}%`"></div>
                                    </div>
                                    <span class="ml-2 text-xs text-gray-500" x-text="`${c.progress_percentage||0}%`"></span>
                                </div>
                                <div class="text-xs text-gray-600 mt-1" x-text="getProgressText(c.progress_status)"></div>
                            </td>

                            {{-- Wilayah --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="c.kelurahan || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="c.padukuhan || '-'"></td>


                            {{-- Aksi --}}
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center gap-2">
                                <a :href="`/customers/${c.reff_id_pelanggan}`"
                                class="px-2 py-1 border rounded text-xs text-blue-700 border-blue-300 hover:bg-blue-50">
                                Detail
                                </a>

                                @php($u = auth()->user())
                                @if($u && ($u->isAdminLike() || $u->isTracer()))
                                <a :href="`/customers/${c.reff_id_pelanggan}/edit`"
                                    class="px-2 py-1 border rounded text-xs text-yellow-700 border-yellow-300 hover:bg-yellow-50">
                                    Edit
                                </a>
                                @endif

                                <template x-if="c.next_available_module">
                                <a :href="`/${c.next_available_module}/create?reff_id=${c.reff_id_pelanggan}`"
                                    class="px-2 py-1 border rounded text-xs text-green-700 border-green-300 hover:bg-green-50">
                                    Lanjut
                                </a>
                                </template>
                            </div>
                            </td>

                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Empty --}}
        <div x-show="!loading && customers.length === 0" class="p-8 text-center">
            <i class="fas fa-users text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada data pelanggan</h3>
            <p class="text-gray-600 mb-4">Belum ada pelanggan yang terdaftar dengan filter yang dipilih</p>
            @if($u && ($u->isAdminLike() || $u->isTracer()))
                <a href="{{ route('customers.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i> Tambah Pelanggan
                </a>
            @endif
        </div>

        {{-- Pagination --}}
        <div x-show="!loading && pagination.last_page > 1" class="px-6 py-3 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Menampilkan <span x-text="pagination.from"></span> - <span x-text="pagination.to"></span>
                    dari <span x-text="pagination.total"></span> data
                </div>
                <div class="flex items-center space-x-2">
                    <button @click="changePage(pagination.current_page - 1)"
                            :disabled="pagination.current_page <= 1"
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        Previous
                    </button>

                    <template x-for="page in getVisiblePages()" :key="page">
                        <button @click="changePage(page)"
                                :class="page === pagination.current_page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                                class="px-3 py-1 border border-gray-300 rounded-md text-sm">
                            <span x-text="page"></span>
                        </button>
                    </template>

                    <button @click="changePage(pagination.current_page + 1)"
                            :disabled="pagination.current_page >= pagination.last_page"
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function customerIndex() {
  return {
    customers: @json($customersInit),
    stats: @json($statsInit),
    pagination: @json($paginationData),
    search: '',
    filters: { status:'', progress_status:'', kelurahan:'', padukuhan:'' },
    perPage: 15,
    currentSort: { field: @json($sortFieldInit), direction: @json($sortDirInit) },
    loading: false,
    aborter: null,

    buildParams() {
      return new URLSearchParams({
        ajax: 1,
        search: this.search || '',
        per_page: this.perPage || 15,
        page: this.pagination.current_page || 1,
        status: this.filters.status || '',
        progress_status: this.filters.progress_status || '',
        kelurahan: this.filters.kelurahan || '',
        padukuhan: this.filters.padukuhan || '',
        sort_by: this.currentSort?.field || 'created_at',
        sort_direction: this.currentSort?.direction || 'desc',
      });
    },

    async loadCustomers(opts = {}) {
      if (opts.resetPage) this.pagination.current_page = 1;
      if (this.aborter) this.aborter.abort();

      const controller = new AbortController();
      this.aborter = controller;
      this.loading = true;

      try {
        const url = `{{ route('customers.index') }}?${this.buildParams().toString()}`;
        const res = await fetch(url, {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          signal: controller.signal
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Gagal mengambil data');

        const p = json.data || {};
        this.customers = p.data || [];
        this.pagination = {
          current_page: p.current_page || 1,
          last_page:    p.last_page    || 1,
          from:         p.from         || 0,
          to:           p.to           || 0,
          total:        p.total        || 0,
        };
        this.stats = json.stats || this.stats;
        this.updateUrl();
      } catch (e) {
        if (e.name !== 'AbortError') {
          console.error(e);
          window.showToast?.('Gagal memuat data pelanggan', 'error');
        }
      } finally {
        this.loading = false;
        this.aborter = null;
      }
    },

    updateUrl() {
      const params = this.buildParams();
      params.delete('ajax');
      const qs = params.toString();
      history.replaceState(null, '', qs ? `${location.pathname}?${qs}` : location.pathname);
    },

    changePage(page) {
      if (page >= 1 && page <= (this.pagination.last_page || 1)) {
        this.pagination.current_page = page;
        this.loadCustomers();
      }
    },

    getVisiblePages() {
      const current = this.pagination.current_page || 1;
      const last = this.pagination.last_page || 1;
      const delta = 2;
      const pages = [];
      for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) pages.push(i);
      return pages;
    },

    onSearchInput()  { this.loadCustomers({ resetPage: true }); },
    onFilterChange() { this.loadCustomers({ resetPage: true }); },
    onSort(field) {
      this.currentSort.field = field;
      this.currentSort.direction = (this.currentSort.direction === 'asc') ? 'desc' : 'asc';
      this.loadCustomers({ resetPage: true });
    },

    // helpers for badges
    getStatusColor(s) {
      const m = {
        'pending':'bg-yellow-100 text-yellow-800',
        'validated':'bg-blue-100 text-blue-800',
        'in_progress':'bg-purple-100 text-purple-800',
        'lanjut':'bg-green-100 text-green-800',
        'batal':'bg-red-100 text-red-800'
      };
      return m[s] || 'bg-gray-100 text-gray-800';
    },
    getStatusText(s) {
      const m = {
        'pending':'Pending','validated':'Validated','in_progress':'In Progress',
        'lanjut':'Lanjut','batal':'Batal'
      };
      return m[s] || (s ?? '-');
    },
    getProgressText(p) {
      const m = {
        'validasi':'Validasi','sk':'SK','sr':'SR','mgrt':'MGRT','gas_in':'Gas In',
        'jalur_pipa':'Jalur Pipa','penyambungan':'Penyambungan','done':'Selesai','batal':'Batal'
      };
      return m[p] || (p ?? '-');
    },

    init() { /* SSR first load */ }
  }
}
</script>
@endpush
@endsection
