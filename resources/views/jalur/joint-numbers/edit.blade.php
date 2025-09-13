@extends('layouts.app')

@section('title', 'Edit Nomor Joint')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Edit Nomor Joint</h1>
            <p class="text-gray-600 mt-1">Ubah informasi nomor joint {{ $jointNumber->nomor_joint }}</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('jalur.joint-numbers.show', $jointNumber) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                ← Kembali ke Detail
            </a>
        </div>
    </div>

    @if($jointNumber->usedByJoint)
    <!-- Warning Alert -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Nomor Joint Sudah Digunakan</h3>
                <p class="text-sm text-yellow-700 mt-1">
                    Nomor joint ini sudah digunakan dalam data joint dan tidak dapat diedit untuk menjaga integritas data.
                    <a href="{{ route('jalur.joint.show', $jointNumber->usedByJoint) }}" class="font-medium underline">
                        Lihat data joint terkait
                    </a>
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Form -->
    <div class="bg-white rounded-lg shadow">
        <form method="POST" action="{{ route('jalur.joint-numbers.update', $jointNumber) }}" class="p-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Cluster -->
                <div>
                    <label for="cluster_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Cluster <span class="text-red-500">*</span>
                    </label>
                    <select name="cluster_id" 
                            id="cluster_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('cluster_id') border-red-500 @enderror"
                            {{ $jointNumber->usedByJoint ? 'disabled' : 'required' }}>
                        <option value="">Pilih Cluster</option>
                        @foreach($clusters as $cluster)
                            <option value="{{ $cluster->id }}" {{ (old('cluster_id', $jointNumber->cluster_id) == $cluster->id) ? 'selected' : '' }}>
                                {{ $cluster->nama_cluster }} ({{ $cluster->code_cluster }})
                            </option>
                        @endforeach
                    </select>
                    @error('cluster_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Fitting Type -->
                <div>
                    <label for="fitting_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Fitting Type <span class="text-red-500">*</span>
                    </label>
                    <select name="fitting_type_id" 
                            id="fitting_type_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('fitting_type_id') border-red-500 @enderror"
                            {{ $jointNumber->usedByJoint ? 'disabled' : 'required' }}>
                        <option value="">Pilih Fitting Type</option>
                        @foreach($fittingTypes as $fittingType)
                            <option value="{{ $fittingType->id }}" {{ (old('fitting_type_id', $jointNumber->fitting_type_id) == $fittingType->id) ? 'selected' : '' }}>
                                {{ $fittingType->nama_fitting }} ({{ $fittingType->code_fitting }})
                            </option>
                        @endforeach
                    </select>
                    @error('fitting_type_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Joint Code -->
            <div class="mt-6">
                <label for="joint_code" class="block text-sm font-medium text-gray-700 mb-2">
                    Kode Joint <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="joint_code" 
                       id="joint_code" 
                       value="{{ old('joint_code', $jointNumber->joint_code) }}"
                       placeholder="Contoh: 001"
                       maxlength="10"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('joint_code') border-red-500 @enderror"
                       {{ $jointNumber->usedByJoint ? 'disabled' : 'required' }}>
                @error('joint_code')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500 mt-1">Kode akan digabung dengan kode cluster dan fitting type menjadi nomor joint lengkap</p>
            </div>

            <!-- Preview Nomor Joint -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Preview Nomor Joint
                </label>
                <div class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-md">
                    <span id="joint-preview" class="text-gray-700 font-medium">{{ $jointNumber->nomor_joint }}</span>
                </div>
                <p class="text-xs text-gray-500 mt-1">Format: ClusterCode-FittingCode + Kode Joint</p>
            </div>

            <!-- Status -->
            <div class="mt-6">
                <div class="flex items-center">
                    <input type="checkbox" 
                           name="is_active" 
                           id="is_active" 
                           value="1"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                           {{ old('is_active', $jointNumber->is_active) ? 'checked' : '' }}
                           {{ $jointNumber->usedByJoint ? 'disabled' : '' }}>
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                        Aktif
                    </label>
                </div>
                <p class="text-xs text-gray-500 mt-1">Nomor joint yang aktif dapat digunakan untuk membuat data joint</p>
            </div>

            @unless($jointNumber->usedByJoint)
            <!-- Hidden inputs for disabled fields -->
            @if($jointNumber->usedByJoint)
            <input type="hidden" name="cluster_id" value="{{ $jointNumber->cluster_id }}">
            <input type="hidden" name="fitting_type_id" value="{{ $jointNumber->fitting_type_id }}">
            <input type="hidden" name="joint_code" value="{{ $jointNumber->joint_code }}">
            <input type="hidden" name="is_active" value="{{ $jointNumber->is_active ? '1' : '0' }}">
            @endif

            <!-- Buttons -->
            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('jalur.joint-numbers.show', $jointNumber) }}" 
                   class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Simpan Perubahan
                </button>
            </div>
            @else
            <!-- Read-only mode buttons -->
            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('jalur.joint-numbers.show', $jointNumber) }}" 
                   class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                    Kembali ke Detail
                </a>
            </div>
            @endunless
        </form>
    </div>
</div>

<script>
function updateJointPreview() {
    const clusterId = document.getElementById('cluster_id').value;
    const fittingTypeId = document.getElementById('fitting_type_id').value;
    const jointCode = document.getElementById('joint_code').value;
    
    // Get cluster and fitting type codes
    const clusterSelect = document.getElementById('cluster_id');
    const fittingTypeSelect = document.getElementById('fitting_type_id');
    
    let clusterCode = '';
    let fittingCode = '';
    
    if (clusterId && clusterSelect.selectedOptions.length > 0) {
        const clusterText = clusterSelect.selectedOptions[0].text;
        const match = clusterText.match(/\(([^)]+)\)$/);
        if (match) clusterCode = match[1];
    }
    
    if (fittingTypeId && fittingTypeSelect.selectedOptions.length > 0) {
        const fittingText = fittingTypeSelect.selectedOptions[0].text;
        const match = fittingText.match(/\(([^)]+)\)$/);
        if (match) fittingCode = match[1];
    }
    
    const preview = document.getElementById('joint-preview');
    if (clusterCode && fittingCode && jointCode) {
        const paddedCode = jointCode.padStart(3, '0');
        preview.textContent = `${clusterCode}-${fittingCode}${paddedCode}`;
        preview.classList.remove('text-gray-500');
        preview.classList.add('text-gray-700');
    } else {
        preview.textContent = 'Pilih cluster, fitting type, dan masukkan kode joint';
        preview.classList.remove('text-gray-700');
        preview.classList.add('text-gray-500');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateJointPreview();
    
    // Add event listeners
    document.getElementById('cluster_id').addEventListener('change', updateJointPreview);
    document.getElementById('fitting_type_id').addEventListener('change', updateJointPreview);
    document.getElementById('joint_code').addEventListener('input', updateJointPreview);
});
</script>
@endsection