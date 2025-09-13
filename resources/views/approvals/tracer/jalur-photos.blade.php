@extends('layouts.app')

@section('title', 'Tracer - Review Foto Jalur')

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
            <h1 class="text-3xl font-bold text-gray-900">Review Foto Jalur - Tracer</h1>
            <p class="text-gray-600 mt-1">Review foto evidence dari lowering dan joint jalur pipa</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('approvals.tracer.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                ← Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="status_filter" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status_filter" id="status_filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Semua Status</option>
                    <option value="tracer_pending" {{ request('status_filter') == 'tracer_pending' ? 'selected' : '' }}>Pending Review</option>
                    <option value="tracer_rejected" {{ request('status_filter') == 'tracer_rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="cgp_pending" {{ request('status_filter') == 'cgp_pending' ? 'selected' : '' }}>Approved (CGP Review)</option>
                    <option value="cgp_approved" {{ request('status_filter') == 'cgp_approved' ? 'selected' : '' }}>Final Approved</option>
                </select>
            </div>
            <div>
                <label for="module_type" class="block text-sm font-medium text-gray-700 mb-2">Module Type</label>
                <select name="module_type" id="module_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Semua Module</option>
                    <option value="jalur_lowering" {{ request('module_type') == 'jalur_lowering' ? 'selected' : '' }}>Jalur Lowering</option>
                    <option value="jalur_joint" {{ request('module_type') == 'jalur_joint' ? 'selected' : '' }}>Jalur Joint</option>
                </select>
            </div>
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" 
                       placeholder="Cari line number, nomor joint..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium mr-2">
                    🔍 Filter
                </button>
                <a href="{{ route('approvals.tracer.jalur-photos') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium">
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
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold text-lg">
                                @if($photo->module_name === 'jalur_lowering' && $photo->jalurLowering)
                                    📍 {{ $photo->jalurLowering->lineNumber->line_number }}
                                @elseif($photo->module_name === 'jalur_joint' && $photo->jalurJoint)
                                    🔗 {{ $photo->jalurJoint->nomor_joint }}
                                @else
                                    📋 Jalur Data
                                @endif
                            </h3>
                            <p class="text-purple-100 text-sm mt-1">
                                @if($photo->module_name === 'jalur_lowering')
                                    Lowering Evidence
                                @elseif($photo->module_name === 'jalur_joint')
                                    Joint Evidence  
                                @endif
                            </p>
                        </div>
                        <div class="text-right">
                            @if($photo->photo_status === 'tracer_pending')
                                <span class="bg-yellow-400 text-yellow-900 px-2 py-1 rounded text-xs font-medium">
                                    Pending Review
                                </span>
                            @elseif($photo->photo_status === 'tracer_rejected')
                                <span class="bg-red-500 text-white px-2 py-1 rounded text-xs font-medium">
                                    Rejected
                                </span>
                            @elseif($photo->photo_status === 'cgp_pending')
                                <span class="bg-green-500 text-white px-2 py-1 rounded text-xs font-medium">
                                    Approved
                                </span>
                            @elseif($photo->photo_status === 'cgp_approved')
                                <span class="bg-blue-500 text-white px-2 py-1 rounded text-xs font-medium">
                                    Final Approved
                                </span>
                            @else
                                <span class="bg-gray-400 text-white px-2 py-1 rounded text-xs font-medium">
                                    {{ ucfirst(str_replace('_', ' ', $photo->photo_status)) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Photo -->
                <div class="p-4">
                    <div class="mb-4">
                        @php
                            // Convert Google Drive URL to direct image URL (same logic as SK/SR/GAS_IN)
                            $photoUrl = $photo->photo_url;
                            if (str_contains($photoUrl, 'drive.google.com')) {
                                if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $photoUrl, $matches)) {
                                    $fileId = $matches[1];
                                    // Use same format as SK/SR/GAS_IN - Google Drive direct image URL
                                    $photoUrl = "https://lh3.googleusercontent.com/d/{$fileId}";
                                } elseif (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $photoUrl, $matches)) {
                                    $fileId = $matches[1];
                                    $photoUrl = "https://lh3.googleusercontent.com/d/{$fileId}";
                                }
                            } elseif (strpos($photoUrl, 'http') !== 0) {
                                // Handle local storage paths
                                $photoUrl = asset('storage/' . ltrim($photoUrl, '/'));
                            }
                        @endphp
                        
                        <div class="relative">
                            <img src="{{ $photoUrl }}" 
                                 alt="{{ $photo->photo_field_name }}" 
                                 class="w-full photo-preview rounded-lg border border-gray-200 bg-gray-100"
                                 data-photo-url="{{ addslashes($photoUrl) }}"
                                 onclick="openPhotoModal(this.dataset.photoUrl)"
                                 onerror="this.onerror=null; this.style.display='none'; this.parentElement.querySelector('.loading-placeholder').style.display='none'; var errorDiv = document.createElement('div'); errorDiv.className='absolute inset-0 flex items-center justify-center bg-gray-100 rounded-lg'; errorDiv.innerHTML='<span class=&quot;text-gray-500 text-sm&quot;>⚠️ Foto tidak dapat dimuat</span>'; this.parentElement.appendChild(errorDiv);">
                            
                            <!-- Loading placeholder -->
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
                        @if($photo->description)
                        <div class="pt-1 border-t border-gray-100">
                            <span class="text-gray-600 text-xs">Deskripsi:</span>
                            <p class="text-gray-800 text-xs mt-1 line-clamp-2">{{ Str::limit($photo->description, 50) }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    @if($photo->photo_status === 'tracer_pending')
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <form method="POST" action="{{ route('approvals.tracer.approve-photo') }}" class="space-y-2">
                                @csrf
                                <input type="hidden" name="photo_id" value="{{ $photo->id }}">
                                
                                <div>
                                    <label for="notes_{{ $photo->id }}" class="block text-xs font-medium text-gray-700 mb-1">Catatan Review</label>
                                    <textarea name="notes" id="notes_{{ $photo->id }}" rows="2" 
                                              placeholder="Catatan review..."
                                              class="w-full px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-purple-500"></textarea>
                                </div>

                                <div class="flex space-x-1">
                                    <button type="submit" name="action" value="approve" 
                                            class="flex-1 bg-green-600 hover:bg-green-700 text-white px-2 py-1.5 rounded text-xs font-medium transition-colors">
                                        ✓ Approve
                                    </button>
                                    <button type="submit" name="action" value="reject"
                                            class="flex-1 bg-red-600 hover:bg-red-700 text-white px-2 py-1.5 rounded text-xs font-medium transition-colors">
                                        ✗ Reject
                                    </button>
                                </div>
                            </form>
                        </div>
                    @elseif($photo->photo_status === 'tracer_rejected')
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <div class="bg-red-50 rounded-lg p-2">
                                <div class="flex items-center text-red-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-xs font-medium">Foto telah di-reject</span>
                                </div>
                                @if($photo->tracer_notes)
                                    <p class="text-xs text-red-600 mt-1">{{ $photo->tracer_notes }}</p>
                                @endif
                            </div>
                        </div>
                    @elseif(in_array($photo->photo_status, ['cgp_pending', 'cgp_approved']))
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <div class="bg-green-50 rounded-lg p-2">
                                <div class="flex items-center text-green-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-xs font-medium">Foto telah di-approve</span>
                                </div>
                                @if($photo->tracer_notes)
                                    <p class="text-xs text-green-600 mt-1">{{ $photo->tracer_notes }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <div class="text-gray-400 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada foto jalur yang perlu direview</h3>
                <p class="text-gray-600">Semua foto jalur sudah diproses atau belum ada yang diupload.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $photos->links() }}
    </div>
</div>

<!-- Photo Modal -->
<div id="photoModal" class="photo-modal" onclick="closePhotoModal()">
    <img id="modalImage" src="" alt="Photo Preview">
</div>
@endsection

@push('scripts')
<script>
function openPhotoModal(imageSrc) {
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('photoModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closePhotoModal() {
    document.getElementById('photoModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePhotoModal();
    }
});

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Setup image loading handlers
    const images = document.querySelectorAll('.photo-preview');
    images.forEach(function(img) {
        img.addEventListener('load', function() {
            const loadingPlaceholder = this.parentElement.querySelector('.loading-placeholder');
            if (loadingPlaceholder) {
                loadingPlaceholder.style.display = 'none';
            }
        });
        
        // If image is already loaded (cached)
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