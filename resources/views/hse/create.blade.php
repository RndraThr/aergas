@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="hseCreate()">
    <!-- Header with Gradient -->
    <div class="mb-8 bg-gradient-to-r from-orange-500 to-red-500 rounded-2xl shadow-xl p-8 text-white">
        <div class="flex items-center space-x-4">
            <a href="{{ route('hse.daily-reports.index') }}"
               class="bg-white bg-opacity-20 hover:bg-opacity-30 p-3 rounded-xl transition-all backdrop-blur-sm">
                <i class="fas fa-arrow-left text-2xl"></i>
            </a>
            <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                    <div class="bg-white bg-opacity-20 p-3 rounded-xl backdrop-blur-sm">
                        <i class="fas fa-clipboard-check text-2xl"></i>
                    </div>
                    <h1 class="text-3xl font-bold">Buat Laporan Harian HSE</h1>
                </div>
                <p class="text-orange-100 ml-14">📋 Dokumentasikan aktivitas HSE harian dengan lengkap dan akurat</p>
            </div>
        </div>
    </div>

    <!-- Display All Validation Errors -->
    @if ($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-6 rounded-lg mb-6 shadow-lg">
        <div class="flex items-center mb-3">
            <i class="fas fa-exclamation-triangle text-2xl mr-3"></i>
            <h3 class="font-bold text-lg">Terjadi Kesalahan!</h3>
        </div>
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('hse.daily-reports.store') }}" method="POST" class="space-y-6" onsubmit="console.log('Form submitting...'); return true;">
        @csrf

        <!-- Informasi Proyek -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden transform transition-all hover:shadow-xl">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                <h2 class="text-xl font-bold text-white flex items-center">
                    <div class="bg-white bg-opacity-20 p-2 rounded-lg mr-3">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    📊 Informasi Proyek
                </h2>
                <p class="text-blue-100 text-sm mt-1 ml-11">Detail informasi proyek dan kondisi lapangan</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-calendar-day text-orange-500 mr-2"></i>
                        Tanggal Laporan <span class="text-red-500 ml-1">*</span>
                    </label>
                    <div class="relative">
                        <input type="date" name="tanggal_laporan" value="{{ old('tanggal_laporan', date('Y-m-d')) }}" required
                               class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 focus:border-orange-500 focus:ring-4 focus:ring-orange-200 transition-all text-gray-900 font-medium">
                    </div>
                    @error('tanggal_laporan')
                        <p class="mt-2 text-sm text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-cloud-sun text-blue-500 mr-2"></i>
                        Cuaca <span class="text-red-500 ml-1">*</span>
                    </label>
                    <div class="relative">
                        <select name="cuaca" required
                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 focus:border-orange-500 focus:ring-4 focus:ring-orange-200 transition-all text-gray-900 font-medium appearance-none bg-white">
                            <option value="cerah" {{ old('cuaca', 'cerah') == 'cerah' ? 'selected' : '' }}>☀️ Cerah</option>
                            <option value="berawan" {{ old('cuaca') == 'berawan' ? 'selected' : '' }}>⛅ Berawan</option>
                            <option value="mendung" {{ old('cuaca') == 'mendung' ? 'selected' : '' }}>☁️ Mendung</option>
                            <option value="hujan" {{ old('cuaca') == 'hujan' ? 'selected' : '' }}>🌧️ Hujan</option>
                            <option value="hujan_lebat" {{ old('cuaca') == 'hujan_lebat' ? 'selected' : '' }}>⛈️ Hujan Lebat</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                    </div>
                    @error('cuaca')
                        <p class="mt-2 text-sm text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-project-diagram text-purple-500 mr-2"></i>
                        Nama Proyek <span class="text-red-500 ml-1">*</span>
                    </label>
                    <input type="text" name="nama_proyek" value="{{ old('nama_proyek', $defaults['nama_proyek']) }}" required
                           placeholder="Contoh: Pembangunan Jargas Gaskita Di Kabupaten Sleman"
                           class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 focus:border-orange-500 focus:ring-4 focus:ring-orange-200 transition-all text-gray-900 font-medium">
                    @error('nama_proyek')
                        <p class="mt-2 text-sm text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-handshake text-green-500 mr-2"></i>
                        Pemberi Pekerjaan <span class="text-red-500 ml-1">*</span>
                    </label>
                    <input type="text" name="pemberi_pekerjaan" value="{{ old('pemberi_pekerjaan', $defaults['pemberi_pekerjaan']) }}" required
                           placeholder="Contoh: PGN - CGP"
                           class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 focus:border-orange-500 focus:ring-4 focus:ring-orange-200 transition-all text-gray-900 font-medium">
                    @error('pemberi_pekerjaan')
                        <p class="mt-2 text-sm text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-building text-blue-500 mr-2"></i>
                        Kontraktor <span class="text-red-500 ml-1">*</span>
                    </label>
                    <input type="text" name="kontraktor" value="{{ old('kontraktor', $defaults['kontraktor']) }}" required
                           placeholder="Contoh: PT. KIAN SANTANG MULIATAMA TBK"
                           class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 focus:border-orange-500 focus:ring-4 focus:ring-orange-200 transition-all text-gray-900 font-medium">
                    @error('kontraktor')
                        <p class="mt-2 text-sm text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-building text-gray-500 mr-2"></i>
                        Sub Kontraktor <span class="text-gray-400 text-xs">(Opsional)</span>
                    </label>
                    <input type="text" name="sub_kontraktor" value="{{ old('sub_kontraktor', $defaults['sub_kontraktor']) }}"
                           placeholder="Masukkan nama sub kontraktor (jika ada)"
                           class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 focus:border-orange-500 focus:ring-4 focus:ring-orange-200 transition-all text-gray-900 font-medium">
                    @error('sub_kontraktor')
                        <p class="mt-2 text-sm text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-sticky-note text-yellow-500 mr-2"></i>
                        Catatan <span class="text-gray-400 text-xs">(Opsional)</span>
                    </label>
                    <textarea name="catatan" rows="3"
                              placeholder="Tambahkan catatan penting untuk laporan ini..."
                              class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 focus:border-orange-500 focus:ring-4 focus:ring-orange-200 transition-all text-gray-900 font-medium resize-none">{{ old('catatan') }}</textarea>
                    @error('catatan')
                        <p class="mt-2 text-sm text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Pekerjaan Harian -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden transform transition-all hover:shadow-xl">
            <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <div class="bg-white bg-opacity-20 p-2 rounded-lg mr-3">
                            <i class="fas fa-tasks"></i>
                        </div>
                        🏗️ Pekerjaan Harian
                    </h2>
                    <p class="text-green-100 text-sm mt-1 ml-11">Daftar aktivitas pekerjaan yang dilakukan hari ini</p>
                </div>
                <button type="button" @click="addPekerjaan"
                        class="px-4 py-2 bg-white text-green-600 rounded-xl hover:bg-green-50 transition-all transform hover:scale-105 shadow-lg font-semibold">
                    <i class="fas fa-plus-circle mr-2"></i> Tambah
                </button>
            </div>
            <div class="p-6 space-y-4">
                <template x-for="(pekerjaan, index) in pekerjaanList" :key="index">
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl p-6 hover:shadow-lg transition-all">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="bg-green-500 text-white w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg shadow-md">
                                    <span x-text="index + 1"></span>
                                </div>
                                <h3 class="font-bold text-gray-800 text-lg">Pekerjaan <span x-text="index + 1"></span></h3>
                            </div>
                            <button type="button" @click="removePekerjaan(index)"
                                    class="px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all transform hover:scale-105 font-semibold">
                                <i class="fas fa-trash mr-1"></i> Hapus
                            </button>
                        </div>
                        <div class="grid grid-cols-1 gap-4">
                            <div class="space-y-2">
                                <label class="block text-sm font-bold text-gray-700 flex items-center">
                                    <i class="fas fa-hammer text-green-600 mr-2"></i>
                                    Jenis Pekerjaan <span class="text-red-500 ml-1">*</span>
                                </label>
                                <input type="text" :name="'pekerjaan[' + index + '][jenis_pekerjaan]'" x-model="pekerjaan.jenis_pekerjaan" required
                                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 focus:border-green-500 focus:ring-4 focus:ring-green-200 transition-all text-gray-900 font-medium bg-white"
                                       placeholder="Contoh: Penggalian tanah, Pemasangan pipa, dll">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-bold text-gray-700 flex items-center">
                                    <i class="fas fa-comment-alt text-purple-600 mr-2"></i>
                                    Deskripsi Pekerjaan <span class="text-red-500 ml-1">*</span>
                                </label>
                                <textarea :name="'pekerjaan[' + index + '][deskripsi_pekerjaan]'" x-model="pekerjaan.deskripsi_pekerjaan" rows="2" required
                                          placeholder="Jelaskan detail pekerjaan yang dilakukan..."
                                          class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 focus:border-green-500 focus:ring-4 focus:ring-green-200 transition-all text-gray-900 font-medium bg-white resize-none"></textarea>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-bold text-gray-700 flex items-center">
                                    <i class="fas fa-map-marker-alt text-red-600 mr-2"></i>
                                    Lokasi Detail <span class="text-red-500 ml-1">*</span>
                                </label>
                                <input type="text" :name="'pekerjaan[' + index + '][lokasi_detail]'" x-model="pekerjaan.lokasi_detail" required
                                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 focus:border-green-500 focus:ring-4 focus:ring-green-200 transition-all text-gray-900 font-medium bg-white"
                                       placeholder="Contoh: Jl. Magelang KM 5, Desa Ngaglik">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-bold text-gray-700 flex items-center">
                                    <i class="fas fa-link text-blue-600 mr-2"></i>
                                    Google Maps Link <span class="text-gray-400 text-xs">(Opsional)</span>
                                </label>
                                <input type="url" :name="'pekerjaan[' + index + '][google_maps_link]'" x-model="pekerjaan.google_maps_link"
                                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 focus:border-green-500 focus:ring-4 focus:ring-green-200 transition-all text-gray-900 font-medium bg-white"
                                       placeholder="https://maps.google.com/...">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Tenaga Kerja -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-users text-aergas-orange mr-2"></i>
                    Tenaga Kerja
                </h2>
                <button type="button" @click="addTenagaKerja"
                        class="px-3 py-1 bg-aergas-orange text-white text-sm rounded-lg hover:bg-aergas-orange-dark transition-colors">
                    <i class="fas fa-plus mr-1"></i> Tambah Tenaga Kerja
                </button>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jabatan/Role</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(tk, index) in tenagaKerjaList" :key="index">
                                <tr>
                                    <td class="px-4 py-3">
                                        <select :name="'tenaga_kerja[' + index + '][kategori_team]'" x-model="tk.kategori_team" required
                                                class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50">
                                            <option value="KSM">KSM</option>
                                            <option value="PGN-CGP">PGN-CGP</option>
                                            <option value="OMM">OMM</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" :name="'tenaga_kerja[' + index + '][role_name]'" x-model="tk.role_name" required
                                               class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50"
                                               placeholder="Contoh: PM, SPV, Teknisi">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" :name="'tenaga_kerja[' + index + '][jumlah_orang]'" x-model="tk.jumlah_orang" required min="1"
                                               class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50">
                                    </td>
                                    <td class="px-4 py-3">
                                        <button type="button" @click="removeTenagaKerja(index)"
                                                class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Toolbox Meeting (TBM) -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-chalkboard-teacher text-aergas-orange mr-2"></i>
                    Toolbox Meeting (TBM)
                </h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Pelaksanaan <span class="text-red-500">*</span></label>
                    <input type="time" name="tbm_waktu" value="{{ old('tbm_waktu', '07:00') }}" required
                           class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Peserta <span class="text-red-500">*</span></label>
                    <input type="number" name="tbm_jumlah_peserta" value="{{ old('tbm_jumlah_peserta', 0) }}" required min="0"
                           class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Materi TBM <span class="text-red-500">*</span></label>
                    <div class="space-y-3">
                        @foreach($defaults['tbm_materi'] as $index => $materi)
                        <div class="flex items-start space-x-3 bg-gray-50 p-3 rounded-lg">
                            <div class="flex-shrink-0 w-8 h-8 bg-aergas-orange text-white rounded-full flex items-center justify-center font-semibold">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-1">
                                <textarea name="tbm_materi[{{ $index }}][materi_pembahasan]" rows="2" required
                                          class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50"
                                          placeholder="Masukkan materi TBM #{{ $index + 1 }}">{{ old('tbm_materi.'.$index.'.materi_pembahasan', $materi['materi_pembahasan']) }}</textarea>
                                <input type="hidden" name="tbm_materi[{{ $index }}][urutan]" value="{{ $index + 1 }}">
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Program HSE -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-clipboard-list text-aergas-orange mr-2"></i>
                    Program HSE Harian
                </h2>
                <button type="button" @click="addProgramHse"
                        class="px-3 py-1 bg-aergas-orange text-white text-sm rounded-lg hover:bg-aergas-orange-dark transition-colors">
                    <i class="fas fa-plus mr-1"></i> Tambah Program
                </button>
            </div>
            <div class="p-6 space-y-3">
                <template x-for="(program, index) in programHseList" :key="index">
                    <div class="flex items-start space-x-3 bg-gray-50 p-3 rounded-lg">
                        <div class="flex-1">
                            <input type="text" :name="'program_hse[' + index + '][nama_program]'" x-model="program.nama_program" required
                                   class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50"
                                   placeholder="Nama program HSE">
                        </div>
                        <button type="button" @click="removeProgramHse(index)"
                                class="text-red-600 hover:text-red-900">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Upload Foto Dokumentasi -->
        <div class="bg-white rounded-lg shadow" x-data="photoUploader()">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-camera text-aergas-orange mr-2"></i>
                    Foto Dokumentasi (Opsional)
                </h2>
                <p class="text-sm text-gray-500 mt-1">Upload foto untuk dokumentasi laporan HSE</p>
            </div>
            <div class="p-6">
                <!-- Upload Form -->
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Foto</label>
                                <select x-model="currentCategory" class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50">
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="pekerjaan">📋 Pekerjaan</option>
                                    <option value="tbm">👷 Toolbox Meeting</option>
                                    <option value="kondisi_site">🏗️ Kondisi Site</option>
                                    <option value="apd">🦺 APD</option>
                                    <option value="housekeeping">🧹 Housekeeping</option>
                                    <option value="incident">⚠️ Incident</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Foto</label>
                                <input type="file"
                                       @change="addPhoto($event)"
                                       accept="image/*"
                                       multiple
                                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-aergas-orange file:text-white hover:file:bg-orange-600">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan (Opsional)</label>
                            <input type="text"
                                   x-model="currentKeterangan"
                                   placeholder="Tambahkan keterangan untuk foto..."
                                   class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50">
                        </div>
                        <p class="text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Format: JPG, PNG, GIF. Max 10MB per foto. Anda bisa upload multiple foto sekaligus.
                        </p>
                    </div>
                </div>

                <!-- Photo Preview -->
                <div class="mt-6" x-show="photos.length > 0">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-images mr-1"></i>
                        Foto yang akan diupload (<span x-text="photos.length"></span>)
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <template x-for="(photo, index) in photos" :key="index">
                            <div class="relative group border-2 border-gray-200 rounded-lg overflow-hidden">
                                <img :src="photo.preview" :alt="photo.category" class="w-full h-32 object-cover">
                                <div class="absolute top-2 right-2">
                                    <button type="button"
                                            @click="removePhoto(index)"
                                            class="bg-red-500 text-white p-2 rounded-full hover:bg-red-600 shadow-lg">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </div>
                                <div class="p-2 bg-white">
                                    <p class="text-xs font-semibold text-gray-900" x-text="getCategoryLabel(photo.category)"></p>
                                    <p class="text-xs text-gray-500 truncate" x-text="photo.keterangan || 'Tanpa keterangan'"></p>
                                    <p class="text-xs text-gray-400" x-text="formatFileSize(photo.size)"></p>
                                </div>
                                <!-- Hidden inputs -->
                                <input type="hidden" :name="'photos[' + index + '][file]'" x-model="photo.base64">
                                <input type="hidden" :name="'photos[' + index + '][category]'" x-model="photo.category">
                                <input type="hidden" :name="'photos[' + index + '][keterangan]'" x-model="photo.keterangan">
                                <input type="hidden" :name="'photos[' + index + '][filename]'" x-model="photo.name">
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl shadow-lg p-8">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    <i class="fas fa-info-circle mr-2"></i>
                    Pastikan semua data sudah terisi dengan benar
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('hse.daily-reports.index') }}"
                       class="px-6 py-3 border-2 border-gray-300 rounded-xl text-gray-700 hover:bg-gray-200 transition-all font-semibold">
                        <i class="fas fa-times-circle mr-2"></i>
                        Batal
                    </a>
                    <button type="submit"
                            class="px-8 py-3 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-xl hover:from-orange-600 hover:to-red-600 transition-all transform hover:scale-105 shadow-lg font-bold">
                        <i class="fas fa-save mr-2"></i>
                        Simpan Laporan HSE
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function hseCreate() {
    return {
        pekerjaanList: [
            { jenis_pekerjaan: '', deskripsi_pekerjaan: '', lokasi_detail: '', google_maps_link: '' }
        ],
        tenagaKerjaList: [
            { kategori_team: 'KSM', role_name: '', jumlah_orang: 1 }
        ],
        programHseList: @json(array_map(fn($p) => ['nama_program' => $p['nama_program']], $defaults['program_hse'])),

        addPekerjaan() {
            this.pekerjaanList.push({ jenis_pekerjaan: '', deskripsi_pekerjaan: '', lokasi_detail: '', google_maps_link: '' });
        },
        removePekerjaan(index) {
            if (this.pekerjaanList.length > 1) {
                this.pekerjaanList.splice(index, 1);
            } else {
                alert('Minimal 1 pekerjaan harus ada');
            }
        },

        addTenagaKerja() {
            this.tenagaKerjaList.push({ kategori_team: 'KSM', role_name: '', jumlah_orang: 1 });
        },
        removeTenagaKerja(index) {
            if (this.tenagaKerjaList.length > 1) {
                this.tenagaKerjaList.splice(index, 1);
            } else {
                alert('Minimal 1 tenaga kerja harus ada');
            }
        },

        addProgramHse() {
            this.programHseList.push({ nama_program: '' });
        },
        removeProgramHse(index) {
            if (this.programHseList.length > 1) {
                this.programHseList.splice(index, 1);
            } else {
                alert('Minimal 1 program HSE harus ada');
            }
        }
    }
}

function photoUploader() {
    return {
        photos: [],
        currentCategory: '',
        currentKeterangan: '',

        addPhoto(event) {
            const files = Array.from(event.target.files);

            if (!this.currentCategory) {
                alert('Pilih kategori foto terlebih dahulu!');
                event.target.value = '';
                return;
            }

            files.forEach(file => {
                if (file.size > 10 * 1024 * 1024) {
                    alert(`File ${file.name} terlalu besar (max 10MB)`);
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    this.photos.push({
                        file: file,
                        name: file.name,
                        size: file.size,
                        category: this.currentCategory,
                        keterangan: this.currentKeterangan,
                        preview: e.target.result,
                        base64: e.target.result
                    });
                };
                reader.readAsDataURL(file);
            });

            // Reset input
            event.target.value = '';
        },

        removePhoto(index) {
            this.photos.splice(index, 1);
        },

        getCategoryLabel(category) {
            const labels = {
                'pekerjaan': '📋 Pekerjaan',
                'tbm': '👷 Toolbox Meeting',
                'kondisi_site': '🏗️ Kondisi Site',
                'apd': '🦺 APD',
                'housekeeping': '🧹 Housekeeping',
                'incident': '⚠️ Incident'
            };
            return labels[category] || category;
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
    }
}
</script>
@endpush
@endsection
