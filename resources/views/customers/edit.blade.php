{{-- resources/views/customers/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Ubah Data Pelanggan - AERGAS')

@section('content')
@php
    /** @var \App\Models\User $auth */
    $auth = auth()->user();
    $canEditReff = $auth && ($auth->isSuperAdmin() || $auth->isAdmin() || $auth->isTracer());
    $statuses = ['pending','validated','in_progress','lanjut','batal'];
    $progresses = ['validasi','sk','sr','mgrt','gas_in','jalur_pipa','penyambungan','done','batal'];
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Ubah Data Pelanggan</h1>
            <p class="text-gray-600 mt-1">Perbarui informasi pelanggan</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('customers.show', $customer->reff_id_pelanggan) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                <i class="fas fa-eye mr-2"></i>Lihat
            </a>
            <a href="{{ route('customers.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
            </a>
        </div>
    </div>

    {{-- Errors --}}
    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg">
            <div class="font-semibold mb-2">Periksa kembali input Anda:</div>
            <ul class="list-disc ml-5 space-y-1">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form --}}
    <form action="{{ route('customers.update', $customer->reff_id_pelanggan) }}" method="POST"
          class="bg-white rounded-xl card-shadow p-6 space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- REFF ID --}}
            <div x-data="reffEditor('{{ $customer->reff_id_pelanggan }}', {{ $canEditReff ? 'true' : 'false' }})">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reference ID @if($canEditReff)<span class="text-red-500">*</span>@endif</label>

                @if ($canEditReff)
                    <div class="flex">
                        <input type="text" name="reff_id_pelanggan" x-model="reff" @input="sanitize()"
                               class="flex-1 px-3 py-2 border rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Contoh: AER001" required>
                        <button type="button" @click="check()"
                                class="px-3 py-2 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700">
                            Cek
                        </button>
                    </div>
                    <p class="text-xs mt-1" :class="stateClass()" x-text="message"></p>
                @else
                    <div class="flex">
                        <input type="text" value="{{ $customer->reff_id_pelanggan }}" readonly
                               class="flex-1 px-3 py-2 border rounded-l-lg bg-gray-100 text-gray-700">
                        <button type="button"
                                @click="navigator.clipboard.writeText('{{ $customer->reff_id_pelanggan }}'); window.showToast?.('Disalin','success')"
                                class="px-3 py-2 bg-gray-700 text-white rounded-r-lg hover:bg-gray-800">
                            Salin
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">ID tidak dapat diubah untuk role Anda.</p>
                @endif
            </div>

            {{-- Nama --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pelanggan <span class="text-red-500">*</span></label>
                <input type="text" name="nama_pelanggan" value="{{ old('nama_pelanggan', $customer->nama_pelanggan) }}"
                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
            </div>

            {{-- Telepon --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">No. Telepon</label>
                <input type="text" name="no_telepon" value="{{ old('no_telepon', $customer->no_telepon) }}"
                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            {{-- Kelurahan --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kelurahan</label>
                <input type="text"
                    name="kelurahan"
                    value="{{ old('kelurahan', $customer->kelurahan) }}"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Contoh: Menteng">
            </div>

            {{-- Padukuhan --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Padukuhan</label>
                <input type="text"
                    name="padukuhan"
                    value="{{ old('padukuhan', $customer->padukuhan) }}"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Contoh: RT 05/RW 02">
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                @php $st = old('status', $customer->status ?? 'pending'); @endphp
                <select name="status" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    @foreach ($statuses as $opt)
                        <option value="{{ $opt }}" {{ $st === $opt ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_',' ',$opt)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Progress --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Progress</label>
                @php $pg = old('progress_status', $customer->progress_status ?? 'validasi'); @endphp
                <select name="progress_status" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    @foreach ($progresses as $opt)
                        <option value="{{ $opt }}" {{ $pg === $opt ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_',' ',$opt)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Email (opsional) --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email (opsional)</label>
                <input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}"
                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            {{-- Alamat --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Alamat <span class="text-red-500">*</span></label>
                <textarea name="alamat" rows="3" required
                          class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Alamat lengkap">{{ old('alamat', $customer->alamat) }}</textarea>
            </div>

            {{-- Keterangan --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan (opsional)</label>
                <textarea name="keterangan" rows="2"
                          class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Catatan">{{ old('keterangan', $customer->keterangan) }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="{{ route('customers.show', $customer->reff_id_pelanggan) }}"
               class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Batal</a>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function reffEditor(current, editable) {
  return {
    reff: current,
    message: '',
    valid: null, // true/false/null

    sanitize() {
      if (!editable) return;
      this.reff = (this.reff || '').toUpperCase().replace(/[^A-Z0-9]/g,'');
      this.valid = null;
      this.message = '';
    },
    stateClass() {
      if (this.valid === true)  return 'text-green-600';
      if (this.valid === false) return 'text-red-600';
      return 'text-gray-500';
    },
    async check() {
      if (!editable) return;
      const v = (this.reff || '').trim();
      if (!v) { this.valid = null; this.message = 'Masukkan Reference ID.'; return; }

      try {
        const url = @json(route('customers.validate-reff', ['reffId' => '___'])).replace('___', encodeURIComponent(v));
        const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
        const j = await res.json().catch(() => ({}));

        if (res.ok && j?.success) {
          // ditemukan di DB
          if (v === current) {
            this.valid = true;  this.message = 'ID sama dengan ID saat ini (boleh).';
          } else {
            this.valid = false; this.message = 'ID sudah dipakai pelanggan lain.';
          }
        } else if (res.status === 404) {
          // tidak ditemukan -> tersedia
          this.valid = true;  this.message = 'ID tersedia.';
        } else {
          this.valid = false; this.message = j?.message || 'Gagal memeriksa ID.';
        }
      } catch (e) {
        this.valid = false; this.message = 'Gagal memeriksa ID.';
      }
    }
  }
}
</script>
@endpush
