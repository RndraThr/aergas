@extends('layouts.app')

@section('title', 'Dashboard AERGAS')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6" x-data="dashboardData()">

    <!-- Header -->
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

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Pelanggan -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-users text-white text-lg"></i>
                        </div>
                        <div>
                            <span class="text-gray-600 text-sm font-medium">Total Pelanggan</span>
                            <div class="text-2xl font-bold text-gray-900" x-text="stats.total_customers || 0">{{ $stats['total_customers'] ?? 0 }}</div>
                        </div>
                    </div>
                    <a href="{{ route('customers.index') }}"
                       class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">
                        Lihat semua <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- SK Module Stats -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-fire text-white text-lg"></i>
                        </div>
                        <div>
                            <span class="text-gray-600 text-sm font-medium">
                                @if(auth()->user()->role === 'sk')
                                    My SK Tasks
                                @else
                                    SK Completed
                                @endif
                            </span>
                            <div class="text-2xl font-bold text-gray-900" x-text="stats.sk_completed || 0">{{ $stats['sk_completed'] ?? 0 }}</div>
                        </div>
                    </div>
                    @if(in_array(auth()->user()->role, ['sk', 'tracer', 'admin']))
                        <a href="{{ route('sk.index') }}"
                           class="inline-flex items-center text-green-600 hover:text-green-800 text-sm font-medium transition-colors">
                            Manage SK <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    @else
                        <span class="text-green-600 text-sm font-medium">View completed</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pending Approvals -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-clock text-white text-lg"></i>
                        </div>
                        <div>
                            <span class="text-gray-600 text-sm font-medium">
                                @if(auth()->user()->role === 'tracer')
                                    Pending Review
                                @elseif(auth()->user()->role === 'admin')
                                    CGP Review
                                @else
                                    Pending Approvals
                                @endif
                            </span>
                            <div class="text-2xl font-bold text-gray-900" x-text="stats.pending_approvals || 0">{{ $stats['pending_approvals'] ?? 0 }}</div>
                        </div>
                    </div>
                    @if(in_array(auth()->user()->role, ['tracer', 'admin']))
                        <a href="{{ route('photos.index') }}"
                           class="inline-flex items-center text-yellow-600 hover:text-yellow-800 text-sm font-medium transition-colors">
                            Review now <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    @else
                        <span class="text-yellow-600 text-sm font-medium">Awaiting review</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- AI Validated -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-robot text-white text-lg"></i>
                        </div>
                        <div>
                            <span class="text-gray-600 text-sm font-medium">AI Validated</span>
                            <div class="text-2xl font-bold text-gray-900" x-text="stats.ai_approved || 0">{{ $stats['ai_approved'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="flex items-center text-purple-600 text-sm">
                        <span class="font-medium" x-text="(stats.ai_approval_rate || 0) + '%'">{{ $stats['ai_approval_rate'] ?? 0 }}%</span>
                        <span class="text-gray-500 ml-1">approval rate</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">Quick Actions</h2>
            <span class="text-sm text-gray-500">Choose an action to get started</span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            @if(in_array(auth()->user()->role, ['admin', 'tracer']))
            <!-- Add Customer -->
            <div class="group p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl hover:from-blue-100 hover:to-blue-200 transition-all duration-300 cursor-pointer transform hover:scale-105"
                 onclick="window.location.href='{{ route('customers.create') }}'">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-3 group-hover:shadow-lg transition-all">
                    <i class="fas fa-user-plus text-white"></i>
                </div>
                <div class="text-sm font-semibold text-gray-900">Add Customer</div>
                <div class="text-xs text-gray-600 mt-1">Register new customer</div>
            </div>
            @endif

            <!-- View Customers -->
            <div class="group p-4 bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl hover:from-gray-100 hover:to-gray-200 transition-all duration-300 cursor-pointer transform hover:scale-105"
                 onclick="window.location.href='{{ route('customers.index') }}'">
                <div class="w-10 h-10 bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl flex items-center justify-center mb-3 group-hover:shadow-lg transition-all">
                    <i class="fas fa-list text-white"></i>
                </div>
                <div class="text-sm font-semibold text-gray-900">View Customers</div>
                <div class="text-xs text-gray-600 mt-1">Browse customer list</div>
            </div>

            @if(in_array(auth()->user()->role, ['sk', 'tracer', 'admin']))
            <!-- Create SK -->
            <div class="group p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl hover:from-green-100 hover:to-green-200 transition-all duration-300 cursor-pointer transform hover:scale-105"
                 onclick="window.location.href='{{ route('sk.create') }}'">
                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center mb-3 group-hover:shadow-lg transition-all">
                    <i class="fas fa-file-plus text-white"></i>
                </div>
                <div class="text-sm font-semibold text-gray-900">Create SK</div>
                <div class="text-xs text-gray-600 mt-1">New SK document</div>
            </div>

            <!-- My SK Tasks -->
            <div class="group p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl hover:from-green-100 hover:to-green-200 transition-all duration-300 cursor-pointer transform hover:scale-105"
                 onclick="window.location.href='{{ route('sk.index') }}'">
                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center mb-3 group-hover:shadow-lg transition-all">
                    <i class="fas fa-tasks text-white"></i>
                </div>
                <div class="text-sm font-semibold text-gray-900">My SK Tasks</div>
                <div class="text-xs text-gray-600 mt-1">View & manage SK</div>
            </div>
            @endif

            @if(in_array(auth()->user()->role, ['sr', 'tracer', 'admin']))
            <!-- SR Module -->
            <div class="group p-4 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl hover:from-yellow-100 hover:to-yellow-200 transition-all duration-300 cursor-pointer transform hover:scale-105"
                 onclick="window.location.href='{{ route('sr.index') }}'">
                <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center mb-3 group-hover:shadow-lg transition-all">
                    <i class="fas fa-route text-white"></i>
                </div>
                <div class="text-sm font-semibold text-gray-900">SR Module</div>
                <div class="text-xs text-gray-600 mt-1">Service route data</div>
            </div>
            @endif

            @if(in_array(auth()->user()->role, ['tracer', 'admin']))
            <!-- Photo Approvals -->
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

    <!-- Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Recent Activities -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Recent Activities</h2>
                <a href="{{ route('notifications.index') }}"
                   class="text-aergas-orange hover:text-aergas-navy text-sm font-medium transition-colors">
                    View all
                </a>
            </div>

            <div class="space-y-4">
                <template x-for="activity in recentActivities" :key="activity.id">
                    <div class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded-lg transition-colors">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                             :class="{
                                 'bg-green-100 text-green-600': activity.type === 'completed',
                                 'bg-yellow-100 text-yellow-600': activity.type === 'pending',
                                 'bg-blue-100 text-blue-600': activity.type === 'new',
                                 'bg-red-100 text-red-600': activity.type === 'rejected',
                                 'bg-gray-100 text-gray-600': !activity.type
                             }">
                            <i class="fas fa-circle text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 font-medium" x-text="activity.message"></p>
                            <p class="text-xs text-gray-500 mt-1" x-text="activity.time"></p>
                        </div>
                    </div>
                </template>

                <!-- Fallback content -->
                <div x-show="recentActivities.length === 0" class="text-center py-8 text-gray-500">
                    <i class="fas fa-clock text-3xl mb-3 text-gray-300"></i>
                    <p class="text-sm">No recent activities</p>
                    <p class="text-xs text-gray-400 mt-1">Activities will appear here when available</p>
                </div>
            </div>
        </div>

        <!-- Workflow Progress -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Workflow Progress</h2>

            <!-- Progress Chart -->
            <div class="relative h-64 flex items-center justify-center">
                <canvas id="progressChart" class="max-w-full max-h-full"></canvas>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-gray-900" x-text="(stats.completion_rate || 0) + '%'">{{ $stats['completion_rate'] ?? 0 }}%</div>
                        <div class="text-sm text-gray-500 font-medium">Overall Completion</div>
                    </div>
                </div>
            </div>

            <!-- Progress Legend -->
            <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <span class="text-gray-700">In Progress</span>
                    <span class="text-gray-500 text-xs" x-text="'(' + (stats.in_progress_count || 0) + ')'"></span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-gray-700">Completed</span>
                    <span class="text-gray-500 text-xs" x-text="'(' + (stats.completed_customers || 0) + ')'"></span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                    <span class="text-gray-700">Pending</span>
                    <span class="text-gray-500 text-xs" x-text="'(' + (stats.pending_validation || 0) + ')'"></span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    <span class="text-gray-700">Cancelled</span>
                    <span class="text-gray-500 text-xs" x-text="'(' + (stats.cancelled_customers || 0) + ')'"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Role-specific Information -->
    @if(auth()->user()->role === 'tracer')
    <div class="bg-gradient-to-r from-aergas-navy/5 to-aergas-orange/5 rounded-xl p-6 border border-aergas-orange/20">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Tracer Dashboard</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-aergas-navy" x-text="stats.my_pending_reviews || 0">{{ $stats['my_pending_reviews'] ?? 0 }}</div>
                <div class="text-sm text-gray-600">Photos to Review</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600" x-text="stats.my_approved_today || 0">{{ $stats['my_approved_today'] ?? 0 }}</div>
                <div class="text-sm text-gray-600">Approved Today</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-aergas-orange" x-text="stats.sla_violations || 0">{{ $stats['sla_violations'] ?? 0 }}</div>
                <div class="text-sm text-gray-600">SLA Violations</div>
            </div>
        </div>
    </div>
    @endif

    @if(auth()->user()->role === 'admin')
    <div class="bg-gradient-to-r from-aergas-navy/5 to-aergas-orange/5 rounded-xl p-6 border border-aergas-orange/20">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Admin Dashboard</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-aergas-navy" x-text="stats.cgp_pending || 0">{{ $stats['cgp_pending'] ?? 0 }}</div>
                <div class="text-sm text-gray-600">CGP Reviews</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600" x-text="stats.cgp_approved_today || 0">{{ $stats['cgp_approved_today'] ?? 0 }}</div>
                <div class="text-sm text-gray-600">CGP Approved Today</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600" x-text="stats.ai_accuracy || 0">{{ $stats['ai_accuracy'] ?? 0 }}%</div>
                <div class="text-sm text-gray-600">AI Accuracy</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-aergas-orange" x-text="stats.system_alerts || 0">{{ $stats['system_alerts'] ?? 0 }}</div>
                <div class="text-sm text-gray-600">System Alerts</div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function dashboardData() {
    return {
        stats: @json($stats ?? []),
        recentActivities: @json($recentActivities ?? []),
        progressChart: null,
        loading: false,
        lastUpdated: '{{ now()->format('H:i:s') }}',

        init() {
            this.$nextTick(() => {
                this.initProgressChart();
            });

            // Refresh data every 5 minutes
            setInterval(() => {
                this.refreshData();
            }, 300000);
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

                const data = await response.json();

                if (data.success) {
                    this.stats = data.data.stats;
                    this.recentActivities = data.data.recent_activities || [];
                    this.lastUpdated = new Date().toLocaleTimeString('id-ID');
                    this.updateProgressChart();

                    window.showToast('success', 'Dashboard updated successfully');
                } else {
                    throw new Error(data.message || 'Failed to refresh data');
                }
            } catch (error) {
                console.error('Error refreshing dashboard:', error);
                window.showToast('error', 'Failed to refresh dashboard');
            } finally {
                this.loading = false;
            }
        },

        initProgressChart() {
            const canvas = document.getElementById('progressChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            this.progressChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['In Progress', 'Completed', 'Pending', 'Cancelled'],
                    datasets: [{
                        data: [
                            this.stats.in_progress_count || 0,
                            this.stats.completed_customers || 0,
                            this.stats.pending_validation || 0,
                            this.stats.cancelled_customers || 0
                        ],
                        backgroundColor: [
                            '#3B82F6', // Blue
                            '#10B981', // Green
                            '#F59E0B', // Yellow
                            '#EF4444'  // Red
                        ],
                        borderWidth: 0,
                        cutout: '75%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                }
            });
        },

        updateProgressChart() {
            if (this.progressChart) {
                this.progressChart.data.datasets[0].data = [
                    this.stats.in_progress_count || 0,
                    this.stats.completed_customers || 0,
                    this.stats.pending_validation || 0,
                    this.stats.cancelled_customers || 0
                ];
                this.progressChart.update('active');
            }
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    Alpine.store('dashboard', {
        loading: false,
        lastUpdated: new Date().toLocaleString('id-ID')
    });
});
</script>
@endpush
@endsection
