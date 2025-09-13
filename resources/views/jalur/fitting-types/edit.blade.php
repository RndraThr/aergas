@extends('layouts.app')

@section('title', 'Edit Tipe Fitting')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Edit Tipe Fitting</h1>
            <p class="text-gray-600">{{ $fittingType->nama_fitting }} ({{ $fittingType->code_fitting }})</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('jalur.fitting-types.update', $fittingType) }}">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <div>
                        <label for="nama_fitting" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Fitting <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="nama_fitting" 
                               name="nama_fitting" 
                               value="{{ old('nama_fitting', $fittingType->nama_fitting) }}"
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
                               value="{{ old('code_fitting', $fittingType->code_fitting) }}"
                               placeholder="Contoh: CP, EL, ET (maksimal 10 karakter)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('code_fitting') border-red-500 @enderror"
                               maxlength="10"
                               style="text-transform: uppercase"
                               required>
                        @error('code_fitting')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        @if($fittingType->total_joints > 0)
                            <p class="text-xs text-yellow-600 mt-1">
                                ⚠️ Hati-hati mengubah code, sudah digunakan di {{ $fittingType->total_joints }} joint
                            </p>
                        @endif
                    </div>

                    <div>
                        <label for="deskripsi" class="block text-sm font-medium text-gray-700 mb-2">
                            Deskripsi
                        </label>
                        <textarea id="deskripsi" 
                                  name="deskripsi" 
                                  rows="4"
                                  placeholder="Deskripsi optional tentang jenis fitting ini..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 @error('deskripsi') border-red-500 @enderror">{{ old('deskripsi', $fittingType->deskripsi) }}</textarea>
                        @error('deskripsi')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="is_active" 
                                   value="1" 
                                   {{ old('is_active', $fittingType->is_active) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Aktif (dapat digunakan untuk joint)</span>
                        </label>
                        @if($fittingType->total_joints > 0 && old('is_active', $fittingType->is_active))
                            <p class="text-xs text-blue-600 mt-1">
                                ℹ️ Menonaktifkan akan menyembunyikan dari pilihan joint baru
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Preview Section -->
                <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-gray-800 mb-3">Preview Nomor Joint</h3>
                    <p class="text-sm text-gray-600 mb-2">Contoh nomor joint yang akan dihasilkan:</p>
                    <div class="bg-white border border-purple-300 rounded px-3 py-2">
                        <code class="text-lg font-mono text-purple-900" id="joint-preview">
                            KRG-<span id="code-preview">{{ $fittingType->code_fitting }}</span>001
                        </code>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Format: [Cluster]-[Code Fitting][Nomor Urut]
                    </p>
                </div>

                <!-- Usage Info -->
                @if($fittingType->total_joints > 0)
                    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-blue-800 mb-2">Informasi Penggunaan</h3>
                        <div class="text-sm text-blue-700">
                            <p>Tipe fitting ini telah digunakan di <strong>{{ $fittingType->total_joints }} joint</strong></p>
                            <p class="text-xs mt-1">Perubahan code akan mempengaruhi sistem penamaan joint yang sudah ada</p>
                        </div>
                    </div>
                @endif

                <div class="mt-8 flex justify-end space-x-4">
                    <a href="{{ route('jalur.fitting-types.show', $fittingType) }}" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                        Update Tipe Fitting
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
</script>
@endsection