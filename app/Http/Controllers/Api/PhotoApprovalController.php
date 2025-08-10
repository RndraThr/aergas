<?php

/**
 * =============================================================================
 * CONTROLLER: PhotoApprovalController.php
 * Location: app/Http/Controllers/Api/PhotoApprovalController.php
 * =============================================================================
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhotoApproval;
use App\Models\User;
use App\Services\PhotoApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class PhotoApprovalController extends Controller
{
    private PhotoApprovalService $photoApprovalService;

    public function __construct(PhotoApprovalService $photoApprovalService)
    {
        $this->photoApprovalService = $photoApprovalService;
    }

    /**
     * Display a listing of photo approvals
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = PhotoApproval::with(['pelanggan', 'tracerUser', 'cgpUser']);

            // Apply role-based filters
            $this->applyRoleBasedFilters($query, $user, $request);

            // Apply additional filters
            $this->applyFilters($query, $request);

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $allowedSortFields = [
                'created_at', 'updated_at', 'photo_status',
                'ai_confidence_score', 'ai_approved_at', 'tracer_approved_at', 'cgp_approved_at'
            ];

            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortDirection);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 50);
            $photoApprovals = $query->paginate($perPage);

            // Add computed fields
            $photoApprovals->getCollection()->transform(function ($item) {
                $item->pending_hours = $this->calculatePendingHours($item);
                $item->sla_status = $this->getSlaStatus($item);
                $item->can_approve = $this->canUserApprove($item, request()->user());
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $photoApprovals,
                'meta' => [
                    'stats' => $this->getApprovalStats($user),
                    'sla_summary' => $this->getSlaStatistics()
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching photo approvals', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching photo approvals'
            ], 500);
        }
    }

    /**
     * Display the specified photo approval
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $photoApproval = PhotoApproval::with([
                'pelanggan',
                'tracerUser',
                'cgpUser'
            ])->findOrFail($id);

            // Add computed fields
            $photoApproval->pending_hours = $this->calculatePendingHours($photoApproval);
            $photoApproval->sla_status = $this->getSlaStatus($photoApproval);
            $photoApproval->can_approve = $this->canUserApprove($photoApproval, request()->user());
            $photoApproval->approval_history = $this->getApprovalHistory($photoApproval);

            return response()->json([
                'success' => true,
                'data' => $photoApproval
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching photo approval details', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Photo approval not found'
            ], 404);
        }
    }

    /**
     * Approve photo by Tracer
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function approveByTracer(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $photoApproval = $this->photoApprovalService->approveByTracer(
                $id,
                $request->user()->id,
                $request->notes
            );

            Log::info('Photo approved by Tracer via API', [
                'photo_approval_id' => $id,
                'tracer_id' => $request->user()->id,
                'tracer_name' => $request->user()->full_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Photo approved by Tracer successfully',
                'data' => $photoApproval->load(['pelanggan', 'tracerUser', 'cgpUser']),
                'next_step' => [
                    'status' => 'cgp_pending',
                    'description' => 'Photo is now waiting for CGP review',
                    'estimated_time' => '48 hours'
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Tracer approval failed via API', [
                'photo_approval_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $this->getErrorStatusCode($e));
        }
    }

    /**
     * Reject photo by Tracer
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function rejectByTracer(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000|min:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $photoApproval = $this->photoApprovalService->rejectByTracer(
                $id,
                $request->user()->id,
                $request->reason
            );

            Log::info('Photo rejected by Tracer via API', [
                'photo_approval_id' => $id,
                'tracer_id' => $request->user()->id,
                'reason' => $request->reason
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Photo rejected by Tracer',
                'data' => $photoApproval->load(['pelanggan', 'tracerUser', 'cgpUser']),
                'next_step' => [
                    'status' => 'tracer_rejected',
                    'description' => 'Field team needs to re-upload photo',
                    'action_required' => 'Re-upload photo with corrections'
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Tracer rejection failed via API', [
                'photo_approval_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $this->getErrorStatusCode($e));
        }
    }

    /**
     * Approve photo by CGP (Final approval)
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function approveByCgp(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $photoApproval = $this->photoApprovalService->approveByCgp(
                $id,
                $request->user()->id,
                $request->notes
            );

            Log::info('Photo approved by CGP via API', [
                'photo_approval_id' => $id,
                'cgp_id' => $request->user()->id,
                'cgp_name' => $request->user()->full_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Photo approved by CGP successfully (Final approval)',
                'data' => $photoApproval->load(['pelanggan', 'tracerUser', 'cgpUser']),
                'next_step' => [
                    'status' => 'cgp_approved',
                    'description' => 'Photo has received final approval',
                    'module_status' => 'Check if all photos in module are completed'
                ]
            ]);

        } catch (Exception $e) {
            Log::error('CGP approval failed via API', [
                'photo_approval_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $this->getErrorStatusCode($e));
        }
    }

    /**
     * Reject photo by CGP
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function rejectByCgp(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000|min:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $photoApproval = $this->photoApprovalService->rejectByCgp(
                $id,
                $request->user()->id,
                $request->reason
            );

            Log::info('Photo rejected by CGP via API', [
                'photo_approval_id' => $id,
                'cgp_id' => $request->user()->id,
                'reason' => $request->reason
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Photo rejected by CGP',
                'data' => $photoApproval->load(['pelanggan', 'tracerUser', 'cgpUser']),
                'next_step' => [
                    'status' => 'cgp_rejected',
                    'description' => 'Photo has been rejected at final review stage',
                    'action_required' => 'Field team needs to re-upload photo with corrections'
                ]
            ]);

        } catch (Exception $e) {
            Log::error('CGP rejection failed via API', [
                'photo_approval_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $this->getErrorStatusCode($e));
        }
    }

    /**
     * Batch process photo approvals
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function batchApprove(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'photo_ids' => 'required|array|min:1|max:50',
            'photo_ids.*' => 'required|integer|exists:photo_approvals,id',
            'action' => 'required|in:tracer_approve,tracer_reject,cgp_approve,cgp_reject',
            'notes' => 'nullable|string|max:1000',
            'reason' => 'required_if:action,tracer_reject,cgp_reject|string|max:1000|min:10'
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

            // Validate user permissions for the action
            $this->validateBatchActionPermissions($request->action, $user);

            // Process batch operation
            $results = $this->photoApprovalService->batchProcessPhotos(
                $request->photo_ids,
                $request->action,
                $user->id,
                [
                    'notes' => $request->notes,
                    'reason' => $request->reason
                ]
            );

            Log::info('Batch photo processing completed via API', [
                'action' => $request->action,
                'total_photos' => count($request->photo_ids),
                'successful' => $results['successful'],
                'failed' => $results['failed'],
                'user_id' => $user->id,
                'user_role' => $user->role
            ]);

            return response()->json([
                'success' => $results['successful'] > 0,
                'message' => "Processed {$results['successful']} out of {$results['total']} photos successfully",
                'data' => [
                    'summary' => [
                        'total' => $results['total'],
                        'successful' => $results['successful'],
                        'failed' => $results['failed'],
                        'action' => $request->action
                    ],
                    'results' => $results['results']
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Batch photo processing failed via API', [
                'action' => $request->action,
                'photo_count' => count($request->photo_ids ?? []),
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $this->getErrorStatusCode($e));
        }
    }

    /**
     * Get photo approval statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get filters from request
            $filters = $request->only([
                'module', 'date_from', 'date_to', 'status', 'reff_id_pelanggan'
            ]);

            $stats = $this->photoApprovalService->getPhotoApprovalStats($filters);

            // Add role-specific stats
            $roleSpecificStats = $this->getRoleSpecificStats($user);

            // Add SLA statistics
            $slaStats = $this->getSlaStatistics();

            return response()->json([
                'success' => true,
                'data' => [
                    'general_stats' => $stats,
                    'role_specific' => $roleSpecificStats,
                    'sla_stats' => $slaStats,
                    'filters_applied' => $filters
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching photo approval stats', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching statistics'
            ], 500);
        }
    }

    /**
     * Get pending approvals for current user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPendingApprovals(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = PhotoApproval::with(['pelanggan', 'tracerUser', 'cgpUser']);

            // Filter based on user role
            if ($user->isTracer()) {
                $query->where('photo_status', 'tracer_pending');
            } elseif ($user->isAdmin()) {
                $query->where('photo_status', 'cgp_pending');
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view pending approvals'
                ], 403);
            }

            // Apply priority ordering (oldest first, high SLA risk)
            $query->orderBy('created_at', 'asc');

            $pendingApprovals = $query->get();

            // Add computed fields and priority scoring
            $pendingApprovals->transform(function ($item) use ($user) {
                $item->pending_hours = $this->calculatePendingHours($item);
                $item->sla_status = $this->getSlaStatus($item);
                $item->priority_score = $this->calculatePriorityScore($item);
                return $item;
            });

            // Sort by priority score (high risk first)
            $pendingApprovals = $pendingApprovals->sortByDesc('priority_score')->values();

            return response()->json([
                'success' => true,
                'data' => $pendingApprovals,
                'meta' => [
                    'total_pending' => $pendingApprovals->count(),
                    'sla_violations' => $pendingApprovals->where('sla_status', 'violation')->count(),
                    'sla_warnings' => $pendingApprovals->where('sla_status', 'warning')->count(),
                    'user_role' => $user->role
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching pending approvals', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching pending approvals'
            ], 500);
        }
    }

    /**
     * Apply role-based filters to query
     *
     * @param $query
     * @param User $user
     * @param Request $request
     * @return void
     */
    private function applyRoleBasedFilters($query, User $user, Request $request): void
    {
        $filterType = $request->get('type', 'all');

        if ($user->isTracer()) {
            switch ($filterType) {
                case 'pending':
                    $query->where('photo_status', 'tracer_pending');
                    break;
                case 'reviewed':
                    $query->whereIn('photo_status', ['tracer_approved', 'tracer_rejected']);
                    break;
                case 'my_reviews':
                    $query->where('tracer_user_id', $user->id);
                    break;
                default:
                    $query->whereIn('photo_status', ['tracer_pending', 'tracer_approved', 'tracer_rejected']);
            }
        } elseif ($user->isAdmin()) {
            switch ($filterType) {
                case 'cgp_review':
                    $query->where('photo_status', 'cgp_pending');
                    break;
                case 'cgp_reviewed':
                    $query->whereIn('photo_status', ['cgp_approved', 'cgp_rejected']);
                    break;
                case 'my_reviews':
                    $query->where('cgp_user_id', $user->id);
                    break;
                default:
                    // Admin can see all
                    break;
            }
        }
    }

    /**
     * Apply additional filters to query
     *
     * @param $query
     * @param Request $request
     * @return void
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->has('module_name') && $request->module_name !== '') {
            $query->where('module_name', $request->module_name);
        }

        if ($request->has('photo_status') && $request->photo_status !== '') {
            $query->where('photo_status', $request->photo_status);
        }

        if ($request->has('reff_id_pelanggan') && $request->reff_id_pelanggan !== '') {
            $query->where('reff_id_pelanggan', 'LIKE', '%' . $request->reff_id_pelanggan . '%');
        }

        if ($request->has('date_from') && $request->date_from !== '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to !== '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('ai_confidence_min')) {
            $query->where('ai_confidence_score', '>=', $request->ai_confidence_min);
        }

        if ($request->has('sla_status')) {
            switch ($request->sla_status) {
                case 'violation':
                    $this->applySlaViolationFilter($query);
                    break;
                case 'warning':
                    $this->applySlaWarningFilter($query);
                    break;
            }
        }
    }

    /**
     * Calculate pending hours for photo approval
     *
     * @param PhotoApproval $photoApproval
     * @return int|null
     */
    private function calculatePendingHours(PhotoApproval $photoApproval): ?int
    {
        $startTime = null;

        if ($photoApproval->photo_status === 'tracer_pending' && $photoApproval->ai_approved_at) {
            $startTime = $photoApproval->ai_approved_at;
        } elseif ($photoApproval->photo_status === 'cgp_pending' && $photoApproval->tracer_approved_at) {
            $startTime = $photoApproval->tracer_approved_at;
        }

        return $startTime ? $startTime->diffInHours(now()) : null;
    }

    /**
     * Get SLA status for photo approval
     *
     * @param PhotoApproval $photoApproval
     * @return string
     */
    private function getSlaStatus(PhotoApproval $photoApproval): string
    {
        $pendingHours = $this->calculatePendingHours($photoApproval);

        if (!$pendingHours) {
            return 'not_applicable';
        }

        $slaLimit = $photoApproval->photo_status === 'tracer_pending' ? 24 : 48;
        $warningLimit = $photoApproval->photo_status === 'tracer_pending' ? 20 : 40;

        if ($pendingHours >= $slaLimit) {
            return 'violation';
        } elseif ($pendingHours >= $warningLimit) {
            return 'warning';
        }

        return 'normal';
    }

    /**
     * Calculate priority score for sorting
     *
     * @param PhotoApproval $photoApproval
     * @return int
     */
    private function calculatePriorityScore(PhotoApproval $photoApproval): int
    {
        $score = 0;
        $pendingHours = $photoApproval->pending_hours ?? 0;

        // Base score from pending hours
        $score += $pendingHours * 2;

        // Bonus for SLA status
        switch ($photoApproval->sla_status) {
            case 'violation':
                $score += 1000;
                break;
            case 'warning':
                $score += 500;
                break;
        }

        // Bonus for high AI confidence (more likely to be correct)
        if ($photoApproval->ai_confidence_score >= 90) {
            $score += 100;
        }

        return $score;
    }

    /**
     * Check if user can approve the photo
     *
     * @param PhotoApproval $photoApproval
     * @param User $user
     * @return bool
     */
    private function canUserApprove(PhotoApproval $photoApproval, User $user): bool
    {
        return match($photoApproval->photo_status) {
            'tracer_pending' => $user->isTracer() || $user->isAdmin(),
            'cgp_pending' => $user->isAdmin(),
            default => false
        };
    }

    /**
     * Get approval history for photo
     *
     * @param PhotoApproval $photoApproval
     * @return array
     */
    private function getApprovalHistory(PhotoApproval $photoApproval): array
    {
        $history = [];

        if ($photoApproval->ai_approved_at) {
            $history[] = [
                'stage' => 'AI Validation',
                'status' => $photoApproval->isAiApproved() ? 'approved' : 'rejected',
                'timestamp' => $photoApproval->ai_approved_at,
                'confidence' => $photoApproval->ai_confidence_score,
                'notes' => $photoApproval->rejection_reason
            ];
        }

        if ($photoApproval->tracer_approved_at || $photoApproval->tracer_user_id) {
            $history[] = [
                'stage' => 'Tracer Review',
                'status' => $photoApproval->isTracerApproved() ? 'approved' : 'rejected',
                'timestamp' => $photoApproval->tracer_approved_at,
                'reviewer' => $photoApproval->tracerUser->full_name ?? 'Unknown',
                'notes' => $photoApproval->tracer_notes
            ];
        }

        if ($photoApproval->cgp_approved_at || $photoApproval->cgp_user_id) {
            $history[] = [
                'stage' => 'CGP Review',
                'status' => $photoApproval->isCgpApproved() ? 'approved' : 'rejected',
                'timestamp' => $photoApproval->cgp_approved_at,
                'reviewer' => $photoApproval->cgpUser->full_name ?? 'Unknown',
                'notes' => $photoApproval->cgp_notes
            ];
        }

        return $history;
    }

    /**
     * Get approval statistics for user role
     *
     * @param User $user
     * @return array
     */
    private function getApprovalStats(User $user): array
    {
        $baseStats = [
            'pending_ai' => PhotoApproval::where('photo_status', 'ai_pending')->count(),
            'pending_tracer' => PhotoApproval::where('photo_status', 'tracer_pending')->count(),
            'pending_cgp' => PhotoApproval::where('photo_status', 'cgp_pending')->count(),
            'completed_today' => PhotoApproval::where('photo_status', 'cgp_approved')
                                             ->whereDate('cgp_approved_at', today())->count(),
        ];

        if ($user->isTracer()) {
            $baseStats['my_pending'] = PhotoApproval::where('photo_status', 'tracer_pending')->count();
            $baseStats['my_reviewed_today'] = PhotoApproval::where('tracer_user_id', $user->id)
                                                          ->whereDate('tracer_approved_at', today())
                                                          ->count();
        } elseif ($user->isAdmin()) {
            $baseStats['cgp_pending'] = PhotoApproval::where('photo_status', 'cgp_pending')->count();
            $baseStats['my_cgp_reviewed_today'] = PhotoApproval::where('cgp_user_id', $user->id)
                                                              ->whereDate('cgp_approved_at', today())
                                                              ->count();
        }

        return $baseStats;
    }

    /**
     * Get role-specific statistics
     *
     * @param User $user
     * @return array
     */
    private function getRoleSpecificStats(User $user): array
    {
        if ($user->isTracer()) {
            return [
                'total_reviewed' => PhotoApproval::where('tracer_user_id', $user->id)->count(),
                'approved_count' => PhotoApproval::where('tracer_user_id', $user->id)
                                                 ->where('photo_status', 'tracer_approved')
                                                 ->count(),
                'rejected_count' => PhotoApproval::where('tracer_user_id', $user->id)
                                                 ->where('photo_status', 'tracer_rejected')
                                                 ->count(),
                'avg_review_time' => $this->getAverageReviewTime($user->id, 'tracer'),
            ];
        } elseif ($user->isAdmin()) {
            return [
                'total_cgp_reviewed' => PhotoApproval::where('cgp_user_id', $user->id)->count(),
                'cgp_approved_count' => PhotoApproval::where('cgp_user_id', $user->id)
                                                     ->where('photo_status', 'cgp_approved')
                                                     ->count(),
                'cgp_rejected_count' => PhotoApproval::where('cgp_user_id', $user->id)
                                                     ->where('photo_status', 'cgp_rejected')
                                                     ->count(),
                'avg_cgp_review_time' => $this->getAverageReviewTime($user->id, 'cgp'),
            ];
        }

        return [];
    }

    /**
     * Get SLA statistics
     *
     * @return array
     */
    private function getSlaStatistics(): array
    {
        $tracerViolations = PhotoApproval::where('photo_status', 'tracer_pending')
                                        ->where('ai_approved_at', '<', now()->subHours(24))
                                        ->count();

        $tracerWarnings = PhotoApproval::where('photo_status', 'tracer_pending')
                                      ->where('ai_approved_at', '<', now()->subHours(20))
                                      ->where('ai_approved_at', '>=', now()->subHours(24))
                                      ->count();

        $cgpViolations = PhotoApproval::where('photo_status', 'cgp_pending')
                                     ->where('tracer_approved_at', '<', now()->subHours(48))
                                     ->count();

        $cgpWarnings = PhotoApproval::where('photo_status', 'cgp_pending')
                                   ->where('tracer_approved_at', '<', now()->subHours(40))
                                   ->where('tracer_approved_at', '>=', now()->subHours(48))
                                   ->count();

        return [
            'tracer_sla' => [
                'violations' => $tracerViolations,
                'warnings' => $tracerWarnings,
                'limit_hours' => 24
            ],
            'cgp_sla' => [
                'violations' => $cgpViolations,
                'warnings' => $cgpWarnings,
                'limit_hours' => 48
            ],
            'total_violations' => $tracerViolations + $cgpViolations,
            'total_warnings' => $tracerWarnings + $cgpWarnings
        ];
    }

    /**
     * Get average review time for user
     *
     * @param int $userId
     * @param string $type
     * @return float|null
     */
    private function getAverageReviewTime(int $userId, string $type): ?float
    {
        $query = PhotoApproval::where("{$type}_user_id", $userId)
                             ->whereNotNull("{$type}_approved_at");

        if ($type === 'tracer') {
            $query->whereNotNull('ai_approved_at')
                  ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, ai_approved_at, tracer_approved_at)) as avg_hours');
        } else {
            $query->whereNotNull('tracer_approved_at')
                  ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, tracer_approved_at, cgp_approved_at)) as avg_hours');
        }

        $result = $query->first();
        return $result ? round($result->avg_hours, 1) : null;
    }

    /**
     * Apply SLA violation filter to query
     *
     * @param $query
     * @return void
     */
    private function applySlaViolationFilter($query): void
    {
        $query->where(function ($q) {
            $q->where(function ($subQ) {
                $subQ->where('photo_status', 'tracer_pending')
                     ->where('ai_approved_at', '<', now()->subHours(24));
            })
            ->orWhere(function ($subQ) {
                $subQ->where('photo_status', 'cgp_pending')
                     ->where('tracer_approved_at', '<', now()->subHours(48));
            });
        });
    }

    /**
     * Apply SLA warning filter to query
     *
     * @param $query
     * @return void
     */
    private function applySlaWarningFilter($query): void
    {
        $query->where(function ($q) {
            $q->where(function ($subQ) {
                $subQ->where('photo_status', 'tracer_pending')
                     ->where('ai_approved_at', '<', now()->subHours(20))
                     ->where('ai_approved_at', '>=', now()->subHours(24));
            })
            ->orWhere(function ($subQ) {
                $subQ->where('photo_status', 'cgp_pending')
                     ->where('tracer_approved_at', '<', now()->subHours(40))
                     ->where('tracer_approved_at', '>=', now()->subHours(48));
            });
        });
    }

    /**
     * Validate batch action permissions
     *
     * @param string $action
     * @param User $user
     * @return void
     * @throws Exception
     */
    private function validateBatchActionPermissions(string $action, User $user): void
    {
        $tracerActions = ['tracer_approve', 'tracer_reject'];
        $cgpActions = ['cgp_approve', 'cgp_reject'];

        if (in_array($action, $tracerActions) && !($user->isTracer() || $user->isAdmin())) {
            throw new Exception('Unauthorized: Only Tracer or Admin can perform this action');
        }

        if (in_array($action, $cgpActions) && !$user->isAdmin()) {
            throw new Exception('Unauthorized: Only Admin can perform CGP actions');
        }
    }

    /**
     * Get appropriate HTTP status code for exception
     *
     * @param Exception $exception
     * @return int
     */
    private function getErrorStatusCode(Exception $exception): int
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'Unauthorized')) {
            return 403;
        }

        if (str_contains($message, 'not found') || str_contains($message, 'not in') || str_contains($message, 'status')) {
            return 422;
        }

        return 500;
    }

    /**
     * Export photo approvals to Excel
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportToExcel(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Apply same filters as index method
            $query = PhotoApproval::with(['pelanggan', 'tracerUser', 'cgpUser']);
            $this->applyRoleBasedFilters($query, $user, $request);
            $this->applyFilters($query, $request);

            $photoApprovals = $query->orderBy('created_at', 'desc')->get();

            // Transform data for export
            $exportData = $photoApprovals->map(function ($item) {
                return [
                    'ID' => $item->id,
                    'Reference ID' => $item->reff_id_pelanggan,
                    'Customer Name' => $item->pelanggan->nama_pelanggan ?? 'N/A',
                    'Module' => strtoupper($item->module_name),
                    'Photo Field' => $item->photo_field_name,
                    'Status' => ucwords(str_replace('_', ' ', $item->photo_status)),
                    'AI Confidence' => $item->ai_confidence_score ? $item->ai_confidence_score . '%' : 'N/A',
                    'AI Approved At' => $item->ai_approved_at?->format('Y-m-d H:i:s'),
                    'Tracer' => $item->tracerUser->full_name ?? 'N/A',
                    'Tracer Approved At' => $item->tracer_approved_at?->format('Y-m-d H:i:s'),
                    'CGP Reviewer' => $item->cgpUser->full_name ?? 'N/A',
                    'CGP Approved At' => $item->cgp_approved_at?->format('Y-m-d H:i:s'),
                    'Rejection Reason' => $item->rejection_reason ?? 'N/A',
                    'Created At' => $item->created_at->format('Y-m-d H:i:s'),
                    'SLA Status' => $this->getSlaStatus($item),
                    'Pending Hours' => $this->calculatePendingHours($item) ?? 'N/A'
                ];
            });

            // In a real application, you would use a package like maatwebsite/excel
            // For now, return the data that can be processed by frontend
            return response()->json([
                'success' => true,
                'message' => 'Data prepared for export',
                'data' => [
                    'export_data' => $exportData,
                    'total_records' => $exportData->count(),
                    'generated_at' => now()->toISOString(),
                    'filters_applied' => $request->only([
                        'type', 'module_name', 'photo_status', 'reff_id_pelanggan',
                        'date_from', 'date_to', 'sla_status'
                    ])
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Export photo approvals failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get photo approval summary report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSummaryReport(Request $request): JsonResponse
    {
        try {
            $dateFrom = $request->get('date_from', now()->subDays(30)->toDateString());
            $dateTo = $request->get('date_to', now()->toDateString());

            $baseQuery = PhotoApproval::whereBetween('created_at', [$dateFrom, $dateTo]);

            $report = [
                'period' => [
                    'from' => $dateFrom,
                    'to' => $dateTo,
                    'days' => now()->parse($dateTo)->diffInDays(now()->parse($dateFrom)) + 1
                ],
                'overview' => [
                    'total_photos' => (clone $baseQuery)->count(),
                    'completed' => (clone $baseQuery)->where('photo_status', 'cgp_approved')->count(),
                    'rejected' => (clone $baseQuery)->whereIn('photo_status', ['ai_rejected', 'tracer_rejected', 'cgp_rejected'])->count(),
                    'pending' => (clone $baseQuery)->whereIn('photo_status', ['ai_pending', 'tracer_pending', 'cgp_pending'])->count(),
                ],
                'by_module' => PhotoApproval::whereBetween('created_at', [$dateFrom, $dateTo])
                                          ->selectRaw('module_name, COUNT(*) as count,
                                                     AVG(ai_confidence_score) as avg_confidence')
                                          ->groupBy('module_name')
                                          ->get()
                                          ->keyBy('module_name'),
                'by_status' => PhotoApproval::whereBetween('created_at', [$dateFrom, $dateTo])
                                          ->selectRaw('photo_status, COUNT(*) as count')
                                          ->groupBy('photo_status')
                                          ->get()
                                          ->keyBy('photo_status'),
                'performance' => [
                    'avg_ai_confidence' => (clone $baseQuery)->avg('ai_confidence_score'),
                    'ai_approval_rate' => $this->calculateApprovalRate($baseQuery, 'ai'),
                    'tracer_approval_rate' => $this->calculateApprovalRate($baseQuery, 'tracer'),
                    'cgp_approval_rate' => $this->calculateApprovalRate($baseQuery, 'cgp'),
                ],
                'sla_performance' => $this->getSlaPerformanceReport($dateFrom, $dateTo),
                'top_rejectors' => $this->getTopRejectors($dateFrom, $dateTo),
                'generated_at' => now()->toISOString()
            ];

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (Exception $e) {
            Log::error('Summary report generation failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate summary report'
            ], 500);
        }
    }

    /**
     * Calculate approval rate for specific stage
     *
     * @param $baseQuery
     * @param string $stage
     * @return float
     */
    private function calculateApprovalRate($baseQuery, string $stage): float
    {
        $total = match($stage) {
            'ai' => (clone $baseQuery)->whereNotNull('ai_approved_at')->count(),
            'tracer' => (clone $baseQuery)->whereNotNull('tracer_approved_at')->count(),
            'cgp' => (clone $baseQuery)->whereNotNull('cgp_approved_at')->count(),
            default => 0
        };

        $approved = match($stage) {
            'ai' => (clone $baseQuery)->whereIn('photo_status', ['ai_approved', 'tracer_pending', 'tracer_approved', 'cgp_pending', 'cgp_approved'])->count(),
            'tracer' => (clone $baseQuery)->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved'])->count(),
            'cgp' => (clone $baseQuery)->where('photo_status', 'cgp_approved')->count(),
            default => 0
        };

        return $total > 0 ? round(($approved / $total) * 100, 2) : 0;
    }

    /**
     * Get SLA performance report
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    private function getSlaPerformanceReport(string $dateFrom, string $dateTo): array
    {
        $tracerCompleted = PhotoApproval::whereBetween('tracer_approved_at', [$dateFrom, $dateTo])
                                       ->whereNotNull('ai_approved_at')
                                       ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, ai_approved_at, tracer_approved_at)) as avg_hours')
                                       ->first();

        $cgpCompleted = PhotoApproval::whereBetween('cgp_approved_at', [$dateFrom, $dateTo])
                                    ->whereNotNull('tracer_approved_at')
                                    ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, tracer_approved_at, cgp_approved_at)) as avg_hours')
                                    ->first();

        return [
            'tracer_avg_hours' => $tracerCompleted?->avg_hours ? round($tracerCompleted->avg_hours, 1) : null,
            'cgp_avg_hours' => $cgpCompleted?->avg_hours ? round($cgpCompleted->avg_hours, 1) : null,
            'tracer_sla_compliance' => $this->calculateSlaCompliance('tracer', $dateFrom, $dateTo),
            'cgp_sla_compliance' => $this->calculateSlaCompliance('cgp', $dateFrom, $dateTo)
        ];
    }

    /**
     * Calculate SLA compliance rate
     *
     * @param string $stage
     * @param string $dateFrom
     * @param string $dateTo
     * @return float
     */
    private function calculateSlaCompliance(string $stage, string $dateFrom, string $dateTo): float
    {
        $slaLimit = $stage === 'tracer' ? 24 : 48;
        $approvedField = $stage . '_approved_at';
        $startField = $stage === 'tracer' ? 'ai_approved_at' : 'tracer_approved_at';

        $total = PhotoApproval::whereBetween($approvedField, [$dateFrom, $dateTo])
                             ->whereNotNull($startField)
                             ->count();

        $withinSla = PhotoApproval::whereBetween($approvedField, [$dateFrom, $dateTo])
                                 ->whereNotNull($startField)
                                 ->whereRaw("TIMESTAMPDIFF(HOUR, {$startField}, {$approvedField}) <= ?", [$slaLimit])
                                 ->count();

        return $total > 0 ? round(($withinSla / $total) * 100, 2) : 0;
    }

    /**
     * Get top rejectors in the period
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    private function getTopRejectors(string $dateFrom, string $dateTo): array
    {
        $tracerRejections = PhotoApproval::with('tracerUser')
                                        ->whereBetween('tracer_approved_at', [$dateFrom, $dateTo])
                                        ->where('photo_status', 'tracer_rejected')
                                        ->selectRaw('tracer_user_id, COUNT(*) as rejection_count')
                                        ->groupBy('tracer_user_id')
                                        ->orderBy('rejection_count', 'desc')
                                        ->take(5)
                                        ->get();

        $cgpRejections = PhotoApproval::with('cgpUser')
                                     ->whereBetween('cgp_approved_at', [$dateFrom, $dateTo])
                                     ->where('photo_status', 'cgp_rejected')
                                     ->selectRaw('cgp_user_id, COUNT(*) as rejection_count')
                                     ->groupBy('cgp_user_id')
                                     ->orderBy('rejection_count', 'desc')
                                     ->take(5)
                                     ->get();

        return [
            'tracer_rejections' => $tracerRejections->map(function ($item) {
                return [
                    'user' => $item->tracerUser->full_name ?? 'Unknown',
                    'rejections' => $item->rejection_count
                ];
            }),
            'cgp_rejections' => $cgpRejections->map(function ($item) {
                return [
                    'user' => $item->cgpUser->full_name ?? 'Unknown',
                    'rejections' => $item->rejection_count
                ];
            })
        ];
    }
}
