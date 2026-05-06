@extends('layouts.app')

@section('title', 'Import Data Lowering')

@section('content')
<div class="container mx-auto px-6 py-8">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-1">Import Data Lowering</h1>
            <p class="text-gray-500 text-sm">Sheet terhubung: <strong>{{ config('services.google_sheets.sheet_name_pe', 'PE') }}</strong></p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('jalur.lowering.duplicates') }}" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-md flex items-center text-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                </svg>
                Cek Duplikat
            </a>
            <a href="{{ route('jalur.lowering.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md flex items-center text-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-5 rounded">
            <div class="flex items-center gap-2"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span>{{ session('success') }}</span></div>
            @if(session('import_summary'))
                @php $s = session('import_summary'); @endphp
                <ul class="list-disc list-inside mt-2 text-sm pl-7">
                    @if($s['new'] > 0)<li>🟢 NEW: {{ $s['new'] }} data</li>@endif
                    @if($s['update'] > 0)<li>🔵 UPDATE: {{ $s['update'] }} data</li>@endif
                    @if($s['recall'] > 0)<li class="text-red-700">🔴 RECALL: {{ $s['recall'] }} data</li>@endif
                    @if($s['skip_no_change'] > 0)<li>🟡 Skip (no change): {{ $s['skip_no_change'] }}</li>@endif
                    @if($s['skip_approved'] > 0)<li>🟠 Skip (approved): {{ $s['skip_approved'] }}</li>@endif
                    @if($s['error'] > 0)<li class="text-red-600">⚫ Error: {{ $s['error'] }}</li>@endif
                </ul>
            @endif
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-5 rounded flex items-center gap-2">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="mb-0">
        <div class="flex border-b border-gray-200">
            <button onclick="switchTab('sheet')" id="tabBtnSheet"
                    class="px-6 py-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 border-green-600 text-green-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Sync dari Google Sheet
            </button>
            <button onclick="switchTab('manual')" id="tabBtnManual"
                    class="px-6 py-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 border-transparent text-gray-500 hover:text-gray-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Import Manual (Excel)
            </button>
        </div>
    </div>

    {{-- ======================== TAB 1: SHEET SYNC ======================== --}}
    <div id="tabSheet" class="bg-white rounded-b-lg rounded-tr-lg shadow p-6">

        {{-- Options row --}}
        <div class="flex flex-wrap gap-6 mb-5 pb-5 border-b border-gray-100">
            {{-- Force Update --}}
            <label class="flex items-start gap-2.5 cursor-pointer select-none group" id="syncForceBox">
                <input type="checkbox" id="sheetForceUpdate" value="1"
                       class="mt-0.5 w-4 h-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500"
                       onchange="onOptionChange()">
                <div>
                    <div class="text-sm font-semibold text-gray-800 flex items-center gap-1.5">
                        Force Update
                        <span id="fuBadgeOff" class="text-xs bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded-full">OFF</span>
                        <span id="fuBadgeOn"  class="text-xs bg-orange-500 text-white px-1.5 py-0.5 rounded-full hidden">ON</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5 max-w-xs">
                        Timpa semua field pada record <strong>draft</strong> yang berbeda dengan sheet (tidak memengaruhi approved).
                    </p>
                </div>
            </label>

            {{-- Allow Recall --}}
            <label class="flex items-start gap-2.5 cursor-pointer select-none group" id="syncRecallBox">
                <input type="checkbox" id="sheetAllowRecall" value="1"
                       class="mt-0.5 w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500"
                       onchange="onOptionChange()">
                <div>
                    <div class="text-sm font-semibold text-gray-800 flex items-center gap-1.5">
                        Allow Recall
                        <span id="arBadgeOff" class="text-xs bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded-full">OFF</span>
                        <span id="arBadgeOn"  class="text-xs bg-red-500 text-white px-1.5 py-0.5 rounded-full hidden">ON</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5 max-w-xs">
                        Izinkan update record <strong>acc_tracer / acc_cgp</strong> — status direset ke <code>draft</code> + photo approval di-reset.
                    </p>
                </div>
            </label>
        </div>

        {{-- Fetch button — form POST ke sheet-sync-preview --}}
        <form action="{{ route('jalur.lowering.import.sheet-sync-preview') }}" method="POST" id="sheetSyncForm">
            @csrf
            <input type="hidden" name="force_update" id="hiddenForceUpdate" value="0">
            <input type="hidden" name="allow_recall" id="hiddenAllowRecall" value="0">
            <button type="submit" id="btnFetch"
                    class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg font-semibold transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Fetch Preview dari Sheet
            </button>
        </form>

    </div>

    {{-- ======================== TAB 2: MANUAL EXCEL ======================== --}}
    <div id="tabManual" class="hidden bg-white rounded-b-lg rounded-tr-lg shadow">

        {{-- Instructions --}}
        <div class="bg-blue-50 border-b border-blue-100 px-6 py-5">
            <h2 class="text-base font-semibold text-blue-900 mb-2 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Petunjuk Import Excel
            </h2>
            <ol class="list-decimal list-inside space-y-1.5 text-blue-900 text-sm">
                <li>Download template → isi data → upload</li>
                <li>Alur: Upload → <strong>Preview</strong> → <strong>Commit</strong></li>
                <li>Kolom wajib: diameter, cluster_code, line_number, tanggal_jalur, tipe_bongkaran, lowering+foto, bongkaran+foto, kedalaman</li>
                <li>tipe_bongkaran: Manual Boring, Open Cut, Crossing, Zinker, HDD, Manual Boring - PK, Crossing - PK</li>
                <li><strong class="text-red-600">PENTING:</strong> Kolom lowering & bongkaran WAJIB ada hyperlink foto Google Drive</li>
            </ol>
        </div>

        <div class="p-6 space-y-6">
            {{-- Download template --}}
            <div>
                <h3 class="text-base font-semibold text-gray-800 mb-3">1. Download Template</h3>
                <a href="{{ route('jalur.lowering.import.template') }}"
                   class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download Template Excel
                </a>
            </div>

            {{-- Upload form --}}
            <div>
                <h3 class="text-base font-semibold text-gray-800 mb-3">2. Upload & Preview</h3>
                <form action="{{ route('jalur.lowering.import.preview') }}" method="POST" enctype="multipart/form-data" id="importForm">
                    @csrf
                    <label for="file-upload" class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 mb-4">
                        <div class="flex flex-col items-center py-4">
                            <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p class="text-sm text-gray-500"><span class="font-semibold">Klik untuk upload</span> atau drag & drop</p>
                            <p class="text-xs text-gray-400 mt-1">Excel (.xlsx, .xls) — maks. 10MB</p>
                            <p id="file-name" class="mt-3 text-sm text-green-600 font-semibold hidden"></p>
                        </div>
                        <input id="file-upload" name="file" type="file" class="hidden" accept=".xlsx,.xls" required />
                    </label>
                    @error('file')<p class="text-sm text-red-600 -mt-2 mb-3">{{ $message }}</p>@enderror

                    <div class="space-y-3 border-t pt-4">
                        <label class="flex items-start gap-3 cursor-pointer group">
                            <input type="checkbox" name="force_update" value="1" class="mt-1 h-4 w-4 text-orange-600 border-gray-300 rounded">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Force Update</div>
                                <p class="text-xs text-gray-500">OFF: hanya isi field kosong. ON: timpa semua field draft dari Excel.</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-3 cursor-pointer group">
                            <input type="checkbox" name="allow_recall" value="1" class="mt-1 h-4 w-4 text-red-600 border-gray-300 rounded">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Allow Recall</div>
                                <p class="text-xs text-gray-500">Izinkan recall record approved → draft + reset foto.</p>
                            </div>
                        </label>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="inline-flex items-center gap-2 bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-2.5 rounded-lg font-semibold transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Preview Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Loading Overlay --}}
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center" style="z-index:9999">
    <div class="bg-white rounded-xl p-8 max-w-sm mx-4 text-center shadow-2xl">
        <svg class="animate-spin h-14 w-14 mx-auto text-green-600 mb-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
        </svg>
        <p class="text-gray-700 font-medium" id="loadingText">Memproses...</p>
        <p class="text-xs text-gray-400 mt-1">Jangan tutup atau refresh halaman</p>
    </div>
</div>

<script>
// ── Tab switching ──────────────────────────────────────────────
function switchTab(tab) {
    const isSheet = tab === 'sheet';
    document.getElementById('tabSheet').classList.toggle('hidden', !isSheet);
    document.getElementById('tabManual').classList.toggle('hidden', isSheet);
    document.getElementById('tabBtnSheet').className  = isSheet
        ? 'px-6 py-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 border-green-600 text-green-700'
        : 'px-6 py-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 border-transparent text-gray-500 hover:text-gray-700';
    document.getElementById('tabBtnManual').className = !isSheet
        ? 'px-6 py-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 border-blue-600 text-blue-700'
        : 'px-6 py-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 border-transparent text-gray-500 hover:text-gray-700';
}

// ── Option badges ─────────────────────────────────────────────
function onOptionChange() {
    const fu = document.getElementById('sheetForceUpdate').checked;
    const ar = document.getElementById('sheetAllowRecall').checked;
    document.getElementById('fuBadgeOff').classList.toggle('hidden', fu);
    document.getElementById('fuBadgeOn').classList.toggle('hidden', !fu);
    document.getElementById('arBadgeOff').classList.toggle('hidden', ar);
    document.getElementById('arBadgeOn').classList.toggle('hidden', !ar);
    // Sync to hidden form inputs
    document.getElementById('hiddenForceUpdate').value = fu ? '1' : '0';
    document.getElementById('hiddenAllowRecall').value = ar ? '1' : '0';
}

// ── Overlay helpers ───────────────────────────────────────────
const overlay = document.getElementById('loadingOverlay');
function showOverlay(text) { document.getElementById('loadingText').textContent = text || 'Memproses...'; overlay.style.display = 'flex'; }
function hideOverlay()     { overlay.style.display = 'none'; }

let sheetData = null;

// ── Sheet sync form loading ───────────────────────────────────
document.getElementById('sheetSyncForm').addEventListener('submit', function() {
    showOverlay('Mengambil data dari Google Sheet...');
});

// ── Manual import form ────────────────────────────────────────
document.getElementById('file-upload').addEventListener('change', function() {
    const name = this.files[0]?.name;
    const el   = document.getElementById('file-name');
    name ? (el.textContent = '✓ ' + name, el.classList.remove('hidden')) : el.classList.add('hidden');
});

document.getElementById('importForm').addEventListener('submit', function(e) {
    if (!document.getElementById('file-upload').files.length) {
        e.preventDefault();
        alert('Silakan pilih file Excel terlebih dahulu');
        return;
    }
    showOverlay('Memvalidasi data...');
});
</script>
@endsection
