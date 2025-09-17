@extends('layouts.app')

@section('title', 'Edit Pelanggan - AERGAS')
@section('page-title', 'Edit Pelanggan')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="customerEditData()">

    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-gradient-to-br from-aergas-navy to-aergas-orange rounded-xl flex items-center justify-center text-white text-lg font-bold">
                {{ substr($customer->nama_pelanggan, 0, 1) }}
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Pelanggan</h1>
                <p class="text-gray-600">{{ $customer->reff_id_pelanggan }} - {{ $customer->nama_pelanggan }}</p>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('customers.show', $customer->reff_id_pelanggan) }}"
               class="flex items-center space-x-2 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                <i class="fas fa-eye"></i>
                <span>View Detail</span>
            </a>

            <a href="{{ route('customers.index') }}"
               class="flex items-center space-x-2 px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition-colors">
                <i class="fas fa-arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <div class="bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-4">
        <div class="flex items-center space-x-3">
            <i class="fas fa-info-circle text-blue-600"></i>
            <div>
                <div class="font-medium text-blue-900">Status Pelanggan Saat Ini</div>
                <div class="text-sm text-blue-700">
                    Status: <span class="font-medium">{{ ucfirst($customer->status) }}</span> |
                    Progress: <span class="font-medium">{{ ucfirst($customer->progress_status) }}</span>
                </div>
            </div>
        </div>
    </div>

    <form @submit.prevent="submitForm()" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center mb-6">
                <div class="w-10 h-10 bg-gradient-to-br from-aergas-navy to-aergas-orange rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-user text-white"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Informasi Dasar</h2>
                    <p class="text-sm text-gray-600">Update data identitas pelanggan</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reference ID</label>
                    <input type="text"
                           value="{{ $customer->reff_id_pelanggan }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed"
                           readonly>
                    <p class="mt-1 text-xs text-gray-500">Reference ID tidak dapat diubah</p>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Pelanggan <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           x-model="form.nama_pelanggan"
                           :class="errors.nama_pelanggan ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-aergas-orange focus:border-transparent'"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 transition-colors"
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
                           required>
                    <div x-show="errors.no_telepon" class="mt-1 text-sm text-red-600" x-text="errors.no_telepon"></div>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Pelanggan</label>
                    <select x-model="form.jenis_pelanggan"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                        <option value="pengembangan">Pengembangan</option>
                        <option value="penetrasi">Penetrasi</option>
                        <option value="on_the_spot">On The Spot</option>
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

        @if(in_array(auth()->user()->role, ['admin', 'tracer', 'super_admin']))
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center mb-6">
                <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-cog text-white"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Manajemen Status</h2>
                    <p class="text-sm text-gray-600">Update status dan progress pelanggan</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status Pelanggan</label>
                    <select x-model="form.status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                        <option value="pending">Pending</option>
                        <option value="lanjut">Lanjut</option>
                        <option value="in_progress">In Progress</option>
                        <option value="batal">Batal</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Status workflow pelanggan</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Progress Status</label>
                    <select x-model="form.progress_status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                        <option value="validasi">Validasi</option>
                        <option value="sk">SK</option>
                        <option value="sr">SR</option>
                        <option value="gas_in">Gas In</option>
                        <option value="done">Done</option>
                        <option value="batal">Batal</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Tahapan progress saat ini</p>
                </div>
            </div>

            <div x-show="form.status !== '{{ $customer->status }}' || form.progress_status !== '{{ $customer->progress_status }}'"
                 class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-start space-x-2">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5"></i>
                    <div class="text-sm text-yellow-700">
                        <p class="font-medium">Perhatian: Perubahan Status</p>
                        <p>Perubahan status atau progress dapat mempengaruhi workflow pelanggan. Pastikan perubahan sudah sesuai dengan kondisi aktual.</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 bg-gray-500 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-history text-white"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Riwayat Perubahan</h2>
                    <p class="text-sm text-gray-600">Catatan perubahan terakhir</p>
                </div>
            </div>

            <div class="text-sm text-gray-600 space-y-1">
                <p><strong>Dibuat:</strong> {{ $customer->created_at->format('d/m/Y H:i') }}</p>
                <p><strong>Diperbarui:</strong> {{ $customer->updated_at->format('d/m/Y H:i') }}</p>
                <p><strong>Registrasi:</strong> {{ $customer->tanggal_registrasi ? $customer->tanggal_registrasi->format('d/m/Y H:i') : '-' }}</p>
            </div>
        </div>

        <div class="flex items-center justify-between space-x-4">
            <div class="flex items-center space-x-3">
                <button type="button"
                        @click="resetForm()"
                        class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Reset Changes
                </button>

                <button type="button"
                        @click="confirmCancel()"
                        class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    Cancel
                </button>
            </div>

            <div class="flex items-center space-x-3">
                <button type="submit"
                        :disabled="submitting || !hasChanges"
                        :class="hasChanges ? 'bg-gradient-to-r from-aergas-navy to-aergas-orange hover:shadow-lg' : 'bg-gray-400 cursor-not-allowed'"
                        class="px-6 py-2 text-white rounded-lg transition-all duration-300 disabled:opacity-50">
                    <span x-show="!submitting">Update Pelanggan</span>
                    <span x-show="submitting">
                        <i class="fas fa-spinner animate-spin mr-2"></i>Updating...
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function customerEditData() {
    return {
        originalForm: {
            reff_id_pelanggan: @json($customer->reff_id_pelanggan),
            nama_pelanggan: @json($customer->nama_pelanggan),
            alamat: @json($customer->alamat),
            no_telepon: @json($customer->no_telepon),
            kelurahan: @json($customer->kelurahan ?? ''),
            padukuhan: @json($customer->padukuhan ?? ''),
            jenis_pelanggan: @json($customer->jenis_pelanggan ?? 'pengembangan'),
            keterangan: @json($customer->keterangan ?? ''),
            status: @json($customer->status),
            progress_status: @json($customer->progress_status)
        },

        form: {
            reff_id_pelanggan: @json($customer->reff_id_pelanggan),
            nama_pelanggan: @json($customer->nama_pelanggan),
            alamat: @json($customer->alamat),
            no_telepon: @json($customer->no_telepon),
            kelurahan: @json($customer->kelurahan ?? ''),
            padukuhan: @json($customer->padukuhan ?? ''),
            jenis_pelanggan: @json($customer->jenis_pelanggan ?? 'pengembangan'),
            keterangan: @json($customer->keterangan ?? ''),
            status: @json($customer->status),
            progress_status: @json($customer->progress_status)
        },

        errors: {},
        submitting: false,

        get hasChanges() {
            return JSON.stringify(this.form) !== JSON.stringify(this.originalForm);
        },

        resetForm() {
            this.form = { ...this.originalForm };
            this.errors = {};
        },

        confirmCancel() {
            if (this.hasChanges) {
                if (confirm('Ada perubahan yang belum disimpan. Yakin ingin membatalkan?')) {
                    window.location.href = '{{ route("customers.index") }}';
                }
            } else {
                window.location.href = '{{ route("customers.index") }}';
            }
        },

        async submitForm() {
            if (!this.hasChanges) {
                this.showNotification('Tidak ada perubahan yang perlu disimpan', 'info');
                return;
            }

            this.submitting = true;
            this.errors = {};

            try {
                const formData = new FormData();
                
                // Add CSRF token and method override
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('_method', 'PUT');
                
                // Add form data
                Object.keys(this.form).forEach(key => {
                    if (this.form[key] !== null && this.form[key] !== '') {
                        formData.append(key, this.form[key]);
                    }
                });
                
                // Debug: log form data
                console.log('Form data being sent:');
                for (let [key, value] of formData.entries()) {
                    console.log(key, ':', value);
                }

                const response = await fetch('{{ route("customers.update", $customer->reff_id_pelanggan) }}', {
                    method: 'POST', // Use POST with _method override
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache'
                    },
                    body: formData
                });

                let result;
                try {
                    const responseText = await response.text();
                    console.log('Raw response:', responseText);
                    
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Failed to parse JSON:', parseError);
                    throw new Error('Server returned invalid response. Please check logs.');
                }

                if (response.ok && result.success) {
                    this.showNotification('Data pelanggan berhasil diperbarui!', 'success');
                    this.originalForm = { ...this.form };

                    setTimeout(() => {
                        // Use the updated reff_id from server response
                        const reffId = result.data?.reff_id_pelanggan || this.form.reff_id_pelanggan;
                        window.location.href = `/customers/${reffId}`;
                    }, 1500);
                } else {
                    console.error('Update failed:', result);
                    if (result.errors) {
                        this.errors = result.errors;
                        this.showNotification('Ada kesalahan validasi dalam form', 'error');
                    } else {
                        this.showNotification(result.message || 'Terjadi kesalahan saat menyimpan data', 'error');
                    }
                }
            } catch (error) {
                console.error('Submit Error:', error);
                this.showNotification(`Terjadi kesalahan jaringan: ${error.message}`, 'error');
            } finally {
                this.submitting = false;
            }
        },

        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg text-white shadow-lg transform transition-transform duration-300 translate-x-full`;

            switch (type) {
                case 'success':
                    notification.className += ' bg-green-500';
                    break;
                case 'error':
                    notification.className += ' bg-red-500';
                    break;
                case 'warning':
                    notification.className += ' bg-yellow-500';
                    break;
                default:
                    notification.className += ' bg-blue-500';
            }

            notification.innerHTML = `
                <div class="flex items-center space-x-2">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(notification);

            requestAnimationFrame(() => {
                notification.classList.remove('translate-x-full');
            });

            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
    };
}
</script>
@endpush

@endsection
