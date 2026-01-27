<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JalurCluster;
use App\Models\JalurJointData;
use App\Models\JalurLineNumber;
use App\Models\JalurLoweringData;
use App\Models\PhotoApproval;
use App\Services\PhotoApprovalService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            'pending_photos' => $clusters->sum(fn($c) => $c->approval_stats['pending_photos']),
            'approved_photos' => $clusters->sum(fn($c) => $c->approval_stats['approved_photos']),
            'rejected_photos' => $clusters->sum(fn($c) => $c->approval_stats['rejected_photos']),
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
            'stats' => $stats,
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
        $dateFrom = $request->input('date_from'); // date range filter
        $dateTo = $request->input('date_to'); // date range filter
        $perPage = $request->input('per_page', 20);

        $linesQuery = $cluster->lineNumbers()
            ->with(['loweringData.photoApprovals', 'cluster'])
            ->when($search, function ($q) use ($search) {
                $q->search($search);
            });

        // Get all lines first
        $allLines = $linesQuery->get();

        // Eager load joint data for this cluster
        // Joints will be shown as separate cards, not grouped under lines
        // Get ALL line numbers in cluster (not just filtered ones) for joint lookup
        $allLineNumbersInCluster = $cluster->lineNumbers()->pluck('line_number')->toArray();

        $jointsForCluster = JalurJointData::with(['photoApprovals.tracerUser', 'photoApprovals.cgpUser', 'fittingType'])
            ->where(function ($q) use ($allLineNumbersInCluster) {
                $q->whereIn('joint_line_from', $allLineNumbersInCluster)
                    ->orWhereIn('joint_line_to', $allLineNumbersInCluster)
                    ->orWhereIn('joint_line_optional', $allLineNumbersInCluster);
            })
            ->when($search, function ($q) use ($search) {
                // Allow search by joint number or joint code
                $q->where(function ($query) use ($search) {
                    $query->where('nomor_joint', 'like', "%{$search}%")
                        ->orWhere('joint_code', 'like', "%{$search}%");
                });
            })
            ->get();

        // Calculate stats for each line (lowering data only)
        $allLines = $allLines->map(function ($line) use ($dateFrom, $dateTo) {
            // Calculate approval statistics for lowering data only
            $stats = $this->calculateLoweringStats($line, $dateFrom, $dateTo);

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

            // Add all necessary fields using makeVisible to ensure they are serialized
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

        // Apply filters BEFORE sorting
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
            // Added photo-level stats
            'total_photos' => $sortedItems->sum(fn($i) => $i->approval_stats['total_photos']),
            'approved_photos' => $sortedItems->sum(fn($i) => $i->approval_stats['approved_photos']),
            'pending_photos' => $sortedItems->sum(fn($i) => $i->approval_stats['pending_photos']),
            'rejected_photos' => $sortedItems->sum(fn($i) => $i->approval_stats['rejected_photos']),

            // Breakdown stats
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

        // AJAX request - return JSON
        if ($request->ajax() || $request->input('ajax')) {
            return response()->json([
                'success' => true,
                'data' => $items,
                'stats' => $stats
            ]);
        }

        return view('approvals.tracer.jalur.lines', [
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

        // Calculate line summary (lowering only, joints are now separate)
        $lineStats = $this->calculateLoweringStats($line);

        return view('approvals.tracer.jalur.evidence', [
            'line' => $line,
            'workDates' => $workDates,
            'lineStats' => $lineStats,
            'filterDate' => $filterDate,
            'sortBy' => $sortBy,
        ]);
    }

    /**
     * LEVEL 3: Joint Evidence Review Page
     * Show evidence photos for a specific joint
     */
    public function jointEvidence(Request $request, int $jointId)
    {
        $joint = JalurJointData::with([
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

        // Calculate stats for joint photos
        $photos = $joint->photoApprovals;
        $jointStats = [
            'total' => $photos->count(),
            'approved' => $photos->where('photo_status', 'tracer_approved')->count(),
            'cgp_approved' => $photos->where('photo_status', 'cgp_approved')->count(),
            'pending' => $photos->whereIn('photo_status', ['tracer_pending', 'draft'])->count(),
            'rejected' => $photos->where('photo_status', 'tracer_rejected')->count(),
        ];

        $cluster = $lineFrom ? $lineFrom->cluster : ($lineTo ? $lineTo->cluster : null);

        return view('approvals.tracer.jalur.joint-evidence', [
            'joint' => $joint,
            'lineFrom' => $lineFrom,
            'lineTo' => $lineTo,
            'lineOptional' => $lineOptional,
            'cluster' => $cluster,
            'jointStats' => $jointStats,
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

        } catch (Exception $e) {
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

        } catch (Exception $e) {
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
                throw new Exception('Tidak ada foto yang perlu di-approve untuk tanggal ini');
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

        } catch (Exception $e) {
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

            $allPhotos = PhotoApproval::whereIn(
                'module_record_id',
                $line->loweringData->pluck('id')
            )
                ->where('module_name', 'jalur_lowering')
                ->whereIn('photo_status', ['tracer_pending', 'draft'])
                ->get();

            if ($allPhotos->isEmpty()) {
                throw new Exception('Tidak ada foto yang perlu di-approve untuk line ini');
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

        } catch (Exception $e) {
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
            Log::info('Photo replaced', [
                'photo_id' => $photoApproval->id,
                'old_status' => $oldStatus,
                'new_status' => 'tracer_pending',
                'replaced_by' => Auth::id(),
                'module' => $photoApproval->module_name,
            ]);

            DB::commit();

            Log::info('Jalur photo replaced successfully', [
                'photo_id' => $photoApproval->id,
                'replaced_by' => Auth::id(),
                'module' => $photoApproval->module_name,
                'old_status' => $oldStatus,
            ]);

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
            Log::error('Replace jalur photo error: ' . $e->getMessage());

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
        $totalJoints = 0;
        $linesWithPending = 0;
        $jointsWithPending = 0;
        $totalPhotos = 0;
        $approvedPhotos = 0;
        $pendingPhotos = 0;
        $rejectedPhotos = 0;
        $countedJoints = []; // Track joints already counted to avoid duplicates

        foreach ($cluster->lineNumbers as $line) {
            $totalLines++;

            // Count lowering photos
            foreach ($line->loweringData as $lowering) {
                foreach ($lowering->photoApprovals as $photo) {
                    $totalPhotos++;

                    if (in_array($photo->photo_status, ['tracer_approved', 'cgp_approved', 'cgp_pending'])) {
                        $approvedPhotos++;
                    } elseif (in_array($photo->photo_status, ['tracer_pending', 'draft'])) {
                        $pendingPhotos++;
                    } elseif (in_array($photo->photo_status, ['tracer_rejected', 'cgp_rejected'])) {
                        $rejectedPhotos++;
                    }
                }
            }

            // Count joint photos (avoid duplicates)
            foreach ($line->jointData as $joint) {
                // Skip if this joint was already counted
                if (in_array($joint->id, $countedJoints)) {
                    continue;
                }

                $countedJoints[] = $joint->id;
                $totalJoints++;
                $jointHasPending = false;

                foreach ($joint->photoApprovals as $photo) {
                    $totalPhotos++;

                    if (in_array($photo->photo_status, ['tracer_approved', 'cgp_approved', 'cgp_pending'])) {
                        $approvedPhotos++;
                    } elseif (in_array($photo->photo_status, ['tracer_pending', 'draft'])) {
                        $pendingPhotos++;
                        $jointHasPending = true;
                    } elseif (in_array($photo->photo_status, ['tracer_rejected', 'cgp_rejected'])) {
                        $rejectedPhotos++;
                    }
                }

                if ($jointHasPending) {
                    $jointsWithPending++;
                }
            }

            // Count lines with pending photos
            $lineStats = $this->getLineApprovalStats($line);
            if ($lineStats['pending_photos'] > 0) {
                $linesWithPending++;
            }
        }

        $totalItems = $totalLines + $totalJoints;
        $itemsWithPending = $linesWithPending + $jointsWithPending;

        return [
            'total_lines' => $totalLines,
            'total_joints' => $totalJoints,
            'total_items' => $totalItems,
            'lines_with_pending' => $linesWithPending,
            'joints_with_pending' => $jointsWithPending,
            'items_with_pending' => $itemsWithPending,
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
    private function getLineApprovalStats(JalurLineNumber $line, $dateFrom = null, $dateTo = null): array
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

            // Store the work date - use tanggal_jalur (field date)
            if ($lowering->tanggal_jalur) {
                $workDates[] = $lowering->tanggal_jalur;

                // Store date with penggelaran detail - only if date exists
                $workDatesDetail[] = [
                    'date' => $lowering->tanggal_jalur->format('Y-m-d'),
                    'penggelaran' => $lowering->penggelaran ?? 0,
                    'bongkaran' => $lowering->bongkaran ?? 0,
                    'photos_count' => $lowering->photoApprovals->count(),
                ];
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
            $hasPhotos = false;
            $dateAllApproved = true;

            foreach ($joint->photoApprovals as $photo) {
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
                $jointDatesWithPhotos++;
                if ($dateAllApproved && $joint->photoApprovals->count() > 0) {
                    $jointDatesFullyApproved++;
                }
            }

            // Store the joint date
            if ($joint->tanggal_joint) {
                $jointDates[] = $joint->tanggal_joint;

                $jointDatesDetail[] = [
                    'date' => $joint->tanggal_joint->format('Y-m-d'),
                    'fitting_type' => $joint->fittingType?->nama_fitting ?? '-',
                    'photos_count' => $joint->photoApprovals->count(),
                ];
            }
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
            'work_dates' => $workDates,
            'work_dates_detail' => $workDatesDetail,
            'joint_dates_count' => $jointDatesCount,
            'joint_dates_with_photos' => $jointDatesWithPhotos,
            'joint_dates_fully_approved' => $jointDatesFullyApproved,
            'joint_dates' => $jointDates,
            'joint_dates_detail' => $jointDatesDetail,
            'percentage' => round($percentage, 2),
            'status' => $this->determineLineStatus($totalPhotos, $approvedPhotos, $pendingPhotos, $rejectedPhotos),
        ];
    }

    /**
     * Determine cluster status based on photos
     */
    private function determineClusterStatus(int $total, int $approved, int $pending, int $rejected): string
    {
        if ($total === 0)
            return 'no_evidence';
        if ($rejected > 0)
            return 'rejected';
        if ($approved === $total)
            return 'approved';
        if ($pending > 0)
            return 'pending';
        return 'unknown';
    }

    /**
     * Calculate approval statistics for lowering data only (for separate line cards)
     */
    private function calculateLoweringStats(JalurLineNumber $line, $dateFrom = null, $dateTo = null): array
    {
        $totalPhotos = 0;
        $approvedPhotos = 0;
        $tracerApprovedPhotos = 0;
        $cgpApprovedPhotos = 0;
        $pendingPhotos = 0;
        $rejectedPhotos = 0;
        $rejections = [];

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
            foreach ($lowering->photoApprovals as $photo) {
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
                } elseif ($photo->photo_status === 'tracer_rejected') {
                    $rejectedPhotos++;
                    // Add rejection detail
                    $rejections[] = [
                        'field_name' => $photo->photo_field_name,
                        'label' => ucwords(str_replace('_', ' ', $photo->photo_field_name)),
                        'user_name' => $photo->tracerUser->name ?? 'Unknown',
                        'rejected_at' => $photo->tracer_rejected_at ? $photo->tracer_rejected_at->format('d/m/Y H:i') : '-',
                        'notes' => $photo->tracer_notes ?? '-',
                        'rejected_by' => 'tracer',
                    ];
                } elseif ($photo->photo_status === 'cgp_rejected') {
                    $rejectedPhotos++;
                    // Add CGP rejection detail
                    $rejections[] = [
                        'field_name' => $photo->photo_field_name,
                        'label' => ucwords(str_replace('_', ' ', $photo->photo_field_name)),
                        'user_name' => $photo->cgpUser->name ?? 'Unknown',
                        'rejected_at' => $photo->cgp_rejected_at ? $photo->cgp_rejected_at->format('d/m/Y H:i') : '-',
                        'notes' => $photo->cgp_notes ?? '-',
                        'rejected_by' => 'cgp',
                    ];
                }
            }
        }

        $percentage = $totalPhotos > 0 ? ($approvedPhotos / $totalPhotos) * 100 : 0;
        $status = $this->determineLineStatus($totalPhotos, $approvedPhotos, $pendingPhotos, $rejectedPhotos);

        // Prepare work dates detail
        $workDatesCount = $loweringData->count();
        $workDatesDetail = [];
        foreach ($loweringData as $lowering) {
            if ($lowering->tanggal_jalur) {
                $workDatesDetail[] = [
                    'date' => $lowering->tanggal_jalur->format('Y-m-d'),
                    'penggelaran' => $lowering->penggelaran ?? 0,
                    'bongkaran' => $lowering->bongkaran ?? 0,
                    'photos_count' => $lowering->photoApprovals->count(),
                ];
            }
        }

        // Determine status label based on HIGHEST status reached
        // Priority: CGP level > Tracer level
        $hasCgpRejection = collect($rejections)->where('rejected_by', 'cgp')->count() > 0;
        $hasCgpApproval = $cgpApprovedPhotos > 0;

        if ($hasCgpRejection) {
            // If any photo rejected by CGP, show CGP rejection
            $statusLabel = 'Rejected by CGP';
        } elseif ($hasCgpApproval && $status === 'approved') {
            // If all approved and has CGP approval, show CGP approval
            $statusLabel = 'Approved by CGP';
        } elseif ($hasCgpApproval && $cgpApprovedPhotos === $totalPhotos) {
            // If all photos are CGP approved
            $statusLabel = 'Approved by CGP';
        } elseif ($hasCgpApproval) {
            // Has some CGP approval but not all
            $statusLabel = $status === 'rejected' ? 'Rejected by Tracer' : 'Partial';
        } else {
            // No CGP involvement yet, use Tracer status
            $statusLabel = match ($status) {
                'pending' => 'Pending Tracer Review',
                'approved' => 'Approved by Tracer',
                'rejected' => 'Rejected by Tracer',
                'no_evidence' => 'No Evidence',
                default => 'Partial'
            };
        }

        return [
            'total_photos' => $totalPhotos,
            'approved_photos' => $approvedPhotos,
            'tracer_approved_photos' => $tracerApprovedPhotos,
            'cgp_approved_photos' => $cgpApprovedPhotos,
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
     * Calculate approval statistics for joint data (for separate joint cards)
     */
    private function calculateJointStats(JalurJointData $joint, $dateFrom = null, $dateTo = null): array
    {
        $totalPhotos = 0;
        $approvedPhotos = 0;
        $tracerApprovedPhotos = 0;
        $cgpApprovedPhotos = 0;
        $pendingPhotos = 0;
        $rejectedPhotos = 0;
        $rejections = [];

        // Check if joint date is within filter range
        if ($dateFrom || $dateTo) {
            if (!$joint->tanggal_joint) {
                // If no date and filter is active, skip this joint
                return [
                    'total_photos' => 0,
                    'approved_photos' => 0,
                    'tracer_approved_photos' => 0,
                    'cgp_approved_photos' => 0,
                    'pending_photos' => 0,
                    'rejected_photos' => 0,
                    'percentage' => 0,
                    'status' => 'no_evidence',
                ];
            }

            $dateStr = $joint->tanggal_joint->format('Y-m-d');
            if (($dateFrom && $dateStr < $dateFrom) || ($dateTo && $dateStr > $dateTo)) {
                // Joint is outside date range
                return [
                    'total_photos' => 0,
                    'approved_photos' => 0,
                    'tracer_approved_photos' => 0,
                    'cgp_approved_photos' => 0,
                    'pending_photos' => 0,
                    'rejected_photos' => 0,
                    'percentage' => 0,
                    'status' => 'no_evidence',
                ];
            }
        }

        foreach ($joint->photoApprovals as $photo) {
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
            } elseif ($photo->photo_status === 'tracer_rejected') {
                $rejectedPhotos++;
                // Add rejection detail
                $rejections[] = [
                    'field_name' => $photo->photo_field_name,
                    'label' => ucwords(str_replace('_', ' ', $photo->photo_field_name)),
                    'user_name' => $photo->tracerUser->name ?? 'Unknown',
                    'rejected_at' => $photo->tracer_rejected_at ? $photo->tracer_rejected_at->format('d/m/Y H:i') : '-',
                    'notes' => $photo->tracer_notes ?? '-',
                    'rejected_by' => 'tracer',
                ];
            } elseif ($photo->photo_status === 'cgp_rejected') {
                $rejectedPhotos++;
                // Add CGP rejection detail
                $rejections[] = [
                    'field_name' => $photo->photo_field_name,
                    'label' => ucwords(str_replace('_', ' ', $photo->photo_field_name)),
                    'user_name' => $photo->cgpUser->name ?? 'Unknown',
                    'rejected_at' => $photo->cgp_rejected_at ? $photo->cgp_rejected_at->format('d/m/Y H:i') : '-',
                    'notes' => $photo->cgp_notes ?? '-',
                    'rejected_by' => 'cgp',
                ];
            }
        }

        $percentage = $totalPhotos > 0 ? ($approvedPhotos / $totalPhotos) * 100 : 0;
        $status = $this->determineLineStatus($totalPhotos, $approvedPhotos, $pendingPhotos, $rejectedPhotos);

        // Calculate work dates detail
        $workDates = [];
        if ($joint->tanggal_joint) {
            $dateStr = $joint->tanggal_joint->format('Y-m-d');
            $photoCount = $joint->photoApprovals->count();

            if ($photoCount > 0) {
                $workDates[] = [
                    'date' => $dateStr,
                    'photo_count' => $photoCount
                ];
            }
        }

        // Determine status label based on HIGHEST status reached
        // Priority: CGP level > Tracer level
        $hasCgpRejection = collect($rejections)->where('rejected_by', 'cgp')->count() > 0;
        $hasCgpApproval = $cgpApprovedPhotos > 0;

        if ($hasCgpRejection) {
            // If any photo rejected by CGP, show CGP rejection
            $statusLabel = 'Rejected by CGP';
        } elseif ($hasCgpApproval && $status === 'approved') {
            // If all approved and has CGP approval, show CGP approval
            $statusLabel = 'Approved by CGP';
        } elseif ($hasCgpApproval && $cgpApprovedPhotos === $totalPhotos) {
            // If all photos are CGP approved
            $statusLabel = 'Approved by CGP';
        } elseif ($hasCgpApproval) {
            // Has some CGP approval but not all
            $statusLabel = $status === 'rejected' ? 'Rejected by Tracer' : 'Partial';
        } else {
            // No CGP involvement yet, use Tracer status
            $statusLabel = match ($status) {
                'pending' => 'Pending Tracer Review',
                'approved' => 'Approved by Tracer',
                'rejected' => 'Rejected by Tracer',
                'no_evidence' => 'No Evidence',
                default => 'Partial'
            };
        }

        return [
            'total_photos' => $totalPhotos,
            'approved_photos' => $approvedPhotos,
            'tracer_approved_photos' => $tracerApprovedPhotos,
            'cgpApprovedPhotos' => $cgpApprovedPhotos,
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
     * Determine line status based on photos
     */
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
        return 'partial';
    }
}
