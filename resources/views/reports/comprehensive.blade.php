@extends('layouts.app')

@section('title', 'Laporan Lengkap - AERGAS')

@section('content')
<div class="min-h-screen bg-gray-50" x-data="comprehensiveReport()">
    <!-- Header -->
    <div class="bg-gradient-to-r from-aergas-navy to-blue-800 shadow-xl">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                <div class="text-white">
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                            <i class="fas fa-table text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold">Laporan Lengkap</h1>
                            <p class="text-blue-100">Data komprehensif pelanggan yang telah selesai</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg px-4 py-2">
                        <div class="text-white text-center">
                            <div class="text-2xl font-bold" x-text="stats.total_completed">{{ $stats['total_completed'] }}</div>
                            <div class="text-xs text-blue-100">Pelanggan Selesai</div>
                        </div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg px-4 py-2">
                        <div class="text-white text-center">
                            <div class="text-2xl font-bold" x-text="stats.completion_rate + '%'">{{ $stats['completion_rate'] }}%</div>
                            <div class="text-xs text-blue-100">Tingkat Selesai</div>
                        </div>
                    </div>
                    <button @click="exportData()"
                            class="px-4 py-2 bg-white text-aergas-navy rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-download mr-2"></i>Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cari Pelanggan</label>
                    <input type="text"
                           x-model="filters.search"
                           @input.debounce.500ms="fetchData()"
                           placeholder="Nama, Reff ID, alamat, telepon..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kelurahan</label>
                    <select x-model="filters.kelurahan" @change="fetchData()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                        <option value="">Semua Kelurahan</option>
                        @foreach($kelurahanList as $kelurahan)
                            <option value="{{ $kelurahan }}">{{ $kelurahan }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Padukuhan</label>
                    <select x-model="filters.padukuhan" @change="fetchData()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                        <option value="">Semua Padukuhan</option>
                        @foreach($padukuhanList as $padukuhan)
                            <option value="{{ $padukuhan }}">{{ $padukuhan }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Pelanggan</label>
                    <select x-model="filters.jenis_pelanggan" @change="fetchData()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                        <option value="">Semua Jenis</option>
                        <option value="pengembangan">Pengembangan</option>
                        <option value="penetrasi">Penetrasi</option>
                        <option value="on_the_spot_penetrasi">On The Spot Penetrasi</option>
                        <option value="on_the_spot_pengembangan">On The Spot Pengembangan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reset</label>
                    <button @click="resetFilters()"
                            class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="fas fa-times mr-2"></i>Reset Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Container with Horizontal Scroll -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-900">
                    Data Pelanggan Lengkap
                    <span class="text-sm font-normal text-gray-500" x-text="'(' + pagination.total + ' total)'"></span>
                </h3>
            </div>

            <!-- Horizontal Scroll Container -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" style="min-width: 2420px;">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <!-- Informasi Pelanggan -->
                            <th colspan="8" class="px-4 py-3 text-center text-xs font-bold text-gray-900 uppercase tracking-wider bg-blue-100 border-b-2 border-blue-200">
                                INFORMASI PELANGGAN
                            </th>
                            <!-- SK Data -->
                            <th colspan="6" class="px-4 py-3 text-center text-xs font-bold text-gray-900 uppercase tracking-wider bg-green-100 border-b-2 border-green-200">
                                DATA SK
                            </th>
                            <!-- SR Data -->
                            <th colspan="8" class="px-4 py-3 text-center text-xs font-bold text-gray-900 uppercase tracking-wider bg-yellow-100 border-b-2 border-yellow-200">
                                DATA SR
                            </th>
                            <!-- Gas In Data -->
                            <th colspan="4" class="px-4 py-3 text-center text-xs font-bold text-gray-900 uppercase tracking-wider bg-purple-100 border-b-2 border-purple-200">
                                DATA GAS IN
                            </th>
                        </tr>
                        <tr>
                            <!-- Informasi Pelanggan Headers -->
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-blue-50">Reff ID</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-blue-50">Nama</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-blue-50">Alamat</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-blue-50">Telepon</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-blue-50">Kelurahan</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-blue-50">Padukuhan</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-blue-50">Jenis</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-blue-50">Tgl Registrasi</th>

                            <!-- SK Headers -->
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-green-50">Tgl Instalasi</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-green-50">Material</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-green-50">Total Fitting</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-green-50">Status</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-green-50">Tgl Approval</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-green-50">Evidence</th>

                            <!-- SR Headers -->
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-yellow-50">Tgl Pemasangan</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-yellow-50">Material</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-yellow-50">Total Items</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-yellow-50">No Seri MGRT</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-yellow-50">Merk MGRT</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-yellow-50">Status</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-yellow-50">Tgl Approval</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-yellow-50">Evidence</th>

                            <!-- Gas In Headers -->
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-purple-50">Tgl Gas In</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-purple-50">Status</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-purple-50">Tgl Approval</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-purple-50">Evidence</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="customer in customers" :key="customer.reff_id_pelanggan">
                            <tr class="hover:bg-gray-50">
                                <!-- Informasi Pelanggan -->
                                <td class="px-3 py-2 text-sm text-gray-900 font-medium" x-text="customer.reff_id_pelanggan"></td>
                                <td class="px-3 py-2 text-sm text-gray-900" x-text="customer.nama_pelanggan"></td>
                                <td class="px-3 py-2 text-sm text-gray-900 max-w-xs" :title="customer.alamat" x-text="customer.alamat"></td>
                                <td class="px-3 py-2 text-sm text-gray-900" x-text="customer.no_telepon"></td>
                                <td class="px-3 py-2 text-sm text-gray-900" x-text="customer.kelurahan || '-'"></td>
                                <td class="px-3 py-2 text-sm text-gray-900" x-text="customer.padukuhan || '-'"></td>
                                <td class="px-3 py-2 text-sm text-gray-900" x-text="formatJenisPelanggan(customer.jenis_pelanggan)"></td>
                                <td class="px-3 py-2 text-sm text-gray-900" x-text="formatDate(customer.tanggal_registrasi)"></td>

                                <!-- SK Data -->
                                <td class="px-3 py-2 text-sm text-gray-900" x-text="formatDate(customer.sk_data?.tanggal_instalasi)"></td>
                                <td class="px-2 py-2 text-sm text-gray-900">
                                    <div class="flex flex-wrap gap-1 max-w-md">
                                        <template x-if="Array.isArray(formatMaterial(customer.sk_data?.material_summary))">
                                            <template x-for="item in formatMaterial(customer.sk_data?.material_summary)" :key="item.label">
                                                <div class="inline-flex items-center px-2 py-1 bg-green-50 text-green-800 rounded text-xs whitespace-nowrap">
                                                    <span class="font-medium" x-text="formatMaterialLabel(item.label) + ':'"></span>
                                                    <span class="ml-1 font-semibold" x-text="formatMaterialValue(item.value, item.unit)"></span>
                                                </div>
                                            </template>
                                        </template>
                                        <template x-if="!Array.isArray(formatMaterial(customer.sk_data?.material_summary))">
                                            <span x-text="formatMaterial(customer.sk_data?.material_summary)"></span>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-center">
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex items-center justify-center px-3 py-1 bg-green-50 text-green-800 rounded-lg font-semibold text-xs" x-text="(customer.sk_data?.material_summary?.total_fitting || '-') + ' pcs'"></span>
                                        <span class="inline-flex items-center justify-center px-3 py-1 bg-green-100 text-green-900 rounded-lg font-semibold text-xs" x-text="(customer.sk_data?.material_summary?.pipa_gl_medium || '-') + 'm'"></span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-sm">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800" x-text="customer.sk_data?.module_status || '-'"></span>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-900" x-text="formatDate(customer.sk_data?.cgp_approved_at)"></td>
                                <td class="px-3 py-2 text-sm">
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="photo in (customer.sk_data?.photo_approvals || [])" :key="photo.id">
                                            <a :href="getEvidenceUrl(photo)" target="_blank"
                                               class="inline-flex items-center px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded hover:bg-blue-200">
                                                <i class="fas fa-image mr-1"></i>
                                                <span x-text="photo.photo_field_name"></span>
                                            </a>
                                        </template>
                                    </div>
                                </td>

                                <!-- SR Data -->
                                <td class="px-3 py-2 text-sm text-gray-900" x-text="formatDate(customer.sr_data?.tanggal_pemasangan)"></td>
                                <td class="px-2 py-2 text-sm text-gray-900">
                                    <div class="flex flex-wrap gap-1 max-w-md">
                                        <template x-if="Array.isArray(formatMaterial(customer.sr_data?.material_summary))">
                                            <template x-for="item in formatMaterial(customer.sr_data?.material_summary)" :key="item.label">
                                                <div class="inline-flex items-center px-2 py-1 bg-yellow-50 text-yellow-800 rounded text-xs whitespace-nowrap">
                                                    <span class="font-medium" x-text="formatMaterialLabel(item.label) + ':'"></span>
                                                    <span class="ml-1 font-semibold" x-text="formatMaterialValue(item.value, item.unit)"></span>
                                                </div>
                                            </template>
                                        </template>
                                        <template x-if="!Array.isArray(formatMaterial(customer.sr_data?.material_summary))">
                                            <span x-text="formatMaterial(customer.sr_data?.material_summary)"></span>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-900 text-center">
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex items-center justify-center px-3 py-1 bg-yellow-50 text-yellow-800 rounded-lg font-semibold text-xs" x-text="(customer.sr_data?.material_summary?.total_items || '-') + ' pcs'"></span>
                                        <span class="inline-flex items-center justify-center px-3 py-1 bg-yellow-100 text-yellow-900 rounded-lg font-semibold text-xs" x-text="(customer.sr_data?.material_summary?.total_lengths || '-') + 'm'"></span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-900" x-text="customer.sr_data?.no_seri_mgrt || '-'"></td>
                                <td class="px-3 py-2 text-sm text-gray-900" x-text="customer.sr_data?.merk_brand_mgrt || '-'"></td>
                                <td class="px-3 py-2 text-sm">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800" x-text="customer.sr_data?.module_status || '-'"></span>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-900" x-text="formatDate(customer.sr_data?.cgp_approved_at)"></td>
                                <td class="px-3 py-2 text-sm">
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="photo in (customer.sr_data?.photo_approvals || [])" :key="photo.id">
                                            <a :href="getEvidenceUrl(photo)" target="_blank"
                                               class="inline-flex items-center px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded hover:bg-blue-200">
                                                <i class="fas fa-image mr-1"></i>
                                                <span x-text="photo.photo_field_name"></span>
                                            </a>
                                        </template>
                                    </div>
                                </td>

                                <!-- Gas In Data -->
                                <td class="px-3 py-2 text-sm text-gray-900" x-text="formatDate(customer.gas_in_data?.tanggal_gas_in)"></td>
                                <td class="px-3 py-2 text-sm">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800" x-text="customer.gas_in_data?.module_status || '-'"></span>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-900" x-text="formatDate(customer.gas_in_data?.cgp_approved_at)"></td>
                                <td class="px-3 py-2 text-sm">
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="photo in (customer.gas_in_data?.photo_approvals || [])" :key="photo.id">
                                            <a :href="getEvidenceUrl(photo)" target="_blank"
                                               class="inline-flex items-center px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded hover:bg-blue-200">
                                                <i class="fas fa-image mr-1"></i>
                                                <span x-text="photo.photo_field_name"></span>
                                            </a>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div x-show="customers.length === 0" class="text-center py-12">
                <i class="fas fa-table text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada data</h3>
                <p class="text-gray-500">Belum ada pelanggan yang selesai dengan kriteria filter ini.</p>
            </div>

            <!-- Pagination -->
            <div x-show="pagination.total > 0" class="bg-gray-50 px-4 py-3 border-t border-gray-200">
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

    <!-- Loading Overlay -->
    <div x-show="loading" x-transition class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin h-6 w-6 text-aergas-orange" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-900 font-medium">Loading...</span>
        </div>
    </div>
</div>

<script>
function comprehensiveReport() {
    return {
        loading: false,
        customers: @json($customers->items() ?? []),
        pagination: {
            current_page: @json($customers->currentPage() ?? 1),
            last_page: @json($customers->lastPage() ?? 1),
            per_page: @json($customers->perPage() ?? 25),
            total: @json($customers->total() ?? 0),
            from: @json($customers->firstItem() ?? 0),
            to: @json($customers->lastItem() ?? 0)
        },
        stats: @json($stats),
        filters: @json($filters),

        formatJenisPelanggan(jenis) {
            const jenisMap = {
                'pengembangan': 'Pengembangan',
                'penetrasi': 'Penetrasi',
                'on_the_spot_penetrasi': 'On The Spot Penetrasi',
                'on_the_spot_pengembangan': 'On The Spot Pengembangan'
            };
            return jenisMap[jenis] || jenis;
        },

        formatDate(dateString) {
            if (!dateString) return '-';
            try {
                return new Date(dateString).toLocaleDateString('id-ID');
            } catch (e) {
                return dateString;
            }
        },

        formatMaterial(materialSummary) {
            if (!materialSummary || typeof materialSummary !== 'object') return '-';

            // Use the new details array for comprehensive breakdown
            if (materialSummary.details && Array.isArray(materialSummary.details)) {
                if (materialSummary.details.length === 0) return '-';

                // Return array for rendering in template
                return materialSummary.details;
            }

            // Fallback to old format
            let summary = [];
            if (materialSummary.pipa_gl_medium) {
                summary.push(`Pipa GL: ${materialSummary.pipa_gl_medium}m`);
            }
            if (materialSummary.total_fitting) {
                summary.push(`${materialSummary.total_fitting} fitting`);
            }
            if (materialSummary.total_items) {
                summary.push(`${materialSummary.total_items} items`);
            }

            return summary.length > 0 ? summary.join(', ') : '-';
        },

        formatMaterialLabel(label) {
            // Shorten label by removing "(Pcs)", "(meter)", etc
            return label
                .replace(/\s*\(Pcs\)/gi, '')
                .replace(/\s*\(meter\)/gi, '')
                .replace(/Panjang /gi, '')
                .replace(/Qty /gi, '');
        },

        formatMaterialValue(value, unit) {
            if (unit === 'm') {
                return `${value}m`;
            } else if (unit === 'pcs') {
                return value;
            }
            return value;
        },

        getEvidenceUrl(photo) {
            if (photo.drive_file_id) {
                return `https://drive.google.com/file/d/${photo.drive_file_id}/view`;
            }
            return '#';
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

        async fetchData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    ...this.filters,
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    ajax: 1
                });

                const response = await fetch(`{{ route('reports.comprehensive') }}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
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
                    this.stats = data.stats;
                }
            } catch (error) {
                console.error('Error fetching data:', error);
            } finally {
                this.loading = false;
            }
        },

        resetFilters() {
            this.filters = {
                search: '',
                kelurahan: '',
                padukuhan: '',
                jenis_pelanggan: '',
                start_date: '',
                end_date: ''
            };
            this.pagination.current_page = 1;
            this.fetchData();
        },

        goToPage(page) {
            this.pagination.current_page = page;
            this.fetchData();
        },

        nextPage() {
            if (this.pagination.current_page < this.pagination.last_page) {
                this.pagination.current_page++;
                this.fetchData();
            }
        },

        previousPage() {
            if (this.pagination.current_page > 1) {
                this.pagination.current_page--;
                this.fetchData();
            }
        },

        async exportData() {
            try {
                const params = new URLSearchParams(this.filters);
                window.open(`{{ route('reports.comprehensive.export') }}?${params}`, '_blank');
            } catch (error) {
                console.error('Export error:', error);
            }
        }
    }
}
</script>
@endsection