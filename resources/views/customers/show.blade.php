{{-- resources/views/customers/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail Pelanggan - AERGAS')

@section('content')
<div class="space-y-6" x-data="{ copying:false }">
    <!-- Header -->
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">{{ $customer->nama_pelanggan }}</h1>
            <div class="mt-2 flex items-center gap-3 text-sm text-gray-600">
                <div class="flex items-center gap-2">
                    <span class="font-mono text-gray-800">{{ $customer->reff_id_pelanggan }}</span>
                    <button class="text-blue-600 hover:underline"
                            @click="copying=true; navigator.clipboard.writeText('{{ $customer->reff_id_pelanggan }}').then(()=>{window.showToast?.('Disalin','success'); copying=false;})">
                        <i class="far fa-copy"></i>
                    </button>
                </div>
                <span>•</span>
                <span class="capitalize">{{ str_replace('_',' ', $customer->jenis_pelanggan ?? 'residensial') }}</span>
                <span>•</span>
                <span>Terakhir diperbarui {{ $customer->updated_at?->diffForHumans() }}</span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('customers.edit', $customer->reff_id_pelanggan) }}"
               class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                <i class="fas fa-edit mr-2"></i> Edit
            </a>
            <a href="{{ route('customers.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Top summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Status card -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="text-sm text-gray-500 mb-1">Status</div>
            @php
                $statusColor = [
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'validated' => 'bg-blue-100 text-blue-800',
                    'in_progress' => 'bg-purple-100 text-purple-800',
                    'lanjut' => 'bg-green-100 text-green-800',
                    'batal' => 'bg-red-100 text-red-800',
                ][$customer->status] ?? 'bg-gray-100 text-gray-800';
            @endphp
            <span class="inline-flex px-2 py-1 rounded-full text-sm font-medium {{ $statusColor }}">
                {{ ucfirst(str_replace('_',' ', $customer->status ?? '-')) }}
            </span>
        </div>

        <!-- Progress -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="text-sm text-gray-500 mb-2">Progress</div>
            <div class="flex items-center">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ (int)($customer->progress_percentage ?? 0) }}%"></div>
                </div>
                <span class="ml-3 text-sm text-gray-700">{{ (int)($customer->progress_percentage ?? 0) }}%</span>
            </div>
            <div class="mt-2 text-xs text-gray-500 capitalize">
                Tahap: {{ str_replace('_',' ', $customer->progress_status ?? '-') }}
            </div>
        </div>

        <!-- Next step -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="text-sm text-gray-500 mb-2">Langkah Berikutnya</div>
            @if(!empty($customer->next_available_module) && $customer->next_available_module !== 'done')
                <a href="/{{ $customer->next_available_module }}/create?reff_id={{ $customer->reff_id_pelanggan }}"
                   class="inline-flex items-center px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-arrow-right mr-2"></i>
                    {{ strtoupper(str_replace('_',' ', $customer->next_available_module)) }}
                </a>
                <p class="text-xs text-gray-500 mt-2">Lanjutkan proses modul berikutnya.</p>
            @else
                <div class="text-gray-700">Tidak ada langkah lanjutan.</div>
            @endif
        </div>
    </div>

    <!-- Content grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Detail -->
        <div class="lg:col-span-2 bg-white rounded-xl card-shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Pelanggan</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <dt class="text-sm text-gray-500">Nama</dt>
                    <dd class="text-gray-900">{{ $customer->nama_pelanggan }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">No. Telepon</dt>
                    <dd class="text-gray-900">{{ $customer->no_telepon ?: '-' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm text-gray-500">Alamat</dt>
                    <dd class="text-gray-900">{{ $customer->alamat }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Kelurahan</dt>
                    <dd class="text-gray-900">{{ $customer->kelurahan ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Padukuhan</dt>
                    <dd class="text-gray-900">{{ $customer->padukuhan ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Jenis Pelanggan</dt>
                    <dd class="text-gray-900 capitalize">{{ $customer->jenis_pelanggan ?: '-' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm text-gray-500">Keterangan</dt>
                    <dd class="text-gray-900">{{ $customer->keterangan ?: '-' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Right: Modul status -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Status Per Modul</h2>
            @php
                $modules = $customer->module_completion_status ?? [];
                $label = [
                    'sk' => 'SK', 'sr' => 'SR', 'mgrt' => 'MGRT', 'gas_in' => 'Gas In',
                    'jalur_pipa' => 'Jalur Pipa', 'penyambungan' => 'Penyambungan'
                ];
                $chip = fn($s) => match($s) {
                    'completed','done','selesai' => 'bg-green-100 text-green-800',
                    'in_progress','progress' => 'bg-blue-100 text-blue-800',
                    'pending','waiting' => 'bg-yellow-100 text-yellow-800',
                    'rejected','batal' => 'bg-red-100 text-red-800',
                    default => 'bg-gray-100 text-gray-800'
                };
            @endphp
            <div class="space-y-3">
                @forelse ($modules as $key => $m)
                    <div class="flex items-center justify-between">
                        <div class="text-gray-700">{{ $label[$key] ?? strtoupper($key) }}</div>
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $chip($m['status'] ?? 'not_started') }}">
                            {{ ucfirst(str_replace('_',' ', $m['status'] ?? 'not_started')) }}
                        </span>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">Belum ada data modul.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
