@extends('layouts.app')

@section('title', 'Edit Data Joint')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Edit Data Joint/Sambungan</h1>
            <p class="text-gray-600">{{ $joint->nomor_joint }} - {{ $joint->fittingType?->nama_fitting ?? '-' }}</p>
            <p class="text-sm text-gray-500">Line Connections: {{ $joint->formatted_joint_line }}
                @if($joint->joint_line_optional && $joint->isEqualTee())
                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded ml-2">3-Way</span>
                @endif
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('jalur.joint.update', $joint) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <div>
                            <label for="cluster_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Cluster <span class="text-red-500">*</span>
                            </label>
                            <select id="cluster_id" 
                                    name="cluster_id" 
                                    onchange="loadLineNumbers()"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    required>
                                <option value="">Pilih Cluster</option>
                                @foreach($clusters as $cluster)
                                    <option value="{{ $cluster->id }}" 
                                            {{ old('cluster_id', $joint->cluster_id) == $cluster->id ? 'selected' : '' }}>
                                        {{ $cluster->nama_cluster }} ({{ $cluster->code_cluster }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="line_number_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Line Number
                            </label>
                            <select id="line_number_id" 
                                    name="line_number_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('line_number_id') border-red-500 @enderror">
                                <option value="">Loading...</option>
                                @foreach($lineNumbers as $lineNumber)
                                    <option value="{{ $lineNumber->id }}" 
                                            {{ old('line_number_id', $joint->line_number_id) == $lineNumber->id ? 'selected' : '' }}>
                                        {{ $lineNumber->line_number }} - {{ $lineNumber->nama_jalan }}
                                    </option>
                                @endforeach
                            </select>
                            @error('line_number_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-yellow-600 text-sm mt-1">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.98-.833-2.75 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                Mengubah line number akan mempengaruhi nomor joint
                            </p>
                        </div>

                        <div>
                            <label for="fitting_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Tipe Fitting
                            </label>
                            <select id="fitting_type_id" 
                                    name="fitting_type_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('fitting_type_id') border-red-500 @enderror">
                                <option value="">Pilih Tipe Fitting</option>
                                @foreach($fittingTypes as $fittingType)
                                    <option value="{{ $fittingType->id }}" 
                                            data-code="{{ $fittingType->code_fitting }}"
                                            {{ old('fitting_type_id', $joint->fitting_type_id) == $fittingType->id ? 'selected' : '' }}>
                                        {{ $fittingType->nama_fitting }} ({{ $fittingType->code_fitting }})
                                    </option>
                                @endforeach
                            </select>
                            @error('fitting_type_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            @if($joint->fitting_type_id != old('fitting_type_id', $joint->fitting_type_id))
                                <p class="text-yellow-600 text-sm mt-1">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.98-.833-2.75 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    Mengubah tipe fitting akan mempengaruhi nomor joint
                                </p>
                            @endif
                        </div>

                        <div>
                            <label for="tanggal_joint" class="block text-sm font-medium text-gray-700 mb-2">
                                Tanggal Joint <span class="text-red-500">*</span>
                            </label>
                            <input type="date" 
                                   id="tanggal_joint" 
                                   name="tanggal_joint" 
                                   value="{{ old('tanggal_joint', $joint->tanggal_joint->format('Y-m-d')) }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('tanggal_joint') border-red-500 @enderror"
                                   required>
                            @error('tanggal_joint')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="joint_line_from" class="block text-sm font-medium text-gray-700 mb-2">
                                    Joint Line From <span class="text-red-500">*</span>
                                </label>
                                <select id="joint_line_from" 
                                        name="joint_line_from" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('joint_line_from') border-red-500 @enderror"
                                        required>
                                    <option value="">Pilih Line From</option>
                                    <option value="EXISTING" 
                                            {{ old('joint_line_from', $joint->joint_line_from) == 'EXISTING' ? 'selected' : '' }}
                                            style="font-style: italic; color: #6B7280;">
                                        EXISTING (Line yang sudah ada)
                                    </option>
                                    @foreach($lineNumbers as $lineNumber)
                                        <option value="{{ $lineNumber->line_number }}" 
                                                {{ old('joint_line_from', $joint->joint_line_from) == $lineNumber->line_number ? 'selected' : '' }}>
                                            {{ $lineNumber->line_number }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('joint_line_from')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="joint_line_to" class="block text-sm font-medium text-gray-700 mb-2">
                                    Joint Line To <span class="text-red-500">*</span>
                                </label>
                                <select id="joint_line_to" 
                                        name="joint_line_to" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('joint_line_to') border-red-500 @enderror"
                                        required>
                                    <option value="">Pilih Line To</option>
                                    <option value="EXISTING" 
                                            {{ old('joint_line_to', $joint->joint_line_to) == 'EXISTING' ? 'selected' : '' }}
                                            style="font-style: italic; color: #6B7280;">
                                        EXISTING (Line yang sudah ada)
                                    </option>
                                    @foreach($lineNumbers as $lineNumber)
                                        <option value="{{ $lineNumber->line_number }}" 
                                                {{ old('joint_line_to', $joint->joint_line_to) == $lineNumber->line_number ? 'selected' : '' }}>
                                            {{ $lineNumber->line_number }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('joint_line_to')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Joint Line Optional (for Equal Tee) -->
                        <div id="joint-line-optional-section" class="{{ $joint->isEqualTee() && $joint->joint_line_optional ? '' : 'hidden' }}">
                            <label for="joint_line_optional" class="block text-sm font-medium text-gray-700 mb-2">
                                Joint Line Optional (3rd Line for Equal Tee) <span class="text-red-500">*</span>
                            </label>
                            <select id="joint_line_optional" 
                                    name="joint_line_optional" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('joint_line_optional') border-red-500 @enderror">
                                <option value="">Pilih Line Optional</option>
                                <option value="EXISTING" 
                                        {{ old('joint_line_optional', $joint->joint_line_optional) == 'EXISTING' ? 'selected' : '' }}
                                        style="font-style: italic; color: #6B7280;">
                                    EXISTING (Line yang sudah ada)
                                </option>
                                @foreach($lineNumbers as $lineNumber)
                                    <option value="{{ $lineNumber->line_number }}" 
                                            {{ old('joint_line_optional', $joint->joint_line_optional) == $lineNumber->line_number ? 'selected' : '' }}>
                                        {{ $lineNumber->line_number }}
                                    </option>
                                @endforeach
                            </select>
                            @error('joint_line_optional')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">Field ini wajib diisi ketika menggunakan Equal Tee untuk koneksi 3-arah</p>
                        </div>

                        <div>
                            <label for="tipe_penyambungan" class="block text-sm font-medium text-gray-700 mb-2">
                                Tipe Penyambungan <span class="text-red-500">*</span>
                            </label>
                            <select id="tipe_penyambungan" 
                                    name="tipe_penyambungan" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('tipe_penyambungan') border-red-500 @enderror"
                                    required>
                                <option value="">Pilih Tipe Penyambungan</option>
                                <option value="EF" {{ old('tipe_penyambungan', $joint->tipe_penyambungan) == 'EF' ? 'selected' : '' }}>EF</option>
                                <option value="BF" {{ old('tipe_penyambungan', $joint->tipe_penyambungan) == 'BF' ? 'selected' : '' }}>BF</option>
                            </select>
                            @error('tipe_penyambungan')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="lokasi_joint" class="block text-sm font-medium text-gray-700 mb-2">
                                Lokasi Joint <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="lokasi_joint" 
                                   name="lokasi_joint" 
                                   value="{{ old('lokasi_joint', $joint->lokasi_joint) }}"
                                   placeholder="Contoh: Depan Warung Pak Budi"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('lokasi_joint') border-red-500 @enderror"
                                   required>
                            @error('lokasi_joint')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-2">
                                Keterangan
                            </label>
                            <textarea id="keterangan" 
                                      name="keterangan" 
                                      rows="4"
                                      placeholder="Keterangan atau catatan tambahan..."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('keterangan') border-red-500 @enderror">{{ old('keterangan', $joint->keterangan) }}</textarea>
                            @error('keterangan')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">

                        <!-- Current Photos -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Foto Evidence Saat Ini</h3>
                            
                            @if($joint->photoApprovals && $joint->photoApprovals->count() > 0)
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    @foreach($joint->photoApprovals as $photo)
                                        <div class="relative">
                                            @php
                                                // Convert Google Drive URL to direct image URL
                                                $imageUrl = $photo->photo_url;
                                                $isPdf = str_ends_with(Str::lower($imageUrl), '.pdf');
                                                $fileId = null;
                                                
                                                // Handle Google Drive URLs
                                                if (str_contains($imageUrl, 'drive.google.com')) {
                                                    // Extract file ID from various Google Drive URL formats
                                                    if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $imageUrl, $matches)) {
                                                        $fileId = $matches[1];
                                                        // Primary Google Drive image URL (most reliable)
                                                        $imageUrl = "https://lh3.googleusercontent.com/d/{$fileId}=w800";
                                                    } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $imageUrl, $matches)) {
                                                        $fileId = $matches[1];
                                                        $imageUrl = "https://lh3.googleusercontent.com/d/{$fileId}=w800";
                                                    }
                                                }
                                                // Local storage files
                                                elseif (!str_contains($imageUrl, 'http')) {
                                                    $checkPath = $imageUrl;
                                                    // Strip /storage/ prefix if present for exists check
                                                    if (str_starts_with($imageUrl, '/storage/')) {
                                                        $checkPath = substr($imageUrl, 9);
                                                    } elseif (str_starts_with($imageUrl, 'storage/')) {
                                                        $checkPath = substr($imageUrl, 8);
                                                    }
                                                    
                                                    if (Storage::disk('public')->exists($checkPath)) {
                                                        $imageUrl = asset('storage/' . $checkPath);
                                                    }
                                                }
                                            @endphp

                                            @if($imageUrl && !$isPdf)
                                                <img src="{{ $imageUrl }}" 
                                                     alt="{{ $photo->photo_field_name }}" 
                                                     class="w-full h-24 object-cover rounded-lg"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                <div class="w-full h-24 bg-gray-200 rounded-lg flex items-center justify-center text-gray-500 text-xs" style="display: none;">
                                                    Foto tidak dapat dimuat
                                                </div>
                                            @else
                                                <div class="w-full h-24 bg-gray-200 rounded-lg flex items-center justify-center text-gray-500 text-xs">
                                                    @if($isPdf)
                                                        PDF File
                                                    @else
                                                        Foto tidak tersedia
                                                    @endif
                                                </div>
                                            @endif

                                            <div class="absolute top-1 left-1 bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded">
                                                {{ str_replace(['foto_evidence_', '_'], ['', ' '], $photo->photo_field_name) }}
                                            </div>
                                            <div class="absolute top-1 right-1">
                                                <span class="inline-flex px-1 py-0.5 text-xs rounded
                                                    @if($photo->ai_status === 'approved') bg-green-100 text-green-800
                                                    @elseif($photo->ai_status === 'rejected') bg-red-100 text-red-800
                                                    @else bg-yellow-100 text-yellow-800
                                                    @endif">
                                                    {{ $photo->ai_status }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            
                            <h4 class="text-sm font-medium text-gray-700 mb-2 mt-6">Upload Foto Baru (Opsional)</h4>
                            <p class="text-sm text-gray-600 mb-4">Upload foto baru untuk mengganti foto evidence yang sudah ada</p>
                            
                            <div>
                                <label for="foto_evidence_joint" class="block text-sm font-medium text-gray-700 mb-2">
                                    Foto Evidence Joint
                                </label>
                                <input type="file" 
                                       id="foto_evidence_joint" 
                                       name="foto_evidence_joint[]" 
                                       accept="image/*,application/pdf"
                                       multiple
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('foto_evidence_joint.*') border-red-500 @enderror">
                                @error('foto_evidence_joint.*')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500 mt-1">
                                    Upload foto evidence joint baru. Foto yang di-upload akan ditambahkan ke foto yang sudah ada.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end space-x-4">
                    <a href="{{ route('jalur.joint.show', $joint) }}" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                        Update Data Joint
                    </button>
                </div>
            </form>
        </div>

        <!-- Current Data Info -->
        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h3 class="text-sm font-medium text-gray-800 mb-2">Data Saat Ini</h3>
            <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                <div>
                    <span class="font-medium">Nomor Joint:</span> {{ $joint->nomor_joint }}
                </div>
                <div>
                    <span class="font-medium">Status:</span> {{ $joint->status_label }}
                </div>
                <div>
                    <span class="font-medium">Total Foto:</span> {{ $joint->photoApprovals ? $joint->photoApprovals->count() : 0 }} foto
                </div>
                <div>
                    <span class="font-medium">Terakhir Update:</span> {{ $joint->updated_at->format('d/m/Y H:i') }}
                </div>
            </div>
            @if($joint->tracer_approved_at || $joint->cgp_approved_at)
                <div class="mt-3 text-xs text-yellow-700 bg-yellow-50 p-2 rounded">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.98-.833-2.75 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    Hati-hati saat mengedit joint yang sudah diapprove. Perubahan akan mereset approval status.
                </div>
            @endif
        </div>
    </div>
</div>

<script>
async function loadLineNumbers() {
    const clusterId = document.getElementById('cluster_id').value;
    const lineNumberSelect = document.getElementById('line_number_id');
    
    if (!clusterId) {
        lineNumberSelect.innerHTML = '<option value="">Pilih Cluster terlebih dahulu</option>';
        return;
    }
    
    const currentValue = lineNumberSelect.value;
    lineNumberSelect.innerHTML = '<option value="">Loading...</option>';
    
    try {
        const response = await fetch(`{{ route('jalur.joint.api.line-numbers') }}?cluster_id=${clusterId}`);
        const lineNumbers = await response.json();
        
        lineNumberSelect.innerHTML = '<option value="">Pilih Line Number</option>';
        
        lineNumbers.forEach(lineNumber => {
            const option = document.createElement('option');
            option.value = lineNumber.id;
            option.textContent = `${lineNumber.line_number} - ${lineNumber.nama_jalan || 'No street name'}`;
            lineNumberSelect.appendChild(option);
        });
        
        // Restore current value if it exists in the new options
        if (currentValue) {
            lineNumberSelect.value = currentValue;
        }
        
    } catch (error) {
        console.error('Failed to load line numbers:', error);
        lineNumberSelect.innerHTML = '<option value="">Error loading line numbers</option>';
    }
}

// Equal Tee functionality
function updateFittingTypeBehavior() {
    const fittingSelect = document.getElementById('fitting_type_id');
    const jointLineOptionalSection = document.getElementById('joint-line-optional-section');
    const jointLineOptionalField = document.getElementById('joint_line_optional');
    
    if (!fittingSelect || !jointLineOptionalSection || !jointLineOptionalField) return;
    
    const selectedOption = fittingSelect.options[fittingSelect.selectedIndex];
    const fittingCode = selectedOption && selectedOption.dataset ? selectedOption.dataset.code : '';
    
    // Get fitting code from option text if data-code is not available
    const fittingText = selectedOption ? selectedOption.text : '';
    const isEqualTee = fittingText.includes('(ET)') || fittingCode === 'ET';
    
    if (isEqualTee) {
        jointLineOptionalSection.classList.remove('hidden');
        jointLineOptionalField.setAttribute('required', 'required');
    } else {
        jointLineOptionalSection.classList.add('hidden');
        jointLineOptionalField.removeAttribute('required');
        jointLineOptionalField.value = '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadLineNumbers();
    
    // Add event listener for fitting type change
    const fittingSelect = document.getElementById('fitting_type_id');
    if (fittingSelect) {
        fittingSelect.addEventListener('change', updateFittingTypeBehavior);
        // Initialize on page load
        updateFittingTypeBehavior();
    }
});
</script>
@endsection