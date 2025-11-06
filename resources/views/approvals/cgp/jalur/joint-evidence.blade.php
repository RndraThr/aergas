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
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-cyan-50">
    <div class="container mx-auto px-4 py-6">
        {{-- Breadcrumb --}}
        <div class="mb-4">
            <a href="{{ route('approvals.cgp.jalur.lines', $cluster->id ?? 1) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-50 transition shadow-sm group">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span class="font-semibold">Kembali ke Lines</span>
            </a>
        </div>

        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow-xl border-2 border-blue-200 p-6 mb-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="mb-1">
                            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-800 to-cyan-600 bg-clip-text text-transparent">
                                {{ $joint->nomor_joint }}
                            </h1>
                        </div>
                        <p class="text-gray-600 font-medium">
                            {{ $cluster->nama_cluster ?? 'Cluster' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Connection Info --}}
            <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-4 border-2 border-blue-200 mb-4">
                <div class="flex items-center justify-center gap-3 text-lg font-semibold text-gray-900">
                    <span class="text-blue-700">{{ $joint->joint_line_from }}</span>
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                    <span class="text-blue-700">{{ $joint->joint_line_to }}</span>
                    @if($joint->joint_line_optional)
                        <span class="text-blue-500">+</span>
                        <span class="text-blue-700">{{ $joint->joint_line_optional }}</span>
                    @endif
                </div>
            </div>

            {{-- Stats Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div class="bg-cyan-50 rounded-lg p-3 border border-cyan-200">
                    <div class="text-xs font-medium text-cyan-600 mb-1">Fitting Type</div>
                    <div class="text-lg font-bold text-cyan-900">{{ $joint->fittingType->nama_fitting ?? '-' }}</div>
                </div>
                <div class="bg-blue-50 rounded-lg p-3 border border-blue-200">
                    <div class="text-xs font-medium text-blue-600 mb-1">Tipe Penyambungan</div>
                    <div class="text-lg font-bold text-blue-900">{{ $joint->tipe_penyambungan ?? '-' }}</div>
                </div>
                <div class="bg-indigo-50 rounded-lg p-3 border border-indigo-200">
                    <div class="text-xs font-medium text-indigo-600 mb-1">Total Photos</div>
                    <div class="text-lg font-bold text-indigo-900">{{ $jointStats['total'] }}</div>
                </div>
                <div class="bg-orange-50 rounded-lg p-3 border border-orange-200">
                    <div class="text-xs font-medium text-orange-600 mb-1">⏳ Pending</div>
                    <div class="text-lg font-bold text-orange-900">{{ $jointStats['pending'] }}</div>
                </div>
                <div class="bg-green-50 rounded-lg p-3 border border-green-200">
                    <div class="text-xs font-medium text-green-600 mb-1">✓ Approved</div>
                    <div class="text-lg font-bold text-green-900">{{ $jointStats['approved'] }}</div>
                </div>
            </div>
        </div>

        {{-- Photos Section --}}
        @php
            $photos = $joint->photoApprovals;
            $hasPhotos = $photos->count() > 0;
        @endphp

        @if($hasPhotos)
            {{-- Joint Card --}}
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                {{-- Card Header --}}
                <div class="bg-gradient-to-r from-blue-600 to-cyan-600 text-white p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-2xl font-bold mb-2">
                                📅 {{ $joint->tanggal_joint ? $joint->tanggal_joint->format('d F Y') : 'No Date' }}
                            </h3>
                            <div class="flex items-center gap-2 text-blue-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <span class="text-sm">Joint #{{ $joint->id }}</span>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="bg-white/20 backdrop-blur-sm px-4 py-2 rounded-lg">
                                <div class="text-xs text-blue-100">Total Photos</div>
                                <div class="text-2xl font-bold">{{ $jointStats['total'] }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Info Grid --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 bg-white/10 backdrop-blur-sm rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-xs text-blue-100">Fitting Type</div>
                            <div class="text-sm font-semibold">{{ $joint->fittingType->nama_fitting ?? '-' }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-blue-100">✅ Approved</div>
                            <div class="text-sm font-semibold">{{ $jointStats['approved'] }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-blue-100">⏳ Pending</div>
                            <div class="text-sm font-semibold">{{ $jointStats['pending'] }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-blue-100">❌ Rejected</div>
                            <div class="text-sm font-semibold">{{ $jointStats['rejected'] }}</div>
                        </div>
                    </div>
                </div>

                {{-- Photos Grid - 3 Columns --}}
                <div class="p-6">
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

                                                // Use Google's high-quality image URL (lh3.googleusercontent.com)
                                                if ($fileId) {
                                                    $imageUrl = "https://lh3.googleusercontent.com/d/{$fileId}";
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
                </div>
            </div>
        @else
            {{-- Empty State --}}
            <div class="bg-white rounded-xl shadow-lg p-12 text-center border-2 border-gray-100">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-700 mb-2">Belum Ada Evidence</h3>
                <p class="text-gray-500">Belum ada photo evidence untuk joint ini</p>
            </div>
        @endif

        {{-- Summary Card --}}
        @if($hasPhotos)
            <div class="sticky bottom-4 mt-6 z-40">
                <div class="bg-white rounded-xl shadow-2xl border-2 border-blue-300 p-6">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                        {{-- Detail Approval (Left) --}}
                        <div class="flex flex-col gap-2">
                            <div class="text-sm font-semibold text-gray-700">Detail Approval</div>
                            <div class="flex flex-wrap gap-4">
                                <div class="text-center">
                                    <div class="text-lg font-bold text-gray-800">{{ $jointStats['total'] }}</div>
                                    <div class="text-xs text-gray-600">Total</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-green-600">{{ $jointStats['approved'] }}</div>
                                    <div class="text-xs text-gray-600">Approved</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-orange-600">{{ $jointStats['pending'] }}</div>
                                    <div class="text-xs text-gray-600">Pending</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-red-600">{{ $jointStats['rejected'] }}</div>
                                    <div class="text-xs text-gray-600">Rejected</div>
                                </div>
                            </div>
                        </div>

                        {{-- Approval Progress (Center) --}}
                        <div class="flex flex-col items-center gap-2">
                            <div class="text-sm font-semibold text-gray-700">Approval Progress</div>
                            <div class="flex items-center gap-2">
                                <div class="w-48 bg-gray-200 rounded-full h-3 overflow-hidden">
                                    <div class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-500"
                                         style="width: {{ $jointStats['total'] > 0 ? round(($jointStats['approved'] / $jointStats['total']) * 100) : 0 }}%">
                                    </div>
                                </div>
                                <span class="text-sm font-bold text-gray-700 min-w-[3rem]">
                                    {{ $jointStats['total'] > 0 ? round(($jointStats['approved'] / $jointStats['total']) * 100) : 0 }}%
                                </span>
                            </div>
                        </div>

                        {{-- Approve All Button (Right) --}}
                        @if($jointStats['pending'] > 0)
                            <button
                                onclick="approveAllPending()"
                                class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition-all flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Approve All
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
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

{{-- Revert Modal --}}
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
                            <li>Change photo status from <code class="bg-yellow-100 px-1 rounded">cgp_approved</code> to <code class="bg-yellow-100 px-1 rounded">tracer_approved</code></li>
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

{{-- Replace Photo Modal - Modern Design --}}
<div id="replacePhotoModal" class="hidden fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">Replace Photo</h3>
                        <p class="text-sm text-blue-100 mt-0.5">
                            <span id="replacePhotoName" class="font-semibold"></span>
                        </p>
                    </div>
                </div>
                <button onclick="closeReplacePhotoModal()" class="text-white hover:bg-white hover:bg-opacity-20 p-2 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Content --}}
        <div class="p-6 max-h-[calc(90vh-200px)] overflow-y-auto">
            {{-- Warning Message --}}
            <div id="replaceWarningMessage" class="hidden mb-6 p-4 bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-300 rounded-xl">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-yellow-400 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-yellow-900 mb-1">⚠️ Perhatian!</p>
                        <p id="replaceWarningText" class="text-sm text-yellow-800"></p>
                    </div>
                </div>
            </div>

            {{-- Upload & Preview Combined Section --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">
                    Upload Foto Baru <span class="text-red-500">*</span>
                </label>

                {{-- Combined Upload/Preview Area --}}
                <div class="relative">
                    <input
                        type="file"
                        id="replacePhotoFile"
                        accept="image/jpeg,image/jpg,image/png"
                        class="hidden"
                        onchange="previewReplacePhoto(event)">

                    {{-- Upload Area (shown when no file selected) --}}
                    <label id="uploadArea" for="replacePhotoFile" class="flex flex-col items-center justify-center w-full min-h-[20rem] border-3 border-dashed border-gray-300 rounded-xl cursor-pointer bg-gradient-to-br from-gray-50 to-blue-50 hover:from-blue-50 hover:to-indigo-50 transition-all group">
                        <div class="flex flex-col items-center justify-center py-8">
                            <div class="w-20 h-20 mb-4 bg-blue-100 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <p class="mb-2 text-base font-semibold text-gray-700">
                                <span class="text-blue-600">Click to upload</span> or drag and drop
                            </p>
                            <p class="text-sm text-gray-500">PNG, JPG, JPEG (Max: 10MB)</p>
                        </div>
                    </label>

                    {{-- Preview Area (shown when file selected) --}}
                    <div id="previewArea" class="hidden">
                        <div class="relative group">
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl blur opacity-25 group-hover:opacity-40 transition"></div>
                            <div class="relative bg-white rounded-xl p-3 border-2 border-blue-300">
                                <img id="replacePhotoPreviewImage" src="" alt="Preview" class="w-full h-96 object-contain bg-gradient-to-br from-gray-50 to-blue-50 rounded-lg">

                                {{-- Action Buttons Overlay --}}
                                <div class="absolute top-6 right-6 flex gap-2">
                                    <button type="button" onclick="clearPreview()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg shadow-lg transition-all flex items-center gap-2 font-medium">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Hapus
                                    </button>
                                    <label for="replacePhotoFile" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg transition-all flex items-center gap-2 font-medium cursor-pointer">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Ganti
                                    </label>
                                </div>

                                {{-- File Info --}}
                                <div class="absolute bottom-6 left-6 right-6">
                                    <div class="bg-white bg-opacity-95 backdrop-blur-sm rounded-lg p-3 shadow-lg">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-gray-900">Foto siap di-upload</p>
                                                    <p id="fileInfo" class="text-xs text-gray-600"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex gap-3">
            <button onclick="closeReplacePhotoModal()"
                    class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 transition font-semibold">
                <span class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Cancel
                </span>
            </button>
            <button onclick="confirmReplacePhoto()"
                    id="confirmReplaceBtn"
                    class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition font-semibold shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:shadow-lg"
                    disabled>
                <span class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Upload & Replace
                </span>
            </button>
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
    const img = document.getElementById('modalImage');
    img.style.transform = 'translate(-50%, -50%) scale(1)';
    img.classList.remove('zoomed');
}

function applyZoom() {
    const img = document.getElementById('modalImage');
    img.style.transform = `translate(calc(-50% + ${currentX}px), calc(-50% + ${currentY}px)) scale(${currentScale})`;
    if (currentScale > 1) {
        img.classList.add('zoomed');
    } else {
        img.classList.remove('zoomed');
        currentX = 0;
        currentY = 0;
    }
}

// Toggle Status Details
function toggleStatusDetails(photoId, statusType) {
    const details = document.getElementById(`${statusType}-details-${photoId}`);
    const chevron = document.getElementById(`${statusType}-chevron-${photoId}`);

    if (details.classList.contains('hidden')) {
        details.classList.remove('hidden');
        chevron.classList.add('rotate-180');
    } else {
        details.classList.add('hidden');
        chevron.classList.remove('rotate-180');
    }
}

// Approve Modal Functions
function openApproveModal(photoId) {
    currentPhotoId = photoId;
    document.getElementById('approveModal').classList.remove('hidden');
    document.getElementById('approveNotes').value = '';
    document.body.style.overflow = 'hidden';
}

function closeApproveModal() {
    document.getElementById('approveModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    currentPhotoId = null;
}

function confirmApprove() {
    if (!currentPhotoId) return;

    const photoId = currentPhotoId;  // Save before modal close
    const notes = document.getElementById('approveNotes').value.trim();

    closeApproveModal();
    showLoading('Memproses approval...');

    safeFetchJSON('{{ route("approvals.cgp.jalur.approve-photo") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            photo_id: photoId,
            notes: notes || 'Approved by tracer'
        })
    })
    .then(data => {
        closeLoading();
        if (data.success) {
            showSuccessToast('Berhasil disetujui');
            setTimeout(() => location.reload(), 1000);
        } else {
            showErrorToast(data.message || 'Gagal approve photo');
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
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectNotes').value = '';
    document.body.style.overflow = 'hidden';
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
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

    closeRejectModal();
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
            showErrorToast(data.message || 'Gagal reject photo');
        }
    })
    .catch(error => {
        closeLoading();
        showErrorToast('Gagal memproses, silakan coba lagi');
    });
}

// Revert Modal Functions
let currentRevertPhotoId = null;

function openRevertModal(photoId, photoName) {
    currentRevertPhotoId = photoId;
    document.getElementById('revertPhotoName').textContent = photoName || 'Unknown Photo';
    document.getElementById('revertModal').classList.remove('hidden');
    document.getElementById('revertReason').value = '';
    document.body.style.overflow = 'hidden';
}

function closeRevertModal() {
    document.getElementById('revertModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    currentRevertPhotoId = null;
    document.getElementById('revertReason').value = '';
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

    safeFetchJSON('{{ route("approvals.cgp.jalur.revert-photo") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            photo_id: photoId,
            notes: reason
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

// Approve All Pending
async function approveAllPending() {
    const confirmed = await showConfirm({
        title: 'Konfirmasi Bulk Approval',
        text: 'Approve semua photo yang pending?',
        confirmText: 'Ya, Approve Semua',
        cancelText: 'Batal'
    });

    if (!confirmed) return;

    showLoading('Memproses bulk approval...');

    try {
        // Get all pending photo IDs (tracer_approved means pending for CGP)
        const pendingPhotos = @json($joint->photoApprovals->whereIn('photo_status', ['tracer_approved', 'cgp_pending'])->pluck('id'));

        let successCount = 0;
        let failCount = 0;

        for (const photoId of pendingPhotos) {
            try {
                const data = await safeFetchJSON('{{ route("approvals.cgp.jalur.approve-photo") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        photo_id: photoId,
                        notes: 'Approved via Approve All'
                    })
                });

                if (data.success) {
                    successCount++;
                } else {
                    failCount++;
                }
            } catch (error) {
                failCount++;
            }
        }

        closeLoading();

        if (successCount > 0) {
            showSuccessToast(`${successCount} photo berhasil disetujui${failCount > 0 ? `, ${failCount} gagal` : ''}`);
            setTimeout(() => location.reload(), 1000);
        } else {
            showErrorToast('Tidak ada photo yang berhasil di-approve');
        }
    } catch (error) {
        closeLoading();
        console.error('Error:', error);
        showErrorToast('Terjadi kesalahan saat approve all');
    }
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
    const uploadArea = document.getElementById('uploadArea');
    const previewArea = document.getElementById('previewArea');
    const warningMessage = document.getElementById('replaceWarningMessage');
    const warningText = document.getElementById('replaceWarningText');
    const confirmBtn = document.getElementById('confirmReplaceBtn');

    photoNameSpan.textContent = photoName;
    fileInput.value = '';

    // Reset to upload view
    uploadArea.classList.remove('hidden');
    previewArea.classList.add('hidden');
    confirmBtn.disabled = true;

    // Show warning for approved photos
    if (photoStatus === 'tracer_approved' || photoStatus === 'cgp_approved') {
        warningMessage.classList.remove('hidden');
        if (photoStatus === 'cgp_approved') {
            warningText.textContent = 'Photo ini sudah di-approve oleh CGP. Replace akan reset status ke tracer_approved dan perlu approval ulang dari CGP.';
        } else {
            warningText.textContent = 'Photo ini sudah di-approve oleh Tracer (ready for CGP). Replace akan reset status ke pending dan perlu approval ulang dari Tracer dan CGP.';
        }
    } else {
        warningMessage.classList.add('hidden');
    }

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeReplacePhotoModal() {
    const modal = document.getElementById('replacePhotoModal');
    const fileInput = document.getElementById('replacePhotoFile');
    const uploadArea = document.getElementById('uploadArea');
    const previewArea = document.getElementById('previewArea');
    const confirmBtn = document.getElementById('confirmReplaceBtn');

    // Hide modal
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';

    // Reset state
    fileInput.value = '';
    uploadArea.classList.remove('hidden');
    previewArea.classList.add('hidden');
    confirmBtn.disabled = true;

    // Clear current photo tracking
    currentReplacePhotoId = null;
    currentReplacePhotoName = '';
}

function previewReplacePhoto(event) {
    const file = event.target.files[0];
    const uploadArea = document.getElementById('uploadArea');
    const previewArea = document.getElementById('previewArea');
    const previewImage = document.getElementById('replacePhotoPreviewImage');
    const fileInfo = document.getElementById('fileInfo');
    const confirmBtn = document.getElementById('confirmReplaceBtn');

    if (file) {
        // Validate file size (10MB max)
        if (file.size > 10 * 1024 * 1024) {
            showErrorToast('Ukuran file maksimal 10MB');
            event.target.value = '';
            return;
        }

        // Validate file type
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!validTypes.includes(file.type)) {
            showErrorToast('Format file harus JPG, JPEG, atau PNG');
            event.target.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            // Update preview image
            previewImage.src = e.target.result;

            // Update file info
            const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
            fileInfo.textContent = `${file.name} (${fileSizeMB} MB)`;

            // Toggle visibility
            uploadArea.classList.add('hidden');
            previewArea.classList.remove('hidden');

            // Enable confirm button
            confirmBtn.disabled = false;
        };
        reader.readAsDataURL(file);
    } else {
        // No file selected - show upload area
        uploadArea.classList.remove('hidden');
        previewArea.classList.add('hidden');
        confirmBtn.disabled = true;
    }
}

function clearPreview() {
    const fileInput = document.getElementById('replacePhotoFile');
    const uploadArea = document.getElementById('uploadArea');
    const previewArea = document.getElementById('previewArea');
    const confirmBtn = document.getElementById('confirmReplaceBtn');

    // Clear file input
    fileInput.value = '';

    // Toggle visibility back to upload area
    uploadArea.classList.remove('hidden');
    previewArea.classList.add('hidden');

    // Disable confirm button
    confirmBtn.disabled = true;
}

function confirmReplacePhoto() {
    const fileInput = document.getElementById('replacePhotoFile');
    const file = fileInput.files[0];

    if (!file) {
        showWarningToast('Silakan pilih foto terlebih dahulu');
        return;
    }

    const formData = new FormData();
    formData.append('photo_id', currentReplacePhotoId);
    formData.append('photo', file);
    formData.append('_token', '{{ csrf_token() }}');

    closeReplacePhotoModal();
    showLoading('Mengupload foto baru...');

    safeFetchJSON('{{ route("approvals.cgp.jalur.replace-photo") }}', {
        method: 'POST',
        body: formData
    })
    .then(data => {
        closeLoading();
        if (data.success) {
            showSuccessToast('Foto berhasil diganti');
            setTimeout(() => location.reload(), 1000);
        } else {
            showErrorToast(data.message || 'Gagal replace photo');
        }
    })
    .catch(error => {
        closeLoading();
        showErrorToast('Gagal memproses, silakan coba lagi');
    });
}

// Document Ready
document.addEventListener('DOMContentLoaded', function() {
    // Photo modal drag functionality
    const modalImg = document.getElementById('modalImage');

    modalImg.addEventListener('mousedown', function(e) {
        if (currentScale > 1) {
            isDragging = true;
            startX = e.clientX - currentX;
            startY = e.clientY - currentY;
            modalImg.classList.remove('zoom-transition');
        }
    });

    document.addEventListener('mousemove', function(e) {
        if (isDragging && currentScale > 1) {
            currentX = e.clientX - startX;
            currentY = e.clientY - startY;
            applyZoom();
        }
    });

    document.addEventListener('mouseup', function() {
        if (isDragging) {
            isDragging = false;
            modalImg.classList.add('zoom-transition');
        }
    });

    // Close modal on click outside
    document.getElementById('photoModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePhotoModal();
        }
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        const photoModal = document.getElementById('photoModal');
        if (photoModal.style.display === 'block') {
            if (e.key === 'Escape') closePhotoModal();
            if (e.key === '+' || e.key === '=') zoomIn(e);
            if (e.key === '-') zoomOut(e);
            if (e.key === '0') resetZoom(e);
        }
    });
});
</script>
@endpush
