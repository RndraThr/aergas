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
    ) {
    }

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
                    'lineNumbers as lines_with_photos' => function ($q) {
                        $q->whereHas('loweringData.photoApprovals', function ($photoQ) {
                            $photoQ->whereIn('photo_status', ['tracer_approved', 'cgp_pending']);
                        });
                    }
                ])
                ->whereHas('lineNumbers.loweringData.photoApprovals', function ($q) {
                    // Only show clusters with tracer-approved photos or higher
                    $q->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected']);
                })
                ->when($search, function ($q) use ($search) {
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
                    return match ($filter) {
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
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $perPage = $request->input('per_page', 20);

        // Only show lines that have photos ready for CGP review (tracer-approved and above)
        // Note: We check joints separately since they use string-based relationships
        $linesQuery = $cluster->lineNumbers()
            ->with(['loweringData.photoApprovals.cgpUser', 'cluster'])
            ->whereHas('loweringData.photoApprovals', function ($query) {
                $query->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected']);
            })
            ->when($search, function ($q) use ($search) {
                $q->search($search);
            });

        // Get all lines first
        $allLines = $linesQuery->get();

        // Load joint data for lines in cluster (string-based relationship)
        // Get ALL line numbers in cluster (not just filtered ones) for joint lookup
        $allLineNumbersInCluster = $cluster->lineNumbers()->pluck('line_number')->toArray();

        $jointsForCluster = JalurJointData::with(['photoApprovals.cgpUser', 'fittingType'])
            ->where(function ($q) use ($allLineNumbersInCluster) {
                $q->whereIn('joint_line_from', $allLineNumbersInCluster)
                    ->orWhereIn('joint_line_to', $allLineNumbersInCluster)
                    ->orWhereIn('joint_line_optional', $allLineNumbersInCluster);
            })
            // Only include joints that have photos ready for CGP
            ->whereHas('photoApprovals', function ($query) {
                $query->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected']);
            })
            ->when($search, function ($q) use ($search) {
                // Allow search by joint number (nomor_joint or joint_code)
                $q->where(function ($query) use ($search) {
                    $query->where('nomor_joint', 'like', "%{$search}%")
                        ->orWhere('joint_code', 'like', "%{$search}%");
                });
            })
            ->get();

        // Group joints by line number (use ALL line numbers in cluster)
        $jointsByLine = [];
        foreach ($jointsForCluster as $joint) {
            foreach ($allLineNumbersInCluster as $lineNum) {
                if (
                    $joint->joint_line_from === $lineNum ||
                    $joint->joint_line_to === $lineNum ||
                    $joint->joint_line_optional === $lineNum
                ) {
                    if (!isset($jointsByLine[$lineNum])) {
                        $jointsByLine[$lineNum] = collect();
                    }
                    $jointsByLine[$lineNum]->push($joint);
                }
            }
        }

        // Also check for lines that ONLY have joint photos (no lowering data ready)
        // Find line numbers that have joints ready for CGP but weren't included
        // IMPORTANT: Only add these lines if there's NO search term, or if search matches the line number
        $linesWithJointsOnly = [];
        foreach ($jointsByLine as $lineNum => $joints) {
            $lineExists = $allLines->contains('line_number', $lineNum);
            if (!$lineExists) {
                // This line has joints ready for CGP but no lowering data
                // Only include if:
                // 1. No search term (show all), OR
                // 2. Line number matches search term
                if (!$search || stripos($lineNum, $search) !== false) {
                    $linesWithJointsOnly[] = $lineNum;
                }
            }
        }

        // Add lines that only have joint data (respecting search filter)
        if (!empty($linesWithJointsOnly)) {
            $additionalLines = $cluster->lineNumbers()
                ->with(['loweringData.photoApprovals.cgpUser', 'cluster'])
                ->whereIn('line_number', $linesWithJointsOnly)
                ->get();
            $allLines = $allLines->merge($additionalLines);
            // No need to update $lineNumbers here anymore, we use $allLineNumbersInCluster everywhere
        }

        // Calculate stats for each line (lowering data only)
        $allLines = $allLines->map(function ($line) use ($dateFrom, $dateTo, $jointsByLine) {
            // Assign preloaded joints to avoid N+1 (for stats calculation only)
            $line->_jointDataCache = $jointsByLine[$line->line_number] ?? collect();

            // Calculate approval statistics for lowering data only
            $stats = $this->calculateLineLoweringOnlyStats($line, $dateFrom, $dateTo);

            // Get earliest installation date from lowering data (considering date filter if active)
            if ($dateFrom || $dateTo) {
                // If date filter is active, get earliest date within the filtered range
                $query = $line->loweringData();
                if ($dateFrom) {
                    $query = $query->where('tanggal_jalur', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query = $query->where('tanggal_jalur', '<=', $dateTo);
                }
                $earliestDate = $query->min('tanggal_jalur');
            } else {
                // No date filter, get overall earliest date
                $earliestDate = $line->loweringData()->min('tanggal_jalur');
            }

            // Add all necessary fields
            $line->approval_stats = $stats;
            $line->item_type = 'line';
            $line->approval_status_label = $stats['status_label'];  // Use different name to avoid conflict with accessor
            $line->approval_progress = $stats['progress_percentage'];
            $line->earliest_installation_date = $earliestDate;

            // Append to visible array for JSON serialization
            $line->makeVisible(['approval_stats', 'item_type', 'approval_status_label', 'approval_progress', 'earliest_installation_date']);

            return $line;
        })->filter(function ($line) use ($dateFrom, $dateTo) {
            // Filter out lines that have no data in the date range (if date filter is active)
            if (($dateFrom || $dateTo) && $line->approval_stats['total_photos'] === 0) {
                return false;
            }
            return true;
        });

        // Process joints as separate items
        $jointItems = $jointsForCluster->map(function ($joint) use ($dateFrom, $dateTo) {
            // Calculate stats for this joint
            $stats = $this->calculateJointStats($joint, $dateFrom, $dateTo);

            // Add all necessary fields
            $joint->approval_stats = $stats;
            $joint->item_type = 'joint';
            $joint->approval_status_label = $stats['status_label'];  // Use different name to avoid conflict
            $joint->approval_progress = $stats['progress_percentage'];
            $joint->earliest_installation_date = $joint->tanggal_joint;

            // Append to visible array for JSON serialization
            $joint->makeVisible(['approval_stats', 'item_type', 'approval_status_label', 'approval_progress', 'earliest_installation_date']);

            return $joint;
        })->filter(function ($joint) use ($dateFrom, $dateTo) {
            // Filter out joints that have no data in the date range (if date filter is active)
            if (($dateFrom || $dateTo) && $joint->approval_stats['total_photos'] === 0) {
                return false;
            }
            return true;
        });

        // Combine lines and joints into one collection
        $allItems = $allLines->concat($jointItems);

        // Apply status filter BEFORE sorting
        if ($filter !== 'all') {
            $allItems = $allItems->filter(function ($item) use ($filter) {
                return match ($filter) {
                    'pending' => $item->approval_stats['status'] === 'pending',
                    'approved' => $item->approval_stats['status'] === 'approved',
                    'rejected' => $item->approval_stats['status'] === 'rejected',
                    'no_evidence' => $item->approval_stats['total_photos'] === 0,
                    default => true
                };
            });
        }

        // Sort by status priority (pending > rejected > no_evidence > approved) then by installation date (oldest first)
        $sortedItems = $allItems->sort(function ($a, $b) {
            // Define status priority: pending first, then rejected, then no_evidence, then approved last
            $statusPriority = [
                'pending' => 1,
                'rejected' => 2,
                'no_evidence' => 3,
                'approved' => 4
            ];

            $aPriority = $statusPriority[$a->approval_stats['status']] ?? 99;
            $bPriority = $statusPriority[$b->approval_stats['status']] ?? 99;

            // First sort by status priority
            if ($aPriority !== $bPriority) {
                return $aPriority <=> $bPriority;
            }

            // Then sort by installation date (oldest first) within the same status
            return strcmp($a->earliest_installation_date ?? '', $b->earliest_installation_date ?? '');
        })->values(); // Reset array keys after sorting

        // Manual pagination
        $currentPage = $request->input('page', 1);

        // Convert to array to ensure all dynamic properties are included
        $itemsForPage = $sortedItems->forPage($currentPage, $perPage)->map(function ($item) {
            // Get array representation
            $itemArray = $item->toArray();

            // Ensure custom properties are included (use different names to avoid accessor conflicts)
            $itemArray['approval_stats'] = $item->approval_stats;
            $itemArray['item_type'] = $item->item_type;
            $itemArray['approval_status_label'] = $item->approval_status_label;  // Approval status, not line status
            $itemArray['approval_progress'] = $item->approval_progress;
            $itemArray['earliest_installation_date'] = $item->earliest_installation_date;

            return $itemArray;
        });

        $items = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsForPage,
            $sortedItems->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Calculate overall stats (lines and joints combined)
        $stats = [
            'total_items' => $sortedItems->count(),
            'total_lines' => $sortedItems->where('item_type', 'line')->count(),
            'total_joints' => $sortedItems->where('item_type', 'joint')->count(),
            'pending_items' => $sortedItems->filter(fn($l) => $l->approval_stats['status'] === 'pending')->count(),
            'approved_items' => $sortedItems->filter(fn($l) => $l->approval_stats['status'] === 'approved')->count(),
            'rejected_items' => $sortedItems->filter(fn($l) => $l->approval_stats['status'] === 'rejected')->count(),
            'no_evidence_items' => $sortedItems->filter(fn($l) => $l->approval_stats['total_photos'] === 0)->count(),

            // Aggregated Photo Stats
            'total_photos' => $sortedItems->sum(fn($i) => $i->approval_stats['total_photos']),
            'approved_photos' => $sortedItems->sum(fn($i) => $i->approval_stats['approved_photos']),
            'pending_photos' => $sortedItems->sum(fn($i) => $i->approval_stats['pending_photos']),
            'rejected_photos' => $sortedItems->sum(fn($i) => $i->approval_stats['rejected_photos']),

            // Breakdown Stats
            'breakdown' => [
                'pending_photos' => [
                    'line' => $sortedItems->where('item_type', 'line')->sum(fn($i) => $i->approval_stats['pending_photos']),
                    'joint' => $sortedItems->where('item_type', 'joint')->sum(fn($i) => $i->approval_stats['pending_photos']),
                ],
                'approved_photos' => [
                    'line' => $sortedItems->where('item_type', 'line')->sum(fn($i) => $i->approval_stats['approved_photos']),
                    'joint' => $sortedItems->where('item_type', 'joint')->sum(fn($i) => $i->approval_stats['approved_photos']),
                ],
                'rejected_photos' => [
                    'line' => $sortedItems->where('item_type', 'line')->sum(fn($i) => $i->approval_stats['rejected_photos']),
                    'joint' => $sortedItems->where('item_type', 'joint')->sum(fn($i) => $i->approval_stats['rejected_photos']),
                ],
            ],
        ];

        if ($request->ajax() || $request->input('ajax')) {
            return response()->json([
                'success' => true,
                'data' => $items,
                'stats' => $stats
            ]);
        }

        return view('approvals.cgp.jalur.lines', [
            'cluster' => $cluster,
            'items' => $items, // Changed from 'lines' to 'items' (contains both lines and joints)
            'search' => $search,
            'filter' => $filter,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'stats' => $stats,
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
                ->whereHas('photoApprovals', function ($q) {
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

            // Get all joint data with photos ready for CGP review
            $jointDates = $line->jointDataQuery()
                ->with(['photoApprovals.tracerUser', 'photoApprovals.cgpUser', 'createdBy', 'fittingType'])
                ->whereHas('photoApprovals', function ($q) {
                    // Only show dates that have photos ready for CGP review
                    $q->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected']);
                })
                ->when($filterDate !== 'all', function ($q) use ($filterDate) {
                    $q->whereDate('tanggal_joint', $filterDate);
                })
                ->orderBy('tanggal_joint', $sortBy === 'date_desc' ? 'desc' : 'asc')
                ->get()
                ->map(function ($joint) {
                    // Calculate stats per date for CGP approval
                    // Only count photos that are ready for CGP review
                    $photos = $joint->photoApprovals()
                        ->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected'])
                        ->get();

                    $joint->date_stats = [
                        'total' => $photos->count(),
                        'approved' => $photos->where('photo_status', 'cgp_approved')->count(),
                        'pending' => $photos->whereIn('photo_status', ['tracer_approved', 'cgp_pending'])->count(),
                        'rejected' => $photos->where('photo_status', 'cgp_rejected')->count(),
                    ];
                    return $joint;
                });

            // Calculate line statistics for CGP approval (including both lowering and joint)
            $lineStats = $this->getLineCgpApprovalStats($line);

            return view('approvals.cgp.jalur.evidence', [
                'line' => $line,
                'workDates' => $workDates,
                'jointDates' => $jointDates,
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
     * LEVEL 3: Joint Evidence Review Page
     * Show evidence photos for a specific joint
     */
    public function jointEvidence(Request $request, int $jointId)
    {
        try {
            $joint = JalurJointData::with([
                'photoApprovals' => function ($q) {
                    // Only show photos ready for CGP review
                    $q->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected'])
                        ->orderBy('photo_field_name', 'asc');
                },
                'photoApprovals.tracerUser',
                'photoApprovals.cgpUser',
                'fittingType',
                'createdBy'
            ])->findOrFail($jointId);

            // Get the lines connected by this joint
            $lineFrom = null;
            $lineTo = null;
            $lineOptional = null;

            if ($joint->joint_line_from) {
                $lineFrom = JalurLineNumber::with('cluster')->where('line_number', $joint->joint_line_from)->first();
            }
            if ($joint->joint_line_to) {
                $lineTo = JalurLineNumber::with('cluster')->where('line_number', $joint->joint_line_to)->first();
            }
            if ($joint->joint_line_optional) {
                $lineOptional = JalurLineNumber::with('cluster')->where('line_number', $joint->joint_line_optional)->first();
            }

            // Calculate stats for joint photos (CGP approval context)
            $photos = $joint->photoApprovals;
            $jointStats = [
                'total' => $photos->count(),
                'approved' => $photos->where('photo_status', 'cgp_approved')->count(),
                'pending' => $photos->whereIn('photo_status', ['tracer_approved', 'cgp_pending'])->count(),
                'rejected' => $photos->where('photo_status', 'cgp_rejected')->count(),
                'percentage' => $photos->count() > 0 ? round(($photos->where('photo_status', 'cgp_approved')->count() / $photos->count()) * 100, 2) : 0,
            ];

            $cluster = $lineFrom ? $lineFrom->cluster : ($lineTo ? $lineTo->cluster : null);

            return view('approvals.cgp.jalur.joint-evidence', [
                'joint' => $joint,
                'lineFrom' => $lineFrom,
                'lineTo' => $lineTo,
                'lineOptional' => $lineOptional,
                'cluster' => $cluster,
                'jointStats' => $jointStats,
            ]);

        } catch (Exception $e) {
            Log::error('CGP joint evidence error: ' . $e->getMessage());
            return back()->with('error', 'Data joint tidak ditemukan');
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
            // Use single photo organization for individual approval
            try {
                // Refresh photo to ensure we have the latest status
                $photoApproval->refresh();

                $organizationResult = $this->folderOrganizationService->organizeSingleJalurPhoto($photoApproval);

                if (!$organizationResult['success']) {
                    Log::warning('Photo organization failed but approval succeeded', [
                        'photo_id' => $photoApproval->id,
                        'error' => $organizationResult['error'] ?? 'Unknown error'
                    ]);
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
            $allPhotos = PhotoApproval::whereIn(
                'module_record_id',
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

    /**
     * Batch approve all joints for a specific line on a specific date
     */
    public function approveJointByDate(Request $request)
    {
        $request->validate([
            'line_number' => 'required|string',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $lineNumber = $request->get('line_number');
            $date = $request->get('date');
            $notes = $request->get('notes');

            // Get all joint records that involve this line number on this date
            $jointRecords = JalurJointData::where(function ($query) use ($lineNumber) {
                $query->where('joint_line_from', $lineNumber)
                    ->orWhere('joint_line_to', $lineNumber)
                    ->orWhere('joint_line_optional', $lineNumber);
            })
                ->whereDate('tanggal_joint', $date)
                ->pluck('id');

            if ($jointRecords->isEmpty()) {
                throw new Exception('Tidak ada joint data untuk line dan tanggal ini');
            }

            // Get all photos ready for CGP approval (tracer_approved or cgp_pending)
            $photos = PhotoApproval::whereIn('module_record_id', $jointRecords)
                ->where('module_name', 'jalur_joint')
                ->whereIn('photo_status', ['tracer_approved', 'cgp_pending'])
                ->get();

            if ($photos->isEmpty()) {
                throw new Exception('Tidak ada foto joint yang perlu di-approve untuk tanggal ini');
            }

            $approved = 0;
            $failed = 0;
            $errors = [];

            foreach ($photos as $photo) {
                try {
                    $this->photoApprovalService->approveByCgp(
                        $photo->id,
                        Auth::id(),
                        $notes
                    );
                    $approved++;
                } catch (Exception $e) {
                    $failed++;
                    $errors[] = "Photo {$photo->id}: " . $e->getMessage();
                    Log::warning('Failed to approve joint photo in batch', [
                        'photo_id' => $photo->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Organize all approved joint photos
            if ($approved > 0) {
                try {
                    // Get line_id for organization
                    $line = JalurLineNumber::where('line_number', $lineNumber)->first();
                    if ($line) {
                        $this->folderOrganizationService->organizeJalurPhotosAfterCgpApproval(
                            $line->id,
                            $date,
                            'jalur_joint'
                        );
                    }
                } catch (Exception $e) {
                    Log::warning('Joint photo organization failed but approvals succeeded', [
                        'line_number' => $lineNumber,
                        'date' => $date,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($failed > 0 && $approved === 0) {
                throw new Exception('Semua foto gagal di-approve: ' . implode(', ', $errors));
            }

            $message = "{$approved} foto joint berhasil di-approve untuk tanggal {$date}";
            if ($failed > 0) {
                $message .= ", {$failed} foto gagal";
            }

            return back()->with('success', $message);

        } catch (Exception $e) {
            Log::error('CGP joint batch approve error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Batch approve all joints connected to a line (all dates)
     */
    public function approveJointByLine(Request $request)
    {
        $request->validate([
            'line_number' => 'required|string',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $lineNumber = $request->get('line_number');
            $notes = $request->get('notes');

            // Get all joint records that involve this line number
            $jointRecords = JalurJointData::where(function ($query) use ($lineNumber) {
                $query->where('joint_line_from', $lineNumber)
                    ->orWhere('joint_line_to', $lineNumber)
                    ->orWhere('joint_line_optional', $lineNumber);
            })
                ->pluck('id');

            if ($jointRecords->isEmpty()) {
                throw new Exception('Tidak ada joint data untuk line ini');
            }

            // Get all photos ready for CGP approval
            $photos = PhotoApproval::whereIn('module_record_id', $jointRecords)
                ->where('module_name', 'jalur_joint')
                ->whereIn('photo_status', ['tracer_approved', 'cgp_pending'])
                ->get();

            if ($photos->isEmpty()) {
                throw new Exception('Tidak ada foto joint yang perlu di-approve untuk line ini');
            }

            $approved = 0;
            $failed = 0;
            $errors = [];

            foreach ($photos as $photo) {
                try {
                    $this->photoApprovalService->approveByCgp(
                        $photo->id,
                        Auth::id(),
                        $notes
                    );
                    $approved++;
                } catch (Exception $e) {
                    $failed++;
                    $errors[] = "Photo {$photo->id}: " . $e->getMessage();
                    Log::warning('Failed to approve joint photo in batch', [
                        'photo_id' => $photo->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Organize all approved joint photos by unique dates
            if ($approved > 0) {
                try {
                    $line = JalurLineNumber::where('line_number', $lineNumber)->first();
                    if ($line) {
                        // Get unique dates for this line's joints
                        $uniqueDates = JalurJointData::where(function ($query) use ($lineNumber) {
                            $query->where('joint_line_from', $lineNumber)
                                ->orWhere('joint_line_to', $lineNumber)
                                ->orWhere('joint_line_optional', $lineNumber);
                        })
                            ->distinct()
                            ->pluck('tanggal_joint')
                            ->map(fn($date) => \Carbon\Carbon::parse($date)->format('Y-m-d'))
                            ->unique();

                        foreach ($uniqueDates as $date) {
                            $this->folderOrganizationService->organizeJalurPhotosAfterCgpApproval(
                                $line->id,
                                $date,
                                'jalur_joint'
                            );
                        }
                    }
                } catch (Exception $e) {
                    Log::warning('Joint photo organization failed but approvals succeeded', [
                        'line_number' => $lineNumber,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($failed > 0 && $approved === 0) {
                throw new Exception('Semua foto gagal di-approve: ' . implode(', ', $errors));
            }

            $message = "Line {$lineNumber}: {$approved} foto joint berhasil di-approve";
            if ($failed > 0) {
                $message .= ", {$failed} foto gagal";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'approved_count' => $approved,
                'failed_count' => $failed,
            ]);

        } catch (Exception $e) {
            Log::error('CGP joint line batch approve error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // ==================== PRIVATE METHODS ====================

    private function getClusterCgpApprovalStats(JalurCluster $cluster): array
    {
        // Get only lines that have photos ready for CGP review (tracer approved)
        $linesWithCgpReadyPhotos = $cluster->lineNumbers()
            ->whereHas('loweringData.photoApprovals', function ($q) {
                $q->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected']);
            })
            ->get();

        $totalLines = $linesWithCgpReadyPhotos->count();
        $linesWithPending = 0;
        $totalJoints = 0;
        $jointsWithPending = 0;
        $countedJoints = []; // Track joints already counted to avoid duplicates

        // Get all lowering photos for this cluster (only tracer-approved and above)
        $loweringPhotos = PhotoApproval::whereIn('module_record_id', function ($query) use ($cluster) {
            $query->select('id')
                ->from('jalur_lowering_data')
                ->whereIn('line_number_id', function ($subQuery) use ($cluster) {
                    $subQuery->select('id')
                        ->from('jalur_line_numbers')
                        ->where('cluster_id', $cluster->id);
                });
        })
            ->where('module_name', 'jalur_lowering')
            ->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected'])
            ->get();

        // Get all joint photos for this cluster
        $jointPhotos = PhotoApproval::whereIn('module_record_id', function ($query) use ($cluster) {
            $query->select('id')
                ->from('jalur_joint_data')
                ->where(function ($q) use ($cluster) {
                    $q->whereIn('joint_line_from', function ($subQ) use ($cluster) {
                        $subQ->select('line_number')
                            ->from('jalur_line_numbers')
                            ->where('cluster_id', $cluster->id);
                    })
                        ->orWhereIn('joint_line_to', function ($subQ) use ($cluster) {
                            $subQ->select('line_number')
                                ->from('jalur_line_numbers')
                                ->where('cluster_id', $cluster->id);
                        });
                });
        })
            ->where('module_name', 'jalur_joint')
            ->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected'])
            ->get();

        // Combine photos
        $allPhotos = $loweringPhotos->concat($jointPhotos);

        $totalPhotos = $allPhotos->count();
        // Count pending: both tracer_approved (ready for CGP) and cgp_pending
        $pendingPhotos = $allPhotos->whereIn('photo_status', ['tracer_approved', 'cgp_pending'])->count();
        $approvedPhotos = $allPhotos->where('photo_status', 'cgp_approved')->count();
        $rejectedPhotos = $allPhotos->where('photo_status', 'cgp_rejected')->count();

        // Count lines with pending photos (ready for CGP review)
        foreach ($linesWithCgpReadyPhotos as $line) {
            $lineHasPending = PhotoApproval::whereIn('module_record_id', function ($query) use ($line) {
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

            // Count joints for this line (avoid duplicates)
            $lineJoints = JalurJointData::where(function ($q) use ($line) {
                $q->where('joint_line_from', $line->line_number)
                    ->orWhere('joint_line_to', $line->line_number);
            })
                ->whereHas('photoApprovals', function ($q) {
                    $q->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected']);
                })
                ->get();

            foreach ($lineJoints as $joint) {
                // Skip if this joint was already counted
                if (in_array($joint->id, $countedJoints)) {
                    continue;
                }

                $countedJoints[] = $joint->id;
                $totalJoints++;
                $jointHasPending = $joint->photoApprovals()
                    ->whereIn('photo_status', ['tracer_approved', 'cgp_pending'])
                    ->exists();

                if ($jointHasPending) {
                    $jointsWithPending++;
                }
            }
        }

        $totalItems = $totalLines + $totalJoints;
        $itemsWithPending = $linesWithPending + $jointsWithPending;

        return [
            'total_lines' => $totalLines,  // Only lines with tracer-approved photos
            'total_joints' => $totalJoints,
            'total_items' => $totalItems,
            'lines_with_pending' => $linesWithPending,
            'joints_with_pending' => $jointsWithPending,
            'items_with_pending' => $itemsWithPending,
            'total_photos' => $totalPhotos,
            'pending_photos' => $pendingPhotos,
            'approved_photos' => $approvedPhotos,
            'rejected_photos' => $rejectedPhotos,
            'percentage' => $totalPhotos > 0 ? round(($approvedPhotos / $totalPhotos) * 100, 2) : 0,
        ];
    }

    private function getLineCgpApprovalStats(JalurLineNumber $line, $dateFrom = null, $dateTo = null): array
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

        $loweringData = $line->loweringData;

        // Apply date range filter if provided
        if ($dateFrom || $dateTo) {
            $loweringData = $loweringData->filter(function ($lowering) use ($dateFrom, $dateTo) {
                if (!$lowering->tanggal_jalur) {
                    return false;
                }
                $dateStr = $lowering->tanggal_jalur->format('Y-m-d');

                if ($dateFrom && $dateStr < $dateFrom) {
                    return false;
                }
                if ($dateTo && $dateStr > $dateTo) {
                    return false;
                }
                return true;
            });
        }

        foreach ($loweringData as $lowering) {
            $hasTracerApprovedPhotos = false;
            $dateAllApproved = true;

            // Only count photos that are tracer-approved or higher
            $cgpReadyPhotos = $lowering->photoApprovals->whereIn('photo_status', [
                'tracer_approved',
                'cgp_pending',
                'cgp_approved',
                'cgp_rejected'
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

        // Process Joint Data
        $jointData = $line->jointData;
        $jointDatesCount = $jointData->count();
        $jointDatesWithPhotos = 0;
        $jointDatesFullyApproved = 0;
        $jointDates = [];
        $jointDatesDetail = [];

        // Apply date range filter to joint data if provided
        if ($dateFrom || $dateTo) {
            $jointData = $jointData->filter(function ($joint) use ($dateFrom, $dateTo) {
                if (!$joint->tanggal_joint) {
                    return false;
                }
                $dateStr = $joint->tanggal_joint->format('Y-m-d');

                if ($dateFrom && $dateStr < $dateFrom) {
                    return false;
                }
                if ($dateTo && $dateStr > $dateTo) {
                    return false;
                }
                return true;
            });
        }

        foreach ($jointData as $joint) {
            $hasTracerApprovedPhotos = false;
            $dateAllApproved = true;

            // Only count photos that are tracer-approved or higher
            $cgpReadyPhotos = $joint->photoApprovals->whereIn('photo_status', [
                'tracer_approved',
                'cgp_pending',
                'cgp_approved',
                'cgp_rejected'
            ]);

            foreach ($cgpReadyPhotos as $photo) {
                $hasTracerApprovedPhotos = true;
                $totalPhotos++;

                if ($photo->photo_status === 'cgp_approved') {
                    $cgpApprovedPhotos++;
                    $approvedPhotos++;
                } elseif ($photo->photo_status === 'tracer_approved') {
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

            if ($hasTracerApprovedPhotos) {
                $jointDatesWithPhotos++;

                if ($dateAllApproved && $cgpReadyPhotos->count() > 0) {
                    $jointDatesFullyApproved++;
                }

                if ($joint->tanggal_joint) {
                    $jointDates[] = $joint->tanggal_joint;
                    $jointDatesDetail[] = [
                        'date' => $joint->tanggal_joint->format('Y-m-d'),
                        'fitting_type' => $joint->fittingType?->nama_fitting ?? '-',
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
            'joint_dates_count' => $jointDatesCount,
            'joint_dates_with_photos' => $jointDatesWithPhotos,
            'joint_dates_fully_approved' => $jointDatesFullyApproved,
            'joint_dates' => $jointDates,
            'joint_dates_detail' => $jointDatesDetail,
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

    /**
     * Calculate stats for line lowering data ONLY (without joints)
     * Used when displaying lines and joints as separate cards
     */
    private function calculateLineLoweringOnlyStats(JalurLineNumber $line, $dateFrom = null, $dateTo = null): array
    {
        $totalPhotos = 0;
        $approvedPhotos = 0;
        $pendingPhotos = 0;
        $rejectedPhotos = 0;
        $workDatesCount = 0;
        $workDatesDetail = [];
        $rejections = [];

        $loweringData = $line->loweringData;

        // Apply date range filter if provided
        if ($dateFrom || $dateTo) {
            $loweringData = $loweringData->filter(function ($lowering) use ($dateFrom, $dateTo) {
                if (!$lowering->tanggal_jalur)
                    return false;
                $dateStr = $lowering->tanggal_jalur->format('Y-m-d');
                if ($dateFrom && $dateStr < $dateFrom)
                    return false;
                if ($dateTo && $dateStr > $dateTo)
                    return false;
                return true;
            });
        }

        foreach ($loweringData as $lowering) {
            $hasTracerApprovedPhotos = false;

            $cgpReadyPhotos = $lowering->photoApprovals->whereIn('photo_status', [
                'tracer_approved',
                'cgp_pending',
                'cgp_approved',
                'cgp_rejected'
            ]);

            foreach ($cgpReadyPhotos as $photo) {
                $hasTracerApprovedPhotos = true;
                $totalPhotos++;

                if ($photo->photo_status === 'cgp_approved') {
                    $approvedPhotos++;
                } elseif (in_array($photo->photo_status, ['tracer_approved', 'cgp_pending'])) {
                    $pendingPhotos++;
                } elseif ($photo->photo_status === 'cgp_rejected') {
                    $rejectedPhotos++;
                    $rejections[] = [
                        'field_name' => $photo->photo_field_name,
                        'label' => ucwords(str_replace('_', ' ', $photo->photo_field_name)),
                        'user_name' => $photo->cgpUser->name ?? 'Unknown',
                        'rejected_at' => $photo->cgp_rejected_at ? $photo->cgp_rejected_at->format('d/m/Y H:i') : '-',
                        'notes' => $photo->cgp_notes ?? '-',
                    ];
                }
            }

            if ($hasTracerApprovedPhotos && $lowering->tanggal_jalur) {
                $workDatesCount++;
                $workDatesDetail[] = [
                    'date' => $lowering->tanggal_jalur->format('Y-m-d'),
                    'penggelaran' => $lowering->penggelaran ?? 0,
                    'photos_count' => $cgpReadyPhotos->count(),
                ];
            }
        }

        $percentage = $totalPhotos > 0 ? ($approvedPhotos / $totalPhotos) * 100 : 0;
        $status = $this->determineLineStatus($totalPhotos, $approvedPhotos, $pendingPhotos, $rejectedPhotos);

        // Determine status label (clarify approval level - CGP for CGP controller)
        $statusLabel = match ($status) {
            'pending' => 'Pending CGP Review',
            'approved' => 'Approved by CGP',
            'rejected' => 'Rejected by CGP',
            'no_evidence' => 'No Evidence',
            default => 'Partial'
        };

        return [
            'total_photos' => $totalPhotos,
            'approved_photos' => $approvedPhotos,
            'pending_photos' => $pendingPhotos,
            'rejected_photos' => $rejectedPhotos,
            'work_dates_count' => $workDatesCount,
            'work_dates_detail' => $workDatesDetail,
            'percentage' => round($percentage, 2),
            'status' => $status,
            'status_label' => $statusLabel,
            'progress_percentage' => round($percentage, 2),
            'rejections' => [
                'has_rejections' => count($rejections) > 0,
                'count' => count($rejections),
                'all' => $rejections,
            ],
        ];
    }

    /**
     * Calculate stats for a single joint
     */
    private function calculateJointStats(JalurJointData $joint, $dateFrom = null, $dateTo = null): array
    {
        $totalPhotos = 0;
        $approvedPhotos = 0;
        $pendingPhotos = 0;
        $rejectedPhotos = 0;
        $rejections = [];

        // Apply date filter if provided
        if ($dateFrom || $dateTo) {
            if (!$joint->tanggal_joint) {
                return [
                    'total_photos' => 0,
                    'approved_photos' => 0,
                    'pending_photos' => 0,
                    'rejected_photos' => 0,
                    'percentage' => 0,
                    'status' => 'no_evidence',
                    'rejections' => ['has_rejections' => false, 'count' => 0, 'all' => []],
                ];
            }

            $dateStr = $joint->tanggal_joint->format('Y-m-d');
            if (($dateFrom && $dateStr < $dateFrom) || ($dateTo && $dateStr > $dateTo)) {
                return [
                    'total_photos' => 0,
                    'approved_photos' => 0,
                    'pending_photos' => 0,
                    'rejected_photos' => 0,
                    'percentage' => 0,
                    'status' => 'no_evidence',
                    'rejections' => ['has_rejections' => false, 'count' => 0, 'all' => []],
                ];
            }
        }

        $cgpReadyPhotos = $joint->photoApprovals->whereIn('photo_status', [
            'tracer_approved',
            'cgp_pending',
            'cgp_approved',
            'cgp_rejected'
        ]);

        foreach ($cgpReadyPhotos as $photo) {
            $totalPhotos++;

            if ($photo->photo_status === 'cgp_approved') {
                $approvedPhotos++;
            } elseif (in_array($photo->photo_status, ['tracer_approved', 'cgp_pending'])) {
                $pendingPhotos++;
            } elseif ($photo->photo_status === 'cgp_rejected') {
                $rejectedPhotos++;
                $rejections[] = [
                    'field_name' => $photo->photo_field_name,
                    'label' => ucwords(str_replace('_', ' ', $photo->photo_field_name)),
                    'user_name' => $photo->cgpUser->name ?? 'Unknown',
                    'rejected_at' => $photo->cgp_rejected_at ? $photo->cgp_rejected_at->format('d/m/Y H:i') : '-',
                    'notes' => $photo->cgp_notes ?? '-',
                ];
            }
        }

        $percentage = $totalPhotos > 0 ? ($approvedPhotos / $totalPhotos) * 100 : 0;
        $status = $this->determineLineStatus($totalPhotos, $approvedPhotos, $pendingPhotos, $rejectedPhotos);

        // Calculate work dates detail
        $workDates = [];
        if ($joint->tanggal_joint) {
            $dateStr = $joint->tanggal_joint->format('Y-m-d');
            $photoCount = $cgpReadyPhotos->count();

            if ($photoCount > 0) {
                $workDates[] = [
                    'date' => $dateStr,
                    'photo_count' => $photoCount
                ];
            }
        }

        // Determine status label (clarify approval level - CGP for CGP controller)
        $statusLabel = match ($status) {
            'pending' => 'Pending CGP Review',
            'approved' => 'Approved by CGP',
            'rejected' => 'Rejected by CGP',
            'no_evidence' => 'No Evidence',
            default => 'Partial'
        };

        return [
            'total_photos' => $totalPhotos,
            'approved_photos' => $approvedPhotos,
            'pending_photos' => $pendingPhotos,
            'rejected_photos' => $rejectedPhotos,
            'percentage' => round($percentage, 2),
            'status' => $status,
            'status_label' => $statusLabel,
            'progress_percentage' => round($percentage, 2),
            'work_dates_count' => count($workDates),
            'work_dates_detail' => $workDates,
            'rejections' => [
                'has_rejections' => count($rejections) > 0,
                'count' => count($rejections),
                'all' => $rejections,
            ],
        ];
    }

    /**
     * Revert photo from cgp_approved back to tracer_approved
     */
    public function revertPhoto(Request $request)
    {
        $request->validate([
            'photo_id' => 'required|integer|exists:photo_approvals,id',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $photo = PhotoApproval::findOrFail($request->photo_id);

            // Only allow revert for cgp_approved photos
            if ($photo->photo_status !== 'cgp_approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only CGP approved photos can be reverted'
                ], 400);
            }

            // Step 1: Revert file organization (move file back to original upload folder)
            if ($photo->organized_at && $photo->organized_folder) {
                try {
                    $revertResult = $this->folderOrganizationService->revertJalurPhotoOrganization($photo);

                    if (!$revertResult['success']) {
                        Log::warning('Failed to revert file organization, but continuing with status revert', [
                            'photo_id' => $photo->id,
                            'error' => $revertResult['error'] ?? 'Unknown error'
                        ]);
                    } else {
                        Log::info('File organization reverted successfully', [
                            'photo_id' => $photo->id,
                            'old_folder' => $photo->organized_folder,
                            'new_folder' => $revertResult['new_folder'] ?? 'original'
                        ]);
                    }
                } catch (Exception $e) {
                    Log::warning('Exception during file organization revert, continuing with status revert', [
                        'photo_id' => $photo->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Step 2: Revert photo status to tracer_approved
            // Note: photo record might be already updated by revertJalurPhotoOrganization
            // so we need to refresh it first
            $photo->refresh();

            $photo->update([
                'photo_status' => 'tracer_approved',
                'cgp_user_id' => null,
                'cgp_approved_at' => null,
                'cgp_notes' => $request->notes ?: 'Reverted by CGP',
            ]);

            Log::info('CGP photo reverted', [
                'photo_id' => $photo->id,
                'reverted_by' => Auth::id(),
                'notes' => $request->notes
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Photo berhasil di-revert ke Tracer Approved'
            ]);

        } catch (Exception $e) {
            Log::error('CGP revert photo error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Replace photo (Admin/Super Admin only) - Available for ALL statuses
     */
    public function replacePhoto(Request $request)
    {
        // Only allow admin/super_admin to replace photos
        if (!Auth::user()->hasAnyRole(['admin', 'super_admin'])) {
            return back()->with('error', 'Unauthorized. Only Admin can replace photos.');
        }

        $request->validate([
            'photo_id' => 'required|integer|exists:photo_approvals,id',
            'photo' => 'required|image|mimes:jpeg,jpg,png|max:10240', // max 10MB
        ]);

        try {
            DB::beginTransaction();

            $photoApproval = PhotoApproval::findOrFail($request->get('photo_id'));
            $oldStatus = $photoApproval->photo_status;

            // Get module data
            if ($photoApproval->module_name === 'jalur_lowering') {
                $moduleData = JalurLoweringData::with('lineNumber.cluster')->find($photoApproval->module_record_id);
            } else {
                $moduleData = JalurJointData::with('cluster')->find($photoApproval->module_record_id);
            }

            if (!$moduleData) {
                throw new Exception('Module data not found');
            }

            // Upload new photo to Google Drive
            $uploadedFile = $request->file('photo');
            $googleDriveService = app(\App\Services\GoogleDriveService::class);

            if (!$googleDriveService->isAvailable()) {
                throw new Exception('Google Drive service tidak tersedia: ' . $googleDriveService->getError());
            }

            // Generate filename
            $timestamp = now()->format('Ymd_His');
            $filename = "{$photoApproval->photo_field_name}_{$timestamp}";

            // Save old photo data to audit_logs for history (DO NOT DELETE)
            if ($photoApproval->photo_url) {
                DB::table('audit_logs')->insert([
                    'user_id' => Auth::id(),
                    'action' => 'photo_replaced',
                    'model_type' => 'App\\Models\\PhotoApproval',
                    'model_id' => $photoApproval->id,
                    'reff_id_pelanggan' => $photoApproval->reff_id_pelanggan,
                    'old_values' => json_encode([
                        'photo_url' => $photoApproval->photo_url,
                        'drive_file_id' => $photoApproval->drive_file_id,
                        'drive_link' => $photoApproval->drive_link,
                        'stored_filename' => $photoApproval->stored_filename,
                        'photo_status' => $oldStatus,
                        'tracer_user_id' => $photoApproval->tracer_user_id,
                        'tracer_approved_at' => $photoApproval->tracer_approved_at,
                        'cgp_user_id' => $photoApproval->cgp_user_id,
                        'cgp_approved_at' => $photoApproval->cgp_approved_at,
                    ]),
                    'new_values' => json_encode([
                        'action' => 'replacing_with_new_photo',
                        'timestamp' => $timestamp,
                    ]),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'description' => "Photo replaced for {$photoApproval->photo_field_name}. Old photo preserved in Google Drive for audit purposes.",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('Old photo data saved to audit_logs for history', [
                    'photo_id' => $photoApproval->id,
                    'old_drive_file_id' => $photoApproval->drive_file_id,
                    'old_photo_url' => $photoApproval->photo_url,
                    'note' => 'Old photo NOT deleted - preserved for audit trail'
                ]);
            }

            // Build folder path for Google Drive
            if ($photoApproval->module_name === 'jalur_lowering') {
                $clusterSlug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $moduleData->lineNumber->cluster->nama_cluster));
                $lineNumber = $moduleData->lineNumber->line_number;
                $date = $moduleData->tanggal_jalur->format('Y-m-d');
                $customPath = "jalur_lowering/{$clusterSlug}/{$lineNumber}/{$date}";
            } else {
                $clusterSlug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $moduleData->cluster->nama_cluster));
                $jointNumber = $moduleData->nomor_joint;
                $date = $moduleData->tanggal_joint->format('Y-m-d');
                $customPath = "jalur_joint/{$clusterSlug}/{$jointNumber}/{$date}";
            }

            // Ensure folder structure exists in Google Drive
            $folderId = $googleDriveService->ensureNestedFolders($customPath);

            // Upload new photo to Google Drive with extension
            $ext = strtolower($uploadedFile->getClientOriginalExtension() ?: 'jpg');
            $fullFileName = $filename . '.' . $ext;

            $uploadResult = $googleDriveService->uploadFile(
                $uploadedFile,
                $folderId,
                $fullFileName
            );

            // Prepare update data
            $updateData = [
                'photo_url' => $uploadResult['webViewLink'] ?? $uploadResult['webContentLink'] ?? null,
                'drive_file_id' => $uploadResult['id'] ?? null,
                'drive_link' => $uploadResult['webViewLink'] ?? $uploadResult['webContentLink'] ?? null,
                'photo_status' => 'tracer_pending', // Always reset to pending for re-approval
                'stored_filename' => $fullFileName,
                'storage_disk' => 'google_drive',
                'storage_path' => "{$customPath}/{$fullFileName}",
            ];

            // Reset approval data based on old status
            if (in_array($oldStatus, ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected'])) {
                // Reset tracer approval
                $updateData['tracer_approved_at'] = null;
                $updateData['tracer_user_id'] = null;
                $updateData['tracer_notes'] = null;
            }

            if (in_array($oldStatus, ['cgp_pending', 'cgp_approved', 'cgp_rejected'])) {
                // Reset CGP approval if photo was already in CGP stage
                $updateData['cgp_approved_at'] = null;
                $updateData['cgp_user_id'] = null;
                $updateData['cgp_notes'] = null;
                $updateData['cgp_rejected_at'] = null;
            }

            if ($oldStatus === 'tracer_rejected') {
                // Clear tracer rejection data
                $updateData['tracer_rejected_at'] = null;
                $updateData['tracer_notes'] = null;
            }

            // Update photo record
            $photoApproval->update($updateData);

            // Log replacement with audit trail
            Log::info('Photo replaced from CGP interface', [
                'photo_id' => $photoApproval->id,
                'old_status' => $oldStatus,
                'new_status' => 'tracer_pending',
                'replaced_by' => Auth::id(),
                'module' => $photoApproval->module_name,
            ]);

            DB::commit();

            // Build appropriate success message based on old status
            $message = 'Photo berhasil diganti. ';
            if (in_array($oldStatus, ['cgp_approved', 'cgp_pending'])) {
                $message .= 'Status direset ke TRACER_PENDING. Approval CGP telah dihapus, memerlukan re-approval dari Tracer dan CGP.';
            } elseif ($oldStatus === 'tracer_approved') {
                $message .= 'Status direset ke TRACER_PENDING. Approval Tracer telah dihapus, memerlukan re-approval.';
            } elseif ($oldStatus === 'cgp_rejected') {
                $message .= 'Status direset ke TRACER_PENDING. Rejection CGP telah dihapus, memerlukan review ulang dari Tracer.';
            } else {
                $message .= 'Status direset ke TRACER_PENDING untuk review ulang.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'old_status' => $oldStatus,
                'new_status' => 'tracer_pending'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Replace jalur photo error from CGP interface: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    private function determineLineStatus(int $total, int $approved, int $pending, int $rejected): string
    {
        if ($total === 0)
            return 'no_evidence';
        if ($rejected > 0)
            return 'rejected';
        if ($approved === $total)
            return 'approved';
        if ($pending > 0)
            return 'pending';
        return 'pending';
    }
}
