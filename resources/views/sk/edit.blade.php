{{-- resources/views/sk/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit SK - AERGAS')

@section('content')
@php
  /** @var \App\Models\SkData $sk */
  // Siapkan photoDefs dari config
  $cfgSlots = (array) (config('aergas_photos.modules.SK.slots') ?? []);
  $photoDefs = [];
  foreach ($cfgSlots as $key => $rule) {
      $accept = $rule['accept'] ?? ['image/*'];
      if (is_string($accept)) $accept = [$accept];
      $checks = collect($rule['checks'] ?? [])->map(fn($c) => $c['label'] ?? $c['id'] ?? '')->filter()->values()->all();
      $photoDefs[] = [
          'field' => $key,
          'label' => $rule['label'] ?? $key,
          'accept' => $accept,
          'required_objects' => $checks,
      ];
  }
@endphp

<div class="space-y-6" x-data="skEdit()" x-init="init()">

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Edit SK</h1>
      <p class="text-gray-600 mt-1">Reff ID: <b>{{ $sk->reff_id_pelanggan }}</b></p>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('sk.show',$sk->id) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Detail</a>
      <a href="{{ route('sk.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Kembali</a>
    </div>
  </div>

  {{-- Info utama --}}
  <div class="bg-white rounded-xl card-shadow p-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
    </div>
  </div>

  {{-- Upload foto tambahan / reupload --}}
  <div class="bg-white rounded-xl card-shadow p-6 space-y-4">
    <div class="flex items-center gap-3">
      <i class="fas fa-camera text-purple-600"></i>
      <h2 class="font-semibold text-gray-800">Upload / Re-upload Foto</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      @foreach ($photoDefs as $ph)
        <div class="border rounded-lg p-4" x-data="slotUploader('{{ $ph['field'] }}')">
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ $ph['label'] }}</label>

          <template x-if="!preview">
            <div class="h-32 flex items-center justify-center bg-gray-50 rounded border-dashed border text-gray-400">Tidak ada file</div>
          </template>
          <template x-if="preview && !isPdf">
            <img :src="preview" class="h-32 w-full object-cover rounded">
          </template>
          <template x-if="isPdf">
            <div class="h-32 flex items-center justify-center bg-gray-50 rounded border">
              <span class="text-xs text-gray-600">PDF terpilih</span>
            </div>
          </template>

          <div class="flex items-center gap-2 mt-3">
            <input class="hidden" type="file"
                   id="file_{{ $ph['field'] }}"
                   accept="{{ implode(',', (array) $ph['accept']) }}"
                   @change="onPick($event)">
            <label for="file_{{ $ph['field'] }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded cursor-pointer">Pilih</label>

            <button type="button" @click="clearPick" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded">Hapus</button>

            <button type="button" @click="upload" :disabled="!file || uploading"
                    class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
              <span x-show="!uploading">Upload</span>
              <span x-show="uploading"><i class="fas fa-spinner fa-spin mr-1"></i>Uploading…</span>
            </button>

            <span class="text-xs" x-text="statusMsg"></span>
          </div>

          @if(!empty($ph['required_objects']))
            <div class="mt-2 text-xs text-gray-500">
              Objek wajib:
              @foreach($ph['required_objects'] as $obj)
                <span class="px-2 py-0.5 mr-1 mb-1 bg-gray-100 rounded border inline-block">{{ $obj }}</span>
              @endforeach
            </div>
          @endif
        </div>
      @endforeach
    </div>

    <p class="text-xs text-gray-500">Format: JPG/PNG/WEBP, Isometrik boleh PDF. Maks 10 MB per file.</p>
  </div>
</div>
@endsection

@push('scripts')
<script>
function skEdit(){ return { init(){} } }

function slotUploader(slot){
  return {
    file:null, preview:null, isPdf:false, uploading:false, statusMsg:'',
    onPick(e){
      const f = e.target.files?.[0]; if(!f) return;
      this.file = f; this.isPdf = (f.type === 'application/pdf');
      if(!this.isPdf){
        const r = new FileReader();
        r.onload = () => this.preview = r.result;
        r.readAsDataURL(f);
      } else {
        this.preview = null;
      }
      this.statusMsg = '';
    },
    clearPick(){
      this.file=null; this.preview=null; this.isPdf=false; this.statusMsg='';
    },
    async upload(){
      if(!this.file) return;
      this.uploading = true;
      try{
        const url = @json(route('sk.photos.upload', ['sk'=>$sk->id]));
        const fd = new FormData();
        fd.append('_token', @json(csrf_token()));
        fd.append('slot_type', slot);
        fd.append('file', this.file);

        const res = await fetch(url, {
          method:'POST',
          headers:{ 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' },
          body: fd
        });
        const j = await res.json().catch(()=>({}));
        if(!res.ok || !(j && (j.success===true || j.photo_id))) throw new Error(j?.message || 'Gagal upload');
        this.statusMsg = '✓ uploaded';
        window.showToast?.('Upload berhasil & divalidasi AI.', 'success');
      }catch(e){
        console.error(e); this.statusMsg='✗ gagal';
        window.showToast?.(e.message || 'Upload gagal', 'error');
      }finally{
        this.uploading=false;
      }
    }
  }
}
</script>
@endpush
