@extends('layouts.app')

@section('title', 'Data Calon Pelanggan - AERGAS')
@section('page-title', 'Data Calon Pelanggan')

@section('content')
    <div class="space-y-6" x-data="customersData()" x-init="initPaginationState(); window.customersData = $data"
        :class="baSelectionMode ? 'pb-20' : ''">

        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center space-y-4 lg:space-y-0">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Data Calon Pelanggan</h1>
                <p class="text-gray-600 mt-1">Kelola data calon pelanggan gas AERGAS</p>
            </div>

            <div class="flex items-center space-x-3">
                @if(auth()->user()->hasAnyRole(['admin', 'tracer', 'super_admin']))
                    <button @click="toggleBaSelectionMode()"
                        :class="baSelectionMode ? 'bg-orange-600 hover:bg-orange-700' : 'bg-purple-600 hover:bg-purple-700'"
                        class="flex items-center space-x-2 px-4 py-2 text-white rounded-lg transition-colors">
                        <i class="fas" :class="baSelectionMode ? 'fa-times' : 'fa-file-pdf'"></i>
                        <span x-text="baSelectionMode ? 'Batal' : 'Download Dokumen'"></span>
                    </button>

                    <a href="#" @click.prevent="openSyncModal()"
                        class="flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-cog"></i>
                        <span>Konfigurasi</span>
                    </a>
                @endif

                @if(auth()->user()->hasAnyRole(['admin', 'tracer', 'super_admin', 'cgp']))
                    <button @click="exportData()"
                        class="flex items-center space-x-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-download"></i>
                        <span>Export</span>
                    </button>
                @endif

                @if(auth()->user()->hasAnyRole(['admin', 'tracer', 'super_admin']))
                    <a href="{{ route('imports.calon-pelanggan.form') }}"
                        class="flex items-center space-x-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                        <i class="fas fa-file-excel"></i>
                        <span>Import Excel</span>
                    </a>

                    <a href="{{ route('customers.create') }}" @click="savePageState()"
                        class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-aergas-navy to-aergas-orange text-white rounded-lg hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-plus"></i>
                        <span>Tambah Calon Pelanggan</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Document Selection Bar - Floating at Bottom --}}
        <div x-show="baSelectionMode" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-full"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform translate-y-full"
            class="fixed bottom-6 left-0 right-0 lg:left-64 z-40 px-6">
            <div class="max-w-full">
                <!-- Tab Header -->
                <div
                    class="inline-flex items-center gap-2 bg-white border-2 border-b-0 border-purple-500 rounded-t-xl px-4 py-1.5 shadow-sm relative top-[2px] z-10 transition-transform hover:-translate-y-0.5">
                    <i class="fas fa-file-pdf text-purple-600 text-sm"></i>
                    <span class="font-semibold text-purple-900 text-sm">Mode Pilih Dokumen</span>
                </div>

                <div class="bg-white rounded-xl rounded-tl-none border-2 border-purple-500 shadow-2xl p-3 relative">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-4">
                            <div
                                class="min-w-[240px] text-center text-sm font-medium text-purple-700 bg-purple-100 px-3 py-1.5 rounded-full whitespace-nowrap">
                                <span x-text="selectedBaIds.length"></span> Customer dipilih
                                <span x-show="allPagesSelected" class="ml-1 text-xs">(Semua Halaman)</span>
                            </div>

                            {{-- Document Type Dropdown --}}
                            <div class="relative" x-data="{ openDocDropdown: false }" @click.away="openDocDropdown = false">
                                <button @click="openDocDropdown = !openDocDropdown"
                                    class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-gray-700 transition-colors whitespace-nowrap">
                                    <span>Pilih Jenis Dokumen</span>
                                    <span x-show="selectedDocTypes.length > 0"
                                        class="bg-purple-600 text-white text-xs rounded-full px-1.5 py-0.5"
                                        x-text="selectedDocTypes.length"></span>
                                    <i class="fas fa-chevron-up text-xs transition-transform duration-200"
                                        :class="openDocDropdown ? 'transform rotate-180' : ''"></i>
                                </button>

                                {{-- Dropdown Menu (Upwards) --}}
                                <div x-show="openDocDropdown" x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform translate-y-2"
                                    x-transition:enter-end="opacity-100 transform translate-y-0"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 transform translate-y-0"
                                    x-transition:leave-end="opacity-0 transform translate-y-2"
                                    class="absolute bottom-full mb-2 left-0 w-56 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-50">
                                    <div
                                        class="px-3 py-1.5 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                        Jenis Dokumen</div>

                                    <label
                                        class="flex items-center px-4 py-2 hover:bg-gray-50 cursor-pointer transition-colors">
                                        <input type="checkbox" x-model="selectedDocTypes" value="gas_in"
                                            class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500 mr-3">
                                        <span class="text-sm text-gray-700">BA Gas In</span>
                                    </label>

                                    <label
                                        class="flex items-center px-4 py-2 hover:bg-gray-50 cursor-pointer transition-colors">
                                        <input type="checkbox" x-model="selectedDocTypes" value="mgrt"
                                            class="w-4 h-4 text-orange-600 rounded border-gray-300 focus:ring-orange-500 mr-3">
                                        <span class="text-sm text-gray-700">BA MGRT</span>
                                    </label>

                                    <label
                                        class="flex items-center px-4 py-2 hover:bg-gray-50 cursor-pointer transition-colors">
                                        <input type="checkbox" x-model="selectedDocTypes" value="ba_sk"
                                            class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500 mr-3">
                                        <span class="text-sm text-gray-700">BA SK</span>
                                    </label>

                                    <label
                                        class="flex items-center px-4 py-2 hover:bg-gray-50 cursor-pointer transition-colors">
                                        <input type="checkbox" x-model="selectedDocTypes" value="isometrik_sk"
                                            class="w-4 h-4 text-pink-600 rounded border-gray-300 focus:ring-pink-500 mr-3">
                                        <span class="text-sm text-gray-700">Isometrik SK</span>
                                    </label>

                                    <label
                                        class="flex items-center px-4 py-2 hover:bg-gray-50 cursor-pointer transition-colors">
                                        <input type="checkbox" x-model="selectedDocTypes" value="isometrik_sr"
                                            class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 mr-3">
                                        <span class="text-sm text-gray-700">Isometrik SR</span>
                                    </label>

                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="selectAllBa()"
                                class="px-3 py-1.5 text-sm bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-colors">
                                <i class="fas fa-check-double mr-1"></i>Pilih Halaman Ini
                            </button>
                            <button @click="selectAllPages()" :disabled="loadingAllPages"
                                class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                <i :class="loadingAllPages ? 'fas fa-spinner fa-spin' : 'fas fa-check-circle'"
                                    class="mr-1"></i>
                                <span x-text="loadingAllPages ? 'Memuat...' : 'Pilih Semua Halaman'"></span>
                            </button>
                            <button @click="clearBaSelection()"
                                class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                                <i class="fas fa-times mr-1"></i>Bersihkan
                            </button>
                            <button @click="openDocumentPreviewModal()"
                                :disabled="selectedBaIds.length === 0 || selectedDocTypes.length === 0"
                                class="px-4 py-1.5 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium">
                                <i class="fas fa-eye mr-1"></i>Preview <span x-text="selectedBaIds.length"></span> Dokumen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            <x-stat-card title="Total Calon Pelanggan" :value="$stats['total_customers'] ?? 0" icon="fas fa-users"
                color="blue" />
            <x-stat-card title="Perlu Validasi" :value="$stats['pending_validation'] ?? 0" icon="fas fa-user-clock"
                color="yellow" description="Calon pelanggan menunggu validasi admin/tracer" />
            <x-stat-card title="Tervalidasi" :value="$stats['validated_customers'] ?? 0" icon="fas fa-user-check"
                color="green" description="Calon pelanggan sudah divalidasi dan dapat melanjutkan" />
            <x-stat-card title="Dalam Proses" :value="$stats['in_progress_customers'] ?? 0" icon="fas fa-tasks"
                color="purple" description="Calon pelanggan sedang dalam tahap SK/SR/Gas In" />
            <x-stat-card title="Selesai" :value="$stats['completed_customers'] ?? 0" icon="fas fa-check-circle"
                color="green" description="Calon pelanggan telah selesai (done)" />
            <x-stat-card title="Batal" :value="$stats['cancelled_customers'] ?? 0" icon="fas fa-times-circle" color="red"
                description="Calon pelanggan dibatalkan" />
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cari Calon Pelanggan</label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" x-model="filters.search" @input.debounce.500ms="fetchCustomers(true)"
                            placeholder="Nama, Reff ID, alamat, atau telepon..."
                            class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select x-model="filters.status" @change="fetchCustomers(true)"
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
                    <select x-model="filters.progress_status" @change="fetchCustomers(true)"
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
                <button @click="setQuickFilter('completed')"
                    :class="quickFilter === 'completed' ? 'bg-aergas-orange text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-3 py-1 rounded-full text-sm font-medium transition-colors">
                    <i class="fas fa-check-circle mr-1"></i> Selesai
                </button>
                <button @click="setQuickFilter('cancelled')"
                    :class="quickFilter === 'cancelled' ? 'bg-aergas-orange text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-3 py-1 rounded-full text-sm font-medium transition-colors">
                    <i class="fas fa-times-circle mr-1"></i> Batal
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
                        Daftar Calon Pelanggan
                        <span class="text-sm font-normal text-gray-500" x-text="'(' + pagination.total + ' total)'"></span>
                    </h3>

                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500">Show:</span>
                        <select x-model="pagination.per_page" @change="fetchCustomers(true)"
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
                            {{-- Checkbox Column (only in BA selection mode) --}}
                            <th x-show="baSelectionMode" class="px-4 py-3 w-12">
                                <input type="checkbox" @change="toggleAllBa($event.target.checked)"
                                    :checked="customers.length > 0 && selectedBaIds.length === customers.length"
                                    class="w-4 h-4 text-purple-600 rounded focus:ring-purple-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <button @click="sortBy('reff_id_pelanggan')"
                                    class="flex items-center space-x-1 hover:text-gray-700">
                                    <span>Reff ID</span>
                                    <i class="fas fa-sort text-xs"></i>
                                </button>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <button @click="sortBy('nama_pelanggan')"
                                    class="flex items-center space-x-1 hover:text-gray-700">
                                    <span>Calon Pelanggan</span>
                                    <i class="fas fa-sort text-xs"></i>
                                </button>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Kontak</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Progress</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Koordinat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Validasi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <button @click="sortBy('tanggal_registrasi')"
                                    class="flex items-center space-x-1 hover:text-gray-700">
                                    <span>Tgl Registrasi</span>
                                    <i class="fas fa-sort text-xs"></i>
                                </button>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="customer in customers" :key="customer.reff_id_pelanggan">
                            <tr class="hover:bg-gray-50 transition-colors"
                                :class="baSelectionMode && selectedBaIds.includes(customer.reff_id_pelanggan) ? 'bg-purple-50' : ''">
                                {{-- Checkbox Column - Now available for ALL customers --}}
                                <td x-show="baSelectionMode" class="px-4 py-3">
                                    <input type="checkbox" :value="customer.reff_id_pelanggan"
                                        @change="toggleBaSelection(customer.reff_id_pelanggan)"
                                        :checked="selectedBaIds.includes(customer.reff_id_pelanggan)"
                                        class="w-4 h-4 text-purple-600 rounded focus:ring-purple-500">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900"
                                        x-text="formatReffId(customer.reff_id_pelanggan)"></div>
                                    <div x-show="formatReffId(customer.reff_id_pelanggan) !== customer.reff_id_pelanggan"
                                        class="text-xs text-gray-500">
                                        Original: <span x-text="customer.reff_id_pelanggan"></span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div
                                            class="w-10 h-10 min-w-[2.5rem] min-h-[2.5rem] bg-gradient-to-br from-aergas-navy to-aergas-orange rounded-full flex items-center justify-center text-white font-semibold text-base mr-3 flex-shrink-0">
                                            <span
                                                x-text="customer.nama_pelanggan ? customer.nama_pelanggan.charAt(0).toUpperCase() : 'U'"></span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900" x-text="customer.nama_pelanggan">
                                            </div>
                                            <div class="text-sm text-gray-500"
                                                x-text="formatJenisPelanggan(customer.jenis_pelanggan || 'pengembangan')">
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900" x-text="customer.no_telepon"></div>
                                    <div class="text-sm text-gray-500" x-text="customer.kelurahan || 'Belum diisi'"></div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="{
                                                                                                                                                            'bg-green-100 text-green-800': customer.status === 'lanjut',
                                                                                                                                                            'bg-blue-100 text-blue-800': customer.status === 'in_progress',
                                                                                                                                                            'bg-yellow-100 text-yellow-800': customer.status === 'pending',
                                                                                                                                                            'bg-red-100 text-red-800': customer.status === 'batal',
                                                                                                                                                            'bg-gray-100 text-gray-800': !customer.status
                                                                                                                                                        }"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                        <span x-text="customer.status || 'unknown'"></span>
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex flex-col space-y-1">
                                        {{-- SK Badge --}}
                                        <div class="flex items-center text-xs">
                                            <span class="w-12 font-medium text-gray-500">SK:</span>
                                            <span class="px-2 py-0.5 rounded-full font-semibold" :class="{
                                                                                                                            'bg-green-100 text-green-800': customer.sk_data?.module_status === 'completed',
                                                                                                                            'bg-yellow-100 text-yellow-800': customer.sk_data && customer.sk_data?.module_status !== 'completed',
                                                                                                                            'bg-gray-100 text-gray-500': !customer.sk_data
                                                                                                                        }"
                                                x-text="customer.sk_data?.module_status === 'completed' ? 'Done' : (customer.sk_data ? 'Draft' : '-')">
                                            </span>
                                        </div>

                                        {{-- SR Badge --}}
                                        <div class="flex items-center text-xs">
                                            <span class="w-12 font-medium text-gray-500">SR:</span>
                                            <span class="px-2 py-0.5 rounded-full font-semibold" :class="{
                                                                                                                            'bg-green-100 text-green-800': customer.sr_data?.module_status === 'completed',
                                                                                                                            'bg-yellow-100 text-yellow-800': customer.sr_data && customer.sr_data?.module_status !== 'completed',
                                                                                                                            'bg-gray-100 text-gray-500': !customer.sr_data
                                                                                                                        }"
                                                x-text="customer.sr_data?.module_status === 'completed' ? 'Done' : (customer.sr_data ? 'Draft' : '-')">
                                            </span>
                                        </div>

                                        {{-- Gas In Badge --}}
                                        <div class="flex items-center text-xs">
                                            <span class="w-12 font-medium text-gray-500">Gas In:</span>
                                            <span class="px-2 py-0.5 rounded-full font-semibold" :class="{
                                                                                                                            'bg-green-100 text-green-800': customer.gas_in_data?.module_status === 'completed',
                                                                                                                            'bg-yellow-100 text-yellow-800': customer.gas_in_data && customer.gas_in_data?.module_status !== 'completed',
                                                                                                                            'bg-gray-100 text-gray-500': !customer.gas_in_data
                                                                                                                        }"
                                                x-text="customer.gas_in_data?.module_status === 'completed' ? 'Done' : (customer.gas_in_data ? 'Draft' : '-')">
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <template x-if="customer.latitude && customer.longitude">
                                            <div class="flex items-center text-green-600">
                                                <i class="fas fa-map-marker-alt mr-1"></i>
                                                <span class="text-xs font-medium">Ada</span>
                                            </div>
                                            <div class="text-xs text-gray-500"
                                                x-text="customer.latitude + ', ' + customer.longitude"></div>
                                        </template>
                                        <template x-if="!customer.latitude || !customer.longitude">
                                            <div class="flex items-center text-gray-400">
                                                <i class="fas fa-map-marker-alt mr-1"></i>
                                                <span class="text-xs">Belum ada</span>
                                            </div>
                                        </template>
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
                                                <div class="text-xs text-gray-500"
                                                    x-text="customer.validated_at ? new Date(customer.validated_at).toLocaleDateString('id-ID') : ''">
                                                </div>
                                                <div class="text-xs text-gray-400"
                                                    x-text="customer.validated_by_name ? 'by ' + customer.validated_by_name : ''">
                                                </div>
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
                                    <div
                                        x-text="customer.tanggal_registrasi ? new Date(customer.tanggal_registrasi).toLocaleDateString('id-ID') : '-'">
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a :href="'/customers/' + customer.reff_id_pelanggan" @click="savePageState()"
                                            class="text-aergas-orange hover:text-aergas-navy transition-colors">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        @if(auth()->user()->hasAnyRole(['admin', 'tracer', 'super_admin']))
                                            <a :href="'/customers/' + customer.reff_id_pelanggan + '/edit'"
                                                @click="savePageState()"
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
                                                    {{-- SK Link --}}
                                                    <a :href="customer.sk_data ? '/sk/' + customer.sk_data.id + '/edit' : '/sk/create?reff_id=' + customer.reff_id_pelanggan"
                                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-wrench mr-2 w-4"></i>
                                                        <span x-text="customer.sk_data ? 'Edit SK' : 'Buat SK'"></span>
                                                    </a>

                                                    {{-- SR Link --}}
                                                    <a :href="customer.sr_data ? '/sr/' + customer.sr_data.id + '/edit' : '/sr/create?reff_id=' + customer.reff_id_pelanggan"
                                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-home mr-2 w-4"></i>
                                                        <span x-text="customer.sr_data ? 'Edit SR' : 'Buat SR'"></span>
                                                    </a>

                                                    {{-- Gas In Link --}}
                                                    <a :href="customer.gas_in_data ? '/gas-in/' + customer.gas_in_data.id + '/edit' : '/gas-in/create?reff_id=' + customer.reff_id_pelanggan"
                                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-fire mr-2 w-4"></i>
                                                        <span
                                                            x-text="customer.gas_in_data ? 'Edit Gas In' : 'Buat Gas In'"></span>
                                                    </a>

                                                    <a :href="'/customers/' + customer.reff_id_pelanggan"
                                                        @click="savePageState()"
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
                    @if(auth()->user()->hasAnyRole(['admin', 'tracer', 'super_admin']))
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
                        <button @click="previousPage()" :disabled="pagination.current_page <= 1"
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

                        <button @click="nextPage()" :disabled="pagination.current_page >= pagination.last_page"
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
                        // Progress percentage now comes from database, no need to calculate
                    },

                    formatProgressPercentage(percentage) {
                        if (!percentage || percentage === 0) return '0%';
                        return Math.round(parseFloat(percentage)) + '%';
                    },

                    formatReffId(reffId) {
                        if (!reffId) return '';

                        const value = reffId.toString().trim();

                        // Check if input is exactly 6 digits
                        if (/^\d{6}$/.test(value)) {
                            return '00' + value;
                        }

                        return value.toUpperCase();
                    },

                    formatJenisPelanggan(jenis) {
                        const jenisMap = {
                            'pengembangan': 'Pengembangan',
                            'penetrasi': 'Penetrasi',
                            'on_the_spot_penetrasi': 'On The Spot Penetrasi',
                            'on_the_spot_pengembangan': 'On The Spot Pengembangan'
                        };
                        return jenisMap[jenis] || 'Pengembangan';
                    },


                    getNextAvailableModule(customer) {
                        const modules = ['sk', 'sr', 'gas_in'];
                        return modules[0];
                    },

                    async fetchCustomers(resetPage = false) {
                        this.loading = true;

                        // Auto-reset to page 1 when filters change
                        if (resetPage) {
                            this.pagination.current_page = 1;
                        }

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

                                // Smart pagination: if current page > last page, auto-adjust
                                if (this.pagination.current_page > this.pagination.last_page && this.pagination.last_page > 0) {
                                    this.pagination.current_page = this.pagination.last_page;
                                    this.fetchCustomers(); // Re-fetch with adjusted page
                                    return;
                                }

                                // Progress percentage now comes from database
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

                        switch (filter) {
                            case 'pending_validation':
                                this.filters.status = 'pending';
                                break;
                            case 'lanjut':
                                this.filters.status = 'lanjut';
                                break;
                            case 'in_progress':
                                this.filters.status = 'in_progress';
                                break;
                            case 'completed':
                                this.filters.progress_status = 'done';
                                break;
                            case 'cancelled':
                                this.filters.progress_status = 'batal';
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

                        this.fetchCustomers(true);
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
                        this.fetchCustomers(true);
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
                        const params = new URLSearchParams({
                            ...this.filters,
                            sort_by: this.sorting.field,
                            sort_direction: this.sorting.direction
                        });
                        window.location.href = `{{ route('customers.export') }}?${params}`;
                    },

                    // BA Download states
                    baSelectionMode: false,
                    selectedBaIds: [],
                    selectedBaCustomersCache: {}, // Cache customer data across pages
                    showBaDownloadModal: false,
                    baDownloadLoading: false,
                    loadingAllPages: false, // Loading state for select all pages
                    allPagesSelected: false, // Flag to indicate all pages are selected

                    // Document Selection States
                    selectedDocTypes: ['gas_in'], // Default to BA Gas In
                    documentPreviewModalOpen: false,
                    activePreviewTab: 'gas_in',
                    previewLoading: false,
                    previewCurrentIndex: 0,
                    mergeToSinglePdf: false, // Option to merge documents into single PDF

                    // BA Selection Methods
                    toggleBaSelectionMode() {
                        this.baSelectionMode = !this.baSelectionMode;
                        if (!this.baSelectionMode) {
                            this.selectedBaIds = [];
                            this.selectedBaCustomersCache = {};
                            this.allPagesSelected = false;
                        }
                    },

                    toggleBaSelection(reffId) {
                        const index = this.selectedBaIds.indexOf(reffId);
                        if (index > -1) {
                            // Remove from selection
                            this.selectedBaIds.splice(index, 1);
                            delete this.selectedBaCustomersCache[reffId];
                        } else {
                            // Add to selection
                            this.selectedBaIds.push(reffId);
                            // Cache the customer data
                            const customer = this.customers.find(c => c.reff_id_pelanggan === reffId);
                            if (customer) {
                                this.selectedBaCustomersCache[reffId] = {
                                    reff_id_pelanggan: customer.reff_id_pelanggan,
                                    nama_pelanggan: customer.nama_pelanggan,
                                    gas_in_data: customer.gas_in_data
                                };
                            }
                        }
                    },

                    toggleAllBa(checked) {
                        if (checked) {
                            // Select ALL customers on current page
                            this.customers.forEach(customer => {
                                if (!this.selectedBaIds.includes(customer.reff_id_pelanggan)) {
                                    this.selectedBaIds.push(customer.reff_id_pelanggan);
                                    // Cache customer data
                                    this.selectedBaCustomersCache[customer.reff_id_pelanggan] = {
                                        reff_id_pelanggan: customer.reff_id_pelanggan,
                                        nama_pelanggan: customer.nama_pelanggan,
                                        gas_in_data: customer.gas_in_data
                                    };
                                }
                            });
                        } else {
                            // Unselect customers on current page only
                            this.customers.forEach(customer => {
                                const index = this.selectedBaIds.indexOf(customer.reff_id_pelanggan);
                                if (index > -1) {
                                    this.selectedBaIds.splice(index, 1);
                                    delete this.selectedBaCustomersCache[customer.reff_id_pelanggan];
                                }
                            });
                        }
                    },

                    selectAllBa() {
                        // Select ALL customers on current page
                        this.customers.forEach(customer => {
                            if (!this.selectedBaIds.includes(customer.reff_id_pelanggan)) {
                                this.selectedBaIds.push(customer.reff_id_pelanggan);
                                // Cache customer data
                                this.selectedBaCustomersCache[customer.reff_id_pelanggan] = {
                                    reff_id_pelanggan: customer.reff_id_pelanggan,
                                    nama_pelanggan: customer.nama_pelanggan,
                                    gas_in_data: customer.gas_in_data
                                };
                            }
                        });
                    },

                    clearBaSelection() {
                        this.selectedBaIds = [];
                        this.selectedBaCustomersCache = {};
                        this.allPagesSelected = false;
                    },

                    async selectAllPages() {
                        this.loadingAllPages = true;
                        try {
                            // Build query params from current filters
                            const params = new URLSearchParams({
                                search: this.search || '',
                                status: this.selectedStatus || '',
                                progress: this.selectedProgress || '',
                                all_pages: 'true' // Signal to backend to return all
                            });

                            const response = await fetch(`/customers/get-all-ids?${params.toString()}`, {
                                method: 'GET',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': window.csrfToken
                                }
                            });

                            if (!response.ok) {
                                throw new Error('Failed to fetch all customers');
                            }

                            const data = await response.json();

                            // Cache all customer data
                            data.customers.forEach(customer => {
                                if (!this.selectedBaIds.includes(customer.reff_id_pelanggan)) {
                                    this.selectedBaIds.push(customer.reff_id_pelanggan);
                                }
                                this.selectedBaCustomersCache[customer.reff_id_pelanggan] = {
                                    reff_id_pelanggan: customer.reff_id_pelanggan,
                                    nama_pelanggan: customer.nama_pelanggan,
                                    gas_in_data: customer.gas_in_data
                                };
                            });

                            this.allPagesSelected = true;
                            window.showToast('success', `${data.customers.length} customer dipilih dari semua halaman`);
                        } catch (error) {
                            console.error('Error selecting all pages:', error);
                            window.showToast('error', 'Gagal memilih semua customer');
                        } finally {
                            this.loadingAllPages = false;
                        }
                    },

                    getSelectedBaItems() {
                        // Return ALL selected customers from cache (works across pagination)
                        return this.selectedBaIds.map(reffId => {
                            const cachedCustomer = this.selectedBaCustomersCache[reffId];
                            if (cachedCustomer) {
                                return {
                                    reff_id_pelanggan: cachedCustomer.reff_id_pelanggan,
                                    tanggal_gas_in: cachedCustomer.gas_in_data?.tanggal_gas_in || null,
                                    calon_pelanggan: {
                                        nama_pelanggan: cachedCustomer.nama_pelanggan,
                                        reff_id_pelanggan: cachedCustomer.reff_id_pelanggan
                                    }
                                };
                            }
                            return null;
                        }).filter(item => item !== null);
                    },

                    getBaFilename(row) {
                        const reffId = row.calon_pelanggan?.reff_id_pelanggan || row.reff_id_pelanggan || 'Unknown';
                        const timestamp = new Date().toISOString().slice(0, 10).replace(/-/g, '');
                        return `BA_GasIn_${reffId}_${timestamp}.pdf`;
                    },

                    formatDate(date) {
                        if (!date) return '-';
                        return new Date(date).toLocaleDateString('id-ID');
                    },

                    openBaDownloadModal() {
                        if (this.selectedBaIds.length === 0) {
                            alert('Silakan pilih minimal 1 BA untuk di-download');
                            return;
                        }

                        // Debug: Log selected items
                        console.log('Selected IDs:', this.selectedBaIds);
                        console.log('Cached Customers:', this.selectedBaCustomersCache);
                        console.log('Selected Items:', this.getSelectedBaItems());
                        console.log('Current Page Customers:', this.customers);

                        this.showBaDownloadModal = true;
                    },

                    closeBaDownloadModal() {
                        this.showBaDownloadModal = false;
                    },

                    // Document Preview Modal Methods
                    openDocumentPreviewModal() {
                        if (this.selectedBaIds.length === 0) {
                            alert('Silakan pilih minimal 1 customer');
                            return;
                        }
                        if (this.selectedDocTypes.length === 0) {
                            alert('Silakan pilih minimal 1 jenis dokumen');
                            return;
                        }
                        this.activePreviewTab = this.selectedDocTypes[0];
                        this.previewCurrentIndex = 0;
                        this.previewLoading = true;
                        this.documentPreviewModalOpen = true;
                    },

                    closeDocumentPreviewModal() {
                        this.documentPreviewModalOpen = false;
                        this.previewLoading = false;
                    },

                    getPreviewUrl() {
                        const customerId = this.selectedBaIds[this.previewCurrentIndex];
                        if (!customerId) return '';

                        if (this.activePreviewTab === 'gas_in') {
                            return `/customers/${customerId}/berita-acara/preview`;
                        } else if (this.activePreviewTab === 'mgrt') {
                            return `/customers/${customerId}/ba-mgrt/preview`;
                        } else if (this.activePreviewTab === 'isometrik_sr') {
                            return `/customers/${customerId}/isometrik-sr/preview`;
                        } else if (this.activePreviewTab === 'ba_sk') {
                            return `/customers/${customerId}/ba-sk/preview`;
                        } else if (this.activePreviewTab === 'isometrik_sk') {
                            return `/customers/${customerId}/isometrik-sk/preview`;
                        }
                        return '';
                    },

                    navigatePreview(direction) {
                        const newIndex = this.previewCurrentIndex + direction;
                        if (newIndex >= 0 && newIndex < this.selectedBaIds.length) {
                            this.previewLoading = true;
                            this.previewCurrentIndex = newIndex;
                        }
                    },

                    goToPreviewIndex(index) {
                        if (index !== this.previewCurrentIndex) {
                            this.previewLoading = true;
                        }
                        this.previewCurrentIndex = index;
                    },

                    async downloadCurrentTab() {
                        const ids = this.selectedBaIds;
                        const docType = this.activePreviewTab;

                        // If merge mode is active, always use bulk download with merge
                        if (this.mergeToSinglePdf) {
                            this.submitBulkDownload([docType], true);
                            return;
                        }

                        if (docType === 'gas_in') {
                            const params = new URLSearchParams();
                            ids.forEach(id => params.append('ids[]', id));
                            window.location.href = '/customers/download-bulk-ba?' + params.toString();
                        } else if (docType === 'mgrt') {
                            const params = new URLSearchParams();
                            ids.forEach(id => params.append('ids[]', id));
                            window.location.href = '/customers/download-bulk-ba-mgrt?' + params.toString();
                        } else if (docType === 'isometrik_sr') {
                            this.submitBulkDownload(['isometrik_sr']);
                        } else if (docType === 'ba_sk') {
                            this.submitBulkDownload(['ba_sk']);
                        } else if (docType === 'isometrik_sk') {
                            this.submitBulkDownload(['isometrik_sk']);
                        }
                    },

                    submitBulkDownload(docTypes, merged = false) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        // Use merged endpoint if mergeToSinglePdf is checked
                        form.action = (merged || this.mergeToSinglePdf)
                            ? '/customers/documents/bulk-download-merged'
                            : '/customers/documents/bulk-download';
                        form.style.display = 'none';

                        const csrfInput = document.createElement('input');
                        csrfInput.name = '_token';
                        csrfInput.value = window.csrfToken;
                        form.appendChild(csrfInput);

                        this.selectedBaIds.forEach(id => {
                            const input = document.createElement('input');
                            input.name = 'ids[]';
                            input.value = id;
                            form.appendChild(input);
                        });

                        docTypes.forEach(type => {
                            const input = document.createElement('input');
                            input.name = 'doc_types[]';
                            input.value = type;
                            form.appendChild(input);
                        });

                        document.body.appendChild(form);
                        form.submit();
                        document.body.removeChild(form);
                    },

                    async downloadAllDocuments() {
                        this.submitBulkDownload(this.selectedDocTypes);
                    },

                    async executeBaDownload() {
                        if (this.selectedBaIds.length === 0) {
                            alert('Tidak ada BA yang dipilih');
                            return;
                        }

                        this.baDownloadLoading = true;

                        try {
                            const params = new URLSearchParams();
                            this.selectedBaIds.forEach(id => {
                                params.append('ids[]', id);
                            });

                            const url = '/customers/download-bulk-ba?' + params.toString();

                            // Create temporary link and trigger download
                            const link = document.createElement('a');
                            link.href = url;
                            link.download = 'Berita_Acara_Gas_In_' + new Date().toISOString().slice(0, 10) + '.zip';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);

                            // Estimasi waktu tunggu berdasarkan jumlah file
                            const estimatedTime = this.selectedBaIds.length > 50 ? 120000 : // 2 menit
                                this.selectedBaIds.length > 20 ? 60000 :   // 1 menit
                                    30000; // 30 detik

                            // Close modal & loading setelah estimasi waktu
                            setTimeout(() => {
                                this.baDownloadLoading = false;
                                this.closeBaDownloadModal();
                                this.selectedBaIds = [];
                                this.baSelectionMode = false;
                            }, estimatedTime);

                        } catch (error) {
                            console.error('BA Download error:', error);
                            alert('Terjadi kesalahan saat mengunduh BA. Silakan coba lagi.');
                            this.baDownloadLoading = false;
                        }
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
                    },

                    savePageState() {
                        // Save current page and filters to sessionStorage
                        const state = {
                            page: this.pagination.current_page,
                            filters: this.filters,
                            timestamp: Date.now()
                        };
                        sessionStorage.setItem('customersIndexPageState', JSON.stringify(state));
                    },

                    restorePageState() {
                        // Restore page state from sessionStorage
                        const savedState = sessionStorage.getItem('customersIndexPageState');
                        if (savedState) {
                            const state = JSON.parse(savedState);
                            // Only restore if saved within last 30 minutes
                            if (Date.now() - state.timestamp < 30 * 60 * 1000) {
                                this.pagination.current_page = state.page || 1;
                                this.filters = state.filters || this.filters;
                                this.fetchCustomers();
                                // Clear the saved state after restoring
                                sessionStorage.removeItem('customersIndexPageState');
                            }
                        }
                    },

                    initPaginationState() {
                        // Check if we're returning from detail page
                        this.restorePageState();
                    },

                    // --- SYNC LOGIC ---
                    showSyncModal: false,
                    syncLoading: false,
                    syncData: { new: [], updated: [], unchanged: [] },

                    openSyncModal() {
                        this.showSyncModal = true;
                        this.syncLoading = true;
                        this.syncData = { new: [], updated: [], unchanged: [] };

                        fetch('{{ route("customers.sync-preview") }}')
                            .then(response => response.json())
                            .then(data => {
                                if (data.error) throw new Error(data.error);
                                this.syncData = data;
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire('Error', 'Gagal memuat data sync: ' + error.message, 'error');
                                this.showSyncModal = false;
                            })
                            .finally(() => {
                                this.syncLoading = false;
                            });
                    },

                    confirmSyncProcess() {
                        const hasChanges = this.syncData.new.length > 0 || this.syncData.updated.length > 0 || (this.syncData.deleted?.length || 0) > 0 || (this.syncData.reff_id_changed?.length || 0) > 0;
                        if (!hasChanges) return;

                        let message = `Anda akan:\n`;
                        if (this.syncData.new.length > 0) message += `- Menambah ${this.syncData.new.length} data baru\n`;
                        if (this.syncData.updated.length > 0) message += `- Mengupdate ${this.syncData.updated.length} data\n`;
                        if ((this.syncData.reff_id_changed?.length || 0) > 0) message += `- Migrasi ${this.syncData.reff_id_changed.length} Reff ID\n`;
                        if ((this.syncData.deleted?.length || 0) > 0) message += `- Menghapus ${this.syncData.deleted.length} data\n`;
                        message += `\nLanjutkan?`;

                        const confirmed = confirm(message);
                        if (confirmed) {
                            const form = document.getElementById('syncProcessForm');
                            const allData = [...this.syncData.new, ...this.syncData.updated.map(u => u.data)];

                            // Clear previous inputs if any
                            form.querySelectorAll('input[name="sync_data[]"], input[name="delete_data[]"], input[name="reff_id_change_data[]"]').forEach(el => el.remove());

                            // Add sync data
                            allData.forEach(item => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'sync_data[]';
                                input.value = JSON.stringify(item);
                                form.appendChild(input);
                            });

                            // Add delete data (reff_ids only)
                            (this.syncData.deleted || []).forEach(item => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'delete_data[]';
                                input.value = item.reff_id_pelanggan;
                                form.appendChild(input);
                            });

                            // Add reff_id change data
                            (this.syncData.reff_id_changed || []).forEach(item => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'reff_id_change_data[]';
                                input.value = JSON.stringify(item);
                                form.appendChild(input);
                            });

                            form.submit();
                        }
                    }
                }
            }
        </script>
    @endpush

    {{-- BA MODALS OUTSIDE x-data FOR PROPER Z-INDEX --}}

    {{-- Download BA Modal --}}
    <div x-data="{
                                                                                                                          get showModal() { return window.customersData?.showBaDownloadModal || false },
                                                                                                                          get loading() { return window.customersData?.baDownloadLoading || false },
                                                                                                                          get selectedIds() { return window.customersData?.selectedBaIds || [] },
                                                                                                                          closeModal() { if(window.customersData) window.customersData.showBaDownloadModal = false },
                                                                                                                          executeDownload() { window.customersData?.executeBaDownload() },
                                                                                                                          getSelectedItems() { return window.customersData?.getSelectedBaItems() || [] },
                                                                                                                          getFilename(row) { return window.customersData?.getBaFilename(row) || '' },
                                                                                                                          formatDate(date) { return window.customersData?.formatDate(date) || '-' }
                                                                                                                        }"
        x-show="showModal" x-cloak @click.self="closeModal()"
        class="fixed bg-black bg-opacity-50 flex items-center justify-center p-4"
        style="display: none; position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; z-index: 999999999 !important;">
        <div @click.stop class="bg-white rounded-xl p-6 w-full mx-4 shadow-2xl max-w-3xl"
            style="position: relative; z-index: 1000000000 !important;">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-file-pdf text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Download Multiple Berita Acara Gas In</h3>
                        <p class="text-sm text-gray-600">Preview data yang akan di-download</p>
                    </div>
                </div>
                <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="mb-6">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-800">
                            Total BA: <span class="text-purple-600" x-text="selectedIds.length"></span> file
                        </p>
                        <p class="text-xs text-gray-500">File akan di-download dalam format ZIP</p>
                    </div>
                </div>

                <div x-show="selectedIds.length > 50" class="mb-3 p-3 bg-orange-50 border border-orange-200 rounded">
                    <p class="text-xs text-orange-700">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Perhatian:</strong> Download BA dalam jumlah besar (<span
                            x-text="selectedIds.length"></span> file) dapat memakan waktu lama.
                    </p>
                </div>

                <div class="max-h-96 overflow-y-auto border rounded">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reff ID</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama Customer
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Gas In
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama File</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(row, index) in getSelectedItems()" :key="row.reff_id_pelanggan || index">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-sm text-gray-700" x-text="index + 1"></td>
                                    <td class="px-3 py-2 text-sm font-medium text-purple-600"
                                        x-text="row.reff_id_pelanggan"></td>
                                    <td class="px-3 py-2 text-sm text-gray-700"
                                        x-text="row.calon_pelanggan?.nama_pelanggan || '-'"></td>
                                    <td class="px-3 py-2 text-sm text-gray-500" x-text="formatDate(row.tanggal_gas_in)">
                                    </td>
                                    <td class="px-3 py-2 text-xs text-gray-600 font-mono" x-text="getFilename(row)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex gap-3">
                <button @click="closeModal()" :disabled="loading"
                    class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 disabled:opacity-50">
                    <i class="fas fa-times mr-2"></i>Batal
                </button>
                <button @click="executeDownload()" :disabled="loading"
                    class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50">
                    <span x-show="!loading"><i class="fas fa-download mr-2"></i>Download ZIP</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-2"></i>Downloading...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Loading Overlay saat Download BA --}}
    <div x-data="{
                                                                                                                          get loading() { return window.customersData?.baDownloadLoading || false },
                                                                                                                          get selectedIds() { return window.customersData?.selectedBaIds || [] },
                                                                                                                          closeModal() { if(window.customersData) { window.customersData.baDownloadLoading = false; window.customersData.showBaDownloadModal = false; } }
                                                                                                                        }"
        x-show="loading" x-cloak class="fixed bg-black bg-opacity-75 flex items-center justify-center p-4"
        style="display: none; position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; z-index: 999999999 !important;">
        <div class="bg-white rounded-xl p-8 max-w-md w-full mx-4 shadow-2xl text-center"
            style="position: relative; z-index: 1000000000 !important;">
            <button @click="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"
                title="Tutup (jika download sudah selesai)">
                <i class="fas fa-times text-xl"></i>
            </button>

            <div class="mb-6">
                <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-file-pdf text-purple-600 text-3xl animate-bounce"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Sedang Mengunduh BA...</h3>
                <p class="text-gray-600 text-sm mb-4">
                    Proses download sedang berlangsung. Harap tunggu dan <strong>jangan tutup halaman ini</strong>.
                </p>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start text-left text-xs text-blue-700 space-y-2">
                        <div class="flex-shrink-0 mr-2">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div>
                            <p class="mb-2">
                                <i class="fas fa-check text-green-600 mr-1"></i> Membuat <span class="font-semibold"
                                    x-text="selectedIds.length"></span> Berita Acara PDF
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-check text-green-600 mr-1"></i> Mengkompres menjadi file ZIP
                            </p>
                            <p>
                                <i class="fas fa-clock text-orange-600 mr-1"></i> Estimasi waktu:
                                <span
                                    x-text="selectedIds.length > 50 ? '2-3 menit' : selectedIds.length > 20 ? '1-2 menit' : '< 1 menit'"></span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-center space-x-2 mb-4">
                    <div class="w-3 h-3 bg-purple-600 rounded-full animate-pulse"></div>
                    <div class="w-3 h-3 bg-purple-600 rounded-full animate-pulse" style="animation-delay: 0.2s"></div>
                    <div class="w-3 h-3 bg-purple-600 rounded-full animate-pulse" style="animation-delay: 0.4s"></div>
                </div>

                <p class="text-xs text-gray-500 italic mb-2">
                    Download akan dimulai otomatis setelah proses selesai
                </p>
                <p class="text-xs text-gray-400">
                    Klik tombol [X] di pojok kanan atas untuk menutup jika download sudah selesai
                </p>
            </div>
        </div>
    </div>

    {{-- Tabbed Document Preview Modal --}}
    <div x-data="{
                                                                                                                          get showModal() { return window.customersData?.documentPreviewModalOpen || false },
                                                                                                                          get loading() { return window.customersData?.previewLoading || false },
                                                                                                                          get selectedIds() { return window.customersData?.selectedBaIds || [] },
                                                                                                                          get selectedDocTypes() { return window.customersData?.selectedDocTypes || [] },
                                                                                                                          get activeTab() { return window.customersData?.activePreviewTab || 'gas_in' },
                                                                                                                          get currentIndex() { return window.customersData?.previewCurrentIndex || 0 },
                                                                                                                          get cachedCustomers() { return window.customersData?.selectedBaCustomersCache || {} },
                                                                                                                          get mergeToSinglePdf() { return window.customersData?.mergeToSinglePdf || false },
                                                                                                                          set mergeToSinglePdf(val) { if(window.customersData) window.customersData.mergeToSinglePdf = val; },
                                                                                                                          setActiveTab(tab) { if(window.customersData) { window.customersData.activePreviewTab = tab; window.customersData.previewLoading = true; } },
                                                                                                                          closeModal() { if(window.customersData) window.customersData.closeDocumentPreviewModal() },
                                                                                                                          getPreviewUrl() { return window.customersData?.getPreviewUrl() || '' },
                                                                                                                          navigatePreview(dir) { window.customersData?.navigatePreview(dir) },
                                                                                                                          goToIndex(i) { window.customersData?.goToPreviewIndex(i) },
                                                                                                                          downloadTab() { window.customersData?.downloadCurrentTab() },
                                                                                                                          downloadAll() { window.customersData?.downloadAllDocuments() },
                                                                                                                          getCurrentCustomer() {
                                                                                                                            const id = this.selectedIds[this.currentIndex];
                                                                                                                            return this.cachedCustomers[id] || { reff_id_pelanggan: id, nama_pelanggan: '-' };
                                                                                                                          }
                                                                                                                        }"
        x-show="showModal" x-cloak @click.self="closeModal()"
        class="fixed bg-black bg-opacity-50 flex items-center justify-center p-4"
        style="display: none; position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; z-index: 999999999 !important;">
        <div @click.stop class="bg-white rounded-xl w-full mx-4 shadow-2xl flex flex-col"
            style="position: relative; z-index: 1000000000 !important; max-width: 1200px; height: 90vh;">

            {{-- Header with Tabs --}}
            <div class="border-b px-6 pt-4 pb-0">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-file-pdf text-purple-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Preview Dokumen</h3>
                            <p class="text-sm text-gray-500"><span x-text="selectedIds.length"></span> customer dipilih</p>
                        </div>
                    </div>
                    <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                {{-- Tabs --}}
                <div class="flex gap-1">
                    <template x-for="docType in selectedDocTypes" :key="docType">
                        <button @click="setActiveTab(docType)"
                            :class="activeTab === docType ? 'bg-purple-100 text-purple-700 border-b-2 border-purple-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                            class="px-4 py-2 text-sm font-medium rounded-t-lg transition-colors">
                            <i
                                :class="{
                                                                                                                            'fas fa-fire mr-1': docType === 'gas_in',
                                                                                                                            'fas fa-tachometer-alt mr-1': docType === 'mgrt',
                                                                                                                            'fas fa-project-diagram mr-1': docType === 'isometrik_sr',
                                                                                                                            'fas fa-file-contract mr-1': docType === 'ba_sk',
                                                                                                                            'fas fa-drafting-compass mr-1': docType === 'isometrik_sk'
                                                                                                                        }"></i>
                            <span
                                x-text="{
                                                                                                                            'gas_in': 'BA Gas In',
                                                                                                                            'mgrt': 'BA MGRT',
                                                                                                                            'isometrik_sr': 'Isometrik SR',
                                                                                                                            'ba_sk': 'BA SK',
                                                                                                                            'isometrik_sk': 'Isometrik SK'
                                                                                                                        }[docType]"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Content Area --}}
            <div class="flex-1 flex overflow-hidden">
                {{-- Sidebar - Customer List --}}
                <div class="w-64 border-r bg-gray-50 overflow-y-auto">
                    <div class="p-3">
                        <p class="text-xs text-gray-500 uppercase font-semibold mb-2">Customer List</p>
                    </div>
                    <div class="space-y-1 px-2 pb-4">
                        <template x-for="(id, index) in selectedIds" :key="id">
                            <button @click="goToIndex(index)"
                                :class="currentIndex === index ? 'bg-purple-100 border-purple-500 text-purple-700' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-100'"
                                class="w-full text-left px-3 py-2 rounded-lg border text-sm transition-colors">
                                <div class="font-medium truncate" x-text="cachedCustomers[id]?.nama_pelanggan || id"></div>
                                <div class="text-xs text-gray-500 truncate" x-text="id"></div>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- PDF Preview --}}
                <div class="flex-1 relative bg-gray-100">
                    {{-- Loading Overlay --}}
                    <div x-show="loading"
                        class="absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center z-10">
                        <div class="text-center">
                            <div
                                class="animate-spin rounded-full h-12 w-12 border-4 border-purple-500 border-t-transparent mx-auto mb-4">
                            </div>
                            <p class="text-gray-700 font-medium">Loading PDF...</p>
                        </div>
                    </div>

                    {{-- PDF Iframe --}}
                    <iframe :src="getPreviewUrl()"
                        @load="if(window.customersData) window.customersData.previewLoading = false" class="w-full h-full"
                        frameborder="0"></iframe>
                </div>
            </div>

            {{-- Footer --}}
            <div class="border-t px-6 py-4 bg-gray-50">
                <div class="flex items-center justify-between">
                    {{-- Navigation --}}
                    <div class="flex items-center gap-2">
                        <button @click="navigatePreview(-1)" :disabled="currentIndex <= 0"
                            class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-chevron-left mr-1"></i>Prev
                        </button>
                        <span class="text-sm text-gray-600">
                            <span x-text="currentIndex + 1"></span> / <span x-text="selectedIds.length"></span>
                        </span>
                        <button @click="navigatePreview(1)" :disabled="currentIndex >= selectedIds.length - 1"
                            class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next<i class="fas fa-chevron-right ml-1"></i>
                        </button>
                    </div>

                    {{-- Download Buttons --}}
                    <div class="flex items-center gap-3">
                        {{-- Merge Checkbox --}}
                        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
                            <input type="checkbox" x-model="mergeToSinglePdf"
                                class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                            <span>Gabung ke 1 PDF</span>
                        </label>

                        <button @click="downloadTab()"
                            class="px-4 py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-colors">
                            <i class="fas fa-download mr-2"></i>Download <span
                                x-text="{
                                                                                                                            'gas_in': 'BA Gas In',
                                                                                                                            'mgrt': 'BA MGRT',
                                                                                                                            'isometrik_sr': 'Isometrik SR',
                                                                                                                            'ba_sk': 'BA SK',
                                                                                                                            'isometrik_sk': 'Isometrik SK'
                                                                                                                        }[activeTab]"></span>
                        </button>
                        <button x-show="selectedDocTypes.length > 1" @click="downloadAll()"
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                            <i class="fas fa-file-archive mr-2"></i>Download Semua
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sync Preview Modal -->
    <div x-data="{
                                                        get showSyncModal() { return window.customersData?.showSyncModal || false },
                                                        set showSyncModal(val) { if(window.customersData) window.customersData.showSyncModal = val },
                                                        get syncLoading() { return window.customersData?.syncLoading || false },
                                                        get syncData() { return window.customersData?.syncData || { new: [], updated: [], unchanged: [], deleted: [], deleted_with_progress: [], reff_id_changed: [] } },
                                                        activeTab: 'new',
                                                        closeModal() { this.showSyncModal = false },
                                                        confirmSync() { if(window.customersData) window.customersData.confirmSyncProcess() }
                                                    }" x-show="showSyncModal" class="fixed inset-0 z-50 overflow-hidden"
        style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen">
            <div class="fixed inset-0 bg-gray-500 opacity-75" @click="closeModal()"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-6xl mx-4 max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900">Preview Data Synchronization</h3>
                    <button @click="closeModal()" class="text-gray-400 hover:text-gray-600"><i
                            class="fas fa-times text-xl"></i></button>
                </div>

                <!-- Loading -->
                <div x-show="syncLoading" class="p-10 text-center flex-1">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i>
                    <p class="mt-4 text-gray-600">Mengambil data dari Google Sheet...</p>
                </div>

                <!-- Content -->
                <div x-show="!syncLoading" class="flex-1 flex flex-col overflow-hidden">
                    <!-- Stats Cards -->
                    <div class="px-6 py-4 grid grid-cols-6 gap-2">
                        <button @click="activeTab = 'new'" :class="activeTab === 'new' ? 'ring-2 ring-green-500' : ''"
                            class="bg-green-50 p-2 rounded-lg border border-green-200 text-left hover:bg-green-100 transition">
                            <div class="text-green-800 font-semibold text-xs">Data Baru</div>
                            <div class="text-lg font-bold text-green-900" x-text="syncData.new.length">0</div>
                        </button>
                        <button @click="activeTab = 'updated'"
                            :class="activeTab === 'updated' ? 'ring-2 ring-blue-500' : ''"
                            class="bg-blue-50 p-2 rounded-lg border border-blue-200 text-left hover:bg-blue-100 transition">
                            <div class="text-blue-800 font-semibold text-xs">Update</div>
                            <div class="text-lg font-bold text-blue-900" x-text="syncData.updated.length">0</div>
                        </button>
                        <button @click="activeTab = 'reff_changed'"
                            :class="activeTab === 'reff_changed' ? 'ring-2 ring-purple-500' : ''"
                            class="bg-purple-50 p-2 rounded-lg border border-purple-200 text-left hover:bg-purple-100 transition">
                            <div class="text-purple-800 font-semibold text-xs">ID Berubah</div>
                            <div class="text-lg font-bold text-purple-900" x-text="(syncData.reff_id_changed?.length || 0)">
                                0</div>
                        </button>
                        <button @click="activeTab = 'unchanged'"
                            :class="activeTab === 'unchanged' ? 'ring-2 ring-gray-500' : ''"
                            class="bg-gray-50 p-2 rounded-lg border border-gray-200 text-left hover:bg-gray-100 transition">
                            <div class="text-gray-800 font-semibold text-xs">Tidak Berubah</div>
                            <div class="text-lg font-bold text-gray-900" x-text="syncData.unchanged.length">0</div>
                        </button>
                        <button @click="activeTab = 'deleted'" :class="activeTab === 'deleted' ? 'ring-2 ring-red-500' : ''"
                            class="bg-red-50 p-2 rounded-lg border border-red-200 text-left hover:bg-red-100 transition">
                            <div class="text-red-800 font-semibold text-xs">Dihapus</div>
                            <div class="text-lg font-bold text-red-900" x-text="(syncData.deleted?.length || 0)">0</div>
                        </button>
                        <button @click="activeTab = 'warning'"
                            :class="activeTab === 'warning' ? 'ring-2 ring-yellow-500' : ''"
                            class="bg-yellow-50 p-2 rounded-lg border border-yellow-200 text-left hover:bg-yellow-100 transition">
                            <div class="text-yellow-800 font-semibold text-xs">⚠️ Warning</div>
                            <div class="text-lg font-bold text-yellow-900"
                                x-text="(syncData.deleted_with_progress?.length || 0)">0</div>
                        </button>
                    </div>

                    <!-- Tables Container -->
                    <div class="flex-1 overflow-auto px-6 pb-4">
                        <!-- New Data Table -->
                        <div x-show="activeTab === 'new'" class="overflow-auto max-h-[400px]">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-green-100 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">No</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Reff ID
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Nama
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Alamat
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">No.
                                            Telepon</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">
                                            Kelurahan</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">RT/RW
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="(item, idx) in syncData.new" :key="idx">
                                        <tr class="hover:bg-green-50">
                                            <td class="px-3 py-2 text-sm text-gray-500" x-text="idx + 1"></td>
                                            <td class="px-3 py-2 text-sm font-medium text-gray-900"
                                                x-text="item.reff_id_pelanggan || '-'"></td>
                                            <td class="px-3 py-2 text-sm text-gray-900" x-text="item.nama_pelanggan || '-'">
                                            </td>
                                            <td class="px-3 py-2 text-sm text-gray-500" x-text="item.alamat || '-'"></td>
                                            <td class="px-3 py-2 text-sm text-gray-500" x-text="item.no_telepon || '-'">
                                            </td>
                                            <td class="px-3 py-2 text-sm text-gray-500" x-text="item.kelurahan || '-'"></td>
                                            <td class="px-3 py-2 text-sm text-gray-500"
                                                x-text="(item.rt || '-') + '/' + (item.rw || '-')"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <div x-show="syncData.new.length === 0" class="text-center py-8 text-gray-500">Tidak ada data
                                baru</div>
                        </div>

                        <!-- Updated Data Table -->
                        <div x-show="activeTab === 'updated'" class="overflow-auto max-h-[400px]">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-blue-100 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">No</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Reff ID
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Nama
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">
                                            Perubahan</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="(item, idx) in syncData.updated" :key="idx">
                                        <tr class="hover:bg-blue-50">
                                            <td class="px-3 py-2 text-sm text-gray-500" x-text="idx + 1"></td>
                                            <td class="px-3 py-2 text-sm font-medium text-gray-900"
                                                x-text="item.data?.reff_id_pelanggan || '-'"></td>
                                            <td class="px-3 py-2 text-sm text-gray-900"
                                                x-text="item.data?.nama_pelanggan || '-'"></td>
                                            <td class="px-3 py-2 text-sm">
                                                <template x-for="(diff, field) in item.differences" :key="field">
                                                    <div class="mb-1">
                                                        <span class="font-semibold text-gray-700" x-text="field"></span>:
                                                        <span class="text-red-500 line-through"
                                                            x-text="diff.old || '(kosong)'"></span>
                                                        <i class="fas fa-arrow-right text-gray-400 mx-1"></i>
                                                        <span class="text-green-600 font-bold"
                                                            x-text="diff.new || '(kosong)'"></span>
                                                    </div>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <div x-show="syncData.updated.length === 0" class="text-center py-8 text-gray-500">Tidak ada
                                data yang diupdate</div>
                        </div>

                        <!-- Reff ID Changed Table -->
                        <div x-show="activeTab === 'reff_changed'" class="overflow-auto max-h-[400px]">
                            <div class="bg-purple-100 p-3 rounded-lg mb-4 text-sm text-purple-800">
                                <i class="fas fa-exchange-alt mr-2"></i>
                                Data berikut terdeteksi memiliki <strong>perubahan Reff ID</strong> berdasarkan kecocokan
                                nama pelanggan.
                                Saat sync, sistem akan memperbarui Reff ID tanpa menghapus progress yang sudah ada.
                            </div>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-purple-100 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">No</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Nama
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Reff ID
                                            Lama</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Reff ID
                                            Baru</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Progress
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="(item, idx) in (syncData.reff_id_changed || [])" :key="idx">
                                        <tr class="hover:bg-purple-50">
                                            <td class="px-3 py-2 text-sm text-gray-500" x-text="idx + 1"></td>
                                            <td class="px-3 py-2 text-sm font-medium text-gray-900"
                                                x-text="item.nama_pelanggan || '-'"></td>
                                            <td class="px-3 py-2 text-sm">
                                                <span class="text-red-500 line-through" x-text="item.old_reff_id"></span>
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                <span class="text-green-600 font-bold" x-text="item.new_reff_id"></span>
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                <span class="px-2 py-1 rounded text-xs"
                                                    :class="item.has_progress ? 'bg-yellow-200 font-semibold' : 'bg-gray-100'"
                                                    x-text="item.progress_status || 'validasi'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <div x-show="(syncData.reff_id_changed?.length || 0) === 0"
                                class="text-center py-8 text-gray-500">Tidak ada perubahan Reff ID</div>
                        </div>

                        <!-- Unchanged Data Table -->
                        <div x-show="activeTab === 'unchanged'" class="overflow-auto max-h-[400px]">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-100 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">No</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Reff ID
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Nama
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Alamat
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">No.
                                            Telepon</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="(item, idx) in syncData.unchanged" :key="idx">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 text-sm text-gray-500" x-text="idx + 1"></td>
                                            <td class="px-3 py-2 text-sm font-medium text-gray-900"
                                                x-text="item.reff_id_pelanggan || '-'"></td>
                                            <td class="px-3 py-2 text-sm text-gray-900" x-text="item.nama_pelanggan || '-'">
                                            </td>
                                            <td class="px-3 py-2 text-sm text-gray-500" x-text="item.alamat || '-'"></td>
                                            <td class="px-3 py-2 text-sm text-gray-500" x-text="item.no_telepon || '-'">
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <div x-show="syncData.unchanged.length === 0" class="text-center py-8 text-gray-500">Tidak ada
                                data yang sama</div>
                        </div>
                        <!-- Deleted Data Table (Safe to Delete) -->
                        <div x-show="activeTab === 'deleted'" class="overflow-auto max-h-[400px]">
                            <div class="bg-red-100 p-3 rounded-lg mb-4 text-sm text-red-800">
                                <i class="fas fa-trash-alt mr-2"></i>
                                Data berikut ada di database tapi tidak ada di Google Sheet. Data ini aman untuk dihapus
                                karena belum ada progress.
                            </div>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-red-100 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">No</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Reff ID
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Nama
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Alamat
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="(item, idx) in (syncData.deleted || [])" :key="idx">
                                        <tr class="hover:bg-red-50">
                                            <td class="px-3 py-2 text-sm text-gray-500" x-text="idx + 1"></td>
                                            <td class="px-3 py-2 text-sm font-medium text-gray-900"
                                                x-text="item.reff_id_pelanggan || '-'"></td>
                                            <td class="px-3 py-2 text-sm text-gray-900" x-text="item.nama_pelanggan || '-'">
                                            </td>
                                            <td class="px-3 py-2 text-sm text-gray-500" x-text="item.alamat || '-'"></td>
                                            <td class="px-3 py-2 text-sm">
                                                <span class="px-2 py-1 bg-gray-100 rounded text-xs"
                                                    x-text="item.progress_status || '-'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <div x-show="(syncData.deleted?.length || 0) === 0" class="text-center py-8 text-gray-500">Tidak
                                ada data yang dihapus</div>
                        </div>

                        <!-- Warning: Deleted but has Progress -->
                        <div x-show="activeTab === 'warning'" class="overflow-auto max-h-[400px]">
                            <div class="bg-yellow-100 p-3 rounded-lg mb-4 text-sm text-yellow-800">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Perhatian!</strong> Data berikut tidak ada di Google Sheet TAPI sudah memiliki
                                progress (SK/SR/GasIn).
                                Data ini <strong>TIDAK AKAN DIHAPUS</strong> otomatis. Silakan periksa secara manual.
                            </div>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-yellow-100 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">No</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Reff ID
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Nama
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Alamat
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase">Progress
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="(item, idx) in (syncData.deleted_with_progress || [])" :key="idx">
                                        <tr class="hover:bg-yellow-50">
                                            <td class="px-3 py-2 text-sm text-gray-500" x-text="idx + 1"></td>
                                            <td class="px-3 py-2 text-sm font-medium text-gray-900"
                                                x-text="item.reff_id_pelanggan || '-'"></td>
                                            <td class="px-3 py-2 text-sm text-gray-900" x-text="item.nama_pelanggan || '-'">
                                            </td>
                                            <td class="px-3 py-2 text-sm text-gray-500" x-text="item.alamat || '-'"></td>
                                            <td class="px-3 py-2 text-sm">
                                                <span class="px-2 py-1 bg-yellow-200 rounded text-xs font-semibold"
                                                    x-text="item.progress_status || '-'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <div x-show="(syncData.deleted_with_progress?.length || 0) === 0"
                                class="text-center py-8 text-gray-500">Tidak ada warning</div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t bg-gray-50 flex justify-between items-center">
                        <div class="text-sm text-gray-500">
                            Total: <span
                                x-text="syncData.new.length + syncData.updated.length + syncData.unchanged.length"></span>
                            data
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="closeModal()"
                                class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Tutup</button>
                            <form id="syncProcessForm" action="{{ route('customers.sync-process') }}" method="POST">
                                @csrf
                                <button type="button" @click="confirmSync()"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="syncData.new.length === 0 && syncData.updated.length === 0&& (syncData.deleted?.length || 0) === 0&& (syncData.reff_id_changed?.length || 0) === 0">
                                    <i class="fas fa-sync mr-2"></i>Simpan & Sync
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection