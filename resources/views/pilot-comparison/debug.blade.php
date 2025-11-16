@extends('layouts.app')

@section('title', 'Debug Info - Google Sheets Import')

@section('content')
<div class="space-y-6">

  <div class="flex items-center gap-4">
    <a href="{{ route('pilot-comparison.create') }}" class="text-gray-600 hover:text-gray-800">
      <i class="fas fa-arrow-left mr-2"></i>Kembali
    </a>
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Debug Information</h1>
      <p class="text-gray-600 mt-1">Troubleshooting Google Sheets Import</p>
    </div>
  </div>

  {{-- Error Message --}}
  @if(isset($errorMessage) && $errorMessage)
    <div class="bg-red-100 border-l-4 border-red-500 p-6 rounded-lg">
      <div class="flex items-start">
        <i class="fas fa-exclamation-circle text-red-500 text-2xl mr-3"></i>
        <div>
          <h3 class="text-lg font-semibold text-red-800 mb-2">Error Occurred</h3>
          <p class="text-red-700">{{ $errorMessage }}</p>
        </div>
      </div>
    </div>
  @endif

  {{-- Success Message --}}
  @if(isset($successMessage) && $successMessage)
    <div class="bg-green-100 border-l-4 border-green-500 p-6 rounded-lg">
      <div class="flex items-start">
        <i class="fas fa-check-circle text-green-500 text-2xl mr-3"></i>
        <div>
          <h3 class="text-lg font-semibold text-green-800 mb-2">Success!</h3>
          <p class="text-green-700">{{ $successMessage }}</p>
        </div>
      </div>
    </div>
  @endif

  {{-- Debug Info Cards --}}
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- Input Parameters --}}
    <div class="bg-white rounded-xl card-shadow p-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-cog text-blue-600 mr-2"></i>Input Parameters
      </h3>
      <div class="space-y-3 text-sm">
        <div class="flex justify-between border-b pb-2">
          <span class="text-gray-600 font-semibold">URL Input:</span>
          <span class="text-gray-800 break-all">{{ $debugInfo['url_input'] ?? 'N/A' }}</span>
        </div>
        <div class="flex justify-between border-b pb-2">
          <span class="text-gray-600 font-semibold">Sheet Name:</span>
          <span class="text-gray-800">{{ $debugInfo['sheet_name'] ?: '(kosong)' }}</span>
        </div>
        <div class="flex justify-between border-b pb-2">
          <span class="text-gray-600 font-semibold">Sheet GID:</span>
          <span class="text-gray-800">{{ $debugInfo['sheet_gid'] ?: '(kosong)' }}</span>
        </div>
        <div class="flex justify-between border-b pb-2">
          <span class="text-gray-600 font-semibold">Skip Rows Input:</span>
          <span class="text-gray-800">{{ $debugInfo['skip_rows_input'] ?? '0' }}</span>
        </div>
        <div class="flex justify-between border-b pb-2">
          <span class="text-gray-600 font-semibold">Skip Rows Parsed:</span>
          <span class="text-gray-800 font-mono text-xs">
            @if(isset($debugInfo['skip_rows_parsed']) && is_array($debugInfo['skip_rows_parsed']))
              [{{ implode(', ', $debugInfo['skip_rows_parsed']) }}]
            @else
              []
            @endif
          </span>
        </div>
        <div class="flex justify-between border-b pb-2">
          <span class="text-gray-600 font-semibold">Skip Columns Input:</span>
          <span class="text-gray-800">{{ $debugInfo['skip_columns_input'] ?? '' }}</span>
        </div>
        <div class="flex justify-between border-b pb-2">
          <span class="text-gray-600 font-semibold">Skip Columns Parsed:</span>
          <span class="text-gray-800 font-mono text-xs">
            @if(isset($debugInfo['skip_columns_parsed']) && is_array($debugInfo['skip_columns_parsed']))
              [{{ implode(', ', $debugInfo['skip_columns_parsed']) }}]
            @else
              []
            @endif
          </span>
        </div>
      </div>
    </div>

    {{-- Processing Results --}}
    <div class="bg-white rounded-xl card-shadow p-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-chart-line text-green-600 mr-2"></i>Processing Results
      </h3>
      <div class="space-y-3 text-sm">
        <div class="flex justify-between border-b pb-2">
          <span class="text-gray-600 font-semibold">Spreadsheet ID:</span>
          <span class="text-gray-800 font-mono text-xs">{{ $debugInfo['spreadsheet_id'] ?? 'NOT FOUND' }}</span>
        </div>
        <div class="flex justify-between border-b pb-2">
          <span class="text-gray-600 font-semibold">File Size:</span>
          <span class="text-gray-800">{{ isset($debugInfo['file_size']) ? number_format($debugInfo['file_size']) . ' bytes' : 'N/A' }}</span>
        </div>
        <div class="flex justify-between border-b pb-2">
          <span class="text-gray-600 font-semibold">Sheet Used:</span>
          <span class="text-gray-800">{{ $debugInfo['sheet_used'] ?? 'N/A' }}</span>
        </div>
        <div class="flex justify-between border-b pb-2">
          <span class="text-gray-600 font-semibold">Total Rows:</span>
          <span class="text-gray-800">{{ $debugInfo['total_rows'] ?? 'N/A' }}</span>
        </div>
        <div class="flex justify-between border-b pb-2">
          <span class="text-gray-600 font-semibold">Data Start Row:</span>
          <span class="text-gray-800">{{ $debugInfo['data_start_row'] ?? 'N/A' }}</span>
        </div>
        <div class="flex justify-between border-b pb-2">
          <span class="text-gray-600 font-semibold">Parsed Records:</span>
          <span class="text-gray-800 font-semibold {{ ($debugInfo['parsed_records'] ?? 0) > 0 ? 'text-green-600' : 'text-red-600' }}">
            {{ $debugInfo['parsed_records'] ?? 0 }}
          </span>
        </div>
        <div class="flex justify-between border-b pb-2">
          <span class="text-gray-600 font-semibold">Highest Column in Excel:</span>
          <span class="text-gray-800 font-semibold">{{ $debugInfo['highest_column'] ?? 'N/A' }}</span>
        </div>
        <div class="flex justify-between border-b pb-2">
          <span class="text-gray-600 font-semibold">Columns Read (after skip):</span>
          <span class="text-gray-800 font-semibold {{ ($debugInfo['sample_columns_count'] ?? 0) > 1 ? 'text-green-600' : 'text-red-600' }}">
            {{ $debugInfo['sample_columns_count'] ?? 0 }}
          </span>
        </div>
        @if(isset($debugInfo['columns_read']) && count($debugInfo['columns_read']) > 0)
        <div class="border-b pb-2">
          <span class="text-gray-600 font-semibold block mb-2">Columns Being Read:</span>
          <div class="bg-blue-50 p-2 rounded text-xs font-mono">
            {{ implode(', ', array_slice($debugInfo['columns_read'], 0, 30)) }}
            @if(count($debugInfo['columns_read']) > 30)
              ... ({{ count($debugInfo['columns_read']) }} total)
            @endif
          </div>
        </div>
        @endif
        @if(isset($debugInfo['sample_first_row']) && count($debugInfo['sample_first_row']) > 0)
        <div class="border-b pb-2">
          <span class="text-gray-600 font-semibold block mb-2">Sample First Row Data (first 15 cols):</span>
          <div class="bg-gray-50 p-2 rounded text-xs font-mono overflow-x-auto max-h-64">
            @foreach($debugInfo['sample_first_row'] as $index => $value)
              <div class="flex gap-2">
                <span class="text-gray-500">[{{ $index }}]:</span>
                <span class="text-gray-800">{{ $value ?: '(empty)' }}</span>
              </div>
            @endforeach
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>

  {{-- Preview First 15 Rows --}}
  @if(isset($debugInfo['preview_rows']) && !empty($debugInfo['preview_rows']))
    <div class="bg-white rounded-xl card-shadow p-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-table text-indigo-600 mr-2"></i>Preview First 15 Rows (Untuk Mencari Header yang Benar)
      </h3>
      <div class="overflow-x-auto">
        <table class="min-w-full text-xs border-collapse">
          <thead>
            <tr class="bg-gray-100">
              <th class="border px-2 py-1 text-left font-semibold text-gray-700">Row</th>
              @for($i = 1; $i <= count($debugInfo['preview_rows'][0] ?? []); $i++)
                <th class="border px-2 py-1 text-left font-semibold text-gray-700">Col {{ $i }}</th>
              @endfor
            </tr>
          </thead>
          <tbody>
            @foreach($debugInfo['preview_rows'] as $rowIndex => $rowData)
              <tr class="{{ $rowIndex == ($debugInfo['header_row'] ?? 0) - 1 ? 'bg-yellow-100' : ($rowIndex % 2 == 0 ? 'bg-white' : 'bg-gray-50') }}">
                <td class="border px-2 py-1 font-mono text-gray-600">
                  {{ $rowIndex + 1 }}
                  @if($rowIndex == ($debugInfo['header_row'] ?? 0) - 1)
                    <span class="ml-1 text-yellow-700 font-bold">← HEADER</span>
                  @endif
                </td>
                @foreach($rowData as $cellValue)
                  <td class="border px-2 py-1 text-gray-800" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    @if(is_array($cellValue) || is_object($cellValue))
                      {{ json_encode($cellValue) }}
                    @else
                      {{ $cellValue ?: '(empty)' }}
                    @endif
                  </td>
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <p class="text-sm text-gray-600 mt-3">
        <i class="fas fa-info-circle mr-1"></i>
        Tabel ini menampilkan preview RAW data (sebelum filter). Lihat tabel di bawah untuk data setelah filter.
      </p>
    </div>
  @endif

  {{-- Filtered Preview with Database Headers --}}
  @if(isset($debugInfo['filtered_preview']) && !empty($debugInfo['filtered_preview']))
    @php
      // Database field names (71 fields after skipping A,B)
      $dbFields = [
        'nama', 'nomor_kartu_identitas', 'nomor_ponsel', 'alamat', 'rt', 'rw',
        'id_kota_kab', 'id_kecamatan', 'id_kelurahan', 'padukuhan',
        'id_reff', 'penetrasi_pengembangan',
        'tanggal_terpasang_sk', 'tanggal_terpasang_sr', 'tanggal_terpasang_gas_in',
        'keterangan', 'batal', 'keterangan_batal', 'anomali',
        'mat_sk_elbow_3_4_to_1_2', 'mat_sk_double_nipple_1_2', 'mat_sk_pipa_galvanize_1_2',
        'mat_sk_elbow_1_2', 'mat_sk_ball_valve_1_2', 'mat_sk_nipple_slang_1_2',
        'mat_sk_klem_pipa_1_2', 'mat_sk_sockdraft_galvanis_1_2', 'mat_sk_sealtape',
        'mat_sr_ts_63x20mm', 'mat_sr_coupler_20mm', 'mat_sr_pipa_pe_20mm',
        'mat_sr_elbow_pe_20mm', 'mat_sr_female_tf_pe_20mm', 'mat_sr_pipa_galvanize_3_4',
        'mat_sr_klem_pipa_3_4', 'mat_sr_ball_valves_3_4', 'mat_sr_long_elbow_90_3_4',
        'mat_sr_double_nipple_3_4', 'mat_sr_regulator', 'mat_sr_meter_gas_rumah_tangga',
        'mat_sr_cassing_1', 'mat_sr_coupling_mgrt', 'mat_sr_sealtape',
        'ev_sk_foto_berita_acara_pemasangan', 'ev_sk_foto_pneumatik_start',
        'ev_sk_foto_pneumatik_finish', 'ev_sk_foto_valve_sk', 'ev_sk_foto_isometrik_sk',
        'ev_sr_foto_pneumatik_start', 'ev_sr_foto_pneumatik_finish',
        'ev_sr_foto_jenis_tapping', 'ev_sr_foto_kedalaman',
        'ev_sr_foto_cassing', 'ev_sr_foto_isometrik_sr',
        'ev_mgrt_foto_meter_gas_rumah_tangga', 'ev_mgrt_foto_pondasi_mgrt', 'ev_mgrt_nomor_seri_mgrt',
        'ev_gasin_berita_acara_gas_in', 'ev_gasin_rangkaian_meter_gas_pondasi',
        'ev_gasin_foto_bubble_test', 'ev_gasin_foto_mgrt',
        'ev_gasin_foto_kompor_menyala_pelanggan', 'ev_gasin_foto_stiker_sosialisasi',
        'ev_gasin_nomor_seri_mgrt',
        'review_cgp_sk', 'review_cgp_sr', 'review_cgp_gas_in',
        'ba_gas_in', 'asbuilt_sk', 'asbuilt_sr', 'comment_cgp'
      ];
      $displayFields = array_slice($dbFields, 0, 15); // Show first 15 fields
    @endphp
    <div class="bg-white rounded-xl card-shadow p-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-table text-purple-600 mr-2"></i>
        Preview Data Setelah Filter (First 10 Rows, First 15 Fields)
      </h3>
      <div class="overflow-x-auto">
        <table class="min-w-full text-xs border-collapse">
          <thead>
            <tr class="bg-purple-100">
              <th class="border px-2 py-1 text-left font-semibold text-gray-700 sticky left-0 bg-purple-100">Row</th>
              <th class="border px-2 py-1 text-left font-semibold text-gray-700 sticky left-12 bg-purple-100">Index</th>
              @foreach($displayFields as $index => $fieldName)
                <th class="border px-2 py-1 text-left font-semibold text-gray-700">
                  <div>[{{ $index }}]</div>
                  <div class="text-purple-700">{{ $fieldName }}</div>
                </th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach($debugInfo['filtered_preview'] as $rowData)
              @php
                $rowNumber = $rowData['_row_number'] ?? '?';
                // Get only numeric values (exclude _row_number)
                $values = array_values(array_filter($rowData, function($key) {
                  return is_numeric($key);
                }, ARRAY_FILTER_USE_KEY));
              @endphp
              <tr class="{{ $loop->index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                <td class="border px-2 py-1 font-mono text-gray-600 sticky left-0 {{ $loop->index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                  {{ $rowNumber }}
                </td>
                <td class="border px-2 py-1 font-mono text-gray-500 sticky left-12 {{ $loop->index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                  {{ $loop->index }}
                </td>
                @foreach(array_slice($values, 0, 15) as $value)
                  <td class="border px-2 py-1 text-gray-800" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    {{ $value ?: '(empty)' }}
                  </td>
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <p class="text-sm text-purple-600 mt-3">
        <i class="fas fa-info-circle mr-1"></i>
        <strong>PENTING:</strong> Tabel ini menampilkan data SETELAH skip rows & columns di-apply.
        Header menunjukkan nama field database yang akan di-map (index 0 = {{ $dbFields[0] }}, index 1 = {{ $dbFields[1] }}, dst).
      </p>
    </div>
  @endif

  {{-- Headers Detected --}}
  @if(isset($debugInfo['headers_detected']) && !empty($debugInfo['headers_detected']))
    <div class="bg-white rounded-xl card-shadow p-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-list text-teal-600 mr-2"></i>Headers Detected ({{ count($debugInfo['headers_detected']) }} columns)
      </h3>
      <div class="bg-gray-50 p-4 rounded border border-gray-200">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
          @foreach($debugInfo['headers_detected'] as $index => $header)
            <div class="bg-white p-2 rounded border text-xs">
              <span class="text-gray-500 font-mono">{{ $index + 1 }}.</span>
              <span class="text-gray-800 font-semibold">{{ $header }}</span>
            </div>
          @endforeach
        </div>
      </div>
      <p class="text-sm text-gray-600 mt-2">
        <i class="fas fa-info-circle mr-1"></i>
        Headers dari row {{ $debugInfo['header_row'] ?? 'N/A' }} setelah skip {{ $debugInfo['skip_rows'] ?? 0 }} rows
      </p>
    </div>
  @endif

  {{-- Export URL --}}
  @if(isset($debugInfo['export_url']))
    <div class="bg-white rounded-xl card-shadow p-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-link text-purple-600 mr-2"></i>Export URL Generated
      </h3>
      <div class="bg-gray-50 p-4 rounded border border-gray-200">
        <code class="text-xs text-gray-700 break-all">{{ $debugInfo['export_url'] }}</code>
      </div>
      <div class="mt-3">
        <a href="{{ $debugInfo['export_url'] }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
          <i class="fas fa-external-link-alt mr-2"></i>Test Download Excel
        </a>
      </div>
      <p class="text-sm text-gray-600 mt-2">
        <i class="fas fa-info-circle mr-1"></i>
        URL ini akan download file Excel (.xlsx) dari Google Sheets
      </p>
    </div>
  @endif

  {{-- Troubleshooting Tips --}}
  <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg">
    <h3 class="text-lg font-semibold text-blue-800 mb-4 flex items-center">
      <i class="fas fa-lightbulb text-blue-600 mr-2"></i>Troubleshooting Tips
    </h3>
    <div class="space-y-3 text-sm text-blue-800">
      <div class="flex items-start gap-2">
        <i class="fas fa-check-circle text-blue-600 mt-1"></i>
        <div>
          <strong>Spreadsheet ID NOT FOUND:</strong>
          <p class="text-blue-700 mt-1">URL tidak valid. Pastikan URL mengandung <code class="bg-blue-200 px-1 rounded">/d/{ID}/</code></p>
        </div>
      </div>
      <div class="flex items-start gap-2">
        <i class="fas fa-check-circle text-blue-600 mt-1"></i>
        <div>
          <strong>File Size = 0 bytes:</strong>
          <p class="text-blue-700 mt-1">Sheet tidak dapat diakses. Pastikan sheet di-share sebagai "Anyone with the link can view"</p>
        </div>
      </div>
      <div class="flex items-start gap-2">
        <i class="fas fa-check-circle text-blue-600 mt-1"></i>
        <div>
          <strong>Total Rows > 0 but Parsed Records = 0:</strong>
          <p class="text-blue-700 mt-1">Skip rows terlalu besar atau format data tidak sesuai. Coba kurangi skip rows atau periksa struktur sheet.</p>
        </div>
      </div>
      <div class="flex items-start gap-2">
        <i class="fas fa-check-circle text-blue-600 mt-1"></i>
        <div>
          <strong>Sheet GID salah:</strong>
          <p class="text-blue-700 mt-1">Buka sheet yang benar di browser, lihat URL-nya, copy GID yang benar (angka setelah #gid=)</p>
        </div>
      </div>
      <div class="flex items-start gap-2">
        <i class="fas fa-check-circle text-blue-600 mt-1"></i>
        <div>
          <strong>Kolom tidak terdeteksi:</strong>
          <p class="text-blue-700 mt-1">Pastikan header row benar. Sistem mencari kolom: "No", "ID REFF", "Nama", "SK", "SR", "Gas In"</p>
        </div>
      </div>
    </div>
  </div>

  {{-- Actions --}}
  <div class="flex gap-3">
    <a href="{{ route('pilot-comparison.create') }}" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
      <i class="fas fa-redo mr-2"></i>Try Again
    </a>
    <button onclick="window.print()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
      <i class="fas fa-print mr-2"></i>Print Debug Info
    </button>
  </div>

</div>
@endsection
