<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class NotificationController extends Controller implements HasMiddleware
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // Proteksi semua action (Laravel 11+)
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:super_admin,admin,sk,sr,gas_in,validasi,tracer,mgrt,pic'),
        ];
    }

    /**
     * List notifikasi user (filter + pagination)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = Notification::where('user_id', $user->id);

            // Optional: quick search
            if ($request->filled('q')) {
                $q = $request->string('q');
                $query->where(function ($qq) use ($q) {
                    $qq->where('title', 'like', '%'.$q.'%')
                       ->orWhere('message', 'like', '%'.$q.'%');
                });
            }

            // Filters
            if ($request->has('is_read') && $request->is_read !== '') {
                $query->where('is_read', $request->boolean('is_read'));
            }
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }
            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Sorting (sanitasi)
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = strtolower($request->get('sort_direction', 'desc'));
            $sortDirection = in_array($sortDirection, ['asc','desc'], true) ? $sortDirection : 'desc';
            $allowedSortFields = ['created_at', 'updated_at', 'priority', 'type', 'is_read'];

            if (in_array($sortBy, $allowedSortFields, true)) {
                $query->orderBy($sortBy, $sortDirection);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Pagination (cast integer)
            $perPage = (int) min((int) $request->get('per_page', 20), 50);
            $notifications = $query->paginate($perPage);

            // Human-readable timestamps
            $notifications->getCollection()->transform(function ($n) {
                $n->created_at_human = $n->created_at?->diffForHumans();
                $n->read_at_human = $n->read_at ? $n->read_at->diffForHumans() : null;
                return $n;
            });

            // Summary
            $stats = $this->notificationService->getUserNotificationStats($user->id);

            return response()->json([
                'success' => true,
                'data' => $notifications,
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching notifications', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil notifikasi'
            ], 500);
        }
    }

    /**
     * Detail notifikasi
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $notification = Notification::where('user_id', $request->user()->id)
                                       ->findOrFail($id);

            $notification->created_at_human = $notification->created_at?->diffForHumans();
            $notification->read_at_human = $notification->read_at ? $notification->read_at->diffForHumans() : null;

            return response()->json([
                'success' => true,
                'data' => $notification
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching notification details', [
                'notification_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Tandai 1 notifikasi sebagai dibaca
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        try {
            $result = $this->notificationService->markAsRead($id, $request->user()->id);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notifikasi tidak ditemukan'
                ], 404);
            }

            Log::info('Notification marked as read', [
                'notification_id' => $id,
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notifikasi ditandai sebagai dibaca'
            ]);

        } catch (Exception $e) {
            Log::error('Error marking notification as read', [
                'notification_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengubah notifikasi'
            ], 500);
        }
    }

    /**
     * Tandai semua notifikasi sebagai dibaca
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
                'message' => "Berhasil menandai {$count} notifikasi sebagai dibaca",
                'data' => ['marked_count' => $count]
            ]);

        } catch (Exception $e) {
            Log::error('Error marking all notifications as read', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengubah notifikasi'
            ], 500);
        }
    }

    /**
     * Hapus 1 notifikasi
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
                'message' => 'Notifikasi berhasil dihapus'
            ]);

        } catch (Exception $e) {
            Log::error('Error deleting notification', [
                'notification_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Statistik notifikasi user
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $stats = $this->notificationService->getUserNotificationStats($user->id);

            $detailedStats = [
                'by_type' => Notification::where('user_id', $user->id)
                    ->selectRaw('type, COUNT(*) as count, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count')
                    ->groupBy('type')
                    ->get()
                    ->keyBy('type')
                    ->map(fn($i) => [
                        'total' => (int) $i->count,
                        'unread' => (int) $i->unread_count,
                        'read' => (int) $i->count - (int) $i->unread_count
                    ])->toArray(),
                'by_priority' => Notification::where('user_id', $user->id)
                    ->selectRaw('priority, COUNT(*) as count, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count')
                    ->groupBy('priority')
                    ->get()
                    ->keyBy('priority')
                    ->map(fn($i) => [
                        'total' => (int) $i->count,
                        'unread' => (int) $i->unread_count,
                        'read' => (int) $i->count - (int) $i->unread_count
                    ])->toArray(),
                'recent_activity' => Notification::where('user_id', $user->id)
                    ->whereDate('created_at', '>=', now()->subDays(7))
                    ->count(),
                'oldest_unread' => Notification::where('user_id', $user->id)
                    ->where('is_read', false)
                    ->orderBy('created_at')
                    ->value('created_at')?->diffForHumans()
            ];

            return response()->json([
                'success' => true,
                'data' => array_merge($stats, $detailedStats)
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching notification stats', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil statistik notifikasi'
            ], 500);
        }
    }

    /**
     * Buat notifikasi dummy (dev only)
     */
    public function createTestNotification(Request $request): JsonResponse
    {
        if (!in_array(config('app.env'), ['local','development'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya tersedia pada environment development'
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
                'message' => 'Validasi gagal',
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
                'message' => 'Notifikasi uji berhasil dibuat',
                'data' => $notification
            ], 201);

        } catch (Exception $e) {
            Log::error('Error creating test notification', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat notifikasi uji'
            ], 500);
        }
    }

    /**
     * Bulk delete notifikasi
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
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $ids = $request->notification_ids;

            $deletedCount = Notification::where('user_id', $user->id)
                ->whereIn('id', $ids)
                ->delete();

            Log::info('Bulk notification deletion', [
                'user_id' => $user->id,
                'requested_count' => count($ids),
                'deleted_count' => $deletedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "Berhasil menghapus {$deletedCount} notifikasi",
                'data' => [
                    'deleted_count' => $deletedCount,
                    'requested_count' => count($ids)
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error in bulk notification deletion', [
                'user_id' => $request->user()?->id,
                'notification_ids' => $request->notification_ids,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus notifikasi'
            ], 500);
        }
    }

    /**
     * Bulk mark-as-read by IDs (tambahan opsional)
     */
    public function bulkMarkAsRead(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $ids = $request->notification_ids;

            $count = Notification::where('user_id', $user->id)
                ->whereIn('id', $ids)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            Log::info('Bulk mark-as-read', [
                'user_id' => $user->id,
                'count' => $count
            ]);

            return response()->json([
                'success' => true,
                'message' => "Berhasil menandai {$count} notifikasi sebagai dibaca",
                'data' => ['marked_count' => $count]
            ]);

        } catch (Exception $e) {
            Log::error('Error bulk mark-as-read', [
                'user_id' => $request->user()?->id,
                'notification_ids' => $request->notification_ids,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengubah notifikasi'
            ], 500);
        }
    }

    /**
     * Dapatkan daftar tipe notifikasi (untuk filter dropdown)
     */
    public function getNotificationTypes(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $types = Notification::where('user_id', $user->id)
                ->distinct()
                ->pluck('type')
                ->map(fn($t) => [
                    'value' => $t,
                    'label' => ucwords(str_replace('_', ' ', $t))
                ])->values();

            return response()->json([
                'success' => true,
                'data' => $types
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching notification types', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil tipe notifikasi'
            ], 500);
        }
    }
}
