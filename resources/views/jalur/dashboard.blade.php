@extends('layouts.app')

@section('title', 'Jalur Dashboard')

@section('content')
    <div class="container mx-auto px-6 py-8 space-y-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Jalur Dashboard</h1>
            <p class="text-gray-600">Monitor aktivitas dan progress jalur pipa secara real-time</p>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Total Line Numbers --}}
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-route text-2xl"></i>
                    </div>
                    <span class="text-3xl font-bold">{{ $stats['total_lines'] }}</span>
                </div>
                <h3 class="text-sm font-medium opacity-90 mb-3">Total Line Numbers</h3>
                <div class="space-y-1 text-xs opacity-80">
                    <div class="flex justify-between">
                        <span>● 63mm:</span>
                        <span class="font-semibold">{{ $stats['diameter_stats']['63']['count'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>● 90mm:</span>
                        <span class="font-semibold">{{ $stats['diameter_stats']['90']['count'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>● 180mm:</span>
                        <span class="font-semibold">{{ $stats['diameter_stats']['180']['count'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Total Panjang Pipa --}}
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-ruler-combined text-2xl"></i>
                    </div>
                    <span class="text-3xl font-bold">{{ number_format($stats['total_actual_lowering'], 1) }}</span>
                </div>
                <h3 class="text-sm font-medium opacity-90 mb-3">Total Panjang (m)</h3>
                <div class="space-y-1 text-xs opacity-80">
                    <div class="flex justify-between">
                        <span>MC-0:</span>
                        <span class="font-semibold">{{ number_format($stats['total_mc0'], 1) }}m</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Actual Lowering:</span>
                        <span class="font-semibold">{{ number_format($stats['total_actual_lowering'], 1) }}m</span>
                    </div>
                    <div class="flex justify-between">
                        <span>MC-100:</span>
                        <span class="font-semibold">{{ number_format($stats['total_mc100'] ?? 0, 1) }}m</span>
                    </div>
                </div>
            </div>

            {{-- Progress Overall --}}
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-tasks text-2xl"></i>
                    </div>
                    <span class="text-3xl font-bold">{{ number_format($stats['overall_progress'], 1) }}%</span>
                </div>
                <h3 class="text-sm font-medium opacity-90 mb-3">Progress Overall</h3>

                {{-- Progress Bar --}}
                <div class="w-full bg-white/20 rounded-full h-2 mb-3">
                    <div class="bg-white h-2 rounded-full transition-all duration-300"
                        style="width: {{ min(100, $stats['overall_progress']) }}%"></div>
                </div>

                <div class="space-y-1 text-xs opacity-80">
                    <div class="flex justify-between">
                        <span>✓ Completed:</span>
                        <span class="font-semibold">{{ $stats['completed_lines'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>⟳ In Progress:</span>
                        <span class="font-semibold">{{ $stats['in_progress_lines'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>○ Not Started:</span>
                        <span class="font-semibold">{{ $stats['not_started_lines'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Fitting Terpakai --}}
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-cogs text-2xl"></i>
                    </div>
                    <span class="text-3xl font-bold">{{ $stats['fitting_stats']['total'] }}</span>
                </div>
                <h3 class="text-sm font-medium opacity-90 mb-3">Total Fitting Terpakai</h3>
                <div class="space-y-1 text-xs opacity-80">
                    @php
                        $topFittings = collect($stats['fitting_stats']['by_type'])->sortByDesc('total')->take(3);
                    @endphp
                    @foreach($topFittings as $fitting)
                        <div class="flex justify-between">
                            <span>● {{ Str::limit($fitting['name'], 12) }}:</span>
                            <span class="font-semibold">{{ $fitting['total'] }}</span>
                        </div>
                    @endforeach
                    @if(count($stats['fitting_stats']['by_type']) > 3)
                        <div class="text-center pt-1 opacity-60">
                            +{{ count($stats['fitting_stats']['by_type']) - 3 }} jenis lainnya
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Diameter Breakdown Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach(['63' => 'blue', '90' => 'orange', '180' => 'red'] as $diameter => $color)
                @php
                    $data = $stats['diameter_stats'][$diameter];
                    $progressPercent = $data['progress'];
                @endphp
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-{{ $color }}-100 rounded-lg flex items-center justify-center">
                                <i
                                    class="fas fa-{{ $diameter == '63' ? 'circle' : ($diameter == '90' ? 'dot-circle' : 'bullseye') }} text-{{ $color }}-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Diameter {{ $diameter }}mm</h3>
                                <p class="text-sm text-gray-500">{{ $data['count'] }} lines</p>
                            </div>
                        </div>
                    </div>

                    {{-- Stats Grid --}}
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500 mb-1">Total Panjang</p>
                            <p class="text-lg font-bold text-gray-900">{{ number_format($data['total_panjang'], 1) }}m</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500 mb-1">Progress</p>
                            <p class="text-lg font-bold text-{{ $color }}-600">{{ number_format($progressPercent, 1) }}%</p>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="w-full bg-gray-200 rounded-full h-2 mb-3">
                        <div class="bg-{{ $color }}-500 h-2 rounded-full transition-all duration-300"
                            style="width: {{ min(100, $progressPercent) }}%"></div>
                    </div>

                    {{-- Status Breakdown --}}
                    <div class="flex justify-between text-xs text-gray-600">
                        <span class="flex items-center">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                            Done: {{ $data['completed'] }}
                        </span>
                        <span class="flex items-center">
                            <span class="w-2 h-2 bg-{{ $color }}-500 rounded-full mr-1"></span>
                            Progress: {{ $data['in_progress'] }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Jalur Maps Section --}}
        @include('components.jalur-maps-view')

        <!-- Recent Activity Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Recent Lowering -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Lowering Terkini</h2>
                    <a href="{{ route('jalur.lowering.index') }}"
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        Lihat Semua
                    </a>
                </div>

                @if($recentLowering->count() > 0)
                    <div class="space-y-4">
                        @foreach($recentLowering as $lowering)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $lowering->lineNumber->line_number }}</p>
                                        <p class="text-sm text-gray-600">{{ $lowering->nama_jalan }} - {{ $lowering->penggelaran }}m
                                        </p>
                                        <p class="text-xs text-gray-500">{{ $lowering->tanggal_jalur->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex px-2 py-1 text-xs rounded-full
                                                @if($lowering->status_laporan === 'acc_cgp') bg-green-100 text-green-800
                                                @elseif($lowering->status_laporan === 'acc_tracer') bg-blue-100 text-blue-800
                                                @elseif(in_array($lowering->status_laporan, ['revisi_tracer', 'revisi_cgp'])) bg-yellow-100 text-yellow-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                        {{ $lowering->status_label }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        <p class="text-gray-500">Belum ada data lowering</p>
                    </div>
                @endif
            </div>

            <!-- Recent Joint -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Joint Terkini</h2>
                    <a href="{{ route('jalur.joint.index') }}"
                        class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                        Lihat Semua
                    </a>
                </div>

                @if($recentJoint->count() > 0)
                    <div class="space-y-4">
                        @foreach($recentJoint as $joint)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $joint->nomor_joint }}</p>
                                        <p class="text-sm text-gray-600">{{ $joint->fittingType->nama_fitting }} -
                                            {{ $joint->tipe_penyambungan }}</p>
                                        <p class="text-xs text-gray-500">{{ $joint->tanggal_joint->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
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
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        <p class="text-gray-500">Belum ada data joint</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Line Progress -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Progress Line Number</h2>
                <a href="{{ route('jalur.line-numbers.index') }}"
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    Lihat Semua
                </a>
            </div>

            @if($lineProgress->count() > 0)
                <div class="space-y-4">
                    @foreach($lineProgress as $line)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="font-medium text-gray-900">{{ $line->line_number }}</h3>
                                    <p class="text-sm text-gray-600">{{ $line->cluster->nama_cluster }} - Ø{{ $line->diameter }}mm
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">{{ $line->total_penggelaran }}m /
                                        {{ $line->estimasi_panjang }}m</p>
                                    <span class="inline-flex px-2 py-1 text-xs rounded-full
                                                @if($line->status_line === 'completed') bg-green-100 text-green-800
                                                @elseif($line->status_line === 'in_progress') bg-blue-100 text-blue-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                        {{ $line->status_label }}
                                    </span>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                                @php
                                    $progress = min(100, $line->getProgressPercentage());
                                    $progressClass = $progress >= 100 ? 'bg-green-500' :
                                        ($progress >= 75 ? 'bg-blue-500' :
                                            ($progress >= 50 ? 'bg-yellow-500' : 'bg-red-500'));
                                @endphp
                                <div class="h-2 rounded-full {{ $progressClass }}" style="width: {{ $progress }}%"></div>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">{{ number_format($progress, 1) }}% completed</span>
                                @if($line->actual_mc100)
                                    <span class="text-xs 
                                                    @php
                                                        $variance = $line->getVariancePercentage();
                                                        echo $variance > 5 ? 'text-red-600' :
                                                            ($variance < -5 ? 'text-green-600' : 'text-gray-600');
                                                    @endphp">
                                        MC-100: {{ $line->actual_mc100 }}m
                                        @if($variance != 0)
                                            ({{ $variance > 0 ? '+' : '' }}{{ number_format($variance, 1) }}%)
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                    <p class="text-gray-500">Belum ada line number dalam progress</p>
                </div>
            @endif
        </div>
    </div>
@endsection