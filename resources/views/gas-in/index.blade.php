{{-- resources/views/gas-in/index.blade.php - UPDATED WITH ALPINE PAGINATION --}}
@extends('layouts.app')

@section('title', 'Data Gas In - AERGAS')

@section('content')
<div class="space-y-6" x-data="gasInIndexData()" x-init="initPaginationState(); window.gasInData = $data">

  {{-- Header Section with Responsive Layout --}}
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
      <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Data Gas In</h1>
      <p class="text-gray-600 mt-1 text-sm md:text-base">Daftar Gas Installation</p>
    </div>
    <div class="grid grid-cols-2 md:flex gap-2">
      {{-- Export Excel Button --}}
      <button @click="showExportModal = true" class="px-3 md:px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 text-sm md:text-base whitespace-nowrap">
        <i class="fas fa-file-excel mr-1 md:mr-2"></i>
        <span class="hidden sm:inline">Export Excel</span>
        <span class="sm:hidden">Export</span>
      </button>
      {{-- Download Foto MGRT Button --}}
      <button @click="showDownloadModal = true" class="px-3 md:px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm md:text-base whitespace-nowrap">
        <i class="fas fa-download mr-1 md:mr-2"></i>
        <span class="hidden sm:inline">Download Foto</span>
        <span class="sm:hidden">Download</span>
      </button>
      {{-- Refresh Button --}}
      <button @click="fetchData()" class="px-3 py-2 bg-gray-100 rounded hover:bg-gray-200 text-sm md:text-base">
        <i class="fas fa-sync-alt mr-1"></i>
        <span class="hidden md:inline">Refresh</span>
      </button>
      {{-- Create Button --}}
      <a href="{{ route('gas-in.create') }}" class="px-3 md:px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm md:text-base whitespace-nowrap text-center">
        <i class="fas fa-plus mr-1 md:mr-2"></i>
        <span class="hidden sm:inline">Buat Gas In</span>
        <span class="sm:hidden">Buat</span>
      </a>
    </div>
  </div>

  {{-- Filters --}}
  <div class="bg-white p-4 rounded-xl card-shadow">
    <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
      <div class="md:col-span-4">
        <input type="text" x-model="filters.q" @input.debounce.500ms="fetchData(true)"
               placeholder="Cari Reff ID, Customer, atau Status..."
               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <select x-model="filters.module_status" @change="fetchData(true)"
                class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
          <option value="">Semua Status</option>
          <option value="draft">Draft</option>
          <option value="ai_validation">AI Validation</option>
          <option value="tracer_review">Tracer Review</option>
          <option value="cgp_review">CGP Review</option>
          <option value="completed">Completed</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
      <div>
        <button @click="resetFilters()" class="w-full px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">
          <i class="fas fa-times mr-1"></i>Reset
        </button>
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
      <div>
        <label class="block text-xs text-gray-600 mb-1">Tanggal Instalasi Dari</label>
        <input type="date" x-model="filters.tanggal_dari" @change="fetchData(true)"
               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-xs text-gray-600 mb-1">Tanggal Instalasi Sampai</label>
        <input type="date" x-model="filters.tanggal_sampai" @change="fetchData(true)"
               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
      </div>
      <div class="flex items-end">
        <button @click="resetDateFilter()" x-show="filters.tanggal_dari || filters.tanggal_sampai"
                class="w-full px-4 py-2 bg-red-100 text-red-700 rounded hover:bg-red-200">
          <i class="fas fa-times mr-1"></i>Reset Filter Tanggal
        </button>
      </div>
    </div>
  </div>

  {{-- Stats Cards --}}
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white p-4 rounded-lg card-shadow">
      <div class="text-2xl font-bold text-blue-600" x-text="stats.total"></div>
      <div class="text-sm text-gray-600">Total Gas In</div>
    </div>
    <div class="bg-white p-4 rounded-lg card-shadow">
      <div class="text-2xl font-bold text-yellow-600" x-text="stats.draft"></div>
      <div class="text-sm text-gray-600">Draft</div>
    </div>
    <div class="bg-white p-4 rounded-lg card-shadow">
      <div class="text-2xl font-bold text-purple-600" x-text="stats.ready"></div>
      <div class="text-sm text-gray-600">Ready for Review</div>
    </div>
    <div class="bg-white p-4 rounded-lg card-shadow">
      <div class="text-2xl font-bold text-green-600" x-text="stats.completed"></div>
      <div class="text-sm text-gray-600">Completed</div>
    </div>
  </div>

  {{-- Loading State --}}
  <div x-show="loading" class="bg-white rounded-xl card-shadow p-8 text-center">
    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
    <p class="text-gray-500 mt-4">Loading data Gas In...</p>
  </div>

  {{-- Table --}}
  <div x-show="!loading" class="bg-white rounded-xl card-shadow overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reff ID</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Seri MGRT</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Foto</th>
          <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <template x-for="row in items" :key="row.id">
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.id"></td>
            <td class="px-4 py-3 text-sm font-medium text-blue-600">
              <a :href="`/gas-in/${row.id}`" class="hover:text-blue-800" x-text="row.reff_id_pelanggan"></a>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700" x-text="row.calon_pelanggan?.nama_pelanggan || '-'"></td>
            <td class="px-4 py-3 text-sm">
              <template x-if="row.sr_data && row.sr_data.no_seri_mgrt">
                <div class="flex items-center gap-2">
                  <i class="fas fa-tachometer-alt text-blue-500 text-xs"></i>
                  <div>
                    <button @click="downloadFotoMGRT(row.reff_id_pelanggan, row.calon_pelanggan?.nama_pelanggan, row.tanggal_gas_in)"
                            class="font-medium text-blue-600 hover:text-blue-800 hover:underline text-left"
                            :title="'Klik untuk download foto MGRT: ' + row.sr_data.no_seri_mgrt">
                      <span x-text="row.sr_data.no_seri_mgrt"></span>
                      <i class="fas fa-download ml-1 text-xs"></i>
                    </button>
                    <div class="text-xs text-gray-500" x-text="row.sr_data.merk_brand_mgrt || ''"></div>
                  </div>
                </div>
              </template>
              <template x-if="!row.sr_data || !row.sr_data.no_seri_mgrt">
                <span class="text-gray-400 text-xs italic">Belum ada SR</span>
              </template>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
              <template x-if="row.created_by">
                <div class="flex items-center">
                  <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-2">
                    <span class="text-xs font-medium text-blue-600" x-text="row.created_by.name?.charAt(0).toUpperCase()"></span>
                  </div>
                  <span class="text-sm" x-text="row.created_by.name"></span>
                </div>
              </template>
              <template x-if="!row.created_by">
                <span class="text-gray-400 text-sm">-</span>
              </template>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700" x-text="formatDate(row.tanggal_gas_in)"></td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs font-medium rounded-full"
                      :class="getStatusClass(row.module_status || row.status)"
                      x-text="getStatusText(row.module_status || row.status)"></span>
                <template x-if="row.rejected_photos_count > 0">
                  <div class="relative">
                    <button class="text-red-600 hover:text-red-800 text-xs flex items-center gap-1"
                            :data-gasin-id="row.id"
                            @mouseenter="showRejectionPopup(row.id, $event.target)"
                            @mouseleave="hideRejectionPopup(row.id)">
                      <i class="fas fa-exclamation-circle"></i>
                      <span class="font-medium" x-text="`(${row.rejected_photos_count})`"></span>
                    </button>
                  </div>
                </template>
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="space-y-0.5">
                <template x-if="row.photo_status_details && row.photo_status_details.length > 0">
                  <template x-for="photo in row.photo_status_details" :key="photo.field">
                    <div class="flex items-center gap-1.5 text-xs" :class="photo.bg + ' rounded px-2 py-0.5'">
                      <i class="fas" :class="[photo.icon, photo.color]"></i>
                      <span class="font-medium" :class="photo.color" x-text="photo.label"></span>
                      <template x-if="photo.status === 'missing'">
                        <span class="text-red-500 text-xs">(Kosong)</span>
                      </template>
                      <template x-if="photo.status === 'corrupted'">
                        <span class="text-yellow-600 text-xs">(Error)</span>
                      </template>
                    </div>
                  </template>
                </template>
                <template x-if="!row.photo_status_details || row.photo_status_details.length === 0">
                  <span class="text-gray-400 text-xs">No photo data</span>
                </template>
              </div>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex justify-end gap-1">
                <a :href="`/gas-in/${row.id}`"
                   @click="savePageState()"
                   class="px-3 py-1.5 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200"
                   title="Lihat Detail">
                  <i class="fas fa-eye mr-1"></i>Detail
                </a>
                <template x-if="canEdit(row)">
                  <a :href="`/gas-in/${row.id}/edit`"
                     @click="savePageState()"
                     class="px-3 py-1.5 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200"
                     title="Edit Gas In">
                    <i class="fas fa-edit mr-1"></i>Edit
                  </a>
                </template>
                <template x-if="canEdit(row)">
                  <button @click="confirmDelete(row.id, row.reff_id_pelanggan)"
                          class="px-3 py-1.5 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200"
                          title="Hapus Gas In">
                    <i class="fas fa-trash mr-1"></i>Hapus
                  </button>
                </template>
              </div>
            </td>
          </tr>
        </template>

        {{-- Empty State --}}
        <tr x-show="items.length === 0">
          <td colspan="9" class="px-4 py-8 text-center text-gray-500">
            <div class="flex flex-col items-center">
              <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
              <p class="text-lg font-medium mb-1">Belum ada data Gas In</p>
              <p class="text-sm">Silakan buat Gas In baru untuk memulai</p>
              <a href="{{ route('gas-in.create') }}" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Buat Gas In Pertama
              </a>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  <div x-show="!loading && pagination.total > 0">
    <x-pagination />
  </div>

  {{-- Download Modal --}}
  <div x-show="showDownloadModal"
       x-cloak
       @click.self="closeDownloadModal()"
       class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
       style="display: none;">
    <div @click.stop class="bg-white rounded-xl p-6 w-full mx-4 shadow-2xl" :class="downloadStep === 'preview' ? 'max-w-3xl' : 'max-w-md'">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
          <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-3">
            <i class="fas fa-download text-green-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Download Foto MGRT</h3>
            <p class="text-sm text-gray-600" x-text="downloadStep === 'filter' ? 'Pilih Rentang Tanggal' : 'Preview Data'"></p>
          </div>
        </div>
        <button @click="closeDownloadModal()" class="text-gray-400 hover:text-gray-600">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>

      {{-- Step 1: Filter --}}
      <div x-show="downloadStep === 'filter'" class="mb-6">
        <p class="text-sm text-gray-600 mb-4">
          Download semua foto regulator (MGRT) dari data Gas In dengan format penamaan:
          <code class="bg-gray-100 px-2 py-1 rounded text-xs">ReffID_NamaCustomer_TanggalGasIn_MGRT.jpg</code>
        </p>

        <div class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Gas In (Dari)</label>
            <input type="date"
                   x-model="downloadFilters.tanggal_dari"
                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-green-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Gas In (Sampai)</label>
            <input type="date"
                   x-model="downloadFilters.tanggal_sampai"
                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-green-500">
          </div>
        </div>

        <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded">
          <p class="text-xs text-blue-700">
            <i class="fas fa-info-circle mr-1"></i>
            Filter berdasarkan <strong>Tanggal Gas In</strong>. Kosongkan untuk download semua foto MGRT.
          </p>
        </div>
      </div>

      {{-- Step 2: Preview --}}
      <div x-show="downloadStep === 'preview'" class="mb-6">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <p class="font-semibold text-gray-800">
              Total File:
              <span :class="previewData.length > 100 ? 'text-orange-600' : 'text-green-600'" x-text="previewData.length"></span>
            </p>
            <p class="text-xs text-gray-500" x-show="downloadFilters.tanggal_dari || downloadFilters.tanggal_sampai">
              Periode:
              <span x-text="downloadFilters.tanggal_dari || '...'"></span> s/d
              <span x-text="downloadFilters.tanggal_sampai || '...'"></span>
            </p>
          </div>
          <button @click="downloadStep = 'filter'" class="text-sm text-blue-600 hover:text-blue-800">
            <i class="fas fa-edit mr-1"></i>Ubah Filter
          </button>
        </div>

        {{-- Warning untuk file count besar --}}
        <div x-show="previewData.length > 100" class="mb-3 p-3 bg-orange-50 border border-orange-200 rounded">
          <p class="text-xs text-orange-700">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <strong>Perhatian:</strong> Download dalam jumlah besar (<span x-text="previewData.length"></span> file) dapat memakan waktu lama (3-5 menit) dan berisiko timeout. Mohon tunggu hingga proses selesai.
          </p>
        </div>

        <div class="max-h-96 overflow-y-auto border rounded">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 sticky top-0">
              <tr>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reff ID</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama Customer</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama File</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <template x-for="(item, index) in previewData" :key="index">
                <tr class="hover:bg-gray-50">
                  <td class="px-3 py-2 text-sm text-gray-700" x-text="index + 1"></td>
                  <td class="px-3 py-2 text-sm font-medium text-blue-600" x-text="item.reff_id"></td>
                  <td class="px-3 py-2 text-sm text-gray-700" x-text="item.nama_pelanggan"></td>
                  <td class="px-3 py-2 text-sm text-gray-500" x-text="item.tanggal_gas_in"></td>
                  <td class="px-3 py-2 text-xs text-gray-600 font-mono" x-text="item.filename"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>

      {{-- Action Buttons --}}
      <div class="flex gap-3">
        <button @click="closeDownloadModal()"
                :disabled="downloadLoading"
                class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
          <i class="fas fa-times mr-2"></i>Batal
        </button>
        <button x-show="downloadStep === 'filter'"
                @click="loadPreview()"
                :disabled="previewLoading"
                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
          <span x-show="!previewLoading"><i class="fas fa-eye mr-2"></i>Preview</span>
          <span x-show="previewLoading"><i class="fas fa-spinner fa-spin mr-2"></i>Loading...</span>
        </button>
        <button x-show="downloadStep === 'preview'"
                @click="executeDownload()"
                :disabled="downloadLoading"
                class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50">
          <span x-show="!downloadLoading"><i class="fas fa-download mr-2"></i>Download ZIP</span>
          <span x-show="downloadLoading"><i class="fas fa-spinner fa-spin mr-2"></i>Downloading...</span>
        </button>
      </div>
    </div>
  </div>

  {{-- Loading Overlay saat Download --}}
  <div x-show="downloadLoading"
       x-cloak
       class="fixed inset-0 bg-black bg-opacity-75 z-[60] flex items-center justify-center"
       style="display: none;">
    <div class="bg-white rounded-xl p-8 max-w-md w-full mx-4 shadow-2xl text-center relative">
      {{-- Close button (only after 5 seconds) --}}
      <button @click="downloadLoading = false; closeDownloadModal();"
              class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors"
              title="Tutup (jika download sudah selesai)">
        <i class="fas fa-times text-xl"></i>
      </button>

      <div class="mb-6">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <i class="fas fa-download text-green-600 text-3xl animate-bounce"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Sedang Mengunduh...</h3>
        <p class="text-gray-600 text-sm mb-4">
          Proses download sedang berlangsung. Harap tunggu dan <strong>jangan tutup halaman ini</strong>.
        </p>

        {{-- Progress info --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
          <div class="flex items-start text-left text-xs text-blue-700 space-y-2">
            <div class="flex-shrink-0 mr-2">
              <i class="fas fa-info-circle"></i>
            </div>
            <div>
              <p class="mb-2">
                <i class="fas fa-check text-green-600 mr-1"></i> Mengunduh <span class="font-semibold" x-text="previewData.length"></span> foto dari Google Drive
              </p>
              <p class="mb-2">
                <i class="fas fa-check text-green-600 mr-1"></i> Membuat file ZIP dengan penamaan custom
              </p>
              <p>
                <i class="fas fa-clock text-orange-600 mr-1"></i> Estimasi waktu:
                <span x-text="previewData.length > 100 ? '3-5 menit' : previewData.length > 50 ? '1-3 menit' : '< 1 menit'"></span>
              </p>
            </div>
          </div>
        </div>

        {{-- Loading spinner --}}
        <div class="flex items-center justify-center space-x-2 mb-4">
          <div class="w-3 h-3 bg-green-600 rounded-full animate-pulse"></div>
          <div class="w-3 h-3 bg-green-600 rounded-full animate-pulse" style="animation-delay: 0.2s"></div>
          <div class="w-3 h-3 bg-green-600 rounded-full animate-pulse" style="animation-delay: 0.4s"></div>
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

  {{-- Export Excel Modal --}}
  <div x-show="showExportModal"
       x-cloak
       @click.self="closeExportModal()"
       class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
       style="display: none;">
    <div @click.stop class="bg-white rounded-xl p-6 w-full mx-4 shadow-2xl" :class="exportStep === 'preview' ? 'max-w-4xl' : 'max-w-md'">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
          <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center mr-3">
            <i class="fas fa-file-excel text-emerald-600 text-xl"></i>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Export ke Excel</h3>
            <p class="text-sm text-gray-600" x-text="exportStep === 'filter' ? 'Pilih Filter Data' : 'Preview Data Export'"></p>
          </div>
        </div>
        <button @click="closeExportModal()" class="text-gray-400 hover:text-gray-600">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>

      {{-- Step 1: Filter --}}
      <div x-show="exportStep === 'filter'" class="mb-6">
        <p class="text-sm text-gray-600 mb-4">
          Export data Gas In dengan kolom lengkap termasuk informasi pelanggan, MGRT, dan link foto.
        </p>

        <div class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Gas In (Dari)</label>
            <input type="date"
                   x-model="exportFilters.tanggal_dari"
                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Gas In (Sampai)</label>
            <input type="date"
                   x-model="exportFilters.tanggal_sampai"
                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status (Opsional)</label>
            <select x-model="exportFilters.module_status"
                    class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-emerald-500">
              <option value="">Semua Status</option>
              <option value="draft">Draft</option>
              <option value="ai_validation">AI Validation</option>
              <option value="tracer_review">Tracer Review</option>
              <option value="cgp_review">CGP Review</option>
              <option value="completed">Completed</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
        </div>

        <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded">
          <p class="text-xs text-blue-700">
            <i class="fas fa-info-circle mr-1"></i>
            Filter berdasarkan <strong>Tanggal Gas In</strong>. Kosongkan untuk export semua data.
          </p>
        </div>
      </div>

      {{-- Step 2: Preview --}}
      <div x-show="exportStep === 'preview'" class="mb-6">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <p class="font-semibold text-gray-800">
              Total Data: <span class="text-emerald-600" x-text="exportPreviewData.length"></span> records
            </p>
            <p class="text-xs text-gray-500" x-show="exportFilters.tanggal_dari || exportFilters.tanggal_sampai">
              Periode:
              <span x-text="exportFilters.tanggal_dari || '...'"></span> s/d
              <span x-text="exportFilters.tanggal_sampai || '...'"></span>
            </p>
          </div>
          <button @click="exportStep = 'filter'" class="text-sm text-blue-600 hover:text-blue-800">
            <i class="fas fa-edit mr-1"></i>Ubah Filter
          </button>
        </div>

        <div class="max-h-96 overflow-y-auto border rounded">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 sticky top-0">
              <tr>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reff ID</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama Customer</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kelurahan</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Gas In</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">No Seri MGRT</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <template x-for="(item, index) in exportPreviewData" :key="index">
                <tr class="hover:bg-gray-50">
                  <td class="px-3 py-2 text-sm text-gray-700" x-text="index + 1"></td>
                  <td class="px-3 py-2 text-sm font-medium text-blue-600" x-text="item.reff_id"></td>
                  <td class="px-3 py-2 text-sm text-gray-700" x-text="item.nama_pelanggan"></td>
                  <td class="px-3 py-2 text-sm text-gray-500" x-text="item.kelurahan"></td>
                  <td class="px-3 py-2 text-sm text-gray-500" x-text="item.tanggal_gas_in"></td>
                  <td class="px-3 py-2 text-sm text-gray-600" x-text="item.no_seri_mgrt"></td>
                  <td class="px-3 py-2 text-xs">
                    <span class="px-2 py-1 rounded-full text-xs"
                          :class="{
                            'bg-green-100 text-green-800': item.module_status === 'completed',
                            'bg-blue-100 text-blue-800': item.module_status === 'tracer_review' || item.module_status === 'cgp_review',
                            'bg-gray-100 text-gray-800': item.module_status === 'draft',
                            'bg-red-100 text-red-800': item.module_status === 'rejected'
                          }"
                          x-text="item.module_status"></span>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>

      {{-- Action Buttons --}}
      <div class="flex gap-3">
        <button @click="closeExportModal()"
                class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
          <i class="fas fa-times mr-2"></i>Batal
        </button>
        <button x-show="exportStep === 'filter'"
                @click="loadExportPreview()"
                :disabled="exportPreviewLoading"
                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
          <span x-show="!exportPreviewLoading"><i class="fas fa-eye mr-2"></i>Preview</span>
          <span x-show="exportPreviewLoading"><i class="fas fa-spinner fa-spin mr-2"></i>Loading...</span>
        </button>
        <button x-show="exportStep === 'preview'"
                @click="executeExport()"
                class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
          <i class="fas fa-file-excel mr-2"></i>Export Excel
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Rejection Popup --}}
<div id="rejection-popup-container" class="hidden fixed w-96 bg-white border border-red-200 rounded-lg shadow-xl z-[9999] max-h-96 overflow-y-auto"
     onmouseenter="keepPopupOpen(window.currentGasInId)"
     onmouseleave="hideRejectionPopup(window.currentGasInId)">
  <div class="sticky top-0 bg-red-50 px-3 py-2 border-b border-red-200">
    <h3 class="text-xs font-semibold text-red-800">Rejection Details</h3>
  </div>
  <div id="rejection-popup-content" class="p-3">
    <!-- Content loaded via JS -->
  </div>
</div>

{{-- Delete Modal (unchanged, kept for consistency) --}}
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center transition-opacity duration-300">
  <div id="deleteModalContent" class="bg-white rounded-xl p-6 max-w-md w-full mx-4 shadow-2xl transform transition-all duration-300 scale-95 opacity-0">
    <div class="flex items-center mb-4">
      <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
      </div>
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Konfirmasi Hapus</h3>
        <p class="text-sm text-gray-600">Tindakan ini tidak dapat dibatalkan</p>
      </div>
    </div>

    <div class="mb-6">
      <p class="text-gray-700">
        Apakah Anda yakin ingin menghapus Gas In dengan Reff ID:
        <span id="deleteReffId" class="font-semibold text-red-600"></span>?
      </p>
      <p class="text-sm text-gray-500 mt-2">
        Semua data terkait termasuk foto dan approval history akan terhapus permanent.
      </p>
    </div>

    <div class="flex gap-3">
      <button onclick="closeDeleteModal()"
              class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
        <i class="fas fa-times mr-2"></i>Batal
      </button>
      <button id="deleteButton" onclick="executeDelete()"
              class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
        <i class="fas fa-trash mr-2"></i>Hapus
      </button>
    </div>
  </div>
</div>

@push('scripts')
<script>
function gasInIndexData() {
    return {
        items: @json($gasIn->items() ?? []),
        pagination: {
            current_page: @json($gasIn->currentPage() ?? 1),
            last_page: @json($gasIn->lastPage() ?? 1),
            per_page: @json($gasIn->perPage() ?? 15),
            total: @json($gasIn->total() ?? 0),
            from: @json($gasIn->firstItem() ?? 0),
            to: @json($gasIn->lastItem() ?? 0)
        },
        filters: {
            q: '{{ request("q") }}',
            module_status: '{{ request("module_status") }}',
            tanggal_dari: '{{ request("tanggal_dari") }}',
            tanggal_sampai: '{{ request("tanggal_sampai") }}'
        },
        stats: {
            total: {{ $gasIn->total() ?? 0 }},
            draft: {{ $gasIn->where('module_status', 'draft')->count() ?? 0 }},
            ready: {{ $gasIn->where('module_status', 'tracer_review')->count() ?? 0 }},
            completed: {{ $gasIn->where('module_status', 'completed')->count() ?? 0 }}
        },
        loading: false,
        showDownloadModal: false,
        downloadStep: 'filter', // 'filter' | 'preview'
        downloadLoading: false,
        previewLoading: false,
        downloadFilters: {
            tanggal_dari: '',
            tanggal_sampai: ''
        },
        previewData: [],
        showExportModal: false,
        exportStep: 'filter', // 'filter' | 'preview'
        exportPreviewLoading: false,
        exportFilters: {
            tanggal_dari: '',
            tanggal_sampai: '',
            module_status: ''
        },
        exportPreviewData: [],

        async fetchData(resetPage = false) {
            // Reset pagination when filters change
            if (resetPage) {
                this.pagination.current_page = 1;
            }

            this.loading = true;

            try {
                const params = new URLSearchParams({
                    q: this.filters.q,
                    module_status: this.filters.module_status,
                    tanggal_dari: this.filters.tanggal_dari,
                    tanggal_sampai: this.filters.tanggal_sampai,
                    page: this.pagination.current_page,
                    ajax: 1
                });

                const response = await fetch(`{{ route('gas-in.index') }}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.items = data.data.data || [];
                    this.pagination = {
                        current_page: data.data.current_page,
                        last_page: data.data.last_page,
                        per_page: data.data.per_page,
                        total: data.data.total,
                        from: data.data.from,
                        to: data.data.to
                    };
                    this.stats = data.stats || this.stats;

                    // Smart pagination: if current page > last page, reset to page 1
                    if (this.pagination.current_page > this.pagination.last_page && this.pagination.last_page > 0) {
                        this.pagination.current_page = 1;
                        this.fetchData(); // Refetch with correct page
                    }
                }
            } catch (error) {
                console.error('Error fetching Gas In data:', error);
            } finally {
                this.loading = false;
            }
        },

        resetFilters() {
            this.filters = {
                q: '',
                module_status: '',
                tanggal_dari: '',
                tanggal_sampai: ''
            };
            this.fetchData(true);
        },

        resetDateFilter() {
            this.filters.tanggal_dari = '';
            this.filters.tanggal_sampai = '';
            this.fetchData(true);
        },

        formatDate(date) {
            if (!date) return '-';
            const d = new Date(date);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
        },

        getStatusClass(status) {
            const classes = {
                'not_started': 'bg-gray-100 text-gray-700',
                'draft': 'bg-gray-100 text-gray-700',
                'ai_validation': 'bg-purple-100 text-purple-800',
                'tracer_review': 'bg-blue-100 text-blue-800',
                'cgp_review': 'bg-yellow-100 text-yellow-800',
                'completed': 'bg-green-100 text-green-800',
                'rejected': 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-700';
        },

        getStatusText(status) {
            const statusMap = {
                'not_started': 'Not Started',
                'draft': 'Draft',
                'ai_validation': 'AI Validation',
                'tracer_review': 'Tracer Review',
                'cgp_review': 'CGP Review',
                'completed': 'Completed',
                'rejected': 'Rejected'
            };
            return statusMap[status] || status?.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },

        canEdit(row) {
            const displayStatus = row.module_status || row.status;
            return ['draft', 'ai_validation', 'tracer_review', 'rejected'].includes(displayStatus);
        },

        confirmDelete(id, reffId) {
            window.deleteId = id;
            document.getElementById('deleteReffId').textContent = reffId;
            showDeleteModal();
        },

        // Pagination methods
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
                this.fetchData();
            }
        },

        previousPage() {
            if (this.pagination.current_page > 1) {
                this.pagination.current_page--;
                this.fetchData();
            }
        },

        nextPage() {
            if (this.pagination.current_page < this.pagination.last_page) {
                this.pagination.current_page++;
                this.fetchData();
            }
        },

        savePageState() {
            // Save current page and filters to sessionStorage
            const state = {
                page: this.pagination.current_page,
                filters: this.filters,
                timestamp: Date.now()
            };
            sessionStorage.setItem('gasInIndexPageState', JSON.stringify(state));
        },

        restorePageState() {
            // Restore page state from sessionStorage
            const savedState = sessionStorage.getItem('gasInIndexPageState');
            if (savedState) {
                try {
                    const state = JSON.parse(savedState);
                    // Only restore if saved within last 30 minutes
                    if (Date.now() - state.timestamp < 30 * 60 * 1000) {
                        this.pagination.current_page = state.page || 1;
                        this.filters = state.filters || this.filters;
                        this.fetchData();
                        // Clear the saved state after restoring
                        sessionStorage.removeItem('gasInIndexPageState');
                    }
                } catch (error) {
                    console.error('Failed to restore pagination state:', error);
                    sessionStorage.removeItem('gasInIndexPageState');
                }
            }
        },

        initPaginationState() {
            // Check if we're returning from detail page
            this.restorePageState();
        },

        async loadPreview() {
            this.previewLoading = true;
            this.previewData = [];

            try {
                const params = new URLSearchParams();
                if (this.downloadFilters.tanggal_dari) {
                    params.append('tanggal_dari', this.downloadFilters.tanggal_dari);
                }
                if (this.downloadFilters.tanggal_sampai) {
                    params.append('tanggal_sampai', this.downloadFilters.tanggal_sampai);
                }

                const url = '{{ route("gas-in.preview-foto-regulator") }}' + (params.toString() ? '?' + params.toString() : '');

                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.previewData = data.data;
                    this.downloadStep = 'preview';
                } else {
                    alert(data.message || 'Tidak ada data yang ditemukan.');
                }

            } catch (error) {
                console.error('Preview error:', error);
                alert('Gagal memuat preview data. Silakan coba lagi.');
            } finally {
                this.previewLoading = false;
            }
        },

        async executeDownload() {
            this.downloadLoading = true;

            try {
                const params = new URLSearchParams();
                if (this.downloadFilters.tanggal_dari) {
                    params.append('tanggal_dari', this.downloadFilters.tanggal_dari);
                }
                if (this.downloadFilters.tanggal_sampai) {
                    params.append('tanggal_sampai', this.downloadFilters.tanggal_sampai);
                }

                const url = '{{ route("gas-in.download-foto-regulator") }}' + (params.toString() ? '?' + params.toString() : '');

                // Create a temporary link and trigger download
                const link = document.createElement('a');
                link.href = url;
                link.download = 'Foto_MGRT_' + new Date().toISOString().slice(0, 10) + '.zip';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Estimasi waktu tunggu berdasarkan jumlah file
                const estimatedTime = this.previewData.length > 100 ? 180000 : // 3 menit
                                     this.previewData.length > 50 ? 90000 :   // 1.5 menit
                                     30000; // 30 detik

                // Close modal & loading setelah estimasi waktu
                // User bisa close manual jika download sudah selesai lebih cepat
                setTimeout(() => {
                    this.downloadLoading = false;
                    this.closeDownloadModal();
                }, estimatedTime);

            } catch (error) {
                console.error('Download error:', error);
                alert('Terjadi kesalahan saat mengunduh file. Silakan coba lagi.');
                this.downloadLoading = false;
            }
        },

        closeDownloadModal() {
            this.showDownloadModal = false;
            this.downloadStep = 'filter';
            this.previewData = [];
            this.downloadFilters.tanggal_dari = '';
            this.downloadFilters.tanggal_sampai = '';
        },

        async loadExportPreview() {
            this.exportPreviewLoading = true;
            this.exportPreviewData = [];

            try {
                const params = new URLSearchParams();
                if (this.exportFilters.tanggal_dari) {
                    params.append('tanggal_dari', this.exportFilters.tanggal_dari);
                }
                if (this.exportFilters.tanggal_sampai) {
                    params.append('tanggal_sampai', this.exportFilters.tanggal_sampai);
                }
                if (this.exportFilters.module_status) {
                    params.append('module_status', this.exportFilters.module_status);
                }
                if (this.filters.q) {
                    params.append('search', this.filters.q);
                }

                const url = '{{ route("gas-in.preview-export-excel") }}' + (params.toString() ? '?' + params.toString() : '');

                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.exportPreviewData = data.data;
                    this.exportStep = 'preview';
                } else {
                    alert(data.message || 'Tidak ada data yang ditemukan.');
                }

            } catch (error) {
                console.error('Export preview error:', error);
                alert('Gagal memuat preview data. Silakan coba lagi.');
            } finally {
                this.exportPreviewLoading = false;
            }
        },

        executeExport() {
            // Build URL dengan query params
            const params = new URLSearchParams();

            if (this.exportFilters.tanggal_dari) {
                params.append('tanggal_dari', this.exportFilters.tanggal_dari);
            }
            if (this.exportFilters.tanggal_sampai) {
                params.append('tanggal_sampai', this.exportFilters.tanggal_sampai);
            }
            if (this.exportFilters.module_status) {
                params.append('module_status', this.exportFilters.module_status);
            }
            if (this.filters.q) {
                params.append('search', this.filters.q);
            }

            const url = '{{ route("gas-in.export-excel") }}' + (params.toString() ? '?' + params.toString() : '');

            // Trigger download
            window.location.href = url;

            // Close modal after short delay
            setTimeout(() => {
                this.closeExportModal();
            }, 500);
        },

        closeExportModal() {
            this.showExportModal = false;
            this.exportStep = 'filter';
            this.exportPreviewData = [];
            this.exportFilters.tanggal_dari = '';
            this.exportFilters.tanggal_sampai = '';
            this.exportFilters.module_status = '';
        },

        async downloadFotoMGRT(reffId, namaCustomer, tanggalGasIn) {
            try {
                // Build query params
                const params = new URLSearchParams();
                params.append('reff_id', reffId);

                const url = '{{ route("gas-in.download-single-foto-mgrt") }}?' + params.toString();

                // Show loading indicator (optional)
                console.log('Downloading foto MGRT for:', reffId);

                // Create temporary link and trigger download
                const link = document.createElement('a');
                link.href = url;

                // Format nama file: {reff_id}_{nama_customer}_{tanggal}_MGRT.jpg
                const customerSlug = namaCustomer ? namaCustomer.replace(/[^a-z0-9]/gi, '_') : 'Customer';
                const tanggal = tanggalGasIn ? tanggalGasIn.replace(/[^0-9]/g, '') : '';
                link.download = `${reffId}_${customerSlug}_${tanggal}_MGRT.jpg`;

                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

            } catch (error) {
                console.error('Error downloading foto MGRT:', error);
                alert('Gagal mendownload foto MGRT. Silakan coba lagi.');
            }
        },
    }
}

// Delete modal functions (kept from original)
let deleteId = null;
let isDeleting = false;

function showDeleteModal() {
  const modal = document.getElementById('deleteModal');
  const modalContent = document.getElementById('deleteModalContent');
  modal.classList.remove('hidden');
  setTimeout(() => {
    modalContent.classList.remove('scale-95', 'opacity-0');
    modalContent.classList.add('scale-100', 'opacity-100');
  }, 10);
}

function closeDeleteModal() {
  if (isDeleting) return;
  const modal = document.getElementById('deleteModal');
  const modalContent = document.getElementById('deleteModalContent');
  modalContent.classList.remove('scale-100', 'opacity-100');
  modalContent.classList.add('scale-95', 'opacity-0');
  setTimeout(() => {
    modal.classList.add('hidden');
    window.deleteId = null;
  }, 300);
}

async function executeDelete() {
  if (!window.deleteId || isDeleting) return;

  isDeleting = true;
  const deleteButton = document.getElementById('deleteButton');
  const originalContent = deleteButton.innerHTML;

  deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menghapus...';
  deleteButton.disabled = true;

  try {
    const response = await fetch(`/gas-in/${window.deleteId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      }
    });

    const result = await response.json();

    if (result.success) {
      closeDeleteModal();
      // Trigger Alpine.js to refetch data
      Alpine.store('refreshSk', true);
      setTimeout(() => location.reload(), 500);
    } else {
      throw new Error('Delete failed');
    }
  } catch (error) {
    console.error('Delete error:', error);
    deleteButton.innerHTML = originalContent;
    deleteButton.disabled = false;
    alert('Terjadi kesalahan saat menghapus data. Silakan coba lagi.');
  } finally {
    isDeleting = false;
  }
}

// Close modal handlers
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
  if (e.target === this && !isDeleting) {
    closeDeleteModal();
  }
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape' && !isDeleting) {
    closeDeleteModal();
  }
});

// Rejection Details Hover Popup
const loadedRejections = new Set();
const hideTimers = {};
window.currentGasInId = null;

function showRejectionPopup(gasInId, triggerElement) {
  // Clear any existing timer
  if (hideTimers[gasInId]) {
    clearTimeout(hideTimers[gasInId]);
    delete hideTimers[gasInId];
  }

  const popup = document.getElementById('rejection-popup-container');
  const contentEl = document.getElementById('rejection-popup-content');

  if (!popup) return;

  // Store current Gas In ID globally
  window.currentGasInId = gasInId;

  // Get trigger position
  const triggerRect = triggerElement.getBoundingClientRect();
  const popupHeight = 400;
  const viewportHeight = window.innerHeight;
  const viewportWidth = window.innerWidth;

  // Calculate horizontal position (position to the right of trigger)
  let leftPos = triggerRect.right + 8; // 8px spacing from trigger
  // Ensure popup doesn't overflow right edge
  if (leftPos + 384 > viewportWidth) {
    leftPos = triggerRect.left - 384 - 8; // Show on left side if no space on right
  }

  // Calculate vertical position (align with trigger top)
  let topPos = triggerRect.top;

  // Adjust if popup would overflow bottom
  if (topPos + popupHeight > viewportHeight) {
    topPos = viewportHeight - popupHeight - 20;
  }

  // Adjust if popup would overflow top
  if (topPos < 20) {
    topPos = 20;
  }

  // Position popup
  popup.style.left = `${leftPos}px`;
  popup.style.top = `${topPos}px`;
  popup.classList.remove('hidden');

  // Load data if not loaded yet
  if (!loadedRejections.has(gasInId)) {
    // Reset content
    contentEl.innerHTML = '<div class="flex items-center justify-center py-4 text-xs text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Loading...</div>';
    loadRejectionPopup(gasInId);
  }
}

function hideRejectionPopup(gasInId) {
  // Set timer specific to this popup
  hideTimers[gasInId] = setTimeout(() => {
    const popup = document.getElementById('rejection-popup-container');
    if (popup && window.currentGasInId === gasInId) {
      popup.classList.add('hidden');
      window.currentGasInId = null;
    }
    delete hideTimers[gasInId];
  }, 200);
}

function keepPopupOpen(gasInId) {
  if (hideTimers[gasInId]) {
    clearTimeout(hideTimers[gasInId]);
    delete hideTimers[gasInId];
  }
}

async function loadRejectionPopup(gasInId) {
  const contentDiv = document.getElementById('rejection-popup-content');
  if (!contentDiv) return;

  try {
    const response = await fetch(`/gas-in/${gasInId}/rejection-details`, {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
      }
    });

    if (!response.ok) throw new Error('Failed to load rejection details');

    const data = await response.json();

    if (data.success && data.rejections && data.rejections.length > 0) {
      let html = '<div class="space-y-2">';

      data.rejections.forEach((rejection) => {
        const rejectedBy = rejection.rejected_by_type === 'tracer' ? 'Tracer' : 'CGP';
        const badgeColor = rejection.rejected_by_type === 'tracer' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700';

        html += `
          <div class="border-l-2 ${rejection.rejected_by_type === 'tracer' ? 'border-blue-400' : 'border-orange-400'} pl-2 py-2">
            <div class="flex items-start justify-between mb-1">
              <div class="font-medium text-xs text-gray-900">${rejection.slot_label}</div>
              <span class="px-1.5 py-0.5 rounded text-xs font-medium ${badgeColor}">${rejectedBy}</span>
            </div>
            <div class="text-xs text-gray-600 mb-1">${rejection.reason || 'No reason provided'}</div>
            <div class="flex items-center justify-between text-xs text-gray-500">
              <span>${rejection.rejected_date}</span>
              ${rejection.rejected_by_name ? `<span>${rejection.rejected_by_name}</span>` : ''}
            </div>
          </div>
        `;
      });

      html += '</div>';
      contentDiv.innerHTML = html;
      loadedRejections.add(gasInId);
    } else {
      contentDiv.innerHTML = '<div class="text-xs text-gray-500 text-center py-2">No rejections found</div>';
    }
  } catch (error) {
    console.error('Error loading rejection details:', error);
    contentDiv.innerHTML = '<div class="text-xs text-red-500 text-center py-2">Failed to load</div>';
  }
}
</script>
@endpush

@endsection
