@extends('layouts.app')

@section('title', 'Data PILOT - AERGAS')

@section('content')
<style>
  /* Auto-width table cells - fit content without truncation */
  .pilot-table {
    width: auto;
    min-width: 100%;
    table-layout: auto;
    white-space: nowrap;
  }

  .pilot-table th,
  .pilot-table td {
    padding: 0.5rem 0.75rem;
    white-space: nowrap;
    overflow: visible;
  }

  /* Ensure all cells stay on one line - no wrapping */
  .pilot-table .no-wrap {
    white-space: nowrap;
  }

  /* Border separators for column groups */
  .group-separator {
    border-left: 3px solid #d1d5db !important;
  }

  /* Column group colors for sub-headers */
  .group-material-sk {
    background-color: #dbeafe !important;
    color: #1e40af !important;
  }

  .group-material-sr {
    background-color: #dcfce7 !important;
    color: #15803d !important;
  }

  .group-evidence-sk {
    background-color: #f3e8ff !important;
    color: #7e22ce !important;
  }

  .group-evidence-sr {
    background-color: #e0e7ff !important;
    color: #4338ca !important;
  }

  .group-evidence-mgrt {
    background-color: #fed7aa !important;
    color: #c2410c !important;
  }

  .group-evidence-gasin {
    background-color: #fecaca !important;
    color: #b91c1c !important;
  }

  .group-review-cgp {
    background-color: #ccfbf1 !important;
    color: #0f766e !important;
  }

  .group-dokumen {
    background-color: #e5e7eb !important;
    color: #374151 !important;
  }

  /* Sort indicator styles */
  .sort-indicator {
    display: inline-block;
    margin-left: 4px;
    font-size: 0.75rem;
    opacity: 0.5;
  }

  .sort-indicator.active {
    opacity: 1;
  }

  th.sortable {
    cursor: pointer;
    user-select: none;
  }

  th.sortable:hover {
    background-color: #f3f4f6;
  }

  /* Filter input styles */
  .filter-input {
    width: 100%;
    min-width: 80px;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
  }

  .filter-input:focus {
    outline: none;
    border-color: #3b82f6;
    ring: 2px;
    ring-color: rgba(59, 130, 246, 0.5);
  }

  /* Hide rows that are not in current page */
  .pagination-hidden {
    display: none !important;
  }
</style>

<div class="space-y-6">

  <div class="flex items-center gap-4">
    <a href="{{ route('pilot-comparison.index') }}" class="text-gray-600 hover:text-gray-800">
      <i class="fas fa-arrow-left mr-2"></i>Kembali
    </a>
    <div class="flex-1">
      <h1 class="text-3xl font-bold text-gray-800">Data PILOT</h1>
      <p class="text-gray-600 mt-1">Batch ID: <span class="font-mono text-sm">{{ $batch }}</span></p>
    </div>
    <div class="flex gap-2">
      <form action="{{ route('pilot-comparison.compare', $batch) }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
          <i class="fas fa-balance-scale mr-2"></i>Compare with Database
        </button>
      </form>
      <a href="{{ route('pilot-comparison.export', $batch) }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
        <i class="fas fa-download mr-2"></i>Export Excel
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
      <span class="block sm:inline">{{ session('success') }}</span>
    </div>
  @endif

  @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
      <span class="block sm:inline">{{ session('error') }}</span>
    </div>
  @endif

  {{-- Comparison Results --}}
  @if(session('comparison_results'))
    @php
      $results = session('comparison_results');
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      {{-- Without Reff ID --}}
      <div class="bg-white rounded-xl p-6 card-shadow">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Belum Ada Reff ID</p>
            <p class="text-3xl font-bold text-orange-600">{{ count($results['without_reff_id']) }}</p>
          </div>
          <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
            <i class="fas fa-id-badge text-orange-600 text-xl"></i>
          </div>
        </div>
      </div>

      {{-- New Customers --}}
      <div class="bg-white rounded-xl p-6 card-shadow">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Pelanggan Baru</p>
            <p class="text-3xl font-bold text-purple-600">{{ count($results['new_customers']) }}</p>
          </div>
          <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
            <i class="fas fa-user-plus text-purple-600 text-xl"></i>
          </div>
        </div>
      </div>

      {{-- Incomplete Installation (Total) --}}
      <div class="bg-white rounded-xl p-6 card-shadow">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Instalasi Belum Lengkap</p>
            <p class="text-3xl font-bold text-yellow-600">
              {{ count($results['incomplete_installation']) }}
            </p>
          </div>
          <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
            <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
          </div>
        </div>
      </div>

      {{-- Date Differences --}}
      <div class="bg-white rounded-xl p-6 card-shadow">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Perbedaan Tanggal</p>
            <p class="text-3xl font-bold text-red-600">{{ count($results['date_differences']) }}</p>
          </div>
          <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
            <i class="fas fa-calendar-times text-red-600 text-xl"></i>
          </div>
        </div>
      </div>

      {{-- Total Records --}}
      <div class="bg-white rounded-xl p-6 card-shadow">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Total Records</p>
            <p class="text-3xl font-bold text-blue-600">{{ $summary->total ?? 0 }}</p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
            <i class="fas fa-database text-blue-600 text-xl"></i>
          </div>
        </div>
      </div>
    </div>

    {{-- Detailed Results --}}
    <div class="space-y-4">
      {{-- Without Reff ID Detail --}}
      @if(count($results['without_reff_id']) > 0)
        <div class="bg-white rounded-xl card-shadow">
          <div class="p-4 border-b bg-orange-50">
            <h3 class="text-lg font-semibold text-orange-800">
              <i class="fas fa-id-badge mr-2"></i>Data Belum Ada Reff ID ({{ count($results['without_reff_id']) }})
            </h3>
            <p class="text-sm text-orange-600 mt-1">Perlu generate reff_id baru</p>
          </div>
          <div class="p-4 max-h-96 overflow-y-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-2 text-left">Nama</th>
                  <th class="px-3 py-2 text-left">Alamat</th>
                  <th class="px-3 py-2 text-left">Action Needed</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                @foreach($results['without_reff_id'] as $item)
                  <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2">{{ $item['nama'] }}</td>
                    <td class="px-3 py-2">{{ $item['alamat'] }}</td>
                    <td class="px-3 py-2 text-orange-600">{{ $item['action_needed'] }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endif

      {{-- New Customers Detail --}}
      @if(count($results['new_customers']) > 0)
        @php
          $newCustomersData = json_encode($results['new_customers']);
        @endphp
        <div class="bg-white rounded-xl card-shadow" x-data="newCustomersFilter(@js($newCustomersData))">
          <div class="p-4 border-b bg-purple-50">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-semibold text-purple-800">
                  <i class="fas fa-user-plus mr-2"></i>Pelanggan Baru (<span x-text="filteredCount"></span>)
                </h3>
                <p class="text-sm text-purple-600 mt-1">Reff ID ada di PILOT tapi belum ada di database calon_pelanggan</p>
              </div>
              <div>
                <select x-model="filter" @change="updateTable()" class="px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-purple-500">
                  <option value="all">Semua</option>
                  <option value="no_dates">Belum Ada Tanggal</option>
                  <option value="has_dates">Sudah Ada Tanggal</option>
                  <option value="complete">Lengkap (SK, SR, Gas In)</option>
                </select>
              </div>
            </div>
          </div>
          <div class="p-4 max-h-96 overflow-y-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-2 text-left">Reff ID</th>
                  <th class="px-3 py-2 text-left">Nama</th>
                  <th class="px-3 py-2 text-left">Alamat</th>
                  <th class="px-3 py-2 text-center">Tgl SK</th>
                  <th class="px-3 py-2 text-center">Tgl SR</th>
                  <th class="px-3 py-2 text-center">Tgl Gas In</th>
                </tr>
              </thead>
              <tbody class="divide-y" x-html="tableHtml">
              </tbody>
            </table>
          </div>
        </div>
      @endif

      {{-- Incomplete Installation Detail (Gabungan dengan Filter) --}}
      @if(count($results['incomplete_installation']) > 0)
        @php
          $incompleteData = json_encode($results['incomplete_installation']);
        @endphp
        <div class="bg-white rounded-xl card-shadow" x-data="incompleteFilter(@js($incompleteData))">
          <div class="p-4 border-b bg-yellow-50">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-semibold text-yellow-800">
                  <i class="fas fa-exclamation-triangle mr-2"></i>Instalasi Belum Lengkap (<span x-text="filteredCount"></span>)
                </h3>
                <p class="text-sm text-yellow-600 mt-1">Reff ID ada di database, minimal 1 tanggal di PILOT, diurutkan dari tanggal terawal</p>
                <div class="mt-2 flex gap-2 text-xs">
                  <span><i class="fas fa-circle text-green-600"></i> Sama dengan database</span>
                  <span><i class="fas fa-circle text-yellow-600"></i> Belum ada di database</span>
                  <span><i class="fas fa-circle text-red-600"></i> Berbeda dengan database</span>
                </div>
              </div>
              <div>
                <select x-model="filter" @change="updateTable()" class="px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-yellow-500">
                  <option value="all">Semua</option>
                  <option value="kurang_sk">Kurang SK</option>
                  <option value="kurang_sr">Kurang SR</option>
                  <option value="kurang_gas_in">Kurang Gas In</option>
                  <option value="different">Perbedaan Tanggal</option>
                </select>
              </div>
            </div>
          </div>
          <div class="p-4 max-h-96 overflow-y-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-2 text-left">Reff ID</th>
                  <th class="px-3 py-2 text-left">Nama</th>
                  <th class="px-3 py-2 text-center">Tanggal SK (PILOT)</th>
                  <th class="px-3 py-2 text-center">Tanggal SR (PILOT)</th>
                  <th class="px-3 py-2 text-center">Tanggal Gas In (PILOT)</th>
                </tr>
              </thead>
              <tbody class="divide-y" x-html="tableHtml">
              </tbody>
            </table>
          </div>
        </div>
      @endif

    </div>
  @else
    {{-- Summary Card (shown when no comparison yet) --}}
    <div class="bg-white rounded-xl p-6 card-shadow">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">Total Records</p>
          <p class="text-3xl font-bold text-blue-600">{{ $summary->total ?? 0 }}</p>
        </div>
        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
          <i class="fas fa-database text-blue-600 text-2xl"></i>
        </div>
      </div>
      <div class="mt-4 text-sm text-gray-500">
        <i class="fas fa-info-circle mr-1"></i>Klik "Compare with Database" untuk melihat analisis perbandingan dengan database
      </div>
    </div>
  @endif

  {{-- PILOT Data Table with Realtime Filter & Sort --}}
  <div class="bg-white rounded-xl card-shadow" x-data="pilotTable()">
    {{-- Search Filter --}}
    <div class="p-4 border-b bg-gray-50">
      <div class="flex gap-3">
        <input type="text" x-model="globalSearch" @input="filterData()"
               placeholder="Cari di semua kolom (ID REFF, Nama, Alamat, dll)..."
               class="flex-1 px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
        <div class="text-sm text-gray-500 flex items-center">
          <span x-text="filteredCount"></span> / <span x-text="totalCount"></span> results
        </div>
      </div>
    </div>
    <div class="p-4 border-b">
      <h2 class="text-xl font-semibold text-gray-800">Detail Data PILOT (Semua Kolom)</h2>
      <p class="text-sm text-gray-500 mt-1">Scroll horizontal untuk melihat semua kolom. Klik header kolom untuk sorting.</p>
    </div>

    @if($allPilots->isEmpty())
      <div class="p-8 text-center text-gray-500">
        <i class="fas fa-inbox text-6xl mb-4"></i>
        <p class="text-lg">Tidak ada data.</p>
      </div>
    @else
      <div class="overflow-x-auto">
        <table class="pilot-table text-xs">
          <thead class="bg-gray-50 border-b sticky top-0">
            <tr>
              {{-- Basic Info --}}
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('id')">
                ID <span class="sort-indicator" :class="{'active': sortColumn === 'id'}" x-text="getSortIndicator('id')"></span>
              </th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('id_reff')">
                ID REFF <span class="sort-indicator" :class="{'active': sortColumn === 'id_reff'}" x-text="getSortIndicator('id_reff')"></span>
              </th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('nama')">
                Nama <span class="sort-indicator" :class="{'active': sortColumn === 'nama'}" x-text="getSortIndicator('nama')"></span>
              </th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('nomor_kartu_identitas')">
                No. KTP <span class="sort-indicator" :class="{'active': sortColumn === 'nomor_kartu_identitas'}" x-text="getSortIndicator('nomor_kartu_identitas')"></span>
              </th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('nomor_ponsel')">
                No. Ponsel <span class="sort-indicator" :class="{'active': sortColumn === 'nomor_ponsel'}" x-text="getSortIndicator('nomor_ponsel')"></span>
              </th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('alamat')">
                Alamat <span class="sort-indicator" :class="{'active': sortColumn === 'alamat'}" x-text="getSortIndicator('alamat')"></span>
              </th>
              <th class="px-3 py-2 text-center font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('rt')">
                RT <span class="sort-indicator" :class="{'active': sortColumn === 'rt'}" x-text="getSortIndicator('rt')"></span>
              </th>
              <th class="px-3 py-2 text-center font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('rw')">
                RW <span class="sort-indicator" :class="{'active': sortColumn === 'rw'}" x-text="getSortIndicator('rw')"></span>
              </th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('id_kota_kab')">
                Kota/Kab <span class="sort-indicator" :class="{'active': sortColumn === 'id_kota_kab'}" x-text="getSortIndicator('id_kota_kab')"></span>
              </th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('id_kecamatan')">
                Kecamatan <span class="sort-indicator" :class="{'active': sortColumn === 'id_kecamatan'}" x-text="getSortIndicator('id_kecamatan')"></span>
              </th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('id_kelurahan')">
                Kelurahan <span class="sort-indicator" :class="{'active': sortColumn === 'id_kelurahan'}" x-text="getSortIndicator('id_kelurahan')"></span>
              </th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('padukuhan')">
                Padukuhan <span class="sort-indicator" :class="{'active': sortColumn === 'padukuhan'}" x-text="getSortIndicator('padukuhan')"></span>
              </th>

              {{-- Status --}}
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('penetrasi_pengembangan')">
                Penetrasi/Pengembangan <span class="sort-indicator" :class="{'active': sortColumn === 'penetrasi_pengembangan'}" x-text="getSortIndicator('penetrasi_pengembangan')"></span>
              </th>

              {{-- Tanggal --}}
              <th class="px-3 py-2 text-center font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('tanggal_terpasang_sk')">
                Tgl SK <span class="sort-indicator" :class="{'active': sortColumn === 'tanggal_terpasang_sk'}" x-text="getSortIndicator('tanggal_terpasang_sk')"></span>
              </th>
              <th class="px-3 py-2 text-center font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('tanggal_terpasang_sr')">
                Tgl SR <span class="sort-indicator" :class="{'active': sortColumn === 'tanggal_terpasang_sr'}" x-text="getSortIndicator('tanggal_terpasang_sr')"></span>
              </th>
              <th class="px-3 py-2 text-center font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('tanggal_terpasang_gas_in')">
                Tgl GAS IN <span class="sort-indicator" :class="{'active': sortColumn === 'tanggal_terpasang_gas_in'}" x-text="getSortIndicator('tanggal_terpasang_gas_in')"></span>
              </th>

              {{-- Keterangan --}}
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('keterangan')">
                Keterangan <span class="sort-indicator" :class="{'active': sortColumn === 'keterangan'}" x-text="getSortIndicator('keterangan')"></span>
              </th>
              <th class="px-3 py-2 text-center font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('batal')">
                Batal <span class="sort-indicator" :class="{'active': sortColumn === 'batal'}" x-text="getSortIndicator('batal')"></span>
              </th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('keterangan_batal')">
                Ket. Batal <span class="sort-indicator" :class="{'active': sortColumn === 'keterangan_batal'}" x-text="getSortIndicator('keterangan_batal')"></span>
              </th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase whitespace-nowrap sortable" @click="sortData('anomali')">
                Anomali <span class="sort-indicator" :class="{'active': sortColumn === 'anomali'}" x-text="getSortIndicator('anomali')"></span>
              </th>

              {{-- Material SK (9 items) --}}
              <th class="px-3 py-2 text-center font-semibold text-blue-600 uppercase whitespace-nowrap group-separator" colspan="9">Material SK</th>

              {{-- Material SR (15 items) --}}
              <th class="px-3 py-2 text-center font-semibold text-green-600 uppercase whitespace-nowrap group-separator" colspan="15">Material SR</th>

              {{-- Evidence SK (5) --}}
              <th class="px-3 py-2 text-center font-semibold text-purple-600 uppercase whitespace-nowrap group-separator" colspan="5">Evidence SK</th>

              {{-- Evidence SR (6) --}}
              <th class="px-3 py-2 text-center font-semibold text-indigo-600 uppercase whitespace-nowrap group-separator" colspan="6">Evidence SR</th>

              {{-- Evidence MGRT (3) --}}
              <th class="px-3 py-2 text-center font-semibold text-orange-600 uppercase whitespace-nowrap group-separator" colspan="3">Evidence MGRT</th>

              {{-- Evidence Gas In (7) --}}
              <th class="px-3 py-2 text-center font-semibold text-red-600 uppercase whitespace-nowrap group-separator" colspan="7">Evidence Gas In</th>

              {{-- Review CGP (3) --}}
              <th class="px-3 py-2 text-center font-semibold text-teal-600 uppercase whitespace-nowrap group-separator" colspan="3">Review CGP</th>

              {{-- Dokumen (4) --}}
              <th class="px-3 py-2 text-center font-semibold text-gray-600 uppercase whitespace-nowrap group-separator" colspan="4">Dokumen</th>
            </tr>
            <tr class="bg-gray-100">
              {{-- Empty headers for Basic Info columns (20 columns total) --}}
              <th class="px-3 py-2"></th><!-- ID -->
              <th class="px-3 py-2"></th><!-- ID REFF -->
              <th class="px-3 py-2"></th><!-- Nama -->
              <th class="px-3 py-2"></th><!-- No. KTP -->
              <th class="px-3 py-2"></th><!-- No. Ponsel -->
              <th class="px-3 py-2"></th><!-- Alamat -->
              <th class="px-3 py-2"></th><!-- RT -->
              <th class="px-3 py-2"></th><!-- RW -->
              <th class="px-3 py-2"></th><!-- Kota/Kab -->
              <th class="px-3 py-2"></th><!-- Kecamatan -->
              <th class="px-3 py-2"></th><!-- Kelurahan -->
              <th class="px-3 py-2"></th><!-- Padukuhan -->
              <th class="px-3 py-2"></th><!-- Penetrasi/Pengembangan -->
              <th class="px-3 py-2"></th><!-- Tgl SK -->
              <th class="px-3 py-2"></th><!-- Tgl SR -->
              <th class="px-3 py-2"></th><!-- Tgl GAS IN -->
              <th class="px-3 py-2"></th><!-- Keterangan -->
              <th class="px-3 py-2"></th><!-- Batal -->
              <th class="px-3 py-2"></th><!-- Ket. Batal -->
              <th class="px-3 py-2"></th><!-- Anomali -->

              {{-- Material SK --}}
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk group-separator">Elbow 3/4-1/2</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk">Dbl Nipple 1/2</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk">Pipa Galv 1/2</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk">Elbow 1/2</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk">Ball Valve 1/2</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk">Nipple Slang 1/2</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk">Klem Pipa 1/2</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk">Sockdraft 1/2</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sk">Sealtape</th>

              {{-- Material SR --}}
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr group-separator">TS 63x20mm</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Coupler 20mm</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Pipa PE 20mm</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Elbow PE 20mm</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Female TF 20mm</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Pipa Galv 3/4</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Klem Pipa 3/4</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Ball Valve 3/4</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Elbow 90 3/4</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Dbl Nipple 3/4</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Regulator</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">MGRT</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Cassing</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Coupling</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-material-sr">Sealtape</th>

              {{-- Evidence SK --}}
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sk group-separator">BA Pasang</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sk">Pneum Start</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sk">Pneum Finish</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sk">Valve SK</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sk">Isometrik</th>

              {{-- Evidence SR --}}
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sr group-separator">Pneum Start</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sr">Pneum Finish</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sr">Jns Tapping</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sr">Kedalaman</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sr">Cassing</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-sr">Isometrik</th>

              {{-- Evidence MGRT --}}
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-mgrt group-separator">Foto MGRT</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-mgrt">Foto Pondasi</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-mgrt">No Seri</th>

              {{-- Evidence Gas In --}}
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-gasin group-separator">BA Gas In</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-gasin">Rangkaian</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-gasin">Bubble Test</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-gasin">Foto MGRT</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-gasin">Kompor</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-gasin">Stiker</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-evidence-gasin">No Seri</th>

              {{-- Review CGP --}}
              <th class="px-3 py-2 text-xs whitespace-nowrap group-review-cgp group-separator">SK</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-review-cgp">SR</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-review-cgp">Gas In</th>

              {{-- Dokumen --}}
              <th class="px-3 py-2 text-xs whitespace-nowrap group-dokumen group-separator">BA Gas In</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-dokumen">Asbuilt SK</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-dokumen">Asbuilt SR</th>
              <th class="px-3 py-2 text-xs whitespace-nowrap group-dokumen">Comment</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100" x-html="renderTableRows()">
            {{-- Table rows will be rendered by JavaScript for instant filtering/sorting/pagination --}}
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="px-4 py-3 border-t border-gray-200 sm:px-6" x-show="totalPages > 1">
        <div class="flex items-center justify-between">
          {{-- Info: Showing X to Y of Z results --}}
          <div class="flex items-center">
            <span class="text-sm text-gray-700">
              Showing
              <span class="font-medium" x-text="fromRecord"></span>
              to
              <span class="font-medium" x-text="toRecord"></span>
              of
              <span class="font-medium" x-text="filteredCount"></span>
              results
            </span>
          </div>

          {{-- Navigation Buttons --}}
          <div class="flex items-center space-x-2">
            {{-- Previous Button --}}
            <button @click="goToPage(currentPage - 1)"
                    :disabled="currentPage === 1"
                    :class="currentPage === 1 ?
                      'px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 opacity-50 cursor-not-allowed' :
                      'px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors'">
              Previous
            </button>

            {{-- Page Numbers --}}
            <template x-for="page in visiblePages" :key="page">
              <div style="display: inline;">
                <span x-show="page === '...'" class="px-2 text-sm text-gray-400" x-text="page"></span>
                <button x-show="page !== '...'"
                        @click="goToPage(page)"
                        :class="page === currentPage ?
                          'px-3 py-1 border border-gray-300 rounded-md text-sm font-medium bg-aergas-orange text-white transition-colors' :
                          'px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors'"
                        x-text="page">
                </button>
              </div>
            </template>

            {{-- Next Button --}}
            <button @click="goToPage(currentPage + 1)"
                    :disabled="currentPage === totalPages"
                    :class="currentPage === totalPages ?
                      'px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 opacity-50 cursor-not-allowed' :
                      'px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors'">
              Next
            </button>
          </div>
        </div>
      </div>
    @endif
  </div>

</div>

<script>
// Helper function to format date from YYYY-MM-DD or ISO datetime to DD/MM/YYYY
function formatDateToDDMMYYYY(dateStr) {
  if (!dateStr) return '-';

  // Handle ISO datetime format (e.g., "2025-08-14T00:00:00.000000Z")
  if (dateStr.includes('T')) {
    dateStr = dateStr.split('T')[0];
  }

  // Now split YYYY-MM-DD
  const parts = dateStr.split('-');
  if (parts.length !== 3) return dateStr;
  return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

// Filter for New Customers
function newCustomersFilter(itemsJson) {
  return {
    filter: 'all',
    items: typeof itemsJson === 'string' ? JSON.parse(itemsJson) : itemsJson,
    tableHtml: '',

    init() {
      this.updateTable();
    },

    get filteredItems() {
      if (this.filter === 'all') return this.items;

      return this.items.filter(item => {
        const hasSK = !!item.tanggal_sk;
        const hasSR = !!item.tanggal_sr;
        const hasGasIn = !!item.tanggal_gas_in;
        const hasDates = hasSK || hasSR || hasGasIn;

        if (this.filter === 'no_dates') {
          return !hasDates;
        }
        if (this.filter === 'has_dates') {
          return hasDates;
        }
        if (this.filter === 'complete') {
          return hasSK && hasSR && hasGasIn;
        }
        return false;
      });
    },

    get filteredCount() {
      return this.filteredItems.length;
    },

    updateTable() {
      this.tableHtml = this.renderRows();
    },

    renderRows() {
      if (this.filteredItems.length === 0) {
        return `<tr><td colspan="6" class="px-3 py-4 text-center text-gray-500">Tidak ada data yang sesuai filter</td></tr>`;
      }

      return this.filteredItems.map(item => `
        <tr class="hover:bg-gray-50">
          <td class="px-3 py-2 font-mono text-xs font-semibold text-purple-600">${item.reff_id}</td>
          <td class="px-3 py-2">${item.nama}</td>
          <td class="px-3 py-2">${item.alamat}</td>
          <td class="px-3 py-2 text-center text-xs">${formatDateToDDMMYYYY(item.tanggal_sk)}</td>
          <td class="px-3 py-2 text-center text-xs">${formatDateToDDMMYYYY(item.tanggal_sr)}</td>
          <td class="px-3 py-2 text-center text-xs">${formatDateToDDMMYYYY(item.tanggal_gas_in)}</td>
        </tr>
      `).join('');
    }
  };
}

// Filter for Incomplete Installation
function incompleteFilter(itemsJson) {
  return {
    filter: 'all',
    items: typeof itemsJson === 'string' ? JSON.parse(itemsJson) : itemsJson,
    tableHtml: '',

    init() {
      this.updateTable();
    },

    get filteredItems() {
      if (this.filter === 'all') return this.items;

      return this.items.filter(item => {
        // Kurang SK: PILOT has SK date AND (missing in DB OR different)
        if (this.filter === 'kurang_sk') {
          return item.tanggal_sk && (item.sk_status === 'missing_db' || item.sk_status === 'different');
        }
        // Kurang SR: PILOT has SR date AND (missing in DB OR different)
        if (this.filter === 'kurang_sr') {
          return item.tanggal_sr && (item.sr_status === 'missing_db' || item.sr_status === 'different');
        }
        // Kurang Gas In: PILOT has Gas In date AND (missing in DB OR different)
        if (this.filter === 'kurang_gas_in') {
          return item.tanggal_gas_in && (item.gas_in_status === 'missing_db' || item.gas_in_status === 'different');
        }
        // Different: Any field has different dates
        if (this.filter === 'different') {
          return item.sk_status === 'different' || item.sr_status === 'different' || item.gas_in_status === 'different';
        }
        return false;
      });
    },

    get filteredCount() {
      return this.filteredItems.length;
    },

    updateTable() {
      this.tableHtml = this.renderRows();
    },

    renderCell(item, field) {
      const tanggal = item[`tanggal_${field}`];
      const status = item[`${field}_status`];
      const dbTanggal = item[`db_tanggal_${field}`];

      if (!tanggal) {
        return '<span class="text-gray-400 font-semibold">-</span>';
      }

      const formattedDate = formatDateToDDMMYYYY(tanggal);

      if (status === 'complete') {
        return `<span class="text-green-600 font-semibold">${formattedDate}</span>`;
      }

      if (status === 'missing_db') {
        return `<span class="text-yellow-600 font-semibold">${formattedDate}</span>`;
      }

      if (status === 'different') {
        const formattedDbDate = formatDateToDDMMYYYY(dbTanggal);
        return `<div>
          <span class="text-red-600 font-semibold">${formattedDate}</span>
          <br><span class="text-xs text-gray-500">(DB: ${formattedDbDate})</span>
        </div>`;
      }

      return `<span class="text-gray-400 font-semibold">-</span>`;
    },

    renderRows() {
      if (this.filteredItems.length === 0) {
        return `<tr><td colspan="5" class="px-3 py-4 text-center text-gray-500">Tidak ada data yang sesuai filter</td></tr>`;
      }

      return this.filteredItems.map(item => `
        <tr class="hover:bg-gray-50">
          <td class="px-3 py-2 font-mono text-xs">${item.reff_id}</td>
          <td class="px-3 py-2">${item.nama}</td>
          <td class="px-3 py-2 text-center text-xs">${this.renderCell(item, 'sk')}</td>
          <td class="px-3 py-2 text-center text-xs">${this.renderCell(item, 'sr')}</td>
          <td class="px-3 py-2 text-center text-xs">${this.renderCell(item, 'gas_in')}</td>
        </tr>
      `).join('');
    }
  };
}

function pilotTable() {
  return {
    // Data
    allData: @json($allPilots),
    filteredData: [],

    // Search & Sort
    globalSearch: '',
    sortColumn: '',
    sortDirection: 'asc',

    // Pagination
    currentPage: 1,
    perPage: 50,

    // Computed properties
    get totalCount() {
      return this.allData.length;
    },

    get filteredCount() {
      return this.filteredData.length;
    },

    get totalPages() {
      return Math.ceil(this.filteredCount / this.perPage);
    },

    get fromRecord() {
      if (this.filteredCount === 0) return 0;
      return ((this.currentPage - 1) * this.perPage) + 1;
    },

    get toRecord() {
      return Math.min(this.currentPage * this.perPage, this.filteredCount);
    },

    get visiblePages() {
      return this.generatePageNumbers();
    },

    get paginatedData() {
      const start = (this.currentPage - 1) * this.perPage;
      const end = start + this.perPage;
      return this.filteredData.slice(start, end);
    },

    init() {
      // Initialize with all data
      this.filteredData = [...this.allData];
    },

    filterData() {
      const searchTerm = this.globalSearch.toLowerCase().trim();

      if (!searchTerm) {
        // No search - show all data
        this.filteredData = [...this.allData];
      } else {
        // Filter data
        this.filteredData = this.allData.filter(item =>
          item.search_text && item.search_text.includes(searchTerm)
        );
      }

      // Reset to page 1 after filter
      this.currentPage = 1;

      // Reapply sort if active
      if (this.sortColumn) {
        this.applySorting();
      }
    },

    sortData(column) {
      // Toggle direction if same column
      if (this.sortColumn === column) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortColumn = column;
        this.sortDirection = 'asc';
      }

      this.applySorting();
    },

    applySorting() {
      this.filteredData.sort((a, b) => {
        const aVal = a[this.sortColumn] !== null && a[this.sortColumn] !== undefined ? String(a[this.sortColumn]) : '';
        const bVal = b[this.sortColumn] !== null && b[this.sortColumn] !== undefined ? String(b[this.sortColumn]) : '';

        // Try numeric comparison
        const aNum = parseFloat(aVal);
        const bNum = parseFloat(bVal);

        if (!isNaN(aNum) && !isNaN(bNum)) {
          return this.sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
        }

        // String comparison
        const aStr = aVal.toLowerCase();
        const bStr = bVal.toLowerCase();

        if (this.sortDirection === 'asc') {
          return aStr < bStr ? -1 : aStr > bStr ? 1 : 0;
        } else {
          return aStr > bStr ? -1 : aStr < bStr ? 1 : 0;
        }
      });
    },

    goToPage(pageNum) {
      if (pageNum >= 1 && pageNum <= this.totalPages) {
        this.currentPage = pageNum;
        // Smooth scroll to table top
        document.querySelector('.pilot-table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    },

    generatePageNumbers() {
      if (this.totalPages === 0) return [];

      const pages = [];
      const current = this.currentPage;
      const total = this.totalPages;

      const start = Math.max(1, current - 2);
      const end = Math.min(total, current + 2);

      // First page
      if (start > 1) {
        pages.push(1);
        if (start > 2) pages.push('...');
      }

      // Middle pages
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }

      // Last page
      if (end < total) {
        if (end < total - 1) pages.push('...');
        pages.push(total);
      }

      return pages;
    },

    getSortIndicator(column) {
      if (this.sortColumn !== column) return '⇅';
      return this.sortDirection === 'asc' ? '↑' : '↓';
    },

    formatDate(dateStr) {
      if (!dateStr) return '-';
      const date = new Date(dateStr);
      const day = String(date.getDate()).padStart(2, '0');
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const year = date.getFullYear();
      return `${day}/${month}/${year}`;
    },

    renderLink(url) {
      if (!url) {
        return '<span class="text-xs text-gray-400">-</span>';
      }
      return `<a href="${url}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs"><i class="fas fa-link"></i></a>`;
    },

    renderTableRows() {
      if (this.paginatedData.length === 0) {
        return `
          <tr>
            <td colspan="100" class="px-4 py-8 text-center">
              <div class="text-gray-500">
                <i class="fas fa-search text-3xl mb-2"></i>
                <p class="text-lg">Tidak ada data ditemukan.</p>
              </div>
            </td>
          </tr>
        `;
      }

      return this.paginatedData.map(pilot => `
        <tr class="hover:bg-gray-50">
          <td class="no-wrap text-gray-600">${pilot.id || '-'}</td>
          <td class="no-wrap"><span class="font-mono text-xs font-semibold text-blue-600">${pilot.id_reff || '-'}</span></td>
          <td class="no-wrap"><span class="text-xs text-gray-700 font-medium">${pilot.nama || '-'}</span></td>
          <td class="no-wrap"><span class="text-xs text-gray-600">${pilot.nomor_kartu_identitas || '-'}</span></td>
          <td class="no-wrap"><span class="text-xs text-gray-600">${pilot.nomor_ponsel || '-'}</span></td>
          <td class="no-wrap"><span class="text-xs text-gray-600">${pilot.alamat || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.rt || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.rw || '-'}</span></td>
          <td class="no-wrap"><span class="text-xs text-gray-600">${pilot.id_kota_kab || '-'}</span></td>
          <td class="no-wrap"><span class="text-xs text-gray-600">${pilot.id_kecamatan || '-'}</span></td>
          <td class="no-wrap"><span class="text-xs text-gray-600">${pilot.id_kelurahan || '-'}</span></td>
          <td class="no-wrap"><span class="text-xs text-gray-600">${pilot.padukuhan || '-'}</span></td>
          <td class="no-wrap"><span class="text-xs text-gray-600">${pilot.penetrasi_pengembangan || '-'}</span></td>
          <td class="no-wrap text-center">${pilot.tanggal_terpasang_sk ? `<span class="text-xs font-semibold text-gray-700">${this.formatDate(pilot.tanggal_terpasang_sk)}</span>` : '<span class="text-xs text-gray-400">-</span>'}</td>
          <td class="no-wrap text-center">${pilot.tanggal_terpasang_sr ? `<span class="text-xs font-semibold text-gray-700">${this.formatDate(pilot.tanggal_terpasang_sr)}</span>` : '<span class="text-xs text-gray-400">-</span>'}</td>
          <td class="no-wrap text-center">${pilot.tanggal_terpasang_gas_in ? `<span class="text-xs font-semibold text-gray-700">${this.formatDate(pilot.tanggal_terpasang_gas_in)}</span>` : '<span class="text-xs text-gray-400">-</span>'}</td>
          <td class="no-wrap"><span class="text-xs text-gray-600">${pilot.keterangan || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.batal || '-'}</span></td>
          <td class="no-wrap"><span class="text-xs text-gray-600">${pilot.keterangan_batal || '-'}</span></td>
          <td class="no-wrap"><span class="text-xs text-gray-600">${pilot.anomali || '-'}</span></td>

          <td class="no-wrap text-center group-separator"><span class="text-xs text-gray-600">${pilot.mat_sk_elbow_3_4_to_1_2 || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sk_double_nipple_1_2 || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sk_pipa_galvanize_1_2 || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sk_elbow_1_2 || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sk_ball_valve_1_2 || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sk_nipple_slang_1_2 || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sk_klem_pipa_1_2 || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sk_sockdraft_galvanis_1_2 || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sk_sealtape || '-'}</span></td>

          <td class="no-wrap text-center group-separator"><span class="text-xs text-gray-600">${pilot.mat_sr_ts_63x20mm || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sr_coupler_20mm || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sr_pipa_pe_20mm || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sr_elbow_pe_20mm || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sr_female_tf_pe_20mm || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sr_pipa_galvanize_3_4 || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sr_klem_pipa_3_4 || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sr_ball_valves_3_4 || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sr_long_elbow_90_3_4 || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sr_double_nipple_3_4 || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sr_regulator || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sr_meter_gas_rumah_tangga || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sr_cassing_1 || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sr_coupling_mgrt || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.mat_sr_sealtape || '-'}</span></td>

          <td class="no-wrap text-center group-separator">${this.renderLink(pilot.ev_sk_foto_berita_acara_pemasangan)}</td>
          <td class="no-wrap text-center">${this.renderLink(pilot.ev_sk_foto_pneumatik_start)}</td>
          <td class="no-wrap text-center">${this.renderLink(pilot.ev_sk_foto_pneumatik_finish)}</td>
          <td class="no-wrap text-center">${this.renderLink(pilot.ev_sk_foto_valve_sk)}</td>
          <td class="no-wrap text-center">${this.renderLink(pilot.ev_sk_foto_isometrik_sk)}</td>

          <td class="no-wrap text-center group-separator">${this.renderLink(pilot.ev_sr_foto_pneumatik_start)}</td>
          <td class="no-wrap text-center">${this.renderLink(pilot.ev_sr_foto_pneumatik_finish)}</td>
          <td class="no-wrap text-center">${this.renderLink(pilot.ev_sr_foto_jenis_tapping)}</td>
          <td class="no-wrap text-center">${this.renderLink(pilot.ev_sr_foto_kedalaman)}</td>
          <td class="no-wrap text-center">${this.renderLink(pilot.ev_sr_foto_cassing)}</td>
          <td class="no-wrap text-center">${this.renderLink(pilot.ev_sr_foto_isometrik_sr)}</td>

          <td class="no-wrap text-center group-separator">${this.renderLink(pilot.ev_mgrt_foto_meter_gas_rumah_tangga)}</td>
          <td class="no-wrap text-center">${this.renderLink(pilot.ev_mgrt_foto_pondasi_mgrt)}</td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.ev_mgrt_nomor_seri_mgrt || '-'}</span></td>

          <td class="no-wrap text-center group-separator">${this.renderLink(pilot.ev_gasin_berita_acara_gas_in)}</td>
          <td class="no-wrap text-center">${this.renderLink(pilot.ev_gasin_rangkaian_meter_gas_pondasi)}</td>
          <td class="no-wrap text-center">${this.renderLink(pilot.ev_gasin_foto_bubble_test)}</td>
          <td class="no-wrap text-center">${this.renderLink(pilot.ev_gasin_foto_mgrt)}</td>
          <td class="no-wrap text-center">${this.renderLink(pilot.ev_gasin_foto_kompor_menyala_pelanggan)}</td>
          <td class="no-wrap text-center">${this.renderLink(pilot.ev_gasin_foto_stiker_sosialisasi)}</td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.ev_gasin_nomor_seri_mgrt || '-'}</span></td>

          <td class="no-wrap text-center group-separator"><span class="text-xs text-gray-600">${pilot.review_cgp_sk || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.review_cgp_sr || '-'}</span></td>
          <td class="no-wrap text-center"><span class="text-xs text-gray-600">${pilot.review_cgp_gas_in || '-'}</span></td>

          <td class="no-wrap text-center group-separator">${this.renderLink(pilot.ba_gas_in)}</td>
          <td class="no-wrap text-center">${this.renderLink(pilot.asbuilt_sk)}</td>
          <td class="no-wrap text-center">${this.renderLink(pilot.asbuilt_sr)}</td>
          <td class="no-wrap"><span class="text-xs text-gray-600">${pilot.comment_cgp || '-'}</span></td>
        </tr>
      `).join('');
    }
  }
}
</script>

@endsection
