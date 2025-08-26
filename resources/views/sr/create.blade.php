@extends('layouts.app')

@section('title', 'Buat SR - AERGAS')

@section('content')

@php
  $cfgAll   = config('aergas_photos') ?: [];
  $cfgSlots = (array) (data_get($cfgAll, 'modules.SR.slots', []));
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
          ['field'=>'pneumatic_start','label'=>'Foto Pneumatic START SR','accept'=>['image/*']],
          ['field'=>'pneumatic_finish','label'=>'Foto Pneumatic FINISH SR','accept'=>['image/*']],
          ['field'=>'jenis_tapping','label'=>'Foto Jenis Tapping','accept'=>['image/*']],
          ['field'=>'kedalaman','label'=>'Foto Kedalaman','accept'=>['image/*']],
          ['field'=>'mgrt','label'=>'Foto MGRT','accept'=>['image/*']],
          ['field'=>'pondasi','label'=>'Foto Pondasi','accept'=>['image/*']],
          ['field'=>'isometrik_scan','label'=>'Scan Isometrik SR (TTD lengkap)','accept'=>['image/*','application/pdf']],
      ];
  }

  $materialLabels = [
      'qty_tapping_saddle' => 'Tapping Saddle (Pcs)',
      'qty_coupler_20mm' => 'Coupler 20 mm (Pcs)',
      'panjang_pipa_pe_20mm_m' => 'Pipa PE 20 mm (meter)',
      'qty_elbow_90x20' => 'Elbow 90 x 20 mm (Pcs)',
      'qty_transition_fitting' => 'Transition Fitting (Pcs)',
      'panjang_pondasi_tiang_sr_m' => 'Pondasi Tiang SR (meter)',
      'panjang_pipa_galvanize_3_4_m' => 'Pipa Galvanize 3/4" (meter)',
      'qty_klem_pipa' => 'Klem Pipa (Pcs)',
      'qty_ball_valve_3_4' => 'Ball Valve 3/4" (Pcs)',
      'qty_double_nipple_3_4' => 'Double Nipple 3/4" (Pcs)',
      'qty_long_elbow_3_4' => 'Long Elbow 3/4" (Pcs)',
      'qty_regulator_service' => 'Regulator Service (Pcs)',
      'qty_coupling_mgrt' => 'Coupling MGRT (Pcs)',
      'qty_meter_gas_rumah_tangga' => 'Meter Gas Rumah Tangga (Pcs)',
      'panjang_casing_1_inch_m' => 'Casing 1" (meter)',
      'qty_sealtape' => 'Sealtape (Pcs)',
  ];

  $tappingOptions = ['63x20','90x20','63x32','180x90','180x63','125x63','90x63','180x32','125x32','90x32'];
@endphp

<div class="space-y-6" x-data="srCreate()" x-init="init()">

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Buat SR</h1>
      <p class="text-gray-600 mt-1">Masukkan Reference ID untuk auto-fill data customer</p>
    </div>
    <a href="{{ route('sr.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Kembali</a>
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
        <i class="fas fa-wrench text-green-600"></i>
        <h2 class="font-semibold text-gray-800">Informasi Instalasi SR</h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nama Petugas SR</label>
          <input type="text" value="{{ auth()->user()->name ?? '-' }}" readonly
                 class="w-full px-3 py-2 border rounded bg-gray-100 text-gray-700">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pemasangan <span class="text-red-500">*</span></label>
          <input type="date" x-model="tanggal"
                 class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Tapping</label>
          <select x-model="jenisTapping" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500">
            <option value="">Pilih Jenis Tapping</option>
            @foreach($tappingOptions as $option)
              <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
          </select>

          <template x-if="jenisTapping">
            <div class="mt-2 p-2 bg-gray-50 rounded border">
              <div class="text-xs text-gray-500 mb-1">Preview Jenis Tapping:</div>
              <div class="w-full h-20 bg-gray-200 rounded flex items-center justify-center">
                <span class="text-xs text-gray-600" x-text="`Tapping ${jenisTapping}`"></span>
              </div>
              <div class="text-xs text-gray-500 mt-1">
                Pastikan foto jenis tapping sesuai dengan pilihan ini
              </div>
            </div>
          </template>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">No Seri MGRT <span class="text-red-500">*</span></label>
          <input type="text" x-model="noSeriMgrt" maxlength="50"
                 class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                 placeholder="Masukkan nomor seri MGRT" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Merk/Brand MGRT</label>
          <input type="text" x-model="merkBrandMgrt" maxlength="50"
                 class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                 placeholder="Masukkan merk/brand MGRT (opsional)">
        </div>

        <div class="md:col-span-3">
          <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
          <textarea x-model="notes" rows="3" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                    placeholder="Catatan atau keterangan (opsional)"></textarea>
        </div>
      </div>
    </div>

    <div class="space-y-4">
      <div class="flex items-center gap-3">
        <i class="fas fa-clipboard-list text-orange-600"></i>
        <h2 class="font-semibold text-gray-800">Daftar Material SR</h2>
        <div class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-medium">
          Sesuai BOM
        </div>
      </div>

      <div class="bg-gray-50 p-4 rounded border">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          @foreach(['qty_tapping_saddle', 'qty_coupler_20mm', 'qty_elbow_90x20', 'qty_transition_fitting', 'qty_klem_pipa', 'qty_ball_valve_3_4', 'qty_double_nipple_3_4', 'qty_long_elbow_3_4', 'qty_regulator_service', 'qty_coupling_mgrt', 'qty_meter_gas_rumah_tangga', 'qty_sealtape'] as $field)
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $materialLabels[$field] }} <span class="text-red-500">*</span>
              </label>
              <input type="number" x-model="material.{{ $field }}" min="0" max="100"
                     class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                     placeholder="0" required>
            </div>
          @endforeach

          @foreach(['panjang_pipa_pe_20mm_m', 'panjang_pondasi_tiang_sr_m', 'panjang_pipa_galvanize_3_4_m', 'panjang_casing_1_inch_m'] as $field)
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $materialLabels[$field] }} <span class="text-red-500">*</span>
              </label>
              <input type="number" x-model="material.{{ $field }}" step="0.01" min="0" max="1000"
                     class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                     placeholder="0.00" required>
            </div>
          @endforeach
        </div>

        <div class="mt-4 p-3 bg-white rounded border">
          <div class="text-sm text-gray-600">
            <div class="flex justify-between items-center">
              <span class="font-medium">Total Items:</span>
              <span class="font-bold" x-text="calculateTotalItems()"></span>
            </div>
            <div class="flex justify-between items-center mt-1">
              <span class="font-medium">Total Lengths:</span>
              <span class="font-bold" x-text="calculateTotalLengths()"></span>
            </div>
            <div class="flex justify-between items-center mt-1">
              <span class="font-medium">Status Kelengkapan:</span>
              <span :class="isMaterialComplete() ? 'text-green-600 font-bold' : 'text-red-600 font-bold'"
                    x-text="isMaterialComplete() ? 'LENGKAP' : 'BELUM LENGKAP'"></span>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-orange-50 border border-orange-200 p-3 rounded text-sm">
        <div class="flex items-start">
          <i class="fas fa-info-circle text-orange-600 mr-2 mt-0.5"></i>
          <div>
            <p class="font-medium text-orange-800 mb-1">Catatan Material:</p>
            <ul class="text-orange-700 space-y-1">
              <li>• Semua field bertanda (*) merah wajib diisi</li>
              <li>• Material harus sesuai dengan gambar isometrik SR</li>
              <li>• Pastikan quantity sudah benar sebelum submit</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="space-y-4">
      <div class="flex items-center gap-3">
        <i class="fas fa-camera text-purple-600"></i>
        <h2 class="font-semibold text-gray-800">Upload Foto</h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
              <li>• Format: JPG/PNG/WEBP untuk foto, PDF untuk dokumen Isometrik</li>
              <li>• Maksimal 10 MB per file</li>
              <li>• Foto akan disimpan sebagai draft dan dianalisa AI saat proses approval</li>
              <li>• Pastikan foto sudah jelas dan sesuai dengan yang diminta</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="flex justify-end gap-3 pt-2">
      <a href="{{ route('sr.index') }}" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">
        <i class="fas fa-arrow-left mr-2"></i>Batal
      </a>
      <button type="submit"
              :disabled="submitting || !customer || !reff || !tanggal || !isMaterialComplete()"
              class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
        <template x-if="!submitting">
          <span><i class="fas fa-save mr-2"></i>Simpan</span>
        </template>
        <template x-if="submitting">
          <span><i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan…</span>
        </template>
      </button>
    </div>

    <template x-if="!isMaterialComplete()">
      <div class="bg-amber-50 border border-amber-200 p-4 rounded">
        <div class="flex items-start">
          <i class="fas fa-exclamation-triangle text-amber-600 mr-2 mt-0.5"></i>
          <div class="text-amber-800">
            <p class="font-medium">Perhatian!</p>
            <div class="text-sm mt-1">
              <p>Data material belum lengkap. Pastikan semua field bertanda (*) sudah diisi.</p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </form>
</div>
@endsection

@push('scripts')
<script>
function srCreate() {
  return {
    photoDefs: @json($photoDefs),

    reff: @json(request('reff_id', old('reff_id_pelanggan',''))),
    customer: null,
    reffMsg: '',
    tanggal: new Date().toISOString().slice(0,10),
    notes: '',
    jenisTapping: '',
    noSeriMgrt: '',
    merkBrandMgrt: '',

    material: {
      qty_tapping_saddle: '',
      qty_coupler_20mm: '',
      panjang_pipa_pe_20mm_m: '',
      qty_elbow_90x20: '',
      qty_transition_fitting: '',
      panjang_pondasi_tiang_sr_m: '',
      panjang_pipa_galvanize_3_4_m: '',
      qty_klem_pipa: '',
      qty_ball_valve_3_4: '',
      qty_double_nipple_3_4: '',
      qty_long_elbow_3_4: '',
      qty_regulator_service: '',
      qty_coupling_mgrt: '',
      qty_meter_gas_rumah_tangga: '',
      panjang_casing_1_inch_m: '',
      qty_sealtape: ''
    },

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

    calculateTotalItems() {
      const itemFields = ['qty_tapping_saddle', 'qty_coupler_20mm', 'qty_elbow_90x20', 'qty_transition_fitting', 'qty_klem_pipa', 'qty_ball_valve_3_4', 'qty_double_nipple_3_4', 'qty_long_elbow_3_4', 'qty_regulator_service', 'qty_coupling_mgrt', 'qty_meter_gas_rumah_tangga', 'qty_sealtape'];
      const total = itemFields.reduce((sum, field) => {
        return sum + (Number(this.material[field]) || 0);
      }, 0);
      return total + ' pcs';
    },

    calculateTotalLengths() {
      const lengthFields = ['panjang_pipa_pe_20mm_m', 'panjang_pondasi_tiang_sr_m', 'panjang_pipa_galvanize_3_4_m', 'panjang_casing_1_inch_m'];
      const total = lengthFields.reduce((sum, field) => {
        return sum + (Number(this.material[field]) || 0);
      }, 0);
      return total.toFixed(2) + ' meter';
    },

    isMaterialComplete() {
      const required = Object.keys(this.material);
      for (const field of required) {
        const value = this.material[field];
        if (field.includes('panjang_')) {
          if (!value || Number(value) <= 0) return false;
        } else {
          if (value === '' || value === null || Number(value) < 0) return false;
        }
      }
      return true;
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
        alert('Silakan lengkapi data customer dan tanggal pemasangan.');
        return;
      }
      if (!this.isMaterialComplete()) {
        alert('Data material belum lengkap. Pastikan semua field bertanda (*) sudah diisi.');
        return;
      }

      this.submitting = true;

      try {
        const formData = new FormData();
        formData.append('_token', document.querySelector('input[name=_token]').value);
        formData.append('reff_id_pelanggan', this.reff);
        formData.append('tanggal_pemasangan', this.tanggal);
        if (this.notes) formData.append('notes', this.notes);
        if (this.jenisTapping) formData.append('jenis_tapping', this.jenisTapping);
        if (this.noSeriMgrt) formData.append('no_seri_mgrt', this.noSeriMgrt);
        if (this.merkBrandMgrt) formData.append('merk_brand_mgrt', this.merkBrandMgrt);

        Object.keys(this.material).forEach(key => {
          const value = this.material[key];
          if (value !== '' && value !== null) {
            formData.append(key, value);
          }
        });

        const response = await fetch(@json(route('sr.store')), {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: formData
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok || !result.success) {
          throw new Error(result.message || 'Gagal menyimpan SR');
        }

        const srId = result.data?.id;
        if (!srId) {
          throw new Error('SR berhasil dibuat tapi ID tidak ditemukan');
        }

        await this.uploadAllPhotos(srId);

        window.showToast?.('SR berhasil dibuat dan foto diupload!', 'success');
        window.location.href = @json(route('sr.show', ['sr' => '__ID__'])).replace('__ID__', srId);

      } catch (error) {
        console.error('Submit error:', error);
        alert('Gagal menyimpan SR: ' + (error.message || 'Unknown error'));
      } finally {
        this.submitting = false;
      }
    },

    async uploadAllPhotos(srId) {
      const urlTpl = @json(route('sr.photos.upload-draft', ['sr' => '__ID__']));
      const url = urlTpl.replace('__ID__', srId);

      for (const def of this.photoDefs) {
        const file = this.pickedFiles[def.field];
        if (!file) continue;

        const fd = new FormData();
        fd.append('_token', document.querySelector('input[name=_token]').value);
        fd.append('slot_type', def.field);
        fd.append('file', file);

        try {
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
