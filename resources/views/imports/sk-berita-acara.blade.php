@extends('layouts.app')

@section('title', 'Import Foto Berita Acara SK')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Import Foto Berita Acara SK</h1>
    <a href="{{ route('imports.sk-berita-acara.template') }}"
       class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
      <i class="fas fa-download mr-2"></i>Download Template Excel
    </a>
  </div>

  <!-- Instruction Card -->
  <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
    <h3 class="font-semibold text-blue-900 mb-2">
      <i class="fas fa-info-circle mr-2"></i>Petunjuk Penggunaan
    </h3>
    <ul class="text-sm text-blue-800 space-y-1 ml-6 list-disc">
      <li>Download template Excel terlebih dahulu</li>
      <li>Isi kolom <code class="bg-blue-100 px-1 rounded">reff_id</code> dengan ID pelanggan</li>
      <li>Isi kolom <code class="bg-blue-100 px-1 rounded">nama_ba</code> dengan nama file foto (tanpa ekstensi)</li>
      <li><strong>Upload semua foto BA ke satu folder Google Drive</strong></li>
      <li>Copy link folder Google Drive tersebut</li>
      <li>Format foto yang didukung: .jpg, .jpeg, .png, .pdf</li>
      <li>Nama file foto di Drive harus sesuai dengan kolom <code class="bg-blue-100 px-1 rounded">nama_ba</code></li>
      <li>Gunakan <strong>Dry-run</strong> terlebih dahulu untuk validasi sebelum commit</li>
      <li class="text-green-700"><strong>💡 Jika SK Data belum ada, sistem akan otomatis membuatnya</strong></li>
    </ul>
  </div>

  <!-- Example Card -->
  <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
    <h3 class="font-semibold text-amber-900 mb-2">
      <i class="fas fa-lightbulb mr-2"></i>Contoh Penggunaan
    </h3>
    <div class="text-sm text-amber-800 space-y-2">
      <p><strong>1. Upload foto ke Google Drive:</strong></p>
      <ul class="ml-6 list-disc space-y-1">
        <li>Buat folder di Google Drive: <code class="bg-amber-100 px-1 rounded">BA_SK_Photos</code></li>
        <li>Upload file: <code class="bg-amber-100 px-1 rounded">442439.jpg, 442432.pdf, 442437.png</code></li>
      </ul>
      <p class="mt-2"><strong>2. Copy link folder:</strong></p>
      <div class="bg-amber-100 p-2 rounded font-mono text-xs">
        https://drive.google.com/drive/folders/1Abc123XyZ456...
      </div>
      <p class="mt-2"><strong>3. Isi Excel dengan data:</strong></p>
      <table class="text-xs border bg-white">
        <tr class="bg-amber-100"><th class="border px-2 py-1">reff_id</th><th class="border px-2 py-1">nama_ba</th></tr>
        <tr><td class="border px-2 py-1">442439</td><td class="border px-2 py-1">442439</td></tr>
        <tr><td class="border px-2 py-1">442432</td><td class="border px-2 py-1">442432</td></tr>
      </table>
    </div>
  </div>

  <!-- Import Form -->
  <form method="POST"
        action="{{ route('imports.sk-berita-acara.import') }}"
        enctype="multipart/form-data"
        class="bg-white shadow rounded-lg p-6 space-y-4">
    @csrf

    <!-- File Excel -->
    <div>
      <label class="block text-sm font-medium mb-2">
        <i class="fas fa-file-excel text-green-600 mr-2"></i>File Excel (.xlsx/.csv)
      </label>
      <input type="file"
             name="file"
             accept=".xlsx,.xls,.csv"
             class="border rounded px-3 py-2 w-full @error('file') border-red-500 @enderror"
             required>
      @error('file')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
      @enderror
    </div>

    <!-- Drive Folder Link -->
    <div>
      <label class="block text-sm font-medium mb-2">
        <i class="fab fa-google-drive text-blue-600 mr-2"></i>Link Folder Google Drive
      </label>
      <input type="text"
             name="drive_folder_link"
             placeholder="https://drive.google.com/drive/folders/1Abc123XyZ..."
             class="border rounded px-3 py-2 w-full font-mono text-sm @error('drive_folder_link') border-red-500 @enderror"
             value="{{ old('drive_folder_link') }}"
             required>
      <p class="text-xs text-gray-500 mt-1">
        <i class="fas fa-info-circle mr-1"></i>
        Paste link folder Google Drive yang berisi semua foto BA
      </p>
      @error('drive_folder_link')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
      @enderror
    </div>

    <!-- Mode Selection -->
    <div>
      <label class="block text-sm font-medium mb-2">
        <i class="fas fa-cog text-gray-600 mr-2"></i>Mode Import
      </label>
      <select name="mode" class="border rounded px-3 py-2 w-full">
        <option value="dry-run" selected>🔍 Dry-run (Validasi saja, tidak upload)</option>
        <option value="commit">✅ Commit (Upload ke database)</option>
      </select>
      <p class="text-xs text-gray-500 mt-1">
        <strong>Dry-run:</strong> Validasi file tanpa upload.
        <strong>Commit:</strong> Upload foto ke sistem.
      </p>
    </div>

    <!-- Heading Row -->
    <div>
      <label class="block text-sm font-medium mb-2">Baris Header</label>
      <input type="number"
             name="heading_row"
             value="1"
             min="1"
             class="border rounded px-3 py-2 w-32">
      <p class="text-xs text-gray-500 mt-1">Baris ke berapa header dimulai (biasanya baris 1)</p>
    </div>

    <!-- Save Report -->
    <div class="flex items-center gap-2">
      <input type="checkbox"
             id="save_report"
             name="save_report"
             value="1"
             class="rounded">
      <label for="save_report" class="text-sm">
        <i class="fas fa-save text-gray-600 mr-1"></i>Simpan report detail (JSON)
      </label>
    </div>

    <!-- Submit Button -->
    <div class="flex gap-3 pt-2">
      <button type="submit"
              class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
        <i class="fas fa-upload mr-2"></i>Proses Import
      </button>
      <a href="{{ route('dashboard') }}"
         class="px-6 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">
        <i class="fas fa-times mr-2"></i>Batal
      </a>
    </div>
  </form>

  <!-- Display Errors -->
  @if ($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
      <h3 class="font-semibold text-red-900 mb-2">
        <i class="fas fa-exclamation-circle mr-2"></i>Error
      </h3>
      <ul class="text-sm text-red-800 space-y-1 ml-6 list-disc">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <!-- Display Results -->
  @if (session('ba_import_results'))
    @php($r = session('ba_import_results'))
    <div class="bg-white shadow rounded-lg p-6">
      <h2 class="text-xl font-semibold mb-4">
        <i class="fas fa-chart-bar text-blue-600 mr-2"></i>Hasil Import Berita Acara
      </h2>

      <!-- Summary Stats -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-green-50 border border-green-200 rounded p-4">
          <div class="text-sm text-green-600 font-medium">Berhasil</div>
          <div class="text-2xl font-bold text-green-700">{{ $r['success'] }}</div>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded p-4">
          <div class="text-sm text-yellow-600 font-medium">Diskip</div>
          <div class="text-2xl font-bold text-yellow-700">{{ $r['skipped'] }}</div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded p-4">
          <div class="text-sm text-red-600 font-medium">Gagal</div>
          <div class="text-2xl font-bold text-red-700">{{ count($r['failed']) }}</div>
        </div>
      </div>

      <!-- Success Details -->
      @if (!empty($r['details']))
        <div class="mb-4">
          <h3 class="font-medium mb-2">Detail Upload:</h3>
          <div class="max-h-64 overflow-y-auto border rounded">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
              <thead class="bg-gray-50 sticky top-0">
                <tr>
                  <th class="px-4 py-2 text-left">Row</th>
                  <th class="px-4 py-2 text-left">Reff ID</th>
                  <th class="px-4 py-2 text-left">Nama BA</th>
                  <th class="px-4 py-2 text-left">Status</th>
                  <th class="px-4 py-2 text-left">Pesan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                @foreach ($r['details'] as $detail)
                  <tr class="{{ $detail['status'] === 'uploaded' ? 'bg-green-50' : ($detail['status'] === 'skipped' ? 'bg-yellow-50' : 'bg-gray-50') }}">
                    <td class="px-4 py-2">{{ $detail['row'] }}</td>
                    <td class="px-4 py-2 font-mono text-xs">{{ $detail['reff_id'] }}</td>
                    <td class="px-4 py-2">{{ $detail['nama_ba'] ?? '-' }}</td>
                    <td class="px-4 py-2">
                      @if ($detail['status'] === 'uploaded')
                        <span class="px-2 py-1 bg-green-200 text-green-800 rounded text-xs">Uploaded</span>
                      @elseif ($detail['status'] === 'validated')
                        <span class="px-2 py-1 bg-blue-200 text-blue-800 rounded text-xs">Validated</span>
                      @else
                        <span class="px-2 py-1 bg-yellow-200 text-yellow-800 rounded text-xs">Skipped</span>
                      @endif
                    </td>
                    <td class="px-4 py-2 text-xs">
                      {{ $detail['message'] }}
                      @if (isset($detail['sk_data_created']) && $detail['sk_data_created'])
                        <span class="inline-block ml-2 px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs">
                          <i class="fas fa-info-circle"></i> SK Data Auto-Created
                        </span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endif

      <!-- Failed Details -->
      @if (!empty($r['failed']))
        <div class="mb-4">
          <h3 class="font-medium mb-2 text-red-700">
            <i class="fas fa-exclamation-triangle mr-2"></i>Data Gagal:
          </h3>
          <div class="max-h-64 overflow-y-auto border border-red-200 rounded">
            <table class="min-w-full divide-y divide-red-200 text-sm">
              <thead class="bg-red-50 sticky top-0">
                <tr>
                  <th class="px-4 py-2 text-left">Row</th>
                  <th class="px-4 py-2 text-left">Data</th>
                  <th class="px-4 py-2 text-left">Error</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-red-100">
                @foreach ($r['failed'] as $failed)
                  <tr class="bg-red-50">
                    <td class="px-4 py-2">{{ $failed['row'] }}</td>
                    <td class="px-4 py-2 font-mono text-xs">
                      {{ json_encode($failed['data'], JSON_UNESCAPED_UNICODE) }}
                    </td>
                    <td class="px-4 py-2 text-red-700">
                      <ul class="list-disc ml-4">
                        @foreach ($failed['errors'] as $error)
                          <li>{{ $error }}</li>
                        @endforeach
                      </ul>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endif

      <!-- Download Report -->
      @if (!empty($r['report_path']))
        <div class="mt-4">
          <a class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition inline-block"
             href="{{ route('imports.report.download', ['path' => $r['report_path']]) }}">
            <i class="fas fa-download mr-2"></i>Download Full Report (JSON)
          </a>
        </div>
      @endif
    </div>
  @endif
</div>
@endsection