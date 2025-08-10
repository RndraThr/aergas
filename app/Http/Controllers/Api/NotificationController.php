<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class NotificationController extends Controller
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of user notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = Notification::where('user_id', $user->id);

            // Apply filters
            if ($request->has('is_read') && $request->is_read !== '') {
                $query->where('is_read', $request->boolean('is_read'));
            }

            if ($request->has('type') && $request->type !== '') {
                $query->where('type', $request->type);
            }

            if ($request->has('priority') && $request->priority !== '') {
                $query->where('priority', $request->priority);
            }

            if ($request->has('date_from') && $request->date_from !== '') {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to !== '') {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $allowedSortFields = ['created_at', 'updated_at', 'priority', 'type', 'is_read'];

            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortDirection);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Pagination
            $perPage = min($request->get('per_page', 20), 50);
            $notifications = $query->paginate($perPage);

            // Add human-readable timestamps
            $notifications->getCollection()->transform(function ($notification) {
                $notification->created_at_human = $notification->created_at->diffForHumans();
                $notification->read_at_human = $notification->read_at ? $notification->read_at->diffForHumans() : null;
                return $notification;
            });

            // Get summary stats
            $stats = $this->notificationService->getUserNotificationStats($user->id);

            return response()->json([
                'success' => true,
                'data' => $notifications,
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching notifications', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching notifications'
            ], 500);
        }
    }

    /**
     * Display the specified notification
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $notification = Notification::where('user_id', $request->user()->id)
                                       ->findOrFail($id);

            // Add human-readable timestamps
            $notification->created_at_human = $notification->created_at->diffForHumans();
            $notification->read_at_human = $notification->read_at ? $notification->read_at->diffForHumans() : null;

            return response()->json([
                'success' => true,
                'data' => $notification
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching notification details', [
                'notification_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }
    }

    /**
     * Mark single notification as read
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        try {
            $result = $this->notificationService->markAsRead($id, $request->user()->id);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            Log::info('Notification marked as read', [
                'notification_id' => $id,
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);

        } catch (Exception $e) {
            Log::error('Error marking notification as read', [
                'notification_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating notification'
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $count = $this->notificationService->markAllAsRead($request->user()->id);

            Log::info('All notifications marked as read', [
                'user_id' => $request->user()->id,
                'count' => $count
            ]);

            return response()->json([
                'success' => true,
                'message' => "Marked {$count} notifications as read",
                'data' => [
                    'marked_count' => $count
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error marking all notifications as read', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating notifications'
            ], 500);
        }
    }

    /**
     * Delete notification
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $notification = Notification::where('user_id', $request->user()->id)
                                       ->findOrFail($id);

            $notification->delete();

            Log::info('Notification deleted', [
                'notification_id' => $id,
                'user_id' => $request->user()->id,
                'notification_type' => $notification->type
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Error deleting notification', [
                'notification_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }
    }

    /**
     * Get notification statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $stats = $this->notificationService->getUserNotificationStats($user->id);

            // Add detailed breakdown
            $detailedStats = [
                'by_type' => Notification::where('user_id', $user->id)
                                        ->selectRaw('type, COUNT(*) as count, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count')
                                        ->groupBy('type')
                                        ->get()
                                        ->keyBy('type')
                                        ->map(function ($item) {
                                            return [
                                                'total' => $item->count,
                                                'unread' => $item->unread_count,
                                                'read' => $item->count - $item->unread_count
                                            ];
                                        })
                                        ->toArray(),
                'by_priority' => Notification::where('user_id', $user->id)
                                             ->selectRaw('priority, COUNT(*) as count, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count')
                                             ->groupBy('priority')
                                             ->get()
                                             ->keyBy('priority')
                                             ->map(function ($item) {
                                                 return [
                                                     'total' => $item->count,
                                                     'unread' => $item->unread_count,
                                                     'read' => $item->count - $item->unread_count
                                                 ];
                                             })
                                             ->toArray(),
                'recent_activity' => Notification::where('user_id', $user->id)
                                                 ->whereDate('created_at', '>=', now()->subDays(7))
                                                 ->count(),
                'oldest_unread' => Notification::where('user_id', $user->id)
                                               ->where('is_read', false)
                                               ->orderBy('created_at')
                                               ->first()
                                               ?->created_at
                                               ?->diffForHumans()
            ];

            return response()->json([
                'success' => true,
                'data' => array_merge($stats, $detailedStats)
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching notification stats', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching notification statistics'
            ], 500);
        }
    }

    /**
     * Create test notification (for development/testing)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createTestNotification(Request $request): JsonResponse
    {
        // Only allow in development environment
        if (config('app.env') !== 'local' && config('app.env') !== 'development') {
            return response()->json([
                'success' => false,
                'message' => 'Test notifications only available in development environment'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'data' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $notification = $this->notificationService->createNotification([
                'user_id' => $request->user()->id,
                'type' => $request->type,
                'title' => $request->title,
                'message' => $request->message,
                'priority' => $request->get('priority', 'medium'),
                'data' => $request->get('data', [])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test notification created',
                'data' => $notification
            ], 201);

        } catch (Exception $e) {
            Log::error('Error creating test notification', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating test notification'
            ], 500);
        }
    }

    /**
     * Bulk delete notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $notificationIds = $request->notification_ids;

            // Delete only user's own notifications
            $deletedCount = Notification::where('user_id', $user->id)
                                       ->whereIn('id', $notificationIds)
                                       ->delete();

            Log::info('Bulk notification deletion', [
                'user_id' => $user->id,
                'requested_count' => count($notificationIds),
                'deleted_count' => $deletedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "Deleted {$deletedCount} notifications",
                'data' => [
                    'deleted_count' => $deletedCount,
                    'requested_count' => count($notificationIds)
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error in bulk notification deletion', [
                'user_id' => $request->user()->id,
                'notification_ids' => $request->notification_ids,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting notifications'
            ], 500);
        }
    }

    /**
     * Mark notifications as read by type
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsReadByType(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $type = $request->type;

            $count = Notification::where('user_id', $user->id)
                                 ->where('type', $type)
                                 ->where('is_read', false)
                                 ->update([
                                     'is_read' => true,
                                     'read_at' => now()
                                 ]);

            Log::info('Notifications marked as read by type', [
                'user_id' => $user->id,
                'type' => $type,
                'count' => $count
            ]);

            return response()->json([
                'success' => true,
                'message' => "Marked {$count} notifications of type '{$type}' as read",
                'data' => [
                    'marked_count' => $count,
                    'type' => $type
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error marking notifications as read by type', [
                'user_id' => $request->user()->id,
                'type' => $request->type,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating notifications'
            ], 500);
        }
    }

    /**
     * Get notification types for current user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getNotificationTypes(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $types = Notification::where('user_id', $user->id)
                                 ->distinct()
                                 ->pluck('type')
                                 ->map(function ($type) {
                                     return [
                                         'value' => $type,
                                         'label' => ucwords(str_replace('_', ' ', $type))
                                     ];
                                 })
                                 ->values();

            return response()->json([
                'success' => true,
                'data' => $types
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching notification types', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching notification types'
            ], 500);
        }
    }
}
