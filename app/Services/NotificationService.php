<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\CalonPelanggan;
use Illuminate\Support\Facades\Log;
use Exception;

class NotificationService
{
    /**
     * Create a new notification
     *
     * @param array $data
     * @return Notification
     */
    public function createNotification(array $data): Notification
    {
        try {
            return Notification::create([
                'user_id' => $data['user_id'],
                'type' => $data['type'],
                'title' => $data['title'],
                'message' => $data['message'],
                'data' => $data['data'] ?? null,
                'priority' => $data['priority'] ?? 'medium'
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create notification', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Notify tracers about pending photo review
     *
     * @param string $reffId
     * @param string $module
     * @return void
     */
    public function notifyTracerPhotoPending(string $reffId, string $module): void
    {
        try {
            $tracers = User::where('role', 'tracer')
                          ->where('is_active', true)
                          ->get();

            $customer = CalonPelanggan::find($reffId);

            if (!$customer) {
                Log::warning("Customer not found for notification: {$reffId}");
                return;
            }

            foreach ($tracers as $tracer) {
                $this->createNotification([
                    'user_id' => $tracer->id,
                    'type' => 'photo_pending',
                    'title' => 'New Photos Pending Review',
                    'message' => "Photos from customer {$customer->nama_pelanggan} ({$reffId}) in module " . strtoupper($module) . " are ready for your review",
                    'data' => [
                        'reff_id_pelanggan' => $reffId,
                        'module' => $module,
                        'customer_name' => $customer->nama_pelanggan,
                        'url' => "/tracer/photo-review/{$reffId}/{$module}"
                    ],
                    'priority' => 'medium'
                ]);
            }

            Log::info('Tracer photo pending notifications sent', [
                'reff_id' => $reffId,
                'module' => $module,
                'tracers_count' => $tracers->count()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to notify tracers about pending photos', [
                'reff_id' => $reffId,
                'module' => $module,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify admins about CGP review needed
     *
     * @param string $reffId
     * @param string $module
     * @return void
     */
    public function notifyAdminCgpReview(string $reffId, string $module): void
    {
        try {
            $admins = User::where('role', 'admin')
                         ->where('is_active', true)
                         ->get();

            $customer = CalonPelanggan::find($reffId);

            if (!$customer) {
                Log::warning("Customer not found for CGP notification: {$reffId}");
                return;
            }

            foreach ($admins as $admin) {
                $this->createNotification([
                    'user_id' => $admin->id,
                    'type' => 'cgp_review_pending',
                    'title' => 'CGP Review Required',
                    'message' => "Photos from {$customer->nama_pelanggan} ({$reffId}) approved by Tracer, ready for CGP review",
                    'data' => [
                        'reff_id_pelanggan' => $reffId,
                        'module' => $module,
                        'customer_name' => $customer->nama_pelanggan,
                        'url' => "/admin/cgp-review/{$reffId}/{$module}"
                    ],
                    'priority' => 'high'
                ]);
            }

            Log::info('CGP review notifications sent', [
                'reff_id' => $reffId,
                'module' => $module,
                'admins_count' => $admins->count()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to notify admins about CGP review', [
                'reff_id' => $reffId,
                'module' => $module,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify field users about photo rejection
     *
     * @param string $reffId
     * @param string $module
     * @param string $photoField
     * @param string $reason
     * @return void
     */
    public function notifyPhotoRejection(string $reffId, string $module, string $photoField, string $reason): void
    {
        try {
            // Determine which users should be notified based on module
            $roleMapping = [
                'sk' => 'sk',
                'sr' => 'sr',
                'mgrt' => 'mgrt',
                'gas_in' => 'gas_in',
                'jalur_pipa' => 'pic',
                'penyambungan' => 'pic'
            ];

            $targetRole = $roleMapping[$module] ?? 'tracer';
            $users = User::where('role', $targetRole)
                        ->where('is_active', true)
                        ->get();

            $customer = CalonPelanggan::find($reffId);

            if (!$customer) {
                Log::warning("Customer not found for rejection notification: {$reffId}");
                return;
            }

            foreach ($users as $user) {
                $this->createNotification([
                    'user_id' => $user->id,
                    'type' => 'photo_rejected',
                    'title' => 'Photo Rejected - Action Required',
                    'message' => "Your photo submission for {$customer->nama_pelanggan} ({$reffId}) has been rejected. Photo: {$photoField}. Reason: {$reason}",
                    'data' => [
                        'reff_id_pelanggan' => $reffId,
                        'module' => $module,
                        'photo_field' => $photoField,
                        'rejection_reason' => $reason,
                        'url' => "/field/{$module}/edit/{$reffId}"
                    ],
                    'priority' => 'high'
                ]);
            }

            Log::info('Photo rejection notifications sent', [
                'reff_id' => $reffId,
                'module' => $module,
                'photo_field' => $photoField,
                'target_role' => $targetRole,
                'users_count' => $users->count()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to notify users about photo rejection', [
                'reff_id' => $reffId,
                'module' => $module,
                'photo_field' => $photoField,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify about module completion
     *
     * @param string $reffId
     * @param string $module
     * @return void
     */
    public function notifyModuleCompletion(string $reffId, string $module): void
    {
        try {
            $customer = CalonPelanggan::find($reffId);

            if (!$customer) {
                Log::warning("Customer not found for completion notification: {$reffId}");
                return;
            }

            // Notify field users about completion
            $this->notifyPhotoApproved($reffId, $module, 'all_photos');

            // Notify management
            $admins = User::whereIn('role', ['admin', 'super_admin'])
                         ->where('is_active', true)
                         ->get();

            foreach ($admins as $admin) {
                $this->createNotification([
                    'user_id' => $admin->id,
                    'type' => 'module_completed',
                    'title' => 'Module Completed',
                    'message' => "Module " . strtoupper($module) . " for {$customer->nama_pelanggan} ({$reffId}) has been completed successfully",
                    'data' => [
                        'reff_id_pelanggan' => $reffId,
                        'module' => $module,
                        'customer_name' => $customer->nama_pelanggan,
                        'completion_date' => now()->toDateString()
                    ],
                    'priority' => 'low'
                ]);
            }

            Log::info('Module completion notifications sent', [
                'reff_id' => $reffId,
                'module' => $module,
                'admins_count' => $admins->count()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to notify about module completion', [
                'reff_id' => $reffId,
                'module' => $module,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify field users about photo approval
     *
     * @param string $reffId
     * @param string $module
     * @param string $photoField
     * @return void
     */
    public function notifyPhotoApproved(string $reffId, string $module, string $photoField): void
    {
        try {
            $roleMapping = [
                'sk' => 'sk',
                'sr' => 'sr',
                'mgrt' => 'mgrt',
                'gas_in' => 'gas_in',
                'jalur_pipa' => 'pic',
                'penyambungan' => 'pic'
            ];

            $targetRole = $roleMapping[$module] ?? 'tracer';
            $users = User::where('role', $targetRole)
                        ->where('is_active', true)
                        ->get();

            $customer = CalonPelanggan::find($reffId);

            if (!$customer) {
                Log::warning("Customer not found for approval notification: {$reffId}");
                return;
            }

            $messageText = $photoField === 'all_photos'
                ? "Congratulations! Your work on {$customer->nama_pelanggan} ({$reffId}) for module " . strtoupper($module) . " has been fully approved"
                : "Photo {$photoField} for {$customer->nama_pelanggan} ({$reffId}) has been approved";

            foreach ($users as $user) {
                $this->createNotification([
                    'user_id' => $user->id,
                    'type' => $photoField === 'all_photos' ? 'module_completed' : 'photo_approved',
                    'title' => $photoField === 'all_photos' ? 'Module Approved' : 'Photo Approved',
                    'message' => $messageText,
                    'data' => [
                        'reff_id_pelanggan' => $reffId,
                        'module' => $module,
                        'photo_field' => $photoField,
                        'customer_name' => $customer->nama_pelanggan
                    ],
                    'priority' => 'low'
                ]);
            }

            Log::info('Photo approval notifications sent', [
                'reff_id' => $reffId,
                'module' => $module,
                'photo_field' => $photoField,
                'target_role' => $targetRole,
                'users_count' => $users->count()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to notify about photo approval', [
                'reff_id' => $reffId,
                'module' => $module,
                'photo_field' => $photoField,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function notifyNewCustomerRegistration(CalonPelanggan $customer): void
    {
        try {
            $tracers = User::where('role', 'tracer')
                        ->where('is_active', true)
                        ->get();

            foreach ($tracers as $tracer) {
                $this->createNotification([
                    'user_id' => $tracer->id,
                    'type' => 'new_customer',
                    'title' => 'New Customer Registration',
                    'message' => "New customer {$customer->nama_pelanggan} ({$customer->reff_id_pelanggan}) has been registered and needs validation",
                    'data' => [
                        'reff_id_pelanggan' => $customer->reff_id_pelanggan,
                        'customer_name' => $customer->nama_pelanggan,
                        'registration_date' => $customer->tanggal_registrasi,
                        'url' => "/customers/{$customer->reff_id_pelanggan}"
                    ],
                    'priority' => 'medium'
                ]);
            }

            Log::info('New customer registration notifications sent', [
                'reff_id' => $customer->reff_id_pelanggan,
                'tracers_count' => $tracers->count()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to notify about new customer registration', [
                'reff_id' => $customer->reff_id_pelanggan,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send SLA warning notification
     *
     * @param string $type
     * @param string $reffId
     * @param string $photoField
     * @param int $hoursPending
     * @param int $slaLimit
     * @return void
     */
    public function sendSlaWarning(string $type, string $reffId, string $photoField, int $hoursPending, int $slaLimit): void
    {
        try {
            $targetRole = $type === 'tracer' ? 'tracer' : 'admin';
            $users = User::where('role', $targetRole)
                        ->where('is_active', true)
                        ->get();

            $customer = CalonPelanggan::find($reffId);

            if (!$customer) {
                Log::warning("Customer not found for SLA warning: {$reffId}");
                return;
            }

            foreach ($users as $user) {
                $this->createNotification([
                    'user_id' => $user->id,
                    'type' => 'sla_warning',
                    'title' => 'SLA Warning - ' . strtoupper($type) . ' Review',
                    'message' => "Photo {$photoField} from {$customer->nama_pelanggan} ({$reffId}) has been pending for {$hoursPending} hours (SLA: {$slaLimit}h)",
                    'data' => [
                        'reff_id_pelanggan' => $reffId,
                        'photo_field' => $photoField,
                        'hours_pending' => $hoursPending,
                        'sla_limit' => $slaLimit,
                        'review_type' => $type
                    ],
                    'priority' => 'urgent'
                ]);
            }

            Log::info('SLA warning notifications sent', [
                'type' => $type,
                'reff_id' => $reffId,
                'hours_pending' => $hoursPending,
                'users_count' => $users->count()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send SLA warning notifications', [
                'type' => $type,
                'reff_id' => $reffId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Mark notification as read
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        try {
            $notification = Notification::where('id', $notificationId)
                                       ->where('user_id', $userId)
                                       ->first();

            if (!$notification) {
                return false;
            }

            $notification->markAsRead();
            return true;

        } catch (Exception $e) {
            Log::error('Failed to mark notification as read', [
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Mark all notifications as read for user
     *
     * @param int $userId
     * @return int
     */
    public function markAllAsRead(int $userId): int
    {
        try {
            $count = Notification::where('user_id', $userId)
                                ->where('is_read', false)
                                ->update([
                                    'is_read' => true,
                                    'read_at' => now()
                                ]);

            Log::info('All notifications marked as read', [
                'user_id' => $userId,
                'count' => $count
            ]);

            return $count;

        } catch (Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get notification statistics for user
     *
     * @param int $userId
     * @return array
     */
    public function getUserNotificationStats(int $userId): array
    {
        try {
            $stats = [
                'total' => Notification::where('user_id', $userId)->count(),
                'unread' => Notification::where('user_id', $userId)->where('is_read', false)->count(),
                'high_priority' => Notification::where('user_id', $userId)
                                                ->where('is_read', false)
                                                ->where('priority', 'high')
                                                ->count(),
                'urgent' => Notification::where('user_id', $userId)
                                       ->where('is_read', false)
                                       ->where('priority', 'urgent')
                                       ->count(),
            ];

            $stats['read'] = $stats['total'] - $stats['unread'];

            return $stats;

        } catch (Exception $e) {
            Log::error('Failed to get user notification stats', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'total' => 0,
                'unread' => 0,
                'read' => 0,
                'high_priority' => 0,
                'urgent' => 0
            ];
        }
    }
}
