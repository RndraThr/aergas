<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Models\{JalurCluster, JalurLineNumber, JalurLoweringData, JalurJointData, PhotoApproval};
use App\Services\{PhotoApprovalService, FolderOrganizationService};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{DB, Log, Auth};
use Exception;

class CgpJalurApprovalController extends Controller implements HasMiddleware
{
    public function __construct(
        private PhotoApprovalService $photoApprovalService,
        private FolderOrganizationService $folderOrganizationService
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:cgp,super_admin,jalur'),
        ];
    }

    /**
     * Level 1: Show only clusters with tracer-approved photos (ready for CGP review)
     */
    public function clusters(Request $request)
    {
        try {
            $search = $request->input('search');
            $filter = $request->input('filter', 'all'); // all, pending, approved, no_evidence

            // Only show clusters that have lines with tracer-approved photos
            $clusters = JalurCluster::with('lineNumbers')
            ->withCount([
                'lineNumbers',
                'lineNumbers as lines_with_photos' => function($q) {
                    $q->whereHas('loweringData.photoApprovals', function($photoQ) {
                        $photoQ->whereIn('photo_status', ['tracer_approved', 'cgp_pending']);
                    });
                }
            ])
            ->whereHas('lineNumbers.loweringData.photoApprovals', function($q) {
                // Only show clusters with tracer-approved photos or higher
                $q->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected']);
            })
            ->when($search, function($q) use ($search) {
                $q->where('nama_cluster', 'like', "%{$search}%")
                  ->orWhere('kode_cluster', 'like', "%{$search}%");
            })
            ->orderBy('nama_cluster')
            ->paginate(20)
            ->through(function ($cluster) {
                $cluster->approval_stats = $this->getClusterCgpApprovalStats($cluster);
                return $cluster;
            });

            // Apply filters
            if ($filter !== 'all') {
                $clusters = $clusters->filter(function ($cluster) use ($filter) {
                    return match($filter) {
                        'pending' => $cluster->approval_stats['pending_photos'] > 0,
                        'approved' => $cluster->approval_stats['approved_photos'] > 0 && $cluster->approval_stats['pending_photos'] === 0,
                        'no_evidence' => $cluster->approval_stats['total_photos'] === 0,
                        default => true
                    };
                });
            }

            if ($request->ajax() || $request->input('ajax')) {
                return response()->json([
                    'success' => true,
                    'data' => $clusters
                ]);
            }

            return view('approvals.cgp.jalur.clusters', [
                'clusters' => $clusters,
                'search' => $search,
                'filter' => $filter
            ]);

        } catch (Exception $e) {
            Log::error('CGP jalur clusters error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat data');
        }
    }

    /**
     * Level 2: Show only lines with tracer-approved photos (ready for CGP review)
     */
    public function lines(Request $request, int $clusterId)
    {
        $cluster = JalurCluster::findOrFail($clusterId);

        $search = $request->input('search');
        $filter = $request->input('filter', 'all');
        $perPage = $request->input('per_page', 20);

        // Only show lines that have photos ready for CGP review (tracer-approved and above)
        $lines = $cluster->lineNumbers()
            ->with(['loweringData.photoApprovals.cgpUser', 'cluster'])
            ->whereHas('loweringData.photoApprovals', function($q) {
                // Only show lines with tracer-approved photos or higher
                $q->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected']);
            })
            ->when($search, function ($q) use ($search) {
                $q->search($search);
            })
            ->paginate($perPage)
            ->through(function ($line) {
                $stats = $this->getLineCgpApprovalStats($line);
                $line->approval_stats = $stats;
                return $line;
            });

        // Apply filters
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

        if ($request->ajax() || $request->input('ajax')) {
            return response()->json([
                'success' => true,
                'data' => $lines
            ]);
        }

        return view('approvals.cgp.jalur.lines', [
            'cluster' => $cluster,
            'lines' => $lines,
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    /**
     * Level 3: Show evidence photos for a line (grouped by date)
     */
    public function evidence(Request $request, int $lineId)
    {
        try {
            $line = JalurLineNumber::with([
                'cluster',
                'loweringData' => function ($q) {
                    $q->orderBy('tanggal_jalur', 'desc');
                },
                'loweringData.photoApprovals' => function ($q) {
                    // Only show photos ready for CGP review (tracer_approved and above)
                    $q->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected'])
                      ->orderBy('photo_field_name', 'asc');
                }
            ])->findOrFail($lineId);

            $filterDate = $request->input('filter_date', 'all');
            $sortBy = $request->input('sort', 'date_desc');

            // Get all lowering data (work dates) with photos ready for CGP review
            $workDates = $line->loweringData()
                ->with(['photoApprovals.tracerUser', 'photoApprovals.cgpUser', 'createdBy'])
                ->whereHas('photoApprovals', function($q) {
                    // Only show dates that have photos ready for CGP review
                    $q->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected']);
                })
                ->when($filterDate !== 'all', function ($q) use ($filterDate) {
                    $q->whereDate('tanggal_jalur', $filterDate);
                })
                ->orderBy('tanggal_jalur', $sortBy === 'date_desc' ? 'desc' : 'asc')
                ->get()
                ->map(function ($lowering) {
                    // Calculate stats per date for CGP approval
                    // Only count photos that are ready for CGP review
                    $photos = $lowering->photoApprovals()
                        ->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected'])
                        ->get();

                    $lowering->date_stats = [
                        'total' => $photos->count(),
                        'approved' => $photos->where('photo_status', 'cgp_approved')->count(),
                        'pending' => $photos->whereIn('photo_status', ['tracer_approved', 'cgp_pending'])->count(),
                        'rejected' => $photos->where('photo_status', 'cgp_rejected')->count(),
                    ];
                    return $lowering;
                });

            // Calculate line statistics for CGP approval
            $lineStats = $this->getLineCgpApprovalStats($line);

            return view('approvals.cgp.jalur.evidence', [
                'line' => $line,
                'workDates' => $workDates,
                'lineStats' => $lineStats,
                'filterDate' => $filterDate,
                'sortBy' => $sortBy,
            ]);

        } catch (Exception $e) {
            Log::error('CGP jalur evidence error: ' . $e->getMessage());
            return back()->with('error', 'Data tidak ditemukan');
        }
    }

    /**
     * Approve individual photo
     */
    public function approvePhoto(Request $request)
    {
        $request->validate([
            'photo_id' => 'required|integer',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $photoApproval = PhotoApproval::findOrFail($request->get('photo_id'));
            $notes = $request->get('notes');

            // approveByCgp has its own transaction
            $result = $this->photoApprovalService->approveByCgp(
                $photoApproval->id,
                Auth::id(),
                $notes
            );

            // Auto-organize jalur photo after approval
            try {
                if ($photoApproval->module_name === 'jalur_lowering') {
                    $moduleData = JalurLoweringData::with('lineNumber')->find($photoApproval->module_record_id);
                    if ($moduleData) {
                        $this->folderOrganizationService->organizeJalurPhotosAfterCgpApproval(
                            $moduleData->line_number_id,
                            $moduleData->tanggal_jalur->format('Y-m-d'),
                            'jalur_lowering'
                        );
                    }
                } else {
                    $moduleData = JalurJointData::with('lineNumber')->find($photoApproval->module_record_id);
                    if ($moduleData) {
                        $this->folderOrganizationService->organizeJalurPhotosAfterCgpApproval(
                            $moduleData->lineNumber->id,
                            $moduleData->tanggal_joint->format('Y-m-d'),
                            'jalur_joint'
                        );
                    }
                }
            } catch (Exception $e) {
                Log::warning('Photo organization failed but approval succeeded', [
                    'photo_id' => $photoApproval->id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Photo berhasil di-approve',
                'photo_id' => $photoApproval->id
            ]);

        } catch (Exception $e) {
            Log::error('CGP jalur approve photo error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Reject individual photo
     */
    public function rejectPhoto(Request $request)
    {
        $request->validate([
            'photo_id' => 'required|integer',
            'notes' => 'required|string|min:10|max:1000'
        ]);

        try {
            $photoApproval = PhotoApproval::findOrFail($request->get('photo_id'));
            $notes = $request->get('notes');

            // rejectByCgp has its own transaction
            $result = $this->photoApprovalService->rejectByCgp(
                $photoApproval->id,
                Auth::id(),
                $notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Photo berhasil di-reject',
                'photo_id' => $photoApproval->id
            ]);

        } catch (Exception $e) {
            Log::error('CGP jalur reject photo error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Approve all photos for entire line
     */
    public function approveLine(Request $request)
    {
        $request->validate([
            'line_id' => 'required|integer|exists:jalur_line_numbers,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $line = JalurLineNumber::with('loweringData')->findOrFail($request->line_id);

            // Get all photos ready for CGP approval (tracer_approved or cgp_pending)
            $allPhotos = PhotoApproval::whereIn('module_record_id',
                $line->loweringData->pluck('id')
            )
            ->where('module_name', 'jalur_lowering')
            ->whereIn('photo_status', ['tracer_approved', 'cgp_pending'])
            ->get();

            if ($allPhotos->isEmpty()) {
                throw new Exception('Tidak ada foto yang perlu di-approve untuk line ini');
            }

            $approved = 0;
            $failed = 0;
            $errors = [];

            foreach ($allPhotos as $photo) {
                try {
                    // Each approveByCgp call has its own transaction
                    $this->photoApprovalService->approveByCgp(
                        $photo->id,
                        Auth::id(),
                        $request->notes
                    );
                    $approved++;
                } catch (Exception $e) {
                    $failed++;
                    $errors[] = "Photo {$photo->id}: " . $e->getMessage();
                    Log::warning('Failed to approve photo in batch', [
                        'photo_id' => $photo->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Auto-organize all approved photos
            if ($approved > 0) {
                try {
                    foreach ($line->loweringData as $lowering) {
                        if ($lowering->tanggal_jalur) {
                            $this->folderOrganizationService->organizeJalurPhotosAfterCgpApproval(
                                $line->id,
                                $lowering->tanggal_jalur->format('Y-m-d'),
                                'jalur_lowering'
                            );
                        }
                    }
                } catch (Exception $e) {
                    Log::warning('Batch photo organization failed but approvals succeeded', [
                        'line_id' => $line->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($failed > 0 && $approved === 0) {
                throw new Exception('Semua foto gagal di-approve: ' . implode(', ', $errors));
            }

            $message = "Line {$line->line_number} berhasil di-approve ({$approved} foto)";
            if ($failed > 0) {
                $message .= ", {$failed} foto gagal";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'approved_count' => $approved,
                'failed_count' => $failed,
                'line_id' => $line->id
            ]);

        } catch (Exception $e) {
            Log::error('CGP approve line failed', [
                'line_id' => $request->line_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Approve all photos for a specific date
     */
    public function approveDatePhotos(Request $request)
    {
        $request->validate([
            'line_id' => 'required|integer',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $lineId = $request->get('line_id');
            $date = $request->get('date');
            $notes = $request->get('notes');

            // Get all pending photos for this line and date (ready for CGP review)
            $loweringRecords = JalurLoweringData::where('line_number_id', $lineId)
                ->whereDate('tanggal_jalur', $date)
                ->pluck('id');

            $photos = PhotoApproval::whereIn('module_record_id', $loweringRecords)
                ->where('module_name', 'jalur_lowering')
                ->whereIn('photo_status', ['tracer_approved', 'cgp_pending'])
                ->get();

            if ($photos->isEmpty()) {
                throw new Exception('Tidak ada foto yang perlu di-approve untuk tanggal ini');
            }

            $approved = 0;
            $failed = 0;
            $errors = [];

            foreach ($photos as $photo) {
                try {
                    // Each approveByCgp call has its own transaction
                    $this->photoApprovalService->approveByCgp(
                        $photo->id,
                        Auth::id(),
                        $notes
                    );
                    $approved++;
                } catch (Exception $e) {
                    $failed++;
                    $errors[] = "Photo {$photo->id}: " . $e->getMessage();
                    Log::warning('Failed to approve photo in batch', [
                        'photo_id' => $photo->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Organize all approved photos
            if ($approved > 0) {
                try {
                    $this->folderOrganizationService->organizeJalurPhotosAfterCgpApproval(
                        $lineId,
                        $date,
                        'jalur_lowering'
                    );
                } catch (Exception $e) {
                    Log::warning('Batch photo organization failed but approvals succeeded', [
                        'line_id' => $lineId,
                        'date' => $date,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($failed > 0 && $approved === 0) {
                throw new Exception('Semua foto gagal di-approve: ' . implode(', ', $errors));
            }

            $message = "{$approved} foto berhasil di-approve untuk tanggal {$date}";
            if ($failed > 0) {
                $message .= ", {$failed} foto gagal";
            }

            return back()->with('success', $message);

        } catch (Exception $e) {
            Log::error('CGP jalur batch approve error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // ==================== PRIVATE METHODS ====================

    private function getClusterCgpApprovalStats(JalurCluster $cluster): array
    {
        // Get only lines that have photos ready for CGP review (tracer approved)
        $linesWithCgpReadyPhotos = $cluster->lineNumbers()
            ->whereHas('loweringData.photoApprovals', function($q) {
                $q->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected']);
            })
            ->get();

        $totalLines = $linesWithCgpReadyPhotos->count();
        $linesWithPending = 0;

        // Get all photos for this cluster (only tracer-approved and above)
        $photos = PhotoApproval::whereIn('module_record_id', function($query) use ($cluster) {
            $query->select('id')
                ->from('jalur_lowering_data')
                ->whereIn('line_number_id', function($subQuery) use ($cluster) {
                    $subQuery->select('id')
                        ->from('jalur_line_numbers')
                        ->where('cluster_id', $cluster->id);
                });
        })
        ->where('module_name', 'jalur_lowering')
        ->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected'])
        ->get();

        $totalPhotos = $photos->count();
        // Count pending: both tracer_approved (ready for CGP) and cgp_pending
        $pendingPhotos = $photos->whereIn('photo_status', ['tracer_approved', 'cgp_pending'])->count();
        $approvedPhotos = $photos->where('photo_status', 'cgp_approved')->count();
        $rejectedPhotos = $photos->where('photo_status', 'cgp_rejected')->count();

        // Count lines with pending photos (ready for CGP review)
        foreach ($linesWithCgpReadyPhotos as $line) {
            $lineHasPending = PhotoApproval::whereIn('module_record_id', function($query) use ($line) {
                $query->select('id')
                    ->from('jalur_lowering_data')
                    ->where('line_number_id', $line->id);
            })
            ->where('module_name', 'jalur_lowering')
            ->whereIn('photo_status', ['tracer_approved', 'cgp_pending'])
            ->exists();

            if ($lineHasPending) {
                $linesWithPending++;
            }
        }

        return [
            'total_lines' => $totalLines,  // Only lines with tracer-approved photos
            'lines_with_pending' => $linesWithPending,
            'total_photos' => $totalPhotos,
            'pending_photos' => $pendingPhotos,
            'approved_photos' => $approvedPhotos,
            'rejected_photos' => $rejectedPhotos,
            'percentage' => $totalPhotos > 0 ? round(($approvedPhotos / $totalPhotos) * 100, 2) : 0,
        ];
    }

    private function getLineCgpApprovalStats(JalurLineNumber $line): array
    {
        $totalPhotos = 0;
        $approvedPhotos = 0;
        $cgpApprovedPhotos = 0;
        $pendingPhotos = 0;
        $rejectedPhotos = 0;
        $workDatesCount = 0;
        $workDatesWithPhotos = 0;
        $workDatesFullyApproved = 0;
        $workDates = [];
        $workDatesDetail = [];
        $rejections = []; // Collect rejection details

        foreach ($line->loweringData as $lowering) {
            $hasTracerApprovedPhotos = false;
            $dateAllApproved = true;

            // Only count photos that are tracer-approved or higher
            $cgpReadyPhotos = $lowering->photoApprovals->whereIn('photo_status', [
                'tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected'
            ]);

            foreach ($cgpReadyPhotos as $photo) {
                $hasTracerApprovedPhotos = true;
                $totalPhotos++;

                if ($photo->photo_status === 'cgp_approved') {
                    $cgpApprovedPhotos++;
                    $approvedPhotos++;
                } elseif ($photo->photo_status === 'tracer_approved') {
                    // tracer_approved = pending for CGP
                    $pendingPhotos++;
                    $dateAllApproved = false;
                } elseif ($photo->photo_status === 'cgp_pending') {
                    $pendingPhotos++;
                    $dateAllApproved = false;
                } elseif ($photo->photo_status === 'cgp_rejected') {
                    $rejectedPhotos++;
                    $dateAllApproved = false;

                    // Collect rejection details
                    $rejections[] = [
                        'field_name' => $photo->photo_field_name,
                        'label' => ucwords(str_replace('_', ' ', $photo->photo_field_name)),
                        'user_name' => $photo->cgpUser->name ?? 'Unknown',
                        'user_id' => $photo->cgp_rejected_by,
                        'rejected_at' => $photo->cgp_rejected_at ? $photo->cgp_rejected_at->format('d/m/Y H:i') : '-',
                        'notes' => $photo->cgp_notes ?? '-',
                    ];
                }
            }

            // Only count dates that have tracer-approved photos
            if ($hasTracerApprovedPhotos) {
                $workDatesCount++;
                $workDatesWithPhotos++;

                if ($dateAllApproved && $cgpReadyPhotos->count() > 0) {
                    $workDatesFullyApproved++;
                }

                if ($lowering->tanggal_jalur) {
                    $workDates[] = $lowering->tanggal_jalur;
                    $workDatesDetail[] = [
                        'date' => $lowering->tanggal_jalur->format('Y-m-d'),
                        'penggelaran' => $lowering->penggelaran ?? 0,
                        'bongkaran' => $lowering->bongkaran ?? 0,
                        'photos_count' => $cgpReadyPhotos->count(),
                    ];
                }
            }
        }

        $percentage = $totalPhotos > 0 ? ($approvedPhotos / $totalPhotos) * 100 : 0;

        // Group rejections by user for better display
        $rejectionsByUser = [];
        foreach ($rejections as $rejection) {
            $userId = $rejection['user_id'];
            if (!isset($rejectionsByUser[$userId])) {
                $rejectionsByUser[$userId] = [
                    'user_id' => $userId,
                    'user_name' => $rejection['user_name'],
                    'count' => 0,
                    'photos' => [],
                ];
            }
            $rejectionsByUser[$userId]['count']++;
            $rejectionsByUser[$userId]['photos'][] = $rejection;
        }

        return [
            'total_photos' => $totalPhotos,
            'approved_photos' => $approvedPhotos,
            'cgp_approved_photos' => $cgpApprovedPhotos,
            'pending_photos' => $pendingPhotos,
            'rejected_photos' => $rejectedPhotos,
            'work_dates_count' => $workDatesCount,
            'work_dates_with_photos' => $workDatesWithPhotos,
            'work_dates_fully_approved' => $workDatesFullyApproved,
            'work_dates' => $workDates,
            'work_dates_detail' => $workDatesDetail,
            'percentage' => round($percentage, 2),
            'status' => $this->determineLineStatus($totalPhotos, $approvedPhotos, $pendingPhotos, $rejectedPhotos),
            'rejections' => [
                'has_rejections' => count($rejections) > 0,
                'count' => count($rejections),
                'all' => $rejections,
                'by_user' => array_values($rejectionsByUser),
            ],
        ];
    }

    private function determineLineStatus(int $total, int $approved, int $pending, int $rejected): string
    {
        if ($total === 0) return 'no_evidence';
        if ($rejected > 0) return 'rejected';
        if ($approved === $total) return 'approved';
        if ($pending > 0) return 'pending';
        return 'pending';
    }
}
