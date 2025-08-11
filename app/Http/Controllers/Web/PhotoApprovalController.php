<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Models\PhotoApproval;
use App\Models\User;
use App\Models\SkData;
use App\Models\SrData;
use App\Services\PhotoApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class PhotoApprovalController extends Controller implements HasMiddleware
{
    public function __construct(private PhotoApprovalService $photoApprovalService) {}

    // Proteksi seluruh endpoint
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            // sesuaikan role yg berhak akses modul approval foto
            new Middleware('role:super_admin,admin,tracer,validasi'),
        ];
    }

    /**
     * List approvals (JSON)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            /** @var User $auth */
            $auth = $request->user();
            $query = PhotoApproval::with(['pelanggan', 'tracerUser', 'cgpUser']);

            // Role-based & advanced filters
            $this->applyRoleBasedFilters($query, $auth, $request);
            $this->applyFilters($query, $request);

            // Sorting (sanitasi)
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDir = strtolower($request->get('sort_direction', 'desc'));
            $sortDir = in_array($sortDir, ['asc','desc'], true) ? $sortDir : 'desc';

            $allowedSort = [
                'created_at','updated_at','photo_status',
                'ai_confidence_score','ai_approved_at','tracer_approved_at','cgp_approved_at'
            ];
            $query->orderBy(in_array($sortBy, $allowedSort, true) ? $sortBy : 'created_at', $sortDir);

            // Pagination
            $perPage = (int) min((int) $request->get('per_page', 15), 50);
            $photoApprovals = $query->paginate($perPage);

            // Computed fields (pakai $auth yg sudah dipastikan)
            $photoApprovals->getCollection()->transform(function (PhotoApproval $item) use ($auth) {
                $item->pending_hours = $this->calculatePendingHours($item);
                $item->sla_status = $this->getSlaStatus($item);
                $item->can_approve = $this->canUserApprove($item, $auth);
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $photoApprovals,
                'meta' => [
                    'stats' => $this->getApprovalStats($auth),
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
                'message' => 'Terjadi kesalahan saat mengambil data persetujuan foto'
            ], 500);
        }
    }

    /**
     * Detail approval
     */
    public function show(int $id): JsonResponse
    {
        try {
            /** @var User $auth */
            $auth = request()->user();

            $photoApproval = PhotoApproval::with([
                'pelanggan','tracerUser','cgpUser'
            ])->findOrFail($id);

            $photoApproval->pending_hours = $this->calculatePendingHours($photoApproval);
            $photoApproval->sla_status = $this->getSlaStatus($photoApproval);
            $photoApproval->can_approve = $this->canUserApprove($photoApproval, $auth);
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
                'message' => 'Photo approval tidak ditemukan'
            ], 404);
        }
    }

    /** Tracer approve */
    public function approveByTracer(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            /** @var User $auth */
            $auth = $request->user();

            $photoApproval = $this->photoApprovalService->approveByTracer(
                $id,
                $auth->id,
                $request->notes
            );
            $this->recalcModuleStatus($photoApproval->reff_id_pelanggan, $photoApproval->module_name);

            Log::info('Photo approved by Tracer via API', [
                'photo_approval_id' => $id,
                'tracer_id' => $auth->id,
                'tracer_name' => $auth->full_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Foto disetujui Tracer',
                'data' => $photoApproval->load(['pelanggan','tracerUser','cgpUser']),
                'next_step' => [
                    'status' => 'cgp_pending',
                    'description' => 'Menunggu review CGP',
                    'estimated_time' => '48 hours'
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Tracer approval failed via API', [
                'photo_approval_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $this->getErrorStatusCode($e));
        }
    }

    /** Tracer reject */
    public function rejectByTracer(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000|min:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            /** @var User $auth */
            $auth = $request->user();

            $photoApproval = $this->photoApprovalService->rejectByTracer(
                $id,
                $auth->id,
                $request->reason
            );
            $this->recalcModuleStatus($photoApproval->reff_id_pelanggan, $photoApproval->module_name);

            Log::info('Photo rejected by Tracer via API', [
                'photo_approval_id' => $id,
                'tracer_id' => $auth->id,
                'reason' => $request->reason
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Foto ditolak oleh Tracer',
                'data' => $photoApproval->load(['pelanggan','tracerUser','cgpUser']),
                'next_step' => [
                    'status' => 'tracer_rejected',
                    'description' => 'Tim lapangan perlu upload ulang foto',
                    'action_required' => 'Upload ulang dengan perbaikan'
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Tracer rejection failed via API', [
                'photo_approval_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $this->getErrorStatusCode($e));
        }
    }

    /** CGP approve (final) */
    public function approveByCgp(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            /** @var User $auth */
            $auth = $request->user();

            $photoApproval = $this->photoApprovalService->approveByCgp(
                $id,
                $auth->id,
                $request->notes
            );
            $this->recalcModuleStatus($photoApproval->reff_id_pelanggan, $photoApproval->module_name);

            Log::info('Photo approved by CGP via API', [
                'photo_approval_id' => $id,
                'cgp_id' => $auth->id,
                'cgp_name' => $auth->full_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Foto disetujui CGP (Final)',
                'data' => $photoApproval->load(['pelanggan','tracerUser','cgpUser']),
                'next_step' => [
                    'status' => 'cgp_approved',
                    'description' => 'Foto final approved',
                    'module_status' => 'Pastikan semua foto modul complete'
                ]
            ]);

        } catch (Exception $e) {
            Log::error('CGP approval failed via API', [
                'photo_approval_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $this->getErrorStatusCode($e));
        }
    }

    /** CGP reject */
    public function rejectByCgp(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000|min:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            /** @var User $auth */
            $auth = $request->user();

            $photoApproval = $this->photoApprovalService->rejectByCgp(
                $id,
                $auth->id,
                $request->reason
            );
            $this->recalcModuleStatus($photoApproval->reff_id_pelanggan, $photoApproval->module_name);

            Log::info('Photo rejected by CGP via API', [
                'photo_approval_id' => $id,
                'cgp_id' => $auth->id,
                'reason' => $request->reason
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Foto ditolak CGP',
                'data' => $photoApproval->load(['pelanggan','tracerUser','cgpUser']),
                'next_step' => [
                    'status' => 'cgp_rejected',
                    'description' => 'Foto ditolak pada tahap final',
                    'action_required' => 'Tim lapangan upload ulang dengan perbaikan'
                ]
            ]);

        } catch (Exception $e) {
            Log::error('CGP rejection failed via API', [
                'photo_approval_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $this->getErrorStatusCode($e));
        }
    }

    /** Batch approve/reject */
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
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            /** @var User $auth */
            $auth = $request->user();

            $this->validateBatchActionPermissions($request->action, $auth);

            $results = $this->photoApprovalService->batchProcessPhotos(
                $request->photo_ids,
                $request->action,
                $auth->id,
                ['notes' => $request->notes, 'reason' => $request->reason]
            );

            Log::info('Batch photo processing completed via API', [
                'action' => $request->action,
                'total_photos' => count($request->photo_ids),
                'successful' => $results['successful'] ?? 0,
                'failed' => $results['failed'] ?? 0,
                'user_id' => $auth->id,
                'user_role' => $auth->role
            ]);

            return response()->json([
                'success' => ($results['successful'] ?? 0) > 0,
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
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $this->getErrorStatusCode($e));
        }
    }

    /** Statistik ringkas */
    public function getStats(Request $request): JsonResponse
    {
        try {
            /** @var User $auth */
            $auth = $request->user();

            $filters = $request->only(['module','date_from','date_to','status','reff_id_pelanggan']);
            $stats = $this->photoApprovalService->getPhotoApprovalStats($filters);
            $roleSpecific = $this->getRoleSpecificStats($auth);
            $slaStats = $this->getSlaStatistics();

            return response()->json([
                'success' => true,
                'data' => [
                    'general_stats' => $stats,
                    'role_specific' => $roleSpecific,
                    'sla_stats' => $slaStats,
                    'filters_applied' => $filters
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching photo approval stats', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil statistik'
            ], 500);
        }
    }

    /** Pending approvals untuk user */
    public function getPendingApprovals(Request $request): JsonResponse
    {
        try {
            /** @var User $auth */
            $auth = $request->user();
            $query = PhotoApproval::with(['pelanggan', 'tracerUser', 'cgpUser']);

            if ($auth->isTracer()) {
                $query->where('photo_status', 'tracer_pending');
            } elseif ($auth->isAdmin()) {
                $query->where('photo_status', 'cgp_pending');
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view pending approvals'
                ], 403);
            }

            $query->orderBy('created_at', 'asc');
            $pending = $query->get();

            $pending->transform(function (PhotoApproval $item) {
                $item->pending_hours = $this->calculatePendingHours($item);
                $item->sla_status = $this->getSlaStatus($item);
                $item->priority_score = $this->calculatePriorityScore($item);
                return $item;
            });

            $pending = $pending->sortByDesc('priority_score')->values();

            return response()->json([
                'success' => true,
                'data' => $pending,
                'meta' => [
                    'total_pending' => $pending->count(),
                    'sla_violations' => $pending->where('sla_status', 'violation')->count(),
                    'sla_warnings' => $pending->where('sla_status', 'warning')->count(),
                    'user_role' => $auth->role
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching pending approvals', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data pending approvals'
            ], 500);
        }
    }

    /* ============================ Helpers (tak berubah besar) ============================ */

    private function applyRoleBasedFilters($query, User $user, Request $request): void
    {
        $filterType = $request->get('type', 'all');

        if ($user->isTracer()) {
            switch ($filterType) {
                case 'pending':     $query->where('photo_status', 'tracer_pending'); break;
                case 'reviewed':    $query->whereIn('photo_status', ['tracer_approved','tracer_rejected']); break;
                case 'my_reviews':  $query->where('tracer_user_id', $user->id); break;
                default:            $query->whereIn('photo_status', ['tracer_pending','tracer_approved','tracer_rejected']);
            }
        } elseif ($user->isAdmin()) {
            switch ($filterType) {
                case 'cgp_review':      $query->where('photo_status', 'cgp_pending'); break;
                case 'cgp_reviewed':    $query->whereIn('photo_status', ['cgp_approved','cgp_rejected']); break;
                case 'my_reviews':      $query->where('cgp_user_id', $user->id); break;
                default: /* admin lihat semua */
            }
        }
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('module_name')) {
            $query->where('module_name', $request->module_name);
        }
        if ($request->filled('photo_status')) {
            $query->where('photo_status', $request->photo_status);
        }
        if ($request->filled('reff_id_pelanggan')) {
            $query->where('reff_id_pelanggan', 'LIKE', '%'.$request->reff_id_pelanggan.'%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->has('ai_confidence_min') && $request->ai_confidence_min !== '') {
            $min = (float) $request->ai_confidence_min;
            $query->where('ai_confidence_score', '>=', $min);
        }
        if ($request->filled('sla_status')) {
            match ($request->sla_status) {
                'violation' => $this->applySlaViolationFilter($query),
                'warning'   => $this->applySlaWarningFilter($query),
                default     => null
            };
        }
    }

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

    private function getSlaStatus(PhotoApproval $photoApproval): string
    {
        $pendingHours = $this->calculatePendingHours($photoApproval);
        if (!$pendingHours) return 'not_applicable';

        $slaLimit = $photoApproval->photo_status === 'tracer_pending' ? 24 : 48;
        $warningLimit = $photoApproval->photo_status === 'tracer_pending' ? 20 : 40;

        return $pendingHours >= $slaLimit ? 'violation'
             : ($pendingHours >= $warningLimit ? 'warning' : 'normal');
    }

    private function calculatePriorityScore(PhotoApproval $photoApproval): int
    {
        $score = 0;
        $pendingHours = (int) ($photoApproval->pending_hours ?? 0);
        $score += $pendingHours * 2;

        $sla = $photoApproval->sla_status ?? 'normal';
        if ($sla === 'violation') $score += 1000;
        elseif ($sla === 'warning') $score += 500;

        if ((int) $photoApproval->ai_confidence_score >= 90) $score += 100;

        return $score;
    }

    private function canUserApprove(PhotoApproval $photoApproval, User $user): bool
    {
        return match ($photoApproval->photo_status) {
            'tracer_pending' => $user->isTracer() || $user->isAdmin(),
            'cgp_pending'    => $user->isAdmin(),
            default          => false
        };
    }

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

    private function getApprovalStats(User $user): array
    {
        $base = [
            'pending_ai'     => PhotoApproval::where('photo_status', 'ai_pending')->count(),
            'pending_tracer' => PhotoApproval::where('photo_status', 'tracer_pending')->count(),
            'pending_cgp'    => PhotoApproval::where('photo_status', 'cgp_pending')->count(),
            'completed_today'=> PhotoApproval::where('photo_status', 'cgp_approved')
                                             ->whereDate('cgp_approved_at', today())->count(),
        ];

        if ($user->isTracer()) {
            $base['my_pending'] = PhotoApproval::where('photo_status', 'tracer_pending')->count();
            $base['my_reviewed_today'] = PhotoApproval::where('tracer_user_id', $user->id)
                                                      ->whereDate('tracer_approved_at', today())->count();
        } elseif ($user->isAdmin()) {
            $base['cgp_pending'] = PhotoApproval::where('photo_status', 'cgp_pending')->count();
            $base['my_cgp_reviewed_today'] = PhotoApproval::where('cgp_user_id', $user->id)
                                                          ->whereDate('cgp_approved_at', today())->count();
        }

        return $base;
    }

    private function getRoleSpecificStats(User $user): array
    {
        if ($user->isTracer()) {
            return [
                'total_reviewed'     => PhotoApproval::where('tracer_user_id', $user->id)->count(),
                'approved_count'     => PhotoApproval::where('tracer_user_id', $user->id)->where('photo_status', 'tracer_approved')->count(),
                'rejected_count'     => PhotoApproval::where('tracer_user_id', $user->id)->where('photo_status', 'tracer_rejected')->count(),
                'avg_review_time'    => $this->getAverageReviewTime($user->id, 'tracer'),
            ];
        } elseif ($user->isAdmin()) {
            return [
                'total_cgp_reviewed' => PhotoApproval::where('cgp_user_id', $user->id)->count(),
                'cgp_approved_count' => PhotoApproval::where('cgp_user_id', $user->id)->where('photo_status', 'cgp_approved')->count(),
                'cgp_rejected_count' => PhotoApproval::where('cgp_user_id', $user->id)->where('photo_status', 'cgp_rejected')->count(),
                'avg_cgp_review_time'=> $this->getAverageReviewTime($user->id, 'cgp'),
            ];
        }
        return [];
    }

    private function getSlaStatistics(): array
    {
        $tracerViolations = PhotoApproval::where('photo_status', 'tracer_pending')
            ->where('ai_approved_at', '<', now()->subHours(24))->count();

        $tracerWarnings = PhotoApproval::where('photo_status', 'tracer_pending')
            ->where('ai_approved_at', '<', now()->subHours(20))
            ->where('ai_approved_at', '>=', now()->subHours(24))->count();

        $cgpViolations = PhotoApproval::where('photo_status', 'cgp_pending')
            ->where('tracer_approved_at', '<', now()->subHours(48))->count();

        $cgpWarnings = PhotoApproval::where('photo_status', 'cgp_pending')
            ->where('tracer_approved_at', '<', now()->subHours(40))
            ->where('tracer_approved_at', '>=', now()->subHours(48))->count();

        return [
            'tracer_sla' => ['violations' => $tracerViolations, 'warnings' => $tracerWarnings, 'limit_hours' => 24],
            'cgp_sla'    => ['violations' => $cgpViolations, 'warnings' => $cgpWarnings, 'limit_hours' => 48],
            'total_violations' => $tracerViolations + $cgpViolations,
            'total_warnings'   => $tracerWarnings + $cgpWarnings
        ];
    }

    private function getAverageReviewTime(int $userId, string $type): ?float
    {
        $q = PhotoApproval::where("{$type}_user_id", $userId)->whereNotNull("{$type}_approved_at");
        if ($type === 'tracer') {
            $q->whereNotNull('ai_approved_at')
              ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, ai_approved_at, tracer_approved_at)) as avg_hours');
        } else {
            $q->whereNotNull('tracer_approved_at')
              ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, tracer_approved_at, cgp_approved_at)) as avg_hours');
        }
        $res = $q->first();
        return $res ? round($res->avg_hours, 1) : null;
    }

    private function applySlaViolationFilter($query): void
    {
        $query->where(function ($q) {
            $q->where(function ($sub) {
                $sub->where('photo_status', 'tracer_pending')
                    ->where('ai_approved_at', '<', now()->subHours(24));
            })->orWhere(function ($sub) {
                $sub->where('photo_status', 'cgp_pending')
                    ->where('tracer_approved_at', '<', now()->subHours(48));
            });
        });
    }

    private function applySlaWarningFilter($query): void
    {
        $query->where(function ($q) {
            $q->where(function ($sub) {
                $sub->where('photo_status', 'tracer_pending')
                    ->where('ai_approved_at', '<', now()->subHours(20))
                    ->where('ai_approved_at', '>=', now()->subHours(24));
            })->orWhere(function ($sub) {
                $sub->where('photo_status', 'cgp_pending')
                    ->where('tracer_approved_at', '<', now()->subHours(40))
                    ->where('tracer_approved_at', '>=', now()->subHours(48));
            });
        });
    }

    private function validateBatchActionPermissions(string $action, User $user): void
    {
        $tracerActions = ['tracer_approve','tracer_reject'];
        $cgpActions = ['cgp_approve','cgp_reject'];

        if (in_array($action, $tracerActions, true) && !($user->isTracer() || $user->isAdmin())) {
            throw new Exception('Unauthorized: Only Tracer or Admin can perform this action');
        }
        if (in_array($action, $cgpActions, true) && !$user->isAdmin()) {
            throw new Exception('Unauthorized: Only Admin can perform CGP actions');
        }
    }

    private function getErrorStatusCode(Exception $e): int
    {
        $msg = $e->getMessage();
        if (str_contains($msg, 'Unauthorized')) return 403;
        if (str_contains($msg, 'not found') || str_contains($msg, 'not in') || str_contains($msg, 'status')) return 422;
        return 500;
    }

    private function recalcModuleStatus(string $reffId, string $module): void
    {
        $modelClass = match ($module) {
            'sk' => SkData::class,
            'sr' => SrData::class,
            // 'mgrt' => MgrtData::class, dst kalau sudah ada
            default => null,
        };
        if (!$modelClass) return;

        $model = $modelClass::where('reff_id_pelanggan', $reffId)->first();
        if ($model && method_exists($model, 'syncModuleStatusFromPhotos')) {
            $model->syncModuleStatusFromPhotos();
        }
    }
}
