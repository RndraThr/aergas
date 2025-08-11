@extends('layouts.app')

@section('title', 'Tambah Pelanggan - AERGAS')

@section('content')
<div class="space-y-6" x-data="createCustomer()">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Tambah Pelanggan</h1>
            <p class="text-gray-600 mt-1">Registrasi calon pelanggan baru AERGAS</p>
        </div>
        <a href="{{ route('customers.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

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

    <form action="{{ route('customers.store') }}" method="POST"
          class="bg-white rounded-xl card-shadow p-6 space-y-6"
          @submit.prevent="onSubmit($event)">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Reff ID (manual) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Reference ID <span class="text-red-500">*</span>
                </label>
                <div class="flex">
                    <input type="text" name="reff_id_pelanggan" x-model="reff"
                           @input="sanitizeReff()"
                           placeholder="Contoh: AER001"
                           class="flex-1 px-3 py-2 border rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           required>
                    <button type="button" @click="validateReff()"
                            class="px-3 py-2 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700">
                        Cek
                    </button>
                </div>
                <p class="text-xs mt-1" :class="reffStateClass()" x-text="reffMessage"></p>
            </div>

            {{-- Nama --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pelanggan <span class="text-red-500">*</span></label>
                <input type="text" name="nama_pelanggan" value="{{ old('nama_pelanggan') }}"
                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
            </div>

            {{-- Telepon --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">No. Telepon</label>
                <input type="text" name="no_telepon" value="{{ old('no_telepon') }}"
                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            {{-- Kelurahan --}}
            <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kelurahan</label>
            <input type="text" name="kelurahan"
                    value="{{ old('kelurahan') }}"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Contoh: CATUR TUNGGAL">
            </div>

            {{-- Padukuhan --}}
            <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Padukuhan</label>
            <input type="text" name="padukuhan" value="{{ old('padukuhan') }}"
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Contoh: KARANGWUNI">
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                @php $st = old('status','pending'); @endphp
                <select name="status" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    @foreach (['pending','validated','in_progress','lanjut','batal'] as $opt)
                        <option value="{{ $opt }}" {{ $st === $opt ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_',' ',$opt)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Progress --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Progress</label>
                @php $pg = old('progress_status','validasi'); @endphp
                <select name="progress_status" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    @foreach (['validasi','sk','sr','mgrt','gas_in','jalur_pipa','penyambungan','done','batal'] as $opt)
                        <option value="{{ $opt }}" {{ $pg === $opt ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_',' ',$opt)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Email (opsional) --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email (opsional)</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            {{-- Alamat --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Alamat <span class="text-red-500">*</span></label>
                <textarea name="alamat" rows="3" required
                          class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Alamat lengkap">{{ old('alamat') }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="{{ route('customers.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Batal</a>
            <button type="submit"
                    :disabled="reffValid === false || !reff"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                Simpan
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function createCustomer() {
    return {
        reff: @json(old('reff_id_pelanggan','')),
        reffValid: null, // true: available, false: taken/invalid, null: belum cek
        reffMessage: '',

        sanitizeReff() {
            this.reff = (this.reff || '').toUpperCase().replace(/[^A-Z0-9]/g,'');
            // reset status saat user mengubah input
            this.reffValid = null;
            this.reffMessage = '';
        },
        reffStateClass() {
            if (this.reffValid === true)  return 'text-green-600';
            if (this.reffValid === false) return 'text-red-600';
            return 'text-gray-500';
        },
        async validateReff() {
            const v = (this.reff || '').trim();
            if (!v) { this.reffValid = null; this.reffMessage = 'Masukkan Reference ID terlebih dahulu.'; return; }

            try {
                const url = @json(route('customers.validate-reff', ['reffId' => '___'])).replace('___', encodeURIComponent(v));
                const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
                const json = await res.json().catch(() => ({}));

                if (res.ok && json?.success) {
                    // DITEMUKAN -> SUDAH DIPAKAI (tidak boleh dipakai untuk create)
                    this.reffValid = false;
                    this.reffMessage = 'Reference ID sudah digunakan.';
                } else if (res.status === 404) {
                    // TIDAK DITEMUKAN -> TERSEDIA
                    this.reffValid = true;
                    this.reffMessage = 'Reference ID tersedia.';
                } else {
                    this.reffValid = false;
                    this.reffMessage = json?.message || 'Reference ID tidak valid.';
                }
            } catch (e) {
                this.reffValid = false;
                this.reffMessage = 'Gagal memeriksa Reference ID.';
            }
        },
        onSubmit(e) {
            // cegah submit kalau reff invalid
            if (this.reffValid === false || !this.reff) {
                e.preventDefault();
                this.reffMessage ||= 'Reference ID wajib diisi dan belum digunakan.';
                this.reffValid = false;
            } else {
                e.target.submit();
            }
        }
    }
}
</script>
@endpush
