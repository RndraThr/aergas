@extends('layouts.app')

@section('title', 'Simple Report Test')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Simple Report Test</h1>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Data Summary</h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded">
                <h3 class="font-medium text-blue-900">Total Customers</h3>
                <p class="text-2xl font-bold text-blue-600">{{ $totalCustomers ?? 0 }}</p>
            </div>

            <div class="bg-green-50 p-4 rounded">
                <h3 class="font-medium text-green-900">SK Data</h3>
                <p class="text-2xl font-bold text-green-600">{{ $moduleStats['sk']->count() ?? 0 }}</p>
            </div>

            <div class="bg-yellow-50 p-4 rounded">
                <h3 class="font-medium text-yellow-900">SR Data</h3>
                <p class="text-2xl font-bold text-yellow-600">{{ $moduleStats['sr']->count() ?? 0 }}</p>
            </div>

            <div class="bg-purple-50 p-4 rounded">
                <h3 class="font-medium text-purple-900">Gas In Data</h3>
                <p class="text-2xl font-bold text-purple-600">{{ $moduleStats['gas_in']->count() ?? 0 }}</p>
            </div>
        </div>

        @if($padukuhanList && $padukuhanList->count() > 0)
        <div class="mt-6">
            <h3 class="text-lg font-medium mb-2">Available Padukuhan:</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($padukuhanList as $padukuhan)
                    <span class="bg-gray-100 px-3 py-1 rounded-full text-sm">{{ $padukuhan }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if($statsByJenis && $statsByJenis->count() > 0)
        <div class="mt-6">
            <h3 class="text-lg font-medium mb-2">Customer Types:</h3>
            <div class="space-y-2">
                @foreach($statsByJenis as $jenis => $stats)
                    <div class="flex justify-between items-center">
                        <span class="capitalize">{{ str_replace('_', ' ', $jenis) }}</span>
                        <span class="font-bold">{{ $stats->total ?? 0 }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection