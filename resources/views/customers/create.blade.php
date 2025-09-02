@extends('layouts.app')

@section('title', 'Tambah Pelanggan - AERGAS')
@section('page-title', 'Tambah Pelanggan')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="customerCreateData()">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Tambah Pelanggan Baru</h1>
            <p class="text-gray-600 mt-1">Daftarkan calon pelanggan gas AERGAS</p>
        </div>

        <a href="{{ route('customers.index') }}"
           class="flex items-center space-x-2 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left"></i>
            <span>Kembali</span>
        </a>
    </div>

    <form @submit.prevent="submitForm()" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center mb-6">
                <div class="w-10 h-10 bg-gradient-to-br from-aergas-navy to-aergas-orange rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-user text-white"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Informasi Dasar</h2>
                    <p class="text-sm text-gray-600">Data identitas calon pelanggan</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Reference ID <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text"
                               x-model="form.reff_id_pelanggan"
                               @input="validateReffId()"
                               :class="errors.reff_id_pelanggan ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-aergas-orange focus:border-transparent'"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 transition-colors"
                               placeholder="REF001, AGS001, etc..."
                               required>
                        <div x-show="validatingReffId" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                            <i class="fas fa-spinner animate-spin text-gray-400"></i>
                        </div>
                    </div>
                    <div x-show="errors.reff_id_pelanggan" class="mt-1 text-sm text-red-600" x-text="errors.reff_id_pelanggan"></div>
                    <div x-show="reffIdAvailable && form.reff_id_pelanggan" class="mt-1 text-sm text-green-600">
                        <i class="fas fa-check mr-1"></i>Reference ID tersedia
                    </div>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Pelanggan <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           x-model="form.nama_pelanggan"
                           :class="errors.nama_pelanggan ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-aergas-orange focus:border-transparent'"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 transition-colors"
                           placeholder="Nama lengkap pelanggan"
                           required>
                    <div x-show="errors.nama_pelanggan" class="mt-1 text-sm text-red-600" x-text="errors.nama_pelanggan"></div>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nomor Telepon <span class="text-red-500">*</span>
                    </label>
                    <input type="tel"
                           x-model="form.no_telepon"
                           :class="errors.no_telepon ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-aergas-orange focus:border-transparent'"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 transition-colors"
                           placeholder="08123456789"
                           required>
                    <div x-show="errors.no_telepon" class="mt-1 text-sm text-red-600" x-text="errors.no_telepon"></div>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email"
                           x-model="form.email"
                           :class="errors.email ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-aergas-orange focus:border-transparent'"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 transition-colors"
                           placeholder="email@example.com">
                    <div x-show="errors.email" class="mt-1 text-sm text-red-600" x-text="errors.email"></div>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kelurahan</label>
                    <input type="text"
                           x-model="form.kelurahan"
                           :class="errors.kelurahan ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-aergas-orange focus:border-transparent'"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 transition-colors"
                           placeholder="Nama kelurahan">
                    <div x-show="errors.kelurahan" class="mt-1 text-sm text-red-600" x-text="errors.kelurahan"></div>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Padukuhan/Dusun</label>
                    <input type="text"
                           x-model="form.padukuhan"
                           :class="errors.padukuhan ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-aergas-orange focus:border-transparent'"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 transition-colors"
                           placeholder="Nama padukuhan/dusun">
                    <div x-show="errors.padukuhan" class="mt-1 text-sm text-red-600" x-text="errors.padukuhan"></div>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Pelanggan</label>
                    <select x-model="form.jenis_pelanggan"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                        <option value="residensial">Residensial</option>
                        <option value="komersial">Komersial</option>
                        <option value="industri">Industri</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Alamat Lengkap <span class="text-red-500">*</span>
                    </label>
                    <textarea x-model="form.alamat"
                              :class="errors.alamat ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-aergas-orange focus:border-transparent'"
                              class="w-full px-3 py-2 border rounded-lg focus:ring-2 transition-colors"
                              rows="3"
                              placeholder="Alamat lengkap termasuk kode pos"
                              required></textarea>
                    <div x-show="errors.alamat" class="mt-1 text-sm text-red-600" x-text="errors.alamat"></div>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan</label>
                    <textarea x-model="form.keterangan"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent"
                              rows="2"
                              placeholder="Catatan tambahan (opsional)"></textarea>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-aergas-navy/5 to-aergas-orange/5 rounded-xl p-6 border border-aergas-orange/20">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan Registrasi</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Status Awal:</span>
                    <div class="font-medium text-yellow-600">Pending Validation</div>
                </div>
                <div>
                    <span class="text-gray-600">Progress:</span>
                    <div class="font-medium text-blue-600">Validasi</div>
                </div>
                <div>
                    <span class="text-gray-600">Tanggal Registrasi:</span>
                    <div class="font-medium text-gray-900" x-text="new Date().toLocaleDateString('id-ID')"></div>
                </div>
            </div>

            <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                <div class="flex items-start space-x-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <div class="text-sm text-blue-700">
                        <p class="font-medium mb-1">Langkah Selanjutnya:</p>
                        <ul class="list-disc list-inside space-y-1 text-xs">
                            <li>Pelanggan akan masuk status "Pending Validation"</li>
                            <li>Tracer akan memvalidasi data pelanggan</li>
                            <li>Setelah divalidasi, dapat memulai proses SK</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between space-x-4">
            <button type="button"
                    @click="resetForm()"
                    class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                Reset Form
            </button>

            <div class="flex items-center space-x-3">
                <button type="submit"
                        :disabled="submitting || !formValid"
                        class="px-6 py-2 bg-gradient-to-r from-aergas-navy to-aergas-orange text-white rounded-lg hover:shadow-lg transition-all duration-300 disabled:opacity-50">
                    <span x-show="!submitting">Daftarkan Pelanggan</span>
                    <span x-show="submitting">
                        <i class="fas fa-spinner animate-spin mr-2"></i>Mendaftarkan...
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function customerCreateData() {
    return {
        form: {
            reff_id_pelanggan: '',
            nama_pelanggan: '',
            alamat: '',
            no_telepon: '',
            email: '',
            kelurahan: '',
            padukuhan: '',
            jenis_pelanggan: 'residensial',
            keterangan: ''
        },

        errors: {},
        submitting: false,
        validatingReffId: false,
        reffIdAvailable: false,

        get formValid() {
            return this.form.reff_id_pelanggan &&
                   this.form.nama_pelanggan &&
                   this.form.alamat &&
                   this.form.no_telepon &&
                   this.reffIdAvailable &&
                   Object.keys(this.errors).length === 0;
        },

        async validateReffId() {
            if (!this.form.reff_id_pelanggan || this.form.reff_id_pelanggan.length < 3) {
                this.reffIdAvailable = false;
                return;
            }

            this.validatingReffId = true;
            this.errors.reff_id_pelanggan = '';

            try {
                const response = await fetch(`{{ route('customers.validate-reff', '') }}/${this.form.reff_id_pelanggan}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    }
                });

                if (response.status === 404) {
                    this.reffIdAvailable = true;
                    this.errors.reff_id_pelanggan = '';
                } else {
                    const data = await response.json();
                    if (data.exists) {
                        this.reffIdAvailable = false;
                        this.errors.reff_id_pelanggan = 'Reference ID sudah digunakan';
                    } else {
                        this.reffIdAvailable = true;
                        this.errors.reff_id_pelanggan = '';
                    }
                }
            } catch (error) {
                this.reffIdAvailable = true;
            } finally {
                this.validatingReffId = false;
            }
        },

        async submitForm() {
            if (!this.formValid) return;

            this.submitting = true;
            this.errors = {};

            try {
                const response = await fetch('{{ route('customers.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();

                if (data.success) {
                    window.showToast('success', 'Pelanggan berhasil didaftarkan!');

                    setTimeout(() => {
                        window.location.href = data.data ? `/customers/${data.data.reff_id_pelanggan}` : '{{ route('customers.index') }}';
                    }, 1500);
                } else {
                    if (data.errors) {
                        this.errors = data.errors;
                    } else {
                        window.showToast('error', data.message || 'Gagal mendaftarkan pelanggan');
                    }
                }
            } catch (error) {
                console.error('Error submitting form:', error);
                window.showToast('error', 'Terjadi kesalahan saat mendaftarkan pelanggan');
            } finally {
                this.submitting = false;
            }
        },

        resetForm() {
            if (confirm('Apakah Anda yakin ingin mengosongkan form?')) {
                this.form = {
                    reff_id_pelanggan: '',
                    nama_pelanggan: '',
                    alamat: '',
                    no_telepon: '',
                    email: '',
                    kelurahan: '',
                    padukuhan: '',
                    jenis_pelanggan: 'residensial',
                    keterangan: ''
                };
                this.errors = {};
                this.reffIdAvailable = false;
            }
        }
    }
}
</script>
@endpush
@endsection
