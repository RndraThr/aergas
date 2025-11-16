@extends('layouts.app')

@section('title', 'Preview PILOT Data - AERGAS')

@section('content')
<div class="space-y-6">

  <div class="flex items-center gap-4">
    <a href="{{ route('pilot-comparison.create') }}" class="text-gray-600 hover:text-gray-800">
      <i class="fas fa-arrow-left mr-2"></i>Kembali
    </a>
    <div class="flex-1">
      <h1 class="text-3xl font-bold text-gray-800">Preview Data PILOT</h1>
      <div class="flex items-center gap-3 mt-1">
        @if(session('pilot_preview.source') === 'google_sheets')
          <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold">
            <i class="fab fa-google-drive mr-2"></i>Google Sheets
          </span>
        @else
          <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold">
            <i class="fas fa-file mr-2"></i>File Upload
          </span>
        @endif
        <p class="text-gray-600">{{ $fileName }}</p>
      </div>
    </div>
  </div>

  {{-- Summary Info --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl p-4 card-shadow">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">Total Records</p>
          <p class="text-2xl font-bold text-gray-800">{{ count($pilotData) }}</p>
        </div>
        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
          <i class="fas fa-list text-blue-600 text-xl"></i>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl p-4 card-shadow">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">Total Columns</p>
          <p class="text-2xl font-bold text-gray-800">{{ count($columns) }}</p>
        </div>
        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
          <i class="fas fa-columns text-green-600 text-xl"></i>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl p-4 card-shadow">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">Status</p>
          <p class="text-lg font-bold text-green-600">Siap untuk Comparison</p>
        </div>
        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
          <i class="fas fa-check-circle text-green-600 text-xl"></i>
        </div>
      </div>
    </div>
  </div>

  {{-- Column Info --}}
  <div class="bg-white rounded-xl card-shadow p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">
      <i class="fas fa-table mr-2"></i>Struktur Kolom
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
      @foreach($columns as $index => $column)
        @php
          $isImportant = in_array(strtolower($column), [
            'reff_id_pelanggan', 'reff_id', 'nama_pelanggan', 'nama',
            'tanggal_sk', 'tanggal_sr', 'tanggal_gas_in', 'tanggal_gasin',
            'status_sk', 'status_sr', 'status_gas_in', 'status_gasin'
          ]);
        @endphp
        <div class="flex items-center p-3 rounded-lg {{ $isImportant ? 'bg-blue-50 border-2 border-blue-200' : 'bg-gray-50 border border-gray-200' }}">
          <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold {{ $isImportant ? 'bg-blue-500 text-white' : 'bg-gray-400 text-white' }}">
            {{ $index + 1 }}
          </div>
          <div class="ml-3 flex-1">
            <p class="text-sm font-semibold {{ $isImportant ? 'text-blue-800' : 'text-gray-700' }}">
              {{ $column }}
            </p>
            @if($isImportant)
              <p class="text-xs text-blue-600">
                <i class="fas fa-star mr-1"></i>Kolom Penting
              </p>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  </div>

  {{-- Data Preview Table --}}
  <div class="bg-white rounded-xl card-shadow">
    <div class="p-4 border-b flex items-center justify-between">
      <h2 class="text-xl font-semibold text-gray-800">
        <i class="fas fa-eye mr-2"></i>Preview Data ({{ count($pilotData) > 50 ? '50 baris pertama dari ' . count($pilotData) : count($pilotData) }} baris)
      </h2>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b sticky top-0">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">#</th>
            @foreach($columns as $column)
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">
                {{ $column }}
              </th>
            @endforeach
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @foreach(array_slice($pilotData, 0, 50) as $index => $row)
            <tr class="hover:bg-gray-50">
              <td class="px-4 py-3 text-gray-500 font-semibold">{{ $index + 1 }}</td>
              @foreach($columns as $column)
                <td class="px-4 py-3 whitespace-nowrap">
                  @php
                    $value = $row[$column] ?? '-';

                    // Handle array/object values (like raw_data)
                    if (is_array($value) || is_object($value)) {
                      $value = json_encode($value);
                    }

                    // Format dates
                    if (in_array(strtolower($column), ['tanggal_sk', 'tanggal_sr', 'tanggal_gas_in', 'tanggal_gasin']) && $value && $value !== '-') {
                      try {
                        $value = \Carbon\Carbon::parse($value)->format('d/m/Y');
                      } catch (\Exception $e) {
                        // Keep original value if parsing fails
                      }
                    }

                    // Highlight important columns
                    $isImportant = in_array(strtolower($column), [
                      'reff_id_pelanggan', 'reff_id', 'nama_pelanggan', 'nama',
                      'tanggal_sk', 'tanggal_sr', 'tanggal_gas_in'
                    ]);
                  @endphp
                  <span class="{{ $isImportant ? 'font-semibold text-gray-800' : 'text-gray-600' }}">
                    {{ $value }}
                  </span>
                </td>
              @endforeach
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    @if(count($pilotData) > 50)
      <div class="p-4 bg-gray-50 border-t text-center text-sm text-gray-600">
        <i class="fas fa-info-circle mr-2"></i>
        Menampilkan 50 baris pertama dari total {{ count($pilotData) }} baris.
        Semua data akan diproses saat melakukan comparison.
      </div>
    @endif
  </div>

  {{-- Actions --}}
  <div class="bg-white rounded-xl card-shadow p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Lanjutkan ke Comparison?</h3>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
      <div class="flex items-start gap-3">
        <i class="fas fa-info-circle text-blue-600 text-xl mt-1"></i>
        <div class="flex-1">
          <p class="text-sm text-blue-800 font-semibold mb-2">Informasi Comparison</p>
          <ul class="text-sm text-blue-700 space-y-1">
            <li><i class="fas fa-check mr-2"></i>Sistem akan membandingkan <strong>{{ count($pilotData) }} record</strong> dengan database</li>
            <li><i class="fas fa-check mr-2"></i>Perbandingan berdasarkan: <strong>reff_id_pelanggan</strong></li>
            <li><i class="fas fa-check mr-2"></i>Deteksi perbedaan: <strong>Tanggal SK, SR, dan GAS IN</strong></li>
            <li><i class="fas fa-check mr-2"></i>Hasil akan disimpan untuk review dan export</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="flex gap-3">
      <form action="{{ route('pilot-comparison.store') }}" method="POST" class="inline">
        @csrf
        <input type="hidden" name="from_preview" value="1">
        <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
          <i class="fas fa-play mr-2"></i>Lanjutkan Comparison
        </button>
      </form>

      <a href="{{ route('pilot-comparison.create') }}" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold">
        <i class="fas fa-times mr-2"></i>Batal
      </a>
    </div>
  </div>

</div>

<style>
.sidebar-scroll {
  scrollbar-width: thin;
  scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
}

.sidebar-scroll::-webkit-scrollbar {
  width: 6px;
}

.sidebar-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-scroll::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.5);
  border-radius: 3px;
}

.sidebar-scroll::-webkit-scrollbar-thumb:hover {
  background-color: rgba(156, 163, 175, 0.7);
}
</style>

@endsection
