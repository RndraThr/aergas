@extends('layouts.app')

@section('title', 'Buat Gas In - AERGAS')

@section('content')

@php
 $cfgAll   = config('aergas_photos') ?: [];
 $cfgSlots = (array) (data_get($cfgAll, 'modules.GAS_IN.slots', []));
 $photoDefs = [];
 foreach ($cfgSlots as $key => $rule) {
     $accept = $rule['accept'] ?? ['image/*'];
     if (is_string($accept)) $accept = [$accept];
     $photoDefs[] = [
         'field' => $key,
         'label' => $rule['label'] ?? $key,
         'accept' => $accept,
     ];
 }
 if (empty($photoDefs)) {
     $photoDefs = [
         ['field'=>'ba_gas_in','label'=>'Berita Acara Gas In','accept'=>['image/*','application/pdf']],
         ['field'=>'foto_bubble_test','label'=>'Foto Bubble Test (Uji Kebocoran)','accept'=>['image/*']],
         ['field'=>'foto_regulator','label'=>'Foto Regulator Service','accept'=>['image/*']],
         ['field'=>'foto_kompor_menyala','label'=>'Foto Kompor Menyala','accept'=>['image/*']],
     ];
 }
@endphp

<div class="space-y-6" x-data="gasInCreate()" x-init="init()">

 <div class="flex items-center justify-between">
   <div>
     <h1 class="text-3xl font-bold text-gray-800">Buat Gas In</h1>
     <p class="text-gray-600 mt-1">Masukkan Reference ID untuk auto-fill data customer</p>
   </div>
   <a href="{{ route('gas-in.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Kembali</a>
 </div>

 @if ($errors->any())
   <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded">
     <div class="font-semibold mb-2">Periksa input:</div>
     <ul class="list-disc ml-5 space-y-1">
       @foreach ($errors->all() as $err)
         <li>{{ $err }}</li>
       @endforeach
     </ul>
   </div>
 @endif

 <form class="bg-white rounded-xl card-shadow p-6 space-y-8" @submit.prevent="onSubmit">
   @csrf

   <div class="space-y-3">
     <div class="flex items-center gap-3">
       <i class="fas fa-user text-blue-600"></i>
       <h2 class="font-semibold text-gray-800">Informasi Customer</h2>
     </div>

     <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
       <div class="md:col-span-10">
         <input type="text" x-model="reff"
                placeholder="Ketik Reference ID…"
                class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
       </div>
       <div class="md:col-span-2">
         <button type="button" @click="findCustomer"
                 class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
           <i class="fas fa-search mr-2"></i> Cari
         </button>
       </div>
     </div>

     <p class="text-sm mt-1" :class="reffMsgClass()" x-text="reffMsg"></p>

     <template x-if="customer">
       <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded border">
         <div>
           <div class="text-xs text-gray-500">Nama Pelanggan</div>
           <div class="font-medium" x-text="customer.nama_pelanggan"></div>
         </div>
         <div>
           <div class="text-xs text-gray-500">No. Telepon</div>
           <div class="font-medium" x-text="customer.no_telepon || '-'"></div>
         </div>
         <div class="md:col-span-2">
           <div class="text-xs text-gray-500">Alamat</div>
           <div class="font-medium" x-text="customer.alamat || '-' "></div>
           <div class="text-sm text-gray-600 mt-1">
             <span class="mr-4">Kelurahan: <b x-text="customer.kelurahan || '-'"></b></span>
             <span>Padukuhan: <b x-text="customer.padukuhan || '-'"></b></span>
           </div>
         </div>
       </div>
     </template>
   </div>

   <div class="space-y-4">
     <div class="flex items-center gap-3">
       <i class="fas fa-gas-pump text-green-600"></i>
       <h2 class="font-semibold text-gray-800">Informasi Gas In</h2>
     </div>

     <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
       <div>
         <label class="block text-sm font-medium text-gray-700 mb-1">Nama Petugas Gas In</label>
         <input type="text" value="{{ auth()->user()->name ?? '-' }}" readonly
                class="w-full px-3 py-2 border rounded bg-gray-100 text-gray-700">
       </div>

       <div>
         <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Gas In <span class="text-red-500">*</span></label>
         <input type="date" x-model="tanggal"
                class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500" required>
       </div>

       <div class="md:col-span-2">
         <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
         <textarea x-model="notes" rows="3" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                   placeholder="Catatan Gas In atau keterangan (opsional)"></textarea>
       </div>
     </div>
   </div>

   <div class="space-y-4">
     <div class="flex items-center gap-3">
       <i class="fas fa-camera text-purple-600"></i>
       <h2 class="font-semibold text-gray-800">Upload Foto Gas In</h2>
     </div>

     <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
       <template x-for="ph in photoDefs" :key="ph.field">
         <div class="border rounded-lg p-4">
           <label class="block text-sm font-medium text-gray-700 mb-2" x-text="ph.label"></label>

           <template x-if="!previews[ph.field]">
             <div class="h-32 flex items-center justify-center bg-gray-50 rounded border-dashed border text-gray-400">
               Tidak ada file
             </div>
           </template>
           <template x-if="previews[ph.field] && !isPdf(ph.field)">
             <img :src="previews[ph.field]" alt="" class="h-32 w-full object-cover rounded">
           </template>
           <template x-if="isPdf(ph.field)">
             <div class="h-32 flex items-center justify-center bg-gray-50 rounded border">
               <span class="text-xs text-gray-600">PDF terpilih</span>
             </div>
           </template>

           <div class="flex items-center gap-2 mt-3">
             <input class="hidden" type="file"
                    :accept="acceptString(ph.accept)"
                    :id="`inp_${ph.field}`"
                    :disabled="!customer || !reff"
                    @change="onPick(ph.field, $event)">
             <label :for="`inp_${ph.field}`"
                    :class="!customer || !reff ? 'px-3 py-2 bg-gray-300 text-gray-500 rounded cursor-not-allowed text-sm' : 'px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded cursor-pointer text-sm'">
               <i class="fas fa-folder-open mr-1"></i>Pilih
             </label>

             <button type="button" @click="clearPick(ph.field)"
                     class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded text-sm">
               <i class="fas fa-trash mr-1"></i>Hapus
             </button>

             <span class="text-xs flex-1 text-gray-500" x-text="uploadStatuses[ph.field] || ''"></span>
           </div>
         </div>
       </template>
     </div>

     <div class="bg-blue-50 border border-blue-200 p-3 rounded text-sm">
       <div class="flex items-start">
         <i class="fas fa-info-circle text-blue-600 mr-2 mt-0.5"></i>
         <div>
           <p class="font-medium text-blue-800 mb-1">Catatan Upload:</p>
           <ul class="text-blue-700 space-y-1">
             <li>• Format: JPG/PNG/WEBP untuk foto, PDF untuk Berita Acara</li>
             <li>• Maksimal 20 MB per file</li>
             <li>• Foto akan disimpan sebagai draft dan dianalisa AI saat proses approval</li>
             <li>• Pastikan objek yang diperlukan terlihat jelas dalam foto</li>
           </ul>
         </div>
       </div>
     </div>
   </div>

   <div class="flex justify-end gap-3 pt-2">
     <a href="{{ route('gas-in.index') }}" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">
       <i class="fas fa-arrow-left mr-2"></i>Batal
     </a>
     <button type="submit"
             :disabled="submitting || !customer || !reff || !tanggal"
             class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
       <template x-if="!submitting">
         <span><i class="fas fa-save mr-2"></i>Simpan</span>
       </template>
       <template x-if="submitting">
         <span><i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan…</span>
       </template>
     </button>
   </div>
 </form>
</div>
@endsection

@push('scripts')
<script>
function gasInCreate() {
 return {
   photoDefs: @json($photoDefs),

   reff: @json(request('reff_id', old('reff_id_pelanggan',''))),
   customer: null,
   reffMsg: '',
   tanggal: new Date().toISOString().slice(0,10),
   notes: '',

   pickedFiles: {},
   previews: {},
   isPdfMap: {},
   uploadStatuses: {},

   submitting: false,

   init() {
     if (this.reff) this.findCustomer();
   },

   acceptString(list) {
     return Array.isArray(list) ? list.join(',') : (list || 'image/*');
   },

   isPdf(field) {
     return !!this.isPdfMap[field];
   },

   reffMsgClass() {
     if (!this.reffMsg) return 'text-gray-500';
     return this.customer ? 'text-green-600' : 'text-red-600';
   },

   clearPick(field) {
     this.pickedFiles[field] = null;
     this.previews[field] = null;
     this.isPdfMap[field] = false;
     this.uploadStatuses[field] = '';
     document.getElementById(`inp_${field}`).value = '';
   },

   async findCustomer() {
     this.customer = null;
     this.reffMsg = '';
     const v = (this.reff || '').trim().toUpperCase();
     if (!v) {
       this.reffMsg = 'Masukkan Reference ID terlebih dahulu.';
       return;
     }

     try {
       const url = @json(route('customers.validate-reff', ['reffId' => '___'])).replace('___', encodeURIComponent(v));
       const res = await fetch(url, {
         headers: { 'Accept': 'application/json' }
       });
       const json = await res.json().catch(() => ({}));

       const ok = (json && (json.success === true || json.valid === true || json.exists === true));
       if (res.ok && ok && json.data) {
         this.customer = json.data;
         this.reff = v;
         this.reffMsg = 'Pelanggan ditemukan.';
       } else {
         this.customer = null;
         this.reffMsg = 'Pelanggan tidak ditemukan.';
       }
     } catch (e) {
       this.customer = null;
       this.reffMsg = 'Gagal memeriksa Reference ID.';
     }
   },

   onPick(field, e) {
     const file = e.target.files?.[0];
     if (!file) return;

     if (!this.customer || !this.reff) {
       alert('Silakan isi Reference ID dan cari customer terlebih dahulu sebelum upload foto.');
       e.target.value = '';
       return;
     }

     this.pickedFiles[field] = file;
     this.isPdfMap[field] = (file.type === 'application/pdf');

     if (!this.isPdfMap[field]) {
       const reader = new FileReader();
       reader.onload = () => this.previews[field] = reader.result;
       reader.readAsDataURL(file);
     } else {
       this.previews[field] = null;
     }

     this.uploadStatuses[field] = 'File siap untuk diupload';
   },

   async onSubmit() {
     if (this.submitting) return;
     if (!this.customer || !this.reff || !this.tanggal) {
       alert('Silakan lengkapi data customer dan tanggal gas in.');
       return;
     }

     this.submitting = true;

     try {
       const formData = new FormData();
       formData.append('_token', document.querySelector('input[name=_token]').value);
       formData.append('reff_id_pelanggan', this.reff);
       formData.append('tanggal_gas_in', this.tanggal);
       if (this.notes) formData.append('notes', this.notes);

       const response = await fetch(@json(route('gas-in.store')), {
         method: 'POST',
         headers: {
           'Accept': 'application/json',
           'X-Requested-With': 'XMLHttpRequest'
         },
         body: formData
       });

       const result = await response.json().catch(() => ({}));

       if (!response.ok || !result.success) {
         throw new Error(result.message || 'Gagal menyimpan Gas In');
       }

       const gasInId = result.data?.id;
       if (!gasInId) {
         throw new Error('Gas In berhasil dibuat tapi ID tidak ditemukan');
       }

       await this.uploadAllPhotos(gasInId);

       window.showToast?.('Gas In berhasil dibuat dan foto diupload!', 'success');
       window.location.href = @json(route('gas-in.show', ['gasIn' => '__ID__'])).replace('__ID__', gasInId);

     } catch (error) {
       console.error('Submit error:', error);
       alert('Gagal menyimpan Gas In: ' + (error.message || 'Unknown error'));
     } finally {
       this.submitting = false;
     }
   },

   async uploadAllPhotos(gasInId) {
     const urlTpl = @json(route('gas-in.photos.upload-draft', ['gasIn' => '__ID__']));
     const url = urlTpl.replace('__ID__', gasInId);

     for (const def of this.photoDefs) {
       const file = this.pickedFiles[def.field];
       if (!file) continue;

       const fd = new FormData();
       fd.append('_token', document.querySelector('input[name=_token]').value);
       fd.append('slot_type', def.field);
       fd.append('file', file);

       try {
         // Add timeout handling
         const controller = new AbortController();
         const timeoutId = setTimeout(() => controller.abort(), 120000); // 2 minutes timeout
         
         const res = await fetch(url, {
           method: 'POST',
           headers: {
             'Accept': 'application/json',
             'X-Requested-With': 'XMLHttpRequest'
           },
           body: fd,
           signal: controller.signal
         });
         
         clearTimeout(timeoutId);

         const j = await res.json().catch(() => ({}));
         if (!res.ok || !(j && (j.success === true || j.photo_id))) {
           throw new Error(j?.message || 'Gagal upload');
         }

         this.uploadStatuses[def.field] = '✓ Uploaded';

       } catch (e) {
         console.error('Upload gagal', def.field, e);
         this.uploadStatuses[def.field] = '✗ Upload gagal';
       }
     }
   }
 }
}
</script>
@endpush
