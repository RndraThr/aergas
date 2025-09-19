@extends('layouts.app')

@section('title', 'Edit Gas In - AERGAS')

@section('content')
@php
  $cfgSlots = (array) (config('aergas_photos.modules.GAS_IN.slots') ?? []);
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

<div class="space-y-6" x-data="gasInEdit()" x-init="init()">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Edit Gas In</h1>
      <p class="text-gray-600 mt-1">Reff ID: <b>{{ $gasIn->reff_id_pelanggan }}</b></p>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('gas-in.show',$gasIn->id) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Detail</a>
      <a href="javascript:void(0)" onclick="goBackWithPagination('{{ route('gas-in.index') }}')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Kembali</a>
    </div>
  </div>

  <div class="bg-white rounded-xl card-shadow p-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <div class="text-xs text-gray-500">Created By</div>
        <select x-model="createdBy" class="mt-1 w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500 text-sm">
          <option value="">Pilih User</option>
          @foreach(\App\Models\User::orderBy('name')->get() as $user)
            <option value="{{ $user->id }}">{{ $user->name }}</option>
          @endforeach
        </select>
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
            'bg-yellow-100 text-yellow-800' => $gasIn->status === 'scheduled',
            'bg-purple-100 text-purple-800' => $gasIn->status === 'tracer_approved',
            'bg-amber-100 text-amber-800' => $gasIn->status === 'cgp_approved',
            'bg-red-100 text-red-800' => str_contains($gasIn->status,'rejected'),
            'bg-green-100 text-green-800' => $gasIn->status === 'completed',
          ])
        ">{{ strtoupper($gasIn->status) }}</span>
      </div>
    </div>
  </div>

  <form @submit.prevent="updateInfo" class="bg-white rounded-xl card-shadow p-6 space-y-4">
    <div class="flex items-center gap-3">
      <i class="fas fa-gas-pump text-green-600"></i>
      <h2 class="font-semibold text-gray-800">Edit Informasi Gas In</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Gas In <span class="text-red-500">*</span></label>
        <input type="date" x-model="tanggalGasIn"
               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500" required>
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
        <textarea x-model="notes" rows="3" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                  placeholder="Catatan Gas In atau keterangan (opsional)"></textarea>
      </div>
    </div>

    <div class="flex justify-end gap-3 pt-4">
      <button type="submit"
              :disabled="updating"
              class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
        <span x-show="!updating"><i class="fas fa-save mr-2"></i>Update Info</span>
        <span x-show="updating"><i class="fas fa-spinner fa-spin mr-2"></i>Updating...</span>
      </button>
    </div>

    <div class="bg-orange-50 border border-orange-200 p-3 rounded text-sm">
      <div class="flex items-start">
        <i class="fas fa-info-circle text-orange-600 mr-2 mt-0.5"></i>
        <div>
          <p class="font-medium text-orange-800 mb-1">Catatan:</p>
          <ul class="text-orange-700 space-y-1">
            <li>• Hanya bisa edit saat status DRAFT</li>
            <li>• Tanggal Gas In wajib diisi</li>
          </ul>
        </div>
      </div>
    </div>
  </form>

  <div class="bg-white rounded-xl card-shadow p-6 space-y-4">
    <div class="flex items-center gap-3">
      <i class="fas fa-camera text-purple-600"></i>
      <h2 class="font-semibold text-gray-800">Upload / Re-upload Foto</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                   :id="`file_${@js($ph['field'])}`"
                   accept="{{ implode(',', (array) $ph['accept']) }}"
                   @change="onPick($event)">
            <label :for="`file_${@js($ph['field'])}`" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded cursor-pointer text-sm">
              <i class="fas fa-folder-open mr-1"></i>Pilih
            </label>

            <button type="button" @click="clearPick" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded text-sm">
              <i class="fas fa-trash mr-1"></i>Hapus
            </button>

            <button type="button" @click="upload" :disabled="!file || uploading"
                    class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 text-sm">
              <span x-show="!uploading"><i class="fas fa-upload mr-1"></i>Upload</span>
              <span x-show="uploading"><i class="fas fa-spinner fa-spin mr-1"></i>Uploading…</span>
            </button>
          </div>

          <div class="text-xs mt-2"
               :class="statusMsg?.includes('✓') ? 'text-green-600' :
                      statusMsg?.includes('✗') ? 'text-red-600' : 'text-gray-500'"
               x-text="statusMsg"></div>
        </div>
      @endforeach
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
</div>
@endsection

@push('scripts')
<script>
function gasInEdit() {
  return {
    createdBy: @json($gasIn->created_by ?? ''),
    tanggalGasIn: @json($gasIn->tanggal_gas_in ? $gasIn->tanggal_gas_in->format('Y-m-d') : ''),
    notes: @json($gasIn->notes ?? ''),
    updating: false,

    init() {},

    async updateInfo() {
      if (this.updating) return;

      this.updating = true;

      try {
        const formData = new FormData();
        formData.append('_token', @json(csrf_token()));
        formData.append('_method', 'PUT');

        if (this.createdBy) formData.append('created_by', this.createdBy);
        if (this.tanggalGasIn) formData.append('tanggal_gas_in', this.tanggalGasIn);
        if (this.notes) formData.append('notes', this.notes);

        const response = await fetch(@json(route('gas-in.update', $gasIn->id)), {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: formData
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok || !result.success) {
          throw new Error(result.message || 'Gagal update informasi');
        }

        window.showToast?.('Informasi Gas In berhasil diupdate!', 'success');

      } catch (error) {
        console.error('Update error:', error);
        window.showToast?.(error.message || 'Gagal update informasi', 'error');
      } finally {
        this.updating = false;
      }
    }
  }
}

function slotUploader(slot) {
  return {
    file: null,
    preview: null,
    isPdf: false,
    uploading: false,
    statusMsg: '',

    onPick(e) {
      const f = e.target.files?.[0];
      if (!f) return;

      this.file = f;
      this.isPdf = (f.type === 'application/pdf');
      this.statusMsg = '';

      if (!this.isPdf) {
        const r = new FileReader();
        r.onload = () => this.preview = r.result;
        r.readAsDataURL(f);
      } else {
        this.preview = null;
      }

      this.statusMsg = 'File siap untuk diupload';
    },

    clearPick() {
      this.file = null;
      this.preview = null;
      this.isPdf = false;
      this.statusMsg = '';
      document.getElementById(`file_${slot}`).value = '';
    },

    async upload() {
      if (!this.file) return;
      this.uploading = true;

      try {
        const url = @json(route('gas-in.photos.upload-draft', ['gasIn'=>$gasIn->id]));
        const fd = new FormData();
        fd.append('_token', @json(csrf_token()));
        fd.append('slot_type', slot);
        fd.append('file', this.file);

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

        this.statusMsg = '✓ Upload berhasil tersimpan sebagai draft';
        window.showToast?.('Upload berhasil tersimpan sebagai draft.', 'success');

      } catch (e) {
        console.error(e);
        this.statusMsg = '✗ Upload gagal';
        window.showToast?.(e.message || 'Upload gagal', 'error');
      } finally {
        this.uploading = false;
      }
    }
  }
}
</script>
@endpush
