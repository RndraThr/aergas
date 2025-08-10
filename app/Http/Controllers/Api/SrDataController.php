<?php

/**
 * =============================================================================
 * CONTROLLER: SrDataController.php
 * Location: app/Http/Controllers/Api/SrDataController.php
 * =============================================================================
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SrData;
use App\Models\CalonPelanggan;
use App\Services\PhotoApprovalService;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Exception;

class SrDataController extends Controller
{
    private PhotoApprovalService $photoApprovalService;
    private FileUploadService $fileUploadService;

    public function __construct(
        PhotoApprovalService $photoApprovalService,
        FileUploadService $fileUploadService
    ) {
        $this->photoApprovalService = $photoApprovalService;
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display a listing of SR data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = SrData::with([
                'pelanggan',
                'photoApprovals',
                'tracerApprover',
                'cgpApprover'
            ]);

            // Apply role-based filters
            $user = $request->user();
            if ($user->role === 'sr') {
                // SR users can only see their own work or work in their area
                // Since SR doesn't have nama_petugas field, we filter by recent records
                $query->whereHas('pelanggan', function($q) {
                    $q->where('progress_status', 'sr');
                });
            }

            // Apply additional filters
            $this->applyFilters($query, $request);

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $allowedSortFields = ['created_at', 'updated_at', 'module_status', 'jenis_tapping', 'panjang_pipa_pe'];

            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortDirection);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $perPage = min($request->get('per_page', 15), 50);
            $srData = $query->paginate($perPage);

            // Add computed fields
            $srData->getCollection()->transform(function ($item) {
                $item->photo_statuses = $item->getAllPhotosStatus();
                $item->can_submit = $item->canSubmit();
                $item->dependency_status = $this->checkDependencyStatus($item);
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $srData,
                'stats' => $this->getSrStats()
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching SR data', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching SR data'
            ], 500);
        }
    }

    /**
     * Display the specified SR data
     *
     * @param string $reffId
     * @return JsonResponse
     */
    public function show(string $reffId): JsonResponse
    {
        try {
            $srData = SrData::with([
                'pelanggan',
                'photoApprovals.tracerUser',
                'photoApprovals.cgpUser',
                'tracerApprover',
                'cgpApprover'
            ])->where('reff_id_pelanggan', $reffId)->firstOrFail();

            // Add computed fields
            $srData->photo_statuses = $srData->getAllPhotosStatus();
            $srData->can_submit = $srData->canSubmit();
            $srData->required_photos = $srData->getRequiredPhotos();
            $srData->dependency_status = $this->checkDependencyStatus($srData);

            return response()->json([
                'success' => true,
                'data' => $srData
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching SR data details', [
                'reff_id' => $reffId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SR data not found'
            ], 404);
        }
    }

    /**
     * Store or update SR data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validate reference ID and dependencies first
        $customer = CalonPelanggan::find($request->reff_id_pelanggan);
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found with the provided reference ID'
            ], 404);
        }

        if (!$customer->canProceedToModule('sr')) {
            return response()->json([
                'success' => false,
                'message' => 'SK module must be completed before proceeding with SR module',
                'dependency_status' => [
                    'current_progress' => $customer->progress_status,
                    'sk_status' => $customer->skData?->module_status ?? 'not_started',
                    'required' => 'SK module must be completed'
                ]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'reff_id_pelanggan' => 'required|exists:calon_pelanggan,reff_id_pelanggan',
            'jenis_tapping' => 'nullable|in:63x20,90x20',
            'panjang_pipa_pe' => 'nullable|numeric|min:0|max:1000',
            'panjang_casing_crossing_sr' => 'nullable|numeric|min:0|max:100',
        ], [
            'jenis_tapping.in' => 'Jenis tapping harus 63x20 atau 90x20',
            'panjang_pipa_pe.numeric' => 'Panjang pipa PE harus berupa angka',
            'panjang_pipa_pe.max' => 'Panjang pipa PE maksimal 1000 meter',
            'panjang_casing_crossing_sr.numeric' => 'Panjang casing crossing harus berupa angka',
            'panjang_casing_crossing_sr.max' => 'Panjang casing crossing maksimal 100 meter'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $srData = SrData::updateOrCreate(
                ['reff_id_pelanggan' => $request->reff_id_pelanggan],
                $request->only([
                    'jenis_tapping',
                    'panjang_pipa_pe',
                    'panjang_casing_crossing_sr'
                ]) + ['module_status' => 'draft']
            );

            // Update customer progress if this is first time
            if ($customer->progress_status === 'sk' && $customer->skData?->module_status === 'completed') {
                $customer->update([
                    'progress_status' => 'sr',
                    'status' => 'in_progress'
                ]);
            }

            DB::commit();

            Log::info('SR data saved', [
                'reff_id' => $request->reff_id_pelanggan,
                'user' => $request->user()->full_name,
                'action' => $srData->wasRecentlyCreated ? 'created' : 'updated'
            ]);

            $srData->load(['pelanggan', 'photoApprovals']);
            $srData->photo_statuses = $srData->getAllPhotosStatus();
            $srData->can_submit = $srData->canSubmit();

            return response()->json([
                'success' => true,
                'message' => 'SR data saved successfully',
                'data' => $srData
            ], $srData->wasRecentlyCreated ? 201 : 200);

        } catch (Exception $e) {
            DB::rollback();

            Log::error('Error saving SR data', [
                'reff_id' => $request->reff_id_pelanggan,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving SR data'
            ], 500);
        }
    }

    /**
     * Upload photo for SR data
     *
     * @param Request $request
     * @param string $reffId
     * @return JsonResponse
     */
    public function uploadPhoto(Request $request, string $reffId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'photo_field' => 'required|in:foto_pneumatic_start_sr_url,foto_pneumatic_finish_sr_url,foto_kedalaman_url,foto_isometrik_sr_url',
            'photo' => 'required|file|mimes:jpeg,png,jpg,gif,webp,pdf|max:10240' // 10MB max
        ], [
            'photo_field.required' => 'Field foto harus dipilih',
            'photo_field.in' => 'Field foto tidak valid',
            'photo.required' => 'File foto harus diupload',
            'photo.mimes' => 'Format file harus: jpeg, png, jpg, gif, webp, atau pdf',
            'photo.max' => 'Ukuran file maksimal 10MB'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $srData = SrData::where('reff_id_pelanggan', $reffId)->firstOrFail();
            $photoField = $request->photo_field;

            Log::info('Starting SR photo upload', [
                'reff_id' => $reffId,
                'photo_field' => $photoField,
                'file_size' => $request->file('photo')->getSize(),
                'uploaded_by' => $request->user()->id
            ]);

            // Upload file with Google Drive integration
            $uploadResult = $this->fileUploadService->uploadPhoto(
                $request->file('photo'),
                $reffId,
                'sr',
                $photoField,
                $request->user()->id
            );

            // Update SR data with photo URL
            $srData->update([$photoField => $uploadResult['url']]);

            DB::commit();

            // Start AI validation process (asynchronous)
            $photoApproval = $this->photoApprovalService->processAIValidation(
                $reffId,
                'sr',
                $photoField,
                $uploadResult['url'],
                $request->user()->id
            );

            Log::info('SR photo upload completed', [
                'reff_id' => $reffId,
                'photo_field' => $photoField,
                'photo_approval_id' => $photoApproval->id,
                'google_drive_id' => $uploadResult['google_drive']['google_drive_id'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Photo uploaded successfully and AI validation started',
                'data' => [
                    'photo_url' => $uploadResult['url'],
                    'photo_approval' => $photoApproval->load(['tracerUser', 'cgpUser']),
                    'file_info' => $uploadResult['file_info'],
                    'google_drive' => $uploadResult['google_drive'] ?? null,
                    'sr_data' => [
                        'can_submit' => $srData->canSubmit(),
                        'photo_statuses' => $srData->getAllPhotosStatus()
                    ]
                ]
            ]);

        } catch (Exception $e) {
            DB::rollback();

            Log::error('SR photo upload failed', [
                'reff_id' => $reffId,
                'photo_field' => $request->photo_field,
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload photo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit SR data for review
     *
     * @param Request $request
     * @param string $reffId
     * @return JsonResponse
     */
    public function submit(Request $request, string $reffId): JsonResponse
    {
        DB::beginTransaction();

        try {
            $srData = SrData::where('reff_id_pelanggan', $reffId)->firstOrFail();

            // Check dependency
            $customer = $srData->pelanggan;
            if (!$customer->canProceedToModule('sr')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot submit SR data: SK module must be completed first',
                    'dependency_status' => [
                        'sk_status' => $customer->skData?->module_status ?? 'not_started',
                        'required' => 'SK module completed'
                    ]
                ], 422);
            }

            if (!$srData->canSubmit()) {
                $photoStatuses = $srData->getAllPhotosStatus();
                $missingPhotos = [];
                $failedPhotos = [];

                foreach ($photoStatuses as $field => $status) {
                    if (!$status['url']) {
                        $missingPhotos[] = $field;
                    } elseif ($status['status'] !== 'ai_approved') {
                        $failedPhotos[] = [
                            'field' => $field,
                            'status' => $status['status'],
                            'reason' => $status['approval']->rejection_reason ?? 'AI validation failed'
                        ];
                    }
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Cannot submit SR data',
                    'errors' => [
                        'missing_photos' => $missingPhotos,
                        'failed_photos' => $failedPhotos,
                        'requirement' => 'All required photos must be uploaded and pass AI validation'
                    ]
                ], 422);
            }

            // Update module status
            $srData->update([
                'module_status' => 'tracer_review',
                'overall_photo_status' => 'tracer_review'
            ]);

            // Update customer progress
            if ($customer->progress_status === 'sk') {
                $customer->update(['progress_status' => 'sr']);
            }

            DB::commit();

            Log::info('SR data submitted for review', [
                'reff_id' => $reffId,
                'submitted_by' => $request->user()->full_name,
                'submission_time' => now()
            ]);

            // Load fresh data with relationships
            $srData->load(['pelanggan', 'photoApprovals', 'tracerApprover']);

            return response()->json([
                'success' => true,
                'message' => 'SR data submitted successfully for Tracer review',
                'data' => [
                    'sr_data' => $srData,
                    'next_step' => 'Waiting for Tracer approval',
                    'estimated_review_time' => '24 hours'
                ]
            ]);

        } catch (Exception $e) {
            DB::rollback();

            Log::error('SR submission failed', [
                'reff_id' => $reffId,
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting SR data'
            ], 500);
        }
    }

    /**
     * Delete photo from SR data
     *
     * @param Request $request
     * @param string $reffId
     * @param string $photoField
     * @return JsonResponse
     */
    public function deletePhoto(Request $request, string $reffId, string $photoField): JsonResponse
    {
        $allowedFields = [
            'foto_pneumatic_start_sr_url',
            'foto_pneumatic_finish_sr_url',
            'foto_kedalaman_url',
            'foto_isometrik_sr_url'
        ];

        if (!in_array($photoField, $allowedFields)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid photo field'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $srData = SrData::where('reff_id_pelanggan', $reffId)->firstOrFail();

            // Check if SR is still in draft mode
            if (!in_array($srData->module_status, ['draft', 'ai_validation'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete photo after submission for review'
                ], 422);
            }

            // Delete file and photo approval
            $this->fileUploadService->deleteExistingPhoto($reffId, 'sr', $photoField);

            // Update SR data
            $srData->update([$photoField => null]);

            // Delete photo approval record
            $srData->photoApprovals()->where('photo_field_name', $photoField)->delete();

            DB::commit();

            Log::info('Photo deleted from SR data', [
                'reff_id' => $reffId,
                'photo_field' => $photoField,
                'deleted_by' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Photo deleted successfully',
                'data' => [
                    'sr_data' => [
                        'can_submit' => $srData->canSubmit(),
                        'photo_statuses' => $srData->getAllPhotosStatus()
                    ]
                ]
            ]);

        } catch (Exception $e) {
            DB::rollback();

            Log::error('SR photo deletion failed', [
                'reff_id' => $reffId,
                'photo_field' => $photoField,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting photo'
            ], 500);
        }
    }

    /**
     * Get SR data progress for customer
     *
     * @param string $reffId
     * @return JsonResponse
     */
    public function getProgress(string $reffId): JsonResponse
    {
        try {
            $customer = CalonPelanggan::with(['skData', 'srData.photoApprovals'])
                                    ->findOrFail($reffId);

            $progress = [
                'customer' => [
                    'reff_id' => $customer->reff_id_pelanggan,
                    'nama_pelanggan' => $customer->nama_pelanggan,
                    'progress_status' => $customer->progress_status,
                    'progress_percentage' => $customer->getProgressPercentage()
                ],
                'dependencies' => [
                    'sk_completed' => $customer->skData?->module_status === 'completed',
                    'can_proceed_sr' => $customer->canProceedToModule('sr')
                ],
                'sr_status' => [
                    'exists' => $customer->srData !== null,
                    'module_status' => $customer->srData?->module_status ?? 'not_started',
                    'can_submit' => $customer->srData?->canSubmit() ?? false,
                    'photo_count' => $customer->srData?->photoApprovals?->count() ?? 0,
                    'approved_photos' => $customer->srData?->photoApprovals?->where('photo_status', 'cgp_approved')->count() ?? 0
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $progress
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching SR progress', [
                'reff_id' => $reffId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }
    }

    /**
     * Get SR statistics
     *
     * @return array
     */
    private function getSrStats(): array
    {
        return [
            'total_sr' => SrData::count(),
            'draft' => SrData::where('module_status', 'draft')->count(),
            'ai_validation' => SrData::where('module_status', 'ai_validation')->count(),
            'tracer_review' => SrData::where('module_status', 'tracer_review')->count(),
            'cgp_review' => SrData::where('module_status', 'cgp_review')->count(),
            'completed' => SrData::where('module_status', 'completed')->count(),
            'rejected' => SrData::where('module_status', 'rejected')->count(),
            'avg_pipa_length' => SrData::whereNotNull('panjang_pipa_pe')->avg('panjang_pipa_pe'),
            'tapping_distribution' => SrData::selectRaw('jenis_tapping, COUNT(*) as count')
                                           ->whereNotNull('jenis_tapping')
                                           ->groupBy('jenis_tapping')
                                           ->get()
                                           ->keyBy('jenis_tapping')
        ];
    }

    /**
     * Check dependency status
     *
     * @param SrData $srData
     * @return array
     */
    private function checkDependencyStatus(SrData $srData): array
    {
        $customer = $srData->pelanggan;
        $skData = $customer->skData;

        return [
            'sk_exists' => $skData !== null,
            'sk_status' => $skData?->module_status ?? 'not_started',
            'sk_completed' => $skData?->module_status === 'completed',
            'can_proceed' => $customer->canProceedToModule('sr'),
            'dependency_message' => $skData?->module_status === 'completed'
                ? 'Dependencies satisfied'
                : 'SK module must be completed first'
        ];
    }

    /**
     * Apply filters to SR query
     *
     * @param $query
     * @param Request $request
     * @return void
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->has('module_status') && $request->module_status !== '') {
            $query->where('module_status', $request->module_status);
        }

        if ($request->has('reff_id_pelanggan') && $request->reff_id_pelanggan !== '') {
            $query->where('reff_id_pelanggan', 'LIKE', '%' . $request->reff_id_pelanggan . '%');
        }

        if ($request->has('jenis_tapping') && $request->jenis_tapping !== '') {
            $query->where('jenis_tapping', $request->jenis_tapping);
        }

        if ($request->has('date_from') && $request->date_from !== '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to !== '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('pipa_length_min') && $request->pipa_length_min !== '') {
            $query->where('panjang_pipa_pe', '>=', $request->pipa_length_min);
        }

        if ($request->has('pipa_length_max') && $request->pipa_length_max !== '') {
            $query->where('panjang_pipa_pe', '<=', $request->pipa_length_max);
        }
    }
}
