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

class CgpApprovalController extends Controller implements HasMiddleware
{
    public function __construct(
        private PhotoApprovalService $photoApprovalService,
        private NotificationService $notificationService
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:tracer,super_admin'), // CGP Review menggunakan role tracer
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
                // Default: show all customers with photos pending CGP approval
                $query->whereHas('photoApprovals', function($q) {
                    $q->where('photo_status', 'cgp_pending');
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

            // Add CGP status untuk setiap customer
            $customers->getCollection()->transform(function ($customer) {
                $customer->cgp_status = $this->getCgpStatus($customer);
                return $customer;
            });

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $customers,
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

            return view('approvals.cgp.photos', compact('customer', 'photos', 'cgpStatus'));

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

            return response()->json([
                'success' => true,
                'message' => $action === 'approve' ? 'Photo berhasil di-approve' : 'Photo berhasil di-reject',
                'data' => $result
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('CGP approve photo error: ' . $e->getMessage());

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
            if (!$moduleData || is_null($moduleData->tracer_approved_at)) {
                throw new Exception('Module belum di-approve oleh Tracer');
            }

            // Get all photos for this module that are pending CGP approval
            $photos = PhotoApproval::where('module_name', $module)
                ->where('reff_id_pelanggan', $reffId)
                ->where('photo_status', 'cgp_pending')
                ->get();

            if ($photos->count() === 0) {
                throw new Exception('Tidak ada photo yang perlu di-approve');
            }

            $approved = [];
            foreach ($photos as $photo) {
                $result = $this->photoApprovalService->approveByCgp(
                    $photo->id,
                    Auth::id(),
                    $notes
                );
                $approved[] = $photo->id;
            }

            // Update module status
            $moduleData->update([
                'cgp_approved_at' => now(),
                'cgp_approved_by' => Auth::id(),
                'cgp_notes' => $notes
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Module {$module} berhasil di-approve",
                'data' => [
                    'approved_photos' => $approved,
                    'total_approved' => count($approved)
                ]
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

        // SK photos (jika sudah di-approve tracer)
        if ($cgpStatus['sk_ready'] || $cgpStatus['sk_completed']) {
            $photos['sk'] = PhotoApproval::where('module_name', 'sk')
                ->where('reff_id_pelanggan', $customer->reff_id_pelanggan)
                ->whereNotNull('tracer_approved_at')
                ->orderBy('photo_field_name')
                ->get();
        }

        // SR photos (jika sudah di-approve tracer)
        if ($cgpStatus['sr_ready'] || $cgpStatus['sr_completed']) {
            $photos['sr'] = PhotoApproval::where('module_name', 'sr')
                ->where('reff_id_pelanggan', $customer->reff_id_pelanggan)
                ->whereNotNull('tracer_approved_at')
                ->orderBy('photo_field_name')
                ->get();
        }

        // Gas In photos (jika sudah di-approve tracer)
        if ($cgpStatus['gas_in_ready'] || $cgpStatus['gas_in_completed']) {
            $photos['gas_in'] = PhotoApproval::where('module_name', 'gas_in')
                ->where('reff_id_pelanggan', $customer->reff_id_pelanggan)
                ->whereNotNull('tracer_approved_at')
                ->orderBy('photo_field_name')
                ->get();
        }

        return $photos;
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
        // This will be handled by existing PhotoApprovalService logic
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
}
