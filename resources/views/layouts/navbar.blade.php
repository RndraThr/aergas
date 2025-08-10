<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="flex items-center justify-between px-6 py-4">
        <!-- Left Side -->
        <div class="flex items-center space-x-4">
            <!-- Mobile menu button -->
            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-600 hover:text-gray-800">
                <i class="fas fa-bars text-xl"></i>
            </button>

            <!-- Breadcrumb Navigation -->
            <nav class="hidden md:flex space-x-2 text-sm">
                <a href="{{ route('dashboard.index') }}" class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full hover:bg-blue-200 transition-colors">
                    Dashboard
                </a>
                @if(!request()->routeIs('dashboard.*'))
                <a href="{{ request()->url() }}" class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full">
                    @yield('page-title', 'Current Page')
                </a>
                @endif
            </nav>
        </div>

        <!-- Right Side -->
        <div class="flex items-center space-x-4">
            <!-- Search -->
            <div class="hidden md:block relative">
                <input type="text" placeholder="Cari..."
                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>

            <!-- Notifications -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="relative p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-full">
                    <i class="fas fa-bell text-xl"></i>
                    @if(isset($unreadNotifications) && $unreadNotifications > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            {{ $unreadNotifications }}
                        </span>
                    @endif
                </button>

                <!-- Notifications Dropdown -->
                <div x-show="open" @click.away="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-1 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-1 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">

                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Notifications</h3>
                    </div>

                    <div class="max-h-64 overflow-y-auto">
                        <!-- Sample notifications -->
                        <div class="p-4 border-b border-gray-100 hover:bg-gray-50">
                            <div class="flex items-start space-x-3">
                                <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-800">New customer registration</p>
                                    <p class="text-xs text-gray-500 mt-1">2 minutes ago</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 text-center">
                            <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View all notifications</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Menu -->
            <div class="flex items-center space-x-3">
                <div class="hidden md:block text-right">
                    <div class="text-sm font-medium text-gray-800">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</div>
                </div>
                <div class="w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center text-white font-medium">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
            </div>
        </div>
    </div>
</header>
