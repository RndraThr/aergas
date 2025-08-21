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
   GudangController,
   ImportController,
   GasInDataController,
   AdminController
};

Route::get('/', function () {
   return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::pattern('id', '[0-9]+');

Route::middleware('guest')->group(function () {
   Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
   Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {

   Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
   Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
   Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
   Route::post('/change-password', [AuthController::class, 'changePassword'])->name('password.change');
   Route::get('/auth/check', [AuthController::class, 'check'])->name('auth.check');

   Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
   Route::get('/dashboard/data', [DashboardController::class, 'getData'])->name('dashboard.data');
   Route::get('/dashboard/installation-trend', [DashboardController::class, 'getInstallationTrend'])->name('dashboard.installation-trend');
   Route::get('/dashboard/activity-metrics', [DashboardController::class, 'getActivityMetrics'])->name('dashboard.activity-metrics');

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
       });
   });

   Route::prefix('sk')->name('sk.')->middleware('role:sk,tracer,admin,super_admin')->group(function () {
       Route::get('/', [SkDataController::class, 'index'])->name('index');
       Route::get('/create', [SkDataController::class, 'create'])->name('create');
       Route::post('/', [SkDataController::class, 'store'])->name('store');
       Route::get('/{sk}', [SkDataController::class, 'show'])->whereNumber('sk')->name('show');
       Route::get('/{sk}/edit', [SkDataController::class, 'edit'])->whereNumber('sk')->name('edit');
       Route::put('/{sk}', [SkDataController::class, 'update'])->whereNumber('sk')->name('update');
       Route::delete('/{sk}', [SkDataController::class, 'destroy'])->whereNumber('sk')->name('destroy');

       Route::post('/photos/precheck-generic', [SkDataController::class, 'precheckGeneric'])->name('photos.precheck-generic');
       Route::post('/{sk}/photos', [SkDataController::class, 'uploadAndValidate'])->whereNumber('sk')->name('photos.upload');
       Route::get('/{sk}/ready-status', [SkDataController::class, 'readyStatus'])->whereNumber('sk')->name('ready-status');

       Route::post('/{sk}/approve-tracer', [SkDataController::class, 'approveTracer'])->whereNumber('sk')->name('approve-tracer');
       Route::post('/{sk}/reject-tracer', [SkDataController::class, 'rejectTracer'])->whereNumber('sk')->name('reject-tracer');
       Route::post('/{sk}/approve-cgp', [SkDataController::class, 'approveCgp'])->whereNumber('sk')->name('approve-cgp');
       Route::post('/{sk}/reject-cgp', [SkDataController::class, 'rejectCgp'])->whereNumber('sk')->name('reject-cgp');
       Route::post('/{sk}/schedule', [SkDataController::class, 'schedule'])->whereNumber('sk')->name('schedule');
       Route::post('/{sk}/complete', [SkDataController::class, 'complete'])->whereNumber('sk')->name('complete');

       Route::get('/by-reff/{reffId}', [SkDataController::class, 'redirectByReff'])
           ->where('reffId', '[A-Za-z0-9\-]+')->name('by-reff');
   });

   Route::prefix('sr')->name('sr.')->middleware('role:sr,tracer,admin,super_admin')->group(function () {
       Route::get('/', [SrDataController::class, 'index'])->name('index');
       Route::get('/create', [SrDataController::class, 'create'])->name('create');
       Route::post('/', [SrDataController::class, 'store'])->name('store');
       Route::get('/{sr}', [SrDataController::class, 'show'])->whereNumber('sr')->name('show');
       Route::get('/{sr}/edit', [SrDataController::class, 'edit'])->whereNumber('sr')->name('edit');
       Route::put('/{sr}', [SrDataController::class, 'update'])->whereNumber('sr')->name('update');
       Route::delete('/{sr}', [SrDataController::class, 'destroy'])->whereNumber('sr')->name('destroy');

       Route::post('/photos/precheck-generic', [SrDataController::class, 'precheckGeneric'])->name('photos.precheck-generic');
       Route::post('/{sr}/photos', [SrDataController::class, 'uploadAndValidate'])->whereNumber('sr')->name('photos.upload');
       Route::get('/{sr}/ready-status', [SrDataController::class, 'readyStatus'])->whereNumber('sr')->name('ready-status');

       Route::post('/{sr}/approve-tracer', [SrDataController::class, 'approveTracer'])->whereNumber('sr')->name('approve-tracer');
       Route::post('/{sr}/reject-tracer', [SrDataController::class, 'rejectTracer'])->whereNumber('sr')->name('reject-tracer');
       Route::post('/{sr}/approve-cgp', [SrDataController::class, 'approveCgp'])->whereNumber('sr')->name('approve-cgp');
       Route::post('/{sr}/reject-cgp', [SrDataController::class, 'rejectCgp'])->whereNumber('sr')->name('reject-cgp');
       Route::post('/{sr}/schedule', [SrDataController::class, 'schedule'])->whereNumber('sr')->name('schedule');
       Route::post('/{sr}/complete', [SrDataController::class, 'complete'])->whereNumber('sr')->name('complete');

       Route::get('/by-reff/{reffId}', [SrDataController::class, 'redirectByReff'])
           ->where('reffId', '[A-Za-z0-9\-]+')->name('by-reff');
   });

   Route::prefix('gas-in')->name('gas-in.')->middleware('role:gas_in,tracer,admin,super_admin')->group(function () {
       Route::get('/', [GasInDataController::class, 'index'])->name('index');
       Route::get('/create', [GasInDataController::class, 'create'])->name('create');
       Route::post('/', [GasInDataController::class, 'store'])->name('store');
       Route::get('/{gasIn}', [GasInDataController::class, 'show'])->whereNumber('gasIn')->name('show');
       Route::get('/{gasIn}/edit', [GasInDataController::class, 'edit'])->whereNumber('gasIn')->name('edit');
       Route::put('/{gasIn}', [GasInDataController::class, 'update'])->whereNumber('gasIn')->name('update');
       Route::delete('/{gasIn}', [GasInDataController::class, 'destroy'])->whereNumber('gasIn')->name('destroy');

       Route::post('/photos/precheck-generic', [GasInDataController::class, 'precheckGeneric'])->name('photos.precheck-generic');
       Route::post('/{gasIn}/photos', [GasInDataController::class, 'uploadAndValidate'])->whereNumber('gasIn')->name('photos.upload');
       Route::get('/{gasIn}/ready-status', [GasInDataController::class, 'readyStatus'])->whereNumber('gasIn')->name('ready-status');

       Route::post('/{gasIn}/approve-tracer', [GasInDataController::class, 'approveTracer'])->whereNumber('gasIn')->name('approve-tracer');
       Route::post('/{gasIn}/reject-tracer', [GasInDataController::class, 'rejectTracer'])->whereNumber('gasIn')->name('reject-tracer');
       Route::post('/{gasIn}/approve-cgp', [GasInDataController::class, 'approveCgp'])->whereNumber('gasIn')->name('approve-cgp');
       Route::post('/{gasIn}/reject-cgp', [GasInDataController::class, 'rejectCgp'])->whereNumber('gasIn')->name('reject-cgp');
       Route::post('/{gasIn}/schedule', [GasInDataController::class, 'schedule'])->whereNumber('gasIn')->name('schedule');
       Route::post('/{gasIn}/complete', [GasInDataController::class, 'complete'])->whereNumber('gasIn')->name('complete');

       Route::get('/by-reff/{reffId}', [GasInDataController::class, 'redirectByReff'])
           ->where('reffId', '[A-Za-z0-9\-]+')->name('by-reff');
   });

   Route::prefix('photo-approvals')->name('photos.')->middleware('role:tracer,admin,super_admin')->group(function () {
       Route::get('/', [PhotoApprovalController::class, 'index'])->name('index');
       Route::get('/stats', [PhotoApprovalController::class, 'getStats'])->name('stats');
       Route::get('/pending', [PhotoApprovalController::class, 'getPendingApprovals'])->name('pending');
       Route::get('/report/summary', [PhotoApprovalController::class, 'getSummaryReport'])->name('summary');
       Route::get('/export/excel', [PhotoApprovalController::class, 'exportToExcel'])->name('export');
       Route::get('/{id}', [PhotoApprovalController::class, 'show'])->whereNumber('id')->name('show');

       Route::post('/{id}/tracer/approve', [PhotoApprovalController::class, 'approveByTracer'])->whereNumber('id')->name('tracer.approve');
       Route::post('/{id}/tracer/reject', [PhotoApprovalController::class, 'rejectByTracer'])->whereNumber('id')->name('tracer.reject');
       Route::post('/{id}/cgp/approve', [PhotoApprovalController::class, 'approveByCgp'])->whereNumber('id')->name('cgp.approve');
       Route::post('/{id}/cgp/reject', [PhotoApprovalController::class, 'rejectByCgp'])->whereNumber('id')->name('cgp.reject');
       Route::post('/batch', [PhotoApprovalController::class, 'batchApprove'])->name('batch');
   });

   Route::prefix('notifications')->name('notifications.')->group(function () {
       Route::get('/', [NotificationController::class, 'index'])->name('index');
       Route::get('/types', [NotificationController::class, 'getNotificationTypes'])->name('types');
       Route::get('/stats', [NotificationController::class, 'getStats'])->name('stats');
       Route::get('/{id}', [NotificationController::class, 'show'])->whereNumber('id')->name('show');
       Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->whereNumber('id')->name('read');
       Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
       Route::delete('/{id}', [NotificationController::class, 'destroy'])->whereNumber('id')->name('destroy');
       Route::post('/bulk-delete', [NotificationController::class, 'bulkDelete'])->name('bulk-delete');
       Route::post('/mark-type', [NotificationController::class, 'markAsReadByType'])->name('mark-type');
       Route::post('/test', [NotificationController::class, 'createTestNotification'])->name('test');
   });

   Route::prefix('gudang')->name('gudang.')->middleware('role:admin,gudang,tracer,super_admin')->group(function () {
       Route::get('items', [GudangController::class, 'items'])->name('items.index');
       Route::post('items', [GudangController::class, 'itemStore'])->name('items.store');
       Route::put('items/{item}', [GudangController::class, 'itemUpdate'])->whereNumber('item')->name('items.update');
       Route::post('items/{item}/toggle', [GudangController::class, 'itemToggle'])->whereNumber('item')->name('items.toggle');

       Route::get('stock', [GudangController::class, 'stock'])->name('stock.index');

       Route::get('transactions', [GudangController::class, 'transactions'])->name('tx.index');
       Route::post('transactions', [GudangController::class, 'txStore'])->name('tx.store');
       Route::post('transactions/in', [GudangController::class, 'txIn'])->name('tx.in');
       Route::post('transactions/out', [GudangController::class, 'txOut'])->name('tx.out');
       Route::post('transactions/return', [GudangController::class, 'txReturn'])->name('tx.return');
       Route::post('transactions/reject', [GudangController::class, 'txReject'])->name('tx.reject');
       Route::post('transactions/installed', [GudangController::class, 'txInstalled'])->name('tx.installed');

       Route::get('material-requests', [GudangController::class, 'mrIndex'])->name('mr.index');
       Route::get('material-requests/{mr}', [GudangController::class, 'mrShow'])->whereNumber('mr')->name('mr.show');
       Route::post('material-requests', [GudangController::class, 'mrStore'])->name('mr.store');
       Route::post('material-requests/{mr}/items', [GudangController::class, 'mrAddItem'])->whereNumber('mr')->name('mr.items.add');
       Route::post('material-requests/{mr}/submit', [GudangController::class, 'mrSubmit'])->whereNumber('mr')->name('mr.submit');
       Route::post('material-requests/{mr}/approve', [GudangController::class, 'mrApprove'])->whereNumber('mr')->name('mr.approve');
       Route::post('material-requests/{mr}/issue', [GudangController::class, 'mrIssue'])->whereNumber('mr')->name('mr.issue');
       Route::post('material-requests/{mr}/return', [GudangController::class, 'mrReturn'])->whereNumber('mr')->name('mr.return');
       Route::post('material-requests/{mr}/reject', [GudangController::class, 'mrReject'])->whereNumber('mr')->name('mr.reject');
   });

   Route::prefix('imports')->name('imports.')->middleware('role:admin,super_admin,tracer')->group(function () {
       Route::get('/calon-pelanggan', [ImportController::class, 'formCalonPelanggan'])->name('calon-pelanggan.form');
       Route::get('/calon-pelanggan/template', [ImportController::class, 'downloadTemplateCalonPelanggan'])->name('calon-pelanggan.template');
       Route::post('/calon-pelanggan', [ImportController::class, 'importCalonPelanggan'])->name('calon-pelanggan.import');
       Route::get('/report', [ImportController::class, 'downloadReport'])->name('report.download');
   });
});

if (app()->environment(['local', 'development'])) {
   Route::get('/test-auth', function () {
       return response()->json([
           'authenticated' => Auth::check(),
           'user' => Auth::user(),
           'session_id' => session()->getId(),
       ]);
   });
}
