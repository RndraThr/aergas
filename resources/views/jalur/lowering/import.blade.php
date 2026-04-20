@extends('layouts.app')

@section('title', 'Import Data Lowering')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Import Data Lowering</h1>
            <p class="text-gray-600">Import data lowering dari file Excel</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('jalur.lowering.duplicates') }}" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-md flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                </svg>
                Cek Duplikat
            </a>
            <a href="{{ route('jalur.lowering.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali ke Daftar
            </a>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ session('success') }}</span>
            </div>

            @if(session('import_summary'))
                @php $s = session('import_summary'); @endphp
                <div class="mt-4 pl-8">
                    <p class="font-semibold">Hasil Import:</p>
                    <ul class="list-disc list-inside mt-2 text-sm">
                        @if($s['new'] > 0) <li>🟢 NEW: {{ $s['new'] }} data</li> @endif
                        @if($s['update'] > 0) <li>🔵 UPDATE: {{ $s['update'] }} data</li> @endif
                        @if($s['recall'] > 0) <li class="text-red-700">🔴 RECALL: {{ $s['recall'] }} data (approved → draft)</li> @endif
                        @if($s['skip_no_change'] > 0) <li>🟡 Skip (no change): {{ $s['skip_no_change'] }}</li> @endif
                        @if($s['skip_approved'] > 0) <li>🟠 Skip (approved protected): {{ $s['skip_approved'] }}</li> @endif
                        @if($s['duplicate_in_file'] > 0) <li>🟣 Duplicate in file: {{ $s['duplicate_in_file'] }}</li> @endif
                        @if($s['error'] > 0) <li class="text-red-600">⚫ Error: {{ $s['error'] }}</li> @endif
                    </ul>
                </div>
            @endif
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- Instructions Card --}}
    <div class="bg-blue-50 border-l-4 border-blue-500 p-6 mb-6 rounded-lg">
        <h2 class="text-lg font-semibold text-blue-900 mb-3 flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Petunjuk Import
        </h2>
        <ol class="list-decimal list-inside space-y-2 text-blue-900">
            <li>Download template Excel dengan klik tombol "Download Template" di bawah</li>
            <li>Alur: Upload file → <strong>Preview</strong> (tinjau perubahan) → <strong>Commit</strong> (simpan ke DB)</li>
            <li>Deteksi duplikat pakai kombinasi <strong>6 field</strong>: Line Number + Tanggal + Tipe Bongkaran + Lowering + Bongkaran + Kedalaman. Jadi 2 pekerjaan berbeda di line & tanggal sama (dengan nilai lowering/bongkaran/kedalaman beda) tetap dianggap 2 record valid.</li>
            <li>Mode <strong>Force Update</strong>: default OFF (hanya isi field yang masih kosong). ON = timpa semua, dan record yang sudah approved akan di-recall ke draft + butuh re-approval.</li>
            <li>Isi data sesuai kolom yang disediakan:
                <ul class="list-disc list-inside ml-6 mt-1 space-y-1 text-sm">
                    <li><strong>diameter:</strong> 63, 90, atau 180</li>
                    <li><strong>cluster_code:</strong> Kode cluster yang sudah ada (contoh: GDK, KRG)</li>
                    <li><strong>line_number:</strong> Suffix line number (contoh: 001, 002) - akan auto-generate jadi 63-GDK-LN001</li>
                    <li><strong>tanggal_jalur:</strong> Format YYYY-MM-DD atau DD-MMM-YY (contoh: 2025-09-15 atau 15-Sep-25)</li>
                    <li><strong>tipe_bongkaran:</strong> Manual Boring, Open Cut, Crossing, Zinker, HDD, Manual Boring - PK, Crossing - PK</li>
                    <li><strong>lowering & bongkaran:</strong> Angka dalam meter (contoh: 45.50) + <strong class="text-red-600">WAJIB ada hyperlink foto Google Drive</strong></li>
                    <li><strong>kedalaman:</strong> Angka dalam cm (contoh: 80)</li>
                    <li><strong>cassing_quantity, marker_tape_quantity, concrete_slab_quantity:</strong> Opsional, jika diisi + <strong class="text-red-600">WAJIB ada hyperlink foto</strong></li>
                    <li><strong>cassing_type:</strong> Wajib jika ada cassing_quantity (4_inch atau 8_inch)</li>
                    <li><strong>mc_100:</strong> Opsional, angka dalam meter (akan update ke Line Number)</li>
                </ul>
            </li>
            <li><strong class="text-red-600">PENTING:</strong> Untuk kolom yang memerlukan foto, masukkan angka di cell dan tambahkan <strong>hyperlink Google Drive</strong> ke cell tersebut (klik kanan cell → Insert Link)</li>
            <li>Simpan file Excel</li>
            <li>Upload file menggunakan form di bawah</li>
        </ol>
    </div>

    {{-- Download Template Button --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">1. Download Template Excel</h3>
        <a href="{{ route('jalur.lowering.import.template') }}"
           class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Download Template Excel
        </a>
        <p class="text-sm text-gray-600 mt-2">Template sudah termasuk contoh data dan format yang benar</p>
    </div>

    {{-- Upload Form --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">2. Upload File Excel</h3>

        <form action="{{ route('jalur.lowering.import.preview') }}" method="POST" enctype="multipart/form-data" id="importForm">
            @csrf

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File Excel</label>
                <div class="flex items-center justify-center w-full">
                    <label for="file-upload" class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg class="w-12 h-12 mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="mb-2 text-sm text-gray-500">
                                <span class="font-semibold">Klik untuk upload</span> atau drag & drop
                            </p>
                            <p class="text-xs text-gray-500">File Excel (.xlsx, .xls) maksimal 10MB</p>
                            <p id="file-name" class="mt-4 text-sm text-green-600 font-semibold hidden"></p>
                        </div>
                        <input id="file-upload" name="file" type="file" class="hidden" accept=".xlsx,.xls" required />
                    </label>
                </div>
                @error('file')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6 border-t border-gray-200 pt-4 space-y-4">
                <label class="flex items-start gap-3 cursor-pointer group">
                    <input type="checkbox" name="force_update" value="1" class="mt-1 h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded">
                    <div class="flex-1">
                        <div class="text-sm font-semibold text-gray-900 group-hover:text-orange-700 flex items-center gap-2">
                            <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Force Update (Timpa Field Non-Kosong pada Record Draft)
                        </div>
                        <div class="text-xs text-gray-600 mt-1">
                            <strong>Default (OFF):</strong> Hanya isi field yang masih <strong>kosong</strong> di DB.<br>
                            <strong>Aktif (ON):</strong> Timpa semua field dari Excel <strong>hanya untuk record berstatus draft</strong>. Record approved tidak terpengaruh.
                        </div>
                    </div>
                </label>

                <label class="flex items-start gap-3 cursor-pointer group">
                    <input type="checkbox" name="allow_recall" value="1" class="mt-1 h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                    <div class="flex-1">
                        <div class="text-sm font-semibold text-gray-900 group-hover:text-red-700 flex items-center gap-2">
                            <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
                            </svg>
                            Allow Recall (Izinkan Recall Record Approved)
                        </div>
                        <div class="text-xs text-gray-600 mt-1">
                            <strong>Default (OFF):</strong> Record approved (<code>acc_tracer</code>/<code>acc_cgp</code>) <strong>tidak pernah disentuh</strong> — masuk kategori SKIP.<br>
                            <strong>Aktif (ON):</strong> Record approved yang berubah akan di-<em>recall</em> ke status <code>draft</code>, foto direset → butuh re-approval ulang dari Tracer & CGP.
                        </div>
                    </div>
                </label>
            </div>

            <div class="flex gap-4">
                <button type="submit"
                        class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-md flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    Preview Import
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center" style="z-index: 9999;">
    <div class="bg-white rounded-lg p-8 max-w-md mx-4 text-center">
        <div class="mb-4">
            <svg class="animate-spin h-16 w-16 mx-auto text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        <h3 id="loadingTitle" class="text-xl font-semibold text-gray-800 mb-2">Memproses Import...</h3>
        <p id="loadingMessage" class="text-gray-600 mb-4">Mohon tunggu, sedang mengimport data lowering Anda.</p>
        <div class="text-sm text-gray-500">
            <p>⏱️ Proses ini mungkin memakan waktu beberapa saat</p>
            <p class="mt-1">🚫 Jangan tutup atau refresh halaman ini</p>
        </div>
    </div>
</div>

<script>
// File upload handler
document.getElementById('file-upload').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name;
    const fileNameDisplay = document.getElementById('file-name');

    if (fileName) {
        fileNameDisplay.textContent = 'File dipilih: ' + fileName;
        fileNameDisplay.classList.remove('hidden');
    } else {
        fileNameDisplay.classList.add('hidden');
    }
});

// Show loading overlay
function showLoading(title, message) {
    const overlay = document.getElementById('loadingOverlay');
    const titleEl = document.getElementById('loadingTitle');
    const messageEl = document.getElementById('loadingMessage');

    if (title) titleEl.textContent = title;
    if (message) messageEl.textContent = message;

    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
}

// Hide loading overlay
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    overlay.classList.add('hidden');
    overlay.classList.remove('flex');
}

document.getElementById('importForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('file-upload');

    if (!fileInput.files.length) {
        e.preventDefault();
        alert('Silakan pilih file Excel terlebih dahulu');
        return;
    }

    showLoading('Memvalidasi Data...', 'Sedang memproses file untuk preview.');
});
</script>
@endsection
