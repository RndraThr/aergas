<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SkData;
use App\Models\CalonPelanggan;
use App\Services\PhotoApprovalService;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SkDataController extends Controller
{
    use AuthorizesRequests;
    private PhotoApprovalService $photoApprovalService;
    private FileUploadService $fileUploadService;

    public function __construct(
        PhotoApprovalService $photoApprovalService,
        FileUploadService $fileUploadService
    ) {
        $this->photoApprovalService = $photoApprovalService;
        $this->fileUploadService = $fileUploadService;
        $this->authorizeResource(SkData::class, 'sk_data');
    }

    /**
     * Menampilkan daftar data SK dengan filter dan paginasi.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = SkData::with(['pelanggan', 'user', 'photoApprovals', 'tracerApprover', 'cgpApprover']);

            $user = $request->user();
            if ($user->role === 'sk') {
                $query->where('user_id', $user->id);
            }

            $this->applyFilters($query, $request);

            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $perPage = min($request->get('per_page', 15), 50);
            $skData = $query->paginate($perPage);

            $skData->getCollection()->transform(function ($item) {
                $item->photo_statuses = $item->getAllPhotosStatus();
                $item->can_submit = $item->canSubmit();
                $item->total_material_cost = $item->getTotalMaterialCost();
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $skData,
                'stats' => $this->getSkStats()
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching SK data', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An error occurred while fetching SK data'], 500);
        }
    }

    /**
     * Menampilkan detail spesifik dari sebuah data SK.
     */
    public function show(SkData $sk_data): JsonResponse
    {
        $sk_data->load(['pelanggan', 'user', 'photoApprovals.tracerUser', 'tracerApprover', 'cgpApprover']);
        $sk_data->photo_statuses = $sk_data->getAllPhotosStatus();
        $sk_data->can_submit = $sk_data->canSubmit();
        $sk_data->total_material_cost = $sk_data->getTotalMaterialCost();

        return response()->json(['success' => true, 'data' => $sk_data]);
    }

    /**
     * Menyimpan data SK baru atau memperbarui yang sudah ada.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = $this->validateStoreRequest($request);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $customer = CalonPelanggan::findOrFail($request->reff_id_pelanggan);
        if (!$customer->canProceedToModule('sk')) {
            return response()->json(['success' => false, 'message' => 'Customer validation must be completed first.'], 422);
        }

        DB::beginTransaction();
        try {
            $payload = $request->safe()->except(['user_id']);

            if ($request->user()->isAdmin() || $request->user()->isTracer()) {
                $payload['user_id'] = $request->user_id;
            } else {
                $payload['user_id'] = $request->user()->id;
            }

            $payload['module_status'] = 'draft';

            $skData = SkData::updateOrCreate(['reff_id_pelanggan' => $request->reff_id_pelanggan], $payload);

            if ($customer->progress_status === 'validasi') {
                $customer->update(['progress_status' => 'sk', 'status' => 'in_progress']);
            }

            DB::commit();
            Log::info('SK data saved', ['reff_id' => $skData->reff_id_pelanggan, 'by_user' => $request->user()->username]);
            $skData->load(['pelanggan', 'user', 'photoApprovals']);
            $skData->photo_statuses = $skData->getAllPhotosStatus();
            $skData->can_submit = $skData->canSubmit();

            return response()->json(['success' => true, 'message' => 'SK data saved successfully.', 'data' => $skData], $skData->wasRecentlyCreated ? 201 : 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error saving SK data', ['error' => $e->getMessage(), 'reff_id' => $request->reff_id_pelanggan]);
            return response()->json(['success' => false, 'message' => 'An error occurred while saving data.'], 500);
        }
    }

    /**
     * Mengunggah foto untuk data SK.
     */
    public function uploadPhoto(Request $request, SkData $sk_data): JsonResponse
    {
        $this->authorize('update', $sk_data);

        $validator = Validator::make($request->all(), [
            'photo_field' => 'required|in:foto_berita_acara_url,foto_pneumatic_sk_url,foto_valve_krunchis_url,foto_isometrik_sk_url',
            'photo' => 'required|file|mimes:jpeg,png,jpg,webp|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        try {
            $photoField = $request->photo_field;
            $uploadResult = $this->fileUploadService->uploadPhoto($request->file('photo'), $sk_data->reff_id_pelanggan, 'sk', $photoField, $request->user()->id);
            $sk_data->update([$photoField => $uploadResult['url']]);
            $this->photoApprovalService->processAIValidation($sk_data->reff_id_pelanggan, 'sk', $photoField, $uploadResult['url'], $request->user()->id);

            return response()->json(['success' => true, 'message' => 'Photo uploaded and sent for AI validation.', 'data' => ['photo_url' => $uploadResult['url']]]);
        } catch (Exception $e) {
            Log::error('Photo upload failed', ['error' => $e->getMessage(), 'reff_id' => $sk_data->reff_id_pelanggan]);
            return response()->json(['success' => false, 'message' => 'Failed to upload photo.'], 500);
        }
    }

    /**
     * Menyerahkan data SK untuk proses review.
     */
    public function submit(Request $request, SkData $sk_data): JsonResponse
    {
        $this->authorize('update', $sk_data);

        if (!$sk_data->canSubmit()) {
            return response()->json(['success' => false, 'message' => 'Cannot submit: All required photos must be uploaded and pass AI validation.'], 422);
        }

        $sk_data->update(['module_status' => 'tracer_review', 'overall_photo_status' => 'tracer_review']);
        Log::info('SK data submitted for review', ['reff_id' => $sk_data->reff_id_pelanggan, 'user' => $request->user()->username]);

        return response()->json(['success' => true, 'message' => 'SK data submitted for Tracer review.', 'data' => $sk_data]);
    }

    /**
     * Menghapus foto dari data SK.
     */
    public function deletePhoto(Request $request, SkData $sk_data, string $photoField): JsonResponse
    {
        $this->authorize('update', $sk_data);

        if (!in_array($photoField, $sk_data->getRequiredPhotos())) {
            return response()->json(['success' => false, 'message' => 'Invalid photo field.'], 422);
        }

        DB::beginTransaction();
        try {
            $this->fileUploadService->deleteExistingPhoto($sk_data->reff_id_pelanggan, 'sk', $photoField);
            $sk_data->update([$photoField => null]);
            $sk_data->photoApprovals()->where('photo_field_name', $photoField)->delete();
            DB::commit();

            Log::info('Photo deleted', ['reff_id' => $sk_data->reff_id_pelanggan, 'field' => $photoField]);
            return response()->json(['success' => true, 'message' => 'Photo deleted successfully.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Photo deletion failed', ['error' => $e->getMessage(), 'reff_id' => $sk_data->reff_id_pelanggan]);
            return response()->json(['success' => false, 'message' => 'Failed to delete photo.'], 500);
        }
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('module_status')) {
            $query->where('module_status', $request->module_status);
        }
        if ($request->filled('reff_id_pelanggan')) {
            $query->where('reff_id_pelanggan', 'LIKE', '%' . $request->reff_id_pelanggan . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('tanggal_instalasi', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('tanggal_instalasi', '<=', $request->date_to);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
    }

    private function getSkStats(): array
    {
        return SkData::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN module_status = "draft" THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN module_status = "tracer_review" THEN 1 ELSE 0 END) as tracer_review,
            SUM(CASE WHEN module_status = "cgp_review" THEN 1 ELSE 0 END) as cgp_review,
            SUM(CASE WHEN module_status = "completed" THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN module_status = "rejected" THEN 1 ELSE 0 END) as rejected
        ')->first()->toArray();
    }

    private function validateStoreRequest(Request $request)
    {
        $rules = [
            'reff_id_pelanggan' => 'required|string|exists:calon_pelanggan,reff_id_pelanggan',
            'tanggal_instalasi' => 'required|date|before_or_equal:today',
            'catatan_tambahan' => 'nullable|string|max:1000',
            'pipa_hot_drip_meter' => 'nullable|numeric|min:0',
            'long_elbow_34_pcs' => 'nullable|integer|min:0',
            'elbow_34_to_12_pcs' => 'nullable|integer|min:0',
            'elbow_12_pcs' => 'nullable|integer|min:0',
            'ball_valve_12_pcs' => 'nullable|integer|min:0',
            'double_nipple_12_pcs' => 'nullable|integer|min:0',
            'sock_draft_galvanis_12_pcs' => 'nullable|integer|min:0',
            'klem_pipa_12_pcs' => 'nullable|integer|min:0',
            'seal_tape_roll' => 'nullable|integer|min:0',
        ];

        if ($request->user()->isAdmin() || $request->user()->isTracer()) {
            $rules['user_id'] = 'required|integer|exists:users,id';
        }

        return Validator::make($request->all(), $rules);
    }
}
