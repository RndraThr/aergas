@extends('layouts.app')

@section('title', 'CGP - Photo Review')

@push('styles')
<style>
    .photo-preview {
        max-height: 300px;
        object-fit: cover;
        cursor: zoom-in;
    }
    .photo-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        z-index: 1000;
        cursor: zoom-out;
    }
    .photo-modal img {
        max-width: 90%;
        max-height: 90%;
        margin: auto;
        display: block;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">CGP Photo Review - {{ $customer->reff_id_pelanggan }}</h1>
            <p class="text-gray-600 mt-1">{{ $customer->nama_pelanggan }} - {{ $customer->alamat }}</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('approvals.cgp.customers') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                ← Kembali ke List
            </a>
        </div>
    </div>

    <!-- Slot Completion Status -->
    @if(isset($completionStatus) && !empty($completionStatus))
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">📋 Slot Completion Status</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach(['sk' => 'SK', 'sr' => 'SR', 'gas_in' => 'Gas In'] as $moduleKey => $moduleName)
                    @if(isset($completionStatus[$moduleKey]))
                        @php $status = $completionStatus[$moduleKey]; @endphp
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-semibold text-gray-900">{{ $moduleName }}</h3>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium 
                                        {{ $status['completion_summary']['is_complete'] ? 'text-green-600' : 'text-orange-600' }}">
                                        {{ $status['completion_summary']['completion_percentage'] }}%
                                    </span>
                                    @if($status['completion_summary']['is_complete'])
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                                            ✅ Complete
                                        </span>
                                    @else
                                        <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs font-medium">
                                            ⚠️ Missing {{ count($status['missing_required']) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Summary Stats -->
                            <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 mb-3">
                                <div>Total Slots: {{ $status['completion_summary']['total_slots'] }}</div>
                                <div>Required: {{ $status['completion_summary']['required_slots'] }}</div>
                                <div>Uploaded: {{ $status['completion_summary']['uploaded_slots'] }}</div>
                                <div>Approved: {{ $status['completion_summary']['approved_slots'] }}</div>
                            </div>
                            
                            <!-- Missing Required Slots -->
                            @if(!empty($status['missing_required']))
                                <div class="bg-red-50 border border-red-200 rounded p-2 mb-3">
                                    <p class="text-xs font-medium text-red-800 mb-1">Missing Required:</p>
                                    @foreach($status['missing_required'] as $missingSlot)
                                        @php 
                                            $slotConfig = config("aergas_photos.modules.".strtoupper($moduleKey).".slots.{$missingSlot}");
                                            $slotLabel = $slotConfig['label'] ?? $missingSlot;
                                        @endphp
                                        <span class="inline-block px-2 py-1 bg-red-100 text-red-700 rounded text-xs mr-1 mb-1">
                                            {{ $slotLabel }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                            
                            <!-- Slot Details (Collapsed by Default) -->
                            <details class="mt-3">
                                <summary class="cursor-pointer text-xs text-blue-600 hover:text-blue-800">
                                    Show All Slots ({{ count($status['slot_status']) }})
                                </summary>
                                <div class="mt-2 space-y-1">
                                    @foreach($status['slot_status'] as $slotKey => $slotInfo)
                                        <div class="flex items-center justify-between text-xs py-1">
                                            <span class="truncate flex-1 mr-2" title="{{ $slotInfo['label'] }}">
                                                {{ $slotInfo['label'] }}
                                                @if($slotInfo['required'])
                                                    <span class="text-red-500">*</span>
                                                @endif
                                            </span>
                                            @if($slotInfo['uploaded'])
                                                @if($slotInfo['approved_by_cgp'])
                                                    <span class="px-1.5 py-0.5 bg-green-100 text-green-700 rounded">✅ CGP</span>
                                                @elseif($slotInfo['approved_by_tracer'])
                                                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded">👤 Tracer</span>
                                                @elseif($slotInfo['status'] === 'draft')
                                                    <span class="px-1.5 py-0.5 bg-gray-100 text-gray-700 rounded">📝 Draft</span>
                                                @else
                                                    <span class="px-1.5 py-0.5 bg-yellow-100 text-yellow-700 rounded">🔄 Process</span>
                                                @endif
                                            @else
                                                <span class="px-1.5 py-0.5 bg-red-100 text-red-700 rounded">❌ Missing</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- CGP Progress Bar -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">CGP Approval Progress</h2>
            <div class="flex items-center justify-between">
                <!-- SK -->
                <div class="flex flex-col items-center flex-1">
                    @if($cgpStatus['sk_completed'])
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-green-600">SK Approved by CGP</span>
                    @elseif($cgpStatus['sk_ready'])
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mb-2">
                            <span class="text-yellow-600 font-bold">SK</span>
                        </div>
                        <span class="text-sm font-medium text-yellow-600">SK Ready for CGP</span>
                    @elseif($cgpStatus['sk_in_progress'] ?? false)
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-blue-600">SK In Progress</span>
                    @elseif($cgpStatus['sk_waiting_tracer'] ?? false)
                        <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-orange-600">SK Waiting Tracer</span>
                    @else
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0h-2"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-400">SK Not Ready</span>
                    @endif
                </div>

                <!-- Arrow -->
                <div class="flex-shrink-0 mx-4">
                    <svg class="w-6 h-6 {{ $cgpStatus['sk_completed'] ? 'text-green-400' : 'text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>

                <!-- SR -->
                <div class="flex flex-col items-center flex-1">
                    @if($cgpStatus['sr_completed'])
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-green-600">SR Approved by CGP</span>
                    @elseif($cgpStatus['sr_ready'])
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-2">
                            <span class="text-blue-600 font-bold">SR</span>
                        </div>
                        <span class="text-sm font-medium text-blue-600">SR Ready for CGP</span>
                    @elseif($cgpStatus['sr_in_progress'] ?? false)
                        <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-indigo-600">SR In Progress</span>
                    @elseif($cgpStatus['sr_waiting_tracer'] ?? false)
                        <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-orange-600">SR Waiting Tracer</span>
                    @else
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0h-2"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-400">SR Not Ready</span>
                    @endif
                </div>

                <!-- Arrow -->
                <div class="flex-shrink-0 mx-4">
                    <svg class="w-6 h-6 {{ $cgpStatus['sr_completed'] ? 'text-green-400' : 'text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>

                <!-- Gas In -->
                <div class="flex flex-col items-center flex-1">
                    @if($cgpStatus['gas_in_completed'])
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-green-600">Gas In Approved by CGP</span>
                    @elseif($cgpStatus['gas_in_ready'])
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mb-2">
                            <span class="text-purple-600 font-bold">GI</span>
                        </div>
                        <span class="text-sm font-medium text-purple-600">Gas In Ready for CGP</span>
                    @elseif($cgpStatus['gas_in_in_progress'] ?? false)
                        <div class="w-12 h-12 bg-pink-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-pink-600">Gas In In Progress</span>
                    @elseif($cgpStatus['gas_in_waiting_tracer'] ?? false)
                        <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-orange-600">Gas In Waiting Tracer</span>
                    @else
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0h-2"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-400">Gas In Not Ready</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($customer)
    <div class="bg-white rounded-xl p-6 border shadow border-gray-100 mb-6">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
        <i class="fas fa-user text-white"></i>
        </div>
        <h2 class="text-xl font-semibold text-gray-800">Customer Information</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div>
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Reference ID</div>
        <div class="font-semibold text-blue-700">{{ $customer->reff_id_pelanggan }}</div>
        </div>
        <div>
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nama Pelanggan</div>
        <div class="font-semibold text-gray-900">{{ $customer->nama_pelanggan ?? '-' }}</div>
        </div>
        <div>
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">No. Telepon</div>
        <div class="font-medium text-gray-700">
            @if(!empty($customer->no_telepon))
            <a href="tel:{{ $customer->no_telepon }}" class="text-indigo-600 hover:text-indigo-800">
                <i class="fas fa-phone mr-1"></i>{{ $customer->no_telepon }}
            </a>
            @else
            <span class="text-gray-400">-</span>
            @endif
        </div>
        </div>
        <div class="lg:col-span-3">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Alamat</div>
        <div class="font-medium text-gray-700">{{ $customer->alamat ?? '-' }}</div>
        </div>
        <div>
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Kelurahan</div>
        <div class="font-medium text-gray-700">
            @if($customer->kelurahan)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                <i class="fas fa-map-marker-alt mr-1"></i>{{ $customer->kelurahan }}
            </span>
            @else
            <span class="text-gray-400">-</span>
            @endif
        </div>
        </div>
        <div>
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Padukuhan</div>
        <div class="font-medium text-gray-700">
            @if($customer->padukuhan)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                <i class="fas fa-home mr-1"></i>{{ $customer->padukuhan }}
            </span>
            @else
            <span class="text-gray-400">-</span>
            @endif
        </div>
        </div>
    </div>

    @php
        $status = $customer->status ?? '';
        $progress = $customer->progress_status ?? '';
    @endphp

    <div class="mt-6 pt-6 border-t border-gray-200 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tanggal Registrasi</div>
        <div class="font-medium text-gray-700">
            {{ optional($customer->tanggal_registrasi)->format('d F Y') ?? '-' }}
        </div>
        </div>
        <div>
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status Customer</div>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
            @class([
            'bg-green-100 text-green-800' => $status === 'lanjut',
            'bg-yellow-100 text-yellow-800' => $status === 'pending',
            'bg-gray-100 text-gray-800' => $status === 'menunda',
            'bg-red-100 text-red-800' => $status === 'batal',
            // fallback jika status di luar daftar
            'bg-blue-100 text-blue-800' => !in_array($status, ['lanjut','pending','menunda','batal']),
            ])
        ">
            {{ strtoupper($status ?: '-') }}
        </span>
        </div>
        <div>
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Progress Status</div>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
            @class([
            'bg-green-100 text-green-800'   => $progress === 'done',
            'bg-blue-100 text-blue-800'     => $progress === 'gas_in',
            'bg-purple-100 text-purple-800' => $progress === 'sr',
            'bg-orange-100 text-orange-800' => $progress === 'sk',
            'bg-yellow-100 text-yellow-800' => $progress === 'validasi',
            'bg-gray-100 text-gray-800'     => in_array($progress, ['pending','batal']),
            // fallback jika progress di luar daftar
            'bg-slate-100 text-slate-800'   => !in_array($progress, ['done','gas_in','sr','sk','validasi','pending','batal']),
            ])
        ">
            {{ ucwords(str_replace('_',' ', $progress ?: '-')) }}
        </span>
        </div>
    </div>
    </div>
    @endif

    <!-- Photo Sections -->
    {{-- ===== 1 CARD / MODULE: MATERIAL (atas) + PHOTOS (bawah) ===== --}}
@php
    $modules = ['sk','sr','gas_in'];

    $desiredOrder = ['isometrik_scan','pneumatic_start','pneumatic_finish','valve','berita_acara'];
    $orderIndex = collect($desiredOrder)->flip(); // key => index
@endphp

@foreach($modules as $module)
    @php
        // status siap/complete modul
        $moduleReady = ($module === 'sk' && $cgpStatus['sk_ready']) ||
                       ($module === 'sr' && $cgpStatus['sr_ready']) ||
                       ($module === 'gas_in' && $cgpStatus['gas_in_ready']);
        $moduleCompleted = ($module === 'sk' && $cgpStatus['sk_completed']) ||
                           ($module === 'sr' && $cgpStatus['sr_completed']) ||
                           ($module === 'gas_in' && $cgpStatus['gas_in_completed']);
        $moduleInProgress = ($module === 'sk' && ($cgpStatus['sk_in_progress'] ?? false)) ||
                            ($module === 'sr' && ($cgpStatus['sr_in_progress'] ?? false)) ||
                            ($module === 'gas_in' && ($cgpStatus['gas_in_in_progress'] ?? false));
        $moduleWaitingTracer = ($module === 'sk' && ($cgpStatus['sk_waiting_tracer'] ?? false)) ||
                               ($module === 'sr' && ($cgpStatus['sr_waiting_tracer'] ?? false)) ||
                               ($module === 'gas_in' && ($cgpStatus['gas_in_waiting_tracer'] ?? false));

        // Module available if: ready, completed, in progress, or waiting tracer
        $moduleAvailable = $moduleReady || $moduleCompleted || $moduleInProgress || $moduleWaitingTracer;

        // foto + urut
        $modulePhotos = collect($photos[$module] ?? [])->sortBy(function($p) use ($orderIndex) {
            $key = $p->photo_key ?? \Illuminate\Support\Str::slug($p->photo_field_name, '_');
            return $orderIndex[$key] ?? PHP_INT_MAX;
        })->values();

        // Check if there are any photos that need CGP approval (not placeholder and status cgp_pending)
        $hasPhotosToApprove = $modulePhotos->filter(function($photo) {
            return !($photo->is_placeholder ?? false) && $photo->photo_status === 'cgp_pending';
        })->isNotEmpty();

        // ---------- MATERIAL (dinamis per modul) ----------
        $showMaterial = false;
        $materialTitle = '';
        $materialSubtitle = '';
        $materialAccent = '';
        $materialData = [];
        $materialTotals = [];

        if ($module === 'sk' && $customer->skData) {
            $showMaterial = true;
            $materialTitle = 'Material SK';
            $materialSubtitle = 'Service connection materials';
            $materialAccent = 'from-green-500 to-emerald-600';
            $sk = $customer->skData;

            $labels = [
                'panjang_pipa_gl_medium_m'       => 'Panjang Pipa 1/2" GL Medium (meter)',
                'qty_elbow_1_2_galvanis'         => 'Elbow 1/2" Galvanis (Pcs)',
                'qty_sockdraft_galvanis_1_2'     => 'SockDraft Galvanis Dia 1/2" (Pcs)',
                'qty_ball_valve_1_2'             => 'Ball Valve 1/2" (Pcs)',
                'qty_nipel_selang_1_2'           => 'Nipel Selang 1/2" (Pcs)',
                'qty_elbow_reduce_3_4_1_2'       => 'Elbow Reduce 3/4" x 1/2" (Pcs)',
                'qty_long_elbow_3_4_male_female' => 'Long Elbow 3/4" Male Female (Pcs)',
                'qty_klem_pipa_1_2'              => 'Klem Pipa 1/2" (Pcs)',
                'qty_double_nipple_1_2'          => 'Double Nipple 1/2" (Pcs)',
                'qty_seal_tape'                   => 'Seal Tape (Pcs)',
                'qty_tee_1_2'                     => 'Tee 1/2" (Pcs)',
            ];

            $totalFitting = 0;
            foreach ($labels as $field => $label) {
                $val = (float)($sk->$field ?? 0);
                if ($val > 0) {
                    $materialData[] = ['label' => $label, 'value' => $val, 'field' => $field];
                    if ($field !== 'panjang_pipa_gl_medium_m') $totalFitting += $val;
                }
            }
            $materialTotals = [
                'total_fitting' => $totalFitting,
                'total_pipa'    => (float)($sk->panjang_pipa_gl_medium_m ?? 0),
            ];
        }

        if ($module === 'sr' && $customer->srData) {
            $showMaterial = true;
            $materialTitle = 'Material SR';
            $materialSubtitle = 'Service regulator materials';
            $materialAccent = 'from-yellow-500 to-amber-600';
            $sr = $customer->srData;

            $labels = $sr->getMaterialLabels();
            $totalItems = 0; $totalLengths = 0;

            foreach ($sr->getRequiredMaterialItems() as $field => $val) {
                $v = (float)($val ?? 0);
                if ($v > 0) {
                    $materialData[] = ['label' => ($labels[$field] ?? $field), 'value' => $v, 'field' => $field];
                    if (str_contains($field, 'panjang_')) $totalLengths += $v; else $totalItems += $v;
                }
            }
            foreach ($sr->getOptionalMaterialItems() as $field => $val) {
                $v = (float)($val ?? 0);
                if ($v > 0) $materialData[] = ['label' => ($labels[$field] ?? $field), 'value' => $v, 'field' => $field];
            }

            $materialTotals = [
                'total_items'   => $totalItems,
                'total_lengths' => $totalLengths,
                'jenis_tapping' => $sr->jenis_tapping ?? null,
            ];
        }

        // kalau dua-duanya kosong, lewati card
        $shouldRenderCard = $showMaterial || $moduleAvailable;
    @endphp

    @if($shouldRenderCard)
        <div class="bg-white rounded-lg shadow mb-6 border border-gray-200">
            {{-- HEADER CARD: Judul modul + status + approve all (kalau perlu) --}}
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br {{ $materialAccent ?: 'from-slate-400 to-slate-500' }} rounded-lg flex items-center justify-center shadow">
                            <i class="fas fa-layer-group text-white"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ strtoupper($module) }} Material & Photos</h2>
                            <p class="text-sm text-gray-600">Ringkasan material dan foto tracer-approved</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($moduleCompleted)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">✅ CGP Approved</span>
                        @elseif($moduleReady)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">⏳ Pending CGP Review</span>
                        @endif

                        @if($moduleReady && !$moduleCompleted && $hasPhotosToApprove)
                            <button onclick="approveModule('{{ $module }}')"
                                    id="approveModuleBtn_{{ $module }}"
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition-all duration-200">
                                <span class="approve-text">✅ Approve All {{ strtoupper($module) }}</span>
                                <span class="approve-loading hidden">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Processing...
                                </span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- SECTION 1: MATERIAL (ATAS) --}}
            @if($showMaterial)
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 bg-gradient-to-br {{ $materialAccent }} rounded-lg flex items-center justify-center shadow">
                            <i class="fas fa-clipboard-list text-white text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">{{ $materialTitle }}</h3>
                            <p class="text-sm text-gray-600">{{ $materialSubtitle }}</p>
                        </div>
                    </div>

                    @if(empty($materialData))
                        <div class="text-center py-6 text-gray-500">Belum ada data material</div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($materialData as $m)
                                <div class="bg-gray-50 p-4 rounded-lg border">
                                    <div class="text-xs text-gray-500 mb-1">{{ $m['label'] }}</div>
                                    <div class="font-bold text-lg text-gray-800">
                                        {{ $m['value'] }}
                                        @if(($module === 'sk' && $m['field'] === 'panjang_pipa_gl_medium_m') || ($module === 'sr' && str_contains($m['field'], 'panjang_')))
                                            <span class="text-sm font-normal text-gray-600">meter</span>
                                        @else
                                            <span class="text-sm font-normal text-gray-600">pcs</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- ringkasan total --}}
                        @if($module === 'sk')
                            <div class="mt-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200">
                                <div class="flex justify-between items-center">
                                    <span class="font-medium text-green-800">Total Fitting:</span>
                                    <span class="font-bold text-green-900 text-lg">{{ $materialTotals['total_fitting'] }} pcs</span>
                                </div>
                                @if($materialTotals['total_pipa'] > 0)
                                    <div class="flex justify-between items-center mt-2">
                                        <span class="font-medium text-green-800">Total Pipa:</span>
                                        <span class="font-bold text-green-900 text-lg">{{ $materialTotals['total_pipa'] }} meter</span>
                                    </div>
                                @endif
                            </div>
                        @elseif($module === 'sr')
                            <div class="mt-6 p-4 bg-gradient-to-r from-yellow-50 to-amber-50 rounded-lg border border-yellow-200">
                                <div class="flex justify-between items-center">
                                    <span class="font-medium text-yellow-800">Total Items:</span>
                                    <span class="font-bold text-yellow-900 text-lg">{{ $materialTotals['total_items'] }} pcs</span>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="font-medium text-yellow-800">Total Lengths:</span>
                                    <span class="font-bold text-yellow-900 text-lg">{{ $materialTotals['total_lengths'] }} meter</span>
                                </div>
                                @if(!empty($materialTotals['jenis_tapping']))
                                    <div class="flex justify-between items-center mt-2">
                                        <span class="font-medium text-yellow-800">Jenis Tapping:</span>
                                        <span class="font-bold text-yellow-900 text-lg">{{ $materialTotals['jenis_tapping'] }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            @endif

            {{-- DIVIDER antar section dalam 1 card --}}
            @if($moduleAvailable)
                <div class="border-t border-gray-200"></div>
            @endif

            {{-- SECTION 2: PHOTOS (BAWAH) --}}
            @if($moduleAvailable)
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ strtoupper($module) }} Photos (Tracer Approved)</h3>
                        @if($moduleCompleted)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">✅ CGP Approved</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">⏳ Pending CGP Review</span>
                        @endif
                    </div>

                    @if($modulePhotos->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($modulePhotos as $photo)
                                @php
                                    $isPlaceholder = $photo->is_placeholder ?? false;
                                @endphp
                                <div class="border border-gray-200 rounded-lg overflow-hidden {{ $isPlaceholder ? 'bg-gray-50 border-dashed border-2' : '' }}">
                                    <div class="aspect-w-4 aspect-h-3 bg-gray-100">
                                        @if($isPlaceholder)
                                            {{-- Empty card placeholder for missing photo --}}
                                            <div class="flex flex-col items-center justify-center h-48 text-gray-400 bg-gray-100">
                                                <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2z"></path>
                                                </svg>
                                                <p class="text-sm font-medium text-gray-600">Photo Not Uploaded</p>
                                                <p class="text-xs text-gray-500 mt-1">Missing from field submission</p>
                                            </div>
                                        @elseif($photo->photo_url && !empty(trim($photo->photo_url)))
                                            @php
                                                $imageUrl = $photo->photo_url;
                                                if (str_contains($imageUrl, 'drive.google.com')) {
                                                    if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $imageUrl, $matches)) {
                                                        $fileId = $matches[1];
                                                        $imageUrl = "https://lh3.googleusercontent.com/d/{$fileId}";
                                                    }
                                                }
                                            @endphp
                                            <img src="{{ $imageUrl }}"
                                                 alt="{{ $photo->photo_field_name }}"
                                                 class="photo-preview w-full h-48 object-cover"
                                                 onclick="openPhotoModal('{{ $imageUrl }}')"
                                                 data-file-id="{{ preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $photo->photo_url, $matches) ? $matches[1] : '' }}"
                                                 data-original-url="{{ $photo->photo_url }}"
                                                 onerror="tryAlternativeUrls(this)">
                                        @else
                                            <div class="flex flex-col items-center justify-center h-48 text-gray-400">
                                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2z"></path>
                                                </svg>
                                                <p class="text-xs mt-2">No image</p>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="p-4">
                                        <div class="flex items-start justify-between mb-2">
                                            <div>
                                                <h4 class="font-medium text-gray-900">
                                                    {{ $isPlaceholder ? $photo->slot_label : $photo->photo_field_name }}
                                                </h4>
                                                @if($isPlaceholder && $photo->is_required)
                                                    <span class="inline-block px-2 py-0.5 text-xs bg-red-100 text-red-700 rounded mt-1">Required</span>
                                                @endif
                                            </div>
                                        </div>

                                        @if($isPlaceholder)
                                            {{-- Placeholder info --}}
                                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-center">
                                                <p class="text-xs text-amber-800 font-medium">⚠️ Missing Required Photo</p>
                                                <p class="text-xs text-amber-600 mt-1">This photo was not uploaded by field officer</p>
                                            </div>
                                        @else
                                            <!-- Status -->
                                            <div class="mb-3 space-y-2">
                                                {{-- Tracer Approval Info --}}
                                                @if($photo->tracer_approved_at)
                                                <div>
                                                    <button type="button"
                                                            onclick="toggleRejectionDetails({{ $photo->id }}, 'tracer-approved')"
                                                            class="w-full inline-flex items-center justify-between px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200 transition-colors cursor-pointer text-left">
                                                        <span>✅ Tracer Approved</span>
                                                        <i id="tracer-approved-chevron-{{ $photo->id }}" class="fas fa-chevron-down transition-transform"></i>
                                                    </button>
                                                    <div id="tracer-approved-details-{{ $photo->id }}" class="hidden mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <span class="text-xs text-gray-600">Approved at:</span>
                                                            <span class="text-xs font-medium text-green-600">{{ $photo->tracer_approved_at->format('d/m/Y H:i') }}</span>
                                                        </div>
                                                        @if($photo->tracerUser)
                                                            <div class="flex items-center gap-1 mb-2">
                                                                <i class="fas fa-user text-green-600 text-xs"></i>
                                                                <span class="text-xs text-green-700">{{ $photo->tracerUser->name }}</span>
                                                            </div>
                                                        @endif
                                                        @if($photo->tracer_notes)
                                                            <div class="mt-2 pt-2 border-t border-green-200">
                                                                <p class="text-xs text-gray-600 mb-1 font-medium">Notes:</p>
                                                                <p class="text-xs text-green-700 bg-white p-2 rounded">{{ $photo->tracer_notes }}</p>
                                                            </div>
                                                        @else
                                                            <p class="text-xs text-gray-500 italic">No notes</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- Approved Status --}}
                                            @if($photo->cgp_approved_at)
                                                <div>
                                                    <button type="button"
                                                            onclick="toggleRejectionDetails({{ $photo->id }}, 'cgp-approved')"
                                                            class="w-full inline-flex items-center justify-between px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200 transition-colors cursor-pointer text-left">
                                                        <span>✅ Approved by CGP</span>
                                                        <i id="cgp-approved-chevron-{{ $photo->id }}" class="fas fa-chevron-down transition-transform"></i>
                                                    </button>
                                                    <div id="cgp-approved-details-{{ $photo->id }}" class="hidden mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <span class="text-xs text-gray-600">Approved at:</span>
                                                            <span class="text-xs font-medium text-green-600">{{ $photo->cgp_approved_at->format('d/m/Y H:i') }}</span>
                                                        </div>
                                                        @if($photo->cgpUser)
                                                            <div class="flex items-center gap-1 mb-2">
                                                                <i class="fas fa-user text-green-600 text-xs"></i>
                                                                <span class="text-xs text-green-700">{{ $photo->cgpUser->name }}</span>
                                                            </div>
                                                        @endif
                                                        @if($photo->cgp_notes)
                                                            <div class="mt-2 pt-2 border-t border-green-200">
                                                                <p class="text-xs text-gray-600 mb-1 font-medium">Notes:</p>
                                                                <p class="text-xs text-green-700 bg-white p-2 rounded">{{ $photo->cgp_notes }}</p>
                                                            </div>
                                                        @else
                                                            <p class="text-xs text-gray-500 italic">No notes</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- Pending Status --}}
                                            @if(!$photo->cgp_rejected_at && !$photo->cgp_approved_at)
                                                <div class="w-full inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    ⏳ Pending CGP Review
                                                </div>
                                            @endif

                                            {{-- Tracer Rejection Dropdown (read-only for CGP) --}}
                                            @if($photo->tracer_rejected_at)
                                                <div>
                                                    <button type="button"
                                                            onclick="toggleRejectionDetails({{ $photo->id }}, 'tracer')"
                                                            class="w-full inline-flex items-center justify-between px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors cursor-pointer text-left">
                                                        <span class="flex items-center gap-1">
                                                            <i class="fas fa-info-circle"></i>
                                                            <span>Tracer Feedback</span>
                                                        </span>
                                                        <i id="tracer-chevron-{{ $photo->id }}" class="fas fa-chevron-down transition-transform"></i>
                                                    </button>
                                                    <div id="tracer-details-{{ $photo->id }}" class="hidden mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <span class="text-xs text-gray-600">Rejected at:</span>
                                                            <span class="text-xs font-medium text-blue-600">{{ $photo->tracer_rejected_at->format('d/m/Y H:i') }}</span>
                                                        </div>
                                                        @if($photo->tracerUser)
                                                            <div class="flex items-center gap-1 mb-2">
                                                                <i class="fas fa-user text-blue-600 text-xs"></i>
                                                                <span class="text-xs text-blue-700">{{ $photo->tracerUser->name }}</span>
                                                            </div>
                                                        @endif
                                                        @if($photo->tracer_notes)
                                                            <div class="mt-2 pt-2 border-t border-blue-200">
                                                                <p class="text-xs text-gray-600 mb-1 font-medium">Reason:</p>
                                                                <p class="text-xs text-blue-700 bg-white p-2 rounded">{{ $photo->tracer_notes }}</p>
                                                            </div>
                                                        @else
                                                            <p class="text-xs text-gray-500 italic">No reason provided</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- CGP Rejection Dropdown --}}
                                            @if($photo->cgp_rejected_at)
                                                <div>
                                                    <button type="button"
                                                            onclick="toggleRejectionDetails({{ $photo->id }}, 'cgp')"
                                                            class="w-full inline-flex items-center justify-between px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200 transition-colors cursor-pointer text-left">
                                                        <span class="flex items-center gap-1">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                            <span>Rejected by CGP</span>
                                                        </span>
                                                        <i id="cgp-chevron-{{ $photo->id }}" class="fas fa-chevron-down transition-transform"></i>
                                                    </button>
                                                    <div id="cgp-details-{{ $photo->id }}" class="hidden mt-2 p-3 bg-red-50 border border-red-200 rounded-lg">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <span class="text-xs text-gray-600">Rejected at:</span>
                                                            <span class="text-xs font-medium text-red-600">{{ $photo->cgp_rejected_at->format('d/m/Y H:i') }}</span>
                                                        </div>
                                                        @if($photo->cgpUser)
                                                            <div class="flex items-center gap-1 mb-2">
                                                                <i class="fas fa-user text-red-600 text-xs"></i>
                                                                <span class="text-xs text-red-700">{{ $photo->cgpUser->name }}</span>
                                                            </div>
                                                        @endif
                                                        @if($photo->cgp_notes)
                                                            <div class="mt-2 pt-2 border-t border-red-200">
                                                                <p class="text-xs text-gray-600 mb-1 font-medium">Reason:</p>
                                                                <p class="text-xs text-red-700 bg-white p-2 rounded">{{ $photo->cgp_notes }}</p>
                                                            </div>
                                                        @else
                                                            <p class="text-xs text-gray-500 italic">No reason provided</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                            @if(!$photo->cgp_approved_at && !$photo->cgp_rejected_at && in_array($photo->photo_status, ['tracer_approved', 'cgp_pending']))
                                                <div class="flex space-x-2">
                                                    <button onclick="approvePhoto({{ $photo->id }})"
                                                            id="approveBtn_{{ $photo->id }}"
                                                            class="flex-1 bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm transition-all duration-200">
                                                        <span class="approve-text">✅ Approve</span>
                                                        <span class="approve-loading hidden">
                                                            <svg class="animate-spin h-3 w-3 text-white inline" fill="none" viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                            </svg>
                                                        </span>
                                                    </button>
                                                    <button onclick="rejectPhoto({{ $photo->id }})"
                                                            id="rejectBtn_{{ $photo->id }}"
                                                            class="flex-1 bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-all duration-200">
                                                        <span class="reject-text">❌ Reject</span>
                                                        <span class="reject-loading hidden">
                                                            <svg class="animate-spin h-3 w-3 text-white inline" fill="none" viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                            </svg>
                                                        </span>
                                                    </button>
                                                </div>
                                            @endif
                                        @endif

                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-8 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2z"></path>
                            </svg>
                            <h4 class="mt-2 text-sm font-medium text-gray-900">Belum ada foto untuk {{ strtoupper($module) }}</h4>
                            <p class="mt-1 text-sm text-gray-500">Foto akan muncul setelah di-approve oleh Tracer</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif
@endforeach

</div>

<!-- Photo Modal -->
<div id="photoModal" class="photo-modal" onclick="closePhotoModal()">
    <img id="modalPhoto" src="" alt="">
</div>

<!-- Notes Modal -->
<div id="notesModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 id="notesModalTitle" class="text-lg font-medium text-gray-900 mb-4"></h3>
        <form id="notesForm">
            <div class="mb-4">
                <label for="reviewNotes" class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                <textarea id="reviewNotes" rows="3" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Add your review notes here..."></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeNotesModal()" 
                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg" 
                        id="cancelButton">
                    Cancel
                </button>
                <button type="submit" id="confirmButton" 
                        class="px-4 py-2 rounded-lg text-white">
                    <span id="confirmButtonText">Confirm</span>
                    <span id="confirmButtonLoading" class="hidden">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Global variables
let currentPhotoId = null;
let currentAction = null;
let currentModule = null;
const reffId = '{{ $customer->reff_id_pelanggan }}';

// Photo modal functions
function openPhotoModal(src) {
    document.getElementById('modalPhoto').src = src;
    document.getElementById('photoModal').style.display = 'block';
}

function closePhotoModal() {
    document.getElementById('photoModal').style.display = 'none';
}

// Notes modal functions
function openNotesModal(title, action, photoId = null, module = null) {
    currentPhotoId = photoId;
    currentAction = action;
    currentModule = module;
    
    document.getElementById('notesModalTitle').textContent = title;
    document.getElementById('reviewNotes').value = '';
    
    const confirmButton = document.getElementById('confirmButton');
    const confirmButtonText = document.getElementById('confirmButtonText');
    
    // Reset button state
    resetButtonState();
    
    if (action === 'approve') {
        confirmButton.className = 'px-4 py-2 rounded-lg text-white bg-green-600 hover:bg-green-700';
        confirmButtonText.textContent = 'Approve';
    } else if (action === 'reject') {
        confirmButton.className = 'px-4 py-2 rounded-lg text-white bg-red-600 hover:bg-red-700';
        confirmButtonText.textContent = 'Reject';
    } else if (action === 'approveModule') {
        confirmButton.className = 'px-4 py-2 rounded-lg text-white bg-green-600 hover:bg-green-700';
        confirmButtonText.textContent = 'Approve All';
    }
    
    document.getElementById('notesModal').classList.remove('hidden');
}

function closeNotesModal() {
    document.getElementById('notesModal').classList.add('hidden');
    // Reset all button states when modal closes
    resetAllButtonStates();
    currentPhotoId = null;
    currentAction = null;
    currentModule = null;
}

// Photo actions
function approvePhoto(photoId) {
    // Set button loading state
    setButtonLoadingState(`approveBtn_${photoId}`, 'approve-text', 'approve-loading');
    openNotesModal('Approve Photo', 'approve', photoId);
}

function rejectPhoto(photoId) {
    // Set button loading state
    setButtonLoadingState(`rejectBtn_${photoId}`, 'reject-text', 'reject-loading');
    openNotesModal('Reject Photo', 'reject', photoId);
}

function approveModule(module) {
    // Set button loading state
    setButtonLoadingState(`approveModuleBtn_${module}`, 'approve-text', 'approve-loading');
    openNotesModal(`Approve All ${module.toUpperCase()} Photos`, 'approveModule', null, module);
}

// Button loading state functions
function setButtonLoadingState(buttonId, textClass, loadingClass) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.disabled = true;
        button.classList.add('opacity-50', 'cursor-not-allowed');
        
        const textSpan = button.querySelector(`.${textClass}`);
        const loadingSpan = button.querySelector(`.${loadingClass}`);
        
        if (textSpan) textSpan.classList.add('hidden');
        if (loadingSpan) loadingSpan.classList.remove('hidden');
    }
}

function resetButtonLoadingState(buttonId, textClass, loadingClass) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.disabled = false;
        button.classList.remove('opacity-50', 'cursor-not-allowed');
        
        const textSpan = button.querySelector(`.${textClass}`);
        const loadingSpan = button.querySelector(`.${loadingClass}`);
        
        if (textSpan) textSpan.classList.remove('hidden');
        if (loadingSpan) loadingSpan.classList.add('hidden');
    }
}

function resetAllButtonStates() {
    // Reset all button states when modal closes or form completes
    document.querySelectorAll('[id^="approveBtn_"], [id^="rejectBtn_"], [id^="approveModuleBtn_"]').forEach(button => {
        button.disabled = false;
        button.classList.remove('opacity-50', 'cursor-not-allowed');
        
        const textSpan = button.querySelector('.approve-text, .reject-text');
        const loadingSpan = button.querySelector('.approve-loading, .reject-loading');
        
        if (textSpan) textSpan.classList.remove('hidden');
        if (loadingSpan) loadingSpan.classList.add('hidden');
    });
}

// Loading state functions
function setLoadingState() {
    const confirmButton = document.getElementById('confirmButton');
    const confirmButtonText = document.getElementById('confirmButtonText');
    const confirmButtonLoading = document.getElementById('confirmButtonLoading');
    const cancelButton = document.getElementById('cancelButton');
    
    confirmButton.disabled = true;
    confirmButton.classList.add('opacity-50', 'cursor-not-allowed');
    confirmButtonText.classList.add('hidden');
    confirmButtonLoading.classList.remove('hidden');
    cancelButton.disabled = true;
    cancelButton.classList.add('opacity-50', 'cursor-not-allowed');
}

function resetButtonState() {
    const confirmButton = document.getElementById('confirmButton');
    const confirmButtonText = document.getElementById('confirmButtonText');
    const confirmButtonLoading = document.getElementById('confirmButtonLoading');
    const cancelButton = document.getElementById('cancelButton');
    
    confirmButton.disabled = false;
    confirmButton.classList.remove('opacity-50', 'cursor-not-allowed');
    confirmButtonText.classList.remove('hidden');
    confirmButtonLoading.classList.add('hidden');
    cancelButton.disabled = false;
    cancelButton.classList.remove('opacity-50', 'cursor-not-allowed');
}

// Form submission
document.getElementById('notesForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Set loading state immediately
    setLoadingState();
    
    const notes = document.getElementById('reviewNotes').value;
    const formData = new FormData();
    formData.append('notes', notes);
    formData.append('_token', '{{ csrf_token() }}');
    
    let url = '';
    let actionText = '';
    
    if (currentAction === 'approveModule') {
        url = '{{ route("approvals.cgp.approve-module") }}';
        formData.append('reff_id', reffId);
        formData.append('module', currentModule);
        actionText = `Approving all ${currentModule.toUpperCase()} photos`;
    } else if (currentAction === 'approve') {
        url = '{{ route("approvals.cgp.approve-photo") }}';
        formData.append('photo_id', currentPhotoId);
        formData.append('action', currentAction);
        actionText = 'Approving photo';
    } else if (currentAction === 'reject') {
        url = '{{ route("approvals.cgp.approve-photo") }}';
        formData.append('photo_id', currentPhotoId);
        formData.append('action', currentAction);
        actionText = 'Rejecting photo';
    }
    
    // Show toast notification with action in progress
    showToast('info', `${actionText}... Please wait.`);
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        resetButtonState();
        resetAllButtonStates(); // Reset all button states
        closeNotesModal();
        
        if (data.success) {
            showToast('success', data.message);
            
            // Add organization info for module approvals
            if (currentAction === 'approveModule' && data.data && data.data.approved_photos) {
                const approvedCount = data.data.approved_photos.length;
                setTimeout(() => {
                    showToast('info', `📁 Organizing ${approvedCount} photos into dedicated folders...`);
                }, 1000);
            }
            
            // Reload page after showing success message
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showToast('error', 'Error: ' + (data.message || 'Unknown error occurred'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        resetButtonState();
        resetAllButtonStates(); // Reset all button states
        closeNotesModal();
        showToast('error', 'Network error occurred. Please try again.');
    });
});

// Toast notification function
function showToast(type, message) {
    // Remove existing toast if any
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }
    
    const toast = document.createElement('div');
    toast.className = `toast-notification fixed top-4 right-4 z-50 max-w-sm w-full bg-white border border-gray-200 rounded-lg shadow-lg p-4 transition-all duration-300 transform translate-x-full`;
    
    let bgClass = '';
    let iconSvg = '';
    
    if (type === 'success') {
        bgClass = 'border-green-400 bg-green-50';
        iconSvg = `<svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>`;
    } else if (type === 'error') {
        bgClass = 'border-red-400 bg-red-50';
        iconSvg = `<svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>`;
    } else if (type === 'info') {
        bgClass = 'border-blue-400 bg-blue-50';
        iconSvg = `<svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>`;
    }
    
    toast.className += ` ${bgClass}`;
    toast.innerHTML = `
        <div class="flex items-start">
            <div class="flex-shrink-0">
                ${iconSvg}
            </div>
            <div class="ml-3">
                <p class="text-sm text-gray-700">${message}</p>
            </div>
            <div class="ml-auto pl-3">
                <button onclick="this.closest('.toast-notification').remove()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
        toast.classList.add('translate-x-0');
    }, 100);
    
    // Auto remove after 5 seconds (except for info toasts which stay longer)
    const autoRemoveTime = type === 'info' ? 8000 : 5000;
    setTimeout(() => {
        if (toast.parentNode) {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 300);
        }
    }, autoRemoveTime);
}

// Google Drive URL fallback function
function tryAlternativeUrls(imgElement) {
    const fileId = imgElement.dataset.fileId;
    if (!fileId) {
        imgElement.parentElement.innerHTML = '<div class="flex flex-col items-center justify-center h-48 text-red-400"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path></svg><p class="text-xs mt-2">Image unavailable</p></div>';
        return;
    }
    
    const alternatives = [
        `https://drive.google.com/uc?export=view&id=${fileId}`,
        `https://drive.google.com/uc?id=${fileId}`,
        `https://drive.google.com/thumbnail?id=${fileId}&sz=w400`,
        `https://docs.google.com/uc?id=${fileId}`
    ];
    
    let currentIndex = imgElement.dataset.attemptIndex || 0;
    currentIndex = parseInt(currentIndex);
    
    if (currentIndex < alternatives.length) {
        imgElement.dataset.attemptIndex = currentIndex + 1;
        imgElement.src = alternatives[currentIndex];
    } else {
        // All alternatives failed, show error with original URL
        const originalUrl = imgElement.dataset.originalUrl || imgElement.src;
        imgElement.parentElement.innerHTML = `
            <div class="flex flex-col items-center justify-center h-48 text-red-400 p-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
            <p class="text-xs mt-2 text-center">Image failed to load</p>
            <p class="text-xs text-gray-500 mt-1 break-all">File ID: ${fileId}</p>
            <a href="${originalUrl}" target="_blank" class="text-xs text-blue-500 mt-2 hover:underline">Open in Drive</a>
        </div>
        `;
    }
}

// Toggle rejection details dropdown
function toggleRejectionDetails(photoId, type) {
    const detailsDiv = document.getElementById(`${type}-details-${photoId}`);
    const chevronIcon = document.getElementById(`${type}-chevron-${photoId}`);

    if (detailsDiv.classList.contains('hidden')) {
        // Show details
        detailsDiv.classList.remove('hidden');
        chevronIcon.classList.add('rotate-180');
    } else {
        // Hide details
        detailsDiv.classList.add('hidden');
        chevronIcon.classList.remove('rotate-180');
    }
}
</script>
@endpush