{{-- Advanced Maps View Component with Integrated Stats --}}
<div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100" x-data="mapsComponent()" x-init="initMap()">
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

        async initMap() {
            try {
                // Initialize the map centered on Yogyakarta
                this.map = L.map('aergas-map').setView([-7.7956, 110.3695], 11);

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(this.map);

                // Load customer data
                await this.loadCustomers();

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
</style>