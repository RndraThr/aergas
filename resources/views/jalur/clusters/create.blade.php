@extends('layouts.app')

@section('title', 'Tambah Cluster')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Tambah Cluster Baru</h1>
            <p class="text-gray-600">Buat cluster baru untuk perencanaan jalur pipa</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('jalur.clusters.store') }}">
                @csrf

                <div class="mb-6">
                    <label for="nama_cluster" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Cluster <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="nama_cluster" 
                           name="nama_cluster" 
                           value="{{ old('nama_cluster') }}"
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
                           value="{{ old('code_cluster') }}"
                           placeholder="Contoh: KRG (maksimal 10 karakter, UPPERCASE)"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('code_cluster') border-red-500 @enderror"
                           style="text-transform: uppercase"
                           maxlength="10"
                           required>
                    @error('code_cluster')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 mt-1">Code akan digunakan untuk membuat line number dan nomor joint</p>
                </div>

                <div class="mb-6">
                    <label for="deskripsi" class="block text-sm font-medium text-gray-700 mb-2">
                        Deskripsi
                    </label>
                    <textarea id="deskripsi" 
                              name="deskripsi" 
                              rows="4"
                              placeholder="Deskripsi atau keterangan tambahan untuk cluster ini..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('deskripsi') border-red-500 @enderror">{{ old('deskripsi') }}</textarea>
                    @error('deskripsi')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="is_active" 
                               name="is_active" 
                               value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">
                            Aktif
                        </label>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Cluster aktif dapat digunakan untuk membuat line number baru</p>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="{{ route('jalur.clusters.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Simpan Cluster
                    </button>
                </div>
            </form>
        </div>

        <!-- Preview Format -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="text-sm font-medium text-blue-800 mb-2">Preview Format</h3>
            <p class="text-sm text-blue-700 mb-2">Dengan cluster code "KRG", format yang akan dihasilkan:</p>
            <ul class="text-sm text-blue-600 space-y-1 ml-4">
                <li>• Line Number: <code class="bg-white px-2 py-1 rounded">63-KRG-LN001</code>, <code class="bg-white px-2 py-1 rounded">180-KRG-LN002</code></li>
                <li>• Nomor Joint: <code class="bg-white px-2 py-1 rounded">KRG-CP001</code>, <code class="bg-white px-2 py-1 rounded">KRG-EL002</code></li>
            </ul>
        </div>
    </div>
</div>

<script>
document.getElementById('code_cluster').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});
</script>
@endsection