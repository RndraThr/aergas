<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Web\{AuthController, DashboardController, CalonPelangganController, SkDataController, SrDataController, PhotoApprovalController, NotificationController, ImportController, GasInDataController, AdminController, TracerApprovalController, CgpApprovalController, JalurController, JalurClusterController, JalurLineNumberController, JalurLoweringController, JalurJointController, JalurJointNumberController, JalurFittingTypeController, ReportDashboardController, ComprehensiveReportController, MapFeatureController};

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

    Route::middleware('role:admin,cgp,tracer,super_admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'getData'])->name('dashboard.data');
    Route::get('/dashboard/installation-trend', [DashboardController::class, 'getInstallationTrend'])->name('dashboard.installation-trend');
    Route::get('/dashboard/activity-metrics', [DashboardController::class, 'getActivityMetrics'])->name('dashboard.activity-metrics');
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
            Route::get('/{gasIn}/berita-acara', [GasInDataController::class, 'generateBeritaAcara'])
                ->whereNumber('gasIn')
                ->name('berita-acara');

            // Find by Reference ID
            Route::get('/by-reff/{reffId}', [GasInDataController::class, 'redirectByReff'])
                ->where('reffId', '[A-Za-z0-9\-]+')
                ->name('by-reff');
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

                    // API endpoints
                    Route::get('/api/line-numbers', [JalurLoweringController::class, 'getLineNumbers'])
                        ->name('api.line-numbers');
                });

            // Joint Data Management
            Route::prefix('joint')
                ->name('joint.')
                ->group(function () {
                    Route::get('/', [JalurJointController::class, 'index'])->name('index');
                    Route::get('/create', [JalurJointController::class, 'create'])->name('create');
                    Route::post('/', [JalurJointController::class, 'store'])->name('store');
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
        ->middleware('role:admin,super_admin')
        ->group(function () {
            Route::get('/', [TracerApprovalController::class, 'index'])->name('index');
            Route::get('/customers', [TracerApprovalController::class, 'customers'])->name('customers');
            Route::get('/customers/{reffId}/photos', [TracerApprovalController::class, 'customerPhotos'])
                ->where('reffId', '[A-Za-z0-9\-]+')
                ->name('photos');
            Route::get('/jalur-photos', [TracerApprovalController::class, 'jalurPhotos'])->name('jalur-photos');

            // Photo Actions
            Route::post('/photos/approve', [TracerApprovalController::class, 'approvePhoto'])->name('approve-photo');
            Route::post('/modules/approve', [TracerApprovalController::class, 'approveModule'])->name('approve-module');
            Route::post('/ai-review', [TracerApprovalController::class, 'aiReview'])->name('ai-review');

        });

    // CGP Approval Interface Routes (untuk role tracer sebagai CGP Review)
    Route::prefix('approvals/cgp')
        ->name('approvals.cgp.')
        ->middleware('role:cgp,super_admin')
        ->group(function () {
            Route::get('/', [CgpApprovalController::class, 'index'])->name('index');
            Route::get('/customers', [CgpApprovalController::class, 'customers'])->name('customers');
            Route::get('/customers/{reffId}/photos', [CgpApprovalController::class, 'customerPhotos'])
                ->where('reffId', '[A-Za-z0-9\-]+')
                ->name('customer-photos');
            Route::get('/jalur-photos', [CgpApprovalController::class, 'jalurPhotos'])->name('jalur-photos');

            // Photo Actions
            Route::post('/photos/approve', [CgpApprovalController::class, 'approvePhoto'])->name('approve-photo');
            Route::post('/modules/approve', [CgpApprovalController::class, 'approveModule'])->name('approve-module');

            // Slot Completion Check
            Route::get('/slot-completion', [CgpApprovalController::class, 'checkSlotCompletion'])->name('check-slot-completion');
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
