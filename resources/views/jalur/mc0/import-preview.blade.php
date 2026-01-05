@extends('layouts.app')

@section('title', 'Preview Import MC-0')

@section('content')
    <div class="container mx-auto px-6 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Preview Import MC-0</h1>
                <p class="text-gray-600">Validasi data sebelum import final</p>
            </div>
            <a href="{{ route('jalur.lowering.index') }}"
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                Kembali ke Data Lowering
            </a>
        </div>

        {{-- Summary Card --}}
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Hasil Validasi</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="text-sm text-green-700">Data Valid</p>
                            <p class="text-3xl font-bold text-green-800">{{ $results['success'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                            </path>
                        </svg>
                        <div>
                            <p class="text-sm text-yellow-700">Baris Kosong</p>
                            <p class="text-3xl font-bold text-yellow-800">{{ $results['skipped'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="text-sm text-red-700">Error Validasi</p>
                            <p class="text-3xl font-bold text-red-800">{{ count($results['failed']) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Error Details --}}
        @if(!empty($results['failed']))
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-bold text-red-700 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                        </path>
                    </svg>
                    Detail Error ({{ count($results['failed']) }} baris)
                </h3>

                <div class="max-h-96 overflow-y-auto border border-red-200 rounded">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-red-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Baris
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Error
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Data
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($results['failed'] as $failure)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $failure['row'] }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <ul class="list-disc list-inside text-red-600">
                                            @foreach($failure['errors'] as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <code class="text-xs bg-gray-100 px-2 py-1 rounded">
                                                    {{ json_encode($failure['data'], JSON_UNESCAPED_UNICODE) }}
                                                </code>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 mb-6">
                <p class="text-yellow-800 font-semibold">
                    ⚠️ Perbaiki error di atas sebelum melanjutkan import!
                </p>
                <p class="text-yellow-700 text-sm mt-2">
                    Silakan kembali, perbaiki file Excel Anda, lalu upload ulang.
                </p>
            </div>
        @else
            <div class="bg-green-100 border-l-4 border-green-500 p-6 mb-6">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="text-green-800 font-bold text-lg">✓ Semua Data Valid!</p>
                        <p class="text-green-700 text-sm mt-1">
                            {{ $results['success'] }} baris siap untuk diimport. Klik tombol "Lanjutkan Import" untuk
                            melanjutkan.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-4">
                <form action="{{ route('jalur.mc0.import.execute') }}" method="POST" enctype="multipart/form-data"
                    class="flex-1">
                    @csrf
                    {{-- Re-upload the same file for actual import --}}
                    <input type="hidden" name="temp_file_path" value="{{ $tempFilePath }}">

                    <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white px-6 py-4 rounded-md flex items-center justify-center text-lg font-semibold">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Lanjutkan Import ({{ $results['success'] }} Data MC-0)
                    </button>
                </form>

                <a href="{{ route('jalur.mc0.import.index') }}"
                    class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-6 py-4 rounded-md flex items-center justify-center text-lg font-semibold">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Batalkan
                </a>
            </div>
        @endif

        {{-- File Info --}}
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mt-6">
            <p class="text-sm text-gray-600">
                <span class="font-semibold">File:</span> {{ $fileName }}
            </p>
        </div>
    </div>
@endsection