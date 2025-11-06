@extends('layouts.app')

@section('title', 'Import Evidence (SK/SR)')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Import Evidence (SK/SR)</h1>
    <a href="{{ route('imports.evidence.template') }}"
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
      <li>Isi kolom <code class="bg-blue-100 px-1 rounded">nama_file</code> dengan nama file foto (tanpa ekstensi)</li>
      <li><strong>Upload semua foto ke satu folder Google Drive</strong></li>
      <li>Copy link folder Google Drive tersebut</li>
      <li>Pilih <strong>Module</strong> (SK atau SR) sesuai dengan modul yang akan di-import</li>
      <li>Pilih <strong>Tipe Evidence</strong> sesuai dengan jenis foto yang akan di-upload</li>
      <li>Format foto yang didukung: .jpg, .jpeg, .png, .pdf</li>
      <li>Nama file foto di Drive harus sesuai dengan kolom <code class="bg-blue-100 px-1 rounded">nama_file</code></li>
      <li>Gunakan <strong>Dry-run</strong> terlebih dahulu untuk validasi sebelum commit</li>
      <li class="text-green-700"><strong>💡 Jika SK/SR Data belum ada, sistem akan otomatis membuatnya</strong></li>
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
        <li>Buat folder di Google Drive: <code class="bg-amber-100 px-1 rounded">Evidence_Photos</code></li>
        <li>Upload file: <code class="bg-amber-100 px-1 rounded">442439.jpg, 442432.pdf, 442437.png</code></li>
      </ul>
      <p class="mt-2"><strong>2. Copy link folder:</strong></p>
      <div class="bg-amber-100 p-2 rounded font-mono text-xs">
        https://drive.google.com/drive/folders/1Abc123XyZ456...
      </div>
      <p class="mt-2"><strong>3. Isi Excel dengan data:</strong></p>
      <table class="text-xs border bg-white">
        <tr class="bg-amber-100"><th class="border px-2 py-1">reff_id</th><th class="border px-2 py-1">nama_file</th></tr>
        <tr><td class="border px-2 py-1">442439</td><td class="border px-2 py-1">442439</td></tr>
        <tr><td class="border px-2 py-1">442432</td><td class="border px-2 py-1">442432</td></tr>
      </table>
    </div>
  </div>

  <!-- Import Form -->
  <form id="importForm"
        method="POST"
        action="{{ route('imports.evidence.import') }}"
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
        Paste link folder Google Drive yang berisi semua foto evidence
      </p>
      @error('drive_folder_link')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
      @enderror
    </div>

    <!-- Module Selection (SK/SR) -->
    <div>
      <label class="block text-sm font-medium mb-2">
        <i class="fas fa-folder text-indigo-600 mr-2"></i>Module <span class="text-red-500">*</span>
      </label>
      <select name="module" id="moduleSelect" class="border rounded px-3 py-2 w-full" required>
        <option value="">Pilih Module</option>
        <option value="SK" selected>SK (Surat Keterangan)</option>
        <option value="SR">SR (Service Request)</option>
        <option value="GAS_IN">GAS IN (Gas Masuk)</option>
      </select>
      <p class="text-xs text-gray-500 mt-1">
        Pilih modul untuk import evidence
      </p>
    </div>

    <!-- Evidence Type Selection (Dynamic based on Module) -->
    <div>
      <label class="block text-sm font-medium mb-2">
        <i class="fas fa-camera text-purple-600 mr-2"></i>Tipe Evidence <span class="text-red-500">*</span>
      </label>
      <select name="evidence_type" id="evidenceTypeSelect" class="border rounded px-3 py-2 w-full" required>
        <option value="">Pilih Tipe Evidence</option>
        <!-- Options will be populated by JavaScript based on module selection -->
      </select>
      <p class="text-xs text-gray-500 mt-1" id="evidenceTypeHint">
        Pilih jenis evidence yang akan di-upload
      </p>
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

    <!-- Force Update Checkbox -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
      <div class="flex items-start gap-2">
        <input type="checkbox"
               id="force_update"
               name="force_update"
               value="1"
               class="mt-1 rounded">
        <label for="force_update" class="text-sm">
          <div class="font-semibold text-yellow-900 mb-1">
            <i class="fas fa-exclamation-triangle mr-1"></i>Force Update (Timpa Evidence yang Sudah Ada)
          </div>
          <div class="text-yellow-800">
            Jika dicentang, evidence yang sudah ada akan <strong>diganti dengan data baru</strong> dan
            status approval akan <strong>di-reset ke "Tracer Pending"</strong> untuk di-review ulang.
          </div>
          <div class="text-yellow-700 mt-2 text-xs">
            ⚠️ <strong>Perhatian:</strong> Approval yang sudah ada akan dibatalkan dan harus disetujui ulang.
          </div>
        </label>
      </div>
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
              id="submitBtn"
              class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
        <i class="fas fa-upload mr-2"></i>Proses Import
      </button>
      <a href="{{ route('dashboard') }}"
         class="px-6 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">
        <i class="fas fa-times mr-2"></i>Batal
      </a>
    </div>
  </form>

  <!-- Results Container (will be populated by JavaScript) -->
  <div id="resultsContainer" class="hidden"></div>

  <!-- Progress Modal -->
  <div id="progressModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center p-4" style="z-index: 9999999 !important;">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6" style="position: relative; z-index: 10000000 !important;">
      <!-- Header -->
      <div class="flex items-center gap-3 mb-4">
        <div class="flex-shrink-0" id="spinnerIcon">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
        <div class="flex-shrink-0 hidden" id="successIcon">
          <div class="rounded-full h-8 w-8 bg-green-500 flex items-center justify-center">
            <i class="fas fa-check text-white"></i>
          </div>
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Memproses Import...</h3>
          <p class="text-sm text-gray-500" id="progressStatus">Sedang memvalidasi data</p>
        </div>
      </div>

      <!-- Progress Bar -->
      <div class="mb-4">
        <div class="flex justify-between text-sm text-gray-600 mb-2">
          <span id="progressText">Memulai...</span>
          <span id="progressPercent">0%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
          <div id="progressBar"
               class="bg-blue-600 h-3 rounded-full transition-all duration-500 ease-out"
               style="width: 0%"></div>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-3 gap-2 text-center text-xs mb-4">
        <div class="bg-green-50 rounded p-2">
          <div class="font-semibold text-green-700" id="statsSuccess">0</div>
          <div class="text-green-600">Berhasil</div>
        </div>
        <div class="bg-yellow-50 rounded p-2">
          <div class="font-semibold text-yellow-700" id="statsSkipped">0</div>
          <div class="text-yellow-600">Diskip</div>
        </div>
        <div class="bg-red-50 rounded p-2">
          <div class="font-semibold text-red-700" id="statsFailed">0</div>
          <div class="text-red-600">Gagal</div>
        </div>
      </div>

      <!-- Info -->
      <div class="bg-blue-50 rounded-lg p-3">
        <p class="text-xs text-blue-800">
          <i class="fas fa-info-circle mr-1"></i>
          <span id="progressInfo">Mohon tunggu hingga proses selesai...</span>
        </p>
      </div>

      <!-- Action Button (hidden initially) -->
      <div id="actionButton" class="mt-4 hidden">
        <button id="closeModalBtn"
                class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
          <i class="fas fa-times mr-2"></i>Tutup
        </button>
      </div>

      <!-- Info -->
      <div class="mt-3 text-center" id="processingInfo">
        <p class="text-xs text-gray-500">
          <i class="fas fa-clock mr-1"></i>
          Proses akan selesai otomatis
        </p>
      </div>
    </div>
  </div>

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
  @if (session('evidence_import_results'))
    @php($r = session('evidence_import_results'))
    <div class="bg-white shadow rounded-lg p-6">
      <h2 class="text-xl font-semibold mb-4">
        <i class="fas fa-chart-bar text-blue-600 mr-2"></i>Hasil Import Evidence
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
                  <th class="px-4 py-2 text-left">Nama File</th>
                  <th class="px-4 py-2 text-left">File di Drive</th>
                  <th class="px-4 py-2 text-left">Status</th>
                  <th class="px-4 py-2 text-left">Pesan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                @foreach ($r['details'] as $detail)
                  <tr class="{{ $detail['status'] === 'uploaded' || $detail['status'] === 'force_updated' ? 'bg-green-50' : ($detail['status'] === 'skipped' ? 'bg-yellow-50' : 'bg-blue-50') }}">
                    <td class="px-4 py-2 font-bold">{{ $detail['row'] }}</td>
                    <td class="px-4 py-2 font-mono text-xs">{{ $detail['reff_id'] }}</td>
                    <td class="px-4 py-2">{{ $detail['nama_file'] ?? '-' }}</td>
                    <td class="px-4 py-2">
                      @if (isset($detail['file_found']) && $detail['file_found'])
                        <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">
                          <i class="fas fa-check-circle mr-1"></i>
                          {{ $detail['drive_filename'] ?? 'Found' }}
                        </span>
                      @elseif (isset($detail['file_found']) && !$detail['file_found'])
                        <span class="inline-flex items-center px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-medium">
                          <i class="fas fa-times-circle mr-1"></i>
                          Not Found
                        </span>
                      @else
                        <span class="text-gray-400 text-xs">-</span>
                      @endif
                    </td>
                    <td class="px-4 py-2">
                      @if ($detail['status'] === 'uploaded')
                        <span class="px-2 py-1 bg-green-200 text-green-800 rounded text-xs">Uploaded</span>
                      @elseif ($detail['status'] === 'force_updated')
                        <span class="px-2 py-1 bg-orange-200 text-orange-800 rounded text-xs">Force Updated</span>
                      @elseif ($detail['status'] === 'validated')
                        <span class="px-2 py-1 bg-blue-200 text-blue-800 rounded text-xs">Validated</span>
                      @else
                        <span class="px-2 py-1 bg-yellow-200 text-yellow-800 rounded text-xs">Skipped</span>
                      @endif
                    </td>
                    <td class="px-4 py-2 text-xs">
                      {{ $detail['message'] }}
                      @if (isset($detail['module_data_created']) && $detail['module_data_created'])
                        <span class="inline-block ml-2 px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs">
                          <i class="fas fa-info-circle"></i> {{ $detail['module'] ?? 'Module' }} Data Auto-Created
                        </span>
                      @endif
                      @if (isset($detail['will_replace']) && $detail['will_replace'])
                        <span class="inline-block ml-2 px-2 py-0.5 bg-orange-100 text-orange-700 rounded text-xs">
                          <i class="fas fa-exclamation-triangle"></i> Will Replace Existing
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

@push('scripts')
<script>
// Evidence types configuration
const evidenceTypes = {
  SK: [
    { value: 'berita_acara', label: '📋 Berita Acara', icon: '📋' },
    { value: 'isometrik_scan', label: '📐 Isometrik Scan', icon: '📐' },
    { value: 'pneumatic_start', label: '🔧 Pneumatic Start', icon: '🔧' },
    { value: 'pneumatic_finish', label: '✅ Pneumatic Finish', icon: '✅' },
    { value: 'valve', label: '🚰 Valve', icon: '🚰' }
  ],
  SR: [
    { value: 'pneumatic_start', label: '🔧 Pneumatic Start SR', icon: '🔧' },
    { value: 'pneumatic_finish', label: '✅ Pneumatic Finish SR', icon: '✅' },
    { value: 'jenis_tapping', label: '🔨 Jenis Tapping', icon: '🔨' },
    { value: 'mgrt', label: '⚙️ MGRT (Meter Gas)', icon: '⚙️' },
    { value: 'pondasi', label: '🏗️ Pondasi', icon: '🏗️' },
    { value: 'isometrik_scan', label: '📐 Isometrik Scan SR', icon: '📐' }
  ],
  GAS_IN: [
    { value: 'ba_gas_in', label: '📋 Berita Acara Gas In', icon: '📋' },
    { value: 'foto_bubble_test', label: '🫧 Foto Bubble Test (Uji Kebocoran)', icon: '🫧' },
    { value: 'foto_regulator', label: '⚙️ Foto Regulator Service', icon: '⚙️' },
    { value: 'foto_kompor_menyala', label: '🔥 Foto Kompor Menyala', icon: '🔥' }
  ]
};

document.addEventListener('DOMContentLoaded', function() {
  const moduleSelect = document.getElementById('moduleSelect');
  const evidenceTypeSelect = document.getElementById('evidenceTypeSelect');
  const evidenceTypeHint = document.getElementById('evidenceTypeHint');

  const form = document.getElementById('importForm');
  const modal = document.getElementById('progressModal');
  const progressBar = document.getElementById('progressBar');
  const progressPercent = document.getElementById('progressPercent');
  const progressText = document.getElementById('progressText');
  const progressStatus = document.getElementById('progressStatus');
  const progressInfo = document.getElementById('progressInfo');
  const statsSuccess = document.getElementById('statsSuccess');
  const statsSkipped = document.getElementById('statsSkipped');
  const statsFailed = document.getElementById('statsFailed');
  const submitBtn = document.getElementById('submitBtn');

  // Initialize evidence types for SK (default)
  updateEvidenceTypes('SK');

  // Module change handler
  moduleSelect.addEventListener('change', function() {
    const selectedModule = this.value;
    updateEvidenceTypes(selectedModule);
  });

  function updateEvidenceTypes(module) {
    if (!module) {
      evidenceTypeSelect.innerHTML = '<option value="">Pilih Module terlebih dahulu</option>';
      evidenceTypeSelect.disabled = true;
      return;
    }

    evidenceTypeSelect.disabled = false;
    evidenceTypeSelect.innerHTML = '<option value="">Pilih Tipe Evidence</option>';

    const types = evidenceTypes[module] || [];
    types.forEach(type => {
      const option = document.createElement('option');
      option.value = type.value;
      option.textContent = type.label;
      evidenceTypeSelect.appendChild(option);
    });

    // Update hint text
    evidenceTypeHint.textContent = `Pilih jenis evidence ${module} yang akan di-upload`;
  }

  // Move modal to document.body
  if (modal && modal.parentElement !== document.body) {
    document.body.appendChild(modal);
  }

  let currentProgress = 0;
  let isProcessing = false;
  let intervals = [];
  let serverResults = null;

  form.addEventListener('submit', function(e) {
    e.preventDefault();

    // Validate module and evidence type
    if (!moduleSelect.value) {
      alert('Silakan pilih Module terlebih dahulu');
      return;
    }
    if (!evidenceTypeSelect.value) {
      alert('Silakan pilih Tipe Evidence terlebih dahulu');
      return;
    }

    isProcessing = true;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    submitBtn.disabled = true;

    const mode = form.querySelector('select[name="mode"]').value;

    currentProgress = 0;
    updateProgress(0);

    statsSuccess.textContent = '0';
    statsSkipped.textContent = '0';
    statsFailed.textContent = '0';

    startBasicProgress();

    progressStatus.textContent = mode === 'dry-run'
      ? 'Mode: Validasi (Dry-run)'
      : 'Mode: Upload (Commit)';

    submitFormViaAjax();
  });

  document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'closeModalBtn') {
      closeModalAndShowResults();
    }
  });

  function startBasicProgress() {
    intervals.forEach(id => clearInterval(id));
    intervals = [];
    currentProgress = 0;

    progressText.textContent = 'Memulai proses import...';

    const basicInterval = setInterval(() => {
      if (currentProgress < 90 && isProcessing) {
        currentProgress += 0.5;
        updateProgress(currentProgress);

        if (currentProgress < 20) {
          progressText.textContent = 'Membaca file Excel...';
        } else if (currentProgress < 40) {
          progressText.textContent = 'Mengakses Google Drive...';
        } else if (currentProgress < 60) {
          progressText.textContent = 'Memproses data...';
        } else {
          progressText.textContent = 'Menyelesaikan...';
        }
      }
    }, 100);

    intervals.push(basicInterval);
  }

  function closeModalAndShowResults() {
    modal.classList.add('hidden');
    document.body.style.overflow = '';
    submitBtn.disabled = false;

    document.getElementById('spinnerIcon').classList.remove('hidden');
    document.getElementById('successIcon').classList.add('hidden');
    document.getElementById('actionButton').classList.add('hidden');
    document.getElementById('processingInfo').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'Memproses Import...';

    if (serverResults) {
      displayResultsOnPage(serverResults);
    }
  }

  function displayResultsOnPage(results) {
    const resultsHtml = createResultsHTML(results);
    const resultsContainer = document.getElementById('resultsContainer');
    if (resultsContainer) {
      resultsContainer.innerHTML = resultsHtml;
      resultsContainer.classList.remove('hidden');
      resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  function showCompletionUI(results) {
    intervals.forEach(id => clearInterval(id));
    isProcessing = false;

    serverResults = results;

    updateProgress(100);
    progressText.textContent = 'Import selesai!';

    document.getElementById('spinnerIcon').classList.add('hidden');
    document.getElementById('successIcon').classList.remove('hidden');

    document.getElementById('modalTitle').textContent = 'Import Berhasil!';
    progressStatus.textContent = 'Proses import telah selesai';

    if (results) {
      statsSuccess.textContent = results.success || 0;
      statsSkipped.textContent = results.skipped || 0;
      statsFailed.textContent = results.failed ? results.failed.length : 0;
    }

    const totalProcessed = (results.success || 0) + (results.skipped || 0) + (results.failed ? results.failed.length : 0);
    progressInfo.innerHTML = `<i class="fas fa-check-circle text-green-600 mr-1"></i> Total ${totalProcessed} rows berhasil diproses`;

    document.getElementById('processingInfo').classList.add('hidden');
    document.getElementById('actionButton').classList.remove('hidden');
  }

  function createResultsHTML(results) {
    const r = results;
    const failedCount = r.failed ? r.failed.length : 0;

    let html = `
      <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">
          <i class="fas fa-chart-bar text-blue-600 mr-2"></i>Hasil Import Evidence
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="bg-green-50 border border-green-200 rounded p-4">
            <div class="text-sm text-green-600 font-medium">Berhasil</div>
            <div class="text-2xl font-bold text-green-700">${r.success || 0}</div>
          </div>
          <div class="bg-yellow-50 border border-yellow-200 rounded p-4">
            <div class="text-sm text-yellow-600 font-medium">Diskip</div>
            <div class="text-2xl font-bold text-yellow-700">${r.skipped || 0}</div>
          </div>
          <div class="bg-red-50 border border-red-200 rounded p-4">
            <div class="text-sm text-red-600 font-medium">Gagal</div>
            <div class="text-2xl font-bold text-red-700">${failedCount}</div>
          </div>
        </div>`;

    if (r.details && r.details.length > 0) {
      html += `
        <div class="mb-4">
          <h3 class="font-medium mb-2">Detail Upload:</h3>
          <div class="max-h-64 overflow-y-auto border rounded">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
              <thead class="bg-gray-50 sticky top-0">
                <tr>
                  <th class="px-4 py-2 text-left">Row</th>
                  <th class="px-4 py-2 text-left">Reff ID</th>
                  <th class="px-4 py-2 text-left">Nama File</th>
                  <th class="px-4 py-2 text-left">Status</th>
                  <th class="px-4 py-2 text-left">Pesan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">`;

      r.details.forEach(detail => {
        const bgClass = detail.status === 'uploaded' || detail.status === 'force_updated' ? 'bg-green-50' :
                       detail.status === 'skipped' ? 'bg-yellow-50' : 'bg-blue-50';

        const statusBadge = detail.status === 'uploaded' ?
          '<span class="px-2 py-1 bg-green-200 text-green-800 rounded text-xs">Uploaded</span>' :
          detail.status === 'force_updated' ?
          '<span class="px-2 py-1 bg-orange-200 text-orange-800 rounded text-xs">Force Updated</span>' :
          detail.status === 'validated' ?
          '<span class="px-2 py-1 bg-blue-200 text-blue-800 rounded text-xs">Validated</span>' :
          '<span class="px-2 py-1 bg-yellow-200 text-yellow-800 rounded text-xs">Skipped</span>';

        html += `
          <tr class="${bgClass}">
            <td class="px-4 py-2 font-bold">${detail.row}</td>
            <td class="px-4 py-2 font-mono text-xs">${detail.reff_id}</td>
            <td class="px-4 py-2">${detail.nama_file || '-'}</td>
            <td class="px-4 py-2">${statusBadge}</td>
            <td class="px-4 py-2 text-xs">${detail.message}</td>
          </tr>`;
      });

      html += `
              </tbody>
            </table>
          </div>
        </div>`;
    }

    if (r.failed && r.failed.length > 0) {
      html += `
        <div class="mb-4">
          <h3 class="font-medium mb-2 text-red-700">
            <i class="fas fa-exclamation-triangle mr-2"></i>Data Gagal (${r.failed.length} rows)
          </h3>
          <div class="max-h-64 overflow-y-auto border border-red-200 rounded">
            <table class="min-w-full divide-y divide-red-200 text-sm">
              <thead class="bg-red-50 sticky top-0">
                <tr>
                  <th class="px-4 py-2 text-left">Row</th>
                  <th class="px-4 py-2 text-left">Reff ID</th>
                  <th class="px-4 py-2 text-left">Error</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-red-100">`;

      r.failed.forEach(failed => {
        html += `
          <tr class="bg-red-50">
            <td class="px-4 py-2 font-bold">${failed.row}</td>
            <td class="px-4 py-2 font-mono text-xs">${failed.data.reff_id || '-'}</td>
            <td class="px-4 py-2 text-red-700">
              <ul class="list-disc ml-4">
                ${failed.errors.map(err => `<li>${err}</li>`).join('')}
              </ul>
            </td>
          </tr>`;
      });

      html += `
              </tbody>
            </table>
          </div>
        </div>`;
    }

    html += `</div>`;
    return html;
  }

  function showErrorUI(errorMessage) {
    intervals.forEach(id => clearInterval(id));
    isProcessing = false;

    document.getElementById('spinnerIcon').classList.add('hidden');

    document.getElementById('modalTitle').textContent = 'Import Gagal';
    progressStatus.textContent = 'Terjadi kesalahan';
    progressText.textContent = 'Proses dihentikan';

    progressBar.className = 'bg-red-600 h-3 rounded-full transition-all duration-300 ease-out';

    progressInfo.innerHTML = `<i class="fas fa-times-circle text-red-600 mr-1"></i> ${errorMessage}`;
    progressInfo.className = 'bg-red-50 rounded-lg p-3';

    document.getElementById('processingInfo').classList.add('hidden');
    document.getElementById('actionButton').classList.remove('hidden');
  }

  function submitFormViaAjax() {
    const formData = new FormData(form);

    fetch(form.action, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      }
    })
    .then(response => {
      if (!response.ok) {
        return response.json().then(err => Promise.reject(err));
      }
      return response.json();
    })
    .then(data => {
      if (data.success) {
        showCompletionUI(data.results || data);
      } else {
        showErrorUI(data.message || 'Import gagal');
      }
    })
    .catch(error => {
      const errorMessage = error.message || error.error || 'Terjadi kesalahan pada server';
      showErrorUI(errorMessage);
    });
  }

  function updateProgress(percent) {
    percent = Math.min(100, Math.max(0, percent));

    progressBar.style.width = percent + '%';
    progressPercent.textContent = Math.floor(percent) + '%';

    if (percent < 30) {
      progressBar.className = 'bg-blue-600 h-3 rounded-full transition-all duration-300 ease-out';
    } else if (percent < 70) {
      progressBar.className = 'bg-indigo-600 h-3 rounded-full transition-all duration-300 ease-out';
    } else if (percent < 100) {
      progressBar.className = 'bg-green-600 h-3 rounded-full transition-all duration-300 ease-out';
    } else {
      progressBar.className = 'bg-green-700 h-3 rounded-full transition-all duration-300 ease-out';
    }
  }

  window.addEventListener('unload', function() {
    intervals.forEach(id => clearInterval(id));
  });
});
</script>
@endpush

@endsection
