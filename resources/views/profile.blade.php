@extends('layouts.app')

@section('title', 'My Profile')
@section('page-title', 'Profile')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Profile Header -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <div class="flex items-center space-x-6">
            <div class="w-20 h-20 bg-gradient-to-br from-aergas-navy to-aergas-orange rounded-full flex items-center justify-center text-white font-bold text-2xl shadow-lg">
                {{ substr(auth()->user()->name, 0, 1) }}
            </div>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900">{{ auth()->user()->name }}</h1>
                <p class="text-gray-600">{{ auth()->user()->email }}</p>
                <div class="flex items-center space-x-3 mt-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-aergas-orange/10 text-aergas-orange">
                        {{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}
                    </span>
                    @if(auth()->user()->last_login)
                        <span class="text-sm text-gray-500">
                            Last login: {{ auth()->user()->last_login->diffForHumans() }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Information Form -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">Profile Information</h2>
            <span class="text-sm text-gray-500">Update your account's profile information and email address</span>
        </div>

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', auth()->user()->name) }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Full Name -->
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <input type="text"
                           id="full_name"
                           name="full_name"
                           value="{{ old('full_name', auth()->user()->full_name) }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                    @error('full_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <input type="text"
                           id="username"
                           name="username"
                           value="{{ old('username', auth()->user()->username) }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                    @error('username')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email', auth()->user()->email) }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Role (Read-only) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                <div class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-gray-600">
                    {{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}
                    <span class="text-sm text-gray-500 ml-2">(Cannot be changed)</span>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-aergas-navy to-aergas-orange text-white rounded-lg hover:shadow-lg transition-all duration-300 font-medium">
                    Update Profile
                </button>
            </div>
        </form>
    </div>

    <!-- Change Password Form -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">Update Password</h2>
            <span class="text-sm text-gray-500">Ensure your account is using a long, random password to stay secure</span>
        </div>

        <form method="POST" action="{{ route('password.change') }}" class="space-y-6">
            @csrf

            <div class="space-y-4">
                <!-- Current Password -->
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                    <input type="password"
                           id="current_password"
                           name="current_password"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                    @error('current_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- New Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input type="password"
                           id="password"
                           name="password"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">Password must be at least 8 characters long</p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                    <input type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-aergas-navy to-aergas-orange text-white rounded-lg hover:shadow-lg transition-all duration-300 font-medium">
                    Update Password
                </button>
            </div>
        </form>
    </div>

    <!-- Account Information -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Account Information</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div>
                    <span class="text-sm font-medium text-gray-500">Account Created</span>
                    <p class="text-gray-900">{{ auth()->user()->created_at->format('F j, Y') }}</p>
                </div>

                <div>
                    <span class="text-sm font-medium text-gray-500">Account Status</span>
                    <div class="flex items-center space-x-2">
                        @if(auth()->user()->is_active)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i> Active
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i> Inactive
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <span class="text-sm font-medium text-gray-500">Last Login</span>
                    <p class="text-gray-900">
                        @if(auth()->user()->last_login)
                            {{ auth()->user()->last_login->format('F j, Y \a\t g:i A') }}
                        @else
                            Never
                        @endif
                    </p>
                </div>

                <div>
                    <span class="text-sm font-medium text-gray-500">Profile Updated</span>
                    <p class="text-gray-900">{{ auth()->user()->updated_at->format('F j, Y \a\t g:i A') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Quick Actions</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('dashboard') }}"
               class="flex flex-col items-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg hover:from-blue-100 hover:to-blue-200 transition-all duration-300 group">
                <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="fas fa-chart-pie text-white text-lg"></i>
                </div>
                <span class="text-sm font-medium text-gray-900">Dashboard</span>
            </a>

            <a href="{{ route('customers.index') }}"
               class="flex flex-col items-center p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-lg hover:from-green-100 hover:to-green-200 transition-all duration-300 group">
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="fas fa-users text-white text-lg"></i>
                </div>
                <span class="text-sm font-medium text-gray-900">Customers</span>
            </a>

            @if(in_array(auth()->user()->role, ['tracer', 'admin']))
            <a href="{{ route('photos.index') }}"
               class="flex flex-col items-center p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg hover:from-purple-100 hover:to-purple-200 transition-all duration-300 group">
                <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="fas fa-clipboard-check text-white text-lg"></i>
                </div>
                <span class="text-sm font-medium text-gray-900">Photo Review</span>
            </a>
            @endif

            <a href="{{ route('notifications.index') }}"
               class="flex flex-col items-center p-4 bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg hover:from-orange-100 hover:to-orange-200 transition-all duration-300 group">
                <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                    <i class="fas fa-bell text-white text-lg"></i>
                </div>
                <span class="text-sm font-medium text-gray-900">Notifications</span>
            </a>
        </div>
    </div>
</div>
@endsection
