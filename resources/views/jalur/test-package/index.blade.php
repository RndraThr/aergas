@extends('layouts.app')

@section('title', 'Test Package Management')

@section('content')
    <div class="container mx-auto px-6 py-8">

        <!-- Title & Action -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Test Packages</h1>
                <p class="text-gray-600">Overview progress pengujian jalur pipa.</p>
            </div>
            <a href="{{ route('jalur.test-package.create') }}"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition duration-300 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6">
                    </path>
                </svg>
                Buat Paket Test
            </a>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total -->
            <div class="bg-white rounded-lg shadow p-5 border-l-4 border-gray-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-gray-100 p-3 rounded-md">
                        <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-gray-500 text-sm font-medium uppercase">Total Packages</h2>
                        <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
                    </div>
                </div>
            </div>

            <!-- Flushing -->
            <div class="bg-white rounded-lg shadow p-5 border-l-4 border-yellow-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-100 p-3 rounded-md">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-gray-500 text-sm font-medium uppercase">On Flushing</h2>
                        <p class="text-2xl font-bold text-gray-800">{{ $stats['flushing'] }}</p>
                    </div>
                </div>
            </div>

            <!-- Pneumatic -->
            <div class="bg-white rounded-lg shadow p-5 border-l-4 border-blue-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 p-3 rounded-md">
                        <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-gray-500 text-sm font-medium uppercase">On Pneumatic</h2>
                        <p class="text-2xl font-bold text-gray-800">{{ $stats['pneumatic'] }}</p>
                    </div>
                </div>
            </div>

            <!-- Completed/Gas In -->
            <div class="bg-white rounded-lg shadow p-5 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 p-3 rounded-md">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-gray-500 text-sm font-medium uppercase">Gas In / Done</h2>
                        <p class="text-2xl font-bold text-gray-800">{{ $stats['gas_in'] }}</p>
                    </div>
                </div>
            </div>
        </div>


        <!-- Status Messages -->
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 shadow-md rounded-r" role="alert">
                <p class="font-bold">Berhasil!</p>
                <p>{{ session('success') }}</p>
            </div>
        @endif

        <!-- Data Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode
                                TP / Cluster</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total
                                Length</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Current Stage</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Flushing</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pneumatic</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Purging</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gas
                                In</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($packages as $package)
                            @php
                                // Calculate Total Length
                                $totalLength = $package->items->sum(function ($item) {
                                    // Ideally use lowering data if exists, else estimate
                                    return $item->lineNumber->estimasi_panjang ?? 0;
                                });
                            @endphp
                            <tr class="hover:bg-gray-50 transition duration-150 group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-bold text-gray-900">{{ $package->test_package_code }}</div>
                                            <div class="text-xs text-gray-500">{{ $package->cluster->nama_cluster }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-700">{{ number_format($totalLength, 2) }} m
                                    </div>
                                    <div class="text-xs text-gray-400">{{ $package->items->count() }} Pipes</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusConf = match ($package->status) {
                                            'draft' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => 'Draft'],
                                            'flushing' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'Flushing Process'],
                                            'pneumatic' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Pneumatic Test'],
                                            'purging' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'label' => 'N2 Purging'],
                                            'gas_in' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'label' => 'Gas In Process'],
                                            'completed' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Completed'],
                                            default => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => $package->status],
                                        };
                                    @endphp
                                    <span
                                        class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusConf['bg'] }} {{ $statusConf['text'] }}">
                                        {{ $statusConf['label'] }}
                                    </span>
                                </td>

                                {{-- Timeline Dates --}}
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm {{ $package->flushing_date ? 'text-gray-700 font-medium' : 'text-gray-400 italic' }}">
                                    {{ $package->flushing_date ? $package->flushing_date->format('d/m/Y') : '-' }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm {{ $package->pneumatic_date ? 'text-gray-700 font-medium' : 'text-gray-400 italic' }}">
                                    {{ $package->pneumatic_date ? $package->pneumatic_date->format('d/m/Y') : '-' }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm {{ $package->purging_date ? 'text-gray-700 font-medium' : 'text-gray-400 italic' }}">
                                    {{ $package->purging_date ? $package->purging_date->format('d/m/Y') : '-' }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm {{ $package->gas_in_date ? 'text-gray-700 font-medium' : 'text-gray-400 italic' }}">
                                    {{ $package->gas_in_date ? $package->gas_in_date->format('d/m/Y') : '-' }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('jalur.test-package.show', $package) }}"
                                        class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded transition">
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                            </path>
                                        </svg>
                                        <p class="text-lg font-medium text-gray-900">Belum ada Test Package</p>
                                        <p class="text-sm text-gray-500">Mulai dengan membuat paket test baru.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $packages->links() }}
            </div>
        </div>
    </div>
@endsection