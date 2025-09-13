@extends('layouts.app')

@section('title', 'Detail Tipe Fitting')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ $fittingType->nama_fitting }}</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-mono text-purple-600 bg-purple-50 px-2 py-1 rounded">
                        {{ $fittingType->code_fitting }}
                    </span>
                    <span class="inline-flex px-2 py-1 text-xs rounded-full
                        {{ $fittingType->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $fittingType->is_active ? 'Aktif' : 'Tidak Aktif' }}
                    </span>
                </div>
            </div>
            <div class="flex space-x-4">
                <a href="{{ route('jalur.fitting-types.edit', $fittingType) }}" 
                   class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </a>
                <a href="{{ route('jalur.fitting-types.index') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                    Kembali
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Fitting Information -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow p-6 mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Informasi Tipe Fitting</h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nama Fitting</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $fittingType->nama_fitting }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Code Fitting</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono bg-purple-50 px-2 py-1 rounded inline-block">
                                {{ $fittingType->code_fitting }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs rounded-full
                                    {{ $fittingType->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $fittingType->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Total Usage</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <span class="font-semibold">{{ $stats['total_joints'] }}</span> joint
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Deskripsi</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $fittingType->deskripsi ?: '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Usage Statistics -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Statistik Penggunaan</h2>
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ $stats['total_joints'] }}</div>
                            <div class="text-sm text-blue-800">Total Joint</div>
                        </div>
                        <div class="text-center p-4 bg-yellow-50 rounded-lg">
                            <div class="text-2xl font-bold text-yellow-600">{{ $stats['active_joints'] }}</div>
                            <div class="text-sm text-yellow-800">Dalam Proses</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ $stats['completed_joints'] }}</div>
                            <div class="text-sm text-green-800">Selesai</div>
                        </div>
                    </div>

                    <!-- Recent Joints -->
                    @if($stats['recent_joints']->count() > 0)
                        <h3 class="text-md font-medium text-gray-900 mb-3">Joint Terbaru</h3>
                        <div class="space-y-3">
                            @foreach($stats['recent_joints'] as $joint)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $joint->nomor_joint }}</div>
                                        <div class="text-sm text-gray-500">
                                            {{ $joint->joint_line_from }} → {{ $joint->joint_line_to }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-900">{{ $joint->tanggal_joint->format('d/m/Y') }}</div>
                                        <span class="inline-flex px-2 py-1 text-xs rounded-full
                                            @if($joint->status_laporan === 'acc_cgp') bg-green-100 text-green-800
                                            @elseif($joint->status_laporan === 'acc_tracer') bg-blue-100 text-blue-800
                                            @elseif(in_array($joint->status_laporan, ['revisi_tracer', 'revisi_cgp'])) bg-yellow-100 text-yellow-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $joint->status_label }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($stats['total_joints'] > 5)
                            <div class="mt-4 text-center">
                                <a href="{{ route('jalur.joint.index', ['fitting_type_id' => $fittingType->id]) }}" 
                                   class="text-purple-600 hover:text-purple-800 text-sm">
                                    Lihat semua joint dengan tipe {{ $fittingType->nama_fitting }} →
                                </a>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Side Panel -->
            <div class="space-y-6">
                <!-- Code Preview -->
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-purple-800 mb-2">Preview Nomor Joint</h3>
                    <p class="text-sm text-purple-700 mb-2">Contoh nomor joint:</p>
                    <div class="bg-white border border-purple-300 rounded px-3 py-2 mb-2">
                        <code class="text-lg font-mono text-purple-900">KRG-{{ $fittingType->code_fitting }}001</code>
                    </div>
                    <div class="bg-white border border-purple-300 rounded px-3 py-2">
                        <code class="text-lg font-mono text-purple-900">KRG-{{ $fittingType->code_fitting }}002</code>
                    </div>
                    <p class="text-xs text-purple-600 mt-2">
                        Format: [Cluster]-[{{ $fittingType->code_fitting }}][Nomor Urut]
                    </p>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="text-sm font-medium text-gray-800 mb-3">Aksi</h3>
                    <div class="space-y-2">
                        <form method="POST" action="{{ route('jalur.fitting-types.toggle-status', $fittingType) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" 
                                    class="w-full flex items-center justify-center px-3 py-2 text-sm rounded-md
                                        {{ $fittingType->is_active ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200' : 'bg-green-100 text-green-800 hover:bg-green-200' }}">
                                @if($fittingType->is_active)
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18 12M6 6l12 12"></path>
                                    </svg>
                                    Nonaktifkan
                                @else
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Aktifkan
                                @endif
                            </button>
                        </form>

                        @if($stats['total_joints'] == 0)
                            <form method="POST" action="{{ route('jalur.fitting-types.destroy', $fittingType) }}"
                                  onsubmit="return confirm('Yakin ingin menghapus tipe fitting {{ $fittingType->nama_fitting }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="w-full flex items-center justify-center px-3 py-2 text-sm bg-red-100 text-red-800 rounded-md hover:bg-red-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Hapus
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Audit Info -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-gray-800 mb-3">Informasi Audit</h3>
                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-gray-500">Dibuat</dt>
                            <dd class="text-gray-900">{{ $fittingType->created_at->format('d/m/Y H:i') }}</dd>
                            @if($fittingType->createdBy)
                                <dd class="text-xs text-gray-500">oleh {{ $fittingType->createdBy->name }}</dd>
                            @endif
                        </div>
                        <div>
                            <dt class="text-gray-500">Terakhir Update</dt>
                            <dd class="text-gray-900">{{ $fittingType->updated_at->format('d/m/Y H:i') }}</dd>
                            @if($fittingType->updatedBy)
                                <dd class="text-xs text-gray-500">oleh {{ $fittingType->updatedBy->name }}</dd>
                            @endif
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection