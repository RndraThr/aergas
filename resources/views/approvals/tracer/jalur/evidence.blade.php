@extends('layouts.app')

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
        z-index: 9999;
        overflow: hidden;
    }
    .photo-modal img {
        max-width: 90%;
        max-height: 90%;
        display: block;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        cursor: zoom-in;
    }
    .photo-modal img.zoom-transition {
        transition: transform 0.2s ease-out;
    }
    .photo-modal img.zoomed {
        max-width: none;
        max-height: none;
        cursor: grab;
    }
    .photo-modal img.zoomed:active {
        cursor: grabbing;
    }
    .photo-modal-controls {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 10000;
        display: flex;
        gap: 10px;
    }
    .photo-modal-controls button {
        background: rgba(255,255,255,0.9);
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        font-size: 18px;
        color: #333;
    }
    .photo-modal-controls button:hover {
        background: rgba(255,255,255,1);
        transform: scale(1.1);
    }

    /* Lazy loading skeleton */
    .lazy-image {
        position: relative;
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading-skeleton 1.5s ease-in-out infinite;
    }

    .lazy-image.loaded {
        animation: none;
        background: none;
    }

    @keyframes loading-skeleton {
        0% {
            background-position: 200% 0;
        }
        100% {
            background-position: -200% 0;
        }
    }

    /* Fade in animation when loaded */
    .lazy-image.loaded {
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-purple-50 to-pink-50">
    <div class="container mx-auto px-4 py-6">
        {{-- Breadcrumb --}}
        <div class="mb-4">
            <a href="{{ route('approvals.tracer.jalur.lines', $line->cluster_id) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-50 transition shadow-sm group">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span class="font-semibold">Kembali ke Lines</span>
            </a>
        </div>

        {{-- Header --}}
        <div class="mb-6 bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                            {{ $line->line_number }}
                        </h1>
                        <p class="text-sm text-gray-600">{{ $line->cluster->nama_cluster }}</p>
                    </div>
                </div>
                @if($lineStats['pending_photos'] > 0)
                    <button onclick="approveEntireLine()" class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition shadow-lg font-bold">
                        ✓ Approve All ({{ $lineStats['pending_photos'] }})
                    </button>
                @endif
            </div>

            {{-- Line Info Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-6 gap-3 p-4 bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg border border-purple-200">
                <div class="text-center">
                    <div class="text-xs text-gray-600 mb-1">Cluster</div>
                    <div class="text-sm font-bold text-gray-900">{{ $line->cluster->nama_cluster }}</div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-gray-600 mb-1">MC-0</div>
                    <div class="text-sm font-bold text-blue-600">{{ number_format($line->estimasi_panjang, 2) }} m</div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-gray-600 mb-1">Actual</div>
                    <div class="text-sm font-bold text-indigo-600">{{ number_format($line->total_penggelaran, 2) }} m</div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-gray-600 mb-1">MC-100</div>
                    <div class="text-sm font-bold {{ $line->actual_mc100 ? 'text-green-600' : 'text-gray-400' }}">
                        {{ $line->actual_mc100 ? number_format($line->actual_mc100, 2) . ' m' : '-' }}
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-gray-600 mb-1">Progress</div>
                    <div class="text-sm font-bold text-purple-600">{{ number_format($line->getProgressPercentage(), 0) }}%</div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-gray-600 mb-1">Work Dates</div>
                    <div class="text-sm font-bold text-pink-600">{{ $lineStats['work_dates_count'] }}</div>
                </div>
            </div>
        </div>

        {{-- Status & Informasi Jalur Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Informasi Jalur --}}
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center gap-3 mb-4">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    <h2 class="font-semibold text-gray-800">Informasi Jalur</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Line Number</div>
                        <div class="text-lg font-bold text-blue-600 mt-1">{{ $line->line_number }}</div>
                        <div class="text-sm text-gray-600">{{ $line->cluster->nama_cluster }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Diameter Pipa</div>
                        <div class="font-semibold text-gray-900 mt-1">{{ $line->diameter }}mm</div>
                        <div class="text-sm text-gray-600">{{ $line->status_line }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Nama Jalan</div>
                        <div class="font-semibold text-gray-900 mt-1">{{ $line->nama_jalan ?? '-' }}</div>
                    </div>
                </div>
            </div>

            {{-- Overall Line Status (if needed) --}}
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center gap-3 mb-4">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h2 class="font-semibold text-gray-800">Status Approval Line</h2>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs text-gray-500">Total Photos</div>
                        <div class="text-2xl font-bold text-gray-900">{{ $lineStats['total_photos'] }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Approved</div>
                        <div class="text-2xl font-bold text-green-600">{{ $lineStats['approved_photos'] }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Pending</div>
                        <div class="text-2xl font-bold text-orange-600">{{ $lineStats['pending_photos'] }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Rejected</div>
                        <div class="text-2xl font-bold text-red-600">{{ $lineStats['rejected_photos'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Evidence Sections Grouped by Date --}}
        @if($workDates->count() > 0)
            <div class="space-y-6">
                @foreach($workDates as $lowering)
                    @php
                        $photos = $lowering->photoApprovals;
                        $hasPhotos = $photos->count() > 0;
                    @endphp

                    {{-- Date Section --}}
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                        {{-- Status & Metadata Card --}}
                        <div class="bg-white p-4 border-b border-gray-200">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <div class="text-xs text-gray-500">Status Laporan</div>
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($lowering->status_laporan === 'draft') bg-gray-100 text-gray-800
                                            @elseif($lowering->status_laporan === 'acc_tracer') bg-yellow-100 text-yellow-800
                                            @elseif($lowering->status_laporan === 'acc_cgp') bg-green-100 text-green-800
                                            @elseif(in_array($lowering->status_laporan, ['revisi_tracer', 'revisi_cgp'])) bg-red-100 text-red-800
                                            @endif">
                                            {{ $lowering->status_label }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Created By</div>
                                    @if($lowering->createdBy)
                                        <div class="flex items-center mt-1">
                                            <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center mr-2">
                                                <span class="text-xs font-medium text-green-600">
                                                    {{ strtoupper(substr($lowering->createdBy->name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <span class="font-medium text-sm">{{ $lowering->createdBy->name }}</span>
                                        </div>
                                    @else
                                        <div class="text-gray-400 text-sm">-</div>
                                    @endif
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Tanggal Jalur</div>
                                    <div class="font-medium text-sm">{{ \Carbon\Carbon::parse($lowering->tanggal_jalur)->format('d/m/Y') }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Dibuat</div>
                                    <div class="font-medium text-sm">{{ $lowering->created_at->format('d/m/Y H:i') }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- Date Header with Lowering Details --}}
                        <div class="bg-gradient-to-r from-purple-500 to-pink-500 text-white p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h2 class="text-xl font-bold">
                                        {{ \Carbon\Carbon::parse($lowering->tanggal_lowering)->format('d M Y') }}
                                    </h2>
                                    <p class="text-sm text-purple-100">
                                        {{ \Carbon\Carbon::parse($lowering->tanggal_lowering)->isoFormat('dddd') }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-purple-100">Photos</div>
                                    <div class="text-2xl font-bold">{{ $photos->count() }}</div>
                                </div>
                            </div>

                            {{-- Lowering Details --}}
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 bg-white/10 backdrop-blur-sm rounded-lg p-3">
                                <div class="text-center">
                                    <div class="text-xs text-purple-100">Tipe Bongkaran</div>
                                    <div class="text-sm font-semibold">{{ $lowering->tipe_bongkaran }}</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xs text-purple-100">Penggelaran</div>
                                    <div class="text-sm font-semibold">{{ number_format($lowering->penggelaran, 1) }}m</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xs text-purple-100">Bongkaran</div>
                                    <div class="text-sm font-semibold">{{ number_format($lowering->bongkaran, 1) }}m</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xs text-purple-100">Kedalaman</div>
                                    <div class="text-sm font-semibold">{{ $lowering->kedalaman_lowering }}cm</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xs text-purple-100">Material</div>
                                    <div class="text-sm font-semibold">{{ $lowering->tipe_material }}</div>
                                </div>
                            </div>

                            {{-- Materials & Accessories (if any) --}}
                            @if($lowering->cassing_quantity || $lowering->marker_tape_quantity || $lowering->concrete_slab_quantity)
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if($lowering->cassing_quantity)
                                        <span class="inline-flex items-center px-3 py-1 bg-white/20 rounded-full text-xs font-medium">
                                            📦 Cassing: {{ $lowering->cassing_quantity }} {{ $lowering->cassing_type }}
                                        </span>
                                    @endif
                                    @if($lowering->marker_tape_quantity)
                                        <span class="inline-flex items-center px-3 py-1 bg-white/20 rounded-full text-xs font-medium">
                                            🏷️ Marker Tape: {{ $lowering->marker_tape_quantity }}m
                                        </span>
                                    @endif
                                    @if($lowering->concrete_slab_quantity)
                                        <span class="inline-flex items-center px-3 py-1 bg-white/20 rounded-full text-xs font-medium">
                                            🧱 Concrete Slab: {{ $lowering->concrete_slab_quantity }} pcs
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Photos Grid - 3 Columns --}}
                        <div class="p-6">
                            @if($hasPhotos)
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    @foreach($photos as $photo)
                                        @php
                                            $isTracerApproved = in_array($photo->photo_status, ['tracer_approved', 'cgp_approved', 'cgp_pending']);
                                            $isCgpApproved = $photo->photo_status === 'cgp_approved';
                                            $isCgpPending = $photo->photo_status === 'cgp_pending';
                                            $isPending = in_array($photo->photo_status, ['tracer_pending', 'draft']);
                                            $isTracerRejected = $photo->photo_status === 'tracer_rejected';
                                            $isCgpRejected = $photo->photo_status === 'cgp_rejected';
                                        @endphp

                                        <div class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                                            {{-- Photo Display --}}
                                            <div class="aspect-w-4 aspect-h-3 bg-gray-100">
                                                @if($photo->photo_url && !empty(trim($photo->photo_url)))
                                                    @php
                                                        // Extract Google Drive file ID and use direct URL
                                                        $imageUrl = $photo->photo_url;
                                                        $fileId = null;

                                                        if (str_contains($imageUrl, 'drive.google.com')) {
                                                            // Extract file ID from various Google Drive URL formats
                                                            if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $imageUrl, $matches)) {
                                                                $fileId = $matches[1];
                                                            } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $imageUrl, $matches)) {
                                                                $fileId = $matches[1];
                                                            } elseif (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $imageUrl, $matches)) {
                                                                $fileId = $matches[1];
                                                            }

                                                            // Use Google's direct thumbnail URL (more reliable)
                                                            if ($fileId) {
                                                                $imageUrl = "https://drive.google.com/thumbnail?id={$fileId}&sz=w800";
                                                            }
                                                        }
                                                    @endphp
                                                    <img src="{{ $imageUrl }}"
                                                         alt="{{ $photo->photo_field_name }}"
                                                         class="photo-preview w-full h-48 object-cover lazy-image"
                                                         onclick="openPhotoModal('{{ $imageUrl }}', '{{ $photo->photo_field_name }}')"
                                                         data-file-id="{{ $fileId }}"
                                                         data-original-url="{{ $photo->photo_url }}"
                                                         onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5JbWFnZSBFcnJvcjwvdGV4dD48L3N2Zz4=';"
                                                         onload="this.classList.add('loaded');">
                                                @else
                                                    <div class="flex flex-col items-center justify-center h-48 text-gray-400">
                                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                        <p class="text-sm mt-2">No Photo</p>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Card Content --}}
                                            <div class="p-4">
                                                {{-- Photo Field Name --}}
                                                <h3 class="font-semibold text-gray-900 text-sm mb-2">
                                                    {{ $photo->photo_field_name }}
                                                </h3>

                                                {{-- Status Dropdown --}}
                                                <div class="mb-3 space-y-2">
                                                    {{-- Tracer Approved Status --}}
                                                    @if($isTracerApproved)
                                                        <div>
                                                            <button type="button"
                                                                    onclick="toggleStatusDetails({{ $photo->id }}, 'tracer-approved')"
                                                                    class="w-full inline-flex items-center justify-between px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200 transition-colors cursor-pointer text-left">
                                                                <span>✅ Approved by Tracer</span>
                                                                <i id="tracer-approved-chevron-{{ $photo->id }}" class="fas fa-chevron-down transition-transform"></i>
                                                            </button>
                                                            <div id="tracer-approved-details-{{ $photo->id }}" class="hidden mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                                                                <div class="flex items-center justify-between mb-2">
                                                                    <span class="text-xs text-gray-600">Approved at:</span>
                                                                    <span class="text-xs font-medium text-green-600">{{ \Carbon\Carbon::parse($photo->tracer_approved_at)->format('d/m/Y H:i') }}</span>
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

                                                    {{-- CGP Status (Final Approval) --}}
                                                    @if($isCgpApproved)
                                                        <div>
                                                            <button type="button"
                                                                    onclick="toggleStatusDetails({{ $photo->id }}, 'cgp-approved')"
                                                                    class="w-full inline-flex items-center justify-between px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors cursor-pointer text-left">
                                                                <span>✅ CGP Approved (Final)</span>
                                                                <i id="cgp-approved-chevron-{{ $photo->id }}" class="fas fa-chevron-down transition-transform"></i>
                                                            </button>
                                                            <div id="cgp-approved-details-{{ $photo->id }}" class="hidden mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                                                <div class="flex items-center justify-between mb-2">
                                                                    <span class="text-xs text-gray-600">Approved at:</span>
                                                                    <span class="text-xs font-medium text-blue-600">{{ \Carbon\Carbon::parse($photo->cgp_approved_at)->format('d/m/Y H:i') }}</span>
                                                                </div>
                                                                @if($photo->cgpUser)
                                                                    <div class="flex items-center gap-1 mb-2">
                                                                        <i class="fas fa-user text-blue-600 text-xs"></i>
                                                                        <span class="text-xs text-blue-700">{{ $photo->cgpUser->name }}</span>
                                                                    </div>
                                                                @endif
                                                                @if($photo->cgp_notes)
                                                                    <div class="mt-2 pt-2 border-t border-blue-200">
                                                                        <p class="text-xs text-gray-600 mb-1 font-medium">Notes:</p>
                                                                        <p class="text-xs text-blue-700 bg-white p-2 rounded">{{ $photo->cgp_notes }}</p>
                                                                    </div>
                                                                @else
                                                                    <p class="text-xs text-gray-500 italic">No notes</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @elseif($isCgpPending)
                                                        <div class="w-full inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            🔄 Under CGP Review
                                                        </div>
                                                    @endif

                                                    {{-- Pending Tracer Review Status --}}
                                                    @if($isPending)
                                                        <div class="w-full inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                            ⏳ Pending Tracer Review
                                                        </div>
                                                    @endif

                                                    {{-- Tracer Rejected Status --}}
                                                    @if($isTracerRejected)
                                                        <div>
                                                            <button type="button"
                                                                    onclick="toggleStatusDetails({{ $photo->id }}, 'tracer-rejected')"
                                                                    class="w-full inline-flex items-center justify-between px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200 transition-colors cursor-pointer text-left">
                                                                <span class="flex items-center gap-1">
                                                                    <i class="fas fa-exclamation-triangle"></i>
                                                                    <span>Rejected by Tracer</span>
                                                                </span>
                                                                <i id="tracer-rejected-chevron-{{ $photo->id }}" class="fas fa-chevron-down transition-transform"></i>
                                                            </button>
                                                            <div id="tracer-rejected-details-{{ $photo->id }}" class="hidden mt-2 p-3 bg-red-50 border border-red-200 rounded-lg">
                                                                <div class="flex items-center justify-between mb-2">
                                                                    <span class="text-xs text-gray-600">Rejected at:</span>
                                                                    <span class="text-xs font-medium text-red-600">{{ \Carbon\Carbon::parse($photo->tracer_rejected_at)->format('d/m/Y H:i') }}</span>
                                                                </div>
                                                                @if($photo->tracerUser)
                                                                    <div class="flex items-center gap-1 mb-2">
                                                                        <i class="fas fa-user text-red-600 text-xs"></i>
                                                                        <span class="text-xs text-red-700">{{ $photo->tracerUser->name }}</span>
                                                                    </div>
                                                                @endif
                                                                @if($photo->tracer_notes)
                                                                    <div class="mt-2 pt-2 border-t border-red-200">
                                                                        <p class="text-xs text-gray-600 mb-1 font-medium">Reason:</p>
                                                                        <p class="text-xs text-red-700 bg-white p-2 rounded">{{ $photo->tracer_notes }}</p>
                                                                    </div>
                                                                @else
                                                                    <p class="text-xs text-gray-500 italic">No reason provided</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif

                                                    {{-- CGP Rejected Status --}}
                                                    @if($isCgpRejected)
                                                        <div>
                                                            <button type="button"
                                                                    onclick="toggleStatusDetails({{ $photo->id }}, 'cgp-rejected')"
                                                                    class="w-full inline-flex items-center justify-between px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 hover:bg-orange-200 transition-colors cursor-pointer text-left">
                                                                <span class="flex items-center gap-1">
                                                                    <i class="fas fa-times-circle"></i>
                                                                    <span>❌ Rejected by CGP</span>
                                                                </span>
                                                                <i id="cgp-rejected-chevron-{{ $photo->id }}" class="fas fa-chevron-down transition-transform"></i>
                                                            </button>
                                                            <div id="cgp-rejected-details-{{ $photo->id }}" class="hidden mt-2 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                                                                <div class="mb-2 p-2 bg-orange-100 border border-orange-300 rounded">
                                                                    <p class="text-xs font-semibold text-orange-900">⚠️ Perlu Perbaikan - Rejected by CGP</p>
                                                                    <p class="text-xs text-orange-700 mt-1">Photo perlu di-replace oleh Admin/Super Admin</p>
                                                                </div>
                                                                <div class="flex items-center justify-between mb-2">
                                                                    <span class="text-xs text-gray-600">Rejected at:</span>
                                                                    <span class="text-xs font-medium text-orange-600">{{ \Carbon\Carbon::parse($photo->cgp_rejected_at)->format('d/m/Y H:i') }}</span>
                                                                </div>
                                                                @if($photo->cgpUser)
                                                                    <div class="flex items-center gap-1 mb-2">
                                                                        <i class="fas fa-user text-orange-600 text-xs"></i>
                                                                        <span class="text-xs text-orange-700">{{ $photo->cgpUser->name }}</span>
                                                                    </div>
                                                                @endif
                                                                @if($photo->cgp_notes)
                                                                    <div class="mt-2 pt-2 border-t border-orange-200">
                                                                        <p class="text-xs text-gray-600 mb-1 font-medium">CGP Rejection Reason:</p>
                                                                        <p class="text-xs text-orange-800 bg-white p-2 rounded font-semibold">{{ $photo->cgp_notes }}</p>
                                                                    </div>
                                                                @else
                                                                    <p class="text-xs text-gray-500 italic">No reason provided</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- Action Buttons --}}
                                                <div class="space-y-2">
                                                    {{-- Approve/Reject Buttons (only for pending/rejected photos) --}}
                                                    @if(!$isTracerApproved && !$isCgpRejected)
                                                        <div class="flex gap-2">
                                                            <button onclick="openApproveModal({{ $photo->id }})"
                                                                    class="flex-1 bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded text-sm font-medium transition">
                                                                ✓ Approve
                                                            </button>
                                                            <button onclick="openRejectModal({{ $photo->id }})"
                                                                    class="flex-1 bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm font-medium transition">
                                                                ✗ Reject
                                                            </button>
                                                        </div>
                                                    @endif

                                                    {{-- Replace Photo Button (Admin/Super Admin only - available for ALL statuses) --}}
                                                    @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                                                        <button onclick="openReplacePhotoModal({{ $photo->id }}, '{{ $photo->photo_field_name }}', '{{ $photo->photo_status }}')"
                                                                class="w-full bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm font-medium transition flex items-center justify-center gap-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                                            </svg>
                                                            Replace Photo
                                                            @if($isTracerApproved || $isCgpApproved)
                                                                <span class="text-xs">(Will reset status)</span>
                                                            @endif
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <svg class="mx-auto w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-gray-500 text-sm">Belum ada evidence photo untuk tanggal ini</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-700 mb-2">Belum Ada Data</h3>
                <p class="text-gray-500">Belum ada evidence photo untuk line ini</p>
            </div>
        @endif

        {{-- Summary Card - Sticky di bottom dalam container --}}
        <div class="sticky bottom-4 mt-6 z-40">
            <div class="bg-white rounded-xl shadow-2xl border-2 border-purple-300 p-6">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex flex-wrap gap-6 justify-center md:justify-start">
                        <div class="text-center">
                            <div class="text-xs text-gray-600 mb-1">Total Photos</div>
                            <div class="text-2xl font-bold text-gray-900">{{ $lineStats['total_photos'] }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-green-600 mb-1">Approved</div>
                            <div class="text-2xl font-bold text-green-700">{{ $lineStats['approved_photos'] }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-orange-600 mb-1">Pending</div>
                            <div class="text-2xl font-bold text-orange-700">{{ $lineStats['pending_photos'] }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-red-600 mb-1">Rejected</div>
                            <div class="text-2xl font-bold text-red-700">{{ $lineStats['rejected_photos'] }}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="text-center md:text-right">
                            <div class="text-xs text-gray-600 mb-1">Approval Progress</div>
                            <div class="text-2xl font-bold text-purple-600">{{ number_format($lineStats['percentage'], 0) }}%</div>
                        </div>
                        <div class="w-48 bg-gray-200 rounded-full h-4 shadow-inner">
                            <div class="h-4 rounded-full transition-all shadow-sm {{ $lineStats['percentage'] === 100 ? 'bg-gradient-to-r from-green-400 to-green-600' : 'bg-gradient-to-r from-purple-400 to-purple-600' }}"
                                 style="width: {{ $lineStats['percentage'] }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Photo Modal --}}
<div id="photoModal" class="photo-modal">
    <div class="photo-modal-controls">
        <button id="zoomInBtn" onclick="zoomIn(event)" title="Zoom In">
            <i class="fas fa-search-plus"></i>
        </button>
        <button id="zoomOutBtn" onclick="zoomOut(event)" title="Zoom Out">
            <i class="fas fa-search-minus"></i>
        </button>
        <button id="resetZoomBtn" onclick="resetZoom(event)" title="Reset">
            <i class="fas fa-compress"></i>
        </button>
        <button onclick="closePhotoModal(event)" title="Close">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <img id="modalImage" src="" alt="Photo" class="zoom-transition">
</div>

{{-- Approve Modal --}}
<div id="approveModal" class="fixed inset-0 bg-black bg-opacity-50 z-[9999] hidden flex items-center justify-center" onclick="closeApproveModal()">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4" onclick="event.stopPropagation()">
        <div class="bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-4 rounded-t-xl">
            <h3 class="text-xl font-bold">Approve Photo</h3>
            <p class="text-sm text-green-100">Konfirmasi approval photo ini</p>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                <textarea
                    id="approveNotes"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                    placeholder="Tambahkan catatan (opsional)..."></textarea>
            </div>
            <div class="flex gap-3">
                <button onclick="closeApproveModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                    Batal
                </button>
                <button onclick="confirmApprove()" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                    ✓ Approve
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 z-[9999] hidden flex items-center justify-center" onclick="closeRejectModal()">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4" onclick="event.stopPropagation()">
        <div class="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-4 rounded-t-xl">
            <h3 class="text-xl font-bold">Reject Photo</h3>
            <p class="text-sm text-red-100">Berikan alasan reject</p>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Alasan Reject <span class="text-red-500">*</span></label>
                <textarea
                    id="rejectNotes"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                    placeholder="Masukkan alasan reject (minimal 10 karakter)..."
                    required></textarea>
                <p class="text-xs text-gray-500 mt-1">Minimal 10 karakter</p>
            </div>
            <div class="flex gap-3">
                <button onclick="closeRejectModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                    Batal
                </button>
                <button onclick="confirmReject()" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                    ✗ Reject
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let currentPhotoId = null;
let currentScale = 1;
let currentX = 0;
let currentY = 0;
let isDragging = false;
let startX = 0;
let startY = 0;

// Photo Modal Functions
function openPhotoModal(url, name) {
    const modal = document.getElementById('photoModal');
    const img = document.getElementById('modalImage');
    img.src = url;
    img.alt = name;
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    resetZoom();
}

function closePhotoModal(event) {
    if (event) event.stopPropagation();
    const modal = document.getElementById('photoModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    resetZoom();
}

function zoomIn(event) {
    event.stopPropagation();
    currentScale = Math.min(currentScale + 0.5, 5);
    applyZoom();
}

function zoomOut(event) {
    event.stopPropagation();
    currentScale = Math.max(currentScale - 0.5, 0.5);
    applyZoom();
}

function resetZoom(event) {
    if (event) event.stopPropagation();
    currentScale = 1;
    currentX = 0;
    currentY = 0;
    applyZoom();
}

function applyZoom() {
    const img = document.getElementById('modalImage');
    if (currentScale > 1) {
        img.classList.add('zoomed');
    } else {
        img.classList.remove('zoomed');
        currentX = 0;
        currentY = 0;
    }
    img.style.transform = `translate(calc(-50% + ${currentX}px), calc(-50% + ${currentY}px)) scale(${currentScale})`;
}

// Drag to pan when zoomed
document.getElementById('photoModal').addEventListener('click', function(e) {
    if (e.target.id === 'photoModal') {
        closePhotoModal(e);
    }
});

document.getElementById('modalImage').addEventListener('mousedown', function(e) {
    if (currentScale > 1) {
        isDragging = true;
        startX = e.clientX - currentX;
        startY = e.clientY - currentY;
        e.preventDefault();
    }
});

document.addEventListener('mousemove', function(e) {
    if (isDragging) {
        currentX = e.clientX - startX;
        currentY = e.clientY - startY;
        applyZoom();
    }
});

document.addEventListener('mouseup', function() {
    isDragging = false;
});

// Click image to toggle zoom
document.getElementById('modalImage').addEventListener('click', function(e) {
    e.stopPropagation();
    if (currentScale === 1) {
        currentScale = 2;
    } else {
        resetZoom();
    }
    applyZoom();
});

// Approve Modal Functions
function openApproveModal(photoId) {
    currentPhotoId = photoId;
    const modal = document.getElementById('approveModal');
    const notes = document.getElementById('approveNotes');
    notes.value = '';
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeApproveModal() {
    const modal = document.getElementById('approveModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    currentPhotoId = null;
}

function confirmApprove() {
    if (!currentPhotoId) return;

    const notes = document.getElementById('approveNotes').value.trim();

    fetch('{{ route("approvals.tracer.jalur.approve-photo") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            photo_id: currentPhotoId,
            notes: notes || 'Approved by tracer'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Photo berhasil di-approve!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat approve photo');
    });
}

// Reject Modal Functions
function openRejectModal(photoId) {
    currentPhotoId = photoId;
    const modal = document.getElementById('rejectModal');
    const notes = document.getElementById('rejectNotes');
    notes.value = '';
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeRejectModal() {
    const modal = document.getElementById('rejectModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    currentPhotoId = null;
}

function confirmReject() {
    if (!currentPhotoId) return;

    const notes = document.getElementById('rejectNotes').value.trim();

    if (!notes || notes.length < 10) {
        alert('Alasan reject harus minimal 10 karakter');
        return;
    }

    fetch('{{ route("approvals.tracer.jalur.reject-photo") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            photo_id: currentPhotoId,
            notes: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Photo berhasil di-reject!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat reject photo');
    });
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePhotoModal();
        closeApproveModal();
        closeRejectModal();
    }
});

// Toggle Status Details Dropdown
function toggleStatusDetails(photoId, type) {
    const detailsDiv = document.getElementById(`${type}-details-${photoId}`);
    const chevron = document.getElementById(`${type}-chevron-${photoId}`);

    if (detailsDiv.classList.contains('hidden')) {
        detailsDiv.classList.remove('hidden');
        chevron.style.transform = 'rotate(180deg)';
    } else {
        detailsDiv.classList.add('hidden');
        chevron.style.transform = 'rotate(0deg)';
    }
}

function approveEntireLine() {
    if (!confirm('Approve semua photo di line ini?')) return;

    fetch('{{ route("approvals.tracer.jalur.approve-line") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            line_id: {{ $line->id }},
            notes: 'Bulk approved by tracer'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Semua photo berhasil di-approve!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat approve line');
    });
}

// Replace Photo Modal Functions
let currentReplacePhotoId = null;
let currentReplacePhotoName = '';

function openReplacePhotoModal(photoId, photoName, photoStatus) {
    currentReplacePhotoId = photoId;
    currentReplacePhotoName = photoName;
    const modal = document.getElementById('replacePhotoModal');
    const photoNameSpan = document.getElementById('replacePhotoName');
    const fileInput = document.getElementById('replacePhotoFile');
    const preview = document.getElementById('replacePhotoPreview');
    const warningBox = document.getElementById('replacePhotoWarning');

    photoNameSpan.textContent = photoName;
    fileInput.value = '';
    preview.innerHTML = '';

    // Show warning if photo is already approved
    const approvedStatuses = ['tracer_approved', 'cgp_pending', 'cgp_approved'];
    if (approvedStatuses.includes(photoStatus)) {
        warningBox.classList.remove('hidden');
        warningBox.innerHTML = `
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Warning: Photo Already Approved</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>Status saat ini: <strong>${photoStatus.replace('_', ' ').toUpperCase()}</strong></p>
                            <p class="mt-1">Mengganti foto ini akan:</p>
                            <ul class="list-disc list-inside mt-1">
                                <li>Reset status ke <strong>TRACER_PENDING</strong></li>
                                <li>Memerlukan re-approval dari Tracer</li>
                                ${photoStatus === 'cgp_approved' || photoStatus === 'cgp_pending' ? '<li class="text-red-600 font-semibold">Menghilangkan approval CGP (harus review ulang)</li>' : ''}
                                <li>Mempengaruhi progress approval</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        `;
    } else {
        warningBox.classList.add('hidden');
        warningBox.innerHTML = '';
    }

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeReplacePhotoModal() {
    const modal = document.getElementById('replacePhotoModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    currentReplacePhotoId = null;
    currentReplacePhotoName = '';
}

function previewReplacePhoto(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('replacePhotoPreview');

    if (file) {
        if (file.size > 10 * 1024 * 1024) { // 10MB
            alert('File terlalu besar! Maximum 10MB');
            event.target.value = '';
            preview.innerHTML = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <img src="${e.target.result}" class="max-w-full max-h-64 rounded-lg shadow-md" alt="Preview">
                <p class="text-sm text-gray-600 mt-2">File: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)</p>
            `;
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '';
    }
}

function confirmReplacePhoto() {
    if (!currentReplacePhotoId) return;

    const fileInput = document.getElementById('replacePhotoFile');
    const file = fileInput.files[0];

    if (!file) {
        alert('Silakan pilih foto terlebih dahulu');
        return;
    }

    const formData = new FormData();
    formData.append('photo_id', currentReplacePhotoId);
    formData.append('photo', file);
    formData.append('_token', '{{ csrf_token() }}');

    // Show loading
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

    fetch('{{ route("approvals.tracer.jalur.replace-photo") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Photo berhasil diganti! Status direset ke pending.');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Terjadi kesalahan'));
            btn.disabled = false;
            btn.innerHTML = 'Upload & Replace';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat replace photo');
        btn.disabled = false;
        btn.innerHTML = 'Upload & Replace';
    });
}
</script>

{{-- Replace Photo Modal --}}
<div id="replacePhotoModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Replace Photo</h3>
                    <p class="text-sm text-gray-600 mt-1">Upload foto baru untuk: <span id="replacePhotoName" class="font-semibold text-blue-600"></span></p>
                </div>
                <button onclick="closeReplacePhotoModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="p-6">
            {{-- Warning Box (will be populated by JS if needed) --}}
            <div id="replacePhotoWarning" class="hidden"></div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Select New Photo
                </label>
                <input type="file"
                       id="replacePhotoFile"
                       accept="image/jpeg,image/jpg,image/png"
                       onchange="previewReplacePhoto(event)"
                       class="block w-full text-sm text-gray-500
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-lg file:border-0
                              file:text-sm file:font-semibold
                              file:bg-blue-50 file:text-blue-700
                              hover:file:bg-blue-100
                              cursor-pointer">
                <p class="text-xs text-gray-500 mt-1">Format: JPEG, JPG, PNG. Max 10MB</p>
            </div>

            <div id="replacePhotoPreview" class="mb-4 text-center"></div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="text-sm text-yellow-800">
                        <p class="font-semibold mb-1">Perhatian:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Foto lama akan dihapus dan diganti dengan foto baru</li>
                            <li>Status approval akan direset ke <strong>Pending</strong></li>
                            <li>Photo perlu di-approve ulang oleh Tracer</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 border-t border-gray-200 bg-gray-50 flex gap-3">
            <button onclick="closeReplacePhotoModal()"
                    class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition font-medium">
                Cancel
            </button>
            <button onclick="confirmReplacePhoto()"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                Upload & Replace
            </button>
        </div>
    </div>
</div>

@endpush
