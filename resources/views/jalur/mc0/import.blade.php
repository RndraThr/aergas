@extends('layouts.app')

@section('title', 'Import MC-0 Jalur')

@section('content')
    <div class="container mx-auto px-6 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Import MC-0 Jalur</h1>
                <p class="text-gray-600">Import data MC-0 (Estimasi Panjang) untuk Line Number</p>
            </div>
            <a href="{{ route('jalur.lowering.index') }}"
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                Kembali ke Data Lowering
            </a>
        </div>

        {{-- Success/Error Messages --}}
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>

                @if(session('import_results'))
                    @php $results = session('import_results'); @endphp
                    <div class="mt-4 pl-8">
                        <p class="font-semibold">Hasil Import:</p>
                        <ul class="list-disc list-inside mt-2">
                            <li>Berhasil: {{ $results['success'] }} baris</li>
                            @if($results['updated'] > 0)
                                <li>Data di-update: {{ $results['updated'] }} baris</li>
                            @endif
                            @if($results['created'] > 0)
                                <li>Data baru dibuat: {{ $results['created'] }} baris</li>
                            @endif
                            @if($results['skipped'] > 0)
                                <li>Dilewati: {{ $results['skipped'] }} baris</li>
                            @endif
                            @if(!empty($results['failed']))
                                <li class="text-red-600">Gagal: {{ count($results['failed']) }} baris</li>
                            @endif
                        </ul>

                        @if(!empty($results['failed']))
                            <div class="mt-4 bg-white border border-red-300 rounded p-4">
                                <p class="font-semibold text-red-700 mb-2">Detail Error:</p>
                                <div class="max-h-64 overflow-y-auto">
                                    @foreach($results['failed'] as $failure)
                                        <div class="mb-3 pb-3 border-b border-gray-200 last:border-0">
                                            <p class="text-sm font-medium">Baris {{ $failure['row'] }}:</p>
                                            <ul class="list-disc list-inside text-sm text-red-600 ml-4">
                                                @foreach($failure['errors'] as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        {{-- Instructions Card --}}
        <div class="bg-blue-50 border-l-4 border-blue-500 p-6 mb-6 rounded-lg">
            <h2 class="text-lg font-semibold text-blue-900 mb-3 flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Petunjuk Import MC-0
            </h2>
            <ol class="list-decimal list-inside space-y-2 text-blue-900">
                <li>Download template Excel dengan klik tombol "Download Template" di bawah</li>
                <li>Isi data sesuai kolom yang disediakan:
                    <ul class="list-disc list-inside ml-6 mt-1 space-y-1 text-sm">
                        <li><strong>diameter:</strong> 63, 90, atau 180</li>
                        <li><strong>cluster_code:</strong> Kode cluster yang sudah ada (contoh: GDK, KRG)</li>
                        <li><strong>line_number:</strong> Suffix line number (contoh: 001, 002) - akan auto-generate jadi
                            63-GDK-LN001</li>
                        <li><strong>mc_0:</strong> Angka dalam meter (contoh: 125.50) - estimasi panjang jalur</li>
                    </ul>
                </li>
                <li><strong class="text-blue-700">Catatan:</strong> Jika Line Number sudah ada, sistem akan
                    <strong>UPDATE</strong> nilai MC-0. Jika belum ada, akan <strong>CREATE</strong> Line Number baru</li>
                <li>Simpan file Excel</li>
                <li>Upload file menggunakan form di bawah</li>
            </ol>
        </div>

        {{-- Download Template Button --}}
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">1. Download Template Excel</h3>
            <a href="{{ route('jalur.mc0.import.template') }}"
                class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                Download Template Excel MC-0
            </a>
            <p class="text-sm text-gray-600 mt-2">Template sudah termasuk contoh data dan format yang benar</p>
        </div>

        {{-- Upload Form --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">2. Upload File Excel</h3>

            <form action="{{ route('jalur.mc0.import.execute') }}" method="POST" enctype="multipart/form-data"
                id="importForm">
                @csrf

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File Excel</label>
                    <div class="flex items-center justify-center w-full">
                        <label for="file-upload"
                            class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-12 h-12 mb-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
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

                <div class="flex gap-4">
                    <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-md flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Import Data MC-0
                    </button>

                    <button type="button" onclick="previewImport()"
                        class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-md flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                            </path>
                        </svg>
                        Preview Dulu (Validasi Tanpa Import)
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center"
        style="z-index: 9999;">
        <div class="bg-white rounded-lg p-8 max-w-md mx-4 text-center">
            <div class="mb-4">
                <svg class="animate-spin h-16 w-16 mx-auto text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </div>
            <h3 id="loadingTitle" class="text-xl font-semibold text-gray-800 mb-2">Memproses Import...</h3>
            <p id="loadingMessage" class="text-gray-600 mb-4">Mohon tunggu, sedang mengimport data MC-0 Anda.</p>
            <div class="text-sm text-gray-500">
                <p>⏱️ Proses ini mungkin memakan waktu beberapa saat</p>
                <p class="mt-1">🚫 Jangan tutup atau refresh halaman ini</p>
            </div>
        </div>
    </div>

    <script>
        // File upload handler
        document.getElementById('file-upload').addEventListener('change', function (e) {
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

        // Flag to track if preview is being triggered
        let isPreviewMode = false;

        // Preview function
        function previewImport() {
            const form = document.getElementById('importForm');
            const fileInput = document.getElementById('file-upload');

            if (!fileInput.files.length) {
                alert('Silakan pilih file Excel terlebih dahulu');
                return;
            }

            // Set preview flag
            isPreviewMode = true;

            // Show loading
            showLoading('Memvalidasi Data...', 'Mohon tunggu, sedang melakukan preview validasi data MC-0.');

            // Change form action to preview route
            form.action = "{{ route('jalur.mc0.import.preview') }}";

            // Submit form
            form.submit();
        }

        // Handle form submit for direct import
        document.getElementById('importForm').addEventListener('submit', function (e) {
            const fileInput = document.getElementById('file-upload');

            if (!fileInput.files.length) {
                e.preventDefault();
                alert('Silakan pilih file Excel terlebih dahulu');
                return;
            }

            // Show loading for direct import (not preview)
            if (!isPreviewMode) {
                showLoading('Mengimport Data MC-0...', 'Mohon tunggu, sedang mengimport data MC-0 ke database.');
            }
        });
    </script>
@endsection