<script>
// Pre-define window.jalurMapsData immediately to avoid Alpine initialization errors
// This ensures all properties exist when Alpine initializes the component
// Full implementation with methods will override this below
if (typeof window.jalurMapsData === 'undefined') {
    window.jalurMapsData = function() {
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
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-route text-white"></i>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Pipeline Jalur</h2>
                    <p class="text-sm text-gray-600">Visualisasi Jalur</p>
                </div>
            </div>

            <!-- Map Controls -->
            <div class="flex items-center space-x-2">
                <!-- Layer Toggle -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="flex items-center space-x-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-layer-group text-gray-600"></i>
                        <span class="text-sm font-medium text-gray-700">Layers</span>
                        <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                    </button>

                    <!-- Layer Dropdown -->
                    <div x-show="open"
                         @click.away="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 z-[35]"
                         style="display: none;">

                        <div class="p-4 space-y-3">
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Toggle Layers</div>

                            <!-- Line Features Layer -->
                            <label class="flex items-center justify-between cursor-pointer group">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                    <span class="text-sm text-gray-700 group-hover:text-gray-900">Jalur Lines</span>
                                </div>
                                <input type="checkbox"
                                       x-model="layers.lines"
                                       @change="toggleLayer('lines')"
                                       class="rounded text-blue-600 focus:ring-blue-500">
                            </label>

                            <!-- Cluster Boundaries Layer -->
                            <label class="flex items-center justify-between cursor-pointer group">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                                    <span class="text-sm text-gray-700 group-hover:text-gray-900">Cluster Boundaries</span>
                                </div>
                                <input type="checkbox"
                                       x-model="layers.clusters"
                                       @change="toggleLayer('clusters')"
                                       class="rounded text-purple-600 focus:ring-purple-500">
                            </label>

                            <!-- Additional Features Layer -->
                            <label class="flex items-center justify-between cursor-pointer group">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                                    <span class="text-sm text-gray-700 group-hover:text-gray-900">Other Features</span>
                                </div>
                                <input type="checkbox"
                                       x-model="layers.others"
                                       @change="toggleLayer('others')"
                                       class="rounded text-orange-600 focus:ring-orange-500">
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Refresh Button -->
                <button @click="refreshMapData()"
                        :disabled="loading"
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
                    <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-sm font-medium text-gray-600">Loading map data...</p>
                </div>
            </div>
        </div>

        <!-- Feature Selection Modal (Line Number or Cluster) -->
        <div x-show="showLineNumberModal"
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[35]"
             style="display: none;"
             @click.self="cancelDrawing()">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4" x-text="isEditMode ? (pendingFeatureType === 'line' ? 'Edit Pipeline Route' : 'Edit Cluster Boundary') : (pendingFeatureType === 'line' ? 'Draw Pipeline Route' : 'Draw Cluster Boundary')"></h3>

                    <!-- For Polyline: Select Line Number -->
                    <div x-show="pendingFeatureType === 'line'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Line Number <span class="text-red-500">*</span>
                        </label>
                        <select x-model="selectedLineNumberId"
                                @change="updateStyleFromLineNumber()"
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
                                    <span class="font-medium">Ø<span x-text="selectedLineNumber?.diameter || '-'"></span>mm</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Estimasi (MC-0):</span>
                                    <span class="font-medium"><span x-text="selectedLineNumber?.estimasi_panjang || '-'"></span>m</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- For Polygon: Select Cluster -->
                    <div x-show="pendingFeatureType === 'polygon'" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Cluster <span class="text-red-500">*</span>
                        </label>
                        <select x-model="selectedClusterId"
                                @change="updateStyleFromCluster()"
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
                            <input type="color"
                                   x-model="customColor"
                                   @change="updateCustomColor()"
                                   class="w-12 h-10 border border-gray-300 rounded cursor-pointer">
                            <input type="text"
                                   x-model="customColor"
                                   placeholder="#10B981"
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
                        <input type="text"
                               x-model="customFeatureName"
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

        <!-- Map Legend -->
        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Legend</div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <!-- Line by Diameter -->
                <div class="space-y-2">
                    <div class="text-xs font-medium text-gray-700 mb-2">Diameter (Auto Color)</div>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-1 bg-green-500 rounded"></div>
                        <span class="text-xs text-gray-600">Ø63mm</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-1.5 bg-blue-500 rounded"></div>
                        <span class="text-xs text-gray-600">Ø90mm</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-2 bg-red-500 rounded"></div>
                        <span class="text-xs text-gray-600">Ø180mm</span>
                    </div>
                </div>

                <!-- Line Status -->
                <div class="space-y-2">
                    <div class="text-xs font-medium text-gray-700 mb-2">Status</div>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-1 border-2 border-green-500 rounded"></div>
                        <span class="text-xs text-gray-600">Completed</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-1 border-2 border-blue-500 border-dashed rounded"></div>
                        <span class="text-xs text-gray-600">In Progress</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-1 border-2 border-gray-400 rounded"></div>
                        <span class="text-xs text-gray-600">Draft</span>
                    </div>
                </div>

                <!-- Feature Types -->
                <div class="space-y-2">
                    <div class="text-xs font-medium text-gray-700 mb-2">Features</div>
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 bg-purple-100 border-2 border-purple-500 rounded"></div>
                        <span class="text-xs text-gray-600">Cluster Area</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 bg-orange-100 border-2 border-orange-500 rounded-full"></div>
                        <span class="text-xs text-gray-600">Special Point</span>
                    </div>
                </div>

                <!-- Stats -->
                <div class="space-y-2">
                    <div class="text-xs font-medium text-gray-700 mb-2">Total Features</div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600">Lines:</span>
                        <span class="text-sm font-semibold text-blue-600" x-text="stats.lines">0</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600">Polygons:</span>
                        <span class="text-sm font-semibold text-purple-600" x-text="stats.polygons">0</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600">Points:</span>
                        <span class="text-sm font-semibold text-orange-600" x-text="stats.circles">0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Define jalurMapsData globally before Alpine initializes
window.jalurMapsData = function() {
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
                const response = await fetch('{{ route('map-features.index') }}', {
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
            const style = properties.style || this.getDefaultStyle(properties.feature_type);

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
                // Add popup with feature info
                const popupContent = this.createPopupContent(feature);
                layer.bindPopup(popupContent, {
                    maxWidth: 360,
                    minWidth: 300,
                    maxHeight: 500,
                    autoPan: true,
                    autoPanPadding: [50, 50],
                    className: 'jalur-feature-popup'
                });

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

        getDefaultStyle(featureType) {
            const defaults = {
                line: {
                    color: '#3388ff',
                    weight: 4,
                    opacity: 0.8
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

            // For Pipeline Route (Line Number)
            if (props.feature_type === 'line' && props.line_number_id) {
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
        },

        fitMapToBounds() {
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

    div[x-data="{ open: false }"] > div[x-show="open"].absolute {
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
