@extends('layouts.app')

@section('title', 'Import KMZ/KML - Jalur')

@section('content')
    <div class="container mx-auto px-6 py-8" x-data="kmzImportData()">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Import KMZ/KML</h1>
                    <p class="text-gray-600">Upload file KMZ/KML untuk menambahkan jalur pipa ke dashboard</p>
                </div>
                <a href="{{ route('jalur.dashboard') }}"
                    class="flex items-center space-x-2 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                    <span>Kembali ke Dashboard</span>
                </a>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-8">
            <div class="flex items-center mb-4">
                <div
                    class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-file-upload text-white"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Upload File KMZ/KML</h2>
                    <p class="text-sm text-gray-600">Drag & drop atau klik untuk memilih file</p>
                </div>
            </div>

            <!-- Dropzone -->
            <div @drop.prevent="handleDrop($event)" @dragover.prevent="dragover = true"
                @dragleave.prevent="dragover = false"
                :class="dragover ? 'border-green-500 bg-green-50' : 'border-gray-300 bg-gray-50'"
                class="border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer"
                @click="$refs.fileInput.click()">

                <input type="file" x-ref="fileInput" @change="handleFileSelect($event)" accept=".kmz,.kml" class="hidden">

                <div x-show="!uploading && !selectedFile">
                    <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-4"></i>
                    <p class="text-lg font-medium text-gray-700 mb-2">Drop file KMZ/KML di sini</p>
                    <p class="text-sm text-gray-500">atau klik untuk memilih file</p>
                    <p class="text-xs text-gray-400 mt-2">Maksimal ukuran file: 10MB</p>
                </div>

                <div x-show="selectedFile && !uploading">
                    <i class="fas fa-file-archive text-5xl text-green-500 mb-4"></i>
                    <p class="text-lg font-medium text-gray-700 mb-2" x-text="selectedFile?.name"></p>
                    <p class="text-sm text-gray-500" x-text="formatFileSize(selectedFile?.size)"></p>
                    <button @click.stop="selectedFile = null"
                        class="mt-4 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                        <i class="fas fa-times mr-2"></i>Hapus File
                    </button>
                </div>

                <div x-show="uploading">
                    <i class="fas fa-spinner fa-spin text-5xl text-blue-500 mb-4"></i>
                    <p class="text-lg font-medium text-gray-700 mb-2">Mengupload & parsing file...</p>
                    <p class="text-sm text-gray-500">Mohon tunggu</p>
                </div>
            </div>

            <!-- Upload Button -->
            <div x-show="selectedFile && !uploading" class="mt-4 flex justify-end">
                <button @click="uploadFile()"
                    class="px-6 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition-colors shadow-sm">
                    <i class="fas fa-upload mr-2"></i>Upload & Import
                </button>
            </div>
        </div>

        <!-- Unassigned Jalur List -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <div
                        class="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-route text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Jalur Belum Di-assign</h2>
                        <p class="text-sm text-gray-600">Assign jalur hasil import ke Line Number</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-600">
                        Total: <span class="font-semibold" x-text="unassignedFeatures.length"></span> jalur
                    </div>

                    <!-- Bulk Actions Dropdown -->
                    <div x-data="{ open: false }" class="relative" @click.away="open = false">
                        <button @click="open = !open" x-show="unassignedFeatures.length > 0"
                            class="flex items-center space-x-2 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors shadow-sm">
                            <i class="fas fa-trash-alt"></i>
                            <span>Bulk Delete</span>
                            <i class="fas fa-chevron-down text-xs" :class="{ 'rotate-180': open }"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="open" x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 z-50"
                            style="display: none;">

                            <div class="py-1">
                                <button @click="bulkDelete('unassigned'); open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 flex items-center space-x-2">
                                    <i class="fas fa-trash-alt text-orange-500 w-4"></i>
                                    <span>Hapus Semua Unassigned</span>
                                </button>

                                <button @click="bulkDelete('all'); open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 flex items-center space-x-2">
                                    <i class="fas fa-exclamation-triangle text-red-500 w-4"></i>
                                    <span>Hapus SEMUA (termasuk assigned)</span>
                                </button>

                                <div class="border-t border-gray-100 my-1"></div>

                                <button @click="bulkDelete('assigned'); open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 flex items-center space-x-2">
                                    <i class="fas fa-link text-blue-500 w-4"></i>
                                    <span>Hapus Hanya Yang Assigned</span>
                                </button>
                            </div>

                            <!-- Warning Footer -->
                            <div class="border-t border-gray-200 bg-gray-50 px-4 py-2 text-xs text-gray-500 rounded-b-lg">
                                <i class="fas fa-info-circle mr-1"></i>
                                Aksi ini tidak bisa di-undo
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature List -->
            <div x-show="unassignedFeatures.length > 0" class="space-y-4">
                <template x-for="feature in unassignedFeatures" :key="feature.id">
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 hover:shadow-md transition-all">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <h3 class="font-medium text-gray-900" x-text="feature.name"></h3>
                                    <span class="px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full">
                                        Unassigned
                                    </span>
                                </div>

                                <div class="text-sm text-gray-600 space-y-1">
                                    <div x-show="feature.metadata?.description">
                                        <i class="fas fa-info-circle text-gray-400 mr-2"></i>
                                        <span x-text="feature.metadata?.description"></span>
                                    </div>
                                    <div>
                                        <i class="fas fa-calendar text-gray-400 mr-2"></i>
                                        Imported: <span x-text="formatDate(feature.created_at)"></span>
                                    </div>
                                    <div x-show="feature.metadata?.original_filename">
                                        <i class="fas fa-file text-gray-400 mr-2"></i>
                                        File: <span x-text="feature.metadata?.original_filename"></span>
                                    </div>
                                    <div>
                                        <i class="fas fa-map-marker-alt text-gray-400 mr-2"></i>
                                        <span x-text="getCoordinateCount(feature)"></span> koordinat
                                    </div>
                                </div>

                                <!-- Assignment Form -->
                                <div class="mt-4 flex items-end space-x-3">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Assign ke Line Number
                                        </label>
                                        <select :id="'line-select-' + feature.id"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="">-- Pilih Line Number --</option>
                                            <template x-for="line in lineNumbers" :key="line.id">
                                                <option :value="line.id"
                                                    x-text="line.line_number + ' - ' + line.nama_jalan + ' (Ø' + line.diameter + 'mm)'">
                                                </option>
                                            </template>
                                        </select>
                                    </div>
                                    <button @click="assignFeature(feature.id)"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-link mr-2"></i>Assign
                                    </button>
                                    <button @click="deleteFeature(feature.id)"
                                        class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty State -->
            <div x-show="unassignedFeatures.length === 0" class="text-center py-12">
                <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg font-medium mb-2">Tidak ada jalur yang belum di-assign</p>
                <p class="text-gray-400 text-sm">Upload file KMZ/KML untuk menambahkan jalur baru</p>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function kmzImportData() {
                return {
                    selectedFile: null,
                    uploading: false,
                    dragover: false,
                    unassignedFeatures: @json($unassignedFeatures),
                    lineNumbers: @json($lineNumbers),

                    handleDrop(e) {
                        this.dragover = false;
                        const files = e.dataTransfer.files;
                        if (files.length > 0) {
                            this.handleFile(files[0]);
                        }
                    },

                    handleFileSelect(e) {
                        const files = e.target.files;
                        if (files.length > 0) {
                            this.handleFile(files[0]);
                        }
                    },

                    handleFile(file) {
                        const extension = file.name.split('.').pop().toLowerCase();
                        if (!['kmz', 'kml'].includes(extension)) {
                            window.showToast && window.showToast('error', 'Format file tidak valid. Hanya KMZ dan KML yang diperbolehkan.');
                            return;
                        }

                        if (file.size > 10 * 1024 * 1024) { // 10MB
                            window.showToast && window.showToast('error', 'Ukuran file terlalu besar. Maksimal 10MB.');
                            return;
                        }

                        this.selectedFile = file;
                    },

                    async uploadFile() {
                        if (!this.selectedFile) return;

                        this.uploading = true;

                        const formData = new FormData();
                        formData.append('file', this.selectedFile);

                        try {
                            const response = await fetch('{{ route('jalur.kmz-import.upload') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: formData
                            });

                            const result = await response.json();

                            if (result.success) {
                                window.showToast && window.showToast('success', result.message);
                                this.selectedFile = null;

                                // Reload page to show new features
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            } else {
                                throw new Error(result.message || 'Upload gagal');
                            }
                        } catch (error) {
                            console.error('Upload error:', error);
                            window.showToast && window.showToast('error', 'Gagal upload file: ' + error.message);
                        } finally {
                            this.uploading = false;
                        }
                    },

                    async assignFeature(featureId) {
                        const selectElement = document.getElementById('line-select-' + featureId);
                        const lineNumberId = selectElement.value;

                        if (!lineNumberId) {
                            window.showToast && window.showToast('error', 'Pilih Line Number terlebih dahulu');
                            return;
                        }

                        try {
                            const response = await fetch(`/jalur/kmz-import/${featureId}/assign`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ line_number_id: lineNumberId })
                            });

                            const result = await response.json();

                            if (result.success) {
                                window.showToast && window.showToast('success', result.message);

                                // Remove from unassigned list
                                this.unassignedFeatures = this.unassignedFeatures.filter(f => f.id !== featureId);
                            } else {
                                throw new Error(result.message || 'Assignment gagal');
                            }
                        } catch (error) {
                            console.error('Assignment error:', error);
                            window.showToast && window.showToast('error', 'Gagal assign jalur: ' + error.message);
                        }
                    },

                    async deleteFeature(featureId) {
                        if (!confirm('Yakin ingin menghapus jalur ini?')) return;

                        try {
                            const response = await fetch(`/jalur/kmz-import/${featureId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            });

                            const result = await response.json();

                            if (result.success) {
                                window.showToast && window.showToast('success', result.message);

                                // Remove from list
                                this.unassignedFeatures = this.unassignedFeatures.filter(f => f.id !== featureId);
                            } else {
                                throw new Error(result.message || 'Delete gagal');
                            }
                        } catch (error) {
                            console.error('Delete error:', error);
                            window.showToast && window.showToast('error', 'Gagal menghapus jalur: ' + error.message);
                        }
                    },

                    async bulkDelete(mode) {
                        const confirmMessages = {
                            'unassigned': 'Hapus SEMUA jalur yang belum di-assign?\n\nTotal: ' + this.unassignedFeatures.length + ' jalur',
                            'all': '⚠️ PERINGATAN ⚠️\n\nHapus SEMUA jalur (termasuk yang sudah di-assign)?\n\nAksi ini akan menghapus SEMUA data import jalur dari database!\n\nAksi ini TIDAK BISA DI-UNDO!',
                            'assigned': 'Hapus semua jalur yang SUDAH di-assign?\n\nAksi ini akan menghapus jalur yang sudah terhubung dengan Line Number.'
                        };

                        if (!confirm(confirmMessages[mode])) return;

                        // Double confirmation for 'all' mode
                        if (mode === 'all') {
                            if (!confirm('Anda yakin 100%? Data yang dihapus tidak bisa dikembalikan!')) {
                                return;
                            }
                        }

                        try {
                            window.showLoading && window.showLoading('Menghapus jalur...');

                            const response = await fetch('/jalur/kmz-import/bulk-delete', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ mode: mode })
                            });

                            const result = await response.json();

                            window.closeLoading && window.closeLoading();

                            if (result.success) {
                                window.showToast && window.showToast('success', result.message);

                                // Reload page after 1.5s
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            } else {
                                throw new Error(result.message || 'Bulk delete gagal');
                            }
                        } catch (error) {
                            window.closeLoading && window.closeLoading();
                            console.error('Bulk delete error:', error);
                            window.showToast && window.showToast('error', 'Gagal menghapus: ' + error.message);
                        }
                    },

                    formatFileSize(bytes) {
                        if (!bytes) return '0 B';
                        const sizes = ['B', 'KB', 'MB', 'GB'];
                        const i = Math.floor(Math.log(bytes) / Math.log(1024));
                        return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
                    },

                    formatDate(dateString) {
                        if (!dateString) return '-';
                        const date = new Date(dateString);
                        return date.toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    },

                    getCoordinateCount(feature) {
                        return feature.geometry?.coordinates?.length || 0;
                    }
                };
            }
        </script>
    @endpush
@endsection