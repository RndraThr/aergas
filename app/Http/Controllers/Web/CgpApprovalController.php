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
    ) {}

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
                // SK photos pending CGP approval
                $query->whereHas('photoApprovals', function($q) {
                    $q->where('module_name', 'sk')
                      ->where('photo_status', 'cgp_pending');
                });
            } elseif ($status === 'sr_ready') {
                // SR photos pending CGP approval
                $query->whereHas('photoApprovals', function($q) {
                    $q->where('module_name', 'sr')
                      ->where('photo_status', 'cgp_pending');
                });
            } elseif ($status === 'gas_in_ready') {
                // Gas In photos pending CGP approval
                $query->whereHas('photoApprovals', function($q) {
                    $q->where('module_name', 'gas_in')
                      ->where('photo_status', 'cgp_pending');
                });
            } else {
                // Default: show all customers with photos pending CGP approval OR already approved
                $query->whereHas('photoApprovals', function($q) {
                    $q->whereIn('photo_status', ['cgp_pending', 'cgp_approved']);
                });
            }

            // Search - apply search filter
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('reff_id_pelanggan', 'like', "%{$search}%")
                      ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                      ->orWhere('alamat', 'like', "%{$search}%");
                });
            }

            $customers = $query->orderBy('created_at', 'desc')->paginate(15);

            // Add CGP status untuk setiap customer
            $customers->getCollection()->transform(function ($customer) {
                $customer->cgp_status = $this->getCgpStatus($customer);
                return $customer;
            });

            if ($request->ajax() || $request->get('ajax')) {
                // Transform items to include cgp_status
                $items = $customers->getCollection()->map(function($customer) {
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

            return view('approvals.cgp.customers', compact('customers'));

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

            // Check if photo is ready for CGP (status cgp_pending)
            if ($photoApproval->photo_status !== 'cgp_pending') {
                Log::warning('CGP Approval Failed - Wrong Status', [
                    'photo_id' => $photoApproval->id,
                    'expected_status' => 'cgp_pending',
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

            if (is_null($moduleData->tracer_approved_at)) {
                throw new Exception('Module belum di-approve oleh Tracer');
            }

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
        // Check based on PhotoApproval status instead of module data
        return [
            'sk_ready' => PhotoApproval::where('reff_id_pelanggan', $customer->reff_id_pelanggan)
                ->where('module_name', 'sk')
                ->where('photo_status', 'cgp_pending')
                ->exists(),
            'sk_completed' => PhotoApproval::where('reff_id_pelanggan', $customer->reff_id_pelanggan)
                ->where('module_name', 'sk')
                ->whereNotNull('cgp_approved_at')
                ->exists(),
            'sr_ready' => PhotoApproval::where('reff_id_pelanggan', $customer->reff_id_pelanggan)
                ->where('module_name', 'sr')
                ->where('photo_status', 'cgp_pending')
                ->exists(),
            'sr_completed' => PhotoApproval::where('reff_id_pelanggan', $customer->reff_id_pelanggan)
                ->where('module_name', 'sr')
                ->whereNotNull('cgp_approved_at')
                ->exists(),
            'gas_in_ready' => PhotoApproval::where('reff_id_pelanggan', $customer->reff_id_pelanggan)
                ->where('module_name', 'gas_in')
                ->where('photo_status', 'cgp_pending')
                ->exists(),
            'gas_in_completed' => PhotoApproval::where('reff_id_pelanggan', $customer->reff_id_pelanggan)
                ->where('module_name', 'gas_in')
                ->whereNotNull('cgp_approved_at')
                ->exists(),
        ];
    }

    private function getPhotosForCgp($customer): array
    {
        $photos = [
            'sk' => [],
            'sr' => [],
            'gas_in' => []
        ];

        $cgpStatus = $this->getCgpStatus($customer);

        // For each module, get slot completion status which includes ALL slots (uploaded + not uploaded)
        foreach (['sk', 'sr', 'gas_in'] as $module) {
            $moduleReady = ($module === 'sk' && ($cgpStatus['sk_ready'] || $cgpStatus['sk_completed'])) ||
                          ($module === 'sr' && ($cgpStatus['sr_ready'] || $cgpStatus['sr_completed'])) ||
                          ($module === 'gas_in' && ($cgpStatus['gas_in_ready'] || $cgpStatus['gas_in_completed']));

            if (!$moduleReady) {
                continue;
            }

            $moduleData = $this->getModuleData($customer->reff_id_pelanggan, $module);

            if ($moduleData) {
                // Get ALL slots from config (both required and optional)
                $slotCompletion = $moduleData->getSlotCompletionStatus();

                // Get uploaded photos that have been approved by tracer
                $uploadedPhotos = PhotoApproval::where('module_name', $module)
                    ->where('reff_id_pelanggan', $customer->reff_id_pelanggan)
                    ->whereNotNull('tracer_approved_at')
                    ->with(['tracerUser', 'cgpUser'])
                    ->get()
                    ->keyBy('photo_field_name');

                // Build array with ALL slots (uploaded + placeholders for unuploaded)
                $allPhotos = [];
                foreach ($slotCompletion as $slotKey => $slotInfo) {
                    if (isset($uploadedPhotos[$slotKey])) {
                        // Photo exists - use actual PhotoApproval model
                        $allPhotos[] = $uploadedPhotos[$slotKey];
                    } else {
                        // Photo not uploaded - create placeholder object
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

                        // Only show placeholder for required photos
                        if ($slotInfo['required']) {
                            $allPhotos[] = $placeholder;
                        }
                    }
                }

                $photos[$module] = $allPhotos;
            } else {
                // No module data - just get uploaded photos (fallback)
                $photos[$module] = PhotoApproval::where('module_name', $module)
                    ->where('reff_id_pelanggan', $customer->reff_id_pelanggan)
                    ->whereNotNull('tracer_approved_at')
                    ->with(['tracerUser', 'cgpUser'])
                    ->orderBy('photo_field_name')
                    ->get();
            }
        }

        return $photos;
    }

    private function getModuleData(string $reffId, string $module)
    {
        return match($module) {
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
                $query->where(function($q) use ($search) {
                    $q->whereHas('jalurLowering', function($subQ) use ($search) {
                        $subQ->whereHas('lineNumber', function($lineQ) use ($search) {
                            $lineQ->where('line_number', 'like', "%{$search}%");
                        });
                    })->orWhereHas('jalurJoint', function($subQ) use ($search) {
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
                $moduleType = 'JALUR_LOWERING';
                $identifier = $moduleData->lineNumber->line_number;
                $clusterName = $moduleData->lineNumber->cluster->nama_cluster;
                $tanggalFolder = \Carbon\Carbon::parse($moduleData->tanggal_jalur)->format('Y-m-d');
            } else { // jalur_joint
                $moduleType = 'JALUR_JOINT';
                $identifier = $moduleData->nomor_joint;
                $clusterName = $moduleData->cluster->nama_cluster;
                $tanggalFolder = \Carbon\Carbon::parse($moduleData->tanggal_joint)->format('Y-m-d');
            }

            // Create ACC_CGP folder path: JALUR_LOWERING_ACC_CGP/Cluster/LineNumber/Date/
            $accFolderPath = "{$moduleType}_ACC_CGP/{$clusterName}/{$identifier}/{$tanggalFolder}";
            
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
