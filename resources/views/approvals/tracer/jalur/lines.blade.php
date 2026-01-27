@extends('layouts.app')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50" x-data="linesData()" x-init="init()">
        <div class="container-fluid px-4 py-8">
            {{-- Breadcrumb --}}
            <div class="mb-6">
                <a href="{{ route('approvals.tracer.jalur.clusters') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-50 transition-all shadow-sm hover:shadow-md group">
                    <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span class="font-semibold">Kembali ke Clusters</span>
                </a>
            </div>

            {{-- Header Section --}}
            <div class="mb-6">
                <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                            </div>
                            <div>
                                <h1
                                    class="text-2xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">
                                    {{ $cluster->nama_cluster }}
                                </h1>
                                <p class="text-gray-600 mt-1">
                                    Pilih line untuk review • Code: <span
                                        class="font-semibold">{{ $cluster->code_cluster }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Stats Cards Row --}}
                    {{-- Stats Cards Row --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        {{-- Total Items (Lines + Joints) --}}
                        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-xs font-medium text-blue-600 mb-1">Total Items (Line/Joint)</div>
                                        <div class="text-2xl font-bold text-blue-900" x-text="stats.total_items || 0"></div>
                                    </div>
                                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center shadow-lg">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-blue-50/50 px-4 py-2 border-t border-blue-100">
                                <div class="text-xs flex justify-between text-blue-800 font-medium">
                                    <span>Line: <strong x-text="stats.total_lines || 0"></strong></span>
                                    <span>Joint: <strong x-text="stats.total_joints || 0"></strong></span>
                                </div>
                            </div>
                        </div>

                        {{-- Pending Photos --}}
                        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                            <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-xs font-medium text-orange-600 mb-1">Pending Review (Photos)</div>
                                        <div class="text-2xl font-bold text-orange-900" x-text="stats.pending_photos || 0"></div>
                                    </div>
                                    <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center shadow-lg">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-orange-50/50 px-4 py-2 border-t border-orange-100">
                                <div class="text-xs flex justify-between text-orange-800 font-medium">
                                    <span>Line: <strong x-text="stats.breakdown?.pending_photos?.line || 0"></strong></span>
                                    <span>Joint: <strong x-text="stats.breakdown?.pending_photos?.joint || 0"></strong></span>
                                </div>
                            </div>
                        </div>

                        {{-- Approved Photos --}}
                        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                            <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-xs font-medium text-green-600 mb-1">Approved (Photos)</div>
                                        <div class="text-2xl font-bold text-green-900" x-text="stats.approved_photos || 0"></div>
                                    </div>
                                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center shadow-lg">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-green-50/50 px-4 py-2 border-t border-green-100">
                                <div class="text-xs flex justify-between text-green-800 font-medium">
                                    <span>Line: <strong x-text="stats.breakdown?.approved_photos?.line || 0"></strong></span>
                                    <span>Joint: <strong x-text="stats.breakdown?.approved_photos?.joint || 0"></strong></span>
                                </div>
                            </div>
                        </div>

                        {{-- Rejected Photos --}}
                        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                            <div class="bg-gradient-to-br from-red-50 to-red-100 p-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-xs font-medium text-red-600 mb-1">Rejected (Photos)</div>
                                        <div class="text-2xl font-bold text-red-900" x-text="stats.rejected_photos || 0"></div>
                                    </div>
                                    <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center shadow-lg">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-red-50/50 px-4 py-2 border-t border-red-100">
                                <div class="text-xs flex justify-between text-red-800 font-medium">
                                    <span>Line: <strong x-text="stats.breakdown?.rejected_photos?.line || 0"></strong></span>
                                    <span>Joint: <strong x-text="stats.breakdown?.rejected_photos?.joint || 0"></strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters & Search Section --}}
            <div class="mb-6">
                <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                    {{-- Header --}}
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-3 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                <h3 class="text-sm font-semibold text-gray-700">Filter & Pencarian</h3>
                            </div>
                            <button @click="resetFilters()"
                                x-show="filters.search || filters.filter !== 'all' || filters.date_from || filters.date_to"
                                class="flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all text-xs font-medium shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Reset Filter
                            </button>
                        </div>
                    </div>

                    {{-- Filter Content --}}
                    <div class="p-6">
                        {{-- Search Bar --}}
                        <div class="mb-4">
                            <label class="block text-xs font-medium text-gray-600 mb-2">Pencarian</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input type="text" x-model="filters.search" @input.debounce.500ms="fetchLines(true)"
                                    placeholder="Cari berdasarkan line number..."
                                    class="pl-10 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            </div>
                        </div>

                        {{-- Filter Row --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- Status Filter --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-2">Status</label>
                                <select x-model="filters.filter" @change="fetchLines(true)"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    <option value="all">🔍 Semua Status</option>
                                    <option value="pending">⏳ Pending Review</option>
                                    <option value="rejected">❌ Rejected</option>
                                    <option value="no_evidence">📭 No Evidence</option>
                                    <option value="approved">✅ Approved</option>
                                </select>
                            </div>

                            {{-- Date From --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-2">Dari Tanggal</label>
                                <input type="date" x-model="filters.date_from" @change="fetchLines(true)"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            </div>

                            {{-- Date To --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-2">Sampai Tanggal</label>
                                <input type="date" x-model="filters.date_to" @change="fetchLines(true)"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            </div>
                        </div>

                        {{-- Active Filters Display --}}
                        <div x-show="filters.search || filters.filter !== 'all' || filters.date_from || filters.date_to"
                            class="mt-4 pt-4 border-t border-gray-200">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-medium text-gray-500">Filter Aktif:</span>

                                <span x-show="filters.search"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-100 text-blue-700 rounded-md text-xs font-medium">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <span x-text="'Pencarian: ' + filters.search"></span>
                                </span>

                                <span x-show="filters.filter !== 'all'"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-purple-100 text-purple-700 rounded-md text-xs font-medium">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    <span
                                        x-text="'Status: ' + (filters.filter === 'pending' ? 'Pending' : filters.filter === 'approved' ? 'Approved' : filters.filter === 'rejected' ? 'Rejected' : 'No Evidence')"></span>
                                </span>

                                <span x-show="filters.date_from"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-100 text-indigo-700 rounded-md text-xs font-medium">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span x-text="'Dari: ' + filters.date_from"></span>
                                </span>

                                <span x-show="filters.date_to"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-100 text-indigo-700 rounded-md text-xs font-medium">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span x-text="'Sampai: ' + filters.date_to"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Items Grid - 2 Columns Compact (Lines + Joints) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
                <template x-for="item in items" :key="`${item.item_type}-${item.id}`">
                    <div class="h-full">
                        {{-- LINE CARD --}}
                        <template x-if="item.item_type === 'line'">
                            <div
                                class="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden flex flex-col h-full">
                                <a :href="`/approvals/tracer/jalur/lines/${item.id}/evidence`"
                                    class="flex flex-col flex-1 h-full">
                                    {{-- Card Header - Compact --}}
                                    <div
                                        class="bg-gradient-to-r from-indigo-50 to-purple-50 px-5 py-3 border-b border-gray-200">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center shadow-md">
                                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition"
                                                        x-text="item.line_number"></h3>
                                                    <span class="text-xs font-medium text-gray-600">
                                                        <span x-text="item.approval_status_label"></span> (<span
                                                            x-text="Math.round(item.approval_progress || 0)"></span>%)
                                                    </span>
                                                </div>
                                            </div>

                                            {{-- Status Badge --}}
                                            <span x-show="item.approval_stats.status === 'approved'"
                                                class="px-3 py-1 text-xs font-bold rounded-full bg-green-100 text-green-800 border border-green-300">
                                                ✓ Approved
                                            </span>
                                            <span x-show="item.approval_stats.status === 'rejected'"
                                                class="px-3 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800 border border-red-300">
                                                ✗ Rejected
                                            </span>
                                            <span x-show="item.approval_stats.status === 'pending'"
                                                class="px-3 py-1 text-xs font-bold rounded-full bg-orange-100 text-orange-800 border border-orange-300">
                                                ⏳ Pending
                                            </span>
                                            <span
                                                x-show="!item.approval_stats.status || item.approval_stats.status === 'no_data'"
                                                class="px-3 py-1 text-xs font-bold rounded-full bg-gray-100 text-gray-600">
                                                No Data
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Card Body - Compact --}}
                                    <div class="p-4 flex-1 flex flex-col">
                                        {{-- Line Detail Information --}}
                                        <div
                                            class="mb-3 p-3 bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg border border-gray-200">
                                            <div class="grid grid-cols-2 gap-2 text-xs">
                                                <div>
                                                    <span class="text-gray-500">Diameter:</span>
                                                    <span class="font-semibold text-gray-900">Ø<span
                                                            x-text="item.diameter"></span>mm</span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500">Jalan:</span>
                                                    <span class="font-semibold text-gray-900"
                                                        x-text="item.nama_jalan && item.nama_jalan.trim() !== '' ? item.nama_jalan : '-'"></span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Metrics - Compact 3 columns --}}
                                        <div class="grid grid-cols-3 gap-2 mb-3">
                                            <div class="text-center bg-blue-50 rounded-lg p-2 border border-blue-100">
                                                <div class="text-xs text-blue-600 font-medium">MC-0</div>
                                                <div class="text-sm font-bold text-blue-900"
                                                    x-text="Number(item.estimasi_panjang || 0).toFixed(1) + 'm'"></div>
                                            </div>
                                            <div class="text-center bg-indigo-50 rounded-lg p-2 border border-indigo-100">
                                                <div class="text-xs text-indigo-600 font-medium">Actual</div>
                                                <div class="text-sm font-bold text-indigo-900"
                                                    x-text="Number(item.total_penggelaran || 0).toFixed(1) + 'm'"></div>
                                            </div>
                                            <div class="text-center bg-green-50 rounded-lg p-2 border border-green-100">
                                                <div class="text-xs text-green-600 font-medium">MC-100</div>
                                                <div class="text-sm font-bold"
                                                    :class="item.actual_mc100 ? 'text-green-900' : 'text-gray-400'"
                                                    x-text="item.actual_mc100 ? Number(item.actual_mc100).toFixed(1) + 'm' : '-'">
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Work Dates with Penggelaran - Flex Grow untuk mengisi space --}}
                                        <div class="mb-3 flex-1 flex flex-col">
                                            <div class="text-xs text-gray-600 mb-2 font-semibold">
                                                <span x-text="item.approval_stats.work_dates_count || 0"></span> Tanggal
                                                Pekerjaan
                                            </div>
                                            <div class="flex-1 max-h-32 overflow-y-auto space-y-1"
                                                x-show="item.approval_stats.work_dates_detail && item.approval_stats.work_dates_detail.length > 0">
                                                <template
                                                    x-for="dateDetail in (item.approval_stats.work_dates_detail || [])"
                                                    :key="dateDetail.date">
                                                    <div
                                                        class="flex items-center justify-between px-2 py-1 bg-indigo-50 rounded border border-indigo-200">
                                                        <span class="text-xs font-medium text-indigo-800"
                                                            x-text="new Date(dateDetail.date).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'})"></span>
                                                        <span class="text-xs font-bold text-indigo-900"
                                                            x-text="Number(dateDetail.penggelaran || 0).toFixed(1) + 'm'"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        {{-- Rejection Info --}}
                                        <template
                                            x-if="item.approval_stats.rejections && item.approval_stats.rejections.has_rejections">
                                            <div class="mb-3 space-y-1">
                                                <template x-for="rejection in item.approval_stats.rejections.all"
                                                    :key="rejection.field_name">
                                                    <div
                                                        class="bg-red-50 border border-red-200 rounded px-2 py-1.5 text-xs">
                                                        <div class="flex items-start gap-1.5">
                                                            <svg class="w-3 h-3 text-red-600 flex-shrink-0 mt-0.5"
                                                                fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="flex items-start justify-between gap-2 mb-0.5">
                                                                    <div class="font-medium text-red-800"
                                                                        x-text="rejection.label"></div>
                                                                    <div
                                                                        class="text-red-500 text-[10px] flex items-center gap-1 flex-shrink-0">
                                                                        <span x-text="rejection.user_name"></span>
                                                                        <span>•</span>
                                                                        <span x-text="rejection.rejected_at"></span>
                                                                    </div>
                                                                </div>
                                                                <div class="text-red-600"
                                                                    x-show="rejection.notes && rejection.notes !== '-'"
                                                                    x-text="rejection.notes"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        {{-- Approval Stats - Always at bottom --}}
                                        <div class="mt-auto">
                                            <template x-if="item.approval_stats.total_photos > 0">
                                                <div>
                                                    <div class="flex items-center justify-between text-xs mb-2">
                                                        <span class="text-gray-600">
                                                            Photos: <span class="font-semibold text-gray-900"><span
                                                                    x-text="item.approval_stats.approved_photos"></span>/<span
                                                                    x-text="item.approval_stats.total_photos"></span></span>
                                                        </span>
                                                        <span class="font-bold text-gray-900"
                                                            x-text="Math.round(item.approval_stats.percentage || 0) + '%'"></span>
                                                    </div>
                                                    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                                        <div class="h-2 rounded-full transition-all"
                                                            :class="item.approval_stats.percentage === 100 ? 'bg-green-500' : 'bg-indigo-500'"
                                                            :style="`width: ${item.approval_stats.percentage || 0}%`">
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                            <template
                                                x-if="!item.approval_stats.total_photos || item.approval_stats.total_photos === 0">
                                                <div class="text-center text-xs text-gray-400 py-2">
                                                    Belum ada evidence
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Card Footer - Always at bottom --}}
                                    <div
                                        class="px-4 py-2 bg-gray-50 border-t border-gray-200 flex items-center justify-between mt-auto">
                                        <span class="text-xs text-gray-600">Klik untuk review</span>
                                        <svg class="w-4 h-4 text-indigo-600 group-hover:translate-x-1 transition-transform"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                    </div>
                                </a>
                            </div>
                        </template>

                        {{-- JOINT CARD --}}
                        <template x-if="item.item_type === 'joint'">
                            <div
                                class="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border-2 border-blue-200 overflow-hidden flex flex-col h-full">
                                <a :href="`/approvals/tracer/jalur/joints/${item.id}/evidence`"
                                    class="flex flex-col flex-1 h-full">
                                    {{-- Card Header - Compact --}}
                                    <div
                                        class="bg-gradient-to-r from-blue-50 to-cyan-50 px-5 py-3 border-b border-blue-200">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-lg flex items-center justify-center shadow-md">
                                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600 transition"
                                                        x-text="item.nomor_joint"></h3>
                                                    <span class="text-xs font-medium text-gray-600">
                                                        <span x-text="item.approval_status_label"></span> (<span
                                                            x-text="Math.round(item.approval_progress || 0)"></span>%)
                                                    </span>
                                                </div>
                                            </div>

                                            {{-- Status Badge --}}
                                            <span x-show="item.approval_stats.status === 'approved'"
                                                class="px-3 py-1 text-xs font-bold rounded-full bg-green-100 text-green-800 border border-green-300">
                                                ✓ Approved
                                            </span>
                                            <span x-show="item.approval_stats.status === 'rejected'"
                                                class="px-3 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800 border border-red-300">
                                                ✗ Rejected
                                            </span>
                                            <span x-show="item.approval_stats.status === 'pending'"
                                                class="px-3 py-1 text-xs font-bold rounded-full bg-orange-100 text-orange-800 border border-orange-300">
                                                ⏳ Pending
                                            </span>
                                            <span
                                                x-show="!item.approval_stats.status || item.approval_stats.status === 'no_data'"
                                                class="px-3 py-1 text-xs font-bold rounded-full bg-gray-100 text-gray-600">
                                                No Data
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Card Body - Compact --}}
                                    <div class="p-4 flex-1 flex flex-col">
                                        {{-- Joint Connection Info --}}
                                        <div
                                            class="mb-3 p-3 bg-gradient-to-br from-blue-50 to-cyan-50 rounded-lg border border-blue-200">
                                            <div
                                                class="flex items-center justify-center gap-2 text-sm font-semibold text-gray-900">
                                                <span class="text-blue-700" x-text="item.joint_line_from"></span>
                                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                </svg>
                                                <span class="text-blue-700" x-text="item.joint_line_to"></span>
                                            </div>
                                            <div class="text-center mt-2 text-xs text-gray-600"
                                                x-show="item.joint_line_optional">
                                                <span>+ </span><span x-text="item.joint_line_optional"></span>
                                            </div>
                                        </div>

                                        {{-- Joint Details --}}
                                        <div
                                            class="mb-3 p-3 bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg border border-gray-200">
                                            <div class="grid grid-cols-2 gap-2 text-xs">
                                                <div>
                                                    <span class="text-gray-500">Tipe Penyambungan:</span>
                                                    <span class="font-semibold text-gray-900"
                                                        x-text="item.tipe_penyambungan || '-'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500">Tipe Fitting:</span>
                                                    <span class="font-semibold text-gray-900"
                                                        x-text="item.fitting_type?.nama_fitting || '-'"></span>
                                                </div>
                                                <div class="col-span-2" x-show="item.jalan">
                                                    <span class="text-gray-500">Jalan:</span>
                                                    <span class="font-semibold text-gray-900"
                                                        x-text="item.jalan || '-'"></span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Work Dates - Flex Grow untuk mengisi space --}}
                                        <div class="mb-3 flex-1 flex flex-col">
                                            <div class="text-xs text-gray-600 mb-2 font-semibold">
                                                <span x-text="item.approval_stats.work_dates_count || 0"></span> Tanggal
                                                Pekerjaan
                                            </div>
                                            <div class="flex-1 max-h-32 overflow-y-auto space-y-1"
                                                x-show="item.approval_stats.work_dates_detail && item.approval_stats.work_dates_detail.length > 0">
                                                <template
                                                    x-for="dateDetail in (item.approval_stats.work_dates_detail || [])"
                                                    :key="dateDetail.date">
                                                    <div
                                                        class="flex items-center justify-between px-2 py-1 bg-blue-50 rounded border border-blue-200">
                                                        <span class="text-xs font-medium text-blue-800"
                                                            x-text="new Date(dateDetail.date).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'})"></span>
                                                        <span class="text-xs font-bold text-blue-900"
                                                            x-text="dateDetail.photo_count + ' foto'"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        {{-- Rejection Info --}}
                                        <template
                                            x-if="item.approval_stats.rejections && item.approval_stats.rejections.has_rejections">
                                            <div class="mb-3 space-y-1">
                                                <template x-for="rejection in item.approval_stats.rejections.all"
                                                    :key="rejection.field_name">
                                                    <div
                                                        class="bg-red-50 border border-red-200 rounded px-2 py-1.5 text-xs">
                                                        <div class="flex items-start gap-1.5">
                                                            <svg class="w-3 h-3 text-red-600 flex-shrink-0 mt-0.5"
                                                                fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="flex items-start justify-between gap-2 mb-0.5">
                                                                    <div class="font-medium text-red-800"
                                                                        x-text="rejection.label"></div>
                                                                    <div
                                                                        class="text-red-500 text-[10px] flex items-center gap-1 flex-shrink-0">
                                                                        <span x-text="rejection.user_name"></span>
                                                                        <span>•</span>
                                                                        <span x-text="rejection.rejected_at"></span>
                                                                    </div>
                                                                </div>
                                                                <div class="text-red-600"
                                                                    x-show="rejection.notes && rejection.notes !== '-'"
                                                                    x-text="rejection.notes"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        {{-- Approval Stats - Always at bottom --}}
                                        <div class="mt-auto">
                                            <template x-if="item.approval_stats.total_photos > 0">
                                                <div>
                                                    <div class="flex items-center justify-between text-xs mb-2">
                                                        <span class="text-gray-600">
                                                            Photos: <span class="font-semibold text-gray-900"><span
                                                                    x-text="item.approval_stats.approved_photos"></span>/<span
                                                                    x-text="item.approval_stats.total_photos"></span></span>
                                                        </span>
                                                        <span class="font-bold text-gray-900"
                                                            x-text="Math.round(item.approval_stats.percentage || 0) + '%'"></span>
                                                    </div>
                                                    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                                        <div class="h-2 rounded-full transition-all"
                                                            :class="item.approval_stats.percentage === 100 ? 'bg-green-500' : 'bg-blue-500'"
                                                            :style="`width: ${item.approval_stats.percentage || 0}%`">
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                            <template
                                                x-if="!item.approval_stats.total_photos || item.approval_stats.total_photos === 0">
                                                <div class="text-center text-xs text-gray-400 py-2">
                                                    Belum ada evidence
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Card Footer - Always at bottom --}}
                                    <div
                                        class="px-4 py-2 bg-blue-50 border-t border-blue-200 flex items-center justify-between mt-auto">
                                        <span class="text-xs text-blue-700 font-medium">Klik untuk review</span>
                                        <svg class="w-4 h-4 text-blue-600 group-hover:translate-x-1 transition-transform"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                    </div>
                                </a>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Empty State --}}
                <div x-show="items.length === 0" class="col-span-2">
                    <div class="bg-white rounded-xl shadow-md p-12 text-center border border-gray-100">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-700 mb-2">Tidak Ada Line</h3>
                        <p class="text-gray-500">
                            <span x-show="filters.search">Tidak ditemukan line dengan kata kunci "<span
                                    x-text="filters.search"></span>"</span>
                            <span x-show="!filters.search && filters.filter !== 'all'">Tidak ada line dengan filter yang
                                dipilih</span>
                            <span x-show="!filters.search && filters.filter === 'all'">Belum ada line dalam cluster
                                ini</span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Pagination --}}
            <div x-show="pagination.total > 0" class="mt-6 bg-white px-4 py-3 border border-gray-200 rounded-xl sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium" x-text="pagination.from || 0"></span>
                            to
                            <span class="font-medium" x-text="pagination.to || 0"></span>
                            of
                            <span class="font-medium" x-text="pagination.total || 0"></span>
                            results
                        </span>
                    </div>

                    <div class="flex items-center space-x-2">
                        <button @click="previousPage()" :disabled="pagination.current_page <= 1"
                            :class="pagination.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 transition-colors">
                            Previous
                        </button>

                        <template x-for="page in paginationPages" :key="page">
                            <button @click="goToPage(page)"
                                :class="page === pagination.current_page ? 'bg-orange-600 text-white' : 'text-gray-700 hover:bg-orange-50'"
                                class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium transition-colors">
                                <span x-text="page"></span>
                            </button>
                        </template>

                        <button @click="nextPage()" :disabled="pagination.current_page >= pagination.last_page"
                            :class="pagination.current_page >= pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 transition-colors">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function linesData() {
                return {
                    items: @json($items->items() ?? []),
                    pagination: {
                        current_page: @json($items->currentPage() ?? 1),
                        last_page: @json($items->lastPage() ?? 1),
                        per_page: @json($items->perPage() ?? 20),
                        total: @json($items->total() ?? 0),
                        from: @json($items->firstItem() ?? 0),
                        to: @json($items->lastItem() ?? 0)
                    },
                    stats: @json($stats ?? []),
                    filters: {
                        search: '{{ $search ?? '' }}',
                        filter: '{{ $filter ?? 'all' }}',
                        date_from: '{{ $dateFrom ?? '' }}',
                        date_to: '{{ $dateTo ?? '' }}'
                    },
                    clusterId: {{ $cluster->id }},
                    loading: false,

                    init() {
                        // Initialization complete
                    },

                    async fetchLines(resetPage = false) {
                        this.loading = true;

                        if (resetPage) {
                            this.pagination.current_page = 1;
                        }

                        try {
                            const params = new URLSearchParams({
                                search: this.filters.search || '',
                                filter: this.filters.filter || 'all',
                                date_from: this.filters.date_from || '',
                                date_to: this.filters.date_to || '',
                                per_page: this.pagination.per_page,
                                page: this.pagination.current_page,
                                ajax: 1
                            });

                            const response = await fetch(`/approvals/tracer/jalur/clusters/${this.clusterId}/lines?${params}`, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]').content
                                }
                            });

                            const data = await response.json();

                            if (data.success) {
                                this.items = data.data.data || [];
                                this.pagination = {
                                    current_page: data.data.current_page,
                                    last_page: data.data.last_page,
                                    per_page: data.data.per_page,
                                    total: data.data.total,
                                    from: data.data.from,
                                    to: data.data.to
                                };
                                this.stats = data.stats || this.stats;

                                if (this.pagination.current_page > this.pagination.last_page && this.pagination.last_page > 0) {
                                    this.pagination.current_page = this.pagination.last_page;
                                    this.fetchLines();
                                    return;
                                }
                            }
                        } catch (error) {
                            console.error('Error fetching lines:', error);
                        } finally {
                            this.loading = false;
                        }
                    },

                    resetFilters() {
                        this.filters = {
                            search: '',
                            filter: 'all',
                            date_from: '',
                            date_to: ''
                        };
                        this.fetchLines(true);
                    },

                    get paginationPages() {
                        const pages = [];
                        const current = this.pagination.current_page;
                        const last = this.pagination.last_page;

                        let start = Math.max(1, current - 2);
                        let end = Math.min(last, current + 2);

                        for (let i = start; i <= end; i++) {
                            pages.push(i);
                        }

                        return pages;
                    },

                    goToPage(page) {
                        this.pagination.current_page = page;
                        this.fetchLines();
                    },

                    previousPage() {
                        if (this.pagination.current_page > 1) {
                            this.pagination.current_page--;
                            this.fetchLines();
                        }
                    },

                    nextPage() {
                        if (this.pagination.current_page < this.pagination.last_page) {
                            this.pagination.current_page++;
                            this.fetchLines();
                        }
                    }
                }
            }
        </script>
    @endpush
@endsection