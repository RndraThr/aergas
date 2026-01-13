@extends('layouts.app')

@section('title', 'Edit Cluster')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Edit Cluster</h1>
            <p class="text-gray-600">{{ $cluster->nama_cluster }} ({{ $cluster->code_cluster }})</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('jalur.clusters.update', $cluster) }}">
                @csrf
                @method('PUT')

                <div class="mb-6">
                    <label for="nama_cluster" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Cluster <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="nama_cluster" 
                           name="nama_cluster" 
                           value="{{ old('nama_cluster', $cluster->nama_cluster) }}"
                           placeholder="Contoh: Karanggayam"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('nama_cluster') border-red-500 @enderror"
                           required>
                    @error('nama_cluster')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="code_cluster" class="block text-sm font-medium text-gray-700 mb-2">
                        Code Cluster <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="code_cluster" 
                           name="code_cluster" 
                           value="{{ old('code_cluster', $cluster->code_cluster) }}"
                           placeholder="Contoh: KRG (maksimal 10 karakter, UPPERCASE)"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('code_cluster') border-red-500 @enderror"
                           style="text-transform: uppercase"
                           maxlength="10"
                           required>
                    @error('code_cluster')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    @if($cluster->lineNumbers->count() > 0)
                        <p class="text-yellow-600 text-sm mt-1">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.98-.833-2.75 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            Peringatan: Mengubah code akan mempengaruhi {{ $cluster->lineNumbers->count() }} line numbers yang ada
                        </p>
                    @endif
                </div>

                <div class="mb-6">
                    <label for="deskripsi" class="block text-sm font-medium text-gray-700 mb-2">
                        Deskripsi
                    </label>
                    <textarea id="deskripsi" 
                              name="deskripsi" 
                              rows="4"
                              placeholder="Deskripsi atau keterangan tambahan untuk cluster ini..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('deskripsi') border-red-500 @enderror">{{ old('deskripsi', $cluster->deskripsi) }}</textarea>
                    @error('deskripsi')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Google Sheets Display Settings -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Pengaturan Tampilan Google Sheets</h3>
                    
                    <div class="mb-4">
                        <label for="rs_sektor" class="block text-sm font-medium text-gray-700 mb-2">
                            RS Sektor / Lokasi
                        </label>
                        <input type="text" 
                               id="rs_sektor" 
                               name="rs_sektor" 
                               value="{{ old('rs_sektor', $cluster->rs_sektor) }}"
                               placeholder="Contoh: RSUP SARDJITO"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('rs_sektor') border-red-500 @enderror">
                        @error('rs_sektor')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-sm text-gray-500 mt-1">Nama rumah sakit / lokasi sektor (untuk diameter 90)</p>
                    </div>

                    <div class="mb-4">
                        <label for="spk_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama SPK
                        </label>
                        <input type="text" 
                               id="spk_name" 
                               name="spk_name" 
                               value="{{ old('spk_name', $cluster->spk_name) }}"
                               placeholder="Contoh: City Gas 5 Tahap 2"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('spk_name') border-red-500 @enderror">
                        @error('spk_name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-sm text-gray-500 mt-1">Nama project/SPK yang ditampilkan di kolom B (Default: City Gas 5 Tahap 2)</p>
                    </div>

                    <div class="mb-4">
                        <label for="test_package_code" class="block text-sm font-medium text-gray-700 mb-2">
                            Kode Test Package
                        </label>
                        <input type="text" 
                               id="test_package_code" 
                               name="test_package_code" 
                               value="{{ old('test_package_code', $cluster->test_package_code) }}"
                               placeholder="Contoh: TP-PK-KI (kosongkan untuk auto-generate)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('test_package_code') border-red-500 @enderror">
                        @error('test_package_code')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-sm text-gray-500 mt-1">Jika kosong, akan auto-generate: TP-{CODE}</p>
                    </div>

                    <div class="mb-0">
                        <label for="sheet_cluster_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Cluster di Sheet
                        </label>
                        <input type="text" 
                               id="sheet_cluster_name" 
                               name="sheet_cluster_name" 
                               value="{{ old('sheet_cluster_name', $cluster->sheet_cluster_name) }}"
                               placeholder="Contoh: PK-KI (kosongkan untuk gunakan Code Cluster)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('sheet_cluster_name') border-red-500 @enderror">
                        @error('sheet_cluster_name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-sm text-gray-500 mt-1">Nama cluster yang ditampilkan di Google Sheets (default: Code Cluster)</p>
                    </div>
                </div>

                <div class="mb-6">
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="is_active" 
                               name="is_active" 
                               value="1"
                               {{ old('is_active', $cluster->is_active) ? 'checked' : '' }}
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">
                            Aktif
                        </label>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Cluster aktif dapat digunakan untuk membuat line number baru</p>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="{{ route('jalur.clusters.show', $cluster) }}" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Update Cluster
                    </button>
                </div>
            </form>
        </div>

        <!-- Current Data Info -->
        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h3 class="text-sm font-medium text-gray-800 mb-2">Data Terkait</h3>
            <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                <div>
                    <span class="font-medium">Line Numbers:</span> {{ $cluster->lineNumbers->count() }} line numbers
                </div>
                <div>
                    <span class="font-medium">Status:</span> {{ $cluster->is_active ? 'Aktif' : 'Tidak Aktif' }}
                </div>
                <div>
                    <span class="font-medium">Dibuat:</span> {{ $cluster->created_at->format('d/m/Y') }}
                </div>
                <div>
                    <span class="font-medium">Terakhir Update:</span> {{ $cluster->updated_at->format('d/m/Y H:i') }}
                </div>
            </div>
            @if($cluster->lineNumbers->count() > 0)
                <div class="mt-3 text-xs text-yellow-700 bg-yellow-50 p-2 rounded">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.98-.833-2.75 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    Hati-hati saat mengubah code cluster karena akan mempengaruhi semua line numbers dan data terkait.
                </div>
            @endif
        </div>

        <!-- Line Numbers Preview -->
        @if($cluster->lineNumbers->count() > 0)
            <div class="mt-6 bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-800 mb-4">Line Numbers di Cluster Ini</h3>
                <div class="space-y-2">
                    @foreach($cluster->lineNumbers->take(5) as $lineNumber)
                        <div class="flex justify-between items-center py-2 px-3 bg-gray-50 rounded">
                            <span class="font-mono text-sm">{{ $lineNumber->line_number }}</span>
                            <span class="text-sm text-gray-600">{{ $lineNumber->nama_jalan }}</span>
                            <a href="{{ route('jalur.line-numbers.show', $lineNumber) }}" 
                               class="text-blue-600 hover:underline text-sm">
                                Detail
                            </a>
                        </div>
                    @endforeach
                    @if($cluster->lineNumbers->count() > 5)
                        <div class="text-center py-2">
                            <a href="{{ route('jalur.clusters.show', $cluster) }}" 
                               class="text-blue-600 hover:underline text-sm">
                                ... dan {{ $cluster->lineNumbers->count() - 5 }} line numbers lainnya
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<script>
document.getElementById('code_cluster').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});
</script>
@endsection