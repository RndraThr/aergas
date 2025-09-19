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
   <a href="javascript:void(0)" onclick="goBackWithPagination('{{ route('gas-in.index') }}')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Kembali</a>
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
         <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 bg-gray-50 hover:bg-gray-100 cursor-pointer min-h-[250px]"
              :class="dragStates[ph.field] ? 'border-blue-400 bg-blue-50 scale-105' : ''"
              @click="openFileDialog(ph.field)"
              @dragover.prevent="setDragging(ph.field, true)"
              @dragleave.prevent="setDragging(ph.field, false)"
              @drop.prevent="handleDrop($event, ph.field)">
           
           <!-- Hidden file input -->
           <input type="file"
                  :id="`inp_${ph.field}`"
                  :accept="acceptString(ph.accept)"
                  class="hidden"
                  :disabled="!customer || !reff"
                  @change="handleFileSelect($event, ph.field)">

           <!-- Header -->
           <div class="mb-3">
             <h4 class="font-semibold text-gray-800" x-text="ph.label"></h4>
             <p class="text-xs text-gray-500" x-text="`Accept: ${acceptString(ph.accept)}`"></p>
           </div>

           <!-- Preview Image -->
           <template x-if="previews[ph.field] && !isPdf(ph.field)">
             <div class="relative">
               <img :src="previews[ph.field]" 
                    :alt="ph.label"
                    class="w-full h-40 object-cover rounded border shadow-sm">
               <button type="button"
                       @click.stop="previewImage(ph.field)"
                       class="absolute top-2 right-2 bg-blue-500 text-white p-1 rounded text-xs hover:bg-blue-600">
                 <i class="fas fa-eye"></i>
               </button>
               <button type="button"
                       @click.stop="clearPick(ph.field)"
                       class="absolute top-2 left-2 bg-red-500 text-white p-1 rounded text-xs hover:bg-red-600">
                 <i class="fas fa-trash"></i>
               </button>
             </div>
           </template>

           <!-- Preview PDF -->
           <template x-if="previews[ph.field] && isPdf(ph.field)">
             <div class="relative">
               <div class="w-full h-40 bg-red-50 border border-red-200 rounded flex flex-col items-center justify-center text-red-600">
                 <i class="fas fa-file-pdf text-3xl mb-2"></i>
                 <span class="text-sm font-medium" x-text="pickedFiles[ph.field]?.name || 'PDF File'"></span>
               </div>
               <button type="button"
                       @click.stop="previewPdf(ph.field)"
                       class="absolute top-2 right-2 bg-blue-500 text-white p-1 rounded text-xs hover:bg-blue-600">
                 <i class="fas fa-external-link-alt"></i>
               </button>
               <button type="button"
                       @click.stop="clearPick(ph.field)"
                       class="absolute top-2 left-2 bg-red-500 text-white p-1 rounded text-xs hover:bg-red-600">
                 <i class="fas fa-trash"></i>
               </button>
             </div>
           </template>

           <!-- Empty State -->
           <template x-if="!previews[ph.field]">
             <div class="flex flex-col items-center justify-center py-8 text-center">
               <i class="fas fa-cloud-upload-alt text-3xl mb-3"
                  :class="dragStates[ph.field] ? 'text-blue-500 animate-bounce' : 'text-gray-400'"></i>
               <p class="font-medium"
                  :class="dragStates[ph.field] ? 'text-blue-600' : 'text-gray-600'">
                 <span x-show="!dragStates[ph.field]">Klik untuk upload</span>
                 <span x-show="dragStates[ph.field]">Lepaskan file di sini</span>
               </p>
               <p class="text-xs mt-1"
                  :class="dragStates[ph.field] ? 'text-blue-400' : 'text-gray-400'">
                 <span x-show="!dragStates[ph.field]">Drag & drop juga didukung</span>
                 <span x-show="dragStates[ph.field]">File siap di-upload</span>
               </p>
               
               <div x-show="!customer || !reff" class="mt-3 text-xs text-red-500">
                 Isi Reference ID terlebih dahulu
               </div>
             </div>
           </template>

           <!-- Status -->
           <div x-show="uploadStatuses[ph.field]" class="mt-2 text-xs p-2 bg-blue-100 rounded">
             <span x-text="uploadStatuses[ph.field]"></span>
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
             <li>• Maksimal 35 MB per file</li>
             <li>• Foto akan disimpan sebagai draft dan dianalisa AI saat proses approval</li>
             <li>• Pastikan objek yang diperlukan terlihat jelas dalam foto</li>
           </ul>
         </div>
       </div>
     </div>
   </div>

   <div class="flex justify-end gap-3 pt-2">
     <a href="javascript:void(0)" onclick="goBackWithPagination('{{ route('gas-in.index') }}')" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">
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
 
 <!-- Preview Modal -->
 <div x-show="showPreviewModal" 
      x-transition:enter="transition ease-out duration-300"
      x-transition:enter-start="opacity-0"
      x-transition:enter-end="opacity-100"
      x-transition:leave="transition ease-in duration-200"
      x-transition:leave-start="opacity-100"
      x-transition:leave-end="opacity-0"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75"
      @click="closePreviewModal()"
      @keydown.escape.window="closePreviewModal()">
     
     <div class="max-w-4xl max-h-full p-4" @click.stop>
         <div class="relative">
             <img :src="previewImageSrc" 
                  :alt="previewImageLabel"
                  class="max-w-full max-h-[80vh] object-contain rounded-lg shadow-2xl">
             
             <button type="button"
                     @click="closePreviewModal()"
                     class="absolute top-4 right-4 bg-black bg-opacity-50 text-white p-3 rounded-full hover:bg-opacity-75 transition-all">
                 <i class="fas fa-times text-lg"></i>
             </button>
         </div>
         
         <div class="text-center mt-4 text-white">
             <p class="text-lg font-medium" x-text="previewImageLabel"></p>
         </div>
     </div>
 </div>
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
   dragStates: {},

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

   // Preview modal state
   showPreviewModal: false,
   previewImageSrc: '',
   previewImageLabel: '',

   previewImage(field) {
     if (this.previews[field] && !this.isPdf(field)) {
       this.previewImageSrc = this.previews[field];
       this.previewImageLabel = this.photoDefs.find(p => p.field === field)?.label || field;
       this.showPreviewModal = true;
       document.body.style.overflow = 'hidden';
     }
   },

   closePreviewModal() {
     this.showPreviewModal = false;
     document.body.style.overflow = '';
   },

   // Drag & Drop functions
   setDragging(field, state) {
     this.dragStates[field] = state;
   },

   getDragClass(field) {
     if (!this.customer || !this.reff) {
       return 'border-gray-200 bg-gray-50 cursor-not-allowed';
     }
     
     if (this.dragStates[field]) {
       return 'border-blue-400 bg-blue-50 shadow-lg scale-105';
     }
     
     if (this.previews[field]) {
       return 'border-green-300 bg-green-50';
     }
     
     return 'border-gray-300 hover:border-gray-400 cursor-pointer';
   },

   openFileDialog(field) {
     if (!this.customer || !this.reff) return;
     document.getElementById(`inp_${field}`).click();
   },

   handleDrop(event, field) {
     this.setDragging(field, false);
     
     if (!this.customer || !this.reff) return;
     
     const files = event.dataTransfer.files;
     if (files.length > 0) {
       this.processFile(files[0], field);
     }
   },

   previewPdf(field) {
     const file = this.pickedFiles[field];
     if (file && file.type === 'application/pdf') {
       const url = URL.createObjectURL(file);
       window.open(url, '_blank');
     }
   },

   formatFileSize(bytes) {
     if (!bytes || bytes === 0) return '0 Bytes';
     const k = 1024;
     const sizes = ['Bytes', 'KB', 'MB', 'GB'];
     const i = Math.floor(Math.log(bytes) / Math.log(k));
     return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
   },

   getStatusClass(field) {
     const status = this.uploadStatuses[field] || '';
     if (status.includes('✓') || status.includes('berhasil')) {
       return 'text-green-600';
     }
     if (status.includes('✗') || status.includes('gagal')) {
       return 'text-red-600';
     }
     if (status.includes('siap')) {
       return 'text-blue-600';
     }
     return 'text-gray-600';
   },

   handleFileSelect(event, field) {
     const file = event.target.files?.[0];
     if (file) {
       this.processFile(file, field);
     }
   },

   processFile(file, field) {
     // Get accepted types for this field
     const photoDef = this.photoDefs.find(p => p.field === field);
     const acceptedTypes = photoDef?.accept || ['image/*'];
     
     // Validate file type
     const isValidType = acceptedTypes.some(type => {
       if (type === 'image/*') return file.type.startsWith('image/');
       if (type === 'application/pdf') return file.type === 'application/pdf';
       return file.type === type;
     });
     
     if (!isValidType) {
       alert(`File type tidak didukung. Hanya menerima: ${acceptedTypes.join(', ')}`);
       return;
     }
     
     // Validate file size (35MB = 35 * 1024 * 1024)
     const maxSizeBytes = 35 * 1024 * 1024;
     if (file.size > maxSizeBytes) {
       alert('File terlalu besar. Maksimal 35MB.');
       return;
     }

     // Check if customer is selected
     if (!this.customer || !this.reff) {
       alert('Silakan isi Reference ID dan cari customer terlebih dahulu sebelum upload foto.');
       document.getElementById(`inp_${field}`).value = '';
       return;
     }
     
     this.pickedFiles[field] = file;
     this.isPdfMap[field] = file.type === 'application/pdf';
     
     // Create preview for images
     if (!this.isPdfMap[field]) {
       const reader = new FileReader();
       reader.onload = (e) => {
         this.previews[field] = e.target.result;
       };
       reader.readAsDataURL(file);
     } else {
       this.previews[field] = 'pdf-placeholder';
     }
     
     this.uploadStatuses[field] = 'File siap untuk diupload';
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
         console.log(`[DEBUG] Starting upload for ${def.field}:`, {
           fileSize: file.size,
           fileName: file.name,
           fileType: file.type,
           url: url
         });

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

         console.log(`[DEBUG] Upload response for ${def.field}:`, {
           status: res.status,
           statusText: res.statusText,
           ok: res.ok,
           headers: Object.fromEntries(res.headers.entries())
         });

         const j = await res.json().catch((parseError) => {
           console.error(`[DEBUG] JSON parse error for ${def.field}:`, parseError);
           return {};
         });
         
         console.log(`[DEBUG] Parsed response for ${def.field}:`, j);

         if (!res.ok || !(j && (j.success === true || j.photo_id))) {
           const errorMsg = j?.message || j?.errors || `HTTP ${res.status}: ${res.statusText}`;
           throw new Error(errorMsg);
         }

         this.uploadStatuses[def.field] = '✓ Uploaded';
         console.log(`[DEBUG] Upload successful for ${def.field}`);

       } catch (e) {
         console.error(`[DEBUG] Upload error for ${def.field}:`, {
           error: e,
           message: e.message,
           stack: e.stack
         });
         this.uploadStatuses[def.field] = `✗ Upload gagal: ${e.message}`;
       }
     }
   }
 }
}

// Drag & Drop Upload Component
</script>


<!-- CSS for Drag & Drop -->
<style>
.drag-drop-container {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.drag-drop-container:hover:not(.cursor-not-allowed) {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
}

.drag-drop-container.border-blue-400 {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.drag-drop-container.border-green-300 {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
}

.preview-area img {
    transition: transform 0.3s ease;
}

.preview-area .group:hover img {
    transform: scale(1.02);
}

.drag-drop-container {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
@endpush
