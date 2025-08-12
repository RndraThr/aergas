<!DOCTYPE html>
<html lang="id" x-data="{ sidebarOpen: false }" x-init="$store.auth = { user: @json(auth()->user()) }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AERGAS System')</title>
    <link rel="icon" href="{{ asset('build/assets/AERGAS PNG.png') }}" type="image/png">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'aergas-navy': '#1e3a5f',
                        'aergas-orange': '#ff6b35',
                        'aergas-light-blue': '#f0f4f8',
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        [x-cloak] { display: none !important; }
        .sidebar-active {
            @apply bg-gradient-to-r from-aergas-orange/10 to-aergas-orange/5 border-r-2 border-aergas-orange text-aergas-orange font-medium;
        }

        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Loading animation */
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Notification pulse */
        .notification-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        @include('layouts.sidebar')

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center space-x-4">
                        <!-- Mobile menu button -->
                        <button @click="sidebarOpen = !sidebarOpen"
                                class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition-colors">
                            <i class="fas fa-bars text-lg"></i>
                        </button>

                        <!-- Page Title & Breadcrumb -->
                        <div class="flex items-center space-x-3">
                            <h1 class="text-xl font-semibold text-gray-900">
                                @yield('page-title', 'Dashboard')
                            </h1>
                            @hasSection('breadcrumb')
                                <nav class="hidden md:flex" aria-label="Breadcrumb">
                                    <ol class="flex items-center space-x-2 text-sm text-gray-500">
                                        @yield('breadcrumb')
                                    </ol>
                                </nav>
                            @endif
                        </div>
                    </div>

                    <!-- Right side -->
                    <div class="flex items-center space-x-4">
                        <!-- Search -->
                        <div class="relative hidden md:block">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="text"
                                   placeholder="Cari customer, reff ID..."
                                   class="pl-10 pr-4 py-2 w-64 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-aergas-orange focus:border-transparent transition-all"
                                   x-data="{ searching: false }"
                                   @input.debounce.500ms="searching = true; setTimeout(() => searching = false, 1000)">
                        </div>

                        <!-- Notifications -->
                        <div class="relative" x-data="{ open: false }">
                            @php
                                $unreadNotifications = \App\Models\Notification::where('user_id', auth()->id())
                                    ->where('is_read', false)->count();
                            @endphp
                            <button @click="open = !open"
                                    class="relative p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-full transition-colors">
                                <i class="fas fa-bell text-lg"></i>
                                @if($unreadNotifications > 0)
                                    <span class="absolute -top-1 -right-1 block h-5 w-5 rounded-full bg-red-500 text-white text-xs font-medium flex items-center justify-center notification-pulse">
                                        {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}
                                    </span>
                                @endif
                            </button>

                            <!-- Notifications Dropdown -->
                            <div x-show="open"
                                 @click.away="open = false"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">

                                <div class="p-4 border-b border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
                                        @if($unreadNotifications > 0)
                                            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">{{ $unreadNotifications }} new</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="max-h-64 overflow-y-auto custom-scrollbar">
                                    @php
                                        $recentNotifications = \App\Models\Notification::where('user_id', auth()->id())
                                            ->latest()->take(5)->get();
                                    @endphp

                                    @forelse($recentNotifications as $notification)
                                        <div class="p-4 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                            <div class="flex items-start space-x-3">
                                                <div class="w-2 h-2 {{ $notification->is_read ? 'bg-gray-300' : 'bg-aergas-orange' }} rounded-full mt-2 flex-shrink-0"></div>
                                                <div class="flex-1">
                                                    <p class="text-sm font-medium text-gray-900">{{ $notification->title }}</p>
                                                    <p class="text-xs text-gray-500 mt-1">{{ Str::limit($notification->message, 60) }}</p>
                                                    <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-4 text-center text-gray-500">
                                            <i class="fas fa-bell-slash text-2xl mb-2"></i>
                                            <p class="text-sm">No notifications yet</p>
                                        </div>
                                    @endforelse

                                    @if($recentNotifications->count() > 0)
                                        <div class="p-4 text-center">
                                            <a href="{{ route('notifications.index') }}"
                                               class="text-aergas-orange hover:text-aergas-navy text-sm font-medium transition-colors">
                                                View all notifications
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- User Menu -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                    class="flex items-center space-x-3 text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-aergas-orange transition-all">
                                <div class="w-8 h-8 bg-gradient-to-br from-aergas-navy to-aergas-orange rounded-full flex items-center justify-center text-white font-medium shadow-md">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <div class="hidden md:block text-left">
                                    <div class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</div>
                                    <div class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</div>
                                </div>
                                <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                            </button>

                            <!-- Dropdown -->
                            <div x-show="open"
                                 @click.away="open = false"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200">

                                <a href="{{ route('auth.me') }}"
                                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                    <i class="fas fa-user mr-3 text-gray-400"></i>
                                    My Profile
                                </a>

                                <a href="#"
                                   class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                    <i class="fas fa-cog mr-3 text-gray-400"></i>
                                    Settings
                                </a>

                                <hr class="my-1">

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                        <i class="fas fa-sign-out-alt mr-3 text-gray-400"></i>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 custom-scrollbar">
                <div class="p-6">
                    @if(session('success'))
                        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg" role="alert">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle mr-2"></i>
                                {{ session('success') }}
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg" role="alert">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                {{ session('error') }}
                            </div>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg" role="alert">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                {{ session('warning') }}
                            </div>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div x-data="toastManager()"
         x-init="init()"
         class="fixed top-4 right-4 z-50 space-y-2"
         @toast.window="addToast($event.detail)">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-x-full"
                 x-transition:enter-end="opacity-100 transform translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-x-0"
                 x-transition:leave-end="opacity-0 transform translate-x-full"
                 :class="{
                     'bg-green-500': toast.type === 'success',
                     'bg-red-500': toast.type === 'error',
                     'bg-yellow-500': toast.type === 'warning',
                     'bg-blue-500': toast.type === 'info'
                 }"
                 class="text-white px-6 py-4 rounded-lg shadow-lg max-w-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i :class="{
                            'fas fa-check-circle': toast.type === 'success',
                            'fas fa-exclamation-circle': toast.type === 'error',
                            'fas fa-exclamation-triangle': toast.type === 'warning',
                            'fas fa-info-circle': toast.type === 'info'
                        }" class="mr-2"></i>
                        <span x-text="toast.message"></span>
                    </div>
                    <button @click="removeToast(toast.id)" class="ml-4 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Loading Overlay -->
    <div x-data="{ loading: false }"
         x-show="loading"
         x-cloak
         @loading.window="loading = $event.detail.show"
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl">
            <div class="flex items-center space-x-3">
                <i class="fas fa-spinner loading-spinner text-aergas-orange text-xl"></i>
                <span class="text-gray-700">Loading...</span>
            </div>
        </div>
    </div>

    <script>
        // Toast Manager
        function toastManager() {
            return {
                toasts: [],
                nextId: 1,

                init() {
                    // Auto-show Laravel flash messages as toasts
                    @if(session('success'))
                        this.addToast({ type: 'success', message: @json(session('success')) });
                    @endif
                    @if(session('error'))
                        this.addToast({ type: 'error', message: @json(session('error')) });
                    @endif
                    @if(session('warning'))
                        this.addToast({ type: 'warning', message: @json(session('warning')) });
                    @endif
                },

                addToast(toast) {
                    const newToast = {
                        id: this.nextId++,
                        type: toast.type || 'info',
                        message: toast.message,
                        show: false
                    };

                    this.toasts.push(newToast);

                    // Show with slight delay for transition
                    setTimeout(() => {
                        newToast.show = true;
                    }, 100);

                    // Auto remove after 5 seconds
                    setTimeout(() => {
                        this.removeToast(newToast.id);
                    }, 5000);
                },

                removeToast(id) {
                    const index = this.toasts.findIndex(toast => toast.id === id);
                    if (index > -1) {
                        this.toasts[index].show = false;
                        setTimeout(() => {
                            this.toasts.splice(index, 1);
                        }, 200);
                    }
                }
            }
        }

        // Global helper functions
        window.showToast = function(type, message) {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { type, message }
            }));
        };

        window.showLoading = function(show = true) {
            window.dispatchEvent(new CustomEvent('loading', {
                detail: { show }
            }));
        };

        // CSRF Token setup for AJAX requests
        window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    </script>

    @stack('scripts')
</body>
</html>
