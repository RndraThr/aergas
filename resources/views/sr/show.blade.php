@extends('layouts.app')

@section('title', 'Detail SR - AERGAS')

@section('content')
@php
  $sr->loadMissing(['calonPelanggan','photoApprovals']);
  $cfgSlots = (array) (config('aergas_photos.modules.SR.slots') ?? []);
  $slotLabels = [];
  foreach ($cfgSlots as $k => $r) {
    $slotLabels[$k] = $r['label'] ?? $k;
  }
@endphp

<div class="space-y-6" x-data="srShow()" x-init="init()">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Detail SR</h1>
      <p class="text-gray-600 mt-1">Reff ID: <b>{{ $sr->reff_id_pelanggan }}</b></p>
    </div>
    <div class="flex gap-2">
      @if($sr->status === 'draft')
        <a href="{{ route('sr.edit',$sr->id) }}" class="px-4 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">Edit</a>
      @endif
      
      @if(in_array(auth()->user()->role, ['admin', 'super_admin', 'tracer']))
        <a href="{{ route('sr.berita-acara', $sr->id) }}" 
           class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors"
           target="_blank">
          <i class="fas fa-file-pdf"></i>
          Generate Berita Acara
        </a>
      @endif
      
      <a href="{{ route('sr.index') }}" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">Kembali</a>
    </div>
  </div>

  <!-- SR Information -->
  <div class="bg-gradient-to-br from-yellow-50 to-amber-50 rounded-xl shadow-lg p-6 border border-yellow-200">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-amber-600 rounded-xl flex items-center justify-center shadow-lg">
        <i class="fas fa-road text-white text-lg"></i>
      </div>
      <div>
        <h2 class="text-xl font-semibold text-gray-800">SR Information</h2>
        <p class="text-yellow-600 text-sm font-medium">Service regulator installation</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="space-y-1">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Created By</div>
        @if($sr->createdBy)
          <div class="flex items-center">
            <div class="w-8 h-8 bg-gradient-to-br from-yellow-500 to-amber-600 rounded-lg flex items-center justify-center mr-3 shadow">
              <span class="text-xs font-semibold text-white">
                {{ strtoupper(substr($sr->createdBy->name, 0, 1)) }}
              </span>
            </div>
            <div>
              <div class="font-semibold text-gray-900">{{ $sr->createdBy->name }}</div>
              <div class="text-xs text-gray-500">{{ ucwords(str_replace('_', ' ', $sr->createdBy->role ?? '')) }}</div>
            </div>
          </div>
        @else
          <div class="font-medium text-gray-400">-</div>
        @endif
      </div>

      <div class="space-y-1">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tanggal Pemasangan</div>
        <div class="flex items-center">
          <i class="fas fa-calendar-alt text-yellow-500 mr-2"></i>
          <div class="font-semibold text-gray-900">
            {{ $sr->tanggal_pemasangan ? $sr->tanggal_pemasangan->format('d F Y') : 'Belum ditentukan' }}
          </div>
        </div>
        @if($sr->tanggal_pemasangan)
          <div class="text-xs text-gray-500">
            {{ $sr->tanggal_pemasangan->diffForHumans() }}
          </div>
        @endif
      </div>

      <div class="space-y-1">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status</div>
        <div>
          <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium
            @class([
              'bg-gray-100 text-gray-700 border border-gray-200' => $sr->status === 'draft',
              'bg-blue-100 text-blue-800 border border-blue-200' => $sr->status === 'ready_for_tracer',
              'bg-yellow-100 text-yellow-800 border border-yellow-200' => $sr->status === 'approved_scheduled',
              'bg-purple-100 text-purple-800 border border-purple-200' => $sr->status === 'tracer_approved',
              'bg-amber-100 text-amber-800 border border-amber-200' => $sr->status === 'cgp_approved',
              'bg-red-100 text-red-800 border border-red-200' => str_contains($sr->status,'rejected'),
              'bg-green-100 text-green-800 border border-green-200' => $sr->status === 'completed',
            ])
          ">
            <div class="w-2 h-2 rounded-full mr-2
              @class([
                'bg-gray-400' => $sr->status === 'draft',
                'bg-blue-500' => $sr->status === 'ready_for_tracer',
                'bg-yellow-500' => $sr->status === 'approved_scheduled',
                'bg-purple-500' => $sr->status === 'tracer_approved',
                'bg-amber-500' => $sr->status === 'cgp_approved',
                'bg-red-500' => str_contains($sr->status,'rejected'),
                'bg-green-500' => $sr->status === 'completed',
              ])
            "></div>
            {{ ucwords(str_replace('_', ' ', $sr->status)) }}
          </span>
        </div>
      </div>

      <div class="space-y-1">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Created At</div>
        <div class="flex items-center">
          <i class="fas fa-clock text-yellow-500 mr-2"></i>
          <div>
            <div class="font-semibold text-gray-900">
              {{ $sr->created_at ? $sr->created_at->format('d/m/Y H:i') : '-' }}
            </div>
            @if($sr->created_at)
              <div class="text-xs text-gray-500">
                {{ $sr->created_at->diffForHumans() }}
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- MGRT Information Section -->
    @if($sr->no_seri_mgrt || $sr->merk_brand_mgrt || $sr->jenis_tapping || $sr->notes)
      <div class="mt-6 pt-6 border-t border-yellow-200">
        <div class="mb-4">
          <h3 class="text-sm font-medium text-gray-700 flex items-center">
            <i class="fas fa-gas-pump text-yellow-500 mr-2"></i>
            Additional Information
          </h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          @if($sr->jenis_tapping)
            <div class="space-y-1">
              <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Jenis Tapping</div>
              <div class="font-semibold text-gray-900">{{ $sr->jenis_tapping }}</div>
            </div>
          @endif

          @if($sr->no_seri_mgrt)
            <div class="space-y-1">
              <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">No. Seri MGRT</div>
              <div class="font-semibold text-yellow-600">{{ $sr->no_seri_mgrt }}</div>
            </div>
          @endif

          @if($sr->merk_brand_mgrt)
            <div class="space-y-1">
              <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Merk/Brand MGRT</div>
              <div class="font-semibold text-gray-900">{{ $sr->merk_brand_mgrt }}</div>
            </div>
          @endif

          @if($sr->notes)
            <div class="space-y-1 {{ !$sr->jenis_tapping && !$sr->no_seri_mgrt && !$sr->merk_brand_mgrt ? 'md:col-span-2 lg:col-span-4' : 'lg:col-span-1' }}">
              <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Catatan Petugas</div>
              <div class="font-medium text-gray-700 text-sm">{{ $sr->notes }}</div>
            </div>
          @endif
        </div>
      </div>
    @endif

    <!-- Module Status Progress -->
    @if($sr->ai_overall_status || $sr->tracer_approved_at || $sr->cgp_approved_at)
      <div class="mt-6 pt-6 border-t border-yellow-200">
        <div class="flex items-center gap-4">
          <div class="text-sm font-medium text-gray-700">Progress:</div>

          @if($sr->ai_overall_status)
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full mr-2 {{ $sr->ai_overall_status === 'ready' ? 'bg-yellow-500' : 'bg-amber-500' }}"></div>
              <span class="text-sm {{ $sr->ai_overall_status === 'ready' ? 'text-yellow-700' : 'text-amber-700' }}">
                AI: {{ ucfirst($sr->ai_overall_status) }}
              </span>
            </div>
          @endif

          @if($sr->tracer_approved_at)
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full bg-purple-500 mr-2"></div>
              <span class="text-sm text-purple-700">
                Tracer: {{ $sr->tracer_approved_at->format('d/m/Y H:i') }}
              </span>
            </div>
          @endif

          @if($sr->cgp_approved_at)
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
              <span class="text-sm text-green-700">
                CGP: {{ $sr->cgp_approved_at->format('d/m/Y H:i') }}
              </span>
            </div>
          @endif
        </div>
      </div>
    @endif
  </div>

  @if($sr->tracer_approved_at || $sr->cgp_approved_at)
    <div class="bg-white rounded-xl card-shadow p-6">
      <div class="flex items-center gap-3 mb-4">
        <i class="fas fa-clipboard-check text-green-600"></i>
        <h2 class="font-semibold text-gray-800">Timeline Approval</h2>
      </div>

      <div class="space-y-4">
        @if($sr->tracer_approved_at)
          <div class="flex items-start space-x-4 p-4 bg-purple-50 rounded-lg border border-purple-200">
            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
              <i class="fas fa-search text-purple-600"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-medium text-purple-800">Tracer Approval</h4>
                  <p class="text-sm text-purple-600">{{ $sr->tracer_approved_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($sr->tracerApprovedBy)
                  <div class="text-right">
                    <div class="text-sm font-medium text-purple-700">{{ $sr->tracerApprovedBy->name }}</div>
                    <div class="text-xs text-purple-600">{{ ucfirst($sr->tracerApprovedBy->role) }}</div>
                  </div>
                @endif
              </div>
              @if($sr->tracer_notes)
                <div class="mt-2 p-2 bg-white rounded border">
                  <div class="text-xs text-gray-500 mb-1">Notes:</div>
                  <div class="text-sm text-gray-700">{{ $sr->tracer_notes }}</div>
                </div>
              @endif
            </div>
          </div>
        @endif

        @if($sr->cgp_approved_at)
          <div class="flex items-start space-x-4 p-4 bg-green-50 rounded-lg border border-green-200">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
              <i class="fas fa-check-circle text-green-600"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-medium text-green-800">CGP Approval</h4>
                  <p class="text-sm text-green-600">{{ $sr->cgp_approved_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($sr->cgpApprovedBy)
                  <div class="text-right">
                    <div class="text-sm font-medium text-green-700">{{ $sr->cgpApprovedBy->name }}</div>
                    <div class="text-xs text-green-600">{{ ucfirst($sr->cgpApprovedBy->role) }}</div>
                  </div>
                @endif
              </div>
              @if($sr->cgp_notes)
                <div class="mt-2 p-2 bg-white rounded border">
                  <div class="text-xs text-gray-500 mb-1">Notes:</div>
                  <div class="text-sm text-gray-700">{{ $sr->cgp_notes }}</div>
                </div>
              @endif
            </div>
          </div>
        @endif
      </div>
    </div>
  @endif

  @if($sr->calonPelanggan)
    <div class="bg-white rounded-xl card-shadow p-6">
      <div class="flex items-center gap-3 mb-4">
        <i class="fas fa-user text-blue-600"></i>
        <h2 class="font-semibold text-gray-800">Informasi Customer</h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <div class="text-xs text-gray-500">Reference ID</div>
          <div class="font-medium text-blue-600">{{ $sr->reff_id_pelanggan }}</div>
        </div>
        <div>
          <div class="text-xs text-gray-500">Nama Pelanggan</div>
          <div class="font-medium">{{ $sr->calonPelanggan->nama_pelanggan }}</div>
        </div>
        <div>
          <div class="text-xs text-gray-500">No. Telepon</div>
          <div class="font-medium">{{ $sr->calonPelanggan->no_telepon ?? '-' }}</div>
        </div>
        <div>
          <div class="text-xs text-gray-500">Status Customer</div>
          <span class="px-2 py-0.5 rounded text-xs
            @class([
              'bg-gray-100 text-gray-700' => $sr->calonPelanggan->status === 'pending',
              'bg-green-100 text-green-800' => $sr->calonPelanggan->status === 'lanjut',
              'bg-blue-100 text-blue-800' => $sr->calonPelanggan->status === 'in_progress',
              'bg-red-100 text-red-800' => $sr->calonPelanggan->status === 'batal',
            ])
          ">{{ strtoupper($sr->calonPelanggan->status ?? '-') }}</span>
        </div>
        <div class="md:col-span-2 lg:col-span-3">
          <div class="text-xs text-gray-500">Alamat</div>
          <div class="font-medium">{{ $sr->calonPelanggan->alamat ?? '-' }}</div>
        </div>
        <div>
          <div class="text-xs text-gray-500">Kelurahan</div>
          <div class="font-medium">{{ $sr->calonPelanggan->kelurahan ?? '-' }}</div>
        </div>
        @if($sr->calonPelanggan->padukuhan)
          <div class="md:col-span-1">
            <div class="text-xs text-gray-500">Padukuhan</div>
            <div class="font-medium">{{ $sr->calonPelanggan->padukuhan }}</div>
          </div>
        @endif
      </div>
    </div>
  @endif

  <!-- Material SR Section -->
  <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-amber-600 rounded-lg flex items-center justify-center shadow">
        <i class="fas fa-clipboard-list text-white"></i>
      </div>
      <div>
        <h2 class="text-lg font-semibold text-gray-800">Material SR</h2>
        <p class="text-yellow-600 text-sm">Service regulator materials</p>
      </div>
    </div>

    @php
      $materialLabels = $sr->getMaterialLabels();
      $materialData = [];
      $totalItems = 0;
      $totalLengths = 0;

      foreach($sr->getRequiredMaterialItems() as $field => $value) {
        if($value > 0) {
          $materialData[] = ['label' => $materialLabels[$field] ?? $field, 'value' => $value, 'field' => $field];
          if(str_contains($field, 'panjang_')) {
            $totalLengths += $value;
          } else {
            $totalItems += $value;
          }
        }
      }

      foreach($sr->getOptionalMaterialItems() as $field => $value) {
        if($value && $value > 0) {
          $materialData[] = ['label' => $materialLabels[$field] ?? $field, 'value' => $value, 'field' => $field];
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
              @if(str_contains($material['field'], 'panjang_'))
                <span class="text-sm font-normal text-gray-600">meter</span>
              @else
                <span class="text-sm font-normal text-gray-600">pcs</span>
              @endif
            </div>
          </div>
        @endforeach
      </div>

      <div class="mt-6 p-4 bg-gradient-to-r from-yellow-50 to-amber-50 rounded-lg border border-yellow-200">
        <div class="flex justify-between items-center">
          <span class="font-medium text-yellow-800">Total Items:</span>
          <span class="font-bold text-yellow-900 text-lg">{{ $totalItems }} pcs</span>
        </div>
        <div class="flex justify-between items-center mt-2">
          <span class="font-medium text-yellow-800">Total Lengths:</span>
          <span class="font-bold text-yellow-900 text-lg">{{ $totalLengths }} meter</span>
        </div>
        @if($sr->jenis_tapping)
          <div class="flex justify-between items-center mt-2">
            <span class="font-medium text-yellow-800">Jenis Tapping:</span>
            <span class="font-bold text-yellow-900 text-lg">{{ $sr->jenis_tapping }}</span>
          </div>
        @endif
      </div>
    @endif
  </div>

  <!-- Workflow Actions -->
  @if(in_array(auth()->user()->role, ['tracer', 'admin', 'super_admin']))
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-amber-600 rounded-xl flex items-center justify-center">
          <i class="fas fa-tasks text-white"></i>
        </div>
        <div>
          <h3 class="text-xl font-semibold text-gray-800">Workflow Actions</h3>
          <p class="text-gray-600 text-sm">Available actions based on your role and current status</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

        @if($sr->canApproveTracer() && in_array(auth()->user()->role, ['tracer', 'super_admin']))
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

        @if($sr->canApproveCgp() && in_array(auth()->user()->role, ['admin', 'super_admin']))
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

        @if($sr->canSchedule())
          <button @click="scheduleSr()"
                  class="group flex flex-col items-center p-4 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all duration-200 transform hover:scale-105 shadow-lg">
            <i class="fas fa-calendar-alt text-2xl mb-2"></i>
            <span class="font-semibold">Schedule SR</span>
            <span class="text-xs opacity-80 text-center">Set installation date</span>
          </button>
        @endif

        @if($sr->canComplete())
          <button @click="completeSr()"
                  class="group flex flex-col items-center p-4 bg-gradient-to-br from-yellow-500 to-amber-600 text-white rounded-xl hover:from-yellow-600 hover:to-amber-700 transition-all duration-200 transform hover:scale-105 shadow-lg">
            <i class="fas fa-check-circle text-2xl mb-2"></i>
            <span class="font-semibold">Complete SR</span>
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
      $list = $sr->photoApprovals->sortBy('photo_field_name')->values();
    @endphp

    @if($list->isEmpty())
      <div class="text-center py-8">
        <i class="fas fa-camera text-gray-300 text-4xl mb-3"></i>
        <p class="text-gray-500 text-sm mb-4">Belum ada foto yang diupload</p>
        @if($sr->status === 'draft')
          <a href="{{ route('sr.edit',$sr->id) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
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
function srShow() {
  return {
    init() {},

    async approveTracer() {
      if (confirm('Are you sure you want to approve this SR?')) {
        try {
          const response = await fetch(`/sr/{{ $sr->id }}/approve-tracer`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
          });

          if (response.ok) {
            location.reload();
          } else {
            alert('Failed to approve SR');
          }
        } catch (error) {
          alert('Error: ' + error.message);
        }
      }
    },

    async rejectTracer() {
      const reason = prompt('Please provide a reason for rejection:');
      if (reason) {
        try {
          const response = await fetch(`/sr/{{ $sr->id }}/reject-tracer`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ reason })
          });

          if (response.ok) {
            location.reload();
          } else {
            alert('Failed to reject SR');
          }
        } catch (error) {
          alert('Error: ' + error.message);
        }
      }
    },

    async approveCgp() {
      if (confirm('Are you sure you want to approve this SR as CGP?')) {
        try {
          const response = await fetch(`/sr/{{ $sr->id }}/approve-cgp`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
          });

          if (response.ok) {
            location.reload();
          } else {
            alert('Failed to approve SR as CGP');
          }
        } catch (error) {
          alert('Error: ' + error.message);
        }
      }
    },

    async rejectCgp() {
      const reason = prompt('Please provide a reason for CGP rejection:');
      if (reason) {
        try {
          const response = await fetch(`/sr/{{ $sr->id }}/reject-cgp`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ reason })
          });

          if (response.ok) {
            location.reload();
          } else {
            alert('Failed to reject SR as CGP');
          }
        } catch (error) {
          alert('Error: ' + error.message);
        }
      }
    },

    async scheduleSr() {
      const date = prompt('Please enter the scheduled date (YYYY-MM-DD):');
      if (date) {
        try {
          const response = await fetch(`/sr/{{ $sr->id }}/schedule`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ scheduled_date: date })
          });

          if (response.ok) {
            location.reload();
          } else {
            alert('Failed to schedule SR');
          }
        } catch (error) {
          alert('Error: ' + error.message);
        }
      }
    },

    async completeSr() {
      if (confirm('Are you sure you want to mark this SR as completed?')) {
        try {
          const response = await fetch(`/sr/{{ $sr->id }}/complete`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
          });

          if (response.ok) {
            location.reload();
          } else {
            alert('Failed to complete SR');
          }
        } catch (error) {
          alert('Error: ' + error.message);
        }
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
