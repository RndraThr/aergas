@extends('layouts.app')

@section('title', 'Detail Gas In - AERGAS')

@section('content')
@php
  use Illuminate\Support\Str;
  
  $gasIn->loadMissing(['calonPelanggan','photoApprovals']);
  $cfgSlots = (array) (config('aergas_photos.modules.GAS_IN.slots') ?? []);
  $slotLabels = [];
  foreach ($cfgSlots as $k => $r) {
    $slotLabels[$k] = $r['label'] ?? $k;
  }
@endphp

<div class="space-y-6" x-data="gasInShow()" x-init="init()">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Detail Gas In</h1>
      <p class="text-gray-600 mt-1">Reff ID: <b>{{ $gasIn->reff_id_pelanggan }}</b></p>
    </div>
    <div class="flex gap-2">
      @if($gasIn->status === 'draft')
        <a href="{{ route('gas-in.edit',$gasIn->id) }}" class="px-4 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">Edit</a>
      @endif
      
      @if(in_array(auth()->user()->role, ['admin', 'super_admin', 'tracer']))
        <a href="{{ route('gas-in.berita-acara', $gasIn->id) }}" 
           class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors"
           target="_blank">
          <i class="fas fa-file-pdf"></i>
          Generate Berita Acara
        </a>
      @endif
      
      <a href="{{ route('gas-in.index') }}" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">Kembali</a>
    </div>
  </div>

  <!-- Basic Information -->
  <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div>
        <div class="text-xs text-gray-500">Created By</div>
        @if($gasIn->createdBy)
          <div class="flex items-center mt-1">
            <div class="w-6 h-6 bg-orange-100 rounded-full flex items-center justify-center mr-2">
              <span class="text-xs font-medium text-orange-600">
                {{ strtoupper(substr($gasIn->createdBy->name, 0, 1)) }}
              </span>
            </div>
            <span class="font-medium">{{ $gasIn->createdBy->name }}</span>
          </div>
        @else
          <div class="font-medium text-gray-400">-</div>
        @endif
      </div>
      <div>
        <div class="text-xs text-gray-500">Tanggal Gas In</div>
        <div class="font-medium">{{ $gasIn->tanggal_gas_in ? $gasIn->tanggal_gas_in->format('d/m/Y') : '-' }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-500">Status</div>
        <span class="px-2 py-0.5 rounded text-xs
          @class([
            'bg-gray-100 text-gray-700' => $gasIn->status === 'draft',
            'bg-blue-100 text-blue-800' => $gasIn->status === 'ready_for_tracer',
            'bg-yellow-100 text-yellow-800' => $gasIn->status === 'approved_scheduled',
            'bg-purple-100 text-purple-800' => $gasIn->status === 'tracer_approved',
            'bg-amber-100 text-amber-800' => $gasIn->status === 'cgp_approved',
            'bg-red-100 text-red-800' => str_contains($gasIn->status,'rejected'),
            'bg-green-100 text-green-800' => $gasIn->status === 'completed',
          ])
        ">{{ strtoupper($gasIn->status) }}</span>
      </div>
      <div>
        <div class="text-xs text-gray-500">Created At</div>
        <div class="font-medium">{{ $gasIn->created_at ? $gasIn->created_at->format('d/m/Y H:i') : '-' }}</div>
      </div>
    </div>
  </div>

  <!-- Customer Information -->
  @if($gasIn->calonPelanggan)
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
      <h2 class="font-semibold mb-3 text-gray-800">Customer Information</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <div class="text-xs text-gray-500">Nama</div>
          <div class="font-medium">{{ $gasIn->calonPelanggan->nama_pelanggan }}</div>
        </div>
        <div>
          <div class="text-xs text-gray-500">Telepon</div>
          <div class="font-medium">{{ $gasIn->calonPelanggan->no_telepon ?? '-' }}</div>
        </div>
        <div>
          <div class="text-xs text-gray-500">Alamat</div>
          <div class="font-medium">{{ $gasIn->calonPelanggan->alamat ?? '-' }}</div>
        </div>
      </div>
    </div>
  @endif

  <!-- Notes -->
  @if($gasIn->notes)
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
      <h3 class="font-semibold mb-3 text-gray-800">Catatan</h3>
      <p class="text-gray-700">{{ $gasIn->notes }}</p>
    </div>
  @endif

  <!-- Photos Section -->
  <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 space-y-4">
    <div class="flex items-center gap-3">
      <i class="fas fa-images text-orange-600"></i>
      <h2 class="font-semibold text-gray-800">Dokumentasi Foto</h2>
    </div>

    @php
      $list = $gasIn->photoApprovals->sortBy('photo_field_name')->values();
    @endphp

    @if($list->isEmpty())
      <div class="text-center py-8">
        <i class="fas fa-camera text-gray-300 text-4xl mb-3"></i>
        <p class="text-gray-500 text-sm mb-4">Belum ada foto yang diupload</p>
        @if($gasIn->status === 'draft')
          <a href="{{ route('gas-in.edit',$gasIn->id) }}" class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">
            Upload Foto
          </a>
        @endif
      </div>
    @else
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($list as $pa)
          <div class="border rounded-lg p-4 space-y-3">
            <div class="flex items-start justify-between">
              <div>
                <div class="text-xs text-gray-500">Slot</div>
                <div class="font-medium">{{ $slotLabels[$pa->photo_field_name] ?? $pa->photo_field_name }}</div>
              </div>
              <div class="text-xs text-gray-500">
                {{ $pa->created_at ? $pa->created_at->format('d/m H:i') : '-' }}
              </div>
            </div>

            @if($pa->photo_url)
              @php
                $originalUrl = $pa->photo_url;
                $photoUrl = $originalUrl;
                $isPdf = str_ends_with(Str::lower($originalUrl), '.pdf');

                if (strpos($originalUrl, 'drive.google.com') !== false && preg_match('/\/file\/d\/([a-zA-Z0-9-_]+)/', $originalUrl, $matches)) {
                  $fileId = $matches[1];
                  $photoUrl = "https://lh3.googleusercontent.com/d/{$fileId}=w800";
                }
              @endphp

              @if(!$isPdf)
                <div class="relative group">
                  <img src="{{ $photoUrl }}"
                       class="w-full h-48 object-cover rounded border cursor-pointer hover:opacity-90 transition-opacity"
                       alt="Photo {{ $pa->photo_field_name }}"
                       loading="lazy"
                       onerror="this.onerror=null; this.src='{{ $originalUrl }}'; if(this.onerror) this.style.display='none'; this.nextElementSibling.style.display='block';"
                       onclick="openImageModal('{{ $photoUrl }}', '{{ $slotLabels[$pa->photo_field_name] ?? $pa->photo_field_name }}')">

                  <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 rounded flex items-center justify-center opacity-0 group-hover:opacity-100">
                    <i class="fas fa-search-plus text-white text-xl"></i>
                  </div>

                  <div class="w-full h-48 bg-gray-100 rounded border-2 border-dashed border-gray-300 flex-col items-center justify-center text-gray-500 hidden">
                    <i class="fas fa-image text-3xl mb-2"></i>
                    <p class="text-xs text-center mb-2">Foto tidak dapat dimuat</p>
                    <a href="{{ $originalUrl }}" target="_blank" class="text-xs text-blue-600 hover:underline">
                      Buka di tab baru
                    </a>
                  </div>
                </div>
              @else
                <div class="w-full h-48 flex flex-col items-center justify-center bg-gray-50 rounded border hover:bg-gray-100 transition-colors cursor-pointer"
                     onclick="window.open('{{ $originalUrl }}', '_blank')">
                  <i class="fas fa-file-pdf text-red-500 text-4xl mb-3"></i>
                  <div class="text-sm text-gray-600 text-center font-medium">PDF Document</div>
                  <div class="text-xs text-blue-600 mt-1">Klik untuk membuka</div>
                </div>
              @endif
            @else
              <div class="w-full h-48 flex items-center justify-center bg-gray-50 rounded border">
                <div class="text-center text-gray-400">
                  <i class="fas fa-image text-3xl mb-2"></i>
                  <div class="text-xs">Foto tidak tersedia</div>
                </div>
              </div>
            @endif

            <!-- Status Badge -->
            <div class="flex items-center justify-between">
              <span class="px-2 py-0.5 rounded text-xs
                @class([
                  'bg-gray-100 text-gray-700' => $pa->photo_status === 'draft',
                  'bg-blue-100 text-blue-800' => $pa->photo_status === 'tracer_pending',
                  'bg-purple-100 text-purple-800' => $pa->photo_status === 'tracer_approved',
                  'bg-yellow-100 text-yellow-800' => $pa->photo_status === 'cgp_pending',
                  'bg-green-100 text-green-800' => $pa->photo_status === 'cgp_approved',
                  'bg-red-100 text-red-800' => str_contains($pa->photo_status ?? '', 'rejected'),
                ])
              ">{{ $pa->photo_status ? strtoupper(str_replace('_', ' ', $pa->photo_status)) : 'DRAFT' }}</span>
              
              @if($pa->ai_score)
                <span class="text-xs text-gray-500">
                  AI: {{ number_format($pa->ai_score, 1) }}%
                </span>
              @endif
            </div>

            <!-- AI Analysis (if available) -->
            @if($pa->ai_reason)
              <div class="text-xs text-gray-600 bg-gray-50 p-2 rounded">
                {{ Str::limit($pa->ai_reason, 80) }}
              </div>
            @endif

            <div class="text-xs text-gray-500 text-center">
              {{ $pa->photo_field_name }}
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  <!-- Workflow Actions -->
  @if(in_array(auth()->user()->role, ['tracer', 'admin', 'super_admin']))
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
      <h3 class="font-semibold mb-4 text-gray-800">Workflow Actions</h3>
      <div class="flex flex-wrap gap-3">
        
        @if($gasIn->canApproveTracer() && in_array(auth()->user()->role, ['tracer', 'super_admin']))
          <button @click="approveTracer()" 
                  class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
            <i class="fas fa-check mr-2"></i>Approve Tracer
          </button>
          <button @click="rejectTracer()" 
                  class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors">
            <i class="fas fa-times mr-2"></i>Reject Tracer
          </button>
        @endif

        @if($gasIn->canApproveCgp() && in_array(auth()->user()->role, ['admin', 'super_admin']))
          <button @click="approveCgp()" 
                  class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
            <i class="fas fa-check mr-2"></i>Approve CGP
          </button>
          <button @click="rejectCgp()" 
                  class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors">
            <i class="fas fa-times mr-2"></i>Reject CGP
          </button>
        @endif

        @if($gasIn->canSchedule())
          <button @click="scheduleGasIn()" 
                  class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
            <i class="fas fa-calendar mr-2"></i>Schedule Gas In
          </button>
        @endif

        @if($gasIn->canComplete())
          <button @click="completeGasIn()" 
                  class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition-colors">
            <i class="fas fa-flag-checkered mr-2"></i>Complete Gas In
          </button>
        @endif
      </div>
    </div>
  @endif
</div>

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4" onclick="closeImageModal()">
  <div class="relative max-w-4xl max-h-full">
    <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300 z-10">
      <i class="fas fa-times"></i>
    </button>
    <img id="modalImage" src="" alt="" class="max-w-full max-h-full object-contain rounded">
    <div id="modalTitle" class="absolute bottom-4 left-4 text-white bg-black bg-opacity-50 px-3 py-1 rounded"></div>
  </div>
</div>

@push('scripts')
<script>
function gasInShow() {
    return {
        init() {
            console.log('Gas In Show initialized');
        },

        async approveTracer() {
            const notes = prompt('Catatan approval (opsional):');
            if (notes === null) return;

            try {
                const response = await fetch(`{{ route('gas-in.approve-tracer', $gasIn->id) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ notes })
                });

                const result = await response.json();
                
                if (result.success) {
                    window.showToast('success', 'Gas In berhasil di-approve tracer');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    window.showToast('error', result.message || 'Gagal approve');
                }
            } catch (error) {
                console.error('Error:', error);
                window.showToast('error', 'Terjadi kesalahan');
            }
        },

        async rejectTracer() {
            const notes = prompt('Alasan reject (wajib):');
            if (!notes) return;

            try {
                const response = await fetch(`{{ route('gas-in.reject-tracer', $gasIn->id) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ notes })
                });

                const result = await response.json();
                
                if (result.success) {
                    window.showToast('success', 'Gas In berhasil di-reject tracer');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    window.showToast('error', result.message || 'Gagal reject');
                }
            } catch (error) {
                console.error('Error:', error);
                window.showToast('error', 'Terjadi kesalahan');
            }
        },

        async approveCgp() {
            const notes = prompt('Catatan approval (opsional):');
            if (notes === null) return;

            try {
                const response = await fetch(`{{ route('gas-in.approve-cgp', $gasIn->id) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ notes })
                });

                const result = await response.json();
                
                if (result.success) {
                    window.showToast('success', 'Gas In berhasil di-approve CGP');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    window.showToast('error', result.message || 'Gagal approve');
                }
            } catch (error) {
                console.error('Error:', error);
                window.showToast('error', 'Terjadi kesalahan');
            }
        },

        async rejectCgp() {
            const notes = prompt('Alasan reject (wajib):');
            if (!notes) return;

            try {
                const response = await fetch(`{{ route('gas-in.reject-cgp', $gasIn->id) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ notes })
                });

                const result = await response.json();
                
                if (result.success) {
                    window.showToast('success', 'Gas In berhasil di-reject CGP');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    window.showToast('error', result.message || 'Gagal reject');
                }
            } catch (error) {
                console.error('Error:', error);
                window.showToast('error', 'Terjadi kesalahan');
            }
        },

        async scheduleGasIn() {
            const tanggal = prompt('Tanggal Gas In (YYYY-MM-DD):');
            if (!tanggal) return;

            try {
                const response = await fetch(`{{ route('gas-in.schedule', $gasIn->id) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ tanggal_gas_in: tanggal })
                });

                const result = await response.json();
                
                if (result.success) {
                    window.showToast('success', 'Gas In berhasil dijadwalkan');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    window.showToast('error', result.message || 'Gagal schedule');
                }
            } catch (error) {
                console.error('Error:', error);
                window.showToast('error', 'Terjadi kesalahan');
            }
        },

        async completeGasIn() {
            if (!confirm('Apakah yakin menyelesaikan Gas In ini?')) return;

            try {
                const response = await fetch(`{{ route('gas-in.complete', $gasIn->id) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const result = await response.json();
                
                if (result.success) {
                    window.showToast('success', 'Gas In berhasil diselesaikan');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    window.showToast('error', result.message || 'Gagal complete');
                }
            } catch (error) {
                console.error('Error:', error);
                window.showToast('error', 'Terjadi kesalahan');
            }
        }
    }
}

function openImageModal(imageUrl, title) {
  const modal = document.getElementById('imageModal');
  const modalImage = document.getElementById('modalImage');
  const modalTitle = document.getElementById('modalTitle');

  modalImage.src = imageUrl;
  modalTitle.textContent = title;
  modal.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function closeImageModal() {
  const modal = document.getElementById('imageModal');
  modal.classList.add('hidden');
  document.body.style.overflow = 'auto';
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeImageModal();
  }
});
</script>
@endpush
@endsection