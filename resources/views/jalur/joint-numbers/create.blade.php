@extends('layouts.app')

@section('title', 'Tambah Nomor Joint')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Tambah Nomor Joint</h1>
            <p class="text-gray-600 mt-1">Buat nomor joint baru untuk cluster dan fitting type tertentu</p>
        </div>
        <a href="{{ route('jalur.joint-numbers.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
            ← Kembali ke Daftar
        </a>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow">
        <form method="POST" action="{{ route('jalur.joint-numbers.store') }}" class="p-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Cluster -->
                <div>
                    <label for="cluster_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Cluster <span class="text-red-500">*</span>
                    </label>
                    <select name="cluster_id" 
                            id="cluster_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('cluster_id') border-red-500 @enderror"
                            required>
                        <option value="">Pilih Cluster</option>
                        @foreach($clusters as $cluster)
                            <option value="{{ $cluster->id }}" {{ old('cluster_id') == $cluster->id ? 'selected' : '' }}>
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
                            required>
                        <option value="">Pilih Fitting Type</option>
                        @foreach($fittingTypes as $fittingType)
                            <option value="{{ $fittingType->id }}" {{ old('fitting_type_id') == $fittingType->id ? 'selected' : '' }}>
                                {{ $fittingType->nama_fitting }} ({{ $fittingType->code_fitting }})
                            </option>
                        @endforeach
                    </select>
                    @error('fitting_type_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Generation Method -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Metode Pembuatan <span class="text-red-500">*</span></label>
                <div class="flex space-x-6">
                    <div class="flex items-center">
                        <input type="radio" 
                               id="single" 
                               name="generation_type" 
                               value="single" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                               onchange="toggleGenerationType()"
                               {{ old('generation_type', 'single') === 'single' ? 'checked' : '' }}>
                        <label for="single" class="ml-2 block text-sm text-gray-900">
                            Manual (Input satu nomor)
                        </label>
                    </div>
                    <div class="flex items-center">
                        <input type="radio" 
                               id="batch" 
                               name="generation_type" 
                               value="batch" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                               onchange="toggleGenerationType()"
                               {{ old('generation_type') === 'batch' ? 'checked' : '' }}>
                        <label for="batch" class="ml-2 block text-sm text-gray-900">
                            Otomatis (Generate beberapa nomor sekaligus)
                        </label>
                    </div>
                </div>
            </div>

            <!-- Single Input -->
            <div id="single-input" class="mt-6" style="{{ old('generation_type', 'single') === 'single' ? '' : 'display: none;' }}">
                <div>
                    <label for="nomor_joint" class="block text-sm font-medium text-gray-700 mb-2">
                        Nomor Joint <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="nomor_joint" 
                           id="nomor_joint" 
                           value="{{ old('nomor_joint') }}"
                           placeholder="Contoh: KRG-ET001"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nomor_joint') border-red-500 @enderror">
                    @error('nomor_joint')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">Format: ClusterCode-FittingCode + Nomor urut</p>
                </div>
            </div>

            <!-- Batch Input -->
            <div id="batch-input" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6" style="{{ old('generation_type') === 'batch' ? '' : 'display: none;' }}">
                <div>
                    <label for="start_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Nomor Mulai <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="start_number" 
                           id="start_number" 
                           value="{{ old('start_number', 1) }}"
                           min="1"
                           max="999"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('start_number') border-red-500 @enderror">
                    @error('start_number')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="end_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Nomor Akhir <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="end_number" 
                           id="end_number" 
                           value="{{ old('end_number', 10) }}"
                           min="1"
                           max="999"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('end_number') border-red-500 @enderror">
                    @error('end_number')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">Maksimal 50 nomor sekaligus</p>
                </div>
            </div>

            <!-- Status -->
            <div class="mt-6">
                <div class="flex items-center">
                    <input type="checkbox" 
                           name="is_active" 
                           id="is_active" 
                           value="1"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                           {{ old('is_active', true) ? 'checked' : '' }}>
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                        Aktif
                    </label>
                </div>
                <p class="text-xs text-gray-500 mt-1">Nomor joint yang aktif dapat digunakan untuk membuat data joint</p>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('jalur.joint-numbers.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleGenerationType() {
    const singleInput = document.getElementById('single-input');
    const batchInput = document.getElementById('batch-input');
    const generationType = document.querySelector('input[name="generation_type"]:checked').value;
    
    if (generationType === 'single') {
        singleInput.style.display = 'block';
        batchInput.style.display = 'none';
        
        // Make single input required
        document.getElementById('nomor_joint').setAttribute('required', 'required');
        document.getElementById('start_number').removeAttribute('required');
        document.getElementById('end_number').removeAttribute('required');
    } else {
        singleInput.style.display = 'none';
        batchInput.style.display = 'grid';
        
        // Make batch inputs required
        document.getElementById('nomor_joint').removeAttribute('required');
        document.getElementById('start_number').setAttribute('required', 'required');
        document.getElementById('end_number').setAttribute('required', 'required');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleGenerationType();
});
</script>
@endsection