@extends('layouts.app')

@section('title', 'Upload PILOT Sheet - AERGAS')

@section('content')
<div class="space-y-6">

  <div class="flex items-center gap-4">
    <a href="{{ route('pilot-comparison.index') }}" class="text-gray-600 hover:text-gray-800">
      <i class="fas fa-arrow-left mr-2"></i>Kembali
    </a>
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Upload PILOT Sheet</h1>
      <p class="text-gray-600 mt-1">Upload file Excel/CSV untuk membandingkan dengan database</p>
    </div>
  </div>

  @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <span class="block sm:inline">{{ session('error') }}</span>
        </div>
        @if(session('show_debug') && session('pilot_debug'))
          <a href="{{ route('pilot-comparison.debug-view') }}" class="ml-4 px-3 py-1 bg-orange-600 text-white rounded hover:bg-orange-700 text-sm whitespace-nowrap">
            <i class="fas fa-bug mr-1"></i>View Debug Info
          </a>
        @endif
      </div>
    </div>
  @endif

  @if ($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
      <ul class="list-disc list-inside">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Import Method Selection --}}
  <div class="bg-white rounded-xl card-shadow p-6" x-data="{ importMethod: 'file' }">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Pilih Metode Import</h2>

    <div class="flex gap-4 mb-6">
      <button @click="importMethod = 'file'"
              :class="importMethod === 'file' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
              class="flex-1 px-6 py-3 rounded-lg font-semibold transition-colors">
        <i class="fas fa-file-upload mr-2"></i>Upload File
      </button>
      <button @click="importMethod = 'sheets'"
              :class="importMethod === 'sheets' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
              class="flex-1 px-6 py-3 rounded-lg font-semibold transition-colors">
        <i class="fab fa-google-drive mr-2"></i>Google Sheets
      </button>
    </div>

    {{-- File Upload Form --}}
    <div x-show="importMethod === 'file'" x-transition>
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Upload File PILOT</h3>
      <form id="uploadForm" action="{{ route('pilot-comparison.preview') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="space-y-6">
          {{-- File Upload --}}
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              File PILOT (Excel/CSV) <span class="text-red-500">*</span>
            </label>
            <input type="file" name="pilot_file" accept=".xlsx,.xls,.csv" required
                   class="block w-full text-sm text-gray-500
                          file:mr-4 file:py-2 file:px-4
                          file:rounded file:border-0
                          file:text-sm file:font-semibold
                          file:bg-blue-50 file:text-blue-700
                          hover:file:bg-blue-100">
            <p class="mt-1 text-sm text-gray-500">Format: .xlsx, .xls, atau .csv (Max 10MB)</p>
          </div>

          {{-- Required Columns Info --}}
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-semibold text-blue-800 mb-2">
              <i class="fas fa-info-circle mr-2"></i>Format File yang Diperlukan
            </h3>
            <p class="text-sm text-blue-700 mb-2">File Excel/CSV harus memiliki kolom-kolom berikut:</p>
            <ul class="list-disc list-inside text-sm text-blue-700 space-y-1">
              <li><strong>reff_id_pelanggan</strong> atau <strong>reff_id</strong> - ID Pelanggan (wajib)</li>
              <li><strong>nama_pelanggan</strong> atau <strong>nama</strong> - Nama Pelanggan</li>
              <li><strong>alamat</strong> - Alamat</li>
              <li><strong>tanggal_sk</strong> - Tanggal Instalasi SK</li>
              <li><strong>tanggal_sr</strong> - Tanggal Pemasangan SR</li>
              <li><strong>tanggal_gas_in</strong> atau <strong>tanggal_gasin</strong> - Tanggal GAS IN</li>
              <li><strong>status_sk</strong> - Status SK (opsional)</li>
              <li><strong>status_sr</strong> - Status SR (opsional)</li>
              <li><strong>status_gas_in</strong> atau <strong>status_gasin</strong> - Status GAS IN (opsional)</li>
            </ul>
            <p class="text-sm text-blue-600 mt-3">
              <i class="fas fa-lightbulb mr-1"></i>
              <strong>Tips:</strong> Pastikan header kolom sesuai dengan yang disebutkan di atas (tidak case-sensitive).
            </p>
          </div>

          {{-- Example Table --}}
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h3 class="font-semibold text-gray-800 mb-3">Contoh Format File:</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-300 text-xs">
                <thead class="bg-gray-200">
                  <tr>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">reff_id_pelanggan</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">nama_pelanggan</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">alamat</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">tanggal_sk</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">tanggal_sr</th>
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">tanggal_gas_in</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr>
                    <td class="px-3 py-2">123456</td>
                    <td class="px-3 py-2">John Doe</td>
                    <td class="px-3 py-2">Jl. Example No. 1</td>
                    <td class="px-3 py-2">2025-01-15</td>
                    <td class="px-3 py-2">2025-01-20</td>
                    <td class="px-3 py-2">2025-01-25</td>
                  </tr>
                  <tr>
                    <td class="px-3 py-2">00123457</td>
                    <td class="px-3 py-2">Jane Smith</td>
                    <td class="px-3 py-2">Jl. Example No. 2</td>
                    <td class="px-3 py-2">2025-01-16</td>
                    <td class="px-3 py-2">2025-01-21</td>
                    <td class="px-3 py-2">2025-01-26</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          {{-- Actions --}}
          <div class="flex gap-3">
            <button type="submit" formaction="{{ route('pilot-comparison.preview') }}" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700">
              <i class="fas fa-eye mr-2"></i>Preview Data
            </button>
            <button type="submit" formaction="{{ route('pilot-comparison.store') }}" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
              <i class="fas fa-upload mr-2"></i>Langsung Upload & Bandingkan
            </button>
            <a href="{{ route('pilot-comparison.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
              Batal
            </a>
          </div>
        </div>
      </form>
    </div>

    {{-- Google Sheets Form --}}
    <div x-show="importMethod === 'sheets'" x-transition>
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Import dari Google Sheets</h3>
      <form action="{{ route('pilot-comparison.import-sheets') }}" method="POST">
        @csrf

        <div class="space-y-6">
          {{-- Google Sheets URL --}}
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              URL Google Sheets <span class="text-red-500">*</span>
            </label>
            <input type="url" name="google_sheets_url" required
                   value="{{ old('google_sheets_url', 'https://docs.google.com/spreadsheets/d/1WrJAAgetFXBciRyo8TyFnb3Eux88dq6r/edit?gid=1139154429#gid=1139154429') }}"
                   placeholder="https://docs.google.com/spreadsheets/d/..."
                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
            <p class="mt-1 text-sm text-gray-500">
              <i class="fas fa-info-circle mr-1"></i>
              Pastikan Google Sheets dapat diakses dengan link (Anyone with the link can view)
            </p>
          </div>

          {{-- Sheet Selection --}}
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Nama Sheet (Optional)
              </label>
              <input type="text" name="sheet_name"
                     value="{{ old('sheet_name', 'PELANGGAN') }}"
                     placeholder="Contoh: PELANGGAN, Sheet1, Data"
                     class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
              <p class="mt-1 text-sm text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                Nama sheet di Google Sheets. Kosongkan jika sheet pertama.
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Sheet GID (Optional)
              </label>
              <input type="text" name="sheet_gid"
                     value="{{ old('sheet_gid', '1139154429') }}"
                     placeholder="Contoh: 0, 123456789"
                     class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
              <p class="mt-1 text-sm text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                ID sheet (dari URL). Lebih akurat dari nama sheet.
              </p>
            </div>
          </div>

          {{-- Skip Rows --}}
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Skip Rows (Baris yang Dilewati)
            </label>
            <input type="text" name="skip_rows" value="1-6"
                   placeholder="Contoh: 1-3,5,7-10"
                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
            <p class="mt-1 text-sm text-gray-500">
              <i class="fas fa-info-circle mr-1"></i>
              Row yang akan dilewati <strong>(termasuk header!)</strong>. Format: angka tunggal (3), range (1-5), atau kombinasi (1-3,5,7-10)
            </p>
            <p class="mt-1 text-sm text-blue-600">
              <i class="fas fa-lightbulb mr-1"></i>
              <strong>Contoh:</strong> Jika data mulai row 7, maka skip "1-6" (skip semua row sebelum data, termasuk header)
            </p>
            <p class="mt-1 text-sm text-amber-600">
              <i class="fas fa-exclamation-triangle mr-1"></i>
              <strong>PENTING:</strong> Semua row yang TIDAK di-skip akan dibaca sebagai DATA (mapping by position kolom)
            </p>
          </div>

          {{-- Skip Columns --}}
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Skip Columns (Kolom yang Dilewati)
            </label>
            <input type="text" name="skip_columns" value="A,B,M,N,P,Q,S"
                   placeholder="Contoh: A,B atau A-C,E,G-J"
                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
            <p class="mt-1 text-sm text-gray-500">
              <i class="fas fa-info-circle mr-1"></i>
              Kolom yang akan dilewati. Format: huruf kolom tunggal (A,B), range (A-C), atau kombinasi (A-C,E,G-J)
            </p>
            <p class="mt-1 text-sm text-blue-600">
              <i class="fas fa-lightbulb mr-1"></i>
              <strong>Contoh:</strong> "A,B" = skip kolom A dan B | "A-C" = skip kolom A,B,C | "A-C,E,G-J" = kombinasi
            </p>
            <p class="mt-1 text-sm text-amber-600">
              <i class="fas fa-exclamation-triangle mr-1"></i>
              <strong>Note:</strong> Gunakan huruf kapital (A,B,C). Sistem akan otomatis convert ke uppercase.
            </p>
          </div>

          {{-- Info Box --}}
          <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-start gap-3">
              <i class="fab fa-google-drive text-green-600 text-2xl mt-1"></i>
              <div class="flex-1">
                <p class="text-sm text-green-800 font-semibold mb-2">Cara Menggunakan Google Sheets</p>
                <ol class="text-sm text-green-700 space-y-2 list-decimal list-inside">
                  <li>Buka Google Sheets Anda</li>
                  <li>Klik <strong>Share</strong> di pojok kanan atas</li>
                  <li>Pilih <strong>"Anyone with the link can view"</strong></li>
                  <li>Copy link dan paste di kolom URL di atas</li>
                  <li><strong>Pilih sheet:</strong>
                    <ul class="ml-6 mt-1 space-y-1">
                      <li>• <strong>Cara 1 (Sheet Name):</strong> Ketik nama sheet (contoh: PELANGGAN)</li>
                      <li>• <strong>Cara 2 (Sheet GID - Recommended):</strong> Lihat URL sheet, cari <code class="bg-green-200 px-1 rounded">#gid=123456</code>, copy angkanya</li>
                      <li>• Jika kosong, akan menggunakan sheet pertama</li>
                    </ul>
                  </li>
                  <li>Atur jumlah baris yang ingin dilewati (skip rows)</li>
                  <li>Row setelah skip akan dianggap sebagai header kolom</li>
                </ol>
                <p class="text-sm text-green-600 mt-3 font-semibold">
                  <i class="fas fa-file-excel mr-1"></i>
                  Data akan di-download sebagai Excel (.xlsx) untuk hasil parsing yang lebih baik
                </p>
              </div>
            </div>
          </div>

          {{-- GID Example --}}
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm font-semibold text-blue-800 mb-2">
              <i class="fas fa-question-circle mr-1"></i>Cara Mendapatkan Sheet GID
            </p>
            <div class="text-sm text-blue-700 space-y-2">
              <p>1. Buka sheet yang ingin diimport (contoh: sheet PELANGGAN)</p>
              <p>2. Lihat URL di browser, akan ada format seperti ini:</p>
              <code class="block bg-blue-100 p-2 rounded text-xs mt-2 break-all">
                https://docs.google.com/spreadsheets/d/1WrAO9CmVKQk.../edit<span class="font-bold text-red-600">#gid=0</span>
              </code>
              <p class="mt-2">3. Copy angka setelah <strong>gid=</strong> (contoh: <strong>0</strong>, <strong>123456789</strong>)</p>
              <p>4. Paste di kolom <strong>Sheet GID</strong></p>
              <p class="font-semibold mt-2">
                <i class="fas fa-star mr-1"></i>Sheet pertama biasanya memiliki GID = 0
              </p>
            </div>
          </div>

          {{-- Example --}}
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-sm font-semibold text-gray-800 mb-2">Contoh Link Google Sheets PILOT:</p>
            <code class="text-xs text-gray-600 break-all">
              https://docs.google.com/spreadsheets/d/1WrAO9CmVKQk-XavFIQTTfpaaXJM7wIZw/edit?usp=sharing...
            </code>
          </div>

          {{-- Actions --}}
          <div class="flex gap-3 flex-wrap">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700">
              <i class="fab fa-google-drive mr-2"></i>Import & Preview
            </button>
            <button type="submit" name="debug_mode" value="1" class="px-6 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">
              <i class="fas fa-bug mr-2"></i>Debug Mode
            </button>
            <a href="{{ route('pilot-comparison.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
              Batal
            </a>
          </div>
          <p class="text-xs text-gray-600 mt-2">
            <i class="fas fa-info-circle mr-1"></i>
            <strong>Debug Mode:</strong> Gunakan untuk troubleshooting jika import gagal. Akan menampilkan informasi detail tentang proses import.
          </p>
        </div>
      </form>
    </div>
  </div>

</div>

<script>
// Auto-extract GID from Google Sheets URL
document.addEventListener('DOMContentLoaded', function() {
  const urlInput = document.querySelector('input[name="google_sheets_url"]');
  const gidInput = document.querySelector('input[name="sheet_gid"]');

  if (urlInput && gidInput) {
    urlInput.addEventListener('blur', function() {
      const url = this.value;

      // Try to extract GID from URL
      const gidMatch = url.match(/#gid=(\d+)/);
      if (gidMatch && gidMatch[1]) {
        // Only auto-fill if GID input is empty
        if (!gidInput.value || gidInput.value === '0') {
          gidInput.value = gidMatch[1];

          // Show success message
          const parent = gidInput.parentElement;
          const helpText = parent.querySelector('.text-gray-500');
          if (helpText) {
            helpText.innerHTML = '<i class="fas fa-check-circle mr-1 text-green-600"></i><span class="text-green-600">GID berhasil terdeteksi dari URL!</span>';
            setTimeout(() => {
              helpText.innerHTML = '<i class="fas fa-info-circle mr-1"></i>ID sheet (dari URL). Lebih akurat dari nama sheet.';
            }, 3000);
          }
        }
      }
    });
  }
});
</script>

@endsection
