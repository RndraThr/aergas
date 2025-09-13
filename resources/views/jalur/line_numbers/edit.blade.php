@extends('layouts.app')

@section('title', 'Edit Line Number')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Edit Line Number</h1>
            <p class="text-gray-600">{{ $lineNumber->line_number }} - {{ $lineNumber->nama_jalan }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="{{ route('jalur.line-numbers.update', $lineNumber) }}">
                @csrf
                @method('PUT')

                <div class="mb-6">
                    <label for="cluster_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Cluster <span class="text-red-500">*</span>
                    </label>
                    <select id="cluster_id" 
                            name="cluster_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('cluster_id') border-red-500 @enderror"
                            required>
                        <option value="">Pilih Cluster</option>
                        @foreach($clusters as $cluster)
                            <option value="{{ $cluster->id }}" 
                                    {{ old('cluster_id', $lineNumber->cluster_id) == $cluster->id ? 'selected' : '' }}>
                                {{ $cluster->nama_cluster }} ({{ $cluster->code_cluster }})
                            </option>
                        @endforeach
                    </select>
                    @error('cluster_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    @if(($lineNumber->loweringData && $lineNumber->loweringData->count() > 0) )
                        <p class="text-yellow-600 text-sm mt-1">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.98-.833-2.75 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            Peringatan: Mengubah cluster akan mempengaruhi line number dan data terkait
                        </p>
                    @endif
                </div>

                <div class="mb-6">
                    <label for="diameter" class="block text-sm font-medium text-gray-700 mb-2">
                        Diameter <span class="text-red-500">*</span>
                    </label>
                    <select id="diameter" 
                            name="diameter" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('diameter') border-red-500 @enderror"
                            required>
                        <option value="">Pilih Diameter</option>
                        <option value="63" {{ old('diameter', $lineNumber->diameter) == '63' ? 'selected' : '' }}>63mm</option>
                        <option value="90" {{ old('diameter', $lineNumber->diameter) == '90' ? 'selected' : '' }}>90mm</option>
                        <option value="110" {{ old('diameter', $lineNumber->diameter) == '110' ? 'selected' : '' }}>110mm</option>
                        <option value="160" {{ old('diameter', $lineNumber->diameter) == '160' ? 'selected' : '' }}>160mm</option>
                        <option value="180" {{ old('diameter', $lineNumber->diameter) == '180' ? 'selected' : '' }}>180mm</option>
                        <option value="200" {{ old('diameter', $lineNumber->diameter) == '200' ? 'selected' : '' }}>200mm</option>
                    </select>
                    @error('diameter')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    @if(($lineNumber->loweringData && $lineNumber->loweringData->count() > 0) )
                        <p class="text-yellow-600 text-sm mt-1">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.98-.833-2.75 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            Peringatan: Mengubah diameter akan mempengaruhi line number
                        </p>
                    @endif
                </div>

                <div class="mb-6">
                    <label for="nama_jalan" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Jalan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="nama_jalan" 
                           name="nama_jalan" 
                           value="{{ old('nama_jalan', $lineNumber->nama_jalan) }}"
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
                           value="{{ old('estimasi_panjang', $lineNumber->estimasi_panjang) }}"
                           step="0.1"
                           min="0"
                           placeholder="Contoh: 100.0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('estimasi_panjang') border-red-500 @enderror"
                           required>
                    @error('estimasi_panjang')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 mt-1">
                        Current total penggelaran: {{ number_format($lineNumber->total_penggelaran, 1) }}m
                        @if($lineNumber->actual_mc100)
                            | MC-100 actual: {{ number_format($lineNumber->actual_mc100, 1) }}m
                        @endif
                    </p>
                </div>

                <div class="mb-6">
                    <label for="actual_mc100" class="block text-sm font-medium text-gray-700 mb-2">
                        Actual MC-100 (meter)
                    </label>
                    <input type="number" 
                           id="actual_mc100" 
                           name="actual_mc100" 
                           value="{{ old('actual_mc100', $lineNumber->actual_mc100) }}"
                           step="0.1"
                           min="0"
                           placeholder="Contoh: 98.5"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('actual_mc100') border-red-500 @enderror">
                    @error('actual_mc100')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 mt-1">Panjang final hasil pengukuran MC-100 (opsional)</p>
                </div>

                <div class="mb-6">
                    <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-2">
                        Keterangan
                    </label>
                    <textarea id="keterangan" 
                              name="keterangan" 
                              rows="4"
                              placeholder="Keterangan atau deskripsi tambahan untuk line number ini..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('keterangan') border-red-500 @enderror">{{ old('keterangan', $lineNumber->keterangan) }}</textarea>
                    @error('keterangan')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="{{ route('jalur.line-numbers.show', $lineNumber) }}" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Update Line Number
                    </button>
                </div>
            </form>
        </div>

        <!-- Current Data Info -->
        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h3 class="text-sm font-medium text-gray-800 mb-2">Data Terkait</h3>
            <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                <div>
                    <span class="font-medium">Data Lowering:</span> {{ $lineNumber->loweringData ? $lineNumber->loweringData->count() : 0 }} entries
                </div>
                <div>
                    <span class="font-medium">Total Penggelaran:</span> {{ number_format($lineNumber->total_penggelaran, 1) }}m
                </div>
                <div>
                    <span class="font-medium">Current Line Number:</span> {{ $lineNumber->line_number }}
                </div>
            </div>
            @if(($lineNumber->loweringData && $lineNumber->loweringData->count() > 0) )
                <div class="mt-3 text-xs text-yellow-700 bg-yellow-50 p-2 rounded">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.98-.833-2.75 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    Hati-hati saat mengubah cluster atau diameter karena akan mempengaruhi nomor line number dan data lowering terkait.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection