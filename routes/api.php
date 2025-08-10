<?php

/**
 * =============================================================================
 * ROUTES: routes/api.php (FIXED VERSION)
 * Location: routes/api.php
 * =============================================================================
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Import all required controllers
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CalonPelangganController;
use App\Http\Controllers\Api\SkDataController;
use App\Http\Controllers\Api\SrDataController;
use App\Http\Controllers\Api\PhotoApprovalController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'AERGAS System',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'environment' => app()->environment()
    ]);
});

// System info endpoint
Route::get('/info', function () {
    return response()->json([
        'app_name' => config('app.name'),
        'app_version' => '1.0.0',
        'laravel_version' => app()->version(),
        'php_version' => PHP_VERSION,
        'timezone' => config('app.timezone'),
        'locale' => config('app.locale')
    ]);
});

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Authentication Required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authentication Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::put('/profile', [AuthController::class, 'updateProfile'])->name('updateProfile');
        Route::post('/revoke-tokens', [AuthController::class, 'revokeAllTokens'])->name('revokeTokens');
    });

    /*
    |--------------------------------------------------------------------------
    | Dashboard Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/charts', [DashboardController::class, 'getChartData'])->name('charts');
        Route::get('/stats', [DashboardController::class, 'getDetailedStats'])->name('stats');
        Route::get('/activities', [DashboardController::class, 'getRecentActivities'])->name('activities');
    });

    /*
    |--------------------------------------------------------------------------
    | Notifications Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/stats', [NotificationController::class, 'getStats'])->name('stats');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('markAllRead');
        Route::get('/{id}', [NotificationController::class, 'show'])->name('show');
        Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('markRead');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('delete');
    });

    /*
    |--------------------------------------------------------------------------
    | Customer Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('customers')->name('customers.')->group(function () {
        // Public customer endpoints (all authenticated users can access)
        Route::get('/validate/{reffId}', [CalonPelangganController::class, 'validateReffId'])->name('validate');
        Route::get('/stats', [CalonPelangganController::class, 'getStats'])->name('stats');
        Route::get('/{reffId}', [CalonPelangganController::class, 'show'])->name('show');

        // Restricted customer management (Admin and Tracer only)
        Route::middleware(['role:admin,tracer'])->group(function () {
            Route::get('/', [CalonPelangganController::class, 'index'])->name('index');
            Route::post('/', [CalonPelangganController::class, 'store'])->name('store');
            Route::put('/{reffId}', [CalonPelangganController::class, 'update'])->name('update');
            Route::delete('/{reffId}', [CalonPelangganController::class, 'destroy'])->name('delete');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | SK Data Management
    |--------------------------------------------------------------------------
    */
    Route::apiResource('sk-data', SkDataController::class)->parameters(['sk-data' => 'sk_data']);

    Route::prefix('sk')->name('sk.')->middleware(['role:sk,tracer,admin'])->group(function () {
        Route::get('/', [SkDataController::class, 'index'])->name('index');
        Route::post('/', [SkDataController::class, 'store'])->name('store');
        Route::get('/stats', [SkDataController::class, 'getStats'])->name('stats'); // Rute ini sudah OK

        // Ubah {reffId} menjadi {sk_data}
        Route::get('/{sk_data}', [SkDataController::class, 'show'])->name('show');
        Route::put('/{sk_data}', [SkDataController::class, 'update'])->name('update'); // Sebaiknya ada method update() terpisah
        Route::post('/{sk_data}/upload-photo', [SkDataController::class, 'uploadPhoto'])->name('uploadPhoto');
        Route::delete('/{sk_data}/photos/{photoField}', [SkDataController::class, 'deletePhoto'])->name('deletePhoto');
        Route::post('/{sk_data}/submit', [SkDataController::class, 'submit'])->name('submit');
        Route::get('/{reffId}/download/{photoField}', [SkDataController::class, 'downloadPhoto'])->name('downloadPhoto');
    });

    /*
    |--------------------------------------------------------------------------
    | SR Data Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('sr')->name('sr.')->middleware(['role:sr,tracer,admin'])->group(function () {
        Route::get('/', [SrDataController::class, 'index'])->name('index');
        Route::post('/', [SrDataController::class, 'store'])->name('store');
        Route::get('/stats', [SrDataController::class, 'getStats'])->name('stats');
        Route::get('/{reffId}', [SrDataController::class, 'show'])->name('show');
        Route::put('/{reffId}', [SrDataController::class, 'store'])->name('update');
        Route::post('/{reffId}/upload-photo', [SrDataController::class, 'uploadPhoto'])->name('uploadPhoto');
        Route::delete('/{reffId}/photos/{photoField}', [SrDataController::class, 'deletePhoto'])->name('deletePhoto');
        Route::post('/{reffId}/submit', [SrDataController::class, 'submit'])->name('submit');
    });

    /*
    |--------------------------------------------------------------------------
    | Photo Approval Workflow
    |--------------------------------------------------------------------------
    */
    Route::prefix('photo-approvals')->name('photoApprovals.')->group(function () {
        // View and manage approvals (Tracer and Admin)
        Route::middleware(['role:tracer,admin'])->group(function () {
            Route::get('/', [PhotoApprovalController::class, 'index'])->name('index');
            Route::get('/stats', [PhotoApprovalController::class, 'getStats'])->name('stats');
            Route::get('/pending-tracer', [PhotoApprovalController::class, 'getPendingTracerApprovals'])->name('pendingTracer');
            Route::get('/pending-cgp', [PhotoApprovalController::class, 'getPendingCgpApprovals'])->name('pendingCgp');
            Route::get('/{id}', [PhotoApprovalController::class, 'show'])->name('show');
            Route::post('/batch-action', [PhotoApprovalController::class, 'batchApprove'])->name('batchAction');
        });

        // Tracer level actions
        Route::middleware(['role:tracer,admin'])->group(function () {
            Route::post('/{id}/tracer-approve', [PhotoApprovalController::class, 'approveByTracer'])->name('tracerApprove');
            Route::post('/{id}/tracer-reject', [PhotoApprovalController::class, 'rejectByTracer'])->name('tracerReject');
        });

        // CGP (Admin only) actions
        Route::middleware(['role:admin'])->group(function () {
            Route::post('/{id}/cgp-approve', [PhotoApprovalController::class, 'approveByCgp'])->name('cgpApprove');
            Route::post('/{id}/cgp-reject', [PhotoApprovalController::class, 'rejectByCgp'])->name('cgpReject');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | System Administration (Super Admin Only)
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->middleware(['role:super_admin,admin'])->group(function () {
        // User Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [AdminController::class, 'getUsers'])->name('index');
            Route::post('/', [AdminController::class, 'createUser'])->name('create');
            Route::get('/{id}', [AdminController::class, 'getUser'])->name('show');
            Route::put('/{id}', [AdminController::class, 'updateUser'])->name('update');
            Route::post('/{id}/toggle-status', [AdminController::class, 'toggleUserStatus'])->name('toggleStatus');
            Route::post('/{id}/reset-password', [AdminController::class, 'resetUserPassword'])->name('resetPassword');
            Route::delete('/{id}', [AdminController::class, 'deleteUser'])->name('delete');
        });

        // System Management
        Route::get('/system-stats', [AdminController::class, 'getSystemStats'])->name('systemStats');
        Route::post('/test-integrations', [AdminController::class, 'testIntegrations'])->name('testIntegrations');
        Route::get('/google-drive/stats', [AdminController::class, 'getGoogleDriveStats'])->name('googleDriveStats');
        Route::post('/google-drive/test', [AdminController::class, 'testGoogleDrive'])->name('testGoogleDrive');
        Route::get('/audit-logs', [AdminController::class, 'getAuditLogs'])->name('auditLogs');
        Route::post('/cleanup/files', [AdminController::class, 'cleanupFiles'])->name('cleanupFiles');
        Route::post('/backup/database', [AdminController::class, 'backupDatabase'])->name('backupDatabase');

        // System Settings
        Route::get('/settings', [AdminController::class, 'getSettings'])->name('settings.index');
        Route::put('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
    });

    /*
    |--------------------------------------------------------------------------
    | File & Media Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('files')->name('files.')->group(function () {
        Route::get('/{reffId}/{module}/{field}', [AdminController::class, 'getFileInfo'])->name('info');
        Route::get('/{reffId}/{module}/{field}/download', [AdminController::class, 'downloadFile'])->name('download');
        Route::delete('/{reffId}/{module}/{field}', [AdminController::class, 'deleteFile'])->name('delete')
             ->middleware(['role:admin,tracer']);
        Route::get('/stats', [AdminController::class, 'getFileStats'])->name('stats');
    });
});

/*
|--------------------------------------------------------------------------
| Development Routes (Only in Development Environment)
|--------------------------------------------------------------------------
*/
if (app()->environment(['local', 'development', 'testing'])) {
    Route::prefix('dev')->name('dev.')->group(function () {
        // Test endpoints for development
        Route::get('/test-telegram', function () {
            $telegram = app(\App\Services\TelegramService::class);
            $result = $telegram->testConnection();
            return response()->json(['telegram_test' => $result]);
        });

        Route::get('/test-openai', function () {
            $openai = app(\App\Services\OpenAIService::class);
            $result = $openai->testConnection();
            return response()->json(['openai_test' => $result]);
        });

        Route::get('/test-google-drive', function () {
            $drive = app(\App\Services\GoogleDriveService::class);
            $result = $drive->testConnection();
            return response()->json(['google_drive_test' => $result]);
        });

        Route::get('/phpinfo', function () {
            return response(phpinfo(), 200)->header('Content-Type', 'text/html');
        });

        Route::get('/clear-cache', function () {
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);
        });
    });
}

/*
|--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'code' => 'ENDPOINT_NOT_FOUND'
    ], 404);
});

/**
 * =============================================================================
 * QUICK CREATE ADMINCONTROLLER
 * =============================================================================
 */

// Jika AdminController belum ada, buat dulu:
// php artisan make:controller Api/AdminController
