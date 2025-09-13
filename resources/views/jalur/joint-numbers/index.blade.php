@extends('layouts.app')

@section('title', 'Manajemen Nomor Joint')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Manajemen Nomor Joint</h1>
            <p class="text-gray-600 mt-1">Kelola nomor joint untuk setiap cluster dan fitting type</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('jalur.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                ← Kembali ke Jalur
            </a>
            <a href="{{ route('jalur.joint-numbers.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                + Tambah Nomor Joint
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Cari Nomor Joint</label>
                    <input type="text" 
                           name="search" 
                           id="search" 
                           value="{{ request('search') }}"
                           placeholder="Cari nomor joint..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="cluster_id" class="block text-sm font-medium text-gray-700 mb-2">Cluster</label>
                    <select name="cluster_id" id="cluster_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Cluster</option>
                        @foreach($clusters as $cluster)
                            <option value="{{ $cluster->id }}" {{ request('cluster_id') == $cluster->id ? 'selected' : '' }}>
                                {{ $cluster->nama_cluster }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="fitting_type_id" class="block text-sm font-medium text-gray-700 mb-2">Fitting Type</label>
                    <select name="fitting_type_id" id="fitting_type_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Fitting Type</option>
                        @foreach($fittingTypes as $fittingType)
                            <option value="{{ $fittingType->id }}" {{ request('fitting_type_id') == $fittingType->id ? 'selected' : '' }}>
                                {{ $fittingType->nama_fitting }} ({{ $fittingType->code_fitting }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end space-x-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                        🔍 Filter
                    </button>
                    <a href="{{ route('jalur.joint-numbers.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Joint Numbers Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nomor Joint
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cluster
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Fitting Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Digunakan
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Dibuat
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($jointNumbers as $jointNumber)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900">{{ $jointNumber->nomor_joint }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $jointNumber->cluster->nama_cluster }}</div>
                                <div class="text-sm text-gray-500">{{ $jointNumber->cluster->code_cluster }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $jointNumber->fittingType->nama_fitting }}</div>
                                <div class="text-sm text-gray-500">{{ $jointNumber->fittingType->code_fitting }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($jointNumber->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($jointNumber->usedByJoint)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Digunakan
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Tersedia
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $jointNumber->created_at->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('jalur.joint-numbers.show', $jointNumber) }}" 
                                       class="text-blue-600 hover:text-blue-900">
                                        👁️ Lihat
                                    </a>
                                    <a href="{{ route('jalur.joint-numbers.edit', $jointNumber) }}" 
                                       class="text-indigo-600 hover:text-indigo-900">
                                        ✏️ Edit
                                    </a>
                                    @unless($jointNumber->usedByJoint)
                                    <form method="POST" action="{{ route('jalur.joint-numbers.destroy', $jointNumber) }}" 
                                          class="inline" 
                                          onsubmit="return confirm('Yakin hapus nomor joint ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            🗑️ Hapus
                                        </button>
                                    </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-4 4m0 0l-4-4m4 4V3"></path>
                                    </svg>
                                    <p class="text-gray-500 mb-2">Belum ada nomor joint</p>
                                    <a href="{{ route('jalur.joint-numbers.create') }}" class="text-blue-600 hover:text-blue-500">
                                        Tambah nomor joint pertama
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $jointNumbers->links() }}
    </div>
</div>
@endsection