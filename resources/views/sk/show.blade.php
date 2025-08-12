{{-- resources/views/sk/show.blade.php --}}
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
      <a href="{{ route('sk.edit',$sk->id) }}" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">Edit</a>
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
      <div class="text-gray-500 text-sm">Belum ada foto. Silakan upload di halaman Edit.</div>
    @else
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($list as $pa)
          <div class="border rounded-lg p-4 space-y-3" x-data="paCard({{ $pa->id }})">
            <div class="flex items-start justify-between">
              <div>
                <div class="text-xs text-gray-500">Slot</div>
                <div class="font-medium">{{ $slotLabels[$pa->photo_field_name] ?? $pa->photo_field_name }}</div>
              </div>
              <div>
                <span class="px-2 py-0.5 rounded text-xs"
                  :class="badgeClass('{{ $pa->ai_status }}')"
                >{{ strtoupper($pa->ai_status ?? 'pending') }}</span>
              </div>
            </div>

            @if($pa->photo_url)
              @php $isPdf = str_ends_with(Str::lower($pa->photo_url), '.pdf'); @endphp
              @if(!$isPdf)
                <img src="{{ $pa->photo_url }}" class="w-full h-40 object-cover rounded border">
              @else
                <div class="w-full h-40 flex items-center justify-center bg-gray-50 rounded border">
                  <span class="text-xs text-gray-600">PDF</span>
                </div>
              @endif
            @endif

            <div class="text-xs">
              <div class="text-gray-500">AI Notes</div>
              <div class="text-gray-800">{{ $pa->ai_notes ?? '-' }}</div>
            </div>

            @if(is_array($pa->ai_checks) && count($pa->ai_checks))
              <div class="text-xs">
                <div class="text-gray-500 mb-1">Checks</div>
                <ul class="space-y-1">
                  @foreach($pa->ai_checks as $c)
                    <li class="flex items-center gap-2">
                      @if(!empty($c['passed']))
                        <span class="text-green-600">✓</span>
                      @else
                        <span class="text-red-600">✗</span>
                      @endif
                      <span>{{ $c['id'] ?? '-' }}</span>
                      @if(isset($c['confidence']))
                        <span class="text-gray-400">({{ number_format($c['confidence']*100,0) }}%)</span>
                      @endif
                      @if(!empty($c['reason']))
                        <span class="text-gray-400">- {{ $c['reason'] }}</span>
                      @endif
                    </li>
                  @endforeach
                </ul>
              </div>
            @endif

            <div class="flex items-center justify-between pt-1">
              <div class="text-xs text-gray-500">Terakhir dicek: {{ $pa->ai_last_checked_at ?? '-' }}</div>
              <button type="button" @click="recheck"
                      class="px-3 py-1.5 text-xs bg-gray-100 rounded hover:bg-gray-200">
                Recheck
              </button>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
function skShow(){ return { init(){} } }
function paCard(id){
  return {
    async recheck(){
      try{
        const url = @json(route('sk.photos.recheck', ['sk'=>$sk->id,'photo'=>'__ID__'])).replace('__ID__', id);
        const res = await fetch(url, { method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())}});
        const j = await res.json().catch(()=>({}));
        if(!res.ok || j.success===false) throw new Error(j?.message || 'Gagal recheck');
        window.showToast?.('Recheck berhasil.', 'success');
        // reload supaya status/notes terupdate
        window.location.reload();
      }catch(e){
        console.error(e);
        window.showToast?.(e.message || 'Recheck gagal', 'error');
      }
    },
    badgeClass(st){
      st = (st||'').toLowerCase();
      if(st==='passed') return 'bg-green-100 text-green-800';
      if(st==='failed') return 'bg-red-100 text-red-800';
      if(st==='pending') return 'bg-gray-100 text-gray-700';
      return 'bg-blue-100 text-blue-800';
    }
  }
}
</script>
@endpush
