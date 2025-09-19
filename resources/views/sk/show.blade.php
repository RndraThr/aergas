@extends('layouts.app')

@section('title', 'Detail SK - AERGAS')

@section('content')
@php
  $sk->loadMissing(['calonPelanggan','photoApprovals']);
  $cfgSlots = (array) (config('aergas_photos.modules.SK.slots') ?? []);
  $slotLabels = [];
  foreach ($cfgSlots as $k => $r) {
    $slotLabels[$k] = $r['label'] ?? $k;
  }
@endphp

<div class="space-y-6" x-data="skShow()" x-init="init()">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Detail SK</h1>
      <p class="text-gray-600 mt-1">Reff ID: <b>{{ $sk->reff_id_pelanggan }}</b></p>
    </div>
    <div class="flex gap-2">
      @if($sk->status === 'draft')
        <a href="{{ route('sk.edit',$sk->id) }}" class="px-4 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">Edit</a>
      @endif
      
      @if(in_array(auth()->user()->role, ['admin', 'super_admin', 'tracer']))
        <a href="{{ route('sk.berita-acara', $sk->id) }}" 
           class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors"
           target="_blank">
          <i class="fas fa-file-pdf"></i>
          Generate Berita Acara
        </a>
      @endif
      
      <a href="javascript:void(0)" onclick="goBackWithPagination('{{ route('sk.index') }}')" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">Kembali</a>
    </div>
  </div>

  <!-- SK Information -->
  <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl shadow-lg p-6 border border-green-200">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
        <i class="fas fa-wrench text-white text-lg"></i>
      </div>
      <div>
        <h2 class="text-xl font-semibold text-gray-800">SK Information</h2>
        <p class="text-green-600 text-sm font-medium">Service connection installation</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="space-y-1">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Created By</div>
        @if($sk->createdBy)
          <div class="flex items-center">
            <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center mr-3 shadow">
              <span class="text-xs font-semibold text-white">
                {{ strtoupper(substr($sk->createdBy->name, 0, 1)) }}
              </span>
            </div>
            <div>
              <div class="font-semibold text-gray-900">{{ $sk->createdBy->name }}</div>
              <div class="text-xs text-gray-500">{{ ucwords(str_replace('_', ' ', $sk->createdBy->role ?? '')) }}</div>
            </div>
          </div>
        @else
          <div class="font-medium text-gray-400">-</div>
        @endif
      </div>

      <div class="space-y-1">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tanggal Instalasi</div>
        <div class="flex items-center">
          <i class="fas fa-calendar-alt text-green-500 mr-2"></i>
          <div class="font-semibold text-gray-900">
            {{ $sk->tanggal_instalasi ? $sk->tanggal_instalasi->format('d F Y') : 'Belum ditentukan' }}
          </div>
        </div>
        @if($sk->tanggal_instalasi)
          <div class="text-xs text-gray-500">
            {{ $sk->tanggal_instalasi->diffForHumans() }}
          </div>
        @endif
      </div>

      <div class="space-y-1">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status</div>
        <div>
          <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium
            @class([
              'bg-gray-100 text-gray-700 border border-gray-200' => $sk->status === 'draft',
              'bg-blue-100 text-blue-800 border border-blue-200' => $sk->status === 'ready_for_tracer',
              'bg-yellow-100 text-yellow-800 border border-yellow-200' => $sk->status === 'approved_scheduled',
              'bg-purple-100 text-purple-800 border border-purple-200' => $sk->status === 'tracer_approved',
              'bg-amber-100 text-amber-800 border border-amber-200' => $sk->status === 'cgp_approved',
              'bg-red-100 text-red-800 border border-red-200' => str_contains($sk->status,'rejected'),
              'bg-green-100 text-green-800 border border-green-200' => $sk->status === 'completed',
            ])
          ">
            <div class="w-2 h-2 rounded-full mr-2
              @class([
                'bg-gray-400' => $sk->status === 'draft',
                'bg-blue-500' => $sk->status === 'ready_for_tracer',
                'bg-yellow-500' => $sk->status === 'approved_scheduled',
                'bg-purple-500' => $sk->status === 'tracer_approved',
                'bg-amber-500' => $sk->status === 'cgp_approved',
                'bg-red-500' => str_contains($sk->status,'rejected'),
                'bg-green-500' => $sk->status === 'completed',
              ])
            "></div>
            {{ ucwords(str_replace('_', ' ', $sk->status)) }}
          </span>
        </div>
      </div>

      <div class="space-y-1">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Created At</div>
        <div class="flex items-center">
          <i class="fas fa-clock text-green-500 mr-2"></i>
          <div>
            <div class="font-semibold text-gray-900">
              {{ $sk->created_at ? $sk->created_at->format('d/m/Y H:i') : '-' }}
            </div>
            @if($sk->created_at)
              <div class="text-xs text-gray-500">
                {{ $sk->created_at->diffForHumans() }}
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Module Status Progress -->
    @if($sk->ai_overall_status || $sk->tracer_approved_at || $sk->cgp_approved_at)
      <div class="mt-6 pt-6 border-t border-green-200">
        <div class="flex items-center gap-4">
          <div class="text-sm font-medium text-gray-700">Progress:</div>

          @if($sk->ai_overall_status)
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full mr-2 {{ $sk->ai_overall_status === 'ready' ? 'bg-green-500' : 'bg-yellow-500' }}"></div>
              <span class="text-sm {{ $sk->ai_overall_status === 'ready' ? 'text-green-700' : 'text-yellow-700' }}">
                AI: {{ ucfirst($sk->ai_overall_status) }}
              </span>
            </div>
          @endif

          @if($sk->tracer_approved_at)
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full bg-purple-500 mr-2"></div>
              <span class="text-sm text-purple-700">
                Tracer: {{ $sk->tracer_approved_at->format('d/m/Y H:i') }}
              </span>
            </div>
          @endif

          @if($sk->cgp_approved_at)
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
              <span class="text-sm text-green-700">
                CGP: {{ $sk->cgp_approved_at->format('d/m/Y H:i') }}
              </span>
            </div>
          @endif
        </div>
      </div>
    @endif
  </div>

  @if($sk->calonPelanggan)
    <div class="bg-white rounded-xl card-shadow p-6">
      <h2 class="font-semibold mb-3 text-gray-800">Customer</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <div class="text-xs text-gray-500">Nama</div>
          <div class="font-medium">{{ $sk->calonPelanggan->nama_pelanggan }}</div>
        </div>
        <div>
          <div class="text-xs text-gray-500">Telepon</div>
          <div class="font-medium">{{ $sk->calonPelanggan->no_telepon ?? '-' }}</div>
        </div>
        <div>
          <div class="text-xs text-gray-500">Alamat</div>
          <div class="font-medium">{{ $sk->calonPelanggan->alamat ?? '-' }}</div>
        </div>
      </div>
    </div>
  @endif

  <!-- Material SK Section -->
  <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center shadow">
        <i class="fas fa-clipboard-list text-white"></i>
      </div>
      <div>
        <h2 class="text-lg font-semibold text-gray-800">Material SK</h2>
        <p class="text-green-600 text-sm">Service connection materials</p>
      </div>
    </div>

    @php
      $materialLabels = [
        'panjang_pipa_gl_medium_m' => 'Panjang Pipa 1/2" GL Medium (meter)',
        'qty_elbow_1_2_galvanis' => 'Elbow 1/2" Galvanis (Pcs)',
        'qty_sockdraft_galvanis_1_2' => 'SockDraft Galvanis Dia 1/2" (Pcs)',
        'qty_ball_valve_1_2' => 'Ball Valve 1/2" (Pcs)',
        'qty_nipel_selang_1_2' => 'Nipel Selang 1/2" (Pcs)',
        'qty_elbow_reduce_3_4_1_2' => 'Elbow Reduce 3/4" x 1/2" (Pcs)',
        'qty_long_elbow_3_4_male_female' => 'Long Elbow 3/4" Male Female (Pcs)',
        'qty_klem_pipa_1_2' => 'Klem Pipa 1/2" (Pcs)',
        'qty_double_nipple_1_2' => 'Double Nipple 1/2" (Pcs)',
        'qty_seal_tape' => 'Seal Tape (Pcs)',
        'qty_tee_1_2' => 'Tee 1/2" (Pcs)',
      ];

      $materialData = [];
      $totalFitting = 0;
      foreach($materialLabels as $field => $label) {
        $value = $sk->$field ?? 0;
        if($value > 0) {
          $materialData[] = ['label' => $label, 'value' => $value, 'field' => $field];
          if($field !== 'panjang_pipa_gl_medium_m') {
            $totalFitting += $value;
          }
        }
      }
    @endphp

    @if(empty($materialData))
      <div class="text-center py-6">
        <i class="fas fa-box-open text-gray-300 text-3xl mb-3"></i>
        <p class="text-gray-500 text-sm">Belum ada data material</p>
      </div>
    @else
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($materialData as $material)
          <div class="bg-gray-50 p-4 rounded-lg border">
            <div class="text-xs text-gray-500 mb-1">{{ $material['label'] }}</div>
            <div class="font-bold text-lg text-gray-800">
              {{ $material['value'] }}
              @if($material['field'] === 'panjang_pipa_gl_medium_m')
                <span class="text-sm font-normal text-gray-600">meter</span>
              @else
                <span class="text-sm font-normal text-gray-600">pcs</span>
              @endif
            </div>
          </div>
        @endforeach
      </div>

      <div class="mt-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200">
        <div class="flex justify-between items-center">
          <span class="font-medium text-green-800">Total Fitting:</span>
          <span class="font-bold text-green-900 text-lg">{{ $totalFitting }} pcs</span>
        </div>
        @if($sk->panjang_pipa_gl_medium_m)
          <div class="flex justify-between items-center mt-2">
            <span class="font-medium text-green-800">Total Pipa:</span>
            <span class="font-bold text-green-900 text-lg">{{ $sk->panjang_pipa_gl_medium_m }} meter</span>
          </div>
        @endif
      </div>
    @endif
  </div>

  <!-- Workflow Actions -->
  @if(in_array(auth()->user()->role, ['tracer', 'admin', 'super_admin']))
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
          <i class="fas fa-tasks text-white"></i>
        </div>
        <div>
          <h3 class="text-xl font-semibold text-gray-800">Workflow Actions</h3>
          <p class="text-gray-600 text-sm">Available actions based on your role and current status</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

        @if($sk->canApproveTracer() && in_array(auth()->user()->role, ['tracer', 'super_admin']))
          <button @click="approveTracer()"
                  class="group flex flex-col items-center p-4 bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition-all duration-200 transform hover:scale-105 shadow-lg">
            <i class="fas fa-check text-2xl mb-2"></i>
            <span class="font-semibold">Approve Tracer</span>
            <span class="text-xs opacity-80 text-center">Validate and approve as tracer</span>
          </button>

          <button @click="rejectTracer()"
                  class="group flex flex-col items-center p-4 bg-gradient-to-br from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all duration-200 transform hover:scale-105 shadow-lg">
            <i class="fas fa-times text-2xl mb-2"></i>
            <span class="font-semibold">Reject Tracer</span>
            <span class="text-xs opacity-80 text-center">Reject with comments</span>
          </button>
        @endif

        @if($sk->canApproveCgp() && in_array(auth()->user()->role, ['admin', 'super_admin']))
          <button @click="approveCgp()"
                  class="group flex flex-col items-center p-4 bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition-all duration-200 transform hover:scale-105 shadow-lg">
            <i class="fas fa-check-double text-2xl mb-2"></i>
            <span class="font-semibold">Approve CGP</span>
            <span class="text-xs opacity-80 text-center">Final CGP approval</span>
          </button>

          <button @click="rejectCgp()"
                  class="group flex flex-col items-center p-4 bg-gradient-to-br from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all duration-200 transform hover:scale-105 shadow-lg">
            <i class="fas fa-times-circle text-2xl mb-2"></i>
            <span class="font-semibold">Reject CGP</span>
            <span class="text-xs opacity-80 text-center">Reject with comments</span>
          </button>
        @endif

        @if($sk->canSchedule())
          <button @click="scheduleSk()"
                  class="group flex flex-col items-center p-4 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all duration-200 transform hover:scale-105 shadow-lg">
            <i class="fas fa-calendar-alt text-2xl mb-2"></i>
            <span class="font-semibold">Schedule SK</span>
            <span class="text-xs opacity-80 text-center">Set installation date</span>
          </button>
        @endif

        @if($sk->canComplete())
          <button @click="completeSk()"
                  class="group flex flex-col items-center p-4 bg-gradient-to-br from-green-500 to-emerald-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition-all duration-200 transform hover:scale-105 shadow-lg">
            <i class="fas fa-check-circle text-2xl mb-2"></i>
            <span class="font-semibold">Complete SK</span>
            <span class="text-xs opacity-80 text-center">Mark as completed</span>
          </button>
        @endif
      </div>
    </div>
  @endif

  <div class="bg-white rounded-xl card-shadow p-6 space-y-4">
    <div class="flex items-center gap-3">
      <i class="fas fa-images text-purple-600"></i>
      <h2 class="font-semibold text-gray-800">Dokumentasi Foto</h2>
    </div>

    @php
      $list = $sk->photoApprovals->sortBy('photo_field_name')->values();
    @endphp

    @if($list->isEmpty())
      <div class="text-center py-8">
        <i class="fas fa-camera text-gray-300 text-4xl mb-3"></i>
        <p class="text-gray-500 text-sm mb-4">Belum ada foto yang diupload</p>
        @if($sk->status === 'draft')
          <a href="{{ route('sk.edit',$sk->id) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
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

            <div class="text-xs text-gray-500 text-center">
              {{ $pa->photo_field_name }}
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</div>

<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4" onclick="closeImageModal()">
  <div class="relative max-w-4xl max-h-full">
    <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300 z-10">
      <i class="fas fa-times"></i>
    </button>
    <img id="modalImage" src="" alt="" class="max-w-full max-h-full object-contain rounded">
    <div id="modalTitle" class="absolute bottom-4 left-4 text-white bg-black bg-opacity-50 px-3 py-1 rounded"></div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function skShow() {
  return {
    init() {},

    async approveTracer() {
      const notes = prompt('Catatan approval (opsional):');
      if (notes === null) return;
      try {
        const response = await fetch(`{{ route('sk.approve-tracer', $sk->id) }}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ notes })
        });
        const result = await response.json();

        if (result.success) {
          window.showToast('success', 'SK berhasil di-approve tracer');
          setTimeout(() => location.reload(), 1000);
        } else {
          window.showToast('error', result.message || 'Gagal approve');
        }
      } catch (error) {
        window.showToast('error', 'Error: ' + error.message);
      }
    },

    async rejectTracer() {
      const notes = prompt('Alasan reject (wajib):');
      if (!notes) return;
      try {
        const response = await fetch(`{{ route('sk.reject-tracer', $sk->id) }}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ notes })
        });
        const result = await response.json();

        if (result.success) {
          window.showToast('success', 'SK berhasil di-reject tracer');
          setTimeout(() => location.reload(), 1000);
        } else {
          window.showToast('error', result.message || 'Gagal reject');
        }
      } catch (error) {
        window.showToast('error', 'Error: ' + error.message);
      }
    },

    async approveCgp() {
      const notes = prompt('Catatan approval (opsional):');
      if (notes === null) return;
      try {
        const response = await fetch(`{{ route('sk.approve-cgp', $sk->id) }}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ notes })
        });
        const result = await response.json();

        if (result.success) {
          window.showToast('success', 'SK berhasil di-approve CGP');
          setTimeout(() => location.reload(), 1000);
        } else {
          window.showToast('error', result.message || 'Gagal approve');
        }
      } catch (error) {
        window.showToast('error', 'Error: ' + error.message);
      }
    },

    async rejectCgp() {
      const notes = prompt('Alasan reject (wajib):');
      if (!notes) return;
      try {
        const response = await fetch(`{{ route('sk.reject-cgp', $sk->id) }}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ notes })
        });
        const result = await response.json();

        if (result.success) {
          window.showToast('success', 'SK berhasil di-reject CGP');
          setTimeout(() => location.reload(), 1000);
        } else {
          window.showToast('error', result.message || 'Gagal reject');
        }
      } catch (error) {
        window.showToast('error', 'Error: ' + error.message);
      }
    },

    async scheduleSk() {
      const date = prompt('Tanggal jadwal instalasi (YYYY-MM-DD):');
      if (!date) return;
      try {
        const response = await fetch(`{{ route('sk.schedule', $sk->id) }}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ scheduled_date: date })
        });
        const result = await response.json();

        if (result.success) {
          window.showToast('success', 'SK berhasil dijadwalkan');
          setTimeout(() => location.reload(), 1000);
        } else {
          window.showToast('error', result.message || 'Gagal jadwalkan');
        }
      } catch (error) {
        window.showToast('error', 'Error: ' + error.message);
      }
    },

    async completeSk() {
      if (!confirm('Apakah Anda yakin ingin menandai SK ini sebagai selesai?')) return;
      try {
        const response = await fetch(`{{ route('sk.complete', $sk->id) }}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          }
        });
        const result = await response.json();

        if (result.success) {
          window.showToast('success', 'SK berhasil diselesaikan');
          setTimeout(() => location.reload(), 1000);
        } else {
          window.showToast('error', result.message || 'Gagal menyelesaikan');
        }
      } catch (error) {
        window.showToast('error', 'Error: ' + error.message);
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
