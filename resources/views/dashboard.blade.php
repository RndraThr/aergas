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
               <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-aergas-orange/10 text-aergas-orange">
                   {{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}
               </span>
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
                               @if(in_array(auth()->user()->role, ['tracer', 'super_admin']))
                                   Pending Review
                               @elseif(in_array(auth()->user()->role, ['admin', 'super_admin']))
                                   CGP Review
                               @else
                                   Progress
                               @endif
                           </span>
                           <div class="text-2xl font-bold text-gray-900" x-text="data.totals?.in_progress || 0">0</div>
                       </div>
                   </div>
                   @if(in_array(auth()->user()->role, ['tracer', 'admin', 'super_admin']))
                       <a href="{{ route('photos.index') }}"
                          class="inline-flex items-center text-yellow-600 hover:text-yellow-800 text-sm font-medium transition-colors">
                           Review now <i class="fas fa-arrow-right ml-1"></i>
                       </a>
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

   <!-- Module Status Cards -->
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
           @if(in_array(auth()->user()->role, ['admin', 'tracer', 'super_admin']))
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

           @if(in_array(auth()->user()->role, ['sk', 'tracer', 'admin', 'super_admin']))
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

           @if(in_array(auth()->user()->role, ['sr', 'tracer', 'admin', 'super_admin']))
           <div class="group p-4 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl hover:from-yellow-100 hover:to-yellow-200 transition-all duration-300 cursor-pointer transform hover:scale-105"
                onclick="window.location.href='{{ route('sr.index') }}'">
               <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center mb-3 group-hover:shadow-lg transition-all">
                   <i class="fas fa-route text-white"></i>
               </div>
               <div class="text-sm font-semibold text-gray-900">SR Module</div>
               <div class="text-xs text-gray-600 mt-1">Sambungan Rumah data</div>
           </div>
           @endif

           @if(in_array(auth()->user()->role, ['gas_in', 'tracer', 'admin', 'super_admin']))
           <div class="group p-4 bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl hover:from-orange-100 hover:to-orange-200 transition-all duration-300 cursor-pointer transform hover:scale-105"
                onclick="window.location.href='{{ route('gas-in.index') }}'">
               <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center mb-3 group-hover:shadow-lg transition-all">
                   <i class="fas fa-gas-pump text-white"></i>
               </div>
               <div class="text-sm font-semibold text-gray-900">Gas In</div>
               <div class="text-xs text-gray-600 mt-1">Gas In data</div>
           </div>
           @endif

           @if(in_array(auth()->user()->role, ['tracer', 'admin', 'super_admin']))
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

   <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
       <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
           <div class="flex items-center justify-between mb-6">
               <h2 class="text-xl font-semibold text-gray-900">Module Statistics</h2>
               <div class="text-sm text-gray-500">Real-time data</div>
           </div>

           <div class="space-y-4">
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

       <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
           <div class="flex items-center justify-between mb-6">
               <h2 class="text-xl font-semibold text-gray-900">Recent Activities</h2>
               <a href="{{ route('notifications.index') }}"
                  class="text-aergas-orange hover:text-aergas-navy text-sm font-medium transition-colors">
                   View all
               </a>
           </div>

           <div class="space-y-4">
               <template x-for="activity in data.activities" :key="activity.id">
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

               <div x-show="!data.activities || data.activities.length === 0" class="text-center py-8 text-gray-500">
                   <i class="fas fa-clock text-3xl mb-3 text-gray-300"></i>
                   <p class="text-sm">No recent activities</p>
                   <p class="text-xs text-gray-400 mt-1">Activities will appear here when available</p>
               </div>
           </div>
       </div>
   </div>

   @if(in_array(auth()->user()->role, ['tracer', 'super_admin']))
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

   @if(in_array(auth()->user()->role, ['admin', 'super_admin']))
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
        chartUpdateTimeout: null,

        init() {
            this.loadData();
            this.$nextTick(() => {
                this.initInstallationChart();
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
                        modules: result.data.modules || { module_details: { sk: {}, sr: {}, gas_in: {} } },
                        activities: result.data.activities || [],
                        performance: result.data.performance || { sla_compliance: {} }
                    };
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
                            beginAtZero: true,
                            suggestedMax: 10,
                            ticks: {
                                stepSize: 1,
                                callback: function(value) {
                                    return Number.isInteger(value) ? value : '';
                                }
                            }
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
                
                // Calculate dynamic max value for Y-axis
                let maxValue = 0;
                datasets.forEach(dataset => {
                    if (Array.isArray(dataset.data)) {
                        const dataMax = Math.max(...dataset.data.filter(val => typeof val === 'number' && !isNaN(val)));
                        maxValue = Math.max(maxValue, dataMax);
                    }
                });
                
                // Add 20% padding to max value, minimum of 10
                const suggestedMax = Math.max(10, Math.ceil(maxValue * 1.2));
                
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
                                beginAtZero: true,
                                suggestedMax: suggestedMax,
                                ticks: {
                                    stepSize: Math.max(1, Math.ceil(suggestedMax / 10)),
                                    callback: function(value) {
                                        return Number.isInteger(value) ? value : '';
                                    }
                                }
                            }
                        }
                    }
                });

                console.log('Chart recreated successfully with max value:', suggestedMax);
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
