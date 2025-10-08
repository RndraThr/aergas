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
                             class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors">
                            <div class="font-medium text-gray-900" x-text="customer.reff_id + ' - ' + (customer.title || 'N/A')"></div>
                            <div class="text-xs text-gray-500 mt-1">
                                <span class="inline-block px-2 py-1 rounded-full text-xs"
                                      :class="customer.status === 'lanjut' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                      x-text="customer.status"></span>
                                <span class="ml-2" x-text="customer.progress || 'validasi'"></span>
                            </div>
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

                <div class="p-3 space-y-1 overflow-y-auto flex-1">
                <!-- Status Legend -->
                <div>
                    <h5 class="font-medium text-xs text-gray-700 mb-2 uppercase tracking-wide">Customer Status</h5>
                    <div class="text-xs">
                        <div class="flex items-center justify-between cursor-pointer hover:bg-gray-50 p-1 rounded transition-colors" @click="toggleLegendStatus('pending')">
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-blue-500 mr-2 flex-shrink-0"></div>
                                <span class="text-gray-700" :class="legendFilters.hiddenStatuses.includes('pending') ? 'line-through opacity-50' : ''">Pending</span>
                            </div>
                            <span class="text-blue-600 font-medium" x-text="(mapsStats.status_counts && mapsStats.status_counts.pending) || 0">0</span>
                        </div>
                        <div class="flex items-center justify-between cursor-pointer hover:bg-gray-50 p-1 rounded transition-colors" @click="toggleLegendStatus('lanjut')">
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-green-500 mr-2 flex-shrink-0"></div>
                                <span class="text-gray-700" :class="legendFilters.hiddenStatuses.includes('lanjut') ? 'line-through opacity-50' : ''">Lanjut</span>
                            </div>
                            <span class="text-green-600 font-medium" x-text="(mapsStats.status_counts && mapsStats.status_counts.lanjut) || 0">0</span>
                        </div>
                        <div class="flex items-center justify-between cursor-pointer hover:bg-gray-50 p-1 rounded transition-colors" @click="toggleLegendStatus('in_progress')">
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-yellow-500 mr-2 flex-shrink-0"></div>
                                <span class="text-gray-700" :class="legendFilters.hiddenStatuses.includes('in_progress') ? 'line-through opacity-50' : ''">In Progress</span>
                            </div>
                            <span class="text-yellow-600 font-medium" x-text="(mapsStats.status_counts && mapsStats.status_counts.in_progress) || 0">0</span>
                        </div>
                        <div class="flex items-center justify-between cursor-pointer hover:bg-gray-50 p-1 rounded transition-colors" @click="toggleLegendStatus('batal')">
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-red-500 mr-2 flex-shrink-0"></div>
                                <span class="text-gray-700" :class="legendFilters.hiddenStatuses.includes('batal') ? 'line-through opacity-50' : ''">Batal</span>
                            </div>
                            <span class="text-red-600 font-medium" x-text="mapsStats.batal || 0">0</span>
                        </div>
                    </div>
                </div>

                <!-- Progress Icons Legend -->
                <div class="pt-2 border-t border-gray-200">
                    <h5 class="font-medium text-xs text-gray-700 mb-2 uppercase tracking-wide">Progress Icons</h5>
                    <div class="text-xs">
                        <div class="flex items-center cursor-pointer hover:bg-gray-50 p-1 rounded transition-colors" @click="toggleLegendProgress('validasi')">
                            <div class="w-5 h-5 bg-purple-500 rounded-full mr-2 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-user-check text-white text-[8px]"></i>
                            </div>
                            <span class="text-gray-700" :class="legendFilters.hiddenProgress.includes('validasi') ? 'line-through opacity-50' : ''">Validasi</span>
                        </div>
                        <div class="flex items-center cursor-pointer hover:bg-gray-50 p-1 rounded transition-colors" @click="toggleLegendProgress('sk')">
                            <div class="w-5 h-5 bg-orange-500 rounded-full mr-2 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-wrench text-white text-[8px]"></i>
                            </div>
                            <span class="text-gray-700" :class="legendFilters.hiddenProgress.includes('sk') ? 'line-through opacity-50' : ''">SK (Sambungan Kompor)</span>
                        </div>
                        <div class="flex items-center cursor-pointer hover:bg-gray-50 p-1 rounded transition-colors" @click="toggleLegendProgress('sr')">
                            <div class="w-5 h-5 bg-blue-500 rounded-full mr-2 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-home text-white text-[8px]"></i>
                            </div>
                            <span class="text-gray-700" :class="legendFilters.hiddenProgress.includes('sr') ? 'line-through opacity-50' : ''">SR (Sambungan Rumah)</span>
                        </div>
                        <div class="flex items-center cursor-pointer hover:bg-gray-50 p-1 rounded transition-colors" @click="toggleLegendProgress('gas_in')">
                            <div class="w-5 h-5 bg-red-500 rounded-full mr-2 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-fire text-white text-[8px]"></i>
                            </div>
                            <span class="text-gray-700" :class="legendFilters.hiddenProgress.includes('gas_in') ? 'line-through opacity-50' : ''">Gas In</span>
                        </div>
                        <div class="flex items-center cursor-pointer hover:bg-gray-50 p-1 rounded transition-colors" @click="toggleLegendProgress('done')">
                            <div class="w-5 h-5 bg-green-500 rounded-full mr-2 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-check-circle text-white text-[8px]"></i>
                            </div>
                            <span class="text-gray-700" :class="legendFilters.hiddenProgress.includes('done') ? 'line-through opacity-50' : ''">Done</span>
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

{{-- Leaflet MarkerCluster Plugin for Performance --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

{{-- Include Leaflet.draw plugin for drawing tools --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

<script>
function mapsComponent() {
    return {
        map: null,
        markers: [],
        markerClusterGroup: null, // For clustering performance
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
        legendFilters: {
            hiddenStatuses: [],
            hiddenProgress: []
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

                // Initialize Marker Cluster Group for better performance
                this.markerClusterGroup = L.markerClusterGroup({
                    maxClusterRadius: 80, // Increased radius = more aggressive clustering
                    spiderfyOnMaxZoom: false, // DISABLE spiderfy to prevent lag
                    showCoverageOnHover: false,
                    zoomToBoundsOnClick: true, // Click cluster = zoom in instead of spiderfy
                    disableClusteringAtZoom: 18, // Only show individual markers at max zoom
                    chunkedLoading: true,
                    chunkInterval: 200, // Larger chunks
                    chunkDelay: 50,
                    spiderfyDistanceMultiplier: 2, // Spread markers more when spiderfy happens
                    // Custom cluster icon with count
                    iconCreateFunction: (cluster) => {
                        const count = cluster.getChildCount();
                        let sizeClass = 'marker-cluster-small';

                        if (count > 100) {
                            sizeClass = 'marker-cluster-large';
                        } else if (count > 50) {
                            sizeClass = 'marker-cluster-medium';
                        }

                        return L.divIcon({
                            html: `<div><span>${count}</span></div>`,
                            className: 'marker-cluster ' + sizeClass,
                            iconSize: L.point(40, 40)
                        });
                    },
                    // Add popup to cluster showing info instead of spiderfy
                    clusterMouseoverPopup: true
                });

                this.map.addLayer(this.markerClusterGroup);

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

                console.log('Loaded data:', data); // Debug

                if (data.data && data.data.customers) {
                    // Filter customers with coordinates (data sekarang langsung lat/lng)
                    this.allCustomers = data.data.customers.filter(customer =>
                        customer.lat && customer.lng
                    );

                    console.log('All customers with coordinates:', this.allCustomers.length); // Debug

                    // Initially show all customers
                    this.customers = [...this.allCustomers];

                    // Load maps specific stats if available
                    if (data.data.maps_stats) {
                        this.mapsStats = { ...this.mapsStats, ...data.data.maps_stats };
                        console.log('Maps stats:', this.mapsStats); // Debug
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
            // Clear existing markers from cluster group
            this.markerClusterGroup.clearLayers();
            this.markers = [];

            console.log('Adding markers to map, customers count:', this.customers.length); // Debug

            // Add markers in batches for better performance
            const batchSize = 200;
            let index = 0;

            const addBatch = () => {
                const batch = this.customers.slice(index, index + batchSize);

                batch.forEach((customer) => {
                    // Data structure changed: customer.lat & customer.lng (not customer.coordinates)
                    if (customer.lat && customer.lng) {
                        // Apply legend filters
                        if (this.legendFilters.hiddenStatuses.includes(customer.status)) {
                            return; // Skip this marker
                        }
                        if (this.legendFilters.hiddenProgress.includes(customer.progress)) {
                            return; // Skip this marker
                        }

                        const marker = this.createMarker(customer);
                        this.markers.push(marker);
                        // Add to cluster group instead of directly to map
                        this.markerClusterGroup.addLayer(marker);
                    }
                });

                index += batchSize;

                // Continue with next batch if there are more customers
                if (index < this.customers.length) {
                    requestAnimationFrame(addBatch);
                } else {
                    console.log('Markers added to map:', this.markers.length); // Debug

                    // Fit map to show all markers if any exist
                    if (this.markers.length > 0) {
                        const bounds = this.markerClusterGroup.getBounds();
                        if (bounds.isValid()) {
                            this.map.fitBounds(bounds.pad(0.1));
                        }
                    }
                }
            };

            addBatch();
        },

        createMarker(customer) {
            const color = this.getStatusColor(customer.status);
            const icon = this.createCustomIcon(color, customer.progress);

            const marker = L.marker(
                [customer.lat, customer.lng],
                { icon: icon }
            );

            // Store customer ID for lazy loading
            marker.customerId = customer.id;

            // LAZY LOADING: Load detail only when marker is clicked
            marker.on('click', async () => {
                // Show loading popup first
                const loadingPopup = `
                    <div style="padding: 16px; text-align: center; width: 280px;">
                        <div style="width: 32px; height: 32px; border: 2px solid #F97316; border-top-color: transparent; border-radius: 50%; margin: 0 auto 8px; animation: spin 1s linear infinite;"></div>
                        <span style="font-size: 13px; color: #4B5563;">Loading detail...</span>
                    </div>
                `;
                marker.bindPopup(loadingPopup, {
                    maxWidth: 300,
                    minWidth: 280,
                    className: 'marker-popup-container',
                    autoPan: true,
                    keepInView: true
                }).openPopup();

                // Fetch full detail
                const detail = await this.loadMarkerDetail(customer.id);

                if (detail) {
                    const popupContent = this.createPopupContent(detail);
                    marker.bindPopup(popupContent, {
                        maxWidth: 300,
                        minWidth: 280,
                        className: 'marker-popup-container',
                        autoPan: true,
                        keepInView: true
                    }).openPopup();
                } else {
                    marker.bindPopup('<div style="padding: 16px; color: #DC2626; width: 280px;">Failed to load detail</div>', {
                        maxWidth: 300,
                        minWidth: 280,
                        className: 'marker-popup-container'
                    }).openPopup();
                }
            });

            return marker;
        },

        // LAZY LOADING: Fetch marker detail on demand
        async loadMarkerDetail(customerId) {
            try {
                // Check cache first
                if (!this.detailCache) {
                    this.detailCache = new Map();
                }

                if (this.detailCache.has(customerId)) {
                    return this.detailCache.get(customerId);
                }

                // Fetch from API
                const response = await fetch(`/dashboard/marker/${customerId}`);
                const result = await response.json();

                if (result.success) {
                    // Cache the result
                    this.detailCache.set(customerId, result.data);
                    return result.data;
                }

                return null;
            } catch (error) {
                console.error('Error loading marker detail:', error);
                return null;
            }
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

        createPopupContent(detail) {
            const progressBarWidth = detail.progress_percentage || 0;
            const progressColorBg = progressBarWidth >= 100 ? '#10B981' :
                                progressBarWidth >= 75 ? '#3B82F6' :
                                progressBarWidth >= 50 ? '#F59E0B' : '#9CA3AF';

            // Show keterangan only for batal status
            const keteranganHtml = detail.status === 'batal' ? `
                <div style="margin-top: 8px; padding: 8px; background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 4px;">
                    <div style="font-size: 11px; font-weight: 600; color: #B91C1C; margin-bottom: 4px;">
                        <i class="fas fa-info-circle" style="margin-right: 4px;"></i>Alasan Batal:
                    </div>
                    <div style="font-size: 11px; color: #DC2626; word-break: break-word;">${detail.keterangan || 'Tanpa keterangan'}</div>
                </div>
            ` : '';

            return `
                <div style="padding: 8px; width: 280px; max-width: 280px; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                    <div style="font-weight: 700; color: #111827; margin-bottom: 4px; font-size: 14px; word-break: break-word;">${detail.reff_id}</div>
                    <div style="font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 8px; word-break: break-word;">${detail.title}</div>

                    <div style="font-size: 11px; color: #4B5563; margin-bottom: 4px; word-break: break-word;">
                        <i class="fas fa-map-marker-alt" style="margin-right: 4px;"></i>
                        ${detail.alamat}
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 11px; margin-bottom: 8px;">
                        <span style="color: #6B7280;">Status:</span>
                        <span style="padding: 4px 8px; border-radius: 4px; color: white; background-color: ${this.getStatusColor(detail.status)}; font-size: 10px;">
                            ${this.getStatusLabel(detail.status)}
                        </span>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 11px; margin-bottom: 8px;">
                        <span style="color: #6B7280;">Progress:</span>
                        <span style="font-weight: 500;">${detail.progress_status.toUpperCase()}</span>
                    </div>

                    <div style="margin-bottom: 8px;">
                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: #6B7280; margin-bottom: 4px;">
                            <span>Progress</span>
                            <span>${Math.round(progressBarWidth)}%</span>
                        </div>
                        <div style="width: 100%; background-color: #E5E7EB; border-radius: 9999px; height: 8px;">
                            <div style="background-color: ${progressColorBg}; height: 8px; border-radius: 9999px; width: ${progressBarWidth}%;"></div>
                        </div>
                    </div>

                    <div style="font-size: 11px; color: #6B7280;">
                        Tgl Registrasi: ${detail.tanggal_registrasi || '-'}
                    </div>

                    ${keteranganHtml}

                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #E5E7EB;">
                        <a href="/customers/${detail.id}"
                           style="font-size: 11px; color: #F97316; font-weight: 500; text-decoration: none;">
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
                // Reset to all customers when search is cleared
                this.customers = [...this.allCustomers];
                this.addMarkersToMap();
                this.refreshMapFeatures();
                this.updateLegendCounts();
                return;
            }

            const term = this.searchTerm.toLowerCase();
            const filtered = this.allCustomers.filter(customer =>
                customer.reff_id.toLowerCase().includes(term) ||
                (customer.title && customer.title.toLowerCase().includes(term))
            );

            // Update search results for dropdown (limit to 10)
            this.searchResults = filtered.slice(0, 10);
            this.showSearchResults = true;

            // Filter markers on map (show all matching results)
            this.customers = filtered;
            this.addMarkersToMap();
            this.refreshMapFeatures();
            this.updateLegendCounts();

            // Auto-fit map to show filtered markers
            if (this.markers.length > 0) {
                const bounds = this.markerClusterGroup.getBounds();
                if (bounds.isValid()) {
                    this.map.fitBounds(bounds.pad(0.1));
                }
            }
        },

        selectCustomer(customer) {
            // Filter to show only selected customer
            this.customers = [customer];
            this.addMarkersToMap();
            this.refreshMapFeatures();
            this.updateLegendCounts();

            // Zoom to customer location
            if (customer.lat && customer.lng) {
                this.map.setView([customer.lat, customer.lng], 16);

                // Find and open the marker popup
                setTimeout(() => {
                    const marker = this.markers.find(m => {
                        const pos = m.getLatLng();
                        return Math.abs(pos.lat - customer.lat) < 0.0001 &&
                               Math.abs(pos.lng - customer.lng) < 0.0001;
                    });

                    if (marker) {
                        marker.fireEvent('click');
                    }
                }, 100);
            }

            // Update search term to show selected customer
            this.searchTerm = customer.reff_id + ' - ' + (customer.title || 'N/A');
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
                filtered = filtered.filter(customer => customer.progress === this.activeFilters.progress);
            }

            // Note: Kelurahan & Padukuhan filters removed since we don't load that data initially
            // User can still search by reff_id

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
                    <div style="padding: 12px; width: 300px; max-width: 300px; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; gap: 8px;">
                            <h3 style="font-weight: 600; color: #1F2937; font-size: 16px; margin: 0; word-break: break-word; flex: 1;">${featureName}</h3>
                            <span style="padding: 4px 8px; background-color: #DBEAFE; color: #1E40AF; font-size: 10px; border-radius: 9999px; text-transform: uppercase; white-space: nowrap;">${featureType}</span>
                        </div>
                `;

            // Add specific information based on feature type
            if (featureType === 'line' || featureType === 'LineString') {
                popupContent += `
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; align-items: center; font-size: 13px; color: #4B5563;">
                            <i class="fas fa-route" style="margin-right: 8px; color: #3B82F6;"></i>
                            <span>Line Feature</span>
                        </div>
                `;
                if (feature.properties.line_number_id) {
                    const lineNumber = this.lineNumbers.find(ln => ln.id === feature.properties.line_number_id);
                    if (lineNumber) {
                        popupContent += `
                            <div style="display: flex; align-items: center; font-size: 13px;">
                                <i class="fas fa-tag" style="margin-right: 8px; color: #9CA3AF;"></i>
                                <span>Line: ${lineNumber.line_number}</span>
                            </div>
                        `;
                    }
                }
                popupContent += `
                        <div style="display: flex; align-items: center; font-size: 13px; color: #4B5563;">
                            <i class="fas fa-ruler" style="margin-right: 8px; color: #10B981;"></i>
                            <span>Click to measure length</span>
                        </div>
                    </div>
                `;
            } else if (featureType === 'polygon' || featureType === 'Polygon' || featureType === 'circle') {
                popupContent += `
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; align-items: center; font-size: 13px; color: #4B5563;">
                            <i class="fas fa-vector-square" style="margin-right: 8px; color: #F97316;"></i>
                            <span>${featureType === 'circle' ? 'Circle Area' : 'Polygon Area'}</span>
                        </div>
                `;
                if (feature.properties.cluster_id) {
                    const cluster = this.clusters.find(c => c.id === feature.properties.cluster_id);
                    if (cluster) {
                        popupContent += `
                            <div style="display: flex; align-items: center; font-size: 13px;">
                                <i class="fas fa-layer-group" style="margin-right: 8px; color: #9CA3AF;"></i>
                                <span>Cluster: ${cluster.name}</span>
                            </div>
                        `;
                    }
                }
                popupContent += `
                        <div style="display: flex; align-items: center; font-size: 13px; color: #4B5563;">
                            <i class="fas fa-calculator" style="margin-right: 8px; color: #10B981;"></i>
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
                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #E5E7EB;">
                        <div style="font-size: 11px; color: #6B7280; margin-bottom: 8px;">Style Properties</div>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <label style="font-size: 11px; color: #4B5563;">Color:</label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="color" value="${currentColor}"
                                           onchange="window.mapComponentInstance.updateFeatureColor(${feature.id}, this.value)"
                                           style="width: 32px; height: 24px; border-radius: 4px; border: 1px solid #D1D5DB; cursor: pointer;">
                                    <span style="font-size: 11px; color: #6B7280; width: 60px;">${currentColor}</span>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <label style="font-size: 11px; color: #4B5563;">Line Weight:</label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="range" min="1" max="10" value="${currentWeight}"
                                           onchange="window.mapComponentInstance.updateFeatureWeight(${feature.id}, this.value)"
                                           style="width: 80px; height: 8px; cursor: pointer;">
                                    <span style="font-size: 11px; color: #6B7280; width: 30px;">${currentWeight}px</span>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <label style="font-size: 11px; color: #4B5563;">Opacity:</label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="range" min="0.1" max="1" step="0.1" value="${currentOpacity}"
                                           onchange="window.mapComponentInstance.updateFeatureOpacity(${feature.id}, this.value)"
                                           style="width: 80px; height: 8px; cursor: pointer;">
                                    <span style="font-size: 11px; color: #6B7280; width: 30px;">${Math.round(currentOpacity * 100)}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            // Add action buttons
            popupContent += `
                <div style="margin-top: 16px; display: flex; gap: 8px;">
                    <button onclick="window.mapComponentInstance.editFeature(${JSON.stringify(feature).replace(/"/g, '&quot;')})"
                            style="flex: 1; padding: 8px 12px; background-color: #3B82F6; color: white; font-size: 12px; border-radius: 6px; border: none; cursor: pointer; transition: background-color 0.2s;"
                            onmouseover="this.style.backgroundColor='#2563EB'" onmouseout="this.style.backgroundColor='#3B82F6'">
                        <i class="fas fa-edit" style="margin-right: 6px;"></i>Edit Name
                    </button>
                    <button onclick="window.mapComponentInstance.deleteFeature(${JSON.stringify(feature).replace(/"/g, '&quot;')})"
                            style="flex: 1; padding: 8px 12px; background-color: #EF4444; color: white; font-size: 12px; border-radius: 6px; border: none; cursor: pointer; transition: background-color 0.2s;"
                            onmouseover="this.style.backgroundColor='#DC2626'" onmouseout="this.style.backgroundColor='#EF4444'">
                        <i class="fas fa-trash" style="margin-right: 6px;"></i>Delete
                    </button>
                </div>
            </div>
            `;

                // Create and show popup with error handling
                const popup = L.popup({
                    maxWidth: 320,
                    minWidth: 300,
                    className: 'feature-info-popup-container',
                    closeOnClick: true,
                    autoClose: true,
                    autoPan: true,
                    keepInView: true
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
        },

        toggleLegendStatus(status) {
            const index = this.legendFilters.hiddenStatuses.indexOf(status);
            if (index > -1) {
                // Remove from hidden list (show markers)
                this.legendFilters.hiddenStatuses.splice(index, 1);
            } else {
                // Add to hidden list (hide markers)
                this.legendFilters.hiddenStatuses.push(status);
            }
            // Re-render markers with new filter
            this.addMarkersToMap();
        },

        toggleLegendProgress(progress) {
            const index = this.legendFilters.hiddenProgress.indexOf(progress);
            if (index > -1) {
                // Remove from hidden list (show markers)
                this.legendFilters.hiddenProgress.splice(index, 1);
            } else {
                // Add to hidden list (hide markers)
                this.legendFilters.hiddenProgress.push(progress);
            }
            // Re-render markers with new filter
            this.addMarkersToMap();
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
    max-width: 320px !important;
    box-sizing: border-box !important;
}

.leaflet-popup-content {
    margin: 0;
    line-height: 1.4;
    overflow-wrap: break-word;
    word-break: break-word;
    box-sizing: border-box;
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

/* Marker Popup Styles */
.marker-popup-container .leaflet-popup-content-wrapper {
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    border: none;
    padding: 0;
    max-width: 300px !important;
    min-width: 280px !important;
    box-sizing: border-box !important;
}

.marker-popup-container .leaflet-popup-content {
    margin: 0;
    line-height: 1.4;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    overflow-wrap: break-word;
    word-break: break-word;
    box-sizing: border-box;
}

.marker-popup-container .leaflet-popup-tip {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Feature Info Popup Styles */
.feature-info-popup-container .leaflet-popup-content-wrapper {
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    border: none;
    padding: 0;
    max-width: 320px !important;
    min-width: 300px !important;
    box-sizing: border-box !important;
}

.feature-info-popup-container .leaflet-popup-content {
    margin: 0;
    line-height: 1.4;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    overflow-wrap: break-word;
    word-break: break-word;
    box-sizing: border-box;
}

.feature-info-popup-container .leaflet-popup-tip {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Spinner animation for loading popup */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
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

/* Marker Cluster Styles for Performance */
.marker-cluster-small {
    background-color: rgba(59, 130, 246, 0.6);
}
.marker-cluster-small div {
    background-color: rgba(59, 130, 246, 0.8);
}

.marker-cluster-medium {
    background-color: rgba(245, 158, 11, 0.6);
}
.marker-cluster-medium div {
    background-color: rgba(245, 158, 11, 0.8);
}

.marker-cluster-large {
    background-color: rgba(239, 68, 68, 0.6);
}
.marker-cluster-large div {
    background-color: rgba(239, 68, 68, 0.8);
}

.marker-cluster {
    background-clip: padding-box;
    border-radius: 20px;
}
.marker-cluster div {
    width: 30px;
    height: 30px;
    margin-left: 5px;
    margin-top: 5px;
    text-align: center;
    border-radius: 15px;
    font: 12px "Helvetica Neue", Arial, Helvetica, sans-serif;
}
.marker-cluster span {
    line-height: 30px;
    color: white;
    font-weight: bold;
}
</style>