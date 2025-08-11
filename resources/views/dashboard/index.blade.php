{{-- resources/views/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard AERGAS')

@once
@push('head')
    {{-- Chart.js (aman kalau layout belum include) --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush
@endonce

@section('content')
<div class="space-y-6" x-data="dashboardData()" x-init="init()">

    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Dashboard AERGAS</h1>
            <p class="text-gray-600 mt-1">
                Selamat datang kembali, {{ auth()->user()->name }}!
                {{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}
            </p>
        </div>
        <button @click="refreshData()"
                class="flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-sync-alt"></i>
            <span>Refresh</span>
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Pelanggan -->
        <div class="bg-white rounded-xl card-shadow p-6 hover-scale">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-blue-600"></i>
                        </div>
                        <span class="text-gray-600 text-sm">Total Pelanggan</span>
                    </div>
                    <div class="text-2xl font-bold text-gray-800" x-text="stats.total_customers">
                        {{ $stats['total_customers'] ?? 0 }}
                    </div>
                    <a href="{{ route('customers.index') }}" class="text-blue-600 text-sm hover:underline">Lihat semua</a>
                </div>
            </div>
        </div>

        <!-- My SK Total / SK Completed -->
        <div class="bg-white rounded-xl card-shadow p-6 hover-scale">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-wrench text-green-600"></i>
                        </div>
                        <span class="text-gray-600 text-sm">
                            @if(auth()->user()->role === 'sk')
                                My SK Total
                            @else
                                SK Completed
                            @endif
                        </span>
                    </div>
                    <div class="text-2xl font-bold text-gray-800" x-text="stats.sk_completed">
                        {{ $stats['sk_completed'] ?? 0 }}
                    </div>
                    @if(auth()->user()->canAccessModule('sk'))
                        <a href="{{ route('sk.index') }}" class="text-green-600 text-sm hover:underline">Manage SK</a>
                    @else
                        <span class="text-green-600 text-sm">View validated</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- SK Pending / Pending Review -->
        <div class="bg-white rounded-xl card-shadow p-6 hover-scale">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600"></i>
                        </div>
                        <span class="text-gray-600 text-sm">
                            @if(auth()->user()->isTracer())
                                Pending Review
                            @else
                                SK Pending
                            @endif
                        </span>
                    </div>
                    <div class="text-2xl font-bold text-gray-800" x-text="stats.pending_approvals">
                        {{ $stats['pending_approvals'] ?? 0 }}
                    </div>
                    @if(auth()->user()->isTracer())
                        <a href="{{ route('photos.index') }}" class="text-yellow-600 text-sm hover:underline">Review pending</a>
                    @else
                        <span class="text-yellow-600 text-sm">Review pending</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- AI Validated -->
        <div class="bg-white rounded-xl card-shadow p-6 hover-scale">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-robot text-purple-600"></i>
                        </div>
                        <span class="text-gray-600 text-sm">AI Validated</span>
                    </div>
                    <div class="text-2xl font-bold text-gray-800" x-text="stats.ai_approved">
                        {{ $stats['ai_approved'] ?? 0 }}
                    </div>
                    <span class="text-purple-600 text-sm">View validated</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Quick Actions</h2>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            @if(auth()->user()->canAccessModule('customers') || auth()->user()->isAdmin())
            <!-- Add Customer -->
            <div class="p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors cursor-pointer"
                 onclick="window.location.href='{{ route('customers.create') }}'">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-plus text-white"></i>
                </div>
                <div class="text-sm font-medium text-gray-800">Add Customer</div>
                <div class="text-xs text-gray-500">Register new customer</div>
            </div>
            @endif

            <!-- View Customers -->
            <div class="p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
                 onclick="window.location.href='{{ route('customers.index') }}'">
                <div class="w-8 h-8 bg-gray-600 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-list text-white"></i>
                </div>
                <div class="text-sm font-medium text-gray-800">View Customers</div>
                <div class="text-xs text-gray-500">Browse customer list</div>
            </div>

            @if(auth()->user()->canAccessModule('sk'))
            <!-- Create SK -->
            <div class="p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors cursor-pointer"
                 onclick="window.location.href='{{ route('sk.create') }}'">
                <div class="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-file-circle-plus text-white"></i>
                </div>
                <div class="text-sm font-medium text-gray-800">Create SK</div>
                <div class="text-xs text-gray-500">New SK document</div>
            </div>

            <!-- My SK Tasks -->
            <div class="p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors cursor-pointer"
                 onclick="window.location.href='{{ route('sk.index') }}'">
                <div class="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-tasks text-white"></i>
                </div>
                <div class="text-sm font-medium text-gray-800">My SK Tasks</div>
                <div class="text-xs text-gray-500">View & manage SK</div>
            </div>
            @endif

            @if(auth()->user()->canAccessModule('sr'))
            <!-- Create SR -->
            <div class="p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors cursor-pointer"
                 onclick="window.location.href='{{ route('sr.create') }}'">
                <div class="w-8 h-8 bg-yellow-600 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-cog text-white"></i>
                </div>
                <div class="text-sm font-medium text-gray-800">Create SR</div>
                <div class="text-xs text-gray-500">Installation request</div>
            </div>
            @endif

            @if(auth()->user()->canAccessModule('gas_in'))
            <!-- Gas In -->
            <div class="p-4 bg-red-50 rounded-lg hover:bg-red-100 transition-colors cursor-pointer">
                <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-fire text-white"></i>
                </div>
                <div class="text-sm font-medium text-gray-800">Gas In</div>
                <div class="text-xs text-gray-500">Gas commissioning</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Recent Activities -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Recent Activities</h2>
                <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View all</a>
            </div>

            <div class="space-y-4">
                <template x-for="activity in recentActivities" :key="activity.id">
                    <div class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded-lg transition-colors">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-user text-gray-600 text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800" x-text="activity.message"></p>
                            <p class="text-xs text-gray-500 mt-1" x-text="activity.time"></p>
                        </div>
                    </div>
                </template>

                <!-- Fallback content -->
                <div x-show="recentActivities.length === 0" class="text-center py-8 text-gray-500">
                    <i class="fas fa-clock text-2xl mb-2"></i>
                    <p>No recent activities</p>
                </div>
            </div>
        </div>

        <!-- Workflow Progress -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Workflow Progress</h2>

            <!-- Progress Chart -->
            <div class="relative h-64">
                <canvas id="progressChart"></canvas>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-800" x-text="(stats.completion_rate || 0) + '%'">
                            {{ $stats['completion_rate'] ?? 0 }}%
                        </div>
                        <div class="text-sm text-gray-500">Completion</div>
                    </div>
                </div>
            </div>

            <!-- Progress Legend -->
            <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <span class="text-gray-600">In Progress</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-gray-600">Completed</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                    <span class="text-gray-600">Pending</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    <span class="text-gray-600">Rejected</span>
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
        stats: @json($stats ?? []),
        recentActivities: @json($recentActivities ?? []),
        progressChart: null,
        timer: null,

        init() {
            this.initProgressChart();
            this.timer = setInterval(() => this.refreshData(), 300000); // 5 menit
        },

        refreshData() {
            fetch(@json(route('dashboard.data')))
                .then(r => r.ok ? r.json() : Promise.reject('HTTP ' + r.status))
                .then(({ success, data }) => {
                    if (!success || !data) return;

                    this.stats = data.stats || {};
                    this.recentActivities = data.recent_activities || [];
                    this.updateProgressChart();

                    if (typeof showToast === 'function') {
                        showToast('Dashboard updated successfully', 'success');
                    }
                })
                .catch(err => {
                    console.error('Dashboard refresh error:', err);
                    if (typeof showToast === 'function') {
                        showToast('Failed to refresh dashboard', 'error');
                    }
                });
        },

        initProgressChart() {
            const el = document.getElementById('progressChart');
            if (!window.Chart || !el) return;

            const ctx = el.getContext('2d');
            this.progressChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['In Progress', 'Completed', 'Pending', 'Rejected'],
                    datasets: [{
                        data: [
                            this.stats.in_progress_count || 0,
                            this.stats.completed_customers || 0,
                            this.stats.pending_validation || 0,
                            this.stats.cancelled_customers || 0
                        ],
                        backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgba(255,255,255,0.1)',
                            borderWidth: 1
                        }
                    }
                }
            });
        },

        updateProgressChart() {
            if (!this.progressChart) return;
            this.progressChart.data.datasets[0].data = [
                this.stats.in_progress_count || 0,
                this.stats.completed_customers || 0,
                this.stats.pending_validation || 0,
                this.stats.cancelled_customers || 0
            ];
            this.progressChart.update();
        }
    }
}

// Toast minimal (kalau layout belum punya)
window.showToast = window.showToast || function (msg, type = 'info') {
    const bg = type === 'success' ? 'bg-green-600' :
               type === 'error' ? 'bg-red-600' :
               type === 'warning' ? 'bg-yellow-600' : 'bg-gray-800';
    const el = document.createElement('div');
    el.className = `fixed bottom-6 right-6 text-white ${bg} px-4 py-2 rounded-lg shadow-lg z-50`;
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 2500);
};
</script>
@endpush
