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
            new Middleware('role:tracer,admin,super_admin'),
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
            // Simple query first - get all customers with SK data
            $query = CalonPelanggan::with(['skData', 'srData', 'gasInData']);

            // Filter berdasarkan status
            $status = $request->get('status');

            if ($status === 'sk_pending') {
                // Only customers with SK data that hasn't been approved by tracer
                $query->whereHas('skData', function($q) {
                    $q->whereNull('tracer_approved_at');
                });
            } elseif ($status === 'sr_pending') {
                // Only customers with SR data that hasn't been approved, but SK is approved
                $query->whereHas('skData', function($q) {
                    $q->whereNotNull('tracer_approved_at');
                })->whereHas('srData', function($q) {
                    $q->whereNull('tracer_approved_at');
                });
            } elseif ($status === 'gas_in_pending') {
                // Only customers with Gas In data that hasn't been approved, but SR is approved
                $query->whereHas('srData', function($q) {
                    $q->whereNotNull('tracer_approved_at');
                })->whereHas('gasInData', function($q) {
                    $q->whereNull('tracer_approved_at');
                });
            } else {
                // Default: show customers that have either SK data OR photo approvals
                $query->where(function($q) {
                    $q->whereHas('skData')
                      ->orWhereHas('photoApprovals', function($photoQuery) {
                          $photoQuery->where('module_name', 'sk');
                      });
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
            'sr_available' => false,
            'sr_completed' => false,
            'gas_in_available' => false,
            'gas_in_completed' => false,
        ];

        try {
            // Check SK - more defensive checking
            if ($customer->skData && !is_null($customer->skData->tracer_approved_at)) {
                $status['sk_completed'] = true;
                $status['current_step'] = 'sr';
                $status['sr_available'] = true;
            }

            // Check SR
            if ($status['sr_available'] && $customer->srData && !is_null($customer->srData->tracer_approved_at)) {
                $status['sr_completed'] = true;
                $status['current_step'] = 'gas_in';
                $status['gas_in_available'] = true;
            }

            // Check Gas In
            if ($status['gas_in_available'] && $customer->gasInData && !is_null($customer->gasInData->tracer_approved_at)) {
                $status['gas_in_completed'] = true;
                $status['current_step'] = 'completed';
            }
        } catch (Exception $e) {
            Log::warning('Error in getSequentialStatus', [
                'reff_id' => $customer->reff_id_pelanggan ?? 'unknown',
                'error' => $e->getMessage()
            ]);
        }

        return $status;
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

        // SR photos (only if SK completed)
        if ($sequential['sr_available']) {
            $photos['sr'] = PhotoApproval::where('module_name', 'sr')
                ->where('reff_id_pelanggan', $customer->reff_id_pelanggan)
                ->orderBy('photo_field_name')
                ->get();
        }

        // Gas In photos (only if SR completed)
        if ($sequential['gas_in_available']) {
            $photos['gas_in'] = PhotoApproval::where('module_name', 'gas_in')
                ->where('reff_id_pelanggan', $customer->reff_id_pelanggan)
                ->orderBy('photo_field_name')
                ->get();
        }

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
