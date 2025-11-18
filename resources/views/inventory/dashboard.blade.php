@extends('layouts.app')

@section('title', 'Inventory Dashboard - AERGAS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Inventory Dashboard</h1>
            <p class="text-gray-600 mt-1">Overview of inventory management</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.warehouses.index') }}" class="px-4 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                <i class="fas fa-warehouse mr-2"></i>Warehouses
            </a>
            <a href="{{ route('inventory.items.index') }}" class="px-4 py-2 bg-green-100 text-green-700 rounded hover:bg-green-200">
                <i class="fas fa-box mr-2"></i>Items
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <!-- Total Warehouses -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-blue-600 font-medium">Total Warehouses</div>
                    <div class="text-3xl font-bold text-blue-700 mt-1">{{ $stats['total_warehouses'] ?? 0 }}</div>
                </div>
                <div class="w-12 h-12 bg-blue-200 rounded-lg flex items-center justify-center">
                    <i class="fas fa-warehouse text-blue-700 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Active Warehouses -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-green-600 font-medium">Active Warehouses</div>
                    <div class="text-3xl font-bold text-green-700 mt-1">{{ $stats['active_warehouses'] ?? 0 }}</div>
                </div>
                <div class="w-12 h-12 bg-green-200 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-700 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Items -->
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 border border-purple-200">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-purple-600 font-medium">Total Items</div>
                    <div class="text-3xl font-bold text-purple-700 mt-1">{{ $stats['total_items'] ?? 0 }}</div>
                </div>
                <div class="w-12 h-12 bg-purple-200 rounded-lg flex items-center justify-center">
                    <i class="fas fa-box text-purple-700 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Categories -->
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-6 border border-orange-200">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-orange-600 font-medium">Total Categories</div>
                    <div class="text-3xl font-bold text-orange-700 mt-1">{{ $stats['total_categories'] ?? 0 }}</div>
                </div>
                <div class="w-12 h-12 bg-orange-200 rounded-lg flex items-center justify-center">
                    <i class="fas fa-tags text-orange-700 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Suppliers -->
        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-6 border border-indigo-200">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-indigo-600 font-medium">Total Suppliers</div>
                    <div class="text-3xl font-bold text-indigo-700 mt-1">{{ $stats['total_suppliers'] ?? 0 }}</div>
                </div>
                <div class="w-12 h-12 bg-indigo-200 rounded-lg flex items-center justify-center">
                    <i class="fas fa-truck text-indigo-700 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Low Stock Items -->
        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-6 border border-red-200">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-red-600 font-medium">Low Stock Items</div>
                    <div class="text-3xl font-bold text-red-700 mt-1">{{ $stats['low_stock_items'] ?? 0 }}</div>
                </div>
                <div class="w-12 h-12 bg-red-200 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-700 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <div class="flex items-center gap-3 mb-4">
            <i class="fas fa-bolt text-aergas-orange"></i>
            <h2 class="text-xl font-semibold text-gray-800">Quick Actions</h2>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <a href="{{ route('inventory.warehouses.create') }}" class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mb-2">
                    <i class="fas fa-warehouse text-white text-xl"></i>
                </div>
                <span class="text-sm font-medium text-gray-700 text-center">Add Warehouse</span>
            </a>

            <a href="{{ route('inventory.items.create') }}" class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mb-2">
                    <i class="fas fa-box text-white text-xl"></i>
                </div>
                <span class="text-sm font-medium text-gray-700 text-center">Add Item</span>
            </a>

            <a href="{{ route('inventory.transactions.create') }}" class="flex flex-col items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mb-2">
                    <i class="fas fa-exchange-alt text-white text-xl"></i>
                </div>
                <span class="text-sm font-medium text-gray-700 text-center">Stock Transaction</span>
            </a>

            <a href="{{ route('inventory.suppliers.create') }}" class="flex flex-col items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">
                <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center mb-2">
                    <i class="fas fa-truck text-white text-xl"></i>
                </div>
                <span class="text-sm font-medium text-gray-700 text-center">Add Supplier</span>
            </a>

            <a href="{{ route('inventory.purchase-orders.create') }}" class="flex flex-col items-center p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                <div class="w-12 h-12 bg-indigo-500 rounded-lg flex items-center justify-center mb-2">
                    <i class="fas fa-shopping-cart text-white text-xl"></i>
                </div>
                <span class="text-sm font-medium text-gray-700 text-center">Create PO</span>
            </a>

            <a href="{{ route('inventory.stock-opnames.create') }}" class="flex flex-col items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors">
                <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center mb-2">
                    <i class="fas fa-clipboard-check text-white text-xl"></i>
                </div>
                <span class="text-sm font-medium text-gray-700 text-center">Stock Opname</span>
            </a>
        </div>
    </div>

    <!-- Warehouse Summary -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-gradient-to-br from-aergas-navy to-aergas-orange rounded-lg flex items-center justify-center">
                <i class="fas fa-warehouse text-white"></i>
            </div>
            <h2 class="text-xl font-semibold text-gray-800">Warehouse Summary</h2>
        </div>

        @if($warehouses && $warehouses->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Warehouse</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Items</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($warehouses as $warehouse)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $warehouse->name }}</div>
                                <div class="text-sm text-gray-500">{{ $warehouse->code }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($warehouse->warehouse_type === 'pusat') bg-purple-100 text-purple-800
                                    @elseif($warehouse->warehouse_type === 'cabang') bg-blue-100 text-blue-800
                                    @else bg-green-100 text-green-800
                                    @endif">
                                    {{ ucfirst($warehouse->warehouse_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $warehouse->city ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $warehouse->stocks_count ?? 0 }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $warehouse->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $warehouse->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('inventory.warehouses.show', $warehouse) }}" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye mr-1"></i>View
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500 text-sm">No warehouses available</p>
            </div>
        @endif
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-history text-white"></i>
                </div>
                <h2 class="text-xl font-semibold text-gray-800">Recent Transactions</h2>
            </div>
            <a href="{{ route('inventory.transactions.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        @if($recentTransactions && $recentTransactions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Warehouse</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentTransactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">#{{ $transaction->id }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($transaction->transaction_type === 'in') bg-green-100 text-green-800
                                    @elseif($transaction->transaction_type === 'out') bg-red-100 text-red-800
                                    @elseif($transaction->transaction_type === 'transfer') bg-blue-100 text-blue-800
                                    @elseif($transaction->transaction_type === 'adjustment') bg-yellow-100 text-yellow-800
                                    @else bg-purple-100 text-purple-800
                                    @endif">
                                    {{ ucfirst($transaction->transaction_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $transaction->item->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $transaction->warehouse->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $transaction->quantity }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $transaction->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500 text-sm">No recent transactions</p>
            </div>
        @endif
    </div>

    <!-- Analytics & Charts -->
    <div class="mt-8 mb-4">
        <h2 class="text-2xl font-bold text-gray-800">Analytics & Insights</h2>
        <p class="text-gray-600 text-sm">Visual analytics of inventory data and trends</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Transaction Trends (Last 7 Days) -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Transaction Trends (Last 7 Days)</h3>
            <div style="height: 300px;">
                <canvas id="transactionTrendsChart"></canvas>
            </div>
        </div>

        <!-- Stock Value by Warehouse -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Stock Value by Warehouse</h3>
            <div style="height: 300px;">
                <canvas id="stockValueChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Top 10 Items by Stock -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Top 10 Items by Stock Quantity</h3>
            <div style="height: 300px;">
                <canvas id="topItemsChart"></canvas>
            </div>
        </div>

        <!-- Category Distribution -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Items by Category</h3>
            <div style="height: 300px;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Monthly Transaction Summary -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Monthly Transactions (Last 6 Months)</h3>
            <div style="height: 300px;">
                <canvas id="monthlyTransactionsChart"></canvas>
            </div>
        </div>

        <!-- Low Stock Alert List -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                Low Stock Alert
            </h3>
            @if($lowStockItems->count() > 0)
                <div class="space-y-3">
                    @foreach($lowStockItems as $item)
                        <div class="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">{{ $item->name }}</div>
                                <div class="text-xs text-gray-600">{{ $item->category->name ?? '-' }} • {{ $item->code }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold text-red-600">{{ number_format($item->total_stock ?? 0, 2) }} {{ $item->unit }}</div>
                                <div class="text-xs text-gray-500">Reorder: {{ $item->reorder_point }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                    <p class="text-gray-600">All items have sufficient stock</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
// Color palette
const colors = {
    primary: '#3B82F6',
    success: '#10B981',
    danger: '#EF4444',
    warning: '#F59E0B',
    info: '#06B6D4',
    purple: '#8B5CF6',
    pink: '#EC4899',
    indigo: '#6366F1',
};

// Default chart options to prevent animation loop
Chart.defaults.animation = {
    duration: 750,
    easing: 'easeInOutQuart',
    loop: false  // IMPORTANT: Prevent animation loop
};

Chart.defaults.responsive = true;
Chart.defaults.maintainAspectRatio = false;

// 1. Transaction Trends Chart (Line)
const transactionTrendsCtx = document.getElementById('transactionTrendsChart').getContext('2d');
let transactionTrendsData = @json($transactionTrends);

// Use dummy data if empty
if (!transactionTrendsData || transactionTrendsData.length === 0) {
    const last7Days = [];
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        last7Days.push(date.toISOString().split('T')[0]);
    }

    transactionTrendsData = [];
    last7Days.forEach((date, index) => {
        transactionTrendsData.push(
            { date: date, transaction_type: 'in', count: Math.floor(Math.random() * 10) + 5 },
            { date: date, transaction_type: 'out', count: Math.floor(Math.random() * 8) + 3 },
            { date: date, transaction_type: 'transfer', count: Math.floor(Math.random() * 5) + 1 },
            { date: date, transaction_type: 'adjustment', count: Math.floor(Math.random() * 3) }
        );
    });
}

// Process data for chart
const dates = [...new Set(transactionTrendsData.map(t => t.date))].sort();
const types = ['in', 'out', 'transfer', 'adjustment'];
const datasets = types.map(type => {
    const colorMap = {
        'in': colors.success,
        'out': colors.danger,
        'transfer': colors.info,
        'adjustment': colors.warning
    };
    return {
        label: type.toUpperCase(),
        data: dates.map(date => {
            const item = transactionTrendsData.find(t => t.date === date && t.transaction_type === type);
            return item ? item.count : 0;
        }),
        borderColor: colorMap[type],
        backgroundColor: colorMap[type] + '20',
        tension: 0.4,
        fill: false
    };
});

new Chart(transactionTrendsCtx, {
    type: 'line',
    data: {
        labels: dates.map(d => {
            const date = new Date(d);
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        }),
        datasets: datasets
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 750,
            easing: 'easeInOutQuart'
        },
        plugins: {
            legend: { position: 'top' },
            title: { display: false },
            tooltip: {
                mode: 'index',
                intersect: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// 2. Stock Value by Warehouse (Bar)
const stockValueCtx = document.getElementById('stockValueChart').getContext('2d');
let stockValueData = @json($stockValueByWarehouse);

// Use dummy data if empty
if (!stockValueData || stockValueData.length === 0) {
    stockValueData = [
        { name: 'Gudang Jakarta Pusat', value: 125000000 },
        { name: 'Gudang Jakarta Selatan', value: 98000000 },
        { name: 'Gudang Bandung', value: 75000000 }
    ];
}

new Chart(stockValueCtx, {
    type: 'bar',
    data: {
        labels: stockValueData.map(w => w.name),
        datasets: [{
            label: 'Stock Value (Rp)',
            data: stockValueData.map(w => w.value),
            backgroundColor: colors.primary,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 750
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Value: Rp ' + context.parsed.y.toLocaleString('id-ID');
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + (value / 1000000).toFixed(0) + 'jt';
                    }
                }
            }
        }
    }
});

// 3. Top Items by Stock (Horizontal Bar)
const topItemsCtx = document.getElementById('topItemsChart').getContext('2d');
let topItemsData = @json($topItemsByStock);

// Use dummy data if empty
if (!topItemsData || topItemsData.length === 0) {
    topItemsData = [
        { name: 'Pipa PE 20mm', stock: 1500, unit: 'm' },
        { name: 'Elbow 1/2" Galvanis', stock: 850, unit: 'pcs' },
        { name: 'Ball Valve 1/2"', stock: 620, unit: 'pcs' },
        { name: 'Coupler 1/2"', stock: 580, unit: 'pcs' },
        { name: 'Gas Meter G1.6', stock: 450, unit: 'unit' },
        { name: 'Regulator MGRT', stock: 380, unit: 'unit' },
        { name: 'Hose 1/2" Meter', stock: 320, unit: 'm' },
        { name: 'Tee 1/2" Galvanis', stock: 280, unit: 'pcs' }
    ];
}

new Chart(topItemsCtx, {
    type: 'bar',
    data: {
        labels: topItemsData.map(i => i.name),
        datasets: [{
            label: 'Stock Quantity',
            data: topItemsData.map(i => i.stock),
            backgroundColor: colors.success,
            borderRadius: 6
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 750
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const item = topItemsData[context.dataIndex];
                        return context.parsed.x + ' ' + item.unit;
                    }
                }
            }
        },
        scales: {
            x: { beginAtZero: true }
        }
    }
});

// 4. Category Distribution (Doughnut)
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
let categoryData = @json($categoryDistribution);

// Use dummy data if empty
if (!categoryData || categoryData.length === 0) {
    categoryData = [
        { name: 'Pipa', count: 15 },
        { name: 'Fitting', count: 25 },
        { name: 'Valve', count: 12 },
        { name: 'Equipment', count: 18 },
        { name: 'Accessory', count: 10 }
    ];
}

new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: categoryData.map(c => c.name),
        datasets: [{
            data: categoryData.map(c => c.count),
            backgroundColor: [
                colors.primary,
                colors.success,
                colors.warning,
                colors.danger,
                colors.purple,
                colors.pink,
                colors.indigo,
                colors.info,
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 750
        },
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    padding: 15,
                    usePointStyle: true
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': ' + context.parsed + ' items (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// 5. Monthly Transactions (Stacked Bar)
const monthlyTransactionsCtx = document.getElementById('monthlyTransactionsChart').getContext('2d');
let monthlyTransactionsData = @json($monthlyTransactions);

// Use dummy data if empty
if (!monthlyTransactionsData || monthlyTransactionsData.length === 0) {
    const last6Months = [];
    for (let i = 5; i >= 0; i--) {
        const date = new Date();
        date.setMonth(date.getMonth() - i);
        const month = date.toISOString().slice(0, 7);
        last6Months.push(month);
    }

    monthlyTransactionsData = [];
    last6Months.forEach(month => {
        monthlyTransactionsData.push(
            { month: month, transaction_type: 'in', count: Math.floor(Math.random() * 50) + 20 },
            { month: month, transaction_type: 'out', count: Math.floor(Math.random() * 40) + 15 },
            { month: month, transaction_type: 'transfer', count: Math.floor(Math.random() * 20) + 5 },
            { month: month, transaction_type: 'adjustment', count: Math.floor(Math.random() * 10) + 2 }
        );
    });
}

const months = [...new Set(monthlyTransactionsData.map(t => t.month))].sort();
const monthlyDatasets = types.map(type => {
    const colorMap = {
        'in': colors.success,
        'out': colors.danger,
        'transfer': colors.info,
        'adjustment': colors.warning
    };
    return {
        label: type.toUpperCase(),
        data: months.map(month => {
            const item = monthlyTransactionsData.find(t => t.month === month && t.transaction_type === type);
            return item ? item.count : 0;
        }),
        backgroundColor: colorMap[type],
        borderRadius: 6
    };
});

new Chart(monthlyTransactionsCtx, {
    type: 'bar',
    data: {
        labels: months.map(m => {
            const date = new Date(m + '-01');
            return date.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
        }),
        datasets: monthlyDatasets
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 750
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true
                }
            },
            tooltip: {
                mode: 'index',
                intersect: false
            }
        },
        scales: {
            x: { stacked: true },
            y: {
                stacked: true,
                beginAtZero: true,
                ticks: {
                    stepSize: 10
                }
            }
        }
    }
});
</script>
@endpush
@endsection
