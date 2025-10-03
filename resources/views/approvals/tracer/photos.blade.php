@extends('layouts.app')

@section('title', 'Tracer - Photo Review')

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
                            <button onclick="aiReviewModule('{{ $module }}')"
                                    class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium text-sm">
                                🤖 AI Review
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
                                            // Convert Google Drive URL to direct image URL
                                            $imageUrl = $photo->photo_url;
                                            if (str_contains($imageUrl, 'drive.google.com')) {
                                                if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $imageUrl, $matches)) {
                                                    $fileId = $matches[1];
                                                    // Try multiple formats - Google keeps changing their direct image URLs
                                                    $imageUrl = "https://lh3.googleusercontent.com/d/{$fileId}";
                                                    // Alternative fallbacks:
                                                    // $imageUrl = "https://drive.google.com/uc?export=view&id={$fileId}";
                                                    // $imageUrl = "https://drive.google.com/thumbnail?id={$fileId}&sz=w400";
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
                                        <!-- AI Results -->
                                        @if($photo->ai_approved_at)
                                        <div class="mb-3 p-2 bg-blue-50 rounded">
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="font-medium text-blue-900">AI Analysis</span>
                                                <span class="text-blue-600">{{ number_format($photo->ai_confidence_score ?? 0, 1) }}%</span>
                                            </div>
                                            @if($photo->ai_notes)
                                                <p class="text-xs text-blue-700 mt-1">{{ $photo->ai_notes }}</p>
                                            @endif
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

// AI Review
function aiReviewModule(module) {
    if (confirm(`Run AI review for all ${module.toUpperCase()} photos?`)) {
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
            if (data.success) {
                alert('AI Review completed successfully!');
                location.reload();
            } else {
                alert('AI Review failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred during AI review');
        });
    }
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
</script>
@endpush