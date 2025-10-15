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
<div class="container mx-auto px-4 py-6" x-data="{ filterSearch: '{{ request('search', '') }}', filterStatus: '{{ request('status_filter', 'tracer_pending') }}', filterModuleType: '{{ request('module_type', '') }}' }">
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status_filter" x-model="filterStatus" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Semua Status</option>
                    <option value="tracer_pending">Pending Review</option>
                    <option value="tracer_rejected">Rejected</option>
                    <option value="cgp_pending">Approved (CGP Review)</option>
                    <option value="cgp_approved">Final Approved</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Module Type</label>
                <select name="module_type" x-model="filterModuleType" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Semua Module</option>
                    <option value="jalur_lowering">Jalur Lowering</option>
                    <option value="jalur_joint">Jalur Joint</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" name="search" x-model="filterSearch"
                       placeholder="Cari line number, nomor joint..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium">
                    Filter
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
                                    📍 {{ $photo->jalurLowering->lineNumber->line_number ?? 'Line Data' }}
                                @elseif($photo->module_name === 'jalur_joint' && $photo->jalurJoint)
                                    🔗 {{ $photo->jalurJoint->nomor_joint ?? 'Joint Data' }}
                                @else
                                    📋 Jalur Data
                                @endif
                            </h3>
                            <p class="text-purple-100 text-sm mt-1">
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
                            @php
                                $statusClass = [
                                    'tracer_pending' => 'bg-yellow-400 text-yellow-900',
                                    'tracer_rejected' => 'bg-red-400 text-red-900',
                                    'cgp_pending' => 'bg-green-400 text-green-900',
                                    'cgp_approved' => 'bg-blue-400 text-blue-900'
                                ];
                                $statusLabel = [
                                    'tracer_pending' => 'Pending Review',
                                    'tracer_rejected' => 'Rejected',
                                    'cgp_pending' => 'Approved',
                                    'cgp_approved' => 'Final Approved'
                                ];
                            @endphp
                            <span class="px-2 py-1 rounded text-xs font-medium {{ $statusClass[$photo->photo_status] ?? 'bg-gray-400 text-gray-900' }}">
                                {{ $statusLabel[$photo->photo_status] ?? $photo->photo_status }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Photo -->
                <div class="p-4">
                    <div class="mb-4">
                        @php
                            $photoUrl = $photo->photo_url;
                            $fileId = null;

                            // Handle Google Drive URLs with proper size parameter
                            if (str_contains($photoUrl, 'drive.google.com')) {
                                if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $photoUrl, $matches)) {
                                    $fileId = $matches[1];
                                    $photoUrl = "https://lh3.googleusercontent.com/d/{$fileId}=w800";
                                } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $photoUrl, $matches)) {
                                    $fileId = $matches[1];
                                    $photoUrl = "https://lh3.googleusercontent.com/d/{$fileId}=w800";
                                } elseif (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $photoUrl, $matches)) {
                                    $fileId = $matches[1];
                                    $photoUrl = "https://lh3.googleusercontent.com/d/{$fileId}=w800";
                                }
                            } elseif (strpos($photoUrl, 'http') !== 0) {
                                $photoUrl = asset('storage/' . ltrim($photoUrl, '/'));
                            }
                        @endphp

                        <div class="relative">
                            <img src="{{ $photoUrl }}"
                                 alt="{{ $photo->photo_field_name }}"
                                 class="w-full photo-preview rounded-lg border border-gray-200 bg-gray-100"
                                 data-file-id="{{ $fileId ?? '' }}"
                                 data-original-url="{{ $photo->photo_url }}"
                                 onclick="openPhotoModal('{{ addslashes($photoUrl) }}')"
                                 onerror="tryAlternativeUrls(this)">

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
                            <p class="text-gray-800 text-xs mt-1">{{ Str::limit($photo->description, 50) }}</p>
                        </div>
                        @endif
                        @if($photo->photo_status === 'tracer_rejected' && $photo->tracer_notes)
                        <div class="pt-1 border-t border-gray-100">
                            <span class="text-red-600 text-xs font-medium">Alasan Rejection:</span>
                            <p class="text-gray-800 text-xs mt-1 bg-red-50 p-1 rounded">{{ $photo->tracer_notes }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-3 pt-3 border-t border-gray-200 space-y-2">
                        <!-- Replace Photo Button (Admin/Super Admin Only) -->
                        @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                        <button type="button" onclick="openReplacePhotoModal({{ $photo->id }}, '{{ addslashes($photo->photo_field_name) }}', '{{ addslashes($photo->module_name) }}')"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white px-2 py-1.5 rounded text-xs font-medium transition-colors flex items-center justify-center space-x-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            <span>Replace Photo</span>
                        </button>
                        @endif

                        <!-- Approve/Reject Form (only for pending status) -->
                        @if($photo->photo_status === 'tracer_pending')
                        <form method="POST" action="{{ route('approvals.tracer.approve-photo') }}" class="space-y-2">
                            @csrf
                            <input type="hidden" name="photo_id" value="{{ $photo->id }}">

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Catatan</label>
                                <textarea name="notes" rows="2"
                                          placeholder="Catatan opsional..."
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
                        @endif
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
                <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada foto jalur yang perlu direview</h3>
                <p class="text-gray-600">Semua foto jalur sudah diproses atau belum ada yang diupload.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $photos->appends(request()->query())->links('vendor.pagination.alpine-style') }}
    </div>
</div>

<!-- Photo Modal -->
<div id="photoModal" class="photo-modal" onclick="closePhotoModal()">
    <img id="modalImage" src="" alt="Photo Preview">
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

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePhotoModal();
    }
});

// Advanced Google Drive Photo Display with Multiple Fallbacks
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
        `https://docs.google.com/uc?id=${fileId}`,
        `https://lh3.googleusercontent.com/d/${fileId}=w800`
    ];

    let currentIndex = imgElement.dataset.attemptIndex || 0;
    currentIndex = parseInt(currentIndex);

    if (currentIndex < alternatives.length) {
        imgElement.dataset.attemptIndex = currentIndex + 1;
        imgElement.src = alternatives[currentIndex];
    } else {
        // All alternatives failed, show fallback
        imgElement.parentElement.innerHTML = '<div class="flex flex-col items-center justify-center h-48 text-orange-400"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg><p class="text-xs mt-2">Foto Google Drive</p><p class="text-xs text-gray-500 mt-1">Gagal memuat dari semua sumber</p></div>';
    }
}

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

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePhotoModal();
        closeReplacePhotoModal();
    }
});
</script>
@endpush
