@extends('layouts.app')

@section('title', 'Detail Joint')

@section('content')
    <div class="container mx-auto px-6 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ $joint->nomor_joint }}</h1>
                    <p class="text-gray-600">{{ $joint->fittingType->nama_fitting }} -
                        {{ $joint->lokasi_joint ?: 'Lokasi belum diisi' }}</p>
                    <p class="text-sm text-gray-500">Line Numbers: {{ $joint->formatted_joint_line }}</p>
                    <p class="text-sm text-gray-500">Cluster: {{ $joint->cluster->nama_cluster }}</p>
                </div>
                <div class="flex space-x-4">
                    @if(in_array($joint->status_laporan, ['draft', 'revisi_tracer', 'revisi_cgp']))
                        <a href="{{ route('jalur.joint.edit', $joint) }}"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                </path>
                            </svg>
                            Edit
                        </a>
                    @endif
                    <a href="{{ route('jalur.joint.index') }}"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                        Kembali
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Joint Information -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow p-6 mb-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Informasi Joint</h2>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Nomor Joint</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $joint->nomor_joint }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Line Number</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $joint->formatted_joint_line }}
                                    @if($joint->joint_line_optional && $joint->isEqualTee())
                                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded ml-2">3-Way
                                            Connection</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Cluster</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $joint->cluster->nama_cluster }} ({{ $joint->cluster->code_cluster }})
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Tipe Fitting</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $joint->fittingType->nama_fitting }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Tanggal Joint</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $joint->tanggal_joint->format('d F Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Lokasi Joint</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $joint->lokasi_joint }}</dd>
                            </div>
                        </dl>

                        @if($joint->keterangan)
                            <div class="mt-6">
                                <dt class="text-sm font-medium text-gray-500">Keterangan</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $joint->keterangan }}</dd>
                            </div>
                        @endif
                    </div>


                    <!-- Photos -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Foto Evidence</h2>

                        @if($joint->photoApprovals && $joint->photoApprovals->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach($joint->photoApprovals as $photo)
                                    <div class="bg-gray-50 rounded-lg overflow-hidden">
                                        <div class="aspect-w-4 aspect-h-3 bg-gray-200">
                                            @if($photo->photo_url && !empty(trim($photo->photo_url)))
                                                @php
                                                    // Convert Google Drive URL to direct image URL
                                                    $imageUrl = $photo->photo_url;
                                                    $isPdf = str_ends_with(Str::lower($imageUrl), '.pdf');
                                                    $fileId = null;

                                                    // Handle Google Drive URLs
                                                    if (str_contains($imageUrl, 'drive.google.com')) {
                                                        // Extract file ID from various Google Drive URL formats
                                                        if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $imageUrl, $matches)) {
                                                            $fileId = $matches[1];
                                                            // Primary Google Drive image URL (most reliable)
                                                            $imageUrl = "https://lh3.googleusercontent.com/d/{$fileId}=w800";
                                                        } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $imageUrl, $matches)) {
                                                            $fileId = $matches[1];
                                                            $imageUrl = "https://lh3.googleusercontent.com/d/{$fileId}=w800";
                                                        }
                                                    }
                                                    // Local storage files
                                                    elseif (!str_contains($imageUrl, 'http') && !str_starts_with($imageUrl, 'jalur/joint/') && Storage::disk('public')->exists($imageUrl)) {
                                                        $imageUrl = asset('storage/' . $imageUrl);
                                                    }
                                                    // Local jalur joint files
                                                    elseif (str_starts_with($imageUrl, 'jalur/joint/')) {
                                                        $imageUrl = asset('storage/' . $imageUrl);
                                                    }
                                                @endphp

                                                @if($imageUrl && !$isPdf)
                                                    <img src="{{ $imageUrl }}"
                                                        class="w-full h-48 object-cover cursor-pointer hover:opacity-90 transition-opacity"
                                                        alt="{{ $photo->photo_field_name }}" loading="lazy"
                                                        data-file-id="{{ $fileId ?? '' }}" data-original-url="{{ $photo->photo_url }}"
                                                        onerror="tryAlternativeUrls(this)"
                                                        onclick="openImageModal('{{ $imageUrl }}', '{{ str_replace('foto_evidence_', '', $photo->photo_field_name) }}')">
                                                @else
                                                    <div class="flex flex-col items-center justify-center h-48 text-gray-400">
                                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                            </path>
                                                        </svg>
                                                        <p class="text-xs mt-2">Foto tidak tersedia</p>
                                                    </div>
                                                @endif
                                            @else
                                                <div class="flex flex-col items-center justify-center h-48 text-gray-400">
                                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                        </path>
                                                    </svg>
                                                    <p class="text-xs mt-2">Belum ada foto</p>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="p-4">
                                            <div class="flex items-center justify-between">
                                                <h3 class="text-sm font-medium text-gray-900 capitalize">
                                                    {{ str_replace('_', ' ', $photo->photo_field_name) }}</h3>
                                                <span class="inline-flex px-2 py-1 text-xs rounded-full
                                                            @if($photo->ai_status === 'passed') bg-green-100 text-green-800
                                                            @elseif($photo->ai_status === 'failed') bg-red-100 text-red-800
                                                            @else bg-yellow-100 text-yellow-800
                                                            @endif">
                                                    {{ ucfirst($photo->ai_status) }}
                                                </span>
                                            </div>
                                            @if($photo->ai_notes)
                                                <p class="text-xs text-gray-600 mt-2">{{ $photo->ai_notes }}</p>
                                            @endif
                                            <p class="text-xs text-gray-500 mt-1">{{ $photo->created_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <p class="text-gray-500">Belum ada foto yang diupload</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Status & Actions -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Status Laporan</h2>

                        <div class="mb-4">
                            <span class="inline-flex px-3 py-1 text-sm rounded-full
                                @if($joint->status_laporan === 'acc_cgp') bg-green-100 text-green-800
                                @elseif($joint->status_laporan === 'acc_tracer') bg-blue-100 text-blue-800
                                @elseif(in_array($joint->status_laporan, ['revisi_tracer', 'revisi_cgp'])) bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ $joint->status_label }}
                            </span>
                        </div>

                        <!-- Approval Timeline -->
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    @if($joint->tracer_approved_at)
                                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                    @endif
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">Approval Tracer</p>
                                    @if($joint->tracer_approved_at)
                                        <p class="text-sm text-green-600">{{ $joint->tracer_approved_at->format('d/m/Y H:i') }}
                                        </p>
                                        @if($joint->tracer_approved_by)
                                            <p class="text-xs text-gray-500">oleh {{ $joint->tracerApprover->name }}</p>
                                        @endif
                                    @else
                                        <p class="text-sm text-gray-500">Menunggu approval</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    @if($joint->cgp_approved_at)
                                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                    @endif
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">Approval CGP</p>
                                    @if($joint->cgp_approved_at)
                                        <p class="text-sm text-green-600">{{ $joint->cgp_approved_at->format('d/m/Y H:i') }}</p>
                                        @if($joint->cgp_approved_by)
                                            <p class="text-xs text-gray-500">oleh {{ $joint->cgpApprover->name }}</p>
                                        @endif
                                    @else
                                        <p class="text-sm text-gray-500">Menunggu approval</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons for Approvers -->
                        @if(auth()->user()->hasAnyRole(['tracer', 'admin', 'super_admin']) && !$joint->tracer_approved_at && $joint->status_laporan !== 'revisi_tracer')
                            <div class="mt-6 space-y-2">
                                <form method="POST" action="{{ route('jalur.joint.approve-tracer', $joint) }}" class="w-full">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Approve laporan joint ini sebagai Tracer?')"
                                        class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                        Approve sebagai Tracer
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('jalur.joint.reject-tracer', $joint) }}" class="w-full">
                                    @csrf
                                    <button type="submit"
                                        onclick="return confirm('Reject laporan joint ini? Joint akan kembali ke status revisi.')"
                                        class="w-full bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                                        Reject (Revisi)
                                    </button>
                                </form>
                            </div>
                        @endif

                        @if(auth()->user()->hasAnyRole(['admin', 'super_admin']) && $joint->tracer_approved_at && !$joint->cgp_approved_at && $joint->status_laporan !== 'revisi_cgp')
                            <div class="mt-6 space-y-2">
                                <form method="POST" action="{{ route('jalur.joint.approve-cgp', $joint) }}" class="w-full">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Approve laporan joint ini sebagai CGP?')"
                                        class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                        Approve sebagai CGP
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('jalur.joint.reject-cgp', $joint) }}" class="w-full">
                                    @csrf
                                    <button type="submit"
                                        onclick="return confirm('Reject laporan joint ini? Joint akan kembali ke status revisi.')"
                                        class="w-full bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                                        Reject (Revisi)
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>

                    <!-- Related Data -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Data Terkait</h2>

                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-medium text-gray-700">Line Numbers</h3>
                                <p class="text-sm text-gray-900">
                                    From: {{ $joint->joint_line_from }} → To: {{ $joint->joint_line_to }}
                                </p>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium text-gray-700">Cluster</h3>
                                <p class="text-sm text-gray-900">{{ $joint->cluster->nama_cluster }}</p>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium text-gray-700">Tipe Penyambungan</h3>
                                <p class="text-sm text-gray-900">{{ $joint->tipe_penyambungan }}</p>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium text-gray-700">Total Foto</h3>
                                <p class="text-sm text-gray-900">
                                    {{ $joint->photoApprovals ? $joint->photoApprovals->count() : 0 }} foto</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 hidden items-center justify-center p-4"
        style="z-index: 9999;" onclick="closeImageModal(event)">
        <div class="photo-modal-controls">
            <button onclick="zoomIn(event)" title="Zoom In (+)">
                <i class="fas fa-search-plus"></i>
            </button>
            <button onclick="zoomOut(event)" title="Zoom Out (-)">
                <i class="fas fa-search-minus"></i>
            </button>
            <button onclick="resetZoom(event)" title="Reset (0)">
                <i class="fas fa-compress"></i>
            </button>
            <button onclick="closeImageModal(event)" title="Close (Esc)">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="relative flex items-center justify-center" style="width: 90vw; height: 90vh;"
            onclick="event.stopPropagation()">
            <img id="modalImage" src="" alt="" class="max-w-full max-h-full object-contain rounded"
                style="cursor: zoom-in;">
            <div id="modalTitle"
                class="absolute bottom-4 left-4 right-4 text-white text-center text-lg font-medium bg-black bg-opacity-50 rounded p-2">
            </div>
        </div>
    </div>
    <style>
        #imageModal img.zoom-transition {
            transition: transform 0.2s ease-out;
        }

        #imageModal img.zoomed {
            max-width: none;
            max-height: none;
            cursor: grab;
        }

        #imageModal img.zoomed:active {
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
            background: rgba(255, 255, 255, 0.9);
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
            background: rgba(255, 255, 255, 1);
            transform: scale(1.1);
        }
    </style>

    <script>
        // Advanced Google Drive Photo Display with Multiple Fallbacks (from lowering view)
        function tryAlternativeUrls(imgElement) {
            if (imgElement.dataset.tried) {
                imgElement.parentElement.innerHTML = '<div class="flex flex-col items-center justify-center h-48 text-red-400"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path></svg><p class="text-xs mt-2">Image unavailable</p><button onclick="viewGoogleDrivePhoto(\'' + imgElement.dataset.originalUrl + '\')" class="text-xs text-blue-500 hover:text-blue-700 mt-1">📁 Buka di Drive</button></div>';
                return;
            }

            imgElement.dataset.tried = 'true';
            const fileId = imgElement.dataset.fileId;
            const originalUrl = imgElement.dataset.originalUrl;

            if (fileId) {
                // Try alternative Google Drive URLs
                const alternatives = [
                    `https://lh3.googleusercontent.com/d/${fileId}`,
                    `https://drive.google.com/thumbnail?id=${fileId}&sz=w800`,
                    `https://drive.google.com/uc?id=${fileId}`,
                ];

                for (let altUrl of alternatives) {
                    if (imgElement.src !== altUrl) {
                        imgElement.src = altUrl;
                        return;
                    }
                }
            }

            // If all alternatives fail, show Google Drive placeholder
            imgElement.parentElement.innerHTML = '<div class="flex flex-col items-center justify-center h-48 text-orange-400"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg><p class="text-xs mt-2">Foto Google Drive</p><button onclick="viewGoogleDrivePhoto(\'' + originalUrl + '\')" class="text-xs text-blue-500 hover:text-blue-700 mt-1 px-2 py-1 border border-blue-300 rounded">📁 Buka di Drive</button></div>';
        }

        // ... existing code ...

        function viewGoogleDrivePhoto(path) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
            <div class="bg-white rounded-lg p-6 max-w-md mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold mb-2">Foto Google Drive</h3>
                    <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">✕</button>
                </div>
                <p class="text-sm text-gray-500 mb-6">Foto tersimpan di Google Drive. Klik tombol di bawah untuk membuka folder Drive.</p>
                <div class="flex space-x-3">
                    <button onclick="this.closest('.fixed').remove()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Tutup</button>
                    <a href="${path}" target="_blank" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-center">📁 Buka Drive</a>
                </div>
            </div>
        `;
            document.body.appendChild(modal);
        }
    </script>
@endsection