<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalonPelanggan;
use App\Services\NotificationService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class CalonPelangganController extends Controller
{
    private NotificationService $notificationService;
    private ReportService $reportService;

    public function __construct(
        NotificationService $notificationService,
        ReportService $reportService
    ) {
        $this->notificationService = $notificationService;
        $this->reportService = $reportService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = CalonPelanggan::with([
                'skData', 'srData', 'mgrtData', 'gasInData',
                'jalurPipaData', 'penyambunganPipaData', 'baBatalData'
            ]);

            $this->applyFilters($query, $request);

            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $allowedSortFields = [
                'created_at', 'updated_at', 'nama_pelanggan',
                'reff_id_pelanggan', 'status', 'progress_status', 'tanggal_registrasi'
            ];

            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortDirection);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $perPage = min($request->get('per_page', 15), 100);
            $customers = $query->paginate($perPage);

            $customers->getCollection()->each(function ($customer) {
                $customer->setAttribute('progress_percentage', $customer->getProgressPercentage());
                $customer->setAttribute('next_available_module', $customer->getNextAvailableModule());
            });

            return response()->json([
                'success' => true,
                'data' => $customers,
                'stats' => $this->reportService->getCustomerSummaryStats()
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching customers', ['error' => $e->getMessage(), 'user_id' => $request->user()?->id]);
            return response()->json(['success' => false, 'message' => 'An error occurred'], 500);
        }
    }

    public function show(string $reffId): JsonResponse
    {
        try {
            $customer = CalonPelanggan::with([
                'skData.photoApprovals', 'srData.photoApprovals',
                'mgrtData.photoApprovals', 'gasInData.photoApprovals',
                'jalurPipaData.photoApprovals', 'penyambunganPipaData.photoApprovals',
                'baBatalData', 'photoApprovals.tracerUser', 'photoApprovals.cgpUser',
                'auditLogs.user'
            ])->findOrFail($reffId);

            $customer->setAttribute('progress_percentage', $customer->getProgressPercentage());
            $customer->setAttribute('next_available_module', $customer->getNextAvailableModule());
            $customer->module_completion_status = $this->getModuleCompletionStatus($customer);

            return response()->json(['success' => true, 'data' => $customer]);
        } catch (Exception $e) {
            Log::error('Error fetching customer details', ['reff_id' => $reffId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reff_id_pelanggan' => 'required|string|unique:calon_pelanggan,reff_id_pelanggan|max:50|regex:/^[A-Z0-9]+$/',
            'nama_pelanggan' => 'required|string|max:255',
            'alamat' => 'required|string|max:1000',
            'no_telepon' => 'required|string|max:20|regex:/^[0-9+\-\s]+$/',
            'wilayah_area' => 'nullable|string|max:100',
            'jenis_pelanggan' => 'nullable|in:residensial,komersial,industri',
            'keterangan' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        try {
            $customerData = $request->only(['reff_id_pelanggan', 'nama_pelanggan', 'alamat', 'no_telepon', 'wilayah_area', 'keterangan']);
            $customerData['jenis_pelanggan'] = $request->jenis_pelanggan ?? 'residensial';
            $customerData['status'] = 'pending';
            $customerData['progress_status'] = 'validasi';
            $customerData['tanggal_registrasi'] = now();

            $customer = CalonPelanggan::create($customerData);

            Log::info('New customer registered', ['reff_id' => $customer->reff_id_pelanggan, 'registered_by' => $request->user()->id]);
            $this->notificationService->notifyNewCustomerRegistration($customer);

            return response()->json(['success' => true, 'message' => 'Customer registered successfully', 'data' => $customer], 201);
        } catch (Exception $e) {
            Log::error('Error creating customer', ['error' => $e->getMessage(), 'data' => $request->all()]);
            return response()->json(['success' => false, 'message' => 'An error occurred'], 500);
        }
    }

    public function update(Request $request, string $reffId): JsonResponse
    {
        $customer = CalonPelanggan::findOrFail($reffId);

        $validator = Validator::make($request->all(), [
            'nama_pelanggan' => 'sometimes|string|max:255',
            'alamat' => 'sometimes|string|max:1000',
            'no_telepon' => 'sometimes|string|max:20|regex:/^[0-9+\-\s]+$/',
            'status' => 'sometimes|in:validated,in_progress,lanjut,batal,pending',
            'progress_status' => 'sometimes|in:validasi,sk,sr,mgrt,gas_in,jalur_pipa,penyambungan,done,batal',
            'wilayah_area' => 'nullable|string|max:100',
            'jenis_pelanggan' => 'nullable|in:residensial,komersial,industri',
            'keterangan' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        try {
            $customer->update($request->all());
            Log::info('Customer updated', ['reff_id' => $reffId, 'updated_by' => $request->user()->id]);
            return response()->json(['success' => true, 'message' => 'Customer updated successfully', 'data' => $customer]);
        } catch (Exception $e) {
            Log::error('Error updating customer', ['reff_id' => $reffId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An error occurred'], 500);
        }
    }

    public function validateReffId(string $reffId): JsonResponse
    {
        try {
            $customer = CalonPelanggan::find($reffId);

            if (!$customer) {
                return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
            }

            $customerData = $customer->only(['reff_id_pelanggan', 'nama_pelanggan', 'alamat', 'no_telepon', 'status', 'progress_status', 'wilayah_area', 'jenis_pelanggan']);
            $customerData['progress_percentage'] = $customer->getProgressPercentage();
            $customerData['can_proceed'] = in_array($customer->status, ['validated', 'in_progress']);
            $customerData['next_available_module'] = $customer->getNextAvailableModule();
            $customerData['last_activity'] = $customer->updated_at;

            return response()->json(['success' => true, 'message' => 'Customer found', 'data' => $customerData]);
        } catch (Exception $e) {
            Log::error('Error validating reference ID', ['reff_id' => $reffId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An error occurred'], 500);
        }
    }

    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->reportService->getCustomerSummaryStats();
            $stats['completion_rate_this_month'] = $this->reportService->getMonthlyCompletionRate();
            $stats['average_completion_time_days'] = $this->reportService->getAverageCompletionTimeInDays();
            return response()->json(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            Log::error('Error fetching customer stats', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An error occurred'], 500);
        }
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_pelanggan', 'LIKE', '%' . $search . '%')
                  ->orWhere('reff_id_pelanggan', 'LIKE', '%' . $search . '%')
                  ->orWhere('alamat', 'LIKE', '%' . $search . '%')
                  ->orWhere('no_telepon', 'LIKE', '%' . $search . '%');
            });
        }

        $filters = ['status', 'progress_status', 'wilayah_area', 'jenis_pelanggan'];
        foreach ($filters as $filter) {
            if ($request->has($filter) && $request->$filter !== '') {
                $query->where($filter, $request->$filter);
            }
        }
    }

    private function getModuleCompletionStatus(CalonPelanggan $customer): array
    {
        $modules = ['sk', 'sr', 'mgrt', 'gas_in', 'jalur_pipa', 'penyambungan'];
        $status = [];
        foreach ($modules as $module) {
            $moduleRelationName = match ($module) {
                'jalur_pipa' => 'jalurPipaData',
                'penyambungan' => 'penyambunganPipaData',
                default => $module . 'Data',
            };
            $moduleData = $customer->$moduleRelationName;
            $status[$module] = [
                'status' => $moduleData?->module_status ?? 'not_started',
                'can_proceed' => $customer->canProceedToModule($module)
            ];
        }
        return $status;
    }
}
