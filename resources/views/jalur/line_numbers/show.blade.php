@extends('layouts.app')

@section('title', 'Detail Line Number')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ $lineNumber->line_number }}</h1>
                <p class="text-gray-600">{{ $lineNumber->nama_jalan }}</p>
            </div>
            <div class="flex space-x-4">
                <a href="{{ route('jalur.line-numbers.edit', $lineNumber) }}" 
                   class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </a>
                <a href="{{ route('jalur.line-numbers.index') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                    Kembali
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Line Number Info -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow p-6 mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Informasi Line Number</h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Line Number</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $lineNumber->line_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Cluster</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $lineNumber->cluster->nama_cluster }} ({{ $lineNumber->cluster->code_cluster }})</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Diameter</dt>
                            <dd class="mt-1 text-sm text-gray-900">Ø{{ $lineNumber->diameter }}mm</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nama Jalan</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $lineNumber->nama_jalan }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Estimasi Panjang</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ number_format($lineNumber->estimasi_panjang, 1) }}m</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Total Penggelaran</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ number_format($lineNumber->total_penggelaran, 1) }}m</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Actual MC-100</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $lineNumber->actual_mc100 ? number_format($lineNumber->actual_mc100, 1) . 'm' : 'Belum diinput' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Variance</dt>
                            <dd class="mt-1 text-sm">
                                @if($lineNumber->actual_mc100)
                                    @php
                                        $variance = $lineNumber->actual_mc100 - $lineNumber->estimasi_panjang;
                                        $variancePercent = $lineNumber->estimasi_panjang > 0 ? ($variance / $lineNumber->estimasi_panjang * 100) : 0;
                                    @endphp
                                    <span class="{{ $variance >= 0 ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $variance >= 0 ? '+' : '' }}{{ number_format($variance, 1) }}m 
                                        ({{ $variancePercent >= 0 ? '+' : '' }}{{ number_format($variancePercent, 1) }}%)
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                    
                    @if($lineNumber->keterangan)
                        <div class="mt-6">
                            <dt class="text-sm font-medium text-gray-500">Keterangan</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $lineNumber->keterangan }}</dd>
                        </div>
                    @endif
                </div>

                <!-- Lowering Data -->
                <div class="bg-white rounded-lg shadow p-6 mb-8">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Data Lowering</h2>
                        <a href="{{ route('jalur.lowering.create', ['line_number_id' => $lineNumber->id]) }}" 
                           class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-sm flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Tambah Lowering
                        </a>
                    </div>
                    
                    @if($lineNumber->loweringData && $lineNumber->loweringData->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Penggelaran</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($lineNumber->loweringData->sortByDesc('tanggal_jalur') as $lowering)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $lowering->tanggal_jalur->format('d/m/Y') }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $lowering->tipe_bongkaran }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ number_format($lowering->penggelaran, 1) }}m</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex px-2 py-1 text-xs rounded-full
                                                    @if($lowering->status_laporan === 'acc_cgp') bg-green-100 text-green-800
                                                    @elseif($lowering->status_laporan === 'acc_tracer') bg-blue-100 text-blue-800
                                                    @elseif(in_array($lowering->status_laporan, ['revisi_tracer', 'revisi_cgp'])) bg-yellow-100 text-yellow-800
                                                    @else bg-gray-100 text-gray-800
                                                    @endif">
                                                    {{ $lowering->status_label }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <a href="{{ route('jalur.lowering.show', $lowering) }}" 
                                                   class="text-blue-600 hover:text-blue-900">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
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
                            <svg class="mx-auto w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <p class="text-gray-500">Belum ada data lowering</p>
                        </div>
                    @endif
                </div>

                <!-- Joint Data -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Data Joint/Sambungan</h2>
                        <a href="{{ route('jalur.joint.create', ['line_number_id' => $lineNumber->id]) }}" 
                           class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded-md text-sm flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Tambah Joint
                        </a>
                    </div>
                    
                    @if($lineNumber->jointData && $lineNumber->jointData->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nomor Joint</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipe Fitting</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($lineNumber->jointData->sortByDesc('tanggal_joint') as $joint)
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-mono text-gray-900">{{ $joint->nomor_joint }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $joint->fittingType->nama_fitting }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $joint->tanggal_joint->format('d/m/Y') }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex px-2 py-1 text-xs rounded-full
                                                    @if($joint->status_laporan === 'acc_cgp') bg-green-100 text-green-800
                                                    @elseif($joint->status_laporan === 'acc_tracer') bg-blue-100 text-blue-800
                                                    @elseif(in_array($joint->status_laporan, ['revisi_tracer', 'revisi_cgp'])) bg-yellow-100 text-yellow-800
                                                    @else bg-gray-100 text-gray-800
                                                    @endif">
                                                    {{ $joint->status_label }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <a href="{{ route('jalur.joint.show', $joint) }}" 
                                                   class="text-blue-600 hover:text-blue-900">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
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
                            <svg class="mx-auto w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-gray-500">Belum ada data joint</p>
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
                                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-blue-900">Total Lowering</p>
                                    <p class="text-lg font-semibold text-blue-600">{{ $lineNumber->loweringData ? $lineNumber->loweringData->count() : 0 }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-purple-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-purple-900">Total Joint</p>
                                    <p class="text-lg font-semibold text-purple-600">{{ $lineNumber->jointData ? $lineNumber->jointData->count() : 0 }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-green-900">Progress</p>
                                    <p class="text-lg font-semibold text-green-600">
                                        @if($lineNumber->estimasi_panjang > 0)
                                            {{ number_format(($lineNumber->total_penggelaran / $lineNumber->estimasi_panjang) * 100, 1) }}%
                                        @else
                                            0%
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    @php
                        $progressPercent = $lineNumber->estimasi_panjang > 0 ? ($lineNumber->total_penggelaran / $lineNumber->estimasi_panjang) * 100 : 0;
                    @endphp
                    <div class="mt-6">
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span>Penggelaran</span>
                            <span>{{ number_format($lineNumber->total_penggelaran, 1) }}m / {{ number_format($lineNumber->estimasi_panjang, 1) }}m</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ min(100, $progressPercent) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection