<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// Controllers (namespace Web)
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\CalonPelangganController;
use App\Http\Controllers\Web\SkDataController;
use App\Http\Controllers\Web\SrDataController;
use App\Http\Controllers\Web\PhotoApprovalController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\GudangController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Root redirect (pakai Auth::check() agar analyzer aman)
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// (Opsional) pola global
Route::pattern('id', '[0-9]+');

/*
|--------------------------------------------------------------------------
| Guest
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

/*
|--------------------------------------------------------------------------
| Authenticated
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // ---- Auth/Profile
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('password.change');
    Route::get('/auth/check', [AuthController::class, 'check'])->name('auth.check');

    // ---- Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'getData'])->name('dashboard.data');

    /*
    |--------------------------------------------------------------------------
    | Calon Pelanggan
    |--------------------------------------------------------------------------
    */
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CalonPelangganController::class, 'index'])->name('index');

        Route::get('/{reffId}', [CalonPelangganController::class, 'show'])
            ->where('reffId', '[A-Z0-9\-]+')
            ->name('show');

        Route::get('/stats/json', [CalonPelangganController::class, 'getStats'])->name('stats');

        Route::get('/validate-reff/{reffId}', [CalonPelangganController::class, 'validateReff'])
            ->where('reffId', '[A-Z0-9]+')
            ->name('validate-reff');

        Route::middleware('role:admin,tracer')->group(function () {
            Route::get('/create', [CalonPelangganController::class, 'create'])->name('create');
            Route::post('/', [CalonPelangganController::class, 'store'])->name('store');

            Route::get('/{reffId}/edit', [CalonPelangganController::class, 'edit'])
                ->where('reffId', '[A-Z0-9\-]+')
                ->name('edit');

            Route::put('/{reffId}', [CalonPelangganController::class, 'update'])
                ->where('reffId', '[A-Z0-9\-]+')
                ->name('update');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | SK (pakai implicit model binding ID numerik)
    |--------------------------------------------------------------------------
    */

    Route::prefix('sk')
        ->name('sk.')
        ->middleware('role:sk|tracer|admin|super_admin') // ← pakai "|" jika Spatie; kalau middleware kamu pakai koma, kembalikan ke koma
        ->group(function () {

            Route::get('/', [SkDataController::class, 'index'])->name('index');

            // Form & store draft
            Route::get('/create', [SkDataController::class, 'create'])->name('create');
            Route::post('/', [SkDataController::class, 'store'])->name('store');

            // Detail & CRUD
            Route::get('/{sk}', [SkDataController::class, 'show'])->whereNumber('sk')->name('show');
            Route::get('/{sk}/edit', [SkDataController::class, 'edit'])->whereNumber('sk')->name('edit');
            Route::put('/{sk}', [SkDataController::class, 'update'])->whereNumber('sk')->name('update');
            Route::delete('/{sk}', [SkDataController::class, 'destroy'])->whereNumber('sk')->name('destroy');

            // Precheck (tanpa {sk}) — dipakai saat create sebelum ada id
            Route::post('/photos/precheck-generic', [SkDataController::class, 'precheckGeneric'])
                ->name('photos.precheck-generic');

            // Upload foto (tanpa AI ulang) + simpan hasil precheck
            Route::post('/{sk}/photos', [SkDataController::class, 'uploadAndValidate'])
                ->whereNumber('sk')->name('photos.upload');

            // ❌ HAPUS: recheck karena tidak dipakai lagi
            // Route::post('/{sk}/photos/{photo}/recheck', ...)->name('photos.recheck');

            // Status & workflow
            Route::get('/{sk}/ready-status', [SkDataController::class, 'readyStatus'])
                ->whereNumber('sk')->name('ready-status');

            Route::post('/{sk}/approve-tracer', [SkDataController::class, 'approveTracer'])
                ->whereNumber('sk')->name('approve-tracer');
            Route::post('/{sk}/reject-tracer',  [SkDataController::class, 'rejectTracer'])
                ->whereNumber('sk')->name('reject-tracer');

            Route::post('/{sk}/approve-cgp', [SkDataController::class, 'approveCgp'])
                ->whereNumber('sk')->name('approve-cgp');
            Route::post('/{sk}/reject-cgp',  [SkDataController::class, 'rejectCgp'])
                ->whereNumber('sk')->name('reject-cgp');

            Route::post('/{sk}/schedule', [SkDataController::class, 'schedule'])
                ->whereNumber('sk')->name('schedule');
            Route::post('/{sk}/complete', [SkDataController::class, 'complete'])
                ->whereNumber('sk')->name('complete');

            // (opsional) by-reff → pastikan controllernya ada. Perlebar regex agar terima huruf kecil juga
            Route::get('/by-reff/{reffId}', [SkDataController::class, 'redirectByReff'])
                ->where('reffId', '[A-Za-z0-9\-]+')->name('by-reff');
        });


    /*
    |--------------------------------------------------------------------------
    | SR (pakai implicit model binding ID numerik)
    |--------------------------------------------------------------------------
    */
    Route::prefix('sr')->name('sr.')->middleware('role:sr,tracer,admin')->group(function () {
        Route::get('/', [SrDataController::class, 'index'])->name('index');
        Route::post('/', [SrDataController::class, 'store'])->name('store');

        Route::get('{sr}', [SrDataController::class, 'show'])->whereNumber('sr')->name('show');
        Route::put('{sr}', [SrDataController::class, 'update'])->whereNumber('sr')->name('update');
        Route::delete('{sr}', [SrDataController::class, 'destroy'])->whereNumber('sr')->name('destroy');

        // Foto (upload realtime + recheck)
        Route::post('{sr}/photos', [SrDataController::class, 'uploadAndValidate'])->whereNumber('sr')->name('photos.upload');
        Route::post('{sr}/photos/{photo}/recheck', [SrDataController::class, 'recheck'])
            ->whereNumber('sr')->whereNumber('photo')->name('photos.recheck');

        // Status kesiapan AI
        Route::get('{sr}/ready-status', [SrDataController::class, 'readyStatus'])->whereNumber('sr')->name('ready-status');

        // Workflow approvals
        Route::post('{sr}/approve-tracer', [SrDataController::class, 'approveTracer'])->whereNumber('sr')->name('approve-tracer');
        Route::post('{sr}/reject-tracer',  [SrDataController::class, 'rejectTracer'])->whereNumber('sr')->name('reject-tracer');
        Route::post('{sr}/approve-cgp',    [SrDataController::class, 'approveCgp'])->whereNumber('sr')->name('approve-cgp');
        Route::post('{sr}/reject-cgp',     [SrDataController::class, 'rejectCgp'])->whereNumber('sr')->name('reject-cgp');

        // Penjadwalan & selesai
        Route::post('{sr}/schedule', [SrDataController::class, 'schedule'])->whereNumber('sr')->name('schedule');
        Route::post('{sr}/complete', [SrDataController::class, 'complete'])->whereNumber('sr')->name('complete');

        Route::post('/sk/photos/precheck-generic', [SkDataController::class, 'precheckGeneric'])
            ->name('sk.photos.precheck-generic'); // untuk halaman create (belum punya {sk})

        Route::post('/sk/{sk}/photos/precheck', [SkDataController::class, 'precheck'])
            ->whereNumber('sk')->name('sk.photos.precheck');

        // (Opsional) akses by reff
        Route::get('by-reff/{reffId}', function (string $reffId) {
            $rec = \App\Models\SrData::where('reff_id_pelanggan', $reffId)->firstOrFail();
            return redirect()->route('sr.show', $rec->id);
        })->where('reffId', '[A-Z0-9\-]+')->name('by-reff');
    });

    /*
    |--------------------------------------------------------------------------
    | Photo Approvals (biarkan sesuai sebelumnya)
    |--------------------------------------------------------------------------
    */
    Route::prefix('photo-approvals')->name('photos.')->middleware('role:tracer,admin')->group(function () {
        Route::get('/', [PhotoApprovalController::class, 'index'])->name('index');
        Route::get('/stats', [PhotoApprovalController::class, 'getStats'])->name('stats');
        Route::get('/pending', [PhotoApprovalController::class, 'getPendingApprovals'])->name('pending');
        Route::get('/report/summary', [PhotoApprovalController::class, 'getSummaryReport'])->name('summary');
        Route::get('/export/excel', [PhotoApprovalController::class, 'exportToExcel'])->name('export');

        Route::get('/{id}', [PhotoApprovalController::class, 'show'])->whereNumber('id')->name('show');

        Route::post('/{id}/tracer/approve', [PhotoApprovalController::class, 'approveByTracer'])->whereNumber('id')->name('tracer.approve');
        Route::post('/{id}/tracer/reject',  [PhotoApprovalController::class, 'rejectByTracer'])->whereNumber('id')->name('tracer.reject');

        Route::post('/{id}/cgp/approve', [PhotoApprovalController::class, 'approveByCgp'])->whereNumber('id')->name('cgp.approve');
        Route::post('/{id}/cgp/reject',  [PhotoApprovalController::class, 'rejectByCgp'])->whereNumber('id')->name('cgp.reject');

        Route::post('/batch', [PhotoApprovalController::class, 'batchApprove'])->name('batch');
    });

    /*
    |--------------------------------------------------------------------------
    | Notifications (biarkan sesuai sebelumnya)
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | GUDANG (Items, Stock, Transaksi, Material Requests)
    |--------------------------------------------------------------------------
    */
    Route::prefix('gudang')->name('gudang.')->middleware('role:admin,gudang,tracer')->group(function () {
        // Items
        Route::get('items', [GudangController::class, 'items'])->name('items.index');
        Route::post('items', [GudangController::class, 'itemStore'])->name('items.store');
        Route::put('items/{item}', [GudangController::class, 'itemUpdate'])->whereNumber('item')->name('items.update');
        Route::post('items/{item}/toggle', [GudangController::class, 'itemToggle'])->whereNumber('item')->name('items.toggle');

        // Stock
        Route::get('stock', [GudangController::class, 'stock'])->name('stock.index');

        // Transaksi
        Route::get('transactions', [GudangController::class, 'transactions'])->name('tx.index');
        Route::post('transactions', [GudangController::class, 'txStore'])->name('tx.store');
        Route::post('transactions/in', [GudangController::class, 'txIn'])->name('tx.in');
        Route::post('transactions/out', [GudangController::class, 'txOut'])->name('tx.out');
        Route::post('transactions/return', [GudangController::class, 'txReturn'])->name('tx.return');
        Route::post('transactions/reject', [GudangController::class, 'txReject'])->name('tx.reject');
        Route::post('transactions/installed', [GudangController::class, 'txInstalled'])->name('tx.installed');

        // Material Requests
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
});

/*
|--------------------------------------------------------------------------
| Dev helper
|--------------------------------------------------------------------------
*/
if (app()->environment(['local', 'development'])) {
    Route::get('/test-auth', function () {
        return response()->json([
            'authenticated' => Auth::check(),
            'user'         => Auth::user(),
            'session_id'   => session()->getId(),
        ]);
    });
}
