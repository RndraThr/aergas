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
                               @input.debounce.500ms="validateReffId()"
                               @blur="validateReffId()"
                               :class="errors.reff_id_pelanggan ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-aergas-orange focus:border-transparent'"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 transition-colors"
                               placeholder="123456, REF001, AGS001, etc..."
                               required>
                        <div x-show="validatingReffId" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                            <i class="fas fa-spinner animate-spin text-gray-400"></i>
                        </div>
                    </div>
                    <div x-show="errors.reff_id_pelanggan" class="mt-1 text-sm text-red-600" x-text="errors.reff_id_pelanggan"></div>
                    <div x-show="reffIdAvailable && form.reff_id_pelanggan" class="mt-1 text-sm text-green-600">
                        <i class="fas fa-check mr-1"></i>Reference ID tersedia
                    </div>

                    <!-- Preview Display -->
                    <div x-show="form.reff_id_pelanggan && displayReffId !== form.reff_id_pelanggan" class="mt-1 text-sm text-blue-600">
                        <i class="fas fa-eye mr-1"></i>Akan ditampilkan sebagai: <span class="font-semibold" x-text="displayReffId"></span>
                    </div>

                    <div class="mt-1 text-xs text-gray-500">
                        💡 Tip: 6 angka (123456) akan ditampilkan sebagai "00123456" di sistem
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
                        <option value="pengembangan">Pengembangan</option>
                        <option value="penetrasi">Penetrasi</option>
                        <option value="on_the_spot_penetrasi">On The Spot Penetrasi</option>
                        <option value="on_the_spot_pengembangan">On The Spot Pengembangan</option>
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

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center mb-6">
                <div class="w-10 h-10 bg-gradient-to-br from-aergas-navy to-aergas-orange rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-map-marker-alt text-white"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Informasi Lokasi</h2>
                    <p class="text-sm text-gray-600">Koordinat GPS lokasi pelanggan (opsional)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Latitude</label>
                    <input type="number"
                           x-model="form.latitude"
                           step="any"
                           :class="errors.latitude ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-aergas-orange focus:border-transparent'"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 transition-colors"
                           placeholder="-7.8014">
                    <div x-show="errors.latitude" class="mt-1 text-sm text-red-600" x-text="errors.latitude"></div>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Longitude</label>
                    <input type="number"
                           x-model="form.longitude"
                           step="any"
                           :class="errors.longitude ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-aergas-orange focus:border-transparent'"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 transition-colors"
                           placeholder="110.3695">
                    <div x-show="errors.longitude" class="mt-1 text-sm text-red-600" x-text="errors.longitude"></div>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sumber Koordinat</label>
                    <select x-model="form.coordinate_source"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                        <option value="manual">Manual Input</option>
                        <option value="gps">GPS</option>
                        <option value="maps">Google Maps</option>
                        <option value="survey">Survey</option>
                    </select>
                </div>

                <div class="md:col-span-1 flex items-end">
                    <button type="button"
                            @click="getCurrentLocation()"
                            :disabled="gettingLocation"
                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!gettingLocation">
                            <i class="fas fa-location-arrow mr-2"></i>Ambil Lokasi Saat Ini
                        </span>
                        <span x-show="gettingLocation">
                            <i class="fas fa-spinner animate-spin mr-2"></i>Mendapatkan Lokasi...
                        </span>
                    </button>
                </div>

                <div class="md:col-span-2" x-show="form.latitude && form.longitude">
                    <div class="bg-green-50 border border-green-200 p-4 rounded-lg">
                        <div class="flex items-start">
                            <i class="fas fa-map-marker-alt text-green-600 mr-2 mt-0.5"></i>
                            <div>
                                <p class="font-medium text-green-800">Koordinat Valid</p>
                                <p class="text-green-700 text-sm mt-1">
                                    Lokasi: <span x-text="form.latitude + ', ' + form.longitude"></span>
                                </p>
                                <a :href="'https://www.google.com/maps?q=' + form.latitude + ',' + form.longitude"
                                   target="_blank"
                                   class="text-blue-600 hover:text-blue-800 text-sm inline-flex items-center mt-1">
                                    <i class="fas fa-external-link-alt mr-1"></i>Lihat di Google Maps
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <div class="bg-gradient-to-r from-aergas-navy/5 to-aergas-orange/5 rounded-xl p-6 border border-aergas-orange/20">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan Registrasi</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Reference ID:</span>
                    <div class="font-medium text-aergas-navy" x-text="displayReffId || 'Belum diisi'"></div>
                </div>
                <div>
                    <span class="text-gray-600">Status Awal:</span>
                    <div class="font-medium text-yellow-600">Pending Validation</div>
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
                <div x-show="!formValid && !submitting" class="text-sm text-red-600">
                    <template x-if="!form.reff_id_pelanggan">
                        <span>Reference ID diperlukan</span>
                    </template>
                    <template x-if="form.reff_id_pelanggan && !reffIdAvailable">
                        <span>Reference ID belum valid</span>
                    </template>
                    <template x-if="!form.nama_pelanggan">
                        <span>Nama pelanggan diperlukan</span>
                    </template>
                    <template x-if="!form.alamat">
                        <span>Alamat diperlukan</span>
                    </template>
                    <template x-if="!form.no_telepon">
                        <span>Nomor telepon diperlukan</span>
                    </template>
                    <template x-if="Object.keys(errors).length > 0">
                        <span>Perbaiki kesalahan pada form</span>
                    </template>
                </div>

                <button type="submit"
                        :disabled="submitting || !formValid"
                        :class="formValid ? 'bg-gradient-to-r from-aergas-navy to-aergas-orange hover:shadow-lg' : 'bg-gray-400 cursor-not-allowed'"
                        class="px-6 py-2 text-white rounded-lg transition-all duration-300 disabled:opacity-50">
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
            jenis_pelanggan: 'pengembangan',
            keterangan: '',
            latitude: '',
            longitude: '',
            coordinate_source: 'manual'
        },

        errors: {},
        submitting: false,
        validatingReffId: false,
        reffIdAvailable: false,
        gettingLocation: false,


        get formValid() {
            const hasRequiredFields = this.form.reff_id_pelanggan &&
                                    this.form.nama_pelanggan &&
                                    this.form.alamat &&
                                    this.form.no_telepon;

            const hasNoErrors = Object.keys(this.errors).length === 0;

            const valid = hasRequiredFields && this.reffIdAvailable && hasNoErrors;

            return valid;
        },

        get displayReffId() {
            if (!this.form.reff_id_pelanggan) return '';

            const value = this.form.reff_id_pelanggan.toString().trim();

            // Check if input is exactly 6 digits
            if (/^\d{6}$/.test(value)) {
                return '00' + value;
            }

            return value.toUpperCase();
        },

        async validateReffId() {
            if (!this.form.reff_id_pelanggan || this.form.reff_id_pelanggan.length < 3) {
                this.reffIdAvailable = false;
                // Clear error when field is too short
                delete this.errors.reff_id_pelanggan;
                return;
            }

            this.validatingReffId = true;
            // Completely remove the error instead of setting to empty string
            delete this.errors.reff_id_pelanggan;

            try {
                const response = await fetch(`/customers/validate-reff/${this.form.reff_id_pelanggan}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    }
                });

                if (response.status === 404) {
                    // 404 means customer not found = Reference ID is available
                    this.reffIdAvailable = true;
                    delete this.errors.reff_id_pelanggan;
                } else if (response.status === 200) {
                    const data = await response.json();

                    if (data.exists) {
                        this.reffIdAvailable = false;
                        this.errors.reff_id_pelanggan = 'Reference ID sudah digunakan';
                    } else {
                        this.reffIdAvailable = true;
                        delete this.errors.reff_id_pelanggan;
                    }
                } else {
                    // Other status codes - assume not available for safety
                    this.reffIdAvailable = false;
                    this.errors.reff_id_pelanggan = 'Error validating Reference ID';
                }
            } catch (error) {
                console.error('Validation error:', error);
                // On error, assume available for better UX
                this.reffIdAvailable = true;
                delete this.errors.reff_id_pelanggan;
            } finally {
                this.validatingReffId = false;
            }
        },

        async submitForm() {
            if (!this.formValid) {
                return;
            }

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

        async getCurrentLocation() {
            if (!navigator.geolocation) {
                window.showToast('error', 'Geolocation tidak didukung browser Anda');
                return;
            }

            this.gettingLocation = true;

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.form.latitude = position.coords.latitude.toFixed(6);
                    this.form.longitude = position.coords.longitude.toFixed(6);
                    this.form.coordinate_source = 'gps';
                    this.gettingLocation = false;
                    window.showToast('success', 'Lokasi berhasil didapatkan');
                },
                (error) => {
                    this.gettingLocation = false;
                    let message = 'Gagal mendapatkan lokasi';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            message = 'Akses lokasi ditolak. Silakan izinkan akses lokasi.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            message = 'Informasi lokasi tidak tersedia';
                            break;
                        case error.TIMEOUT:
                            message = 'Request lokasi timeout';
                            break;
                    }
                    window.showToast('error', message);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                }
            );
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
                    jenis_pelanggan: 'pengembangan',
                    keterangan: '',
                    latitude: '',
                    longitude: '',
                    coordinate_source: 'manual'
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
