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
    <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform lg:translate-x-0 lg:static lg:inset-0 transition-transform duration-300 ease-in-out border-r border-gray-200"
         :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
         x-cloak>

        <!-- Logo -->
        <div class="flex items-center justify-between h-16 px-6 bg-gradient-to-r from-aergas-navy to-aergas-navy/90 border-b border-aergas-navy/20">
            <div class="flex items-center space-x-3">
                <img src="{{ asset('assets/AERGAS_PNG.png') }}"
                     alt="AERGAS Logo"
                     class="h-8 w-auto filter brightness-0 invert">
            </div>
            <button @click="sidebarOpen = false"
                    class="lg:hidden p-2 rounded-md text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- User Info -->
        <div class="p-6 bg-gradient-to-br from-gray-50 to-white border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-gradient-to-br from-aergas-navy to-aergas-orange rounded-full flex items-center justify-center text-white font-medium shadow-md">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full inline-block mt-1">
                        {{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-6 overflow-y-auto custom-scrollbar">
            <div class="space-y-1">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('dashboard') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-chart-pie mr-3 text-lg {{ request()->routeIs('dashboard') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                    Dashboard
                    @if(request()->routeIs('dashboard'))
                        <div class="ml-auto">
                            <div class="w-2 h-2 bg-aergas-orange rounded-full"></div>
                        </div>
                    @endif
                </a>

                <!-- Data Pelanggan -->
                <a href="{{ route('customers.index') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('customers.*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-users mr-3 text-lg {{ request()->routeIs('customers.*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                    Data Pelanggan
                    @if(request()->routeIs('customers.*'))
                        <div class="ml-auto">
                            <div class="w-2 h-2 bg-aergas-orange rounded-full"></div>
                        </div>
                    @endif
                </a>

                <!-- Divider -->
                <div class="border-t border-gray-200 my-4"></div>
                <div class="px-3 mb-2">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">MODULES</span>
                </div>

                @if(in_array(auth()->user()->role, ['sk', 'tracer', 'admin', 'super_admin']))
                <!-- SK Data -->
                <a href="{{ route('sk.index') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('sk.*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-fire mr-3 text-lg {{ request()->routeIs('sk.*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                    SK Form
                    @if(request()->routeIs('sk.*'))
                        <div class="ml-auto">
                            <div class="w-2 h-2 bg-aergas-orange rounded-full"></div>
                        </div>
                    @endif
                </a>
                @endif

                @if(in_array(auth()->user()->role, ['sr', 'tracer', 'admin', 'super_admin']))
                <!-- SR Data -->
                <a href="{{ route('sr.index') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('sr.*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-house-user mr-3 text-lg {{ request()->routeIs('sr.*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                    SR Form
                    @if(request()->routeIs('sr.*'))
                        <div class="ml-auto">
                            <div class="w-2 h-2 bg-aergas-orange rounded-full"></div>
                        </div>
                    @endif
                </a>
                @endif

                @if(in_array(auth()->user()->role, ['gas_in', 'tracer', 'admin', 'super_admin']))
                <!-- Gas In Data -->
                <a href="{{ route('gas-in.index') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('gas-in.*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-gas-pump mr-3 text-lg {{ request()->routeIs('gas-in.*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                    Gas In Form
                    @if(request()->routeIs('gas-in.*'))
                        <div class="ml-auto">
                            <div class="w-2 h-2 bg-aergas-orange rounded-full"></div>
                        </div>
                    @endif
                </a>
                @endif

                @if(in_array(auth()->user()->role, ['pic', 'tracer', 'admin']))
                <!-- Jalur Pipa -->
                <a href="#"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group text-gray-400 cursor-not-allowed">
                    <i class="fas fa-project-diagram mr-3 text-lg text-gray-300"></i>
                    Jalur Pipa
                    <span class="ml-auto text-xs bg-gray-200 text-gray-500 px-2 py-1 rounded-full">Soon</span>
                </a>

                <!-- Penyambungan -->
                <a href="#"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group text-gray-400 cursor-not-allowed">
                    <i class="fas fa-link mr-3 text-lg text-gray-300"></i>
                    Penyambungan
                    <span class="ml-auto text-xs bg-gray-200 text-gray-500 px-2 py-1 rounded-full">Soon</span>
                </a>
                @endif

                <!-- Divider -->
                <div class="border-t border-gray-200 my-4"></div>
                <div class="px-3 mb-2">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">WORKFLOW</span>
                </div>

                @if(in_array(auth()->user()->role, ['tracer', 'admin']))
                <!-- Photo Approvals -->
                <a href="{{ route('photos.index') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('photos.*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-clipboard-check mr-3 text-lg {{ request()->routeIs('photos.*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                    Photo Approvals
                    @php
                        $pendingPhotos = \App\Models\PhotoApproval::where('photo_status',
                            auth()->user()->role === 'tracer' ? 'tracer_pending' : 'cgp_pending'
                        )->count();
                    @endphp
                    @if($pendingPhotos > 0)
                        <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">{{ $pendingPhotos }}</span>
                    @endif
                </a>
                @endif

                <!-- Notifications -->
                <a href="{{ route('notifications.index') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('notifications.*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-bell mr-3 text-lg {{ request()->routeIs('notifications.*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                    Notifications
                </a>

                <!-- Divider -->
                <div class="border-t border-gray-200 my-4"></div>
                <div class="px-3 mb-2">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">MANAGEMENT</span>
                </div>

                {{-- @if(in_array(auth()->user()->role, ['admin', 'gudang', 'tracer']))
                <!-- Gudang Data -->
                <a href="{{ route('gudang.items') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('gudang.*') ? 'sidebar-active' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-warehouse mr-3 text-lg {{ request()->routeIs('gudang.*') ? 'text-aergas-orange' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                    Gudang & Inventory
                    @if(request()->routeIs('gudang.*'))
                        <div class="ml-auto">
                            <div class="w-2 h-2 bg-aergas-orange rounded-full"></div>
                        </div>
                    @endif
                </a>
                @endif --}}

                <!-- File Manager -->
                <a href="#"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group text-gray-400 cursor-not-allowed">
                    <i class="fas fa-folder mr-3 text-lg text-gray-300"></i>
                    File Manager
                    <span class="ml-auto text-xs bg-gray-200 text-gray-500 px-2 py-1 rounded-full">Soon</span>
                </a>

                <!-- Audit Logs -->
                <a href="#"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group text-gray-400 cursor-not-allowed">
                    <i class="fas fa-history mr-3 text-lg text-gray-300"></i>
                    Audit Logs
                    <span class="ml-auto text-xs bg-gray-200 text-gray-500 px-2 py-1 rounded-full">Soon</span>
                </a>

                @if(auth()->user()->role === 'super_admin' || auth()->user()->role === 'admin')
                    <!-- Divider -->
                    <div class="border-t border-gray-200 my-4"></div>
                    <div class="px-3 mb-2">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">SYSTEM</span>
                    </div>

                    <!-- System Settings -->
                    <a href="{{ route('admin.settings') }}"
                    class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.settings') ? 'bg-aergas-orange text-white' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                        <i class="fas fa-cogs mr-3 text-lg {{ request()->routeIs('admin.settings') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                        System Settings
                    </a>

                    <!-- User Management -->
                    <a href="{{ route('admin.users') }}"
                    class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.users*') ? 'bg-aergas-orange text-white' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                        <i class="fas fa-users-cog mr-3 text-lg {{ request()->routeIs('admin.users*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
                        User Management
                    </a>
                    @endif
            </div>

            <!-- Bottom Section -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <!-- Profile -->
                <a href="{{ route('auth.me') }}"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group text-gray-600 hover:text-gray-900 hover:bg-gray-50">
                    <i class="fas fa-user mr-3 text-lg text-gray-400 group-hover:text-gray-600"></i>
                    My Profile
                </a>

                <!-- Settings -->
                <a href="#"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group text-gray-600 hover:text-gray-900 hover:bg-gray-50">
                    <i class="fas fa-cog mr-3 text-lg text-gray-400 group-hover:text-gray-600"></i>
                    Settings
                </a>

                <!-- Logout -->
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit"
                            class="flex items-center w-full px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 group text-gray-600 hover:text-gray-900 hover:bg-red-50 hover:text-red-600">
                        <i class="fas fa-sign-out-alt mr-3 text-lg text-gray-400 group-hover:text-red-500"></i>
                        Logout
                    </button>
                </form>
            </div>
        </nav>
    </div>
</div>
