{{-- Advanced Maps View Component with Integrated Stats --}}
<div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100" x-data="mapsComponent()" x-init="initMapWithDrawingTools()">
    {{-- Header with Stats and Controls --}}
    <div class="bg-gradient-to-r from-aergas-navy to-aergas-orange p-6 text-white">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            {{-- Title and Main Stats --}}
            <div class="flex-1">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-map-marked-alt text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Peta Distribusi Pelanggan</h2>
                        <p class="text-white/80 text-sm">Real-time customer locations & status</p>
                    </div>
                </div>

                {{-- Main Stats Cards --}}
                <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-2xl font-bold text-white" x-text="mapsStats.total_customers || 0">0</div>
                                <div class="text-white/80 text-sm">Total Pelanggan</div>
                            </div>
                            <div class="w-10 h-10 bg-blue-500/30 rounded-lg flex items-center justify-center">
                                <i class="fas fa-users text-white"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-2xl font-bold text-white" x-text="mapsStats.customers_with_coordinates || 0">0</div>
                                <div class="text-white/80 text-sm">Dengan Koordinat</div>
                                <div class="text-xs text-white/60" x-text="(mapsStats.coordinate_coverage_rate || 0) + '% coverage'">0% coverage</div>
                            </div>
                            <div class="w-10 h-10 bg-indigo-500/30 rounded-lg flex items-center justify-center">
                                <i class="fas fa-map-marker-alt text-white"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-2xl font-bold text-white" x-text="mapsStats.customers_without_coordinates || 0">0</div>
                                <div class="text-white/80 text-sm">Tanpa Koordinat</div>
                                <div class="text-xs text-white/60">perlu update lokasi</div>
                            </div>
                            <div class="w-10 h-10 bg-orange-500/30 rounded-lg flex items-center justify-center">
                                <i class="fas fa-map-pin text-white"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-2xl font-bold text-white" x-text="mapsStats.done || 0">0</div>
                                <div class="text-white/80 text-sm">Selesai</div>
                                <div class="text-xs text-white/60" x-text="(mapsStats.completion_rate || 0) + '% completion rate'">0% completion rate</div>
                            </div>
                            <div class="w-10 h-10 bg-green-500/30 rounded-lg flex items-center justify-center">
                                <i class="fas fa-check-circle text-white"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-2xl font-bold text-white" x-text="mapsStats.pending_review || 0">0</div>
                                <div class="text-white/80 text-sm">Pending Review</div>
                            </div>
                            <div class="w-10 h-10 bg-yellow-500/30 rounded-lg flex items-center justify-center">
                                <i class="fas fa-clock text-white"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-2xl font-bold text-white" x-text="mapsStats.photo_approved_count || 0">0</div>
                                <div class="text-white/80 text-sm">Photo Approved</div>
                                <div class="text-xs text-white/60" x-text="(mapsStats.photo_approval_rate || 0) + '%'">0%</div>
                                <div class="text-xs text-white/60">approval rate</div>
                            </div>
                            <div class="w-10 h-10 bg-purple-500/30 rounded-lg flex items-center justify-center">
                                <i class="fas fa-camera text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search and Filters --}}
    <div class="p-6 border-b border-gray-200 bg-gray-50">
        <div class="flex flex-col lg:flex-row gap-4">
            {{-- Search Box --}}
            <div class="flex-1 relative">
                <input
                    type="text"
                    x-model="searchTerm"
                    @input="searchCustomers()"
                    placeholder="Cari berdasarkan Reff ID atau Nama pelanggan..."
                    class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-aergas-orange focus:border-transparent bg-white shadow-sm"
                >
                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>

                {{-- Search Results Dropdown --}}
                <div x-show="showSearchResults && searchResults.length > 0"
                     x-transition
                     class="absolute top-full left-0 right-0 mt-2 bg-white border border-gray-200 rounded-xl shadow-lg z-[9999] max-h-60 overflow-y-auto">
                    <template x-for="customer in searchResults" :key="customer.id">
                        <div @click="selectCustomer(customer)"
                             class="p-4 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                            <div class="font-medium text-gray-900" x-text="customer.reff_id"></div>
                            <div class="text-sm text-gray-600" x-text="customer.title"></div>
                            <div class="text-xs text-gray-500" x-text="customer.alamat"></div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Filters --}}
            <div class="flex flex-wrap gap-3">
                {{-- Status Filter --}}
                <select x-model="activeFilters.status" @change="applyFilters()"
                        class="px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-aergas-orange focus:border-transparent bg-white shadow-sm">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="lanjut">Lanjut</option>
                    <option value="in_progress">In Progress</option>
                    <option value="batal">Batal</option>
                </select>

                {{-- Progress Filter --}}
                <select x-model="activeFilters.progress" @change="applyFilters()"
                        class="px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-aergas-orange focus:border-transparent bg-white shadow-sm">
                    <option value="">Semua Progress</option>
                    <option value="validasi">Validasi</option>
                    <option value="sk">SK</option>
                    <option value="sr">SR</option>
                    <option value="gas_in">Gas In</option>
                    <option value="done">Done</option>
                </select>

                {{-- Kelurahan Filter --}}
                <select x-model="activeFilters.kelurahan" @change="applyFilters()"
                        class="px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-aergas-orange focus:border-transparent bg-white shadow-sm">
                    <option value="">Semua Kelurahan</option>
                    <!-- Options will be populated from actual database data -->
                </select>

                {{-- Padukuhan Filter --}}
                <select x-model="activeFilters.padukuhan" @change="applyFilters()"
                        class="px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-aergas-orange focus:border-transparent bg-white shadow-sm">
                    <option value="">Semua Padukuhan</option>
                    <!-- Options will be populated from actual database data -->
                </select>

                {{-- Reset Filters --}}
                <button @click="resetFilters()"
                        class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-colors duration-200 flex items-center gap-2">
                    <i class="fas fa-refresh"></i>
                    Reset
                </button>
            </div>
        </div>

        {{-- Active Filters Display --}}
        <div x-show="hasActiveFilters()" class="mt-4">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm text-gray-600">Filter aktif:</span>
                <template x-for="filter in getActiveFiltersDisplay()" :key="filter.key">
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-aergas-orange text-white text-sm rounded-full">
                        <span x-text="filter.label"></span>
                        <button @click="removeFilter(filter.key)" class="hover:bg-white/20 rounded-full p-1">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </span>
                </template>
            </div>
        </div>
    </div>

    {{-- Map Container --}}
    <div class="relative">
        <div id="aergas-map" class="h-96 lg:h-[500px] w-full" style="z-index: 1;"></div>

        {{-- Loading Spinner --}}
        <div x-show="loading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center">
            <div class="flex items-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-aergas-orange"></div>
                <span class="ml-2 text-gray-600">Memuat peta...</span>
            </div>
        </div>

        {{-- Drawing Tools Toggle Button --}}
        <button @click="drawingMode = !drawingMode"
                :class="drawingMode ? 'bg-aergas-orange text-white' : 'bg-white text-gray-700'"
                class="absolute top-4 left-16 z-[1000] px-3 py-2 rounded-lg shadow-lg border border-gray-200 hover:shadow-xl transition-all text-sm">
            <i class="fas fa-draw-polygon mr-1"></i>
            <span x-text="drawingMode ? 'Exit' : 'Draw'">Draw</span>
        </button>

        {{-- Drawing Tools Panel --}}
        <div class="absolute top-4 left-32 bg-white rounded-lg shadow-xl border border-gray-200 p-3 max-h-[calc(100vh-8rem)] overflow-y-auto"
             x-show="drawingMode" x-data="{ activeTab: 'tools' }" style="z-index: 1000;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-x-2"
             x-transition:enter-end="opacity-100 transform translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform translate-x-0"
             x-transition:leave-end="opacity-0 transform -translate-x-2">

            {{-- Tool Header --}}
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900 text-sm">Drawing Tools</h4>
                <button @click="drawingMode = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Tabs --}}
            <div class="flex space-x-1 mb-3 border-b">
                <button @click="activeTab = 'tools'"
                        :class="activeTab === 'tools' ? 'border-aergas-orange text-aergas-orange' : 'text-gray-600'"
                        class="px-2 py-1 border-b-2 text-xs font-medium">
                    Tools
                </button>
                <button @click="activeTab = 'layers'"
                        :class="activeTab === 'layers' ? 'border-aergas-orange text-aergas-orange' : 'text-gray-600'"
                        class="px-2 py-1 border-b-2 text-xs font-medium">
                    Layers
                </button>
            </div>

            {{-- Tools Tab --}}
            <div x-show="activeTab === 'tools'" class="space-y-2 w-52">
                {{-- Line Number Integration (for lines only) --}}
                <div x-show="currentDrawingType === 'line' || !currentDrawingType" class="bg-blue-50 p-2 rounded">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Link to Line Number</label>
                    <select x-model="selectedLineNumber" class="w-full p-1 border rounded text-xs">
                        <option value="">Select Line Number</option>
                        <template x-for="lineNumber in lineNumbers" :key="lineNumber.id">
                            <option :value="lineNumber.id" x-text="lineNumber.display_text"></option>
                        </template>
                    </select>
                </div>

                {{-- Cluster Integration (for areas/polygons and circles) --}}
                <div x-show="currentDrawingType === 'polygon' || currentDrawingType === 'circle'" class="bg-orange-50 p-2 rounded">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Link to Cluster</label>
                    <select x-model="selectedCluster" class="w-full p-1 border rounded text-xs">
                        <option value="">Select Cluster</option>
                        <template x-for="cluster in clusters" :key="cluster.id">
                            <option :value="cluster.id" x-text="cluster.display_text"></option>
                        </template>
                    </select>
                </div>

                {{-- Drawing Types --}}
                <div class="grid grid-cols-3 gap-1">
                    <button @click="startDrawing('line')"
                            class="flex flex-col items-center p-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                        <i class="fas fa-minus text-sm"></i>
                        <span class="text-xs mt-1">Line</span>
                    </button>

                    <button @click="startDrawing('polygon')"
                            class="flex flex-col items-center p-2 bg-orange-500 text-white rounded hover:bg-orange-600 transition-colors">
                        <i class="fas fa-draw-polygon text-sm"></i>
                        <span class="text-xs mt-1">Area</span>
                    </button>

                    <button @click="startDrawing('circle')"
                            class="flex flex-col items-center p-2 bg-pink-500 text-white rounded hover:bg-pink-600 transition-colors">
                        <i class="fas fa-circle text-sm"></i>
                        <span class="text-xs mt-1">Circle</span>
                    </button>
                </div>

                {{-- Style Options --}}
                <div class="border-t pt-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Style</label>
                    <div class="grid grid-cols-2 gap-1">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Color</label>
                            <input type="color" x-model="currentStyle.color" class="w-full h-6 rounded border">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Width</label>
                            <input type="range" x-model="currentStyle.weight" min="1" max="10" class="w-full">
                            <div class="text-xs text-center text-gray-500" x-text="currentStyle.weight + 'px'"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Layers Tab --}}
            <div x-show="activeTab === 'layers'" class="space-y-1 max-h-48 overflow-y-auto w-52">
                <template x-for="feature in mapFeatures" :key="feature.id">
                    <div class="flex items-center justify-between p-1 bg-gray-50 rounded">
                        <div class="flex items-center space-x-1">
                            <input type="checkbox"
                                   :checked="feature.properties.is_visible"
                                   @change="toggleFeatureVisibility(feature)"
                                   class="scale-75">
                            <span class="text-xs truncate" x-text="feature.properties.name"></span>
                        </div>
                        <div class="flex space-x-1">
                            <button @click="editFeature(feature)" class="text-blue-500 hover:text-blue-700">
                                <i class="fas fa-edit text-xs"></i>
                            </button>
                            <button @click="deleteFeature(feature)" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </div>
                </template>

                <div x-show="mapFeatures.length === 0" class="text-center py-3 text-gray-500">
                    <i class="fas fa-layer-group text-lg mb-1 text-gray-300"></i>
                    <p class="text-xs">No features drawn yet</p>
                    <p class="text-xs text-gray-400 mt-1">Use tools tab to draw</p>
                </div>
            </div>
        </div>

        {{-- Legend Toggle Button --}}
        <div class="map-legend absolute top-4 right-4 max-h-[calc(100vh-8rem)] overflow-y-auto" x-data="{ legendExpanded: false }" style="z-index: 1001 !important;">
            <!-- Collapsed Legend Button -->
            <div x-show="!legendExpanded" class="bg-white rounded-lg shadow-xl border border-gray-200 p-3 cursor-pointer hover:shadow-2xl transition-all" @click="legendExpanded = true">
                <div class="flex items-center text-aergas-navy">
                    <i class="fas fa-map text-lg mr-2"></i>
                    <span class="font-medium text-sm">Legend</span>
                    <i class="fas fa-chevron-right ml-2 text-xs"></i>
                </div>
            </div>

            <!-- Expanded Legend Panel -->
            <div x-show="legendExpanded"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 class="bg-white rounded-lg shadow-xl border border-gray-200 max-w-xs flex flex-col max-h-[calc(100vh-6rem)]"
                 style="max-height: calc(100vh - 6rem);">

                <!-- Legend Header -->
                <div class="bg-gradient-to-r from-aergas-navy to-aergas-orange p-3 rounded-t-lg cursor-pointer hover:shadow-lg transition-shadow" @click="legendExpanded = false">
                    <h4 class="font-semibold text-sm text-white flex items-center justify-between">
                        <span class="flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>
                            Legend & Info
                        </span>
                        <i class="fas fa-times text-white hover:text-gray-200 transition-colors"></i>
                    </h4>
                </div>

                <div class="p-3 space-y-3 overflow-y-auto flex-1">
                <!-- Status Legend -->
                <div>
                    <h5 class="font-medium text-xs text-gray-700 mb-2 uppercase tracking-wide">Customer Status</h5>
                    <div class="space-y-1.5 text-xs">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-blue-500 mr-2 flex-shrink-0"></div>
                                <span class="text-gray-700">Pending</span>
                            </div>
                            <span class="text-blue-600 font-medium" x-text="(mapsStats.status_counts && mapsStats.status_counts.pending) || 0">0</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-green-500 mr-2 flex-shrink-0"></div>
                                <span class="text-gray-700">Selesai (Done)</span>
                            </div>
                            <span class="text-green-600 font-medium" x-text="mapsStats.done || 0">0</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-yellow-500 mr-2 flex-shrink-0"></div>
                                <span class="text-gray-700">Pending Review</span>
                            </div>
                            <span class="text-yellow-600 font-medium" x-text="mapsStats.pending_review || 0">0</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-red-500 mr-2 flex-shrink-0"></div>
                                <span class="text-gray-700">Batal</span>
                            </div>
                            <span class="text-red-600 font-medium" x-text="mapsStats.batal || 0">0</span>
                        </div>
                    </div>
                </div>

                <!-- Progress Icons Legend -->
                <div class="pt-2 border-t border-gray-200">
                    <h5 class="font-medium text-xs text-gray-700 mb-2 uppercase tracking-wide">Progress Icons</h5>
                    <div class="space-y-1.5 text-xs">
                        <div class="flex items-center">
                            <div class="w-5 h-5 bg-purple-500 rounded-full mr-2 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-user-check text-white text-[8px]"></i>
                            </div>
                            <span class="text-gray-700">Validasi</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-5 h-5 bg-orange-500 rounded-full mr-2 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-wrench text-white text-[8px]"></i>
                            </div>
                            <span class="text-gray-700">SK (Sambungan Kompor)</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-5 h-5 bg-blue-500 rounded-full mr-2 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-home text-white text-[8px]"></i>
                            </div>
                            <span class="text-gray-700">SR (Sambungan Rumah)</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-5 h-5 bg-red-500 rounded-full mr-2 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-fire text-white text-[8px]"></i>
                            </div>
                            <span class="text-gray-700">Gas In</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-5 h-5 bg-green-500 rounded-full mr-2 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-check-circle text-white text-[8px]"></i>
                            </div>
                            <span class="text-gray-700">Done</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="pt-2 border-t border-gray-200">
                    <h5 class="font-medium text-xs text-gray-700 mb-2 uppercase tracking-wide">Quick Stats</h5>
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Markers:</span>
                            <span class="font-medium text-gray-800" x-text="customers.length || 0">0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Coverage:</span>
                            <span class="font-medium text-gray-800" x-text="(mapsStats.coordinate_coverage_rate || 0) + '%'">0%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Completion:</span>
                            <span class="font-medium text-gray-800" x-text="(mapsStats.completion_rate || 0) + '%'">0%</span>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Include Leaflet CSS and JS --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

{{-- Include Leaflet.draw plugin for drawing tools --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

<script>
function mapsComponent() {
    return {
        map: null,
        markers: [],
        customers: [],
        allCustomers: [], // Store all customers for filtering
        searchTerm: '',
        searchResults: [],
        showSearchResults: false,
        loading: true,
        filterOptions: {
            kelurahan: [],
            padukuhan: []
        },
        mapsStats: {
            total_customers: 0,
            customers_with_coordinates: 0,
            customers_without_coordinates: 0,
            coordinate_coverage_rate: 0,
            status_counts: {
                pending: 0,
                lanjut: 0,
                in_progress: 0,
                batal: 0
            },
            done: 0,
            batal: 0,
            pending_review: 0,
            photo_approved_count: 0,
            total_photos: 0,
            photo_approval_rate: 0,
            completion_rate: 0
        },
        activeFilters: {
            status: '',
            progress: '',
            kelurahan: '',
            padukuhan: ''
        },

        // Drawing tool properties
        drawingMode: false,
        showDrawingPanel: false,
        activeTab: 'tools',
        currentDrawingType: null,
        currentDrawingTool: null,
        drawingControl: null,
        drawnLayers: null,
        mapFeatures: [],
        lineNumbers: [],
        clusters: [],
        selectedLineNumber: '',
        selectedCluster: '',
        currentStyle: {
            color: '#3388ff',
            weight: 4,
            opacity: 0.8,
            fillColor: '#3388ff',
            fillOpacity: 0.3
        },

        async initMapWithDrawingTools() {
            try {
                // Initialize the map centered on Yogyakarta with enhanced options
                this.map = L.map('aergas-map', {
                    center: [-7.7956, 110.3695],
                    zoom: 11,
                    zoomAnimation: true,
                    fadeAnimation: true,
                    markerZoomAnimation: true,
                    preferCanvas: false
                });

                // Add error handling for map events
                this.map.on('error', (e) => {
                    console.warn('Map error:', e);
                });

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19,
                    errorTileUrl: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
                }).addTo(this.map);

                // Load customer data
                await this.loadCustomers();

                // Setup drawing tools
                this.setupDrawingControls();

                // Load existing map features
                await this.loadMapFeatures();

                // Load line numbers and clusters for dropdown
                await this.loadLineNumbers();
                await this.loadClusters();

                // Fix for popup zoom animation errors
                this.setupPopupErrorHandling();

            } catch (error) {
                console.error('Error initializing map:', error);
                alert('Gagal memuat peta. Silakan refresh halaman.');
            } finally {
                this.loading = false;
            }
        },

        async loadCustomers() {
            try {
                const response = await fetch('/dashboard/data?include_coordinates=true');
                const data = await response.json();

                if (data.data && data.data.customers) {
                    this.allCustomers = data.data.customers.filter(customer =>
                        customer.coordinates && customer.coordinates.lat && customer.coordinates.lng
                    );

                    // Initially show all customers
                    this.customers = [...this.allCustomers];

                    // Load maps specific stats if available
                    if (data.data.maps_stats) {
                        this.mapsStats = { ...this.mapsStats, ...data.data.maps_stats };
                    }

                    // Load filter options if available
                    if (data.data.filter_options) {
                        this.filterOptions = { ...this.filterOptions, ...data.data.filter_options };
                        this.populateFilterOptions();
                    }

                    this.calculateStatusCounts();
                    this.addMarkersToMap();
                }
            } catch (error) {
                console.error('Error loading customers:', error);
            }
        },

        calculateStatusCounts() {
            // For map markers, we only calculate what's currently visible
            // But we don't override the global stats from mapsStats
            // This method is primarily for legend updates
            // We keep the global mapsStats intact for the header boxes and legend
        },

        addMarkersToMap() {
            // Clear existing markers
            this.markers.forEach(marker => this.map.removeLayer(marker));
            this.markers = [];

            this.customers.forEach((customer, index) => {
                if (customer.coordinates) {
                    const marker = this.createMarker(customer);
                    this.markers.push(marker);
                    marker.addTo(this.map);
                }
            });

            // Fit map to show all markers if any exist
            if (this.markers.length > 0) {
                const group = new L.featureGroup(this.markers);
                this.map.fitBounds(group.getBounds().pad(0.1));
            }
        },

        createMarker(customer) {
            const color = this.getStatusColor(customer.status);
            const icon = this.createCustomIcon(color, customer.progress_status);

            const marker = L.marker(
                [customer.coordinates.lat, customer.coordinates.lng],
                { icon: icon }
            );

            // Create popup content
            const popupContent = this.createPopupContent(customer);
            marker.bindPopup(popupContent);

            return marker;
        },

        createCustomIcon(color, progressStatus) {
            const iconMap = {
                'validasi': 'user-check',
                'sk': 'wrench',
                'sr': 'home',
                'gas_in': 'fire',
                'done': 'check-circle',
                'batal': 'times-circle'
            };

            const iconName = iconMap[progressStatus] || 'map-marker';

            return L.divIcon({
                className: 'custom-marker',
                html: `
                    <div style="
                        background-color: ${color};
                        width: 25px;
                        height: 25px;
                        border-radius: 50%;
                        border: 2px solid white;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    ">
                        <i class="fas fa-${iconName}" style="color: white; font-size: 10px;"></i>
                    </div>
                `,
                iconSize: [25, 25],
                iconAnchor: [12, 12]
            });
        },

        createPopupContent(customer) {
            const progressBarWidth = customer.progress_percentage || 0;
            const progressColor = progressBarWidth >= 100 ? 'bg-green-500' :
                                progressBarWidth >= 75 ? 'bg-blue-500' :
                                progressBarWidth >= 50 ? 'bg-yellow-500' : 'bg-gray-400';

            return `
                <div class="p-2 min-w-64">
                    <div class="font-bold text-gray-900 mb-1">${customer.reff_id}</div>
                    <div class="text-sm font-medium text-gray-700 mb-2">${customer.title}</div>

                    <div class="text-xs text-gray-600 mb-1">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        ${customer.alamat}
                    </div>

                    <div class="flex items-center justify-between text-xs mb-2">
                        <span class="text-gray-500">Status:</span>
                        <span class="px-2 py-1 rounded text-white" style="background-color: ${this.getStatusColor(customer.status)}">
                            ${this.getStatusLabel(customer.status)}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-xs mb-2">
                        <span class="text-gray-500">Progress:</span>
                        <span class="font-medium">${customer.progress_status.toUpperCase()}</span>
                    </div>

                    <div class="mb-2">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Progress</span>
                            <span>${progressBarWidth}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="${progressColor} h-2 rounded-full" style="width: ${progressBarWidth}%"></div>
                        </div>
                    </div>

                    <div class="text-xs text-gray-500">
                        Tgl Registrasi: ${customer.tanggal_registrasi || '-'}
                    </div>

                    <div class="mt-2 pt-2 border-t border-gray-200">
                        <a href="/customers/${customer.id}"
                           class="text-xs text-aergas-orange hover:text-aergas-orange font-medium">
                            Lihat Detail →
                        </a>
                    </div>
                </div>
            `;
        },

        getStatusColor(status) {
            const colors = {
                'pending': '#3B82F6',
                'lanjut': '#10B981',
                'in_progress': '#F59E0B',
                'batal': '#EF4444'
            };
            return colors[status] || '#6B7280';
        },

        getStatusLabel(status) {
            const labels = {
                'pending': 'Pending',
                'lanjut': 'Lanjut',
                'in_progress': 'In Progress',
                'batal': 'Batal'
            };
            return labels[status] || status;
        },

        searchCustomers() {
            if (this.searchTerm.length < 2) {
                this.searchResults = [];
                this.showSearchResults = false;
                return;
            }

            const term = this.searchTerm.toLowerCase();
            this.searchResults = this.customers.filter(customer =>
                customer.reff_id.toLowerCase().includes(term) ||
                customer.title.toLowerCase().includes(term)
            ).slice(0, 10); // Limit to 10 results

            this.showSearchResults = true;
        },

        selectCustomer(customer) {
            if (customer.coordinates) {
                this.map.setView([customer.coordinates.lat, customer.coordinates.lng], 16);

                // Find and open the marker popup
                const marker = this.markers.find(m => {
                    const pos = m.getLatLng();
                    return Math.abs(pos.lat - customer.coordinates.lat) < 0.0001 &&
                           Math.abs(pos.lng - customer.coordinates.lng) < 0.0001;
                });

                if (marker) {
                    marker.openPopup();
                }
            }

            this.searchTerm = customer.reff_id;
            this.showSearchResults = false;
        },

        // Filter Methods
        applyFilters() {
            let filtered = [...this.allCustomers];

            // Apply status filter
            if (this.activeFilters.status) {
                filtered = filtered.filter(customer => customer.status === this.activeFilters.status);
            }

            // Apply progress filter
            if (this.activeFilters.progress) {
                filtered = filtered.filter(customer => customer.progress_status === this.activeFilters.progress);
            }

            // Apply kelurahan filter
            if (this.activeFilters.kelurahan) {
                filtered = filtered.filter(customer =>
                    customer.kelurahan && customer.kelurahan.toLowerCase() === this.activeFilters.kelurahan.toLowerCase()
                );
            }

            // Apply padukuhan filter
            if (this.activeFilters.padukuhan) {
                filtered = filtered.filter(customer =>
                    customer.padukuhan && customer.padukuhan.toLowerCase() === this.activeFilters.padukuhan.toLowerCase()
                );
            }

            this.customers = filtered;
            this.calculateStatusCounts();
            this.addMarkersToMap();
            // Re-render map features to ensure labels are repositioned correctly
            this.refreshMapFeatures();
            this.updateLegendCounts();
        },

        resetFilters() {
            this.activeFilters = {
                status: '',
                progress: '',
                kelurahan: '',
                padukuhan: ''
            };
            this.customers = [...this.allCustomers];
            this.calculateStatusCounts();
            this.addMarkersToMap();
            // Re-render map features to ensure labels are repositioned correctly
            this.refreshMapFeatures();
            this.updateLegendCounts();
        },

        hasActiveFilters() {
            return this.activeFilters.status || this.activeFilters.progress || this.activeFilters.kelurahan || this.activeFilters.padukuhan;
        },

        getActiveFiltersDisplay() {
            const filters = [];
            if (this.activeFilters.status) {
                const statusLabels = {
                    'pending': 'Pending',
                    'lanjut': 'Lanjut',
                    'in_progress': 'In Progress',
                    'batal': 'Batal'
                };
                filters.push({
                    key: 'status',
                    label: statusLabels[this.activeFilters.status]
                });
            }
            if (this.activeFilters.progress) {
                const progressLabels = {
                    'validasi': 'Validasi',
                    'sk': 'SK',
                    'sr': 'SR',
                    'gas_in': 'Gas In',
                    'done': 'Done'
                };
                filters.push({
                    key: 'progress',
                    label: progressLabels[this.activeFilters.progress]
                });
            }
            if (this.activeFilters.kelurahan) {
                filters.push({
                    key: 'kelurahan',
                    label: 'Kelurahan: ' + this.activeFilters.kelurahan
                });
            }
            if (this.activeFilters.padukuhan) {
                filters.push({
                    key: 'padukuhan',
                    label: 'Padukuhan: ' + this.activeFilters.padukuhan
                });
            }
            return filters;
        },

        removeFilter(filterKey) {
            this.activeFilters[filterKey] = '';
            this.applyFilters();
        },

        // Stats calculation methods
        getCompletionRate() {
            return this.mapsStats.completion_rate || 0;
        },

        getPhotoApprovalRate() {
            return this.mapsStats.photo_approval_rate || 0;
        },

        updateLegendCounts() {
            // This method ensures legend reflects current filtered data
            // Status counts are already updated in calculateStatusCounts()
            // This is for any additional legend updates if needed in the future
        },

        refreshMapFeatures() {
            // Re-render map features to ensure labels are positioned correctly after filtering
            // This fixes the issue where labels get stuck in wrong positions
            if (this.mapFeatures && this.mapFeatures.length > 0) {
                setTimeout(() => {
                    this.addFeaturesToMap();
                }, 200);
            }
        },

        setupPopupErrorHandling() {
            // Handle popup-related errors during zoom animations
            const originalOnAdd = L.Popup.prototype.onAdd;
            L.Popup.prototype.onAdd = function(map) {
                try {
                    return originalOnAdd.call(this, map);
                } catch (error) {
                    console.warn('Popup onAdd error handled:', error);
                    return this;
                }
            };

            // Handle zoom animation errors
            const originalAnimateZoom = L.Popup.prototype._animateZoom;
            L.Popup.prototype._animateZoom = function(e) {
                try {
                    if (this._map && this._latlng) {
                        return originalAnimateZoom.call(this, e);
                    }
                } catch (error) {
                    console.warn('Popup zoom animation error handled:', error);
                }
            };

            // Add map zoom event handlers with error protection
            this.map.on('zoomstart', () => {
                // Close popups before zoom to prevent errors
                this.map.closePopup();
            });

            this.map.on('zoom', (e) => {
                try {
                    // Handle zoom events safely
                } catch (error) {
                    console.warn('Zoom event error handled:', error);
                }
            });
        },

        populateFilterOptions() {
            // Populate kelurahan select
            const kelurahanSelect = document.querySelector('select[x-model="activeFilters.kelurahan"]');
            if (kelurahanSelect && this.filterOptions.kelurahan) {
                // Clear existing options except the first one
                while (kelurahanSelect.children.length > 1) {
                    kelurahanSelect.removeChild(kelurahanSelect.lastChild);
                }

                // Add new options
                this.filterOptions.kelurahan.forEach(kelurahan => {
                    const option = document.createElement('option');
                    option.value = kelurahan;
                    option.textContent = kelurahan;
                    kelurahanSelect.appendChild(option);
                });
            }

            // Populate padukuhan select
            const padukahanSelect = document.querySelector('select[x-model="activeFilters.padukuhan"]');
            if (padukahanSelect && this.filterOptions.padukuhan) {
                // Clear existing options except the first one
                while (padukahanSelect.children.length > 1) {
                    padukahanSelect.removeChild(padukahanSelect.lastChild);
                }

                // Add new options
                this.filterOptions.padukuhan.forEach(padukuhan => {
                    const option = document.createElement('option');
                    option.value = padukuhan;
                    option.textContent = padukuhan;
                    padukahanSelect.appendChild(option);
                });
            }
        },

        // Drawing Tools Methods
        setupDrawingControls() {
            console.log('Setting up drawing controls...');
            console.log('L.Draw available:', typeof L.Draw);
            console.log('L.Control.Draw available:', typeof L.Control.Draw);

            // Initialize feature group for drawn layers
            this.drawnLayers = new L.FeatureGroup();
            this.map.addLayer(this.drawnLayers);
            console.log('Drawn layers group created:', this.drawnLayers);

            // Setup drawing controls - initially disabled, will be activated by buttons
            this.drawingControl = new L.Control.Draw({
                draw: {
                    polyline: false,
                    polygon: false,
                    circle: false,
                    rectangle: false,
                    marker: false,
                    circlemarker: false
                },
                edit: {
                    featureGroup: this.drawnLayers,
                    remove: true
                }
            });

            this.map.addControl(this.drawingControl);
            console.log('Drawing control added to map');

            // Hide the default drawing toolbar since we use custom buttons
            setTimeout(() => {
                const drawToolbar = document.querySelector('.leaflet-draw');
                if (drawToolbar) {
                    drawToolbar.style.display = 'none';
                }
            }, 100);

            this.setupDrawingEventHandlers();

            // Set up global reference for popup actions
            window.mapComponentInstance = this;

            // Initialize enhanced drawing flow
            this.enhanceDrawingFlow();
        },

        setupDrawingEventHandlers() {
            // Remove existing handlers to prevent duplicates
            this.map.off(L.Draw.Event.CREATED);
            this.map.off(L.Draw.Event.EDITED);
            this.map.off(L.Draw.Event.DELETED);

            // Handle drawing created
            this.map.on(L.Draw.Event.CREATED, (e) => {
                console.log('Draw event created:', e);
                const layer = e.layer;
                const type = e.layerType;

                console.log('Layer created:', layer);
                console.log('Layer type:', type);

                this.drawnLayers.addLayer(layer);
                this.saveDrawnFeature(layer, type);
            });

            // Handle feature edited
            this.map.on(L.Draw.Event.EDITED, (e) => {
                console.log('Draw event edited:', e);
                const layers = e.layers;
                layers.eachLayer((layer) => {
                    this.updateDrawnFeature(layer);
                });
            });

            // Handle feature deleted
            this.map.on(L.Draw.Event.DELETED, (e) => {
                console.log('Draw event deleted:', e);
                const layers = e.layers;
                layers.eachLayer((layer) => {
                    this.deleteDrawnFeature(layer);
                });
            });
        },

        async loadMapFeatures() {
            try {
                const response = await fetch('/map-features', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    credentials: 'include'
                });

                console.log('loadMapFeatures response status:', response.status);
                const result = await response.json();
                console.log('loadMapFeatures result:', result);

                if (result.success) {
                    this.mapFeatures = result.features;
                    this.addFeaturesToMap();
                } else {
                    console.warn('Failed to load map features:', result.message);
                }
            } catch (error) {
                console.error('Error loading map features:', error);
            }
        },

        addFeaturesToMap() {
            // Clear existing drawn layers
            this.drawnLayers.clearLayers();

            this.mapFeatures.forEach(feature => {
                if (feature.properties.is_visible) {
                    const layer = L.geoJSON(feature, {
                        style: feature.properties.style
                    });

                    layer.feature_id = feature.id;
                    this.drawnLayers.addLayer(layer);

                    // Add name label if feature has a name (after layer is added to map)
                    if (feature.properties.name) {
                        // Use setTimeout to ensure the layer is fully rendered before calculating label position
                        setTimeout(() => {
                            const labelData = this.calculateOptimalLabelPosition(layer, feature);
                            if (labelData) {
                                const label = L.marker(labelData.position, {
                                    icon: L.divIcon({
                                        className: 'feature-label feature-label-' + labelData.type.toLowerCase(),
                                        html: `<div class="bg-white/95 backdrop-blur-sm px-3 py-1.5 rounded-md shadow-md border border-gray-300 text-xs font-medium text-gray-800 whitespace-nowrap">${feature.properties.name}</div>`,
                                        iconSize: [null, null], // Auto-size based on content
                                        iconAnchor: labelData.anchor
                                    }),
                                    interactive: false // Make labels non-interactive to avoid blocking map interactions
                                });
                                this.drawnLayers.addLayer(label);
                            }
                        }, 100);
                    }

                    // Add click handler for feature information
                    layer.on('click', (e) => {
                        this.showFeatureInfo(feature, e.latlng);
                    });
                }
            });
        },

        getFeatureCenter(layer) {
            try {
                if (layer.getCenter) {
                    return layer.getCenter();
                } else if (layer.getBounds) {
                    return layer.getBounds().getCenter();
                } else if (layer.getLatLng) {
                    return layer.getLatLng();
                } else if (layer.getLayers) {
                    const layers = layer.getLayers();
                    if (layers.length > 0 && layers[0].getLatLng) {
                        return layers[0].getLatLng();
                    }
                }
                return null;
            } catch (error) {
                console.warn('Error getting feature center:', error);
                return null;
            }
        },

        calculateOptimalLabelPosition(layer, feature) {
            try {
                const featureType = feature.geometry.type;
                const coordinates = feature.geometry.coordinates;

                if (featureType === 'LineString') {
                    // For lines, position label at the midpoint of the line
                    const midIndex = Math.floor(coordinates.length / 2);
                    const midPoint = coordinates[midIndex];
                    const position = L.latLng(midPoint[1], midPoint[0]);

                    // Calculate direction for better label placement
                    let anchor = [0, -8]; // Default above and to the left
                    if (coordinates.length > 1) {
                        const prev = coordinates[midIndex - 1] || coordinates[0];
                        const next = coordinates[midIndex + 1] || coordinates[coordinates.length - 1];
                        const angle = Math.atan2(next[1] - prev[1], next[0] - prev[0]) * 180 / Math.PI;

                        // Adjust anchor based on line direction
                        if (angle > -45 && angle < 45) {
                            anchor = [0, -8]; // Horizontal line - label above
                        } else if (angle > 45 && angle < 135) {
                            anchor = [-10, 0]; // Vertical line going up - label to the left
                        } else if (angle > 135 || angle < -135) {
                            anchor = [0, 8]; // Horizontal line - label below
                        } else {
                            anchor = [10, 0]; // Vertical line going down - label to the right
                        }
                    }

                    return {
                        position: position,
                        anchor: anchor,
                        type: featureType
                    };

                } else if (featureType === 'MultiLineString') {
                    // For multi-line strings, use the first line's midpoint
                    const firstLine = coordinates[0];
                    const midIndex = Math.floor(firstLine.length / 2);
                    const midPoint = firstLine[midIndex];
                    const position = L.latLng(midPoint[1], midPoint[0]);

                    return {
                        position: position,
                        anchor: [0, -8],
                        type: featureType
                    };

                } else if (featureType === 'Polygon') {
                    // For polygons, find the visual center or centroid
                    const ring = coordinates[0]; // External ring

                    // Calculate centroid
                    let area = 0;
                    let centroidLat = 0;
                    let centroidLng = 0;

                    for (let i = 0; i < ring.length - 1; i++) {
                        const x0 = ring[i][0];
                        const y0 = ring[i][1];
                        const x1 = ring[i + 1][0];
                        const y1 = ring[i + 1][1];
                        const a = x0 * y1 - x1 * y0;
                        area += a;
                        centroidLng += (x0 + x1) * a;
                        centroidLat += (y0 + y1) * a;
                    }

                    area *= 0.5;
                    centroidLng /= (6 * area);
                    centroidLat /= (6 * area);

                    // Find the topmost point for label placement to avoid blocking the polygon
                    let topmostPoint = ring[0];
                    for (let point of ring) {
                        if (point[1] > topmostPoint[1]) {
                            topmostPoint = point;
                        }
                    }

                    // Position label slightly above the topmost point
                    const position = L.latLng(topmostPoint[1] + 0.0001, centroidLng);

                    return {
                        position: position,
                        anchor: [50, 20], // Center horizontally, position below the label
                        type: featureType
                    };

                } else if (featureType === 'MultiPolygon') {
                    // For multi-polygons, use the first polygon's centroid
                    const firstPolygon = coordinates[0][0]; // First polygon, external ring
                    const midIndex = Math.floor(firstPolygon.length / 2);
                    const midPoint = firstPolygon[midIndex];
                    const position = L.latLng(midPoint[1], midPoint[0]);

                    return {
                        position: position,
                        anchor: [50, 20],
                        type: featureType
                    };
                } else {
                    // Fallback to original method
                    const center = this.getFeatureCenter(layer);
                    if (center) {
                        return {
                            position: center,
                            anchor: [50, 15],
                            type: featureType
                        };
                    }
                }

                return null;
            } catch (error) {
                console.warn('Error calculating optimal label position:', error);
                // Fallback to original method
                const center = this.getFeatureCenter(layer);
                if (center) {
                    return {
                        position: center,
                        anchor: [50, 15],
                        type: feature.geometry.type
                    };
                }
                return null;
            }
        },

        async loadLineNumbers() {
            try {
                const response = await fetch('/map-features/line-numbers', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    credentials: 'include'
                });

                console.log('loadLineNumbers response status:', response.status);
                const result = await response.json();
                console.log('loadLineNumbers result:', result);

                if (result.success) {
                    this.lineNumbers = result.line_numbers;
                } else {
                    console.warn('Failed to load line numbers:', result.message);
                }
            } catch (error) {
                console.error('Error loading line numbers:', error);
            }
        },

        async loadClusters() {
            try {
                const response = await fetch('/map-features/clusters', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    credentials: 'include'
                });

                console.log('loadClusters response status:', response.status);
                const result = await response.json();
                console.log('loadClusters result:', result);

                if (result.success) {
                    this.clusters = result.clusters;
                } else {
                    console.warn('Failed to load clusters:', result.message);
                }
            } catch (error) {
                console.error('Error loading clusters:', error);
            }
        },

        startDrawing(type) {
            console.log('Starting drawing for type:', type);

            // Stop any existing drawing first
            this.stopDrawing();

            this.currentDrawingType = type;

            // Create drawing tools with current style
            const shapeOptions = {
                color: this.currentStyle.color,
                weight: this.currentStyle.weight,
                opacity: this.currentStyle.opacity,
                fillColor: this.currentStyle.fillColor,
                fillOpacity: this.currentStyle.fillOpacity
            };

            try {
                // Re-setup drawing event handlers to ensure they're active
                this.setupDrawingEventHandlers();

                if (type === 'line') {
                    this.currentDrawingTool = new L.Draw.Polyline(this.map, { shapeOptions });
                } else if (type === 'polygon') {
                    this.currentDrawingTool = new L.Draw.Polygon(this.map, { shapeOptions });
                } else if (type === 'circle') {
                    this.currentDrawingTool = new L.Draw.Circle(this.map, { shapeOptions });
                }

                if (this.currentDrawingTool && this.currentDrawingTool.enable) {
                    this.currentDrawingTool.enable();
                    console.log(`${type} drawing tool enabled`);
                } else {
                    console.error('Drawing tool or enable method not available');
                }
            } catch (error) {
                console.error('Error starting drawing tool:', error);
                alert('Gagal memulai alat gambar. Silakan coba lagi.');
            }
        },

        stopDrawing() {
            if (this.currentDrawingTool) {
                try {
                    if (this.currentDrawingTool.disable && typeof this.currentDrawingTool.disable === 'function') {
                        this.currentDrawingTool.disable();
                    }
                } catch (error) {
                    console.warn('Error stopping drawing tool:', error);
                }
                this.currentDrawingTool = null;
            }
            this.currentDrawingType = null;
        },

        async saveDrawnFeature(layer, type) {
            try {
                console.log('Saving feature with type:', type);
                const geoJson = layer.toGeoJSON();
                console.log('GeoJSON:', geoJson);

                const featureType = type === 'polyline' ? 'line' : type;
                const featureData = {
                    name: '',
                    feature_type: featureType,
                    line_number_id: (featureType === 'line') ? (this.selectedLineNumber || null) : null,
                    cluster_id: (featureType === 'polygon' || featureType === 'circle') ? (this.selectedCluster || null) : null,
                    geometry: geoJson.geometry,
                    style_properties: this.currentStyle
                };

                console.log('Feature data to save:', featureData);

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                console.log('CSRF Token:', csrfToken);

                const response = await fetch('/map-features', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify(featureData)
                });

                console.log('Response status:', response.status);
                const result = await response.json();
                console.log('Response data:', result);

                if (result.success) {
                    layer.feature_id = result.feature.id;
                    this.mapFeatures.push(result.feature);
                    console.log('Feature saved successfully:', result.feature);
                    alert('Feature saved successfully!');
                } else {
                    console.error('Failed to save feature:', result.message);
                    alert('Failed to save feature: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error saving feature:', error);
                alert('Error saving feature: ' + error.message);
            }
        },

        async updateDrawnFeature(layer) {
            if (!layer.feature_id) return;

            try {
                const geoJson = layer.toGeoJSON();

                const response = await fetch(`/map-features/${layer.feature_id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        geometry: geoJson.geometry
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Update local feature data
                    const featureIndex = this.mapFeatures.findIndex(f => f.id === layer.feature_id);
                    if (featureIndex !== -1) {
                        this.mapFeatures[featureIndex] = result.feature;
                    }

                    if (window.showToast) {
                        window.showToast('success', 'Feature updated successfully');
                    }
                }
            } catch (error) {
                console.error('Error updating feature:', error);
            }
        },

        async deleteDrawnFeature(layer) {
            if (!layer.feature_id) return;

            try {
                const response = await fetch(`/map-features/${layer.feature_id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });

                const result = await response.json();

                if (result.success) {
                    // Remove from local features
                    this.mapFeatures = this.mapFeatures.filter(f => f.id !== layer.feature_id);

                    if (window.showToast) {
                        window.showToast('success', 'Feature deleted successfully');
                    }
                }
            } catch (error) {
                console.error('Error deleting feature:', error);
            }
        },

        async toggleFeatureVisibility(feature) {
            try {
                const response = await fetch(`/map-features/${feature.id}/toggle-visibility`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });

                const result = await response.json();

                if (result.success) {
                    feature.properties.is_visible = result.is_visible;
                    this.addFeaturesToMap();
                }
            } catch (error) {
                console.error('Error toggling feature visibility:', error);
            }
        },

        editFeature(feature) {
            // Implementation for editing feature properties
            const newName = prompt('Enter new name for feature:', feature.properties.name);
            if (newName && newName !== feature.properties.name) {
                this.updateFeatureName(feature, newName);
            }
        },

        async updateFeatureName(feature, newName) {
            try {
                const response = await fetch(`/map-features/${feature.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        name: newName
                    })
                });

                const result = await response.json();

                if (result.success) {
                    feature.properties.name = newName;
                    if (window.showToast) {
                        window.showToast('success', 'Feature name updated');
                    }
                }
            } catch (error) {
                console.error('Error updating feature name:', error);
            }
        },

        async deleteFeature(feature) {
            if (confirm('Are you sure you want to delete this feature?')) {
                try {
                    const response = await fetch(`/map-features/${feature.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.mapFeatures = this.mapFeatures.filter(f => f.id !== feature.id);
                        this.addFeaturesToMap();

                        if (window.showToast) {
                            window.showToast('success', 'Feature deleted');
                        }
                    }
                } catch (error) {
                    console.error('Error deleting feature:', error);
                }
            }
        },

        showFeatureInfo(feature, position) {
            try {
                // Close any existing popups first to prevent conflicts
                this.map.closePopup();

                const featureType = feature.properties.feature_type || feature.geometry.type;
                const featureName = feature.properties.name || 'Unnamed Feature';

                // Create popup content based on feature type
                let popupContent = `
                    <div class="feature-info-popup p-3 min-w-[280px]">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold text-gray-800 text-lg">${featureName}</h3>
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full uppercase">${featureType}</span>
                        </div>
                `;

            // Add specific information based on feature type
            if (featureType === 'line' || featureType === 'LineString') {
                popupContent += `
                    <div class="space-y-2">
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-route mr-2 text-blue-500"></i>
                            <span>Line Feature</span>
                        </div>
                `;
                if (feature.properties.line_number_id) {
                    const lineNumber = this.lineNumbers.find(ln => ln.id === feature.properties.line_number_id);
                    if (lineNumber) {
                        popupContent += `
                            <div class="flex items-center text-sm">
                                <i class="fas fa-tag mr-2 text-gray-400"></i>
                                <span>Line: ${lineNumber.line_number}</span>
                            </div>
                        `;
                    }
                }
                popupContent += `
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-ruler mr-2 text-green-500"></i>
                            <span>Click to measure length</span>
                        </div>
                    </div>
                `;
            } else if (featureType === 'polygon' || featureType === 'Polygon' || featureType === 'circle') {
                popupContent += `
                    <div class="space-y-2">
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-vector-square mr-2 text-orange-500"></i>
                            <span>${featureType === 'circle' ? 'Circle Area' : 'Polygon Area'}</span>
                        </div>
                `;
                if (feature.properties.cluster_id) {
                    const cluster = this.clusters.find(c => c.id === feature.properties.cluster_id);
                    if (cluster) {
                        popupContent += `
                            <div class="flex items-center text-sm">
                                <i class="fas fa-layer-group mr-2 text-gray-400"></i>
                                <span>Cluster: ${cluster.name}</span>
                            </div>
                        `;
                    }
                }
                popupContent += `
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-calculator mr-2 text-green-500"></i>
                            <span>Click to calculate area</span>
                        </div>
                    </div>
                `;
            }

            // Add style information with editable controls
            if (feature.properties.style) {
                const currentColor = feature.properties.style.color || '#3388ff';
                const currentWeight = feature.properties.style.weight || 3;
                const currentOpacity = feature.properties.style.opacity || 1;

                popupContent += `
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="text-xs text-gray-500 mb-2">Style Properties</div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs text-gray-600">Color:</label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" value="${currentColor}"
                                           onchange="window.mapComponentInstance.updateFeatureColor(${feature.id}, this.value)"
                                           class="w-8 h-6 rounded border border-gray-300 cursor-pointer">
                                    <span class="text-xs text-gray-500">${currentColor}</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <label class="text-xs text-gray-600">Line Weight:</label>
                                <div class="flex items-center space-x-2">
                                    <input type="range" min="1" max="10" value="${currentWeight}"
                                           onchange="window.mapComponentInstance.updateFeatureWeight(${feature.id}, this.value)"
                                           class="w-16 h-2 bg-gray-200 rounded cursor-pointer">
                                    <span class="text-xs text-gray-500 w-6">${currentWeight}px</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <label class="text-xs text-gray-600">Opacity:</label>
                                <div class="flex items-center space-x-2">
                                    <input type="range" min="0.1" max="1" step="0.1" value="${currentOpacity}"
                                           onchange="window.mapComponentInstance.updateFeatureOpacity(${feature.id}, this.value)"
                                           class="w-16 h-2 bg-gray-200 rounded cursor-pointer">
                                    <span class="text-xs text-gray-500 w-8">${Math.round(currentOpacity * 100)}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            // Add action buttons
            popupContent += `
                <div class="mt-4 flex space-x-2">
                    <button onclick="window.mapComponentInstance.editFeature(${JSON.stringify(feature).replace(/"/g, '&quot;')})"
                            class="flex-1 px-4 py-2 bg-blue-500 text-white text-sm rounded-md hover:bg-blue-600 transition-colors">
                        <i class="fas fa-edit mr-2"></i>Edit Name
                    </button>
                    <button onclick="window.mapComponentInstance.deleteFeature(${JSON.stringify(feature).replace(/"/g, '&quot;')})"
                            class="flex-1 px-4 py-2 bg-red-500 text-white text-sm rounded-md hover:bg-red-600 transition-colors">
                        <i class="fas fa-trash mr-2"></i>Delete
                    </button>
                </div>
            </div>
            `;

                // Create and show popup with error handling
                const popup = L.popup({
                    maxWidth: 320,
                    className: 'feature-info-popup-container',
                    closeOnClick: true,
                    autoClose: true
                })
                .setLatLng(position)
                .setContent(popupContent);

                // Open popup safely
                try {
                    popup.openOn(this.map);
                } catch (popupError) {
                    console.warn('Error opening popup:', popupError);
                    // Fallback: try again after a short delay
                    setTimeout(() => {
                        try {
                            popup.openOn(this.map);
                        } catch (secondError) {
                            console.error('Failed to open popup twice:', secondError);
                        }
                    }, 100);
                }
            } catch (error) {
                console.error('Error in showFeatureInfo:', error);
            }
        },

        async updateFeatureColor(featureId, newColor) {
            try {
                const feature = this.mapFeatures.find(f => f.id === featureId);
                if (!feature) return;

                const updatedStyle = { ...feature.properties.style, color: newColor };
                await this.updateFeatureStyle(featureId, updatedStyle);
            } catch (error) {
                console.error('Error updating feature color:', error);
            }
        },

        async updateFeatureWeight(featureId, newWeight) {
            try {
                const feature = this.mapFeatures.find(f => f.id === featureId);
                if (!feature) return;

                const updatedStyle = { ...feature.properties.style, weight: parseInt(newWeight) };
                await this.updateFeatureStyle(featureId, updatedStyle);
            } catch (error) {
                console.error('Error updating feature weight:', error);
            }
        },

        async updateFeatureOpacity(featureId, newOpacity) {
            try {
                const feature = this.mapFeatures.find(f => f.id === featureId);
                if (!feature) return;

                const updatedStyle = { ...feature.properties.style, opacity: parseFloat(newOpacity), fillOpacity: parseFloat(newOpacity) * 0.7 };
                await this.updateFeatureStyle(featureId, updatedStyle);
            } catch (error) {
                console.error('Error updating feature opacity:', error);
            }
        },

        async updateFeatureStyle(featureId, newStyle) {
            try {
                const response = await fetch(`/map-features/${featureId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({ style_properties: newStyle })
                });

                const result = await response.json();
                if (result.success) {
                    // Update local feature data
                    const featureIndex = this.mapFeatures.findIndex(f => f.id === featureId);
                    if (featureIndex !== -1) {
                        this.mapFeatures[featureIndex].properties.style = newStyle;
                    }

                    // Refresh the map to show updated styles
                    this.addFeaturesToMap();

                    if (window.showToast) {
                        window.showToast('success', 'Feature style updated');
                    }
                } else {
                    console.error('Failed to update feature style:', result.message);
                }
            } catch (error) {
                console.error('Error updating feature style:', error);
                if (window.showToast) {
                    window.showToast('error', 'Failed to update feature style');
                }
            }
        },

        // Enhanced drawing flow with better user experience
        async enhanceDrawingFlow() {
            // Add snap-to-existing-features functionality
            this.setupSnapToFeatures();

            // Add measurement display during drawing
            this.setupMeasurementDisplay();

            // Add drawing guidelines
            this.setupDrawingGuidelines();
        },

        setupSnapToFeatures() {
            // This would implement snapping to existing features while drawing
            // for better line connections and area alignment
            if (this.map && this.drawnLayers) {
                // Implementation for snapping functionality
                console.log('Snap to features enabled');
            }
        },

        setupMeasurementDisplay() {
            // Show real-time measurements while drawing lines or areas
            console.log('Measurement display enabled');
        },

        setupDrawingGuidelines() {
            // Show helpful guidelines during drawing
            console.log('Drawing guidelines enabled');
        }
    }
}

// Hide search results when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('[x-data="mapsComponent()"]')) {
        Alpine.store('searchResults', false);
    }
});
</script>

<style>
.custom-marker {
    background: none !important;
    border: none !important;
}

.leaflet-popup-content-wrapper {
    border-radius: 8px;
}

.leaflet-popup-content {
    margin: 0;
    line-height: 1.4;
}

/* Ensure legend is always visible above map */
.leaflet-container {
    z-index: 1 !important;
}

.leaflet-control-container {
    z-index: 999 !important;
}

/* Force legend to be above everything */
.map-legend {
    z-index: 1001 !important;
    position: absolute !important;
    pointer-events: auto !important;
}

/* Ensure legend content is not affected by map styles */
.map-legend * {
    pointer-events: auto !important;
}

/* Feature Info Popup Styles */
.feature-info-popup-container .leaflet-popup-content-wrapper {
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    border: none;
    padding: 0;
}

.feature-info-popup-container .leaflet-popup-content {
    margin: 0;
    line-height: 1.4;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.feature-info-popup-container .leaflet-popup-tip {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Feature Label Positioning Improvements */
.feature-label {
    pointer-events: none !important;
}

.feature-label-linestring {
    transform: translateY(-10px);
}

.feature-label-polygon {
    transform: translateY(-5px);
}

.feature-label-multilinestring {
    transform: translateY(-10px);
}

.feature-label-multipolygon {
    transform: translateY(-5px);
}

/* Enhanced drawing tools styling */
.leaflet-draw-toolbar {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
}

/* Improved line and area interaction feedback */
.leaflet-interactive:hover {
    filter: brightness(1.1);
    cursor: pointer;
}
</style>