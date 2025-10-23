@extends('layouts.app')

@section('title', 'Dashboard AERGAS')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6" x-data="dashboardData()">

   <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center space-y-4 lg:space-y-0">
       <div>
           <h1 class="text-3xl font-bold text-gray-900">Dashboard AERGAS</h1>
           <p class="text-gray-600 mt-1">
               Selamat datang kembali, <span class="font-semibold text-aergas-navy">{{ auth()->user()->name }}</span>!
               @php
                   $userRoles = auth()->user()->getAllActiveRoles();
               @endphp
               @foreach($userRoles as $role)
                   <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-aergas-orange/10 text-aergas-orange mr-1">
                       {{ ucfirst(str_replace('_', ' ', $role)) }}
                   </span>
               @endforeach
           </p>
       </div>
       <div class="flex items-center space-x-3">
           <div class="text-sm text-gray-500">
               <i class="fas fa-clock mr-1"></i>
               Last updated: <span x-text="lastUpdated">{{ now()->format('H:i:s') }}</span>
           </div>
           <button @click="refreshData()"
                   :disabled="loading"
                   class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-aergas-navy to-aergas-orange text-white rounded-lg hover:shadow-lg transition-all duration-300 disabled:opacity-50">
               <i class="fas fa-sync-alt" :class="{ 'animate-spin': loading }"></i>
               <span x-text="loading ? 'Refreshing...' : 'Refresh'">Refresh</span>
           </button>
       </div>
   </div>


   <!-- Maps Section -->
   @include('components.maps-view')

   <!-- Installation Trend Chart -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6 space-y-4 lg:space-y-0">
            <h2 class="text-xl font-semibold text-gray-900">Installation Trend</h2>

            <!-- Chart Controls -->
            <div class="flex items-center space-x-3">
                <select x-model="chartPeriod" @change="updateChart()"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>

                <select x-model="chartDays" @change="updateChart()"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="365">Last year</option>
                </select>

                <select x-model="chartModule" @change="updateChart()"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                    <option value="all">All Modules</option>
                    <option value="sk">SK Only</option>
                    <option value="sr">SR Only</option>
                    <option value="gas_in">Gas In Only</option>
                </select>
            </div>
        </div>

        <div class="relative h-80">
            <canvas id="installationChart" class="w-full h-full"></canvas>
            <div x-show="chartLoading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75">
                <div class="flex items-center space-x-2 text-gray-600">
                    <i class="fas fa-spinner animate-spin"></i>
                    <span>Loading chart...</span>
                </div>
            </div>
        </div>
    </div>

   <!-- Pipe Length Statistics Card -->
   <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl shadow-lg p-6 border-2 border-blue-200">
       <div class="flex items-center justify-between mb-4">
           <div class="flex items-center space-x-3">
               <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center shadow-md">
                   <i class="fas fa-ruler-horizontal text-white text-xl"></i>
               </div>
               <div>
                   <h2 class="text-xl font-bold text-gray-900">Panjang Pipa 1/2" GL Medium</h2>
                   <p class="text-sm text-gray-600">Rata-rata dari semua SK yang terupload</p>
               </div>
           </div>
       </div>

       <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-4">
           <!-- Average Length -->
           <div class="bg-white rounded-lg p-5 shadow-sm">
               <div class="flex items-center justify-between">
                   <div>
                       <p class="text-sm font-medium text-gray-600 mb-1">Rata-rata Panjang</p>
                       <div class="flex items-baseline space-x-2">
                           <span class="text-3xl font-bold text-blue-600" x-text="(data.pipe_stats?.average_length || 0).toFixed(2)">0</span>
                           <span class="text-lg text-gray-500">meter</span>
                       </div>
                   </div>
                   <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                       <i class="fas fa-ruler text-blue-600"></i>
                   </div>
               </div>
           </div>

           <!-- Total Length -->
           <div class="bg-white rounded-lg p-5 shadow-sm">
               <div class="flex items-center justify-between">
                   <div>
                       <p class="text-sm font-medium text-gray-600 mb-1">Total Panjang Pipa</p>
                       <div class="flex items-baseline space-x-2">
                           <span class="text-3xl font-bold text-purple-600" x-text="(data.pipe_stats?.total_length || 0).toFixed(2)">0</span>
                           <span class="text-lg text-gray-500">meter</span>
                       </div>
                   </div>
                   <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                       <i class="fas fa-calculator text-purple-600"></i>
                   </div>
               </div>
           </div>

           <!-- Total SK -->
           <div class="bg-white rounded-lg p-5 shadow-sm" x-data="{ showTooltip: false }">
               <div class="flex items-center justify-between">
                   <div class="flex-1">
                       <div class="flex items-center space-x-2 mb-1">
                           <p class="text-sm font-medium text-gray-600">SK dengan Data Pipa</p>
                           <div class="relative">
                               <i class="fas fa-info-circle text-gray-400 text-xs cursor-help"
                                  @mouseenter="showTooltip = true"
                                  @mouseleave="showTooltip = false"></i>
                               <div x-show="showTooltip"
                                    x-transition
                                    class="absolute left-0 bottom-full mb-2 w-64 p-3 bg-gray-800 text-white text-xs rounded-lg shadow-lg z-10"
                                    style="display: none;">
                                   <p class="font-semibold mb-1">Hanya SK yang sudah mengisi data panjang pipa</p>
                                   <p class="text-gray-300">SK yang belum mengisi tidak dihitung dalam statistik ini</p>
                               </div>
                           </div>
                       </div>
                       <div class="flex items-baseline space-x-2">
                           <span class="text-3xl font-bold text-green-600" x-text="data.pipe_stats?.total_sk || 0">0</span>
                           <span class="text-lg text-gray-500">SK</span>
                       </div>
                   </div>
                   <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                       <i class="fas fa-file-alt text-green-600"></i>
                   </div>
               </div>
           </div>

           <!-- Exceed Threshold -->
           <div class="bg-white rounded-lg p-5 shadow-sm cursor-pointer hover:shadow-md transition-shadow"
                :class="(data.pipe_stats?.exceed_threshold || 0) > 0 ? 'ring-2 ring-orange-400' : ''"
                @click="(data.pipe_stats?.exceed_threshold || 0) > 0 ? showPipeExceedModal() : null">
               <div class="flex items-center justify-between">
                   <div>
                       <p class="text-sm font-medium text-gray-600 mb-1">Melebihi 15m</p>
                       <div class="flex items-baseline space-x-2">
                           <span class="text-3xl font-bold"
                                 :class="(data.pipe_stats?.exceed_threshold || 0) > 0 ? 'text-orange-600' : 'text-gray-600'"
                                 x-text="data.pipe_stats?.exceed_threshold || 0">0</span>
                           <span class="text-lg text-gray-500">SK</span>
                       </div>
                   </div>
                   <div class="w-10 h-10 rounded-full flex items-center justify-center"
                        :class="(data.pipe_stats?.exceed_threshold || 0) > 0 ? 'bg-orange-100' : 'bg-gray-100'">
                       <i class="fas fa-exclamation-triangle"
                          :class="(data.pipe_stats?.exceed_threshold || 0) > 0 ? 'text-orange-600' : 'text-gray-600'"></i>
                   </div>
               </div>
               <template x-if="(data.pipe_stats?.exceed_threshold || 0) > 0">
                   <div class="mt-2 flex items-center justify-between">
                       <span class="text-xs text-orange-600 font-medium">
                           <i class="fas fa-info-circle"></i> Akan dikenakan biaya tambahan
                       </span>
                       <span class="text-xs text-blue-600 font-medium hover:underline">
                           <i class="fas fa-eye"></i> Lihat Detail
                       </span>
                   </div>
               </template>
           </div>
       </div>
   </div>

   <!-- Charts Section 1 - Photo Approvals, Customer Types, Module Status -->
   <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
       <!-- Photo Approval Status Chart -->
       <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-all duration-300">
           <div class="flex items-center justify-between mb-4">
               <div class="flex items-center space-x-2">
                   <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center">
                       <i class="fas fa-camera text-white text-sm"></i>
                   </div>
                   <div>
                       <h2 class="text-sm font-semibold text-gray-900">Photo Approvals</h2>
                       <div class="text-xs text-gray-500">Status Distribution</div>
                   </div>
               </div>
               <div class="text-xl font-bold text-purple-600" x-text="(data.photos?.total_photos || 0)">0</div>
           </div>
           <div class="relative h-64">
               <canvas id="photoApprovalChart" class="w-full h-full"></canvas>
               <div x-show="chartLoading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 rounded-lg">
                   <div class="flex items-center space-x-2 text-gray-600">
                       <i class="fas fa-spinner animate-spin text-sm"></i>
                       <span class="text-xs">Loading...</span>
                   </div>
               </div>
           </div>
       </div>

       <!-- Customer Types Chart -->
       <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-all duration-300">
           <div class="flex items-center justify-between mb-4">
               <div class="flex items-center space-x-2">
                   <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                       <i class="fas fa-users text-white text-sm"></i>
                   </div>
                   <div>
                       <h2 class="text-sm font-semibold text-gray-900">Customer Types</h2>
                       <div class="text-xs text-gray-500">Type Distribution</div>
                   </div>
               </div>
               <div class="text-xl font-bold text-blue-600" x-text="(data.totals?.total_customers || 0)">0</div>
           </div>
           <div class="relative h-64">
               <canvas id="customerTypesChart" class="w-full h-full"></canvas>
               <div x-show="chartLoading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 rounded-lg">
                   <div class="flex items-center space-x-2 text-gray-600">
                       <i class="fas fa-spinner animate-spin text-sm"></i>
                       <span class="text-xs">Loading...</span>
                   </div>
               </div>
           </div>
       </div>

       <!-- Module Completion Status Chart -->
       <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-all duration-300">
           <div class="flex items-center justify-between mb-4">
               <div class="flex items-center space-x-2">
                   <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                       <i class="fas fa-tasks text-white text-sm"></i>
                   </div>
                   <div>
                       <h2 class="text-sm font-semibold text-gray-900">Module Status</h2>
                       <div class="text-xs text-gray-500">Completion Rate</div>
                   </div>
               </div>
               <div class="text-xl font-bold text-green-600" x-text="((data.totals?.completion_rate || 0) + '%')">0%</div>
           </div>
           <div class="relative h-64">
               <canvas id="moduleStatusChart" class="w-full h-full"></canvas>
               <div x-show="chartLoading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 rounded-lg">
                   <div class="flex items-center space-x-2 text-gray-600">
                       <i class="fas fa-spinner animate-spin text-sm"></i>
                       <span class="text-xs">Loading...</span>
                   </div>
               </div>
           </div>
       </div>
   </div>

   <!-- Charts Section 2 - Total Modules & All Modules Distribution (1:2 proportion) -->
   <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
       <!-- Total Module Chart - 1/3 width -->
       <div class="lg:col-span-1 bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-all duration-300">
           <div class="flex items-center justify-between mb-4">
               <div class="flex items-center space-x-2">
                   <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg flex items-center justify-center">
                       <i class="fas fa-chart-pie text-white text-sm"></i>
                   </div>
                   <div>
                       <h2 class="text-sm font-semibold text-gray-900">Total Modules</h2>
                       <div class="text-xs text-gray-500">All Modules</div>
                   </div>
               </div>
               <div class="text-xl font-bold text-indigo-600" x-text="((data.modules?.sk?.total || 0) + (data.modules?.sr?.total || 0) + (data.modules?.gas_in?.total || 0))">0</div>
           </div>
           <div class="relative h-80">
               <canvas id="totalModuleChart" class="w-full h-full"></canvas>
               <div x-show="chartLoading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 rounded-lg">
                   <div class="flex items-center space-x-2 text-gray-600">
                       <i class="fas fa-spinner animate-spin text-sm"></i>
                       <span class="text-xs">Loading...</span>
                   </div>
               </div>
           </div>
       </div>

       <!-- Combined Module Chart - 2/3 width -->
       <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-all duration-300">
           <div class="flex items-center justify-between mb-4">
               <div class="flex items-center space-x-2">
                   <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg flex items-center justify-center">
                       <i class="fas fa-layer-group text-white text-sm"></i>
                   </div>
                   <div>
                       <h2 class="text-sm font-semibold text-gray-900">All Modules Distribution</h2>
                       <div class="text-xs text-gray-500">SK, SR, and Gas In Modules</div>
                   </div>
               </div>
           </div>
           <div class="relative h-80">
               <canvas id="combinedModuleChart" class="w-full h-full"></canvas>
               <div x-show="chartLoading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 rounded-lg">
                   <div class="flex items-center space-x-2 text-gray-600">
                       <i class="fas fa-spinner animate-spin"></i>
                       <span class="text-xs">Loading...</span>
                   </div>
               </div>
           </div>
       </div>
   </div>

   <!-- Pipe Exceed Detail Modal -->
   <div x-show="pipeExceedModalOpen"
        x-cloak
        @keydown.escape.window="pipeExceedModalOpen = false"
        class="fixed inset-0 z-[1200] overflow-y-auto"
        style="display: none;">
       <!-- Backdrop -->
       <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
            @click="pipeExceedModalOpen = false"></div>

       <!-- Modal Content -->
       <div class="flex min-h-full items-center justify-center p-4">
           <div class="relative bg-white rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden"
                @click.stop>
               <!-- Header -->
               <div class="bg-gradient-to-r from-orange-500 to-red-600 px-6 py-4 flex items-center justify-between">
                   <div class="flex items-center space-x-3">
                       <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                           <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                       </div>
                       <div>
                           <h3 class="text-xl font-bold text-white">SK dengan Pipa Melebihi 15 Meter</h3>
                           <p class="text-sm text-orange-100">Customer yang akan dikenakan biaya tambahan</p>
                       </div>
                   </div>
                   <button @click="pipeExceedModalOpen = false"
                           class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-colors">
                       <i class="fas fa-times text-xl"></i>
                   </button>
               </div>

               <!-- Loading State -->
               <div x-show="pipeExceedLoading" class="p-8 flex items-center justify-center">
                   <div class="flex flex-col items-center space-y-3">
                       <i class="fas fa-spinner fa-spin text-4xl text-orange-600"></i>
                       <p class="text-gray-600">Memuat data...</p>
                   </div>
               </div>

               <!-- Content -->
               <div x-show="!pipeExceedLoading" class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                   <!-- Summary -->
                   <div class="mb-6 bg-orange-50 border border-orange-200 rounded-lg p-4">
                       <div class="flex items-center justify-between">
                           <div>
                               <p class="text-sm text-gray-600">Total SK yang melebihi threshold</p>
                               <p class="text-2xl font-bold text-orange-600" x-text="pipeExceedData.length">0</p>
                           </div>
                           <div class="text-right">
                               <p class="text-sm text-gray-600">Threshold</p>
                               <p class="text-2xl font-bold text-gray-800">15 meter</p>
                           </div>
                       </div>
                   </div>

                   <!-- Table -->
                   <div class="overflow-x-auto">
                       <table class="min-w-full divide-y divide-gray-200">
                           <thead class="bg-gray-50">
                               <tr>
                                   <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                   <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reff ID</th>
                                   <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pelanggan</th>
                                   <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat</th>
                                   <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelurahan</th>
                                   <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Telepon</th>
                                   <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Panjang Pipa</th>
                                   <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelebihan</th>
                                   <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                               </tr>
                           </thead>
                           <tbody class="bg-white divide-y divide-gray-200">
                               <template x-for="(item, index) in pipeExceedData" :key="item.id">
                                   <tr class="hover:bg-gray-50 transition-colors">
                                       <td class="px-4 py-3 text-sm text-gray-900" x-text="index + 1"></td>
                                       <td class="px-4 py-3 text-sm">
                                           <a :href="`/customers/${item.reff_id}`"
                                              class="text-blue-600 hover:underline font-medium"
                                              x-text="item.reff_id">
                                           </a>
                                       </td>
                                       <td class="px-4 py-3 text-sm text-gray-900" x-text="item.nama_pelanggan"></td>
                                       <td class="px-4 py-3 text-sm text-gray-600" x-text="item.alamat"></td>
                                       <td class="px-4 py-3 text-sm text-gray-600" x-text="item.kelurahan"></td>
                                       <td class="px-4 py-3 text-sm text-gray-600" x-text="item.no_telepon"></td>
                                       <td class="px-4 py-3 text-sm">
                                           <span class="font-bold text-orange-600" x-text="item.panjang_pipa + ' m'"></span>
                                       </td>
                                       <td class="px-4 py-3 text-sm">
                                           <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                               <i class="fas fa-arrow-up mr-1"></i>
                                               <span x-text="'+' + item.excess_length + ' m'"></span>
                                           </span>
                                       </td>
                                       <td class="px-4 py-3 text-sm text-gray-600" x-text="item.tanggal_instalasi"></td>
                                   </tr>
                               </template>
                           </tbody>
                       </table>
                   </div>

                   <!-- Empty State -->
                   <div x-show="pipeExceedData.length === 0 && !pipeExceedLoading"
                        class="text-center py-12">
                       <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                       <p class="text-gray-500">Tidak ada data SK yang melebihi 15 meter</p>
                   </div>
               </div>
           </div>
       </div>
   </div>

   <!-- Quick Actions Overlay -->
   <div x-data="{ quickActionsOpen: false }" class="fixed bottom-6 right-6 z-[1100]">
       <!-- Quick Actions Menu -->
       <div x-show="quickActionsOpen"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="absolute bottom-20 right-0 bg-transparent"
            style="display: none;">

           <div class="flex flex-col items-end space-y-2 p-2">
               @if(auth()->user()->hasAnyRole(['admin', 'tracer', 'super_admin']))
               <div @click="window.location.href='{{ route('customers.create') }}'"
                    class="flex items-center justify-end space-x-3 cursor-pointer transition-all duration-200 group">
                   <span class="text-sm font-medium text-gray-800 bg-white/90 px-4 py-2 rounded-lg shadow-lg whitespace-nowrap group-hover:bg-blue-50">Add Customer</span>
                   <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all group-hover:scale-110">
                       <i class="fas fa-user-plus text-white text-base"></i>
                   </div>
               </div>
               @endif

               <div @click="window.location.href='{{ route('customers.index') }}'"
                    class="flex items-center justify-end space-x-3 cursor-pointer transition-all duration-200 group">
                   <span class="text-sm font-medium text-gray-800 bg-white/90 px-4 py-2 rounded-lg shadow-lg whitespace-nowrap group-hover:bg-gray-50">View Customers</span>
                   <div class="w-12 h-12 bg-gradient-to-br from-gray-500 to-gray-600 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all group-hover:scale-110">
                       <i class="fas fa-list text-white text-base"></i>
                   </div>
               </div>

               @if(auth()->user()->hasAnyRole(['sk', 'tracer', 'admin', 'super_admin']))
               <div @click="window.location.href='{{ route('sk.create') }}'"
                    class="flex items-center justify-end space-x-3 cursor-pointer transition-all duration-200 group">
                   <span class="text-sm font-medium text-gray-800 bg-white/90 px-4 py-2 rounded-lg shadow-lg whitespace-nowrap group-hover:bg-green-50">Create SK</span>
                   <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all group-hover:scale-110">
                       <i class="fas fa-fire text-white text-base"></i>
                   </div>
               </div>

               <div @click="window.location.href='{{ route('sk.index') }}'"
                    class="flex items-center justify-end space-x-3 cursor-pointer transition-all duration-200 group">
                   <span class="text-sm font-medium text-gray-800 bg-white/90 px-4 py-2 rounded-lg shadow-lg whitespace-nowrap group-hover:bg-green-50">SK Module</span>
                   <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all group-hover:scale-110">
                       <i class="fas fa-tasks text-white text-base"></i>
                   </div>
               </div>
               @endif

               @if(auth()->user()->hasAnyRole(['sr', 'tracer', 'admin', 'super_admin']))
               <div @click="window.location.href='{{ route('sr.create') }}'"
                    class="flex items-center justify-end space-x-3 cursor-pointer transition-all duration-200 group">
                   <span class="text-sm font-medium text-gray-800 bg-white/90 px-4 py-2 rounded-lg shadow-lg whitespace-nowrap group-hover:bg-yellow-50">Create SR</span>
                   <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all group-hover:scale-110">
                       <i class="fas fa-plus text-white text-base"></i>
                   </div>
               </div>

               <div @click="window.location.href='{{ route('sr.index') }}'"
                    class="flex items-center justify-end space-x-3 cursor-pointer transition-all duration-200 group">
                   <span class="text-sm font-medium text-gray-800 bg-white/90 px-4 py-2 rounded-lg shadow-lg whitespace-nowrap group-hover:bg-yellow-50">SR Module</span>
                   <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all group-hover:scale-110">
                       <i class="fas fa-route text-white text-base"></i>
                   </div>
               </div>
               @endif

               @if(auth()->user()->hasAnyRole(['gas_in', 'tracer', 'admin', 'super_admin']))
               <div @click="window.location.href='{{ route('gas-in.create') }}'"
                    class="flex items-center justify-end space-x-3 cursor-pointer transition-all duration-200 group">
                   <span class="text-sm font-medium text-gray-800 bg-white/90 px-4 py-2 rounded-lg shadow-lg whitespace-nowrap group-hover:bg-orange-50">Create Gas In</span>
                   <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all group-hover:scale-110">
                       <i class="fas fa-plus text-white text-base"></i>
                   </div>
               </div>

               <div @click="window.location.href='{{ route('gas-in.index') }}'"
                    class="flex items-center justify-end space-x-3 cursor-pointer transition-all duration-200 group">
                   <span class="text-sm font-medium text-gray-800 bg-white/90 px-4 py-2 rounded-lg shadow-lg whitespace-nowrap group-hover:bg-orange-50">Gas In Module</span>
                   <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all group-hover:scale-110">
                       <i class="fas fa-gas-pump text-white text-base"></i>
                   </div>
               </div>
               @endif

               @if(auth()->user()->hasAnyRole(['tracer', 'admin', 'super_admin']))
               <div @click="window.location.href='{{ route('photos.index') }}'"
                    class="flex items-center justify-end space-x-3 cursor-pointer transition-all duration-200 group">
                   <span class="text-sm font-medium text-gray-800 bg-white/90 px-4 py-2 rounded-lg shadow-lg whitespace-nowrap group-hover:bg-purple-50">Photo Review</span>
                   <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all group-hover:scale-110">
                       <i class="fas fa-clipboard-check text-white text-base"></i>
                   </div>
               </div>
               @endif
           </div>
       </div>

       <!-- Floating Action Button -->
       <button @click="quickActionsOpen = !quickActionsOpen"
               class="w-16 h-16 bg-gradient-to-br from-aergas-navy to-aergas-orange rounded-full shadow-2xl hover:shadow-3xl flex items-center justify-center transition-all duration-300 transform hover:scale-110 active:scale-95">
           <i class="fas fa-bolt text-white text-2xl" x-show="!quickActionsOpen"></i>
           <i class="fas fa-times text-white text-2xl" x-show="quickActionsOpen" style="display: none;"></i>
       </button>
   </div>
   {{-- Smart FAB akan ditampilkan otomatis oleh layout --}}

   @if(auth()->user()->hasAnyRole(['tracer', 'super_admin']))
   <div class="bg-gradient-to-r from-aergas-navy/5 to-aergas-orange/5 rounded-xl p-6 border border-aergas-orange/20">
       <h3 class="text-lg font-semibold text-gray-900 mb-4">Tracer Dashboard</h3>
       <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
           <div class="text-center">
               <div class="text-2xl font-bold text-aergas-navy" x-text="data.photos?.pending_tracer || 0">0</div>
               <div class="text-sm text-gray-600">Photos to Review</div>
           </div>
           <div class="text-center">
               <div class="text-2xl font-bold text-green-600" x-text="data.performance?.monthly_completion_rate || 0">0</div>
               <div class="text-sm text-gray-600">Monthly Rate</div>
           </div>
           <div class="text-center">
               <div class="text-2xl font-bold text-aergas-orange" x-text="data.performance?.sla_compliance?.tracer_violations || 0">0</div>
               <div class="text-sm text-gray-600">SLA Violations</div>
           </div>
       </div>
   </div>
   @endif

   @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
   <div class="bg-gradient-to-r from-aergas-navy/5 to-aergas-orange/5 rounded-xl p-6 border border-aergas-orange/20">
       <h3 class="text-lg font-semibold text-gray-900 mb-4">Admin Dashboard</h3>
       <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
           <div class="text-center">
               <div class="text-2xl font-bold text-aergas-navy" x-text="data.photos?.pending_cgp || 0">0</div>
               <div class="text-sm text-gray-600">CGP Reviews</div>
           </div>
           <div class="text-center">
               <div class="text-2xl font-bold text-green-600" x-text="data.photos?.approved || 0">0</div>
               <div class="text-sm text-gray-600">Total Approved</div>
           </div>
           <div class="text-center">
               <div class="text-2xl font-bold text-purple-600" x-text="data.photos?.approval_rate || 0">0%</div>
               <div class="text-sm text-gray-600">Approval Rate</div>
           </div>
           <div class="text-center">
               <div class="text-2xl font-bold text-aergas-orange" x-text="data.performance?.sla_compliance?.compliance_percentage || 0">0%</div>
               <div class="text-sm text-gray-600">SLA Compliance</div>
           </div>
       </div>
   </div>
   @endif

</div>


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script>
function dashboardData() {
    return {
        data: {
            totals: {},
            photos: {},
            modules: { module_details: { sk: {}, sr: {}, gas_in: {} } },
            activities: [],
            performance: { sla_compliance: {} },
            donut_stats: { photo_approval: {}, customer_types: {} },
            pipe_stats: { average_length: 0, total_length: 0, total_sk: 0, exceed_threshold: 0, threshold: 15 }
        },
        loading: false,
        lastUpdated: '{{ now()->format('H:i:s') }}',

        // Pipe exceed modal
        pipeExceedModalOpen: false,
        pipeExceedLoading: false,
        pipeExceedData: [],

        // Chart properties
        chartPeriod: 'daily',
        chartDays: 30,
        chartModule: 'all',
        chartLoading: false,
        installationChart: null,
        chartUpdateTimeout: null,

        // Additional chart instances
        photoApprovalChart: null,
        customerTypesChart: null,
        moduleStatusChart: null,
        totalModuleChart: null,
        combinedModuleChart: null,

        init() {
            this.loadData().then(() => {
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.initInstallationChart();
                        this.initDonutCharts();
                        this.initCombinedModuleChart();
                    }, 100);
                });
            });

            setInterval(() => {
                this.refreshData();
            }, 300000);
        },

        async loadData() {
            try {
                const response = await fetch('{{ route('dashboard.data') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    }
                });

                const result = await response.json();
                if (result.success) {
                    // Merge dengan default structure untuk menghindari undefined
                    this.data = {
                        totals: result.data.totals || {},
                        photos: result.data.photos || {},
                        modules: result.data.modules || {
                            module_details: { sk: {}, sr: {}, gas_in: {} },
                            pie_charts: {
                                completion_status: {
                                    labels: ['Completed', 'In Progress', 'Draft', 'Rejected'],
                                    data: [0, 0, 0, 0],
                                    colors: ['#10B981', '#F59E0B', '#6B7280', '#EF4444']
                                }
                            }
                        },
                        activities: result.data.activities || [],
                        performance: result.data.performance || { sla_compliance: {} },
                        donut_stats: result.data.donut_stats || {
                            photo_approval: {
                                labels: ['Approved', 'Pending CGP', 'Pending Tracer', 'AI Validation', 'Rejected'],
                                data: [0, 0, 0, 0, 0],
                                colors: ['#10B981', '#F59E0B', '#3B82F6', '#8B5CF6', '#EF4444']
                            },
                            customer_types: {
                                labels: ['Residential', 'Commercial', 'Industrial'],
                                data: [0, 0, 0],
                                colors: ['#6366F1', '#EC4899', '#14B8A6']
                            }
                        },
                        pipe_stats: result.data.pipe_stats || { average_length: 0, total_length: 0, total_sk: 0, exceed_threshold: 0, threshold: 15 }
                    };

                    // Merge actual data if available
                    if (result.data.modules?.pie_charts) {
                        this.data.modules.pie_charts = { ...this.data.modules.pie_charts, ...result.data.modules.pie_charts };
                    }
                    if (result.data.donut_stats) {
                        this.data.donut_stats = { ...this.data.donut_stats, ...result.data.donut_stats };
                    }

                    console.log('Dashboard data loaded:', this.data);
                }
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        },


        async refreshData() {
            this.loading = true;

            try {
                const response = await fetch('{{ route('dashboard.data') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    }
                });

                const result = await response.json();

                if (result.success) {
                    this.data = result.data;
                    this.lastUpdated = new Date().toLocaleTimeString('id-ID');

                    // Update charts after data refresh
                    this.$nextTick(() => {
                        this.updateDonutCharts();
                        this.updateTotalModuleChart();
                        this.updateCombinedModuleChart();
                    });

                    window.showToast('success', 'Dashboard updated successfully');
                } else {
                    throw new Error(result.message || 'Failed to refresh data');
                }
            } catch (error) {
                console.error('Error refreshing dashboard:', error);
                window.showToast('error', 'Failed to refresh dashboard');
            } finally {
                this.loading = false;
            }
        },

        async initInstallationChart() {
            const canvas = document.getElementById('installationChart');
            if (!canvas) return;

            // Create a simple empty chart first
            const ctx = canvas.getContext('2d');

            if (this.installationChart) {
                try {
                    this.installationChart.destroy();
                } catch (e) {
                    console.warn('Chart destroy error during init:', e);
                }
                this.installationChart = null;
            }

            this.installationChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 0 // Disable animations completely
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            cornerRadius: 8,
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Time Period'
                            }
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Installations'
                            },
                            beginAtZero: true
                        }
                    }
                }
            });

            // Load chart data after initialization
            this.updateChart();
        },

        async updateChart() {
            // Debounce chart updates to prevent rapid successive calls
            if (this.chartUpdateTimeout) {
                clearTimeout(this.chartUpdateTimeout);
            }

            this.chartUpdateTimeout = setTimeout(() => {
                this.performChartUpdate();
            }, 300);
        },

        async performChartUpdate() {
            if (this.chartLoading) return;

            this.chartLoading = true;

            try {
                const params = new URLSearchParams({
                    period: this.chartPeriod,
                    days: this.chartDays,
                    module: this.chartModule
                });

                const response = await fetch(`{{ route('dashboard.installation-trend') }}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    }
                });

                if (!response.ok) throw new Error('Network response was not ok');

                const result = await response.json();
                console.log('Chart data received:', result);

                if (result.success && result.data) {
                    // Always recreate chart instead of updating to avoid stack overflow
                    this.recreateChart(result.data);
                }
            } catch (error) {
                console.error('Error updating chart:', error);
                window.showToast && window.showToast('error', 'Failed to update chart');
            } finally {
                this.chartLoading = false;
            }
        },

        recreateChart(chartData) {
            try {
                // Safely destroy existing chart
                if (this.installationChart) {
                    try {
                        this.installationChart.destroy();
                    } catch (destroyError) {
                        console.warn('Chart destroy error (ignoring):', destroyError);
                    }
                    this.installationChart = null;
                }

                // Validate and sanitize chart data
                const labels = Array.isArray(chartData.labels) ? chartData.labels : [];
                const datasets = Array.isArray(chartData.datasets) ? chartData.datasets : [];

                // Ensure all datasets have proper data arrays and required properties
                const validatedDatasets = datasets.map(dataset => {
                    const validatedDataset = {
                        label: dataset.label || 'Unknown',
                        data: Array.isArray(dataset.data) ? dataset.data : [],
                        type: dataset.type || 'bar',
                        backgroundColor: dataset.backgroundColor || 'rgba(59, 130, 246, 0.8)',
                        borderColor: dataset.borderColor || '#3B82F6',
                        borderWidth: dataset.borderWidth || 1
                    };

                    // Add additional properties for line charts
                    if (dataset.type === 'line') {
                        validatedDataset.fill = dataset.fill !== undefined ? dataset.fill : false;
                        validatedDataset.tension = dataset.tension || 0.4;
                        validatedDataset.pointBackgroundColor = dataset.pointBackgroundColor || dataset.borderColor;
                        validatedDataset.pointBorderColor = dataset.pointBorderColor || '#fff';
                        validatedDataset.pointBorderWidth = dataset.pointBorderWidth || 2;
                        validatedDataset.pointRadius = dataset.pointRadius || 4;
                    }

                    return validatedDataset;
                });

                // Create new chart with validated data
                const canvas = document.getElementById('installationChart');
                if (!canvas) {
                    console.error('Chart canvas not found');
                    return;
                }

                const ctx = canvas.getContext('2d');

                this.installationChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: validatedDatasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 0 // Disable animations to prevent issues
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                cornerRadius: 8,
                            }
                        },
                        scales: {
                            x: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Time Period'
                                }
                            },
                            y: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Installations'
                                },
                                beginAtZero: true
                            }
                        }
                    }
                });

                console.log('Chart recreated successfully');
            } catch (error) {
                console.error('Error recreating chart:', error);
                this.installationChart = null;
            }
        },

        initDonutCharts() {
            setTimeout(() => {
                this.initPhotoApprovalChart();
            }, 200);
            setTimeout(() => {
                this.initCustomerTypesChart();
            }, 400);
            setTimeout(() => {
                this.initModuleStatusChart();
            }, 600);
            setTimeout(() => {
                this.initTotalModuleChart();
            }, 800);
        },

        initPhotoApprovalChart() {
            const canvas = document.getElementById('photoApprovalChart');
            if (!canvas) {
                console.warn('Photo approval chart canvas not found');
                return;
            }

            try {
                const ctx = canvas.getContext('2d');

                if (this.photoApprovalChart) {
                    this.photoApprovalChart.destroy();
                    this.photoApprovalChart = null;
                }

                const chartData = this.data.donut_stats?.photo_approval || {
                    labels: ['Approved', 'Pending CGP', 'Pending Tracer', 'AI Validation', 'Rejected'],
                    data: [0, 0, 0, 0, 0],
                    colors: ['#10B981', '#F59E0B', '#3B82F6', '#8B5CF6', '#EF4444']
                };

                this.photoApprovalChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            data: chartData.data,
                            backgroundColor: chartData.colors,
                            borderWidth: 2,
                            borderColor: '#fff',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true,
                                    font: {
                                        size: 11
                                    },
                                    boxWidth: 12
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        cutout: '65%',
                        animation: {
                            animateRotate: true,
                            duration: 1000
                        }
                    }
                });

                console.log('Photo approval chart initialized successfully');
            } catch (error) {
                console.error('Error initializing photo approval chart:', error);
            }
        },

        initCustomerTypesChart() {
            const canvas = document.getElementById('customerTypesChart');
            if (!canvas) {
                console.warn('Customer types chart canvas not found');
                return;
            }

            try {
                const ctx = canvas.getContext('2d');

                if (this.customerTypesChart) {
                    this.customerTypesChart.destroy();
                    this.customerTypesChart = null;
                }

                const chartData = this.data.donut_stats?.customer_types || {
                    labels: ['Residential', 'Commercial', 'Industrial'],
                    data: [0, 0, 0],
                    colors: ['#6366F1', '#EC4899', '#14B8A6']
                };

                this.customerTypesChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            data: chartData.data,
                            backgroundColor: chartData.colors,
                            borderWidth: 2,
                            borderColor: '#fff',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true,
                                    font: {
                                        size: 11
                                    },
                                    boxWidth: 12
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        cutout: '65%',
                        animation: {
                            animateRotate: true,
                            duration: 1000
                        }
                    }
                });

                console.log('Customer types chart initialized successfully');
            } catch (error) {
                console.error('Error initializing customer types chart:', error);
            }
        },

        initModuleStatusChart() {
            const canvas = document.getElementById('moduleStatusChart');
            if (!canvas) {
                console.warn('Module status chart canvas not found');
                return;
            }

            try {
                const ctx = canvas.getContext('2d');

                if (this.moduleStatusChart) {
                    this.moduleStatusChart.destroy();
                    this.moduleStatusChart = null;
                }

                const chartData = this.data.modules?.pie_charts?.completion_status || {
                    labels: ['Completed', 'In Progress', 'Draft', 'Rejected'],
                    data: [0, 0, 0, 0],
                    colors: ['#10B981', '#F59E0B', '#6B7280', '#EF4444']
                };

                this.moduleStatusChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            data: chartData.data,
                            backgroundColor: chartData.colors,
                            borderWidth: 2,
                            borderColor: '#fff',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true,
                                    font: {
                                        size: 11
                                    },
                                    boxWidth: 12
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        cutout: '65%',
                        animation: {
                            animateRotate: true,
                            duration: 1000
                        }
                    }
                });

                console.log('Module status chart initialized successfully');
            } catch (error) {
                console.error('Error initializing module status chart:', error);
            }
        },

        updateDonutCharts() {
            setTimeout(() => this.updatePhotoApprovalChart(), 100);
            setTimeout(() => this.updateCustomerTypesChart(), 200);
            setTimeout(() => this.updateModuleStatusChart(), 300);
        },

        updatePhotoApprovalChart() {
            if (!this.photoApprovalChart) return;

            try {
                const chartData = this.data.donut_stats?.photo_approval || {
                    labels: ['Approved', 'Pending CGP', 'Pending Tracer', 'AI Validation', 'Rejected'],
                    data: [0, 0, 0, 0, 0],
                    colors: ['#10B981', '#F59E0B', '#3B82F6', '#8B5CF6', '#EF4444']
                };

                this.photoApprovalChart.data.labels = chartData.labels;
                this.photoApprovalChart.data.datasets[0].data = chartData.data;
                this.photoApprovalChart.data.datasets[0].backgroundColor = chartData.colors;

                this.photoApprovalChart.update('none');
            } catch (error) {
                console.error('Error updating photo approval chart:', error);
            }
        },

        updateCustomerTypesChart() {
            if (!this.customerTypesChart) return;

            try {
                const chartData = this.data.donut_stats?.customer_types || {
                    labels: ['Residential', 'Commercial', 'Industrial'],
                    data: [0, 0, 0],
                    colors: ['#6366F1', '#EC4899', '#14B8A6']
                };

                this.customerTypesChart.data.labels = chartData.labels;
                this.customerTypesChart.data.datasets[0].data = chartData.data;
                this.customerTypesChart.data.datasets[0].backgroundColor = chartData.colors;

                this.customerTypesChart.update('none');
            } catch (error) {
                console.error('Error updating customer types chart:', error);
            }
        },

        updateModuleStatusChart() {
            if (!this.moduleStatusChart) return;

            try {
                const chartData = this.data.modules?.pie_charts?.completion_status || {
                    labels: ['Completed', 'In Progress', 'Draft', 'Rejected'],
                    data: [0, 0, 0, 0],
                    colors: ['#10B981', '#F59E0B', '#6B7280', '#EF4444']
                };

                this.moduleStatusChart.data.labels = chartData.labels;
                this.moduleStatusChart.data.datasets[0].data = chartData.data;
                this.moduleStatusChart.data.datasets[0].backgroundColor = chartData.colors;

                this.moduleStatusChart.update('none');
            } catch (error) {
                console.error('Error updating module status chart:', error);
            }
        },

        initTotalModuleChart() {
            const canvas = document.getElementById('totalModuleChart');
            if (!canvas) {
                console.warn('Total module chart canvas not found');
                return;
            }

            try {
                const ctx = canvas.getContext('2d');

                if (this.totalModuleChart) {
                    this.totalModuleChart.destroy();
                    this.totalModuleChart = null;
                }

                const skTotal = this.data.modules?.sk?.total || 0;
                const srTotal = this.data.modules?.sr?.total || 0;
                const gasInTotal = this.data.modules?.gas_in?.total || 0;

                this.totalModuleChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['SK Module', 'SR Module', 'Gas In Module'],
                        datasets: [{
                            data: [skTotal, srTotal, gasInTotal],
                            backgroundColor: ['#10B981', '#F59E0B', '#F97316'], // Green, Yellow, Orange
                            borderWidth: 2,
                            borderColor: '#fff',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 10,
                                    usePointStyle: true,
                                    font: {
                                        size: 10
                                    },
                                    boxWidth: 10
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        cutout: '60%',
                        animation: {
                            animateRotate: true,
                            duration: 1000
                        }
                    }
                });

                console.log('Total module chart initialized successfully');
            } catch (error) {
                console.error('Error initializing total module chart:', error);
            }
        },

        initCombinedModuleChart() {
            const canvas = document.getElementById('combinedModuleChart');
            if (!canvas) {
                console.warn('Combined module chart canvas not found');
                return;
            }

            try {
                const ctx = canvas.getContext('2d');

                if (this.combinedModuleChart) {
                    this.combinedModuleChart.destroy();
                    this.combinedModuleChart = null;
                }

                // Get data for all modules
                const skData = this.data.modules?.sk || {};
                const srData = this.data.modules?.sr || {};
                const gasInData = this.data.modules?.gas_in || {};

                // Each module gets exactly 1/3 of the circle (equal segments)
                const segmentSize = 100; // Base size for each main segment (1/3)

                // Function to normalize data within a segment
                const normalizeSegment = (completed, inProgress, draft, rejected) => {
                    const total = completed + inProgress + draft + rejected;
                    if (total === 0) {
                        return [segmentSize/4, segmentSize/4, segmentSize/4, segmentSize/4];
                    }
                    return [
                        (completed / total) * segmentSize,
                        (inProgress / total) * segmentSize,
                        (draft / total) * segmentSize,
                        (rejected / total) * segmentSize
                    ];
                };

                // Normalize data for each module
                const skNormalized = normalizeSegment(
                    skData.completed || 0,
                    skData.in_progress || 0,
                    skData.draft || 0,
                    skData.rejected || 0
                );

                const srNormalized = normalizeSegment(
                    srData.completed || 0,
                    srData.in_progress || 0,
                    srData.draft || 0,
                    srData.rejected || 0
                );

                const gasInNormalized = normalizeSegment(
                    gasInData.completed || 0,
                    gasInData.in_progress || 0,
                    gasInData.draft || 0,
                    gasInData.rejected || 0
                );

                // Labels for legend
                const chartLabels = [
                    'SK - Completed', 'SK - In Progress', 'SK - Draft', 'SK - Rejected',
                    'SR - Completed', 'SR - In Progress', 'SR - Draft', 'SR - Rejected',
                    'Gas In - Completed', 'Gas In - In Progress', 'Gas In - Draft', 'Gas In - Rejected'
                ];

                // Store actual values for tooltip/legend
                const actualValues = [
                    skData.completed || 0, skData.in_progress || 0, skData.draft || 0, skData.rejected || 0,
                    srData.completed || 0, srData.in_progress || 0, srData.draft || 0, srData.rejected || 0,
                    gasInData.completed || 0, gasInData.in_progress || 0, gasInData.draft || 0, gasInData.rejected || 0
                ];

                // Color themes for each module
                const skColors = ['#059669', '#10B981', '#34D399', '#6EE7B7'];
                const srColors = ['#D97706', '#F59E0B', '#FBBF24', '#FCD34D'];
                const gasInColors = ['#EA580C', '#F97316', '#FB923C', '#FDBA74'];

                this.combinedModuleChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: chartLabels,
                        datasets: [
                            // SK Module Dataset
                            {
                                label: 'SK Module',
                                data: skNormalized,
                                actualValues: actualValues.slice(0, 4),
                                backgroundColor: skColors,
                                borderWidth: 0,
                                spacing: 0,
                                hoverOffset: 8
                            },
                            // SR Module Dataset
                            {
                                label: 'SR Module',
                                data: srNormalized,
                                actualValues: actualValues.slice(4, 8),
                                backgroundColor: srColors,
                                borderWidth: 0,
                                spacing: 0,
                                hoverOffset: 8
                            },
                            // Gas In Module Dataset
                            {
                                label: 'Gas In Module',
                                data: gasInNormalized,
                                actualValues: actualValues.slice(8, 12),
                                backgroundColor: gasInColors,
                                borderWidth: 0,
                                spacing: 0,
                                hoverOffset: 8
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'right',
                                align: 'start',
                                labels: {
                                    padding: 12,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    font: {
                                        size: 11
                                    },
                                    boxWidth: 10,
                                    boxHeight: 10,
                                    generateLabels: function(chart) {
                                        const allLabels = [];
                                        const allActualValues = [];

                                        // Collect all actual values from all datasets
                                        chart.data.datasets.forEach(dataset => {
                                            if (dataset.actualValues) {
                                                allActualValues.push(...dataset.actualValues);
                                            }
                                        });

                                        const totalActual = allActualValues.reduce((a, b) => a + b, 0);

                                        // Generate labels for all datasets
                                        let globalIndex = 0;
                                        chart.data.datasets.forEach((dataset, datasetIndex) => {
                                            const meta = chart.getDatasetMeta(datasetIndex);

                                            dataset.data.forEach((value, index) => {
                                                const actualValue = dataset.actualValues[index];
                                                const percentage = totalActual > 0 ? Math.round((actualValue / totalActual) * 100) : 0;
                                                const label = chart.data.labels[globalIndex];

                                                allLabels.push({
                                                    text: `${label}: ${actualValue} (${percentage}%)`,
                                                    fillStyle: dataset.backgroundColor[index],
                                                    strokeStyle: dataset.backgroundColor[index],
                                                    hidden: meta.data[index] ? meta.data[index].hidden : false,
                                                    datasetIndex: datasetIndex,
                                                    index: index
                                                });

                                                globalIndex++;
                                            });
                                        });

                                        return allLabels;
                                    }
                                },
                                onClick: function(e, legendItem, legend) {
                                    const chart = legend.chart;
                                    const datasetIndex = legendItem.datasetIndex;
                                    const index = legendItem.index;
                                    const meta = chart.getDatasetMeta(datasetIndex);

                                    if (meta.data[index]) {
                                        meta.data[index].hidden = !meta.data[index].hidden;
                                        chart.update();
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                cornerRadius: 8,
                                padding: 12,
                                callbacks: {
                                    label: function(context) {
                                        const datasetIndex = context.datasetIndex;
                                        const dataIndex = context.dataIndex;
                                        const actualValue = context.dataset.actualValues[dataIndex];

                                        // Calculate total from all datasets
                                        let totalActual = 0;
                                        context.chart.data.datasets.forEach(ds => {
                                            if (ds.actualValues) {
                                                totalActual += ds.actualValues.reduce((a, b) => a + b, 0);
                                            }
                                        });

                                        const percentage = totalActual > 0 ? Math.round((actualValue / totalActual) * 100) : 0;

                                        // Get label from global labels array
                                        let labelIndex = 0;
                                        for (let i = 0; i < datasetIndex; i++) {
                                            labelIndex += context.chart.data.datasets[i].data.length;
                                        }
                                        labelIndex += dataIndex;
                                        const label = context.chart.data.labels[labelIndex];

                                        return `${label}: ${actualValue} (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        cutout: '45%', // Reduced from 65% to make segments thicker
                        animation: {
                            animateRotate: true,
                            duration: 1200
                        }
                    }
                });

                console.log('Combined module chart initialized successfully');
            } catch (error) {
                console.error('Error initializing combined module chart:', error);
            }
        },

        updateTotalModuleChart() {
            if (!this.totalModuleChart) return;

            try {
                const skTotal = this.data.modules?.sk?.total || 0;
                const srTotal = this.data.modules?.sr?.total || 0;
                const gasInTotal = this.data.modules?.gas_in?.total || 0;

                this.totalModuleChart.data.datasets[0].data = [skTotal, srTotal, gasInTotal];
                this.totalModuleChart.update('none');
            } catch (error) {
                console.error('Error updating total module chart:', error);
            }
        },

        updateCombinedModuleChart() {
            if (!this.combinedModuleChart) return;

            try {
                const skData = this.data.modules?.sk || {};
                const srData = this.data.modules?.sr || {};
                const gasInData = this.data.modules?.gas_in || {};

                const segmentSize = 100;

                const normalizeSegment = (completed, inProgress, draft, rejected) => {
                    const total = completed + inProgress + draft + rejected;
                    if (total === 0) {
                        return [segmentSize/4, segmentSize/4, segmentSize/4, segmentSize/4];
                    }
                    return [
                        (completed / total) * segmentSize,
                        (inProgress / total) * segmentSize,
                        (draft / total) * segmentSize,
                        (rejected / total) * segmentSize
                    ];
                };

                const skNormalized = normalizeSegment(
                    skData.completed || 0,
                    skData.in_progress || 0,
                    skData.draft || 0,
                    skData.rejected || 0
                );

                const srNormalized = normalizeSegment(
                    srData.completed || 0,
                    srData.in_progress || 0,
                    srData.draft || 0,
                    srData.rejected || 0
                );

                const gasInNormalized = normalizeSegment(
                    gasInData.completed || 0,
                    gasInData.in_progress || 0,
                    gasInData.draft || 0,
                    gasInData.rejected || 0
                );

                const actualValues = [
                    skData.completed || 0, skData.in_progress || 0, skData.draft || 0, skData.rejected || 0,
                    srData.completed || 0, srData.in_progress || 0, srData.draft || 0, srData.rejected || 0,
                    gasInData.completed || 0, gasInData.in_progress || 0, gasInData.draft || 0, gasInData.rejected || 0
                ];

                // Update each dataset
                this.combinedModuleChart.data.datasets[0].data = skNormalized;
                this.combinedModuleChart.data.datasets[0].actualValues = actualValues.slice(0, 4);

                this.combinedModuleChart.data.datasets[1].data = srNormalized;
                this.combinedModuleChart.data.datasets[1].actualValues = actualValues.slice(4, 8);

                this.combinedModuleChart.data.datasets[2].data = gasInNormalized;
                this.combinedModuleChart.data.datasets[2].actualValues = actualValues.slice(8, 12);

                this.combinedModuleChart.update('none');
            } catch (error) {
                console.error('Error updating combined module chart:', error);
            }
        },

        /**
         * Show pipe exceed detail modal
         */
        async showPipeExceedModal() {
            this.pipeExceedModalOpen = true;
            this.pipeExceedLoading = true;
            this.pipeExceedData = [];

            try {
                const response = await fetch('{{ route('dashboard.pipe-exceed-detail') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    }
                });

                const result = await response.json();

                if (result.success) {
                    this.pipeExceedData = result.data;
                    console.log('Loaded pipe exceed data:', result.data.length, 'records');
                } else {
                    throw new Error(result.message || 'Failed to load data');
                }
            } catch (error) {
                console.error('Error loading pipe exceed detail:', error);
                window.showToast && window.showToast('error', 'Gagal memuat data detail');
                this.pipeExceedModalOpen = false;
            } finally {
                this.pipeExceedLoading = false;
            }
        }
    }
}
</script>
@endpush
@endsection
