@extends('layouts.app')

@section('title', 'Edit SK - AERGAS')

@section('content')
@php
  $cfgSlots = (array) (config('aergas_photos.modules.SK.slots') ?? []);
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

  <div class="bg-white rounded-xl card-shadow p-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <div class="text-xs text-gray-500">Created By</div>
        @if($sk->createdBy)
          <div class="flex items-center mt-1">
            <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center mr-2">
              <span class="text-xs font-medium text-blue-600">
                {{ strtoupper(substr($sk->createdBy->name, 0, 1)) }}
              </span>
            </div>
            <span class="font-medium">{{ $sk->createdBy->name }}</span>
          </div>
        @else
          <div class="font-medium text-gray-400">-</div>
        @endif
      </div>
      <div>
        <div class="text-xs text-gray-500">Tanggal Instalasi</div>
        <div class="font-medium">{{ $sk->tanggal_instalasi ? $sk->tanggal_instalasi->format('d/m/Y') : '-' }}</div>
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

  <form @submit.prevent="updateMaterial" class="bg-white rounded-xl card-shadow p-6 space-y-4">
    <div class="flex items-center gap-3">
      <i class="fas fa-clipboard-list text-orange-600"></i>
      <h2 class="font-semibold text-gray-800">Edit Material SK</h2>
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
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-3">
        <label class="block text-sm font-medium text-gray-700 mb-1">
          {{ $materialLabels['panjang_pipa_gl_medium_m'] }} <span class="text-red-500">*</span>
        </label>
        <input type="number" x-model="material.panjang_pipa_gl_medium_m" step="0.01" min="0" max="1000"
               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
               placeholder="0.00" required>
      </div>

      @foreach(['qty_elbow_1_2_galvanis', 'qty_sockdraft_galvanis_1_2', 'qty_ball_valve_1_2', 'qty_nipel_selang_1_2', 'qty_elbow_reduce_3_4_1_2', 'qty_long_elbow_3_4_male_female', 'qty_klem_pipa_1_2', 'qty_double_nipple_1_2', 'qty_seal_tape'] as $field)
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ $materialLabels[$field] }} <span class="text-red-500">*</span>
          </label>
          <input type="number" x-model="material.{{ $field }}" min="0" max="100"
                 class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                 placeholder="0" required>
        </div>
      @endforeach

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          {{ $materialLabels['qty_tee_1_2'] }}
        </label>
        <input type="number" x-model="material.qty_tee_1_2" min="0" max="100"
               class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
               placeholder="0">
      </div>
    </div>

    <div class="mt-4 p-3 bg-white rounded border">
      <div class="text-sm text-gray-600">
        <div class="flex justify-between items-center">
          <span class="font-medium">Total Fitting:</span>
          <span class="font-bold" x-text="calculateTotalFitting()"></span>
        </div>
        <div class="flex justify-between items-center mt-1">
          <span class="font-medium">Status Kelengkapan:</span>
          <span :class="isMaterialComplete() ? 'text-green-600 font-bold' : 'text-red-600 font-bold'"
                x-text="isMaterialComplete() ? 'LENGKAP' : 'BELUM LENGKAP'"></span>
        </div>
      </div>
    </div>

    <div class="flex justify-end gap-3 pt-4">
      <button type="submit"
              :disabled="updating || !isMaterialComplete()"
              class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
        <span x-show="!updating"><i class="fas fa-save mr-2"></i>Update Material</span>
        <span x-show="updating"><i class="fas fa-spinner fa-spin mr-2"></i>Updating...</span>
      </button>
    </div>

    <div class="bg-orange-50 border border-orange-200 p-3 rounded text-sm">
      <div class="flex items-start">
        <i class="fas fa-info-circle text-orange-600 mr-2 mt-0.5"></i>
        <div>
          <p class="font-medium text-orange-800 mb-1">Catatan Material:</p>
          <ul class="text-orange-700 space-y-1">
            <li>• Semua field bertanda (*) merah wajib diisi</li>
            <li>• Field Tee 1/2" bersifat opsional</li>
            <li>• Material harus sesuai dengan gambar isometrik SK</li>
            <li>• Hanya bisa edit saat status DRAFT</li>
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
            <li>• Format: JPG/PNG/WEBP untuk foto, PDF untuk dokumen Isometrik</li>
            <li>• Maksimal 10 MB per file</li>
            <li>• Foto akan disimpan sebagai draft dan dianalisa AI saat proses approval</li>
            <li>• Pastikan foto sudah jelas dan sesuai dengan yang diminta</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function skEdit() {
  return {
    material: {
      panjang_pipa_gl_medium_m: @json($sk->panjang_pipa_gl_medium_m ?? ''),
      qty_elbow_1_2_galvanis: @json($sk->qty_elbow_1_2_galvanis ?? ''),
      qty_sockdraft_galvanis_1_2: @json($sk->qty_sockdraft_galvanis_1_2 ?? ''),
      qty_ball_valve_1_2: @json($sk->qty_ball_valve_1_2 ?? ''),
      qty_nipel_selang_1_2: @json($sk->qty_nipel_selang_1_2 ?? ''),
      qty_elbow_reduce_3_4_1_2: @json($sk->qty_elbow_reduce_3_4_1_2 ?? ''),
      qty_long_elbow_3_4_male_female: @json($sk->qty_long_elbow_3_4_male_female ?? ''),
      qty_klem_pipa_1_2: @json($sk->qty_klem_pipa_1_2 ?? ''),
      qty_double_nipple_1_2: @json($sk->qty_double_nipple_1_2 ?? ''),
      qty_seal_tape: @json($sk->qty_seal_tape ?? ''),
      qty_tee_1_2: @json($sk->qty_tee_1_2 ?? '')
    },
    updating: false,

    init() {},

    calculateTotalFitting() {
      const values = Object.values(this.material);
      const total = values.reduce((sum, val) => {
        const num = Number(val) || 0;
        return sum + num;
      }, 0);
      return total - (Number(this.material.panjang_pipa_gl_medium_m) || 0);
    },

    isMaterialComplete() {
      const required = [
        'panjang_pipa_gl_medium_m',
        'qty_elbow_1_2_galvanis',
        'qty_sockdraft_galvanis_1_2',
        'qty_ball_valve_1_2',
        'qty_nipel_selang_1_2',
        'qty_elbow_reduce_3_4_1_2',
        'qty_long_elbow_3_4_male_female',
        'qty_klem_pipa_1_2',
        'qty_double_nipple_1_2',
        'qty_seal_tape'
      ];

      for (const field of required) {
        const value = this.material[field];
        if (field === 'panjang_pipa_gl_medium_m') {
          if (!value || Number(value) <= 0) return false;
        } else {
          if (value === '' || value === null || Number(value) < 0) return false;
        }
      }
      return true;
    },

    async updateMaterial() {
      if (this.updating || !this.isMaterialComplete()) return;

      this.updating = true;

      try {
        const formData = new FormData();
        formData.append('_token', @json(csrf_token()));
        formData.append('_method', 'PUT');

        Object.keys(this.material).forEach(key => {
          const value = this.material[key];
          if (value !== '' && value !== null) {
            formData.append(key, value);
          }
        });

        const response = await fetch(@json(route('sk.update', $sk->id)), {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: formData
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok || !result.success) {
          throw new Error(result.message || 'Gagal update material');
        }

        window.showToast?.('Material berhasil diupdate!', 'success');

      } catch (error) {
        console.error('Update error:', error);
        window.showToast?.(error.message || 'Gagal update material', 'error');
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
        const url = @json(route('sk.photos.upload-draft', ['sk'=>$sk->id]));
        const fd = new FormData();
        fd.append('_token', @json(csrf_token()));
        fd.append('slot_type', slot);
        fd.append('file', this.file);

        const res = await fetch(url, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: fd
        });

        const j = await res.json().catch(() => ({}));
        if (!res.ok || !(j && (j.success === true || j.photo_id))) {
          throw new Error(j?.message || 'Gagal upload');
        }

        this.statusMsg = '✓ Upload berhasil';
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
