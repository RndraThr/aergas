<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - AERGAS System</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        [x-cloak] { display: none !important; }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="h-full">
    <div class="min-h-full flex" x-data="loginForm()">
        <!-- Left side - Brand/Info -->
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <!-- Logo & Title -->
                <div class="text-center mb-8">
                    <div class="flex items-center justify-center mb-6">
                        <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-fire text-white text-xl"></i>
                        </div>
                        <h1 class="text-3xl font-bold text-gray-900">AERGAS</h1>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Masuk ke Sistem</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        AI Enabled Reporting Gas System
                    </p>
                </div>

                <!-- Login Form -->
                <form @submit.prevent="submitLogin" class="space-y-6">
                    <!-- Username Field -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-2 text-gray-400"></i>Username
                        </label>
                        <input type="text"
                               id="username"
                               name="username"
                               x-model="form.username"
                               required
                               autocomplete="username"
                               :class="errors.username ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'"
                               class="appearance-none block w-full px-3 py-2 border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 sm:text-sm"
                               placeholder="Masukkan username">
                        <p x-show="errors.username" x-text="errors.username" class="mt-1 text-sm text-red-600" x-cloak></p>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-gray-400"></i>Password
                        </label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'"
                                   id="password"
                                   name="password"
                                   x-model="form.password"
                                   required
                                   autocomplete="current-password"
                                   :class="errors.password ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'"
                                   class="appearance-none block w-full px-3 py-2 pr-10 border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 sm:text-sm"
                                   placeholder="Masukkan password">
                            <button type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'" class="h-4 w-4 text-gray-400 hover:text-gray-600"></i>
                            </button>
                        </div>
                        <p x-show="errors.password" x-text="errors.password" class="mt-1 text-sm text-red-600" x-cloak></p>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember"
                                   name="remember"
                                   type="checkbox"
                                   x-model="form.remember"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-700">
                                Ingat saya
                            </label>
                        </div>

                        <div class="text-sm">
                            <a href="#" class="font-medium text-blue-600 hover:text-blue-500">
                                Lupa password?
                            </a>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit"
                                :disabled="loading"
                                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i :class="loading ? 'fas fa-spinner fa-spin' : 'fas fa-sign-in-alt'" class="h-4 w-4 text-blue-500 group-hover:text-blue-400"></i>
                            </span>
                            <span x-text="loading ? 'Memproses...' : 'Masuk'"></span>
                        </button>
                    </div>
                </form>

                <!-- System Info -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="text-center text-xs text-gray-500 space-y-1">
                        <p>AERGAS System v1.0</p>
                        <p>AI Enabled Reporting Gas</p>
                        <p>&copy; 2025 All rights reserved</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right side - Visual/Branding -->
        <div class="hidden lg:block relative w-0 flex-1">
            <div class="absolute inset-0 gradient-bg flex items-center justify-center">
                <div class="text-center text-white p-8">
                    <div class="mb-8">
                        <i class="fas fa-fire text-6xl mb-4 opacity-80"></i>
                        <h3 class="text-3xl font-bold mb-4">AERGAS System</h3>
                        <p class="text-lg opacity-90 mb-6">AI Enabled Reporting Gas</p>
                    </div>

                    <div class="space-y-4 text-left max-w-sm">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <i class="fas fa-robot text-sm"></i>
                            </div>
                            <span>AI Photo Validation</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <i class="fas fa-users text-sm"></i>
                            </div>
                            <span>3-Level Approval System</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <i class="fas fa-chart-line text-sm"></i>
                            </div>
                            <span>Real-time Progress Tracking</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <i class="fas fa-shield-alt text-sm"></i>
                            </div>
                            <span>Role-based Access Control</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div x-show="toast.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         class="fixed top-4 right-4 z-50" x-cloak>
        <div :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'"
             class="text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2">
            <i :class="toast.type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
            <span x-text="toast.message"></span>
        </div>
    </div>

    <script>
        function loginForm() {
            return {
                form: {
                    username: '',
                    password: '',
                    remember: false
                },
                errors: {},
                loading: false,
                showPassword: false,
                toast: {
                    show: false,
                    message: '',
                    type: 'success'
                },

                async submitLogin() {
                    // Reset errors
                    this.errors = {};
                    this.loading = true;

                    try {
                        // Get CSRF token
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                        const response = await fetch('/api/auth/login', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify(this.form)
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Store token if using API
                            if (data.access_token) {
                                localStorage.setItem('auth_token', data.access_token);
                            }

                            this.showToast('Login berhasil! Mengalihkan...', 'success');

                            // Redirect to dashboard after short delay
                            setTimeout(() => {
                                window.location.href = '/dashboard';
                            }, 1000);

                        } else {
                            // Handle validation errors
                            if (data.errors) {
                                this.errors = data.errors;
                            } else {
                                this.showToast(data.message || 'Username atau password salah', 'error');
                            }
                        }

                    } catch (error) {
                        console.error('Login error:', error);
                        this.showToast('Terjadi kesalahan sistem. Silakan coba lagi.', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                showToast(message, type = 'success') {
                    this.toast = {
                        show: true,
                        message: message,
                        type: type
                    };

                    // Auto hide after 3 seconds
                    setTimeout(() => {
                        this.toast.show = false;
                    }, 3000);
                },

                init() {
                    // Auto-focus username field
                    document.getElementById('username').focus();

                    // Handle enter key
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter' && !this.loading) {
                            this.submitLogin();
                        }
                    });
                }
            }
        }

        // Set CSRF token for axios if using
        if (typeof axios !== 'undefined') {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        }
    </script>
</body>
</html>
