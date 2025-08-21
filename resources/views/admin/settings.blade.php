@extends('layouts.app')

@section('title', 'System Settings')
@section('page-title', 'System Settings')

@section('content')
<div class="space-y-6" x-data="systemSettings()">

    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center space-y-4 lg:space-y-0">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">System Settings</h1>
            <p class="text-gray-600 mt-1">Monitor system performance and manage integrations</p>
        </div>
        <div class="flex items-center space-x-3">
            <button @click="refreshStats()"
                    :disabled="loading"
                    class="flex items-center space-x-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all duration-300 disabled:opacity-50">
                <i class="fas fa-sync-alt" :class="{ 'animate-spin': loading }"></i>
                <span>Refresh Stats</span>
            </button>
            <button @click="testIntegrations()"
                    :disabled="testing"
                    class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-aergas-navy to-aergas-orange text-white rounded-lg hover:shadow-lg transition-all duration-300 disabled:opacity-50">
                <i class="fas fa-check-circle" :class="{ 'animate-spin': testing }"></i>
                <span x-text="testing ? 'Testing...' : 'Test Integrations'">Test Integrations</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">System Overview</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-users text-white"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">Total Users</div>
                                    <div class="text-sm text-gray-600" x-text="`${stats.users?.active || 0} active / ${stats.users?.total || 0} total`">0 active / 0 total</div>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-blue-600" x-text="stats.users?.total || 0">0</div>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-user-check text-white"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">Customers</div>
                                    <div class="text-sm text-gray-600">Registered customers</div>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-green-600" x-text="stats.customers?.total || 0">0</div>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-purple-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-camera text-white"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">Photo Approvals</div>
                                    <div class="text-sm text-gray-600" x-text="`${stats.photos?.approval_rate || 0}% approval rate`">0% approval rate</div>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-purple-600" x-text="stats.photos?.total || 0">0</div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-orange-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-tasks text-white"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">SK Module</div>
                                    <div class="text-sm text-gray-600" x-text="`${stats.modules?.sk?.completed || 0} completed`">0 completed</div>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-orange-600" x-text="stats.modules?.sk?.total || 0">0</div>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-yellow-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-route text-white"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">SR Module</div>
                                    <div class="text-sm text-gray-600" x-text="`${stats.modules?.sr?.completed || 0} completed`">0 completed</div>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-yellow-600" x-text="stats.modules?.sr?.total || 0">0</div>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-gas-pump text-white"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">Gas In Module</div>
                                    <div class="text-sm text-gray-600" x-text="`${stats.modules?.gas_in?.completed || 0} completed`">0 completed</div>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-red-600" x-text="stats.modules?.gas_in?.total || 0">0</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Storage Statistics</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Total Files</span>
                            <span class="text-sm text-gray-900" x-text="stats.storage?.total_files || 0">0</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Total Size</span>
                            <span class="text-sm text-gray-900" x-text="stats.storage?.total_size_human || '0 B'">0 B</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Recent Uploads</span>
                            <span class="text-sm text-gray-900" x-text="stats.storage?.recent_uploads || 0">0</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="text-sm font-medium text-gray-700 mb-3">Storage by Module</div>
                        <template x-for="(module, name) in stats.storage?.by_module || {}" :key="name">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600" x-text="name.toUpperCase()">MODULE</span>
                                <div class="text-right">
                                    <div class="text-gray-900" x-text="module.count + ' files'">0 files</div>
                                    <div class="text-gray-500 text-xs" x-text="module.size_human">0 B</div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Performance Metrics</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600" x-text="stats.performance?.daily_completions || 0">0</div>
                        <div class="text-sm text-gray-600">Daily Completions</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600" x-text="(stats.performance?.avg_processing_time || 0).toFixed(1) + 'h'">0h</div>
                        <div class="text-sm text-gray-600">Avg Processing Time</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-purple-600" x-text="(stats.performance?.sla_compliance?.compliance_rate || 0) + '%'">0%</div>
                        <div class="text-sm text-gray-600">SLA Compliance</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Integration Status</h2>

                <div class="space-y-4">
                    <template x-for="(integration, name) in integrations" :key="name">
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                     :class="getStatusColor(integration.status)">
                                    <i class="fas fa-circle text-xs text-white"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900" x-text="formatIntegrationName(name)">Integration</div>
                                    <div class="text-sm text-gray-500" x-text="integration.message || 'No message'">Status message</div>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  :class="getStatusBadgeClass(integration.status)"
                                  x-text="integration.status || 'unknown'">
                            </span>
                        </div>
                    </template>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Recent Activities</h2>

                <div class="space-y-3">
                    <template x-for="activity in stats.recent_activities || []" :key="activity.id">
                        <div class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-circle text-xs text-gray-400"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900 font-medium" x-text="activity.description">Activity description</p>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="text-xs text-gray-500" x-text="activity.user">User</span>
                                    <span class="text-xs text-gray-400">•</span>
                                    <span class="text-xs text-gray-500" x-text="activity.created_at_human">Time ago</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">System Actions</h2>

                <div class="space-y-3">
                    <button @click="clearCache()"
                            class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all duration-300">
                        <i class="fas fa-broom"></i>
                        <span>Clear Cache</span>
                    </button>

                    <button @click="exportLogs()"
                            class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-all duration-300">
                        <i class="fas fa-download"></i>
                        <span>Export Logs</span>
                    </button>

                    <button @click="backupDatabase()"
                            class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-all duration-300">
                        <i class="fas fa-database"></i>
                        <span>Backup Database</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function systemSettings() {
    return {
        stats: {},
        integrations: {},
        loading: false,
        testing: false,

        init() {
            this.loadStats();
        },

        async loadStats() {
            this.loading = true;
            try {
                const response = await fetch('{{ route('admin.api.system-stats') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    }
                });

                const result = await response.json();
                if (result.success) {
                    this.stats = result.data;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
                window.showToast('error', 'Failed to load system stats');
            } finally {
                this.loading = false;
            }
        },

        async refreshStats() {
            await this.loadStats();
            window.showToast('success', 'System stats refreshed');
        },

        async testIntegrations() {
            this.testing = true;
            try {
                const response = await fetch('{{ route('admin.api.test-integrations') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    }
                });

                const result = await response.json();
                if (result.success) {
                    this.integrations = result.results;
                    const overall = result.overall_status;

                    if (overall === 'success') {
                        window.showToast('success', 'All integrations are working properly');
                    } else {
                        window.showToast('warning', 'Some integrations have issues');
                    }
                }
            } catch (error) {
                console.error('Error testing integrations:', error);
                window.showToast('error', 'Failed to test integrations');
            } finally {
                this.testing = false;
            }
        },

        formatIntegrationName(name) {
            return name.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        },

        getStatusColor(status) {
            const colors = {
                'success': 'bg-green-500',
                'failed': 'bg-red-500',
                'error': 'bg-red-600',
                'partial': 'bg-yellow-500'
            };
            return colors[status] || 'bg-gray-500';
        },

        getStatusBadgeClass(status) {
            const classes = {
                'success': 'bg-green-100 text-green-800',
                'failed': 'bg-red-100 text-red-800',
                'error': 'bg-red-100 text-red-800',
                'partial': 'bg-yellow-100 text-yellow-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },

        async clearCache() {
            if (confirm('Are you sure you want to clear the cache?')) {
                try {
                    window.showToast('info', 'Cache clearing functionality not implemented yet');
                } catch (error) {
                    window.showToast('error', 'Failed to clear cache');
                }
            }
        },

        async exportLogs() {
            try {
                window.showToast('info', 'Log export functionality not implemented yet');
            } catch (error) {
                window.showToast('error', 'Failed to export logs');
            }
        },

        async backupDatabase() {
            if (confirm('Are you sure you want to create a database backup?')) {
                try {
                    window.showToast('info', 'Database backup functionality not implemented yet');
                } catch (error) {
                    window.showToast('error', 'Failed to backup database');
                }
            }
        }
    }
}
</script>
@endpush
@endsection
