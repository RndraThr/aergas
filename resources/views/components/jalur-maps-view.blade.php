<script>
    // Pre-define window.jalurMapsData immediately to avoid Alpine initialization errors
    // This ensures all properties exist when Alpine initializes the component
    // Full implementation with methods will override this below
    if (typeof window.jalurMapsData === 'undefined') {
        window.jalurMapsData = function () {
            return {
                map: null,
                loading: true,
                features: [],
                featureLayers: {
                    lines: null,
                    polygons: null,
                    circles: null
                },
                drawnItems: null,
                drawControl: null,
                layers: { lines: true, clusters: true, others: true },
                stats: { lines: 0, polygons: 0, circles: 0 },
                availableLineNumbers: [],
                availableClusters: [],
                showLineNumberModal: false,
                selectedLineNumberId: '',
                selectedLineNumber: null,
                selectedClusterId: '',
                selectedCluster: null,
                customFeatureName: '',
                customColor: '',
                pendingLayer: null,
                pendingFeatureType: null,
                pendingGeometry: null,
                isEditMode: false,
                editingFeatureId: null,
                editingFeature: null,
                init() {
                    console.log('Jalur Maps Component Initializing...');
                    // Will be overridden by full implementation
                }
            };
        };
    }
</script>

{{-- Jalur Maps Component - Infrastructure Focus --}}
<div class="bg-white rounded-xl shadow-lg border border-gray-100" x-data="jalurMapsData()">
    <div class="p-6">
        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6 space-y-4 lg:space-y-0">
            <div class="flex items-center space-x-3">
                <div
                    class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-route text-white"></i>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Pipeline Jalur</h2>
                    <p class="text-sm text-gray-600">Visualisasi Jalur</p>
                </div>
            </div>

            <!-- Map Controls -->
            <div class="flex items-center space-x-2">
                <!-- Import KMZ/KML Button -->
                <a href="{{ route('jalur.kmz-import.index') }}"
                    class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition-colors shadow-sm">
                    <i class="fas fa-file-upload"></i>
                    <span class="text-sm font-medium">Import KMZ/KML</span>
                </a>

                <!-- Layer Toggle -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                        class="flex items-center space-x-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-layer-group text-gray-600"></i>
                        <span class="text-sm font-medium text-gray-700">Layers</span>
                        <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                    </button>

                    <!-- Layer Dropdown -->
                    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 z-[35]"
                        style="display: none;">

                        <div class="p-4 space-y-3">
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Toggle Layers
                            </div>

                            <!-- Line Features Layer -->
                            <label class="flex items-center justify-between cursor-pointer group">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                    <span class="text-sm text-gray-700 group-hover:text-gray-900">Jalur Lines</span>
                                </div>
                                <input type="checkbox" x-model="layers.lines" @change="toggleLayer('lines')"
                                    class="rounded text-blue-600 focus:ring-blue-500">
                            </label>

                            <!-- Cluster Boundaries Layer -->
                            <label class="flex items-center justify-between cursor-pointer group">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                                    <span class="text-sm text-gray-700 group-hover:text-gray-900">Cluster
                                        Boundaries</span>
                                </div>
                                <input type="checkbox" x-model="layers.clusters" @change="toggleLayer('clusters')"
                                    class="rounded text-purple-600 focus:ring-purple-500">
                            </label>

                            <!-- Additional Features Layer -->
                            <label class="flex items-center justify-between cursor-pointer group">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                                    <span class="text-sm text-gray-700 group-hover:text-gray-900">Other Features</span>
                                </div>
                                <input type="checkbox" x-model="layers.others" @change="toggleLayer('others')"
                                    class="rounded text-orange-600 focus:ring-orange-500">
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Refresh Button -->
                <button @click="refreshMapData()" :disabled="loading"
                    class="flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
                    <i class="fas fa-sync-alt text-sm" :class="{ 'animate-spin': loading }"></i>
                    <span class="text-sm font-medium">Refresh</span>
                </button>
            </div>
        </div>

        <!-- Map Container -->
        <div class="relative">
            <div id="jalur-map" class="w-full h-[600px] rounded-lg border border-gray-200 overflow-hidden"></div>

            <!-- Loading Overlay -->
            <div x-show="loading"
                class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-[30]"
                style="display: none;">
                <div class="flex flex-col items-center space-y-3">
                    <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin">
                    </div>
                    <p class="text-sm font-medium text-gray-600">Loading map data...</p>
                </div>
            </div>
        </div>

        <!-- Feature Selection Modal (Line Number or Cluster) -->
        <div x-show="showLineNumberModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[35]" style="display: none;"
            @click.self="cancelDrawing()">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4"
                        x-text="isEditMode ? (pendingFeatureType === 'line' ? 'Edit Pipeline Route' : 'Edit Cluster Boundary') : (pendingFeatureType === 'line' ? 'Draw Pipeline Route' : 'Draw Cluster Boundary')">
                    </h3>

                    <!-- For Polyline: Select Line Number -->
                    <div x-show="pendingFeatureType === 'line'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Line Number <span class="text-red-500">*</span>
                        </label>
                        <select x-model="selectedLineNumberId" @change="updateStyleFromLineNumber()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Select Line Number --</option>
                            <template x-for="line in availableLineNumbers" :key="line.id">
                                <option :value="line.id" x-text="line.display_text"></option>
                            </template>
                        </select>

                        <div x-show="selectedLineNumberId" class="mt-3 p-3 bg-gray-50 rounded-lg">
                            <div class="text-xs text-gray-600 space-y-1">
                                <div class="flex justify-between">
                                    <span>Cluster:</span>
                                    <span class="font-medium" x-text="selectedLineNumber?.cluster_name || '-'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Diameter:</span>
                                    <span class="font-medium">Ø<span
                                            x-text="selectedLineNumber?.diameter || '-'"></span>mm</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Estimasi (MC-0):</span>
                                    <span class="font-medium"><span
                                            x-text="selectedLineNumber?.estimasi_panjang || '-'"></span>m</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- For Polygon: Select Cluster -->
                    <div x-show="pendingFeatureType === 'polygon'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Cluster <span class="text-red-500">*</span>
                        </label>
                        <select x-model="selectedClusterId" @change="updateStyleFromCluster()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">-- Select Cluster --</option>
                            <template x-for="cluster in availableClusters" :key="cluster.id">
                                <option :value="cluster.id" x-text="cluster.display_text"></option>
                            </template>
                        </select>

                        <div x-show="selectedClusterId" class="mt-3 p-3 bg-gray-50 rounded-lg">
                            <div class="text-xs text-gray-600 space-y-1">
                                <div class="flex justify-between">
                                    <span>Code:</span>
                                    <span class="font-medium" x-text="selectedCluster?.code || '-'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Name:</span>
                                    <span class="font-medium" x-text="selectedCluster?.name || '-'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Custom Color Picker -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Custom Color
                        </label>
                        <div class="flex items-center space-x-3">
                            <input type="color" x-model="customColor" @change="updateCustomColor()"
                                class="w-12 h-10 border border-gray-300 rounded cursor-pointer">
                            <input type="text" x-model="customColor" placeholder="#10B981"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <button @click="resetToDefaultColor()"
                                class="px-3 py-2 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50">
                                Reset
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Leave empty for auto color based on diameter/cluster</p>
                    </div>

                    <!-- Feature Name -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Feature Name (optional)
                        </label>
                        <input type="text" x-model="customFeatureName"
                            :placeholder="pendingFeatureType === 'line' ? 'Line ' + (selectedLineNumber?.line_number || '') : 'Cluster ' + (selectedCluster?.name || '')"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3">
                        <button @click="cancelDrawing()"
                            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            Cancel
                        </button>
                        <button @click="confirmDrawing()"
                            :disabled="(pendingFeatureType === 'line' && !selectedLineNumberId) || (pendingFeatureType === 'polygon' && !selectedClusterId)"
                            :class="((pendingFeatureType === 'line' && selectedLineNumberId) || (pendingFeatureType === 'polygon' && selectedClusterId)) ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-300 cursor-not-allowed'"
                            class="px-4 py-2 text-white rounded-lg transition-colors">
                            <span x-text="isEditMode ? 'Update Feature' : 'Save Feature'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map Legend (Simple & Clean) -->
        <div class="mt-4 bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Legend Header -->
            <div class="px-4 py-3 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Legend</h3>
                    <span class="text-xs text-gray-500" x-show="legendLines.length > 0">
                        <span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                        <span x-text="stats.lines + ' Lines'"></span>
                    </span>
                </div>
            </div>

            <!-- Diameter Guide - Always visible with all pipe types -->
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <div class="text-xs font-medium text-gray-600 mb-2">Tipe Pipa</div>
                <div class="grid grid-cols-3 gap-2">
                    <!-- Pipa 63mm -->
                    <div class="flex items-center space-x-2 px-2 py-1.5 bg-white rounded border border-gray-200">
                        <div class="w-6 h-1 rounded" style="background-color: #10B981"></div>
                        <span class="text-xs font-medium text-gray-700">Ø63mm</span>
                    </div>

                    <!-- Pipa 90mm -->
                    <div class="flex items-center space-x-2 px-2 py-1.5 bg-white rounded border border-gray-200">
                        <div class="w-6 h-1.5 rounded" style="background-color: #3B82F6"></div>
                        <span class="text-xs font-medium text-gray-700">Ø90mm</span>
                    </div>

                    <!-- Pipa 180mm -->
                    <div class="flex items-center space-x-2 px-2 py-1.5 bg-white rounded border border-gray-200">
                        <div class="w-6 h-2 rounded" style="background-color: #EF4444"></div>
                        <span class="text-xs font-medium text-gray-700">Ø180mm</span>
                    </div>
                </div>

                <!-- Info for each diameter -->
                <div class="mt-3 grid grid-cols-3 gap-2">
                    <!-- Count for 63mm -->
                    <div class="text-center px-2 py-1 bg-green-50 rounded">
                        <div class="text-xs text-green-600 font-medium"
                            x-text="legendLines.filter(l => l.diameter == '63').length + ' lines'">0 lines</div>
                    </div>

                    <!-- Count for 90mm -->
                    <div class="text-center px-2 py-1 bg-blue-50 rounded">
                        <div class="text-xs text-blue-600 font-medium"
                            x-text="legendLines.filter(l => l.diameter == '90').length + ' lines'">0 lines</div>
                    </div>

                    <!-- Count for 180mm -->
                    <div class="text-center px-2 py-1 bg-red-50 rounded">
                        <div class="text-xs text-red-600 font-medium"
                            x-text="legendLines.filter(l => l.diameter == '180').length + ' lines'">0 lines</div>
                    </div>
                </div>
            </div>

            <!-- All Lines List (Simple) -->
            <div class="px-4 py-3">
                <div class="text-xs font-medium text-gray-600 mb-2">Jalur Lines</div>

                <template x-if="legendLines.length === 0">
                    <div class="text-center py-6 text-gray-400">
                        <i class="fas fa-route text-2xl mb-2"></i>
                        <p class="text-xs">Belum ada jalur</p>
                    </div>
                </template>

                <div class="space-y-1.5 max-h-[300px] overflow-y-auto custom-scrollbar">
                    <template x-for="line in legendLines" :key="line.id">
                        <div
                            class="flex items-center justify-between px-2 py-2 rounded hover:bg-gray-50 transition-colors">
                            <div class="flex items-center space-x-2 flex-1 min-w-0">
                                <!-- Color bar -->
                                <div class="w-1 h-8 rounded-full flex-shrink-0"
                                    :style="`background-color: ${line.color}`"></div>

                                <!-- Line info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-1.5">
                                        <span class="text-xs font-bold text-gray-900" x-text="line.line_number"></span>
                                        <span class="px-1.5 py-0.5 text-[9px] font-medium rounded"
                                            :style="`background-color: ${line.color}15; color: ${line.color}`"
                                            x-text="'Ø' + line.diameter + 'mm'"></span>
                                    </div>
                                    <div class="text-[10px] text-gray-500 truncate" x-text="line.cluster_name"></div>

                                    <!-- Simple progress -->
                                    <div class="flex items-center space-x-1 mt-1" x-show="line.estimasi_panjang > 0">
                                        <div class="flex-1 h-1 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full"
                                                :style="`width: ${Math.min(line.progress, 100)}%; background-color: ${line.color}`">
                                            </div>
                                        </div>
                                        <span class="text-[9px] text-gray-600 font-medium"
                                            x-text="line.progress + '%'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Summary Stats (Simple) -->
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50" x-show="legendLines.length > 0">
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div>
                        <div class="text-[10px] text-gray-500">Total MC-0</div>
                        <div class="text-sm font-bold text-gray-700"
                            x-text="legendLines.reduce((sum, l) => sum + (parseFloat(l.estimasi_panjang) || 0), 0).toFixed(1) + 'm'">
                            0m</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-gray-500">Terpasang</div>
                        <div class="text-sm font-bold text-green-600"
                            x-text="legendLines.reduce((sum, l) => sum + (parseFloat(l.total_penggelaran) || 0), 0).toFixed(1) + 'm'">
                            0m</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-gray-500">Progress</div>
                        <div class="text-sm font-bold text-blue-600"
                            x-text="legendLines.length > 0 ? (legendLines.reduce((sum, l) => sum + (parseFloat(l.progress) || 0), 0) / legendLines.length).toFixed(1) + '%' : '0%'">
                            0%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Define jalurMapsData globally before Alpine initializes
    window.jalurMapsData = function () {
        return {
            map: null,
            loading: false,
            features: [],
            featureLayers: {
                lines: L.layerGroup(),
                polygons: L.layerGroup(),
                circles: L.layerGroup()
            },
            drawnItems: new L.FeatureGroup(),
            drawControl: null,
            layers: {
                lines: true,
                clusters: true,
                others: true
            },
            stats: {
                lines: 0,
                polygons: 0,
                circles: 0
            },
            // Dynamic legend data
            uniqueDiameters: [],
            uniqueStatuses: [],
            legendLines: [], // All lines with actual colors and info
            // Line Number & Cluster selection for drawing
            availableLineNumbers: [],
            availableClusters: [],
            showLineNumberModal: false,
            selectedLineNumberId: '',
            selectedLineNumber: null,
            selectedClusterId: '',
            selectedCluster: null,
            customFeatureName: '',
            customColor: '',
            pendingLayer: null,
            pendingFeatureType: null,
            pendingGeometry: null,
            // Edit mode
            isEditMode: false,
            editingFeatureId: null,
            editingFeature: null,

            init() {
                // Set global reference for popup button actions
                window.jalurMapsInstance = this;

                this.$nextTick(() => {
                    this.initMap();
                    this.loadLineNumbers();
                    this.loadClusters();
                    this.loadMapData();
                });
            },

            async loadLineNumbers() {
                try {
                    const response = await fetch('{{ route('map-features.line-numbers') }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.availableLineNumbers = result.line_numbers;
                        console.log('Line numbers loaded:', this.availableLineNumbers.length);
                    } else {
                        console.warn('Failed to load line numbers:', result.message);
                    }
                } catch (error) {
                    console.error('Error loading line numbers:', error);
                }
            },

            async loadClusters() {
                try {
                    const response = await fetch('{{ route('map-features.clusters') }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.availableClusters = result.clusters;
                        console.log('Clusters loaded:', this.availableClusters.length);
                    } else {
                        console.warn('Failed to load clusters:', result.message);
                    }
                } catch (error) {
                    console.error('Error loading clusters:', error);
                }
            },

            updateStyleFromLineNumber() {
                const lineId = parseInt(this.selectedLineNumberId);
                this.selectedLineNumber = this.availableLineNumbers.find(l => l.id === lineId);

                if (this.selectedLineNumber) {
                    // Update pending layer style based on diameter
                    const style = this.getStyleByDiameter(this.selectedLineNumber.diameter);

                    if (this.pendingLayer) {
                        this.pendingLayer.setStyle(style);
                    }

                    // Set customColor to auto color if not set
                    if (!this.customColor) {
                        this.customColor = style.color;
                    }
                }
            },

            updateStyleFromCluster() {
                const clusterId = parseInt(this.selectedClusterId);
                this.selectedCluster = this.availableClusters.find(c => c.id === clusterId);

                if (this.selectedCluster && this.pendingLayer) {
                    const style = {
                        color: '#8B5CF6',
                        weight: 2,
                        opacity: 0.8,
                        fillColor: '#8B5CF6',
                        fillOpacity: 0.2
                    };
                    this.pendingLayer.setStyle(style);

                    if (!this.customColor) {
                        this.customColor = '#8B5CF6';
                    }
                }
            },

            updateCustomColor() {
                if (this.customColor && this.pendingLayer) {
                    const style = {
                        color: this.customColor,
                        weight: this.pendingFeatureType === 'line' ? 4 : 2,
                        opacity: 0.8
                    };

                    if (this.pendingFeatureType === 'polygon') {
                        style.fillColor = this.customColor;
                        style.fillOpacity = 0.2;
                    }

                    this.pendingLayer.setStyle(style);
                }
            },

            resetToDefaultColor() {
                this.customColor = '';
                if (this.pendingFeatureType === 'line' && this.selectedLineNumber) {
                    this.updateStyleFromLineNumber();
                } else if (this.pendingFeatureType === 'polygon') {
                    this.updateStyleFromCluster();
                }
            },

            getStyleByDiameter(diameter) {
                const diameterNum = parseInt(diameter);
                const colorMap = {
                    63: { color: '#10B981', weight: 4 },   // Green
                    90: { color: '#3B82F6', weight: 5 },   // Blue
                    180: { color: '#EF4444', weight: 6 }   // Red
                };

                return {
                    ...(colorMap[diameterNum] || { color: '#6B7280', weight: 4 }),
                    opacity: 0.8
                };
            },

            initMap() {
                // Initialize Leaflet map
                this.map = L.map('jalur-map', {
                    center: [-7.7956, 110.3695], // Yogyakarta default
                    zoom: 13,
                    zoomControl: true,
                    attributionControl: true
                });

                // Add tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(this.map);

                // Add all feature layers to map
                Object.values(this.featureLayers).forEach(layer => {
                    layer.addTo(this.map);
                });

                // Add drawn items layer
                this.map.addLayer(this.drawnItems);

                // Initialize drawing tools
                this.initDrawingTools();

                // Add scale control
                L.control.scale({
                    imperial: false,
                    metric: true
                }).addTo(this.map);
            },

            initDrawingTools() {
                // Initialize the draw control
                this.drawControl = new L.Control.Draw({
                    position: 'topright',
                    draw: {
                        polyline: {
                            shapeOptions: {
                                color: '#3388ff',
                                weight: 4,
                                opacity: 0.8
                            },
                            showLength: true,
                            metric: true
                        },
                        polygon: {
                            allowIntersection: false,
                            showArea: true,
                            shapeOptions: {
                                color: '#8b5cf6',
                                weight: 2,
                                opacity: 0.8,
                                fillOpacity: 0.2
                            }
                        },
                        circle: false,  // Disabled
                        rectangle: false,
                        marker: false,
                        circlemarker: false
                    },
                    edit: {
                        featureGroup: this.drawnItems,
                        remove: true
                    }
                });

                this.map.addControl(this.drawControl);

                // Drawing event handlers
                this.map.on(L.Draw.Event.CREATED, (e) => {
                    this.handleDrawCreated(e);
                });

                this.map.on(L.Draw.Event.EDITED, (e) => {
                    this.handleDrawEdited(e);
                });

                this.map.on(L.Draw.Event.DELETED, (e) => {
                    this.handleDrawDeleted(e);
                });
            },

            handleDrawCreated(e) {
                const layer = e.layer;
                const type = e.layerType;

                // Prepare geometry for saving
                let geometry;
                let featureType;

                if (type === 'polyline') {
                    const latlngs = layer.getLatLngs();
                    geometry = {
                        type: 'LineString',
                        coordinates: latlngs.map(ll => [ll.lng, ll.lat]) // GeoJSON format
                    };
                    featureType = 'line';
                } else if (type === 'polygon') {
                    const latlngs = layer.getLatLngs()[0];
                    geometry = {
                        type: 'Polygon',
                        coordinates: [latlngs.map(ll => [ll.lng, ll.lat])]
                    };
                    featureType = 'polygon';
                } else if (type === 'circle') {
                    const center = layer.getLatLng();
                    geometry = {
                        type: 'Point',
                        coordinates: [center.lng, center.lat]
                    };
                    featureType = 'circle';
                }

                // Store pending data and show modal
                this.pendingLayer = layer;
                this.pendingFeatureType = featureType;
                this.pendingGeometry = geometry;
                this.selectedLineNumberId = '';
                this.selectedLineNumber = null;
                this.customFeatureName = '';
                this.showLineNumberModal = true;

                // Add temporary layer to map
                this.drawnItems.addLayer(layer);
            },

            cancelDrawing() {
                // Remove pending layer from map
                if (this.pendingLayer) {
                    this.drawnItems.removeLayer(this.pendingLayer);
                }

                // Reset modal state
                this.showLineNumberModal = false;
                this.isEditMode = false;
                this.editingFeatureId = null;
                this.editingFeature = null;
                this.selectedLineNumberId = '';
                this.selectedLineNumber = null;
                this.selectedClusterId = '';
                this.selectedCluster = null;
                this.customFeatureName = '';
                this.customColor = '';
                this.pendingLayer = null;
                this.pendingFeatureType = null;
                this.pendingGeometry = null;
            },

            async confirmDrawing() {
                // Check if Edit Mode
                if (this.isEditMode) {
                    await this.updateExistingFeature();
                    return;
                }

                // Validate based on feature type
                if (this.pendingFeatureType === 'line' && !this.selectedLineNumberId) {
                    window.showToast && window.showToast('error', 'Please select a Line Number');
                    return;
                }

                if (this.pendingFeatureType === 'polygon' && !this.selectedClusterId) {
                    window.showToast && window.showToast('error', 'Please select a Cluster');
                    return;
                }

                let featureName, entityId, clusterId, style;

                if (this.pendingFeatureType === 'line') {
                    // For polyline: use Line Number data
                    featureName = this.customFeatureName || `Line ${this.selectedLineNumber.line_number}`;
                    entityId = this.selectedLineNumberId;
                    clusterId = this.selectedLineNumber.cluster_id;

                    // Get style (custom or auto by diameter)
                    if (this.customColor) {
                        style = {
                            color: this.customColor,
                            weight: 4,
                            opacity: 0.8
                        };
                    } else {
                        style = this.getStyleByDiameter(this.selectedLineNumber.diameter);
                    }

                } else if (this.pendingFeatureType === 'polygon') {
                    // For polygon: use Cluster data
                    featureName = this.customFeatureName || `Cluster ${this.selectedCluster.name} Boundary`;
                    entityId = null; // No line_number_id for polygons
                    clusterId = this.selectedClusterId;

                    // Get style (custom or default purple)
                    if (this.customColor) {
                        style = {
                            color: this.customColor,
                            weight: 2,
                            opacity: 0.8,
                            fillColor: this.customColor,
                            fillOpacity: 0.2
                        };
                    } else {
                        style = {
                            color: '#8B5CF6',
                            weight: 2,
                            opacity: 0.8,
                            fillColor: '#8B5CF6',
                            fillOpacity: 0.2
                        };
                    }
                }

                // Save to database
                await this.saveFeature(
                    this.pendingGeometry,
                    this.pendingFeatureType,
                    this.pendingLayer,
                    featureName,
                    entityId,     // line_number_id (for polyline) or null (for polygon)
                    clusterId,    // cluster_id
                    style
                );

                // Close modal and reset
                this.showLineNumberModal = false;
                this.selectedLineNumberId = '';
                this.selectedLineNumber = null;
                this.selectedClusterId = '';
                this.selectedCluster = null;
                this.customFeatureName = '';
                this.customColor = '';
                this.pendingLayer = null;
                this.pendingFeatureType = null;
                this.pendingGeometry = null;
            },

            async updateExistingFeature() {
                // Validate based on feature type
                if (this.pendingFeatureType === 'line' && !this.selectedLineNumberId) {
                    window.showToast && window.showToast('error', 'Please select a Line Number');
                    return;
                }

                if (this.pendingFeatureType === 'polygon' && !this.selectedClusterId) {
                    window.showToast && window.showToast('error', 'Please select a Cluster');
                    return;
                }

                try {
                    let featureName, lineNumberId, clusterId, style, metadata;

                    if (this.pendingFeatureType === 'line') {
                        featureName = this.customFeatureName || `Line ${this.selectedLineNumber.line_number}`;
                        lineNumberId = this.selectedLineNumberId;
                        clusterId = this.selectedLineNumber.cluster_id;

                        if (this.customColor) {
                            style = {
                                color: this.customColor,
                                weight: 4,
                                opacity: 0.8
                            };
                        } else {
                            style = this.getStyleByDiameter(this.selectedLineNumber.diameter);
                        }

                        metadata = {
                            line_number: this.selectedLineNumber?.line_number,
                            nama_jalan: this.selectedLineNumber?.nama_jalan,
                            diameter: this.selectedLineNumber?.diameter,
                            cluster_name: this.selectedLineNumber?.cluster_name,
                            cluster_code: this.selectedLineNumber?.cluster_code,
                            line_code: this.selectedLineNumber?.line_code,
                            estimasi_panjang: this.selectedLineNumber?.estimasi_panjang,
                            total_penggelaran: this.selectedLineNumber?.total_penggelaran,
                            actual_mc100: this.selectedLineNumber?.actual_mc100,
                            status: this.selectedLineNumber?.status_line,
                            keterangan: this.selectedLineNumber?.keterangan
                        };

                    } else if (this.pendingFeatureType === 'polygon') {
                        featureName = this.customFeatureName || `Cluster ${this.selectedCluster.name} Boundary`;
                        lineNumberId = null;
                        clusterId = this.selectedClusterId;

                        if (this.customColor) {
                            style = {
                                color: this.customColor,
                                weight: 2,
                                opacity: 0.8,
                                fillColor: this.customColor,
                                fillOpacity: 0.2
                            };
                        } else {
                            style = {
                                color: '#8B5CF6',
                                weight: 2,
                                opacity: 0.8,
                                fillColor: '#8B5CF6',
                                fillOpacity: 0.2
                            };
                        }

                        metadata = {
                            cluster_name: this.selectedCluster?.name,
                            cluster_code: this.selectedCluster?.code
                        };
                    }

                    // Update via API
                    const response = await fetch(`/map-features/${this.editingFeatureId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken
                        },
                        body: JSON.stringify({
                            name: featureName,
                            line_number_id: lineNumberId,
                            cluster_id: clusterId,
                            style_properties: style,
                            metadata: metadata
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.showToast && window.showToast('success', 'Feature updated successfully');
                        await this.loadMapData();
                    } else {
                        throw new Error(result.message || 'Failed to update feature');
                    }

                } catch (error) {
                    console.error('Error updating feature:', error);
                    window.showToast && window.showToast('error', 'Failed to update feature: ' + error.message);
                }

                // Close modal and reset
                this.showLineNumberModal = false;
                this.isEditMode = false;
                this.editingFeatureId = null;
                this.editingFeature = null;
                this.selectedLineNumberId = '';
                this.selectedLineNumber = null;
                this.selectedClusterId = '';
                this.selectedCluster = null;
                this.customFeatureName = '';
                this.customColor = '';
                this.pendingLayer = null;
                this.pendingFeatureType = null;
                this.pendingGeometry = null;
            },

            async saveFeature(geometry, featureType, layer, featureName, lineNumberId, clusterId, style) {
                try {
                    let metadata = {};

                    if (featureType === 'line' && lineNumberId) {
                        // For polyline: get Line Number data with full details
                        const lineNumber = this.availableLineNumbers.find(l => l.id === parseInt(lineNumberId));
                        metadata = {
                            line_number: lineNumber?.line_number,
                            nama_jalan: lineNumber?.nama_jalan,
                            diameter: lineNumber?.diameter,
                            cluster_name: lineNumber?.cluster_name,
                            cluster_code: lineNumber?.cluster_code,
                            line_code: lineNumber?.line_code,
                            estimasi_panjang: lineNumber?.estimasi_panjang,
                            total_penggelaran: lineNumber?.total_penggelaran,
                            actual_mc100: lineNumber?.actual_mc100,
                            status: lineNumber?.status_line,
                            keterangan: lineNumber?.keterangan
                        };
                    } else if (featureType === 'polygon' && clusterId) {
                        // For polygon: get Cluster data
                        const cluster = this.availableClusters.find(c => c.id === parseInt(clusterId));
                        metadata = {
                            cluster_name: cluster?.name,
                            cluster_code: cluster?.code
                        };
                    }

                    const response = await fetch('{{ route('map-features.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken
                        },
                        body: JSON.stringify({
                            name: featureName,
                            feature_type: featureType,
                            line_number_id: lineNumberId,
                            cluster_id: clusterId,
                            geometry: geometry,
                            style_properties: style,
                            metadata: metadata,
                            is_visible: true
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Store the feature ID on the layer
                        layer.featureId = result.feature.id;

                        // Refresh map to show the feature with proper styling
                        await this.loadMapData();

                        window.showToast && window.showToast('success', `Feature saved: ${featureName}`);
                    } else {
                        throw new Error(result.message || 'Failed to save feature');
                    }
                } catch (error) {
                    console.error('Error saving feature:', error);
                    window.showToast && window.showToast('error', 'Failed to save feature: ' + error.message);
                    // Remove from drawn items if save failed
                    this.drawnItems.removeLayer(layer);
                }
            },

            editFeature(featureId) {
                // Find the feature from features array
                const feature = this.features.find(f => f.id === featureId);
                if (!feature) {
                    window.showToast && window.showToast('error', 'Feature not found');
                    return;
                }

                // Set edit mode
                this.isEditMode = true;
                this.editingFeatureId = featureId;
                this.editingFeature = feature;

                // Set feature type
                this.pendingFeatureType = feature.properties.feature_type;

                // Populate form fields based on feature type
                if (feature.properties.feature_type === 'line') {
                    this.selectedLineNumberId = feature.properties.line_number_id || '';
                    if (this.selectedLineNumberId) {
                        this.selectedLineNumber = this.availableLineNumbers.find(l => l.id === parseInt(this.selectedLineNumberId));
                    }
                } else if (feature.properties.feature_type === 'polygon') {
                    this.selectedClusterId = feature.properties.cluster_id || '';
                    if (this.selectedClusterId) {
                        this.selectedCluster = this.availableClusters.find(c => c.id === parseInt(this.selectedClusterId));
                    }
                }

                // Set name and color
                this.customFeatureName = feature.properties.name || '';
                this.customColor = feature.properties.style_properties?.color || '';

                // Show modal
                this.showLineNumberModal = true;

                // Close any open popup
                this.map.closePopup();
            },

            async deleteFeature(featureId) {
                if (!confirm('Are you sure you want to delete this feature?')) {
                    return;
                }

                try {
                    const response = await fetch(`/map-features/${featureId}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.showToast && window.showToast('success', 'Feature deleted successfully');
                        await this.loadMapData();
                        this.map.closePopup();
                    } else {
                        throw new Error(result.message || 'Failed to delete feature');
                    }
                } catch (error) {
                    console.error('Error deleting feature:', error);
                    window.showToast && window.showToast('error', 'Failed to delete feature: ' + error.message);
                }
            },

            handleDrawEdited(e) {
                const layers = e.layers;
                layers.eachLayer(layer => {
                    if (layer.featureId) {
                        this.updateFeature(layer);
                    }
                });
            },

            async updateFeature(layer) {
                try {
                    let geometry;

                    if (layer instanceof L.Polyline && !(layer instanceof L.Polygon)) {
                        const latlngs = layer.getLatLngs();
                        geometry = {
                            type: 'LineString',
                            coordinates: latlngs.map(ll => [ll.lng, ll.lat])
                        };
                    } else if (layer instanceof L.Polygon) {
                        const latlngs = layer.getLatLngs()[0];
                        geometry = {
                            type: 'Polygon',
                            coordinates: [latlngs.map(ll => [ll.lng, ll.lat])]
                        };
                    } else if (layer instanceof L.Circle || layer instanceof L.CircleMarker) {
                        const center = layer.getLatLng();
                        geometry = {
                            type: 'Point',
                            coordinates: [center.lng, center.lat]
                        };
                    }

                    const response = await fetch(`/map-features/${layer.featureId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken
                        },
                        body: JSON.stringify({ geometry })
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.showToast && window.showToast('success', 'Feature updated');
                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    console.error('Error updating feature:', error);
                    window.showToast && window.showToast('error', 'Failed to update feature');
                }
            },

            handleDrawDeleted(e) {
                const layers = e.layers;
                layers.eachLayer(layer => {
                    if (layer.featureId) {
                        this.deleteFeature(layer.featureId);
                    }
                });
            },

            async deleteFeature(featureId) {
                try {
                    const response = await fetch(`/map-features/${featureId}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.showToast && window.showToast('success', 'Feature deleted');
                        await this.loadMapData();
                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    console.error('Error deleting feature:', error);
                    window.showToast && window.showToast('error', 'Failed to delete feature');
                }
            },

            async loadMapData() {
                this.loading = true;

                try {
                    const response = await fetch('{{ route('map-features.index') }}?context=jalur', {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.features = result.features || [];
                        this.renderFeatures();
                        this.updateStats();

                        if (this.features.length > 0) {
                            this.fitMapToBounds();
                        }

                        console.log('Jalur map data loaded:', this.features.length, 'features');
                    } else {
                        console.error('Failed to load map features:', result.message);
                        window.showToast && window.showToast('error', 'Failed to load map data');
                    }
                } catch (error) {
                    console.error('Error loading map data:', error);
                    window.showToast && window.showToast('error', 'Failed to load map data');
                } finally {
                    this.loading = false;
                }
            },

            renderFeatures() {
                // Clear existing layers
                Object.values(this.featureLayers).forEach(layer => layer.clearLayers());

                this.features.forEach(feature => {
                    const layer = this.createFeatureLayer(feature);
                    if (layer) {
                        const featureType = feature.properties.feature_type;

                        if (featureType === 'line') {
                            this.featureLayers.lines.addLayer(layer);
                        } else if (featureType === 'polygon') {
                            this.featureLayers.polygons.addLayer(layer);
                        } else if (featureType === 'circle') {
                            this.featureLayers.circles.addLayer(layer);
                        }
                    }
                });
            },

            createFeatureLayer(feature) {
                const geometry = feature.geometry;
                const properties = feature.properties;
                const isUnassigned = properties.feature_type === 'line' && !properties.line_number_id;
                const style = properties.style || this.getDefaultStyle(properties.feature_type, isUnassigned);

                let layer;

                if (geometry.type === 'LineString') {
                    // GeoJSON format: [lng, lat] → Leaflet needs: [lat, lng]
                    // REVERSE the coordinates!
                    const coords = geometry.coordinates.map(coord => [coord[1], coord[0]]);
                    layer = L.polyline(coords, style);
                } else if (geometry.type === 'Polygon') {
                    // GeoJSON format: [lng, lat] → Leaflet needs: [lat, lng]
                    const coords = geometry.coordinates[0].map(coord => [coord[1], coord[0]]);
                    layer = L.polygon(coords, style);
                } else if (geometry.type === 'Point') {
                    // GeoJSON format: [lng, lat] → Leaflet needs: [lat, lng]
                    const coord = [geometry.coordinates[1], geometry.coordinates[0]];
                    layer = L.circleMarker(coord, {
                        ...style,
                        radius: 8
                    });
                }

                if (layer) {
                    // Store feature ID on layer for later reference
                    layer.featureId = feature.id;
                    layer.featureData = feature;

                    // Add popup with feature info
                    const popupContent = this.createPopupContent(feature);
                    layer.bindPopup(popupContent, {
                        maxWidth: isUnassigned ? 400 : 360,
                        minWidth: isUnassigned ? 350 : 300,
                        maxHeight: 500,
                        autoPan: true,
                        autoPanPadding: [50, 50],
                        className: 'jalur-feature-popup'
                    });

                    // For unassigned lines, add click event to enable assignment
                    if (isUnassigned) {
                        layer.on('popupopen', () => {
                            this.attachAssignmentListeners(feature.id);
                        });
                    }

                    // Tooltip disabled to prevent zoom/pan errors
                    // Popup provides sufficient information
                    // Uncomment below if tooltip is needed with better error handling
                    /*
                    layer.on('add', () => {
                        try {
                            layer.bindTooltip(properties.name || 'Unnamed Feature', {
                                permanent: false,
                                direction: 'top',
                                sticky: true
                            });
                        } catch (e) {
                            console.warn('Tooltip binding failed:', e);
                        }
                    });
                    */
                }

                return layer;
            },

            getDefaultStyle(featureType, isUnassigned = false) {
                const defaults = {
                    line: {
                        color: isUnassigned ? '#f97316' : '#3388ff', // Orange for unassigned
                        weight: isUnassigned ? 5 : 4,
                        opacity: 0.8,
                        dashArray: isUnassigned ? '10, 10' : null // Dashed line for unassigned
                    },
                    polygon: {
                        color: '#8b5cf6',
                        weight: 2,
                        opacity: 0.8,
                        fillColor: '#8b5cf6',
                        fillOpacity: 0.2
                    },
                    circle: {
                        color: '#f97316',
                        weight: 2,
                        opacity: 0.8,
                        fillColor: '#f97316',
                        fillOpacity: 0.4
                    }
                };

                return defaults[featureType] || defaults.line;
            },

            createPopupContent(feature) {
                const props = feature.properties;
                const metadata = props.metadata || {};
                const style = props.style_properties || {};
                const featureId = feature.id; // ID is at root level in GeoJSON

                let html = `
                <div class="jalur-popup-content" style="max-width: 340px;">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <h3 class="text-xs font-bold text-gray-900 mb-1 leading-tight">${props.name || 'Unnamed Feature'}</h3>
                            <span class="inline-block px-1.5 py-0.5 text-[10px] rounded-full ${this.getFeatureTypeBadgeClass(props.feature_type)}">
                                ${this.formatFeatureType(props.feature_type)}
                            </span>
                        </div>
            `;

                // Color indicator
                if (style.color) {
                    html += `
                    <div class="ml-2">
                        <div class="w-5 h-5 rounded border border-gray-300" style="background-color: ${style.color}"></div>
                    </div>
                `;
                }

                html += `</div>`;

                // For UNASSIGNED Line - Show Assignment UI
                if (props.feature_type === 'line' && !props.line_number_id) {
                    html += `
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-3 mb-2 border border-orange-300">
                        <div class="text-xs font-bold text-orange-900 mb-2 flex items-center">
                            <i class="fas fa-exclamation-triangle mr-2 text-orange-600"></i> JALUR BELUM DI-ASSIGN
                        </div>
                        <p class="text-[10px] text-orange-700 mb-3">Assign jalur ini ke Line Number yang sudah ada</p>

                        <div class="mb-2">
                            <label class="block text-[10px] font-semibold text-gray-700 mb-1">Pilih Line Number:</label>
                            <select id="assign-line-select-${featureId}"
                                    class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">-- Pilih Line Number --</option>
                            </select>
                        </div>

                        <div class="flex space-x-2">
                            <button onclick="window.jalurMapsData().assignLineFromMap(${featureId})"
                                    class="flex-1 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition-colors">
                                <i class="fas fa-link mr-1"></i>Assign
                            </button>
                            <button onclick="window.jalurMapsData().deleteUnassignedLine(${featureId})"
                                    class="px-3 py-1.5 bg-red-500 text-white text-xs font-medium rounded hover:bg-red-600 transition-colors">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;

                    // Show metadata if available
                    if (metadata.description || metadata.original_filename) {
                        html += `<div class="bg-gray-50 rounded-lg p-2 mb-2 text-[10px] text-gray-600">`;
                        if (metadata.description) {
                            html += `<div class="mb-1"><i class="fas fa-info-circle mr-1"></i>${metadata.description}</div>`;
                        }
                        if (metadata.original_filename) {
                            html += `<div><i class="fas fa-file mr-1"></i>File: ${metadata.original_filename}</div>`;
                        }
                        html += `</div>`;
                    }
                }

                // For Pipeline Route (Line Number) - ASSIGNED
                else if (props.feature_type === 'line' && props.line_number_id) {
                    html += `
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-2.5 mb-2 border border-blue-200">
                        <div class="text-[10px] font-bold text-blue-900 mb-2 flex items-center border-b border-blue-300 pb-1">
                            <i class="fas fa-route mr-1 text-xs"></i> LINE NUMBER DETAIL
                        </div>
                `;

                    // Lokasi Section
                    html += `
                    <div class="bg-white rounded-md p-1.5 mb-1.5 shadow-sm">
                        <div class="text-[10px] font-semibold text-gray-700 mb-1 flex items-center">
                            <i class="fas fa-map-marker-alt mr-1 text-red-500 text-xs"></i> Lokasi
                        </div>
                `;

                    if (metadata.nama_jalan) {
                        html += `
                        <div class="flex justify-between items-center mb-0.5">
                            <span class="text-gray-600 text-[10px]">Nama Jalan:</span>
                            <span class="font-medium text-gray-900 text-[10px]">${metadata.nama_jalan}</span>
                        </div>
                    `;
                    }

                    if (metadata.line_code) {
                        html += `
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 text-[10px]">Line Code:</span>
                            <span class="font-medium text-gray-700 text-[10px]">${metadata.line_code}</span>
                        </div>
                    `;
                    }

                    html += `</div>`;

                    // Cluster Section
                    html += `
                    <div class="bg-white rounded-md p-1.5 mb-1.5 shadow-sm">
                        <div class="text-[10px] font-semibold text-gray-700 mb-1 flex items-center">
                            <i class="fas fa-layer-group mr-1 text-purple-500 text-xs"></i> Cluster
                        </div>
                `;

                    if (metadata.cluster_name) {
                        html += `
                        <div class="flex justify-between items-center mb-0.5">
                            <span class="text-gray-600 text-[10px]">Nama:</span>
                            <span class="font-medium text-gray-900 text-[10px]">${metadata.cluster_name}</span>
                        </div>
                    `;
                    }

                    if (metadata.cluster_code) {
                        html += `
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 text-[10px]">Code:</span>
                            <span class="font-bold text-purple-600 text-[10px]">${metadata.cluster_code}</span>
                        </div>
                    `;
                    }

                    html += `</div>`;

                    // Diameter Section
                    if (metadata.diameter) {
                        const diameterColor = this.getDiameterColor(metadata.diameter);
                        html += `
                        <div class="bg-white rounded-md p-1.5 mb-1.5 shadow-sm">
                            <div class="text-[10px] font-semibold text-gray-700 mb-1 flex items-center">
                                <i class="fas fa-circle mr-1 text-xs" style="color: ${diameterColor}"></i> Diameter
                            </div>
                            <div class="flex justify-center">
                                <span class="font-bold px-3 py-1 rounded text-xs" style="background-color: ${diameterColor}; color: white;">
                                    Ø${metadata.diameter}mm
                                </span>
                            </div>
                        </div>
                    `;
                    }

                    // Measurement Section (MC-0, Actual, MC-100, Variance)
                    html += `
                    <div class="bg-white rounded-md p-2 mb-2 shadow-sm">
                        <div class="text-xs font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-ruler mr-1 text-green-500"></i> Pengukuran Panjang
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                `;

                    // MC-0 (Estimasi)
                    const mc0 = metadata.estimasi_panjang ? parseFloat(metadata.estimasi_panjang) : 0;
                    html += `
                    <div class="bg-blue-50 rounded p-1.5">
                        <div class="text-xs text-gray-600 mb-0.5">MC-0 (m)</div>
                        <div class="font-bold text-blue-700 text-sm">${mc0.toFixed(2)}</div>
                    </div>
                `;

                    // Actual Lowering
                    const actual = metadata.total_penggelaran ? parseFloat(metadata.total_penggelaran) : 0;
                    html += `
                    <div class="bg-orange-50 rounded p-1.5">
                        <div class="text-xs text-gray-600 mb-0.5">Actual Lowering (m)</div>
                        <div class="font-bold text-orange-700 text-sm">${actual.toFixed(2)}</div>
                    </div>
                `;

                    // MC-100
                    const mc100 = metadata.actual_mc100 ? parseFloat(metadata.actual_mc100) : 0;
                    html += `
                    <div class="bg-green-50 rounded p-1.5">
                        <div class="text-xs text-gray-600 mb-0.5">MC-100 (m)</div>
                        <div class="font-bold text-green-700 text-sm">${mc100.toFixed(2)}</div>
                    </div>
                `;

                    // Variance (MC-100 - MC-0)
                    const variance = mc100 - mc0;
                    const varianceClass = variance >= 0 ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50';
                    const varianceIcon = variance >= 0 ? '▲' : '▼';
                    html += `
                    <div class="${varianceClass} rounded p-1.5">
                        <div class="text-xs text-gray-600 mb-0.5">Variance (m)</div>
                        <div class="font-bold text-sm">${varianceIcon} ${variance.toFixed(2)}</div>
                    </div>
                `;

                    html += `
                        </div>
                    </div>
                `;

                    // Progress Section (reuse mc0 and actual variables from above)
                    const progressPercent = mc0 > 0 ? ((actual / mc0) * 100) : 0;
                    const progressColor = progressPercent >= 100 ? '#10B981' : progressPercent >= 75 ? '#3B82F6' : progressPercent >= 50 ? '#F59E0B' : '#EF4444';

                    html += `
                    <div class="bg-white rounded-md p-3 mb-2 shadow-sm">
                        <div class="text-xs font-semibold text-gray-700 mb-3 flex items-center">
                            <i class="fas fa-chart-line mr-1 text-blue-500"></i> Progress Penggelaran
                        </div>

                        <!-- Progress Stats -->
                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <div class="text-center">
                                <div class="text-xs text-gray-600 mb-1">Terpasang</div>
                                <div class="font-bold text-lg" style="color: ${progressColor}">${actual.toFixed(2)}m</div>
                            </div>
                            <div class="text-center">
                                <div class="text-xs text-gray-600 mb-1">Progress</div>
                                <div class="font-bold text-lg" style="color: ${progressColor}">${progressPercent.toFixed(1)}%</div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="relative w-full h-6 bg-gray-200 rounded-full overflow-hidden">
                            <div class="absolute top-0 left-0 h-full transition-all duration-300 rounded-full"
                                 style="width: ${Math.min(progressPercent, 100)}%; background-color: ${progressColor}">
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center text-xs font-semibold text-gray-700">
                                ${actual.toFixed(2)}m / ${mc0.toFixed(2)}m
                            </div>
                        </div>

                        <!-- Status Indicator -->
                        <div class="mt-2 text-center text-xs font-medium" style="color: ${progressColor}">
                            ${progressPercent >= 100 ? '✓ Selesai' : progressPercent >= 75 ? '⚡ Hampir Selesai' : progressPercent >= 50 ? '⏳ Dalam Progress' : '🚧 Baru Dimulai'}
                        </div>
                    </div>
                `;

                    // Status & Keterangan
                    if (metadata.status || metadata.keterangan) {
                        html += `<div class="bg-white rounded-md p-2 shadow-sm">`;

                        if (metadata.status) {
                            const statusBadge = this.getStatusBadge(metadata.status);
                            html += `
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-600 text-xs font-semibold">Status:</span>
                                <span class="${statusBadge.class}">${statusBadge.text}</span>
                            </div>
                        `;
                        }

                        if (metadata.keterangan) {
                            html += `
                            <div class="text-xs">
                                <span class="text-gray-600 font-semibold">Keterangan:</span>
                                <p class="text-gray-700 mt-1 italic">${metadata.keterangan}</p>
                            </div>
                        `;
                        }

                        html += `</div>`;
                    }

                    html += `
                    </div>
                `;
                }

                // For Cluster Boundary (Polygon)
                if (props.feature_type === 'polygon' && props.cluster_id) {
                    html += `
                    <div class="bg-purple-50 rounded-lg p-3 mb-3">
                        <div class="text-xs font-semibold text-purple-900 mb-2 flex items-center">
                            <i class="fas fa-vector-square mr-1"></i> Cluster Information
                        </div>
                        <div class="space-y-2 text-xs">
                `;

                    if (metadata.cluster_code) {
                        html += `
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Code:</span>
                            <span class="font-bold text-purple-700">${metadata.cluster_code}</span>
                        </div>
                    `;
                    }

                    if (metadata.cluster_name) {
                        html += `
                        <div class="flex justify-between">
                            <span class="text-gray-600">Name:</span>
                            <span class="font-medium text-gray-900">${metadata.cluster_name}</span>
                        </div>
                    `;
                    }

                    html += `
                        </div>
                    </div>
                `;
                }

                // Created/Updated info
                if (props.created_at || props.updated_at) {
                    html += `
                    <div class="text-xs text-gray-500 border-t border-gray-200 pt-2 mb-3">
                `;

                    if (props.created_at) {
                        const createdDate = new Date(props.created_at).toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric'
                        });
                        html += `
                        <div class="flex items-center mb-1">
                            <i class="fas fa-calendar-plus text-gray-400 mr-1.5 text-xs"></i>
                            <span>Created: ${createdDate}</span>
                        </div>
                    `;
                    }

                    if (props.updated_at && props.updated_at !== props.created_at) {
                        const updatedDate = new Date(props.updated_at).toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric'
                        });
                        html += `
                        <div class="flex items-center">
                            <i class="fas fa-calendar-check text-gray-400 mr-1.5 text-xs"></i>
                            <span>Updated: ${updatedDate}</span>
                        </div>
                    `;
                    }

                    html += `</div>`;
                }

                // Action Buttons
                html += `
                    <div class="flex gap-2">
                        <button onclick="window.jalurMapsInstance.editFeature(${feature.id})"
                                class="flex-1 px-3 py-2 bg-blue-500 text-white text-xs font-medium rounded-lg hover:bg-blue-600 transition-colors shadow-sm">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </button>
                        <button onclick="window.jalurMapsInstance.deleteFeature(${feature.id})"
                                class="flex-1 px-3 py-2 bg-red-500 text-white text-xs font-medium rounded-lg hover:bg-red-600 transition-colors shadow-sm">
                            <i class="fas fa-trash mr-1"></i> Delete
                        </button>
                    </div>
                </div>
            `;

                return html;
            },

            getDiameterColor(diameter) {
                const colors = {
                    '63': '#10B981',   // Green
                    '90': '#3B82F6',   // Blue
                    '180': '#EF4444'   // Red
                };
                return colors[diameter] || '#6B7280';
            },

            getStatusBadge(status) {
                const badges = {
                    'draft': {
                        text: 'Draft',
                        class: 'px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-700 font-medium'
                    },
                    'in_progress': {
                        text: 'In Progress',
                        class: 'px-2 py-0.5 text-xs rounded-full bg-blue-200 text-blue-700 font-medium'
                    },
                    'completed': {
                        text: 'Completed',
                        class: 'px-2 py-0.5 text-xs rounded-full bg-green-200 text-green-700 font-medium'
                    }
                };
                return badges[status] || badges.draft;
            },

            getFeatureTypeBadgeClass(type) {
                const classes = {
                    'line': 'bg-blue-100 text-blue-700',
                    'polygon': 'bg-purple-100 text-purple-700',
                    'circle': 'bg-orange-100 text-orange-700'
                };
                return classes[type] || 'bg-gray-100 text-gray-700';
            },

            formatFeatureType(type) {
                const types = {
                    'line': 'Pipeline Route',
                    'polygon': 'Area/Cluster',
                    'circle': 'Point of Interest'
                };
                return types[type] || type;
            },

            toggleLayer(layerType) {
                const layerMap = {
                    'lines': this.featureLayers.lines,
                    'clusters': this.featureLayers.polygons,
                    'others': this.featureLayers.circles
                };

                const layer = layerMap[layerType];
                if (layer) {
                    if (this.layers[layerType]) {
                        this.map.addLayer(layer);
                    } else {
                        this.map.removeLayer(layer);
                    }
                }
            },

            updateStats() {
                this.stats.lines = this.features.filter(f => f.properties.feature_type === 'line').length;
                this.stats.polygons = this.features.filter(f => f.properties.feature_type === 'polygon').length;
                this.stats.circles = this.features.filter(f => f.properties.feature_type === 'circle').length;

                // Update dynamic legend data
                this.updateDynamicLegend();
            },

            updateDynamicLegend() {
                // Extract unique diameters from line features
                const diameters = new Set();
                const statuses = new Set();
                const lines = [];

                this.features.forEach(feature => {
                    if (feature.properties.feature_type === 'line') {
                        const metadata = feature.properties.metadata || {};
                        const style = feature.properties.style_properties || {};

                        // Collect diameters
                        if (metadata.diameter) {
                            diameters.add(parseInt(metadata.diameter));
                        }

                        // Collect statuses
                        if (metadata.status) {
                            statuses.add(metadata.status);
                        }

                        // Calculate progress
                        const estimasi = parseFloat(metadata.estimasi_panjang) || 0;
                        const actual = parseFloat(metadata.total_penggelaran) || 0;
                        const progress = estimasi > 0 ? ((actual / estimasi) * 100) : 0;

                        // Collect line info with ACTUAL color from map
                        lines.push({
                            id: feature.id,
                            name: feature.properties.name || 'Unnamed Line',
                            line_number: metadata.line_number || '-',
                            diameter: metadata.diameter || '-',
                            color: style.color || '#6B7280', // ACTUAL color from style
                            weight: style.weight || 4,
                            cluster_name: metadata.cluster_name || '-',
                            status: metadata.status || 'draft',
                            estimasi_panjang: estimasi,
                            total_penggelaran: actual,
                            progress: progress.toFixed(1)
                        });
                    }
                });

                // Sort diameters in ascending order
                this.uniqueDiameters = Array.from(diameters).sort((a, b) => a - b).map(d => {
                    return {
                        diameter: d,
                        color: this.getDiameterColor(d.toString()),
                        weight: this.getStyleByDiameter(d).weight
                    };
                });

                // Convert statuses to array with badge info
                this.uniqueStatuses = Array.from(statuses).map(s => {
                    return {
                        status: s,
                        badge: this.getStatusBadge(s)
                    };
                });

                // Sort lines by diameter then line number
                this.legendLines = lines.sort((a, b) => {
                    // First by diameter
                    const diameterDiff = (parseInt(a.diameter) || 0) - (parseInt(b.diameter) || 0);
                    if (diameterDiff !== 0) return diameterDiff;
                    // Then by line number
                    return (a.line_number || '').localeCompare(b.line_number || '');
                });
            },

            fitMapToBounds() {
                // Check if map is initialized
                if (!this.map) {
                    console.warn('Map not initialized yet, skipping fitBounds');
                    return;
                }

                const allCoords = [];

                this.features.forEach(feature => {
                    const geometry = feature.geometry;
                    if (geometry.type === 'LineString') {
                        // GeoJSON: [lng, lat] → Leaflet: [lat, lng]
                        geometry.coordinates.forEach(coord => allCoords.push([coord[1], coord[0]]));
                    } else if (geometry.type === 'Polygon') {
                        // GeoJSON: [lng, lat] → Leaflet: [lat, lng]
                        geometry.coordinates[0].forEach(coord => allCoords.push([coord[1], coord[0]]));
                    } else if (geometry.type === 'Point') {
                        // GeoJSON: [lng, lat] → Leaflet: [lat, lng]
                        allCoords.push([geometry.coordinates[1], geometry.coordinates[0]]);
                    }
                });

                if (allCoords.length > 0) {
                    const bounds = L.latLngBounds(allCoords);
                    this.map.fitBounds(bounds, { padding: [50, 50] });
                }
            },

            async refreshMapData() {
                await this.loadMapData();
                window.showToast && window.showToast('success', 'Map data refreshed');
            },

            // Populate dropdown with Line Numbers when popup opens
            attachAssignmentListeners(featureId) {
                const selectElement = document.getElementById(`assign-line-select-${featureId}`);
                if (selectElement && this.availableLineNumbers.length > 0) {
                    // Populate dropdown
                    this.availableLineNumbers.forEach(line => {
                        const option = document.createElement('option');
                        option.value = line.id;
                        option.textContent = `${line.line_number} - ${line.nama_jalan} (Ø${line.diameter}mm)`;
                        selectElement.appendChild(option);
                    });
                }
            },

            // Assign line from map popup
            async assignLineFromMap(featureId) {
                const selectElement = document.getElementById(`assign-line-select-${featureId}`);
                const lineNumberId = selectElement?.value;

                if (!lineNumberId) {
                    window.showToast && window.showToast('error', 'Pilih Line Number terlebih dahulu');
                    return;
                }

                try {
                    const response = await fetch(`/jalur/kmz-import/${featureId}/assign`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ line_number_id: lineNumberId })
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.showToast && window.showToast('success', result.message);

                        // Reload map data to show updated line
                        await this.loadMapData();
                    } else {
                        throw new Error(result.message || 'Assignment gagal');
                    }
                } catch (error) {
                    console.error('Assignment error:', error);
                    window.showToast && window.showToast('error', 'Gagal assign jalur: ' + error.message);
                }
            },

            // Delete unassigned line from map
            async deleteUnassignedLine(featureId) {
                if (!confirm('Yakin ingin menghapus jalur ini?')) return;

                try {
                    const response = await fetch(`/jalur/kmz-import/${featureId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': window.csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.showToast && window.showToast('success', result.message);

                        // Reload map data
                        await this.loadMapData();
                    } else {
                        throw new Error(result.message || 'Delete gagal');
                    }
                } catch (error) {
                    console.error('Delete error:', error);
                    window.showToast && window.showToast('error', 'Gagal menghapus jalur: ' + error.message);
                }
            }
        };
    };
</script>

<style>
    .jalur-feature-popup .leaflet-popup-content-wrapper {
        @apply rounded-lg shadow-lg;
    }

    .jalur-feature-popup .leaflet-popup-content {
        @apply m-0 p-3;
    }

    .jalur-popup-content {
        @apply min-w-[200px];
    }

    /* Custom Scrollbar for Legend */
    .custom-scrollbar::-webkit-scrollbar {
        width: 8px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /*
 * Z-Index Hierarchy for Jalur Map:
 * ----------------------------------
 * z-50  : Sidebar (from layout)
 * z-40  : Sidebar backdrop (from layout)
 * z-35  : Map modals and dropdowns (below sidebar)
 * z-30  : Map loading overlay and leaflet controls (mobile)
 * z-10  : Map controls (desktop)
 * z-1   : Map container and panes (always at bottom)
 *
 * This ensures sidebar always appears above map elements
 */

    /* Ensure map stays below sidebar */
    #jalur-map {
        z-index: 1 !important;
    }

    #jalur-map .leaflet-pane,
    #jalur-map .leaflet-map-pane {
        z-index: 1 !important;
    }

    /* Map controls should be below sidebar backdrop (z-40) and sidebar (z-50) */
    #jalur-map .leaflet-control-container {
        z-index: 10 !important;
    }

    /* Mobile adjustments - keep below sidebar */
    @media (max-width: 1023px) {

        div[x-data="{ open: false }"]>div[x-show="open"].absolute {
            z-index: 35 !important;
        }

        div.fixed.inset-0.bg-black.bg-opacity-50[x-show="showLineNumberModal"] {
            z-index: 35 !important;
        }

        .leaflet-draw {
            z-index: 30 !important;
        }

        .leaflet-control-container .leaflet-top,
        .leaflet-control-container .leaflet-bottom {
            z-index: 30 !important;
        }

        .leaflet-popup-pane {
            z-index: 35 !important;
        }
    }
</style>