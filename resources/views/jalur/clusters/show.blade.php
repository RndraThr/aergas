@extends('layouts.app')

@section('title', 'Detail Cluster')

@section('content')
    <div class="container mx-auto px-6 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ $cluster->nama_cluster }}</h1>
                    <p class="text-gray-600">{{ $cluster->code_cluster }} - {{ $cluster->lineNumbers->count() }} Line
                        Numbers</p>
                </div>
                <div class="flex space-x-4">
                    <a href="{{ route('jalur.clusters.edit', $cluster) }}"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        Edit
                    </a>
                    <a href="{{ route('jalur.clusters.index') }}"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                        Kembali
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cluster Information -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow p-6 mb-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Informasi Cluster</h2>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Nama Cluster</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $cluster->nama_cluster }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Code Cluster</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $cluster->code_cluster }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs rounded-full 
                                        {{ $cluster->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $cluster->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Dibuat Oleh</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $cluster->createdBy->name ?? 'System' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Tanggal Dibuat</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $cluster->created_at->format('d F Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Terakhir Update</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $cluster->updated_at->format('d F Y H:i') }}</dd>
                            </div>
                        </dl>

                        @if($cluster->deskripsi)
                            <div class="mt-6">
                                <dt class="text-sm font-medium text-gray-500">Deskripsi</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $cluster->deskripsi }}</dd>
                            </div>
                        @endif
                    </div>

                    <!-- Line Numbers -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Line Numbers</h2>
                            <a href="{{ route('jalur.line-numbers.create', ['cluster_id' => $cluster->id]) }}"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Tambah Line Number
                            </a>
                        </div>

                        @if($cluster->lineNumbers->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Line
                                                Number</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Diameter
                                            </th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">MC-0
                                            </th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actual
                                                Lowering</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">MC-100
                                            </th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($cluster->lineNumbers->sortBy('line_number') as $lineNumber)
                                            <tr>
                                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                                    <a href="{{ route('jalur.line-numbers.show', $lineNumber) }}"
                                                        class="text-blue-600 hover:underline">
                                                        {{ $lineNumber->line_number }}
                                                    </a>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-900">Ø{{ $lineNumber->diameter }}mm</td>
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    {{ number_format($lineNumber->estimasi_panjang, 1) }}m</td>
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    {{ $lineNumber->total_penggelaran ? number_format($lineNumber->total_penggelaran, 1) . 'm' : '-' }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    {{ $lineNumber->actual_mc100 ? number_format($lineNumber->actual_mc100, 1) . 'm' : '-' }}
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <a href="{{ route('jalur.line-numbers.show', $lineNumber) }}"
                                                        class="text-blue-600 hover:text-blue-900">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                            </path>
                                                        </svg>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 0a9 9 0 1118 0a9 9 0 01-18 0z"></path>
                                </svg>
                                <p class="text-gray-500 mb-4">Belum ada line number di cluster ini</p>
                                <a href="{{ route('jalur.line-numbers.create', ['cluster_id' => $cluster->id]) }}"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Tambah Line Number Pertama
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Statistics -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Statistik</h2>

                        <div class="space-y-4">
                            <div class="bg-blue-50 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 0a9 9 0 1118 0a9 9 0 01-18 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-blue-900">Total Line Numbers</p>
                                        <p class="text-lg font-semibold text-blue-600">{{ $cluster->lineNumbers->count() }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-green-50 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-green-900">Total Lowering</p>
                                        <p class="text-lg font-semibold text-green-600">{{ $stats['total_lowering'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-purple-50 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-purple-900">Total Joint</p>
                                        <p class="text-lg font-semibold text-purple-600">{{ $stats['total_joint'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Summary -->
                        @if($cluster->lineNumbers->count() > 0)
                            <div class="mt-6">
                                <h3 class="text-sm font-medium text-gray-700 mb-3">Progress Summary</h3>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Total Estimasi</span>
                                        <span class="font-medium">{{ number_format($stats['total_estimate'], 1) }}m</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Total Penggelaran</span>
                                        <span class="font-medium">{{ number_format($stats['total_penggelaran'], 1) }}m</span>
                                    </div>
                                    @if($stats['total_actual'] > 0)
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Total MC-100</span>
                                            <span class="font-medium">{{ number_format($stats['total_actual'], 1) }}m</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Variance</span>
                                            <span
                                                class="font-medium {{ $stats['total_variance'] >= 0 ? 'text-red-600' : 'text-green-600' }}">
                                                {{ $stats['total_variance'] >= 0 ? '+' : '' }}{{ number_format($stats['total_variance'], 1) }}m
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="bg-white rounded-lg shadow p-6 mt-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Aksi</h2>

                        <div class="space-y-3">
                            <a href="{{ route('jalur.line-numbers.create', ['cluster_id' => $cluster->id]) }}"
                                class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 flex items-center justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Tambah Line Number
                            </a>

                            <a href="{{ route('jalur.clusters.edit', $cluster) }}"
                                class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 flex items-center justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                                Edit Cluster
                            </a>

                            @if(!$cluster->is_active)
                                <form method="POST" action="{{ route('jalur.clusters.toggle', $cluster) }}" class="w-full">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                        Aktifkan Cluster
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('jalur.clusters.toggle', $cluster) }}" class="w-full">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="w-full bg-yellow-600 text-white px-4 py-2 rounded-md hover:bg-yellow-700">
                                        Nonaktifkan Cluster
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection