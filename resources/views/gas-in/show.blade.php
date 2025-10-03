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
      @if($gasIn->canEdit() || in_array($gasIn->module_status, ['draft', 'ai_validation', 'tracer_review', 'rejected']) && in_array(auth()->user()->role, ['admin', 'super_admin', 'gas_in', 'tracer']))
        <a href="{{ route('gas-in.edit',$gasIn->id) }}" class="px-4 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
          @if($gasIn->module_status === 'rejected')
            <i class="fas fa-edit mr-1"></i>Perbaiki
          @else
            Edit
          @endif
        </a>
      @endif
      
      @if(in_array(auth()->user()->role, ['admin', 'super_admin', 'tracer']))
        <a href="{{ route('gas-in.berita-acara', $gasIn->id) }}" 
           class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors"
           target="_blank">
          <i class="fas fa-file-pdf"></i>
          Generate Berita Acara
        </a>
      @endif
      
      <a href="javascript:void(0)" onclick="goBackWithPagination('{{ route('gas-in.index') }}')" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">Kembali</a>
    </div>
  </div>

  <!-- Gas In Information -->
  <div class="bg-gradient-to-br from-orange-50 to-amber-50 rounded-xl shadow-lg p-6 border border-orange-200">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl flex items-center justify-center shadow-lg">
        <i class="fas fa-gas-pump text-white text-lg"></i>
      </div>
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Gas In Information</h2>
        <p class="text-orange-600 text-sm font-medium">Final stage - Gas activation and testing</p>
      </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="space-y-1">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Created By</div>
        @if($gasIn->createdBy)
          <div class="flex items-center">
            <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-amber-600 rounded-lg flex items-center justify-center mr-3 shadow">
              <span class="text-xs font-semibold text-white">
                {{ strtoupper(substr($gasIn->createdBy->name, 0, 1)) }}
              </span>
            </div>
            <div>
              <div class="font-semibold text-gray-900">{{ $gasIn->createdBy->name }}</div>
              <div class="text-xs text-gray-500">{{ ucwords(str_replace('_', ' ', $gasIn->createdBy->role ?? '')) }}</div>
            </div>
          </div>
        @else
          <div class="font-medium text-gray-400">-</div>
        @endif
      </div>
      
      <div class="space-y-1">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tanggal Gas In</div>
        <div class="flex items-center">
          <i class="fas fa-calendar-alt text-orange-500 mr-2"></i>
          <div class="font-semibold text-gray-900">
            {{ $gasIn->tanggal_gas_in ? $gasIn->tanggal_gas_in->format('d F Y') : 'Belum ditentukan' }}
          </div>
        </div>
        @if($gasIn->tanggal_gas_in)
          <div class="text-xs text-gray-500">
            {{ $gasIn->tanggal_gas_in->diffForHumans() }}
          </div>
        @endif
      </div>
      
      <div class="space-y-1">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status</div>
        <div>
          <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium
            @class([
              'bg-gray-100 text-gray-700 border border-gray-200' => $gasIn->status === 'draft',
              'bg-blue-100 text-blue-800 border border-blue-200' => $gasIn->status === 'ready_for_tracer',
              'bg-yellow-100 text-yellow-800 border border-yellow-200' => $gasIn->status === 'approved_scheduled',
              'bg-purple-100 text-purple-800 border border-purple-200' => $gasIn->status === 'tracer_approved',
              'bg-amber-100 text-amber-800 border border-amber-200' => $gasIn->status === 'cgp_approved',
              'bg-red-100 text-red-800 border border-red-200' => str_contains($gasIn->status,'rejected'),
              'bg-green-100 text-green-800 border border-green-200' => $gasIn->status === 'completed',
            ])
          ">
            <div class="w-2 h-2 rounded-full mr-2
              @class([
                'bg-gray-400' => $gasIn->status === 'draft',
                'bg-blue-500' => $gasIn->status === 'ready_for_tracer',
                'bg-yellow-500' => $gasIn->status === 'approved_scheduled',
                'bg-purple-500' => $gasIn->status === 'tracer_approved',
                'bg-amber-500' => $gasIn->status === 'cgp_approved',
                'bg-red-500' => str_contains($gasIn->status,'rejected'),
                'bg-green-500' => $gasIn->status === 'completed',
              ])
            "></div>
            {{ ucwords(str_replace('_', ' ', $gasIn->status)) }}
          </span>
        </div>
      </div>
      
      <div class="space-y-1">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Created At</div>
        <div class="flex items-center">
          <i class="fas fa-clock text-orange-500 mr-2"></i>
          <div>
            <div class="font-semibold text-gray-900">
              {{ $gasIn->created_at ? $gasIn->created_at->format('d/m/Y H:i') : '-' }}
            </div>
            @if($gasIn->created_at)
              <div class="text-xs text-gray-500">
                {{ $gasIn->created_at->diffForHumans() }}
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Module Status Progress -->
    @if($gasIn->ai_overall_status || $gasIn->tracer_approved_at || $gasIn->cgp_approved_at)
      <div class="mt-6 pt-6 border-t border-orange-200">
        <div class="flex items-center gap-4">
          <div class="text-sm font-medium text-gray-700">Progress:</div>
          
          @if($gasIn->ai_overall_status)
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full mr-2 {{ $gasIn->ai_overall_status === 'ready' ? 'bg-green-500' : 'bg-yellow-500' }}"></div>
              <span class="text-sm {{ $gasIn->ai_overall_status === 'ready' ? 'text-green-700' : 'text-yellow-700' }}">
                AI: {{ ucfirst($gasIn->ai_overall_status) }}
              </span>
            </div>
          @endif
          
          @if($gasIn->tracer_approved_at)
            <div class="flex items-center">
              <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
              <span class="text-sm text-blue-700">Tracer Approved</span>
            </div>
          @endif
          
          @if($gasIn->cgp_approved_at)
            <div class="flex items-center">
              <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
              <span class="text-sm text-green-700">CGP Approved</span>
            </div>
          @endif
        </div>
      </div>
    @endif
  </div>

  <!-- Customer Information -->
  @if($gasIn->calonPelanggan)
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center">
          <i class="fas fa-user text-white"></i>
        </div>
        <h2 class="text-xl font-semibold text-gray-800">Customer Information</h2>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="space-y-1">
          <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nama Pelanggan</div>
          <div class="font-semibold text-gray-900">{{ $gasIn->calonPelanggan->nama_pelanggan }}</div>
        </div>
        <div class="space-y-1">
          <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">No. Telepon</div>
          <div class="font-medium text-gray-700">
            @if($gasIn->calonPelanggan->no_telepon)
              <a href="tel:{{ $gasIn->calonPelanggan->no_telepon }}" class="text-orange-600 hover:text-orange-800 transition-colors">
                <i class="fas fa-phone mr-1"></i>{{ $gasIn->calonPelanggan->no_telepon }}
              </a>
            @else
              <span class="text-gray-400">-</span>
            @endif
          </div>
        </div>
        <div class="space-y-1">
          <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Email</div>
          <div class="font-medium text-gray-700">
            @if($gasIn->calonPelanggan->email)
              <a href="mailto:{{ $gasIn->calonPelanggan->email }}" class="text-orange-600 hover:text-orange-800 transition-colors">
                <i class="fas fa-envelope mr-1"></i>{{ $gasIn->calonPelanggan->email }}
              </a>
            @else
              <span class="text-gray-400">-</span>
            @endif
          </div>
        </div>
      </div>

      <div class="mt-6 pt-6 border-t border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div class="space-y-1">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Alamat Lengkap</div>
            <div class="font-medium text-gray-700">{{ $gasIn->calonPelanggan->alamat ?? '-' }}</div>
          </div>
          <div class="space-y-1">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Kelurahan</div>
            <div class="font-medium text-gray-700">
              @if($gasIn->calonPelanggan->kelurahan)
                <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                  <i class="fas fa-map-marker-alt mr-1"></i>{{ $gasIn->calonPelanggan->kelurahan }}
                </div>
              @else
                <span class="text-gray-400">-</span>
              @endif
            </div>
          </div>
          <div class="space-y-1">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Padukuhan</div>
            <div class="font-medium text-gray-700">
              @if($gasIn->calonPelanggan->padukuhan)
                <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  <i class="fas fa-home mr-1"></i>{{ $gasIn->calonPelanggan->padukuhan }}
                </div>
              @else
                <span class="text-gray-400">-</span>
              @endif
            </div>
          </div>
        </div>
      </div>

      <!-- Registration Info -->
      <div class="mt-6 pt-6 border-t border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="space-y-1">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tanggal Registrasi</div>
            <div class="font-medium text-gray-700">
              {{ $gasIn->calonPelanggan->tanggal_registrasi ? $gasIn->calonPelanggan->tanggal_registrasi->format('d F Y') : '-' }}
            </div>
          </div>
          <div class="space-y-1">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status Customer</div>
            <div class="font-medium">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                @class([
                  'bg-green-100 text-green-800' => $gasIn->calonPelanggan->status === 'lanjut',
                  'bg-yellow-100 text-yellow-800' => $gasIn->calonPelanggan->status === 'pending',
                  'bg-gray-100 text-gray-800' => $gasIn->calonPelanggan->status === 'menunda',
                  'bg-red-100 text-red-800' => $gasIn->calonPelanggan->status === 'batal',
                ])
              ">
                {{ ucfirst($gasIn->calonPelanggan->status) }}
              </span>
            </div>
          </div>
          <div class="space-y-1">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Progress Status</div>
            <div class="font-medium">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                @class([
                  'bg-green-100 text-green-800' => $gasIn->calonPelanggan->progress_status === 'done',
                  'bg-blue-100 text-blue-800' => $gasIn->calonPelanggan->progress_status === 'gas_in',
                  'bg-purple-100 text-purple-800' => $gasIn->calonPelanggan->progress_status === 'sr',
                  'bg-orange-100 text-orange-800' => $gasIn->calonPelanggan->progress_status === 'sk',
                  'bg-yellow-100 text-yellow-800' => $gasIn->calonPelanggan->progress_status === 'validasi',
                  'bg-gray-100 text-gray-800' => in_array($gasIn->calonPelanggan->progress_status, ['pending', 'batal']),
                ])
              ">
                {{ ucwords(str_replace('_', ' ', $gasIn->calonPelanggan->progress_status)) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- Notes -->
  @if($gasIn->notes)
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
          <i class="fas fa-sticky-note text-white"></i>
        </div>
        <h3 class="text-xl font-semibold text-gray-800">Catatan</h3>
      </div>
      <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg">
        <div class="flex">
          <div class="flex-shrink-0">
            <i class="fas fa-info-circle text-blue-400"></i>
          </div>
          <div class="ml-3">
            <p class="text-gray-700 leading-relaxed">{{ $gasIn->notes }}</p>
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- Photos Section -->
  <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 space-y-4">
    <div class="flex items-center gap-3">
      <i class="fas fa-images text-orange-600"></i>
      <h2 class="font-semibold text-gray-800">Dokumentasi Foto</h2>
    </div>

    @php
      // Get all slot completion status (includes both uploaded and missing photos)
      $slotCompletion = $gasIn->getSlotCompletionStatus();
      $uploadedPhotos = $gasIn->photoApprovals->keyBy('photo_field_name');
    @endphp

    @if(empty($slotCompletion))
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
        @foreach($slotCompletion as $slotKey => $slotInfo)
          @php
            $pa = $uploadedPhotos->get($slotKey);
            $isUploaded = !is_null($pa);
          @endphp
          <div class="border rounded-lg p-4 space-y-3 {{ !$isUploaded ? 'bg-gray-50 border-dashed border-2' : '' }}">
            <div class="flex items-start justify-between">
              <div>
                <div class="text-xs text-gray-500">Slot</div>
                <div class="font-medium">{{ $slotInfo['label'] }}</div>
                @if($slotInfo['required'])
                  <span class="inline-block px-2 py-0.5 text-xs bg-red-100 text-red-700 rounded mt-1">Required</span>
                @else
                  <span class="inline-block px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded mt-1">Optional</span>
                @endif
              </div>
              <div class="text-xs text-gray-500">
                {{ $isUploaded && $pa->created_at ? $pa->created_at->format('d/m H:i') : '-' }}
              </div>
            </div>

            @if($isUploaded && $pa->photo_url)
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
            @elseif(!$isUploaded)
              {{-- Placeholder for photo not uploaded --}}
              <div class="w-full h-48 flex items-center justify-center bg-gray-100 rounded border-2 border-dashed border-gray-300">
                <div class="text-center text-gray-400">
                  <i class="fas fa-image text-4xl mb-2"></i>
                  <div class="text-sm font-medium text-gray-600">Photo Not Uploaded</div>
                  <div class="text-xs text-gray-500 mt-1">Waiting for upload</div>
                </div>
              </div>
            @else
              <div class="w-full h-48 flex items-center justify-center bg-gray-50 rounded border">
                <div class="text-center text-gray-400">
                  <i class="fas fa-image text-3xl mb-2"></i>
                  <div class="text-xs">Foto tidak tersedia</div>
                </div>
              </div>
            @endif

            @if($isUploaded)
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
            @endif

            <div class="text-xs text-gray-500 text-center">
              {{ $slotKey }}
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  <!-- Workflow Actions -->
  @if(in_array(auth()->user()->role, ['tracer', 'admin', 'super_admin']))
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
          <i class="fas fa-tasks text-white"></i>
        </div>
        <div>
          <h3 class="text-xl font-semibold text-gray-800">Workflow Actions</h3>
          <p class="text-gray-600 text-sm">Available actions based on your role and current status</p>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        
        @if($gasIn->canApproveTracer() && in_array(auth()->user()->role, ['tracer', 'super_admin']))
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

        @if($gasIn->canApproveCgp() && in_array(auth()->user()->role, ['admin', 'super_admin']))
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

        @if($gasIn->canSchedule())
          <button @click="scheduleGasIn()" 
                  class="group flex flex-col items-center p-4 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all duration-200 transform hover:scale-105 shadow-lg">
            <i class="fas fa-calendar-alt text-2xl mb-2"></i>
            <span class="font-semibold">Schedule Gas In</span>
            <span class="text-xs opacity-80 text-center">Set gas activation date</span>
          </button>
        @endif

        @if($gasIn->canComplete())
          <button @click="completeGasIn()" 
                  class="group flex flex-col items-center p-4 bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl hover:from-purple-600 hover:to-purple-700 transition-all duration-200 transform hover:scale-105 shadow-lg">
            <i class="fas fa-flag-checkered text-2xl mb-2"></i>
            <span class="font-semibold">Complete Gas In</span>
            <span class="text-xs opacity-80 text-center">Mark as completed</span>
          </button>
        @endif
      </div>
      
      @if(!$gasIn->canApproveTracer() && !$gasIn->canApproveCgp() && !$gasIn->canSchedule() && !$gasIn->canComplete())
        <div class="text-center py-8">
          <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-info-circle text-gray-400 text-2xl"></i>
          </div>
          <p class="text-gray-500 text-sm">No actions available at this time</p>
          <p class="text-gray-400 text-xs mt-1">Current status: {{ ucwords(str_replace('_', ' ', $gasIn->status)) }}</p>
        </div>
      @endif
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