@extends('layouts.app')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Profile Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center space-x-6">
            <div class="w-20 h-20 bg-gradient-to-br from-aergas-navy to-aergas-orange rounded-full flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                {{ substr($user->name, 0, 1) }}
            </div>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900">{{ $user->full_name ?: $user->name }}</h1>
                <p class="text-gray-600">{{ $user->username }}</p>
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach($user->getAllActiveRoles() as $role)
                        <span class="px-3 py-1 bg-aergas-orange/10 text-aergas-orange text-xs font-medium rounded-full border border-aergas-orange/20">
                            {{ ucfirst(str_replace('_', ' ', $role)) }}
                        </span>
                    @endforeach
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Last Login</p>
                <p class="text-sm font-medium text-gray-900">
                    {{ $user->last_login ? $user->last_login->format('M d, Y - H:i') : 'Never' }}
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Profile Information -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-900">Profile Information</h2>
                <button type="button"
                        onclick="toggleEditMode()"
                        class="px-4 py-2 bg-aergas-orange text-white text-sm font-medium rounded-lg hover:bg-aergas-orange/90 transition-colors">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Profile
                </button>
            </div>

            <form id="profileForm" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <input type="text"
                           name="full_name"
                           id="full_name"
                           value="{{ $user->full_name ?: $user->name }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent transition-all"
                           disabled>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <input type="text"
                           value="{{ $user->username }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
                           disabled readonly>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email"
                           name="email"
                           id="email"
                           value="{{ $user->email }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent transition-all"
                           disabled>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Account Status</label>
                    <div class="flex items-center">
                        <span class="px-3 py-1 {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} text-sm font-medium rounded-full">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                <div id="editButtons" class="hidden pt-4 border-t border-gray-200">
                    <div class="flex space-x-3">
                        <button type="submit"
                                class="px-4 py-2 bg-aergas-orange text-white text-sm font-medium rounded-lg hover:bg-aergas-orange/90 transition-colors">
                            <i class="fas fa-save mr-2"></i>
                            Save Changes
                        </button>
                        <button type="button"
                                onclick="cancelEdit()"
                                class="px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-400 transition-colors">
                            <i class="fas fa-times mr-2"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Change Password -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">Change Password</h2>

            <form id="passwordForm" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                    <input type="password"
                           name="current_password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent transition-all"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input type="password"
                           name="new_password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent transition-all"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                    <input type="password"
                           name="new_password_confirmation"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent transition-all"
                           required>
                </div>

                <div class="pt-4">
                    <button type="submit"
                            class="w-full px-4 py-2 bg-aergas-navy text-white text-sm font-medium rounded-lg hover:bg-aergas-navy/90 transition-colors">
                        <i class="fas fa-key mr-2"></i>
                        Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Account Information -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Account Information</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-sm font-medium text-gray-500">Member Since</p>
                <p class="text-lg text-gray-900">{{ $user->created_at->format('M d, Y') }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Last Login</p>
                <p class="text-lg text-gray-900">
                    {{ $user->last_login ? $user->last_login->diffForHumans() : 'Never' }}
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Active Roles</p>
                <p class="text-lg text-gray-900">{{ count($user->getAllActiveRoles()) }}</p>
            </div>
        </div>
    </div>
</div>

<script>
let isEditMode = false;
let originalValues = {};

function toggleEditMode() {
    isEditMode = !isEditMode;
    const inputs = document.querySelectorAll('#profileForm input[name]');
    const editButtons = document.getElementById('editButtons');

    if (isEditMode) {
        // Store original values
        inputs.forEach(input => {
            if (!input.readOnly) {
                originalValues[input.name] = input.value;
                input.disabled = false;
            }
        });
        editButtons.classList.remove('hidden');
    } else {
        inputs.forEach(input => {
            if (!input.readOnly) {
                input.disabled = true;
            }
        });
        editButtons.classList.add('hidden');
    }
}

function cancelEdit() {
    // Restore original values
    Object.keys(originalValues).forEach(name => {
        const input = document.querySelector(`#profileForm input[name="${name}"]`);
        if (input) {
            input.value = originalValues[name];
        }
    });

    toggleEditMode();
}

// Profile form submission
document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('{{ route("profile.update") }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showToast('success', result.message);
            toggleEditMode();
            // Update the header name if changed
            if (data.full_name) {
                document.querySelector('h1').textContent = data.full_name;
            }
        } else {
            showToast('error', result.message || 'Failed to update profile');
        }
    } catch (error) {
        console.error('Profile update error:', error);
        showToast('error', 'Network error occurred');
    }
});

// Password form submission
document.getElementById('passwordForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('{{ route("password.change") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showToast('success', result.message);
            this.reset();
        } else {
            showToast('error', result.message || 'Failed to change password');
        }
    } catch (error) {
        console.error('Password change error:', error);
        showToast('error', 'Network error occurred');
    }
});

function showToast(type, message) {
    // Use the global toast function if available
    if (typeof window.showToast === 'function') {
        window.showToast(type, message);
    } else {
        alert(message);
    }
}
</script>
@endsection