<?php

/**
 * =============================================================================
 * SERVICE: PhotoApprovalService.php
 * Location: app/Services/PhotoApprovalService.php
 * =============================================================================
 */
namespace App\Services;

use App\Models\PhotoApproval;
use App\Models\CalonPelanggan;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\TelegramService;
use App\Services\OpenAIService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Exception;

class PhotoApprovalService
{
    private TelegramService $telegramService;
    private OpenAIService $openAIService;
    private NotificationService $notificationService;

    public function __construct(
        TelegramService $telegramService,
        OpenAIService $openAIService,
        NotificationService $notificationService
    ) {
        $this->telegramService = $telegramService;
        $this->openAIService = $openAIService;
        $this->notificationService = $notificationService;
    }

    /**
     * Process AI validation for uploaded photo
     *
     * @param string $reffId
     * @param string $module
     * @param string $photoField
     * @param string $photoUrl
     * @param int|null $uploadedBy
     * @return PhotoApproval
     * @throws Exception
     */
    public function processAIValidation(
        string $reffId,
        string $module,
        string $photoField,
        string $photoUrl,
        ?int $uploadedBy = null
    ): PhotoApproval {
        DB::beginTransaction();

        try {
            Log::info('Starting AI validation process', [
                'reff_id' => $reffId,
                'module' => $module,
                'photo_field' => $photoField,
                'uploaded_by' => $uploadedBy
            ]);

            // Verify customer exists
            $customer = CalonPelanggan::find($reffId);
            if (!$customer) {
                throw new Exception("Customer not found: {$reffId}");
            }

            // Get full file path
            $fullPath = Storage::path('public' . str_replace('/storage', '', $photoUrl));

            if (!file_exists($fullPath)) {
                throw new Exception("Photo file not found: {$fullPath}");
            }

            // Create or update PhotoApproval record with pending status
            $photoApproval = PhotoApproval::updateOrCreate(
                [
                    'reff_id_pelanggan' => $reffId,
                    'module_name' => $module,
                    'photo_field_name' => $photoField
                ],
                [
                    'photo_url' => $photoUrl,
                    'photo_status' => 'ai_pending',
                    'ai_confidence_score' => null,
                    'ai_validation_result' => null,
                    'ai_approved_at' => null,
                    'rejection_reason' => null
                ]
            );

            // Create audit log
            $this->createAuditLog($uploadedBy, 'ai_validation_started', 'PhotoApproval', $photoApproval->id, $reffId, [
                'module' => $module,
                'photo_field' => $photoField,
                'status' => 'ai_pending'
            ]);

            DB::commit();

            // Run AI validation asynchronously (in real production, use queue)
            $this->runAIValidation($photoApproval, $fullPath);

            return $photoApproval->fresh();

        } catch (Exception $e) {
            DB::rollback();

            Log::error('AI validation process failed', [
                'reff_id' => $reffId,
                'module' => $module,
                'photo_field' => $photoField,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Create failed validation record
            $photoApproval = PhotoApproval::updateOrCreate(
                [
                    'reff_id_pelanggan' => $reffId,
                    'module_name' => $module,
                    'photo_field_name' => $photoField
                ],
                [
                    'photo_url' => $photoUrl,
                    'photo_status' => 'ai_rejected',
                    'rejection_reason' => 'Technical error during AI validation: ' . $e->getMessage()
                ]
            );

            return $photoApproval;
        }
    }

    /**
     * Run AI validation on photo
     *
     * @param PhotoApproval $photoApproval
     * @param string $fullPath
     * @return void
     */
    private function runAIValidation(PhotoApproval $photoApproval, string $fullPath): void
    {
        try {
            // Run AI validation
            $aiResult = $this->openAIService->validatePhoto(
                $fullPath,
                $photoApproval->photo_field_name,
                $photoApproval->module_name
            );

            // Update photo approval with AI results
            $photoApproval->update([
                'ai_confidence_score' => $aiResult['confidence'],
                'ai_validation_result' => $aiResult,
                'ai_approved_at' => $aiResult['validation_passed'] ? now() : null,
                'photo_status' => $aiResult['validation_passed'] ? 'tracer_pending' : 'ai_rejected',
                'rejection_reason' => $aiResult['rejection_reason']
            ]);

            Log::info('AI validation completed', [
                'reff_id' => $photoApproval->reff_id_pelanggan,
                'photo_field' => $photoApproval->photo_field_name,
                'result' => $aiResult['validation_passed'] ? 'PASSED' : 'REJECTED',
                'confidence' => $aiResult['confidence']
            ]);

            // Handle result
            if (!$aiResult['validation_passed']) {
                $this->handlePhotoRejection(
                    $photoApproval,
                    'AI System',
                    $aiResult['rejection_reason'] ?? 'Failed AI validation'
                );
            } else {
                // Notify tracer about pending review
                $this->notificationService->notifyTracerPhotoPending(
                    $photoApproval->reff_id_pelanggan,
                    $photoApproval->module_name
                );
            }

            // Create audit log
            $this->createAuditLog(null, 'ai_validation_completed', 'PhotoApproval', $photoApproval->id, $photoApproval->reff_id_pelanggan, [
                'result' => $aiResult['validation_passed'] ? 'passed' : 'rejected',
                'confidence' => $aiResult['confidence'],
                'reason' => $aiResult['rejection_reason']
            ]);

        } catch (Exception $e) {
            Log::error('AI validation execution failed', [
                'photo_approval_id' => $photoApproval->id,
                'error' => $e->getMessage()
            ]);

            $photoApproval->update([
                'photo_status' => 'ai_rejected',
                'rejection_reason' => 'AI validation system error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Approve photo by Tracer
     *
     * @param int $photoApprovalId
     * @param int $userId
     * @param string|null $notes
     * @return PhotoApproval
     * @throws Exception
     */
    public function approveByTracer(int $photoApprovalId, int $userId, ?string $notes = null): PhotoApproval
    {
        DB::beginTransaction();

        try {
            $photoApproval = PhotoApproval::findOrFail($photoApprovalId);
            $user = User::findOrFail($userId);

            // Validate user permissions
            if (!$user->isTracer() && !$user->isAdmin()) {
                throw new Exception('Unauthorized: Only Tracer or Admin can approve photos');
            }

            // Validate current status
            if ($photoApproval->photo_status !== 'tracer_pending') {
                throw new Exception("Photo is not in tracer_pending status. Current status: {$photoApproval->photo_status}");
            }

            // Update photo approval
            $oldStatus = $photoApproval->photo_status;
            $photoApproval->update([
                'tracer_user_id' => $userId,
                'tracer_approved_at' => now(),
                'tracer_notes' => $notes,
                'photo_status' => 'cgp_pending'
            ]);

            Log::info('Photo approved by Tracer', [
                'photo_approval_id' => $photoApprovalId,
                'reff_id' => $photoApproval->reff_id_pelanggan,
                'tracer_id' => $userId,
                'tracer_name' => $user->full_name
            ]);

            // Create audit log
            $this->createAuditLog($userId, 'tracer_approved', 'PhotoApproval', $photoApprovalId, $photoApproval->reff_id_pelanggan, [
                'old_status' => $oldStatus,
                'new_status' => 'cgp_pending',
                'notes' => $notes
            ]);

            // Notify admin for CGP review
            $this->notificationService->notifyAdminCgpReview(
                $photoApproval->reff_id_pelanggan,
                $photoApproval->module_name
            );

            // Send Telegram status update
            $this->telegramService->sendModuleStatusAlert(
                $photoApproval->reff_id_pelanggan,
                $photoApproval->module_name,
                'tracer_review',
                'cgp_pending',
                $user->full_name
            );

            DB::commit();

            return $photoApproval->fresh();

        } catch (Exception $e) {
            DB::rollback();

            Log::error('Tracer approval failed', [
                'photo_approval_id' => $photoApprovalId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Reject photo by Tracer
     *
     * @param int $photoApprovalId
     * @param int $userId
     * @param string $reason
     * @return PhotoApproval
     * @throws Exception
     */
    public function rejectByTracer(int $photoApprovalId, int $userId, string $reason): PhotoApproval
    {
        DB::beginTransaction();

        try {
            $photoApproval = PhotoApproval::findOrFail($photoApprovalId);
            $user = User::findOrFail($userId);

            // Validate user permissions
            if (!$user->isTracer() && !$user->isAdmin()) {
                throw new Exception('Unauthorized: Only Tracer or Admin can reject photos');
            }

            // Validate current status
            if ($photoApproval->photo_status !== 'tracer_pending') {
                throw new Exception("Photo is not in tracer_pending status. Current status: {$photoApproval->photo_status}");
            }

            // Update photo approval
            $oldStatus = $photoApproval->photo_status;
            $photoApproval->update([
                'tracer_user_id' => $userId,
                'tracer_approved_at' => null,
                'tracer_notes' => $reason,
                'photo_status' => 'tracer_rejected',
                'rejection_reason' => $reason
            ]);

            Log::info('Photo rejected by Tracer', [
                'photo_approval_id' => $photoApprovalId,
                'reff_id' => $photoApproval->reff_id_pelanggan,
                'tracer_id' => $userId,
                'reason' => $reason
            ]);

            // Create audit log
            $this->createAuditLog($userId, 'tracer_rejected', 'PhotoApproval', $photoApprovalId, $photoApproval->reff_id_pelanggan, [
                'old_status' => $oldStatus,
                'new_status' => 'tracer_rejected',
                'rejection_reason' => $reason
            ]);

            // Handle rejection notifications
            $this->handlePhotoRejection($photoApproval, $user->full_name, $reason);

            DB::commit();

            return $photoApproval->fresh();

        } catch (Exception $e) {
            DB::rollback();

            Log::error('Tracer rejection failed', [
                'photo_approval_id' => $photoApprovalId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Approve photo by CGP (Final approval)
     *
     * @param int $photoApprovalId
     * @param int $userId
     * @param string|null $notes
     * @return PhotoApproval
     * @throws Exception
     */
    public function approveByCgp(int $photoApprovalId, int $userId, ?string $notes = null): PhotoApproval
    {
        DB::beginTransaction();

        try {
            $photoApproval = PhotoApproval::findOrFail($photoApprovalId);
            $user = User::findOrFail($userId);

            // Validate user permissions
            if (!$user->isAdmin()) {
                throw new Exception('Unauthorized: Only Admin can perform CGP approval');
            }

            // Validate current status
            if ($photoApproval->photo_status !== 'cgp_pending') {
                throw new Exception("Photo is not in cgp_pending status. Current status: {$photoApproval->photo_status}");
            }

            // Update photo approval
            $oldStatus = $photoApproval->photo_status;
            $photoApproval->update([
                'cgp_user_id' => $userId,
                'cgp_approved_at' => now(),
                'cgp_notes' => $notes,
                'photo_status' => 'cgp_approved'
            ]);

            Log::info('Photo approved by CGP', [
                'photo_approval_id' => $photoApprovalId,
                'reff_id' => $photoApproval->reff_id_pelanggan,
                'cgp_id' => $userId,
                'cgp_name' => $user->full_name
            ]);

            // Create audit log
            $this->createAuditLog($userId, 'cgp_approved', 'PhotoApproval', $photoApprovalId, $photoApproval->reff_id_pelanggan, [
                'old_status' => $oldStatus,
                'new_status' => 'cgp_approved',
                'notes' => $notes
            ]);

            // Check if all photos in module are completed
            $this->checkModuleCompletion($photoApproval->reff_id_pelanggan, $photoApproval->module_name);

            // Notify field user about approval
            $this->notificationService->notifyPhotoApproved(
                $photoApproval->reff_id_pelanggan,
                $photoApproval->module_name,
                $photoApproval->photo_field_name
            );

            // Send Telegram status update
            $this->telegramService->sendModuleStatusAlert(
                $photoApproval->reff_id_pelanggan,
                $photoApproval->module_name,
                'cgp_review',
                'completed',
                $user->full_name
            );

            DB::commit();

            return $photoApproval->fresh();

        } catch (Exception $e) {
            DB::rollback();

            Log::error('CGP approval failed', [
                'photo_approval_id' => $photoApprovalId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Reject photo by CGP
     *
     * @param int $photoApprovalId
     * @param int $userId
     * @param string $reason
     * @return PhotoApproval
     * @throws Exception
     */
    public function rejectByCgp(int $photoApprovalId, int $userId, string $reason): PhotoApproval
    {
        DB::beginTransaction();

        try {
            $photoApproval = PhotoApproval::findOrFail($photoApprovalId);
            $user = User::findOrFail($userId);

            // Validate user permissions
            if (!$user->isAdmin()) {
                throw new Exception('Unauthorized: Only Admin can perform CGP rejection');
            }

            // Validate current status
            if ($photoApproval->photo_status !== 'cgp_pending') {
                throw new Exception("Photo is not in cgp_pending status. Current status: {$photoApproval->photo_status}");
            }

            // Update photo approval
            $oldStatus = $photoApproval->photo_status;
            $photoApproval->update([
                'cgp_user_id' => $userId,
                'cgp_approved_at' => null,
                'cgp_notes' => $reason,
                'photo_status' => 'cgp_rejected',
                'rejection_reason' => $reason
            ]);

            Log::info('Photo rejected by CGP', [
                'photo_approval_id' => $photoApprovalId,
                'reff_id' => $photoApproval->reff_id_pelanggan,
                'cgp_id' => $userId,
                'reason' => $reason
            ]);

            // Create audit log
            $this->createAuditLog($userId, 'cgp_rejected', 'PhotoApproval', $photoApprovalId, $photoApproval->reff_id_pelanggan, [
                'old_status' => $oldStatus,
                'new_status' => 'cgp_rejected',
                'rejection_reason' => $reason
            ]);

            // Handle rejection notifications
            $this->handlePhotoRejection($photoApproval, $user->full_name . ' (CGP)', $reason);

            DB::commit();

            return $photoApproval->fresh();

        } catch (Exception $e) {
            DB::rollback();

            Log::error('CGP rejection failed', [
                'photo_approval_id' => $photoApprovalId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Handle photo rejection notifications and alerts
     *
     * @param PhotoApproval $photoApproval
     * @param string $rejectedBy
     * @param string $reason
     * @return void
     */
    private function handlePhotoRejection(PhotoApproval $photoApproval, string $rejectedBy, string $reason): void
    {
        try {
            // Send Telegram alert
            $this->telegramService->sendPhotoRejectionAlert(
                $photoApproval->reff_id_pelanggan,
                $photoApproval->module_name,
                $photoApproval->photo_field_name,
                $rejectedBy,
                $reason
            );

            // Send notification to field user
            $this->notificationService->notifyPhotoRejection(
                $photoApproval->reff_id_pelanggan,
                $photoApproval->module_name,
                $photoApproval->photo_field_name,
                $reason
            );

            Log::info('Photo rejection notifications sent', [
                'reff_id' => $photoApproval->reff_id_pelanggan,
                'photo_field' => $photoApproval->photo_field_name,
                'rejected_by' => $rejectedBy
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send photo rejection notifications', [
                'photo_approval_id' => $photoApproval->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if module is completed and update status
     *
     * @param string $reffId
     * @param string $module
     * @return void
     */
    private function checkModuleCompletion(string $reffId, string $module): void
    {
        try {
            $moduleClassName = 'App\\Models\\' . ucfirst(str_replace('_', '', ucwords($module, '_'))) . 'Data';

            if (!class_exists($moduleClassName)) {
                Log::warning("Module class not found: {$moduleClassName}");
                return;
            }

            $moduleData = $moduleClassName::where('reff_id_pelanggan', $reffId)->first();

            if (!$moduleData) {
                Log::warning("Module data not found for {$module}: {$reffId}");
                return;
            }

            // Get required photos for this module
            $requiredPhotos = $moduleData->getRequiredPhotos();

            // Count completed photos
            $completedPhotos = PhotoApproval::where('reff_id_pelanggan', $reffId)
                ->where('module_name', $module)
                ->where('photo_status', 'cgp_approved')
                ->count();

            Log::info('Checking module completion', [
                'reff_id' => $reffId,
                'module' => $module,
                'required_photos' => count($requiredPhotos),
                'completed_photos' => $completedPhotos
            ]);

            // If all photos are completed, mark module as completed
            if ($completedPhotos >= count($requiredPhotos)) {
                $moduleData->update([
                    'module_status' => 'completed',
                    'overall_photo_status' => 'completed'
                ]);

                // Update customer progress
                $this->updateCustomerProgress($reffId, $module);

                // Send completion notification
                $this->notificationService->notifyModuleCompletion($reffId, $module);

                Log::info('Module completed', [
                    'reff_id' => $reffId,
                    'module' => $module
                ]);
            }

        } catch (Exception $e) {
            Log::error('Error checking module completion', [
                'reff_id' => $reffId,
                'module' => $module,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update customer progress after module completion
     *
     * @param string $reffId
     * @param string $completedModule
     * @return void
     */
    private function updateCustomerProgress(string $reffId, string $completedModule): void
    {
        try {
            $customer = CalonPelanggan::find($reffId);

            if (!$customer) {
                Log::warning("Customer not found for progress update: {$reffId}");
                return;
            }

            // Define module progression
            $moduleProgression = [
                'sk' => 'sr',
                'sr' => 'mgrt',
                'mgrt' => 'gas_in',
                'gas_in' => 'jalur_pipa',
                'jalur_pipa' => 'penyambungan',
                'penyambungan' => 'done'
            ];

            // Update progress status
            if (isset($moduleProgression[$completedModule])) {
                $nextStatus = $moduleProgression[$completedModule];

                $customer->update([
                    'progress_status' => $nextStatus,
                    'status' => $nextStatus === 'done' ? 'lanjut' : 'in_progress'
                ]);

                Log::info('Customer progress updated', [
                    'reff_id' => $reffId,
                    'completed_module' => $completedModule,
                    'new_progress_status' => $nextStatus
                ]);
            }

        } catch (Exception $e) {
            Log::error('Error updating customer progress', [
                'reff_id' => $reffId,
                'completed_module' => $completedModule,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create audit log entry
     *
     * @param int|null $userId
     * @param string $action
     * @param string $modelType
     * @param int|null $modelId
     * @param string|null $reffId
     * @param array $data
     * @return void
     */
    private function createAuditLog(
        ?int $userId,
        string $action,
        string $modelType,
        ?int $modelId,
        ?string $reffId,
        array $data = []
    ): void {
        try {
            AuditLog::create([
                'user_id' => $userId,
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'reff_id_pelanggan' => $reffId,
                'old_values' => $data['old_values'] ?? null,
                'new_values' => $data,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'description' => $this->getActionDescription($action, $data)
            ]);

        } catch (Exception $e) {
            Log::error('Failed to create audit log', [
                'action' => $action,
                'model_type' => $modelType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get description for audit log action
     *
     * @param string $action
     * @param array $data
     * @return string
     */
    private function getActionDescription(string $action, array $data): string
    {
        return match($action) {
            'ai_validation_started' => 'AI validation process started for photo',
            'ai_validation_completed' => 'AI validation completed with result: ' . ($data['result'] ?? 'unknown'),
            'tracer_approved' => 'Photo approved by Tracer',
            'tracer_rejected' => 'Photo rejected by Tracer: ' . ($data['rejection_reason'] ?? ''),
            'cgp_approved' => 'Photo approved by CGP (Final approval)',
            'cgp_rejected' => 'Photo rejected by CGP: ' . ($data['rejection_reason'] ?? ''),
            default => ucwords(str_replace('_', ' ', $action))
        };
    }

    /**
     * Get photo approval statistics
     *
     * @param array $filters
     * @return array
     */
    public function getPhotoApprovalStats(array $filters = []): array
    {
        $query = PhotoApproval::query();

        // Apply filters
        if (isset($filters['module'])) {
            $query->where('module_name', $filters['module']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $stats = [
            'total_photos' => $query->count(),
            'ai_pending' => (clone $query)->where('photo_status', 'ai_pending')->count(),
            'ai_approved' => (clone $query)->where('photo_status', 'ai_approved')->count(),
            'ai_rejected' => (clone $query)->where('photo_status', 'ai_rejected')->count(),
            'tracer_pending' => (clone $query)->where('photo_status', 'tracer_pending')->count(),
            'tracer_approved' => (clone $query)->where('photo_status', 'tracer_approved')->count(),
            'tracer_rejected' => (clone $query)->where('photo_status', 'tracer_rejected')->count(),
            'cgp_pending' => (clone $query)->where('photo_status', 'cgp_pending')->count(),
            'cgp_approved' => (clone $query)->where('photo_status', 'cgp_approved')->count(),
            'cgp_rejected' => (clone $query)->where('photo_status', 'cgp_rejected')->count(),
        ];

        // Calculate rates
        $total = $stats['total_photos'];
        if ($total > 0) {
            $stats['ai_approval_rate'] = round(($stats['ai_approved'] / $total) * 100, 2);
            $stats['tracer_approval_rate'] = round(($stats['tracer_approved'] / $total) * 100, 2);
            $stats['cgp_approval_rate'] = round(($stats['cgp_approved'] / $total) * 100, 2);
            $stats['overall_completion_rate'] = round(($stats['cgp_approved'] / $total) * 100, 2);
        } else {
            $stats['ai_approval_rate'] = 0;
            $stats['tracer_approval_rate'] = 0;
            $stats['cgp_approval_rate'] = 0;
            $stats['overall_completion_rate'] = 0;
        }

        return $stats;
    }

    /**
     * Batch process photo approvals
     *
     * @param array $photoIds
     * @param string $action
     * @param int $userId
     * @param array $options
     * @return array
     */
    public function batchProcessPhotos(array $photoIds, string $action, int $userId, array $options = []): array
    {
        $results = [];
        $successCount = 0;

        DB::beginTransaction();

        try {
            foreach ($photoIds as $photoId) {
                try {
                    $result = match($action) {
                        'tracer_approve' => $this->approveByTracer($photoId, $userId, $options['notes'] ?? null),
                        'tracer_reject' => $this->rejectByTracer($photoId, $userId, $options['reason'] ?? 'Batch rejection'),
                        'cgp_approve' => $this->approveByCgp($photoId, $userId, $options['notes'] ?? null),
                        'cgp_reject' => $this->rejectByCgp($photoId, $userId, $options['reason'] ?? 'Batch rejection'),
                        default => throw new Exception("Invalid action: {$action}")
                    };

                    $results[] = [
                        'id' => $photoId,
                        'success' => true,
                        'data' => $result
                    ];
                    $successCount++;

                } catch (Exception $e) {
                    $results[] = [
                        'id' => $photoId,
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            Log::info('Batch photo processing completed', [
                'action' => $action,
                'total_photos' => count($photoIds),
                'successful' => $successCount,
                'failed' => count($photoIds) - $successCount,
                'user_id' => $userId
            ]);

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Batch photo processing failed', [
                'action' => $action,
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);

            throw $e;
        }

        return [
            'total' => count($photoIds),
            'successful' => $successCount,
            'failed' => count($photoIds) - $successCount,
            'results' => $results
        ];
    }
}
