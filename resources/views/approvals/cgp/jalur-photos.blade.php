@extends('layouts.app')

@section('title', 'CGP - Review Foto Jalur')

@push('styles')
<style>
    .photo-preview {
        width: 100%;
        height: 240px;
        object-fit: cover;
        object-position: center;
        cursor: zoom-in;
        transition: transform 0.2s ease;
    }

    .photo-preview:hover {
        transform: scale(1.02);
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
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6" x-data="{ filterSearch: '{{ request('search', '') }}', filterModuleType: '{{ request('module_type', '') }}' }">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Review Foto Jalur - CGP</h1>
            <p class="text-gray-600 mt-1">Final approval foto evidence jalur yang sudah di-review Tracer</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('approvals.cgp.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                ← Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Module Type</label>
                <select name="module_type" x-model="filterModuleType" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Module</option>
                    <option value="jalur_lowering">Jalur Lowering</option>
                    <option value="jalur_joint">Jalur Joint</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" name="search" x-model="filterSearch"
                       placeholder="Cari line number, nomor joint..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                    Filter
                </button>
                <a href="{{ route('approvals.cgp.jalur-photos') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Photos Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($photos as $photo)
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold text-lg">
                                @if($photo->module_name === 'jalur_lowering' && $photo->jalurLowering)
                                    📍 {{ $photo->jalurLowering->lineNumber->line_number ?? 'Line Data' }}
                                @elseif($photo->module_name === 'jalur_joint' && $photo->jalurJoint)
                                    🔗 {{ $photo->jalurJoint->nomor_joint ?? 'Joint Data' }}
                                @else
                                    📋 Jalur Data
                                @endif
                            </h3>
                            <p class="text-blue-100 text-sm mt-1">
                                @if($photo->module_name === 'jalur_lowering')
                                    Lowering Evidence
                                @elseif($photo->module_name === 'jalur_joint')
                                    Joint Evidence
                                @else
                                    Photo Evidence
                                @endif
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="bg-green-400 text-green-900 px-2 py-1 rounded text-xs font-medium">
                                Ready for CGP
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Photo -->
                <div class="p-4">
                    <div class="mb-4">
                        @php
                            $photoUrl = $photo->photo_url;
                            if (str_contains($photoUrl, 'drive.google.com')) {
                                if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $photoUrl, $matches)) {
                                    $fileId = $matches[1];
                                    $photoUrl = "https://lh3.googleusercontent.com/d/{$fileId}";
                                } elseif (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $photoUrl, $matches)) {
                                    $fileId = $matches[1];
                                    $photoUrl = "https://lh3.googleusercontent.com/d/{$fileId}";
                                }
                            } elseif (strpos($photoUrl, 'http') !== 0) {
                                $photoUrl = asset('storage/' . ltrim($photoUrl, '/'));
                            }
                        @endphp

                        <div class="relative">
                            <img src="{{ $photoUrl }}"
                                 alt="{{ $photo->photo_field_name }}"
                                 class="w-full photo-preview rounded-lg border border-gray-200 bg-gray-100"
                                 onclick="openPhotoModal('{{ addslashes($photoUrl) }}')"
                                 onerror="this.onerror=null; this.style.display='none'; this.parentElement.querySelector('.loading-placeholder').style.display='none'; var errorDiv = document.createElement('div'); errorDiv.className='absolute inset-0 flex items-center justify-center bg-gray-100 rounded-lg'; errorDiv.innerHTML='<span class=&quot;text-gray-500 text-sm&quot;>⚠️ Foto tidak dapat dimuat</span>'; this.parentElement.appendChild(errorDiv);">

                            <div class="absolute inset-0 flex items-center justify-center bg-gray-100 rounded-lg loading-placeholder">
                                <div class="text-gray-400">
                                    <svg class="animate-spin w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between items-start">
                            <span class="text-gray-600 text-xs">Field:</span>
                            <span class="font-medium text-xs text-right">{{ ucfirst(str_replace(['foto_evidence_', '_'], ['', ' '], $photo->photo_field_name)) }}</span>
                        </div>
                        <div class="flex justify-between items-start">
                            <span class="text-gray-600 text-xs">Upload:</span>
                            <span class="font-medium text-xs text-right">{{ $photo->uploaded_at ? $photo->uploaded_at->format('d M Y H:i') : '-' }}</span>
                        </div>
                        <div class="flex justify-between items-start">
                            <span class="text-gray-600 text-xs">Tracer:</span>
                            <span class="font-medium text-green-600 text-xs text-right">{{ $photo->tracer_approved_at ? $photo->tracer_approved_at->format('d M Y H:i') : '-' }}</span>
                        </div>
                        @if($photo->tracer_notes)
                        <div class="pt-1 border-t border-gray-100">
                            <span class="text-gray-600 text-xs">Catatan Tracer:</span>
                            <p class="text-gray-800 text-xs mt-1 bg-gray-50 p-1 rounded">{{ Str::limit($photo->tracer_notes, 50) }}</p>
                        </div>
                        @endif
                        @if($photo->description)
                        <div class="pt-1 border-t border-gray-100">
                            <span class="text-gray-600 text-xs">Deskripsi:</span>
                            <p class="text-gray-800 text-xs mt-1">{{ Str::limit($photo->description, 50) }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <form method="POST" action="{{ route('approvals.cgp.approve-photo') }}" class="space-y-2">
                            @csrf
                            <input type="hidden" name="photo_id" value="{{ $photo->id }}">

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Catatan CGP</label>
                                <textarea name="notes" rows="2"
                                          placeholder="Catatan final..."
                                          class="w-full px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                            </div>

                            <div class="flex space-x-1">
                                <button type="submit" name="action" value="approve"
                                        class="flex-1 bg-green-600 hover:bg-green-700 text-white px-2 py-1.5 rounded text-xs font-medium transition-colors">
                                    ✓ Final Approve
                                </button>
                                <button type="submit" name="action" value="reject"
                                        class="flex-1 bg-red-600 hover:bg-red-700 text-white px-2 py-1.5 rounded text-xs font-medium transition-colors">
                                    ✗ Reject
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <div class="text-gray-400 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada foto jalur yang perlu final approval</h3>
                <p class="text-gray-600">Semua foto jalur sudah diproses final atau belum ada yang di-approve Tracer.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $photos->appends(request()->query())->links('vendor.pagination.alpine-style') }}
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
    <img id="modalImage" src="" alt="Photo Preview">
</div>
@endsection

@push('scripts')
<script>
// Photo modal zoom state
let zoomLevel = 1;
let isDragging = false;
let startX, startY, translateX = 0, translateY = 0;

function openPhotoModal(imageSrc) {
    const img = document.getElementById('modalImage');
    img.src = imageSrc;
    document.getElementById('photoModal').style.display = 'block';
    document.body.style.overflow = 'hidden';

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
    document.body.style.overflow = 'auto';

    // Reset state
    zoomLevel = 1;
    translateX = 0;
    translateY = 0;
    isDragging = false;
}

// Zoom functions
function zoomIn(event) {
    event.stopPropagation();
    zoomLevel = Math.min(zoomLevel + 0.5, 5);
    updateImageTransform(true);
    updateZoomClass();
}

function zoomOut(event) {
    event.stopPropagation();
    zoomLevel = Math.max(zoomLevel - 0.5, 1);
    if (zoomLevel === 1) {
        translateX = 0;
        translateY = 0;
    }
    updateImageTransform(true);
    updateZoomClass();
}

function resetZoom(event) {
    event.stopPropagation();
    zoomLevel = 1;
    translateX = 0;
    translateY = 0;
    updateImageTransform(true);
    updateZoomClass();
}

function updateImageTransform(withTransition = false) {
    const img = document.getElementById('modalImage');

    if (withTransition) {
        img.classList.add('zoom-transition');
        setTimeout(() => {
            img.classList.remove('zoom-transition');
        }, 200);
    }

    img.style.transform = `translate(calc(-50% + ${translateX}px), calc(-50% + ${translateY}px)) scale(${zoomLevel})`;
}

function updateZoomClass() {
    const img = document.getElementById('modalImage');
    if (zoomLevel > 1) {
        img.classList.add('zoomed');
    } else {
        img.classList.remove('zoomed');
    }
}

// Image dragging and zoom event listeners
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('photoModal');
    const img = document.getElementById('modalImage');

    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closePhotoModal();
        }
    });

    img.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    modal.addEventListener('wheel', function(e) {
        if (modal.style.display === 'block') {
            e.preventDefault();

            const oldZoom = zoomLevel;

            if (e.deltaY < 0) {
                zoomLevel = Math.min(zoomLevel + 0.2, 5);
            } else {
                zoomLevel = Math.max(zoomLevel - 0.2, 1);
            }

            if (zoomLevel === 1) {
                translateX = 0;
                translateY = 0;
            } else if (oldZoom !== zoomLevel) {
                const rect = modal.getBoundingClientRect();
                const cursorX = e.clientX - rect.left - rect.width / 2;
                const cursorY = e.clientY - rect.top - rect.height / 2;

                const zoomRatio = zoomLevel / oldZoom;
                translateX = cursorX + (translateX - cursorX) * zoomRatio;
                translateY = cursorY + (translateY - cursorY) * zoomRatio;
            }

            updateImageTransform(true);
            updateZoomClass();
        }
    });

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

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePhotoModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('.photo-preview');
    images.forEach(function(img) {
        img.addEventListener('load', function() {
            const loadingPlaceholder = this.parentElement.querySelector('.loading-placeholder');
            if (loadingPlaceholder) {
                loadingPlaceholder.style.display = 'none';
            }
        });

        if (img.complete && img.naturalWidth > 0) {
            const loadingPlaceholder = img.parentElement.querySelector('.loading-placeholder');
            if (loadingPlaceholder) {
                loadingPlaceholder.style.display = 'none';
            }
        }
    });
});
</script>
@endpush
