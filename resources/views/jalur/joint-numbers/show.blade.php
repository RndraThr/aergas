@extends('layouts.app')

@section('title', 'Detail Nomor Joint')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Detail Nomor Joint</h1>
            <p class="text-gray-600 mt-1">Informasi lengkap nomor joint {{ $jointNumber->nomor_joint }}</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('jalur.joint-numbers.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                ← Kembali ke Daftar
            </a>
            <a href="{{ route('jalur.joint-numbers.edit', $jointNumber) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                ✏️ Edit
            </a>
        </div>
    </div>

    <!-- Main Info Card -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Nomor Joint -->
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Nomor Joint</h3>
                    <p class="text-xl font-bold text-gray-900">{{ $jointNumber->nomor_joint }}</p>
                </div>

                <!-- Joint Code -->
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Kode Joint</h3>
                    <p class="text-lg text-gray-700">{{ $jointNumber->joint_code }}</p>
                </div>

                <!-- Status -->
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Status</h3>
                    @if($jointNumber->is_active)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            ✅ Aktif
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            ❌ Nonaktif
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Cluster & Fitting Type Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Cluster Info -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Cluster</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Nama Cluster</p>
                        <p class="text-gray-900">{{ $jointNumber->cluster->nama_cluster }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Kode Cluster</p>
                        <p class="text-gray-900">{{ $jointNumber->cluster->code_cluster }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Status Cluster</p>
                        @if($jointNumber->cluster->is_active)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                                Nonaktif
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Fitting Type Info -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Fitting Type</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Nama Fitting</p>
                        <p class="text-gray-900">{{ $jointNumber->fittingType->nama_fitting }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Kode Fitting</p>
                        <p class="text-gray-900">{{ $jointNumber->fittingType->code_fitting }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Status Fitting Type</p>
                        @if($jointNumber->fittingType->is_active)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                                Nonaktif
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage Info -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Status Penggunaan</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-2">Status Penggunaan</p>
                    @if($jointNumber->usedByJoint)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            🔗 Digunakan
                        </span>
                        <p class="text-xs text-gray-500 mt-1">Nomor joint ini sudah digunakan dalam data joint</p>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                            ⭕ Tersedia
                        </span>
                        <p class="text-xs text-gray-500 mt-1">Nomor joint ini belum digunakan dan masih tersedia</p>
                    @endif
                </div>

                @if($jointNumber->usedByJoint)
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-2">Digunakan dalam Joint Data</p>
                    <a href="{{ route('jalur.joint.show', $jointNumber->usedByJoint) }}" 
                       class="text-blue-600 hover:text-blue-900 font-medium">
                        Lihat Data Joint →
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Metadata -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Sistem</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <p class="text-sm font-medium text-gray-500">Dibuat Tanggal</p>
                    <p class="text-gray-900">{{ $jointNumber->created_at->format('d M Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Diperbarui Terakhir</p>
                    <p class="text-gray-900">{{ $jointNumber->updated_at->format('d M Y H:i') }}</p>
                </div>
                @if($jointNumber->createdBy)
                <div>
                    <p class="text-sm font-medium text-gray-500">Dibuat Oleh</p>
                    <p class="text-gray-900">{{ $jointNumber->createdBy->name }}</p>
                </div>
                @endif
                @if($jointNumber->updatedBy)
                <div>
                    <p class="text-sm font-medium text-gray-500">Diperbarui Oleh</p>
                    <p class="text-gray-900">{{ $jointNumber->updatedBy->name }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection