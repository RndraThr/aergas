<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Web\{
   AuthController,
   DashboardController,
   CalonPelangganController,
   SkDataController,
   SrDataController,
   PhotoApprovalController,
   NotificationController,
   ImportController,
   GasInDataController,
   JalurPipaDataController,
   PenyambunganPipaDataController,
   AdminController,
   TracerApprovalController,
   CgpApprovalController
};

Route::get('/', function () {
   return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::pattern('id', '[0-9]+');
Route::get('/auth/check', [AuthController::class, 'check'])->name('auth.check');

Route::middleware('guest')->group(function () {
   Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
   Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {

   Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
   Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
   Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
   Route::post('/change-password', [AuthController::class, 'changePassword'])->name('password.change');

   Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
   Route::get('/dashboard/data', [DashboardController::class, 'getData'])->name('dashboard.data');
   Route::get('/dashboard/installation-trend', [DashboardController::class, 'getInstallationTrend'])->name('dashboard.installation-trend');
   Route::get('/dashboard/activity-metrics', [DashboardController::class, 'getActivityMetrics'])->name('dashboard.activity-metrics');

   // Admin Routes
   Route::middleware(['role:super_admin,admin'])->prefix('admin')->name('admin.')->group(function () {
       Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
       Route::get('/users', [AdminController::class, 'usersIndex'])->name('users');

       Route::prefix('api')->name('api.')->group(function () {
           Route::get('/users', [AdminController::class, 'getUsers'])->name('users');
           Route::post('/users', [AdminController::class, 'createUser'])->name('users.create');
           Route::get('/users/{id}', [AdminController::class, 'getUser'])->whereNumber('id')->name('users.show');
           Route::put('/users/{id}', [AdminController::class, 'updateUser'])->whereNumber('id')->name('users.update');
           Route::patch('/users/{id}/toggle', [AdminController::class, 'toggleUserStatus'])->whereNumber('id')->name('users.toggle');
           Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->whereNumber('id')->name('users.delete');
           Route::get('/system-stats', [AdminController::class, 'getSystemStats'])->name('system-stats');
           Route::get('/test-integrations', [AdminController::class, 'testIntegrations'])->name('test-integrations');
           Route::get('/google-drive-stats', [AdminController::class, 'getGoogleDriveStats'])->name('google-drive-stats');
       });
   });

   // Customer Routes
   Route::prefix('customers')->name('customers.')->group(function () {
       Route::get('/', [CalonPelangganController::class, 'index'])->name('index');
       Route::get('/stats/json', [CalonPelangganController::class, 'getStats'])->name('stats');
       Route::get('/validate-reff/{reffId}', [CalonPelangganController::class, 'validateReff'])
           ->where('reffId', '[A-Z0-9\-]+')->name('validate-reff');
       Route::get('/{reffId}', [CalonPelangganController::class, 'show'])
           ->where('reffId', '[A-Z0-9\-]+')->name('show');

       Route::middleware('role:admin,tracer,super_admin')->group(function () {
           Route::get('/create', [CalonPelangganController::class, 'create'])->name('create');
           Route::post('/', [CalonPelangganController::class, 'store'])->name('store');
           Route::get('/{reffId}/edit', [CalonPelangganController::class, 'edit'])
               ->where('reffId', '[A-Z0-9\-]+')->name('edit');
           Route::put('/{reffId}', [CalonPelangganController::class, 'update'])
               ->where('reffId', '[A-Z0-9\-]+')->name('update');
           
           // Customer validation routes
           Route::post('/{reffId}/validate', [CalonPelangganController::class, 'validateCustomer'])
               ->where('reffId', '[A-Z0-9\-]+')->name('validate');
           Route::post('/{reffId}/reject', [CalonPelangganController::class, 'rejectCustomer'])
               ->where('reffId', '[A-Z0-9\-]+')->name('reject');
       });
   });

   // SK Module Routes
   Route::prefix('sk')->name('sk.')->middleware('role:sk,tracer,admin,super_admin')->group(function () {
       Route::get('/', [SkDataController::class, 'index'])->name('index');
       Route::get('/create', [SkDataController::class, 'create'])->middleware('customer.validated:sk')->name('create');
       Route::post('/', [SkDataController::class, 'store'])->middleware('customer.validated:sk')->name('store');
       Route::get('/{sk}', [SkDataController::class, 'show'])->whereNumber('sk')->name('show');
       Route::get('/{sk}/edit', [SkDataController::class, 'edit'])->whereNumber('sk')->name('edit');
       Route::put('/{sk}', [SkDataController::class, 'update'])->whereNumber('sk')->name('update');
       Route::delete('/{sk}', [SkDataController::class, 'destroy'])->whereNumber('sk')->name('destroy');

       // Photo Management
       Route::post('/photos/precheck-generic', [SkDataController::class, 'precheckGeneric'])->name('photos.precheck-generic');
       Route::post('/{sk}/photos', [SkDataController::class, 'uploadAndValidate'])->whereNumber('sk')->name('photos.upload');
       Route::post('/{sk}/photos/draft', [SkDataController::class, 'uploadDraft'])->whereNumber('sk')->name('photos.upload-draft');
       Route::get('/{sk}/ready-status', [SkDataController::class, 'readyStatus'])->whereNumber('sk')->name('ready-status');

       // Workflow Actions
       Route::post('/{sk}/approve-tracer', [SkDataController::class, 'approveTracer'])->whereNumber('sk')->name('approve-tracer');
       Route::post('/{sk}/reject-tracer', [SkDataController::class, 'rejectTracer'])->whereNumber('sk')->name('reject-tracer');
       Route::post('/{sk}/approve-cgp', [SkDataController::class, 'approveCgp'])->whereNumber('sk')->name('approve-cgp');
       Route::post('/{sk}/reject-cgp', [SkDataController::class, 'rejectCgp'])->whereNumber('sk')->name('reject-cgp');
       Route::post('/{sk}/schedule', [SkDataController::class, 'schedule'])->whereNumber('sk')->name('schedule');
       Route::post('/{sk}/complete', [SkDataController::class, 'complete'])->whereNumber('sk')->name('complete');

       // Find by Reference ID
       Route::get('/by-reff/{reffId}', [SkDataController::class, 'redirectByReff'])
           ->where('reffId', '[A-Za-z0-9\-]+')->name('by-reff');
   });

   // SR Module Routes
   Route::prefix('sr')->name('sr.')->middleware('role:sr,tracer,admin,super_admin')->group(function () {
       Route::get('/', [SrDataController::class, 'index'])->name('index');
       Route::get('/create', [SrDataController::class, 'create'])->name('create');
       Route::post('/', [SrDataController::class, 'store'])->name('store');
       Route::get('/{sr}', [SrDataController::class, 'show'])->whereNumber('sr')->name('show');
       Route::get('/{sr}/edit', [SrDataController::class, 'edit'])->whereNumber('sr')->name('edit');
       Route::put('/{sr}', [SrDataController::class, 'update'])->whereNumber('sr')->name('update');
       Route::delete('/{sr}', [SrDataController::class, 'destroy'])->whereNumber('sr')->name('destroy');

       // Photo Management - DIPERBAIKI: gunakan SrDataController
       Route::post('/photos/precheck-generic', [SrDataController::class, 'precheckGeneric'])->name('photos.precheck-generic');
       Route::post('/{sr}/photos', [SrDataController::class, 'uploadAndValidate'])->whereNumber('sr')->name('photos.upload');
       Route::post('/{sr}/photos/draft', [SrDataController::class, 'uploadDraft'])->whereNumber('sr')->name('photos.upload-draft');
       Route::get('/{sr}/ready-status', [SrDataController::class, 'readyStatus'])->whereNumber('sr')->name('ready-status');

       // Workflow Actions
       Route::post('/{sr}/approve-tracer', [SrDataController::class, 'approveTracer'])->whereNumber('sr')->name('approve-tracer');
       Route::post('/{sr}/reject-tracer', [SrDataController::class, 'rejectTracer'])->whereNumber('sr')->name('reject-tracer');
       Route::post('/{sr}/approve-cgp', [SrDataController::class, 'approveCgp'])->whereNumber('sr')->name('approve-cgp');
       Route::post('/{sr}/reject-cgp', [SrDataController::class, 'rejectCgp'])->whereNumber('sr')->name('reject-cgp');
       Route::post('/{sr}/schedule', [SrDataController::class, 'schedule'])->whereNumber('sr')->name('schedule');
       Route::post('/{sr}/complete', [SrDataController::class, 'complete'])->whereNumber('sr')->name('complete');

       // Find by Reference ID
       Route::get('/by-reff/{reffId}', [SrDataController::class, 'redirectByReff'])
           ->where('reffId', '[A-Za-z0-9\-]+')->name('by-reff');
   });

   // Gas In Module Routes
   Route::prefix('gas-in')->name('gas-in.')->middleware('role:gas_in,tracer,admin,super_admin')->group(function () {
       Route::get('/', [GasInDataController::class, 'index'])->name('index');
       Route::get('/create', [GasInDataController::class, 'create'])->name('create');
       Route::post('/', [GasInDataController::class, 'store'])->name('store');
       Route::get('/{gasIn}', [GasInDataController::class, 'show'])->whereNumber('gasIn')->name('show');
       Route::get('/{gasIn}/edit', [GasInDataController::class, 'edit'])->whereNumber('gasIn')->name('edit');
       Route::put('/{gasIn}', [GasInDataController::class, 'update'])->whereNumber('gasIn')->name('update');
       Route::delete('/{gasIn}', [GasInDataController::class, 'destroy'])->whereNumber('gasIn')->name('destroy');

       // Photo Management - DIPERBAIKI: gunakan GasInDataController
       Route::post('/photos/precheck-generic', [GasInDataController::class, 'precheckGeneric'])->name('photos.precheck-generic');
       Route::post('/{gasIn}/photos', [GasInDataController::class, 'uploadAndValidate'])->whereNumber('gasIn')->name('photos.upload');
       Route::post('/{gasIn}/photos/draft', [GasInDataController::class, 'uploadDraft'])->whereNumber('gasIn')->name('photos.upload-draft');
       Route::get('/{gasIn}/ready-status', [GasInDataController::class, 'readyStatus'])->whereNumber('gasIn')->name('ready-status');

       // Workflow Actions
       Route::post('/{gasIn}/approve-tracer', [GasInDataController::class, 'approveTracer'])->whereNumber('gasIn')->name('approve-tracer');
       Route::post('/{gasIn}/reject-tracer', [GasInDataController::class, 'rejectTracer'])->whereNumber('gasIn')->name('reject-tracer');
       Route::post('/{gasIn}/approve-cgp', [GasInDataController::class, 'approveCgp'])->whereNumber('gasIn')->name('approve-cgp');
       Route::post('/{gasIn}/reject-cgp', [GasInDataController::class, 'rejectCgp'])->whereNumber('gasIn')->name('reject-cgp');
       Route::post('/{gasIn}/schedule', [GasInDataController::class, 'schedule'])->whereNumber('gasIn')->name('schedule');
       Route::post('/{gasIn}/complete', [GasInDataController::class, 'complete'])->whereNumber('gasIn')->name('complete');

       // Find by Reference ID
       Route::get('/by-reff/{reffId}', [GasInDataController::class, 'redirectByReff'])
           ->where('reffId', '[A-Za-z0-9\-]+')->name('by-reff');
   });

   // Jalur Pipa Module Routes - BARU DITAMBAHKAN
//    Route::prefix('jalur-pipa')->name('jalur-pipa.')->middleware('role:jalur_pipa,tracer,admin,super_admin')->group(function () {
//        Route::get('/', [JalurPipaDataController::class, 'index'])->name('index');
//        Route::get('/create', [JalurPipaDataController::class, 'create'])->name('create');
//        Route::post('/', [JalurPipaDataController::class, 'store'])->name('store');
//        Route::get('/{jalurPipa}', [JalurPipaDataController::class, 'show'])->whereNumber('jalurPipa')->name('show');
//        Route::get('/{jalurPipa}/edit', [JalurPipaDataController::class, 'edit'])->whereNumber('jalurPipa')->name('edit');
//        Route::put('/{jalurPipa}', [JalurPipaDataController::class, 'update'])->whereNumber('jalurPipa')->name('update');
//        Route::delete('/{jalurPipa}', [JalurPipaDataController::class, 'destroy'])->whereNumber('jalurPipa')->name('destroy');

//        // Photo Management
//        Route::post('/photos/precheck-generic', [JalurPipaDataController::class, 'precheckGeneric'])->name('photos.precheck-generic');
//        Route::post('/{jalurPipa}/photos', [JalurPipaDataController::class, 'uploadAndValidate'])->whereNumber('jalurPipa')->name('photos.upload');
//        Route::post('/{jalurPipa}/photos/draft', [JalurPipaDataController::class, 'uploadDraft'])->whereNumber('jalurPipa')->name('photos.upload-draft');
//        Route::get('/{jalurPipa}/ready-status', [JalurPipaDataController::class, 'readyStatus'])->whereNumber('jalurPipa')->name('ready-status');

//        // Workflow Actions
//        Route::post('/{jalurPipa}/approve-tracer', [JalurPipaDataController::class, 'approveTracer'])->whereNumber('jalurPipa')->name('approve-tracer');
//        Route::post('/{jalurPipa}/reject-tracer', [JalurPipaDataController::class, 'rejectTracer'])->whereNumber('jalurPipa')->name('reject-tracer');
//        Route::post('/{jalurPipa}/approve-cgp', [JalurPipaDataController::class, 'approveCgp'])->whereNumber('jalurPipa')->name('approve-cgp');
//        Route::post('/{jalurPipa}/reject-cgp', [JalurPipaDataController::class, 'rejectCgp'])->whereNumber('jalurPipa')->name('reject-cgp');
//        Route::post('/{jalurPipa}/schedule', [JalurPipaDataController::class, 'schedule'])->whereNumber('jalurPipa')->name('schedule');
//        Route::post('/{jalurPipa}/complete', [JalurPipaDataController::class, 'complete'])->whereNumber('jalurPipa')->name('complete');

//        // Find by Reference ID
//        Route::get('/by-reff/{reffId}', [JalurPipaDataController::class, 'redirectByReff'])
//            ->where('reffId', '[A-Za-z0-9\-]+')->name('by-reff');
//    });

//    // Penyambungan Module Routes - BARU DITAMBAHKAN
//    Route::prefix('penyambungan')->name('penyambungan.')->middleware('role:penyambungan,tracer,admin,super_admin')->group(function () {
//        Route::get('/', [PenyambunganPipaDataController::class, 'index'])->name('index');
//        Route::get('/create', [PenyambunganPipaDataController::class, 'create'])->name('create');
//        Route::post('/', [PenyambunganPipaDataController::class, 'store'])->name('store');
//        Route::get('/{penyambungan}', [PenyambunganPipaDataController::class, 'show'])->whereNumber('penyambungan')->name('show');
//        Route::get('/{penyambungan}/edit', [PenyambunganPipaDataController::class, 'edit'])->whereNumber('penyambungan')->name('edit');
//        Route::put('/{penyambungan}', [PenyambunganPipaDataController::class, 'update'])->whereNumber('penyambungan')->name('update');
//        Route::delete('/{penyambungan}', [PenyambunganPipaDataController::class, 'destroy'])->whereNumber('penyambungan')->name('destroy');

//        // Photo Management
//        Route::post('/photos/precheck-generic', [PenyambunganPipaDataController::class, 'precheckGeneric'])->name('photos.precheck-generic');
//        Route::post('/{penyambungan}/photos', [PenyambunganPipaDataController::class, 'uploadAndValidate'])->whereNumber('penyambungan')->name('photos.upload');
//        Route::post('/{penyambungan}/photos/draft', [PenyambunganPipaDataController::class, 'uploadDraft'])->whereNumber('penyambungan')->name('photos.upload-draft');
//        Route::get('/{penyambungan}/ready-status', [PenyambunganPipaDataController::class, 'readyStatus'])->whereNumber('penyambungan')->name('ready-status');

//        // Workflow Actions
//        Route::post('/{penyambungan}/approve-tracer', [PenyambunganPipaDataController::class, 'approveTracer'])->whereNumber('penyambungan')->name('approve-tracer');
//        Route::post('/{penyambungan}/reject-tracer', [PenyambunganPipaDataController::class, 'rejectTracer'])->whereNumber('penyambungan')->name('reject-tracer');
//        Route::post('/{penyambungan}/approve-cgp', [PenyambunganPipaDataController::class, 'approveCgp'])->whereNumber('penyambungan')->name('approve-cgp');
//        Route::post('/{penyambungan}/reject-cgp', [PenyambunganPipaDataController::class, 'rejectCgp'])->whereNumber('penyambungan')->name('reject-cgp');
//        Route::post('/{penyambungan}/schedule', [PenyambunganPipaDataController::class, 'schedule'])->whereNumber('penyambungan')->name('schedule');
//        Route::post('/{penyambungan}/complete', [PenyambunganPipaDataController::class, 'complete'])->whereNumber('penyambungan')->name('complete');

//        // Find by Reference ID
//        Route::get('/by-reff/{reffId}', [PenyambunganPipaDataController::class, 'redirectByReff'])
//            ->where('reffId', '[A-Za-z0-9\-]+')->name('by-reff');
//    });

   // Photo Approval Management Routes
   Route::prefix('photo-approvals')->name('photos.')->middleware('role:tracer,admin,super_admin')->group(function () {
       Route::get('/', [PhotoApprovalController::class, 'index'])->name('index');
       Route::get('/stats', [PhotoApprovalController::class, 'getStats'])->name('stats');
       Route::get('/pending', [PhotoApprovalController::class, 'getPendingApprovals'])->name('pending');
       Route::get('/{id}', [PhotoApprovalController::class, 'show'])->whereNumber('id')->name('show');

       // Approval Actions
       Route::post('/{id}/tracer/approve', [PhotoApprovalController::class, 'approveByTracer'])->whereNumber('id')->name('tracer.approve');
       Route::post('/{id}/tracer/reject', [PhotoApprovalController::class, 'rejectByTracer'])->whereNumber('id')->name('tracer.reject');
       Route::post('/{id}/cgp/approve', [PhotoApprovalController::class, 'approveByCgp'])->whereNumber('id')->name('cgp.approve');
       Route::post('/{id}/cgp/reject', [PhotoApprovalController::class, 'rejectByCgp'])->whereNumber('id')->name('cgp.reject');
       Route::post('/batch', [PhotoApprovalController::class, 'batchApprove'])->name('batch');
   });

   // Tracer Approval Interface Routes
   Route::prefix('approvals/tracer')->name('approvals.tracer.')->middleware('role:tracer,super_admin')->group(function () {
       Route::get('/', [TracerApprovalController::class, 'index'])->name('index');
       Route::get('/customers', [TracerApprovalController::class, 'customers'])->name('customers');
       Route::get('/customers/{reffId}/photos', [TracerApprovalController::class, 'customerPhotos'])
           ->where('reffId', '[A-Za-z0-9\-]+')->name('photos');
       
       // Photo Actions
       Route::post('/photos/approve', [TracerApprovalController::class, 'approvePhoto'])->name('approve-photo');
       Route::post('/modules/approve', [TracerApprovalController::class, 'approveModule'])->name('approve-module');
       Route::post('/ai-review', [TracerApprovalController::class, 'aiReview'])->name('ai-review');
       
       // Debug route (temporary)
       Route::get('/debug', function() {
           $customers = \App\Models\CalonPelanggan::with(['skData', 'srData', 'gasInData'])->limit(10)->get();
           $photos = \App\Models\PhotoApproval::limit(10)->get(['id', 'reff_id_pelanggan', 'module_name', 'photo_field_name', 'photo_url', 'photo_status']);
           $customer416009 = \App\Models\CalonPelanggan::with(['skData'])->where('reff_id_pelanggan', '416009')->first();
           $sk416009 = \App\Models\SkData::where('reff_id_pelanggan', '416009')->first();
           
           return response()->json([
               'customers_count' => $customers->count(),
               'photos_count' => $photos->count(),
               'customer_416009_exists' => !is_null($customer416009),
               'customer_416009_has_sk' => $customer416009 ? !is_null($customer416009->skData) : false,
               'sk_416009_direct' => !is_null($sk416009),
               'customers' => $customers->map(function($c) {
                   return [
                       'reff_id' => $c->reff_id_pelanggan,
                       'has_sk' => !is_null($c->skData),
                       'sk_tracer_approved' => $c->skData ? $c->skData->tracer_approved_at : null,
                       'has_sr' => !is_null($c->srData),
                       'has_gas_in' => !is_null($c->gasInData),
                   ];
               }),
               'photos' => $photos->map(function($p) {
                   return [
                       'id' => $p->id,
                       'reff_id' => $p->reff_id_pelanggan,
                       'module' => $p->module_name,
                       'field' => $p->photo_field_name,
                       'url' => $p->photo_url,
                       'url_length' => strlen($p->photo_url ?? ''),
                       'status' => $p->photo_status
                   ];
               })
           ]);
       })->name('debug');
   });

   // CGP Approval Interface Routes
   Route::prefix('approvals/cgp')->name('approvals.cgp.')->middleware('role:admin,super_admin')->group(function () {
       Route::get('/', [CgpApprovalController::class, 'index'])->name('index');
       Route::get('/customers', [CgpApprovalController::class, 'customers'])->name('customers');
       Route::get('/customers/{reffId}/photos', [CgpApprovalController::class, 'customerPhotos'])
           ->where('reffId', '[A-Za-z0-9\-]+')->name('customer-photos');
       
       // Photo Actions
       Route::post('/photos/approve', [CgpApprovalController::class, 'approvePhoto'])->name('approve-photo');
       Route::post('/modules/approve', [CgpApprovalController::class, 'approveModule'])->name('approve-module');
   });

   // Notification Routes
   Route::prefix('notifications')->name('notifications.')->group(function () {
       Route::get('/', [NotificationController::class, 'index'])->name('index');
       Route::get('/types', [NotificationController::class, 'getNotificationTypes'])->name('types');
       Route::get('/stats', [NotificationController::class, 'getStats'])->name('stats');
       Route::get('/{id}', [NotificationController::class, 'show'])->whereNumber('id')->name('show');
       Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->whereNumber('id')->name('read');
       Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
       Route::delete('/{id}', [NotificationController::class, 'destroy'])->whereNumber('id')->name('destroy');
       Route::post('/bulk-delete', [NotificationController::class, 'bulkDelete'])->name('bulk-delete');
       Route::post('/bulk-mark-read', [NotificationController::class, 'bulkMarkAsRead'])->name('bulk-mark-read');

       // Development only
       Route::post('/test', [NotificationController::class, 'createTestNotification'])
           ->name('test')
           ->middleware('env:local,development');
   });

   // Import/Export Routes
   Route::prefix('imports')->name('imports.')->middleware('role:admin,super_admin,tracer')->group(function () {
            Route::get('/calon-pelanggan', [ImportController::class, 'formCalonPelanggan'])->name('calon-pelanggan.form');
            Route::get('/calon-pelanggan/template', [ImportController::class, 'downloadTemplateCalonPelanggan'])->name('calon-pelanggan.template'); // TAMBAH INI
            Route::post('/calon-pelanggan', [ImportController::class, 'importCalonPelanggan'])->name('calon-pelanggan.import');
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

   Route::get('/test-gdrive', function() {
       try {
           $service = new \App\Services\GoogleDriveService();
           return response()->json($service->testConnection());
       } catch (Exception $e) {
           return response()->json(['error' => $e->getMessage()]);
       }
   });
}
