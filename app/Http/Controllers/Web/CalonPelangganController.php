<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CalonPelanggan;
use App\Models\SkData;
use App\Services\NotificationService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

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

    /**
     * Display a listing of customers
     * Returns Blade view for web requests, JSON for AJAX
     */
    public function index(Request $request)
    {
        try {
            $query = CalonPelanggan::with(['validatedBy:id,name']);

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
            if ($request->filled('kelurahan')){$query->where('kelurahan','like','%'.$request->kelurahan.'%');}
            if ($request->filled('padukuhan')){$query->where('padukuhan','like','%'.$request->padukuhan.'%');}


            // Sorting (whitelist)
            $allowedSorts = ['created_at','updated_at','nama_pelanggan','progress_status','status','kelurahan','padukuhan'];
            $sortBy        = in_array($request->input('sort_by'), $allowedSorts, true) ? $request->input('sort_by') : 'created_at';
            $sortDirection = strtolower($request->input('sort_direction')) === 'asc' ? 'asc' : 'desc';

            $query->orderBy($sortBy, $sortDirection);

            // Pagination
            $perPage   = min(max((int) $request->input('per_page', 15), 5), 50);
            $customers = $query->paginate($perPage)->appends($request->only([
                'search','status','progress_status','kelurahan','padukuhan','sort_by','sort_direction','per_page'
            ]));

            // Updated stats with validation metrics
            $stats = [
                'total_customers'       => CalonPelanggan::count(),
                'pending_validation'    => CalonPelanggan::where('status', 'pending')->count(),
                'validated_customers'   => CalonPelanggan::where('status', 'lanjut')->count(),
                'in_progress_customers' => CalonPelanggan::whereIn('status', ['in_progress', 'lanjut'])
                                                       ->whereNotIn('progress_status', ['done', 'batal'])
                                                       ->count(),
                'completed_customers'   => CalonPelanggan::where('progress_status', 'done')->count(),
                'cancelled_customers'   => CalonPelanggan::where('progress_status', 'batal')
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
                    'data'    => $customers, // paginator object
                    'stats'   => $stats,
                    'filters' => $request->only(['search','status','progress_status','kelurahan','padukuhan']),
                    'currentSort' => ['field' => $sortBy, 'direction' => $sortDirection],
                ]);
            }

            // Blade view (SSR first load)
            return view('customers.index', [
                'customers'    => $customers,
                'stats'        => $stats,
                'currentSort'  => ['field' => $sortBy, 'direction' => $sortDirection],
                'activeFilters'=> $request->only(['search','status','progress_status','kelurahan','padukuhan']),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error fetching customers', [
                'error'   => $e->getMessage(),
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
                'skData.photoApprovals', 'srData.photoApprovals','gasInData.photoApprovals',
                'photoApprovals.tracerUser', 'photoApprovals.cgpUser',
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
        // Normalisasi dulu agar unique ngecek nilai uppercase juga
        $request->merge([
            'reff_id_pelanggan' => strtoupper((string) $request->input('reff_id_pelanggan')),
        ]);

        $validator = Validator::make($request->all(), [
            'reff_id_pelanggan' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9]+$/',
                Rule::unique('calon_pelanggan', 'reff_id_pelanggan'),
            ],
            'nama_pelanggan'   => 'required|string|max:255',
            'alamat'           => 'required|string|max:1000',
            'no_telepon'       => 'required|string|max:20|regex:/^[0-9+\-\s]+$/',
            'kelurahan'        => 'nullable|string|max:120',
            'padukuhan'        => 'nullable|string|max:120',
            'jenis_pelanggan'  => 'nullable|in:pengembangan,penetrasi,on_the_spot_penetrasi,on_the_spot_pengembangan',
            'keterangan'       => 'nullable|string|max:500',
            'status'           => 'sometimes|in:pending,lanjut,in_progress,batal',
            'progress_status'  => 'sometimes|in:validasi,sk,sr,gas_in,done,batal',
            'email'            => 'nullable|email',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors'  => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = $request->only([
                'reff_id_pelanggan','nama_pelanggan','alamat','no_telepon',
                'kelurahan','padukuhan','keterangan','jenis_pelanggan','status','progress_status','email',
            ]);

            // default kalau tidak dikirim
            $data['jenis_pelanggan']   = $data['jenis_pelanggan']  ?? 'pengembangan';
            $data['status']            = $data['status']           ?? 'pending';
            $data['progress_status']   = $data['progress_status']  ?? 'validasi';
            $data['tanggal_registrasi'] = now();

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
                    'data'    => $customer,
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

        if ($currentIndex === false) return 0;
        if ($customer->progress_status === 'done') return 100;

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
                'nama_pelanggan'  => 'sometimes|string|max:255',
                'alamat'          => 'sometimes|string|max:1000',
                'no_telepon'      => 'sometimes|string|max:20|regex:/^[0-9+\-\s]+$/',
                'status'          => 'sometimes|in:pending,lanjut,in_progress,batal',
                'progress_status' => 'sometimes|in:validasi,sk,sr,gas_in,done,batal',
                'kelurahan'       => 'nullable|string|max:120',
                'padukuhan'       => 'nullable|string|max:120',
                'jenis_pelanggan' => 'nullable|in:pengembangan,penetrasi,on_the_spot_penetrasi,on_the_spot_pengembangan',
                'keterangan'      => 'nullable|string|max:500',
                'email'           => 'nullable|email'
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
                'nama_pelanggan','alamat','no_telepon','status',
                'progress_status','kelurahan','padukuhan','jenis_pelanggan','keterangan','email'
            ]);

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
                    'data'    => $customer
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
                $cp = CalonPelanggan::whereRaw('CAST(reff_id_pelanggan AS UNSIGNED) = ?', [(int)$id])->first();
            }

            // 3) fallback: ada di SK → ambil relasi calon_pelanggan
            if (!$cp) {
                $sk = SkData::with('calonPelanggan')->where('reff_id_pelanggan', $id)->first();
                if (!$sk && ctype_digit($id)) {
                    $sk = SkData::with('calonPelanggan')
                        ->whereRaw('CAST(reff_id_pelanggan AS UNSIGNED) = ?', [(int)$id])
                        ->first();
                }
                if ($sk && $sk->calonPelanggan) $cp = $sk->calonPelanggan;
            }

            if ($cp) {
                return response()->json([
                    'success' => true,
                    'valid'   => false,
                    'exists'  => true,
                    'message' => 'Pelanggan ditemukan',
                    'data'    => [
                        'reff_id_pelanggan' => $cp->reff_id_pelanggan,
                        'nama_pelanggan'    => $cp->nama_pelanggan ?? null,
                        'alamat'            => $cp->alamat ?? null,
                        'no_telepon'        => $cp->no_telepon ?? null,
                        'kelurahan'         => $cp->kelurahan ?? null,
                        'padukuhan'         => $cp->padukuhan ?? null,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'valid'   => true,
                'exists'  => false,
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
            $query->where(function($q) use ($search) {
                $q->where('nama_pelanggan', 'LIKE', '%'.$search.'%')
                  ->orWhere('reff_id_pelanggan', 'LIKE', '%'.$search.'%')
                  ->orWhere('alamat', 'LIKE', '%'.$search.'%')
                  ->orWhere('no_telepon', 'LIKE', '%'.$search.'%');
            });
        }

        foreach (['status','progress_status','kelurahan','padukuhan','jenis_pelanggan'] as $filter) {
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
            $relationName = $module.'Data';
            $moduleData = $customer->$relationName;

            // NOTE: fallback ke status/workflow_status bila module_status tidak ada
            $moduleStatus = $moduleData?->module_status
                ?? $moduleData?->status
                ?? $moduleData?->workflow_status
                ?? 'not_started';

            $status[$module] = [
                'status'       => $moduleStatus,
                'can_proceed'  => $customer->canProceedToModule($module)
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
}
