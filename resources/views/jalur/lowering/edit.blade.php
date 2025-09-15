@extends('layouts.app')

@section('title', 'Edit Lowering - AERGAS')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Edit Lowering</h1>
                    <p class="text-gray-600">Line: {{ $lowering->lineNumber->line_number }} - {{ $lowering->nama_jalan }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('jalur.lowering.show', $lowering) }}" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">
                        <i class="fas fa-eye mr-1"></i> Lihat Detail
                    </a>
                    <a href="{{ route('jalur.lowering.index') }}" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <!-- Status Info -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Status Saat Ini</div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium
                        @if($lowering->status_laporan === 'draft') bg-gray-100 text-gray-800
                        @elseif($lowering->status_laporan === 'acc_tracer') bg-yellow-100 text-yellow-800  
                        @elseif($lowering->status_laporan === 'acc_cgp') bg-green-100 text-green-800
                        @elseif(in_array($lowering->status_laporan, ['revisi_tracer', 'revisi_cgp'])) bg-red-100 text-red-800
                        @endif">
                        {{ $lowering->status_label }}
                    </span>
                </div>
                @if(in_array($lowering->status_laporan, ['revisi_tracer', 'revisi_cgp']))
                    <div class="text-sm text-red-600">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Data perlu diperbaiki sesuai catatan reviewer
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('jalur.lowering.update', $lowering) }}" id="loweringForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Line Number Info (Read-only) -->
                <div class="bg-blue-50 rounded-lg p-4 mb-6">
                    <div class="flex items-center gap-3 mb-3">
                        <i class="fas fa-route text-blue-600"></i>
                        <h3 class="font-semibold text-gray-800">Informasi Jalur</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Cluster:</span>
                            <span class="font-medium ml-2">{{ $lowering->lineNumber->cluster->nama_cluster }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Line Number:</span>
                            <span class="font-medium ml-2">{{ $lowering->lineNumber->line_number }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Diameter:</span>
                            <span class="font-medium ml-2">{{ $lowering->lineNumber->diameter }}mm</span>
                        </div>
                    </div>
                </div>

                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="nama_jalan" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Jalan <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="nama_jalan" 
                               name="nama_jalan" 
                               value="{{ old('nama_jalan', $lowering->nama_jalan) }}"
                               placeholder="Contoh: Jl. Karanggayam Raya"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 @error('nama_jalan') border-red-500 @enderror"
                               required>
                        @error('nama_jalan')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="tanggal_jalur" class="block text-sm font-medium text-gray-700 mb-2">
                            Tanggal Jalur <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               id="tanggal_jalur" 
                               name="tanggal_jalur" 
                               value="{{ old('tanggal_jalur', $lowering->tanggal_jalur->format('Y-m-d')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 @error('tanggal_jalur') border-red-500 @enderror"
                               required>
                        @error('tanggal_jalur')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Tipe Bongkaran -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Tipe Bongkaran <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @php
                            $tipeBongkaran = ['Open Cut', 'Crossing', 'Zinker', 'Manual Boring', 'Manual Boring - PK'];
                        @endphp
                        @foreach($tipeBongkaran as $tipe)
                            <label class="flex items-center p-3 border-2 rounded-lg cursor-pointer transition-colors
                                {{ old('tipe_bongkaran', $lowering->tipe_bongkaran) === $tipe ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300' }}">
                                <input type="radio"
                                       name="tipe_bongkaran"
                                       value="{{ $tipe }}"
                                       {{ old('tipe_bongkaran', $lowering->tipe_bongkaran) === $tipe ? 'checked' : '' }}
                                       class="sr-only"
                                       onchange="updateTipeBongkaran()" required>
                                <div class="flex-1 text-center">
                                    <div class="text-sm font-medium">{{ $tipe }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('tipe_bongkaran')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tipe Material -->
                <div class="mb-6">
                    <label for="tipe_material" class="block text-sm font-medium text-gray-700 mb-2">
                        Tipe Material Bongkaran <span class="text-red-500">*</span>
                    </label>
                    <select id="tipe_material"
                            name="tipe_material"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 @error('tipe_material') border-red-500 @enderror"
                            required>
                        <option value="">Pilih Tipe Material</option>
                        <option value="Aspal" {{ old('tipe_material', $lowering->tipe_material) === 'Aspal' ? 'selected' : '' }}>Aspal</option>
                        <option value="Tanah" {{ old('tipe_material', $lowering->tipe_material) === 'Tanah' ? 'selected' : '' }}>Tanah</option>
                        <option value="Paving" {{ old('tipe_material', $lowering->tipe_material) === 'Paving' ? 'selected' : '' }}>Paving</option>
                    </select>
                    @error('tipe_material')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Progress Data -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label for="penggelaran" class="block text-sm font-medium text-gray-700 mb-2">
                            Panjang Lowering (meter) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               id="penggelaran" 
                               name="penggelaran" 
                               value="{{ old('penggelaran', $lowering->penggelaran) }}"
                               step="0.1" 
                               min="0"
                               placeholder="0.0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 @error('penggelaran') border-red-500 @enderror"
                               required>
                        @error('penggelaran')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="bongkaran" class="block text-sm font-medium text-gray-700 mb-2">
                            <span id="bongkaran-label">
                                @if(in_array(old('tipe_bongkaran', $lowering->tipe_bongkaran), ['Manual Boring', 'Manual Boring - PK']))
                                    Pekerjaan Manual Boring (meter)
                                @else
                                    Bongkaran (meter)
                                @endif
                            </span>
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               id="bongkaran" 
                               name="bongkaran" 
                               value="{{ old('bongkaran', $lowering->bongkaran) }}"
                               step="0.1" 
                               min="0"
                               placeholder="0.0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 @error('bongkaran') border-red-500 @enderror"
                               required>
                        @error('bongkaran')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="kedalaman_lowering" class="block text-sm font-medium text-gray-700 mb-2">
                            Kedalaman Lowering (cm) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               id="kedalaman_lowering" 
                               name="kedalaman_lowering" 
                               value="{{ old('kedalaman_lowering', $lowering->kedalaman_lowering) }}"
                               min="0"
                               placeholder="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 @error('kedalaman_lowering') border-red-500 @enderror"
                               required>
                        @error('kedalaman_lowering')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Aksesoris Open Cut -->
                <div id="open-cut-aksesoris" class="{{ old('tipe_bongkaran', $lowering->tipe_bongkaran) === 'Open Cut' ? '' : 'hidden' }} mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Aksesoris Open Cut</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <div class="flex items-center mb-3">
                                <input type="checkbox" 
                                       id="aksesoris_marker_tape" 
                                       name="aksesoris_marker_tape" 
                                       {{ old('aksesoris_marker_tape', $lowering->aksesoris_marker_tape) ? 'checked' : '' }}
                                       class="h-4 w-4 text-green-600 rounded border-gray-300 focus:ring-green-500"
                                       onchange="toggleMarkerTapeQuantity()">
                                <label for="aksesoris_marker_tape" class="ml-2 text-sm font-medium text-gray-700">
                                    Marker Tape
                                </label>
                            </div>
                            <div id="marker-tape-quantity" class="{{ old('aksesoris_marker_tape', $lowering->aksesoris_marker_tape) ? '' : 'hidden' }}">
                                <input type="number" 
                                       name="marker_tape_quantity" 
                                       value="{{ old('marker_tape_quantity', $lowering->marker_tape_quantity) }}"
                                       step="0.1" 
                                       min="0"
                                       placeholder="Quantity (meter)"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center mb-3">
                                <input type="checkbox" 
                                       id="aksesoris_concrete_slab" 
                                       name="aksesoris_concrete_slab" 
                                       {{ old('aksesoris_concrete_slab', $lowering->aksesoris_concrete_slab) ? 'checked' : '' }}
                                       class="h-4 w-4 text-green-600 rounded border-gray-300 focus:ring-green-500"
                                       onchange="toggleConcreteSlabQuantity()">
                                <label for="aksesoris_concrete_slab" class="ml-2 text-sm font-medium text-gray-700">
                                    Concrete Slab
                                </label>
                            </div>
                            <div id="concrete-slab-quantity" class="{{ old('aksesoris_concrete_slab', $lowering->aksesoris_concrete_slab) ? '' : 'hidden' }}">
                                <input type="number" 
                                       name="concrete_slab_quantity" 
                                       value="{{ old('concrete_slab_quantity', $lowering->concrete_slab_quantity) }}"
                                       min="0"
                                       placeholder="Quantity (pcs)"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center mb-3">
                                <input type="checkbox" 
                                       id="aksesoris_cassing" 
                                       name="aksesoris_cassing" 
                                       {{ old('aksesoris_cassing', $lowering->aksesoris_cassing) ? 'checked' : '' }}
                                       class="h-4 w-4 text-green-600 rounded border-gray-300 focus:ring-green-500"
                                       onchange="toggleCassingQuantity()">
                                <label for="aksesoris_cassing" class="ml-2 text-sm font-medium text-gray-700">
                                    Cassing
                                </label>
                            </div>
                            <div id="cassing-fields" class="{{ old('aksesoris_cassing', $lowering->aksesoris_cassing) ? '' : 'hidden' }}">
                                <input type="number" 
                                       name="cassing_quantity" 
                                       value="{{ old('cassing_quantity', $lowering->cassing_quantity) }}"
                                       step="0.1" 
                                       min="0"
                                       placeholder="Quantity (meter)"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 mb-2">
                                <select name="cassing_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">Pilih Tipe Cassing</option>
                                    <option value="PVC" {{ old('cassing_type', $lowering->cassing_type) === 'PVC' ? 'selected' : '' }}>PVC</option>
                                    <option value="Steel" {{ old('cassing_type', $lowering->cassing_type) === 'Steel' ? 'selected' : '' }}>Steel</option>
                                    <option value="HDPE" {{ old('cassing_type', $lowering->cassing_type) === 'HDPE' ? 'selected' : '' }}>HDPE</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aksesoris Crossing/Zinker -->
                <div id="crossing-zinker-aksesoris" class="{{ in_array(old('tipe_bongkaran', $lowering->tipe_bongkaran), ['Crossing', 'Zinker']) ? '' : 'hidden' }} mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Aksesoris {{ old('tipe_bongkaran', $lowering->tipe_bongkaran) }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="flex items-center mb-3">
                                <input type="checkbox" 
                                       id="aksesoris_cassing_crossing" 
                                       name="aksesoris_cassing" 
                                       {{ old('aksesoris_cassing', $lowering->aksesoris_cassing) ? 'checked' : '' }}
                                       class="h-4 w-4 text-green-600 rounded border-gray-300 focus:ring-green-500"
                                       onchange="toggleCassingQuantityCrossing()">
                                <label for="aksesoris_cassing_crossing" class="ml-2 text-sm font-medium text-gray-700">
                                    Cassing
                                </label>
                            </div>
                            <div id="cassing-fields-crossing" class="{{ old('aksesoris_cassing', $lowering->aksesoris_cassing) ? '' : 'hidden' }}">
                                <input type="number" 
                                       name="cassing_quantity" 
                                       value="{{ old('cassing_quantity', $lowering->cassing_quantity) }}"
                                       step="0.1" 
                                       min="0"
                                       placeholder="Quantity (meter)"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 mb-2">
                                <select name="cassing_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">Pilih Tipe Cassing</option>
                                    <option value="PVC" {{ old('cassing_type', $lowering->cassing_type) === 'PVC' ? 'selected' : '' }}>PVC</option>
                                    <option value="Steel" {{ old('cassing_type', $lowering->cassing_type) === 'Steel' ? 'selected' : '' }}>Steel</option>
                                    <option value="HDPE" {{ old('cassing_type', $lowering->cassing_type) === 'HDPE' ? 'selected' : '' }}>HDPE</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Keterangan -->
                <div class="mb-6">
                    <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-2">
                        Keterangan Tambahan
                    </label>
                    <textarea id="keterangan" 
                              name="keterangan" 
                              rows="3"
                              placeholder="Catatan tambahan jika diperlukan..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 @error('keterangan') border-red-500 @enderror">{{ old('keterangan', $lowering->keterangan) }}</textarea>
                    @error('keterangan')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Dokumentasi Foto -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Dokumentasi Foto Evidence</h3>
                    
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

                    <!-- Current Photos Display -->
                    @if($lowering->photoApprovals && $lowering->photoApprovals->count() > 0)
                        <div class="mb-6">
                            <h4 class="text-md font-medium text-gray-700 mb-3">Foto Saat Ini:</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($lowering->photoApprovals as $photo)
                                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                                        <div class="aspect-w-4 aspect-h-3 bg-gray-100">
                                            @if($photo->photo_url && !empty(trim($photo->photo_url)))
                                                @php
                                                    $imageUrl = $photo->photo_url;
                                                    $isPdf = str_ends_with(Str::lower($imageUrl), '.pdf');
                                                    $fileId = null;
                                                    
                                                    if (str_contains($imageUrl, 'drive.google.com')) {
                                                        if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $imageUrl, $matches)) {
                                                            $fileId = $matches[1];
                                                            $imageUrl = "https://lh3.googleusercontent.com/d/{$fileId}=w800";
                                                        } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $imageUrl, $matches)) {
                                                            $fileId = $matches[1];
                                                            $imageUrl = "https://lh3.googleusercontent.com/d/{$fileId}=w800";
                                                        }
                                                    }
                                                    elseif (!str_contains($imageUrl, 'http') && !str_starts_with($imageUrl, 'JALUR_LOWERING/') && Storage::disk('public')->exists($imageUrl)) {
                                                        $imageUrl = asset('storage/' . $imageUrl);
                                                    }
                                                    elseif (str_starts_with($imageUrl, 'JALUR_LOWERING/') || str_starts_with($imageUrl, 'aergas/')) {
                                                        $imageUrl = null;
                                                    }
                                                @endphp

                                                @if($imageUrl && !$isPdf)
                                                    <img src="{{ $imageUrl }}"
                                                         class="w-full h-32 object-cover cursor-pointer hover:opacity-90 transition-opacity"
                                                         alt="Evidence foto"
                                                         loading="lazy"
                                                         data-file-id="{{ $fileId ?? '' }}"
                                                         data-original-url="{{ $photo->photo_url }}"
                                                         onerror="tryAlternativeUrls(this)"
                                                         onclick="openImageModal('{{ $imageUrl }}', '{{ str_replace('foto_evidence_', '', $photo->photo_field_name) }}')">
                                                @else
                                                    <div class="w-full h-32 bg-gradient-to-br from-blue-50 to-blue-100 flex flex-col items-center justify-center p-2">
                                                        <i class="fas fa-cloud text-blue-500 text-2xl mb-2"></i>
                                                        <span class="text-xs text-blue-800 text-center break-all">{{ basename($photo->photo_url) }}</span>
                                                    </div>
                                                @endif
                                            @else
                                                <div class="w-full h-32 flex items-center justify-center bg-gray-50">
                                                    <div class="text-center text-gray-400">
                                                        <i class="fas fa-camera text-2xl mb-1"></i>
                                                        <p class="text-xs">Belum ada foto</p>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="p-3">
                                            <h5 class="font-medium text-gray-900 text-sm">{{ str_replace('foto_evidence_', '', $photo->photo_field_name) }}</h5>
                                            <div class="flex items-center justify-between text-xs mt-2">
                                                <span class="px-2 py-1 rounded
                                                    @if($photo->photo_status === 'tracer_pending') bg-yellow-100 text-yellow-800
                                                    @elseif($photo->photo_status === 'tracer_approved') bg-green-100 text-green-800
                                                    @elseif($photo->photo_status === 'tracer_rejected') bg-red-100 text-red-800
                                                    @elseif($photo->photo_status === 'cgp_pending') bg-blue-100 text-blue-800
                                                    @elseif($photo->photo_status === 'cgp_approved') bg-green-100 text-green-800
                                                    @elseif($photo->photo_status === 'cgp_rejected') bg-red-100 text-red-800
                                                    @else bg-gray-100 text-gray-800
                                                    @endif">
                                                    {{ ucfirst(str_replace('_', ' ', $photo->photo_status)) }}
                                                </span>
                                                <span class="text-gray-500">
                                                    {{ $photo->created_at ? $photo->created_at->format('d/m H:i') : '-' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Upload New Photos -->
                    <div class="border-t pt-6">
                        <h4 class="text-md font-medium text-gray-700 mb-4">Upload/Update Foto Evidence:</h4>
                        
                        <!-- Upload Method Selection -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Metode Upload</label>
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

                        <!-- Main Progress Photo -->
                        <div class="border rounded-lg p-4 mb-4">
                            <label for="foto_evidence_lowering" class="block text-sm font-medium text-gray-700 mb-2">
                                Foto Evidence Lowering Harian
                            </label>
                            
                            <!-- File Upload Section -->
                            <div id="file_upload_section">
                                <div id="photo-preview" class="hidden mb-3">
                                    <img id="preview-image" src="" alt="Preview" class="h-32 w-full object-cover rounded">
                                </div>
                                
                                <div id="photo-placeholder" class="h-32 flex items-center justify-center bg-gray-50 rounded border-dashed border text-gray-400 mb-3">
                                    Tidak ada file dipilih
                                </div>
                                
                                <input type="file" 
                                       id="foto_evidence_lowering" 
                                       name="foto_evidence_penggelaran_bongkaran" 
                                       accept="image/*"
                                       onchange="previewPhoto(this)"
                                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100 @error('foto_evidence_penggelaran_bongkaran') border-red-500 @enderror">
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
                                    Link Google Drive
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
                                    Foto Evidence Marker Tape
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
                                    Foto Evidence Concrete Slab
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
                                    Foto Evidence Cassing
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
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end gap-4 pt-6 border-t">
                    <a href="{{ route('jalur.lowering.show', $lowering) }}" 
                       class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <i class="fas fa-save mr-2"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateTipeBongkaran() {
    const selectedTipe = document.querySelector('input[name="tipe_bongkaran"]:checked');
    if (!selectedTipe) return;

    const tipe = selectedTipe.value;
    const openCutDiv = document.getElementById('open-cut-aksesoris');
    const crossingZinkerDiv = document.getElementById('crossing-zinker-aksesoris');
    const bongkaranLabel = document.getElementById('bongkaran-label');

    // Hide all aksesoris sections
    openCutDiv.classList.add('hidden');
    crossingZinkerDiv.classList.add('hidden');

    // Update bongkaran label
    if (['Manual Boring', 'Manual Boring - PK'].includes(tipe)) {
        bongkaranLabel.textContent = 'Pekerjaan Manual Boring (meter)';
    } else {
        bongkaranLabel.textContent = 'Bongkaran (meter)';
    }

    // Show relevant aksesoris section
    if (tipe === 'Open Cut') {
        openCutDiv.classList.remove('hidden');
    } else if (['Crossing', 'Zinker'].includes(tipe)) {
        crossingZinkerDiv.classList.remove('hidden');
    }

    // Update radio button styles
    document.querySelectorAll('input[name="tipe_bongkaran"]').forEach(radio => {
        const label = radio.closest('label');
        if (radio.checked) {
            label.classList.add('border-green-500', 'bg-green-50');
            label.classList.remove('border-gray-200');
        } else {
            label.classList.remove('border-green-500', 'bg-green-50');
            label.classList.add('border-gray-200');
        }
    });
}

function toggleMarkerTapeQuantity() {
    const checkbox = document.getElementById('aksesoris_marker_tape');
    const quantityDiv = document.getElementById('marker-tape-quantity');
    
    if (checkbox.checked) {
        quantityDiv.classList.remove('hidden');
    } else {
        quantityDiv.classList.add('hidden');
        quantityDiv.querySelector('input').value = '';
    }
}

function toggleConcreteSlabQuantity() {
    const checkbox = document.getElementById('aksesoris_concrete_slab');
    const quantityDiv = document.getElementById('concrete-slab-quantity');
    
    if (checkbox.checked) {
        quantityDiv.classList.remove('hidden');
    } else {
        quantityDiv.classList.add('hidden');
        quantityDiv.querySelector('input').value = '';
    }
}

function toggleCassingQuantity() {
    const checkbox = document.getElementById('aksesoris_cassing');
    const fieldsDiv = document.getElementById('cassing-fields');
    
    if (checkbox.checked) {
        fieldsDiv.classList.remove('hidden');
    } else {
        fieldsDiv.classList.add('hidden');
        fieldsDiv.querySelectorAll('input, select').forEach(input => input.value = '');
    }
}

function toggleCassingQuantityCrossing() {
    const checkbox = document.getElementById('aksesoris_cassing_crossing');
    const fieldsDiv = document.getElementById('cassing-fields-crossing');
    
    if (checkbox.checked) {
        fieldsDiv.classList.remove('hidden');
    } else {
        fieldsDiv.classList.add('hidden');
        fieldsDiv.querySelectorAll('input, select').forEach(input => input.value = '');
    }
}

// Photo upload functions
function previewPhoto(input) {
    const preview = document.getElementById('photo-preview');
    const previewImage = document.getElementById('preview-image');
    const placeholder = document.getElementById('photo-placeholder');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        };
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
    }
}

function toggleUploadMethod() {
    const fileSection = document.getElementById('file_upload_section');
    const linkSection = document.getElementById('drive_link_section');
    const fileInput = document.getElementById('foto_evidence_lowering');
    const linkInput = document.getElementById('foto_evidence_lowering_link');
    const uploadMethod = document.querySelector('input[name="upload_method"]:checked').value;

    if (uploadMethod === 'file') {
        fileSection.classList.remove('hidden');
        linkSection.classList.add('hidden');
        fileInput.removeAttribute('disabled');
        linkInput.setAttribute('disabled', 'disabled');
    } else {
        fileSection.classList.add('hidden');
        linkSection.classList.remove('hidden');
        fileInput.setAttribute('disabled', 'disabled');
        linkInput.removeAttribute('disabled');
    }
}

function updatePhotoSections() {
    const selectedTipe = document.querySelector('input[name="tipe_bongkaran"]:checked');
    if (!selectedTipe) return;

    const tipe = selectedTipe.value;
    const accessoryPhotosSection = document.getElementById('accessory-photos-section');
    const markerTapePhoto = document.getElementById('marker-tape-photo');
    const concreteSlabPhoto = document.getElementById('concrete-slab-photo');
    const cassingPhoto = document.getElementById('cassing-photo');

    // Hide all accessory photo sections first
    accessoryPhotosSection.classList.add('hidden');
    markerTapePhoto.classList.add('hidden');
    concreteSlabPhoto.classList.add('hidden');
    cassingPhoto.classList.add('hidden');

    // Show relevant photo sections based on tipe
    if (tipe === 'Open Cut') {
        accessoryPhotosSection.classList.remove('hidden');
        markerTapePhoto.classList.remove('hidden');
        concreteSlabPhoto.classList.remove('hidden');
        cassingPhoto.classList.remove('hidden');
    } else if (['Crossing', 'Zinker'].includes(tipe)) {
        accessoryPhotosSection.classList.remove('hidden');
        cassingPhoto.classList.remove('hidden');
    }
}

// Image modal functions (reuse from show view)
function openImageModal(imageUrl, title) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const modalTitle = document.getElementById('modalTitle');

    modalImage.src = imageUrl;
    modalTitle.textContent = title;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function tryAlternativeUrls(imgElement) {
    const fileId = imgElement.dataset.fileId;
    if (!fileId) {
        imgElement.parentElement.innerHTML = '<div class="flex flex-col items-center justify-center h-32 text-red-400"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path></svg><p class="text-xs mt-2">Image unavailable</p></div>';
        return;
    }
    
    const alternatives = [
        `https://drive.google.com/uc?export=view&id=${fileId}`,
        `https://drive.google.com/uc?id=${fileId}`,
        `https://drive.google.com/thumbnail?id=${fileId}&sz=w400`,
        `https://docs.google.com/uc?id=${fileId}`,
        `https://lh3.googleusercontent.com/d/${fileId}=w800`
    ];
    
    let currentIndex = imgElement.dataset.attemptIndex || 0;
    currentIndex = parseInt(currentIndex);
    
    if (currentIndex < alternatives.length) {
        imgElement.dataset.attemptIndex = currentIndex + 1;
        imgElement.src = alternatives[currentIndex];
    } else {
        // All alternatives failed, show fallback
        imgElement.parentElement.innerHTML = '<div class="flex flex-col items-center justify-center h-32 text-orange-400"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg><p class="text-xs mt-2">Foto Google Drive</p></div>';
    }
}

// Initialize form state on page load
document.addEventListener('DOMContentLoaded', function() {
    updateTipeBongkaran();
    updatePhotoSections();
    
    // Initialize aksesoris visibility based on current values
    if (document.getElementById('aksesoris_marker_tape').checked) {
        document.getElementById('marker-tape-quantity').classList.remove('hidden');
    }
    if (document.getElementById('aksesoris_concrete_slab').checked) {
        document.getElementById('concrete-slab-quantity').classList.remove('hidden');
    }
    if (document.getElementById('aksesoris_cassing').checked) {
        document.getElementById('cassing-fields').classList.remove('hidden');
    }
    if (document.getElementById('aksesoris_cassing_crossing') && document.getElementById('aksesoris_cassing_crossing').checked) {
        document.getElementById('cassing-fields-crossing').classList.remove('hidden');
    }

    // Initialize upload method display
    toggleUploadMethod();
});

// Add event listeners for tipe bongkaran changes to update photo sections
document.addEventListener('change', function(e) {
    if (e.target.name === 'tipe_bongkaran') {
        updatePhotoSections();
    }
});

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
    }
});
</script>

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4" onclick="closeImageModal()">
  <div class="relative max-w-4xl max-h-full">
    <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300 z-10">
      <i class="fas fa-times"></i>
    </button>
    <img id="modalImage" src="" alt="" class="max-w-full max-h-full object-contain rounded">
    <div id="modalTitle" class="absolute bottom-4 left-4 right-4 text-white text-center text-lg font-medium bg-black bg-opacity-50 rounded p-2"></div>
  </div>
</div>

@endsection