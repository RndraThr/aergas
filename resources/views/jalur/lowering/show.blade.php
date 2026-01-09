@extends('layouts.app')

@section('title', 'Detail Lowering - AERGAS')

@section('content')
  <div class="space-y-6" x-data="loweringShow()" x-init="init()">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Detail Lowering</h1>
        <p class="text-gray-600 mt-1">Line:
          <b>{{ $lowering->lineNumber->line_number }}</b>@if($lowering->lineNumber->nama_jalan) -
          {{ $lowering->lineNumber->nama_jalan }}@endif</p>
      </div>
      <div class="flex gap-2">
        @if(in_array($lowering->status_laporan, ['draft', 'revisi_tracer', 'revisi_cgp']))
          <a href="{{ route('jalur.lowering.edit', $lowering) }}"
            class="px-4 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
            <i class="fas fa-edit mr-1"></i> Edit
          </a>
        @endif

        <a href="{{ route('jalur.lowering.index') }}" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">
          <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
      </div>
    </div>

    <!-- Status & Metadata Card -->
    <div class="bg-white rounded-xl card-shadow p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
              <span class="font-medium">{{ $lowering->createdBy->name }}</span>
            </div>
          @else
            <div class="text-gray-400">-</div>
          @endif
        </div>
        <div>
          <div class="text-xs text-gray-500">Tanggal Jalur</div>
          <div class="font-medium">{{ $lowering->tanggal_jalur->format('d/m/Y') }}</div>
        </div>
        <div>
          <div class="text-xs text-gray-500">Dibuat</div>
          <div class="font-medium">{{ $lowering->created_at->format('d/m/Y H:i') }}</div>
        </div>
      </div>
    </div>

    <!-- Line Number & Cluster Info -->
    <div class="bg-white rounded-xl card-shadow p-6">
      <div class="flex items-center gap-3 mb-4">
        <i class="fas fa-route text-blue-600"></i>
        <h2 class="font-semibold text-gray-800">Informasi Jalur</h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <div class="text-xs text-gray-500 uppercase tracking-wider">Line Number</div>
          <div class="text-xl font-bold text-blue-600 mt-1">{{ $lowering->lineNumber->line_number }}</div>
          <div class="text-sm text-gray-600">{{ $lowering->lineNumber->cluster->nama_cluster }}</div>
        </div>
        <div>
          <div class="text-xs text-gray-500 uppercase tracking-wider">Tipe Bongkaran</div>
          <div class="font-semibold text-gray-900 mt-1">{{ $lowering->tipe_bongkaran }}</div>
          <div class="text-sm text-gray-600">
            @if($lowering->tipe_material)
              Material: {{ $lowering->tipe_material }}
            @else
              {{ $lowering->nama_jalan ?? '-' }}
            @endif
          </div>
        </div>
        <div>
          <div class="text-xs text-gray-500 uppercase tracking-wider">Diameter Pipa</div>
          <div class="font-semibold text-gray-900 mt-1">{{ $lowering->lineNumber->diameter }}mm</div>
          <div class="text-sm text-gray-600">{{ $lowering->lineNumber->status_line }}</div>
        </div>
      </div>
    </div>

    <!-- Progress Data -->
    <div class="bg-white rounded-xl card-shadow p-6">
      <div class="flex items-center gap-3 mb-6">
        <i class="fas fa-chart-line text-green-600"></i>
        <h2 class="font-semibold text-gray-800">Data Progress Lowering</h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="text-center p-4 bg-blue-50 rounded-lg">
          <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Panjang Lowering</div>
          <div class="text-3xl font-bold text-blue-600">{{ number_format($lowering->penggelaran, 1) }}</div>
          <div class="text-sm text-blue-700">meter</div>
        </div>
        <div class="text-center p-4 bg-green-50 rounded-lg">
          <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">
            @if(in_array($lowering->tipe_bongkaran, ['Manual Boring', 'Manual Boring - PK']))
              Pekerjaan Manual Boring
            @else
              Bongkaran
            @endif
          </div>
          <div class="text-3xl font-bold text-green-600">{{ number_format($lowering->bongkaran, 1) }}</div>
          <div class="text-sm text-green-700">meter</div>
        </div>
        <div class="text-center p-4 bg-purple-50 rounded-lg">
          <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Kedalaman Lowering</div>
          <div class="text-3xl font-bold text-purple-600">{{ $lowering->kedalaman_lowering }}</div>
          <div class="text-sm text-purple-700">cm</div>
        </div>
      </div>
    </div>

    <!-- Aksesoris (if applicable) -->
    @if($lowering->aksesoris_marker_tape || $lowering->aksesoris_concrete_slab || $lowering->aksesoris_cassing)
      <div class="bg-white rounded-xl card-shadow p-6">
        <div class="flex items-center gap-3 mb-4">
          <i class="fas fa-tools text-orange-600"></i>
          <h2 class="font-semibold text-gray-800">Aksesoris</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          @if($lowering->aksesoris_marker_tape)
            <div class="flex items-center p-4 bg-yellow-50 rounded-lg">
              <i class="fas fa-tape text-yellow-600 text-xl mr-3"></i>
              <div>
                <div class="font-semibold text-gray-900">Marker Tape</div>
                <div class="text-sm text-gray-600">{{ number_format($lowering->marker_tape_quantity, 1) }} meter</div>
              </div>
            </div>
          @endif
          @if($lowering->aksesoris_concrete_slab)
            <div class="flex items-center p-4 bg-gray-50 rounded-lg">
              <i class="fas fa-th-large text-gray-600 text-xl mr-3"></i>
              <div>
                <div class="font-semibold text-gray-900">Concrete Slab</div>
                <div class="text-sm text-gray-600">{{ $lowering->concrete_slab_quantity }} pcs</div>
              </div>
            </div>
          @endif
          @if($lowering->aksesoris_cassing && $lowering->cassing_quantity)
            <div class="flex items-center p-4 bg-green-50 rounded-lg">
              <i class="fas fa-grip-lines-vertical text-green-600 text-xl mr-3"></i>
              <div>
                <div class="font-semibold text-gray-900">Cassing</div>
                <div class="text-sm text-gray-600">
                  {{ number_format($lowering->cassing_quantity, 1) }} meter
                  @if($lowering->cassing_type)
                    - {{ str_replace('_', ' ', ucfirst($lowering->cassing_type)) }}
                  @endif
                </div>
              </div>
            </div>
          @endif
        </div>
      </div>
    @endif

    <!-- Keterangan -->
    @if($lowering->keterangan)
      <div class="bg-white rounded-xl card-shadow p-6">
        <div class="flex items-center gap-3 mb-4">
          <i class="fas fa-sticky-note text-indigo-600"></i>
          <h2 class="font-semibold text-gray-800">Keterangan</h2>
        </div>
        <div class="p-4 bg-gray-50 rounded-lg">
          <p class="text-gray-700">{{ $lowering->keterangan }}</p>
        </div>
      </div>
    @endif

    <!-- Evidence Photos -->
    <div class="bg-white rounded-xl card-shadow p-6">
      <div class="flex items-center gap-3 mb-6">
        <i class="fas fa-images text-purple-600"></i>
        <h2 class="font-semibold text-gray-800">Dokumentasi Foto</h2>
      </div>

      @if($lowering->photoApprovals && $lowering->photoApprovals->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          @foreach($lowering->photoApprovals as $photo)
            <div class="border border-gray-200 rounded-lg overflow-hidden">
              <!-- Photo -->
              <div class="aspect-w-4 aspect-h-3 bg-gray-100">
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
                        // Use lh3.googleusercontent.com - fastest and most reliable for thumbnails
                        $imageUrl = "https://lh3.googleusercontent.com/d/{$fileId}=w800";
                      } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $imageUrl, $matches)) {
                        $fileId = $matches[1];
                        $imageUrl = "https://lh3.googleusercontent.com/d/{$fileId}=w800";
                      }
                    }
                    // Local storage files
                    elseif (!str_contains($imageUrl, 'http') && !str_starts_with($imageUrl, 'JALUR_LOWERING/') && !str_starts_with($imageUrl, 'aergas/')) {
                      $cleanPath = str_replace('/storage/', '', $imageUrl);
                      // Remove leading slash if present
                      $cleanPath = ltrim($cleanPath, '/');

                      if (Storage::disk('public')->exists($cleanPath)) {
                        $imageUrl = asset('storage/' . $cleanPath);
                      } elseif (file_exists(public_path($imageUrl))) {
                        // Try direct public path if absolute path is stored
                        $imageUrl = asset($imageUrl);
                      }
                    }
                    // Legacy internal paths ...
                    elseif (str_starts_with($imageUrl, 'JALUR_LOWERING/') || str_starts_with($imageUrl, 'aergas/')) {
                      $imageUrl = null;
                    }
                  @endphp

                  @if($imageUrl && !$isPdf)
                    <img src="{{ $imageUrl }}" class="w-full h-48 object-cover cursor-pointer hover:opacity-90 transition-opacity"
                      alt="Evidence foto" loading="lazy" data-file-id="{{ $fileId ?? '' }}"
                      data-original-url="{{ $photo->photo_url }}" onerror="tryAlternativeUrls(this)"
                      onclick="openImageModal('{{ $imageUrl }}', '{{ str_replace('foto_evidence_', '', $photo->photo_field_name) }}')">
                  @elseif($isPdf)
                    <div class="w-full h-48 flex flex-col items-center justify-center bg-gray-50 cursor-pointer hover:bg-gray-100"
                      onclick="window.open('{{ $photo->photo_url }}', '_blank')">
                      <i class="fas fa-file-pdf text-red-500 text-4xl mb-3"></i>
                      <div class="text-sm text-gray-600 text-center font-medium">PDF Document</div>
                      <div class="text-xs text-blue-600 mt-1">Klik untuk membuka</div>
                    </div>
                  @else
                    {{-- Show placeholder for internal paths or unavailable images --}}
                    @php
                      $fileName = basename($photo->photo_url);
                      $isInternalPath = str_starts_with($photo->photo_url, 'JALUR_LOWERING/') || str_starts_with($photo->photo_url, 'aergas/');
                    @endphp

                    @if($isInternalPath)
                      {{-- Google Drive internal path - show nice placeholder --}}
                      <div
                        class="w-full h-48 bg-gradient-to-br from-blue-50 to-blue-100 flex flex-col items-center justify-center p-4">
                        <i class="fas fa-cloud text-blue-500 text-3xl mb-3"></i>
                        <span class="text-blue-800 font-medium mb-1">Foto Google Drive</span>
                        <span class="text-xs text-blue-600 mb-2 text-center break-all">{{ $fileName }}</span>
                        <div class="text-xs text-blue-500">File tersimpan di cloud storage</div>
                      </div>
                    @else
                      {{-- General unavailable image placeholder --}}
                      <div
                        class="w-full h-48 bg-gradient-to-br from-gray-50 to-gray-100 flex flex-col items-center justify-center p-4">
                        <i class="fas fa-image text-gray-400 text-3xl mb-3"></i>
                        <span class="text-gray-700 font-medium mb-1">Foto Evidence</span>
                        <span class="text-xs text-gray-600 mb-2 text-center break-all">{{ $fileName }}</span>
                        <div class="text-xs text-gray-500">File tidak dapat dimuat</div>
                      </div>
                    @endif
                  @endif
                @else
                  <div class="w-full h-48 flex items-center justify-center bg-gray-50">
                    <div class="text-center text-gray-400">
                      <i class="fas fa-camera text-3xl mb-2"></i>
                      <p class="text-sm">Belum ada foto</p>
                    </div>
                  </div>
                @endif
              </div>

              <!-- Photo Info -->
              <div class="p-4">
                <h3 class="font-medium text-gray-900 mb-2">{{ str_replace('foto_evidence_', '', $photo->photo_field_name) }}
                </h3>

                <div class="flex items-center justify-between text-xs">
                  <span class="px-2 py-1 rounded
                        @if($photo->photo_status === 'ai_pending') bg-yellow-100 text-yellow-800
                        @elseif($photo->photo_status === 'ai_approved') bg-green-100 text-green-800
                        @elseif($photo->photo_status === 'ai_rejected') bg-red-100 text-red-800
                        @endif">
                    {{ ucfirst(str_replace('_', ' ', $photo->photo_status)) }}
                  </span>
                  <span class="text-gray-500">
                    {{ $photo->created_at ? $photo->created_at->format('d/m H:i') : '-' }}
                  </span>
                </div>
              </div>
            </div>
          @endforeach
        </div>

      @else
        <div class="text-center py-8">
          <i class="fas fa-camera text-gray-300 text-4xl mb-3"></i>
          <p class="text-gray-500 text-sm mb-4">Belum ada foto yang diupload</p>
        </div>
      @endif
    </div>

    <!-- Line Number Progress Summary -->
    <div class="bg-white rounded-xl card-shadow p-6">
      <div class="flex items-center gap-3 mb-6">
        <i class="fas fa-chart-bar text-teal-600"></i>
        <h2 class="font-semibold text-gray-800">Progress Line Number</h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="text-center">
          <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Estimasi Panjang</div>
          <div class="text-2xl font-bold text-gray-900">{{ number_format($lowering->lineNumber->estimasi_panjang, 1) }}
          </div>
          <div class="text-sm text-gray-600">meter</div>
        </div>
        <div class="text-center">
          <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Total Penggelaran</div>
          <div class="text-2xl font-bold text-blue-600">{{ number_format($lowering->lineNumber->total_penggelaran, 1) }}
          </div>
          <div class="text-sm text-blue-700">meter</div>
        </div>
        <div class="text-center">
          <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Progress</div>
          <div class="text-2xl font-bold text-green-600">
            {{ number_format($lowering->lineNumber->getProgressPercentage(), 1) }}%</div>
          <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
            <div class="bg-green-600 h-2 rounded-full transition-all duration-300"
              style="width: {{ min(100, $lowering->lineNumber->getProgressPercentage()) }}%"></div>
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
      <img id="modalImage" src="" alt="" class="max-w-full max-h-full object-contain rounded" style="cursor: zoom-in;">
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
    // Photo modal zoom state
    let zoomLevel = 1;
    let isDragging = false;
    let startX, startY, translateX = 0, translateY = 0;

    function openImageModal(imageUrl, title) {
      const modal = document.getElementById('imageModal');
      const img = document.getElementById('modalImage');
      const modalTitle = document.getElementById('modalTitle');

      console.log('Opening modal with URL:', imageUrl);

      // Show loading state
      modalTitle.textContent = 'Loading...';
      modal.classList.remove('hidden');
      modal.classList.add('flex');
      document.body.style.overflow = 'hidden';

      // Reset zoom
      zoomLevel = 1;
      translateX = 0;
      translateY = 0;
      img.classList.remove('zoomed');
      img.style.transform = 'translate(0, 0) scale(1)';

      // Load image with error handling
      img.onerror = function () {
        console.error('Failed to load image:', imageUrl);

        // Extract file ID and try alternative URLs
        const fileIdMatch = imageUrl.match(/\/file\/d\/([a-zA-Z0-9_-]+)|[?&]id=([a-zA-Z0-9_-]+)/);
        const fileId = fileIdMatch ? (fileIdMatch[1] || fileIdMatch[2]) : null;

        if (fileId && !img.dataset.modalRetried) {
          console.log('Trying alternative URL for file ID:', fileId);
          img.dataset.modalRetried = 'true';

          // Try different URL formats (alternatives to lh3 which is the primary)
          const alternatives = [
            `https://drive.google.com/uc?export=view&id=${fileId}`,
            `https://drive.google.com/uc?id=${fileId}`,
            `https://docs.google.com/uc?id=${fileId}`
          ];

          const currentSrc = img.src;
          const nextAlt = alternatives.find(alt => alt !== currentSrc);

          if (nextAlt) {
            console.log('Retrying with:', nextAlt);
            img.src = nextAlt;
          } else {
            showImageError(img, modalTitle, imageUrl);
          }
        } else {
          showImageError(img, modalTitle, imageUrl);
        }
      };

      img.onload = function () {
        console.log('Image loaded successfully');
        modalTitle.textContent = title;
        delete img.dataset.modalRetried;
      };

      // Set image source
      img.src = imageUrl;
    }

    function showImageError(img, modalTitle, originalUrl) {
      modalTitle.innerHTML = '⚠️ Tidak dapat memuat foto. <a href="' + originalUrl + '" target="_blank" class="underline text-blue-400 hover:text-blue-300">Buka di tab baru</a>';
      img.style.display = 'none';

      setTimeout(() => {
        img.style.display = 'block';
      }, 100);
    }

    function closeImageModal(event) {
      if (event) event.stopPropagation();
      const modal = document.getElementById('imageModal');
      modal.classList.add('hidden');
      modal.classList.remove('flex');
      document.body.style.overflow = 'auto';

      // Reset state
      zoomLevel = 1;
      translateX = 0;
      translateY = 0;
      isDragging = false;

      console.log('Modal closed');
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

      img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${zoomLevel})`;
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
    document.addEventListener('DOMContentLoaded', function () {
      const modal = document.getElementById('imageModal');
      const img = document.getElementById('modalImage');

      if (!modal || !img) return;

      modal.addEventListener('click', function (e) {
        if (e.target === modal) {
          closeImageModal();
        }
      });

      img.addEventListener('click', function (e) {
        e.stopPropagation();
      });

      modal.addEventListener('wheel', function (e) {
        const isVisible = !modal.classList.contains('hidden');
        if (isVisible) {
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

      img.addEventListener('mousedown', function (e) {
        if (zoomLevel > 1) {
          isDragging = true;
          startX = e.clientX - translateX;
          startY = e.clientY - translateY;
          e.preventDefault();
        }
      });

      document.addEventListener('mousemove', function (e) {
        if (isDragging && zoomLevel > 1) {
          translateX = e.clientX - startX;
          translateY = e.clientY - startY;
          updateImageTransform();
        }
      });

      document.addEventListener('mouseup', function () {
        isDragging = false;
      });

      document.addEventListener('keydown', function (e) {
        const isVisible = !modal.classList.contains('hidden');
        if (isVisible) {
          if (e.key === 'Escape') {
            closeImageModal();
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

    // Fallback URLs for JALUR_LOWERING internal paths
    function tryFallbackUrls(imgElement) {
      const fallbackUrls = JSON.parse(imgElement.dataset.fallbackUrls || '[]');
      const filename = imgElement.dataset.filename;
      let currentIndex = parseInt(imgElement.dataset.currentIndex || '0');

      currentIndex++;

      if (currentIndex < fallbackUrls.length) {
        imgElement.dataset.currentIndex = currentIndex;
        imgElement.src = fallbackUrls[currentIndex];
        return;
      }

      // All fallback URLs failed, show placeholder
      imgElement.parentElement.innerHTML = `
          <div class="w-full h-48 bg-gradient-to-br from-gray-50 to-gray-100 flex flex-col items-center justify-center p-4">
              <i class="fas fa-image text-gray-400 text-3xl mb-3"></i>
              <span class="text-gray-700 font-medium mb-1">Foto Evidence</span>
              <span class="text-xs text-gray-600 mb-2 text-center break-all">${filename}</span>
              <div class="text-xs text-gray-500">File tidak ditemukan di local storage</div>
          </div>
      `;
    }

    // Advanced Google Drive Photo Display with Multiple Fallbacks (from tracer review)
    function tryAlternativeUrls(imgElement) {
      const fileId = imgElement.dataset.fileId;
      if (!fileId) {
        imgElement.parentElement.innerHTML = '<div class="flex flex-col items-center justify-center h-48 text-red-400"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path></svg><p class="text-xs mt-2">Image unavailable</p><button onclick="viewGoogleDrivePhoto(\'' + imgElement.dataset.originalUrl + '\')" class="text-xs text-blue-500 hover:text-blue-700 mt-1">📁 Buka di Drive</button></div>';
        return;
      }

      const alternatives = [
        `https://lh3.googleusercontent.com/d/${fileId}=w800`,
        `https://drive.google.com/uc?id=${fileId}`,
        `https://docs.google.com/uc?id=${fileId}`,
        `https://drive.google.com/thumbnail?id=${fileId}&sz=w800`
      ];

      let currentIndex = imgElement.dataset.attemptIndex || 0;
      currentIndex = parseInt(currentIndex);

      if (currentIndex < alternatives.length) {
        imgElement.dataset.attemptIndex = currentIndex + 1;
        console.log(`Trying alternative URL ${currentIndex + 1}/${alternatives.length}: ${alternatives[currentIndex]}`);
        imgElement.src = alternatives[currentIndex];
      } else {
        // All alternatives failed, show fallback with link to open in Drive
        console.log('All alternative URLs failed, showing fallback UI');
        imgElement.parentElement.innerHTML = '<div class="flex flex-col items-center justify-center h-48 text-orange-400"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg><p class="text-xs mt-2">Foto Google Drive</p><button onclick="viewGoogleDrivePhoto(\'' + imgElement.dataset.originalUrl + '\')" class="text-xs text-blue-500 hover:text-blue-700 mt-1 px-2 py-1 border border-blue-300 rounded">📁 Buka di Drive</button></div>';
      }
    }

    function loweringShow() {
      return {
        init() { }
      }
    }

    function viewGoogleDrivePhoto(path) {
      // Tampilkan info dan buka Google Drive folder
      const modal = document.createElement('div');
      modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
      modal.innerHTML = `
          <div class="bg-white rounded-lg p-6 max-w-md mx-4">
              <div class="text-center">
                  <i class="fas fa-cloud text-blue-500 text-4xl mb-4"></i>
                  <h3 class="text-lg font-semibold mb-2">Foto Google Drive</h3>
                  <p class="text-sm text-gray-600 mb-4">File: ${path}</p>
                  <p class="text-sm text-gray-500 mb-6">Foto tersimpan di Google Drive. Klik tombol di bawah untuk membuka folder Drive.</p>
                  <div class="flex gap-2 justify-center">
                      <button onclick="this.parentElement.parentElement.parentElement.parentElement.remove()" 
                              class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                          Tutup
                      </button>
                      <button onclick="window.open('https://drive.google.com/drive/folders/{{ config('services.google_drive.folder_id') }}', '_blank'); this.parentElement.parentElement.parentElement.parentElement.remove();" 
                              class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                          <i class="fas fa-external-link-alt mr-1"></i> Buka Drive
                      </button>
                  </div>
              </div>
          </div>
      `;

      // Close on background click
      modal.addEventListener('click', function (e) {
        if (e.target === modal) {
          modal.remove();
        }
      });

      document.body.appendChild(modal);
    }
  </script>
@endsection