@extends('layouts.app')

@section('title', 'Report Dashboard - Evidence Upload Analytics')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50" x-data="reportDashboard()">
    <!-- Header -->
    <div class="bg-gradient-to-r from-aergas-navy via-blue-800 to-indigo-900 shadow-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                <div class="text-white">
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                            <i class="fas fa-chart-line text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold">Report Dashboard</h1>
                            <p class="text-blue-100 mt-1">Real-time evidence upload analytics & insights</p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                    <button @click="exportData()"
                            class="inline-flex items-center justify-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-semibold transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <i class="fas fa-download mr-2"></i>
                        <span class="hidden sm:inline">Export Data</span>
                        <span class="sm:hidden">Export</span>
                    </button>
                    <button @click="refreshData()"
                            class="inline-flex items-center justify-center px-6 py-3 bg-white/20 hover:bg-white/30 text-white rounded-xl font-semibold transition-all duration-200 backdrop-blur-sm border border-white/20">
                        <i class="fas fa-sync-alt mr-2" :class="{ 'animate-spin': loading }"></i>
                        <span class="hidden sm:inline">Refresh</span>
                        <span class="sm:hidden">Sync</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-2xl shadow-lg border-0 p-6 mb-8 backdrop-blur-sm">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-gray-600 to-gray-800 rounded-lg flex items-center justify-center">
                        <i class="fas fa-filter text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Filters & Search</h3>
                        <p class="text-gray-600 text-sm">Refine your data view with advanced filtering</p>
                    </div>
                </div>
                <button @click="filters = {padukuhan: '', kelurahan: '', jenis_pelanggan: '', start_date: '', end_date: ''}; applyFilters()"
                        class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                    <i class="fas fa-times mr-1"></i>
                    Clear All
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                <!-- Padukuhan Filter -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        <i class="fas fa-map-pin mr-1 text-blue-500"></i>
                        Padukuhan
                    </label>
                    <select x-model="filters.padukuhan" @change="applyFilters()"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-aergas-orange focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white">
                        <option value="">Semua Padukuhan</option>
                        @foreach($padukuhanList as $padukuhan)
                            <option value="{{ $padukuhan }}">{{ $padukuhan }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Kelurahan Filter -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        <i class="fas fa-building mr-1 text-green-500"></i>
                        Kelurahan
                    </label>
                    <select x-model="filters.kelurahan" @change="applyFilters()"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-aergas-orange focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white">
                        <option value="">Semua Kelurahan</option>
                        @foreach($kelurahanList as $kelurahan)
                            <option value="{{ $kelurahan }}">{{ $kelurahan }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Jenis Pelanggan Filter -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        <i class="fas fa-users mr-1 text-purple-500"></i>
                        Jenis Pelanggan
                    </label>
                    <select x-model="filters.jenis_pelanggan" @change="applyFilters()"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-aergas-orange focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white">
                        <option value="">Semua Jenis</option>
                        <option value="pengembangan">Pengembangan</option>
                        <option value="penetrasi">Penetrasi</option>
                        <option value="on_the_spot">On The Spot</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        <i class="fas fa-calendar-alt mr-1 text-yellow-500"></i>
                        Dari Tanggal
                    </label>
                    <input type="date" x-model="filters.start_date" @change="applyFilters()"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-aergas-orange focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white">
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        <i class="fas fa-calendar-check mr-1 text-orange-500"></i>
                        Sampai Tanggal
                    </label>
                    <input type="date" x-model="filters.end_date" @change="applyFilters()"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-aergas-orange focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white">
                </div>
            </div>

            <!-- Filter Summary -->
            <div x-show="Object.values(filters).some(f => f !== '')" x-transition class="mt-6 p-4 bg-blue-50 rounded-xl border border-blue-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-info-circle text-blue-600"></i>
                        <span class="text-sm font-medium text-blue-800">Active Filters:</span>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="[key, value] in Object.entries(filters).filter(([k, v]) => v !== '')" :key="key">
                                <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
                                    <span x-text="key.replace('_', ' ') + ': ' + value"></span>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total Customers -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg border-0 p-6 text-white relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">Total Customers</p>
                            <p class="text-3xl font-bold mt-1" x-text="data.total_customers || {{ $totalCustomers }}">{{ $totalCustomers }}</p>
                            <p class="text-blue-200 text-xs mt-1">Registered in system</p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                            <i class="fas fa-users text-xl text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12"></div>
                <div class="absolute bottom-0 left-0 w-16 h-16 bg-white/5 rounded-full -ml-8 -mb-8"></div>
            </div>

            <!-- SK Evidence Count -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg border-0 p-6 text-white relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium">SK Evidence</p>
                            <div class="flex items-baseline space-x-2">
                                <p class="text-3xl font-bold mt-1" x-text="data.module_stats?.sk?.evidence_uploaded?.total || 0">0</p>
                                <span class="text-green-200 text-sm">uploaded</span>
                            </div>
                            <div class="mt-2">
                                <span class="bg-green-400/30 text-green-100 text-xs px-2 py-1 rounded-full">
                                    <span x-text="data.total_customers > 0 ? Math.round(((data.module_stats?.sk?.evidence_uploaded?.total || 0) / data.total_customers) * 100) : 0">0</span>% completion
                                </span>
                            </div>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                            <i class="fas fa-fire text-xl text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12"></div>
            </div>

            <!-- SR Evidence Count -->
            <div class="bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl shadow-lg border-0 p-6 text-white relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-yellow-100 text-sm font-medium">SR Evidence</p>
                            <div class="flex items-baseline space-x-2">
                                <p class="text-3xl font-bold mt-1" x-text="data.module_stats?.sr?.evidence_uploaded?.total || 0">0</p>
                                <span class="text-yellow-200 text-sm">uploaded</span>
                            </div>
                            <div class="mt-2">
                                <span class="bg-yellow-400/30 text-yellow-100 text-xs px-2 py-1 rounded-full">
                                    <span x-text="data.total_customers > 0 ? Math.round(((data.module_stats?.sr?.evidence_uploaded?.total || 0) / data.total_customers) * 100) : 0">0</span>% completion
                                </span>
                            </div>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                            <i class="fas fa-house-user text-xl text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12"></div>
            </div>

            <!-- Gas In Evidence Count -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg border-0 p-6 text-white relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm font-medium">Gas In Evidence</p>
                            <div class="flex items-baseline space-x-2">
                                <p class="text-3xl font-bold mt-1" x-text="data.module_stats?.gas_in?.evidence_uploaded?.total || 0">0</p>
                                <span class="text-purple-200 text-sm">uploaded</span>
                            </div>
                            <div class="mt-2">
                                <span class="bg-purple-400/30 text-purple-100 text-xs px-2 py-1 rounded-full">
                                    <span x-text="data.total_customers > 0 ? Math.round(((data.module_stats?.gas_in?.evidence_uploaded?.total || 0) / data.total_customers) * 100) : 0">0</span>% completion
                                </span>
                            </div>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                            <i class="fas fa-gas-pump text-xl text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12"></div>
            </div>
        </div>

        <!-- Customer Type & Progress Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- By Customer Type -->
            <div class="bg-white rounded-xl shadow-lg border-0 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Customer Types</h3>
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-pie text-white"></i>
                    </div>
                </div>
                <div class="space-y-3">
                    <template x-for="(stats, jenis) in data.stats_by_jenis || {}" :key="jenis">
                        <div class="group hover:bg-gray-50 rounded-lg p-4 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-4 h-4 rounded-full mr-4 border-2 border-white shadow-md"
                                         :class="{
                                             'bg-gradient-to-r from-blue-400 to-blue-600': jenis === 'pengembangan',
                                             'bg-gradient-to-r from-green-400 to-green-600': jenis === 'penetrasi',
                                             'bg-gradient-to-r from-purple-400 to-purple-600': jenis === 'on_the_spot'
                                         }"></div>
                                    <div>
                                        <span class="font-semibold text-gray-900 capitalize" x-text="jenis.replace('_', ' ')"></span>
                                        <div class="text-sm text-gray-500" x-text="'Customer category'"></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-2xl font-bold text-gray-900" x-text="stats.total"></span>
                                    <div class="text-sm text-gray-500">customers</div>
                                </div>
                            </div>
                            <div class="mt-3 bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all duration-500"
                                     :class="{
                                         'bg-gradient-to-r from-blue-400 to-blue-600': jenis === 'pengembangan',
                                         'bg-gradient-to-r from-green-400 to-green-600': jenis === 'penetrasi',
                                         'bg-gradient-to-r from-purple-400 to-purple-600': jenis === 'on_the_spot'
                                     }"
                                     :style="'width: ' + (data.total_customers > 0 ? Math.round((stats.total / data.total_customers) * 100) : 0) + '%'"></div>
                            </div>
                            <div class="mt-1 text-right">
                                <span class="text-xs text-gray-500" x-text="(data.total_customers > 0 ? Math.round((stats.total / data.total_customers) * 100) : 0) + '% of total'"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- By Progress Status -->
            <div class="bg-white rounded-xl shadow-lg border-0 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Progress Status</h3>
                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-teal-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-tasks text-white"></i>
                    </div>
                </div>
                <div class="space-y-3">
                    <template x-for="(stats, status) in data.progress_stats || {}" :key="status">
                        <div class="group hover:bg-gray-50 rounded-lg p-4 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-4 h-4 rounded-full mr-4 border-2 border-white shadow-md"
                                         :class="{
                                             'bg-gradient-to-r from-yellow-400 to-orange-500': status === 'validasi',
                                             'bg-gradient-to-r from-blue-400 to-blue-600': status === 'sk',
                                             'bg-gradient-to-r from-green-400 to-green-600': status === 'sr',
                                             'bg-gradient-to-r from-purple-400 to-purple-600': status === 'gas_in',
                                             'bg-gradient-to-r from-gray-400 to-gray-600': status === 'done',
                                             'bg-gradient-to-r from-red-400 to-red-600': status === 'batal'
                                         }"></div>
                                    <div>
                                        <span class="font-semibold text-gray-900 capitalize" x-text="status.replace('_', ' ')"></span>
                                        <div class="text-sm text-gray-500">Stage</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-2xl font-bold text-gray-900" x-text="stats.total"></span>
                                    <div class="text-sm text-gray-500">customers</div>
                                </div>
                            </div>
                            <div class="mt-3 bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all duration-500"
                                     :class="{
                                         'bg-gradient-to-r from-yellow-400 to-orange-500': status === 'validasi',
                                         'bg-gradient-to-r from-blue-400 to-blue-600': status === 'sk',
                                         'bg-gradient-to-r from-green-400 to-green-600': status === 'sr',
                                         'bg-gradient-to-r from-purple-400 to-purple-600': status === 'gas_in',
                                         'bg-gradient-to-r from-gray-400 to-gray-600': status === 'done',
                                         'bg-gradient-to-r from-red-400 to-red-600': status === 'batal'
                                     }"
                                     :style="'width: ' + (data.total_customers > 0 ? Math.round((stats.total / data.total_customers) * 100) : 0) + '%'"></div>
                            </div>
                            <div class="mt-1 text-right">
                                <span class="text-xs text-gray-500" x-text="(data.total_customers > 0 ? Math.round((stats.total / data.total_customers) * 100) : 0) + '% of total'"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Detailed Breakdown by Padukuhan -->
        <div class="bg-white rounded-xl shadow-lg border-0 p-6">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900">Regional Breakdown</h3>
                    <p class="text-gray-600 mt-1">Evidence upload statistics by Padukuhan & Customer Type</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-map-marked-alt text-white text-lg"></i>
                </div>
            </div>

            <template x-for="(padukuhanData, padukuhan) in data.breakdown || {}" :key="padukuhan">
                <div class="mb-10 border border-gray-100 rounded-xl p-6 bg-gradient-to-r from-gray-50 to-white shadow-sm">
                    <div class="flex items-center mb-6 bg-white rounded-lg p-4 shadow-sm">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-map-pin text-white"></i>
                        </div>
                        <div>
                            <h4 class="text-xl font-bold text-gray-800" x-text="padukuhan || 'No Padukuhan Specified'"></h4>
                            <p class="text-sm text-gray-600">Regional distribution by customer type</p>
                        </div>
                    </div>

                    <!-- Customer Type Breakdown for this Padukuhan -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <template x-for="(jenisData, jenis) in padukuhanData" :key="jenis">
                            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-lg mr-3"
                                             :class="{
                                                 'bg-gradient-to-br from-blue-400 to-blue-600': jenis === 'pengembangan',
                                                 'bg-gradient-to-br from-green-400 to-green-600': jenis === 'penetrasi',
                                                 'bg-gradient-to-br from-purple-400 to-purple-600': jenis === 'on_the_spot'
                                             }">
                                        </div>
                                        <div>
                                            <h5 class="font-bold text-gray-900 capitalize text-lg" x-text="jenis.replace('_', ' ')"></h5>
                                            <p class="text-xs text-gray-500">Customer Type</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-gray-900" x-text="jenisData.total"></div>
                                        <div class="text-xs text-gray-500">customers</div>
                                    </div>
                                </div>

                                <!-- Evidence Upload Summary -->
                                <div class="space-y-4">
                                    <!-- SK Evidence -->
                                    <div class="bg-green-50 rounded-lg p-3">
                                        <div class="flex justify-between items-center mb-2">
                                            <div class="flex items-center">
                                                <i class="fas fa-fire text-green-600 mr-2"></i>
                                                <span class="text-sm font-semibold text-green-800">SK Evidence</span>
                                            </div>
                                            <span class="text-lg font-bold text-green-700" x-text="jenisData.evidence_counts.sk + '/' + jenisData.total"></span>
                                        </div>
                                        <div class="w-full bg-green-200 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full transition-all duration-500"
                                                 :style="'width: ' + (jenisData.total > 0 ? Math.round((jenisData.evidence_counts.sk / jenisData.total) * 100) : 0) + '%'"></div>
                                        </div>
                                        <div class="text-right mt-1">
                                            <span class="text-xs text-green-600 font-medium" x-text="(jenisData.total > 0 ? Math.round((jenisData.evidence_counts.sk / jenisData.total) * 100) : 0) + '% complete'"></span>
                                        </div>
                                    </div>

                                    <!-- SR Evidence -->
                                    <div class="bg-yellow-50 rounded-lg p-3">
                                        <div class="flex justify-between items-center mb-2">
                                            <div class="flex items-center">
                                                <i class="fas fa-house-user text-yellow-600 mr-2"></i>
                                                <span class="text-sm font-semibold text-yellow-800">SR Evidence</span>
                                            </div>
                                            <span class="text-lg font-bold text-yellow-700" x-text="jenisData.evidence_counts.sr + '/' + jenisData.total"></span>
                                        </div>
                                        <div class="w-full bg-yellow-200 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-yellow-500 to-orange-500 h-2 rounded-full transition-all duration-500"
                                                 :style="'width: ' + (jenisData.total > 0 ? Math.round((jenisData.evidence_counts.sr / jenisData.total) * 100) : 0) + '%'"></div>
                                        </div>
                                        <div class="text-right mt-1">
                                            <span class="text-xs text-yellow-600 font-medium" x-text="(jenisData.total > 0 ? Math.round((jenisData.evidence_counts.sr / jenisData.total) * 100) : 0) + '% complete'"></span>
                                        </div>
                                    </div>

                                    <!-- Gas In Evidence -->
                                    <div class="bg-purple-50 rounded-lg p-3">
                                        <div class="flex justify-between items-center mb-2">
                                            <div class="flex items-center">
                                                <i class="fas fa-gas-pump text-purple-600 mr-2"></i>
                                                <span class="text-sm font-semibold text-purple-800">Gas In Evidence</span>
                                            </div>
                                            <span class="text-lg font-bold text-purple-700" x-text="jenisData.evidence_counts.gas_in + '/' + jenisData.total"></span>
                                        </div>
                                        <div class="w-full bg-purple-200 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-2 rounded-full transition-all duration-500"
                                                 :style="'width: ' + (jenisData.total > 0 ? Math.round((jenisData.evidence_counts.gas_in / jenisData.total) * 100) : 0) + '%'"></div>
                                        </div>
                                        <div class="text-right mt-1">
                                            <span class="text-xs text-purple-600 font-medium" x-text="(jenisData.total > 0 ? Math.round((jenisData.evidence_counts.gas_in / jenisData.total) * 100) : 0) + '% complete'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Empty State -->
            <div x-show="Object.keys(data.breakdown || {}).length === 0" class="text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-chart-bar text-gray-400 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Data Available</h3>
                <p class="text-gray-500">Apply filters or refresh to load regional breakdown data.</p>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div x-show="loading" x-transition class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin h-6 w-6 text-aergas-orange" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-900 font-medium">Loading...</span>
        </div>
    </div>
</div>

<script>
function reportDashboard() {
    return {
        loading: false,
        data: {
            total_customers: {{ $totalCustomers ?? 0 }},
            stats_by_jenis: @json($statsByJenis ?? collect()),
            progress_stats: @json($progressStats ?? collect()),
            module_stats: @json($moduleStats ?? []),
            photo_stats: @json($photoStats ?? []),
            breakdown: @json($breakdown ?? collect())
        },
        filters: @json($filters),

        async applyFilters() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value) params.append(key, value);
                });
                params.append('ajax', '1');

                const response = await fetch(`{{ route('reports.dashboard') }}?${params.toString()}`);
                const result = await response.json();

                if (result.success) {
                    this.data = result.data;
                }
            } catch (error) {
                console.error('Filter error:', error);
            } finally {
                this.loading = false;
            }
        },

        async refreshData() {
            this.loading = true;
            try {
                await this.applyFilters();
            } finally {
                this.loading = false;
            }
        },

        async exportData() {
            try {
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value) params.append(key, value);
                });

                window.open(`{{ route('reports.export') }}?${params.toString()}`, '_blank');
            } catch (error) {
                console.error('Export error:', error);
            }
        }
    }
}
</script>
@endsection