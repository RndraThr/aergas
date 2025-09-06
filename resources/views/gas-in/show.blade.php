@extends('layouts.app')

@section('title', 'Detail Gas In - AERGAS')

@section('content')
@php
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
  <div class="bg-white rounded-xl shadow-lg border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
      <h2 class="font-semibold text-gray-800">Foto Dokumentasi</h2>
      <div class="flex items-center gap-2">
        <span class="text-sm text-gray-500">Status AI:</span>
        <span class="px-2 py-0.5 rounded text-xs
          @class([
            'bg-green-100 text-green-700' => $gasIn->ai_overall_status === 'ready',
            'bg-yellow-100 text-yellow-700' => $gasIn->ai_overall_status === 'pending',
            'bg-gray-100 text-gray-700' => !$gasIn->ai_overall_status,
          ])
        ">{{ $gasIn->ai_overall_status ? strtoupper($gasIn->ai_overall_status) : 'PENDING' }}</span>
      </div>
    </div>

    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($slotLabels as $slot => $label)
          @php
            $photos = $gasIn->photoApprovals->where('photo_field_name', $slot)->values();
            $latestPhoto = $photos->first();
          @endphp
          
          <div class="border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
              <h4 class="font-medium text-gray-800">{{ $label }}</h4>
              @if($latestPhoto)
                <span class="px-2 py-0.5 rounded text-xs
                  @class([
                    'bg-gray-100 text-gray-700' => $latestPhoto->photo_status === 'draft',
                    'bg-blue-100 text-blue-800' => $latestPhoto->photo_status === 'tracer_pending',
                    'bg-purple-100 text-purple-800' => $latestPhoto->photo_status === 'tracer_approved',
                    'bg-yellow-100 text-yellow-800' => $latestPhoto->photo_status === 'cgp_pending',
                    'bg-green-100 text-green-800' => $latestPhoto->photo_status === 'cgp_approved',
                    'bg-red-100 text-red-800' => str_contains($latestPhoto->photo_status ?? '', 'rejected'),
                  ])
                ">{{ $latestPhoto->photo_status ? strtoupper(str_replace('_', ' ', $latestPhoto->photo_status)) : 'DRAFT' }}</span>
              @else
                <span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-500">BELUM UPLOAD</span>
              @endif
            </div>

            @if($latestPhoto && $latestPhoto->photo_url)
              <div class="mb-3">
                <img src="{{ $latestPhoto->photo_url }}" 
                     alt="{{ $label }}" 
                     class="w-full h-48 object-cover rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
                     onclick="openImageModal('{{ $latestPhoto->photo_url }}', '{{ $label }}')">
              </div>

              <!-- AI Analysis -->
              @if($latestPhoto->ai_status)
                <div class="text-xs space-y-1">
                  <div class="flex items-center justify-between">
                    <span class="text-gray-500">AI Status:</span>
                    <span class="
                      @class([
                        'text-green-600' => $latestPhoto->ai_status === 'passed',
                        'text-red-600' => $latestPhoto->ai_status === 'failed',
                        'text-yellow-600' => $latestPhoto->ai_status === 'warning',
                      ])
                    ">{{ strtoupper($latestPhoto->ai_status) }}</span>
                  </div>
                  @if($latestPhoto->ai_score)
                    <div class="flex items-center justify-between">
                      <span class="text-gray-500">AI Score:</span>
                      <span class="font-medium">{{ number_format($latestPhoto->ai_score, 1) }}%</span>
                    </div>
                  @endif
                  @if($latestPhoto->ai_reason)
                    <div class="mt-2 p-2 bg-gray-50 rounded text-xs">
                      <strong>AI Analysis:</strong> {{ $latestPhoto->ai_reason }}
                    </div>
                  @endif
                </div>
              @endif

              <!-- Approval History -->
              @if($latestPhoto->tracer_approved_at || $latestPhoto->cgp_approved_at)
                <div class="mt-3 text-xs space-y-1 border-t pt-2">
                  @if($latestPhoto->tracer_approved_at)
                    <div class="flex items-center text-green-600">
                      <i class="fas fa-check mr-1"></i>
                      Tracer: {{ $latestPhoto->tracer_approved_at->format('d/m/Y H:i') }}
                    </div>
                  @endif
                  @if($latestPhoto->cgp_approved_at)
                    <div class="flex items-center text-green-600">
                      <i class="fas fa-check mr-1"></i>
                      CGP: {{ $latestPhoto->cgp_approved_at->format('d/m/Y H:i') }}
                    </div>
                  @endif
                </div>
              @endif
            @else
              <div class="h-48 bg-gray-100 rounded-lg flex items-center justify-center">
                <div class="text-center text-gray-500">
                  <i class="fas fa-image text-3xl mb-2"></i>
                  <div class="text-sm">Belum ada foto</div>
                </div>
              </div>
            @endif
          </div>
        @endforeach
      </div>
    </div>
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
<div x-show="imageModal.show" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50" 
     @click="imageModal.show = false">
  <div class="max-w-4xl max-h-full p-4" @click.stop>
    <img :src="imageModal.url" :alt="imageModal.title" class="max-w-full max-h-full object-contain">
    <div class="text-center mt-4">
      <h3 class="text-white text-lg font-medium" x-text="imageModal.title"></h3>
      <button @click="imageModal.show = false" class="mt-2 px-4 py-2 bg-white text-black rounded">Close</button>
    </div>
  </div>
</div>

@push('scripts')
<script>
function gasInShow() {
    return {
        imageModal: {
            show: false,
            url: '',
            title: ''
        },

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

function openImageModal(url, title) {
    const app = Alpine.data;
    // This would need to be handled differently in a real Alpine.js context
    // For now, we'll use a simple approach
    window.dispatchEvent(new CustomEvent('open-image-modal', { 
        detail: { url, title } 
    }));
}

// Listen for the custom event
window.addEventListener('open-image-modal', (e) => {
    // This would need proper Alpine.js integration
    console.log('Open image modal:', e.detail);
});
</script>
@endpush
@endsection