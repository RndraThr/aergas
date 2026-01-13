@extends('layouts.app')

@section('title', 'Detail Test Package: ' . $testPackage->test_package_code)

@section('content')
    <div class="container mx-auto px-6 py-8">

        <!-- Header -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between">
            <div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('jalur.test-package.index') }}" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <h1 class="text-3xl font-bold text-gray-800">{{ $testPackage->test_package_code }}</h1>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold 
                            {{ match ($testPackage->status) {
        'draft' => 'bg-gray-100 text-gray-800',
        'flushing' => 'bg-yellow-100 text-yellow-800',
        'pneumatic' => 'bg-blue-100 text-blue-800',
        'purging' => 'bg-purple-100 text-purple-800',
        'gas_in' => 'bg-orange-100 text-orange-800',
        'completed' => 'bg-green-100 text-green-800',
        default => 'bg-gray-100'
    } }}">
                        {{ ucfirst(str_replace('_', ' ', $testPackage->status)) }}
                    </span>
                </div>
                <p class="mt-2 text-gray-600 ml-10">Cluster: {{ $testPackage->cluster->nama_cluster }} | Total
                    {{ $testPackage->items->count() }} Pipes
                </p>
            </div>
            <div class="mt-4 md:mt-0">
                <!-- Add line items view logic if needed later -->
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Left Column: Line Items List -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="font-semibold text-gray-900">Included Lines</h3>
                    </div>
                    <div class="p-0 overflow-y-auto max-h-[600px]">
                        <ul class="divide-y divide-gray-200">
                            @foreach($testPackage->items as $item)
                                <li class="px-6 py-4 hover:bg-gray-50 border-b last:border-b-0">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span
                                                class="text-sm font-bold text-gray-800 block">{{ $item->lineNumber->line_number }}</span>
                                            <span class="text-xs text-gray-500 block mt-1">
                                                Diameter: <span
                                                    class="font-semibold text-gray-700">{{ $item->lineNumber->diameter ?? '-' }}</span>
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            @php
                                                $totalLowering = $item->lineNumber->loweringData->sum('panjang_pipa');
                                                $estimasi = $item->lineNumber->estimasi_panjang;
                                                // Use actual lowering sum if available and significant, else estimation
                                                $displayLength = $totalLowering > 0 ? $totalLowering : $estimasi;
                                            @endphp
                                            <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded-full">
                                                {{ number_format($displayLength, 2) }} m
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-400 flex space-x-3">
                                        <span class="flex items-center" title="Data Lowering">
                                            <i
                                                class="fas fa-arrow-down mr-1 {{ $item->lineNumber->loweringData->count() > 0 ? 'text-green-500' : 'text-gray-300' }}"></i>
                                            Lowering: {{ $item->lineNumber->loweringData->count() }}
                                        </span>
                                        {{-- Joint data count requires extra query or loading, keeping it simple for now or
                                        adding if critical --}}
                                        {{-- <span class="flex items-center">
                                            <i class="fas fa-link mr-1"></i> Joint
                                        </span> --}}
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right Column: Testing Steps -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Step 1: Flushing -->
                @include('jalur.test-package.partials.step-card', [
                    'step' => 'flushing',
                    'title' => '1. Flushing',
                    'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
                    'description' => 'Membersihkan jalur pipa dari kotoran/debris.',
                    'color' => 'yellow'
                ])

                <!-- Step 2: Pneumatic -->
                @include('jalur.test-package.partials.step-card', [
                    'step' => 'pneumatic',
                    'title' => '2. Pneumatic Test',
                    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    'description' => 'Pengujian tekanan udara untuk memastikan tidak ada kebocoran.',
                    'color' => 'blue'
                ])

                    <!-- Step 3: Purging -->
                    @include('jalur.test-package.partials.step-card', [
                        'step' => 'purging',
                        'title' => '3. N2 Purging',
                        'icon' => 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4',
                        'description' => 'Mengganti udara di dalam pipa dengan Nitrogen (Inerting).',
                        'color' => 'purple'
                    ])

                    <!-- Step 4: Gas In -->
                    @include('jalur.test-package.partials.step-card', [
                        'step' => 'gas_in',
                        'title' => '4. Gas In',
                        'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                        'description' => 'Pengaliran Gas Alam ke dalam sistem pipa.',
                        'color' => 'green'
                    ])

                </div>
            </div>
        </div>
@endsection
