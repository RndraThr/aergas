@extends('layouts.app')

@section('title', 'Tambah Tipe Fitting')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Tambah Tipe Fitting</h1>
            <p class="text-gray-600">Tambahkan jenis fitting baru dengan kode unik</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('jalur.fitting-types.store') }}">
                @csrf

                <div class="space-y-6">
                    <div>
                        <label for="nama_fitting" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Fitting <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="nama_fitting" 
                               name="nama_fitting" 
                               value="{{ old('nama_fitting') }}"
                               placeholder="Contoh: Coupler, Elbow 90, Equal Tee"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('nama_fitting') border-red-500 @enderror"
                               required>
                        @error('nama_fitting')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="code_fitting" class="block text-sm font-medium text-gray-700 mb-2">
                            Code Fitting <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="code_fitting" 
                               name="code_fitting" 
                               value="{{ old('code_fitting') }}"
                               placeholder="Contoh: CP, EL, ET (maksimal 10 karakter)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('code_fitting') border-red-500 @enderror"
                               maxlength="10"
                               style="text-transform: uppercase"
                               required>
                        @error('code_fitting')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">
                            Code akan digunakan untuk generate nomor joint. Contoh: KRG-CP001, KRG-EL002
                        </p>
                    </div>

                    <div>
                        <label for="deskripsi" class="block text-sm font-medium text-gray-700 mb-2">
                            Deskripsi
                        </label>
                        <textarea id="deskripsi" 
                                  name="deskripsi" 
                                  rows="4"
                                  placeholder="Deskripsi optional tentang jenis fitting ini..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('deskripsi') border-red-500 @enderror">{{ old('deskripsi') }}</textarea>
                        @error('deskripsi')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="is_active" 
                                   value="1" 
                                   {{ old('is_active', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Aktif (dapat digunakan untuk joint)</span>
                        </label>
                    </div>
                </div>

                <!-- Preview Section -->
                <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-gray-800 mb-3">Preview Nomor Joint</h3>
                    <p class="text-sm text-gray-600 mb-2">Contoh nomor joint yang akan dihasilkan:</p>
                    <div class="bg-white border border-purple-300 rounded px-3 py-2">
                        <code class="text-lg font-mono text-purple-900" id="joint-preview">
                            KRG-<span id="code-preview">XXX</span>001
                        </code>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Format: [Cluster]-[Code Fitting][Nomor Urut]
                    </p>
                </div>

                <div class="mt-8 flex justify-end space-x-4">
                    <a href="{{ route('jalur.fitting-types.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                        Simpan Tipe Fitting
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('code_fitting').addEventListener('input', function() {
    const codePreview = document.getElementById('code-preview');
    const code = this.value.toUpperCase() || 'XXX';
    codePreview.textContent = code;
    
    // Auto uppercase the input
    this.value = this.value.toUpperCase();
});

// Initialize preview
document.addEventListener('DOMContentLoaded', function() {
    const codeInput = document.getElementById('code_fitting');
    const codePreview = document.getElementById('code-preview');
    const code = codeInput.value.toUpperCase() || 'XXX';
    codePreview.textContent = code;
});
</script>
@endsection