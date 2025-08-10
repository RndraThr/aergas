@extends('aergas.layouts.app')

@section('title', 'Dashboard')

@section('page-header')
<div class="md:flex md:items-center md:justify-between">
    <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Dashboard AERGAS
        </h2>
        <p class="mt-1 text-sm text-gray-500">
            Selamat datang kembali, {{ auth()->user()->name }}! 
            <span class="text-blue-600 font-medium">
                @if(auth()->user()->hasRole('super_admin'))
                    Super Administrator
                @elseif(auth()->user()->hasRole('admin'))
                    Administrator
                @elseif(auth()->user()->hasRole('sk'))
                    SK Officer
                @elseif(auth()->user()->hasRole('sr'))
                    SR Officer
                @elseif(auth()->user()->hasRole('gas_in'))
                    Gas In Officer
                @elseif(auth()->user()->hasRole('validasi'))
                    Validation Officer
                @else
                    User
                @endif
            </span>
        </p>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
        <button onclick="refreshDashboard()" 
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Refresh
        </button>
        @canany(['super_admin', 'admin'])
        <a href="{{ route('aergas.admin.index') }}" 
           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Admin Panel
        </a>
        @endcanany
    </div>
</div>
@endsection

@section('content')
<div x-data="dashboardData()" x-init="initDashboard()" class="space-y-6">
    
    {{-- Quick Stats Cards --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Total Pelanggan --}}
        <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Pelanggan</dt>
                            <dd class="text-lg font-medium text-gray-900" x-text="stats.total_pelanggan || 0"></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="{{ route('aergas.calon-pelanggan.index') }}" class="font-medium text-blue-700 hover:text-blue-900 transition-colors">
                        Lihat semua
                    </a>
                </div>
            </div>
        </div>

        {{-- Pending Actions --}}
        <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Pending Actions</dt>
                            <dd class="text-lg font-medium text-gray-900" x-text="stats.pending_pelanggan || 0"></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="{{ route('aergas.calon-pelanggan.index', ['status' => 'pending']) }}" class="font-medium text-yellow-700 hover:text-yellow-900 transition-colors">
                        Review pending
                    </a>
                </div>
            </div>
        </div>

        {{-- Completed This Month --}}
        <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                            <dd class="text-lg font-medium text-gray-900" x-text="stats.completed_pelanggan || 0"></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="{{ route('aergas.calon-pelanggan.index', ['status' => 'completed']) }}" class="font-medium text-green-700 hover:text-green-900 transition-colors">
                        View completed
                    </a>
                </div>
            </div>
        </div>

        {{-- Files Storage --}}
        <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Files</dt>
                            <dd class="text-lg font-medium text-gray-900" x-text="stats.total_files || 0"></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="{{ route('aergas.files.index') }}" class="font-medium text-purple-700 hover:text-purple-900 transition-colors">
                        Manage files
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Role-specific Quick Actions --}}
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                
                {{-- Add New Customer --}}
                @canany(['super_admin', 'admin'])
                <div class="relative group">
                    <div class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 hover:shadow-md transition-all duration-200">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('aergas.calon-pelanggan.create') }}" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                <p class="text-sm font-medium text-gray-900">Add Customer</p>
                                <p class="text-sm text-gray-500">Register new customer</p>
                            </a>
                        </div>
                    </div>
                </div>
                @endcanany

                {{-- Create SK --}}
                @canany(['super_admin', 'admin', 'sk'])
                <div class="relative group">
                    <div class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 hover:shadow-md transition-all duration-200">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('aergas.sk.create') }}" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                <p class="text-sm font-medium text-gray-900">Create SK</p>
                                <p class="text-sm text-gray-500">New SK document</p>
                            </a>
                        </div>
                    </div>
                </div>
                @endcanany

                {{-- Create SR --}}
                @canany(['super_admin', 'admin', 'sr'])
                <div class="relative group">
                    <div class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 hover:shadow-md transition-all duration-200">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('aergas.sr.create') }}" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                <p class="text-sm font-medium text-gray-900">Create SR</p>
                                <p class="text-sm text-gray-500">Installation request</p>
                            </a>
                        </div>
                    </div>
                </div>
                @endcanany

                {{-- Create Gas In --}}
                @canany(['super_admin', 'admin', 'gas_in'])
                <div class="relative group">
                    <div class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 hover:shadow-md transition-all duration-200">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('aergas.gas-in.create') }}" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                <p class="text-sm font-medium text-gray-900">Gas In</p>
                                <p class="text-sm text-gray-500">Gas commissioning</p>
                            </a>
                        </div>
                    </div>
                </div>
                @endcanany
            </div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        
        {{-- Recent Activities --}}
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Activities</h3>
                    <a href="{{ route('aergas.audit.index') }}" class="text-sm text-blue-600 hover:text-blue-800 transition-colors">View all</a>
                </div>
                <div x-show="!recentActivities.length" class="text-center py-8">
                    <div class="text-gray-400 mb-2">
                        <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500">No recent activities</p>
                </div>
                <div class="flow-root">
                    <ul class="-mb-8 space-y-6" x-show="recentActivities.length">
                        <template x-for="(activity, index) in recentActivities.slice(0, 5)" :key="activity.id">
                            <li>
                                <div class="relative pb-8" x-show="index < 4">
                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true" x-show="index < recentActivities.slice(0, 5).length - 1"></span>
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white"
                                                  :class="{
                                                      'bg-green-500': activity.action === 'CREATE',
                                                      'bg-blue-500': activity.action === 'UPDATE',
                                                      'bg-red-500': activity.action === 'DELETE',
                                                      'bg-yellow-500': activity.action === 'APPROVE',
                                                      'bg-gray-500': !['CREATE', 'UPDATE', 'DELETE', 'APPROVE'].includes(activity.action)
                                                  }">
                                                <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path x-show="activity.action === 'CREATE'" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/>
                                                    <path x-show="activity.action === 'UPDATE'" fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                                                    <path x-show="activity.action === 'DELETE'" fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z M4 5a2 2 0 012-2h8a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h.01a1 1 0 100-2H10zm3 0a1 1 0 000 2h.01a1 1 0 100-2H13z" clip-rule="evenodd"/>
                                                    <circle x-show="!['CREATE', 'UPDATE', 'DELETE'].includes(activity.action)" cx="10" cy="10" r="3"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                            <div>
                                                <p class="text-sm text-gray-900" x-text="activity.description"></p>
                                                <p class="text-xs text-gray-500">
                                                    by <span x-text="activity.user_name"></span>
                                                    <span x-show="activity.reff_id">• <span x-text="activity.reff_id"></span></span>
                                                </p>
                                            </div>
                                            <div class="text-right text-xs text-gray-400">
                                                <time x-text="activity.time"></time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Workflow Status Chart --}}
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Workflow Progress</h3>
                <div class="chart-container" style="position: relative; height: 250px; width: 100%;">
                    <canvas id="workflowChart" style="max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Monthly Progress Chart --}}
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Monthly Progress</h3>
                <div class="chart-container" style="position: relative; height: 250px; width: 100%;">
                    <canvas id="monthlyChart" style="max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>

        {{-- Status Distribution --}}
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Status Distribution</h3>
                <div class="chart-container" style="position: relative; height: 250px; width: 100%;">
                    <canvas id="statusChart" style="max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function dashboardData() {
    return {
        stats: {},
        recentActivities: [],
        myRecentWork: [],
        chartData: {},
        systemStatus: {
            google_drive: true,
            google_sheets: true,
            database: true
        },
        charts: {
            workflow: null,
            monthly: null,
            status: null
        },
        chartsInitialized: false,

        async initDashboard() {
            try {
                await this.loadStats();
                await this.loadRecentActivities();
                await this.loadMyRecentWork();
                await this.loadChartData();
                
                // Delay chart initialization to ensure DOM is ready
                setTimeout(() => {
                    this.initCharts();
                }, 100);
                
            } catch (error) {
                console.error('Dashboard initialization error:', error);
            }
        },

        async loadStats() {
            try {
                // Gunakan route() helper dari Laravel atau URL statis
                const response = await fetch("{{ route('aergas.api.quick-stats') }}");
                if (!response.ok) throw new Error('Network response was not ok');
                this.stats = await response.json();
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },

        async loadRecentActivities() {
            try {
                // Simulate API call - replace with actual endpoint
                this.recentActivities = [
                    {
                        id: 1,
                        action: 'CREATE',
                        description: 'User accessed AERGAS dashboard',
                        user_name: 'Super Admin',
                        reff_id: null,
                        time: '1 second ago'
                    },
                    {
                        id: 2,
                        action: 'UPDATE',
                        description: 'Customer data updated',
                        user_name: 'Super Admin',
                        reff_id: 'CUST-001',
                        time: '5 minutes ago'
                    },
                    {
                        id: 3,
                        action: 'CREATE',
                        description: 'New SK document created',
                        user_name: 'SK Officer',
                        reff_id: 'SK-001',
                        time: '1 hour ago'
                    }
                ];
            } catch (error) {
                console.error('Error loading activities:', error);
                this.recentActivities = [];
            }
        },

        async loadMyRecentWork() {
            try {
                this.myRecentWork = [];
            } catch (error) {
                console.error('Error loading my recent work:', error);
                this.myRecentWork = [];
            }
        },

        async loadChartData() {
            try {
                this.chartData = {
                    workflow: {
                        labels: ['SK', 'SR', 'Gas In', 'Validasi'],
                        datasets: [{
                            label: 'Completed',
                            data: [85, 72, 45, 68],
                            backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
                            borderWidth: 0
                        }]
                    },
                    monthly: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                        datasets: [{
                            label: 'New Customers',
                            data: [12, 19, 15, 25, 22, 30],
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    status: {
                        labels: ['Pending', 'In Progress', 'Completed', 'Rejected'],
                        datasets: [{
                            data: [25, 15, 85, 5],
                            backgroundColor: ['#F59E0B', '#3B82F6', '#10B981', '#EF4444']
                        }]
                    }
                };
            } catch (error) {
                console.error('Error loading chart data:', error);
            }
        },

        initCharts() {
            if (this.chartsInitialized) {
                return;
            }

            try {
                // Destroy existing charts if they exist
                this.destroyCharts();

                // Workflow Chart
                const workflowCtx = document.getElementById('workflowChart');
                if (workflowCtx && this.chartData.workflow) {
                    this.charts.workflow = new Chart(workflowCtx, {
                        type: 'doughnut',
                        data: this.chartData.workflow,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: {
                                duration: 1000
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true
                                    }
                                }
                            }
                        }
                    });
                }

                // Monthly Chart
                const monthlyCtx = document.getElementById('monthlyChart');
                if (monthlyCtx && this.chartData.monthly) {
                    this.charts.monthly = new Chart(monthlyCtx, {
                        type: 'line',
                        data: this.chartData.monthly,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: {
                                duration: 1000
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.1)'
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });
                }

                // Status Chart
                const statusCtx = document.getElementById('statusChart');
                if (statusCtx && this.chartData.status) {
                    this.charts.status = new Chart(statusCtx, {
                        type: 'pie',
                        data: this.chartData.status,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: {
                                duration: 1000
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 20,
                                        usePointStyle: true
                                    }
                                }
                            }
                        }
                    });
                }

                this.chartsInitialized = true;
                
            } catch (error) {
                console.error('Error initializing charts:', error);
            }
        },

        destroyCharts() {
            Object.keys(this.charts).forEach(key => {
                if (this.charts[key]) {
                    this.charts[key].destroy();
                    this.charts[key] = null;
                }
            });
            this.chartsInitialized = false;
        },

        async refreshData() {
            try {
                await this.loadStats();
                await this.loadRecentActivities();
                await this.loadMyRecentWork();
                
                // Update chart data without recreating charts
                if (this.charts.status && this.chartData.status) {
                    this.charts.status.data.datasets[0].data = [
                        this.stats.pending_pelanggan || 0, 
                        this.stats.in_progress_pelanggan || 0, 
                        this.stats.completed_pelanggan || 0, 
                        this.stats.rejected_pelanggan || 0
                    ];
                    this.charts.status.update('none'); // No animation on update
                }
            } catch (error) {
                console.error('Error refreshing data:', error);
            }
        }
    }
}

// Global refresh function
function refreshDashboard() {
    const dashboardComponent = document.querySelector('[x-data*="dashboardData"]');
    if (dashboardComponent && dashboardComponent._x_dataStack) {
        const data = dashboardComponent._x_dataStack[0];
        if (data.refreshData) {
            data.refreshData();
            window.showNotification('Dashboard refreshed successfully', 'success');
        }
    }
}

// Handle page visibility change
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        const lastRefresh = localStorage.getItem('dashboard_last_refresh');
        const now = Date.now();
        if (!lastRefresh || now - parseInt(lastRefresh) > 300000) {
            refreshDashboard();
            localStorage.setItem('dashboard_last_refresh', now.toString());
        }
    }
});

// Clean up charts on page unload
window.addEventListener('beforeunload', function() {
    const dashboardComponent = document.querySelector('[x-data*="dashboardData"]');
    if (dashboardComponent && dashboardComponent._x_dataStack) {
        const data = dashboardComponent._x_dataStack[0];
        if (data.destroyCharts) {
            data.destroyCharts();
        }
    }
});
</script>
@endpush

@push('styles')
<style>
/* Fixed chart container styles */
.chart-container {
    position: relative !important;
    width: 100% !important;
}

.chart-container canvas {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    max-width: 100% !important;
    max-height: 100% !important;
}

/* Prevent infinite scroll issues */
.chart-container,
.chart-container * {
    overflow: hidden !important;
    scroll-behavior: auto !important;
}

/* Dashboard specific improvements */
.card-hover {
    transition: all 0.2s ease-in-out;
}

.card-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Loading states */
.loading-shimmer {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Activity timeline improvements */
.activity-timeline .activity-item {
    position: relative;
    padding-left: 3rem;
}

/* Quick action cards */
.quick-action-card {
    transition: all 0.2s ease;
    cursor: pointer;
}

.quick-action-card:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Responsive chart adjustments */
@media (max-width: 640px) {
    .chart-container {
        height: 200px !important;
    }
}

@media (max-width: 768px) {
    .chart-container {
        height: 220px !important;
    }
}

/* Ensure charts don't cause layout shifts */
.chart-container {
    contain: layout style paint;
}

/* Disable pointer events during chart initialization */
.chart-container.initializing {
    pointer-events: none;
}

/* Fix for chart.js responsive issues */
.chartjs-render-monitor {
    animation: none !important;
}
</style>
@endpush