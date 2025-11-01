@extends('layouts.app')

@section('title', 'Tracer - Photo Review')

@push('head')
<!-- Preconnect untuk mempercepat image loading -->
<link rel="preconnect" href="{{ url('/') }}">
<link rel="dns-prefetch" href="{{ url('/') }}">
@endpush

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

    /* AI Loading Animation */
    @keyframes pulse-glow {
        0%, 100% {
            box-shadow: 0 0 20px rgba(147, 51, 234, 0.4);
        }
        50% {
            box-shadow: 0 0 40px rgba(147, 51, 234, 0.8);
        }
    }

    #aiLoadingModal .bg-white {
        animation: pulse-glow 2s ease-in-out infinite;
    }

    /* Smooth transitions */
    .transition-all {
        transition: all 0.3s ease;
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
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Photo Review - {{ $customer->reff_id_pelanggan }}</h1>
            <p class="text-gray-600 mt-1">{{ $customer->nama_pelanggan }} - {{ $customer->alamat }}</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('approvals.tracer.customers') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                ← Kembali ke List
            </a>
        </div>
    </div>

    <!-- Sequential Progress Bar -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Progress Sequential</h2>
            <div class="flex items-center justify-between">
                <!-- SK -->
                <div class="flex flex-col items-center flex-1">
                    @if($sequential['sk_completed'])
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-green-600">SK Completed</span>
                    @else
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mb-2">
                            <span class="text-yellow-600 font-bold">SK</span>
                        </div>
                        <span class="text-sm font-medium text-yellow-600">SK Pending</span>
                    @endif
                </div>

                <!-- Arrow -->
                <div class="flex-shrink-0 mx-4">
                    <svg class="w-6 h-6 {{ $sequential['sk_completed'] ? 'text-green-400' : 'text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>

                <!-- SR -->
                <div class="flex flex-col items-center flex-1">
                    @if($sequential['sr_completed'])
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-green-600">SR Completed</span>
                    @elseif($sequential['sr_available'])
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-2">
                            <span class="text-blue-600 font-bold">SR</span>
                        </div>
                        <span class="text-sm font-medium text-blue-600">SR Available</span>
                    @else
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0h-2"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-400">SR Locked</span>
                    @endif
                </div>

                <!-- Arrow -->
                <div class="flex-shrink-0 mx-4">
                    <svg class="w-6 h-6 {{ $sequential['sr_completed'] ? 'text-green-400' : 'text-gray-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>

                <!-- Gas In -->
                <div class="flex flex-col items-center flex-1">
                    @if($sequential['gas_in_completed'])
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-green-600">Gas In Completed</span>
                    @elseif($sequential['gas_in_available'])
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-2">
                            <span class="text-green-600 font-bold">GI</span>
                        </div>
                        <span class="text-sm font-medium text-green-600">Gas In Available</span>
                    @else
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0h-2"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-400">Gas In Locked</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Photo Sections -->
    @foreach(['sk', 'sr', 'gas_in'] as $module)
        @php
            $modulePhotos = $photos[$module] ?? [];
            $moduleAvailable = $module === 'sk' || ($module === 'sr' && $sequential['sr_available']) || ($module === 'gas_in' && $sequential['gas_in_available']);
            $moduleCompleted = ($module === 'sk' && $sequential['sk_completed']) || ($module === 'sr' && $sequential['sr_completed']) || ($module === 'gas_in' && $sequential['gas_in_completed']);

            // Check if there are any photos that need approval (not placeholder and not yet tracer approved)
            $hasPhotosToApprove = collect($modulePhotos)->filter(function($photo) {
                return !($photo->is_placeholder ?? false) && is_null($photo->tracer_approved_at);
            })->isNotEmpty();
        @endphp

        <div class="bg-white rounded-lg shadow mb-6 {{ !$moduleAvailable ? 'opacity-60' : '' }}">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <h2 class="text-lg font-semibold text-gray-900 mr-4">
                            {{ strtoupper($module) }} Photos
                        </h2>
                        @if($moduleCompleted)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                ✅ Tracer Approved
                            </span>
                        @elseif(!$moduleAvailable)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                🔒 Locked - Complete previous steps first
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                ⏳ Pending Review
                            </span>
                        @endif
                    </div>
                    
                    @if($moduleAvailable && !$moduleCompleted && $hasPhotosToApprove)
                        <div class="flex space-x-2">
                            <button id="aiReviewBtn-{{ $module }}" onclick="aiReviewModule('{{ $module }}')"
                                    class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition-all">
                                <span class="ai-btn-content">🤖 AI Review</span>
                            </button>
                            <button onclick="approveModule('{{ $module }}')"
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium text-sm">
                                ✅ Approve All {{ strtoupper($module) }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            @if(count($modulePhotos) > 0)
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($modulePhotos as $photo)
                            <div class="border border-gray-200 rounded-lg overflow-hidden {{ isset($photo->is_placeholder) && $photo->is_placeholder ? 'bg-gray-50 border-dashed border-2' : '' }}">
                                <!-- Photo -->
                                <div class="aspect-w-4 aspect-h-3 bg-gray-100">
                                    @if(isset($photo->is_placeholder) && $photo->is_placeholder)
                                        {{-- Placeholder for unuploaded photo --}}
                                        <div class="flex flex-col items-center justify-center h-48 text-gray-400">
                                            <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <p class="text-sm font-medium text-gray-600">Photo Not Uploaded</p>
                                            <p class="text-xs text-red-600 mt-1">⚠ Required Photo</p>
                                        </div>
                                    @elseif($photo->photo_url && !empty(trim($photo->photo_url)))
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
                                             class="photo-preview w-full h-48 object-cover"
                                             onclick="openPhotoModal('{{ $imageUrl }}')"
                                             data-file-id="{{ $fileId }}"
                                             data-original-url="{{ $photo->photo_url }}"
                                             onerror="tryAlternativeUrls(this)"
                                             loading="lazy">
                                    @else
                                        <div class="flex flex-col items-center justify-center h-48 text-gray-400">
                                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <p class="text-xs mt-2">No image</p>
                                            @if(config('app.debug'))
                                                <p class="text-xs text-gray-500">{{ $photo->photo_url ?: 'URL empty' }}</p>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <!-- Photo Info -->
                                <div class="p-4">
                                    <h3 class="font-medium text-gray-900 mb-2">
                                        {{ isset($photo->slot_label) ? $photo->slot_label : $photo->photo_field_name }}
                                    </h3>

                                    @if(isset($photo->is_placeholder) && $photo->is_placeholder)
                                        <!-- Placeholder Status -->
                                        <div class="mb-3">
                                            <div class="w-full inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                📷 Menunggu Upload
                                            </div>
                                        </div>

                                        <!-- Placeholder Actions - Can reject to mark module incomplete -->
                                        @if($moduleAvailable)
                                            <div class="flex space-x-2">
                                                <button disabled
                                                        class="flex-1 bg-gray-300 text-gray-500 px-3 py-1 rounded text-sm cursor-not-allowed"
                                                        title="Tidak dapat approve foto yang belum diupload">
                                                    ✅ Approve
                                                </button>
                                                <button onclick="rejectMissingPhoto('{{ $module }}', '{{ $photo->photo_field_name }}', '{{ $photo->slot_label }}')"
                                                        class="flex-1 bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm"
                                                        title="Reject module karena foto wajib belum diupload">
                                                    ❌ Reject Module
                                                </button>
                                            </div>
                                        @endif
                                    @else
                                        {{-- Real photo info (existing code) --}}
                                        <!-- AI Insights (Advisory Only) -->
                                        @if($photo->ai_last_checked_at)
                                        <div class="mb-3 p-2 {{ $photo->ai_status === 'passed' ? 'bg-blue-50 border border-blue-200' : 'bg-yellow-50 border border-yellow-200' }} rounded">
                                            <div class="flex items-center justify-between text-sm mb-1">
                                                <span class="font-medium {{ $photo->ai_status === 'passed' ? 'text-blue-900' : 'text-yellow-900' }}">
                                                    🤖 AI Insight
                                                </span>
                                                <span class="text-xs {{ $photo->ai_status === 'passed' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800' }} px-2 py-0.5 rounded-full font-medium">
                                                    {{ number_format(($photo->ai_score ?? $photo->ai_confidence_score ?? 0) * 100, 0) }}%
                                                </span>
                                            </div>
                                            @if($photo->ai_notes)
                                                <p class="text-xs {{ $photo->ai_status === 'passed' ? 'text-blue-700' : 'text-yellow-700' }} mt-1">
                                                    {{ $photo->ai_notes }}
                                                </p>
                                            @endif
                                            <p class="text-xs text-gray-500 mt-1 italic">
                                                ℹ️ Advisory only - tracer decision required
                                            </p>
                                        </div>
                                    @endif

                                    <!-- Status -->
                                    <div class="mb-3 space-y-2">
                                        {{-- Approved Status --}}
                                        @if($photo->tracer_approved_at)
                                            <div>
                                                <button type="button"
                                                        onclick="toggleRejectionDetails({{ $photo->id }}, 'approved')"
                                                        class="w-full inline-flex items-center justify-between px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200 transition-colors cursor-pointer text-left">
                                                    <span>✅ Approved by Tracer</span>
                                                    <i id="approved-chevron-{{ $photo->id }}" class="fas fa-chevron-down transition-transform"></i>
                                                </button>
                                                <div id="approved-details-{{ $photo->id }}" class="hidden mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
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

                                        {{-- Pending Status --}}
                                        @if(!$photo->tracer_rejected_at && !$photo->tracer_approved_at)
                                            <div class="w-full inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                ⏳ Pending Review
                                            </div>
                                        @endif

                                        {{-- Tracer Rejection Dropdown --}}
                                        @if($photo->tracer_rejected_at)
                                            <div>
                                                <button type="button"
                                                        onclick="toggleRejectionDetails({{ $photo->id }}, 'tracer')"
                                                        class="w-full inline-flex items-center justify-between px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200 transition-colors cursor-pointer text-left">
                                                    <span class="flex items-center gap-1">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        <span>Rejected by Tracer</span>
                                                    </span>
                                                    <i id="tracer-chevron-{{ $photo->id }}" class="fas fa-chevron-down transition-transform"></i>
                                                </button>
                                                <div id="tracer-details-{{ $photo->id }}" class="hidden mt-2 p-3 bg-red-50 border border-red-200 rounded-lg">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-xs text-gray-600">Rejected at:</span>
                                                        <span class="text-xs font-medium text-red-600">{{ $photo->tracer_rejected_at->format('d/m/Y H:i') }}</span>
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

                                        {{-- CGP Rejection Dropdown --}}
                                        @if($photo->cgp_rejected_at)
                                            <div>
                                                <button type="button"
                                                        onclick="toggleRejectionDetails({{ $photo->id }}, 'cgp')"
                                                        class="w-full inline-flex items-center justify-between px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 hover:bg-orange-200 transition-colors cursor-pointer text-left">
                                                    <span class="flex items-center gap-1">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        <span>Rejected by CGP</span>
                                                    </span>
                                                    <i id="cgp-chevron-{{ $photo->id }}" class="fas fa-chevron-down transition-transform"></i>
                                                </button>
                                                <div id="cgp-details-{{ $photo->id }}" class="hidden mt-2 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-xs text-gray-600">Rejected at:</span>
                                                        <span class="text-xs font-medium text-orange-600">{{ $photo->cgp_rejected_at->format('d/m/Y H:i') }}</span>
                                                    </div>
                                                    @if($photo->cgpUser)
                                                        <div class="flex items-center gap-1 mb-2">
                                                            <i class="fas fa-user text-orange-600 text-xs"></i>
                                                            <span class="text-xs text-orange-700">{{ $photo->cgpUser->name }}</span>
                                                        </div>
                                                    @endif
                                                    @if($photo->cgp_notes)
                                                        <div class="mt-2 pt-2 border-t border-orange-200">
                                                            <p class="text-xs text-gray-600 mb-1 font-medium">Reason:</p>
                                                            <p class="text-xs text-orange-700 bg-white p-2 rounded">{{ $photo->cgp_notes }}</p>
                                                        </div>
                                                    @else
                                                        <p class="text-xs text-gray-500 italic">No reason provided</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Photo Updated Badge --}}
                                        @if($photo->tracer_rejected_at && $photo->updated_at && $photo->updated_at > $photo->tracer_rejected_at)
                                            <div class="w-full inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                📱 Photo Updated - Needs Re-review
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Actions -->
                                    <div class="space-y-2">
                                        <!-- Replace Photo Button (Admin/Super Admin Only) -->
                                        @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                                        <button type="button" onclick="openReplacePhotoModal({{ $photo->id }}, '{{ addslashes($photo->photo_field_name) }}', '{{ addslashes($module) }}')"
                                                class="w-full bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm font-medium transition-colors flex items-center justify-center space-x-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                            </svg>
                                            <span>Replace Photo</span>
                                        </button>
                                        @endif

                                        <!-- Approve/Reject Buttons -->
                                        @if($moduleAvailable && !$photo->tracer_approved_at)
                                            @if($photo->tracer_rejected_at && (!$photo->updated_at || $photo->updated_at <= $photo->tracer_rejected_at))
                                                <!-- Photo is rejected and not updated since rejection -->
                                                <div class="text-center py-2">
                                                    <span class="text-sm text-gray-500">Photo rejected. Please update photo to re-review.</span>
                                                </div>
                                            @else
                                                <!-- Photo is pending or updated after rejection -->
                                                <div class="flex space-x-2">
                                                    <button onclick="approvePhoto({{ $photo->id }})"
                                                            class="flex-1 bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                                                        ✅ Approve
                                                    </button>
                                                    <button onclick="rejectPhoto({{ $photo->id }})"
                                                            class="flex-1 bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                                                        ❌ Reject
                                                    </button>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada foto untuk {{ strtoupper($module) }}</h3>
                    <p class="mt-1 text-sm text-gray-500">Foto akan muncul setelah petugas mengupload</p>
                </div>
            @endif
        </div>
    @endforeach
</div>

<!-- AI Review Loading Modal -->
<div id="aiLoadingModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-8 max-w-md w-full mx-4">
        <div class="text-center">
            <!-- Animated Spinner -->
            <div class="inline-block">
                <svg class="animate-spin h-16 w-16 text-purple-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <!-- Loading Text -->
            <h3 class="text-xl font-bold text-gray-900 mt-4">🤖 AI Review In Progress</h3>
            <p id="aiLoadingText" class="text-gray-600 mt-2">Initializing AI analysis...</p>

            <!-- Progress Bar -->
            <div class="mt-4 w-full bg-gray-200 rounded-full h-2.5">
                <div id="aiProgressBar" class="bg-purple-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>

            <!-- Progress Counter -->
            <p id="aiProgressCounter" class="text-sm text-gray-500 mt-2">0 of 0 photos analyzed</p>

            <!-- Additional Info -->
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-xs text-blue-800">
                    <i class="fas fa-info-circle"></i> AI sedang menganalisis setiap foto. Proses ini mungkin memakan waktu beberapa menit.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Photo Modal -->
<div id="photoModal" class="photo-modal">
    <div class="photo-modal-controls">
        <button id="zoomInBtn" onclick="zoomIn(event)" title="Zoom In (+)">
            <i class="fas fa-search-plus"></i>
        </button>
        <button id="zoomOutBtn" onclick="zoomOut(event)" title="Zoom Out (-)">
            <i class="fas fa-search-minus"></i>
        </button>
        <button id="resetZoomBtn" onclick="resetZoom(event)" title="Reset (0)">
            <i class="fas fa-compress"></i>
        </button>
        <button onclick="closePhotoModal(event)" title="Close (Esc)">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <img id="modalPhoto" src="" alt="">
</div>

<!-- Replace Photo Modal -->
<div id="replacePhotoModal" class="fixed inset-0 bg-black bg-opacity-50 z-[1000] hidden items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4" onclick="event.stopPropagation()">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-4 rounded-t-lg">
            <h3 class="text-lg font-semibold">Replace Photo</h3>
            <p class="text-blue-100 text-sm mt-1" id="replacePhotoFieldName"></p>
        </div>

        <!-- Modal Body -->
        <form id="replacePhotoForm" enctype="multipart/form-data" class="p-6">
            @csrf
            <input type="hidden" name="photo_id" id="replacePhotoId">
            <input type="hidden" name="module_name" id="replaceModuleName">

            <!-- Warning Notice -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h4 class="text-sm font-medium text-yellow-800">Perhatian</h4>
                        <p class="text-xs text-yellow-700 mt-1">Mengganti foto akan mereset status approval dan foto harus direview ulang.</p>
                    </div>
                </div>
            </div>

            <!-- File Upload -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Upload Foto Baru <span class="text-red-500">*</span>
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-blue-400 transition-colors">
                    <input type="file" name="new_photo" id="replacePhotoFile" accept="image/*,.pdf"
                           class="hidden" onchange="handleFileSelect(this)">
                    <label for="replacePhotoFile" class="cursor-pointer">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <span class="text-sm text-gray-600">Klik untuk pilih file</span>
                        <p class="text-xs text-gray-500 mt-1">JPG, PNG, PDF (Max 5MB)</p>
                    </label>
                </div>
                <div id="selectedFileName" class="text-sm text-gray-600 mt-2 hidden"></div>
            </div>

            <!-- AI Precheck Option -->
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="ai_precheck" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Jalankan AI Precheck setelah upload</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-6">AI akan otomatis mengecek kualitas foto yang diupload</p>
            </div>

            <!-- Notes -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Catatan Penggantian</label>
                <textarea name="replacement_notes" rows="3"
                          placeholder="Alasan penggantian foto..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="flex space-x-3">
                <button type="button" onclick="closeReplacePhotoModal()"
                        class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                    Batal
                </button>
                <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    <span id="replacePhotoSubmitText">Replace Photo</span>
                    <span id="replacePhotoLoadingText" class="hidden">
                        <svg class="animate-spin h-4 w-4 inline-block" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Uploading...
                    </span>
                </button>
            </div>
        </form>
    </div>
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
                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                    Cancel
                </button>
                <button type="submit" id="confirmButton" 
                        class="px-4 py-2 rounded-lg text-white">
                    Confirm
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Lazy loading images using Intersection Observer
document.addEventListener('DOMContentLoaded', function() {
    const lazyImages = document.querySelectorAll('.lazy-image');

    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.getAttribute('data-src');

                    if (src) {
                        img.src = src;
                        img.classList.add('loaded');
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                }
            });
        }, {
            rootMargin: '50px' // Start loading 50px before entering viewport
        });

        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for browsers without Intersection Observer
        lazyImages.forEach(img => {
            const src = img.getAttribute('data-src');
            if (src) {
                img.src = src;
                img.classList.add('loaded');
            }
        });
    }
});

// Google Drive URL fallback function - MUST BE DEFINED FIRST (called from HTML onerror)
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

// Global variables
let currentPhotoId = null;
let currentAction = null;
let currentModule = null;
const reffId = '{{ $customer->reff_id_pelanggan }}';

// Photo modal zoom state
let zoomLevel = 1;
let isDragging = false;
let startX, startY, translateX = 0, translateY = 0;

// Photo modal functions
function openPhotoModal(src) {
    const img = document.getElementById('modalPhoto');
    img.src = src;
    document.getElementById('photoModal').style.display = 'block';

    // Reset zoom
    zoomLevel = 1;
    translateX = 0;
    translateY = 0;
    updateImageTransform();
    img.classList.remove('zoomed');
}

function closePhotoModal(event) {
    if (event) event.stopPropagation();
    document.getElementById('photoModal').style.display = 'none';

    // Reset state
    zoomLevel = 1;
    translateX = 0;
    translateY = 0;
    isDragging = false;
}

// Zoom functions
function zoomIn(event) {
    event.stopPropagation();
    zoomLevel = Math.min(zoomLevel + 0.5, 5); // Max 5x zoom
    updateImageTransform(true); // Enable transition for smooth zoom
    updateZoomClass();
}

function zoomOut(event) {
    event.stopPropagation();
    zoomLevel = Math.max(zoomLevel - 0.5, 1); // Min 1x zoom
    if (zoomLevel === 1) {
        translateX = 0;
        translateY = 0;
    }
    updateImageTransform(true); // Enable transition for smooth zoom
    updateZoomClass();
}

function resetZoom(event) {
    event.stopPropagation();
    zoomLevel = 1;
    translateX = 0;
    translateY = 0;
    updateImageTransform(true); // Enable transition for smooth zoom
    updateZoomClass();
}

function updateImageTransform(withTransition = false) {
    const img = document.getElementById('modalPhoto');

    // Add transition class only for zoom operations, not for drag
    if (withTransition) {
        img.classList.add('zoom-transition');
        // Remove transition after animation completes to avoid lag during drag
        setTimeout(() => {
            img.classList.remove('zoom-transition');
        }, 200); // Match transition duration (0.2s)
    }

    img.style.transform = `translate(calc(-50% + ${translateX}px), calc(-50% + ${translateY}px)) scale(${zoomLevel})`;
}

function updateZoomClass() {
    const img = document.getElementById('modalPhoto');
    if (zoomLevel > 1) {
        img.classList.add('zoomed');
    } else {
        img.classList.remove('zoomed');
    }
}

// Image dragging and zoom event listeners
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('photoModal');
    const img = document.getElementById('modalPhoto');

    // Click on modal background to close
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closePhotoModal();
        }
    });

    // Prevent image click from closing modal
    img.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Mouse wheel zoom with cursor-based zoom center
    modal.addEventListener('wheel', function(e) {
        if (modal.style.display === 'block') {
            e.preventDefault();

            const oldZoom = zoomLevel;

            // Calculate new zoom level
            if (e.deltaY < 0) {
                zoomLevel = Math.min(zoomLevel + 0.2, 5);
            } else {
                zoomLevel = Math.max(zoomLevel - 0.2, 1);
            }

            // If zooming to 1x, reset position
            if (zoomLevel === 1) {
                translateX = 0;
                translateY = 0;
            } else if (oldZoom !== zoomLevel) {
                // Calculate cursor position relative to modal center
                const rect = modal.getBoundingClientRect();
                const cursorX = e.clientX - rect.left - rect.width / 2;
                const cursorY = e.clientY - rect.top - rect.height / 2;

                // Adjust translation to keep cursor position stable
                const zoomRatio = zoomLevel / oldZoom;
                translateX = cursorX + (translateX - cursorX) * zoomRatio;
                translateY = cursorY + (translateY - cursorY) * zoomRatio;
            }

            updateImageTransform(true); // Enable transition for smooth wheel zoom
            updateZoomClass();
        }
    });

    // Drag to pan when zoomed
    img.addEventListener('mousedown', function(e) {
        if (zoomLevel > 1) {
            isDragging = true;
            startX = e.clientX - translateX;
            startY = e.clientY - translateY;
            e.preventDefault();
        }
    });

    document.addEventListener('mousemove', function(e) {
        if (isDragging && zoomLevel > 1) {
            translateX = e.clientX - startX;
            translateY = e.clientY - startY;
            updateImageTransform();
        }
    });

    document.addEventListener('mouseup', function() {
        isDragging = false;
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (modal.style.display === 'block') {
            if (e.key === 'Escape') {
                closePhotoModal();
            } else if (e.key === '+' || e.key === '=') {
                zoomIn(e);
            } else if (e.key === '-') {
                zoomOut(e);
            } else if (e.key === '0') {
                resetZoom(e);
            }
        }
    });
});

// Notes modal functions
function openNotesModal(title, action, photoId = null, module = null) {
    currentPhotoId = photoId;
    currentAction = action;
    currentModule = module;
    
    document.getElementById('notesModalTitle').textContent = title;
    document.getElementById('reviewNotes').value = '';
    
    const confirmButton = document.getElementById('confirmButton');
    if (action === 'approve') {
        confirmButton.className = 'px-4 py-2 rounded-lg text-white bg-green-600 hover:bg-green-700';
        confirmButton.textContent = 'Approve';
    } else if (action === 'reject') {
        confirmButton.className = 'px-4 py-2 rounded-lg text-white bg-red-600 hover:bg-red-700';
        confirmButton.textContent = 'Reject';
    } else if (action === 'approveModule') {
        confirmButton.className = 'px-4 py-2 rounded-lg text-white bg-green-600 hover:bg-green-700';
        confirmButton.textContent = 'Approve All';
    }
    
    document.getElementById('notesModal').classList.remove('hidden');
}

function closeNotesModal() {
    document.getElementById('notesModal').classList.add('hidden');
    currentPhotoId = null;
    currentAction = null;
    currentModule = null;
}

// Photo actions
function approvePhoto(photoId) {
    openNotesModal('Approve Photo', 'approve', photoId);
}

function rejectPhoto(photoId) {
    openNotesModal('Reject Photo', 'reject', photoId);
}

function approveModule(module) {
    openNotesModal(`Approve All ${module.toUpperCase()} Photos`, 'approveModule', null, module);
}

// AI Review Loading Functions
function showAILoadingModal(module, estimatedPhotos = 5) {
    const modal = document.getElementById('aiLoadingModal');
    const loadingText = document.getElementById('aiLoadingText');
    const progressBar = document.getElementById('aiProgressBar');
    const progressCounter = document.getElementById('aiProgressCounter');

    modal.classList.remove('hidden');
    loadingText.textContent = `Analyzing ${module.toUpperCase()} photos...`;
    progressBar.style.width = '0%';
    progressCounter.textContent = `0 of ${estimatedPhotos} photos analyzed`;

    // Simulate progress (since we don't have real-time updates from backend)
    let progress = 0;
    const progressInterval = setInterval(() => {
        if (progress < 90) {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;

            progressBar.style.width = progress + '%';
            const currentPhoto = Math.floor((progress / 100) * estimatedPhotos);
            progressCounter.textContent = `~${currentPhoto} of ${estimatedPhotos} photos analyzed`;
        }
    }, 800);

    // Store interval ID so we can clear it later
    window.aiProgressInterval = progressInterval;
}

function hideAILoadingModal() {
    const modal = document.getElementById('aiLoadingModal');
    modal.classList.add('hidden');

    // Clear progress interval
    if (window.aiProgressInterval) {
        clearInterval(window.aiProgressInterval);
        window.aiProgressInterval = null;
    }
}

function updateAIProgress(current, total) {
    const progressBar = document.getElementById('aiProgressBar');
    const progressCounter = document.getElementById('aiProgressCounter');

    const percentage = (current / total) * 100;
    progressBar.style.width = percentage + '%';
    progressCounter.textContent = `${current} of ${total} photos analyzed`;
}

// AI Review
function aiReviewModule(module) {
    if (!confirm(`Run AI review for all ${module.toUpperCase()} photos?\n\nThis will analyze each photo and provide AI insights (advisory only).`)) {
        return;
    }

    // Get button and disable it
    const button = document.getElementById(`aiReviewBtn-${module}`);
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.classList.add('opacity-75', 'cursor-not-allowed');
    button.innerHTML = '<span class="flex items-center"><svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Processing...</span>';

    // Estimate photo count based on module
    const estimatedPhotos = {
        'sk': 5,
        'sr': 6,
        'gas_in': 4
    }[module] || 5;

    // Show loading modal
    showAILoadingModal(module, estimatedPhotos);

    const formData = new FormData();
    formData.append('reff_id', reffId);
    formData.append('module', module);
    formData.append('_token', '{{ csrf_token() }}');

    fetch('{{ route("approvals.tracer.ai-review") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Complete the progress bar
        if (window.aiProgressInterval) {
            clearInterval(window.aiProgressInterval);
        }
        document.getElementById('aiProgressBar').style.width = '100%';

        // Wait a bit to show 100% before hiding
        setTimeout(() => {
            hideAILoadingModal();

            // Re-enable button
            button.disabled = false;
            button.classList.remove('opacity-75', 'cursor-not-allowed');
            button.innerHTML = originalContent;

            if (data.success) {
                const processed = data.data?.processed || 0;
                const total = data.data?.total_photos || 0;

                // Show success message
                const message = `AI Review Completed Successfully!\n\n` +
                               `✅ Analyzed: ${processed}/${total} photos\n` +
                               `📊 Module: ${module.toUpperCase()}\n\n` +
                               `ℹ️ AI insights are now visible below each photo.\n` +
                               `Remember: AI recommendations are advisory only.\n` +
                               `Your decision as tracer is final.`;

                alert(message);
                location.reload();
            } else {
                alert('❌ AI Review Failed\n\n' + (data.message || 'Unknown error occurred'));
            }
        }, 500);
    })
    .catch(error => {
        console.error('Error:', error);
        hideAILoadingModal();

        // Re-enable button
        button.disabled = false;
        button.classList.remove('opacity-75', 'cursor-not-allowed');
        button.innerHTML = originalContent;

        alert('❌ An error occurred during AI review\n\n' + error.message);
    });
}

// Form submission
document.getElementById('notesForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const notes = document.getElementById('reviewNotes').value;
    const formData = new FormData();
    formData.append('notes', notes);
    formData.append('_token', '{{ csrf_token() }}');

    let url = '';

    if (currentAction === 'approveModule') {
        url = '{{ route("approvals.tracer.approve-module") }}';
        formData.append('reff_id', reffId);
        formData.append('module', currentModule);
    } else if (currentAction === 'rejectModule') {
        url = '{{ route("approvals.tracer.reject-module") }}';
        formData.append('reff_id', reffId);
        formData.append('module', currentModule);
    } else {
        url = '{{ route("approvals.tracer.approve-photo") }}';
        formData.append('photo_id', currentPhotoId);
        formData.append('action', currentAction);
    }

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        closeNotesModal();
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
        closeNotesModal();
    });
});

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

// Replace Photo Modal Functions
function openReplacePhotoModal(photoId, fieldName, moduleName) {
    document.getElementById('replacePhotoId').value = photoId;
    document.getElementById('replaceModuleName').value = moduleName;
    document.getElementById('replacePhotoFieldName').textContent = formatFieldName(fieldName);
    document.getElementById('replacePhotoModal').classList.remove('hidden');
    document.getElementById('replacePhotoModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeReplacePhotoModal() {
    document.getElementById('replacePhotoModal').classList.add('hidden');
    document.getElementById('replacePhotoModal').classList.remove('flex');
    document.body.style.overflow = 'auto';
    document.getElementById('replacePhotoForm').reset();
    document.getElementById('selectedFileName').classList.add('hidden');
}

function formatFieldName(fieldName) {
    return fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function handleFileSelect(input) {
    const fileName = input.files[0]?.name;
    const fileNameDisplay = document.getElementById('selectedFileName');
    if (fileName) {
        fileNameDisplay.textContent = '📎 ' + fileName;
        fileNameDisplay.classList.remove('hidden');
    } else {
        fileNameDisplay.classList.add('hidden');
    }
}

// Handle Replace Photo Form Submission
document.getElementById('replacePhotoForm').addEventListener('submit', function(e) {
    e.preventDefault();

    // Validate file is selected
    const fileInput = document.getElementById('replacePhotoFile');
    if (!fileInput.files || fileInput.files.length === 0) {
        alert('Silakan pilih file foto terlebih dahulu');
        return;
    }

    // Validate file size (5MB max)
    const file = fileInput.files[0];
    const maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if (file.size > maxSize) {
        alert('Ukuran file terlalu besar. Maksimal 5MB');
        return;
    }

    const submitBtn = document.querySelector('#replacePhotoForm button[type="submit"]');
    const submitText = document.getElementById('replacePhotoSubmitText');
    const loadingText = document.getElementById('replacePhotoLoadingText');

    // Show loading state
    submitBtn.disabled = true;
    submitText.classList.add('hidden');
    loadingText.classList.remove('hidden');

    const formData = new FormData(this);

    fetch('{{ route("approvals.tracer.replace-photo") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert('Foto berhasil diganti! Halaman akan di-reload.');
            closeReplacePhotoModal();
            location.reload();
        } else {
            // Show error message
            alert('Error: ' + (data.message || 'Gagal mengganti foto'));
            submitBtn.disabled = false;
            submitText.classList.remove('hidden');
            loadingText.classList.add('hidden');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat mengganti foto');
        submitBtn.disabled = false;
        submitText.classList.remove('hidden');
        loadingText.classList.add('hidden');
    });
});

// Reject module due to missing photo
function rejectMissingPhoto(module, photoFieldName, photoLabel) {
    const defaultMessage = `Foto ${photoLabel} belum diupload. Mohon upload foto wajib terlebih dahulu sebelum submit untuk approval.`;

    // Open notes modal with pre-filled message
    currentModule = module;
    currentAction = 'rejectModule';

    document.getElementById('notesModalTitle').textContent = `Reject Module ${module.toUpperCase()} - Foto Belum Lengkap`;
    document.getElementById('reviewNotes').value = defaultMessage;

    const confirmButton = document.getElementById('confirmButton');
    confirmButton.className = 'px-4 py-2 rounded-lg text-white bg-red-600 hover:bg-red-700';
    confirmButton.textContent = 'Reject Module';

    document.getElementById('notesModal').classList.remove('hidden');
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePhotoModal();
        closeNotesModal();
        closeReplacePhotoModal();
    }
});
</script>
@endpush