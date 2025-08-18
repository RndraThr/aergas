{{-- resources/views/sk/show.blade.php - UPDATED --}}
@extends('layouts.app')

@section('title', 'Detail SK - AERGAS')

@section('content')
@php
  /** @var \App\Models\SkData $sk */
  $sk->loadMissing(['calonPelanggan','photoApprovals']);
  $cfgSlots = (array) (config('aergas_photos.modules.SK.slots') ?? []);
  // Map slot -> label agar kartu foto ada judul yang konsisten
  $slotLabels = [];
  foreach ($cfgSlots as $k => $r) { $slotLabels[$k] = $r['label'] ?? $k; }
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
      <a href="{{ route('sk.index') }}" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">Kembali</a>
    </div>
  </div>

  {{-- Ringkasan --}}
  <div class="bg-white rounded-xl card-shadow p-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div>
        <div class="text-xs text-gray-500">Nomor SK</div>
        <div class="font-medium">{{ $sk->nomor_sk ?? '-' }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-500">Tanggal Instalasi</div>
        <div class="font-medium">{{ $sk->tanggal_instalasi ?? '-' }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-500">Status</div>
        <span class="px-2 py-0.5 rounded text-xs
          @class([
            'bg-gray-100 text-gray-700' => $sk->status === 'draft',
            'bg-blue-100 text-blue-800' => $sk->status === 'ready_for_tracer',
            'bg-yellow-100 text-yellow-800' => $sk->status === 'scheduled',
            'bg-purple-100 text-purple-800' => $sk->status === 'tracer_approved',
            'bg-amber-100 text-amber-800' => $sk->status === 'cgp_approved',
            'bg-red-100 text-red-800' => str_contains($sk->status,'rejected'),
            'bg-green-100 text-green-800' => $sk->status === 'completed',
          ])
        ">{{ strtoupper($sk->status) }}</span>
      </div>
      <div>
        <div class="text-xs text-gray-500">AI Overall</div>
        <div class="font-medium">{{ $sk->ai_overall_status ?? '-' }}</div>
      </div>
    </div>
  </div>

  {{-- Customer --}}
  @if($sk->calonPelanggan)
    <div class="bg-white rounded-xl card-shadow p-6">
      <h2 class="font-semibold mb-3 text-gray-800">Customer</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div><div class="text-xs text-gray-500">Nama</div><div class="font-medium">{{ $sk->calonPelanggan->nama_pelanggan }}</div></div>
        <div><div class="text-xs text-gray-500">Telepon</div><div class="font-medium">{{ $sk->calonPelanggan->no_telepon ?? '-' }}</div></div>
        <div><div class="text-xs text-gray-500">Alamat</div><div class="font-medium">{{ $sk->calonPelanggan->alamat ?? '-' }}</div></div>
      </div>
    </div>
  @endif

  {{-- Foto & Status AI --}}
  <div class="bg-white rounded-xl card-shadow p-6 space-y-4">
    <div class="flex items-center gap-3">
      <i class="fas fa-images text-purple-600"></i>
      <h2 class="font-semibold text-gray-800">Foto & Validasi AI</h2>
    </div>

    @php
      /** @var \Illuminate\Support\Collection $list */
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
              <div>
                <span class="px-2 py-0.5 rounded text-xs" :class="badgeClass('{{ $pa->ai_status }}')">
                  {{ strtoupper($pa->ai_status ?? 'pending') }}
                </span>
              </div>
            </div>

            @if($pa->photo_url)
              @php $isPdf = str_ends_with(Str::lower($pa->photo_url), '.pdf'); @endphp
              @if(!$isPdf)
                <img src="{{ $pa->photo_url }}" class="w-full h-40 object-cover rounded border" alt="Photo {{ $pa->photo_field_name }}">
              @else
                <div class="w-full h-40 flex items-center justify-center bg-gray-50 rounded border">
                  <div class="text-center">
                    <i class="fas fa-file-pdf text-red-500 text-2xl mb-2"></i>
                    <div class="text-xs text-gray-600">PDF Document</div>
                  </div>
                </div>
              @endif
            @endif

            {{-- AI Score & Status --}}
            <div class="flex items-center justify-between text-xs">
              <div class="text-gray-500">
                Score: <span class="font-medium">{{ $pa->ai_score ? number_format($pa->ai_score, 1) : '-' }}</span>
              </div>
              <div class="text-gray-500">
                {{ $pa->ai_last_checked_at ? $pa->ai_last_checked_at->format('d/m H:i') : '-' }}
              </div>
            </div>

            {{-- AI Notes --}}
            @if($pa->ai_notes)
              <div class="text-xs">
                <div class="text-gray-500 mb-1">AI Notes</div>
                <div class="text-gray-800 bg-gray-50 p-2 rounded text-xs">{{ $pa->ai_notes }}</div>
              </div>
            @endif

            {{-- AI Checks Detail --}}
            @if(is_array($pa->ai_checks) && count($pa->ai_checks))
              <div class="text-xs">
                <div class="text-gray-500 mb-1">Validasi Detail</div>
                <ul class="space-y-1">
                  @foreach($pa->ai_checks as $c)
                    <li class="flex items-center gap-2 text-xs">
                      @if(!empty($c['passed']))
                        <span class="text-green-600 text-sm">✓</span>
                      @else
                        <span class="text-red-600 text-sm">✗</span>
                      @endif
                      <span class="flex-1">{{ $c['id'] ?? '-' }}</span>
                      @if(isset($c['confidence']))
                        <span class="text-gray-400">({{ number_format($c['confidence']*100,0) }}%)</span>
                      @endif
                    </li>
                    @if(!empty($c['reason']) && $c['reason'] !== 'ok')
                      <li class="text-gray-500 ml-6 text-xs">{{ $c['reason'] }}</li>
                    @endif
                  @endforeach
                </ul>
              </div>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  </div>

  {{-- Action Buttons for Status Management --}}
  @if($sk->status !== 'completed' && $sk->status !== 'canceled')
    <div class="bg-white rounded-xl card-shadow p-6">
      <h3 class="font-semibold text-gray-800 mb-4">Aksi</h3>
      <div class="flex gap-3">
        @if($sk->status === 'ready_for_tracer')
          <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
            Approve (Tracer)
          </button>
          <button class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
            Reject (Tracer)
          </button>
        @elseif($sk->status === 'tracer_approved')
          <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
            Approve (CGP)
          </button>
          <button class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
            Reject (CGP)
          </button>
        @elseif($sk->status === 'cgp_approved')
          <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Schedule Installation
          </button>
        @endif
      </div>
    </div>
  @endif
</div>
@endsection

@push('scripts')
<script>
function skShow() {
  return {
    init() {},

    badgeClass(status) {
      const st = (status || '').toLowerCase();
      if (st === 'passed') return 'bg-green-100 text-green-800';
      if (st === 'failed') return 'bg-red-100 text-red-800';
      if (st === 'pending') return 'bg-gray-100 text-gray-700';
      return 'bg-blue-100 text-blue-800';
    }
  }
}
</script>
@endpush
