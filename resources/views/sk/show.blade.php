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
      @if($sk->canEdit() || in_array($sk->module_status, ['draft', 'ai_validation', 'tracer_review', 'rejected']) && auth()->user()->hasAnyRole(['admin', 'super_admin', 'sk', 'tracer']))
        <a href="{{ route('sk.edit',$sk->id) }}" class="px-4 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
          @if($sk->module_status === 'rejected')
            <i class="fas fa-edit mr-1"></i>Edit
          @else
            Edit
          @endif
        </a>
      @endif
      
      @if(auth()->user()->hasAnyRole(['admin', 'super_admin', 'tracer']))
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
    @php
      $hasRejectedPhotos = $sk->photoApprovals->filter(function($photo) {
        return $photo->tracer_rejected_at || $photo->cgp_rejected_at;
      })->isNotEmpty();
      $tracerRejectedCount = $sk->photoApprovals->whereNotNull('tracer_rejected_at')->count();
      $cgpRejectedCount = $sk->photoApprovals->whereNotNull('cgp_rejected_at')->count();
    @endphp

    @if($sk->ai_overall_status || $sk->tracer_approved_at || $sk->cgp_approved_at || $hasRejectedPhotos)
      <div class="mt-6 pt-6 border-t border-green-200">
        <div class="flex items-center gap-4 flex-wrap">
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

          @if($tracerRejectedCount > 0)
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
              <span class="text-sm text-red-700">
                Tracer Rejected: {{ $tracerRejectedCount }} foto
              </span>
            </div>
          @endif

          @if($cgpRejectedCount > 0)
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full bg-orange-500 mr-2"></div>
              <span class="text-sm text-orange-700">
                CGP Rejected: {{ $cgpRejectedCount }} foto
              </span>
            </div>
          @endif
        </div>
      </div>
    @endif
  </div>

  @php
    $rejectedPhotos = $sk->photoApprovals->filter(function($photo) {
      return $photo->tracer_rejected_at || $photo->cgp_rejected_at;
    });
  @endphp

  @if($sk->tracer_approved_at || $sk->cgp_approved_at || $rejectedPhotos->isNotEmpty())
    <div class="bg-white rounded-xl card-shadow p-6">
      <div class="flex items-center gap-3 mb-4">
        <i class="fas fa-clipboard-check text-green-600"></i>
        <h2 class="font-semibold text-gray-800">Timeline Approval</h2>
      </div>

      <div class="space-y-4">
        @if($sk->tracer_approved_at)
          <div class="flex items-start space-x-4 p-4 bg-purple-50 rounded-lg border border-purple-200">
            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
              <i class="fas fa-search text-purple-600"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-medium text-purple-800">Tracer Approval</h4>
                  <p class="text-sm text-purple-600">{{ $sk->tracer_approved_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($sk->tracerApprovedBy)
                  <div class="text-right">
                    <div class="text-sm font-medium text-purple-700">{{ $sk->tracerApprovedBy->name }}</div>
                    <div class="text-xs text-purple-600">{{ ucfirst($sk->tracerApprovedBy->role) }}</div>
                  </div>
                @endif
              </div>
            </div>
          </div>
        @endif

        @if($sk->cgp_approved_at)
          <div class="flex items-start space-x-4 p-4 bg-green-50 rounded-lg border border-green-200">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
              <i class="fas fa-check-circle text-green-600"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-medium text-green-800">CGP Approval</h4>
                  <p class="text-sm text-green-600">{{ $sk->cgp_approved_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($sk->cgpApprovedBy)
                  <div class="text-right">
                    <div class="text-sm font-medium text-green-700">{{ $sk->cgpApprovedBy->name }}</div>
                    <div class="text-xs text-green-600">{{ ucfirst($sk->cgpApprovedBy->role) }}</div>
                  </div>
                @endif
              </div>
            </div>
          </div>
        @endif

        {{-- Tracer Rejections - Show only once with rejector and date --}}
        @php
          $tracerRejection = $rejectedPhotos->where('tracer_rejected_at', '!=', null)->sortByDesc('tracer_rejected_at')->first();
        @endphp
        @if($tracerRejection)
          <div class="flex items-start space-x-4 p-4 bg-red-50 rounded-lg border border-red-200">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
              <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-medium text-red-800">Tracer Rejection</h4>
                  <p class="text-sm text-red-600">{{ $tracerRejection->tracer_rejected_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($tracerRejection->tracerUser)
                  <div class="text-right">
                    <div class="text-sm font-medium text-red-700">{{ $tracerRejection->tracerUser->name }}</div>
                    <div class="text-xs text-red-600">{{ ucfirst($tracerRejection->tracerUser->role) }}</div>
                  </div>
                @endif
              </div>
            </div>
          </div>
        @endif

        {{-- CGP Rejections - Show only once with rejector and date --}}
        @php
          $cgpRejection = $rejectedPhotos->where('cgp_rejected_at', '!=', null)->sortByDesc('cgp_rejected_at')->first();
        @endphp
        @if($cgpRejection)
          <div class="flex items-start space-x-4 p-4 bg-orange-50 rounded-lg border border-orange-200">
            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0">
              <i class="fas fa-exclamation-triangle text-orange-600"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-medium text-orange-800">CGP Rejection</h4>
                  <p class="text-sm text-orange-600">{{ $cgpRejection->cgp_rejected_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($cgpRejection->cgpUser)
                  <div class="text-right">
                    <div class="text-sm font-medium text-orange-700">{{ $cgpRejection->cgpUser->name }}</div>
                    <div class="text-xs text-orange-600">{{ ucfirst($cgpRejection->cgpUser->role) }}</div>
                  </div>
                @endif
              </div>
            </div>
          </div>
        @endif
      </div>
    </div>
  @endif

  @if($sk->calonPelanggan)
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
          <i class="fas fa-user text-white"></i>
        </div>
        <h2 class="text-xl font-semibold text-gray-800">Informasi Pelanggan</h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="space-y-1">
          <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nama Pelanggan</div>
          <div class="font-semibold text-gray-900">{{ $sk->calonPelanggan->nama_pelanggan }}</div>
        </div>
        <div class="space-y-1">
          <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">No. Telepon</div>
          <div class="font-medium text-gray-700">
            @if($sk->calonPelanggan->no_telepon)
              <a href="tel:{{ $sk->calonPelanggan->no_telepon }}" class="text-green-600 hover:text-green-800 transition-colors">
                <i class="fas fa-phone mr-1"></i>{{ $sk->calonPelanggan->no_telepon }}
              </a>
            @else
              <span class="text-gray-400">-</span>
            @endif
          </div>
        </div>
        <div class="space-y-1">
          <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Email</div>
          <div class="font-medium text-gray-700">
            @if($sk->calonPelanggan->email)
              <a href="mailto:{{ $sk->calonPelanggan->email }}" class="text-green-600 hover:text-green-800 transition-colors">
                <i class="fas fa-envelope mr-1"></i>{{ $sk->calonPelanggan->email }}
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
            <div class="font-medium text-gray-700">{{ $sk->calonPelanggan->alamat ?? '-' }}</div>
          </div>
          <div class="space-y-1">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Kelurahan</div>
            <div class="font-medium text-gray-700">
              @if($sk->calonPelanggan->kelurahan)
                <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  <i class="fas fa-map-marker-alt mr-1"></i>{{ $sk->calonPelanggan->kelurahan }}
                </div>
              @else
                <span class="text-gray-400">-</span>
              @endif
            </div>
          </div>
          <div class="space-y-1">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Padukuhan</div>
            <div class="font-medium text-gray-700">
              @if($sk->calonPelanggan->padukuhan)
                <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  <i class="fas fa-home mr-1"></i>{{ $sk->calonPelanggan->padukuhan }}
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
              {{ $sk->calonPelanggan->tanggal_registrasi ? $sk->calonPelanggan->tanggal_registrasi->format('d F Y') : '-' }}
            </div>
          </div>
          <div class="space-y-1">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status Customer</div>
            <div class="font-medium">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                @class([
                  'bg-green-100 text-green-800' => $sk->calonPelanggan->status === 'lanjut',
                  'bg-yellow-100 text-yellow-800' => $sk->calonPelanggan->status === 'pending',
                  'bg-gray-100 text-gray-800' => $sk->calonPelanggan->status === 'menunda',
                  'bg-red-100 text-red-800' => $sk->calonPelanggan->status === 'batal',
                ])
              ">
                {{ ucfirst($sk->calonPelanggan->status) }}
              </span>
            </div>
          </div>
          <div class="space-y-1">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Progress Status</div>
            <div class="font-medium">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                @class([
                  'bg-green-100 text-green-800' => $sk->calonPelanggan->progress_status === 'done',
                  'bg-blue-100 text-blue-800' => $sk->calonPelanggan->progress_status === 'gas_in',
                  'bg-purple-100 text-purple-800' => $sk->calonPelanggan->progress_status === 'sr',
                  'bg-orange-100 text-orange-800' => $sk->calonPelanggan->progress_status === 'sk',
                  'bg-yellow-100 text-yellow-800' => $sk->calonPelanggan->progress_status === 'validasi',
                  'bg-gray-100 text-gray-800' => in_array($sk->calonPelanggan->progress_status, ['pending', 'batal']),
                ])
              ">
                {{ ucwords(str_replace('_', ' ', $sk->calonPelanggan->progress_status)) }}
              </span>
            </div>
          </div>
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

      @if($sk->panjang_pipa_gl_medium_m)
        <div class="mt-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200">
          <div class="flex justify-between items-center">
            <span class="font-medium text-green-800">Total Panjang Pipa:</span>
            <span class="font-bold text-green-900 text-lg">{{ $sk->panjang_pipa_gl_medium_m }} meter</span>
          </div>
        </div>
      @endif
    @endif
  </div>

  <!-- Workflow Actions -->
  @if(auth()->user()->hasAnyRole(['tracer', 'admin', 'super_admin']))
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

        @if($sk->canApproveTracer() && auth()->user()->hasAnyRole(['tracer', 'super_admin']))
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

        @if($sk->canApproveCgp() && auth()->user()->hasAnyRole(['admin', 'super_admin']))
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
      <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
        <i class="fas fa-images text-white"></i>
      </div>
      <h2 class="font-semibold text-gray-800">Dokumentasi Foto</h2>
    </div>

    @php
      // Get all slot completion status (includes both uploaded and missing photos)
      $slotCompletion = $sk->getSlotCompletionStatus();
      $uploadedPhotos = $sk->photoApprovals->keyBy('photo_field_name');
    @endphp

    @if(empty($slotCompletion))
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
                  $photoUrl = "https://lh3.googleusercontent.com/d/{$fileId}";
                }
              @endphp

              @if(!$isPdf)
                <div class="relative group photo-clickable"
                     style="cursor: zoom-in;"
                     data-photo-url="{{ $photoUrl }}"
                     data-photo-title="{{ $slotLabels[$pa->photo_field_name] ?? $pa->photo_field_name }}">
                  <img src="{{ $photoUrl }}"
                       class="w-full h-48 object-cover rounded border hover:opacity-90 transition-opacity"
                       alt="Photo {{ $pa->photo_field_name }}"
                       loading="lazy"
                       onerror="this.onerror=null; this.src='{{ $originalUrl }}'; if(this.onerror) this.style.display='none'; this.nextElementSibling.style.display='block';">

                  <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 rounded flex items-center justify-center opacity-0 group-hover:opacity-100 pointer-events-none">
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

            <div class="text-xs text-gray-500 text-center">
              {{ $slotKey }}
            </div>

            {{-- Rejection Details Dropdown --}}
            @if($isUploaded && $pa->tracer_rejected_at)
              <div class="mt-2">
                <button type="button"
                        onclick="toggleRejectionDetails({{ $pa->id }}, 'tracer')"
                        class="w-full inline-flex items-center justify-between px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200 transition-colors cursor-pointer text-left">
                  <span>🚫 Rejected by Tracer</span>
                  <i id="tracer-chevron-{{ $pa->id }}" class="fas fa-chevron-down transition-transform"></i>
                </button>
                <div id="tracer-details-{{ $pa->id }}" class="hidden mt-2 p-3 bg-red-50 border border-red-200 rounded-lg text-left">
                  <div class="space-y-2">
                    <div>
                      <div class="text-xs text-gray-600">Rejected at:</div>
                      <div class="text-sm font-medium text-gray-800">{{ $pa->tracer_rejected_at->format('d/m/Y H:i') }}</div>
                    </div>
                    @if($pa->tracerUser)
                      <div>
                        <div class="text-xs text-gray-600">Rejected by:</div>
                        <div class="text-sm font-medium text-gray-800">{{ $pa->tracerUser->name }}</div>
                        <div class="text-xs text-gray-500">{{ ucfirst($pa->tracerUser->role) }}</div>
                      </div>
                    @endif
                    @if($pa->tracer_notes)
                      <div>
                        <div class="text-xs text-gray-600">Reason:</div>
                        <div class="text-sm text-gray-800">{{ $pa->tracer_notes }}</div>
                      </div>
                    @endif
                  </div>
                </div>
              </div>
            @elseif($isUploaded && $pa->cgp_rejected_at)
              <div class="mt-2">
                <button type="button"
                        onclick="toggleRejectionDetails({{ $pa->id }}, 'cgp')"
                        class="w-full inline-flex items-center justify-between px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 hover:bg-orange-200 transition-colors cursor-pointer text-left">
                  <span>⚠️ Rejected by CGP</span>
                  <i id="cgp-chevron-{{ $pa->id }}" class="fas fa-chevron-down transition-transform"></i>
                </button>
                <div id="cgp-details-{{ $pa->id }}" class="hidden mt-2 p-3 bg-orange-50 border border-orange-200 rounded-lg text-left">
                  <div class="space-y-2">
                    <div>
                      <div class="text-xs text-gray-600">Rejected at:</div>
                      <div class="text-sm font-medium text-gray-800">{{ $pa->cgp_rejected_at->format('d/m/Y H:i') }}</div>
                    </div>
                    @if($pa->cgpUser)
                      <div>
                        <div class="text-xs text-gray-600">Rejected by:</div>
                        <div class="text-sm font-medium text-gray-800">{{ $pa->cgpUser->name }}</div>
                        <div class="text-xs text-gray-500">{{ ucfirst($pa->cgpUser->role) }}</div>
                      </div>
                    @endif
                    @if($pa->cgp_notes)
                      <div>
                        <div class="text-xs text-gray-600">Reason:</div>
                        <div class="text-sm text-gray-800">{{ $pa->cgp_notes }}</div>
                      </div>
                    @endif
                  </div>
                </div>
              </div>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  </div>
</div>

<div id="imageModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; overflow: hidden;">
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
  <img id="modalImage" src="" alt="" style="max-width: 90%; max-height: 90%; margin: auto; display: block; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); cursor: zoom-in;">
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
  background: rgba(255,255,255,0.9);
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
  background: rgba(255,255,255,1);
  transform: scale(1.1);
}
</style>
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

// Photo modal zoom state
let zoomLevel = 1;
let isDragging = false;
let startX, startY, translateX = 0, translateY = 0;

function openImageModal(imageUrl) {
  const img = document.getElementById('modalImage');
  img.src = imageUrl;
  document.getElementById('imageModal').style.display = 'block';
  document.body.style.overflow = 'hidden';

  // Reset zoom
  zoomLevel = 1;
  translateX = 0;
  translateY = 0;
  updateImageTransform();
  img.classList.remove('zoomed');
}

function closeImageModal(event) {
  if (event) event.stopPropagation();
  document.getElementById('imageModal').style.display = 'none';
  document.body.style.overflow = 'auto';

  // Reset state
  zoomLevel = 1;
  translateX = 0;
  translateY = 0;
  isDragging = false;
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

  img.style.transform = `translate(calc(-50% + ${translateX}px), calc(-50% + ${translateY}px)) scale(${zoomLevel})`;
}

function updateZoomClass() {
  const img = document.getElementById('modalImage');
  if (zoomLevel > 1) {
    img.classList.add('zoomed');
  } else {
    img.classList.remove('zoomed');
  }
}

// Add click event listeners to all photo images
document.addEventListener('DOMContentLoaded', function() {
  const photoImages = document.querySelectorAll('.photo-clickable');

  photoImages.forEach(function(img) {
    img.addEventListener('click', function() {
      const photoUrl = this.getAttribute('data-photo-url');
      openImageModal(photoUrl);
    });
  });

  const modal = document.getElementById('imageModal');
  const img = document.getElementById('modalImage');

  if (!modal || !img) return;

  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      closeImageModal();
    }
  });

  img.addEventListener('click', function(e) {
    e.stopPropagation();
  });

  modal.addEventListener('wheel', function(e) {
    const isVisible = modal.style.display === 'block';
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

  img.addEventListener('mousedown', function(e) {
    if (zoomLevel > 1) {
      isDragging = true;
      startX = e.clientX - translateX;
      startY = e.clientY - translateY;
      e.preventDefault();
    }
  });

  document.addEventListener('mousemove', function(e) {
    if (isDragging && zoomLevel > 1) {
      translateX = e.clientX - startX;
      translateY = e.clientY - startY;
      updateImageTransform();
    }
  });

  document.addEventListener('mouseup', function() {
    isDragging = false;
  });

  document.addEventListener('keydown', function(e) {
    const isVisible = modal.style.display === 'block';
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

// Toggle rejection details dropdown
function toggleRejectionDetails(photoId, type) {
  const detailsDiv = document.getElementById(`${type}-details-${photoId}`);
  const chevron = document.getElementById(`${type}-chevron-${photoId}`);

  if (detailsDiv.classList.contains('hidden')) {
    detailsDiv.classList.remove('hidden');
    chevron.classList.add('rotate-180');
  } else {
    detailsDiv.classList.add('hidden');
    chevron.classList.remove('rotate-180');
  }
}

// Function to go back with pagination state
function goBackWithPagination(baseRoute) {
  const storageKey = 'sk_pagination_state';
  const savedState = localStorage.getItem(storageKey);

  if (savedState) {
    try {
      const state = JSON.parse(savedState);
      // Check if state is recent (within 10 minutes)
      if (Date.now() - state.timestamp < 600000) {
        const url = new URL(baseRoute, window.location.origin);

        // Add pagination and search parameters
        if (state.page && state.page !== '1') {
          url.searchParams.set('page', state.page);
        }

        if (state.search) {
          const savedParams = new URLSearchParams(state.search);
          for (const [key, value] of savedParams) {
            if (key !== 'page') {
              url.searchParams.set(key, value);
            }
          }
        }

        window.location.href = url.href;
        return;
      }
    } catch (e) {
      console.log('Error parsing pagination state:', e);
    }
  }

  // Fallback to base route if no valid state
  window.location.href = baseRoute;
}
</script>
@endpush
