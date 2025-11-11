@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Success Message -->
    @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <i class="fas fa-check-circle text-xl mr-3"></i>
            <p class="font-medium">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    <!-- Professional Header -->
    <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Laporan Harian HSE</h1>
                <p class="mt-1 text-sm text-gray-600">Health, Safety & Environment Management System</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="{{ route('hse.daily-reports.create') }}"
                   class="inline-flex items-center px-5 py-2.5 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors font-medium shadow-sm">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Buat Laporan Baru
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards - Subtle Design -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Laporan</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['total_reports'] ?? 0 }}</p>
                </div>
                <div class="bg-gray-100 p-3 rounded-lg">
                    <i class="fas fa-file-alt text-2xl text-gray-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Draft</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['draft'] ?? 0 }}</p>
                </div>
                <div class="bg-gray-100 p-3 rounded-lg">
                    <i class="fas fa-edit text-2xl text-gray-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Submitted</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['submitted'] ?? 0 }}</p>
                </div>
                <div class="bg-blue-50 p-3 rounded-lg">
                    <i class="fas fa-paper-plane text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Approved</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['approved'] ?? 0 }}</p>
                </div>
                <div class="bg-green-50 p-3 rounded-lg">
                    <i class="fas fa-check-circle text-2xl text-green-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- JKA Stats - Professional Design -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-green-50 p-4 rounded-lg">
                    <i class="fas fa-clock text-4xl text-green-600"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Jam Kerja Aman Kumulatif</p>
                    <div class="flex items-baseline space-x-2 mt-1">
                        <p class="text-4xl font-bold text-gray-900">{{ number_format($stats['total_jka'] ?? 0) }}</p>
                        <span class="text-lg text-gray-600 font-medium">Jam</span>
                    </div>
                    <p class="text-sm text-green-600 mt-1 font-medium">🛡️ Zero Accident Achievement</p>
                </div>
            </div>
            <div class="hidden lg:block">
                <div class="text-right bg-gray-50 px-6 py-3 rounded-lg border border-gray-200">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Target Bulanan</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format(($stats['total_jka'] ?? 0) * 1.2) }}</p>
                    <p class="text-xs text-gray-500">Jam</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Export PDF Options - Compact Design -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <!-- Export Mingguan -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center space-x-3 mb-4">
                <div class="bg-blue-50 p-2.5 rounded-lg">
                    <i class="fas fa-calendar-week text-blue-600 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Laporan Mingguan</h3>
                    <p class="text-xs text-gray-500">Export berdasarkan rentang tanggal</p>
                </div>
            </div>

            <form action="{{ route('hse.reports.weekly-pdf') }}" method="GET" target="_blank">
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Dari Tanggal</label>
                        <input type="date" name="start_date" required
                               class="w-full text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                        <input type="date" name="end_date" required
                               class="w-full text-sm rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
                    </div>
                </div>
                <button type="submit"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                    <i class="fas fa-file-pdf mr-2"></i>Generate PDF Mingguan
                </button>
            </form>
        </div>

        <!-- Export Bulanan -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center space-x-3 mb-4">
                <div class="bg-purple-50 p-2.5 rounded-lg">
                    <i class="fas fa-calendar-alt text-purple-600 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Laporan Bulanan</h3>
                    <p class="text-xs text-gray-500">Export berdasarkan bulan</p>
                </div>
            </div>

            <form action="{{ route('hse.reports.monthly-pdf') }}" method="GET" target="_blank">
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Bulan</label>
                        <select name="month" required
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-purple-500 focus:ring focus:ring-purple-200">
                            @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m, 1)->isoFormat('MMMM') }}
                            </option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Tahun</label>
                        <select name="year" required
                                class="w-full text-sm rounded-lg border-gray-300 focus:border-purple-500 focus:ring focus:ring-purple-200">
                            @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}" {{ date('Y') == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <button type="submit"
                        class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium">
                    <i class="fas fa-file-pdf mr-2"></i>Generate PDF Bulanan
                </button>
            </form>
        </div>
    </div>

    <!-- Filters - Compact Above Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{ showFilters: false }">
        <div class="p-4 border-b border-gray-200">
            <button @click="showFilters = !showFilters"
                    class="flex items-center justify-between w-full text-gray-700 hover:text-gray-900 transition-colors">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-filter text-gray-400"></i>
                    <span class="font-medium">Filter & Pencarian Laporan</span>
                </div>
                <i class="fas fa-chevron-down text-sm transition-transform duration-200" :class="{ 'rotate-180': showFilters }"></i>
            </button>
        </div>

        <div x-show="showFilters"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="p-4 bg-gray-50">
            <form method="GET" action="{{ route('hse.daily-reports.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Status</label>
                    <select name="status" class="w-full text-sm rounded-lg border-gray-300 focus:border-orange-500 focus:ring focus:ring-orange-200">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full text-sm rounded-lg border-gray-300 focus:border-orange-500 focus:ring focus:ring-orange-200">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full text-sm rounded-lg border-gray-300 focus:border-orange-500 focus:ring focus:ring-orange-200">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">Pencarian</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari..."
                           class="w-full text-sm rounded-lg border-gray-300 focus:border-orange-500 focus:ring focus:ring-orange-200">
                </div>

                <div class="md:col-span-4 flex justify-end space-x-2 pt-2">
                    <a href="{{ route('hse.daily-reports.index') }}"
                       class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors font-medium">
                        <i class="fas fa-redo mr-1.5"></i>Reset
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors font-medium">
                        <i class="fas fa-search mr-1.5"></i>Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nama Proyek
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tanggal
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cuaca
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Pekerja
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            JKA
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Dibuat Oleh
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($reports as $report)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <div class="font-medium">{{ Str::limit($report->nama_proyek, 40) }}</div>
                            <div class="text-xs text-gray-500">{{ $report->pemberi_pekerjaan }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ \Carbon\Carbon::parse($report->tanggal_laporan)->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="text-2xl" title="{{ ucfirst(str_replace('_', ' ', $report->cuaca)) }}">
                                {{ $report->getCuacaIcon() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $report->total_pekerja }} orang
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($report->jka_hari_ini) }} jam
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $report->getStatusBadgeClass() }}">
                                {{ $report->getStatusLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $report->creator->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="{{ route('hse.daily-reports.show', $report->id) }}"
                                   class="text-blue-600 hover:text-blue-900"
                                   title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($report->canEdit())
                                <a href="{{ route('hse.daily-reports.edit', $report->id) }}"
                                   class="text-yellow-600 hover:text-yellow-900"
                                   title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endif
                                <a href="{{ route('hse.daily-reports.pdf', $report->id) }}"
                                   class="text-red-600 hover:text-red-900"
                                   target="_blank"
                                   title="Export PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-500">
                                <i class="fas fa-inbox text-5xl mb-3"></i>
                                <p class="text-lg font-medium">Tidak ada laporan</p>
                                <p class="text-sm mt-1">Mulai dengan membuat laporan baru</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($reports->hasPages())
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            {{ $reports->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
