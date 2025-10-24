@extends('layouts.app')

@section('title', 'Input Lowering Baru')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Input Lowering Baru</h1>
            <p class="text-gray-600">Input data lowering harian untuk jalur pipa</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('jalur.lowering.store') }}" id="loweringForm" enctype="multipart/form-data">
                @csrf

                <!-- Line Number Input -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Line Number <span class="text-red-500">*</span>
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                        <div>
                            <label for="diameter" class="block text-sm font-medium text-gray-700 mb-2">
                                Diameter Pipa <span class="text-red-500">*</span>
                            </label>
                            <select id="diameter" name="diameter"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 @error('diameter') border-red-500 @enderror"
                                    required
                                    onchange="updateLineNumberPreview()">
                                <option value="">Pilih Diameter</option>
                                <option value="63" {{ old('diameter') == '63' ? 'selected' : '' }}>63 mm</option>
                                <option value="90" {{ old('diameter') == '90' ? 'selected' : '' }}>90 mm</option>
                                <option value="180" {{ old('diameter') == '180' ? 'selected' : '' }}>180 mm</option>
                            </select>
                            @error('diameter')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="cluster_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Cluster <span class="text-red-500">*</span>
                            </label>
                            <select id="cluster_id" name="cluster_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 @error('cluster_id') border-red-500 @enderror"
                                    required
                                    onchange="updateLineNumberPreview()">
                                <option value="">Pilih Cluster</option>
                                @foreach($clusters as $cluster)
                                    <option value="{{ $cluster->id }}"
                                            data-code="{{ $cluster->code_cluster }}"
                                            {{ old('cluster_id', request('cluster_id')) == $cluster->id ? 'selected' : '' }}>
                                        {{ $cluster->nama_cluster }} ({{ $cluster->code_cluster }})
                                    </option>
                                @endforeach
                            </select>
                            @error('cluster_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="line_number_suffix" class="block text-sm font-medium text-gray-700 mb-2">
                                Nomor Suffix <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="line_number_suffix"
                                   name="line_number_suffix"
                                   value="{{ old('line_number_suffix') }}"
                                   placeholder="001"
                                   maxlength="10"
                                   required
                                   oninput="updateLineNumberPreview()"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 @error('line_number_suffix') border-red-500 @enderror">
                            @error('line_number_suffix')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">Contoh: 001, 002, dst</p>
                        </div>
                    </div>

                    <!-- Line Number Preview -->
                    <div class="bg-green-50 border border-green-200 rounded-md p-3">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <span class="text-sm font-medium text-gray-700">Preview Line Number:</span>
                                <span id="line_number_preview" class="ml-2 text-lg font-bold text-green-700">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Line Number Status Info -->
                    <div id="line-number-status-container" class="hidden mt-3">
                        <div id="line-number-status-message" class="p-3 rounded-md text-sm">
                            <!-- Status message will be populated by JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Basic Info -->
                <div class="mb-6">
                    <label for="tanggal_jalur" class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Pemasangan <span class="text-red-500">*</span>
                    </label>
                    <input type="date"
                           id="tanggal_jalur"
                           name="tanggal_jalur"
                           value="{{ old('tanggal_jalur', date('Y-m-d')) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 @error('tanggal_jalur') border-red-500 @enderror"
                           required>
                    @error('tanggal_jalur')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">Nama jalan akan diambil dari data Line Number</p>
                </div>

                <!-- Tipe Pekerjaan & Aksesoris -->
                <div class="mb-6">
                    <label for="tipe_bongkaran" class="block text-sm font-medium text-gray-700 mb-2">
                        Tipe Pekerjaan <span class="text-red-500">*</span>
                    </label>
                    <select id="tipe_bongkaran" name="tipe_bongkaran"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 @error('tipe_bongkaran') border-red-500 @enderror"
                            required onchange="updateAksesoris()">
                        <option value="">Pilih Tipe Bongkaran</option>
                        <option value="Manual Boring" {{ old('tipe_bongkaran') === 'Manual Boring' ? 'selected' : '' }}>Manual Boring</option>
                        <option value="Open Cut" {{ old('tipe_bongkaran') === 'Open Cut' ? 'selected' : '' }}>Open Cut</option>
                        <option value="Crossing" {{ old('tipe_bongkaran') === 'Crossing' ? 'selected' : '' }}>Crossing</option>
                        <option value="Zinker" {{ old('tipe_bongkaran') === 'Zinker' ? 'selected' : '' }}>Zinker</option>
                        <option value="HDD" {{ old('tipe_bongkaran') === 'HDD' ? 'selected' : '' }}>HDD</option>
                        <option value="Manual Boring - PK" {{ old('tipe_bongkaran') === 'Manual Boring - PK' ? 'selected' : '' }}>Manual Boring - PK</option>
                        <option value="Crossing - PK" {{ old('tipe_bongkaran') === 'Crossing - PK' ? 'selected' : '' }}>Crossing - PK</option>
                    </select>
                    @error('tipe_bongkaran')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jenis Perkerasan -->
                <div class="mb-6">
                    <label for="tipe_material" class="block text-sm font-medium text-gray-700 mb-2">
                        Jenis Perkerasan
                    </label>
                    <select id="tipe_material" name="tipe_material"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 @error('tipe_material') border-red-500 @enderror">
                        <option value="">Pilih Tipe Material</option>
                        <option value="Aspal" {{ old('tipe_material') === 'Aspal' ? 'selected' : '' }}>Aspal</option>
                        <option value="Tanah" {{ old('tipe_material') === 'Tanah' ? 'selected' : '' }}>Tanah</option>
                        <option value="Paving" {{ old('tipe_material') === 'Paving' ? 'selected' : '' }}>Paving</option>
                        <option value="Beton" {{ old('tipe_material') === 'Beton' ? 'selected' : '' }}>Beton</option>
                    </select>
                    @error('tipe_material')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">Pilih jenis perkerasan pada jalur lowering</p>
                </div>

                <!-- Aksesoris (conditional) -->
                <div id="aksesoris-section" class="mb-6 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Aksesoris</label>
                    <div class="space-y-2">
                        <div id="aksesoris-cassing" class="flex items-center hidden">
                            <input type="checkbox" 
                                   id="aksesoris_cassing" 
                                   name="aksesoris_cassing" 
                                   value="1"
                                   {{ old('aksesoris_cassing') ? 'checked' : '' }}
                                   class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500">
                            <label for="aksesoris_cassing" class="ml-2 text-sm text-gray-700">
                                Cassing (Crossing/Zinker)
                            </label>
                        </div>
                        <div id="aksesoris-marker-tape" class="space-y-2 hidden">
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       id="aksesoris_marker_tape" 
                                       name="aksesoris_marker_tape" 
                                       value="1"
                                       onchange="toggleQuantityField('marker_tape')"
                                       {{ old('aksesoris_marker_tape') ? 'checked' : '' }}
                                       class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500">
                                <label for="aksesoris_marker_tape" class="ml-2 text-sm text-gray-700">
                                    Marker Tape
                                </label>
                            </div>
                            <div id="marker_tape_quantity_field" class="ml-6 hidden">
                                <label for="marker_tape_quantity" class="block text-xs text-gray-600 mb-1">
                                    Jumlah (meter) - Auto-fill dari Panjang Lowering
                                </label>
                                <input type="number" 
                                       id="marker_tape_quantity" 
                                       name="marker_tape_quantity" 
                                       value="{{ old('marker_tape_quantity') }}"
                                       step="0.1"
                                       min="0.1"
                                       placeholder="0.0"
                                       class="w-32 px-2 py-1 text-sm border border-gray-300 bg-gray-100 rounded focus:outline-none focus:ring-1 focus:ring-green-500"
                                       readonly>
                            </div>
                        </div>
                        <div id="aksesoris-concrete-slab" class="space-y-2 hidden">
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       id="aksesoris_concrete_slab" 
                                       name="aksesoris_concrete_slab" 
                                       value="1"
                                       onchange="toggleQuantityField('concrete_slab')"
                                       {{ old('aksesoris_concrete_slab') ? 'checked' : '' }}
                                       class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500">
                                <label for="aksesoris_concrete_slab" class="ml-2 text-sm text-gray-700">
                                    Concrete Slab
                                </label>
                            </div>
                            <div id="concrete_slab_quantity_field" class="ml-6 hidden">
                                <label for="concrete_slab_quantity" class="block text-xs text-gray-600 mb-1">
                                    Jumlah (pcs) - Auto-calculate (Panjang Lowering x2)
                                </label>
                                <input type="number" 
                                       id="concrete_slab_quantity" 
                                       name="concrete_slab_quantity" 
                                       value="{{ old('concrete_slab_quantity') }}"
                                       min="1"
                                       placeholder="0"
                                       class="w-32 px-2 py-1 text-sm border border-gray-300 bg-gray-100 rounded focus:outline-none focus:ring-1 focus:ring-green-500"
                                       readonly>
                            </div>
                        </div>
                        
                        <!-- Cassing for Open Cut (New) -->
                        <div id="aksesoris-cassing-open-cut" class="space-y-3 hidden">
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       id="aksesoris_cassing_open_cut" 
                                       name="aksesoris_cassing" 
                                       value="1"
                                       onchange="toggleQuantityField('cassing')"
                                       {{ old('aksesoris_cassing') ? 'checked' : '' }}
                                       class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500">
                                <label for="aksesoris_cassing_open_cut" class="ml-2 text-sm text-gray-700">
                                    Cassing
                                </label>
                            </div>
                            <div id="cassing_quantity_field" class="ml-6 space-y-2 hidden">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="cassing_quantity" class="block text-xs text-gray-600 mb-1">
                                            Jumlah (meter)
                                        </label>
                                        <input type="number" 
                                               id="cassing_quantity" 
                                               name="cassing_quantity" 
                                               value="{{ old('cassing_quantity') }}"
                                               step="0.1"
                                               min="0.1"
                                               placeholder="0.0"
                                               class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500">
                                    </div>
                                    <div>
                                        <label for="cassing_type" class="block text-xs text-gray-600 mb-1">
                                            Diameter Cassing
                                        </label>
                                        <select id="cassing_type" 
                                                name="cassing_type" 
                                                class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500">
                                            <option value="">Pilih Diameter</option>
                                            <option value="4_inch" {{ old('cassing_type') === '4_inch' ? 'selected' : '' }}>4 inch</option>
                                            <option value="8_inch" {{ old('cassing_type') === '8_inch' ? 'selected' : '' }}>8 inch</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Data -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label for="penggelaran" class="block text-sm font-medium text-gray-700 mb-2">
                            Lowering (m) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               id="penggelaran" 
                               name="penggelaran" 
                               value="{{ old('penggelaran') }}"
                               step="0.1"
                               min="0.1"
                               placeholder="0.0"
                               onchange="updateBongkaran()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 @error('penggelaran') border-red-500 @enderror"
                               required>
                        @error('penggelaran')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="bongkaran" id="bongkaran-label" class="block text-sm font-medium text-gray-700 mb-2">
                            Bongkaran (m) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               id="bongkaran" 
                               name="bongkaran" 
                               value="{{ old('bongkaran') }}"
                               step="0.1"
                               min="0.1"
                               placeholder="0.0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500 @error('bongkaran') border-red-500 @enderror"
                               readonly>
                        @error('bongkaran')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p id="bongkaran-help" class="text-xs text-gray-500 mt-1">Nilai otomatis sama dengan panjang lowering</p>
                    </div>

                    <div>
                        <label for="kedalaman_lowering" class="block text-sm font-medium text-gray-700 mb-2">
                            Kedalaman (cm) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               id="kedalaman_lowering" 
                               name="kedalaman_lowering" 
                               value="{{ old('kedalaman_lowering') }}"
                               step="1"
                               min="1"
                               placeholder="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 @error('kedalaman_lowering') border-red-500 @enderror"
                               required>
                        @error('kedalaman_lowering')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Keterangan -->
                <div class="mb-6">
                    <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-2">
                        Keterangan
                    </label>
                    <textarea id="keterangan" 
                              name="keterangan" 
                              rows="3"
                              placeholder="Keterangan tambahan..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 @error('keterangan') border-red-500 @enderror">{{ old('keterangan') }}</textarea>
                    @error('keterangan')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Evidence Upload -->
                <div class="mb-6">
                    <div class="flex items-center gap-3 mb-3">
                        <i class="fas fa-camera text-purple-600"></i>
                        <h2 class="font-semibold text-gray-800">Upload Evidence Foto</h2>
                        <span class="text-red-500">*</span>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 p-3 rounded text-sm mb-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-600 mr-2 mt-0.5"></i>
                            <div>
                                <p class="font-medium text-blue-800 mb-1">Upload foto evidence sesuai dengan aksesoris yang dipilih:</p>
                                <ul class="text-blue-700 space-y-1">
                                    <li>• Setiap aksesoris memerlukan foto evidence terpisah</li>
                                    <li>• Foto akan disimpan dalam folder line number yang sama</li>
                                    <li>• Format: JPG, PNG, JPEG. Max 5MB per foto</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Main Progress Photo (Always Required) -->
                    <div class="border rounded-lg p-4 mb-4">
                        <!-- Upload Method Selection -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Metode Upload <span class="text-red-500">*</span></label>
                            <div class="flex space-x-4">
                                <div class="flex items-center">
                                    <input type="radio" 
                                           id="upload_method_file" 
                                           name="upload_method" 
                                           value="file" 
                                           checked
                                           onchange="toggleUploadMethod()">
                                    <label for="upload_method_file" class="ml-2 text-sm text-gray-700">Upload File</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" 
                                           id="upload_method_link" 
                                           name="upload_method" 
                                           value="link"
                                           onchange="toggleUploadMethod()">
                                    <label for="upload_method_link" class="ml-2 text-sm text-gray-700">Link Google Drive</label>
                                </div>
                            </div>
                        </div>
                        
                        <label for="foto_evidence_lowering" class="block text-sm font-medium text-gray-700 mb-2">
                            Foto Evidence Lowering Harian <span class="text-red-500">*</span>
                        </label>
                        
                        <!-- File Upload Section -->
                        <div id="file_upload_section">
                            <div id="photo-preview" class="hidden mb-3">
                                <img id="preview-image" src="" alt="Preview" class="h-32 w-full object-cover rounded">
                            </div>
                            
                            <div id="photo-placeholder" class="h-32 flex items-center justify-center bg-gray-50 rounded border-dashed border text-gray-400 mb-3">
                                Tidak ada file
                            </div>
                            
                            <input type="file" 
                                   id="foto_evidence_lowering" 
                                   name="foto_evidence_penggelaran_bongkaran" 
                                   accept="image/*"
                                   onchange="previewPhoto(this)"
                                   class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100 @error('foto_evidence_penggelaran_bongkaran') border-red-500 @enderror"
                                   required>
                            @error('foto_evidence_penggelaran_bongkaran')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">
                                Foto progress penggelaran dan bongkaran lowering harian.
                            </p>
                        </div>
                        
                        <!-- Google Drive Link Section -->
                        <div id="drive_link_section" class="hidden">
                            <label for="foto_evidence_lowering_link" class="block text-sm font-medium text-gray-700 mb-2">
                                Link Google Drive <span class="text-red-500">*</span>
                            </label>
                            <input type="url" 
                                   id="foto_evidence_lowering_link" 
                                   name="foto_evidence_penggelaran_bongkaran_link" 
                                   value="{{ old('foto_evidence_penggelaran_bongkaran_link') }}"
                                   placeholder="https://drive.google.com/file/d/..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 @error('foto_evidence_penggelaran_bongkaran_link') border-red-500 @enderror">
                            @error('foto_evidence_penggelaran_bongkaran_link')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">
                                Masukkan link Google Drive foto progress penggelaran dan bongkaran lowering harian.
                            </p>
                        </div>
                    </div>

                    <!-- Accessory Photos (Conditional) -->
                    <div id="accessory-photos-section" class="space-y-4 hidden">
                        <div class="text-sm font-medium text-gray-700 mb-3">Foto Evidence Aksesoris:</div>
                        
                        <!-- Marker Tape Photo -->
                        <div id="marker-tape-photo" class="border rounded-lg p-4 hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Foto Evidence Marker Tape <span class="text-red-500">*</span>
                            </label>
                            <input type="file" 
                                   id="foto_evidence_marker_tape" 
                                   name="foto_evidence_marker_tape" 
                                   accept="image/*"
                                   class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-yellow-50 file:text-yellow-700 hover:file:bg-yellow-100">
                            <p class="text-xs text-gray-500 mt-1">Foto pemasangan marker tape pada jalur lowering.</p>
                        </div>

                        <!-- Concrete Slab Photo -->
                        <div id="concrete-slab-photo" class="border rounded-lg p-4 hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Foto Evidence Concrete Slab <span class="text-red-500">*</span>
                            </label>
                            <input type="file" 
                                   id="foto_evidence_concrete_slab" 
                                   name="foto_evidence_concrete_slab" 
                                   accept="image/*"
                                   class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100">
                            <p class="text-xs text-gray-500 mt-1">Foto pemasangan concrete slab di jalur lowering.</p>
                        </div>

                        <!-- Cassing Photo -->
                        <div id="cassing-photo" class="border rounded-lg p-4 hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Foto Evidence Cassing <span class="text-red-500">*</span>
                            </label>
                            <input type="file" 
                                   id="foto_evidence_cassing" 
                                   name="foto_evidence_cassing" 
                                   accept="image/*"
                                   class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                            <p class="text-xs text-gray-500 mt-1">Foto pemasangan cassing pada jalur crossing/open cut.</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="{{ route('jalur.lowering.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        Simpan Lowering
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let availabilityCheckTimeout = null;

function updateLineNumberPreview() {
    const diameter = document.getElementById('diameter').value;
    const clusterSelect = document.getElementById('cluster_id');
    const selectedOption = clusterSelect.options[clusterSelect.selectedIndex];
    const clusterCode = selectedOption.dataset.code || '';
    const clusterId = clusterSelect.value;
    const suffix = document.getElementById('line_number_suffix').value;
    const previewElement = document.getElementById('line_number_preview');

    if (diameter && clusterCode && suffix) {
        const lineNumber = `${diameter}-${clusterCode}-LN${suffix}`;
        previewElement.textContent = lineNumber;
        previewElement.classList.remove('text-gray-400');
        previewElement.classList.add('text-green-700');

        // Check availability after a short delay (debounce)
        clearTimeout(availabilityCheckTimeout);
        availabilityCheckTimeout = setTimeout(() => {
            checkLineNumberAvailability(lineNumber, clusterId);
        }, 500);
    } else if (diameter && clusterCode) {
        const lineNumber = `${diameter}-${clusterCode}-LN___`;
        previewElement.textContent = lineNumber;
        previewElement.classList.remove('text-green-700');
        previewElement.classList.add('text-gray-400');
        hideLineNumberStatus();
    } else {
        previewElement.textContent = '-';
        previewElement.classList.remove('text-green-700');
        previewElement.classList.add('text-gray-400');
        hideLineNumberStatus();
    }
}

async function checkLineNumberAvailability(lineNumber, clusterId) {
    const submitButton = document.querySelector('button[type="submit"]');
    const statusContainer = document.getElementById('line-number-status-container');
    const statusMessage = document.getElementById('line-number-status-message');

    if (!statusContainer || !statusMessage) return;

    // Show container and loading state
    statusContainer.classList.remove('hidden');
    statusMessage.className = 'p-3 rounded-md text-sm bg-gray-50 border border-gray-200 text-gray-700';
    statusMessage.innerHTML = `
        <div class="flex items-center">
            <svg class="animate-spin h-4 w-4 mr-2 text-gray-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="font-medium">Memeriksa ketersediaan...</span>
        </div>
    `;

    try {
        const response = await fetch(`{{ route('jalur.lowering.api.check-line-availability') }}?line_number=${encodeURIComponent(lineNumber)}&cluster_id=${encodeURIComponent(clusterId)}`);

        if (!response.ok) {
            throw new Error('API request failed');
        }

        const data = await response.json();

        // Update status message with appropriate styling and icon
        if (data.status_class === 'success') {
            statusMessage.className = 'p-3 rounded-lg text-sm bg-green-50 border-2 border-green-500 text-green-900 shadow-sm';
            statusMessage.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="font-semibold">${data.status_message}</p>
                    </div>
                </div>
            `;
            // Enable submit
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        } else if (data.status_class === 'info') {
            statusMessage.className = 'p-3 rounded-lg text-sm bg-blue-50 border-2 border-blue-500 text-blue-900 shadow-sm';
            statusMessage.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="font-semibold">${data.status_message}</p>
                    </div>
                </div>
            `;
            // Enable submit (existing line in same cluster is OK)
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        } else if (data.status_class === 'error') {
            statusMessage.className = 'p-3 rounded-lg text-sm bg-red-50 border-2 border-red-500 text-red-900 shadow-sm';
            let message = `
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="font-semibold">${data.status_message}</p>
            `;
            if (data.existing_line) {
                message += `
                        <p class="mt-1 text-xs text-red-700">
                            <span class="font-medium">Cluster:</span> ${data.existing_line.cluster_name} |
                            <span class="font-medium">Created:</span> ${new Date(data.existing_line.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}
                        </p>
                `;
            }
            message += `
                    </div>
                </div>
            `;
            statusMessage.innerHTML = message;

            // Disable submit for errors
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
        } else if (data.status_class === 'warning') {
            statusMessage.className = 'p-3 rounded-lg text-sm bg-yellow-50 border-2 border-yellow-500 text-yellow-900 shadow-sm';
            statusMessage.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="font-semibold">${data.status_message}</p>
                    </div>
                </div>
            `;
            // Disable submit for warnings
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }
    } catch (error) {
        console.error('Error checking line number availability:', error);
        statusMessage.className = 'p-3 rounded-lg text-sm bg-red-50 border-2 border-red-400 text-red-900 shadow-sm';
        statusMessage.innerHTML = `
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="font-semibold">Gagal memeriksa ketersediaan line number</p>
                    <p class="mt-1 text-xs text-red-700">Silakan coba lagi atau lanjutkan submit (validasi tetap akan dilakukan di server)</p>
                </div>
            </div>
        `;
        // Enable submit on error (let backend validate)
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
}

function hideLineNumberStatus() {
    const statusContainer = document.getElementById('line-number-status-container');
    const submitButton = document.querySelector('button[type="submit"]');

    if (statusContainer) {
        statusContainer.classList.add('hidden');
    }

    // Re-enable submit button when hiding status
    if (submitButton) {
        submitButton.disabled = false;
        submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}


function updateAksesoris() {
    const tipeBongkaran = document.getElementById('tipe_bongkaran').value;
    const aksesorisSection = document.getElementById('aksesoris-section');
    const cassing = document.getElementById('aksesoris-cassing');
    const markerTape = document.getElementById('aksesoris-marker-tape');
    const concreteSlab = document.getElementById('aksesoris-concrete-slab');
    const cassingOpenCut = document.getElementById('aksesoris-cassing-open-cut');
    
    // Update label field Bongkaran berdasarkan tipe bongkaran
    updateBongkaranLabel(tipeBongkaran);
    
    // Hide all first
    cassing.classList.add('hidden');
    markerTape.classList.add('hidden');
    concreteSlab.classList.add('hidden');
    cassingOpenCut.classList.add('hidden');
    aksesorisSection.classList.add('hidden');
    
    // Hide all photo sections first
    updateAccessoryPhotoSections('hide');
    
    // Show relevant aksesoris based on tipe bongkaran
    if (tipeBongkaran === 'Open Cut') {
        markerTape.classList.remove('hidden');
        concreteSlab.classList.remove('hidden');
        cassingOpenCut.classList.remove('hidden');
        aksesorisSection.classList.remove('hidden');
        
        // Auto-check marker tape and concrete slab for Open Cut
        document.getElementById('aksesoris_marker_tape').checked = true;
        document.getElementById('aksesoris_concrete_slab').checked = true;
        toggleQuantityField('marker_tape');
        toggleQuantityField('concrete_slab');
        
        // Show accessory photo sections for Open Cut
        updateAccessoryPhotoSections('open_cut');
        
        // Update auto-calculations
        updateAccessoryCalculations();
        
    } else if (tipeBongkaran === 'Crossing' || tipeBongkaran === 'Zinker') {
        cassing.classList.remove('hidden');
        aksesorisSection.classList.remove('hidden');
        
        // Show cassing photo for Crossing/Zinker
        updateAccessoryPhotoSections('crossing_zinker');
    }
}

function toggleQuantityField(type) {
    let checkbox, quantityField;
    
    if (type === 'cassing') {
        // Handle both cassing types (open cut and crossing/zinker)
        checkbox = document.getElementById('aksesoris_cassing_open_cut') || document.getElementById('aksesoris_cassing');
        quantityField = document.getElementById('cassing_quantity_field');
    } else {
        checkbox = document.getElementById(`aksesoris_${type}`);
        quantityField = document.getElementById(`${type}_quantity_field`);
    }
    
    if (checkbox && checkbox.checked) {
        quantityField.classList.remove('hidden');
        // Make quantity field required when checkbox is checked
        if (type === 'cassing') {
            document.getElementById('cassing_quantity').setAttribute('required', 'required');
            document.getElementById('cassing_type').setAttribute('required', 'required');
            // Also make cassing photo required
            document.getElementById('foto_evidence_cassing').setAttribute('required', 'required');
        } else {
            document.getElementById(`${type}_quantity`).setAttribute('required', 'required');
        }
    } else {
        quantityField.classList.add('hidden');
        // Remove required when checkbox is unchecked
        if (type === 'cassing') {
            document.getElementById('cassing_quantity').removeAttribute('required');
            document.getElementById('cassing_type').removeAttribute('required');
            // Also remove cassing photo required
            document.getElementById('foto_evidence_cassing').removeAttribute('required');
        } else {
            document.getElementById(`${type}_quantity`).removeAttribute('required');
        }
    }
}

function updateBongkaran() {
    const penggelaran = document.getElementById('penggelaran').value;
    document.getElementById('bongkaran').value = penggelaran;
    
    // Update accessory calculations if Open Cut is selected
    const tipeBongkaran = document.getElementById('tipe_bongkaran').value;
    if (tipeBongkaran === 'Open Cut') {
        updateAccessoryCalculations();
    }
}

function updateAccessoryCalculations() {
    const penggelaran = parseFloat(document.getElementById('penggelaran').value) || 0;
    
    // Update Marker Tape (same as Panjang Lowering)
    const markerTapeField = document.getElementById('marker_tape_quantity');
    if (markerTapeField) {
        markerTapeField.value = penggelaran.toFixed(1);
    }
    
    // Update Concrete Slab (Panjang Lowering x 2)
    const concreteSlabField = document.getElementById('concrete_slab_quantity');
    if (concreteSlabField) {
        concreteSlabField.value = Math.round(penggelaran * 2);
    }
}

function updateBongkaranLabel(tipeBongkaran) {
    const bongkaranLabel = document.getElementById('bongkaran-label');
    const bongkaranHelp = document.getElementById('bongkaran-help');
    
    if (tipeBongkaran === 'Manual Boring' || tipeBongkaran === 'Manual Boring - PK') {
        bongkaranLabel.innerHTML = 'Pekerjaan Manual Boring (m) <span class="text-red-500">*</span>';
        bongkaranHelp.textContent = 'Nilai otomatis sama dengan panjang lowering';
    } else {
        bongkaranLabel.innerHTML = 'Bongkaran (m) <span class="text-red-500">*</span>';
        bongkaranHelp.textContent = 'Nilai otomatis sama dengan panjang lowering';
    }
}

function updateAccessoryPhotoSections(mode) {
    const accessoryPhotosSection = document.getElementById('accessory-photos-section');
    const markerTapePhoto = document.getElementById('marker-tape-photo');
    const concreteSlabPhoto = document.getElementById('concrete-slab-photo');
    const cassingPhoto = document.getElementById('cassing-photo');
    
    // Hide all first
    accessoryPhotosSection.classList.add('hidden');
    markerTapePhoto.classList.add('hidden');
    concreteSlabPhoto.classList.add('hidden');
    cassingPhoto.classList.add('hidden');
    
    // Remove required attributes
    document.getElementById('foto_evidence_marker_tape').removeAttribute('required');
    document.getElementById('foto_evidence_concrete_slab').removeAttribute('required');
    document.getElementById('foto_evidence_cassing').removeAttribute('required');
    
    if (mode === 'open_cut') {
        accessoryPhotosSection.classList.remove('hidden');
        markerTapePhoto.classList.remove('hidden');
        concreteSlabPhoto.classList.remove('hidden');
        cassingPhoto.classList.remove('hidden');
        
        // Make photos required for Open Cut
        document.getElementById('foto_evidence_marker_tape').setAttribute('required', 'required');
        document.getElementById('foto_evidence_concrete_slab').setAttribute('required', 'required');
        
    } else if (mode === 'crossing_zinker') {
        accessoryPhotosSection.classList.remove('hidden');
        cassingPhoto.classList.remove('hidden');
        
        // Cassing photo is only required when checkbox is checked
        // This will be handled by toggleQuantityField function
    }
}

function previewPhoto(input) {
    const file = input.files[0];
    const preview = document.getElementById('photo-preview');
    const previewImage = document.getElementById('preview-image');
    const placeholder = document.getElementById('photo-placeholder');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        }
        reader.readAsDataURL(file);
    } else {
        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
    }
}

// Form submit handler to ensure bongkaran is filled
document.getElementById('loweringForm').addEventListener('submit', function(e) {
    const penggelaran = document.getElementById('penggelaran').value;
    const bongkaran = document.getElementById('bongkaran').value;
    
    // Ensure bongkaran has value before submit
    if (penggelaran && (!bongkaran || bongkaran === '0')) {
        document.getElementById('bongkaran').value = penggelaran;
    }
});

// Toggle upload method between file and link
function toggleUploadMethod() {
    const fileSection = document.getElementById('file_upload_section');
    const linkSection = document.getElementById('drive_link_section');
    const fileInput = document.getElementById('foto_evidence_lowering');
    const linkInput = document.getElementById('foto_evidence_lowering_link');
    const uploadMethod = document.querySelector('input[name="upload_method"]:checked').value;
    
    if (uploadMethod === 'file') {
        fileSection.classList.remove('hidden');
        linkSection.classList.add('hidden');
        fileInput.required = true;
        linkInput.required = false;
        linkInput.value = '';
    } else {
        fileSection.classList.add('hidden');
        linkSection.classList.remove('hidden');
        fileInput.required = false;
        linkInput.required = true;
        fileInput.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize line number preview
    updateLineNumberPreview();

    if (document.getElementById('tipe_bongkaran').value) {
        updateAksesoris();
    }
    // Initialize upload method display
    toggleUploadMethod();
    // Initialize quantity fields based on old values
    if (document.getElementById('aksesoris_marker_tape').checked) {
        toggleQuantityField('marker_tape');
    }
    if (document.getElementById('aksesoris_concrete_slab').checked) {
        toggleQuantityField('concrete_slab');
    }
    if (document.getElementById('aksesoris_cassing').checked ||
        (document.getElementById('aksesoris_cassing_open_cut') && document.getElementById('aksesoris_cassing_open_cut').checked)) {
        toggleQuantityField('cassing');
    }
    // Initialize bongkaran label and value
    const tipeBongkaran = document.getElementById('tipe_bongkaran').value;
    if (tipeBongkaran) {
        updateBongkaranLabel(tipeBongkaran);
    }

    // Ensure bongkaran field has value on page load
    const penggelaran = document.getElementById('penggelaran').value;
    const bongkaran = document.getElementById('bongkaran').value;
    if (penggelaran && (!bongkaran || bongkaran === '0' || bongkaran === '')) {
        document.getElementById('bongkaran').value = penggelaran;
    }
});
</script>
@endsection