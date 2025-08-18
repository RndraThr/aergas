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
          ['field'=>'pipa_depan','label'=>'Foto Pipa Depan SK','accept'=>['image/*'],'required_objects'=>[]],
          ['field'=>'isometrik_scan','label'=>'Scan Isometrik SK (TTD lengkap)','accept'=>['image/*','application/pdf'],'required_objects'=>[]],
      ];
  }
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
                   :class="ai[ph.field].passed ? 'border-green-300 bg-green-50 text-green-700' : 'border-amber-300 bg-amber-50 text-amber-700'">
                <div class="font-medium mb-1 flex items-center">
                  <i :class="ai[ph.field].passed ? 'fas fa-check-circle text-green-600' : 'fas fa-exclamation-triangle text-amber-600'" class="mr-1"></i>
                  Hasil AI: <span x-text="ai[ph.field].passed ? 'LULUS' : 'PERLU PERBAIKAN'" class="font-bold"></span>
                  <span class="ml-2 text-gray-600" x-show="ai[ph.field]">
                    Skor: <span x-text="formatScore(ai[ph.field])"></span>
                  </span>
                </div>
                <template x-if="(ai[ph.field].objects || []).length">
                  <div class="mb-1">
                    <span class="text-gray-600 font-medium">Objek terdeteksi:</span>
                    <div class="mt-1">
                      <template x-for="o in ai[ph.field].objects" :key="o">
                        <span class="ml-1 px-2 py-0.5 bg-white border rounded inline-block mr-1 mb-1" x-text="o"></span>
                      </template>
                    </div>
                  </div>
                </template>
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

            <template x-if="(ph.required_objects || []).length">
              <div class="mt-2 text-xs text-gray-500">
                <span class="font-medium">Objek wajib:</span>
                <div class="mt-1">
                  <template x-for="obj in ph.required_objects" :key="obj">
                    <span class="px-2 py-0.5 mr-1 mb-1 bg-gray-100 rounded border inline-block" x-text="obj"></span>
                  </template>
                </div>
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
              :disabled="submitting || !customer || !reff || !tanggal || hasAiFailure"
              class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
        <template x-if="!submitting">
          <span><i class="fas fa-save mr-2"></i>Simpan</span>
        </template>
        <template x-if="submitting">
          <span><i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan…</span>
        </template>
      </button>
    </div>

    <template x-if="hasAiFailure">
      <div class="bg-amber-50 border border-amber-200 p-4 rounded">
        <div class="flex items-start">
          <i class="fas fa-exclamation-triangle text-amber-600 mr-2 mt-0.5"></i>
          <div class="text-amber-800">
            <p class="font-medium">Perhatian!</p>
            <p class="text-sm mt-1">Beberapa foto perlu diperbaiki sebelum dapat disimpan. Periksa hasil analisa AI di atas dan pastikan foto memenuhi kriteria yang diperlukan.</p>
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

    pickedFiles: {},
    previews: {},
    isPdfMap: {},
    uploadStatuses: {},

    ai: {},
    hasAiFailure: false,

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

      this.uploadStatuses[field] = 'Menganalisa…';
      this.ai[field] = null;

      if (this.isPdfMap[field]) {
        this.ai[field] = {
          passed: true,
          score: null,
          objects: [],
          messages: ['Berkas PDF: cek manual/TTD']
        };
        this.refreshAiFailureFlag();
        this.uploadStatuses[field] = 'AI: LULUS (PDF)';
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
        if (!res.ok || !j?.success) {
          throw new Error(j?.message || 'Precheck gagal');
        }

        this.ai[field] = {
          passed: !!j.ai?.passed,
          score: Number(j.ai?.score ?? 0),
          objects: Array.isArray(j.ai?.objects) ? j.ai.objects : [],
          messages: Array.isArray(j.ai?.messages) ? j.ai.messages : [],
        };

        this.uploadStatuses[field] = this.ai[field].passed ? ( (j.warnings && j.warnings.length) ? 'AI: LULUS (warning)' : 'AI: LULUS' ) : 'AI: PERLU PERBAIKAN';

      } catch (err) {
        console.error('Precheck error', err);
        this.ai[field] = {
          passed: false,
          score: null,
          objects: [],
          messages: ['Precheck gagal diproses: ' + (err.message || 'Unknown error')]
        };
        this.uploadStatuses[field] = 'Precheck gagal';
      } finally {
        this.refreshAiFailureFlag();
      }
    },

    clearPick(field) {
      delete this.pickedFiles[field];
      delete this.previews[field];
      delete this.isPdfMap[field];
      delete this.ai[field];
      this.uploadStatuses[field] = '';

      const inp = document.getElementById(`inp_${field}`);
      if (inp) inp.value = '';

      this.refreshAiFailureFlag();
    },

    refreshAiFailureFlag() {
      this.hasAiFailure = Object.entries(this.pickedFiles).some(([f]) => {
        const a = this.ai[f];
        return a && a.passed === false;
      });
    },

    async onSubmit() {
      if (!this.customer || !this.reff || !this.tanggal) {
        this.reffMsg ||= 'Lengkapi Reference ID & cari pelanggan.';
        return;
      }

      if (this.hasAiFailure) {
        alert('Tidak dapat menyimpan karena ada foto yang perlu diperbaiki. Periksa hasil AI precheck.');
        return;
      }

      this.submitting = true;
      try {
        const saveRes = await fetch(@json(route('sk.store')), {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: this.makeSaveFormData()
        });

        const saveJson = await saveRes.json().catch(() => ({}));
        if (!saveRes.ok) {
          throw new Error((saveJson && (saveJson.message || saveJson.error)) || 'Gagal menyimpan data SK');
        }

        const sk = saveJson.data ?? saveJson;
        if (!sk?.id) {
          throw new Error('Response tidak berisi ID SK');
        }

        await this.uploadAllPhotos(sk.id);

        window.showToast?.('Data SK tersimpan. Foto yang dipilih sudah diunggah.', 'success') ||
          alert('Data SK tersimpan. Foto terunggah.');
        window.location.href = @json(route('sk.show', ['sk'=>'__ID__'])).replace('__ID__', sk.id);

      } catch (e) {
        console.error(e);
        window.showToast?.(e.message || 'Terjadi kesalahan saat menyimpan', 'error') ||
          alert(e.message || 'Terjadi kesalahan saat menyimpan');
      } finally {
        this.submitting = false;
      }
    },

    makeSaveFormData() {
      const fd = new FormData();
      fd.append('_token', document.querySelector('input[name=_token]').value);
      fd.append('reff_id_pelanggan', this.reff);
      fd.append('tanggal_instalasi', this.tanggal);
      if (this.notes) fd.append('notes', this.notes);
      return fd;
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
          (a.objects || []).forEach(v => fd.append('ai_objects[]', v));
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

          this.uploadStatuses[def.field] = '✓ uploaded';

        } catch (e) {
          console.error('Upload gagal', def.field, e);
          this.uploadStatuses[def.field] = '✗ gagal';
        }
      }
    }
  }
}
</script>
@endpush
