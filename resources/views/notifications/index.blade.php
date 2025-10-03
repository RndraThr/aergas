@extends('layouts.app')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header with Stats -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
            <div class="flex items-center space-x-4">
                <button type="button"
                        onclick="markAllAsRead()"
                        class="px-4 py-2 bg-aergas-orange text-white text-sm font-medium rounded-lg hover:bg-aergas-orange/90 transition-colors">
                    <i class="fas fa-check-double mr-2"></i>
                    Mark All Read
                </button>
                <button type="button"
                        onclick="refreshNotifications()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center text-white">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-blue-600">Total</p>
                        <p class="text-2xl font-bold text-blue-900">{{ $stats['total'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-orange-50 rounded-lg p-4 border border-orange-200">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center text-white">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-orange-600">Unread</p>
                        <p class="text-2xl font-bold text-orange-900">{{ $stats['unread'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center text-white">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-green-600">Read</p>
                        <p class="text-2xl font-bold text-green-900">{{ $stats['read'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center text-white">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-purple-600">Recent</p>
                        <p class="text-2xl font-bold text-purple-900">{{ $stats['recent_activity'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <form id="filterForm" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="Search title or message..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent transition-all">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="is_read" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent transition-all">
                    <option value="">All</option>
                    <option value="0" {{ request('is_read') === '0' ? 'selected' : '' }}>Unread</option>
                    <option value="1" {{ request('is_read') === '1' ? 'selected' : '' }}>Read</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent transition-all">
                    <option value="">All Types</option>
                    <!-- Types will be populated by JavaScript -->
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-aergas-orange focus:border-transparent transition-all">
                    <option value="">All Priorities</option>
                    <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                    <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit"
                        class="w-full px-4 py-2 bg-aergas-orange text-white font-medium rounded-lg hover:bg-aergas-orange/90 transition-colors">
                    <i class="fas fa-search mr-2"></i>
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Notifications List -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Your Notifications</h2>
        </div>

        @if($notifications->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($notifications as $notification)
                    <div class="p-6 hover:bg-gray-50 transition-colors {{ $notification->is_read ? '' : 'bg-blue-50/30' }}"
                         data-notification-id="{{ $notification->id }}">
                        <div class="flex items-start space-x-4">
                            <!-- Icon based on priority -->
                            <div class="flex-shrink-0">
                                @switch($notification->priority)
                                    @case('urgent')
                                        <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center text-white">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        @break
                                    @case('high')
                                        <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center text-white">
                                            <i class="fas fa-exclamation-circle"></i>
                                        </div>
                                        @break
                                    @case('medium')
                                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white">
                                            <i class="fas fa-info-circle"></i>
                                        </div>
                                        @break
                                    @default
                                        <div class="w-10 h-10 bg-gray-400 rounded-full flex items-center justify-center text-white">
                                            <i class="fas fa-bell"></i>
                                        </div>
                                @endswitch
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-medium text-gray-900 {{ $notification->is_read ? '' : 'font-semibold' }}">
                                        {{ $notification->title }}
                                    </h3>
                                    <div class="flex items-center space-x-2">
                                        <!-- Type badge -->
                                        <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
                                            {{ ucwords(str_replace('_', ' ', $notification->type)) }}
                                        </span>

                                        <!-- Priority badge -->
                                        @if($notification->priority && $notification->priority !== 'medium')
                                            <span class="px-2 py-1 text-xs rounded-full
                                                {{ $notification->priority === 'urgent' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ $notification->priority === 'high' ? 'bg-orange-100 text-orange-800' : '' }}
                                                {{ $notification->priority === 'low' ? 'bg-gray-100 text-gray-600' : '' }}">
                                                {{ ucfirst($notification->priority) }}
                                            </span>
                                        @endif

                                        <!-- Read status -->
                                        @if(!$notification->is_read)
                                            <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                        @endif
                                    </div>
                                </div>

                                <p class="mt-1 text-sm text-gray-600">
                                    {{ Str::limit($notification->message, 150) }}
                                </p>

                                <div class="mt-2 flex items-center justify-between">
                                    <div class="flex items-center space-x-4 text-xs text-gray-500">
                                        <span>{{ $notification->created_at_human }}</span>
                                        @if($notification->is_read && $notification->read_at_human)
                                            <span>Read {{ $notification->read_at_human }}</span>
                                        @endif
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        @if(!$notification->is_read)
                                            <button type="button"
                                                    onclick="markAsRead({{ $notification->id }})"
                                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                Mark as Read
                                            </button>
                                        @endif
                                        <button type="button"
                                                onclick="deleteNotification({{ $notification->id }})"
                                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="p-6 border-t border-gray-200">
                {{ $notifications->appends(request()->query())->links('vendor.pagination.alpine-style') }}
            </div>
        @else
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-bell-slash text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications found</h3>
                <p class="text-gray-500">You don't have any notifications matching the current filters.</p>
            </div>
        @endif
    </div>
</div>

<script>
// Initialize filters and functionality
document.addEventListener('DOMContentLoaded', function() {
    // Load notification types for filter
    loadNotificationTypes();

    // Set up filter form
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        applyFilters();
    });
});

async function loadNotificationTypes() {
    try {
        const response = await fetch('{{ route("notifications.types") }}', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();
        if (result.success) {
            const typeSelect = document.querySelector('select[name="type"]');
            const currentValue = typeSelect.value;

            // Clear existing options except "All Types"
            while (typeSelect.children.length > 1) {
                typeSelect.removeChild(typeSelect.lastChild);
            }

            // Add new options
            result.data.forEach(type => {
                const option = document.createElement('option');
                option.value = type.value;
                option.textContent = type.label;
                if (type.value === currentValue) {
                    option.selected = true;
                }
                typeSelect.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading notification types:', error);
    }
}

function applyFilters() {
    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams(window.location.search);

    // Update URL params
    for (const [key, value] of formData.entries()) {
        if (value) {
            params.set(key, value);
        } else {
            params.delete(key);
        }
    }

    // Remove page parameter when filtering
    params.delete('page');

    // Reload page with new filters
    window.location.search = params.toString();
}

async function markAsRead(notificationId) {
    try {
        const response = await fetch(`{{ url('/notifications') }}/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });

        const result = await response.json();
        if (result.success) {
            showToast('success', result.message);
            // Remove unread styling and button
            const notificationDiv = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationDiv) {
                notificationDiv.classList.remove('bg-blue-50/30');
                const unreadDot = notificationDiv.querySelector('.bg-blue-500.rounded-full');
                if (unreadDot) unreadDot.remove();
                const markButton = notificationDiv.querySelector('button[onclick*="markAsRead"]');
                if (markButton) markButton.remove();
            }
        } else {
            showToast('error', result.message || 'Failed to mark as read');
        }
    } catch (error) {
        console.error('Error marking as read:', error);
        showToast('error', 'Network error occurred');
    }
}

async function markAllAsRead() {
    if (!confirm('Mark all notifications as read?')) return;

    try {
        const response = await fetch('{{ route("notifications.read-all") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });

        const result = await response.json();
        if (result.success) {
            showToast('success', result.message);
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast('error', result.message || 'Failed to mark all as read');
        }
    } catch (error) {
        console.error('Error marking all as read:', error);
        showToast('error', 'Network error occurred');
    }
}

async function deleteNotification(notificationId) {
    if (!confirm('Delete this notification?')) return;

    try {
        const response = await fetch(`{{ url('/notifications') }}/${notificationId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });

        const result = await response.json();
        if (result.success) {
            showToast('success', result.message);
            // Remove notification from view
            const notificationDiv = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationDiv) {
                notificationDiv.remove();
            }
        } else {
            showToast('error', result.message || 'Failed to delete notification');
        }
    } catch (error) {
        console.error('Error deleting notification:', error);
        showToast('error', 'Network error occurred');
    }
}

function refreshNotifications() {
    window.location.reload();
}

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