<!DOCTYPE html>
<html lang="id" x-data="{ sidebarOpen: false }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AERGAS System')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/png">

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
    <script>
    document.addEventListener('alpine:init', () => {
    Alpine.store('auth', { user: @js(auth()->user()) });
    });
    </script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Leaflet CSS for Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin=""/>

    <!-- Leaflet MarkerCluster CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

    <style>
        [x-cloak] { display: none !important; }
        .sidebar-active {
            @apply bg-gradient-to-r from-aergas-orange/10 to-aergas-orange/5 border-r-2 border-aergas-orange text-aergas-orange font-medium;
        }

        /* Modern Dropdown - Simple & Clean Design */
        select {
            padding: 0.5rem 0.875rem !important;
            padding-right: 2.25rem !important;
            font-size: 0.875rem !important;
            font-weight: 400 !important;
            line-height: 1.5 !important;
            color: #1f2937 !important;
            background-color: #ffffff !important;
            border: 1.5px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            transition: all 0.15s ease !important;
            cursor: pointer !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%239ca3af'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 0.625rem center !important;
            background-size: 1rem !important;
        }
        select:hover {
            border-color: #9ca3af !important;
            background-color: #fafafa !important;
        }
        select:focus {
            outline: none !important;
            border-color: #ff6b35 !important;
            background-color: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.08) !important;
        }
        select:disabled {
            opacity: 0.5 !important;
            cursor: not-allowed !important;
            background-color: #f3f4f6 !important;
            border-color: #e5e7eb !important;
        }
        select::-ms-expand {
            display: none;
        }

        /* Styling untuk option items - rounded & smooth */
        select option {
            padding: 0.625rem 0.875rem !important;
            margin: 0.125rem 0.375rem !important;
            font-size: 0.875rem !important;
            line-height: 1.5 !important;
            color: #1f2937 !important;
            background-color: transparent !important;
            border-radius: 0.375rem !important;
        }
        select option:hover {
            background-color: #fff7ed !important;
            color: #c2410c !important;
            border-radius: 0.375rem !important;
        }
        select option:checked {
            background-color: #ff6b35 !important;
            color: #ffffff !important;
            font-weight: 500 !important;
            border-radius: 0.375rem !important;
        }
        select option:disabled {
            color: #d1d5db !important;
            background-color: transparent !important;
            cursor: not-allowed !important;
            opacity: 0.5 !important;
        }

        /* Smooth scrollbar untuk dropdown */
        select {
            scrollbar-width: thin !important;
            scrollbar-color: #d1d5db transparent !important;
        }
        select::-webkit-scrollbar {
            width: 6px !important;
        }
        select::-webkit-scrollbar-track {
            background: transparent !important;
        }
        select::-webkit-scrollbar-thumb {
            background: #d1d5db !important;
            border-radius: 3px !important;
        }
        select::-webkit-scrollbar-thumb:hover {
            background: #9ca3af !important;
        }

        /* Alpine Custom Dropdown dengan Animasi */
        .custom-dropdown {
            position: relative;
        }
        .custom-dropdown-button {
            width: 100%;
            padding: 0.5rem 0.875rem;
            padding-right: 2.25rem;
            font-size: 0.875rem;
            font-weight: 400;
            line-height: 1.5;
            color: #1f2937;
            background-color: #ffffff;
            border: 1.5px solid #d1d5db;
            border-radius: 0.5rem;
            transition: all 0.15s ease;
            cursor: pointer;
            text-align: left;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%239ca3af'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.625rem center;
            background-size: 1rem;
        }
        .custom-dropdown-button:hover {
            border-color: #9ca3af;
            background-color: #fafafa;
        }
        .custom-dropdown-button.active {
            border-color: #ff6b35;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.08);
        }
        .custom-dropdown-button svg {
            position: absolute;
            right: 0.625rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1rem;
            height: 1rem;
            color: #9ca3af;
            transition: transform 0.2s ease;
        }
        .custom-dropdown-button.active svg {
            transform: translateY(-50%) rotate(180deg);
        }

        /* Dropdown Menu dengan Rounded & Animasi */
        .custom-dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 0.375rem;
            background: white;
            border: 1.5px solid #e5e7eb;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            z-index: 50;
            max-height: 280px;
            overflow-y: auto;
        }
        .custom-dropdown-menu::-webkit-scrollbar {
            width: 6px;
        }
        .custom-dropdown-menu::-webkit-scrollbar-track {
            background: #f9fafb;
        }
        .custom-dropdown-menu::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 3px;
        }
        .custom-dropdown-menu::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        /* Dropdown Option Items */
        .custom-dropdown-option {
            padding: 0.625rem 0.875rem;
            font-size: 0.875rem;
            color: #1f2937;
            cursor: pointer;
            transition: all 0.1s ease;
        }
        .custom-dropdown-option:hover {
            background-color: #fff7ed;
            color: #c2410c;
        }
        .custom-dropdown-option.selected {
            background-color: #ff6b35;
            color: #ffffff;
            font-weight: 500;
        }
        .custom-dropdown-option:first-child {
            margin-top: 0.25rem;
        }
        .custom-dropdown-option:last-child {
            margin-bottom: 0.25rem;
        }

        /* Animasi Slide Down/Up */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes slideUp {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }
        .dropdown-enter {
            animation: slideDown 0.2s ease-out;
        }
        .dropdown-leave {
            animation: slideUp 0.15s ease-in;
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

        /* Custom Loading Screen */
        .custom-loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.2s ease-out;
        }
        .custom-loading-overlay.active {
            display: flex;
        }
        .custom-loading-content {
            background: white;
            padding: 2rem 3rem;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: slideUp 0.3s ease-out;
        }
        .custom-loading-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #f3f4f6;
            border-top-color: #ff6b35;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1rem;
        }

        /* Custom Toast */
        .custom-toast-container {
            position: fixed;
            top: 5rem;
            right: 1.5rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            pointer-events: none;
        }
        .custom-toast {
            background: white;
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 300px;
            max-width: 400px;
            pointer-events: auto;
            animation: slideInRight 0.3s ease-out;
            border-left: 4px solid;
        }
        .custom-toast.success {
            border-left-color: #10b981;
        }
        .custom-toast.error {
            border-left-color: #ef4444;
        }
        .custom-toast.warning {
            border-left-color: #f59e0b;
        }
        .custom-toast.info {
            border-left-color: #3b82f6;
        }
        .custom-toast.hiding {
            animation: slideOutRight 0.3s ease-in forwards;
        }
        .custom-toast-icon {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 14px;
        }
        .custom-toast.success .custom-toast-icon {
            background: #d1fae5;
            color: #059669;
        }
        .custom-toast.error .custom-toast-icon {
            background: #fee2e2;
            color: #dc2626;
        }
        .custom-toast.warning .custom-toast-icon {
            background: #fef3c7;
            color: #d97706;
        }
        .custom-toast.info .custom-toast-icon {
            background: #dbeafe;
            color: #2563eb;
        }
        .custom-toast-message {
            flex: 1;
            color: #374151;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        .custom-toast-close {
            flex-shrink: 0;
            color: #9ca3af;
            cursor: pointer;
            transition: color 0.2s;
        }
        .custom-toast-close:hover {
            color: #4b5563;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
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
                    {{-- @if(session('success'))
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
                    @endif --}}

                    @yield('content')
                </div>
            </main>
        </div>

        {{-- Smart FAB - Role & Context Aware Quick Actions --}}
        <x-smart-fab />
    </div>

    <!-- Custom Loading Overlay -->
    <div id="customLoadingOverlay" class="custom-loading-overlay">
        <div class="custom-loading-content">
            <div class="custom-loading-spinner"></div>
            <div id="loadingMessage" class="text-gray-700 font-medium text-base">Memproses...</div>
        </div>
    </div>

    <!-- Custom Toast Container -->
    <div id="customToastContainer" class="custom-toast-container"></div>

    <script>
        // CSRF Token setup for AJAX requests
        window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Custom Toast System
        let toastIdCounter = 0;

        window.closeToast = function(toastId) {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.classList.add('hiding');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }
        };

        function showToast(type, message) {
            const container = document.getElementById('customToastContainer');
            const toastId = `toast-${toastIdCounter++}`;

            const icons = {
                success: 'fa-check-circle',
                error: 'fa-times-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };

            const toast = document.createElement('div');
            toast.id = toastId;
            toast.className = `custom-toast ${type}`;
            toast.innerHTML = `
                <div class="custom-toast-icon">
                    <i class="fas ${icons[type]}"></i>
                </div>
                <div class="custom-toast-message">${message}</div>
                <div class="custom-toast-close" onclick="window.closeToast('${toastId}')">
                    <i class="fas fa-times"></i>
                </div>
            `;

            container.appendChild(toast);

            // Auto remove after 4 seconds
            setTimeout(() => {
                window.closeToast(toastId);
            }, 4000);
        }

        // Expose showToast to window scope
        window.showToast = showToast;

        // Helper functions
        window.showSuccessToast = function(message) {
            showToast('success', message);
        };

        window.showErrorToast = function(message) {
            showToast('error', message);
        };

        window.showWarningToast = function(message) {
            showToast('warning', message);
        };

        window.showInfoToast = function(message) {
            showToast('info', message);
        };

        // Custom Loading Screen
        window.showLoading = function(message = 'Memproses...') {
            const overlay = document.getElementById('customLoadingOverlay');
            const messageEl = document.getElementById('loadingMessage');
            messageEl.textContent = message;
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        };

        window.closeLoading = function() {
            const overlay = document.getElementById('customLoadingOverlay');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        };

        // Show confirm dialog
        window.showConfirm = function(options) {
            return new Promise((resolve) => {
                const overlay = document.createElement('div');
                overlay.className = 'custom-loading-overlay active';
                overlay.style.zIndex = '10000';

                const content = document.createElement('div');
                content.className = 'custom-loading-content';
                content.style.maxWidth = '400px';
                content.innerHTML = `
                    <div class="text-center mb-6">
                        <div class="mx-auto w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-question text-aergas-orange text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">${options.title || 'Konfirmasi'}</h3>
                        <p class="text-gray-600">${options.text || 'Apakah Anda yakin?'}</p>
                    </div>
                    <div class="flex gap-3 justify-center">
                        <button id="confirmCancel" class="px-6 py-2.5 bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium rounded-lg transition-colors">
                            ${options.cancelText || 'Batal'}
                        </button>
                        <button id="confirmOk" class="px-6 py-2.5 bg-aergas-orange hover:bg-orange-600 text-white font-medium rounded-lg transition-colors">
                            ${options.confirmText || 'Ya'}
                        </button>
                    </div>
                `;

                overlay.appendChild(content);
                document.body.appendChild(overlay);
                document.body.style.overflow = 'hidden';

                document.getElementById('confirmOk').onclick = () => {
                    overlay.remove();
                    document.body.style.overflow = '';
                    resolve(true);
                };

                document.getElementById('confirmCancel').onclick = () => {
                    overlay.remove();
                    document.body.style.overflow = '';
                    resolve(false);
                };
            });
        };

        // Helper for safe JSON fetch
        window.safeFetchJSON = async function(url, options = {}) {
            try {
                // Merge Accept header with existing headers
                const mergedOptions = {
                    ...options,
                    headers: {
                        ...options.headers,
                        'Accept': 'application/json'
                    }
                };

                const response = await fetch(url, mergedOptions);
                const contentType = response.headers.get('content-type');

                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Terjadi kesalahan pada server');
                }

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Terjadi kesalahan');
                }

                return data;
            } catch (error) {
                throw error;
            }
        };

        // Auto-show Laravel flash messages as toasts
        @if(session('success'))
            window.addEventListener('DOMContentLoaded', () => {
                showSuccessToast(@json(session('success')));
            });
        @endif
        @if(session('error'))
            window.addEventListener('DOMContentLoaded', () => {
                showErrorToast(@json(session('error')));
            });
        @endif
        @if(session('warning'))
            window.addEventListener('DOMContentLoaded', () => {
                showWarningToast(@json(session('warning')));
            });
        @endif
    </script>
    <script>
        function appLogout() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("logout") }}';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    </script>

    <!-- Global Pagination Helper -->
    <script>
    // Global function to handle back navigation with pagination state
    window.goBackWithPagination = function(baseRoute, module = null) {
        // Determine storage key based on route or module
        let storageKey = 'pagination_state';
        if (module) {
            storageKey = `${module}_pagination_state`;
        } else if (baseRoute.includes('/sk')) {
            storageKey = 'sk_pagination_state';
        } else if (baseRoute.includes('/sr')) {
            storageKey = 'sr_pagination_state';
        } else if (baseRoute.includes('/gas-in')) {
            storageKey = 'gas_in_pagination_state';
        }

        const savedState = localStorage.getItem(storageKey);

        if (savedState) {
            try {
                const state = JSON.parse(savedState);
                // Check if state is recent (within 10 minutes)
                if (Date.now() - state.timestamp < 600000) {
                    const url = new URL(baseRoute, window.location.origin);

                    // Add pagination and search parameters
                    if (state.page && state.page !== '1') {
                        url.searchParams.set('page', state.page);
                    }

                    if (state.search) {
                        const savedParams = new URLSearchParams(state.search);
                        for (const [key, value] of savedParams) {
                            if (key !== 'page') {
                                url.searchParams.set(key, value);
                            }
                        }
                    }

                    window.location.href = url.href;
                    return;
                }
            } catch (e) {
                console.log('Error parsing pagination state:', e);
            }
        }

        // Fallback to base route if no valid state
        window.location.href = baseRoute;
    };
    </script>

    <!-- Leaflet JS for Maps -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>

    <!-- Leaflet MarkerCluster JS -->
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <!-- Leaflet Draw JS (for drawing tools) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
    <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>

    <!-- Auto Convert All Select to Custom Dropdown -->
    <!-- TEMPORARILY DISABLED - Uncomment to enable custom dropdowns -->
    <!--
    <script>
    document.addEventListener('alpine:init', function() {
        // Wait for next tick to ensure Alpine is fully initialized
        setTimeout(function() {
        document.querySelectorAll('select').forEach(function(select) {
            // Skip if already converted
            if (select.dataset.converted) return;

            // Skip if explicitly marked to not convert
            if (select.dataset.noCustomDropdown) return;

            // Get select properties
            const name = select.name;
            const id = select.id;
            const required = select.required;
            const disabled = select.disabled;
            const selectedIndex = select.selectedIndex;
            const options = Array.from(select.options);

            // Get current value
            let currentValue = select.value;
            let currentLabel = options[selectedIndex]?.text || options[0]?.text || 'Pilih opsi';

            // Create wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'custom-dropdown';
            wrapper.setAttribute('x-data', JSON.stringify({
                open: false,
                selected: currentValue,
                label: currentLabel
            }));
            // Use setAttribute with x-on:click.outside instead of @click.away
            wrapper.setAttribute('x-on:click.outside', 'open = false');

            // Create button
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'custom-dropdown-button';
            button.setAttribute('x-bind:class', "{ 'active': open }");
            button.setAttribute('x-on:click', 'open = !open');
            button.disabled = disabled;
            button.innerHTML = '<span x-text="label"></span>';

            // Create menu
            const menu = document.createElement('div');
            menu.className = 'custom-dropdown-menu';
            menu.setAttribute('x-show', 'open');
            menu.setAttribute('x-transition:enter', 'dropdown-enter');
            menu.setAttribute('x-transition:leave', 'dropdown-leave');
            menu.style.display = 'none';

            // Add options
            options.forEach(function(option) {
                if (option.value === '' && option.text === '') return;

                const optionDiv = document.createElement('div');
                optionDiv.className = 'custom-dropdown-option';
                optionDiv.textContent = option.text;
                optionDiv.setAttribute('x-on:click',
                    `selected = '${option.value}'; label = '${option.text.replace(/'/g, "\\'")}'; open = false`
                );
                optionDiv.setAttribute('x-bind:class', `{ 'selected': selected === '${option.value}' }`);

                menu.appendChild(optionDiv);
            });

            // Create hidden input
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = name;
            if (id) hidden.id = id;
            hidden.required = required;
            hidden.setAttribute('x-model', 'selected');

            // Copy classes from original select
            if (select.className) {
                const classes = select.className.split(' ').filter(c =>
                    c.includes('error') || c.includes('invalid')
                );
                if (classes.length > 0) {
                    button.className += ' ' + classes.join(' ');
                }
            }

            // Build structure
            wrapper.appendChild(button);
            wrapper.appendChild(menu);
            wrapper.appendChild(hidden);

            // Replace select with wrapper
            select.parentNode.replaceChild(wrapper, select);
            select.dataset.converted = 'true';

            // Reinitialize Alpine for this element
            if (window.Alpine) {
                Alpine.initTree(wrapper);
            }
        });
        }, 100); // Wait 100ms for Alpine to fully initialize
    });
    </script>
    -->

    @stack('scripts')
</body>
</html>