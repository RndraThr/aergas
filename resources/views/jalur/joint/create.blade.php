@extends('layouts.app')

@section('title', 'Tambah Data Joint')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Tambah Data Joint/Sambungan</h1>
            <p class="text-gray-600">Input data joint/sambungan jalur pipa baru</p>
        </div>

        <!-- Alert Messages -->
        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <div>
                        <p class="font-medium">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <p class="font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('jalur.joint.store') }}" id="jointForm" enctype="multipart/form-data">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <div>
                            <label for="cluster_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Cluster <span class="text-red-500">*</span>
                            </label>
                            <select id="cluster_id" 
                                    name="cluster_id" 
                                    onchange=""
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('cluster_id') border-red-500 @enderror"
                                    required>
                                <option value="">Pilih Cluster</option>
                                @foreach($clusters as $cluster)
                                    <option value="{{ $cluster->id }}" 
                                            {{ old('cluster_id') == $cluster->id ? 'selected' : '' }}>
                                        {{ $cluster->nama_cluster }} ({{ $cluster->code_cluster }})
                                    </option>
                                @endforeach
                            </select>
                            @error('cluster_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="fitting_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Tipe Fitting <span class="text-red-500">*</span>
                            </label>
                            <select id="fitting_type_id" 
                                    name="fitting_type_id" 
                                    onchange="updateFittingTypeBehavior();"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('fitting_type_id') border-red-500 @enderror"
                                    required>
                                <option value="" data-code="">Pilih Tipe Fitting</option>
                                @foreach($fittingTypes as $fittingType)
                                    <option value="{{ $fittingType->id }}" 
                                            data-code="{{ $fittingType->code_fitting }}"
                                            {{ old('fitting_type_id') == $fittingType->id ? 'selected' : '' }}>
                                        {{ $fittingType->nama_fitting }} ({{ $fittingType->code_fitting }})
                                    </option>
                                @endforeach
                            </select>
                            @error('fitting_type_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Nomor Joint <span class="text-red-500">*</span>
                            </label>
                            
                            <!-- Joint Number Mode Selection -->
                            <div class="mb-4">
                                <div class="flex space-x-6">
                                    <div class="flex items-center">
                                        <input type="radio" 
                                               id="joint_mode_manual" 
                                               name="joint_number_mode" 
                                               value="manual" 
                                               class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300"
                                               onchange="toggleJointNumberMode()"
                                               {{ old('joint_number_mode', 'manual') === 'manual' ? 'checked' : '' }}>
                                        <label for="joint_mode_manual" class="ml-2 block text-sm text-gray-900">
                                            Manual (Generate Baru)
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" 
                                               id="joint_mode_select" 
                                               name="joint_number_mode" 
                                               value="select" 
                                               class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300"
                                               onchange="toggleJointNumberMode()"
                                               {{ old('joint_number_mode') === 'select' ? 'checked' : '' }}>
                                        <label for="joint_mode_select" class="ml-2 block text-sm text-gray-900">
                                            Pilih dari Pre-created
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Manual Joint Number Input -->
                            <div id="manual-joint-input" class="space-y-3" style="{{ old('joint_number_mode', 'manual') === 'manual' ? '' : 'display: none;' }}">
                                <div class="flex space-x-2">
                                    <div class="flex-1">
                                        <div class="flex">
                                            <div class="bg-gray-100 border border-gray-300 border-r-0 rounded-l-md px-3 py-2 text-sm text-gray-600 font-mono" id="joint-prefix">
                                                -
                                            </div>
                                            <input type="text" 
                                                   id="nomor_joint_suffix" 
                                                   name="nomor_joint_suffix" 
                                                   placeholder="001"
                                                   maxlength="10"
                                                   onchange="updateJointPreview(); checkJointNumberAvailability()"
                                                   oninput="updateJointPreview(); checkJointNumberAvailability()"
                                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-r-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('nomor_joint_suffix') border-red-500 @enderror">
                                        </div>
                                        @error('nomor_joint_suffix')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                
                                <!-- Joint Number Status Info -->
                                <div id="joint-status-info" class="hidden">
                                    <div id="joint-status-message" class="p-3 rounded-md text-sm">
                                        <!-- Status message will be populated by JavaScript -->
                                    </div>
                                </div>
                            </div>

                            <!-- Select Pre-created Joint Number -->
                            <div id="select-joint-input" class="space-y-3" style="{{ old('joint_number_mode') === 'select' ? '' : 'display: none;' }}">
                                <div>
                                    <select id="available_joint_numbers" 
                                            name="selected_joint_number_id"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('selected_joint_number_id') border-red-500 @enderror">
                                        <option value="">Pilih cluster dan fitting type terlebih dahulu</option>
                                    </select>
                                    @error('selected_joint_number_id')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <p class="text-xs text-gray-500">Hanya menampilkan nomor joint yang tersedia (belum digunakan)</p>
                            </div>
                        </div>

                        <div>
                            <label for="tanggal_joint" class="block text-sm font-medium text-gray-700 mb-2">
                                Tanggal Joint <span class="text-red-500">*</span>
                            </label>
                            <input type="date" 
                                   id="tanggal_joint" 
                                   name="tanggal_joint" 
                                   value="{{ old('tanggal_joint', date('Y-m-d')) }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('tanggal_joint') border-red-500 @enderror"
                                   required>
                            @error('tanggal_joint')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="diameter_filter" class="block text-sm font-medium text-gray-700 mb-2">
                                Diameter Filter <span class="text-red-500">*</span>
                            </label>
                            <select id="diameter_filter" 
                                    name="diameter_filter" 
                                    onchange="filterLineNumbersByDiameter()"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('diameter_filter') border-red-500 @enderror"
                                    required>
                                <option value="">Pilih Diameter</option>
                                @foreach($diameters as $diameter)
                                    <option value="{{ $diameter }}" 
                                            {{ old('diameter_filter') == $diameter ? 'selected' : '' }}>
                                        {{ $diameter }}"
                                    </option>
                                @endforeach
                            </select>
                            @error('diameter_filter')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">Pilih diameter untuk memfilter line numbers yang tersedia</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="joint_line_from" class="block text-sm font-medium text-gray-700 mb-2">
                                    Joint Line From <span class="text-red-500">*</span>
                                </label>
                                <select id="joint_line_from" 
                                        name="joint_line_from" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('joint_line_from') border-red-500 @enderror"
                                        required disabled>
                                    <option value="">Pilih Line From</option>
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
                                        required disabled>
                                    <option value="">Pilih Line To</option>
                                </select>
                                @error('joint_line_to')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Joint Line Optional (for Equal Tee) -->
                        <div id="joint-line-optional-section" class="hidden">
                            <label for="joint_line_optional" class="block text-sm font-medium text-gray-700 mb-2">
                                Joint Line Optional (3rd Line for Equal Tee) <span class="text-red-500">*</span>
                            </label>
                            <select id="joint_line_optional" 
                                    name="joint_line_optional" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('joint_line_optional') border-red-500 @enderror"
                                    disabled>
                                <option value="">Pilih Line Optional</option>
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
                                <option value="EF" {{ old('tipe_penyambungan') == 'EF' ? 'selected' : '' }}>EF</option>
                                <option value="BF" {{ old('tipe_penyambungan') == 'BF' ? 'selected' : '' }}>BF</option>
                            </select>
                            @error('tipe_penyambungan')
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
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('keterangan') border-red-500 @enderror">{{ old('keterangan') }}</textarea>
                            @error('keterangan')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                        <!-- Nomor Joint Preview -->
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-purple-800 mb-2">Preview Nomor Joint</h3>
                            <p class="text-sm text-purple-700 mb-2">Nomor joint yang akan dibuat:</p>
                            <div class="bg-white border border-purple-300 rounded px-3 py-2">
                                <code class="text-lg font-mono text-purple-900" id="joint-code-preview">-</code>
                            </div>
                            <p class="text-xs text-purple-600 mt-2">
                                Format: [Cluster Code]-[Fitting Code][Number]
                            </p>
                        </div>

                        <!-- Joint Line to Line Preview -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-blue-800 mb-2">Joint Line to Line</h3>
                            <div class="bg-white border border-blue-300 rounded px-3 py-2">
                                <code class="text-sm font-mono text-blue-900" id="joint-line-preview">
                                    <span id="line-from-preview">-</span> → <span id="line-to-preview">-</span>
                                </code>
                            </div>
                        </div>

                        <!-- Single Photo Upload Section -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Upload Foto Evidence Joint</h3>
                            <p class="text-sm text-gray-600 mb-4">Upload 1 foto evidence hasil penyambungan joint</p>
                            
                            <!-- Upload Method Selection -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-3">Metode Upload <span class="text-red-500">*</span></label>
                                <div class="flex space-x-4">
                                    <div class="flex items-center">
                                        <input type="radio" id="upload_method_file" name="upload_method" value="file" checked
                                               class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300"
                                               onchange="toggleUploadMethod()">
                                        <label for="upload_method_file" class="ml-2 text-sm text-gray-700">Upload File</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" id="upload_method_link" name="upload_method" value="link"
                                               class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300"
                                               onchange="toggleUploadMethod()">
                                        <label for="upload_method_link" class="ml-2 text-sm text-gray-700">Link Google Drive</label>
                                    </div>
                                </div>
                            </div>

                            <!-- File Upload Section -->
                            <div id="file_upload_section">
                                <label for="foto_evidence_joint" class="block text-sm font-medium text-gray-700 mb-2">
                                    Foto Evidence Joint <span class="text-red-500">*</span>
                                </label>
                                <input type="file" 
                                       id="foto_evidence_joint" 
                                       name="foto_evidence_joint" 
                                       accept="image/*"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('foto_evidence_joint') border-red-500 @enderror">
                                @error('foto_evidence_joint')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG. Max: 5MB</p>
                            </div>

                            <!-- Google Drive Link Section -->
                            <div id="drive_link_section" class="hidden">
                                <label for="foto_evidence_joint_link" class="block text-sm font-medium text-gray-700 mb-2">
                                    Link Google Drive <span class="text-red-500">*</span>
                                </label>
                                <input type="url" 
                                       id="foto_evidence_joint_link" 
                                       name="foto_evidence_joint_link" 
                                       placeholder="https://drive.google.com/file/d/xyz/view"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('foto_evidence_joint_link') border-red-500 @enderror">
                                @error('foto_evidence_joint_link')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500 mt-1">Masukkan link Google Drive. Sistem akan otomatis download dan simpan foto ke folder project.</p>
                            </div>
                        </div>

                        <!-- Status Information -->
                        <div class="bg-gray-100 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-800 mb-2">Status Laporan Joint</h3>
                            <p class="text-sm text-gray-600">
                                Status akan dimulai dari <span class="font-semibold text-gray-800">"Draft"</span> 
                                kemudian menjalani approval:
                            </p>
                            <ul class="text-xs text-gray-600 mt-2 space-y-1">
                                <li>• Draft → ACC Tracer → ACC CGP</li>
                                <li>• Jika ditolak: Revisi Tracer / Revisi CGP</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end space-x-4">
                    <a href="{{ route('jalur.joint.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                        Simpan Data Joint
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateJointLinePreview() {
    const lineFromSelect = document.getElementById('joint_line_from');
    const lineToSelect = document.getElementById('joint_line_to');
    const lineFromPreview = document.getElementById('line-from-preview');
    const lineToPreview = document.getElementById('line-to-preview');
    
    const lineFrom = lineFromSelect.value;
    const lineTo = lineToSelect.value;
    
    lineFromPreview.textContent = lineFrom || '-';
    lineToPreview.textContent = lineTo || '-';
}

function updateFittingTypeBehavior() {
    const fittingTypeSelect = document.getElementById('fitting_type_id');
    const selectedOption = fittingTypeSelect.options[fittingTypeSelect.selectedIndex];
    const jointLineOptionalSection = document.getElementById('joint-line-optional-section');
    const jointLineOptionalField = document.getElementById('joint_line_optional');
    
    // Check if we have a valid selection and it's Equal Tee
    const fittingCode = selectedOption && selectedOption.dataset ? selectedOption.dataset.code : '';
    
    // Show/hide optional line field based on fitting type
    if (fittingCode === 'ET') { // Equal Tee
        jointLineOptionalSection.classList.remove('hidden');
        jointLineOptionalField.setAttribute('required', 'required');
    } else {
        jointLineOptionalSection.classList.add('hidden');
        jointLineOptionalField.removeAttribute('required');
        jointLineOptionalField.value = ''; // Clear value when hidden
    }
}


function updateJointPrefix() {
    const clusterId = document.getElementById('cluster_id').value;
    const fittingTypeId = document.getElementById('fitting_type_id').value;
    const jointPrefixElement = document.getElementById('joint-prefix');
    
    if (!clusterId || !fittingTypeId) {
        jointPrefixElement.textContent = '-';
        return;
    }
    
    // Get cluster code
    const clusterSelect = document.getElementById('cluster_id');
    const clusterText = clusterSelect.options[clusterSelect.selectedIndex].text;
    const clusterCode = clusterText.match(/\(([^)]+)\)$/)?.[1] || '';
    
    // Get fitting code
    const fittingSelect = document.getElementById('fitting_type_id');
    const fittingCode = fittingSelect.options[fittingSelect.selectedIndex].getAttribute('data-code') || '';
    
    const prefix = clusterCode && fittingCode ? `${clusterCode}-${fittingCode}` : '-';
    jointPrefixElement.textContent = prefix;
    
    updateJointPreview();
}

function updateJointPreview() {
    const jointPrefixElement = document.getElementById('joint-prefix');
    const suffixInput = document.getElementById('nomor_joint_suffix');
    const previewCode = document.getElementById('joint-code-preview');
    
    const prefix = jointPrefixElement.textContent;
    const suffix = suffixInput.value.trim();
    
    if (prefix !== '-' && suffix) {
        previewCode.textContent = `${prefix}${suffix}`;
    } else {
        previewCode.textContent = '-';
    }
}

async function checkJointNumberAvailability() {
    const jointPrefixElement = document.getElementById('joint-prefix');
    const suffixInput = document.getElementById('nomor_joint_suffix');
    const statusInfo = document.getElementById('joint-status-info');
    const statusMessage = document.getElementById('joint-status-message');
    const submitButton = document.querySelector('button[type="submit"]');
    
    const prefix = jointPrefixElement.textContent;
    const suffix = suffixInput.value.trim();
    
    if (prefix === '-' || !suffix) {
        statusInfo.classList.add('hidden');
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
        }
        return;
    }
    
    const fullJointNumber = `${prefix}${suffix}`;
    
    try {
        const response = await fetch(`{{ route('jalur.joint.api.check-joint-availability') }}?nomor_joint=${encodeURIComponent(fullJointNumber)}`);
        const data = await response.json();
        
        if (response.ok) {
            statusInfo.classList.remove('hidden');
            
            if (data.is_available) {
                // Available - show success message
                statusMessage.className = 'p-3 rounded-md text-sm bg-green-100 border border-green-200 text-green-800';
                statusMessage.innerHTML = `
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Nomor joint tersedia dan dapat digunakan
                    </div>
                `;
                
                // Enable submit button
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            } else {
                // Not available - show error message
                statusMessage.className = 'p-3 rounded-md text-sm bg-red-100 border border-red-200 text-red-800';
                let message = `
                    <div class="flex items-start">
                        <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <div>
                            <p class="font-medium">Nomor joint sudah digunakan</p>
                `;
                
                if (data.used_by) {
                    message += `
                        <p class="mt-1 text-xs">
                            Digunakan oleh joint: <span class="font-mono">${data.used_by.nomor_joint}</span><br>
                            Tanggal: ${new Date(data.used_by.created_at).toLocaleDateString('id-ID')}
                        </p>
                    `;
                }
                
                message += `
                        </div>
                    </div>
                `;
                statusMessage.innerHTML = message;
                
                // Disable submit button
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                }
            }
        } else {
            throw new Error(data.error || 'Failed to check joint number availability');
        }
        
    } catch (error) {
        console.error('Error checking joint number availability:', error);
        statusInfo.classList.add('hidden');
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
}

// Filter line numbers by diameter
async function filterLineNumbersByDiameter() {
    const clusterSelect = document.getElementById('cluster_id');
    const diameterSelect = document.getElementById('diameter_filter');
    const lineFromSelect = document.getElementById('joint_line_from');
    const lineToSelect = document.getElementById('joint_line_to');
    const lineOptionalSelect = document.getElementById('joint_line_optional');
    
    const clusterId = clusterSelect.value;
    const diameter = diameterSelect.value;
    
    // Clear and disable line selects if no cluster or diameter selected
    if (!clusterId || !diameter) {
        [lineFromSelect, lineToSelect, lineOptionalSelect].forEach(select => {
            select.innerHTML = '<option value="">Pilih Line Number</option>';
            select.disabled = true;
        });
        return;
    }
    
    try {
        // Fetch line numbers filtered by cluster and diameter
        const response = await fetch(`/jalur/joint/api/line-numbers?cluster_id=${clusterId}&diameter=${diameter}`);
        const lineNumbers = await response.json();
        
        // Populate all line number selects
        [lineFromSelect, lineToSelect, lineOptionalSelect].forEach(select => {
            select.innerHTML = '<option value="">Pilih Line Number</option>';
            
            // Add EXISTING option for special cases
            const existingOption = document.createElement('option');
            existingOption.value = 'EXISTING';
            existingOption.textContent = 'EXISTING (Line yang sudah ada)';
            existingOption.style.fontStyle = 'italic';
            existingOption.style.color = '#6B7280';
            select.appendChild(existingOption);
            
            lineNumbers.forEach(line => {
                const option = document.createElement('option');
                option.value = line.line_number;
                option.textContent = `${line.line_number} (${line.diameter}")`;
                select.appendChild(option);
            });
            
            select.disabled = false;
        });
        
    } catch (error) {
        console.error('Error fetching line numbers:', error);
        alert('Gagal memuat line numbers. Silakan coba lagi.');
    }
}

// Toggle upload method between file and link
function toggleUploadMethod() {
    const fileSection = document.getElementById('file_upload_section');
    const linkSection = document.getElementById('drive_link_section');
    const fileInput = document.getElementById('foto_evidence_joint');
    const linkInput = document.getElementById('foto_evidence_joint_link');
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

// Event listeners
document.getElementById('cluster_id').addEventListener('change', function() {
    updateJointPrefix();
    loadAvailableJointNumbers();
    // Reset diameter filter when cluster changes
    document.getElementById('diameter_filter').value = '';
    filterLineNumbersByDiameter();
});
document.getElementById('fitting_type_id').addEventListener('change', function() {
    updateJointPrefix();
    loadAvailableJointNumbers();
});
document.getElementById('joint_line_from').addEventListener('change', updateJointLinePreview);
document.getElementById('joint_line_to').addEventListener('change', updateJointLinePreview);

// Joint Number Mode Toggle
function toggleJointNumberMode() {
    const manualInput = document.getElementById('manual-joint-input');
    const selectInput = document.getElementById('select-joint-input');
    const manualRadio = document.getElementById('joint_mode_manual');
    const suffixInput = document.getElementById('nomor_joint_suffix');
    const jointNumberSelect = document.getElementById('available_joint_numbers');
    
    if (manualRadio.checked) {
        manualInput.style.display = 'block';
        selectInput.style.display = 'none';
        suffixInput.setAttribute('required', 'required');
        jointNumberSelect.removeAttribute('required');
    } else {
        manualInput.style.display = 'none';
        selectInput.style.display = 'block';
        suffixInput.removeAttribute('required');
        jointNumberSelect.setAttribute('required', 'required');
        
        // Load available joint numbers when switching to select mode
        loadAvailableJointNumbers();
    }
}

// Load Available Joint Numbers
async function loadAvailableJointNumbers() {
    const clusterId = document.getElementById('cluster_id').value;
    const fittingTypeId = document.getElementById('fitting_type_id').value;
    const jointNumberSelect = document.getElementById('available_joint_numbers');
    
    if (!clusterId || !fittingTypeId) {
        jointNumberSelect.innerHTML = '<option value="">Pilih cluster dan fitting type terlebih dahulu</option>';
        return;
    }
    
    try {
        const response = await fetch(`{{ route('jalur.joint-numbers.api.available-joint-numbers') }}?cluster_id=${clusterId}&fitting_type_id=${fittingTypeId}`);
        const data = await response.json();
        
        jointNumberSelect.innerHTML = '<option value="">Pilih nomor joint</option>';
        
        if (data.length === 0) {
            jointNumberSelect.innerHTML = '<option value="">Tidak ada nomor joint yang tersedia</option>';
        } else {
            data.forEach(jointNumber => {
                const option = document.createElement('option');
                option.value = jointNumber.id;
                option.textContent = jointNumber.nomor_joint;
                jointNumberSelect.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading joint numbers:', error);
        jointNumberSelect.innerHTML = '<option value="">Error loading data</option>';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateJointLinePreview();
    updateJointPrefix();
    updateFittingTypeBehavior();
    toggleUploadMethod(); // Initialize upload method display
    toggleJointNumberMode(); // Initialize joint number mode
    
    // Restore old value if exists
    const oldSuffix = '{{ old('nomor_joint_suffix') }}';
    if (oldSuffix) {
        document.getElementById('nomor_joint_suffix').value = oldSuffix;
        updateJointPreview();
        checkJointNumberAvailability();
    }
});
</script>
@endsection