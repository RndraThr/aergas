<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JalurCluster;
use App\Models\JalurLineNumber;
use App\Models\JalurLoweringData;
use App\Models\PhotoApproval;
use App\Services\PhotoApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TracerJalurApprovalController extends Controller
{
    private PhotoApprovalService $photoApprovalService;

    public function __construct(PhotoApprovalService $photoApprovalService)
    {
        $this->photoApprovalService = $photoApprovalService;
    }

    /**
     * LEVEL 1: Cluster Selection Page
     * Show all clusters with pending review summary
     */
    public function clusters(Request $request)
    {
        $search = $request->input('search');
        $filter = $request->input('filter', 'all'); // all, pending, approved
        $perPage = $request->input('per_page', 12);

        $clusters = JalurCluster::query()
            ->with(['lineNumbers.loweringData.photoApprovals'])
            ->when($search, function ($q) use ($search) {
                $q->search($search);
            })
            ->where('is_active', true)
            ->paginate($perPage)
            ->through(function ($cluster) {
                // Calculate approval statistics for this cluster
                $stats = $this->getClusterApprovalStats($cluster);
                $cluster->approval_stats = $stats;
                return $cluster;
            });

        // Apply filters after pagination (if needed, refine this)
        if ($filter === 'pending') {
            $clusters = $clusters->filter(fn($c) => $c->approval_stats['pending_photos'] > 0);
        } elseif ($filter === 'approved') {
            $clusters = $clusters->filter(fn($c) => $c->approval_stats['pending_photos'] === 0 && $c->approval_stats['total_photos'] > 0);
        }

        // Calculate overall stats
        $stats = [
            'total_clusters' => $clusters->total(),
            'pending_review' => $clusters->filter(fn($c) => $c->approval_stats['pending_photos'] > 0)->count(),
            'pending_photos' => $clusters->sum(fn($c) => $c->approval_stats['pending_photos']),
            'approved_photos' => $clusters->sum(fn($c) => $c->approval_stats['approved_photos'])
        ];

        // AJAX request - return JSON
        if ($request->ajax() || $request->input('ajax')) {
            return response()->json([
                'success' => true,
                'data' => $clusters,
                'stats' => $stats
            ]);
        }

        return view('approvals.tracer.jalur.clusters', [
            'clusters' => $clusters,
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    /**
     * LEVEL 2: Line Selection Page
     * Show all lines in a cluster with their approval status
     */
    public function lines(Request $request, int $clusterId)
    {
        $cluster = JalurCluster::findOrFail($clusterId);

        $search = $request->input('search');
        $filter = $request->input('filter', 'all'); // all, pending, approved, rejected, no_evidence
        $perPage = $request->input('per_page', 20);

        $lines = $cluster->lineNumbers()
            ->with(['loweringData.photoApprovals', 'cluster'])
            ->when($search, function ($q) use ($search) {
                $q->search($search);
            })
            ->paginate($perPage)
            ->through(function ($line) {
                // Calculate approval statistics for this line
                $stats = $this->getLineApprovalStats($line);
                $line->approval_stats = $stats;
                return $line;
            });

        // Apply filters after pagination
        if ($filter !== 'all') {
            $lines = $lines->filter(function ($line) use ($filter) {
                return match($filter) {
                    'pending' => $line->approval_stats['status'] === 'pending',
                    'approved' => $line->approval_stats['status'] === 'approved',
                    'rejected' => $line->approval_stats['status'] === 'rejected',
                    'no_evidence' => $line->approval_stats['total_photos'] === 0,
                    default => true
                };
            });
        }

        // Calculate overall stats
        $stats = [
            'total_lines' => $lines->total(),
            'pending_lines' => $lines->filter(fn($l) => $l->approval_stats['status'] === 'pending')->count(),
            'approved_lines' => $lines->filter(fn($l) => $l->approval_stats['status'] === 'approved')->count(),
            'rejected_lines' => $lines->filter(fn($l) => $l->approval_stats['status'] === 'rejected')->count(),
            'no_evidence_lines' => $lines->filter(fn($l) => $l->approval_stats['total_photos'] === 0)->count()
        ];

        // AJAX request - return JSON
        if ($request->ajax() || $request->input('ajax')) {
            return response()->json([
                'success' => true,
                'data' => $lines,
                'stats' => $stats
            ]);
        }

        return view('approvals.tracer.jalur.lines', [
            'cluster' => $cluster,
            'lines' => $lines,
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    /**
     * LEVEL 3: Evidence Review Page
     * Show all evidence photos from all dates for a line
     */
    public function evidence(Request $request, int $lineId)
    {
        $line = JalurLineNumber::with([
            'cluster',
            'loweringData' => function ($q) {
                $q->orderBy('tanggal_jalur', 'asc');
            },
            'loweringData.photoApprovals' => function ($q) {
                $q->orderBy('photo_field_name', 'asc');
            }
        ])->findOrFail($lineId);

        $filterDate = $request->input('filter_date', 'all'); // all, specific date
        $sortBy = $request->input('sort', 'date_asc'); // date_asc, date_desc

        // Get all lowering data (work dates) with photos
        $workDates = $line->loweringData()
            ->with(['photoApprovals.tracerUser', 'photoApprovals.cgpUser', 'createdBy'])
            ->when($filterDate !== 'all', function ($q) use ($filterDate) {
                $q->whereDate('tanggal_jalur', $filterDate);
            })
            ->orderBy('tanggal_jalur', $sortBy === 'date_desc' ? 'desc' : 'asc')
            ->get()
            ->map(function ($lowering) {
                // Calculate stats per date
                $photos = $lowering->photoApprovals;
                $lowering->date_stats = [
                    'total' => $photos->count(),
                    'approved' => $photos->where('photo_status', 'tracer_approved')->count(),
                    'cgp_approved' => $photos->where('photo_status', 'cgp_approved')->count(),
                    'pending' => $photos->whereIn('photo_status', ['tracer_pending', 'draft'])->count(),
                    'rejected' => $photos->where('photo_status', 'tracer_rejected')->count(),
                ];
                return $lowering;
            });

        // Calculate line summary
        $lineStats = $this->getLineApprovalStats($line);

        return view('approvals.tracer.jalur.evidence', [
            'line' => $line,
            'workDates' => $workDates,
            'lineStats' => $lineStats,
            'filterDate' => $filterDate,
            'sortBy' => $sortBy,
        ]);
    }

    /**
     * Approve single photo
     */
    public function approvePhoto(Request $request)
    {
        $request->validate([
            'photo_id' => 'required|integer|exists:photo_approvals,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $photo = PhotoApproval::findOrFail($request->photo_id);

            // Use PhotoApprovalService to approve
            $this->photoApprovalService->approvePhotoByTracer(
                $photo,
                Auth::id(),
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Photo berhasil di-approve',
                'photo_id' => $photo->id,
                'new_status' => 'tracer_approved'
            ]);

        } catch (\Exception $e) {
            Log::error('Approve photo failed', [
                'photo_id' => $request->photo_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Reject single photo
     */
    public function rejectPhoto(Request $request)
    {
        $request->validate([
            'photo_id' => 'required|integer|exists:photo_approvals,id',
            'notes' => 'required|string|min:10|max:500',
        ]);

        try {
            $photo = PhotoApproval::findOrFail($request->photo_id);

            // Use PhotoApprovalService to reject
            $this->photoApprovalService->rejectPhotoByTracer(
                $photo,
                Auth::id(),
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Photo berhasil di-reject',
                'photo_id' => $photo->id,
                'new_status' => 'tracer_rejected'
            ]);

        } catch (\Exception $e) {
            Log::error('Reject photo failed', [
                'photo_id' => $request->photo_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Approve all photos for a specific date (lowering record)
     */
    public function approveDatePhotos(Request $request)
    {
        $request->validate([
            'lowering_id' => 'required|integer|exists:jalur_lowering_data,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $lowering = JalurLoweringData::findOrFail($request->lowering_id);
            $photos = PhotoApproval::where('module_name', 'jalur_lowering')
                ->where('module_record_id', $lowering->id)
                ->whereIn('photo_status', ['tracer_pending', 'draft'])
                ->get();

            if ($photos->isEmpty()) {
                throw new \Exception('Tidak ada foto yang perlu di-approve untuk tanggal ini');
            }

            $approved = 0;
            foreach ($photos as $photo) {
                $this->photoApprovalService->approvePhotoByTracer(
                    $photo,
                    Auth::id(),
                    $request->notes
                );
                $approved++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$approved} foto berhasil di-approve",
                'approved_count' => $approved
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Approve date photos failed', [
                'lowering_id' => $request->lowering_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Approve entire line (all photos from all dates)
     */
    public function approveLine(Request $request)
    {
        $request->validate([
            'line_id' => 'required|integer|exists:jalur_line_numbers,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $line = JalurLineNumber::with('loweringData')->findOrFail($request->line_id);

            $allPhotos = PhotoApproval::whereIn('module_record_id',
                $line->loweringData->pluck('id')
            )
            ->where('module_name', 'jalur_lowering')
            ->whereIn('photo_status', ['tracer_pending', 'draft'])
            ->get();

            if ($allPhotos->isEmpty()) {
                throw new \Exception('Tidak ada foto yang perlu di-approve untuk line ini');
            }

            $approved = 0;
            foreach ($allPhotos as $photo) {
                $this->photoApprovalService->approvePhotoByTracer(
                    $photo,
                    Auth::id(),
                    $request->notes
                );
                $approved++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Line {$line->line_number} berhasil di-approve ({$approved} foto)",
                'approved_count' => $approved,
                'line_id' => $line->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Approve line failed', [
                'line_id' => $request->line_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Calculate cluster approval statistics
     */
    private function getClusterApprovalStats(JalurCluster $cluster): array
    {
        $totalLines = 0;
        $linesWithPending = 0;
        $totalPhotos = 0;
        $approvedPhotos = 0;
        $pendingPhotos = 0;
        $rejectedPhotos = 0;

        foreach ($cluster->lineNumbers as $line) {
            $totalLines++;

            foreach ($line->loweringData as $lowering) {
                foreach ($lowering->photoApprovals as $photo) {
                    $totalPhotos++;

                    if (in_array($photo->photo_status, ['tracer_approved', 'cgp_approved', 'cgp_pending'])) {
                        $approvedPhotos++;
                    } elseif (in_array($photo->photo_status, ['tracer_pending', 'draft'])) {
                        $pendingPhotos++;
                    } elseif ($photo->photo_status === 'tracer_rejected') {
                        $rejectedPhotos++;
                    }
                }
            }

            // Count lines with pending photos
            $lineStats = $this->getLineApprovalStats($line);
            if ($lineStats['pending_photos'] > 0) {
                $linesWithPending++;
            }
        }

        return [
            'total_lines' => $totalLines,
            'lines_with_pending' => $linesWithPending,
            'total_photos' => $totalPhotos,
            'approved_photos' => $approvedPhotos,
            'pending_photos' => $pendingPhotos,
            'rejected_photos' => $rejectedPhotos,
            'status' => $this->determineClusterStatus($totalPhotos, $approvedPhotos, $pendingPhotos, $rejectedPhotos),
        ];
    }

    /**
     * Calculate line approval statistics
     */
    private function getLineApprovalStats(JalurLineNumber $line): array
    {
        $totalPhotos = 0;
        $approvedPhotos = 0;
        $tracerApprovedPhotos = 0;
        $cgpApprovedPhotos = 0;
        $pendingPhotos = 0;
        $rejectedPhotos = 0;
        $workDatesCount = $line->loweringData->count();
        $workDatesWithPhotos = 0;
        $workDatesFullyApproved = 0;
        $workDates = []; // Array to store actual work dates
        $workDatesDetail = []; // Array to store dates with penggelaran detail

        foreach ($line->loweringData as $lowering) {
            $hasPhotos = false;
            $dateAllApproved = true;

            foreach ($lowering->photoApprovals as $photo) {
                $hasPhotos = true;
                $totalPhotos++;

                if ($photo->photo_status === 'tracer_approved') {
                    $tracerApprovedPhotos++;
                    $approvedPhotos++;
                } elseif ($photo->photo_status === 'cgp_approved') {
                    $cgpApprovedPhotos++;
                    $approvedPhotos++;
                } elseif ($photo->photo_status === 'cgp_pending') {
                    $approvedPhotos++;
                } elseif (in_array($photo->photo_status, ['tracer_pending', 'draft'])) {
                    $pendingPhotos++;
                    $dateAllApproved = false;
                } elseif ($photo->photo_status === 'tracer_rejected') {
                    $rejectedPhotos++;
                    $dateAllApproved = false;
                }
            }

            if ($hasPhotos) {
                $workDatesWithPhotos++;
                if ($dateAllApproved && $lowering->photoApprovals->count() > 0) {
                    $workDatesFullyApproved++;
                }
            }

            // Store the work date
            if ($lowering->tanggal_lowering) {
                $workDates[] = $lowering->tanggal_lowering;
            }

            // Store date with penggelaran detail
            $workDatesDetail[] = [
                'date' => $lowering->tanggal_lowering,
                'penggelaran' => $lowering->penggelaran ?? 0,
                'bongkaran' => $lowering->bongkaran ?? 0,
                'photos_count' => $lowering->photoApprovals->count(),
            ];
        }

        $percentage = $totalPhotos > 0 ? ($approvedPhotos / $totalPhotos) * 100 : 0;

        return [
            'total_photos' => $totalPhotos,
            'approved_photos' => $approvedPhotos,
            'tracer_approved_photos' => $tracerApprovedPhotos,
            'cgp_approved_photos' => $cgpApprovedPhotos,
            'pending_photos' => $pendingPhotos,
            'rejected_photos' => $rejectedPhotos,
            'work_dates_count' => $workDatesCount,
            'work_dates_with_photos' => $workDatesWithPhotos,
            'work_dates_fully_approved' => $workDatesFullyApproved,
            'work_dates' => $workDates, // Add actual dates array
            'work_dates_detail' => $workDatesDetail, // Add dates with penggelaran detail
            'percentage' => round($percentage, 2),
            'status' => $this->determineLineStatus($totalPhotos, $approvedPhotos, $pendingPhotos, $rejectedPhotos),
        ];
    }

    /**
     * Determine cluster status based on photos
     */
    private function determineClusterStatus(int $total, int $approved, int $pending, int $rejected): string
    {
        if ($total === 0) return 'no_evidence';
        if ($rejected > 0) return 'rejected';
        if ($approved === $total) return 'approved';
        if ($pending > 0) return 'pending';
        return 'unknown';
    }

    /**
     * Determine line status based on photos
     */
    private function determineLineStatus(int $total, int $approved, int $pending, int $rejected): string
    {
        if ($total === 0) return 'no_evidence';
        if ($rejected > 0) return 'rejected';
        if ($approved === $total) return 'approved';
        if ($pending > 0) return 'pending';
        return 'partial';
    }
}
