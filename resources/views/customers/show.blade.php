@extends('layouts.app')

@section('title', 'Detail Pelanggan - AERGAS')
@section('page-title', 'Detail Pelanggan')

@section('breadcrumb')
    <li class="flex items-center">
        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
        <i class="fas fa-chevron-right mx-2 text-gray-400 text-xs"></i>
    </li>
    <li class="flex items-center">
        <a href="{{ route('customers.index') }}" class="text-gray-500 hover:text-gray-700">Data Pelanggan</a>
        <i class="fas fa-chevron-right mx-2 text-gray-400 text-xs"></i>
    </li>
    <li class="text-gray-900 font-medium">{{ $customer->reff_id_pelanggan }}</li>
@endsection

@section('content')
<div class="space-y-6" x-data="customerDetailData()">

    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start space-y-4 lg:space-y-0">
        <div class="flex items-start space-x-4">
            <!-- Avatar -->
            <div class="w-16 h-16 bg-gradient-to-br from-aergas-navy to-aergas-orange rounded-xl flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                {{ substr($customer->nama_pelanggan, 0, 1) }}
            </div>

            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $customer->nama_pelanggan }}</h1>
                <p class="text-gray-600">{{ $customer->reff_id_pelanggan }}</p>

                <div class="flex items-center space-x-4 mt-2">
                    <!-- Status Badge -->
                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                        @if($customer->status === 'validated' || $customer->status === 'lanjut') bg-green-100 text-green-800
                        @elseif($customer->status === 'in_progress') bg-blue-100 text-blue-800
                        @elseif($customer->status === 'pending') bg-yellow-100 text-yellow-800
                        @elseif($customer->status === 'batal') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800 @endif">
                        {{ ucfirst($customer->status) }}
                    </span>

                    <!-- Progress Badge -->
                    <span class="inline-flex px-3 py-1 text-sm font-medium bg-aergas-orange/10 text-aergas-orange rounded-full">
                        {{ ucfirst($customer->progress_status) }} ({{ $customer->progress_percentage }}%)
                    </span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center space-x-3">
            @if(in_array(auth()->user()->role, ['admin', 'tracer']))
                @if($customer->status === 'pending')
                    <button @click="validateCustomer()"
                            class="flex items-center space-x-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-check"></i>
                        <span>Validate</span>
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

    <!-- Progress Overview -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Progress Overview</h3>

        <!-- Progress Bar -->
        <div class="mb-6">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>Overall Progress</span>
                <span>{{ $customer->progress_percentage }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-gradient-to-r from-aergas-navy to-aergas-orange h-3 rounded-full transition-all duration-500"
                     style="width: {{ $customer->progress_percentage }}%"></div>
            </div>
        </div>

        <!-- Module Status Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            @php
                $modules = [
                    'validasi' => ['icon' => 'fa-clipboard-check', 'name' => 'Validasi'],
                    'sk' => ['icon' => 'fa-fire', 'name' => 'SK'],
                    'sr' => ['icon' => 'fa-route', 'name' => 'SR'],
                    'mgrt' => ['icon' => 'fa-tachometer-alt', 'name' => 'MGRT'],
                    'gas_in' => ['icon' => 'fa-gas-pump', 'name' => 'Gas In'],
                    'jalur_pipa' => ['icon' => 'fa-project-diagram', 'name' => 'Jalur Pipa'],
                    'penyambungan' => ['icon' => 'fa-link', 'name' => 'Penyambungan']
                ];

                $currentProgress = $customer->progress_status;
                $progressOrder = array_keys($modules);
                $currentIndex = array_search($currentProgress, $progressOrder);
            @endphp

            @foreach($modules as $key => $module)
                @php
                    $moduleIndex = array_search($key, $progressOrder);
                    $isCompleted = $moduleIndex < $currentIndex || ($moduleIndex == $currentIndex && $customer->status === 'lanjut');
                    $isCurrent = $moduleIndex == $currentIndex && $customer->status !== 'lanjut';
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
                        <div class="text-xs mt-1">In Progress</div>
                    @elseif($isCompleted)
                        <div class="text-xs mt-1">Completed</div>
                    @else
                        <div class="text-xs mt-1">Pending</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Customer Information -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Basic Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Pelanggan</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Reference ID</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->reff_id_pelanggan }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Nama Pelanggan</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->nama_pelanggan }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500">No. Telepon</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->no_telepon }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Jenis Pelanggan</label>
                        <div class="mt-1 text-sm text-gray-900">{{ ucfirst($customer->jenis_pelanggan ?? 'residensial') }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Wilayah Area</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->wilayah_area ?? '-' }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Tanggal Registrasi</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->tanggal_registrasi ? $customer->tanggal_registrasi->format('d/m/Y H:i') : '-' }}</div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-500">Alamat</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $customer->alamat }}</div>
                    </div>

                    @if($customer->keterangan)
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500">Keterangan</label>
                            <div class="mt-1 text-sm text-gray-900">{{ $customer->keterangan }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Module Status Details -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Status Modul</h3>

                <div class="space-y-4">
                    <!-- SK Module -->
                    @if($customer->skData)
                        <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-fire text-white text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">SK (Stove Installation)</div>
                                    <div class="text-sm text-gray-600">Status: {{ ucfirst($customer->skData->module_status) }}</div>
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
                                    <div class="font-medium text-gray-900">SK (Stove Installation)</div>
                                    <div class="text-sm text-gray-600">Belum dimulai</div>
                                </div>
                            </div>
                            @if(in_array(auth()->user()->role, ['sk', 'tracer', 'admin']) && $customer->canProceedToModule('sk'))
                                <a href="{{ route('sk.create') }}?reff_id={{ $customer->reff_id_pelanggan }}"
                                   class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-plus"></i>
                                </a>
                            @endif
                        </div>
                    @endif

                    <!-- SR Module -->
                    @if($customer->srData)
                        <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-route text-white text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">SR (Service Route)</div>
                                    <div class="text-sm text-gray-600">Status: {{ ucfirst($customer->srData->module_status) }}</div>
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
                                    <div class="font-medium text-gray-900">SR (Service Route)</div>
                                    <div class="text-sm text-gray-600">Belum dimulai</div>
                                </div>
                            </div>
                            @if(in_array(auth()->user()->role, ['sr', 'tracer', 'admin']) && $customer->canProceedToModule('sr'))
                                <a href="{{ route('sr.index') }}?reff_id={{ $customer->reff_id_pelanggan }}"
                                   class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-plus"></i>
                                </a>
                            @endif
                        </div>
                    @endif

                    <!-- Other Modules (Coming Soon) -->
                    @foreach(['mgrt', 'gas_in', 'jalur_pipa', 'penyambungan'] as $module)
                        <div class="flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-lg opacity-60">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                    <i class="fas fa-clock text-white text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ strtoupper($module) }}</div>
                                    <div class="text-sm text-gray-600">Coming soon</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Activities -->
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
                                :user="$log->user->full_name ?? 'System'"
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

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>

                <div class="space-y-3">
                    @if($customer->next_available_module)
                        <a href="{{ $customer->getNextModuleUrl() }}"
                           class="flex items-center space-x-3 p-3 bg-gradient-to-r from-aergas-navy to-aergas-orange text-white rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-play"></i>
                            <span>Start {{ strtoupper($customer->next_available_module) }}</span>
                        </a>
                    @endif

                    @if(in_array(auth()->user()->role, ['admin', 'tracer']))
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

            <!-- Statistics -->
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

            <!-- Contact Information -->
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

@push('scripts')
<script>
function customerDetailData() {
    return {
        customer: @json($customer),

        async validateCustomer() {
            if (!confirm('Validate this customer? This will allow them to proceed to SK module.')) return;

            try {
                const response = await fetch(`{{ route('customers.update', $customer->reff_id_pelanggan) }}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    body: JSON.stringify({
                        status: 'validated',
                        progress_status: 'sk'
                    })
                });

                if (response.ok) {
                    window.showToast('success', 'Customer validated successfully');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error('Validation failed');
                }
            } catch (error) {
                window.showToast('error', 'Failed to validate customer');
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
        }
    }
}
</script>
@endpush
@endsection
