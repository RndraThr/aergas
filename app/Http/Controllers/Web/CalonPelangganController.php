<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CalonPelanggan;
use App\Models\GasInData;
use App\Models\SkData;
use App\Services\NotificationService;
use App\Services\ReportService;
use App\Services\BeritaAcaraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ReffIdHelper;
use Illuminate\Support\Facades\Storage;

use Exception;

class CalonPelangganController extends Controller
{
    private NotificationService $notificationService;
    private ReportService $reportService;
    private \App\Services\ComprehensiveExportService $exportService;

    public function __construct(
        NotificationService $notificationService,
        ReportService $reportService,
        \App\Services\ComprehensiveExportService $exportService
    ) {
        $this->notificationService = $notificationService;
        $this->reportService = $reportService;
        $this->exportService = $exportService;
    }

    /**
     * Display a listing of customers
     * Returns Blade view for web requests, JSON for AJAX
     */
    public function index(Request $request)
    {
        try {
            $query = CalonPelanggan::with(['validatedBy:id,name', 'skData:id,reff_id_pelanggan,module_status', 'srData:id,reff_id_pelanggan,module_status', 'gasInData:id,reff_id_pelanggan,module_status']);

            // Filters
            if ($s = trim((string) $request->input('search', ''))) {
                $query->where(function ($q) use ($s) {
                    $q->where('nama_pelanggan', 'like', "%{$s}%")
                        ->orWhere('alamat', 'like', "%{$s}%")
                        ->orWhere('reff_id_pelanggan', 'like', "%{$s}%");
                });
            }
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
            if ($request->filled('progress_status')) {
                $query->where('progress_status', $request->input('progress_status'));
            }
            if ($request->filled('kelurahan')) {
                $query->where('kelurahan', 'like', '%' . $request->kelurahan . '%');
            }
            if ($request->filled('padukuhan')) {
                $query->where('padukuhan', 'like', '%' . $request->padukuhan . '%');
            }


            // Sorting (whitelist)
            $allowedSorts = ['created_at', 'updated_at', 'nama_pelanggan', 'progress_status', 'status', 'kelurahan', 'padukuhan'];
            $sortBy = in_array($request->input('sort_by'), $allowedSorts, true) ? $request->input('sort_by') : 'created_at';
            $sortDirection = strtolower($request->input('sort_direction')) === 'asc' ? 'asc' : 'desc';

            $query->orderBy($sortBy, $sortDirection);

            // Pagination
            $perPage = min(max((int) $request->input('per_page', 15), 5), 50);
            $customers = $query->paginate($perPage)->appends($request->only([
                'search',
                'status',
                'progress_status',
                'kelurahan',
                'padukuhan',
                'sort_by',
                'sort_direction',
                'per_page'
            ]));

            // Updated stats with validation metrics
            $stats = [
                'total_customers' => CalonPelanggan::count(),
                'pending_validation' => CalonPelanggan::where('status', 'pending')->count(),
                'validated_customers' => CalonPelanggan::where('status', 'lanjut')->count(),
                'in_progress_customers' => CalonPelanggan::whereIn('status', ['in_progress', 'lanjut'])
                    ->whereNotIn('progress_status', ['done', 'batal'])
                    ->count(),
                'completed_customers' => CalonPelanggan::where('progress_status', 'done')->count(),
                'cancelled_customers' => CalonPelanggan::where('progress_status', 'batal')
                    ->orWhere('status', 'batal')
                    ->count(),
            ];

            // JSON for AJAX (fetch ?ajax=1)
            if ($request->expectsJson() || $request->boolean('ajax')) {
                // Transform customers data to include validated_by_name
                $customersData = $customers->getCollection()->map(function ($customer) {
                    $customer->validated_by_name = $customer->validatedBy?->name;
                    return $customer;
                });

                $customers->setCollection($customersData);

                return response()->json([
                    'success' => true,
                    'data' => $customers, // paginator object
                    'stats' => $stats,
                    'filters' => $request->only(['search', 'status', 'progress_status', 'kelurahan', 'padukuhan']),
                    'currentSort' => ['field' => $sortBy, 'direction' => $sortDirection],
                ]);
            }

            // Blade view (SSR first load)
            return view('customers.index', [
                'customers' => $customers,
                'stats' => $stats,
                'currentSort' => ['field' => $sortBy, 'direction' => $sortDirection],
                'activeFilters' => $request->only(['search', 'status', 'progress_status', 'kelurahan', 'padukuhan']),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error fetching customers', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            if ($request->expectsJson() || $request->boolean('ajax')) {
                return response()->json(['success' => false, 'message' => 'Terjadi kesalahan'], 500);
            }

            return back()->with('error', 'Terjadi kesalahan saat memuat data pelanggan');
        }
    }


    /**
     * Show customer details
     */
    public function show(string $reffId)
    {
        try {
            $customer = CalonPelanggan::with([
                'skData.photoApprovals',
                'srData.photoApprovals',
                'gasInData.photoApprovals',
                'photoApprovals.tracerUser',
                'photoApprovals.cgpUser',
                'auditLogs.user'
            ])->findOrFail($reffId);

            $customer->setAttribute('progress_percentage', $customer->getProgressPercentage());
            $customer->setAttribute('next_available_module', $customer->getNextAvailableModule());
            $customer->module_completion_status = $this->getModuleCompletionStatus($customer);

            if (request()->expectsJson()) {
                return response()->json(['success' => true, 'data' => $customer]);
            }

            // was: return view('calon-pelanggan.show', compact('customer'));
            return view('customers.show', compact('customer'));

        } catch (Exception $e) {
            // …
        }
    }

    /**
     * Show form for creating new customer
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store new customer
     */
    public function store(Request $request)
    {
        // Normalize reff_id_pelanggan (uppercase + auto-pad to 8 digits if numeric)
        $request->merge([
            'reff_id_pelanggan' => ReffIdHelper::normalize($request->input('reff_id_pelanggan')),
        ]);

        $validator = Validator::make($request->all(), [
            'reff_id_pelanggan' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9]+$/',
                Rule::unique('calon_pelanggan', 'reff_id_pelanggan'),
            ],
            'nama_pelanggan' => 'required|string|max:255',
            'alamat' => 'required|string|max:1000',
            'no_telepon' => 'required|string|max:20|regex:/^[0-9+\-\s]+$/',
            'no_ktp' => 'nullable|string|max:20|regex:/^[0-9]+$/',
            'kelurahan' => 'nullable|string|max:120',
            'kota_kabupaten' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'padukuhan' => 'nullable|string|max:120',
            'jenis_pelanggan' => 'nullable|in:pengembangan,penetrasi,on_the_spot_penetrasi,on_the_spot_pengembangan',
            'keterangan' => 'nullable|string|max:500',
            'status' => 'sometimes|in:pending,lanjut,in_progress,batal',
            'progress_status' => 'sometimes|in:validasi,sk,sr,gas_in,done,batal',
            'email' => 'nullable|email',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'coordinate_source' => 'nullable|in:manual,gps,maps,survey,excel_import',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = $request->only([
                'reff_id_pelanggan',
                'nama_pelanggan',
                'alamat',
                'no_telepon',
                'kelurahan',
                'padukuhan',
                'keterangan',
                'jenis_pelanggan',
                'status',
                'progress_status',
                'email',
                'latitude',
                'longitude',
                'coordinate_source'
            ]);

            // default kalau tidak dikirim
            $data['jenis_pelanggan'] = $data['jenis_pelanggan'] ?? 'pengembangan';
            $data['status'] = $data['status'] ?? 'pending';
            $data['progress_status'] = $data['progress_status'] ?? 'validasi';
            $data['tanggal_registrasi'] = now();

            // Set coordinate_updated_at if coordinates are provided
            if (!empty($data['latitude']) && !empty($data['longitude'])) {
                $data['coordinate_updated_at'] = now();
            }

            DB::beginTransaction();

            $customer = CalonPelanggan::create($data);

            // Notifikasi jangan block alur
            try {
                $this->notificationService->notifyNewCustomerRegistration($customer);
            } catch (\Throwable $e) {
                Log::warning('Notif gagal dikirim', ['reff_id' => $customer->reff_id_pelanggan, 'err' => $e->getMessage()]);
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pelanggan berhasil didaftarkan',
                    'data' => $customer,
                ], 201);
            }

            return redirect()
                ->route('customers.show', $customer->reff_id_pelanggan)
                ->with('success', 'Pelanggan berhasil didaftarkan');

        } catch (QueryException $qe) {
            DB::rollBack();

            if ((int) ($qe->errorInfo[1] ?? 0) === 1062) {
                $msg = 'Reference ID sudah digunakan.';
                return $request->expectsJson()
                    ? response()->json(['success' => false, 'message' => $msg], 409)
                    : back()->withErrors(['reff_id_pelanggan' => $msg])->withInput();
            }

            Log::error('DB error creating customer', ['err' => $qe->getMessage()]);
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Terjadi kesalahan'], 500)
                : back()->with('error', 'Terjadi kesalahan saat mendaftarkan pelanggan')->withInput();

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error creating customer', ['err' => $e->getMessage(), 'data' => $request->all()]);

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Terjadi kesalahan'], 500)
                : back()->with('error', 'Terjadi kesalahan saat mendaftarkan pelanggan')->withInput();
        }
    }

    private function calculateProgressPercentage($customer): int
    {
        $steps = ['validasi', 'sk', 'sr', 'gas_in', 'done'];
        $currentIndex = array_search($customer->progress_status, $steps);

        if ($currentIndex === false)
            return 0;
        if ($customer->progress_status === 'done')
            return 100;

        return round(($currentIndex / (count($steps) - 1)) * 100);
    }

    /**
     * Show form for editing customer
     */
    public function edit(string $reffId)
    {
        try {
            $customer = CalonPelanggan::findOrFail($reffId);
            return view('customers.edit', compact('customer'));
        } catch (Exception $e) {
            return back()->with('error', 'Pelanggan tidak ditemukan');
        }
    }

    /**
     * Update customer
     */
    public function update(Request $request, string $reffId)
    {
        try {
            Log::info('Customer update request', [
                'reff_id' => $reffId,
                'user_id' => Auth::id(),
                'data' => $request->all()
            ]);

            $customer = CalonPelanggan::findOrFail($reffId);

            // siapa saja yg boleh ubah reff?
            $canEditReff = $request->user()
                && ($request->user()->isSuperAdmin() || $request->user()->isAdmin() || $request->user()->isTracer());

            // rules dasar
            $rules = [
                'nama_pelanggan' => 'sometimes|string|max:255',
                'alamat' => 'sometimes|string|max:1000',
                'no_telepon' => 'sometimes|string|max:20|regex:/^[0-9+\-\s]+$/',
                'status' => 'sometimes|in:pending,lanjut,in_progress,batal',
                'progress_status' => 'sometimes|in:validasi,sk,sr,gas_in,done,batal',
                'kelurahan' => 'nullable|string|max:120',
                'padukuhan' => 'nullable|string|max:120',
                'jenis_pelanggan' => 'nullable|in:pengembangan,penetrasi,on_the_spot_penetrasi,on_the_spot_pengembangan',
                'keterangan' => 'nullable|string|max:500',
                'email' => 'nullable|email',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'coordinate_source' => 'nullable|in:manual,gps,maps,survey,excel_import'
            ];

            // jika boleh ubah reff, tambahkan rule unik (excluded current key)
            if ($canEditReff) {
                $rules['reff_id_pelanggan'] =
                    'required|string|max:50|regex:/^[A-Z0-9]+$/|unique:calon_pelanggan,reff_id_pelanggan,' .
                    $reffId . ',reff_id_pelanggan';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $validator->errors()
                    ], 422);
                }
                return back()->withErrors($validator)->withInput();
            }

            $validated = $validator->validated();

            // siapkan data update biasa
            $data = $request->only([
                'nama_pelanggan',
                'alamat',
                'no_telepon',
                'status',
                'progress_status',
                'kelurahan',
                'padukuhan',
                'jenis_pelanggan',
                'keterangan',
                'email',
                'latitude',
                'longitude',
                'coordinate_source'
            ]);

            // Update coordinate_updated_at if coordinates are being updated
            if (
                ($request->filled('latitude') && $request->filled('longitude')) &&
                ($customer->latitude !== $request->latitude || $customer->longitude !== $request->longitude)
            ) {
                $data['coordinate_updated_at'] = now();
            }

            // kalau boleh dan ada reff baru -> uppercase & set
            if ($canEditReff && $request->filled('reff_id_pelanggan')) {
                $newReff = strtoupper($request->string('reff_id_pelanggan'));
                if ($newReff !== $customer->reff_id_pelanggan) {
                    $customer->reff_id_pelanggan = $newReff;
                }
            }

            // Check if status is changing from 'pending' to 'lanjut' or 'batal'
            $isChangingToValidated = $customer->status === 'pending' &&
                isset($data['status']) &&
                $data['status'] === 'lanjut';

            $isChangingToRejected = $customer->status === 'pending' &&
                isset($data['status']) &&
                $data['status'] === 'batal';

            // If validating customer, use proper validation method
            if ($isChangingToValidated) {
                Log::info('Customer validation: pending -> lanjut', [
                    'reff_id' => $customer->reff_id_pelanggan,
                    'user_id' => Auth::id(),
                    'notes' => $request->input('validation_notes')
                ]);

                // Use model's validateCustomer method for proper validation tracking
                $customer->validateCustomer(Auth::id(), $request->input('validation_notes'));

                // Remove status from data since it's already handled by validateCustomer
                unset($data['status']);
            }
            // If rejecting customer validation
            elseif ($isChangingToRejected) {
                Log::info('Customer validation: pending -> batal', [
                    'reff_id' => $customer->reff_id_pelanggan,
                    'user_id' => Auth::id(),
                    'notes' => $request->input('validation_notes')
                ]);

                // Use model's rejectValidation method
                $customer->rejectValidation(Auth::id(), $request->input('validation_notes'));

                // Remove status from data since it's already handled by rejectValidation
                unset($data['status']);
            }

            // Apply other changes
            if (!empty($data)) {
                $customer->fill($data)->save();
            }

            // NOTE: jika ada tabel lain yang refer ke reff_id_pelanggan dan tidak pakai
            // ON UPDATE CASCADE, Anda perlu update manual di tabel-tabel itu.

            $targetReff = $customer->reff_id_pelanggan; // bisa saja berubah

            Log::info('Customer update success', [
                'reff_id' => $targetReff,
                'user_id' => Auth::id(),
                'is_ajax' => $request->expectsJson() || $request->ajax()
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pelanggan berhasil diperbarui',
                    'data' => $customer
                ]);
            }

            return redirect()->route('customers.show', $targetReff)
                ->with('success', 'Pelanggan berhasil diperbarui');

        } catch (Exception $e) {
            Log::error('Error updating customer', ['reff_id' => $reffId, 'error' => $e->getMessage()]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat memperbarui pelanggan: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Terjadi kesalahan')->withInput();
        }
    }

    public function validateReff(string $reffId) // ← sengaja TANPA : JsonResponse
    {
        try {
            $id = strtoupper(trim($reffId));

            // 1) cari langsung di calon_pelanggan
            $cp = CalonPelanggan::where('reff_id_pelanggan', $id)->first();

            // 2) fallback kalau nol depan mungkin hilang saat disimpan numerik
            if (!$cp && ctype_digit($id)) {
                $cp = CalonPelanggan::whereRaw('CAST(reff_id_pelanggan AS UNSIGNED) = ?', [(int) $id])->first();
            }

            // 3) fallback: ada di SK → ambil relasi calon_pelanggan
            if (!$cp) {
                $sk = SkData::with('calonPelanggan')
                    ->where('reff_id_pelanggan', $id)
                    ->whereNull('deleted_at')
                    ->first();
                if (!$sk && ctype_digit($id)) {
                    $sk = SkData::with('calonPelanggan')
                        ->whereRaw('CAST(reff_id_pelanggan AS UNSIGNED) = ?', [(int) $id])
                        ->whereNull('deleted_at')
                        ->first();
                }
                if ($sk && $sk->calonPelanggan)
                    $cp = $sk->calonPelanggan;
            }

            if ($cp) {
                return response()->json([
                    'success' => true,
                    'valid' => false,
                    'exists' => true,
                    'message' => 'Pelanggan ditemukan',
                    'data' => [
                        'reff_id_pelanggan' => $cp->reff_id_pelanggan,
                        'nama_pelanggan' => $cp->nama_pelanggan ?? null,
                        'alamat' => $cp->alamat ?? null,
                        'no_telepon' => $cp->no_telepon ?? null,
                        'kelurahan' => $cp->kelurahan ?? null,
                        'padukuhan' => $cp->padukuhan ?? null,
                        'status' => $cp->status ?? null,
                        'progress_status' => $cp->progress_status ?? null,
                        'latitude' => $cp->latitude ?? null,
                        'longitude' => $cp->longitude ?? null,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'valid' => true,
                'exists' => false,
                'message' => 'Pelanggan tidak ditemukan',
            ], 404);

        } catch (\Throwable $e) {
            Log::error('validateReff error', ['reff_id' => $reffId, 'err' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan'], 500);
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
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan'], 500);
        }
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelanggan', 'LIKE', '%' . $search . '%')
                    ->orWhere('reff_id_pelanggan', 'LIKE', '%' . $search . '%')
                    ->orWhere('alamat', 'LIKE', '%' . $search . '%')
                    ->orWhere('no_telepon', 'LIKE', '%' . $search . '%');
            });
        }

        foreach (['status', 'progress_status', 'kelurahan', 'padukuhan', 'jenis_pelanggan'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
    }

    private function getModuleCompletionStatus(CalonPelanggan $customer): array
    {
        $modules = ['sk', 'sr', 'gas_in'];
        $status = [];

        foreach ($modules as $module) {
            $relationName = $module . 'Data';
            $moduleData = $customer->$relationName;

            // NOTE: fallback ke status/workflow_status bila module_status tidak ada
            $moduleStatus = $moduleData?->module_status
                ?? $moduleData?->status
                ?? $moduleData?->workflow_status
                ?? 'not_started';

            $status[$module] = [
                'status' => $moduleStatus,
                'can_proceed' => $customer->canProceedToModule($module)
            ];
        }
        return $status;
    }

    /**
     * Validate customer (approve)
     */
    public function validateCustomer(Request $request, string $reffId)
    {
        try {
            $customer = CalonPelanggan::findOrFail($reffId);

            if ($customer->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pelanggan sudah divalidasi atau dibatalkan'
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $success = $customer->validateCustomer(
                Auth::id(),
                $request->input('notes')
            );

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memvalidasi pelanggan'
                ], 422);
            }

            // Send notification
            $this->notificationService->create(
                'customer_validated',
                'Pelanggan Divalidasi',
                "Pelanggan {$customer->nama_pelanggan} (ID: {$customer->reff_id_pelanggan}) telah divalidasi.",
                ['customer_id' => $customer->reff_id_pelanggan]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pelanggan berhasil divalidasi',
                    'data' => $customer->fresh()
                ]);
            }

            return redirect()->route('customers.show', $customer->reff_id_pelanggan)
                ->with('success', 'Pelanggan berhasil divalidasi');

        } catch (Exception $e) {
            Log::error('Error validating customer', [
                'reff_id' => $reffId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memvalidasi pelanggan'
            ], 500);
        }
    }

    /**
     * Reject customer validation
     */
    public function rejectCustomer(Request $request, string $reffId)
    {
        try {
            $customer = CalonPelanggan::findOrFail($reffId);

            $validator = Validator::make($request->all(), [
                'notes' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $success = $customer->rejectValidation(
                Auth::id(),
                $request->input('notes')
            );

            // Send notification
            $this->notificationService->create(
                'customer_rejected',
                'Pelanggan Ditolak',
                "Pelanggan {$customer->nama_pelanggan} (ID: {$customer->reff_id_pelanggan}) ditolak validasinya.",
                ['customer_id' => $customer->reff_id_pelanggan]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pelanggan berhasil ditolak',
                    'data' => $customer->fresh()
                ]);
            }

            return redirect()->route('customers.show', $customer->reff_id_pelanggan)
                ->with('success', 'Pelanggan berhasil ditolak');

        } catch (Exception $e) {
            Log::error('Error rejecting customer', [
                'reff_id' => $reffId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menolak pelanggan'
            ], 500);
        }
    }

    /**
     * Show form Import Data Calon Pelanggan
     */
    public function importBulkDataForm()
    {
        // Get allowed columns for display
        $import = new \App\Imports\CalonPelangganBulkImport();
        $allowedColumns = $import->getAllowedColumns();

        return view('imports.calon-pelanggan-bulk', compact('allowedColumns'));
    }

    /**
     * Export Customer Data (Comprehensive Format)
     */
    public function export(Request $request)
    {
        try {
            // Re-use the existing logic to build the query from filters
            $query = CalonPelanggan::with([
                'validatedBy:id,name',
                'skData.photoApprovals',
                'srData.photoApprovals',
                'gasInData.photoApprovals',
                'photoApprovals.tracerUser',
                'photoApprovals.cgpUser',
            ]);

            // Apply Filters (same as index, extracted to helper method)
            $this->applyFilters($query, $request);

            // Get all results (no pagination)
            $customers = $query->latest('tanggal_registrasi')->get();

            if ($customers->isEmpty()) {
                return back()->with('error', 'Tidak ada data pelanggan yang sesuai filter untuk diexport.');
            }

            // Generate Spreadsheet using the shared service
            $spreadsheet = $this->exportService->generateSpreadsheet($customers);
            $filename = 'Export_Pelanggan_' . now()->format('Y-m-d_His') . '.xlsx';

            // Download Logic
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $temp_file = tempnam(sys_get_temp_dir(), 'excel');
            $writer->save($temp_file);

            return response()->download($temp_file, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Export customers failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            return back()->with('error', 'Gagal export data: ' . $e->getMessage());
        }
    }

    /**
     * Import Data Calon Pelanggan dari Excel (Bulk Update)
     */
    public function importBulkData(Request $request)
    {
        $mode = $request->input('mode');

        // Validation rules berbeda untuk preview vs commit
        $rules = [
            'mode' => 'required|in:preview,commit',
            'force_update' => 'nullable|boolean'
        ];

        // Untuk preview, file wajib diupload
        if ($mode === 'preview') {
            $rules['file'] = 'required|mimes:xlsx,xls,csv|max:5120';
        }
        // Untuk commit, file atau temp_file harus ada
        else if ($mode === 'commit') {
            $rules['file'] = 'nullable|mimes:xlsx,xls,csv|max:5120';
            $rules['temp_file'] = 'required_without:file|string';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $forceUpdate = $request->boolean('force_update', false);

        try {
            $file = $request->file('file');

            // PREVIEW MODE (Dry Run)
            if ($mode === 'preview') {
                // Create import instance with dry run mode and force_update flag
                $import = new \App\Imports\CalonPelangganBulkImport(true, $forceUpdate);

                // Import the file (without saving to database)
                \Maatwebsite\Excel\Facades\Excel::import($import, $file);

                // Get detailed statistics
                $summary = $import->getSummary();

                // Store file temporarily for commit
                $tempPath = $file->store('temp-imports');

                Log::info('Calon Pelanggan bulk import preview', [
                    'total_rows' => $summary['total_rows'],
                    'would_update' => $summary['updated'],
                    'would_skip' => $summary['skipped'],
                    'force_update' => $forceUpdate,
                    'user_id' => Auth::id()
                ]);

                return redirect()->back()->with('import_preview', [
                    'success' => true,
                    'mode' => 'preview',
                    'message' => "Preview: {$summary['updated']} data akan diupdate, {$summary['skipped']} data akan dilewati dari total {$summary['total_rows']} baris.",
                    'summary' => $summary,
                    'temp_file' => $tempPath,
                    'force_update' => $forceUpdate
                ]);
            }

            // COMMIT MODE (Actual Update)
            if ($mode === 'commit') {
                // Check if there's a temp file from preview
                $tempPath = $request->input('temp_file');

                if ($tempPath && Storage::exists($tempPath)) {
                    // Use the temp file - Storage::path() will give correct full path
                    $filePath = Storage::path($tempPath);
                } else {
                    // Use newly uploaded file
                    $filePath = $file->getRealPath();
                }

                // Create import instance (normal mode) with force_update flag
                $import = new \App\Imports\CalonPelangganBulkImport(false, $forceUpdate);

                // Import the file (save to database)
                \Maatwebsite\Excel\Facades\Excel::import($import, $filePath);

                // Get detailed statistics
                $summary = $import->getSummary();

                // Clean up temp file
                if ($tempPath && Storage::exists($tempPath)) {
                    Storage::delete($tempPath);
                }

                Log::info('Calon Pelanggan bulk import committed', [
                    'total_rows' => $summary['total_rows'],
                    'updated' => $summary['updated'],
                    'skipped' => $summary['skipped'],
                    'not_found_count' => $summary['not_found_count'],
                    'missing_reff_count' => $summary['missing_reff_count'],
                    'force_update' => $forceUpdate,
                    'user_id' => Auth::id()
                ]);

                return redirect()->back()->with('import_results', [
                    'success' => true,
                    'mode' => 'commit',
                    'message' => "Import berhasil! {$summary['updated']} data diupdate, {$summary['skipped']} data dilewati dari total {$summary['total_rows']} baris.",
                    'summary' => $summary
                ]);
            }

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];

            foreach ($failures as $failure) {
                $errorMessages[] = "Baris {$failure->row()}: " . implode(', ', $failure->errors());
            }

            return redirect()->back()->with('import_results', [
                'success' => false,
                'message' => 'Validasi gagal pada beberapa baris',
                'errors' => $errorMessages
            ]);

        } catch (Exception $e) {
            Log::error('Calon Pelanggan bulk import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()->with('import_results', [
                'success' => false,
                'message' => 'Terjadi kesalahan saat import: ' . $e->getMessage(),
                'errors' => []
            ]);
        }
    }

    /**
     * Preview Berita Acara Gas In for a customer
     * Works even if customer doesn't have Gas In data yet
     */
    public function previewBeritaAcara(CalonPelanggan $customer, BeritaAcaraService $beritaAcaraService)
    {
        try {
            // Check if customer has Gas In data
            $gasIn = $customer->gasInData;

            if (!$gasIn) {
                // Create a temporary Gas In instance for preview
                $gasIn = new GasInData([
                    'reff_id_pelanggan' => $customer->reff_id_pelanggan,
                    'tanggal_gas_in' => now(),
                    'status' => 'draft',
                ]);
                $gasIn->setRelation('calonPelanggan', $customer);
            }

            $result = $beritaAcaraService->generateGasInBeritaAcara($gasIn);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }

            // Stream PDF to browser for preview
            return $result['pdf']->stream($result['filename']);

        } catch (Exception $e) {
            Log::error('Preview Customer Berita Acara failed', [
                'customer_id' => $customer->reff_id_pelanggan,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menampilkan preview BA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download Berita Acara Gas In for a customer
     */
    public function downloadBeritaAcara(CalonPelanggan $customer, BeritaAcaraService $beritaAcaraService)
    {
        try {
            // Check if customer has Gas In data
            $gasIn = $customer->gasInData;

            if (!$gasIn) {
                // Create a temporary Gas In instance for download
                $gasIn = new GasInData([
                    'reff_id_pelanggan' => $customer->reff_id_pelanggan,
                    'tanggal_gas_in' => now(),
                    'status' => 'draft',
                ]);
                $gasIn->setRelation('calonPelanggan', $customer);
            }

            $result = $beritaAcaraService->generateGasInBeritaAcara($gasIn);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }

            // Download PDF
            return $result['pdf']->download($result['filename']);

        } catch (Exception $e) {
            Log::error('Download Customer Berita Acara failed', [
                'customer_id' => $customer->reff_id_pelanggan,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunduh BA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download multiple Berita Acara Gas In as ZIP
     * Now accepts reff_id_pelanggan instead of gas_in_data.id
     * Works for ALL customers (with or without Gas In data)
     */
    public function downloadBulkBeritaAcara(Request $r, BeritaAcaraService $beritaAcaraService)
    {
        try {
            $ids = $r->input('ids', []);

            if (empty($ids) || !is_array($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada BA yang dipilih'
                ], 400);
            }

            // Limit to 100 files
            if (count($ids) > 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maksimal 100 BA dapat di-download sekaligus'
                ], 400);
            }

            // Fetch customers by reff_id_pelanggan
            $customers = CalonPelanggan::with('gasInData')
                ->whereIn('reff_id_pelanggan', $ids)
                ->get();

            if ($customers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pelanggan tidak ditemukan'
                ], 404);
            }

            // Create temp directory
            $tempDir = storage_path('app/temp/ba_bulk_' . uniqid());
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $generatedFiles = [];

            // Generate PDF for each customer
            foreach ($customers as $customer) {
                try {
                    $gasIn = $customer->gasInData;

                    // If customer doesn't have Gas In data, create temporary instance
                    if (!$gasIn) {
                        $gasIn = new GasInData([
                            'reff_id_pelanggan' => $customer->reff_id_pelanggan,
                            'tanggal_gas_in' => now(),
                            'status' => 'draft',
                        ]);
                        $gasIn->setRelation('calonPelanggan', $customer);
                    }

                    $result = $beritaAcaraService->generateGasInBeritaAcara($gasIn);

                    if ($result['success']) {
                        $filePath = $tempDir . '/' . $result['filename'];
                        $result['pdf']->save($filePath);
                        $generatedFiles[] = $filePath;
                    }
                } catch (Exception $e) {
                    Log::warning('Failed to generate BA for customer: ' . $customer->reff_id_pelanggan, [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if (empty($generatedFiles)) {
                // Cleanup
                $this->recursiveRemoveDirectory($tempDir);

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menggenerate BA'
                ], 500);
            }

            // Create ZIP
            $zip = new \ZipArchive();
            $zipPath = storage_path('app/temp/Berita_Acara_Gas_In_' . date('Ymd_His') . '.zip');

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                $this->recursiveRemoveDirectory($tempDir);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuat file ZIP'
                ], 500);
            }

            // Add files to ZIP
            foreach ($generatedFiles as $file) {
                $zip->addFile($file, basename($file));
            }

            $zip->close();

            // Cleanup temp PDFs
            $this->recursiveRemoveDirectory($tempDir);

            // Download and delete ZIP after send
            return response()->download($zipPath)->deleteFileAfterSend(true);

        } catch (Exception $e) {
            Log::error('Bulk BA download failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengunduh BA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all customer IDs with current filters
     * Used for "Select All Pages" functionality
     */
    public function getAllIds(Request $request)
    {
        try {
            $query = CalonPelanggan::with(['gasInData']);

            // Apply same filters as index method
            if ($s = trim((string) $request->input('search', ''))) {
                $query->where(function ($q) use ($s) {
                    $q->where('nama_pelanggan', 'like', "%{$s}%")
                        ->orWhere('alamat', 'like', "%{$s}%")
                        ->orWhere('reff_id_pelanggan', 'like', "%{$s}%")
                        ->orWhere('nomor_telepon', 'like', "%{$s}%");
                });
            }

            if ($status = trim((string) $request->input('status', ''))) {
                $query->where('status', $status);
            }

            if ($progress = trim((string) $request->input('progress', ''))) {
                $query->where('progress_status', $progress);
            }

            // Get all customers matching filters (no pagination)
            $customers = $query->select([
                'reff_id_pelanggan',
                'nama_pelanggan'
            ])->get()->map(function ($customer) {
                return [
                    'reff_id_pelanggan' => $customer->reff_id_pelanggan,
                    'nama_pelanggan' => $customer->nama_pelanggan,
                    'gas_in_data' => $customer->gasInData ? [
                        'id' => $customer->gasInData->id,
                        'tanggal_gas_in' => $customer->gasInData->tanggal_gas_in
                    ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'customers' => $customers,
                'total' => $customers->count()
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get all customer IDs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recursively remove directory
     */
    private function recursiveRemoveDirectory($directory)
    {
        if (!file_exists($directory)) {
            return;
        }

        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $file) {
            $path = $directory . '/' . $file;
            is_dir($path) ? $this->recursiveRemoveDirectory($path) : unlink($path);
        }
        rmdir($directory);
    }

    /**
     * Preview BA MGRT for a customer (requires SR data)
     */
    public function previewBaMgrt(CalonPelanggan $customer)
    {
        try {
            // Removed SR data check as it's not required for BA MGRT
            $pdfService = app(\App\Services\PdfTemplateService::class);
            $result = $pdfService->generateBaMgrt($customer);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }

            return response()->file($result['path'], [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $result['filename'] . '"'
            ])->deleteFileAfterSend(true);

        } catch (Exception $e) {
            Log::error('Preview BA MGRT failed', [
                'customer_id' => $customer->reff_id_pelanggan,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal preview BA MGRT: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download BA MGRT for a customer
     */
    public function downloadBaMgrt(CalonPelanggan $customer)
    {
        try {
            // Removed SR data check as it's not required for BA MGRT
            $pdfService = app(\App\Services\PdfTemplateService::class);
            $result = $pdfService->generateBaMgrt($customer);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }

            return response()->download($result['path'], $result['filename'])->deleteFileAfterSend(true);

        } catch (Exception $e) {
            Log::error('Download BA MGRT failed', [
                'customer_id' => $customer->reff_id_pelanggan,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal download BA MGRT: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview Isometrik SR for a customer
     */
    public function previewIsometrikSr(CalonPelanggan $customer)
    {
        try {
            $pdfService = app(\App\Services\PdfTemplateService::class);
            $result = $pdfService->generateIsometrikSr($customer);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }

            return response()->file($result['path'], [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $result['filename'] . '"'
            ])->deleteFileAfterSend(true);

        } catch (Exception $e) {
            Log::error('Preview Isometrik SR failed', [
                'customer_id' => $customer->reff_id_pelanggan,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal preview Isometrik SR: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download Isometrik SR for a customer
     */
    public function downloadIsometrikSr(CalonPelanggan $customer)
    {
        try {
            $pdfService = app(\App\Services\PdfTemplateService::class);
            $result = $pdfService->generateIsometrikSr($customer);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }

            return response()->download($result['path'], $result['filename'])->deleteFileAfterSend(true);

        } catch (Exception $e) {
            Log::error('Download Isometrik SR failed', [
                'customer_id' => $customer->reff_id_pelanggan,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal download Isometrik SR: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download multiple BA MGRT as ZIP
     */
    public function downloadBulkBaMgrt(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids) || !is_array($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada BA MGRT yang dipilih'
                ], 400);
            }

            if (count($ids) > 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maksimal 100 BA dapat di-download sekaligus'
                ], 400);
            }

            $customers = CalonPelanggan::with('srData')
                ->whereIn('reff_id_pelanggan', $ids)
                ->get();

            if ($customers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pelanggan tidak ditemukan'
                ], 404);
            }

            $tempDir = storage_path('app/temp/ba_mgrt_bulk_' . uniqid());
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $pdfService = app(\App\Services\PdfTemplateService::class);
            $generatedFiles = [];

            foreach ($customers as $customer) {
                try {
                    $result = $pdfService->generateBaMgrt($customer);
                    if ($result['success']) {
                        $destPath = $tempDir . '/' . $result['filename'];
                        copy($result['path'], $destPath);
                        unlink($result['path']);
                        $generatedFiles[] = $destPath;
                    }
                } catch (Exception $e) {
                    Log::warning('Failed to generate BA MGRT: ' . $customer->reff_id_pelanggan, [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if (empty($generatedFiles)) {
                $this->recursiveRemoveDirectory($tempDir);
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada BA MGRT yang berhasil digenerate. Pastikan customer memiliki data SR.'
                ], 422);
            }

            $zip = new \ZipArchive();
            $zipPath = storage_path('app/temp/BA_MGRT_' . date('Ymd_His') . '.zip');

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                $this->recursiveRemoveDirectory($tempDir);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuat file ZIP'
                ], 500);
            }

            foreach ($generatedFiles as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();

            $this->recursiveRemoveDirectory($tempDir);

            return response()->download($zipPath)->deleteFileAfterSend(true);

        } catch (Exception $e) {
            Log::error('Bulk BA MGRT download failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal download bulk BA MGRT: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview Berita Acara SK
     */
    public function previewBaSk(CalonPelanggan $customer)
    {
        try {
            $pdfService = app(\App\Services\PdfTemplateService::class);
            $result = $pdfService->generateBaSk($customer);

            if (!$result['success']) {
                return response()->json(['message' => $result['message']], 500);
            }

            return response()->file($result['path'], [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $result['filename'] . '"'
            ])->deleteFileAfterSend(true);

        } catch (Exception $e) {
            Log::error('Preview BA SK failed', [
                'customer_id' => $customer->reff_id_pelanggan,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Gagal preview BA SK'], 500);
        }
    }

    /**
     * Download Berita Acara SK
     */
    public function downloadBaSk(CalonPelanggan $customer)
    {
        try {
            $pdfService = app(\App\Services\PdfTemplateService::class);
            $result = $pdfService->generateBaSk($customer);

            if (!$result['success']) {
                return back()->with('error', $result['message']);
            }

            return response()->download($result['path'], $result['filename'])->deleteFileAfterSend(true);

        } catch (Exception $e) {
            Log::error('Download BA SK failed', [
                'customer_id' => $customer->reff_id_pelanggan,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Gagal download BA SK');
        }
    }

    /**
     * Preview Isometrik SK
     */
    public function previewIsometrikSk(CalonPelanggan $customer)
    {
        try {
            $pdfService = app(\App\Services\PdfTemplateService::class);
            $result = $pdfService->generateIsometrikSk($customer);

            if (!$result['success']) {
                return response()->json(['message' => $result['message']], 500);
            }

            return response()->file($result['path'], [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $result['filename'] . '"'
            ])->deleteFileAfterSend(true);

        } catch (Exception $e) {
            Log::error('Preview Isometrik SK failed', [
                'customer_id' => $customer->reff_id_pelanggan,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Gagal preview Isometrik SK'], 500);
        }
    }

    /**
     * Download Isometrik SK
     */
    public function downloadIsometrikSk(CalonPelanggan $customer)
    {
        try {
            $pdfService = app(\App\Services\PdfTemplateService::class);
            $result = $pdfService->generateIsometrikSk($customer);

            if (!$result['success']) {
                return back()->with('error', $result['message']);
            }

            return response()->download($result['path'], $result['filename'])->deleteFileAfterSend(true);

        } catch (Exception $e) {
            Log::error('Download Isometrik SK failed', [
                'customer_id' => $customer->reff_id_pelanggan,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Gagal download Isometrik SK');
        }
    }

    /**
     * Download multiple documents (BA Gas In, BA MGRT, Isometrik SR, BA SK, Isometrik SK) as ZIP
     */
    public function downloadBulkDocuments(Request $request, BeritaAcaraService $beritaAcaraService)
    {
        try {
            $ids = $request->input('ids', []);
            $docTypes = $request->input('doc_types', ['gas_in']);

            if (empty($ids) || !is_array($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada customer yang dipilih'
                ], 400);
            }

            if (count($ids) > 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maksimal 100 customer dapat di-download sekaligus'
                ], 400);
            }

            $customers = CalonPelanggan::with(['gasInData', 'srData'])
                ->whereIn('reff_id_pelanggan', $ids)
                ->get();

            if ($customers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pelanggan tidak ditemukan'
                ], 404);
            }

            $tempDir = storage_path('app/temp/documents_bulk_' . uniqid());
            $pdfService = app(\App\Services\PdfTemplateService::class);
            $generatedFiles = [];

            // Create subdirectories for each doc type
            foreach ($docTypes as $type) {
                $subDir = $tempDir . '/' . $type;
                if (!file_exists($subDir)) {
                    mkdir($subDir, 0755, true);
                }
            }

            foreach ($customers as $customer) {
                // Generate BA Gas In
                if (in_array('gas_in', $docTypes)) {
                    try {
                        $gasIn = $customer->gasInData;
                        if (!$gasIn) {
                            $gasIn = new GasInData([
                                'reff_id_pelanggan' => $customer->reff_id_pelanggan,
                                'tanggal_gas_in' => now(),
                                'status' => 'draft',
                            ]);
                            $gasIn->setRelation('calonPelanggan', $customer);
                        }

                        $result = $beritaAcaraService->generateGasInBeritaAcara($gasIn);
                        if ($result['success']) {
                            $filePath = $tempDir . '/gas_in/' . $result['filename'];
                            $result['pdf']->save($filePath);
                            $generatedFiles[] = ['path' => $filePath, 'type' => 'gas_in'];
                        }
                    } catch (Exception $e) {
                        Log::warning('Failed to generate BA Gas In: ' . $customer->reff_id_pelanggan);
                    }
                }

                // Generate BA MGRT
                if (in_array('mgrt', $docTypes)) {
                    try {
                        $result = $pdfService->generateBaMgrt($customer);
                        if ($result['success']) {
                            $destPath = $tempDir . '/mgrt/' . $result['filename'];
                            copy($result['path'], $destPath);
                            unlink($result['path']);
                            $generatedFiles[] = ['path' => $destPath, 'type' => 'mgrt'];
                        }
                    } catch (Exception $e) {
                        Log::warning('Failed to generate BA MGRT: ' . $customer->reff_id_pelanggan);
                    }
                }

                // Generate Isometrik SR
                if (in_array('isometrik_sr', $docTypes)) {
                    try {
                        $result = $pdfService->generateIsometrikSr($customer);
                        if ($result['success']) {
                            $destPath = $tempDir . '/isometrik_sr/' . $result['filename'];
                            copy($result['path'], $destPath);
                            unlink($result['path']);
                            $generatedFiles[] = ['path' => $destPath, 'type' => 'isometrik_sr'];
                        }
                    } catch (Exception $e) {
                        Log::warning('Failed to generate Isometrik SR: ' . $customer->reff_id_pelanggan);
                    }
                }

                // Generate BA SK
                if (in_array('ba_sk', $docTypes)) {
                    try {
                        $result = $pdfService->generateBaSk($customer);
                        if ($result['success']) {
                            $destPath = $tempDir . '/ba_sk/' . $result['filename'];
                            copy($result['path'], $destPath);
                            unlink($result['path']);
                            $generatedFiles[] = ['path' => $destPath, 'type' => 'ba_sk'];
                        }
                    } catch (Exception $e) {
                        Log::warning('Failed to generate BA SK: ' . $customer->reff_id_pelanggan);
                    }
                }

                // Generate Isometrik SK
                if (in_array('isometrik_sk', $docTypes)) {
                    try {
                        $result = $pdfService->generateIsometrikSk($customer);
                        if ($result['success']) {
                            $destPath = $tempDir . '/isometrik_sk/' . $result['filename'];
                            copy($result['path'], $destPath);
                            unlink($result['path']);
                            $generatedFiles[] = ['path' => $destPath, 'type' => 'isometrik_sk'];
                        }
                    } catch (Exception $e) {
                        Log::warning('Failed to generate Isometrik SK: ' . $customer->reff_id_pelanggan);
                    }
                }
            }

            if (empty($generatedFiles)) {
                $this->recursiveRemoveDirectory($tempDir);
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada dokumen yang berhasil digenerate'
                ], 422);
            }

            $zip = new \ZipArchive();
            $zipName = 'Documents_' . implode('_', $docTypes) . '_' . date('Ymd_His') . '.zip';
            $zipPath = storage_path('app/temp/' . $zipName);

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                $this->recursiveRemoveDirectory($tempDir);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuat file ZIP'
                ], 500);
            }

            foreach ($generatedFiles as $file) {
                $relativePath = $file['type'] . '/' . basename($file['path']);
                $zip->addFile($file['path'], $relativePath);
            }
            $zip->close();

            $this->recursiveRemoveDirectory($tempDir);

            return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);

        } catch (Exception $e) {
            Log::error('Bulk documents download failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal download dokumen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download multiple documents merged into PDF files
     * - Single doc type: Returns 1 merged PDF with all customers
     * - Multiple doc types: Returns ZIP with separate merged PDFs per doc type
     */
    public function downloadBulkDocumentsMerged(Request $request, BeritaAcaraService $beritaAcaraService)
    {
        try {
            $ids = $request->input('ids', []);
            $docTypes = $request->input('doc_types', ['gas_in']);

            if (empty($ids) || !is_array($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada customer yang dipilih'
                ], 400);
            }

            if (count($ids) > 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maksimal 100 customer dapat di-download sekaligus'
                ], 400);
            }

            $customers = CalonPelanggan::with(['gasInData', 'srData'])
                ->whereIn('reff_id_pelanggan', $ids)
                ->get()
                ->sortBy(function ($customer) use ($ids) {
                    return array_search($customer->reff_id_pelanggan, $ids);
                });

            if ($customers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pelanggan tidak ditemukan'
                ], 404);
            }

            $tempDir = storage_path('app/temp/documents_merge_' . uniqid());
            $pdfService = app(\App\Services\PdfTemplateService::class);

            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $docTypeNames = [
                'gas_in' => 'BA_Gas_In',
                'mgrt' => 'BA_MGRT',
                'isometrik_sr' => 'Isometrik_SR',
                'ba_sk' => 'BA_SK',
                'isometrik_sk' => 'Isometrik_SK'
            ];

            $mergedFiles = []; // Store merged PDF paths per doc type

            // Generate and merge PDFs per document type
            foreach ($docTypes as $docType) {
                $pdfFilesForType = [];

                // Generate individual PDFs for all customers for this doc type
                foreach ($customers as $customer) {
                    $result = null;

                    try {
                        switch ($docType) {
                            case 'gas_in':
                                $gasIn = $customer->gasInData;
                                if (!$gasIn) {
                                    $gasIn = new GasInData([
                                        'reff_id_pelanggan' => $customer->reff_id_pelanggan,
                                        'tanggal_gas_in' => now(),
                                        'status' => 'draft',
                                    ]);
                                    $gasIn->setRelation('calonPelanggan', $customer);
                                }
                                $baResult = $beritaAcaraService->generateGasInBeritaAcara($gasIn);
                                if ($baResult['success']) {
                                    $filePath = $tempDir . '/gas_in_' . $customer->reff_id_pelanggan . '.pdf';
                                    $baResult['pdf']->save($filePath);
                                    $pdfFilesForType[] = $filePath;
                                }
                                break;

                            case 'mgrt':
                                $result = $pdfService->generateBaMgrt($customer);
                                break;

                            case 'isometrik_sr':
                                $result = $pdfService->generateIsometrikSr($customer);
                                break;

                            case 'ba_sk':
                                $result = $pdfService->generateBaSk($customer);
                                break;

                            case 'isometrik_sk':
                                $result = $pdfService->generateIsometrikSk($customer);
                                break;
                        }

                        if ($result && $result['success'] && isset($result['path'])) {
                            $pdfFilesForType[] = $result['path'];
                        }
                    } catch (Exception $e) {
                        Log::warning('Failed to generate PDF for merge: ' . $customer->reff_id_pelanggan . ' - ' . $docType, [
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Merge all PDFs for this doc type into one
                if (!empty($pdfFilesForType)) {
                    $mergedPdf = new \setasign\Fpdi\Fpdi();

                    foreach ($pdfFilesForType as $pdfFile) {
                        try {
                            $pageCount = $mergedPdf->setSourceFile($pdfFile);
                            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                                $templateId = $mergedPdf->importPage($pageNo);
                                $size = $mergedPdf->getTemplateSize($templateId);
                                $mergedPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                                $mergedPdf->useTemplate($templateId);
                            }
                        } catch (Exception $e) {
                            Log::warning('Failed to merge PDF page', [
                                'file' => $pdfFile,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }

                    // Save merged PDF for this doc type
                    $mergedFilename = ($docTypeNames[$docType] ?? 'Documents') . '_Merged_' . date('Ymd_His') . '.pdf';
                    $mergedPath = $tempDir . '/' . $mergedFilename;
                    $mergedPdf->Output($mergedPath, 'F');
                    $mergedFiles[$docType] = [
                        'path' => $mergedPath,
                        'filename' => $mergedFilename
                    ];

                    // Cleanup individual PDFs for this type
                    foreach ($pdfFilesForType as $pdfFile) {
                        if (file_exists($pdfFile)) {
                            @unlink($pdfFile);
                        }
                    }
                }
            }

            if (empty($mergedFiles)) {
                $this->recursiveRemoveDirectory($tempDir);
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada dokumen yang berhasil digenerate'
                ], 422);
            }

            // Single doc type: return the merged PDF directly
            if (count($mergedFiles) === 1) {
                $file = array_values($mergedFiles)[0];
                return response()->download($file['path'], $file['filename'])->deleteFileAfterSend(true);
            }

            // Multiple doc types: create ZIP containing all merged PDFs
            $zipFilename = 'Documents_Merged_' . date('Ymd_His') . '.zip';
            $zipPath = storage_path('app/temp/' . $zipFilename);

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Could not create ZIP file');
            }

            foreach ($mergedFiles as $docType => $file) {
                $zip->addFile($file['path'], $file['filename']);
            }

            $zip->close();

            // Cleanup merged PDFs
            foreach ($mergedFiles as $file) {
                if (file_exists($file['path'])) {
                    @unlink($file['path']);
                }
            }
            $this->recursiveRemoveDirectory($tempDir);

            return response()->download($zipPath, $zipFilename)->deleteFileAfterSend(true);

        } catch (Exception $e) {
            Log::error('Bulk documents merged download failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal download dokumen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show preview of Google Sheet Sync
     */
    public function syncPreview(\App\Services\GoogleSheetsService $sheetsService)
    {
        try {
            $sheetData = $sheetsService->getCalonPelangganData();

            // Fetch all existing customers Keyed by Reff ID
            $existingCustomers = CalonPelanggan::all()->keyBy('reff_id_pelanggan');

            // Also create a map by nama_pelanggan for reff_id change detection
            $existingByName = CalonPelanggan::all()->keyBy(function ($item) {
                return strtolower(trim($item->nama_pelanggan ?? ''));
            });

            $new = [];
            $updated = [];
            $unchanged = [];
            $deleted = [];
            $deletedWithProgress = [];
            $reffIdChanged = [];

            // Track which reff_ids are in the sheet
            $sheetReffIds = [];
            // Track names in sheet for reff_id change detection
            $sheetNames = [];

            foreach ($sheetData as $row) {
                $reffId = $row['reff_id_pelanggan'] ?? null;

                if (!$reffId)
                    continue;

                $sheetReffIds[] = $reffId;
                $sheetNames[strtolower(trim($row['nama_pelanggan'] ?? ''))] = $row;

                if (isset($existingCustomers[$reffId])) {
                    $customer = $existingCustomers[$reffId];
                    $differences = [];

                    // Compare fields
                    $fieldsToCheck = ['nama_pelanggan', 'alamat', 'no_telepon', 'no_ktp', 'kota_kabupaten', 'kecamatan', 'kelurahan', 'padukuhan', 'rt', 'rw', 'jenis_pelanggan', 'keterangan'];

                    foreach ($fieldsToCheck as $field) {
                        $sheetVal = trim($row[$field] ?? '');
                        $dbVal = trim($customer->$field ?? '');

                        // Skip if sheet value is empty (don't overwrite with empty)
                        if ($sheetVal === '')
                            continue;

                        // Detect difference: either values differ OR db is empty but sheet has value
                        $sheetLower = strtolower($sheetVal);
                        $dbLower = strtolower($dbVal);

                        if ($sheetLower !== $dbLower) {
                            $differences[$field] = ['old' => $dbVal ?: '(kosong)', 'new' => $sheetVal];
                        }
                    }

                    if (!empty($differences)) {
                        $updated[] = [
                            'data' => $row,
                            'differences' => $differences
                        ];
                    } else {
                        $unchanged[] = $row;
                    }
                } else {
                    $new[] = $row;
                }
            }

            // Detect deleted: customers in DB but NOT in sheet
            foreach ($existingCustomers as $reffId => $customer) {
                if (!in_array($reffId, $sheetReffIds)) {
                    $customerName = strtolower(trim($customer->nama_pelanggan ?? ''));

                    // Check if this customer's name exists in sheet with different reff_id
                    // This indicates a reff_id change
                    if (!empty($customerName) && isset($sheetNames[$customerName])) {
                        $newSheetData = $sheetNames[$customerName];
                        $newReffId = $newSheetData['reff_id_pelanggan'];

                        // Only consider as reff_id change if the new reff_id is actually new (not already in DB)
                        if (!isset($existingCustomers[$newReffId])) {
                            // Check if customer has progress
                            $hasProgress = $customer->progress_status !== 'validasi'
                                || $customer->skData()->exists()
                                || $customer->srData()->exists()
                                || $customer->gasInData()->exists();

                            $reffIdChanged[] = [
                                'old_reff_id' => $reffId,
                                'new_reff_id' => $newReffId,
                                'nama_pelanggan' => $customer->nama_pelanggan,
                                'alamat' => $customer->alamat,
                                'progress_status' => $customer->progress_status,
                                'has_progress' => $hasProgress,
                                'new_data' => $newSheetData
                            ];

                            // Remove from new array since it's a reff_id change, not truly new
                            $new = array_filter($new, function ($item) use ($newReffId) {
                                return ($item['reff_id_pelanggan'] ?? '') !== $newReffId;
                            });
                            $new = array_values($new); // Re-index

                            continue; // Don't add to deleted
                        }
                    }

                    // Regular deleted case
                    $hasProgress = $customer->progress_status !== 'validasi'
                        || $customer->skData()->exists()
                        || $customer->srData()->exists()
                        || $customer->gasInData()->exists();

                    $customerData = [
                        'reff_id_pelanggan' => $customer->reff_id_pelanggan,
                        'nama_pelanggan' => $customer->nama_pelanggan,
                        'alamat' => $customer->alamat,
                        'progress_status' => $customer->progress_status,
                        'has_progress' => $hasProgress
                    ];

                    if ($hasProgress) {
                        $deletedWithProgress[] = $customerData;
                    } else {
                        $deleted[] = $customerData;
                    }
                }
            }

            return response()->json([
                'new' => $new,
                'updated' => $updated,
                'unchanged' => $unchanged,
                'deleted' => $deleted,
                'deleted_with_progress' => $deletedWithProgress,
                'reff_id_changed' => $reffIdChanged
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch sync preview: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Process Sync (Save Data)
     */
    public function syncProcess(Request $request)
    {
        try {
            $dataToSync = $request->input('sync_data', []);
            $dataToDelete = $request->input('delete_data', []);
            $reffIdChanges = $request->input('reff_id_change_data', []);
            $countNew = 0;
            $countUpdated = 0;
            $countDeleted = 0;
            $countReffIdChanged = 0;

            // Process reff_id changes first (migrate old reff_id to new)
            foreach ($reffIdChanges as $change) {
                $change = json_decode($change, true);
                if (!$change || !isset($change['old_reff_id']) || !isset($change['new_reff_id']))
                    continue;

                $oldReffId = $change['old_reff_id'];
                $newReffId = $change['new_reff_id'];
                $newData = $change['new_data'] ?? [];

                $customer = CalonPelanggan::where('reff_id_pelanggan', $oldReffId)->first();
                if ($customer) {
                    // Update reff_id and other fields from sheet
                    $customer->reff_id_pelanggan = $newReffId;

                    // Update other fields from new sheet data
                    $fieldsToUpdate = ['nama_pelanggan', 'alamat', 'no_telepon', 'no_ktp', 'kota_kabupaten', 'kecamatan', 'kelurahan', 'padukuhan', 'rt', 'rw', 'jenis_pelanggan', 'keterangan'];
                    foreach ($fieldsToUpdate as $field) {
                        if (isset($newData[$field]) && $newData[$field] !== '') {
                            $customer->$field = $newData[$field];
                        }
                    }

                    // Normalize jenis_pelanggan to valid ENUM value
                    if ($customer->jenis_pelanggan) {
                        $customer->jenis_pelanggan = $this->normalizeJenisPelanggan($customer->jenis_pelanggan);
                    }

                    $customer->save();
                    $countReffIdChanged++;

                    \Log::info("Reff ID migrated: {$oldReffId} -> {$newReffId} for {$customer->nama_pelanggan}");
                }
            }

            // Process new and updated data
            foreach ($dataToSync as $item) {
                $item = json_decode($item, true);
                if (!$item || !isset($item['reff_id_pelanggan']))
                    continue;

                $reffId = $item['reff_id_pelanggan'];

                // Allow empty fields to be null
                $data = array_map(function ($val) {
                    return $val === '' ? null : $val;
                }, $item);

                // Normalize jenis_pelanggan to valid ENUM value
                if (isset($data['jenis_pelanggan']) && $data['jenis_pelanggan']) {
                    $data['jenis_pelanggan'] = $this->normalizeJenisPelanggan($data['jenis_pelanggan']);
                }

                // Set defaults
                if (!isset($data['status']))
                    $data['status'] = 'lanjut';
                if (!isset($data['progress_status']))
                    $data['progress_status'] = 'validasi';

                // Now that $fillable includes no_ktp, kota_kabupaten, kecamatan,
                // updateOrCreate properly handles all fields
                $customer = CalonPelanggan::updateOrCreate(
                    ['reff_id_pelanggan' => $reffId],
                    $data
                );

                if ($customer->wasRecentlyCreated) {
                    $countNew++;
                } else {
                    $countUpdated++;
                }
            }

            // Process deletions (only safe ones - no progress)
            foreach ($dataToDelete as $reffId) {
                $customer = CalonPelanggan::where('reff_id_pelanggan', $reffId)->first();
                if ($customer) {
                    // Double-check: only delete if no progress
                    $hasProgress = $customer->progress_status !== 'validasi'
                        || $customer->skData()->exists()
                        || $customer->srData()->exists()
                        || $customer->gasInData()->exists();

                    if (!$hasProgress) {
                        $customer->delete();
                        $countDeleted++;
                    }
                }
            }

            $message = "Sync completed.";
            if ($countNew > 0)
                $message .= " New: {$countNew}";
            if ($countUpdated > 0)
                $message .= " Updated: {$countUpdated}";
            if ($countReffIdChanged > 0)
                $message .= " Reff ID Changed: {$countReffIdChanged}";
            if ($countDeleted > 0)
                $message .= " Deleted: {$countDeleted}";

            return redirect()->route('customers.index')->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Normalize jenis_pelanggan value from Google Sheet to valid ENUM value.
     */
    private function normalizeJenisPelanggan(string $value): string
    {
        $normalized = strtolower(trim($value));

        $mapping = [
            'pengembangan' => 'pengembangan',
            'penetrasi' => 'penetrasi',
            'on the spot penetrasi' => 'on_the_spot_penetrasi',
            'on the spot pengembangan' => 'on_the_spot_pengembangan',
            'on_the_spot_penetrasi' => 'on_the_spot_penetrasi',
            'on_the_spot_pengembangan' => 'on_the_spot_pengembangan',
            // Handle combined/alternate format from Google Sheet
            'pengembangan (penetrasi)' => 'penetrasi',
            'penetrasi (pengembangan)' => 'pengembangan',
        ];

        return $mapping[$normalized] ?? 'pengembangan';
    }
}

