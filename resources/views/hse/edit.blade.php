@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="hseCreate()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center space-x-4 mb-4">
            <a href="{{ route('hse.daily-reports.index') }}"
               class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Laporan Harian HSE</h1>
                <p class="mt-1 text-sm text-gray-600">Perbarui data laporan harian HSE</p>
            </div>
        </div>
    </div>

    <form action="{{ route('hse.daily-reports.update', $report->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Informasi Proyek -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-info-circle text-aergas-orange mr-2"></i>
                    Informasi Proyek
                </h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Tanggal Laporan <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="tanggal_laporan" value="{{ old('tanggal_laporan', \Carbon\Carbon::parse($report->tanggal_laporan)->format('Y-m-d')) }}" required
                           class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50">
                    @error('tanggal_laporan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Cuaca <span class="text-red-500">*</span>
                    </label>
                    <select name="cuaca" required
                            class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50">
                        <option value="cerah" {{ old('cuaca', $report->cuaca) == 'cerah' ? 'selected' : '' }}>☀️ Cerah</option>
                        <option value="berawan" {{ old('cuaca', $report->cuaca) == 'berawan' ? 'selected' : '' }}>⛅ Berawan</option>
                        <option value="mendung" {{ old('cuaca', $report->cuaca) == 'mendung' ? 'selected' : '' }}>☁️ Mendung</option>
                        <option value="hujan" {{ old('cuaca', $report->cuaca) == 'hujan' ? 'selected' : '' }}>🌧️ Hujan</option>
                        <option value="hujan_lebat" {{ old('cuaca', $report->cuaca) == 'hujan_lebat' ? 'selected' : '' }}>⛈️ Hujan Lebat</option>
                    </select>
                    @error('cuaca')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nama Proyek <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nama_proyek" value="{{ old('nama_proyek', $report->nama_proyek) }}" required
                           class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50">
                    @error('nama_proyek')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Pemberi Pekerjaan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="pemberi_pekerjaan" value="{{ old('pemberi_pekerjaan', $report->pemberi_pekerjaan) }}" required
                           class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50">
                    @error('pemberi_pekerjaan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Kontraktor <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="kontraktor" value="{{ old('kontraktor', $report->kontraktor) }}" required
                           class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50">
                    @error('kontraktor')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Sub Kontraktor
                    </label>
                    <input type="text" name="sub_kontraktor" value="{{ old('sub_kontraktor', $report->sub_kontraktor) }}"
                           class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50">
                    @error('sub_kontraktor')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Catatan
                    </label>
                    <textarea name="catatan" rows="3"
                              class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50">{{ old('catatan', $report->catatan) }}</textarea>
                    @error('catatan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Pekerjaan Harian -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-tasks text-aergas-orange mr-2"></i>
                    Pekerjaan Harian
                </h2>
                <button type="button" @click="addPekerjaan"
                        class="px-3 py-1 bg-aergas-orange text-white text-sm rounded-lg hover:bg-aergas-orange-dark transition-colors">
                    <i class="fas fa-plus mr-1"></i> Tambah Pekerjaan
                </button>
            </div>
            <div class="p-6 space-y-4">
                <template x-for="(pekerjaan, index) in pekerjaanList" :key="index">
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="font-medium text-gray-900" x-text="'Pekerjaan ' + (index + 1)"></h3>
                            <button type="button" @click="removePekerjaan(index)"
                                    class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Pekerjaan <span class="text-red-500">*</span></label>
                                <input type="text" :name="'pekerjaan[' + index + '][jenis_pekerjaan]'" x-model="pekerjaan.jenis_pekerjaan" required
                                       class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50"
                                       placeholder="Contoh: Penggalian tanah, Pemasangan pipa, dll">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Pekerjaan <span class="text-red-500">*</span></label>
                                <textarea :name="'pekerjaan[' + index + '][deskripsi_pekerjaan]'" x-model="pekerjaan.deskripsi_pekerjaan" required rows="2"
                                          class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50"
                                          placeholder="Deskripsi detail pekerjaan yang dilakukan"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi Detail <span class="text-red-500">*</span></label>
                                <input type="text" :name="'pekerjaan[' + index + '][lokasi_detail]'" x-model="pekerjaan.lokasi_detail" required
                                       class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50"
                                       placeholder="Contoh: Jl. Magelang KM 5">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Google Maps Link</label>
                                <input type="url" :name="'pekerjaan[' + index + '][google_maps_link]'" x-model="pekerjaan.google_maps_link"
                                       class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50"
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
                    <input type="time" name="tbm_waktu" value="{{ old('tbm_waktu', $report->toolboxMeeting ? \Carbon\Carbon::parse($report->toolboxMeeting->waktu_mulai)->format('H:i') : '07:00') }}" required
                           class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Peserta <span class="text-red-500">*</span></label>
                    <input type="number" name="tbm_jumlah_peserta" value="{{ old('tbm_jumlah_peserta', $report->toolboxMeeting->jumlah_peserta ?? 0) }}" required min="0"
                           class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Materi TBM <span class="text-red-500">*</span></label>
                    <div class="space-y-3">
                        @php
                            $tbmMateri = $report->toolboxMeeting ? $report->toolboxMeeting->materiList->sortBy('urutan') : collect($defaults['tbm_materi']);
                        @endphp
                        @foreach($tbmMateri as $index => $materi)
                        <div class="flex items-start space-x-3 bg-gray-50 p-3 rounded-lg">
                            <div class="flex-shrink-0 w-8 h-8 bg-aergas-orange text-white rounded-full flex items-center justify-center font-semibold">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-1">
                                <textarea name="tbm_materi[{{ $index }}][materi_pembahasan]" rows="2" required
                                          class="w-full rounded-lg border-gray-300 focus:border-aergas-orange focus:ring focus:ring-aergas-orange focus:ring-opacity-50"
                                          placeholder="Masukkan materi TBM #{{ $index + 1 }}">{{ old('tbm_materi.'.$index.'.materi_pembahasan', is_array($materi) ? $materi['materi_pembahasan'] : $materi->materi_pembahasan) }}</textarea>
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
                    Foto Dokumentasi
                </h2>
                <p class="text-sm text-gray-500 mt-1">Kelola foto dokumentasi laporan HSE (tambah/replace/hapus)</p>
            </div>
            <div class="p-6">
                <!-- Existing Photos -->
                @if($report->photos->count() > 0)
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-images mr-1"></i>
                        Foto yang sudah ada ({{ $report->photos->count() }})
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($report->photos as $photo)
                        <div class="relative group border-2 border-gray-200 rounded-lg overflow-hidden">
                            <a href="{{ $photo->image_url }}" target="_blank" class="block">
                                <img src="{{ $photo->thumbnail_url }}" alt="{{ $photo->getCategoryLabel() }}" class="w-full h-32 object-cover">
                            </a>
                            <div class="absolute top-2 right-2">
                                <input type="checkbox"
                                       name="delete_photos[]"
                                       value="{{ $photo->id }}"
                                       class="w-5 h-5 text-red-500 rounded focus:ring-red-500"
                                       title="Centang untuk hapus">
                            </div>
                            <div class="p-2 bg-white">
                                <p class="text-xs font-semibold text-gray-900">{{ $photo->getCategoryLabel() }}</p>
                                @if($photo->keterangan)
                                <p class="text-xs text-gray-500 truncate">{{ $photo->keterangan }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Centang foto yang ingin dihapus, kemudian klik "Simpan Laporan"
                    </p>
                </div>
                @endif

                <!-- Upload New Photos -->
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-plus-circle mr-1"></i>
                        Tambah Foto Baru
                    </h3>
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

                <!-- New Photo Preview -->
                <div class="mt-6" x-show="photos.length > 0">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-images mr-1"></i>
                        Foto baru yang akan diupload (<span x-text="photos.length"></span>)
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <template x-for="(photo, index) in photos" :key="index">
                            <div class="relative group border-2 border-green-200 rounded-lg overflow-hidden">
                                <img :src="photo.preview" :alt="photo.category" class="w-full h-32 object-cover">
                                <div class="absolute top-2 right-2">
                                    <button type="button"
                                            @click="removePhoto(index)"
                                            class="bg-red-500 text-white p-2 rounded-full hover:bg-red-600 shadow-lg">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </div>
                                <div class="p-2 bg-white">
                                    <p class="text-xs font-semibold text-green-600" x-text="getCategoryLabel(photo.category)"></p>
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
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('hse.daily-reports.index') }}"
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Batal
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-aergas-orange text-white rounded-lg hover:bg-aergas-orange-dark transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    Simpan Laporan
                </button>
            </div>
        </div>
    </form>
</div>

@php
    $pekerjaanData = $report->pekerjaanHarian->count() > 0
        ? $report->pekerjaanHarian->map(function($p) {
            return [
                'jenis_pekerjaan' => $p->jenis_pekerjaan,
                'deskripsi_pekerjaan' => $p->deskripsi_pekerjaan,
                'lokasi_detail' => $p->lokasi_detail,
                'google_maps_link' => $p->google_maps_link
            ];
        })->toArray()
        : [['jenis_pekerjaan' => '', 'deskripsi_pekerjaan' => '', 'lokasi_detail' => '', 'google_maps_link' => '']];

    $tenagaKerjaData = $report->tenagaKerja->count() > 0
        ? $report->tenagaKerja->map(function($tk) {
            return [
                'kategori_team' => $tk->kategori_team,
                'role_name' => $tk->role_name,
                'jumlah_orang' => $tk->jumlah_orang
            ];
        })->toArray()
        : [['kategori_team' => 'KSM', 'role_name' => '', 'jumlah_orang' => 1]];

    $programHseData = $report->programHarian->count() > 0
        ? $report->programHarian->map(function($p) {
            return ['nama_program' => $p->nama_program];
        })->toArray()
        : array_map(function($p) { return ['nama_program' => $p['nama_program']]; }, $defaults['program_hse']);
@endphp

@push('scripts')
<script>
function hseCreate() {
    return {
        pekerjaanList: @json($pekerjaanData),
        tenagaKerjaList: @json($tenagaKerjaData),
        programHseList: @json($programHseData),

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
