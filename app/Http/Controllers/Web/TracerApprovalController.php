<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Models\{CalonPelanggan, PhotoApproval, SkData, SrData, GasInData};
use App\Services\{PhotoApprovalService, NotificationService};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{DB, Log, Auth};
use Exception;

class TracerApprovalController extends Controller implements HasMiddleware
{
    public function __construct(
        private PhotoApprovalService $photoApprovalService,
        private NotificationService $notificationService
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:admin,super_admin'), // Tracer Review menggunakan role admin
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

            // Filter berdasarkan status
            $status = $request->get('status');

            if ($status === 'sk_pending') {
                // Customers with SK photos pending tracer approval OR SK data without photos
                $query->where(function($q) {
                    $q->whereHas('photoApprovals', function($photoQ) {
                        $photoQ->where('module_name', 'sk')
                              ->where('photo_status', 'tracer_pending');
                    })->orWhereHas('skData');
                });
            } elseif ($status === 'sr_pending') {
                // Show all customers with SR data (locked or unlocked)
                $query->whereHas('srData');
            } elseif ($status === 'gas_in_pending') {
                // Show all customers with Gas In data (locked or unlocked)
                $query->whereHas('gasInData');
            } else {
                // Default: show customers that have photos pending tracer approval OR have any module data
                $query->where(function($q) {
                    $q->whereHas('photoApprovals', function($photoQ) {
                        $photoQ->where('photo_status', 'tracer_pending');
                    })->orWhereHas('skData')
                      ->orWhereHas('srData')
                      ->orWhereHas('gasInData');
                });
            }

            // Search
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
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

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $customers,
                ]);
            }

            return view('approvals.tracer.customers', compact('customers'));

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
            if (!$this->canApproveModule($photoApproval->reff_id_pelanggan, $photoApproval->module_name)) {
                throw new Exception('Module sebelumnya belum selesai di-approve');
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

            return response()->json([
                'success' => true,
                'message' => $action === 'approve' ? 'Photo berhasil di-approve' : 'Photo berhasil di-reject',
                'data' => $result
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Approve photo error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
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

    // ==================== PRIVATE METHODS ====================

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
            'current_step' => 'sk',
            'sk_completed' => false,
            'sk_locked' => false,
            'sr_available' => false,
            'sr_completed' => false, 
            'sr_locked' => true,
            'gas_in_available' => false,
            'gas_in_completed' => false,
            'gas_in_locked' => true,
        ];

        try {
            // Check SK photos - completed if any SK photo is tracer_approved or cgp_approved
            $skPhotos = $customer->photoApprovals->where('module_name', 'sk');
            if ($skPhotos->isNotEmpty() && $skPhotos->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved'])->isNotEmpty()) {
                $status['sk_completed'] = true;
                $status['current_step'] = 'sr';
                $status['sr_available'] = true;
                $status['sr_locked'] = false; // SR unlocked
            }

            // Check SR photos - completed if any SR photo is tracer_approved or cgp_approved  
            if ($status['sr_available']) {
                $srPhotos = $customer->photoApprovals->where('module_name', 'sr');
                if ($srPhotos->isNotEmpty() && $srPhotos->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved'])->isNotEmpty()) {
                    $status['sr_completed'] = true;
                    $status['current_step'] = 'gas_in';
                    $status['gas_in_available'] = true;
                    $status['gas_in_locked'] = false; // Gas In unlocked
                }
            }

            // Check Gas In photos - completed if any Gas In photo is tracer_approved or cgp_approved
            if ($status['gas_in_available']) {
                $gasInPhotos = $customer->photoApprovals->where('module_name', 'gas_in');
                if ($gasInPhotos->isNotEmpty() && $gasInPhotos->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved'])->isNotEmpty()) {
                    $status['gas_in_completed'] = true;
                    $status['current_step'] = 'completed';
                }
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
        
        if ($hasPhotos) {
            $photoCount = $photos->count();
            $pendingCount = $photos->where('photo_status', 'tracer_pending')->count();
            $approvedCount = $photos->whereIn('photo_status', ['tracer_approved', 'cgp_pending', 'cgp_approved'])->count();
            
            if ($approvedCount > 0) {
                $photoStatus = 'has_approved';
            } elseif ($pendingCount > 0) {
                $photoStatus = 'has_pending';
            }
        }
        
        return [
            'has_data' => $hasData,
            'has_photos' => $hasPhotos,
            'photo_count' => $photoCount,
            'pending_count' => $pendingCount,
            'approved_count' => $approvedCount,
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
            case 'has_approved':
                return 'Approved';
            case 'has_pending':
                return 'Pending Review';
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

        // SK photos (always available)
        $photos['sk'] = PhotoApproval::where('module_name', 'sk')
            ->where('reff_id_pelanggan', $customer->reff_id_pelanggan)
            ->orderBy('photo_field_name')
            ->get();

        // SR photos (always show, but mark as locked if not available)
        $photos['sr'] = PhotoApproval::where('module_name', 'sr')
            ->where('reff_id_pelanggan', $customer->reff_id_pelanggan)
            ->orderBy('photo_field_name')
            ->get();

        // Gas In photos (always show, but mark as locked if not available)
        $photos['gas_in'] = PhotoApproval::where('module_name', 'gas_in')
            ->where('reff_id_pelanggan', $customer->reff_id_pelanggan)
            ->orderBy('photo_field_name')
            ->get();

        return $photos;
    }

    private function canApproveModule(string $reffId, string $module): bool
    {
        $customer = CalonPelanggan::where('reff_id_pelanggan', $reffId)->first();
        if (!$customer) return false;

        $sequential = $this->getSequentialStatus($customer);

        return match($module) {
            'sk' => true, // SK always can be approved
            'sr' => $sequential['sk_completed'],
            'gas_in' => $sequential['sr_completed'],
            default => false
        };
    }

    private function getModuleData(string $reffId, string $module)
    {
        return match($module) {
            'sk' => SkData::where('reff_id_pelanggan', $reffId)->first(),
            'sr' => SrData::where('reff_id_pelanggan', $reffId)->first(),
            'gas_in' => GasInData::where('reff_id_pelanggan', $reffId)->first(),
            default => null
        };
    }

    private function updateModuleStatus($photoApproval)
    {
        // Update module status based on photo approval status
        // This will be implemented based on existing PhotoApprovalService logic
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
}
