<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Models\{CalonPelanggan, PhotoApproval, SkData, SrData, GasInData, JalurLoweringData, JalurJointData};
use App\Services\{PhotoApprovalService, NotificationService, FolderOrganizationService};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{DB, Log, Auth};
use Exception;

class CgpApprovalController extends Controller implements HasMiddleware
{
    public function __construct(
        private PhotoApprovalService $photoApprovalService,
        private NotificationService $notificationService,
        private FolderOrganizationService $folderOrganizationService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            // Role jalur can ONLY access jalur-photos and approve jalur photos
            // All other methods require cgp or super_admin
            new Middleware('role:cgp,super_admin', except: ['jalurPhotos', 'approvePhoto']),
            // jalurPhotos and approvePhoto can be accessed by cgp, super_admin, OR jalur
            new Middleware('role:cgp,super_admin,jalur', only: ['jalurPhotos', 'approvePhoto']),
        ];
    }

    /**
     * Dashboard CGP - Overview semua pending CGP approvals
     */
    public function index()
    {
        try {
            $stats = $this->getCgpStats();
            $recentActivities = $this->getRecentActivities();

            return view('approvals.cgp.index', compact('stats', 'recentActivities'));

        } catch (Exception $e) {
            Log::error('CGP dashboard error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat dashboard');
        }
    }

    /**
     * List pelanggan yang perlu review CGP (sudah di-approve tracer)
     */
    public function customers(Request $request)
    {
        try {
            // CGP hanya review yang sudah di-approve tracer
            $query = CalonPelanggan::with(['skData', 'srData', 'gasInData']);

            // Filter berdasarkan status
            $status = $request->get('status');

            if ($status === 'sk_ready') {
                // SK photos that need CGP attention (pending, in-progress, or waiting tracer)
                $query->whereHas('photoApprovals', function ($q) {
                    $q->where('module_name', 'sk')
                        ->whereIn('photo_status', ['cgp_pending', 'cgp_approved', 'cgp_rejected', 'tracer_pending', 'tracer_approved']);
                });
            } elseif ($status === 'sr_ready') {
                // SR photos that need CGP attention (pending, in-progress, or waiting tracer)
                $query->whereHas('photoApprovals', function ($q) {
                    $q->where('module_name', 'sr')
                        ->whereIn('photo_status', ['cgp_pending', 'cgp_approved', 'cgp_rejected', 'tracer_pending', 'tracer_approved']);
                });
            } elseif ($status === 'gas_in_ready') {
                // Gas In photos that need CGP attention (pending, in-progress, or waiting tracer)
                $query->whereHas('photoApprovals', function ($q) {
                    $q->where('module_name', 'gas_in')
                        ->whereIn('photo_status', ['cgp_pending', 'cgp_approved', 'cgp_rejected', 'tracer_pending', 'tracer_approved']);
                });
            } else {
                // Default: show all customers with photos in CGP workflow
                $query->whereHas('photoApprovals', function ($q) {
                    $q->whereIn('photo_status', ['cgp_pending', 'cgp_approved', 'cgp_rejected', 'tracer_approved']);
                });
            }

            // Search - apply search filter
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('reff_id_pelanggan', 'like', "%{$search}%")
                        ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                        ->orWhere('alamat', 'like', "%{$search}%");
                });
            }

            // Get all customers (without pagination first for custom sorting)
            $allCustomers = $query->get();

            // Add CGP status and relevant dates for each customer
            $allCustomers->transform(function ($customer) {
                $customer->cgp_status = $this->getCgpStatus($customer);

                // Get module-specific dates for sorting
                $customer->tanggal_instalasi = $customer->skData?->tanggal_instalasi;
                $customer->tanggal_pemasangan = $customer->srData?->tanggal_pemasangan;
                $customer->tanggal_gas_in = $customer->gasInData?->tanggal_gas_in;

                // Debug: Add priority score to customer object for visibility
                $customer->priority_score = $this->getPriorityScore($customer->cgp_status);

                return $customer;
            });

            // Sort by priority first, then by module-specific date (chain sorting)
            $sorted = $allCustomers->sort(function ($a, $b) {
                // First priority: compare priority scores (lower score = higher priority)
                $priorityA = $this->getPriorityScore($a->cgp_status);
                $priorityB = $this->getPriorityScore($b->cgp_status);

                if ($priorityA !== $priorityB) {
                    return $priorityA <=> $priorityB; // ASC: lower score first
                }

                // Same priority: compare by module-specific date (older first - FIFO)
                // Priority 1 (SK Ready) -> use tanggal_instalasi
                // Priority 2 (SR Ready) -> use tanggal_pemasangan
                // Priority 3 (Gas In Ready) -> use tanggal_gas_in
                // Others -> use fallback date

                $dateA = null;
                $dateB = null;

                if ($priorityA === 1) { // SK Ready
                    $dateA = $a->tanggal_instalasi;
                    $dateB = $b->tanggal_instalasi;
                } elseif ($priorityA === 2) { // SR Ready
                    $dateA = $a->tanggal_pemasangan;
                    $dateB = $b->tanggal_pemasangan;
                } elseif ($priorityA === 3) { // Gas In Ready
                    $dateA = $a->tanggal_gas_in;
                    $dateB = $b->tanggal_gas_in;
                }

                // Convert to string for comparison, use fallback if null
                $timeA = $dateA ? $dateA->format('Y-m-d H:i:s') : '9999-12-31 23:59:59';
                $timeB = $dateB ? $dateB->format('Y-m-d H:i:s') : '9999-12-31 23:59:59';

                return $timeA <=> $timeB; // ASC: older time first (FIFO - First In, First Out)
            })->values();

            // Manual pagination
            $page = $request->get('page', 1);
            $perPage = 15;
            $offset = ($page - 1) * $perPage;
            $total = $sorted->count();
            $items = $sorted->slice($offset, $perPage)->values();

            // Create paginator instance
            $customers = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            if ($request->ajax() || $request->get('ajax')) {
                // Transform items to include cgp_status
                $items = $customers->getCollection()->map(function ($customer) {
                    return [
                        'reff_id_pelanggan' => $customer->reff_id_pelanggan,
                        'nama_pelanggan' => $customer->nama_pelanggan,
                        'alamat' => $customer->alamat,
                        'cgp_status' => $customer->cgp_status ?? $this->getCgpStatus($customer),
                    ];
                });

                return response()->json([
                    'success' => true,
                    'data' => [
                        'data' => $items,
                        'current_page' => $customers->currentPage(),
                        'last_page' => $customers->lastPage(),
                        'per_page' => $customers->perPage(),
                        'total' => $customers->total(),
                        'from' => $customers->firstItem(),
                        'to' => $customers->lastItem(),
                    ],
                ]);
            }

            // Get Dashboard Stats
            $stats = $this->photoApprovalService->getDashboardStats('cgp');

            return view('approvals.cgp.customers', compact('customers', 'stats'));

        } catch (Exception $e) {
            Log::error('CGP customers error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'status_filter' => $request->get('status'),
                'search_filter' => $request->get('search')
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Terjadi kesalahan saat memuat data: ' . $e->getMessage());
        }
    }

    /**
     * Detail photos per reff_id untuk CGP review
     */
    public function customerPhotos(string $reffId)
    {
        try {
            $customer = CalonPelanggan::with(['skData', 'srData', 'gasInData'])
                ->where('reff_id_pelanggan', $reffId)
                ->firstOrFail();

            // Get photos yang sudah di-approve tracer
            $photos = $this->getPhotosForCgp($customer);
            $cgpStatus = $this->getCgpStatus($customer);

            // Debug logging untuk troubleshooting
            Log::info('CGP Customer Photos Debug', [
                'reff_id' => $reffId,
                'cgp_status' => $cgpStatus,
                'photo_counts' => [
                    'sk' => count($photos['sk'] ?? []),
                    'sr' => count($photos['sr'] ?? []),
                    'gas_in' => count($photos['gas_in'] ?? []),
                ],
                'all_photo_approvals' => PhotoApproval::where('reff_id_pelanggan', $reffId)
                    ->select('id', 'module_name', 'photo_field_name', 'photo_status', 'tracer_approved_at', 'cgp_approved_at', 'cgp_rejected_at')
                    ->get()
                    ->toArray()
            ]);

            // Get completion status for each module
            $completionStatus = [];

            if ($customer->skData) {
                $completionStatus['sk'] = [
                    'completion_summary' => $customer->skData->getCompletionSummary(),
                    'slot_status' => $customer->skData->getSlotCompletionStatus(),
                    'missing_required' => $customer->skData->getMissingRequiredSlots(),
                ];
            }

            if ($customer->srData) {
                $completionStatus['sr'] = [
                    'completion_summary' => $customer->srData->getCompletionSummary(),
                    'slot_status' => $customer->srData->getSlotCompletionStatus(),
                    'missing_required' => $customer->srData->getMissingRequiredSlots(),
                ];
            }

            if ($customer->gasInData) {
                $completionStatus['gas_in'] = [
                    'completion_summary' => $customer->gasInData->getCompletionSummary(),
                    'slot_status' => $customer->gasInData->getSlotCompletionStatus(),
                    'missing_required' => $customer->gasInData->getMissingRequiredSlots(),
                ];
            }

            return view('approvals.cgp.photos', compact('customer', 'photos', 'cgpStatus', 'completionStatus'));

        } catch (Exception $e) {
            Log::error('Customer photos error: ' . $e->getMessage());
            return back()->with('error', 'Data tidak ditemukan');
        }
    }

    /**
     * Revert CGP approval - undo approval and revert folder organization
     */
    public function revertApproval(Request $request)
    {
        $request->validate([
            'photo_id' => 'required|integer',
            'reason' => 'required|string|min:10|max:1000'
        ]);

        try {
            DB::beginTransaction();

            // Get photo first
            $photo = PhotoApproval::findOrFail($request->get('photo_id'));

            // Revert approval status
            $result = $this->photoApprovalService->revertCgpApproval(
                $request->get('photo_id'),
                Auth::id(),
                $request->get('reason')
            );

            if (!$result['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }

            // Revert folder organization if photo was organized
            if ($photo->organized_at) {
                try {
                    if (in_array($photo->module_name, ['jalur_lowering', 'jalur_joint'])) {
                        // Revert jalur photo organization
                        $revertResult = $this->folderOrganizationService->revertJalurPhotoOrganization($photo->fresh());
                    } else {
                        // Revert SK/SR/Gas In photo organization
                        $revertResult = $this->folderOrganizationService->revertPhotoOrganization($photo->fresh());
                    }

                    if ($revertResult['success']) {
                        Log::info('Photo organization reverted after CGP approval revert', [
                            'photo_id' => $photo->id,
                            'module' => $photo->module_name,
                            'revert_result' => $revertResult
                        ]);
                    }
                } catch (Exception $e) {
                    Log::warning('Failed to revert photo organization, but approval was reverted', [
                        'photo_id' => $photo->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('CGP revert approval error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve/Reject individual photo by CGP
     */
    public function approvePhoto(Request $request)
    {
        $request->validate([
            'photo_id' => 'required|integer',
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $photoApproval = PhotoApproval::findOrFail($request->get('photo_id'));
            $action = $request->get('action');
            $notes = $request->get('notes');

            // Add detailed logging for debugging
            Log::info('CGP Approval Attempt', [
                'photo_id' => $photoApproval->id,
                'reff_id_pelanggan' => $photoApproval->reff_id_pelanggan,
                'module_name' => $photoApproval->module_name,
                'photo_field_name' => $photoApproval->photo_field_name,
                'current_status' => $photoApproval->photo_status,
                'tracer_approved_at' => $photoApproval->tracer_approved_at,
                'cgp_approved_at' => $photoApproval->cgp_approved_at,
                'action' => $action,
                'user_id' => Auth::id()
            ]);

            // Check if photo is ready for CGP (status tracer_approved or cgp_pending)
            if (!in_array($photoApproval->photo_status, ['tracer_approved', 'cgp_pending'])) {
                Log::warning('CGP Approval Failed - Wrong Status', [
                    'photo_id' => $photoApproval->id,
                    'expected_status' => 'tracer_approved or cgp_pending',
                    'actual_status' => $photoApproval->photo_status,
                    'tracer_approved_at' => $photoApproval->tracer_approved_at
                ]);
                throw new Exception("Photo belum ready untuk CGP approval. Current status: {$photoApproval->photo_status}");
            }

            if ($action === 'approve') {
                $result = $this->photoApprovalService->approveByCgp(
                    $photoApproval->id,
                    Auth::id(),
                    $notes
                );

                // Organize photo into dedicated folder after individual approval
                if (!in_array($photoApproval->module_name, ['jalur_lowering', 'jalur_joint'])) {
                    // SK, SR, Gas In organization
                    try {
                        $organizationResult = $this->folderOrganizationService->organizePhotosAfterCgpApproval(
                            $photoApproval->reff_id_pelanggan,
                            $photoApproval->module_name
                        );
                        Log::info('Individual photo organization completed', $organizationResult);
                    } catch (Exception $e) {
                        Log::warning('Individual photo organization failed but approval succeeded', [
                            'photo_id' => $photoApproval->id,
                            'reff_id' => $photoApproval->reff_id_pelanggan,
                            'module' => $photoApproval->module_name,
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    // Jalur organization - organize by line/date
                    try {
                        if ($photoApproval->module_name === 'jalur_lowering') {
                            $moduleData = \App\Models\JalurLoweringData::with('lineNumber')->find($photoApproval->module_record_id);
                            if ($moduleData) {
                                $organizationResult = $this->folderOrganizationService->organizeJalurPhotosAfterCgpApproval(
                                    $moduleData->line_number_id,
                                    $moduleData->tanggal_jalur->format('Y-m-d'),
                                    'jalur_lowering'
                                );
                                Log::info('Jalur lowering photo organization completed', $organizationResult);
                            }
                        } else {
                            $moduleData = \App\Models\JalurJointData::with('lineNumber')->find($photoApproval->module_record_id);
                            if ($moduleData) {
                                $organizationResult = $this->folderOrganizationService->organizeJalurPhotosAfterCgpApproval(
                                    $moduleData->lineNumber->id,
                                    $moduleData->tanggal_joint->format('Y-m-d'),
                                    'jalur_joint'
                                );
                                Log::info('Jalur joint photo organization completed', $organizationResult);
                            }
                        }
                    } catch (Exception $e) {
                        Log::warning('Jalur photo organization failed but approval succeeded', [
                            'photo_id' => $photoApproval->id,
                            'module' => $photoApproval->module_name,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            } else {
                $result = $this->photoApprovalService->rejectByCgp(
                    $photoApproval->id,
                    Auth::id(),
                    $notes ?? 'Rejected by CGP'
                );

                // Send Telegram notification for rejection
                $this->sendRejectionNotification($photoApproval, $notes);
            }

            // Update module status if needed
            $this->updateModuleStatus($photoApproval);

            DB::commit();

            $message = $action === 'approve' ? 'Photo berhasil di-approve' : 'Photo berhasil di-reject';

            // Check if this is jalur photos and redirect appropriately
            if (in_array($photoApproval->module_name, ['jalur_lowering', 'jalur_joint'])) {
                return back()->with('success', $message);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $result
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('CGP approve photo error: ' . $e->getMessage());

            // Check if we're dealing with jalur photos and handle error appropriately
            if (isset($photoApproval) && in_array($photoApproval->module_name, ['jalur_lowering', 'jalur_joint'])) {
                return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Batch approve untuk module by CGP
     */
    public function approveModule(Request $request)
    {
        $request->validate([
            'reff_id' => 'required|string',
            'module' => 'required|in:sk,sr,gas_in',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $reffId = $request->get('reff_id');
            $module = $request->get('module');
            $notes = $request->get('notes');

            $moduleData = $this->getModuleData($reffId, $module);
            if (!$moduleData) {
                throw new Exception('Data module tidak ditemukan');
            }

            // No longer check tracer_approved_at at module level
            // CGP can approve individual photos that are ready (tracer_approved or cgp_pending)
            // even if not all required photos have been uploaded/approved yet

            $result = $this->photoApprovalService->approveModuleByCgp(
                $moduleData,
                $module,
                Auth::id(),
                $notes
            );

            DB::commit();

            // Organize photos into dedicated folders after successful approval
            try {
                $organizationResult = $this->folderOrganizationService->organizePhotosAfterCgpApproval($reffId, $module);
                Log::info('Photo organization completed', $organizationResult);
            } catch (Exception $e) {
                Log::warning('Photo organization failed but approval succeeded', [
                    'reff_id' => $reffId,
                    'module' => $module,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Module {$module} berhasil di-approve oleh CGP",
                'data' => $result
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('CGP approve module error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API endpoint to check slot completion for a module
     */
    public function checkSlotCompletion(Request $request)
    {
        $request->validate([
            'reff_id' => 'required|string',
            'module' => 'required|in:sk,sr,gas_in'
        ]);

        try {
            $reffId = $request->get('reff_id');
            $module = $request->get('module');

            $moduleData = $this->getModuleData($reffId, $module);
            if (!$moduleData) {
                return response()->json([
                    'success' => false,
                    'message' => "Module {$module} not found for {$reffId}"
                ], 404);
            }

            $completionSummary = $moduleData->getCompletionSummary();
            $slotStatus = $moduleData->getSlotCompletionStatus();
            $missingRequired = $moduleData->getMissingRequiredSlots();

            return response()->json([
                'success' => true,
                'reff_id' => $reffId,
                'module' => $module,
                'completion_summary' => $completionSummary,
                'slot_status' => $slotStatus,
                'missing_required' => $missingRequired,
                'is_ready_for_cgp' => empty($missingRequired), // Only approve if all required slots are present
            ]);

        } catch (Exception $e) {
            Log::error('Slot completion check failed', [
                'reff_id' => $request->get('reff_id'),
                'module' => $request->get('module'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check slot completion: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== PRIVATE METHODS ====================

    /**
     * Get priority score for sorting customers
     * Lower score = higher priority (appears at top)
     */
    private function getPriorityScore(array $cgpStatus): int
    {
        // Priority 1: SK Ready (highest priority - needs immediate action)
        if ($cgpStatus['sk_ready'] ?? false) {
            return 1;
        }

        // Priority 2: SR Ready (ready after SK complete)
        if ($cgpStatus['sr_ready'] ?? false) {
            return 2;
        }

        // Priority 3: Gas In Ready (ready after SR complete)
        if ($cgpStatus['gas_in_ready'] ?? false) {
            return 3;
        }

        // Priority 4: In Progress (partially reviewed, needs follow-up)
        if (
            ($cgpStatus['sk_in_progress'] ?? false) ||
            ($cgpStatus['sr_in_progress'] ?? false) ||
            ($cgpStatus['gas_in_in_progress'] ?? false)
        ) {
            return 4;
        }

        // Priority 5: Completed or Waiting Tracer (lowest priority)
        return 5;
    }

    private function getCgpStats(): array
    {
        return [
            'total_pending' => PhotoApproval::where('photo_status', 'cgp_pending')->count(),
            'sk_ready' => PhotoApproval::where('module_name', 'sk')->where('photo_status', 'cgp_pending')->count(),
            'sr_ready' => PhotoApproval::where('module_name', 'sr')->where('photo_status', 'cgp_pending')->count(),
            'gas_in_ready' => PhotoApproval::where('module_name', 'gas_in')->where('photo_status', 'cgp_pending')->count(),
            'today_approved' => PhotoApproval::whereDate('cgp_approved_at', today())->count(),
        ];
    }

    private function getRecentActivities()
    {
        return PhotoApproval::with(['pelanggan'])
            ->whereNotNull('cgp_approved_at')
            ->orderBy('cgp_approved_at', 'desc')
            ->limit(10)
            ->get();
    }

    private function getCgpStatus($customer): array
    {
        // Helper function to get latest photos per field_name for a module
        $getLatestPhotos = function ($module) use ($customer) {
            return PhotoApproval::where('reff_id_pelanggan', $customer->reff_id_pelanggan)
                ->where('module_name', $module)
                ->with('cgpUser') // Load CGP user for rejection info
                ->get()
                ->groupBy('photo_field_name')
                ->map(function ($photos) {
                    // Get the latest photo (highest id) for each field
                    return $photos->sortByDesc('id')->first();
                });
        };

        // Get latest photos for each module
        $skLatestPhotos = $getLatestPhotos('sk');
        $srLatestPhotos = $getLatestPhotos('sr');
        $gasInLatestPhotos = $getLatestPhotos('gas_in');

        // Count cgp_approved photos (only from latest records)
        $skCgpApprovedCount = $skLatestPhotos->where('photo_status', 'cgp_approved')->count();
        $srCgpApprovedCount = $srLatestPhotos->where('photo_status', 'cgp_approved')->count();
        $gasInCgpApprovedCount = $gasInLatestPhotos->where('photo_status', 'cgp_approved')->count();

        // Check for photos ready for CGP review - only from latest records
        // Include both 'tracer_approved' and 'cgp_pending' as ready for CGP review
        $skHasReadyPhotos = $skLatestPhotos->whereIn('photo_status', ['tracer_approved', 'cgp_pending'])->isNotEmpty();
        $srHasReadyPhotos = $srLatestPhotos->whereIn('photo_status', ['tracer_approved', 'cgp_pending'])->isNotEmpty();
        $gasInHasReadyPhotos = $gasInLatestPhotos->whereIn('photo_status', ['tracer_approved', 'cgp_pending'])->isNotEmpty();

        // Check for photos that have been reviewed by CGP (approved or rejected) - only from latest records
        $skHasReviewedPhotos = $skLatestPhotos->whereIn('photo_status', ['cgp_approved', 'cgp_rejected'])->isNotEmpty();
        $srHasReviewedPhotos = $srLatestPhotos->whereIn('photo_status', ['cgp_approved', 'cgp_rejected'])->isNotEmpty();
        $gasInHasReviewedPhotos = $gasInLatestPhotos->whereIn('photo_status', ['cgp_approved', 'cgp_rejected'])->isNotEmpty();

        // Check for photos waiting for tracer approval - only from latest records
        $skWaitingTracer = $skLatestPhotos->whereIn('photo_status', ['tracer_pending', 'tracer_rejected'])->isNotEmpty();
        $srWaitingTracer = $srLatestPhotos->whereIn('photo_status', ['tracer_pending', 'tracer_rejected'])->isNotEmpty();
        $gasInWaitingTracer = $gasInLatestPhotos->whereIn('photo_status', ['tracer_pending', 'tracer_rejected'])->isNotEmpty();

        $skCompleted = $skCgpApprovedCount >= 5; // SK requires 5 photos
        $srCompleted = $srCgpApprovedCount >= 6; // SR requires 6 photos
        $gasInCompleted = $gasInCgpApprovedCount >= 4; // Gas In requires 4 photos

        // READY: Has photos ready to approve/reject AND not completed
        $skReady = $skHasReadyPhotos && !$skCompleted;
        $srReady = $srHasReadyPhotos && !$srCompleted;
        $gasInReady = $gasInHasReadyPhotos && !$gasInCompleted;

        // IN PROGRESS: Has reviewed photos OR waiting for tracer, but NO ready photos AND not completed
        $skInProgress = ($skHasReviewedPhotos || $skWaitingTracer) && !$skHasReadyPhotos && !$skCompleted;
        $srInProgress = ($srHasReviewedPhotos || $srWaitingTracer) && !$srHasReadyPhotos && !$srCompleted;
        $gasInInProgress = ($gasInHasReviewedPhotos || $gasInWaitingTracer) && !$gasInHasReadyPhotos && !$gasInCompleted;

        // Get rejection info for each module
        $skRejections = $this->getRejectionInfo($skLatestPhotos);
        $srRejections = $this->getRejectionInfo($srLatestPhotos);
        $gasInRejections = $this->getRejectionInfo($gasInLatestPhotos);

        return [
            // Ready statuses (has photos ready to approve/reject)
            'sk_ready' => $skReady,
            'sr_ready' => $srReady,
            'gas_in_ready' => $gasInReady,

            // In progress statuses (has reviewed/waiting photos, but no ready photos)
            'sk_in_progress' => $skInProgress,
            'sr_in_progress' => $srInProgress,
            'gas_in_in_progress' => $gasInInProgress,

            // Waiting tracer statuses (has tracer_pending/rejected)
            'sk_waiting_tracer' => $skWaitingTracer,
            'sr_waiting_tracer' => $srWaitingTracer,
            'gas_in_waiting_tracer' => $gasInWaitingTracer,

            // Completed statuses
            'sk_completed' => $skCompleted,
            'sr_completed' => $srCompleted,
            'gas_in_completed' => $gasInCompleted,

            // Rejection info
            'sk_rejections' => $skRejections,
            'sr_rejections' => $srRejections,
            'gas_in_rejections' => $gasInRejections,
        ];
    }

    private function getRejectionInfo($latestPhotos): array
    {
        // Filter only rejected photos
        $rejectedPhotos = $latestPhotos->where('photo_status', 'cgp_rejected');

        if ($rejectedPhotos->isEmpty()) {
            return [
                'has_rejections' => false,
                'count' => 0,
                'rejections' => []
            ];
        }

        // Group rejections by user and collect details
        $rejectionsByUser = [];
        $allRejections = [];

        foreach ($rejectedPhotos as $photo) {
            $cgpUserId = $photo->cgp_user_id;
            $cgpUserName = $photo->cgpUser?->name ?? 'Unknown User';

            // Initialize user entry if not exists
            if (!isset($rejectionsByUser[$cgpUserId])) {
                $rejectionsByUser[$cgpUserId] = [
                    'user_id' => $cgpUserId,
                    'user_name' => $cgpUserName,
                    'count' => 0,
                    'photos' => []
                ];
            }

            // Add photo details
            $photoLabel = $this->getPhotoLabel($photo->photo_field_name, $photo->module_name);
            $rejectionsByUser[$cgpUserId]['count']++;
            $rejectionsByUser[$cgpUserId]['photos'][] = [
                'field_name' => $photo->photo_field_name,
                'label' => $photoLabel,
                'notes' => $photo->cgp_notes,
                'rejected_at' => $photo->cgp_rejected_at?->format('d/m/Y H:i'),
            ];

            $allRejections[] = [
                'user_name' => $cgpUserName,
                'field_name' => $photo->photo_field_name,
                'label' => $photoLabel,
                'notes' => $photo->cgp_notes,
                'rejected_at' => $photo->cgp_rejected_at?->format('d/m/Y H:i'),
            ];
        }

        return [
            'has_rejections' => true,
            'count' => $rejectedPhotos->count(),
            'by_user' => array_values($rejectionsByUser),
            'all' => $allRejections,
        ];
    }

    private function getPhotoLabel($fieldName, $module): string
    {
        $module = strtoupper($module);
        $config = config("aergas_photos.modules.{$module}.slots.{$fieldName}");

        return $config['label'] ?? ucwords(str_replace('_', ' ', $fieldName));
    }

    private function getPhotosForCgp($customer): array
    {
        $photos = [
            'sk' => [],
            'sr' => [],
            'gas_in' => []
        ];

        // For each module, get slot completion status which includes ALL slots (uploaded + not uploaded)
        foreach (['sk', 'sr', 'gas_in'] as $module) {
            $moduleData = $this->getModuleData($customer->reff_id_pelanggan, $module);

            // Skip if module data doesn't exist
            if (!$moduleData) {
                continue;
            }

            // Get ALL slots from config (both required and optional)
            $slotCompletion = $moduleData->getSlotCompletionStatus();

            // Get uploaded photos that are ready for CGP review
            // Only include: tracer_approved, cgp_pending, cgp_approved, cgp_rejected
            // Group by photo_field_name and get the LATEST record (highest ID) for each
            $uploadedPhotos = PhotoApproval::where('module_name', $module)
                ->where('reff_id_pelanggan', $customer->reff_id_pelanggan)
                ->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved', 'cgp_rejected'])
                ->with(['tracerUser', 'cgpUser'])
                ->get()
                ->groupBy('photo_field_name')
                ->map(function ($photos) {
                    // Get the latest photo (highest id) for each field
                    return $photos->sortByDesc('id')->first();
                });

            // Build array with ALL slots (uploaded + placeholders for not ready)
            $allPhotos = [];
            foreach ($slotCompletion as $slotKey => $slotInfo) {
                if (isset($uploadedPhotos[$slotKey])) {
                    // Photo ready for CGP - use actual PhotoApproval model
                    $allPhotos[] = $uploadedPhotos[$slotKey];
                } else {
                    // Photo not ready for CGP (draft/tracer_pending/not uploaded)
                    // Create placeholder object
                    $placeholder = (object) [
                        'id' => null,
                        'photo_field_name' => $slotKey,
                        'photo_url' => null,
                        'photo_status' => 'missing',
                        'uploaded_at' => null,
                        'tracer_approved_at' => null,
                        'tracer_rejected_at' => null,
                        'cgp_approved_at' => null,
                        'cgp_rejected_at' => null,
                        'ai_approved_at' => null,
                        'ai_confidence_score' => null,
                        'ai_notes' => null,
                        'tracer_notes' => null,
                        'cgp_notes' => null,
                        'tracerUser' => null,
                        'cgpUser' => null,
                        'is_placeholder' => true,
                        'slot_label' => $slotInfo['label'],
                        'is_required' => $slotInfo['required'],
                    ];

                    // Show placeholder for ALL photos (required and optional)
                    $allPhotos[] = $placeholder;
                }
            }

            $photos[$module] = $allPhotos;
        }

        return $photos;
    }

    private function getModuleData(string $reffId, string $module)
    {
        return match ($module) {
            'sk' => SkData::where('reff_id_pelanggan', $reffId)->whereNull('deleted_at')->first(),
            'sr' => SrData::where('reff_id_pelanggan', $reffId)->whereNull('deleted_at')->first(),
            'gas_in' => GasInData::where('reff_id_pelanggan', $reffId)->whereNull('deleted_at')->first(),
            default => null
        };
    }

    private function updateModuleStatus($photoApproval)
    {
        // Update module status for jalur modules when photos are approved/rejected
        if (in_array($photoApproval->module_name, ['jalur_lowering', 'jalur_joint'])) {
            try {
                if ($photoApproval->module_name === 'jalur_lowering') {
                    $loweringData = \App\Models\JalurLoweringData::find($photoApproval->module_record_id);
                    if ($loweringData && $photoApproval->photo_status === 'cgp_approved') {
                        // Move photo to ACC_CGP folder
                        $this->movePhotoToAccFolder($photoApproval, $loweringData);
                        // Update to acc_cgp if all required photos for this record are approved
                        $this->updateJalurStatus($loweringData, 'cgp');
                    } elseif ($loweringData && $photoApproval->photo_status === 'cgp_rejected') {
                        // Update to revisi_cgp if any photo is rejected
                        $loweringData->update(['status_laporan' => 'revisi_cgp']);
                    }
                } elseif ($photoApproval->module_name === 'jalur_joint') {
                    $jointData = \App\Models\JalurJointData::find($photoApproval->module_record_id);
                    if ($jointData && $photoApproval->photo_status === 'cgp_approved') {
                        // Move photo to ACC_CGP folder
                        $this->movePhotoToAccFolder($photoApproval, $jointData);
                        // Update to acc_cgp if all required photos for this record are approved
                        $this->updateJalurStatus($jointData, 'cgp');
                    } elseif ($jointData && $photoApproval->photo_status === 'cgp_rejected') {
                        // Update to revisi_cgp if any photo is rejected
                        $jointData->update(['status_laporan' => 'revisi_cgp']);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error updating jalur module status: ' . $e->getMessage());
            }
        }
    }

    private function updateJalurStatus($moduleData, $approverType)
    {
        // Get all photo approvals for this record
        $photoApprovals = \App\Models\PhotoApproval::where('module_record_id', $moduleData->id)
            ->where('module_name', $moduleData->getModuleName())
            ->get();

        // Check if all required photos are approved
        $requiredPhotos = $moduleData->getRequiredPhotos();
        $approvedPhotos = $photoApprovals->where('photo_status', $approverType . '_approved')->pluck('photo_field_name')->toArray();

        // If all required photos are approved, update the module status
        if (empty(array_diff($requiredPhotos, $approvedPhotos))) {
            if ($approverType === 'tracer') {
                $moduleData->update(['status_laporan' => 'acc_tracer']);
            } elseif ($approverType === 'cgp') {
                $moduleData->update(['status_laporan' => 'acc_cgp']);
            }
        }
    }

    private function sendRejectionNotification($photoApproval, $notes)
    {
        try {
            // Send Telegram notification
            $message = "🚫 *Photo Rejected by CGP*\n\n";
            $message .= "📋 Reff ID: `{$photoApproval->reff_id_pelanggan}`\n";
            $message .= "📸 Photo: {$photoApproval->photo_field_name}\n";
            $message .= "🏷️ Module: {$photoApproval->module_name}\n";
            $message .= "📝 Notes: {$notes}\n";
            $message .= "👤 Rejected by: " . Auth::user()->name;

            // This will be implemented with actual Telegram service
            // $this->notificationService->sendTelegram($message);

        } catch (Exception $e) {
            Log::error('Failed to send CGP rejection notification: ' . $e->getMessage());
        }
    }

    /**
     * Get jalur photos for CGP review (separate from customer-based reviews)
     */
    public function jalurPhotos(Request $request)
    {
        try {
            $query = PhotoApproval::with(['jalurLowering', 'jalurJoint'])
                ->whereIn('module_name', ['jalur_lowering', 'jalur_joint'])
                ->where('photo_status', 'cgp_pending'); // CGP only reviews after tracer approval

            // Filter by module type
            if ($request->filled('module_type')) {
                $query->where('module_name', $request->module_type);
            }

            // Search by nomor joint or line number
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('jalurLowering', function ($subQ) use ($search) {
                        $subQ->whereHas('lineNumber', function ($lineQ) use ($search) {
                            $lineQ->where('line_number', 'like', "%{$search}%");
                        });
                    })->orWhereHas('jalurJoint', function ($subQ) use ($search) {
                        $subQ->where('nomor_joint', 'like', "%{$search}%");
                    });
                });
            }

            $photos = $query->orderBy('uploaded_at', 'desc')
                ->paginate(20);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $photos,
                ]);
            }

            return view('approvals.cgp.jalur-photos', compact('photos'));

        } catch (Exception $e) {
            Log::error('CGP jalur photos error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat foto jalur');
        }
    }

    private function movePhotoToAccFolder($photoApproval, $moduleData)
    {
        try {
            $originalUrl = $photoApproval->photo_url;

            // Skip if photo URL is not a Google Drive URL
            if (!str_contains($originalUrl, 'drive.google.com') && !str_contains($originalUrl, 'googleusercontent.com')) {
                Log::info('Skipping non-Google Drive photo move', ['photo_id' => $photoApproval->id, 'url' => $originalUrl]);
                return;
            }

            $googleDriveService = app(\App\Services\GoogleDriveService::class);

            // Determine module path and identifiers
            if ($photoApproval->module_name === 'jalur_lowering') {
                $moduleType = 'jalur_lowering';
                $identifier = $moduleData->lineNumber->line_number;
                $clusterName = $moduleData->lineNumber->cluster->nama_cluster;
                $clusterSlug = \Illuminate\Support\Str::slug($clusterName, '_');
                $tanggalFolder = \Carbon\Carbon::parse($moduleData->tanggal_jalur)->format('Y-m-d');
            } else { // jalur_joint
                $moduleType = 'jalur_joint';
                $identifier = $moduleData->nomor_joint;
                $clusterName = $moduleData->cluster->nama_cluster;
                $clusterSlug = \Illuminate\Support\Str::slug($clusterName, '_');
                $tanggalFolder = \Carbon\Carbon::parse($moduleData->tanggal_joint)->format('Y-m-d');
            }

            // Create ACC_CGP folder path: jalur_lowering_acc_cgp/cluster_slug/LineNumber/Date/
            $accFolderPath = "{$moduleType}_acc_cgp/{$clusterSlug}/{$identifier}/{$tanggalFolder}";

            // Extract file ID from Google Drive URL
            $fileId = null;
            if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $originalUrl, $matches)) {
                $fileId = $matches[1];
            } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $originalUrl, $matches)) {
                $fileId = $matches[1];
            } elseif (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $originalUrl, $matches)) {
                $fileId = $matches[1];
            }

            if (!$fileId) {
                Log::warning('Could not extract file ID from photo URL', [
                    'photo_id' => $photoApproval->id,
                    'url' => $originalUrl
                ]);
                return;
            }

            // Generate new filename with ACC prefix
            $timestamp = now()->format('Y-m-d_H-i-s');
            $fieldSlug = str_replace(['foto_evidence_', '_'], ['', '-'], $photoApproval->photo_field_name);
            $newFileName = "ACC_CGP_{$identifier}_{$timestamp}_{$fieldSlug}";

            // Copy file to ACC folder
            $newFileId = $googleDriveService->copyFileToFolder($fileId, $accFolderPath, $newFileName);

            if ($newFileId) {
                $newUrl = "https://drive.google.com/file/d/{$newFileId}/view";

                // Update photo approval with new URL
                $photoApproval->update(['photo_url' => $newUrl]);

                Log::info('Photo moved to ACC_CGP folder successfully', [
                    'photo_id' => $photoApproval->id,
                    'original_url' => $originalUrl,
                    'new_url' => $newUrl,
                    'acc_folder_path' => $accFolderPath
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to move photo to ACC_CGP folder', [
                'photo_id' => $photoApproval->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
