@extends('layouts.app')

@section('title', 'Tambah Line Number')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Tambah Line Number Baru</h1>
            <p class="text-gray-600">Buat line number baru untuk jalur pipa</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('jalur.line-numbers.store') }}" id="lineNumberForm">
                @csrf

                <div class="mb-6">
                    <label for="cluster_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Cluster <span class="text-red-500">*</span>
                    </label>
                    <select id="cluster_id" 
                            name="cluster_id" 
                            onchange="updateLineNumberPreview()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('cluster_id') border-red-500 @enderror"
                            required>
                        <option value="">Pilih Cluster</option>
                        @foreach($clusters as $cluster)
                            <option value="{{ $cluster->id }}" 
                                    data-code="{{ $cluster->code_cluster }}"
                                    {{ old('cluster_id') == $cluster->id ? 'selected' : '' }}>
                                {{ $cluster->nama_cluster }} ({{ $cluster->code_cluster }})
                            </option>
                        @endforeach
                    </select>
                    @error('cluster_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="diameter" class="block text-sm font-medium text-gray-700 mb-2">
                        Diameter <span class="text-red-500">*</span>
                    </label>
                    <select id="diameter" 
                            name="diameter" 
                            onchange="updateLineNumberPreview()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('diameter') border-red-500 @enderror"
                            required>
                        <option value="">Pilih Diameter</option>
                        <option value="63" {{ old('diameter') == '63' ? 'selected' : '' }}>63mm</option>
                        <option value="90" {{ old('diameter') == '90' ? 'selected' : '' }}>90mm</option>
                        <option value="110" {{ old('diameter') == '110' ? 'selected' : '' }}>110mm</option>
                        <option value="160" {{ old('diameter') == '160' ? 'selected' : '' }}>160mm</option>
                        <option value="180" {{ old('diameter') == '180' ? 'selected' : '' }}>180mm</option>
                        <option value="200" {{ old('diameter') == '200' ? 'selected' : '' }}>200mm</option>
                    </select>
                    @error('diameter')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="nomor_line" class="block text-sm font-medium text-gray-700 mb-2">
                        Nomor Line <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="nomor_line" 
                           name="nomor_line" 
                           value="{{ old('nomor_line') }}"
                           placeholder="Contoh: 001 (akan menjadi 63-KRG-LN001)"
                           maxlength="3"
                           pattern="[0-9]{3}"
                           onchange="updateLineNumberPreview()"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nomor_line') border-red-500 @enderror"
                           required>
                    @error('nomor_line')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 mt-1">Masukkan nomor line 3 digit (001-999) sesuai laporan dari lapangan</p>
                </div>

                <div class="mb-6">
                    <label for="nama_jalan" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Jalan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="nama_jalan" 
                           name="nama_jalan" 
                           value="{{ old('nama_jalan') }}"
                           placeholder="Contoh: Jl. Raya Karanggayam"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nama_jalan') border-red-500 @enderror"
                           required>
                    @error('nama_jalan')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="estimasi_panjang" class="block text-sm font-medium text-gray-700 mb-2">
                        Estimasi Panjang (meter) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="estimasi_panjang" 
                           name="estimasi_panjang" 
                           value="{{ old('estimasi_panjang') }}"
                           step="0.1"
                           min="0"
                           placeholder="Contoh: 100.0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('estimasi_panjang') border-red-500 @enderror"
                           required>
                    @error('estimasi_panjang')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 mt-1">Estimasi panjang jalur pipa yang akan dipasang (dalam meter)</p>
                </div>

                <div class="mb-6">
                    <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-2">
                        Keterangan
                    </label>
                    <textarea id="keterangan" 
                              name="keterangan" 
                              rows="4"
                              placeholder="Keterangan atau deskripsi tambahan untuk line number ini..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('keterangan') border-red-500 @enderror">{{ old('keterangan') }}</textarea>
                    @error('keterangan')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="{{ route('jalur.line-numbers.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Simpan Line Number
                    </button>
                </div>
            </form>
        </div>

        <!-- Line Number Preview -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4" id="preview-container" style="display: none;">
            <h3 class="text-sm font-medium text-blue-800 mb-2">Preview Line Number</h3>
            <p class="text-sm text-blue-700 mb-2">Line number yang akan dibuat:</p>
            <div class="bg-white border border-blue-300 rounded px-3 py-2">
                <code class="text-lg font-mono text-blue-900" id="line-number-preview">-</code>
            </div>
        </div>
    </div>
</div>

<script>
function updateLineNumberPreview() {
    const clusterSelect = document.getElementById('cluster_id');
    const diameterSelect = document.getElementById('diameter');
    const nomorLineInput = document.getElementById('nomor_line');
    const previewContainer = document.getElementById('preview-container');
    const previewCode = document.getElementById('line-number-preview');
    
    const clusterId = clusterSelect.value;
    const diameter = diameterSelect.value;
    const nomorLine = nomorLineInput.value;
    
    if (clusterId && diameter && nomorLine) {
        const selectedOption = clusterSelect.options[clusterSelect.selectedIndex];
        const clusterCode = selectedOption.getAttribute('data-code');
        
        // Format nomor line to 3 digits
        const formattedNumber = nomorLine.padStart(3, '0');
        const lineNumber = `${diameter}-${clusterCode}-LN${formattedNumber}`;
        
        previewCode.textContent = lineNumber;
        previewContainer.style.display = 'block';
    } else {
        previewContainer.style.display = 'none';
    }
}

// Auto format nomor line input to 3 digits
document.getElementById('nomor_line').addEventListener('input', function(e) {
    // Only allow numbers
    this.value = this.value.replace(/[^0-9]/g, '');
    updateLineNumberPreview();
});

// Initialize preview on page load if form has old values
document.addEventListener('DOMContentLoaded', function() {
    updateLineNumberPreview();
});
</script>
@endsection