<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Web\{AuthController, DashboardController, CalonPelangganController, SkDataController, SrDataController, PhotoApprovalController, NotificationController, ImportController, GasInDataController, AdminController, TracerApprovalController, CgpApprovalController, TracerJalurApprovalController, JalurController, JalurClusterController, JalurLineNumberController, JalurLoweringController, JalurLoweringImportController, JalurJointController, JalurJointNumberController, JalurFittingTypeController, ReportDashboardController, ComprehensiveReportController, MapFeatureController, HseDailyReportController};

Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});


Route::pattern('id', '[0-9]+');
Route::get('/auth/check', [AuthController::class, 'check'])->name('auth.check');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware(['auth', 'user.active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('password.change');

    // Proxy Google Drive images (authenticated download with caching)
    Route::get('/drive-image/{fileId}', function($fileId) {
        try {
            $cacheKey = "drive_image_{$fileId}";
            $cacheDuration = 3600; // 1 hour

            // Try to get from cache first
            $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);

            if ($cached) {
                return response($cached['data'])
                    ->header('Content-Type', $cached['mime_type'])
                    ->header('Cache-Control', 'public, max-age=3600')
                    ->header('X-Cache', 'HIT') // Debug: show cache hit
                    ->header('Content-Length', strlen($cached['data']));
            }

            // Not in cache, download from Google Drive
            $driveService = app(\App\Services\GoogleDriveService::class);
            $imageData = $driveService->downloadFileContent($fileId);

            // Detect mime type
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageData) ?: 'image/jpeg';

            // Store in cache
            \Illuminate\Support\Facades\Cache::put($cacheKey, [
                'data' => $imageData,
                'mime_type' => $mimeType
            ], $cacheDuration);

            // Return image response with proper headers
            return response($imageData)
                ->header('Content-Type', $mimeType)
                ->header('Cache-Control', 'public, max-age=3600')
                ->header('X-Cache', 'MISS') // Debug: show cache miss
                ->header('Content-Length', strlen($imageData));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Drive image proxy error', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);

            // Return SVG placeholder on error
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300"><rect width="400" height="300" fill="#f3f4f6"/><text x="200" y="140" text-anchor="middle" font-family="Arial" font-size="16" fill="#9ca3af">Image Load Failed</text><text x="200" y="165" text-anchor="middle" font-family="Arial" font-size="12" fill="#6b7280">File ID: ' . htmlspecialchars($fileId) . '</text></svg>';

            return response($svg)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Cache-Control', 'no-cache');
        }
    })->name('drive.image');

    Route::middleware('role:admin,cgp,tracer,super_admin,jalur')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'getData'])->name('dashboard.data');
    Route::get('/dashboard/installation-trend', [DashboardController::class, 'getInstallationTrend'])->name('dashboard.installation-trend');
    Route::get('/dashboard/activity-metrics', [DashboardController::class, 'getActivityMetrics'])->name('dashboard.activity-metrics');
    Route::get('/dashboard/marker/{customerId}', [DashboardController::class, 'getMarkerDetail'])->name('dashboard.marker-detail');
    Route::get('/dashboard/pipe-exceed-detail', [DashboardController::class, 'getPipeExceedDetail'])->name('dashboard.pipe-exceed-detail');
    });

    // Map Features Routes
    Route::prefix('map-features')->name('map-features.')->group(function () {
        Route::get('/', [MapFeatureController::class, 'index'])->name('index');
        Route::post('/', [MapFeatureController::class, 'store'])->name('store');
        Route::put('/{feature}', [MapFeatureController::class, 'update'])->name('update');
        Route::delete('/{feature}', [MapFeatureController::class, 'destroy'])->name('destroy');
        Route::patch('/{feature}/toggle-visibility', [MapFeatureController::class, 'toggleVisibility'])->name('toggle-visibility');
        Route::get('/line-numbers', [MapFeatureController::class, 'getLineNumbers'])->name('line-numbers');
        Route::get('/clusters', [MapFeatureController::class, 'getClusters'])->name('clusters');
    });

    // Report Dashboard Routes
    Route::get('/reports/test', function() {
        // Auto login test user for testing
        $testUser = \App\Models\User::where('email', 'test@test.com')->first();
        if (!$testUser) {
            return response()->json(['error' => 'Test user not found'], 404);
        }

        Auth::login($testUser);

        // Test the actual controller method to catch errors
        try {
            $controller = new ReportDashboardController();
            $request = new \Illuminate\Http\Request(['ajax' => true]);
            $response = $controller->index($request);

            $data = null;
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                $responseData = $response->getData(true);
                $data = $responseData['data'] ?? null;
            }

            return response()->json([
                'test' => 'Reports controller working',
                'user' => Auth::user()?->name,
                'response_type' => get_class($response),
                'has_data' => $response instanceof \Illuminate\Http\JsonResponse,
                'evidence_counts' => [
                    'sk' => $data['module_stats']['sk']['evidence_uploaded']['total'] ?? 'not found',
                    'sr' => $data['module_stats']['sr']['evidence_uploaded']['total'] ?? 'not found',
                    'gas_in' => $data['module_stats']['gas_in']['evidence_uploaded']['total'] ?? 'not found',
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'test' => 'Reports controller ERROR',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile()),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });

    Route::middleware('role:admin,super_admin')->group(function () {
        Route::get('/reports', [ReportDashboardController::class, 'index'])->name('reports.dashboard');
    });

    Route::get('/reports/export', [ReportDashboardController::class, 'export'])->name('reports.export');
    Route::get('/reports/comprehensive', [ComprehensiveReportController::class, 'index'])->name('reports.comprehensive');
    Route::get('/reports/comprehensive/export', [ComprehensiveReportController::class, 'export'])->name('reports.comprehensive.export');

    // PILOT Comparison Routes
    Route::middleware('role:admin,super_admin,cgp')->prefix('pilot-comparison')->name('pilot-comparison.')->group(function () {
        Route::get('/', [\App\Http\Controllers\PilotComparisonController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\PilotComparisonController::class, 'create'])->name('create');
        Route::post('/preview', [\App\Http\Controllers\PilotComparisonController::class, 'preview'])->name('preview');
        Route::post('/import-sheets', [\App\Http\Controllers\PilotComparisonController::class, 'importFromGoogleSheets'])->name('import-sheets');
        Route::get('/preview/view', [\App\Http\Controllers\PilotComparisonController::class, 'previewView'])->name('preview-view');
        Route::get('/debug', [\App\Http\Controllers\PilotComparisonController::class, 'debugView'])->name('debug-view');
        Route::post('/', [\App\Http\Controllers\PilotComparisonController::class, 'store'])->name('store');
        Route::get('/{batch}', [\App\Http\Controllers\PilotComparisonController::class, 'show'])->name('show');
        Route::post('/{batch}/compare', [\App\Http\Controllers\PilotComparisonController::class, 'compare'])->name('compare');
        Route::delete('/{batch}', [\App\Http\Controllers\PilotComparisonController::class, 'destroy'])->name('destroy');
        Route::get('/{batch}/export', [\App\Http\Controllers\PilotComparisonController::class, 'export'])->name('export');
    });

    // Admin Routes
    Route::middleware(['role:super_admin,admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
            Route::get('/users', [AdminController::class, 'usersIndex'])->name('users');

            Route::prefix('api')
                ->name('api.')
                ->group(function () {
                    Route::get('/users', [AdminController::class, 'getUsers'])->name('users');
                    Route::post('/users', [AdminController::class, 'createUser'])->name('users.create');
                    Route::get('/users/{id}', [AdminController::class, 'getUser'])
                        ->whereNumber('id')
                        ->name('users.show');
                    Route::put('/users/{id}', [AdminController::class, 'updateUser'])
                        ->whereNumber('id')
                        ->name('users.update');
                    Route::patch('/users/{id}/toggle', [AdminController::class, 'toggleUserStatus'])
                        ->whereNumber('id')
                        ->name('users.toggle');
                    Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])
                        ->whereNumber('id')
                        ->name('users.delete');

                    // Multi-role management routes
                    Route::get('/users-with-roles', [AdminController::class, 'getUsersWithRoles'])->name('users.with-roles');
                    Route::get('/users/{id}/roles', [AdminController::class, 'getUserWithRoles'])
                        ->whereNumber('id')
                        ->name('users.roles');
                    Route::post('/users/{id}/roles/assign', [AdminController::class, 'assignRoleToUser'])
                        ->whereNumber('id')
                        ->name('users.roles.assign');
                    Route::delete('/users/{id}/roles/remove', [AdminController::class, 'removeRoleFromUser'])
                        ->whereNumber('id')
                        ->name('users.roles.remove');
                    Route::put('/users/{id}/roles/sync', [AdminController::class, 'syncUserRoles'])
                        ->whereNumber('id')
                        ->name('users.roles.sync');
                    Route::get('/system-stats', [AdminController::class, 'getSystemStats'])->name('system-stats');
                    Route::get('/test-integrations', [AdminController::class, 'testIntegrations'])->name('test-integrations');
                    Route::get('/google-drive-stats', [AdminController::class, 'getGoogleDriveStats'])->name('google-drive-stats');
                });

            // System Information Routes (Super Admin only)
            Route::middleware('role:super_admin')
                ->prefix('system')
                ->name('system.')
                ->group(function () {
                    Route::get('/phpinfo', function () {
                        return view('info.phpinfo');
                    });
                });
        });

    // Customer Routes
    Route::prefix('customers')->name('customers.')->group(function () {

        Route::middleware('role:sk,sr,gas_in,cgp,admin,super_admin,tracer')->group(function () {
            Route::get('/', [CalonPelangganController::class, 'index'])->name('index');
            Route::get('/stats/json', [CalonPelangganController::class, 'getStats'])->name('stats');
            Route::get('/validate-reff/{reffId}', [CalonPelangganController::class, 'validateReff'])
                ->where('reffId', '[A-Z0-9\-]+')->name('validate-reff');

            // BA Gas In Routes
            Route::get('/get-all-ids', [CalonPelangganController::class, 'getAllIds'])
                ->name('get-all-ids');
            Route::get('/download-bulk-ba', [CalonPelangganController::class, 'downloadBulkBeritaAcara'])
                ->name('download-bulk-ba');
            Route::get('/{customer}/berita-acara/preview', [CalonPelangganController::class, 'previewBeritaAcara'])
                ->name('berita-acara.preview');
            Route::get('/{customer}/berita-acara', [CalonPelangganController::class, 'downloadBeritaAcara'])
                ->name('berita-acara');

            Route::get('/{reffId}', [CalonPelangganController::class, 'show'])
                ->where('reffId', '[A-Z0-9\-]+')->name('show');
        });

        Route::middleware('role:admin,tracer,super_admin')->group(function () {
            Route::get('/create', [CalonPelangganController::class, 'create'])->name('create');
            Route::post('/', [CalonPelangganController::class, 'store'])->name('store');
            Route::get('/{reffId}/edit', [CalonPelangganController::class, 'edit'])->where('reffId', '[A-Z0-9\-]+')->name('edit');
            Route::put('/{reffId}', [CalonPelangganController::class, 'update'])->where('reffId', '[A-Z0-9\-]+')->name('update');

            Route::post('/{reffId}/validate', [CalonPelangganController::class, 'validateCustomer'])
                ->where('reffId', '[A-Z0-9\-]+')->name('validate');
            Route::post('/{reffId}/reject', [CalonPelangganController::class, 'rejectCustomer'])
                ->where('reffId', '[A-Z0-9\-]+')->name('reject');

            // Import Data Calon Pelanggan (Bulk Update)
            Route::get('/import-bulk-data', [CalonPelangganController::class, 'importBulkDataForm'])->name('import-bulk-data.form');
            Route::post('/import-bulk-data', [CalonPelangganController::class, 'importBulkData'])->name('import-bulk-data');
        });
    });

    // SK Module Routes
    Route::prefix('sk')
        ->name('sk.')
        ->middleware('role:sk,tracer,admin,super_admin')
        ->group(function () {
            Route::get('/', [SkDataController::class, 'index'])->name('index');
            Route::get('/create', [SkDataController::class, 'create'])
                ->middleware('customer.validated:sk')
                ->name('create');
            Route::post('/', [SkDataController::class, 'store'])
                ->middleware('customer.validated:sk')
                ->name('store');
            Route::get('/{sk}', [SkDataController::class, 'show'])
                ->whereNumber('sk')
                ->name('show');
            Route::get('/{sk}/edit', [SkDataController::class, 'edit'])
                ->whereNumber('sk')
                ->name('edit');
            Route::put('/{sk}', [SkDataController::class, 'update'])
                ->whereNumber('sk')
                ->name('update');
            Route::delete('/{sk}', [SkDataController::class, 'destroy'])
                ->whereNumber('sk')
                ->name('destroy');

            // Photo Management
            Route::post('/photos/precheck-generic', [SkDataController::class, 'precheckGeneric'])->name('photos.precheck-generic');
            Route::post('/{sk}/photos', [SkDataController::class, 'uploadAndValidate'])
                ->whereNumber('sk')
                ->name('photos.upload');
            Route::post('/{sk}/photos/draft', [SkDataController::class, 'uploadDraft'])
                ->whereNumber('sk')
                ->name('photos.upload-draft');
            Route::get('/{sk}/ready-status', [SkDataController::class, 'readyStatus'])
                ->whereNumber('sk')
                ->name('ready-status');

            // Workflow Actions
            Route::post('/{sk}/approve-tracer', [SkDataController::class, 'approveTracer'])
                ->whereNumber('sk')
                ->name('approve-tracer');
            Route::post('/{sk}/reject-tracer', [SkDataController::class, 'rejectTracer'])
                ->whereNumber('sk')
                ->name('reject-tracer');
            Route::post('/{sk}/approve-cgp', [SkDataController::class, 'approveCgp'])
                ->whereNumber('sk')
                ->name('approve-cgp');
            Route::post('/{sk}/reject-cgp', [SkDataController::class, 'rejectCgp'])
                ->whereNumber('sk')
                ->name('reject-cgp');
            Route::post('/{sk}/schedule', [SkDataController::class, 'schedule'])
                ->whereNumber('sk')
                ->name('schedule');
            Route::post('/{sk}/complete', [SkDataController::class, 'complete'])
                ->whereNumber('sk')
                ->name('complete');

            // Generate Berita Acara
            Route::get('/{sk}/berita-acara', [SkDataController::class, 'generateBeritaAcara'])
                ->whereNumber('sk')
                ->name('berita-acara');

            // Rejection Details
            Route::get('/{sk}/rejection-details', [SkDataController::class, 'getRejectionDetails'])
                ->whereNumber('sk')
                ->name('rejection-details');

            // Incomplete Details
            Route::get('/{sk}/incomplete-details', [SkDataController::class, 'getIncompleteDetails'])
                ->whereNumber('sk')
                ->name('incomplete-details');

            // Find by Reference ID
            Route::get('/by-reff/{reffId}', [SkDataController::class, 'redirectByReff'])
                ->where('reffId', '[A-Za-z0-9\-]+')
                ->name('by-reff');
        });

    // SR Module Routes
    Route::prefix('sr')
        ->name('sr.')
        ->middleware('role:sr,tracer,admin,super_admin')
        ->group(function () {
            Route::get('/', [SrDataController::class, 'index'])->name('index');
            Route::get('/create', [SrDataController::class, 'create'])->name('create');
            Route::post('/', [SrDataController::class, 'store'])->name('store');
            Route::get('/{sr}', [SrDataController::class, 'show'])
                ->whereNumber('sr')
                ->name('show');
            Route::get('/{sr}/edit', [SrDataController::class, 'edit'])
                ->whereNumber('sr')
                ->name('edit');
            Route::put('/{sr}', [SrDataController::class, 'update'])
                ->whereNumber('sr')
                ->name('update');
            Route::delete('/{sr}', [SrDataController::class, 'destroy'])
                ->whereNumber('sr')
                ->name('destroy');

            // Photo Management - DIPERBAIKI: gunakan SrDataController
            Route::post('/photos/precheck-generic', [SrDataController::class, 'precheckGeneric'])->name('photos.precheck-generic');
            Route::post('/{sr}/photos', [SrDataController::class, 'uploadAndValidate'])
                ->whereNumber('sr')
                ->name('photos.upload');
            Route::post('/{sr}/photos/draft', [SrDataController::class, 'uploadDraft'])
                ->whereNumber('sr')
                ->name('photos.upload-draft');
            Route::get('/{sr}/ready-status', [SrDataController::class, 'readyStatus'])
                ->whereNumber('sr')
                ->name('ready-status');

            // Workflow Actions
            Route::post('/{sr}/approve-tracer', [SrDataController::class, 'approveTracer'])
                ->whereNumber('sr')
                ->name('approve-tracer');
            Route::post('/{sr}/reject-tracer', [SrDataController::class, 'rejectTracer'])
                ->whereNumber('sr')
                ->name('reject-tracer');
            Route::post('/{sr}/approve-cgp', [SrDataController::class, 'approveCgp'])
                ->whereNumber('sr')
                ->name('approve-cgp');
            Route::post('/{sr}/reject-cgp', [SrDataController::class, 'rejectCgp'])
                ->whereNumber('sr')
                ->name('reject-cgp');
            Route::post('/{sr}/schedule', [SrDataController::class, 'schedule'])
                ->whereNumber('sr')
                ->name('schedule');
            Route::post('/{sr}/complete', [SrDataController::class, 'complete'])
                ->whereNumber('sr')
                ->name('complete');

            // Generate Berita Acara
            Route::get('/{sr}/berita-acara', [SrDataController::class, 'generateBeritaAcara'])
                ->whereNumber('sr')
                ->name('berita-acara');

            // Rejection Details
            Route::get('/{sr}/rejection-details', [SrDataController::class, 'getRejectionDetails'])
                ->whereNumber('sr')
                ->name('rejection-details');

            // Find by Reference ID
            Route::get('/by-reff/{reffId}', [SrDataController::class, 'redirectByReff'])
                ->where('reffId', '[A-Za-z0-9\-]+')
                ->name('by-reff');
        });

    // Gas In Module Routes
    Route::prefix('gas-in')
        ->name('gas-in.')
        ->middleware('role:gas_in,tracer,admin,super_admin')
        ->group(function () {
            Route::get('/', [GasInDataController::class, 'index'])->name('index');
            Route::get('/create', [GasInDataController::class, 'create'])->name('create');
            Route::post('/', [GasInDataController::class, 'store'])->name('store');
            Route::get('/{gasIn}', [GasInDataController::class, 'show'])
                ->whereNumber('gasIn')
                ->name('show');
            Route::get('/{gasIn}/edit', [GasInDataController::class, 'edit'])
                ->whereNumber('gasIn')
                ->name('edit');
            Route::put('/{gasIn}', [GasInDataController::class, 'update'])
                ->whereNumber('gasIn')
                ->name('update');
            Route::delete('/{gasIn}', [GasInDataController::class, 'destroy'])
                ->whereNumber('gasIn')
                ->name('destroy');
            Route::get('/{gasIn}/rejection-details', [GasInDataController::class, 'getRejectionDetails'])
                ->whereNumber('gasIn')
                ->name('rejection-details');

            // Photo Management - DIPERBAIKI: gunakan GasInDataController
            Route::post('/photos/precheck-generic', [GasInDataController::class, 'precheckGeneric'])->name('photos.precheck-generic');
            Route::post('/{gasIn}/photos', [GasInDataController::class, 'uploadAndValidate'])
                ->whereNumber('gasIn')
                ->name('photos.upload');
            Route::post('/{gasIn}/photos/draft', [GasInDataController::class, 'uploadDraft'])
                ->whereNumber('gasIn')
                ->name('photos.upload-draft');
            Route::get('/{gasIn}/ready-status', [GasInDataController::class, 'readyStatus'])
                ->whereNumber('gasIn')
                ->name('ready-status');

            // Workflow Actions
            Route::post('/{gasIn}/approve-tracer', [GasInDataController::class, 'approveTracer'])
                ->whereNumber('gasIn')
                ->name('approve-tracer');
            Route::post('/{gasIn}/reject-tracer', [GasInDataController::class, 'rejectTracer'])
                ->whereNumber('gasIn')
                ->name('reject-tracer');
            Route::post('/{gasIn}/approve-cgp', [GasInDataController::class, 'approveCgp'])
                ->whereNumber('gasIn')
                ->name('approve-cgp');
            Route::post('/{gasIn}/reject-cgp', [GasInDataController::class, 'rejectCgp'])
                ->whereNumber('gasIn')
                ->name('reject-cgp');
            Route::post('/{gasIn}/schedule', [GasInDataController::class, 'schedule'])
                ->whereNumber('gasIn')
                ->name('schedule');
            Route::post('/{gasIn}/complete', [GasInDataController::class, 'complete'])
                ->whereNumber('gasIn')
                ->name('complete');

            // Generate Berita Acara
            Route::get('/{gasIn}/berita-acara/preview', [GasInDataController::class, 'previewBeritaAcara'])
                ->whereNumber('gasIn')
                ->name('berita-acara.preview');
            Route::get('/{gasIn}/berita-acara', [GasInDataController::class, 'generateBeritaAcara'])
                ->whereNumber('gasIn')
                ->name('berita-acara');
            Route::get('/download-bulk-ba', [GasInDataController::class, 'downloadBulkBeritaAcara'])
                ->name('download-bulk-ba');

            // Download Foto Regulator (MGRT) - Batch dengan filter tanggal
            Route::get('/preview-foto-regulator', [GasInDataController::class, 'previewFotoRegulator'])
                ->name('preview-foto-regulator');
            Route::get('/download-foto-regulator', [GasInDataController::class, 'downloadFotoRegulator'])
                ->name('download-foto-regulator');

            // Download Single Foto MGRT - Direct download by clicking MGRT number
            Route::get('/download-single-foto-mgrt', [GasInDataController::class, 'downloadSingleFotoMGRT'])
                ->name('download-single-foto-mgrt');

            // Export to Excel
            Route::get('/preview-export-excel', [GasInDataController::class, 'previewExportExcel'])
                ->name('preview-export-excel');
            Route::get('/export-excel', [GasInDataController::class, 'exportExcel'])
                ->name('export-excel');

            // Find by Reference ID
            Route::get('/by-reff/{reffId}', [GasInDataController::class, 'redirectByReff'])
                ->where('reffId', '[A-Za-z0-9\-]+')
                ->name('by-reff');
        });

    // HSE Module Routes
    Route::prefix('hse')
        ->name('hse.')
        ->middleware('role:admin,super_admin,hse')
        ->group(function () {
            // Daily Report CRUD
            Route::get('/daily-reports', [HseDailyReportController::class, 'index'])->name('daily-reports.index');
            Route::get('/daily-reports/create', [HseDailyReportController::class, 'create'])->name('daily-reports.create');
            Route::post('/daily-reports', [HseDailyReportController::class, 'store'])->name('daily-reports.store');
            Route::get('/daily-reports/{id}', [HseDailyReportController::class, 'show'])
                ->whereNumber('id')
                ->name('daily-reports.show');
            Route::get('/daily-reports/{id}/edit', [HseDailyReportController::class, 'edit'])
                ->whereNumber('id')
                ->name('daily-reports.edit');
            Route::put('/daily-reports/{id}', [HseDailyReportController::class, 'update'])
                ->whereNumber('id')
                ->name('daily-reports.update');
            Route::delete('/daily-reports/{id}', [HseDailyReportController::class, 'destroy'])
                ->whereNumber('id')
                ->name('daily-reports.destroy');

            // Workflow Actions
            Route::post('/daily-reports/{id}/submit', [HseDailyReportController::class, 'submit'])
                ->whereNumber('id')
                ->name('daily-reports.submit');
            Route::post('/daily-reports/{id}/approve', [HseDailyReportController::class, 'approve'])
                ->whereNumber('id')
                ->name('daily-reports.approve');
            Route::post('/daily-reports/{id}/reject', [HseDailyReportController::class, 'reject'])
                ->whereNumber('id')
                ->name('daily-reports.reject');

            // Photo Management
            Route::post('/daily-reports/{id}/photos', [HseDailyReportController::class, 'uploadPhoto'])
                ->whereNumber('id')
                ->name('daily-reports.photos.upload');
            Route::delete('/daily-reports/{id}/photos/{photoId}', [HseDailyReportController::class, 'deletePhoto'])
                ->whereNumber('id')
                ->whereNumber('photoId')
                ->name('daily-reports.photos.delete');

            // PDF Export
            Route::get('/daily-reports/{id}/pdf', [HseDailyReportController::class, 'exportDailyPdf'])
                ->whereNumber('id')
                ->name('daily-reports.pdf');
            Route::get('/reports/weekly-pdf', [HseDailyReportController::class, 'exportWeeklyPdf'])
                ->name('reports.weekly-pdf');
            Route::get('/reports/monthly-pdf', [HseDailyReportController::class, 'exportMonthlyPdf'])
                ->name('reports.monthly-pdf');
        });

    // Jalur Module Routes
    Route::prefix('jalur')
        ->name('jalur.')
        ->middleware('role:jalur,admin,super_admin')
        ->group(function () {
            // Main Jalur Dashboard & Reports
            Route::get('/', [JalurController::class, 'index'])->name('index');
            Route::get('/dashboard', [JalurController::class, 'dashboard'])->name('dashboard');
            Route::get('/reports', [JalurController::class, 'reports'])->name('reports');
            Route::get('/reports/data', [JalurController::class, 'getReportData'])->name('reports.data');

            // Cluster Management (Perencanaan)
            Route::prefix('clusters')
                ->name('clusters.')
                ->group(function () {
                    Route::get('/', [JalurClusterController::class, 'index'])->name('index');
                    Route::get('/create', [JalurClusterController::class, 'create'])->name('create');
                    Route::post('/', [JalurClusterController::class, 'store'])->name('store');
                    Route::get('/{cluster}', [JalurClusterController::class, 'show'])
                        ->name('show');
                    Route::get('/{cluster}/edit', [JalurClusterController::class, 'edit'])
                        ->name('edit');
                    Route::put('/{cluster}', [JalurClusterController::class, 'update'])
                        ->name('update');
                    Route::delete('/{cluster}', [JalurClusterController::class, 'destroy'])
                        ->name('destroy');
                    Route::patch('/{cluster}/toggle', [JalurClusterController::class, 'toggleStatus'])
                        ->name('toggle');

                    // API endpoints
                    Route::prefix('api')
                        ->name('api.')
                        ->group(function () {
                            Route::get('/', [JalurClusterController::class, 'apiIndex'])->name('index');
                            Route::get('/{cluster}/line-numbers', [JalurClusterController::class, 'getLineNumbers'])
                                ->name('line-numbers');
                        });
                });

            // Line Number Management
            Route::prefix('line-numbers')
                ->name('line-numbers.')
                ->group(function () {
                    Route::get('/', [JalurLineNumberController::class, 'index'])->name('index');
                    Route::get('/create', [JalurLineNumberController::class, 'create'])->name('create');
                    Route::post('/', [JalurLineNumberController::class, 'store'])->name('store');
                    Route::get('/{lineNumber}', [JalurLineNumberController::class, 'show'])
                        ->name('show');
                    Route::get('/{lineNumber}/edit', [JalurLineNumberController::class, 'edit'])
                        ->name('edit');
                    Route::put('/{lineNumber}', [JalurLineNumberController::class, 'update'])
                        ->name('update');
                    Route::delete('/{lineNumber}', [JalurLineNumberController::class, 'destroy'])
                        ->name('destroy');
                    Route::patch('/{lineNumber}/toggle', [JalurLineNumberController::class, 'toggleStatus'])
                        ->name('toggle');
                    Route::put('/{lineNumber}/mc100', [JalurLineNumberController::class, 'updateMC100'])
                        ->name('update-mc100');

                    // API endpoints
                    Route::prefix('api')
                        ->name('api.')
                        ->group(function () {
                            Route::get('/', [JalurLineNumberController::class, 'apiIndex'])->name('index');
                            Route::get('/{lineNumber}/stats', [JalurLineNumberController::class, 'getStats'])
                                ->name('stats');
                        });
                });

            // Lowering Data Management
            Route::prefix('lowering')
                ->name('lowering.')
                ->group(function () {
                    Route::get('/', [JalurLoweringController::class, 'index'])->name('index');
                    Route::get('/create', [JalurLoweringController::class, 'create'])->name('create');
                    Route::post('/', [JalurLoweringController::class, 'store'])->name('store');

                    // Import/Export Routes - MUST BE BEFORE {lowering} parameter routes
                    Route::prefix('import')
                        ->name('import.')
                        ->group(function () {
                            Route::get('/', [JalurLoweringImportController::class, 'index'])
                                ->name('index');
                            Route::get('/template', [JalurLoweringImportController::class, 'downloadTemplate'])
                                ->name('template');
                            Route::post('/preview', [JalurLoweringImportController::class, 'preview'])
                                ->name('preview');
                            Route::post('/', [JalurLoweringImportController::class, 'import'])
                                ->name('execute');
                        });

                    // API endpoints - Also before {lowering} parameter routes
                    Route::get('/api/line-numbers', [JalurLoweringController::class, 'getLineNumbers'])
                        ->name('api.line-numbers');
                    Route::get('/api/check-line-availability', [JalurLoweringController::class, 'checkLineNumberAvailability'])
                        ->name('api.check-line-availability');

                    // Resource routes with {lowering} parameter - MUST BE LAST
                    Route::get('/{lowering}', [JalurLoweringController::class, 'show'])
                        ->name('show');
                    Route::get('/{lowering}/edit', [JalurLoweringController::class, 'edit'])
                        ->name('edit');
                    Route::put('/{lowering}', [JalurLoweringController::class, 'update'])
                        ->name('update');
                    Route::delete('/{lowering}', [JalurLoweringController::class, 'destroy'])
                        ->name('destroy');

                    // Photo upload
                    Route::post('/{lowering}/photos', [JalurLoweringController::class, 'uploadPhoto'])
                        ->name('photos.upload');

                    // Approval Actions
                    Route::post('/{lowering}/approve-tracer', [JalurLoweringController::class, 'approveByTracer'])
                        ->name('approve-tracer');
                    Route::post('/{lowering}/reject-tracer', [JalurLoweringController::class, 'rejectByTracer'])
                        ->name('reject-tracer');
                    Route::post('/{lowering}/approve-cgp', [JalurLoweringController::class, 'approveByCgp'])
                        ->name('approve-cgp');
                    Route::post('/{lowering}/reject-cgp', [JalurLoweringController::class, 'rejectByCgp'])
                        ->name('reject-cgp');
                });

            // Joint Data Management
            Route::prefix('joint')
                ->name('joint.')
                ->group(function () {
                    Route::get('/', [JalurJointController::class, 'index'])->name('index');
                    Route::get('/create', [JalurJointController::class, 'create'])->name('create');
                    Route::post('/', [JalurJointController::class, 'store'])->name('store');

                    // Import/Export Routes - MUST BE BEFORE {joint} parameter routes
                    Route::prefix('import')
                        ->name('import.')
                        ->group(function () {
                            Route::get('/', [\App\Http\Controllers\Web\JalurJointImportController::class, 'index'])
                                ->name('index');
                            Route::get('/template', [\App\Http\Controllers\Web\JalurJointImportController::class, 'downloadTemplate'])
                                ->name('template');
                            Route::post('/preview', [\App\Http\Controllers\Web\JalurJointImportController::class, 'preview'])
                                ->name('preview');
                            Route::get('/preview', function () {
                                return redirect()->route('jalur.joint.import.index')
                                    ->with('info', 'Silakan upload file untuk melakukan preview.');
                            });
                            Route::post('/', [\App\Http\Controllers\Web\JalurJointImportController::class, 'import'])
                                ->name('execute');
                            Route::get('/execute', function () {
                                return redirect()->route('jalur.joint.import.index')
                                    ->with('info', 'Silakan upload file untuk melakukan import.');
                            });
                        });

                    // Resource routes with {joint} parameter - MUST BE AFTER import routes
                    Route::get('/{joint}', [JalurJointController::class, 'show'])
                        ->name('show');
                    Route::get('/{joint}/edit', [JalurJointController::class, 'edit'])
                        ->name('edit');
                    Route::put('/{joint}', [JalurJointController::class, 'update'])
                        ->name('update');
                    Route::delete('/{joint}', [JalurJointController::class, 'destroy'])
                        ->name('destroy');

                    // Photo upload
                    Route::post('/{joint}/photos', [JalurJointController::class, 'uploadPhoto'])
                        ->name('photos.upload');

                    // Approval Actions
                    Route::post('/{joint}/approve-tracer', [JalurJointController::class, 'approveByTracer'])
                        ->name('approve-tracer');
                    Route::post('/{joint}/reject-tracer', [JalurJointController::class, 'rejectByTracer'])
                        ->name('reject-tracer');
                    Route::post('/{joint}/approve-cgp', [JalurJointController::class, 'approveByCgp'])
                        ->name('approve-cgp');
                    Route::post('/{joint}/reject-cgp', [JalurJointController::class, 'rejectByCgp'])
                        ->name('reject-cgp');

                    // API endpoints
                    Route::prefix('api')
                        ->name('api.')
                        ->group(function () {
                            Route::get('/fitting-types', [JalurJointController::class, 'getFittingTypes'])
                                ->name('fitting-types');
                            Route::get('/available-diameters', [JalurJointController::class, 'getAvailableDiameters'])
                                ->name('available-diameters');
                            Route::get('/line-numbers', [JalurJointController::class, 'getLineNumbers'])
                                ->name('line-numbers');
                            Route::get('/available-joint-numbers', [JalurJointController::class, 'getAvailableJointNumbers'])
                                ->name('available-joint-numbers');
                            Route::get('/check-joint-status', [JalurJointController::class, 'checkJointNumberStatus'])
                                ->name('check-joint-status');
                            Route::get('/check-joint-availability', [JalurJointController::class, 'checkJointAvailability'])
                                ->name('check-joint-availability');
                        });
                });

            // Fitting Types Management (Admin only)
            Route::prefix('fitting-types')
                ->name('fitting-types.')
                ->middleware('role:admin,super_admin')
                ->group(function () {
                    Route::get('/', [JalurFittingTypeController::class, 'index'])->name('index');
                    Route::get('/create', [JalurFittingTypeController::class, 'create'])->name('create');
                    Route::post('/', [JalurFittingTypeController::class, 'store'])->name('store');
                    Route::get('/{fittingType}', [JalurFittingTypeController::class, 'show'])
                        ->name('show');
                    Route::get('/{fittingType}/edit', [JalurFittingTypeController::class, 'edit'])
                        ->name('edit');
                    Route::put('/{fittingType}', [JalurFittingTypeController::class, 'update'])
                        ->name('update');
                    Route::delete('/{fittingType}', [JalurFittingTypeController::class, 'destroy'])
                        ->name('destroy');
                    Route::patch('/{fittingType}/toggle', [JalurFittingTypeController::class, 'toggleStatus'])
                        ->name('toggle-status');
                });

            // Joint Numbers Management (Admin only)
            Route::prefix('joint-numbers')
                ->name('joint-numbers.')
                ->middleware('role:jalur,admin,super_admin')
                ->group(function () {
                    Route::get('/', [JalurJointNumberController::class, 'index'])->name('index');
                    Route::get('/create', [JalurJointNumberController::class, 'create'])->name('create');
                    Route::post('/', [JalurJointNumberController::class, 'store'])->name('store');
                    Route::get('/{jointNumber}', [JalurJointNumberController::class, 'show'])
                        ->name('show');
                    Route::get('/{jointNumber}/edit', [JalurJointNumberController::class, 'edit'])
                        ->name('edit');
                    Route::put('/{jointNumber}', [JalurJointNumberController::class, 'update'])
                        ->name('update');
                    Route::delete('/{jointNumber}', [JalurJointNumberController::class, 'destroy'])
                        ->name('destroy');
                    Route::patch('/{jointNumber}/toggle', [JalurJointNumberController::class, 'toggleStatus'])
                        ->name('toggle-status');
                    Route::post('/batch-create', [JalurJointNumberController::class, 'batchCreate'])
                        ->name('batch-create');

                    // API routes
                    Route::prefix('api')
                        ->name('api.')
                        ->group(function () {
                            Route::get('/available-joint-numbers', [JalurJointNumberController::class, 'getAvailableJointNumbers'])
                                ->name('available-joint-numbers');
                        });
                });
        });

    // Photo Approval Management Routes
    Route::prefix('photo-approvals')
        ->name('photos.')
        ->middleware('role:tracer,admin,super_admin')
        ->group(function () {
            Route::get('/', [PhotoApprovalController::class, 'index'])->name('index');
            Route::get('/stats', [PhotoApprovalController::class, 'getStats'])->name('stats');
            Route::get('/pending', [PhotoApprovalController::class, 'getPendingApprovals'])->name('pending');
            Route::get('/{id}', [PhotoApprovalController::class, 'show'])
                ->whereNumber('id')
                ->name('show');

            // Approval Actions
            Route::post('/{id}/tracer/approve', [PhotoApprovalController::class, 'approveByTracer'])
                ->whereNumber('id')
                ->name('tracer.approve');
            Route::post('/{id}/tracer/reject', [PhotoApprovalController::class, 'rejectByTracer'])
                ->whereNumber('id')
                ->name('tracer.reject');
            Route::post('/{id}/cgp/approve', [PhotoApprovalController::class, 'approveByCgp'])
                ->whereNumber('id')
                ->name('cgp.approve');
            Route::post('/{id}/cgp/reject', [PhotoApprovalController::class, 'rejectByCgp'])
                ->whereNumber('id')
                ->name('cgp.reject');
            Route::post('/batch', [PhotoApprovalController::class, 'batchApprove'])->name('batch');
        });

    // Tracer Approval Interface Routes (untuk role admin sebagai Tracer Internal)
    Route::prefix('approvals/tracer')
        ->name('approvals.tracer.')
        ->middleware('role:tracer,admin,super_admin')
        ->group(function () {
            Route::get('/', [TracerApprovalController::class, 'index'])->name('index');
            Route::get('/customers', [TracerApprovalController::class, 'customers'])->name('customers');
            Route::get('/customers/{reffId}/photos', [TracerApprovalController::class, 'customerPhotos'])
                ->where('reffId', '[A-Za-z0-9\-]+')
                ->name('photos');
            Route::get('/jalur-photos', [TracerApprovalController::class, 'jalurPhotos'])->name('jalur-photos');

            // Photo Actions
            Route::post('/photos/approve', [TracerApprovalController::class, 'approvePhoto'])->name('approve-photo');
            Route::post('/photos/replace', [TracerApprovalController::class, 'replacePhoto'])->name('replace-photo');
            Route::post('/modules/approve', [TracerApprovalController::class, 'approveModule'])->name('approve-module');
            Route::post('/modules/reject', [TracerApprovalController::class, 'rejectModule'])->name('reject-module');
            Route::post('/ai-review', [TracerApprovalController::class, 'aiReview'])->name('ai-review');

            // Jalur Approval Routes (3-Level: Cluster -> Line -> Evidence)
            Route::prefix('jalur')->name('jalur.')->group(function () {
                // Level 1: Cluster Selection
                Route::get('/clusters', [TracerJalurApprovalController::class, 'clusters'])->name('clusters');

                // Level 2: Line Selection (per Cluster)
                Route::get('/clusters/{clusterId}/lines', [TracerJalurApprovalController::class, 'lines'])->name('lines');

                // Level 3: Evidence Review (per Line)
                Route::get('/lines/{lineId}/evidence', [TracerJalurApprovalController::class, 'evidence'])->name('evidence');

                // Level 3: Evidence Review (per Joint)
                Route::get('/joints/{jointId}/evidence', [TracerJalurApprovalController::class, 'jointEvidence'])->name('joint-evidence');

                // Approval Actions
                Route::post('/approve-photo', [TracerJalurApprovalController::class, 'approvePhoto'])->name('approve-photo');
                Route::post('/reject-photo', [TracerJalurApprovalController::class, 'rejectPhoto'])->name('reject-photo');
                Route::post('/approve-date', [TracerJalurApprovalController::class, 'approveDatePhotos'])->name('approve-date');
                Route::post('/approve-line', [TracerJalurApprovalController::class, 'approveLine'])->name('approve-line');
                Route::post('/replace-photo', [TracerJalurApprovalController::class, 'replacePhoto'])->name('replace-photo');
            });

        });

    // CGP Approval Interface Routes (untuk role CGP & Super Admin + Jalur for specific routes)
    Route::prefix('approvals/cgp')
        ->name('approvals.cgp.')
        ->middleware('role:cgp,super_admin')
        ->group(function () {
            Route::get('/', [CgpApprovalController::class, 'index'])->name('index');
            Route::get('/customers', [CgpApprovalController::class, 'customers'])->name('customers');
            Route::get('/customers/{reffId}/photos', [CgpApprovalController::class, 'customerPhotos'])
                ->where('reffId', '[A-Za-z0-9\-]+')
                ->name('customer-photos');

            // Photo Actions (Customers only)
            Route::post('/modules/approve', [CgpApprovalController::class, 'approveModule'])->name('approve-module');

            // Slot Completion Check
            Route::get('/slot-completion', [CgpApprovalController::class, 'checkSlotCompletion'])->name('check-slot-completion');
        });

    // CGP Jalur Photos - Accessible by CGP, Super Admin, AND Jalur Role
    Route::prefix('approvals/cgp')
        ->name('approvals.cgp.')
        ->middleware('role:cgp,super_admin,jalur')
        ->group(function () {
            Route::get('/jalur-photos', [CgpApprovalController::class, 'jalurPhotos'])->name('jalur-photos');
            Route::post('/photos/approve', [CgpApprovalController::class, 'approvePhoto'])->name('approve-photo');
            Route::post('/photos/revert', [CgpApprovalController::class, 'revertApproval'])->name('revert-approval');

            // CGP Jalur Approval Routes (3-Level: Cluster -> Line -> Evidence)
            Route::prefix('jalur')->name('jalur.')->group(function () {
                // Level 1: Cluster Selection
                Route::get('/clusters', [App\Http\Controllers\Web\CgpJalurApprovalController::class, 'clusters'])->name('clusters');

                // Level 2: Line Selection (per Cluster)
                Route::get('/clusters/{clusterId}/lines', [App\Http\Controllers\Web\CgpJalurApprovalController::class, 'lines'])->name('lines');

                // Level 3: Evidence Review (per Line)
                Route::get('/lines/{lineId}/evidence', [App\Http\Controllers\Web\CgpJalurApprovalController::class, 'evidence'])->name('evidence');

                // Level 3: Joint Evidence Review (per Joint)
                Route::get('/joints/{jointId}/evidence', [App\Http\Controllers\Web\CgpJalurApprovalController::class, 'jointEvidence'])->name('joint-evidence');

                // Approval Actions
                Route::post('/approve-photo', [App\Http\Controllers\Web\CgpJalurApprovalController::class, 'approvePhoto'])->name('approve-photo');
                Route::post('/reject-photo', [App\Http\Controllers\Web\CgpJalurApprovalController::class, 'rejectPhoto'])->name('reject-photo');
                Route::post('/revert-photo', [App\Http\Controllers\Web\CgpJalurApprovalController::class, 'revertPhoto'])->name('revert-photo');
                Route::post('/approve-date', [App\Http\Controllers\Web\CgpJalurApprovalController::class, 'approveDatePhotos'])->name('approve-date');
                Route::post('/approve-line', [App\Http\Controllers\Web\CgpJalurApprovalController::class, 'approveLine'])->name('approve-line');
                Route::post('/replace-photo', [App\Http\Controllers\Web\CgpJalurApprovalController::class, 'replacePhoto'])->name('replace-photo');

                // Batch Joint Approval Actions
                Route::post('/approve-joint-by-date', [App\Http\Controllers\Web\CgpJalurApprovalController::class, 'approveJointByDate'])->name('approve-joint-by-date');
                Route::post('/approve-joint-by-line', [App\Http\Controllers\Web\CgpJalurApprovalController::class, 'approveJointByLine'])->name('approve-joint-by-line');
            });
        });

    // Notification Routes
    Route::prefix('notifications')
        ->name('notifications.')
        ->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('/types', [NotificationController::class, 'getNotificationTypes'])->name('types');
            Route::get('/stats', [NotificationController::class, 'getStats'])->name('stats');
            Route::get('/{id}', [NotificationController::class, 'show'])
                ->whereNumber('id')
                ->name('show');
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])
                ->whereNumber('id')
                ->name('read');
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
            Route::delete('/{id}', [NotificationController::class, 'destroy'])
                ->whereNumber('id')
                ->name('destroy');
            Route::post('/bulk-delete', [NotificationController::class, 'bulkDelete'])->name('bulk-delete');
            Route::post('/bulk-mark-read', [NotificationController::class, 'bulkMarkAsRead'])->name('bulk-mark-read');

            // Development only
            Route::post('/test', [NotificationController::class, 'createTestNotification'])
                ->name('test')
                ->middleware('env:local,development');
        });

    // Import/Export Routes
    Route::prefix('imports')
        ->name('imports.')
        ->middleware('role:admin,super_admin,tracer')
        ->group(function () {
            Route::get('/calon-pelanggan', [ImportController::class, 'formCalonPelanggan'])->name('calon-pelanggan.form');
            Route::get('/calon-pelanggan/template', [ImportController::class, 'downloadTemplateCalonPelanggan'])->name('calon-pelanggan.template');
            Route::post('/calon-pelanggan', [ImportController::class, 'importCalonPelanggan'])->name('calon-pelanggan.import');

            Route::get('/coordinates', [ImportController::class, 'formCoordinates'])->name('coordinates.form');
            Route::get('/coordinates/template', [ImportController::class, 'downloadTemplateCoordinates'])->name('coordinates.template');
            Route::post('/coordinates', [ImportController::class, 'importCoordinates'])->name('coordinates.import');

            // Evidence Import (SK/SR) - New unified import
            Route::get('/evidence', [ImportController::class, 'formEvidence'])->name('evidence.form');
            Route::get('/evidence/template', [ImportController::class, 'downloadTemplateEvidence'])->name('evidence.template');
            Route::post('/evidence', [ImportController::class, 'importEvidence'])->name('evidence.import');

            // Old SK Berita Acara routes (keep for backward compatibility)
            Route::get('/sk-berita-acara', [ImportController::class, 'formSkBeritaAcara'])->name('sk-berita-acara.form');
            Route::get('/sk-berita-acara/template', [ImportController::class, 'downloadTemplateSkBeritaAcara'])->name('sk-berita-acara.template');
            Route::post('/sk-berita-acara', [ImportController::class, 'importSkBeritaAcara'])->name('sk-berita-acara.import');

            Route::get('/report', [ImportController::class, 'downloadReport'])->name('report.download');
        });
});


// Development Routes
if (app()->environment(['local', 'development'])) {
    Route::get('/test-auth', function () {
        return response()->json([
            'authenticated' => Auth::check(),
            'user' => Auth::user(),
            'session_id' => session()->getId(),
        ]);
    });

    Route::get('/test-gdrive', function () {
        try {
            $service = new \App\Services\GoogleDriveService();
            return response()->json($service->testConnection());
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    });
}
