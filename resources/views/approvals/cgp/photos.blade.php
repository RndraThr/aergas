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

    <!-- Photo Sections -->
    @foreach(['sk', 'sr', 'gas_in'] as $module)
        @php
            $modulePhotos = $photos[$module] ?? [];
            $moduleReady = ($module === 'sk' && $cgpStatus['sk_ready']) || 
                          ($module === 'sr' && $cgpStatus['sr_ready']) || 
                          ($module === 'gas_in' && $cgpStatus['gas_in_ready']);
            $moduleCompleted = ($module === 'sk' && $cgpStatus['sk_completed']) || 
                              ($module === 'sr' && $cgpStatus['sr_completed']) || 
                              ($module === 'gas_in' && $cgpStatus['gas_in_completed']);
            $moduleAvailable = $moduleReady || $moduleCompleted;
        @endphp

        @if($moduleAvailable)
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <h2 class="text-lg font-semibold text-gray-900 mr-4">
                            {{ strtoupper($module) }} Photos (Tracer Approved)
                        </h2>
                        @if($moduleCompleted)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                ✅ CGP Approved
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                ⏳ Pending CGP Review
                            </span>
                        @endif
                    </div>
                    
                    @if($moduleReady && !$moduleCompleted && count($modulePhotos) > 0)
                        <button onclick="approveModule('{{ $module }}')" 
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium text-sm">
                            ✅ Approve All {{ strtoupper($module) }}
                        </button>
                    @endif
                </div>
            </div>

            @if(count($modulePhotos) > 0)
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($modulePhotos as $photo)
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <!-- Photo -->
                                <div class="aspect-w-4 aspect-h-3 bg-gray-100">
                                    @if($photo->photo_url && !empty(trim($photo->photo_url)))
                                        @php
                                            // Convert Google Drive URL to direct image URL
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
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <p class="text-xs mt-2">No image</p>
                                        </div>
                                    @endif
                                </div>

                                <!-- Photo Info -->
                                <div class="p-4">
                                    <h3 class="font-medium text-gray-900 mb-2">{{ $photo->photo_field_name }}</h3>
                                    
                                    <!-- Tracer Approval Info -->
                                    @if($photo->tracer_approved_at)
                                        <div class="mb-3 p-2 bg-green-50 rounded">
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="font-medium text-green-900">✅ Tracer Approved</span>
                                                <span class="text-green-600">{{ $photo->tracer_approved_at->format('d/m/y H:i') }}</span>
                                            </div>
                                            @if($photo->tracer_notes)
                                                <p class="text-xs text-green-700 mt-1">{{ $photo->tracer_notes }}</p>
                                            @endif
                                        </div>
                                    @endif

                                    <!-- CGP Status -->
                                    <div class="flex flex-col mb-3">
                                        @if($photo->cgp_approved_at)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                ✅ Approved by CGP
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                ⏳ Pending CGP Review
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Actions -->
                                    @if($moduleReady && !$photo->cgp_approved_at && $photo->photo_status === 'cgp_pending')
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

                                    <!-- Rejection Status -->
                                    @if($photo->photo_status === 'cgp_rejected')
                                        <div class="mt-3 p-2 bg-red-50 rounded">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                ❌ Rejected by CGP
                                            </span>
                                            @if($photo->rejection_reason)
                                                <p class="text-xs text-red-600 mt-1">{{ $photo->rejection_reason }}</p>
                                            @endif
                                        </div>
                                    @endif

                                    <!-- CGP Notes -->
                                    @if($photo->cgp_notes)
                                        <div class="mt-3 p-2 bg-gray-50 rounded">
                                            <p class="text-xs text-gray-600"><strong>CGP Notes:</strong> {{ $photo->cgp_notes }}</p>
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
                    <p class="mt-1 text-sm text-gray-500">Foto akan muncul setelah di-approve oleh Tracer</p>
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

// Form submission
document.getElementById('notesForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const notes = document.getElementById('reviewNotes').value;
    const formData = new FormData();
    formData.append('notes', notes);
    formData.append('_token', '{{ csrf_token() }}');
    
    let url = '';
    
    if (currentAction === 'approveModule') {
        url = '{{ route("approvals.cgp.approve-module") }}';
        formData.append('reff_id', reffId);
        formData.append('module', currentModule);
    } else {
        url = '{{ route("approvals.cgp.approve-photo") }}';
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
</script>
@endpush