@extends('layouts.app')

@section('title', 'Pengaturan Sinkronisasi Google Sheets')

@section('content')
    <div class="row">
        <div class="col-12">

            <!-- Header Section with Gradient -->
            <div class="bg-gradient-to-r from-blue-600 to-cyan-600 rounded-xl shadow-lg p-6 text-white mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="bg-white/20 p-3 rounded-lg backdrop-blur-sm">
                            <i class="fas fa-cogs text-2xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">Sinkronisasi & Pengaturan Otomatisasi</h1>
                            <p class="text-blue-100 mt-1">Konfigurasikan sinkronisasi latar belakang Google Sheets</p>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2 text-lg"></i>
                        <p>{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2 text-lg"></i>
                        <p>{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">

                <!-- Left Column: Service Account Info & Manual Trigger -->
                <div class="lg:col-span-1 flex flex-col gap-6">

                    <!-- Status Panel & Manual Trigger -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden flex flex-col flex-1">
                        <div class="flex items-center gap-3 px-6 pt-5 pb-3 border-b border-gray-100">
                            <div class="bg-emerald-100 p-2 rounded-lg">
                                <i class="fas fa-play-circle text-emerald-600 text-lg"></i>
                            </div>
                            <h2 class="text-lg font-bold text-gray-800">Sinkronisasi Manual</h2>
                        </div>
                        
                        <div class="p-6 flex flex-col justify-between flex-1">
                            <div>
                                <p class="text-sm text-gray-600 mb-4">
                                    Status Sinkronisasi Otomatis Terakhir: <br>
                                    <span class="font-mono text-xs p-1 bg-gray-100 rounded text-gray-800 mt-1 block">
                                        <i class="fas fa-clock mr-1 text-gray-400"></i> {{ $settings['status_message'] ?? 'Belum ada data' }}
                                    </span>
                                </p>
                            </div>
                            
                            <div class="mt-auto">
                                <form id="syncForm" action="{{ route('admin.sync-sheets.sync') }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="w-full py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2 transform active:scale-95"
                                        {{ !$serviceAccountEmail ? 'disabled' : '' }}>
                                        <i class="fas fa-sync {{ !$serviceAccountEmail ? '' : 'fa-spin-hover' }}"></i>
                                        <span>Jalankan Manual Sekarang</span>
                                    </button>
                                </form>
                                @if(!$settings['spreadsheet_id'])
                                    <p class="text-xs text-red-500 mt-3 text-center"><i class="fas fa-exclamation-circle"></i> Simpan pengaturan terlebih dahulu untuk mengaktifkan manual sync.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Service Account Details -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden flex flex-col flex-1">
                        <div class="flex items-center gap-3 px-6 pt-5 pb-3 border-b border-gray-100">
                            <div class="bg-gray-100 p-2 rounded-lg">
                                <i class="fas fa-robot text-gray-600 text-lg"></i>
                            </div>
                            <h2 class="text-lg font-bold text-gray-800">Akses Service Account</h2>
                        </div>
                        
                        <div class="p-6">
                            <p class="text-sm text-gray-600 mb-3">Pastikan email berikut memiliki akses <strong>Editor</strong> pada Google Sheet target Anda:
                            </p>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-50 border border-gray-300 rounded px-2 py-2 font-mono text-[11px] text-gray-700 break-all select-all">
                                    {{ $serviceAccountEmail ?? 'Service Account Error' }}
                                </div>
                                <button onclick="navigator.clipboard.writeText('{{ $serviceAccountEmail }}')"
                                    class="px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded transition-colors"
                                    title="Copy">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Settings Form -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden flex flex-col h-full">
                    <div class="flex items-center gap-3 px-6 pt-5 pb-3 border-b border-gray-100">
                        <div class="bg-blue-100 p-2.5 rounded-lg">
                            <i class="fas fa-cog text-blue-600 text-lg"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Konfigurasi Target Data</h2>
                    </div>

                    <div class="p-6 flex flex-col flex-1">
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                                <div class="text-sm text-blue-800">
                                    Sistem hanya mengekstrak pembersihan nilai cell <em>(Clear Values)</em> tanpa menghapus format tabel (warna/border). Anda bebas berinovasi merancang baris pertama sebagai Header Custom Anda.
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('admin.sync-sheets.settings.update') }}" method="POST" class="flex flex-col flex-1">
                            @csrf
                            
                            <!-- Google Sheet Target Settings -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <!-- Spreadsheet ID Input -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fab fa-google-drive text-green-600 mr-1"></i> Google Spreadsheet ID
                                    </label>
                                    <div class="relative">
                                        <input type="text" name="spreadsheet_id" value="{{ $settings['spreadsheet_id'] ?? '' }}" required
                                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm"
                                            placeholder="Contoh: 1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74O...">
                                        <i class="fas fa-key absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Kumpulan teks acak di dalam URL target Anda.</p>
                                </div>

                                <!-- Sheet Name Input -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-table text-green-600 mr-1"></i> Nama Sheet (Tab Name)
                                    </label>
                                    <div class="relative">
                                        <input type="text" name="sheet_name" value="{{ $settings['sheet_name'] ?? 'Recap AERGAS' }}" required
                                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm"
                                            placeholder="Contoh: Recap AERGAS">
                                        <i class="fas fa-file-alt absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Case Sensitive (huruf besar kecil wajib akurat).</p>
                                </div>
                            </div>

                            <!-- Start Row Grid Setting -->
                            <div class="grid grid-cols-1 mb-8">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-level-down-alt text-amber-500 mr-1"></i> Mulai Cetak Data Dari Baris Ke (Start Row)
                                    </label>
                                    <div class="relative w-full md:w-1/2">
                                        <input type="number" name="start_row" value="{{ $settings['start_row'] ?? 5 }}" required min="2"
                                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all text-sm"
                                            placeholder="5">
                                        <i class="fas fa-list-ol absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Sistem akan secara otomatis me-resize Google Sheets dari baris awal ini ke bawah.</p>
                                </div>
                            </div>

                            <hr class="mb-6 border-gray-200">

                            <!-- Automation Settings -->
                            <h3 class="text-sm font-bold text-gray-700 mb-4"><i class="fas fa-robot mr-2"></i> Pengaturan Otomatisasi (Cron Job)</h3>
                            
                            <!-- Toggle On/Off -->
                            <div class="mb-5">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Status Auto-Sync Background
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="auto_sync_enabled" class="sr-only peer" {{ ($settings['auto_sync_enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    <span class="ms-3 text-sm font-semibold text-gray-700 peer-checked:text-blue-600">Scheduler Sistem Aktif</span>
                                </label>
                            </div>

                            <!-- Advanced Modes -->
                            <div class="bg-gray-50 p-5 rounded-lg border border-gray-200 grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                
                                <!-- Mode Selector -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Mode Target Dieksekusi
                                    </label>
                                    <div class="relative">
                                        <select name="sync_mode" id="sync_mode" class="w-full pl-4 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition-all text-sm font-semibold text-gray-700">
                                            <option value="interval" {{ ($settings['sync_mode'] ?? 'interval') === 'interval' ? 'selected' : '' }}>Secara Berulang (Interval)</option>
                                            <option value="daily" {{ ($settings['sync_mode'] ?? 'interval') === 'daily' ? 'selected' : '' }}>Satu Kali Sehari (Waktu Tetap)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Dynamic Input Field 1: Interval -->
                                <div id="interval_wrapper" class="{{ ($settings['sync_mode'] ?? 'interval') === 'interval' ? '' : 'hidden' }}">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Durasi Delay Per Eksekusi
                                    </label>
                                    <div class="relative">
                                        <input type="number" name="sync_interval_minutes" value="{{ $settings['sync_interval_minutes'] ?? 60 }}" required min="1"
                                            class="w-full pl-4 pr-16 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm text-right">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                            <span class="text-gray-500 text-sm font-medium">Menit</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dynamic Input Field 2: Daily Time -->
                                <div id="time_wrapper" class="{{ ($settings['sync_mode'] ?? 'interval') === 'daily' ? '' : 'hidden' }}">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Pada Jam Berapa Tepatnya?
                                    </label>
                                    <div class="relative">
                                        <input type="time" name="sync_time" value="{{ $settings['sync_time'] ?? '00:00' }}" required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm">
                                    </div>
                                    <p class="text-xs text-blue-500 mt-1"><i class="fas fa-info-circle"></i> Mengikuti Waktu Indonesia Barat (WIB)</p>
                                </div>

                            </div>

                            <!-- Action Button always at bottom -->
                            <div class="flex justify-end pt-4 mt-auto border-t border-gray-100">
                                <button type="submit"
                                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2 transform active:scale-95">
                                    <i class="fas fa-save"></i>
                                    <span>Simpan Pengaturan</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <style>
        .fa-spin-hover:hover { animation: fa-spin 2s infinite linear; }
        #loadingOverlay {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            z-index: 10000 !important;
        }
    </style>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 flex items-center justify-center hidden">
        <div class="text-center">
            <div class="relative mb-4">
                <i class="fas fa-circle-notch fa-spin text-blue-600 text-5xl"></i>
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                    <i class="fab fa-google-drive text-blue-600 text-sm"></i>
                </div>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-1">Sedang Sinkronisasi...</h3>
            <p class="text-gray-600">Mohon jangan tutup halaman ini.</p>
            <p class="text-sm text-blue-500 mt-2 animate-pulse"><i class="fas fa-info-circle mr-1"></i> Data besar mungkin butuh 1-2 menit</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Loading Overlay Trigger
            const form = document.getElementById('syncForm');
            const overlay = document.getElementById('loadingOverlay');

            if (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if (confirm('Jalankan proses Sinkronisasi Manual secara instan sekarang?')) {
                        overlay.classList.remove('hidden');
                        const btn = this.querySelector('button[type="submit"]');
                        if (btn) {
                            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Memproses...';
                            btn.classList.add('opacity-75', 'cursor-not-allowed');
                            btn.disabled = true;
                        }
                        setTimeout(() => this.submit(), 50);
                    }
                });
            }

            // Sync Mode Toggle Logic
            const syncModeSelect = document.getElementById('sync_mode');
            const intervalWrapper = document.getElementById('interval_wrapper');
            const timeWrapper = document.getElementById('time_wrapper');

            if (syncModeSelect) {
                syncModeSelect.addEventListener('change', function() {
                    if (this.value === 'daily') {
                        intervalWrapper.classList.add('hidden');
                        timeWrapper.classList.remove('hidden');
                    } else {
                        timeWrapper.classList.add('hidden');
                        intervalWrapper.classList.remove('hidden');
                    }
                });
            }
        });
    </script>
@endsection