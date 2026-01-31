@extends('layouts.app')

@section('title', 'Jalur Reports')

@section('content')
    <div class="container mx-auto px-6 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Laporan Jalur</h1>
            <p class="text-gray-600">Monitor progress, variance, dan summary jalur pipa</p>
        </div>

        <!-- Report Type Tabs -->
        <div class="mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button onclick="showReport('summary')" id="tab-summary"
                        class="report-tab py-2 px-1 border-b-2 font-medium text-sm border-blue-500 text-blue-600">
                        Summary per Cluster
                    </button>
                    <button onclick="showReport('progress')" id="tab-progress"
                        class="report-tab py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Progress Detail
                    </button>
                    <button onclick="showReport('variance')" id="tab-variance"
                        class="report-tab py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Variance Analysis
                    </button>
                    <button onclick="showReport('comprehensive')" id="tab-comprehensive"
                        class="report-tab py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Laporan Lengkap
                    </button>
                </nav>
            </div>
        </div>

        <!-- Loading State -->
        <div id="loading" class="hidden">
            <div class="bg-white rounded-lg shadow p-8 text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-4 text-gray-600">Loading data...</p>
            </div>
        </div>

        <!-- Summary Report -->
        <div id="report-summary" class="report-content">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Summary per Cluster</h2>
                    <p class="text-sm text-gray-600 mt-1">Ringkasan progress dan variance setiap cluster</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cluster</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lines</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estimasi (m)
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Penggelaran (m)
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actual MC-100
                                    (m)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variance</th>
                            </tr>
                        </thead>
                        <tbody id="summary-data" class="bg-white divide-y divide-gray-200">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Progress Report -->
        <div id="report-progress" class="report-content hidden">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Progress Detail per Line</h2>
                    <p class="text-sm text-gray-600 mt-1">Detail progress setiap line number</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Line Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cluster</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diameter</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lowering Entries
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">MC-100</th>
                            </tr>
                        </thead>
                        <tbody id="progress-data" class="bg-white divide-y divide-gray-200">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Variance Report -->
        <div id="report-variance" class="report-content hidden">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Variance Analysis</h2>
                    <p class="text-sm text-gray-600 mt-1">Analisis perbedaan estimasi vs actual MC-100</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Line Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cluster</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estimasi (m)
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actual MC-100
                                    (m)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variance (m)
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variance (%)
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody id="variance-data" class="bg-white divide-y divide-gray-200">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Comprehensive Report -->
        <div id="report-comprehensive" class="report-content hidden">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Laporan Lengkap Jalur</h2>
                        <p class="text-sm text-gray-600 mt-1">Data komprehensif lowering, joint, dan perhitungan lengkap</p>
                    </div>
                    <div class="flex space-x-2">
                        <button id="btn-export-excel" onclick="exportComprehensiveReport()"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="btn-export-icon" class="mr-2">📊</span>
                            <svg id="btn-export-spinner" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white hidden"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span id="btn-export-text">Export Excel</span>
                        </button>
                        <button onclick="printComprehensiveReport()"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                            🖨️ Print
                        </button>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="p-6 bg-gray-50 border-b">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-white p-4 rounded-lg shadow-sm">
                            <div class="text-sm text-gray-500">Total Line Numbers</div>
                            <div class="text-2xl font-bold text-gray-900" id="comp-total-lines">-</div>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-sm">
                            <div class="text-sm text-gray-500">Total Estimasi</div>
                            <div class="text-2xl font-bold text-blue-600" id="comp-total-estimasi">-</div>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-sm">
                            <div class="text-sm text-gray-500">Total Penggelaran</div>
                            <div class="text-2xl font-bold text-green-600" id="comp-total-penggelaran">-</div>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-sm">
                            <div class="text-sm text-gray-500">Total MC-100</div>
                            <div class="text-2xl font-bold text-purple-600" id="comp-total-mc100">-</div>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <!-- Basic Info -->
                                <th rowspan="2"
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase border-r">Line
                                    Number</th>
                                <th rowspan="2"
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase border-r">Cluster
                                </th>
                                <th rowspan="2"
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase border-r">
                                    Diameter</th>
                                <th rowspan="2"
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase border-r">Jalan
                                </th>

                                <!-- Planning -->
                                <th colspan="2"
                                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase border-r bg-blue-50">
                                    Perencanaan</th>

                                <!-- Lowering Data -->
                                <th colspan="4"
                                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase border-r bg-green-50">
                                    Data Lowering</th>

                                <!-- Joint Data -->
                                <th colspan="3"
                                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase border-r bg-orange-50">
                                    Data Joint</th>

                                <!-- Calculations -->
                                <th colspan="4"
                                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase bg-purple-50">
                                    Perhitungan</th>
                            </tr>
                            <tr>
                                <!-- Planning sub-headers -->
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-r">
                                    Estimasi (m)</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-r">Status
                                </th>

                                <!-- Lowering sub-headers -->
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Entries</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Penggelaran (m)
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">MC-100 (m)</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-r">Last
                                    Update</th>

                                <!-- Joint sub-headers -->
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Joints</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Completed</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-r">Last
                                    Update</th>

                                <!-- Calculation sub-headers -->
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Variance (m)
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Variance (%)
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Progress (%)
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Overall Status
                                </th>
                            </tr>
                        </thead>
                        <tbody id="comprehensive-data" class="bg-white divide-y divide-gray-200">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentReport = 'summary';

        function showReport(type) {
            // Update tabs
            document.querySelectorAll('.report-tab').forEach(tab => {
                tab.classList.remove('border-blue-500', 'text-blue-600');
                tab.classList.add('border-transparent', 'text-gray-500');
            });
            document.getElementById(`tab-${type}`).classList.add('border-blue-500', 'text-blue-600');
            document.getElementById(`tab-${type}`).classList.remove('border-transparent', 'text-gray-500');

            // Update content
            document.querySelectorAll('.report-content').forEach(content => {
                content.classList.add('hidden');
            });
            document.getElementById(`report-${type}`).classList.remove('hidden');

            currentReport = type;
            loadReportData(type);
        }

        function loadReportData(type) {
            document.getElementById('loading').classList.remove('hidden');

            console.log('Loading report data for type:', type); // Debug log

            fetch(`{{ route('jalur.reports.data') }}?type=${type}`)
                .then(response => {
                    console.log('Response received:', response.status); // Debug log
                    return response.json();
                })
                .then(data => {
                    console.log('Data received:', data); // Debug log
                    document.getElementById('loading').classList.add('hidden');
                    renderReportData(type, data);
                })
                .catch(error => {
                    document.getElementById('loading').classList.add('hidden');
                    console.error('Error loading report data:', error);

                    // Show error message to user
                    const container = document.getElementById(`${type}-data`);
                    if (container) {
                        container.innerHTML = `
                        <tr>
                            <td colspan="15" class="px-6 py-8 text-center text-red-500">
                                Error loading data: ${error.message}
                            </td>
                        </tr>
                    `;
                    }
                });
        }

        function renderReportData(type, data) {
            const container = document.getElementById(`${type}-data`);
            container.innerHTML = '';

            // Handle different data structures
            let dataToRender = data;
            if (type === 'comprehensive') {
                dataToRender = data.data || data;
                if (data.totals) {
                    // Update summary cards
                    document.getElementById('comp-total-lines').textContent = data.totals.total_lines;
                    document.getElementById('comp-total-estimasi').textContent = parseFloat(data.totals.total_estimasi).toFixed(1) + 'm';
                    document.getElementById('comp-total-penggelaran').textContent = parseFloat(data.totals.total_penggelaran).toFixed(1) + 'm';
                    document.getElementById('comp-total-mc100').textContent = parseFloat(data.totals.total_mc100).toFixed(1) + 'm';
                }
            }

            if (!Array.isArray(dataToRender) || dataToRender.length === 0) {
                const colspan = type === 'comprehensive' ? '17' : '7';
                container.innerHTML = `
                <tr>
                    <td colspan="${colspan}" class="px-6 py-8 text-center text-gray-500">
                        Tidak ada data untuk ditampilkan
                    </td>
                </tr>
            `;
                return;
            }

            dataToRender.forEach(item => {
                let row = '';

                if (type === 'summary') {
                    const progress = item.total_lines > 0 ? (item.completed_lines / item.total_lines * 100).toFixed(1) : 0;
                    const variance = item.variance;
                    const varianceClass = variance === null ? 'text-gray-400' :
                        (variance > 0 ? 'text-red-600' : (variance < 0 ? 'text-green-600' : 'text-gray-600'));
                    const varianceDisplay = variance === null ? '-' :
                        `${variance > 0 ? '+' : ''}${parseFloat(variance).toFixed(1)}m`;

                    row = `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-gray-900">${item.cluster}</div>
                            <div class="text-sm text-gray-500">${item.code}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="font-semibold">${item.completed_lines}</span>/${item.total_lines}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-3">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: ${progress}%"></div>
                                </div>
                                <span class="text-sm text-gray-900">${progress}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${parseFloat(item.total_estimate).toFixed(1)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${parseFloat(item.total_penggelaran).toFixed(1)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${parseFloat(item.total_actual || 0).toFixed(1)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${varianceClass}">
                            ${varianceDisplay}
                        </td>
                    </tr>
                `;
                } else if (type === 'progress') {
                    const statusClass = item.status === 'completed' ? 'bg-green-100 text-green-800' :
                        (item.status === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800');

                    row = `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">${item.line_number}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.cluster}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Ø${item.diameter}mm</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs rounded-full ${statusClass}">
                                ${item.status}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-3">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: ${Math.min(100, item.progress_percentage)}%"></div>
                                </div>
                                <span class="text-sm text-gray-900">${parseFloat(item.progress_percentage).toFixed(1)}%</span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                ${parseFloat(item.total_penggelaran).toFixed(1)}m / ${parseFloat(item.estimasi_panjang).toFixed(1)}m
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.lowering_count}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${item.actual_mc100 ? parseFloat(item.actual_mc100).toFixed(1) + 'm' : '-'}
                        </td>
                    </tr>
                `;
                } else if (type === 'variance') {
                    const variancePercent = item.variance_percentage || 0;
                    const statusClass = item.status === 'over' ? 'bg-red-100 text-red-800' :
                        (item.status === 'under' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800');
                    const statusText = item.status === 'over' ? 'Over Budget' :
                        (item.status === 'under' ? 'Under Budget' : 'Normal');

                    row = `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">${item.line_number}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.cluster}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${parseFloat(item.estimasi_panjang).toFixed(1)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${parseFloat(item.actual_mc100).toFixed(1)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${item.variance >= 0 ? 'text-red-600' : 'text-green-600'}">
                            ${item.variance >= 0 ? '+' : ''}${parseFloat(item.variance).toFixed(1)}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${variancePercent >= 0 ? 'text-red-600' : 'text-green-600'}">
                            ${variancePercent >= 0 ? '+' : ''}${parseFloat(variancePercent).toFixed(1)}%
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs rounded-full ${statusClass}">
                                ${statusText}
                            </span>
                        </td>
                    </tr>
                `;
                } else if (type === 'comprehensive') {
                    // Update summary cards if data has totals
                    if (data.totals) {
                        document.getElementById('comp-total-lines').textContent = data.totals.total_lines;
                        document.getElementById('comp-total-estimasi').textContent = parseFloat(data.totals.total_estimasi).toFixed(1) + 'm';
                        document.getElementById('comp-total-penggelaran').textContent = parseFloat(data.totals.total_penggelaran).toFixed(1) + 'm';
                        document.getElementById('comp-total-mc100').textContent = parseFloat(data.totals.total_mc100).toFixed(1) + 'm';
                    }

                    // Render comprehensive table
                    const actualData = data.data || data;
                    if (!Array.isArray(actualData)) return;

                    actualData.forEach(item => {
                        const variance = item.variance !== null ? parseFloat(item.variance).toFixed(1) : '-';
                        const variancePercent = item.variance_percentage !== null ? parseFloat(item.variance_percentage).toFixed(1) : '-';
                        const progress = item.progress_percentage ? parseFloat(item.progress_percentage).toFixed(1) : '0.0';

                        const statusClass = item.overall_status === 'completed' ? 'bg-green-100 text-green-800' :
                            (item.overall_status === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800');

                        const varianceClass = item.variance === null ? 'text-gray-400' :
                            (item.variance > 0 ? 'text-red-600' : (item.variance < 0 ? 'text-green-600' : 'text-gray-600'));

                        const variancePercentClass = item.variance_percentage === null ? 'text-gray-400' :
                            (item.variance_percentage > 0 ? 'text-red-600' : (item.variance_percentage < 0 ? 'text-green-600' : 'text-gray-600'));

                        row = `
                        <tr class="hover:bg-gray-50 text-sm">
                            <!-- Basic Info -->
                            <td class="px-4 py-3 font-medium text-gray-900 border-r">${item.line_number}</td>
                            <td class="px-4 py-3 text-gray-900 border-r">
                                <div class="font-medium">${item.cluster}</div>
                                <div class="text-xs text-gray-500">${item.cluster_code}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-900 border-r">Ø${item.diameter}mm</td>
                            <td class="px-4 py-3 text-gray-900 border-r">
                                <div class="max-w-32 truncate" title="${item.nama_jalan || '-'}">${item.nama_jalan || '-'}</div>
                            </td>

                            <!-- Planning -->
                            <td class="px-4 py-3 text-gray-900">${parseFloat(item.estimasi_panjang || 0).toFixed(1)}m</td>
                            <td class="px-4 py-3 border-r">
                                <span class="inline-flex px-2 py-1 text-xs rounded-full ${item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${item.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </td>

                            <!-- Lowering Data -->
                            <td class="px-4 py-3 text-gray-900">${item.lowering_entries || 0}</td>
                            <td class="px-4 py-3 text-gray-900">${parseFloat(item.total_penggelaran || 0).toFixed(1)}m</td>
                            <td class="px-4 py-3 text-gray-900">${parseFloat(item.actual_mc100 || 0).toFixed(1)}m</td>
                            <td class="px-4 py-3 text-gray-500 border-r">
                                <div class="text-xs">${item.lowering_last_update ? new Date(item.lowering_last_update).toLocaleDateString('id-ID') : '-'}</div>
                            </td>

                            <!-- Joint Data -->
                            <td class="px-4 py-3 text-gray-900">${item.joint_total || 0}</td>
                            <td class="px-4 py-3 text-gray-900">${item.joint_completed || 0}</td>
                            <td class="px-4 py-3 text-gray-500 border-r">
                                <div class="text-xs">${item.joint_last_update ? new Date(item.joint_last_update).toLocaleDateString('id-ID') : '-'}</div>
                            </td>

                            <!-- Calculations -->
                            <td class="px-4 py-3 ${varianceClass}">${variance !== '-' ? (item.variance >= 0 ? '+' : '') + variance + 'm' : '-'}</td>
                            <td class="px-4 py-3 ${variancePercentClass}">${variancePercent !== '-' ? (item.variance_percentage >= 0 ? '+' : '') + variancePercent + '%' : '-'}</td>
                            <td class="px-4 py-3 text-gray-900">
                                <div class="flex items-center">
                                    <div class="w-12 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: ${Math.min(100, parseFloat(progress))}%"></div>
                                    </div>
                                    <span class="text-xs">${progress}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs rounded-full ${statusClass}">
                                    ${item.overall_status || 'pending'}
                                </span>
                            </td>
                        </tr>
                    `;
                    });
                }

                if (type !== 'comprehensive') {
                    container.innerHTML += row;
                }
            });

            // For comprehensive report, render all rows at once for better performance
            if (type === 'comprehensive') {
                const allRows = data.data || data;
                if (Array.isArray(allRows)) {
                    container.innerHTML = allRows.map(item => {
                        const variance = item.variance !== null ? parseFloat(item.variance).toFixed(1) : '-';
                        const variancePercent = item.variance_percentage !== null ? parseFloat(item.variance_percentage).toFixed(1) : '-';
                        const progress = item.progress_percentage ? parseFloat(item.progress_percentage).toFixed(1) : '0.0';

                        const statusClass = item.overall_status === 'completed' ? 'bg-green-100 text-green-800' :
                            (item.overall_status === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800');

                        const varianceClass = item.variance === null ? 'text-gray-400' :
                            (item.variance > 0 ? 'text-red-600' : (item.variance < 0 ? 'text-green-600' : 'text-gray-600'));

                        const variancePercentClass = item.variance_percentage === null ? 'text-gray-400' :
                            (item.variance_percentage > 0 ? 'text-red-600' : (item.variance_percentage < 0 ? 'text-green-600' : 'text-gray-600'));

                        return `
                        <tr class="hover:bg-gray-50 text-sm">
                            <!-- Basic Info -->
                            <td class="px-4 py-3 font-medium text-gray-900 border-r">${item.line_number}</td>
                            <td class="px-4 py-3 text-gray-900 border-r">
                                <div class="font-medium">${item.cluster}</div>
                                <div class="text-xs text-gray-500">${item.cluster_code}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-900 border-r">Ø${item.diameter}mm</td>
                            <td class="px-4 py-3 text-gray-900 border-r">
                                <div class="max-w-32 truncate" title="${item.nama_jalan || '-'}">${item.nama_jalan || '-'}</div>
                            </td>

                            <!-- Planning -->
                            <td class="px-4 py-3 text-gray-900">${parseFloat(item.estimasi_panjang || 0).toFixed(1)}m</td>
                            <td class="px-4 py-3 border-r">
                                <span class="inline-flex px-2 py-1 text-xs rounded-full ${item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${item.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </td>

                            <!-- Lowering Data -->
                            <td class="px-4 py-3 text-gray-900">${item.lowering_entries || 0}</td>
                            <td class="px-4 py-3 text-gray-900">${parseFloat(item.total_penggelaran || 0).toFixed(1)}m</td>
                            <td class="px-4 py-3 text-gray-900">${parseFloat(item.actual_mc100 || 0).toFixed(1)}m</td>
                            <td class="px-4 py-3 text-gray-500 border-r">
                                <div class="text-xs">${item.lowering_last_update ? new Date(item.lowering_last_update).toLocaleDateString('id-ID') : '-'}</div>
                            </td>

                            <!-- Joint Data -->
                            <td class="px-4 py-3 text-gray-900">${item.joint_total || 0}</td>
                            <td class="px-4 py-3 text-gray-900">${item.joint_completed || 0}</td>
                            <td class="px-4 py-3 text-gray-500 border-r">
                                <div class="text-xs">${item.joint_last_update ? new Date(item.joint_last_update).toLocaleDateString('id-ID') : '-'}</div>
                            </td>

                            <!-- Calculations -->
                            <td class="px-4 py-3 ${varianceClass}">${variance !== '-' ? (item.variance >= 0 ? '+' : '') + variance + 'm' : '-'}</td>
                            <td class="px-4 py-3 ${variancePercentClass}">${variancePercent !== '-' ? (item.variance_percentage >= 0 ? '+' : '') + variancePercent + '%' : '-'}</td>
                            <td class="px-4 py-3 text-gray-900">
                                <div class="flex items-center">
                                    <div class="w-12 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: ${Math.min(100, parseFloat(progress))}%"></div>
                                    </div>
                                    <span class="text-xs">${progress}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs rounded-full ${statusClass}">
                                    ${item.overall_status || 'pending'}
                                </span>
                            </td>
                        </tr>
                    `;
                    }).join('');
                }
            }
        }

        // Export and Print Functions
        function exportComprehensiveReport() {
            const btn = document.getElementById('btn-export-excel');
            const spinner = document.getElementById('btn-export-spinner');
            const icon = document.getElementById('btn-export-icon');
            const text = document.getElementById('btn-export-text');

            // Set loading state
            if(btn) {
                btn.disabled = true;
                spinner.classList.remove('hidden');
                icon.classList.add('hidden');
                text.textContent = 'Generating Excel...';
            }

            const exportUrl = `{{ route('jalur.reports.data') }}?type=comprehensive&export=excel`;

            fetch(exportUrl)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.blob();
                })
                .then(blob => {
                    // Create a temporary link element to trigger the download
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    // Try to extract filename from content-disposition header if possible, otherwise default
                    a.download = `Laporan_Lengkap_Jalur_${new Date().toISOString().slice(0,10)}.xlsx`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);

                    // Reset button state
                    if(btn) {
                        btn.disabled = false;
                        spinner.classList.add('hidden');
                        icon.classList.remove('hidden');
                        text.textContent = 'Export Excel';
                    }
                })
                .catch(error => {
                    console.error('Export failed:', error);
                    alert('Gagal mengunduh file Excel. Silakan coba lagi.');

                    // Reset button state
                    if(btn) {
                        btn.disabled = false;
                        spinner.classList.add('hidden');
                        icon.classList.remove('hidden');
                        text.textContent = 'Export Excel';
                    }
                });
        }

        function printComprehensiveReport() {
            const printContent = document.getElementById('report-comprehensive').innerHTML;
            const originalContent = document.body.innerHTML;

            // Create print-friendly styles
            const printStyles = `
            <style>
                @media print {
                    body { font-size: 12px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
                    .no-print { display: none !important; }
                    .bg-blue-50, .bg-green-50, .bg-orange-50, .bg-purple-50 { background-color: #f8f9fa !important; }
                }
            </style>
        `;

            document.body.innerHTML = printStyles + '<div class="p-4">' +
                '<h1>Laporan Lengkap Jalur - ' + new Date().toLocaleDateString('id-ID') + '</h1>' +
                printContent + '</div>';

            window.print();
            document.body.innerHTML = originalContent;

            // Reload the page to restore functionality
            setTimeout(() => {
                window.location.reload();
            }, 100);
        }

        // Load initial data
        document.addEventListener('DOMContentLoaded', function () {
            loadReportData('summary');
        });
    </script>
@endsection