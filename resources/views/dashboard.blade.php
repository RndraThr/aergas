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

   <!-- Summary Cards -->
   <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
       <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
           <div class="flex items-center justify-between">
               <div class="flex-1">
                   <div class="flex items-center space-x-3 mb-3">
                       <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                           <i class="fas fa-users text-white text-lg"></i>
                       </div>
                       <div>
                           <span class="text-gray-600 text-sm font-medium">Total Pelanggan</span>
                           <div class="text-2xl font-bold text-gray-900" x-text="data.totals?.total_customers || 0">0</div>
                       </div>
                   </div>
                   <a href="{{ route('customers.index') }}"
                      class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">
                       Lihat semua <i class="fas fa-arrow-right ml-1"></i>
                   </a>
               </div>
           </div>
       </div>

       <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
           <div class="flex items-center justify-between">
               <div class="flex-1">
                   <div class="flex items-center space-x-3 mb-3">
                       <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                           <i class="fas fa-check-circle text-white text-lg"></i>
                       </div>
                       <div>
                           <span class="text-gray-600 text-sm font-medium">Selesai</span>
                           <div class="text-2xl font-bold text-gray-900" x-text="data.totals?.done || 0">0</div>
                       </div>
                   </div>
                   <div class="text-green-600 text-sm">
                       <span class="font-medium" x-text="data.totals?.completion_rate || 0">0</span>% completion rate
                   </div>
               </div>
           </div>
       </div>

       <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
           <div class="flex items-center justify-between">
               <div class="flex-1">
                   <div class="flex items-center space-x-3 mb-3">
                       <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center shadow-lg">
                           <i class="fas fa-clock text-white text-lg"></i>
                       </div>
                       <div>
                           <span class="text-gray-600 text-sm font-medium">
                               @if(auth()->user()->hasAnyRole(['tracer', 'super_admin']))
                                   Pending Review
                               @elseif(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                                   CGP Review
                               @else
                                   Progress
                               @endif
                           </span>
                           <div class="text-2xl font-bold text-gray-900" x-text="data.totals?.in_progress || 0">0</div>
                       </div>
                   </div>
                   @if(auth()->user()->hasAnyRole(['tracer', 'admin', 'super_admin']))
                       <span class="text-yellow-600 text-sm font-medium">
                           @if(auth()->user()->hasAnyRole(['tracer', 'super_admin']))
                               Pending Review
                           @elseif(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                               CGP Review
                           @endif
                       </span>
                   @else
                       <span class="text-yellow-600 text-sm font-medium">In progress</span>
                   @endif
               </div>
           </div>
       </div>

       <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
           <div class="flex items-center justify-between">
               <div class="flex-1">
                   <div class="flex items-center space-x-3 mb-3">
                       <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                           <i class="fas fa-camera text-white text-lg"></i>
                       </div>
                       <div>
                           <span class="text-gray-600 text-sm font-medium">Photo Approved</span>
                           <div class="text-2xl font-bold text-gray-900" x-text="data.photos?.approved || 0">0</div>
                       </div>
                   </div>
                   <div class="flex items-center text-purple-600 text-sm">
                       <span class="font-medium" x-text="(data.photos?.approval_rate || 0) + '%'">0%</span>
                       <span class="text-gray-500 ml-1">approval rate</span>
                   </div>
               </div>
           </div>
       </div>
   </div>

   <!-- Modern Chart Section -->
   <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
       <!-- Module Distribution Pie Chart -->
       <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
           <div class="flex items-center justify-between mb-4">
               <h3 class="text-lg font-semibold text-gray-900">Module Distribution</h3>
               <div class="text-sm text-gray-500">Total Data</div>
           </div>
           <div class="relative h-64">
               <canvas id="moduleDistributionChart" class="w-full h-full"></canvas>
               <!-- Center Text for Donut -->
               <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                   <div class="text-2xl font-bold text-gray-700" x-text="(data.modules?.sk?.total || 0) + (data.modules?.sr?.total || 0) + (data.modules?.gas_in?.total || 0)">0</div>
                   <div class="text-sm text-gray-500 font-medium">Total Records</div>
               </div>
           </div>
           <div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
               <div class="flex flex-col items-center">
                   <div class="w-3 h-3 bg-red-500 rounded-full mb-1"></div>
                   <span class="text-gray-600">SK Module</span>
                   <span class="font-medium" x-text="data.modules?.sk?.total || 0">0</span>
               </div>
               <div class="flex flex-col items-center">
                   <div class="w-3 h-3 bg-green-500 rounded-full mb-1"></div>
                   <span class="text-gray-600">SR Module</span>
                   <span class="font-medium" x-text="data.modules?.sr?.total || 0">0</span>
               </div>
               <div class="flex flex-col items-center">
                   <div class="w-3 h-3 bg-blue-500 rounded-full mb-1"></div>
                   <span class="text-gray-600">Gas In</span>
                   <span class="font-medium" x-text="data.modules?.gas_in?.total || 0">0</span>
               </div>
           </div>
       </div>

       <!-- Completion Status Pie Chart -->
       <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
           <div class="flex items-center justify-between mb-4">
               <h3 class="text-lg font-semibold text-gray-900">Completion Status</h3>
               <div class="text-sm text-gray-500">Overall Progress</div>
           </div>
           <div class="relative h-64">
               <canvas id="completionStatusChart" class="w-full h-full"></canvas>
               <!-- Center Text for Donut -->
               <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                   <div class="text-2xl font-bold text-green-600" x-text="data.modules?.pie_charts?.completion_status?.data?.[0] || 0">0</div>
                   <div class="text-sm text-gray-500 font-medium">Completed</div>
               </div>
           </div>
           <div class="mt-4 grid grid-cols-2 gap-2 text-center text-xs">
               <div class="flex flex-col items-center">
                   <div class="w-3 h-3 bg-green-500 rounded-full mb-1"></div>
                   <span class="text-gray-600">Completed</span>
               </div>
               <div class="flex flex-col items-center">
                   <div class="w-3 h-3 bg-yellow-500 rounded-full mb-1"></div>
                   <span class="text-gray-600">In Progress</span>
               </div>
               <div class="flex flex-col items-center">
                   <div class="w-3 h-3 bg-gray-500 rounded-full mb-1"></div>
                   <span class="text-gray-600">Draft</span>
               </div>
               <div class="flex flex-col items-center">
                   <div class="w-3 h-3 bg-red-500 rounded-full mb-1"></div>
                   <span class="text-gray-600">Rejected</span>
               </div>
           </div>
       </div>

       <!-- Photo Approval Status -->
       <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
           <div class="flex items-center justify-between mb-4">
               <h3 class="text-lg font-semibold text-gray-900">Photo Approval</h3>
               <div class="text-sm text-gray-500">Status Overview</div>
           </div>
           <div class="relative h-64">
               <canvas id="photoApprovalChart" class="w-full h-full"></canvas>
               <!-- Center Text for Donut -->
               <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                   <div class="text-xl font-bold text-green-600" x-text="data.donut_stats?.photo_approval?.data?.[0] || 0">0</div>
                   <div class="text-sm text-gray-500 font-medium">Approved</div>
               </div>
           </div>
           <div class="mt-4 space-y-2 text-xs">
               <div class="flex items-center justify-between">
                   <div class="flex items-center">
                       <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                       <span class="text-gray-600">Approved</span>
                   </div>
                   <span class="font-medium" x-text="data.donut_stats?.photo_approval?.data?.[0] || 0">0</span>
               </div>
               <div class="flex items-center justify-between">
                   <div class="flex items-center">
                       <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                       <span class="text-gray-600">Pending</span>
                   </div>
                   <span class="font-medium" x-text="(data.donut_stats?.photo_approval?.data?.[1] || 0) + (data.donut_stats?.photo_approval?.data?.[2] || 0)">0</span>
               </div>
           </div>
       </div>
   </div>

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

   <!-- Module Status Cards - Moved Below -->
   <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
       <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow">
           <div class="flex items-center justify-between mb-4">
               <div class="flex items-center space-x-3">
                   <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-red-600 rounded-lg flex items-center justify-center">
                       <i class="fas fa-fire text-white"></i>
                   </div>
                   <h3 class="text-lg font-semibold text-gray-900">SK Module</h3>
               </div>
           </div>
           <div class="space-y-3">
               <div class="flex justify-between text-sm">
                   <span class="text-gray-600">Total SK:</span>
                   <span class="font-medium" x-text="data.modules?.sk?.total || 0">0</span>
               </div>
               <div class="flex justify-between text-sm">
                   <span class="text-gray-600">Draft:</span>
                   <span class="text-gray-500" x-text="data.modules?.sk?.draft || 0">0</span>
               </div>
               <div class="flex justify-between text-sm">
                   <span class="text-gray-600">Ready for Review:</span>
                   <span class="text-blue-600" x-text="data.modules?.sk?.ready || 0">0</span>
               </div>
               <div class="flex justify-between text-sm">
                   <span class="text-gray-600">Completed:</span>
                   <span class="text-green-600 font-semibold" x-text="data.modules?.sk?.completed || 0">0</span>
               </div>
           </div>
       </div>

       <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow">
           <div class="flex items-center justify-between mb-4">
               <div class="flex items-center space-x-3">
                   <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                       <i class="fas fa-route text-white"></i>
                   </div>
                   <h3 class="text-lg font-semibold text-gray-900">SR Module</h3>
               </div>
           </div>
           <div class="space-y-3">
               <div class="flex justify-between text-sm">
                   <span class="text-gray-600">Total SR:</span>
                   <span class="font-medium" x-text="data.modules?.sr?.total || 0">0</span>
               </div>
               <div class="flex justify-between text-sm">
                   <span class="text-gray-600">Draft:</span>
                   <span class="text-gray-500" x-text="data.modules?.sr?.draft || 0">0</span>
               </div>
               <div class="flex justify-between text-sm">
                   <span class="text-gray-600">Ready for Review:</span>
                   <span class="text-blue-600" x-text="data.modules?.sr?.ready || 0">0</span>
               </div>
               <div class="flex justify-between text-sm">
                   <span class="text-gray-600">Completed:</span>
                   <span class="text-green-600 font-semibold" x-text="data.modules?.sr?.completed || 0">0</span>
               </div>
           </div>
       </div>

       <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow">
           <div class="flex items-center justify-between mb-4">
               <div class="flex items-center space-x-3">
                   <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                       <i class="fas fa-gas-pump text-white"></i>
                   </div>
                   <h3 class="text-lg font-semibold text-gray-900">Gas In Module</h3>
               </div>
           </div>
           <div class="space-y-3">
               <div class="flex justify-between text-sm">
                   <span class="text-gray-600">Total Gas In:</span>
                   <span class="font-medium" x-text="data.modules?.gas_in?.total || 0">0</span>
               </div>
               <div class="flex justify-between text-sm">
                   <span class="text-gray-600">Draft:</span>
                   <span class="text-gray-500" x-text="data.modules?.gas_in?.draft || 0">0</span>
               </div>
               <div class="flex justify-between text-sm">
                   <span class="text-gray-600">Ready for Review:</span>
                   <span class="text-blue-600" x-text="data.modules?.gas_in?.ready || 0">0</span>
               </div>
               <div class="flex justify-between text-sm">
                   <span class="text-gray-600">Completed:</span>
                   <span class="text-green-600 font-semibold" x-text="data.modules?.gas_in?.completed || 0">0</span>
               </div>
           </div>
       </div>
   </div>

   <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
       <div class="flex items-center justify-between mb-6">
           <h2 class="text-xl font-semibold text-gray-900">Quick Actions</h2>
           <span class="text-sm text-gray-500">Choose an action to get started</span>
       </div>

       <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
           @if(auth()->user()->hasAnyRole(['admin', 'tracer', 'super_admin']))
           <div class="group p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl hover:from-blue-100 hover:to-blue-200 transition-all duration-300 cursor-pointer transform hover:scale-105"
                onclick="window.location.href='{{ route('customers.create') }}'">
               <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-3 group-hover:shadow-lg transition-all">
                   <i class="fas fa-user-plus text-white"></i>
               </div>
               <div class="text-sm font-semibold text-gray-900">Add Customer</div>
               <div class="text-xs text-gray-600 mt-1">Register new customer</div>
           </div>
           @endif

           <div class="group p-4 bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl hover:from-gray-100 hover:to-gray-200 transition-all duration-300 cursor-pointer transform hover:scale-105"
                onclick="window.location.href='{{ route('customers.index') }}'">
               <div class="w-10 h-10 bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl flex items-center justify-center mb-3 group-hover:shadow-lg transition-all">
                   <i class="fas fa-list text-white"></i>
               </div>
               <div class="text-sm font-semibold text-gray-900">View Customers</div>
               <div class="text-xs text-gray-600 mt-1">Browse customer list</div>
           </div>

           @if(auth()->user()->hasAnyRole(['sk', 'tracer', 'admin', 'super_admin']))
           <div class="group p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl hover:from-green-100 hover:to-green-200 transition-all duration-300 cursor-pointer transform hover:scale-105"
                onclick="window.location.href='{{ route('sk.create') }}'">
               <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center mb-3 group-hover:shadow-lg transition-all">
                   <i class="fas fa-fire text-white"></i>
               </div>
               <div class="text-sm font-semibold text-gray-900">Create SK</div>
               <div class="text-xs text-gray-600 mt-1">New SK document</div>
           </div>

           <div class="group p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl hover:from-green-100 hover:to-green-200 transition-all duration-300 cursor-pointer transform hover:scale-105"
                onclick="window.location.href='{{ route('sk.index') }}'">
               <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center mb-3 group-hover:shadow-lg transition-all">
                   <i class="fas fa-tasks text-white"></i>
               </div>
               <div class="text-sm font-semibold text-gray-900">SK Module</div>
               <div class="text-xs text-gray-600 mt-1">View & manage SK</div>
           </div>
           @endif

           @if(auth()->user()->hasAnyRole(['sr', 'tracer', 'admin', 'super_admin']))
           <div class="group p-4 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl hover:from-yellow-100 hover:to-yellow-200 transition-all duration-300 cursor-pointer transform hover:scale-105"
                onclick="window.location.href='{{ route('sr.create') }}'">
               <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center mb-3 group-hover:shadow-lg transition-all">
                   <i class="fas fa-plus text-white"></i>
               </div>
               <div class="text-sm font-semibold text-gray-900">Create SR</div>
               <div class="text-xs text-gray-600 mt-1">New Sambungan Rumah</div>
           </div>

           <div class="group p-4 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl hover:from-yellow-100 hover:to-yellow-200 transition-all duration-300 cursor-pointer transform hover:scale-105"
                onclick="window.location.href='{{ route('sr.index') }}'">
               <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center mb-3 group-hover:shadow-lg transition-all">
                   <i class="fas fa-route text-white"></i>
               </div>
               <div class="text-sm font-semibold text-gray-900">SR Module</div>
               <div class="text-xs text-gray-600 mt-1">Sambungan Rumah data</div>
           </div>
           @endif

           @if(auth()->user()->hasAnyRole(['gas_in', 'tracer', 'admin', 'super_admin']))
           <div class="group p-4 bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl hover:from-orange-100 hover:to-orange-200 transition-all duration-300 cursor-pointer transform hover:scale-105"
                onclick="window.location.href='{{ route('gas-in.create') }}'">
               <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center mb-3 group-hover:shadow-lg transition-all">
                   <i class="fas fa-plus text-white"></i>
               </div>
               <div class="text-sm font-semibold text-gray-900">Create Gas In</div>
               <div class="text-xs text-gray-600 mt-1">New Gas In entry</div>
           </div>

           <div class="group p-4 bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl hover:from-orange-100 hover:to-orange-200 transition-all duration-300 cursor-pointer transform hover:scale-105"
                onclick="window.location.href='{{ route('gas-in.index') }}'">
               <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center mb-3 group-hover:shadow-lg transition-all">
                   <i class="fas fa-gas-pump text-white"></i>
               </div>
               <div class="text-sm font-semibold text-gray-900">Gas In</div>
               <div class="text-xs text-gray-600 mt-1">Gas In data</div>
           </div>
           @endif

           @if(auth()->user()->hasAnyRole(['tracer', 'admin', 'super_admin']))
           <div class="group p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl hover:from-purple-100 hover:to-purple-200 transition-all duration-300 cursor-pointer transform hover:scale-105"
                onclick="window.location.href='{{ route('photos.index') }}'">
               <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-3 group-hover:shadow-lg transition-all">
                   <i class="fas fa-clipboard-check text-white"></i>
               </div>
               <div class="text-sm font-semibold text-gray-900">Photo Review</div>
               <div class="text-xs text-gray-600 mt-1">Approve photos</div>
           </div>
           @endif
       </div>
   </div>

   <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
       <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 h-fit">
           <div class="flex items-center justify-between mb-6">
               <h2 class="text-xl font-semibold text-gray-900">Module Statistics</h2>
               <div class="text-sm text-gray-500">Real-time data</div>
           </div>

           <div class="space-y-3">
               <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                   <div class="flex items-center space-x-3">
                       <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                           <i class="fas fa-fire text-white text-sm"></i>
                       </div>
                       <div>
                           <div class="font-medium text-gray-900">SK Module</div>
                           <div class="text-sm text-gray-600">Sambungan Kompor</div>
                       </div>
                   </div>
                   <div class="text-right">
                       <div class="text-lg font-bold text-gray-900" x-text="data.modules?.module_details?.sk?.completed || 0">0</div>
                       <div class="text-xs text-green-600">completed</div>
                   </div>
               </div>

               <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                   <div class="flex items-center space-x-3">
                       <div class="w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center">
                           <i class="fas fa-route text-white text-sm"></i>
                       </div>
                       <div>
                           <div class="font-medium text-gray-900">SR Module</div>
                           <div class="text-sm text-gray-600">Sambungan Rumah</div>
                       </div>
                   </div>
                   <div class="text-right">
                       <div class="text-lg font-bold text-gray-900" x-text="data.modules?.module_details?.sr?.completed || 0">0</div>
                       <div class="text-xs text-yellow-600">completed</div>
                   </div>
               </div>

               <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                   <div class="flex items-center space-x-3">
                       <div class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center">
                           <i class="fas fa-gas-pump text-white text-sm"></i>
                       </div>
                       <div>
                           <div class="font-medium text-gray-900">Gas In</div>
                           <div class="text-sm text-gray-600">Gas In</div>
                       </div>
                   </div>
                   <div class="text-right">
                       <div class="text-lg font-bold text-gray-900" x-text="data.modules?.module_details?.gas_in?.completed || 0">0</div>
                       <div class="text-xs text-orange-600">completed</div>
                   </div>
               </div>
           </div>
       </div>

       <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 h-fit">
           <div class="flex items-center justify-between mb-6">
               <h2 class="text-xl font-semibold text-gray-900">Recent Activities</h2>
               <a href="{{ route('notifications.index') }}"
                  class="text-aergas-orange hover:text-aergas-navy text-sm font-medium transition-colors">
                   View all
               </a>
           </div>

           <div class="space-y-3 max-h-72 overflow-y-auto">
               <template x-for="activity in data.activities?.slice(0, 5)" :key="activity.id">
                   <div class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded-lg transition-colors">
                       <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                           <i class="fas fa-circle text-xs text-gray-400"></i>
                       </div>
                       <div class="flex-1 min-w-0">
                           <p class="text-sm text-gray-900 font-medium" x-text="activity.description">Activity description</p>
                           <p class="text-xs text-gray-500 mt-1" x-text="activity.time_ago">Time ago</p>
                       </div>
                   </div>
               </template>

               <div x-show="!data.activities || data.activities.length === 0" class="text-center py-6 text-gray-500">
                   <i class="fas fa-clock text-2xl mb-2 text-gray-300"></i>
                   <p class="text-sm">No recent activities</p>
                   <p class="text-xs text-gray-400 mt-1">Activities will appear here when available</p>
               </div>
           </div>
       </div>
   </div>

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
// Global Chart.js error handler to prevent canvas errors
Chart.defaults.animation.duration = 0; // Disable animations to reduce errors

// Override Chart.js canvas helper to add null checks
const originalGetContext = HTMLCanvasElement.prototype.getContext;
HTMLCanvasElement.prototype.getContext = function(type, options) {
    if (!this || !this.parentNode || !document.contains(this)) {
        console.warn('Canvas not in DOM, skipping getContext');
        return null;
    }
    return originalGetContext.call(this, type, options);
};
</script>
<script>
function dashboardData() {
    return {
        data: {
            totals: {},
            photos: {},
            modules: {
                module_details: { sk: {}, sr: {}, gas_in: {} },
                pie_charts: {
                    total_by_module: { labels: [], data: [], colors: [] },
                    completion_status: { labels: [], data: [], colors: [] },
                    progress_distribution: { labels: [], data: [], colors: [] }
                }
            },
            donut_stats: {
                photo_approval: { labels: [], data: [], colors: [] },
                customer_types: { labels: [], data: [], colors: [] }
            },
            activities: [],
            performance: { sla_compliance: {} }
        },
        loading: false,
        lastUpdated: '{{ now()->format('H:i:s') }}',

        // Chart properties
        chartPeriod: 'daily',
        chartDays: 30,
        chartModule: 'all',
        chartLoading: false,
        installationChart: null,
        moduleDistributionChart: null,
        completionStatusChart: null,
        photoApprovalChart: null,
        chartUpdateTimeout: null,
        isDestroyed: false,
        chartInitialized: false,
        domObserver: null,

        init() {
            this.isDestroyed = false;
            this.loadData();

            // Wait for DOM to be fully ready before initializing charts
            setTimeout(() => {
                if (!this.isDestroyed) {
                    this.$nextTick(() => {
                        this.initAllCharts();
                    });
                }
            }, 200);

            setInterval(() => {
                if (!this.isDestroyed) {
                    this.refreshData();
                }
            }, 300000);

            // Add visibility change handler
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && !this.isDestroyed && this.chartInitialized) {
                    setTimeout(() => {
                        this.validateAndRepairCharts();
                    }, 500);
                }
            });

            // Add beforeunload handler to clean up charts
            window.addEventListener('beforeunload', () => {
                this.destroy();
            });

            // Add page hide handler for mobile
            window.addEventListener('pagehide', () => {
                this.destroy();
            });

            // Add DOM observer to watch for canvas removal
            this.setupDOMObserver();
        },

        destroy() {
            this.isDestroyed = true;
            this.chartInitialized = false;

            if (this.chartUpdateTimeout) {
                clearTimeout(this.chartUpdateTimeout);
            }

            this.destroyAllCharts();

            if (this.domObserver) {
                this.domObserver.disconnect();
                this.domObserver = null;
            }
        },

        setupDOMObserver() {
            if (!window.MutationObserver) return;

            this.domObserver = new MutationObserver((mutations) => {
                if (this.isDestroyed) return;

                let needsValidation = false;
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList') {
                        mutation.removedNodes.forEach((node) => {
                            if (node.nodeType === Node.ELEMENT_NODE) {
                                const canvasRemoved = node.querySelector && (
                                    node.querySelector('#moduleDistributionChart') ||
                                    node.querySelector('#completionStatusChart') ||
                                    node.querySelector('#photoApprovalChart') ||
                                    node.id === 'moduleDistributionChart' ||
                                    node.id === 'completionStatusChart' ||
                                    node.id === 'photoApprovalChart'
                                );

                                if (canvasRemoved) {
                                    console.warn('Canvas removed from DOM, destroying corresponding charts');
                                    needsValidation = true;
                                }
                            }
                        });
                    }
                });

                if (needsValidation) {
                    setTimeout(() => {
                        this.validateAndRepairCharts();
                    }, 100);
                }
            });

            this.domObserver.observe(document.body, {
                childList: true,
                subtree: true
            });
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
                                total_by_module: { labels: [], data: [], colors: [] },
                                completion_status: { labels: [], data: [], colors: [] },
                                progress_distribution: { labels: [], data: [], colors: [] }
                            }
                        },
                        donut_stats: result.data.donut_stats || {
                            photo_approval: { labels: [], data: [], colors: [] },
                            customer_types: { labels: [], data: [], colors: [] }
                        },
                        activities: result.data.activities || [],
                        performance: result.data.performance || { sla_compliance: {} }
                    };

                    // Update pie charts after data load
                    this.$nextTick(() => {
                        this.updatePieCharts();
                    });
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

                    // Update all charts with delay to ensure DOM is ready
                    setTimeout(() => {
                        this.$nextTick(() => {
                            this.updatePieCharts();
                        });
                    }, 50);

                    window.showToast && window.showToast('success', 'Dashboard updated successfully');
                } else {
                    throw new Error(result.message || 'Failed to refresh data');
                }
            } catch (error) {
                console.error('Error refreshing dashboard:', error);
                window.showToast && window.showToast('error', 'Failed to refresh dashboard');
            } finally {
                this.loading = false;
            }
        },

        initAllCharts() {
            if (this.isDestroyed) return;

            console.log('Initializing all charts...');

            // Destroy existing charts first
            this.destroyAllCharts();

            // Initialize new charts with delays
            setTimeout(() => this.initInstallationChart(), 50);
            setTimeout(() => this.initModuleDistributionChart(), 100);
            setTimeout(() => this.initCompletionStatusChart(), 150);
            setTimeout(() => this.initPhotoApprovalChart(), 200);

            // Mark as initialized
            setTimeout(() => {
                this.chartInitialized = true;
                console.log('All charts initialized');
            }, 300);
        },

        destroyAllCharts() {
            const charts = [
                'installationChart',
                'moduleDistributionChart',
                'completionStatusChart',
                'photoApprovalChart'
            ];

            charts.forEach(chartName => {
                if (this[chartName]) {
                    try {
                        console.log(`Destroying ${chartName}`);
                        this[chartName].destroy();
                    } catch (e) {
                        console.warn(`Error destroying ${chartName}:`, e);
                    }
                    this[chartName] = null;
                }
            });
        },

        validateAndRepairCharts() {
            if (this.isDestroyed || !this.chartInitialized) return;

            const chartsToCheck = [
                { name: 'moduleDistributionChart', init: this.initModuleDistributionChart },
                { name: 'completionStatusChart', init: this.initCompletionStatusChart },
                { name: 'photoApprovalChart', init: this.initPhotoApprovalChart }
            ];

            chartsToCheck.forEach(({ name, init }) => {
                if (!this.isChartValid(this[name])) {
                    console.warn(`Chart ${name} is invalid, reinitializing...`);
                    this[name] = null;
                    setTimeout(() => init.call(this), 100);
                }
            });
        },

        initModuleDistributionChart() {
            if (this.isDestroyed) return;

            const canvas = document.getElementById('moduleDistributionChart');
            if (!canvas) {
                console.warn('moduleDistributionChart canvas not found');
                return;
            }

            // Additional check - ensure canvas is actually attached to DOM
            if (!document.contains(canvas)) {
                console.warn('moduleDistributionChart canvas not in DOM');
                return;
            }

            let ctx;
            try {
                ctx = canvas.getContext('2d');
                if (!ctx) {
                    console.warn('Could not get 2d context from moduleDistributionChart canvas');
                    return;
                }
            } catch (e) {
                console.warn('Error getting context from moduleDistributionChart canvas:', e);
                return;
            }

            if (this.moduleDistributionChart) {
                try {
                    this.moduleDistributionChart.destroy();
                } catch (e) {
                    console.warn('Error destroying moduleDistributionChart:', e);
                }
                this.moduleDistributionChart = null;
            }

            if (this.isDestroyed) return; // Double check before creating

            try {
                this.moduleDistributionChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['SK Module', 'SR Module', 'Gas In'],
                    datasets: [{
                        data: [0, 0, 0],
                        backgroundColor: ['#EF4444', '#10B981', '#3B82F6'],
                        borderWidth: 3,
                        borderColor: '#ffffff',
                        hoverBorderWidth: 4,
                        hoverBackgroundColor: ['#DC2626', '#059669', '#2563EB'],
                        cutout: '65%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: 10
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                    return `${context.label}: ${context.parsed} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true,
                        duration: 1000
                    },
                    interaction: {
                        intersect: false,
                        mode: 'nearest'
                    }
                }
                });

                console.log('moduleDistributionChart initialized successfully');
            } catch (e) {
                console.error('Error creating moduleDistributionChart:', e);
                this.moduleDistributionChart = null;
            }
        },

        initCompletionStatusChart() {
            const canvas = document.getElementById('completionStatusChart');
            if (!canvas) {
                console.warn('completionStatusChart canvas not found');
                return;
            }

            let ctx;
            try {
                ctx = canvas.getContext('2d');
                if (!ctx) {
                    console.warn('Could not get 2d context from completionStatusChart canvas');
                    return;
                }
            } catch (e) {
                console.warn('Error getting context from completionStatusChart canvas:', e);
                return;
            }

            if (this.completionStatusChart) {
                try {
                    this.completionStatusChart.destroy();
                } catch (e) {
                    console.warn('Error destroying completionStatusChart:', e);
                }
                this.completionStatusChart = null;
            }

            this.completionStatusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'In Progress', 'Draft', 'Rejected'],
                    datasets: [{
                        data: [0, 0, 0, 0],
                        backgroundColor: ['#10B981', '#F59E0B', '#6B7280', '#EF4444'],
                        borderWidth: 3,
                        borderColor: '#ffffff',
                        hoverBorderWidth: 4,
                        hoverBackgroundColor: ['#059669', '#D97706', '#4B5563', '#DC2626'],
                        cutout: '65%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: 10
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                    return `${context.label}: ${context.parsed} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true,
                        duration: 1000
                    },
                    interaction: {
                        intersect: false,
                        mode: 'nearest'
                    }
                }
            });
        },

        initPhotoApprovalChart() {
            const canvas = document.getElementById('photoApprovalChart');
            if (!canvas) {
                console.warn('photoApprovalChart canvas not found');
                return;
            }

            let ctx;
            try {
                ctx = canvas.getContext('2d');
                if (!ctx) {
                    console.warn('Could not get 2d context from photoApprovalChart canvas');
                    return;
                }
            } catch (e) {
                console.warn('Error getting context from photoApprovalChart canvas:', e);
                return;
            }

            if (this.photoApprovalChart) {
                try {
                    this.photoApprovalChart.destroy();
                } catch (e) {
                    console.warn('Error destroying photoApprovalChart:', e);
                }
                this.photoApprovalChart = null;
            }

            this.photoApprovalChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Approved', 'Pending CGP', 'Pending Tracer', 'AI Validation', 'Rejected'],
                    datasets: [{
                        data: [0, 0, 0, 0, 0],
                        backgroundColor: ['#10B981', '#F59E0B', '#3B82F6', '#8B5CF6', '#EF4444'],
                        borderWidth: 3,
                        borderColor: '#ffffff',
                        hoverBorderWidth: 4,
                        hoverBackgroundColor: ['#059669', '#D97706', '#2563EB', '#7C3AED', '#DC2626'],
                        cutout: '65%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: 10
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                    return `${context.label}: ${context.parsed} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true,
                        duration: 1000
                    },
                    interaction: {
                        intersect: false,
                        mode: 'nearest'
                    }
                }
            });
        },

        updatePieCharts() {
            try {
                // Update module distribution chart
                if (this.moduleDistributionChart && this.data.modules.pie_charts?.total_by_module) {
                    const moduleData = this.data.modules.pie_charts.total_by_module;
                    const newData = moduleData.data || [0, 0, 0];

                    // Check if chart is still valid
                    if (this.isChartValid(this.moduleDistributionChart)) {
                        this.moduleDistributionChart.data.datasets[0].data = newData;
                        this.moduleDistributionChart.update('none');
                    } else {
                        this.moduleDistributionChart = null;
                        this.initModuleDistributionChart();
                    }
                }

                // Update completion status chart
                if (this.completionStatusChart && this.data.modules.pie_charts?.completion_status) {
                    const completionData = this.data.modules.pie_charts.completion_status;
                    const newData = completionData.data || [0, 0, 0, 0];

                    // Check if chart is still valid
                    if (this.isChartValid(this.completionStatusChart)) {
                        this.completionStatusChart.data.datasets[0].data = newData;
                        this.completionStatusChart.update('none');
                    } else {
                        this.completionStatusChart = null;
                        this.initCompletionStatusChart();
                    }
                }

                // Update photo approval chart
                if (this.photoApprovalChart && this.data.donut_stats?.photo_approval) {
                    const photoData = this.data.donut_stats.photo_approval;
                    const newData = photoData.data || [0, 0, 0, 0, 0];

                    // Check if chart is still valid
                    if (this.isChartValid(this.photoApprovalChart)) {
                        this.photoApprovalChart.data.datasets[0].data = newData;
                        this.photoApprovalChart.update('none');
                    } else {
                        this.photoApprovalChart = null;
                        this.initPhotoApprovalChart();
                    }
                }
            } catch (error) {
                console.warn('Error updating pie charts:', error);
                // Reinitialize all charts if update fails
                this.reinitializeCharts();
            }
        },

        isChartValid(chart) {
            try {
                return chart &&
                       chart.canvas &&
                       chart.canvas.getContext &&
                       chart.canvas.getContext('2d') &&
                       chart.data &&
                       chart.data.datasets &&
                       chart.data.datasets[0];
            } catch (e) {
                return false;
            }
        },

        reinitializeCharts() {
            // Clean up existing charts
            [this.moduleDistributionChart, this.completionStatusChart, this.photoApprovalChart].forEach(chart => {
                if (chart) {
                    try {
                        chart.destroy();
                    } catch (e) {
                        console.warn('Error destroying chart during reinit:', e);
                    }
                }
            });

            // Reset chart references
            this.moduleDistributionChart = null;
            this.completionStatusChart = null;
            this.photoApprovalChart = null;

            // Reinitialize after a short delay
            setTimeout(() => {
                this.$nextTick(() => {
                    this.initAllCharts();
                });
            }, 200);
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
        }
    }
}
</script>
@endpush
@endsection