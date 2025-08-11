{{-- resources/views/sk/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Buat SK - AERGAS')

@section('content')
@php
    // fallback kalau $photoDefs belum dikirim dari controller
    $photoDefs = $photoDefs ?? [
        ['field' => 'pneumatic_start',  'label' => 'Foto Pneumatic START SK',  'accept' => ['image/*']],
        ['field' => 'pneumatic_finish', 'label' => 'Foto Pneumatic FINISH SK', 'accept' => ['image/*']],
        ['field' => 'valve',            'label' => 'Foto Valve SK',            'accept' => ['image/*']],
        ['field' => 'pipa_depan',       'label' => 'Foto Pipa SK Depan',       'accept' => ['image/*']],
        ['field' => 'isometrik_scan',   'label' => 'Scan Isometrik SK (TTD lengkap)', 'accept' => ['image/*','application/pdf']],
    ];
@endphp

<div class="space-y-6" x-data="skCreate()" x-init="init()">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold text-gray-800">Buat SK</h1>
      <p class="text-gray-600 mt-1">Masukkan Reference ID untuk auto-fill data customer</p>
    </div>
    <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">Kembali</a>
  </div>

  {{-- Errors (server) --}}
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

  {{-- FORM (disubmit via JS agar bisa upload foto setelah create) --}}
  <form class="bg-white rounded-xl card-shadow p-6 space-y-8" @submit.prevent="onSubmit">
    @csrf

    {{-- SECTION: Informasi Customer --}}
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
            <div class="font-medium" x-text="customer.alamat || '-'"></div>
            <div class="text-sm text-gray-600 mt-1">
              <span class="mr-4">Kelurahan: <b x-text="customer.kelurahan || '-'"></b></span>
              <span>Padukuhan: <b x-text="customer.padukuhan || '-'"></b></span>
            </div>
          </div>
        </div>
      </template>
    </div>

    {{-- SECTION: Informasi Instalasi SK --}}
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

    {{-- SECTION: Upload Foto (dinamis dari config) --}}
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
            <template x-if="previews[ph.field]">
              <img :src="previews[ph.field]" alt="" class="h-32 w-full object-cover rounded" x-show="!isPdf(ph.field)">
            </template>
            <template x-if="isPdf(ph.field)">
              <div class="h-32 flex items-center justify-center bg-gray-50 rounded border">
                <span class="text-xs text-gray-600">PDF terpilih</span>
              </div>
            </template>

            <div class="mt-2">
              <template x-if="(ph.required_objects || []).length">
                <div class="text-xs text-gray-500">
                  Objek wajib:
                  <span class="inline-block mt-1">
                    <template x-for="obj in ph.required_objects" :key="obj">
                      <span class="px-2 py-0.5 mr-1 mb-1 bg-gray-100 rounded border inline-block" x-text="obj"></span>
                    </template>
                  </span>
                </div>
              </template>
            </div>

            <div class="flex items-center gap-2 mt-3">
              <input class="hidden" type="file"
                     :accept="acceptString(ph.accept)"
                     :id="`inp_${ph.field}`"
                     @change="onPick(ph.field, $event)">
              <label :for="`inp_${ph.field}`"
                     class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded cursor-pointer">Pilih</label>

              <button type="button" @click="clearPick(ph.field)"
                      class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded">Hapus</button>

              <span class="text-xs" x-text="uploadStatuses[ph.field] || ''"></span>
            </div>
          </div>
        </template>
      </div>

      <p class="text-xs text-gray-500">Format: JPG/PNG/WEBP, dan untuk Isometrik boleh PDF. Maks 10 MB per file.</p>
    </div>

    <div class="flex justify-end gap-3 pt-2">
      <a href="{{ route('sk.index') }}" class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200">Batal</a>
      <button type="submit"
              :disabled="submitting || !customer || !reff || !tanggal"
              class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
        <span x-show="!submitting">Simpan</span>
        <span x-show="submitting"><i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan…</span>
      </button>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
function skCreate() {
  return {
    // data dari server
    photoDefs: @json($photoDefs),

    // state form
    reff: @json(request('reff_id', old('reff_id_pelanggan',''))),
    customer: null,
    reffMsg: '',
    tanggal: new Date().toISOString().slice(0,10),
    notes: '',

    // foto
    pickedFiles: {},     // { field: File }
    previews: {},        // { field: dataURL }
    isPdfMap: {},        // { field: boolean }
    uploadStatuses: {},  // { field: 'uploaded' | 'gagal' | '' }
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

    async findCustomer() {
      this.customer = null;
      this.reffMsg = '';
      const v = (this.reff || '').trim().toUpperCase();
      if (!v) { this.reffMsg = 'Masukkan Reference ID terlebih dahulu.'; return; }

      try {
        const url = @json(route('customers.validate-reff', ['reffId' => '___'])).replace('___', encodeURIComponent(v));
        const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
        const json = await res.json().catch(() => ({}));

        // kompatibel 2 bentuk: {success:bool,data} atau {valid,exists,data}
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
      this.pickedFiles[field] = file;
      this.isPdfMap[field] = (file.type === 'application/pdf');

      if (!this.isPdfMap[field]) {
        const reader = new FileReader();
        reader.onload = () => this.$nextTick(() => { this.previews[field] = reader.result; });
        reader.readAsDataURL(file);
      } else {
        this.previews[field] = null;
      }
      this.uploadStatuses[field] = '';
    },

    clearPick(field) {
      delete this.pickedFiles[field];
      delete this.previews[field];
      delete this.isPdfMap[field];
      this.uploadStatuses[field] = '';
      const inp = document.getElementById(`inp_${field}`);
      if (inp) inp.value = '';
    },

    async onSubmit() {
      if (!this.customer || !this.reff || !this.tanggal) {
        this.reffMsg ||= 'Lengkapi Reference ID & cari pelanggan.';
        return;
      }

      this.submitting = true;
      try {
        // 1) Simpan data dasar SK
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
        if (!saveRes.ok) throw new Error((saveJson && (saveJson.message || saveJson.error)) || 'Gagal menyimpan data SK');

        // response bisa {id:...} atau {data:{id:...}}
        const sk = saveJson.data ?? saveJson;
        if (!sk?.id) throw new Error('Response tidak berisi ID SK');

        // 2) Upload setiap foto yang dipilih
        await this.uploadAllPhotos(sk.id);

        // 3) Beres
        (window.showToast ? window.showToast('Data SK tersimpan. Foto yang dipilih sudah diunggah.', 'success') : alert('Data SK tersimpan. Foto terunggah.'));
        // optional redirect:
        // window.location.href = `/sk/${sk.id}`;
      } catch (e) {
        console.error(e);
        (window.showToast ? window.showToast(e.message || 'Terjadi kesalahan saat menyimpan', 'error') : alert(e.message || 'Terjadi kesalahan saat menyimpan'));
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

        try {
          const res = await fetch(url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
          });
          const j = await res.json().catch(() => ({}));
          if (!res.ok || !(j && (j.success === true || j.photo_id))) throw new Error(j?.message || 'Gagal upload');
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
