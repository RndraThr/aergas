@extends('layouts.app')

@section('title', 'Buat SK - AERGAS')

@section('content')

@php
  $cfgAll   = config('aergas_photos') ?: [];
  $cfgSlots = (array) (data_get($cfgAll, 'modules.SK.slots', []));
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
  if (empty($photoDefs)) {
      $photoDefs = [
          ['field'=>'pneumatic_start','label'=>'Foto Pneumatic START SK','accept'=>['image/*'],'required_objects'=>[]],
          ['field'=>'pneumatic_finish','label'=>'Foto Pneumatic FINISH SK','accept'=>['image/*'],'required_objects'=>[]],
          ['field'=>'valve','label'=>'Foto Valve SK','accept'=>['image/*'],'required_objects'=>[]],
          ['field'=>'isometrik_scan','label'=>'Scan Isometrik SK (TTD lengkap)','accept'=>['image/*','application/pdf'],'required_objects'=>[]],
      ];
  }

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
      'qty_tee_1_2' => 'Tee 1/2" (Pcs) - Opsional',
  ];
@endphp

<div class="space-y-6" x-data="skCreate()" x-init="init()">

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Buat SK</h1>
      <p class="text-gray-600 mt-1">Masukkan Reference ID untuk auto-fill data customer</p>
    </div>
    <a href="{{ route('sk.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Kembali</a>
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
        <h2 class="font-semibold text-gray-800">Informasi Instalasi SK</h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nama Petugas SK</label>
          <input type="text" value="{{ auth()->user()->name ?? '-' }}" readonly
                 class="w-full px-3 py-2 border rounded bg-gray-100 text-gray-700">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Instalasi <span class="text-red-500">*</span></label>
          <input type="date" x-model="tanggal"
                 class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
          <textarea x-model="notes" rows="3" class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                    placeholder="Catatan atau keterangan (opsional)"></textarea>
        </div>
      </div>
    </div>

    <div class="space-y-4">
      <div class="flex items-center gap-3">
        <i class="fas fa-clipboard-list text-orange-600"></i>
        <h2 class="font-semibold text-gray-800">Daftar Material SK</h2>
        <div class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-medium">
          Sesuai Isometrik
        </div>
      </div>

      <div class="bg-gray-50 p-4 rounded border">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div class="lg:col-span-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $materialLabels['panjang_pipa_gl_medium_m'] }} <span class="text-red-500">*</span>
            </label>
            <input type="number" x-model="material.panjang_pipa_gl_medium_m" step="0.01" min="0" max="1000"
                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                   placeholder="0.00" required>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $materialLabels['qty_elbow_1_2_galvanis'] }} <span class="text-red-500">*</span>
            </label>
            <input type="number" x-model="material.qty_elbow_1_2_galvanis" min="0" max="100"
                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                   placeholder="0" required>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $materialLabels['qty_sockdraft_galvanis_1_2'] }} <span class="text-red-500">*</span>
            </label>
            <input type="number" x-model="material.qty_sockdraft_galvanis_1_2" min="0" max="100"
                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                   placeholder="0" required>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $materialLabels['qty_ball_valve_1_2'] }} <span class="text-red-500">*</span>
            </label>
            <input type="number" x-model="material.qty_ball_valve_1_2" min="0" max="100"
                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                   placeholder="0" required>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $materialLabels['qty_nipel_selang_1_2'] }} <span class="text-red-500">*</span>
            </label>
            <input type="number" x-model="material.qty_nipel_selang_1_2" min="0" max="100"
                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                   placeholder="0" required>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $materialLabels['qty_elbow_reduce_3_4_1_2'] }} <span class="text-red-500">*</span>
            </label>
            <input type="number" x-model="material.qty_elbow_reduce_3_4_1_2" min="0" max="100"
                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                   placeholder="0" required>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $materialLabels['qty_long_elbow_3_4_male_female'] }} <span class="text-red-500">*</span>
            </label>
            <input type="number" x-model="material.qty_long_elbow_3_4_male_female" min="0" max="100"
                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                   placeholder="0" required>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $materialLabels['qty_klem_pipa_1_2'] }} <span class="text-red-500">*</span>
            </label>
            <input type="number" x-model="material.qty_klem_pipa_1_2" min="0" max="100"
                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                   placeholder="0" required>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $materialLabels['qty_double_nipple_1_2'] }} <span class="text-red-500">*</span>
            </label>
            <input type="number" x-model="material.qty_double_nipple_1_2" min="0" max="100"
                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                   placeholder="0" required>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ $materialLabels['qty_seal_tape'] }} <span class="text-red-500">*</span>
            </label>
            <input type="number" x-model="material.qty_seal_tape" min="0" max="100"
                   class="w-full px-3 py-2 border rounded focus:ring-2 focus:ring-blue-500"
                   placeholder="0" required>
          </div>

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
        <template x-if="hasAiFailure">
          <div class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-medium">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            Ada foto yang perlu diperbaiki
          </div>
        </template>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="ph in photoDefs" :key="ph.field">
          <div class="border rounded-lg p-4"
               :class="ai[ph.field] && !ai[ph.field].passed ? 'border-amber-300 bg-amber-50' : ''">
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

            <template x-if="ai[ph.field]">
            <div class="mt-3 text-xs border rounded p-2"
                :class="{
                    'border-green-300 bg-green-50 text-green-700': ai[ph.field].warning_level === 'excellent',
                    'border-blue-300 bg-blue-50 text-blue-700': ai[ph.field].warning_level === 'good',
                    'border-amber-300 bg-amber-50 text-amber-700': ai[ph.field].warning_level === 'warning',
                    'border-red-300 bg-red-50 text-red-700': ai[ph.field].warning_level === 'poor'
                }">
                <div class="font-medium mb-1 flex items-center">
                <i :class="{
                    'fas fa-check-circle text-green-600': ai[ph.field].warning_level === 'excellent',
                    'fas fa-thumbs-up text-blue-600': ai[ph.field].warning_level === 'good',
                    'fas fa-exclamation-triangle text-amber-600': ai[ph.field].warning_level === 'warning',
                    'fas fa-times-circle text-red-600': ai[ph.field].warning_level === 'poor'
                    }" class="mr-1"></i>
                Hasil AI: <span x-text="getWarningText(ai[ph.field].warning_level)" class="font-bold"></span>
                <span class="ml-2 text-gray-600" x-text="`Skor: ${formatScore(ai[ph.field])}`"></span>
                </div>
                <template x-if="(ai[ph.field].messages || []).length">
                  <div>
                    <span class="text-gray-600 font-medium">Catatan:</span>
                    <ul class="list-disc ml-4 mt-1">
                      <template x-for="m in ai[ph.field].messages" :key="m">
                        <li x-text="m"></li>
                      </template>
                    </ul>
                  </div>
                </template>
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

              <span class="text-xs flex-1"
                    :class="uploadStatuses[ph.field]?.includes('AI: LULUS') ? 'text-green-600' :
                           uploadStatuses[ph.field]?.includes('PERBAIKAN') ? 'text-amber-600' :
                           uploadStatuses[ph.field]?.includes('gagal') ? 'text-red-600' : 'text-gray-500'"
                    x-text="uploadStatuses[ph.field] || ''"></span>
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
              <li>• Foto akan dianalisa otomatis menggunakan AI</li>
              <li>• Pastikan objek yang diperlukan terlihat jelas dalam foto</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="flex justify-end gap-3 pt-2">
      <a href="{{ route('sk.index') }}" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">
        <i class="fas fa-arrow-left mr-2"></i>Batal
      </a>
      <button type="submit"
              :disabled="submitting || !customer || !reff || !tanggal || hasAiFailure || !isMaterialComplete()"
              class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
        <template x-if="!submitting">
          <span><i class="fas fa-save mr-2"></i>Simpan</span>
        </template>
        <template x-if="submitting">
          <span><i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan…</span>
        </template>
      </button>
    </div>

    <template x-if="hasAiFailure || !isMaterialComplete()">
      <div class="bg-amber-50 border border-amber-200 p-4 rounded">
        <div class="flex items-start">
          <i class="fas fa-exclamation-triangle text-amber-600 mr-2 mt-0.5"></i>
          <div class="text-amber-800">
            <p class="font-medium">Perhatian!</p>
            <div class="text-sm mt-1">
              <template x-if="hasAiFailure">
                <p>Beberapa foto perlu diperbaiki sebelum dapat disimpan.</p>
              </template>
              <template x-if="!isMaterialComplete()">
                <p>Data material belum lengkap. Pastikan semua field bertanda (*) sudah diisi.</p>
              </template>
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
function skCreate() {
  return {
    photoDefs: @json($photoDefs),

    reff: @json(request('reff_id', old('reff_id_pelanggan',''))),
    customer: null,
    reffMsg: '',
    tanggal: new Date().toISOString().slice(0,10),
    notes: '',

    material: {
      panjang_pipa_gl_medium_m: '',
      qty_elbow_1_2_galvanis: '',
      qty_sockdraft_galvanis_1_2: '',
      qty_ball_valve_1_2: '',
      qty_nipel_selang_1_2: '',
      qty_elbow_reduce_3_4_1_2: '',
      qty_long_elbow_3_4_male_female: '',
      qty_klem_pipa_1_2: '',
      qty_double_nipple_1_2: '',
      qty_seal_tape: '',
      qty_tee_1_2: ''
    },

    pickedFiles: {},
    previews: {},
    isPdfMap: {},
    uploadStatuses: {},

    ai: {},
    // ✅ FIXED: Update variable names untuk warning system
    hasWarnings: false,      // ← TAMBAH INI
    hasPoorPhotos: false,    // ← TAMBAH INI
    hasAiFailure: false,     // ← Keep untuk backward compatibility

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

    formatScore(aiObj) {
      if (!aiObj) return '—';
      const s = Number(aiObj.score);
      if (!Number.isFinite(s)) return '—';
      return s > 1 ? Math.round(s) + '%' : Math.round(s * 100) + '%';
    },

    // ✅ FIXED: Add missing getWarningText function
    getWarningText(warningLevel) {
      switch(warningLevel) {
        case 'excellent': return 'SANGAT BAIK';
        case 'good': return 'BAIK';
        case 'warning': return 'PERLU PERHATIAN';
        case 'poor': return 'BUTUH PERBAIKAN';
        default: return 'UNKNOWN';
      }
    },

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

    // ✅ FIXED: Update function untuk warning system
    refreshWarningFlag() {
      // Hitung ada berapa foto dengan warning/poor
      this.hasWarnings = Object.values(this.ai).some(result =>
        result && ['warning', 'poor'].includes(result.warning_level)
      );

      // Hitung ada berapa foto dengan poor (butuh perhatian extra)
      this.hasPoorPhotos = Object.values(this.ai).some(result =>
        result && result.warning_level === 'poor'
      );

      // Keep legacy hasAiFailure untuk backward compatibility
      this.hasAiFailure = this.hasPoorPhotos;
    },

    // Keep legacy function untuk backward compatibility
    refreshAiFailureFlag() {
      this.refreshWarningFlag();
    },

    clearPick(field) {
      this.pickedFiles[field] = null;
      this.previews[field] = null;
      this.isPdfMap[field] = false;
      this.uploadStatuses[field] = '';
      this.ai[field] = null;
      this.refreshWarningFlag(); // ✅ FIXED: Update call
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

    async onPick(field, e) {
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
            reader.onload = () => this.$nextTick(() => {
                this.previews[field] = reader.result;
            });
            reader.readAsDataURL(file);
        } else {
            this.previews[field] = null;
        }

        this.uploadStatuses[field] = 'Menganalisa dengan AI...';
        this.ai[field] = null;

        if (this.isPdfMap[field]) {
            this.ai[field] = {
                passed: true,
                score: 100,
                warning_level: 'excellent',
                reason: 'PDF file - akan diperiksa manual',
                messages: ['Berkas PDF: memerlukan pemeriksaan manual untuk kelengkapan tanda tangan']
            };
            this.refreshWarningFlag();
            this.uploadStatuses[field] = 'AI: SANGAT BAIK (PDF - Manual Review)';
            return;
        }

        const fd = new FormData();
        fd.append('_token', document.querySelector('input[name=_token]').value);
        fd.append('slot_type', field);
        fd.append('file', file);

        try {
            const res = await fetch(@json(route('sk.photos.precheck-generic')), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: fd
            });

            const j = await res.json().catch(() => ({}));

            if (!res.ok) {
                throw new Error(j?.message || 'Validasi AI gagal');
            }

            // ✅ FIXED: Better handling untuk warning_level
            const warningLevel = j.warning_level || this.determineWarningLevel(j.ai?.score || 0);

            this.ai[field] = {
                passed: !!j.ai?.passed,
                score: Number(j.ai?.score ?? 0),
                warning_level: warningLevel,
                reason: j.ai?.reason || 'Tidak ada keterangan',
                messages: j.ai?.messages || [j.ai?.reason || 'Validasi selesai'],
                confidence: j.ai?.confidence || 0,
                objects: [],
            };

            // Update status message berdasarkan warning level
            const scoreText = this.ai[field].score ? ` (${Math.round(this.ai[field].score)}%)` : '';
            this.uploadStatuses[field] = `AI: ${this.getWarningText(warningLevel)}${scoreText}`;

            if (j.debug) {
                console.log('AI Validation Debug:', {
                    field: field,
                    prompt: j.debug.prompt_used,
                    response: j.debug.raw_response,
                    result: this.ai[field]
                });
            }

        } catch (err) {
            console.error('AI Validation error', err);
            this.ai[field] = {
                passed: false,
                score: 0,
                warning_level: 'poor',
                reason: err.message || 'Terjadi kesalahan saat validasi AI',
                messages: ['Validasi AI gagal: ' + (err.message || 'Unknown error')],
                confidence: 0,
                objects: [],
            };
            this.uploadStatuses[field] = 'AI: ERROR - ' + (err.message || 'Validasi gagal');
        } finally {
            this.refreshWarningFlag();
        }
    },

    // ✅ ADD: Helper function untuk determine warning level di frontend
    determineWarningLevel(score) {
        if (score >= 85) return 'excellent';
        if (score >= 70) return 'good';
        if (score >= 50) return 'warning';
        return 'poor';
    },

    async onSubmit() {
        if (this.submitting) return;
        if (!this.customer || !this.reff || !this.tanggal) {
            alert('Silakan lengkapi data customer dan tanggal instalasi.');
            return;
        }
        if (!this.isMaterialComplete()) {
            alert('Data material belum lengkap. Pastikan semua field bertanda (*) sudah diisi.');
            return;
        }

        // ✅ FIXED: Warning confirmation system
        if (this.hasPoorPhotos) {
            const confirmed = confirm(
                'Ada foto dengan kualitas yang memerlukan perhatian khusus. ' +
                'Foto akan tetap diproses dan akan direview oleh tim. Lanjutkan?'
            );
            if (!confirmed) return;
        } else if (this.hasWarnings) {
            const confirmed = confirm(
                'Ada beberapa foto yang perlu perhatian. ' +
                'Foto akan tetap diproses normal. Lanjutkan?'
            );
            if (!confirmed) return;
        }

        this.submitting = true;

        try {
            const formData = new FormData();
            formData.append('_token', document.querySelector('input[name=_token]').value);
            formData.append('reff_id_pelanggan', this.reff);
            formData.append('tanggal_instalasi', this.tanggal);
            if (this.notes) formData.append('notes', this.notes);

            Object.keys(this.material).forEach(key => {
                const value = this.material[key];
                if (value !== '' && value !== null) {
                    formData.append(key, value);
                }
            });

            const response = await fetch(@json(route('sk.store')), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Gagal menyimpan SK');
            }

            const skId = result.data?.id;
            if (!skId) {
                throw new Error('SK berhasil dibuat tapi ID tidak ditemukan');
            }

            await this.uploadAllPhotos(skId);

            window.showToast?.('SK berhasil dibuat dan foto diupload!', 'success');
            window.location.href = @json(route('sk.show', ['sk' => '__ID__'])).replace('__ID__', skId);

        } catch (error) {
            console.error('Submit error:', error);
            alert('Gagal menyimpan SK: ' + (error.message || 'Unknown error'));
        } finally {
            this.submitting = false;
        }
    },

    async uploadAllPhotos(skId) {
        const urlTpl = @json(route('sk.photos.upload', ['sk' => '__ID__']));
        const url = urlTpl.replace('__ID__', skId);

        for (const def of this.photoDefs) {
            const file = this.pickedFiles[def.field];
            if (!file) continue;

            const fd = new FormData();
            fd.append('_token', document.querySelector('input[name=_token]').value);
            fd.append('slot_type', def.field);
            fd.append('file', file);

            const a = this.ai[def.field];
            if (a) {
                fd.append('ai_passed', a.passed ? '1' : '0');
                if (a.score != null) fd.append('ai_score', a.score);
                if (a.reason) fd.append('ai_reason', a.reason);
                (a.messages || []).forEach(v => fd.append('ai_notes[]', v));
            }

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
