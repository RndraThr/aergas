@extends('layouts.app')

@section('title', 'User Management')
@section('page-title', 'User Management')

@section('content')
<div class="space-y-6" x-data="userManagement()">

    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center space-y-4 lg:space-y-0">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
            <p class="text-gray-600 mt-1">Manage system users and their permissions</p>
        </div>
        <div class="flex items-center space-x-3">
            <button @click="refreshUsers()"
                    :disabled="loading"
                    class="flex items-center space-x-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all duration-300 disabled:opacity-50">
                <i class="fas fa-sync-alt" :class="{ 'animate-spin': loading }"></i>
                <span>Refresh</span>
            </button>
            <button @click="openCreateModal()"
                    class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-aergas-navy to-aergas-orange text-white rounded-lg hover:shadow-lg transition-all duration-300">
                <i class="fas fa-plus"></i>
                <span>Add User</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 text-lg"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900" x-text="stats.total || 0">0</div>
                    <div class="text-sm text-gray-600">Total Users</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-lg"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900" x-text="stats.active || 0">0</div>
                    <div class="text-sm text-gray-600">Active Users</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-times-circle text-red-600 text-lg"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900" x-text="stats.inactive || 0">0</div>
                    <div class="text-sm text-gray-600">Inactive Users</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clock text-purple-600 text-lg"></i>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900" x-text="stats.recent_logins || 0">0</div>
                    <div class="text-sm text-gray-600">Recent Logins</div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text"
                       x-model="filters.search"
                       @input.debounce.500ms="loadUsers()"
                       placeholder="Search by name, username, email..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                <select x-model="filters.role" @change="loadUsers()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                    <option value="">All Roles</option>
                    <option value="super_admin">Super Admin</option>
                    <option value="admin">Admin</option>
                    <option value="sk">SK</option>
                    <option value="sr">SR</option>
                    <option value="gas_in">Gas In</option>
                    <option value="tracer">Tracer</option>
                    <option value="pic">PIC</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select x-model="filters.is_active" @change="loadUsers()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Per Page</label>
                <select x-model="filters.per_page" @change="loadUsers()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent">
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="user in users" :key="user.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-aergas-navy to-aergas-orange rounded-full flex items-center justify-center text-white font-medium text-sm"
                                         x-text="user.name ? user.name.charAt(0).toUpperCase() : 'U'">
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900" x-text="user.full_name || user.name">Name</div>
                                        <div class="text-sm text-gray-500" x-text="user.username">Username</div>
                                        <div class="text-sm text-gray-500" x-text="user.email">Email</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                      :class="getRoleBadgeClass(user.role)"
                                      x-text="user.role ? user.role.replace('_', ' ').toUpperCase() : 'Unknown'">
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                      :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                    <span x-text="user.is_active ? 'Active' : 'Inactive'"></span>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="user.last_login_human || 'Never'">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="user.created_at_human">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <button @click="editUser(user)"
                                            class="text-blue-600 hover:text-blue-900 p-1 rounded">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button @click="toggleUserStatus(user)"
                                            :class="user.is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'"
                                            class="p-1 rounded">
                                        <i :class="user.is_active ? 'fas fa-ban' : 'fas fa-check'"></i>
                                    </button>
                                    <button @click="deleteUser(user)"
                                            x-show="user.role !== 'super_admin' && user.id !== {{ auth()->id() }}"
                                            class="text-red-600 hover:text-red-900 p-1 rounded">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="flex-1 flex justify-between sm:hidden">
                <button @click="changePage(pagination.current_page - 1)"
                        :disabled="pagination.current_page <= 1"
                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
                    Previous
                </button>
                <button @click="changePage(pagination.current_page + 1)"
                        :disabled="pagination.current_page >= pagination.last_page"
                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
                    Next
                </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium" x-text="pagination.from || 0"></span> to
                        <span class="font-medium" x-text="pagination.to || 0"></span> of
                        <span class="font-medium" x-text="pagination.total || 0"></span> results
                    </p>
                </div>
                <div class="flex space-x-1">
                    <button @click="changePage(pagination.current_page - 1)"
                            :disabled="pagination.current_page <= 1"
                            class="px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <template x-for="page in getPageNumbers()" :key="page">
                        <button @click="changePage(page)"
                                :class="page === pagination.current_page ? 'bg-aergas-orange text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                                class="px-3 py-2 text-sm border border-gray-300 rounded-md"
                                x-text="page">
                        </button>
                    </template>
                    <button @click="changePage(pagination.current_page + 1)"
                            :disabled="pagination.current_page >= pagination.last_page"
                            class="px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="saveUser()">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900" x-text="editingUser ? 'Edit User' : 'Create New User'"></h3>
                            <p class="text-sm text-gray-500">Fill in the user information below</p>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                    <input type="text"
                                           x-model="form.name"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent"
                                           required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                    <input type="text"
                                           x-model="form.full_name"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent"
                                           required>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                                <input type="text"
                                       x-model="form.username"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent"
                                       required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email"
                                       x-model="form.email"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent"
                                       required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                <select x-model="form.role"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent"
                                        required>
                                    <option value="">Select Role</option>
                                    <option value="admin">Admin</option>
                                    <option value="sk">SK</option>
                                    <option value="sr">SR</option>
                                    <option value="gas_in">Gas In</option>
                                    <option value="tracer">Tracer</option>
                                    <option value="pic">PIC</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                    <input type="password"
                                           x-model="form.password"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent"
                                           :required="!editingUser">
                                    <p class="text-xs text-gray-500 mt-1" x-show="editingUser">Leave blank to keep current password</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                                    <input type="password"
                                           x-model="form.password_confirmation"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent"
                                           :required="form.password.length > 0">
                                </div>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox"
                                       x-model="form.is_active"
                                       class="h-4 w-4 text-aergas-orange focus:ring-aergas-orange border-gray-300 rounded">
                                <label class="ml-2 block text-sm text-gray-700">Active User</label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                                :disabled="submitting"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-aergas-orange text-base font-medium text-white hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-aergas-orange sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                            <i class="fas fa-spinner animate-spin mr-2" x-show="submitting"></i>
                            <span x-text="submitting ? 'Saving...' : (editingUser ? 'Update' : 'Create')"></span>
                        </button>
                        <button type="button"
                                @click="closeModal()"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function userManagement() {
    return {
        users: [],
        stats: {},
        pagination: {},
        loading: false,
        submitting: false,
        showModal: false,
        editingUser: null,

        filters: {
            search: '',
            role: '',
            is_active: '',
            per_page: 15,
            page: 1
        },

        form: {
            name: '',
            full_name: '',
            username: '',
            email: '',
            password: '',
            password_confirmation: '',
            role: '',
            is_active: true
        },

        init() {
            this.loadUsers();
        },

        async loadUsers() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    ...this.filters,
                    page: this.filters.page
                });

                const response = await fetch(`{{ route('admin.api.users') }}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    }
                });

                const result = await response.json();
                if (result.success) {
                    this.users = result.data.data || [];
                    this.pagination = {
                        current_page: result.data.current_page,
                        last_page: result.data.last_page,
                        per_page: result.data.per_page,
                        total: result.data.total,
                        from: result.data.from,
                        to: result.data.to
                    };
                    this.stats = result.stats || {};
                }
            } catch (error) {
                console.error('Error loading users:', error);
                window.showToast('error', 'Failed to load users');
            } finally {
                this.loading = false;
            }
        },

        async refreshUsers() {
            this.filters.page = 1;
            await this.loadUsers();
            window.showToast('success', 'Users refreshed');
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.filters.page = page;
                this.loadUsers();
            }
        },

        getPageNumbers() {
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;
            const pages = [];

            for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
                pages.push(i);
            }

            return pages;
        },

        openCreateModal() {
            this.resetForm();
            this.editingUser = null;
            this.showModal = true;
        },

        editUser(user) {
            this.editingUser = user;
            this.form = {
                name: user.name,
                full_name: user.full_name,
                username: user.username,
                email: user.email,
                password: '',
                password_confirmation: '',
                role: user.role,
                is_active: user.is_active
            };
            this.showModal = true;
        },

        async saveUser() {
            this.submitting = true;
            try {
                const url = this.editingUser
                    ? `{{ route('admin.api.users.update', ':id') }}`.replace(':id', this.editingUser.id)
                    : '{{ route('admin.api.users.create') }}';

                const method = this.editingUser ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken
                    },
                    body: JSON.stringify(this.form)
                });

                const result = await response.json();

                if (result.success) {
                    window.showToast('success', result.message);
                    this.closeModal();
                    this.loadUsers();
                } else {
                    if (result.errors) {
                        const firstError = Object.values(result.errors)[0][0];
                        window.showToast('error', firstError);
                    } else {
                        window.showToast('error', result.message || 'An error occurred');
                    }
                }
            } catch (error) {
                console.error('Error saving user:', error);
                window.showToast('error', 'Failed to save user');
            } finally {
                this.submitting = false;
            }
        },

        async toggleUserStatus(user) {
            if (confirm(`Are you sure you want to ${user.is_active ? 'deactivate' : 'activate'} this user?`)) {
                try {
                    const response = await fetch(`{{ route('admin.api.users.toggle', ':id') }}`.replace(':id', user.id), {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.showToast('success', result.message);
                        this.loadUsers();
                    } else {
                        window.showToast('error', result.message || 'Failed to update user status');
                    }
                } catch (error) {
                    console.error('Error toggling user status:', error);
                    window.showToast('error', 'Failed to update user status');
                }
            }
        },

        async deleteUser(user) {
            if (confirm(`Are you sure you want to delete user "${user.username}"? This action cannot be undone.`)) {
                try {
                    const response = await fetch(`{{ route('admin.api.users.delete', ':id') }}`.replace(':id', user.id), {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.showToast('success', result.message);
                        this.loadUsers();
                    } else {
                        window.showToast('error', result.message || 'Failed to delete user');
                    }
                } catch (error) {
                    console.error('Error deleting user:', error);
                    window.showToast('error', 'Failed to delete user');
                }
            }
        },

        closeModal() {
            this.showModal = false;
            this.editingUser = null;
            this.resetForm();
        },

        resetForm() {
            this.form = {
                name: '',
                full_name: '',
                username: '',
                email: '',
                password: '',
                password_confirmation: '',
                role: '',
                is_active: true
            };
        },

        getRoleBadgeClass(role) {
            const roleClasses = {
                'super_admin': 'bg-purple-100 text-purple-800',
                'admin': 'bg-blue-100 text-blue-800',
                'sk': 'bg-green-100 text-green-800',
                'sr': 'bg-yellow-100 text-yellow-800',
                'gas_in': 'bg-orange-100 text-orange-800',
                'tracer': 'bg-indigo-100 text-indigo-800',
                'pic': 'bg-pink-100 text-pink-800'
            };
            return roleClasses[role] || 'bg-gray-100 text-gray-800';
        }
    }
}
</script>
@endpush
@endsection
