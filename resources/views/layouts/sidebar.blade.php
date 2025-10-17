<div class="flex">
    <!-- Sidebar backdrop -->
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden" x-cloak></div>

    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform lg:translate-x-0 lg:static lg:inset-0 transition-transform duration-300 ease-in-out border-r border-gray-200 flex flex-col"
         :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
         x-cloak>

        <!-- Logo -->
        <div 
            x-data="{ logos: [
                '{{ asset('assets/CGP5.png') }}',
                '{{ asset('assets/AERGAS_PNG.png') }}',
            ], current: 0 }"
            x-init="setInterval(() => { current = (current + 1) % logos.length }, 3000)" 
            class="flex items-center justify-between h-[4.8rem] px-6 bg-white border-b border-gray-200 flex-shrink-0"
        >
            <!-- Carousel Logo -->
            <div class="flex items-center justify-center flex-1">
                <template x-for="(logo, index) in logos" :key="index">
                    <img 
                        :src="logo" 
                        alt="Logo Carousel" 
                        class="h-12 w-auto transition-opacity duration-700"
                        :class="{ 'opacity-100': index === current, 'opacity-0 absolute': index !== current }"
                    >
                </template>
            </div>

            <!-- Tombol Tutup (untuk mobile) -->
            <button @click="sidebarOpen = false"
                    class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- User Info -->
        <div class="p-6 bg-gradient-to-br from-gray-50 to-white border-b border-gray-200 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-gradient-to-br from-aergas-navy to-aergas-orange rounded-full flex items-center justify-center text-white font-medium shadow-md">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</div>
                    <div class="flex flex-wrap gap-1 mt-1">
                        @php
                            $userRoles = auth()->user()->getAllActiveRoles();
                        @endphp
                        @foreach($userRoles as $role)
                            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
                                {{ ucfirst(str_replace('_', ' ', $role)) }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-6 overflow-y-auto sidebar-scroll">
            <div class="space-y-1">
                <!-- Dashboard with Submenu -->
                @if(auth()->user()->hasAnyRole(['admin', 'cgp', 'tracer', 'super_admin', 'jalur']))
                @php
                    $isDashboardActive = request()->routeIs('dashboard') || request()->routeIs('jalur.dashboard');
                @endphp
                <div x-data="{ dashboardOpen: {{ $isDashboardActive ? 'true' : 'false' }} }" class="space-y-1">
                    <button @click="dashboardOpen = !dashboardOpen"
                            class="flex items-center w-full px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ $isDashboardActive ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                        <i class="fas fa-chart-pie w-5 mr-3 text-lg {{ $isDashboardActive ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                        <span class="flex-1 text-left">Dashboard</span>
                        <svg class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-180': dashboardOpen }"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Submenu -->
                    <div x-show="dashboardOpen"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-1"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 transform translate-y-0"
                         x-transition:leave-end="opacity-0 transform -translate-y-1"
                         class="space-y-1 ml-6 mt-1">
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('dashboard') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-tachometer-alt w-5 mr-3 text-sm"></i>
                            Dashboard Utama
                        </a>
                        @if(auth()->user()->hasAnyRole(['admin', 'super_admin', 'jalur']))
                        <a href="{{ route('jalur.dashboard') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('jalur.dashboard') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-chart-line w-5 mr-3 text-sm"></i>
                            Dashboard Jalur
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Data Calon Pelanggan -->
                @if(auth()->user()->hasAnyRole(['admin','sk','sr','gas_in','cgp','tracer','super_admin']))
                    <a href="{{ route('customers.index') }}"
                    class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('customers.*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                        <i class="fas fa-users w-5 mr-3 text-lg {{ request()->routeIs('customers.*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                        Data Calon Pelanggan
                        @if(request()->routeIs('customers.*'))
                            <div class="ml-auto"><div class="w-2 h-2 bg-aergas-orange rounded-full"></div></div>
                        @endif
                    </a>
                @endif

                <!-- Divider -->
                @if(auth()->user()->hasAnyRole(['admin','sk','sr','gas_in','super_admin']))
                <div class="border-t border-gray-200 my-4"></div>
                <div class="px-3 mb-2">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">MODULES</span>
                </div>
                @endif

                @if(auth()->user()->hasAnyRole(['sk', 'admin', 'super_admin']))
                <!-- SK Data -->
                <a href="{{ route('sk.index') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('sk.*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-fire w-5 mr-3 text-lg {{ request()->routeIs('sk.*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                    SK Form
                    @if(request()->routeIs('sk.*'))
                        <div class="ml-auto">
                            <div class="w-2 h-2 bg-aergas-orange rounded-full"></div>
                        </div>
                    @endif
                </a>
                @endif

                @if(auth()->user()->hasAnyRole(['sr', 'admin', 'super_admin']))
                <!-- SR Data -->
                <a href="{{ route('sr.index') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('sr.*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-house-user w-5 mr-3 text-lg {{ request()->routeIs('sr.*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                    SR Form
                    @if(request()->routeIs('sr.*'))
                        <div class="ml-auto">
                            <div class="w-2 h-2 bg-aergas-orange rounded-full"></div>
                        </div>
                    @endif
                </a>
                @endif

                @if(auth()->user()->hasAnyRole(['gas_in', 'admin', 'super_admin']))
                <!-- Gas In Data -->
                <a href="{{ route('gas-in.index') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('gas-in.*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-gas-pump w-5 mr-3 text-lg {{ request()->routeIs('gas-in.*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                    Gas In Form
                    @if(request()->routeIs('gas-in.*'))
                        <div class="ml-auto">
                            <div class="w-2 h-2 bg-aergas-orange rounded-full"></div>
                        </div>
                    @endif
                </a>
                @endif

                @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                <!-- Jalur Management with Submenu -->
                @php
                    $isJalurActive = request()->routeIs('jalur.*') && !request()->routeIs('jalur.reports*') && !request()->routeIs('jalur.dashboard');
                @endphp
                <div x-data="{ jalurOpen: {{ $isJalurActive ? 'true' : 'false' }} }" class="space-y-1">
                    <button @click="jalurOpen = !jalurOpen"
                            class="flex items-center w-full px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ $isJalurActive ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                        <i class="fas fa-road w-5 mr-3 text-lg {{ $isJalurActive ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                        <span class="flex-1 text-left">Jalur Management</span>
                        <svg class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-180': jalurOpen }"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Submenu -->
                    <div x-show="jalurOpen"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-1"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 transform translate-y-0"
                         x-transition:leave-end="opacity-0 transform -translate-y-1"
                         class="space-y-1 ml-6 mt-1">
                        <a href="{{ route('jalur.clusters.index') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('jalur.clusters.*') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-layer-group w-5 mr-3 text-sm"></i>
                            Clusters
                        </a>
                        <a href="{{ route('jalur.line-numbers.index') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('jalur.line-numbers.*') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-list-ol w-5 mr-3 text-sm"></i>
                            Line Numbers
                        </a>
                        <a href="{{ route('jalur.lowering.index') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('jalur.lowering.*') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-arrow-down w-5 mr-3 text-sm"></i>
                            Lowering Data
                        </a>
                        <a href="{{ route('jalur.joint.index') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('jalur.joint.*') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-link w-5 mr-3 text-sm"></i>
                            Joint Data
                        </a>
                        <a href="{{ route('jalur.joint-numbers.index') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('jalur.joint-numbers.*') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-hashtag w-5 mr-3 text-sm"></i>
                            Joint Numbers
                        </a>
                        @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                        <a href="{{ route('jalur.fitting-types.index') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('jalur.fitting-types.*') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-cogs w-5 mr-3 text-sm"></i>
                            Fitting Types
                        </a>
                        @endif
                    </div>
                </div>

@elseif (auth()->user()->hasAnyRole(['jalur']))
                <!-- Jalur Management with Submenu for Jalur-only users -->
                @php
                    $isJalurActiveForJalurRole = request()->routeIs('jalur.*') && !request()->routeIs('jalur.reports*') && !request()->routeIs('jalur.dashboard');
                @endphp
                <div x-data="{ jalurOpen: {{ $isJalurActiveForJalurRole ? 'true' : 'false' }} }" class="space-y-1">
                    <button @click="jalurOpen = !jalurOpen"
                            class="flex items-center w-full px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ $isJalurActiveForJalurRole ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                        <i class="fas fa-road w-5 mr-3 text-lg {{ $isJalurActiveForJalurRole ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                        <span class="flex-1 text-left">Jalur Management</span>
                        <svg class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-180': jalurOpen }"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Submenu -->
                    <div x-show="jalurOpen"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-1"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 transform translate-y-0"
                         x-transition:leave-end="opacity-0 transform -translate-y-1"
                         class="space-y-1 ml-6 mt-1">
                        <a href="{{ route('jalur.clusters.index') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('jalur.clusters.*') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-layer-group w-5 mr-3 text-sm"></i>
                            Clusters
                        </a>
                        <a href="{{ route('jalur.line-numbers.index') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('jalur.line-numbers.*') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-list-ol w-5 mr-3 text-sm"></i>
                            Line Numbers
                        </a>
                        <a href="{{ route('jalur.lowering.index') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('jalur.lowering.*') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-arrow-down w-5 mr-3 text-sm"></i>
                            Lowering Data
                        </a>
                        <a href="{{ route('jalur.joint.index') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('jalur.joint.*') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-link w-5 mr-3 text-sm"></i>
                            Joint Data
                        </a>
                        <a href="{{ route('jalur.joint-numbers.index') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('jalur.joint-numbers.*') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-hashtag w-5 mr-3 text-sm"></i>
                            Joint Numbers
                        </a>
                    </div>
                </div>

                <!-- Reports Menu for Jalur Role -->
                <a href="{{ route('jalur.reports') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('jalur.reports*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-chart-bar w-5 mr-3 text-lg {{ request()->routeIs('jalur.reports*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                    Laporan Jalur
                    @if(request()->routeIs('jalur.reports*'))
                        <div class="ml-auto">
                            <div class="w-2 h-2 bg-aergas-orange rounded-full"></div>
                        </div>
                    @endif
                </a>
                @endif

                @if(auth()->user()->hasAnyRole(['admin', 'cgp', 'tracer', 'super_admin']))
                <!-- Divider -->
                <div class="border-t border-gray-200 my-4"></div>
                <div class="px-3 mb-2">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">WORKFLOW</span>
                </div>
                @endif

                {{-- @if(auth()->user()->hasAnyRole(['admin']) && !auth()->user()->hasAnyRole(['tracer', 'super_admin']))
                <!-- Admin-only users see single Photo Review for tracer function -->
                <a href="{{ route('approvals.tracer.index') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('approvals.tracer.*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-search mr-3 text-lg {{ request()->routeIs('approvals.tracer.*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                    Photo Review
                    @php
                        $pendingTracer = \App\Models\PhotoApproval::where('photo_status', 'tracer_pending')->count();
                    @endphp
                    @if($pendingTracer > 0)
                        <span class="ml-auto bg-blue-500 text-white text-xs px-2 py-1 rounded-full">{{ $pendingTracer }}</span>
                    @endif
                </a>
                @endif

                @if(auth()->user()->hasAnyRole(['cgp']) && !auth()->user()->hasAnyRole(['admin', 'super_admin']))
                <!-- Tracer-only users see single Photo Review for CGP function -->
                <a href="{{ route('approvals.cgp.index') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('approvals.cgp.*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-clipboard-check mr-3 text-lg {{ request()->routeIs('approvals.cgp.*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                    Photo Review
                    @php
                        $pendingCgp = \App\Models\PhotoApproval::where('photo_status', 'cgp_pending')->count();
                    @endphp
                    @if($pendingCgp > 0)
                        <span class="ml-auto bg-green-500 text-white text-xs px-2 py-1 rounded-full">{{ $pendingCgp }}</span>
                    @endif
                </a>
                @endif --}}

                {{-- Tracer Review with Submenu: TRACER, ADMIN & SUPER ADMIN --}}
                @if(auth()->user()->hasAnyRole(['tracer','admin','super_admin']))
                <div x-data="{ tracerOpen: {{ request()->routeIs('approvals.tracer.*') ? 'true' : 'false' }} }" class="space-y-1">
                    <button @click="tracerOpen = !tracerOpen"
                            class="flex items-center w-full px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('approvals.tracer.*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                        <i class="fas fa-search w-5 mr-3 text-lg {{ request()->routeIs('approvals.tracer.*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                        <span class="flex-1 text-left">Tracer Review</span>
                        @php
                            $pendingTracer = \App\Models\PhotoApproval::where('photo_status', 'tracer_pending')->count();
                        @endphp
                        @if($pendingTracer > 0)
                            <span class="bg-blue-500 text-white text-xs px-2 py-1 rounded-full mr-2">{{ $pendingTracer }}</span>
                        @endif
                        <svg class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-180': tracerOpen }"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Submenu -->
                    <div x-show="tracerOpen"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-1"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 transform translate-y-0"
                         x-transition:leave-end="opacity-0 transform -translate-y-1"
                         class="space-y-1 ml-6 mt-1">
                        <a href="{{ route('approvals.tracer.customers') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('approvals.tracer.customers') || request()->routeIs('approvals.tracer.customer-photos') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-users w-5 mr-3 text-sm"></i>
                            Calon Pelanggan (SK, SR, Gas In)
                        </a>
                        <a href="{{ route('approvals.tracer.jalur-photos') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('approvals.tracer.jalur-photos') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-road w-5 mr-3 text-sm"></i>
                            Jalur (Lowering, Joint)
                        </a>
                    </div>
                </div>
                @endif

                {{-- CGP Review with Submenu: CGP & SUPER ADMIN (admin tidak melihat menu ini) --}}
                @if(auth()->user()->hasAnyRole(['cgp','super_admin']))
                <div x-data="{ cgpOpen: {{ request()->routeIs('approvals.cgp.*') ? 'true' : 'false' }} }" class="space-y-1">
                    <button @click="cgpOpen = !cgpOpen"
                            class="flex items-center w-full px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('approvals.cgp.*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                        <i class="fas fa-clipboard-check w-5 mr-3 text-lg {{ request()->routeIs('approvals.cgp.*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                        <span class="flex-1 text-left">CGP Review</span>
                        @php
                            $pendingCgp = \App\Models\PhotoApproval::where('photo_status', 'cgp_pending')->count();
                        @endphp
                        @if($pendingCgp > 0)
                            <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full mr-2">{{ $pendingCgp }}</span>
                        @endif
                        <svg class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-180': cgpOpen }"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Submenu -->
                    <div x-show="cgpOpen"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-1"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 transform translate-y-0"
                         x-transition:leave-end="opacity-0 transform -translate-y-1"
                         class="space-y-1 ml-6 mt-1">
                        <a href="{{ route('approvals.cgp.customers') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('approvals.cgp.customers') || request()->routeIs('approvals.cgp.customer-photos') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-users w-5 mr-3 text-sm"></i>
                            Calon Pelanggan (SK, SR, Gas In)
                        </a>
                        <a href="{{ route('approvals.cgp.jalur-photos') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('approvals.cgp.jalur-photos') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-road w-5 mr-3 text-sm"></i>
                            Jalur (Lowering, Joint)
                        </a>
                    </div>
                </div>
                @endif

                {{-- CGP Review Jalur Only: For JALUR role --}}
                @if(auth()->user()->hasAnyRole(['jalur']) && !auth()->user()->hasAnyRole(['cgp','super_admin']))
                <!-- Divider -->
                <div class="border-t border-gray-200 my-4"></div>
                <div class="px-3 mb-2">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">APPROVAL</span>
                </div>

                <a href="{{ route('approvals.cgp.jalur-photos') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('approvals.cgp.jalur-photos') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-clipboard-check w-5 mr-3 text-lg {{ request()->routeIs('approvals.cgp.jalur-photos') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                    <span class="flex-1 text-left">CGP Review - Jalur</span>
                    @php
                        $pendingJalurCgp = \App\Models\PhotoApproval::where('photo_status', 'cgp_pending')
                            ->whereIn('module_name', ['lowering', 'joint'])
                            ->count();
                    @endphp
                    @if($pendingJalurCgp > 0)
                        <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">{{ $pendingJalurCgp }}</span>
                    @endif
                </a>
                @endif

                @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                <!-- Notifications -->
                <a href="{{ route('notifications.index') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('notifications.*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-bell w-5 mr-3 text-lg {{ request()->routeIs('notifications.*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                    Notifications
                </a>

                <!-- Reports with Submenu -->
                <div class="relative" x-data="{ open: {{ request()->routeIs('reports.*') || request()->routeIs('jalur.reports*') ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            class="flex items-center w-full px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('reports.*') || request()->routeIs('jalur.reports*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                        <i class="fas fa-chart-bar w-5 mr-3 text-lg {{ request()->routeIs('reports.*') || request()->routeIs('jalur.reports*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                        Reports
                        <i class="fas fa-chevron-down ml-auto text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-y-90" x-transition:enter-end="opacity-100 transform scale-y-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-y-100" x-transition:leave-end="opacity-0 transform scale-y-90" class="mt-1 space-y-1 ml-6 transform-origin-top">
                        <a href="{{ route('reports.dashboard') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('reports.dashboard') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-tachometer-alt w-5 mr-3 text-sm"></i>
                            Laporan Dashboard
                        </a>
                        <a href="{{ route('reports.comprehensive') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('reports.comprehensive') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-file-alt w-5 mr-3 text-sm"></i>
                            Laporan Komprehensif
                        </a>
                        <a href="{{ route('jalur.reports') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('jalur.reports*') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-road w-5 mr-3 text-sm"></i>
                            Laporan Jalur
                        </a>
                    </div>
                </div>
                @endif

                @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                <!-- Divider -->
                <div class="border-t border-gray-200 my-4"></div>
                <div class="px-3 mb-2">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">MANAGEMENT</span>
                </div>
                @endif

                @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                <!-- Import Excel -->
                <div x-data="{ open: {{ request()->routeIs('imports.*') ? 'true' : 'false' }} }" class="relative">
                    <button @click="open = !open"
                            class="flex items-center w-full px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('imports.*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                        <i class="fas fa-file-excel w-5 mr-3 text-lg {{ request()->routeIs('imports.*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                        Import Excel
                        <div class="ml-auto flex items-center">
                            @if(request()->routeIs('imports.*'))
                                <div class="w-2 h-2 bg-aergas-orange rounded-full mr-2"></div>
                            @endif
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200"
                               :class="{ 'rotate-180': open }"></i>
                        </div>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="mt-1 ml-6 space-y-1">

                        <!-- Calon Pelanggan Import -->
                        <a href="{{ route('imports.calon-pelanggan.form') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('imports.calon-pelanggan.*') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-users w-5 mr-3 text-sm"></i>
                            Calon Pelanggan
                        </a>

                        <!-- Coordinates Import -->
                        <a href="{{ route('imports.coordinates.form') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('imports.coordinates.*') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-map-marker-alt w-5 mr-3 text-sm"></i>
                            Koordinat Calon Pelanggan
                        </a>

                        <!-- SK Berita Acara Import -->
                        <a href="{{ route('imports.sk-berita-acara.form') }}"
                           class="flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 {{ request()->routeIs('imports.sk-berita-acara.*') ? 'text-aergas-orange bg-aergas-orange/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            <i class="fas fa-file-pdf w-5 mr-3 text-sm"></i>
                            SK Berita Acara
                        </a>

                    </div>
                </div>
                @endif

                @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                <!-- File Manager -->
                <a href="#"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group text-gray-400 cursor-not-allowed">
                    <i class="fas fa-folder w-5 mr-3 text-lg text-gray-300"></i>
                    File Manager
                    <span class="ml-auto text-xs bg-gray-200 text-gray-500 px-2 py-1 rounded-full">Soon</span>
                </a>

                <!-- Audit Logs -->
                <a href="#"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group text-gray-400 cursor-not-allowed">
                    <i class="fas fa-history w-5 mr-3 text-lg text-gray-300"></i>
                    Audit Logs
                    <span class="ml-auto text-xs bg-gray-200 text-gray-500 px-2 py-1 rounded-full">Soon</span>
                </a>
                @endif

                @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
                    <!-- Divider -->
                    <div class="border-t border-gray-200 my-4"></div>
                    <div class="px-3 mb-2">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">SYSTEM</span>
                    </div>

                    <!-- System Settings -->
                    <a href="{{ route('admin.settings') }}"
                    class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.settings') ? 'bg-aergas-orange text-white' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                        <i class="fas fa-cogs w-5 mr-3 text-lg {{ request()->routeIs('admin.settings') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                        System Settings
                    </a>

                    <!-- User Management -->
                    <a href="{{ route('admin.users') }}"
                    class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.users*') ? 'bg-aergas-orange text-white' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                        <i class="fas fa-users-cog w-5 mr-3 text-lg {{ request()->routeIs('admin.users*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                        User Management
                    </a>
                    @endif
            </div>
        </nav>

        <!-- Bottom Section -->
        <div class="px-4 py-4 border-t border-gray-200 flex-shrink-0">
            <!-- Profile -->
            <a href="{{ route('auth.me') }}"
               class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group text-gray-600 hover:text-gray-900 hover:bg-gray-50">
                <i class="fas fa-user w-5 mr-3 text-lg text-gray-400 group-hover:text-gray-600"></i>
                My Profile
            </a>

            {{-- <!-- Settings -->
            <a href="#"
               class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group text-gray-600 hover:text-gray-900 hover:bg-gray-50">
                <i class="fas fa-cog mr-3 text-lg text-gray-400 group-hover:text-gray-600"></i>
                Settings
            </a> --}}

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit"
                        class="flex items-center w-full px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group text-gray-600 hover:bg-red-50 hover:text-red-600">
                    <i class="fas fa-sign-out-alt w-5 mr-3 text-lg text-gray-400 group-hover:text-red-500"></i>
                    Logout
                </button>
            </form>
        </div>
    </div>
</div>
