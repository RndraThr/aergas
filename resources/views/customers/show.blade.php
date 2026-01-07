@extends('layouts.app')

@section('title', 'Detail Calon Pelanggan - AERGAS')
@section('page-title', 'Detail Calon Pelanggan')

@section('content')
<div class="space-y-6" x-data="customerDetailData()">

    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start space-y-4 lg:space-y-0">
        <div class="flex items-start space-x-4">
            <div class="w-16 h-16 bg-gradient-to-br from-aergas-navy to-aergas-orange rounded-xl flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                {{ substr($customer->nama_pelanggan, 0, 1) }}
            </div>

            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $customer->nama_pelanggan }}</h1>
                <p class="text-gray-600">
                    <span class="font-medium">{{ $customer->display_reff_id }}</span>
                    @if($customer->display_reff_id !== $customer->reff_id_pelanggan)
                        <span class="text-xs text-gray-500 ml-2">(Original: {{ $customer->reff_id_pelanggan }})</span>
                    @endif
                </p>

                <div class="flex items-center space-x-4 mt-2">
                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                        @if($customer->status === 'lanjut') bg-green-100 text-green-800
                        @elseif($customer->status === 'in_progress') bg-blue-100 text-blue-800
                        @elseif($customer->status === 'pending') bg-yellow-100 text-yellow-800
                        @elseif($customer->status === 'batal') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800 @endif">
                        {{ ucfirst($customer->status) }}
                    </span>

                    <span class="inline-flex px-3 py-1 text-sm font-medium bg-aergas-orange/10 text-aergas-orange rounded-full">
                        {{ ucfirst($customer->progress_status) }} ({{ $customer->progress_percentage ?? 0 }}%)
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <button onclick="openBaPreview()"
                    class="flex items-center space-x-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                <i class="fas fa-eye"></i>
                <span>Preview BA</span>
            </button>

            @if(auth()->user()->hasAnyRole(['admin', 'tracer', 'super_admin']))
                @if($customer->status === 'pending')
                    <button @click="validateCustomer()"
                            class="flex items-center space-x-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-check"></i>
                        <span>Validate</span>
                    </button>

                    <button @click="rejectCustomer()"
                            class="flex items-center space-x-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-times"></i>
                        <span>Reject</span>
                    </button>
                @endif

                <a href="{{ route('customers.edit', $customer->reff_id_pelanggan) }}"
                   class="flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-edit"></i>
                    <span>Edit</span>
                </a>
            @endif

            <a href="{{ route('customers.index') }}"
               class="flex items-center space-x-2 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                <i class="fas fa-arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    @if($customer->status === 'batal')
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Calon Pelanggan Dibatalkan</h3>
                <div class="mt-2 text-sm text-red-700">
                    <p>Calon pelanggan ini telah dibatalkan dan tidak dapat melanjutkan ke proses berikutnya (SK, SR, Gas In).</p>
                    @if($customer->keterangan)
                        <p class="mt-1"><strong>Alasan:</strong> {{ $customer->keterangan }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Progress Overview</h3>

        <div class="mb-6">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>Overall Progress</span>
                <span>{{ $customer->progress_percentage ?? 0 }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-gradient-to-r from-aergas-navy to-aergas-orange h-3 rounded-full transition-all duration-500"
                     style="width: {{ $customer->progress_percentage ?? 0 }}%"></div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            @php
                $modules = [
                    'validasi' => ['icon' => 'fa-clipboard-check', 'name' => 'Validasi'],
                    'sk' => ['icon' => 'fa-fire', 'name' => 'SK'],
                    'sr' => ['icon' => 'fa-route', 'name' => 'SR'],
                    'gas_in' => ['icon' => 'fa-gas-pump', 'name' => 'Gas In'],
                    'done' => ['icon' => 'fa-check-circle', 'name' => 'Selesai']
                ];

                $currentProgress = $customer->progress_status;
                $progressOrder = array_keys($modules);
                $currentIndex = array_search($currentProgress, $progressOrder);
            @endphp

            @foreach($modules as $key => $module)
                @php
                    $moduleIndex = array_search($key, $progressOrder);
                    $isCompleted = $moduleIndex < $currentIndex || ($moduleIndex == $currentIndex && $customer->progress_status === 'done');
                    $isCurrent = $moduleIndex == $currentIndex;
                    $isPending = $moduleIndex > $currentIndex;
                @endphp

                <div class="text-center p-4 rounded-lg border-2 transition-all
                    @if($isCompleted) border-green-200 bg-green-50 text-green-700
                    @elseif($isCurrent) border-aergas-orange bg-aergas-orange/10 text-aergas-orange
                    @else border-gray-200 bg-gray-50 text-gray-400 @endif">

                    <div class="w-10 h-10 mx-auto rounded-full flex items-center justify-center mb-2
                        @if($isCompleted) bg-green-500 text-white
                        @elseif($isCurrent) bg-aergas-orange text-white
                        @else bg-gray-300 text-gray-500 @endif">

                        @if($isCompleted)
                            <i class="fas fa-check"></i>
                        @else
                            <i class="fas {{ $module['icon'] }}"></i>
                        @endif
                    </div>

                    <div class="text-xs font-medium">{{ $module['name'] }}</div>

                    @if($isCurrent)
                        <div class="text-xs mt-1">Current</div>
                    @elseif($isCompleted)
                        <div class="text-xs mt-1">Done</div>
                    @else
                        <div class="text-xs mt-1">Pending</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Calon Pelanggan</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Reference ID</label>
                        <div class="mt-1 text-sm text-gray-900">
                            <span class="font-medium">{{ $customer->display_reff_id }}</span>
                            @if($customer->display_reff_id !== $customer->reff_id_pelanggan)
                                <span class="text-xs text-gray-500 ml-2">({{ $customer->reff_id_pelanggan }})</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Nama Calon Pelanggan</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->nama_pelanggan }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500">No. Telepon</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->no_telepon }}</div>
                    </div>

                    @if($customer->no_ktp)
                    <div>
                        <label class="block text-sm font-medium text-gray-500">No. KTP</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->no_ktp }}</div>
                    </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Jenis Calon Pelanggan</label>
                        <div class="mt-1 text-sm text-gray-900">
                            @php
                                $jenisMap = [
                                    'pengembangan' => 'Pengembangan',
                                    'penetrasi' => 'Penetrasi',
                                    'on_the_spot_penetrasi' => 'On The Spot Penetrasi',
                                    'on_the_spot_pengembangan' => 'On The Spot Pengembangan'
                                ];
                            @endphp
                            {{ $jenisMap[$customer->jenis_pelanggan ?? 'pengembangan'] ?? 'Pengembangan' }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Kelurahan</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->kelurahan ?: 'Belum diisi' }}</div>
                    </div>

                    @if($customer->kota_kabupaten)
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Kota/Kabupaten</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->kota_kabupaten }}</div>
                    </div>
                    @endif

                    @if($customer->kecamatan)
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Kecamatan</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->kecamatan }}</div>
                    </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Padukuhan/Dusun</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->padukuhan ?: 'Belum diisi' }}</div>
                    </div>

                    @if($customer->rt)
                    <div>
                        <label class="block text-sm font-medium text-gray-500">RT</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->rt }}</div>
                    </div>
                    @endif

                    @if($customer->rw)
                    <div>
                        <label class="block text-sm font-medium text-gray-500">RW</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->rw }}</div>
                    </div>
                    @endif

                    @if($customer->email)
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Email</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->email }}</div>
                    </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Tanggal Registrasi</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->tanggal_registrasi ? $customer->tanggal_registrasi->format('d/m/Y H:i') : '-' }}</div>
                    </div>

                    @if($customer->latitude && $customer->longitude)
                    <div class="md:col-span-2 lg:col-span-3">
                        <label class="block text-sm font-medium text-gray-500">Koordinat Lokasi</label>
                        <div class="mt-1 text-sm text-gray-900 flex items-center space-x-2">
                            <span class="font-mono">{{ $customer->latitude }}, {{ $customer->longitude }}</span>
                            @if($customer->coordinate_source)
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">{{ ucfirst($customer->coordinate_source) }}</span>
                            @endif
                            <a href="https://www.google.com/maps?q={{ $customer->latitude }},{{ $customer->longitude }}"
                               target="_blank"
                               class="text-blue-600 hover:text-blue-800 text-xs flex items-center">
                                <i class="fas fa-external-link-alt mr-1"></i> Buka di Maps
                            </a>
                        </div>
                    </div>
                    @endif

                    <div class="md:col-span-2 lg:col-span-3">
                        <label class="block text-sm font-medium text-gray-500">Alamat</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->alamat }}</div>
                    </div>

                    @if($customer->keterangan)
                        <div class="md:col-span-2 lg:col-span-3">
                            <label class="block text-sm font-medium text-gray-500">Keterangan</label>
                            <div class="mt-1 text-sm text-gray-900">{{ $customer->keterangan }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Status Modul</h3>

                <div class="space-y-4">
                    @if($customer->skData)
                        <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-fire text-white text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">SK (Sambungan Kompor)</div>
                                    <div class="text-sm text-gray-600">Status: {{ ucfirst($customer->skData->status ?? 'draft') }}</div>
                                </div>
                            </div>
                            <a href="{{ route('sk.show', $customer->skData->id) }}"
                               class="text-green-600 hover:text-green-800">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    @else
                        <div class="flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                    <i class="fas fa-fire text-white text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">SK (Sambungan Kompor)</div>
                                    <div class="text-sm text-gray-600">Belum dimulai</div>
                                </div>
                            </div>
                            @if(auth()->user()->hasAnyRole(['sk', 'tracer', 'admin', 'super_admin']))
                                <a href="{{ route('sk.create') }}?reff_id={{ $customer->reff_id_pelanggan }}"
                                   class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-plus"></i>
                                </a>
                            @endif
                        </div>
                    @endif

                    @if($customer->srData)
                        <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-route text-white text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">SR (Sambungan Rumah)</div>
                                    <div class="text-sm text-gray-600">Status: {{ ucfirst($customer->srData->status ?? 'draft') }}</div>
                                </div>
                            </div>
                            <a href="{{ route('sr.show', $customer->srData->id) }}"
                               class="text-green-600 hover:text-green-800">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    @else
                        <div class="flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                    <i class="fas fa-route text-white text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">SR (Sambungan Rumah)</div>
                                    <div class="text-sm text-gray-600">Belum dimulai</div>
                                </div>
                            </div>
                            @if(auth()->user()->hasAnyRole(['sr', 'tracer', 'admin', 'super_admin']))
                                <a href="{{ route('sr.create') }}?reff_id={{ $customer->reff_id_pelanggan }}"
                                   class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-plus"></i>
                                </a>
                            @endif
                        </div>
                    @endif

                    @if($customer->gasInData)
                        <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-gas-pump text-white text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">Gas In</div>
                                    <div class="text-sm text-gray-600">Status: {{ ucfirst($customer->gasInData->status ?? 'draft') }}</div>
                                </div>
                            </div>
                            <a href="{{ route('gas-in.show', $customer->gasInData->id) }}"
                               class="text-green-600 hover:text-green-800">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    @else
                        <div class="flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                    <i class="fas fa-gas-pump text-white text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">Gas In</div>
                                    <div class="text-sm text-gray-600">Belum dimulai</div>
                                </div>
                            </div>
                            @if(auth()->user()->hasAnyRole(['gas_in', 'tracer', 'admin', 'super_admin']))
                                <a href="{{ route('gas-in.create') }}?reff_id={{ $customer->reff_id_pelanggan }}"
                                   class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-plus"></i>
                                </a>
                            @endif
                        </div>
                    @endif

                    @foreach(['jalur_pipa', 'penyambungan'] as $module)
                        <div class="flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-lg opacity-60">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                    <i class="fas fa-clock text-white text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ strtoupper(str_replace('_', ' ', $module)) }}</div>
                                    <div class="text-sm text-gray-600">Coming soon</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($customer->auditLogs && $customer->auditLogs->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Aktivitas Terbaru</h3>

                    <div class="space-y-3">
                        @foreach($customer->auditLogs->take(5) as $log)
                            <x-activity-item
                                :type="$log->action"
                                :title="$log->description"
                                :description="$log->model_type"
                                :time="$log->created_at->diffForHumans()"
                                :user="$log->user->name ?? 'System'"
                            />
                        @endforeach
                    </div>

                    @if($customer->auditLogs->count() > 5)
                        <div class="mt-4 text-center">
                            <button @click="loadMoreActivities()"
                                    class="text-aergas-orange hover:text-aergas-navy text-sm font-medium">
                                Load more activities
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>

                <div class="space-y-3">
                    @if($customer->next_available_module)
                        <a href="{{ $customer->getNextModuleUrl() ?? '#' }}"
                           class="flex items-center space-x-3 p-3 bg-gradient-to-r from-aergas-navy to-aergas-orange text-white rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-play"></i>
                            <span>Start {{ strtoupper($customer->next_available_module ?? 'MODULE') }}</span>
                        </a>
                    @endif

                    @if(auth()->user()->hasAnyRole(['admin', 'tracer', 'super_admin']))
                        <button @click="exportCustomerData()"
                                class="flex items-center space-x-3 w-full p-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="fas fa-download"></i>
                            <span>Export Data</span>
                        </button>

                        <button @click="printCustomerInfo()"
                                class="flex items-center space-x-3 w-full p-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="fas fa-print"></i>
                            <span>Print Info</span>
                        </button>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistik</h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Total Photos</span>
                        <span class="font-medium text-gray-900">{{ $customer->photoApprovals->count() }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Approved Photos</span>
                        <span class="font-medium text-green-600">{{ $customer->photoApprovals->where('photo_status', 'cgp_approved')->count() }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Pending Photos</span>
                        <span class="font-medium text-yellow-600">{{ $customer->photoApprovals->whereIn('photo_status', ['ai_pending', 'tracer_pending', 'cgp_pending'])->count() }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Days Since Registration</span>
                        <span class="font-medium text-gray-900">{{ $customer->tanggal_registrasi ? $customer->tanggal_registrasi->diffInDays(now()) : 0 }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Kontak</h3>

                <div class="space-y-3">
                    <a href="tel:{{ $customer->no_telepon }}"
                       class="flex items-center space-x-3 p-3 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors">
                        <i class="fas fa-phone"></i>
                        <span>{{ $customer->no_telepon }}</span>
                    </a>

                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->no_telepon) }}"
                       target="_blank"
                       class="flex items-center space-x-3 p-3 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors">
                        <i class="fab fa-whatsapp"></i>
                        <span>WhatsApp</span>
                    </a>

                    <a href="https://maps.google.com?q={{ urlencode($customer->alamat) }}"
                       target="_blank"
                       class="flex items-center space-x-3 p-3 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>View on Maps</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- BA Preview Modal --}}
<div id="baPreviewModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 p-4" style="display: none; align-items: center; justify-content: center;">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-6xl h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-indigo-50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-pdf text-white"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Preview Berita Acara Gas In</h3>
                    <p class="text-sm text-gray-600">{{ $customer->display_reff_id }} - {{ $customer->nama_pelanggan }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="downloadBaPdf()"
                        class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-download"></i>
                    Download PDF
                </button>
                <button onclick="closeBaPreview()"
                        class="w-10 h-10 flex items-center justify-center text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <!-- PDF Viewer -->
        <div class="flex-1 overflow-hidden bg-gray-100 relative">
            <!-- Loading Overlay -->
            <div id="baLoadingOverlay" class="absolute inset-0 bg-gray-100 flex flex-col items-center justify-center z-10" style="display: flex;">
                <div class="flex flex-col items-center gap-4">
                    <!-- Spinner -->
                    <div class="relative">
                        <div class="w-16 h-16 border-4 border-purple-200 border-t-purple-600 rounded-full animate-spin"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i class="fas fa-file-pdf text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <!-- Loading Text -->
                    <div class="text-center">
                        <p class="text-lg font-semibold text-gray-700">Memuat Berita Acara...</p>
                        <p class="text-sm text-gray-500 mt-1">Mohon tunggu sebentar</p>
                    </div>
                </div>
            </div>

            <iframe id="baPreviewIframe"
                    class="w-full h-full"
                    frameborder="0">
            </iframe>
        </div>
    </div>
</div>

@push('scripts')
<script>
function customerDetailData() {
    return {
        customer: @json($customer),

        async validateCustomer() {
            const notes = prompt('Catatan validasi (opsional):');
            if (notes === null) return; // User cancelled
            
            if (!confirm('Validate calon pelanggan ini? Calon pelanggan akan bisa melanjutkan ke modul SK.')) return;
            
            try {
                const response = await fetch(`{{ route('customers.validate', $customer->reff_id_pelanggan) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    body: JSON.stringify({
                        notes: notes
                    })
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    window.showToast('success', result.message || 'Calon pelanggan berhasil divalidasi');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(result.message || 'Validation failed');
                }
            } catch (error) {
                console.error('Validation error:', error);
                window.showToast('error', error.message || 'Gagal memvalidasi calon pelanggan');
            }
        },

        async rejectCustomer() {
            const notes = prompt('Alasan penolakan (wajib diisi):');
            if (!notes || notes.trim() === '') {
                window.showToast('error', 'Alasan penolakan harus diisi');
                return;
            }
            
            if (!confirm('Reject calon pelanggan ini? Aksi ini tidak dapat dibatalkan.')) return;
            
            try {
                const response = await fetch(`{{ route('customers.reject', $customer->reff_id_pelanggan) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    body: JSON.stringify({
                        notes: notes
                    })
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    window.showToast('success', result.message || 'Calon pelanggan berhasil ditolak');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(result.message || 'Rejection failed');
                }
            } catch (error) {
                console.error('Rejection error:', error);
                window.showToast('error', error.message || 'Gagal menolak calon pelanggan');
            }
        },

        exportCustomerData() {
            window.showToast('info', 'Export feature coming soon');
        },

        printCustomerInfo() {
            window.print();
        },

        loadMoreActivities() {
            window.showToast('info', 'Load more feature coming soon');
        },

    }
}

// BA Preview Modal Functions (Vanilla JS - same approach as gas-in/show.blade.php)
function openBaPreview() {
    const modal = document.getElementById('baPreviewModal');
    const iframe = document.getElementById('baPreviewIframe');
    const loadingOverlay = document.getElementById('baLoadingOverlay');
    const reffId = '{{ $customer->reff_id_pelanggan }}';
    const previewUrl = `/customers/${reffId}/berita-acara/preview`;

    // Store download URL globally
    window.baDownloadUrl = `/customers/${reffId}/berita-acara`;

    // Show loading overlay
    loadingOverlay.style.display = 'flex';

    iframe.src = previewUrl;
    modal.style.display = 'flex';
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeBaPreview() {
    const modal = document.getElementById('baPreviewModal');
    const iframe = document.getElementById('baPreviewIframe');
    const loadingOverlay = document.getElementById('baLoadingOverlay');

    iframe.src = '';
    loadingOverlay.style.display = 'flex'; // Reset to loading state for next open
    modal.style.display = 'none';
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function downloadBaPdf() {
    if (window.baDownloadUrl) {
        window.open(window.baDownloadUrl, '_blank');
    } else {
        if (window.showToast) {
            window.showToast('error', 'URL download tidak tersedia');
        }
    }
}

// Hide loading overlay when PDF is loaded
document.addEventListener('DOMContentLoaded', function() {
    const iframe = document.getElementById('baPreviewIframe');
    const loadingOverlay = document.getElementById('baLoadingOverlay');

    if (iframe && loadingOverlay) {
        iframe.addEventListener('load', function() {
            if (iframe.src) {
                setTimeout(function() {
                    loadingOverlay.style.display = 'none';
                }, 500); // Small delay to ensure PDF is rendered
            }
        });
    }
});

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('baPreviewModal');
    if (e.target === modal) {
        closeBaPreview();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('baPreviewModal');
        if (modal && modal.style.display === 'flex') {
            closeBaPreview();
        }
    }
});

</script>
@endpush
@endsection
