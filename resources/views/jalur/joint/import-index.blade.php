@extends('layouts.app')

@section('title', 'Import Data Joint')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Import Data Joint</h1>
            <p class="text-gray-600">Import data joint/sambungan dari file Excel</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('jalur.joint.import.duplicates') }}"
               class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md flex items-center text-sm">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Kelola Duplikat DB
            </a>
            <a href="{{ route('jalur.joint.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md flex items-center text-sm">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali ke Daftar
            </a>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded flex items-center">
            <svg class="w-5 h-5 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('warning'))
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded flex items-center">
            <svg class="w-5 h-5 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <span>{{ session('warning') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded flex items-center">
            <svg class="w-5 h-5 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Instructions --}}
    <div class="bg-purple-50 border-l-4 border-purple-500 p-6 mb-6 rounded-lg">
        <h2 class="text-lg font-semibold text-purple-900 mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Petunjuk Import
        </h2>
        <ol class="list-decimal list-inside space-y-2 text-purple-900 text-sm">
            <li>Download template Excel, isi data sesuai format, lalu upload.</li>
            <li><strong>joint_number:</strong> Format <code>{CLUSTER}-{FITTING}{CODE}</code> (mis. <code>KRG-CP001</code>).
                Untuk diameter 180: <code>BF.05</code> / <code>EF.010</code>.
                <span class="text-red-600 font-semibold">PENTING:</span> cell ini harus punya hyperlink ke foto Google Drive.</li>
            <li><strong>tanggal_joint:</strong> Format YYYY-MM-DD (mis. 2025-01-15).</li>
            <li><strong>diameter:</strong> 63, 90, 110, 160, 180, atau 200.</li>
            <li><strong>joint_line_from / joint_line_to:</strong> Nomor line yang disambung (mis. <code>63-KRG-LN001</code>).
                Isi <code>EXISTING</code> jika line sudah ada sebelumnya.</li>
            <li><strong>joint_line_optional:</strong> Wajib diisi untuk fitting Equal Tee (TE). Bisa <code>EXISTING</code>.</li>
            <li><strong>tipe_penyambungan:</strong> <code>EF</code> atau <code>BF</code>.</li>
        </ol>
        <div class="mt-3 p-3 bg-purple-100 rounded text-xs text-purple-800">
            <strong>Referensi Fitting Type Code:</strong>
            CP = Coupler &nbsp;|&nbsp; ECP = End Cap &nbsp;|&nbsp; EL90 = Elbow 90° &nbsp;|&nbsp; EL45 = Elbow 45° &nbsp;|&nbsp;
            TE = Equal Tee &nbsp;|&nbsp; RD = Reducer &nbsp;|&nbsp; FA = Flange Adaptor &nbsp;|&nbsp; VL = Valve &nbsp;|&nbsp;
            TF = Transition Fitting &nbsp;|&nbsp; TS = Tapping Saddle
        </div>
    </div>

    {{-- Download Template --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">1. Download Template Excel</h3>
        <a href="{{ route('jalur.joint.import.template') }}"
           class="inline-flex items-center bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-md">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Download Template
        </a>
    </div>

    {{-- Upload Form --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">2. Upload & Konfigurasi Import</h3>

        <form action="{{ route('jalur.joint.import.execute') }}" method="POST" enctype="multipart/form-data" id="importForm">
            @csrf

            {{-- File upload area --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File Excel</label>
                <label for="file-upload" class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                    <div class="flex flex-col items-center justify-center py-4">
                        <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-sm text-gray-500"><span class="font-semibold">Klik untuk upload</span> atau drag & drop</p>
                        <p class="text-xs text-gray-400 mt-1">File Excel (.xlsx, .xls) — maks. 10 MB</p>
                        <p id="file-name" class="mt-3 text-sm text-purple-700 font-semibold hidden"></p>
                    </div>
                    <input id="file-upload" name="file" type="file" class="hidden" accept=".xlsx,.xls" />
                </label>
                @error('file')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Options --}}
            <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Force Update --}}
                <div class="border rounded-lg p-4" id="forceUpdateBox">
                    <label class="flex items-start cursor-pointer gap-3">
                        <input type="checkbox" name="force_update" id="force_update" value="1"
                               class="mt-1 w-4 h-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500"
                               onchange="updateOptionStyles()">
                        <div>
                            <div class="font-semibold text-gray-800 flex items-center gap-2">
                                Force Update
                                <span id="fuBadgeOff" class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">OFF</span>
                                <span id="fuBadgeOn"  class="text-xs bg-orange-500 text-white px-2 py-0.5 rounded-full hidden">ON</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                <strong>OFF (default):</strong> Hanya mengisi field yang masih kosong pada data draft.<br>
                                <strong>ON:</strong> Timpa semua field draft dengan data baru dari Excel, termasuk foto.
                            </p>
                        </div>
                    </label>
                </div>

                {{-- Allow Recall --}}
                <div class="border rounded-lg p-4" id="allowRecallBox">
                    <label class="flex items-start cursor-pointer gap-3">
                        <input type="checkbox" name="allow_recall" id="allow_recall" value="1"
                               class="mt-1 w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500"
                               onchange="updateOptionStyles()">
                        <div>
                            <div class="font-semibold text-gray-800 flex items-center gap-2">
                                Allow Recall
                                <span id="arBadgeOff" class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">OFF</span>
                                <span id="arBadgeOn"  class="text-xs bg-red-500 text-white px-2 py-0.5 rounded-full hidden">ON</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                <strong>OFF (default):</strong> Data yang sudah approved/rejected tidak diubah sama sekali.<br>
                                <strong>ON:</strong> Jika ada perubahan data krusial, record di-reset ke draft lalu diupdate.
                            </p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Buttons --}}
            <div class="flex gap-4">
                <button type="button" onclick="doPreview()"
                        class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-md flex items-center justify-center font-semibold">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    Preview & Validasi Dulu
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Loading Overlay --}}
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center" style="z-index:9999">
    <div class="bg-white rounded-lg p-8 max-w-sm mx-4 text-center shadow-xl">
        <svg class="animate-spin h-14 w-14 mx-auto text-purple-600 mb-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <h3 class="text-lg font-semibold text-gray-800 mb-1">Memvalidasi Data…</h3>
        <p class="text-gray-500 text-sm">Mohon tunggu, jangan tutup halaman ini.</p>
    </div>
</div>

<script>
document.getElementById('file-upload').addEventListener('change', function () {
    const name = this.files[0]?.name;
    const el   = document.getElementById('file-name');
    if (name) { el.textContent = '✓ ' + name; el.classList.remove('hidden'); }
    else       { el.classList.add('hidden'); }
});

function updateOptionStyles() {
    const fu = document.getElementById('force_update').checked;
    const ar = document.getElementById('allow_recall').checked;

    document.getElementById('forceUpdateBox').className = fu
        ? 'border rounded-lg p-4 border-orange-400 bg-orange-50'
        : 'border rounded-lg p-4';
    document.getElementById('fuBadgeOff').classList.toggle('hidden', fu);
    document.getElementById('fuBadgeOn').classList.toggle('hidden', !fu);

    document.getElementById('allowRecallBox').className = ar
        ? 'border rounded-lg p-4 border-red-400 bg-red-50'
        : 'border rounded-lg p-4';
    document.getElementById('arBadgeOff').classList.toggle('hidden', ar);
    document.getElementById('arBadgeOn').classList.toggle('hidden', !ar);
}

function doPreview() {
    const fileInput = document.getElementById('file-upload');
    if (!fileInput.files.length) {
        alert('Silakan pilih file Excel terlebih dahulu.');
        return;
    }
    document.getElementById('loadingOverlay').classList.remove('hidden');
    document.getElementById('loadingOverlay').classList.add('flex');

    const form = document.getElementById('importForm');
    form.action = "{{ route('jalur.joint.import.preview') }}";
    form.submit();
}
</script>
@endsection
