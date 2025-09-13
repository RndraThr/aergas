@extends('layouts.app')

@section('title', 'Line Numbers')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Line Numbers</h1>
            <p class="text-gray-600">Kelola data line number jalur pipa per cluster</p>
        </div>
        <a href="{{ route('jalur.line-numbers.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah Line Number
        </a>
    </div>

    <!-- Filter & Search -->
    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Line number, nama jalan..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cluster</label>
                <select name="cluster_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Cluster</option>
                    @foreach($clusters as $cluster)
                        <option value="{{ $cluster->id }}" {{ request('cluster_id') == $cluster->id ? 'selected' : '' }}>
                            {{ $cluster->nama_cluster }} ({{ $cluster->code_cluster }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Diameter</label>
                <select name="diameter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Diameter</option>
                    <option value="63" {{ request('diameter') == '63' ? 'selected' : '' }}>63mm</option>
                    <option value="90" {{ request('diameter') == '90' ? 'selected' : '' }}>90mm</option>
                    <option value="110" {{ request('diameter') == '110' ? 'selected' : '' }}>110mm</option>
                    <option value="160" {{ request('diameter') == '160' ? 'selected' : '' }}>160mm</option>
                    <option value="180" {{ request('diameter') == '180' ? 'selected' : '' }}>180mm</option>
                    <option value="200" {{ request('diameter') == '200' ? 'selected' : '' }}>200mm</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                    Filter
                </button>
            </div>
            <div class="flex items-end">
                <a href="{{ route('jalur.line-numbers.index') }}" class="w-full bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Line Numbers Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Line Number & Lokasi
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cluster & Diameter
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estimasi (m)
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Penggelaran Total (m)
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            MC-100 Actual (m)
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Variance
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($lineNumbers as $lineNumber)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $lineNumber->line_number }}</div>
                                    <div class="text-sm text-gray-500">{{ $lineNumber->nama_jalan }}</div>
                                    @if($lineNumber->deskripsi)
                                        <div class="text-xs text-gray-400">{{ Str::limit($lineNumber->deskripsi, 50) }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm text-gray-900">{{ $lineNumber->cluster->nama_cluster }}</div>
                                    <div class="text-sm text-gray-500">{{ $lineNumber->cluster->code_cluster }}</div>
                                    <div class="text-xs text-gray-400">Ø{{ $lineNumber->diameter }}mm</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($lineNumber->estimasi_panjang, 1) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($lineNumber->total_penggelaran, 1) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $lineNumber->actual_mc100 ? number_format($lineNumber->actual_mc100, 1) : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($lineNumber->actual_mc100)
                                    @php
                                        $variance = $lineNumber->actual_mc100 - $lineNumber->estimasi_panjang;
                                        $variancePercent = $lineNumber->estimasi_panjang > 0 ? ($variance / $lineNumber->estimasi_panjang * 100) : 0;
                                    @endphp
                                    <div class="text-sm {{ $variance >= 0 ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $variance >= 0 ? '+' : '' }}{{ number_format($variance, 1) }}m
                                    </div>
                                    <div class="text-xs {{ $variancePercent >= 0 ? 'text-red-500' : 'text-green-500' }}">
                                        {{ $variancePercent >= 0 ? '+' : '' }}{{ number_format($variancePercent, 1) }}%
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <a href="{{ route('jalur.line-numbers.show', $lineNumber) }}" 
                                       class="text-blue-600 hover:text-blue-900">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <a href="{{ route('jalur.line-numbers.edit', $lineNumber) }}" 
                                       class="text-indigo-600 hover:text-indigo-900">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('jalur.line-numbers.destroy', $lineNumber) }}" class="inline"
                                          onsubmit="return confirm('Yakin ingin menghapus line number ini? Data lowering dan joint yang terkait juga akan terhapus.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <svg class="mx-auto w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 0a9 9 0 1118 0a9 9 0 01-18 0z"></path>
                                    </svg>
                                    <p class="text-lg font-medium mb-2">Belum ada data line number</p>
                                    <p>Mulai dengan tambah line number pertama</p>
                                    <a href="{{ route('jalur.line-numbers.create') }}" 
                                       class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Tambah Line Number
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($lineNumbers->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $lineNumbers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection