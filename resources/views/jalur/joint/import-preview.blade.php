@extends('layouts.app')

@section('title', 'Preview Import Data Joint')

@section('content')
@php
    $details    = collect($summary['details']);
    $newData    = $details->where('status', 'new');
    $updateData = $details->where('status', 'update');
    $skipNoCh   = $details->where('status', 'skip_no_change');
    $skipAppr   = $details->where('status', 'skip_approved');
    $recallData = $details->where('status', 'recall');
    $errorData  = $details->where('status', 'error');
    $dupData    = $details->where('status', 'duplicate_in_file');

    $hasRecall  = $summary['recall'] > 0;
    $toCommit   = $summary['new'] + $summary['update'] + $summary['recall'];
@endphp

<div class="container mx-auto px-6 py-8">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-1">Preview Import Data Joint</h1>
            <p class="text-gray-500 text-sm">File: <strong>{{ $fileName }}</strong></p>
        </div>
        <a href="{{ route('jalur.joint.import.index') }}"
           class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md flex items-center text-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali
        </a>
    </div>

    {{-- Mode Indicator --}}
    <div class="bg-gray-50 border rounded-lg p-4 mb-6 flex flex-wrap gap-4 items-center text-sm">
        <span class="font-semibold text-gray-700">Mode aktif:</span>

        <span class="flex items-center gap-1">
            <span class="font-medium text-gray-600">Force Update:</span>
            @if($forceUpdate)
                <span class="bg-orange-500 text-white px-2 py-0.5 rounded-full text-xs font-bold">ON — Timpa semua field draft</span>
            @else
                <span class="bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full text-xs">OFF — Isi field kosong saja</span>
            @endif
        </span>

        <span class="flex items-center gap-1">
            <span class="font-medium text-gray-600">Allow Recall:</span>
            @if($allowRecall)
                <span class="bg-red-500 text-white px-2 py-0.5 rounded-full text-xs font-bold">ON — Data approved bisa di-recall</span>
            @else
                <span class="bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full text-xs">OFF — Data approved tidak disentuh</span>
            @endif
        </span>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">
        {{-- NEW --}}
        <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-green-700">{{ $summary['new'] }}</p>
            <p class="text-xs text-green-600 font-medium mt-0.5">BARU</p>
        </div>
        {{-- UPDATE --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-blue-700">{{ $summary['update'] }}</p>
            <p class="text-xs text-blue-600 font-medium mt-0.5">UPDATE</p>
        </div>
        {{-- SKIP NO CHANGE --}}
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-gray-500">{{ $summary['skip_no_change'] }}</p>
            <p class="text-xs text-gray-400 font-medium mt-0.5">SKIP<br>(sama)</p>
        </div>
        {{-- SKIP APPROVED --}}
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-amber-600">{{ $summary['skip_approved'] }}</p>
            <p class="text-xs text-amber-500 font-medium mt-0.5">SKIP<br>(approved)</p>
        </div>
        {{-- RECALL --}}
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-red-600">{{ $summary['recall'] }}</p>
            <p class="text-xs text-red-500 font-medium mt-0.5">RECALL</p>
        </div>
        {{-- DUPLICATE --}}
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-yellow-600">{{ $summary['duplicate_in_file'] }}</p>
            <p class="text-xs text-yellow-500 font-medium mt-0.5">DUPLIKAT</p>
        </div>
        {{-- ERROR --}}
        <div class="bg-rose-50 border border-rose-200 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-rose-600">{{ $summary['error'] }}</p>
            <p class="text-xs text-rose-500 font-medium mt-0.5">ERROR</p>
        </div>
        {{-- DB DUPLICATE --}}
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-3 text-center {{ ($summary['db_duplicate'] ?? 0) > 0 ? 'border-purple-400' : '' }}">
            <p class="text-2xl font-bold text-purple-600">{{ $summary['db_duplicate'] ?? 0 }}</p>
            <p class="text-xs text-purple-500 font-medium mt-0.5">DUPLIKAT DB</p>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ ERROR ══ --}}
    @if($summary['error'] > 0)
    <div class="bg-white rounded-lg shadow mb-5 overflow-hidden">
        <div class="bg-rose-600 text-white px-5 py-3 flex items-center justify-between cursor-pointer" onclick="toggleSection('sec-error')">
            <span class="font-semibold">Error Validasi ({{ $summary['error'] }} baris)</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
        <div id="sec-error">
            <div class="p-3 bg-rose-50 text-rose-700 text-sm font-medium">
                Baris-baris berikut gagal validasi dan akan dilewati saat import.
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">Baris</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Joint Number</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pesan Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($errorData as $item)
                        <tr class="hover:bg-rose-50">
                            <td class="px-4 py-2 font-mono text-gray-600">{{ $item['row'] }}</td>
                            <td class="px-4 py-2 font-semibold text-gray-800">{{ $item['data']['joint_number'] ?? '-' }}</td>
                            <td class="px-4 py-2 text-rose-700">{{ $item['message'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ DB DUPLICATE ══ --}}
    @if(($summary['db_duplicate'] ?? 0) > 0)
    <div class="bg-white rounded-lg shadow mb-5 overflow-hidden border-2 border-purple-400">
        <div class="bg-purple-600 text-white px-5 py-3 flex items-center justify-between">
            <span class="font-semibold">⚠ Duplikat Ditemukan di Database ({{ $summary['db_duplicate'] }} baris)</span>
            <a href="{{ route('jalur.joint.import.duplicates') }}" target="_blank"
               class="text-xs bg-white text-purple-700 font-semibold px-3 py-1 rounded-full hover:bg-purple-50">
                Kelola Duplikat →
            </a>
        </div>
        <div class="p-4 bg-purple-50 text-purple-800 text-sm">
            Data berikut tidak bisa diproses karena di database sudah ada lebih dari 1 record dengan kombinasi
            <em>joint number + tanggal + line from + line to</em> yang sama.
            Selesaikan duplikat di database terlebih dahulu melalui halaman <strong>Kelola Duplikat</strong>,
            lalu upload ulang file ini.
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">Baris</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Joint Number</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cluster</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Line From → To</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jumlah di DB</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">IDs di DB</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($details->where('status', 'db_duplicate') as $item)
                    <tr class="hover:bg-purple-50">
                        <td class="px-4 py-2 font-mono text-gray-500 text-xs">{{ $item['row'] }}</td>
                        <td class="px-4 py-2"><code class="text-purple-700 font-bold">{{ $item['data']['joint_number'] }}</code></td>
                        <td class="px-4 py-2 text-gray-700">{{ $item['data']['cluster'] }}</td>
                        <td class="px-4 py-2 text-gray-600 text-xs">{{ $item['data']['tanggal_joint'] }}</td>
                        <td class="px-4 py-2 text-xs text-gray-600">
                            {{ $item['data']['joint_line_from'] }} → {{ $item['data']['joint_line_to'] }}
                        </td>
                        <td class="px-4 py-2">
                            <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full font-semibold">
                                {{ $item['data']['count'] }} record
                            </span>
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-500 font-mono">
                            [{{ implode(', ', $item['data']['duplicate_ids']) }}]
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ DUPLICATE ══ --}}
    @if($summary['duplicate_in_file'] > 0)
    <div class="bg-white rounded-lg shadow mb-5 overflow-hidden">
        <div class="bg-yellow-500 text-white px-5 py-3 flex items-center justify-between cursor-pointer" onclick="toggleSection('sec-dup')">
            <span class="font-semibold">Duplikat Dalam File ({{ $summary['duplicate_in_file'] }} baris)</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
        <div id="sec-dup">
            <div class="p-3 bg-yellow-50 text-yellow-700 text-sm">
                Joint number yang sama muncul lebih dari sekali dalam file. Hanya baris pertama yang diproses.
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">Baris</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Joint Number</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($dupData as $item)
                        <tr class="hover:bg-yellow-50">
                            <td class="px-4 py-2 font-mono text-gray-600">{{ $item['row'] }}</td>
                            <td class="px-4 py-2 font-semibold text-gray-800">{{ $item['data']['joint_number'] }}</td>
                            <td class="px-4 py-2 text-yellow-700">{{ $item['message'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ RECALL ══ --}}
    @if($summary['recall'] > 0)
    <div class="bg-white rounded-lg shadow mb-5 overflow-hidden border-2 border-red-400">
        <div class="bg-red-600 text-white px-5 py-3 flex items-center justify-between cursor-pointer" onclick="toggleSection('sec-recall')">
            <span class="font-semibold">⚠ Recall ke Draft ({{ $summary['recall'] }} data)</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
        <div id="sec-recall">
            <div class="p-3 bg-red-50 text-red-800 text-sm font-medium">
                Data-data berikut statusnya akan di-reset ke <strong>draft</strong> karena ada perubahan pada data krusial.
                Semua approval sebelumnya akan hilang.
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-red-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">Baris</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Joint Number</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status Lama</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Perubahan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($recallData as $item)
                        <tr class="hover:bg-red-50">
                            <td class="px-4 py-2 font-mono text-gray-600">{{ $item['row'] }}</td>
                            <td class="px-4 py-2">
                                <code class="text-red-700 font-semibold">{{ $item['data']['joint_number'] }}</code>
                                @if(!empty($item['data']['foto_hyperlink']) && $item['photo_changed'])
                                    <span class="ml-1 text-xs bg-red-100 text-red-600 px-1 rounded">foto diupdate</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                <span class="text-xs bg-red-200 text-red-800 px-2 py-0.5 rounded-full">{{ $item['previous_status'] }}</span>
                                <span class="text-red-400 mx-1">→</span>
                                <span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full">draft</span>
                            </td>
                            <td class="px-4 py-2">
                                @if(!empty($item['diff']))
                                    <ul class="space-y-0.5">
                                    @foreach($item['diff'] as $d)
                                        <li class="text-xs">
                                            <span class="font-medium text-gray-600">{{ $d['label'] }}:</span>
                                            <span class="text-red-500 line-through">{{ $d['old'] ?: '(kosong)' }}</span>
                                            <span class="text-gray-400 mx-1">→</span>
                                            <span class="text-green-700">{{ $d['new'] ?: '(kosong)' }}</span>
                                        </li>
                                    @endforeach
                                    </ul>
                                @else
                                    <span class="text-xs text-gray-400">Perubahan foto saja</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ SKIP APPROVED ══ --}}
    @if($summary['skip_approved'] > 0)
    <div class="bg-white rounded-lg shadow mb-5 overflow-hidden">
        <div class="bg-amber-500 text-white px-5 py-3 flex items-center justify-between cursor-pointer" onclick="toggleSection('sec-skip-appr')">
            <span class="font-semibold">Dilewati — Data Approved/Rejected ({{ $summary['skip_approved'] }} data)</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
        <div id="sec-skip-appr">
            <div class="p-3 bg-amber-50 text-amber-700 text-sm">
                Data berikut sudah approved/rejected dan ada perubahan krusial yang tidak bisa diterapkan.
                Aktifkan <strong>Allow Recall</strong> untuk mengizinkan perubahan ini.
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">Baris</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Joint Number</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Perubahan yang Diblokir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($skipAppr as $item)
                        <tr class="hover:bg-amber-50">
                            <td class="px-4 py-2 font-mono text-gray-600">{{ $item['row'] }}</td>
                            <td class="px-4 py-2 font-semibold text-gray-800">{{ $item['data']['joint_number'] }}</td>
                            <td class="px-4 py-2">
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">{{ $item['data']['existing_status'] }}</span>
                            </td>
                            <td class="px-4 py-2">
                                @if(!empty($item['diff']))
                                    <ul class="space-y-0.5">
                                    @foreach($item['diff'] as $d)
                                        @if($d['krusial'])
                                        <li class="text-xs text-gray-600">
                                            <span class="font-medium">{{ $d['label'] }}:</span>
                                            {{ $d['old'] ?: '(kosong)' }}
                                            <span class="text-gray-400 mx-1">→</span>
                                            <span class="text-orange-600">{{ $d['new'] }}</span>
                                            <span class="text-red-400 ml-1">[diblokir]</span>
                                        </li>
                                        @endif
                                    @endforeach
                                    </ul>
                                @else
                                    <span class="text-xs text-gray-400">Perubahan foto saja</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ NEW ══ --}}
    @if($summary['new'] > 0)
    <div class="bg-white rounded-lg shadow mb-5 overflow-hidden">
        <div class="bg-green-600 text-white px-5 py-3 flex items-center justify-between cursor-pointer" onclick="toggleSection('sec-new')">
            <span class="font-semibold">Data Baru ({{ $summary['new'] }} joint)</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
        <div id="sec-new">
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-green-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">Baris</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Joint Number</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cluster</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fitting Type</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Line From → To</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Foto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($newData as $item)
                        <tr class="hover:bg-green-50">
                            <td class="px-4 py-2 font-mono text-gray-500 text-xs">{{ $item['row'] }}</td>
                            <td class="px-4 py-2"><code class="text-green-700 font-bold">{{ $item['data']['joint_number'] }}</code></td>
                            <td class="px-4 py-2 text-gray-700">{{ $item['data']['cluster'] }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $item['data']['fitting_type'] }}</td>
                            <td class="px-4 py-2 text-gray-600 text-xs">{{ $item['data']['tanggal_joint'] }}</td>
                            <td class="px-4 py-2 text-xs text-gray-600">
                                {{ $item['data']['joint_line_from'] }}<br>
                                <span class="text-gray-400">→</span> {{ $item['data']['joint_line_to'] }}
                            </td>
                            <td class="px-4 py-2">
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">{{ $item['data']['tipe_penyambungan'] }}</span>
                            </td>
                            <td class="px-4 py-2">
                                @if(!empty($item['data']['foto_hyperlink']))
                                    <a href="{{ $item['data']['foto_hyperlink'] }}" target="_blank" class="text-green-600 text-xs underline">Ada</a>
                                @else
                                    <span class="text-gray-300 text-xs">–</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ UPDATE ══ --}}
    @if($summary['update'] > 0)
    <div class="bg-white rounded-lg shadow mb-5 overflow-hidden">
        <div class="bg-blue-600 text-white px-5 py-3 flex items-center justify-between cursor-pointer" onclick="toggleSection('sec-update')">
            <span class="font-semibold">Akan Diupdate ({{ $summary['update'] }} joint)</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
        <div id="sec-update">
            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-blue-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">Baris</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Joint Number</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Perubahan (lama → baru)</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Foto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($updateData as $item)
                        <tr class="hover:bg-blue-50">
                            <td class="px-4 py-2 font-mono text-gray-500 text-xs">{{ $item['row'] }}</td>
                            <td class="px-4 py-2"><code class="text-blue-700 font-bold">{{ $item['data']['joint_number'] }}</code></td>
                            <td class="px-4 py-2">
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $item['data']['existing_status'] ?? 'draft' }}</span>
                            </td>
                            <td class="px-4 py-2">
                                @if(!empty($item['diff']))
                                    <ul class="space-y-0.5">
                                    @foreach($item['diff'] as $d)
                                        <li class="text-xs flex items-start gap-1">
                                            @if($d['will_apply'])
                                                <span class="text-green-500 mt-0.5">✓</span>
                                            @else
                                                <span class="text-gray-300 mt-0.5">○</span>
                                            @endif
                                            <span>
                                                <span class="font-medium text-gray-600">{{ $d['label'] }}:</span>
                                                @if($d['will_apply'])
                                                    <span class="text-red-400 line-through">{{ $d['old'] ?: '(kosong)' }}</span>
                                                    <span class="text-gray-400 mx-0.5">→</span>
                                                    <span class="text-green-700 font-medium">{{ $d['new'] ?: '(kosong)' }}</span>
                                                @else
                                                    <span class="text-gray-400">{{ $d['old'] ?: '(kosong)' }}
                                                        <em class="text-gray-300 not-italic">(tidak diterapkan)</em>
                                                    </span>
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                    </ul>
                                @else
                                    <span class="text-xs text-gray-400">Tidak ada perubahan field</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-xs">
                                @if($item['photo_changed'] ?? false)
                                    <span class="bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded">diupdate</span>
                                @elseif(!empty($item['data']['foto_hyperlink']))
                                    <span class="text-gray-400">ada (tidak diubah)</span>
                                @else
                                    <span class="text-gray-300">–</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ SKIP NO CHANGE ══ --}}
    @if($summary['skip_no_change'] > 0)
    <div class="bg-white rounded-lg shadow mb-5 overflow-hidden">
        <div class="bg-gray-400 text-white px-5 py-3 flex items-center justify-between cursor-pointer" onclick="toggleSection('sec-skip')">
            <span class="font-semibold">Tidak Ada Perubahan — Dilewati ({{ $summary['skip_no_change'] }} data)</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
        <div id="sec-skip" class="hidden">
            <div class="overflow-x-auto max-h-64 overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">Baris</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Joint Number</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($skipNoCh as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-mono text-gray-400 text-xs">{{ $item['row'] }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $item['data']['joint_number'] }}</td>
                            <td class="px-4 py-2">
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">{{ $item['data']['existing_status'] ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-400">{{ $item['message'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ ACTION ══ --}}
    @if($toCommit > 0)
    <div class="bg-white rounded-lg shadow p-6 mt-4">

        {{-- Recall confirmation --}}
        @if($hasRecall)
        <div class="bg-red-50 border border-red-300 rounded-lg p-4 mb-5">
            <p class="text-red-800 font-semibold text-sm mb-3">
                ⚠ Import ini akan me-recall {{ $summary['recall'] }} record dari status approved/rejected kembali ke draft.
                Semua persetujuan sebelumnya akan hilang dan perlu disetujui ulang.
            </p>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="recallConfirm" class="w-4 h-4 text-red-600" onchange="toggleCommitBtn()">
                <span class="text-sm text-red-700 font-medium">
                    Saya mengerti dan menyetujui recall data di atas
                </span>
            </label>
        </div>
        @endif

        <form action="{{ route('jalur.joint.import.execute') }}" method="POST" id="commitForm">
            @csrf
            <input type="hidden" name="temp_file_path" value="{{ $tempFilePath }}">
            <input type="hidden" name="force_update"   value="{{ $forceUpdate ? '1' : '0' }}">
            <input type="hidden" name="allow_recall"   value="{{ $allowRecall ? '1' : '0' }}">

            <div class="flex gap-4">
                <button type="submit" id="commitBtn"
                        {{ $hasRecall ? 'disabled' : '' }}
                        onclick="showCommitLoading()"
                        class="flex-1 px-6 py-3 rounded-md flex items-center justify-center text-base font-semibold transition
                               {{ $hasRecall ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700 text-white' }}"
                        id="commitBtn">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Commit Import
                    ({{ $summary['new'] > 0 ? $summary['new'].' baru' : '' }}{{ $summary['new'] > 0 && $summary['update'] > 0 ? ' · ' : '' }}{{ $summary['update'] > 0 ? $summary['update'].' update' : '' }}{{ ($summary['new'] > 0 || $summary['update'] > 0) && $summary['recall'] > 0 ? ' · ' : '' }}{{ $summary['recall'] > 0 ? $summary['recall'].' recall' : '' }})
                </button>

                <a href="{{ route('jalur.joint.import.index') }}"
                   class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md flex items-center font-medium">
                    Batal
                </a>
            </div>

            @if($summary['error'] > 0 || $summary['duplicate_in_file'] > 0)
            <p class="text-xs text-gray-400 mt-2">
                * {{ $summary['error'] }} baris error dan {{ $summary['duplicate_in_file'] }} duplikat akan dilewati otomatis.
            </p>
            @endif
        </form>
    </div>
    @else
    <div class="bg-gray-100 rounded-lg p-6 text-center text-gray-500 mt-4">
        <p class="text-lg font-medium">Tidak ada data yang perlu diproses.</p>
        <p class="text-sm mt-1">Semua baris sudah ada di database dan tidak ada perubahan, atau seluruh baris error.</p>
        <a href="{{ route('jalur.joint.import.index') }}" class="mt-4 inline-block bg-gray-600 hover:bg-gray-700 text-white px-5 py-2 rounded-md text-sm">
            Kembali &amp; Upload File Lain
        </a>
    </div>
    @endif

</div>

{{-- Commit loading overlay --}}
<div id="commitOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center" style="z-index:9999">
    <div class="bg-white rounded-lg p-8 max-w-sm mx-4 text-center shadow-xl">
        <svg class="animate-spin h-14 w-14 mx-auto text-green-600 mb-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <h3 class="text-lg font-semibold text-gray-800 mb-1">Mengimport Data…</h3>
        <p class="text-gray-500 text-sm">Mohon tunggu, jangan tutup halaman ini.</p>
    </div>
</div>

<script>
function toggleSection(id) {
    const el = document.getElementById(id);
    el.classList.toggle('hidden');
}

function toggleCommitBtn() {
    const cb  = document.getElementById('recallConfirm');
    const btn = document.getElementById('commitBtn');
    if (cb.checked) {
        btn.disabled = false;
        btn.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
        btn.classList.add('bg-green-600', 'hover:bg-green-700', 'text-white');
    } else {
        btn.disabled = true;
        btn.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
        btn.classList.remove('bg-green-600', 'hover:bg-green-700', 'text-white');
    }
}

function showCommitLoading() {
    const overlay = document.getElementById('commitOverlay');
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
}
</script>
@endsection
