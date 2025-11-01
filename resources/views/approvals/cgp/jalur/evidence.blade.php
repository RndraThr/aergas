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
            <a href="{{ route('approvals.cgp.jalur.lines', $line->cluster_id) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-50 transition shadow-sm group">
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
                        {{-- Date Header with Lowering Details --}}
                        <div class="bg-gradient-to-r from-purple-500 to-pink-500 text-white p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h2 class="text-xl font-bold">
                                        {{ \Carbon\Carbon::parse($lowering->tanggal_jalur)->format('d M Y') }}
                                    </h2>
                                    <p class="text-sm text-purple-100">
                                        {{ \Carbon\Carbon::parse($lowering->tanggal_jalur)->isoFormat('dddd') }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-purple-100">Photos</div>
                                    <div class="text-2xl font-bold">{{ $photos->count() }}</div>
                                </div>
                            </div>

                            {{-- Report Info --}}
                            <div class="mb-3 flex flex-wrap gap-2 items-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                    @if($lowering->status_laporan === 'draft') bg-white/20 text-white border border-white/30
                                    @elseif($lowering->status_laporan === 'acc_tracer') bg-yellow-400/30 text-yellow-100 border border-yellow-300/50
                                    @elseif($lowering->status_laporan === 'acc_cgp') bg-green-400/30 text-green-100 border border-green-300/50
                                    @elseif(in_array($lowering->status_laporan, ['revisi_tracer', 'revisi_cgp'])) bg-red-400/30 text-red-100 border border-red-300/50
                                    @endif">
                                    {{ $lowering->status_label }}
                                </span>
                                @if($lowering->createdBy)
                                    <span class="inline-flex items-center px-2.5 py-1 bg-white/20 rounded-full text-xs font-medium border border-white/30">
                                        <span class="w-5 h-5 bg-white/30 rounded-full flex items-center justify-center mr-1.5">
                                            <span class="text-[10px] font-bold">{{ strtoupper(substr($lowering->createdBy->name, 0, 1)) }}</span>
                                        </span>
                                        {{ $lowering->createdBy->name }}
                                    </span>
                                @endif
                                <span class="inline-flex items-center px-2.5 py-1 bg-white/20 rounded-full text-xs font-medium border border-white/30">
                                    <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Dibuat: {{ $lowering->created_at->format('d/m/Y H:i') }}
                                </span>
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
                                    <div class="text-sm font-semibold">{{ $lowering->tipe_material ?? '-' }}</div>
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
                                            // CGP Status Logic:
                                            // - tracer_approved = Ready for CGP review (pending)
                                            // - cgp_pending = CGP reviewing (same as tracer_approved, treated as pending)
                                            // - cgp_approved = Final approved by CGP
                                            // - cgp_rejected = Rejected by CGP
                                            $isCgpApproved = $photo->photo_status === 'cgp_approved';
                                            $isPendingCgpReview = in_array($photo->photo_status, ['tracer_approved', 'cgp_pending']);
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

                                                {{-- Status Display for CGP --}}
                                                <div class="mb-3 space-y-2">
                                                    {{-- Tracer Approval Info (Always show for context) --}}
                                                    <div>
                                                        <button type="button"
                                                                onclick="toggleStatusDetails({{ $photo->id }}, 'tracer')"
                                                                class="w-full inline-flex items-center justify-between px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors cursor-pointer text-left">
                                                            <span>✓ Approved by Tracer</span>
                                                            <i id="tracer-chevron-{{ $photo->id }}" class="fas fa-chevron-down transition-transform"></i>
                                                        </button>
                                                        <div id="tracer-details-{{ $photo->id }}" class="hidden mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                                            <div class="flex items-center justify-between mb-2">
                                                                <span class="text-xs text-gray-600">Approved at:</span>
                                                                <span class="text-xs font-medium text-blue-600">{{ \Carbon\Carbon::parse($photo->tracer_approved_at)->format('d/m/Y H:i') }}</span>
                                                            </div>
                                                            @if($photo->tracerUser)
                                                                <div class="flex items-center gap-1 mb-2">
                                                                    <i class="fas fa-user text-blue-600 text-xs"></i>
                                                                    <span class="text-xs text-blue-700">{{ $photo->tracerUser->name }}</span>
                                                                </div>
                                                            @endif
                                                            @if($photo->tracer_notes)
                                                                <div class="mt-2 pt-2 border-t border-blue-200">
                                                                    <p class="text-xs text-gray-600 mb-1 font-medium">Notes:</p>
                                                                    <p class="text-xs text-blue-700 bg-white p-2 rounded">{{ $photo->tracer_notes }}</p>
                                                                </div>
                                                            @else
                                                                <p class="text-xs text-gray-500 italic">No notes</p>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    {{-- CGP Final Approval Status --}}
                                                    @if($isCgpApproved)
                                                        <div>
                                                            <button type="button"
                                                                    onclick="toggleStatusDetails({{ $photo->id }}, 'cgp-approved')"
                                                                    class="w-full inline-flex items-center justify-between px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200 transition-colors cursor-pointer text-left">
                                                                <span>✅ APPROVED by CGP (Final)</span>
                                                                <i id="cgp-approved-chevron-{{ $photo->id }}" class="fas fa-chevron-down transition-transform"></i>
                                                            </button>
                                                            <div id="cgp-approved-details-{{ $photo->id }}" class="hidden mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                                                                <div class="flex items-center justify-between mb-2">
                                                                    <span class="text-xs text-gray-600">CGP Approved at:</span>
                                                                    <span class="text-xs font-medium text-green-600">{{ \Carbon\Carbon::parse($photo->cgp_approved_at)->format('d/m/Y H:i') }}</span>
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
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif

                                                    {{-- Pending CGP Review --}}
                                                    @if($isPendingCgpReview)
                                                        <div class="w-full inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                            ⏳ Pending CGP Review
                                                        </div>
                                                    @endif

                                                    {{-- CGP Rejected --}}
                                                    @if($isCgpRejected)
                                                        <div>
                                                            <button type="button"
                                                                    onclick="toggleStatusDetails({{ $photo->id }}, 'cgp-rejected')"
                                                                    class="w-full inline-flex items-center justify-between px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200 transition-colors cursor-pointer text-left">
                                                                <span class="flex items-center gap-1">
                                                                    <i class="fas fa-exclamation-triangle"></i>
                                                                    <span>Rejected by CGP</span>
                                                                </span>
                                                                <i id="cgp-rejected-chevron-{{ $photo->id }}" class="fas fa-chevron-down transition-transform"></i>
                                                            </button>
                                                            <div id="cgp-rejected-details-{{ $photo->id }}" class="hidden mt-2 p-3 bg-red-50 border border-red-200 rounded-lg">
                                                                <div class="flex items-center justify-between mb-2">
                                                                    <span class="text-xs text-gray-600">Rejected at:</span>
                                                                    <span class="text-xs font-medium text-red-600">{{ \Carbon\Carbon::parse($photo->cgp_rejected_at)->format('d/m/Y H:i') }}</span>
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

                                                {{-- Action Buttons for CGP --}}
                                                @if($isPendingCgpReview)
                                                    <div class="flex gap-2">
                                                        <button onclick="openApproveModal({{ $photo->id }})"
                                                                class="flex-1 bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded text-sm font-medium transition">
                                                            ✓ Approve (Final)
                                                        </button>
                                                        <button onclick="openRejectModal({{ $photo->id }})"
                                                                class="flex-1 bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm font-medium transition">
                                                            ✗ Reject
                                                        </button>
                                                    </div>
                                                @endif

                                                {{-- Revert Button (CGP Approved photos only) --}}
                                                @if($isCgpApproved && auth()->user()->hasAnyRole(['cgp', 'super_admin']))
                                                    <button onclick="openRevertModal({{ $photo->id }}, '{{ $photo->photo_field_name }}')"
                                                            class="w-full mt-2 bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-2 rounded text-sm font-medium transition flex items-center justify-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                                        </svg>
                                                        Revert Approval
                                                    </button>
                                                @endif
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
                <h3 class="text-xl font-bold text-gray-700 mb-2">Belum Ada Data Lowering</h3>
                <p class="text-gray-500">Belum ada evidence photo lowering untuk line ini</p>
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
                        @if($lineStats['pending_photos'] > 0)
                            <button onclick="approveEntireLine()" class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition shadow-lg font-bold whitespace-nowrap">
                                ✓ Approve All ({{ $lineStats['pending_photos'] }})
                            </button>
                        @endif
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
    if (!currentPhotoId) {
        return;
    }

    // Save ID to local variable BEFORE closing modal (which resets currentPhotoId)
    const photoId = currentPhotoId;
    const notes = document.getElementById('approveNotes').value.trim();


    // Close modal first
    closeApproveModal();

    // Show loading
    showLoading('Memproses approval...');

    const requestBody = {
        photo_id: photoId,
        notes: notes || 'Approved by CGP'
    };


    safeFetchJSON('{{ route("approvals.cgp.jalur.approve-photo") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(requestBody)
    })

    .then(data => {
        closeLoading();
        if (data.success) {
            showSuccessToast('Berhasil disetujui');
            setTimeout(() => location.reload(), 1000);
        } else {
            showErrorToast('Gagal memproses, silakan coba lagi');
        }
    })

    .catch(error => {
        closeLoading();
        showErrorToast('Gagal memproses, silakan coba lagi');
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

    const photoId = currentPhotoId;  // Save before modal close
    const notes = document.getElementById('rejectNotes').value.trim();

    if (!notes || notes.length < 10) {
        showWarningToast('Alasan reject harus minimal 10 karakter');
        return;
    }

    // Close modal first
    closeRejectModal();

    // Show loading
    showLoading('Memproses rejection...');

    safeFetchJSON('{{ route("approvals.cgp.jalur.reject-photo") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            photo_id: photoId,
            notes: notes
        })

    })

    
    .then(data => {
        closeLoading();
        if (data.success) {
            showSuccessToast('Berhasil ditolak');
            setTimeout(() => location.reload(), 1000);
        } else {
            showErrorToast('Error: ' + data.message);
        }
    })

    .catch(error => {
        closeLoading();
        showErrorToast('Terjadi kesalahan saat reject photo');
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

async function approveEntireLine() {
    const confirmed = await showConfirm({
        title: 'Konfirmasi Bulk Approval',
        text: 'Approve semua photo di line ini?',
        confirmText: 'Ya, Approve Semua',
        cancelText: 'Batal'
    });

    if (!confirmed) return;

    // Show loading
    showLoading('Memproses bulk approval...');

    safeFetchJSON('{{ route("approvals.cgp.jalur.approve-line") }}', {
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

    
    .then(data => {
        closeLoading();
        if (data.success) {
            showSuccessToast('Semua foto berhasil disetujui');
            setTimeout(() => location.reload(), 1000);
        } else {
            showErrorToast('Error: ' + data.message);
        }
    })

    .catch(error => {
        closeLoading();
        showErrorToast('Terjadi kesalahan saat approve line');
    });
}

// Revert Approval Modal Functions
let currentRevertPhotoId = null;
let currentRevertPhotoName = '';

function openRevertModal(photoId, photoName) {
    currentRevertPhotoId = photoId;
    currentRevertPhotoName = photoName;
    const modal = document.getElementById('revertModal');
    const photoNameSpan = document.getElementById('revertPhotoName');
    const reasonTextarea = document.getElementById('revertReason');

    photoNameSpan.textContent = photoName;
    reasonTextarea.value = '';

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeRevertModal() {
    const modal = document.getElementById('revertModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    currentRevertPhotoId = null;
    currentRevertPhotoName = '';
}

function confirmRevert() {
    if (!currentRevertPhotoId) return;

    const photoId = currentRevertPhotoId;  // Save before modal close

    const reason = document.getElementById('revertReason').value.trim();

    if (!reason || reason.length < 10) {
        showWarningToast('Silakan berikan alasan minimal 10 karakter');
        return;
    }

    // Close modal first
    closeRevertModal();

    // Show loading
    showLoading('Memproses revert approval...');

    safeFetchJSON('{{ route("approvals.cgp.revert-approval") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            photo_id: photoId,
            reason: reason
        })

    })

    
    .then(data => {
        closeLoading();
        if (data.success) {
            showSuccessToast('Approval berhasil dibatalkan');
            setTimeout(() => location.reload(), 1500);
        } else {
            showErrorToast('Error: ' + (data.message || 'Terjadi kesalahan'));
        }
    })

    .catch(error => {
        closeLoading();
        showErrorToast('Terjadi kesalahan saat revert approval');
    });
}
</script>

{{-- Revert Approval Modal --}}
<div id="revertModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Revert CGP Approval</h3>
                    <p class="text-sm text-gray-600 mt-1">Photo: <span id="revertPhotoName" class="font-semibold text-yellow-600"></span></p>
                </div>
                <button onclick="closeRevertModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="p-6">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Reason for Reverting <span class="text-red-500">*</span>
                </label>
                <textarea id="revertReason"
                          rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                          placeholder="Jelaskan alasan revert approval (minimal 10 karakter)..."></textarea>
                <p class="text-xs text-gray-500 mt-1">Minimum 10 characters required</p>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="text-sm text-yellow-800">
                        <p class="font-semibold mb-1">Warning - This action will:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Change photo status from <code class="bg-yellow-100 px-1 rounded">cgp_approved</code> to <code class="bg-yellow-100 px-1 rounded">cgp_pending</code></li>
                            <li>Move file back to upload folder (revert organization)</li>
                            <li>Clear organized_at and organized_folder fields</li>
                            <li>Photo will need to be reviewed again by CGP</li>
                            <li>This action is logged for audit trail</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 border-t border-gray-200 bg-gray-50 flex gap-3">
            <button onclick="closeRevertModal()"
                    class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition font-medium">
                Cancel
            </button>
            <button onclick="confirmRevert()"
                    class="flex-1 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition font-medium">
                Confirm Revert
            </button>
        </div>
    </div>
</div>

@endpush
