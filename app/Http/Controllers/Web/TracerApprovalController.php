<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Models\{CalonPelanggan, PhotoApproval, SkData, SrData, GasInData, JalurLoweringData, JalurJointData};
use App\Services\{PhotoApprovalService, NotificationService};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{DB, Log, Auth};
use Exception;

class TracerApprovalController extends Controller implements HasMiddleware
{
    public function __construct(
        private PhotoApprovalService $photoApprovalService,
        private NotificationService $notificationService
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:tracer,admin,super_admin'), // Tracer Review menggunakan role admin
        ];
    }

    /**
     * Dashboard Tracer - Overview semua pending approvals
     */
    public function index()
    {
        try {
            $stats = $this->getTracerStats();
            $recentActivities = $this->getRecentActivities();

            return view('approvals.tracer.index', compact('stats', 'recentActivities'));

        } catch (Exception $e) {
            Log::error('Tracer dashboard error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat dashboard');
        }
    }

    /**
     * List pelanggan yang perlu review tracer
     */
    public function customers(Request $request)
    {
        try {
            // Simple query first - get all customers with photo approvals
            $query = CalonPelanggan::with(['skData', 'srData', 'gasInData', 'photoApprovals']);

            // Filter berdasarkan tanggal lapangan
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $dateModule = $request->get('date_module', 'all'); // all, sk, sr, gas_in

            if ($dateFrom && $dateTo) {
                $this->applyDateFilter($query, $dateFrom, $dateTo, $dateModule);
            }

            // Filter berdasarkan status
            $status = $request->get('status');

            if ($status === 'sk_pending') {
                // Customers with SK photos pending tracer approval OR SK data without photos
                $query->where(function ($q) {
                    $q->whereHas('photoApprovals', function ($photoQ) {
                        $photoQ->where('module_name', 'sk')
                            ->where('photo_status', 'tracer_pending');
                    })->orWhereHas('skData');
                });
            } elseif ($status === 'sr_pending') {
                // SR Pending: SK must be completed (cgp_review or completed) AND SR has pending photos
                $query->whereHas('skData', function ($skQ) {
                    // SK harus sudah di-approve tracer (minimal cgp_review)
                    $skQ->whereIn('module_status', ['cgp_review', 'completed']);
                })->where(function ($q) {
                    // DAN SR ada foto pending atau ada data SR
                    $q->whereHas('photoApprovals', function ($photoQ) {
                        $photoQ->where('module_name', 'sr')
                            ->where('photo_status', 'tracer_pending');
                    })->orWhereHas('srData');
                });
            } elseif ($status === 'gas_in_pending') {
                // Gas In Pending: SK AND SR must be completed AND Gas In has pending photos
                $query->whereHas('skData', function ($skQ) {
                    // SK harus sudah di-approve tracer (minimal cgp_review)
                    $skQ->whereIn('module_status', ['cgp_review', 'completed']);
                })->whereHas('srData', function ($srQ) {
                    // SR harus sudah di-approve tracer (minimal cgp_review)
                    $srQ->whereIn('module_status', ['cgp_review', 'completed']);
                })->where(function ($q) {
                    // DAN Gas In ada foto pending atau ada data Gas In
                    $q->whereHas('photoApprovals', function ($photoQ) {
                        $photoQ->where('module_name', 'gas_in')
                            ->where('photo_status', 'tracer_pending');
                    })->orWhereHas('gasInData');
                });
            } else {
                // Default: show customers that have photos pending tracer approval OR have any module data
                $query->where(function ($q) {
                    $q->whereHas('photoApprovals', function ($photoQ) {
                        $photoQ->where('photo_status', 'tracer_pending');
                    })->orWhereHas('skData')
                        ->orWhereHas('srData')
                        ->orWhereHas('gasInData');
                });
            }

            // Search
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('reff_id_pelanggan', 'like', "%{$search}%")
                        ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                        ->orWhere('alamat', 'like', "%{$search}%");
                });
            }

            $customers = $query->orderBy('created_at', 'desc')->paginate(15);

            // Add sequential status untuk setiap customer
            $customers->getCollection()->transform(function ($customer) {
                try {
                    return $this->addSequentialStatus($customer);
                } catch (Exception $e) {
                    Log::warning('Failed to add sequential status for customer', [
                        'reff_id' => $customer->reff_id_pelanggan,
                        'error' => $e->getMessage()
                    ]);
                    // Return customer without sequential status rather than failing
                    $customer->sequential_status = $this->getDefaultSequentialStatus();
                    return $customer;
                }
            });

            if ($request->ajax() || $request->get('ajax')) {
                // Transform items to include sequential_status
                $items = $customers->getCollection()->map(function ($customer) {
                    return [
                        'reff_id_pelanggan' => $customer->reff_id_pelanggan,
                        'nama_pelanggan' => $customer->nama_pelanggan,
                        'alamat' => $customer->alamat,
                        'sequential_status' => $customer->sequential_status ?? $this->getSequentialStatus($customer),
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
            $stats = $this->photoApprovalService->getDashboardStats('tracer');

            return view('approvals.tracer.customers', compact('customers', 'stats'));

        } catch (Exception $e) {
            Log::error('Tracer customers error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'sql' => $e->getMessage(),
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
     * Detail photos per reff_id untuk review
     */
    public function customerPhotos(string $reffId)
    {
        try {
            $customer = CalonPelanggan::with(['skData', 'srData', 'gasInData'])
                ->where('reff_id_pelanggan', $reffId)
                ->firstOrFail();

            // Get photos dengan sequential logic
            $photos = $this->getPhotosForTracer($customer);
            $sequential = $this->getSequentialStatus($customer);

            return view('approvals.tracer.photos', compact('customer', 'photos', 'sequential'));

        } catch (Exception $e) {
            Log::error('Customer photos error: ' . $e->getMessage());
            return back()->with('error', 'Data tidak ditemukan');
        }
    }

    /**
     * AI Review untuk batch photos
     */
    public function aiReview(Request $request)
    {
        $request->validate([
            'reff_id' => 'required|string',
            'module' => 'required|in:sk,sr,gas_in'
        ]);

        try {
            DB::beginTransaction();

            $reffId = $request->get('reff_id');
            $module = $request->get('module');

            // Validation: Check sequential requirements for AI review
            if (!$this->canApproveModule($reffId, $module)) {
                throw new Exception('Module sebelumnya belum selesai di-approve');
            }

            // Get module data
            $moduleData = $this->getModuleData($reffId, $module);
            if (!$moduleData) {
                throw new Exception('Data module tidak ditemukan');
            }

            // Run AI review
            $result = $this->photoApprovalService->runAIReviewForModule($moduleData, $module);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'AI Review berhasil dijalankan',
                'data' => $result
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('AI Review error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menjalankan AI Review: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve/Reject individual photo
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

            // Validation: Check sequential requirements for individual photo approval
            // Skip validation for jalur modules as they don't have sequential dependencies
            if (!in_array($photoApproval->module_name, ['jalur_lowering', 'jalur_joint'])) {
                if (!$this->canApproveModule($photoApproval->reff_id_pelanggan, $photoApproval->module_name)) {
                    throw new Exception('Module sebelumnya belum selesai di-approve');
                }
            }

            if ($action === 'approve') {
                $result = $this->photoApprovalService->approvePhotoByTracer(
                    $photoApproval,
                    Auth::id(),
                    $notes
                );
            } else {
                $result = $this->photoApprovalService->rejectPhotoByTracer(
                    $photoApproval,
                    Auth::id(),
                    $notes
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
            Log::error('Approve photo error: ' . $e->getMessage());

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
     * Replace photo (Admin/Super Admin only)
     */
    public function replacePhoto(Request $request)
    {
        // Validate admin/super_admin permission
        if (!Auth::user()->hasAnyRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Hanya admin/super_admin yang dapat mengganti foto.'
            ], 403);
        }

        $request->validate([
            'photo_id' => 'required|integer|exists:photo_approvals,id',
            'new_photo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
            'module_name' => 'required|string',
            'replacement_notes' => 'nullable|string|max:1000',
            'ai_precheck' => 'nullable|boolean'
        ]);

        try {
            DB::beginTransaction();

            $photoApproval = PhotoApproval::findOrFail($request->photo_id);
            $replacementNotes = $request->replacement_notes ?? 'Photo replaced by ' . Auth::user()->name;
            $runAiPrecheck = $request->boolean('ai_precheck', false);

            // Store old photo information for logging
            $oldPhotoUrl = $photoApproval->photo_url;
            $oldStoragePath = $photoApproval->storage_path;

            // Upload new photo using PhotoApprovalService
            $uploadedFile = $request->file('new_photo');

            // Determine module data for proper storage
            $moduleData = match ($photoApproval->module_name) {
                'sk' => SkData::where('reff_id_pelanggan', $photoApproval->reff_id_pelanggan)->first(),
                'sr' => SrData::where('reff_id_pelanggan', $photoApproval->reff_id_pelanggan)->first(),
                'gas_in' => GasInData::where('reff_id_pelanggan', $photoApproval->reff_id_pelanggan)->first(),
                'jalur_lowering' => JalurLoweringData::find($photoApproval->module_record_id),
                'jalur_joint' => JalurJointData::find($photoApproval->module_record_id),
                default => null
            };

            if (!$moduleData) {
                throw new Exception('Module data tidak ditemukan');
            }

            // Use PhotoApprovalService to upload the new photo
            // handleUploadAndValidate will upload file and update/create PhotoApproval record
            $result = $this->photoApprovalService->handleUploadAndValidate(
                module: strtoupper($photoApproval->module_name),
                reffId: $photoApproval->reff_id_pelanggan,
                slotIncoming: $photoApproval->photo_field_name,
                file: $uploadedFile,
                userId: Auth::id()
            );

            if (!$result['success']) {
                throw new Exception($result['message'] ?? 'Upload failed');
            }

            // Get the updated photo approval
            $photoApproval->refresh();

            // Run AI precheck if requested
            if ($runAiPrecheck) {
                try {
                    $aiResult = $this->photoApprovalService->runAIPrecheck($photoApproval);
                    $photoApproval->refresh();
                } catch (Exception $e) {
                    Log::warning('AI precheck failed after photo replacement: ' . $e->getMessage());
                }
            }

            // Log the replacement action
            Log::info('Photo replaced by admin', [
                'photo_id' => $photoApproval->id,
                'reff_id' => $photoApproval->reff_id_pelanggan,
                'module' => $photoApproval->module_name,
                'field' => $photoApproval->photo_field_name,
                'old_photo_url' => $oldPhotoUrl,
                'new_photo_url' => $photoApproval->photo_url,
                'replaced_by' => Auth::user()->name,
                'replacement_notes' => $replacementNotes,
                'ai_precheck' => $runAiPrecheck
            ]);

            // Update module status if needed
            $this->updateModuleStatus($photoApproval);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Foto berhasil diganti. Status approval telah direset.',
                'data' => [
                    'photo_approval' => $photoApproval,
                    'ai_precheck_run' => $runAiPrecheck
                ]
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Replace photo error: ' . $e->getMessage(), [
                'photo_id' => $request->photo_id,
                'user' => Auth::user()->name,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengganti foto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch approve untuk module
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

            // Validation: Check sequential requirements
            if (!$this->canApproveModule($reffId, $module)) {
                throw new Exception('Module sebelumnya belum selesai di-approve');
            }

            $moduleData = $this->getModuleData($reffId, $module);
            if (!$moduleData) {
                throw new Exception('Data module tidak ditemukan');
            }

            $result = $this->photoApprovalService->approveModuleByTracer(
                $moduleData,
                $module,
                Auth::id(),
                $notes
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Module {$module} berhasil di-approve",
                'data' => $result
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Approve module error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject module (for incomplete/missing required photos)
     */
    public function rejectModule(Request $request)
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
            $notes = $request->get('notes', 'Module rejected: Foto wajib belum lengkap');

            $moduleData = $this->getModuleData($reffId, $module);
            if (!$moduleData) {
                throw new Exception('Data module tidak ditemukan');
            }

            $result = $this->photoApprovalService->rejectModuleByTracer(
                $moduleData,
                $module,
                Auth::id(),
                $notes
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Module {$module} telah di-reject. Petugas lapangan harus melengkapi foto yang kurang.",
                'data' => $result
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Reject module error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ==================== PRIVATE METHODS ====================

    /**
     * Apply date filter based on module field dates (tanggal_instalasi, tanggal_pemasangan, tanggal_gas_in)
     */
    private function applyDateFilter($query, string $dateFrom, string $dateTo, string $dateModule): void
    {
        if ($dateModule === 'all') {
            // Filter untuk semua module - customer muncul jika salah satu module ada dalam range tanggal
            $query->where(function ($q) use ($dateFrom, $dateTo) {
                // SK: tanggal_instalasi
                $q->orWhereHas('skData', function ($skQ) use ($dateFrom, $dateTo) {
                    $skQ->whereBetween('tanggal_instalasi', [$dateFrom, $dateTo]);
                })
                    // SR: tanggal_pemasangan
                    ->orWhereHas('srData', function ($srQ) use ($dateFrom, $dateTo) {
                        $srQ->whereBetween('tanggal_pemasangan', [$dateFrom, $dateTo]);
                    })
                    // Gas In: tanggal_gas_in
                    ->orWhereHas('gasInData', function ($gasInQ) use ($dateFrom, $dateTo) {
                        $gasInQ->whereBetween('tanggal_gas_in', [$dateFrom, $dateTo]);
                    });
            });
        } elseif ($dateModule === 'sk') {
            // Filter hanya SK berdasarkan tanggal_instalasi
            $query->whereHas('skData', function ($skQ) use ($dateFrom, $dateTo) {
                $skQ->whereBetween('tanggal_instalasi', [$dateFrom, $dateTo]);
            });
        } elseif ($dateModule === 'sr') {
            // Filter hanya SR berdasarkan tanggal_pemasangan
            $query->whereHas('srData', function ($srQ) use ($dateFrom, $dateTo) {
                $srQ->whereBetween('tanggal_pemasangan', [$dateFrom, $dateTo]);
            });
        } elseif ($dateModule === 'gas_in') {
            // Filter hanya Gas In berdasarkan tanggal_gas_in
            $query->whereHas('gasInData', function ($gasInQ) use ($dateFrom, $dateTo) {
                $gasInQ->whereBetween('tanggal_gas_in', [$dateFrom, $dateTo]);
            });
        }
    }

    private function getTracerStats(): array
    {
        return [
            'total_pending' => PhotoApproval::whereNull('tracer_approved_at')->count(),
            'sk_pending' => PhotoApproval::where('module_name', 'sk')->whereNull('tracer_approved_at')->count(),
            'sr_pending' => PhotoApproval::where('module_name', 'sr')->whereNull('tracer_approved_at')->count(),
            'gas_in_pending' => PhotoApproval::where('module_name', 'gas_in')->whereNull('tracer_approved_at')->count(),
            'today_approved' => PhotoApproval::whereDate('tracer_approved_at', today())->count(),
        ];
    }

    private function getRecentActivities()
    {
        return PhotoApproval::with(['pelanggan'])
            ->whereNotNull('tracer_approved_at')
            ->orderBy('tracer_approved_at', 'desc')
            ->limit(10)
            ->get();
    }

    private function addSequentialStatus($customer)
    {
        $customer->sequential_status = $this->getSequentialStatus($customer);
        return $customer;
    }

    private function getSequentialStatus($customer): array
    {
        $status = [
            'current_step' => 'parallel',
            'sk_completed' => false,
            'sk_locked' => false,
            'sk_rejected' => false,
            'sr_available' => true, // Always available in parallel
            'sr_completed' => false,
            'sr_locked' => false, // Never locked
            'sr_rejected' => false,
            'gas_in_available' => true, // Always available in parallel
            'gas_in_completed' => false,
            'gas_in_locked' => false, // Never locked
            'gas_in_rejected' => false,
        ];

        try {
            // Check SK photos - use module status from database
            $skData = $customer->skData;
            if ($skData) {
                $status['sk_rejected'] = $skData->module_status === 'rejected';
                // SK completed only if module_status is cgp_review or completed
                if (in_array($skData->module_status, ['cgp_review', 'completed'])) {
                    $status['sk_completed'] = true;
                }
            }

            // Check SR photos - Independent
            $srData = $customer->srData;
            if ($srData) {
                $status['sr_rejected'] = $srData->module_status === 'rejected';
                // SR completed only if module_status is cgp_review or completed
                if (in_array($srData->module_status, ['cgp_review', 'completed'])) {
                    $status['sr_completed'] = true;
                }
            }

            // Check Gas In photos - Independent
            $gasInData = $customer->gasInData;
            if ($gasInData) {
                $status['gas_in_rejected'] = $gasInData->module_status === 'rejected';
                // Gas In completed only if module_status is cgp_review or completed
                if (in_array($gasInData->module_status, ['cgp_review', 'completed'])) {
                    $status['gas_in_completed'] = true;
                }
            }

            // Determine if everything is completed
            if ($status['sk_completed'] && $status['sr_completed'] && $status['gas_in_completed']) {
                $status['current_step'] = 'completed';
            }

            // Add module-specific status info
            $status['modules'] = [
                'sk' => $this->getModuleStatus($customer, 'sk'),
                'sr' => $this->getModuleStatus($customer, 'sr'),
                'gas_in' => $this->getModuleStatus($customer, 'gas_in'),
            ];
        } catch (Exception $e) {
            Log::warning('Error in getSequentialStatus', [
                'reff_id' => $customer->reff_id_pelanggan ?? 'unknown',
                'error' => $e->getMessage()
            ]);
        }

        return $status;
    }

    private function getModuleStatus($customer, $module): array
    {
        $moduleData = null;
        $hasData = false;

        switch ($module) {
            case 'sk':
                $moduleData = $customer->skData;
                break;
            case 'sr':
                $moduleData = $customer->srData;
                break;
            case 'gas_in':
                $moduleData = $customer->gasInData;
                break;
        }

        $hasData = !is_null($moduleData);
        $photos = $customer->photoApprovals->where('module_name', $module);
        $hasPhotos = $photos->isNotEmpty();

        $photoStatus = null;
        $photoCount = 0;
        $pendingCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;

        if ($hasPhotos) {
            $photoCount = $photos->count();
            $rejectedCount = $photos->whereIn('photo_status', ['tracer_rejected', 'cgp_rejected'])->count();
            $pendingCount = $photos->whereIn('photo_status', ['tracer_pending', 'cgp_pending', 'ai_pending'])->count();
            $approvedCount = $photos->whereIn('photo_status', ['tracer_approved', 'cgp_approved'])->count();

            // Priority: rejected > pending > approved
            if ($rejectedCount > 0) {
                $photoStatus = 'has_rejected';
            } elseif ($pendingCount > 0) {
                $photoStatus = 'has_pending';
            } elseif ($approvedCount > 0) {
                $photoStatus = 'has_approved';
            }
        }

        return [
            'has_data' => $hasData,
            'has_photos' => $hasPhotos,
            'photo_count' => $photoCount,
            'pending_count' => $pendingCount,
            'approved_count' => $approvedCount,
            'rejected_count' => $rejectedCount,
            'photo_status' => $photoStatus,
            'status_text' => $this->getModuleStatusText($hasData, $hasPhotos, $photoStatus)
        ];
    }

    private function getModuleStatusText($hasData, $hasPhotos, $photoStatus): string
    {
        if (!$hasData) {
            return 'No Data';
        }

        if (!$hasPhotos) {
            return 'No Photos';
        }

        switch ($photoStatus) {
            case 'has_rejected':
                return 'Has Rejections';
            case 'has_pending':
                return 'Pending Review';
            case 'has_approved':
                return 'Approved';
            default:
                return 'Draft';
        }
    }

    private function getDefaultSequentialStatus(): array
    {
        return [
            'current_step' => 'sk',
            'sk_completed' => false,
            'sr_available' => false,
            'sr_completed' => false,
            'gas_in_available' => false,
            'gas_in_completed' => false,
        ];
    }

    private function getPhotosForTracer($customer): array
    {
        $photos = [
            'sk' => [],
            'sr' => [],
            'gas_in' => []
        ];

        $sequential = $this->getSequentialStatus($customer);

        // For each module, get slot completion status which includes ALL slots (uploaded + not uploaded)
        foreach (['sk', 'sr', 'gas_in'] as $module) {
            $moduleData = $this->getModuleData($customer->reff_id_pelanggan, $module);

            if ($moduleData) {
                // Get ALL slots from config (both required and uploaded)
                $slotCompletion = $moduleData->getSlotCompletionStatus();

                // Get uploaded photos
                $uploadedPhotos = PhotoApproval::where('module_name', $module)
                    ->where('reff_id_pelanggan', $customer->reff_id_pelanggan)
                    ->with(['tracerUser', 'cgpUser'])
                    ->get()
                    ->keyBy('photo_field_name');

                // Build array with ALL slots (uploaded + placeholders for unuploaded required)
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
                // No module data - just get uploaded photos
                $photos[$module] = PhotoApproval::where('module_name', $module)
                    ->where('reff_id_pelanggan', $customer->reff_id_pelanggan)
                    ->with(['tracerUser', 'cgpUser'])
                    ->orderBy('photo_field_name')
                    ->get();
            }
        }

        return $photos;
    }

    private function canApproveModule(string $reffId, string $module): bool
    {
        $customer = CalonPelanggan::where('reff_id_pelanggan', $reffId)->first();
        if (!$customer)
            return false;

        $sequential = $this->getSequentialStatus($customer);

        // Parallel workflow: All modules can be approved independently
        return true;
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
                    if ($loweringData && $photoApproval->photo_status === 'tracer_approved') {
                        // Update to acc_tracer if all required photos for this record are approved
                        $this->updateJalurStatus($loweringData, 'tracer');
                    } elseif ($loweringData && $photoApproval->photo_status === 'tracer_rejected') {
                        // Update to revisi_tracer if any photo is rejected
                        $loweringData->update(['status_laporan' => 'revisi_tracer']);
                    }
                } elseif ($photoApproval->module_name === 'jalur_joint') {
                    $jointData = \App\Models\JalurJointData::find($photoApproval->module_record_id);
                    if ($jointData && $photoApproval->photo_status === 'tracer_approved') {
                        // Update to acc_tracer if all required photos for this record are approved
                        $this->updateJalurStatus($jointData, 'tracer');
                    } elseif ($jointData && $photoApproval->photo_status === 'tracer_rejected') {
                        // Update to revisi_tracer if any photo is rejected
                        $jointData->update(['status_laporan' => 'revisi_tracer']);
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
            $message = "🚫 *Photo Rejected by Tracer*\n\n";
            $message .= "📋 Reff ID: `{$photoApproval->reff_id_pelanggan}`\n";
            $message .= "📸 Photo: {$photoApproval->photo_type}\n";
            $message .= "🏷️ Module: {$photoApproval->module_type}\n";
            $message .= "📝 Notes: {$notes}\n";
            $message .= "👤 Rejected by: " . Auth::user()->name;

            // This will be implemented with actual Telegram service
            // $this->notificationService->sendTelegram($message);

        } catch (Exception $e) {
            Log::error('Failed to send rejection notification: ' . $e->getMessage());
        }
    }

    /**
     * Get jalur photos for review (separate from customer-based reviews)
     */
    public function jalurPhotos(Request $request)
    {
        try {
            $query = PhotoApproval::with(['jalurLowering', 'jalurJoint'])
                ->whereIn('module_name', ['jalur_lowering', 'jalur_joint']);

            // Filter by status - default to tracer_pending if no filter
            $statusFilter = $request->get('status_filter', 'tracer_pending');
            if ($statusFilter) {
                $query->where('photo_status', $statusFilter);
            }

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

            return view('approvals.tracer.jalur-photos', compact('photos'));

        } catch (Exception $e) {
            Log::error('Tracer jalur photos error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat foto jalur');
        }
    }
}
