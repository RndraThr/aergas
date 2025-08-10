<?php

/**
 * =============================================================================
 * CONTROLLER: AdminController.php
 * Location: app/Http/Controllers/Api/AdminController.php
 * =============================================================================
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CalonPelanggan;
use App\Models\PhotoApproval;
use App\Models\SkData;
use App\Models\SrData;
use App\Models\MgrtData;
use App\Models\GasInData;
use App\Models\FileStorage;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Services\TelegramService;
use App\Services\OpenAIService;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Exception;

class AdminController extends Controller
{
    private TelegramService $telegramService;
    private OpenAIService $openAIService;
    private GoogleDriveService $googleDriveService;

    public function __construct(
        TelegramService $telegramService,
        OpenAIService $openAIService,
        GoogleDriveService $googleDriveService
    ) {
        $this->telegramService = $telegramService;
        $this->openAIService = $openAIService;
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Get all users with filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUsers(Request $request): JsonResponse
    {
        try {
            $query = User::query();

            // Apply filters
            if ($request->has('role') && $request->role !== '') {
                $query->where('role', $request->role);
            }

            if ($request->has('is_active') && $request->is_active !== '') {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('search') && $request->search !== '') {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', '%' . $search . '%')
                      ->orWhere('full_name', 'LIKE', '%' . $search . '%')
                      ->orWhere('username', 'LIKE', '%' . $search . '%')
                      ->orWhere('email', 'LIKE', '%' . $search . '%');
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $allowedSortFields = ['created_at', 'updated_at', 'name', 'username', 'email', 'role', 'last_login'];

            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortDirection);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $perPage = min($request->get('per_page', 15), 100);
            $users = $query->paginate($perPage);

            // Add computed fields
            $users->getCollection()->transform(function ($user) {
                $user->last_login_human = $user->last_login ? $user->last_login->diffForHumans() : 'Never';
                $user->created_at_human = $user->created_at->diffForHumans();
                return $user;
            });

            return response()->json([
                'success' => true,
                'data' => $users,
                'stats' => $this->getUserStats()
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching users', [
                'error' => $e->getMessage(),
                'admin_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching users'
            ], 500);
        }
    }

    /**
     * Create new user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:users,username|max:100|regex:/^[a-zA-Z0-9_]+$/',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:6|confirmed',
            'name' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'role' => 'required|in:super_admin,admin,sk,sr,mgrt,gas_in,pic,tracer',
            'is_active' => 'boolean'
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
            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'name' => $request->name,
                'full_name' => $request->full_name,
                'role' => $request->role,
                'is_active' => $request->get('is_active', true)
            ]);

            // Create audit log
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'create',
                'model_type' => 'User',
                'model_id' => $user->id,
                'new_values' => $user->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'description' => "Created new user: {$user->username} with role {$user->role}"
            ]);

            DB::commit();

            Log::info('New user created', [
                'user_id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
                'created_by' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user
            ], 201);

        } catch (Exception $e) {
            DB::rollback();

            Log::error('Error creating user', [
                'error' => $e->getMessage(),
                'data' => $request->only(['username', 'email', 'role']),
                'admin_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating user'
            ], 500);
        }
    }

    /**
     * Update existing user
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent admin from modifying super admin unless they are super admin
        if ($user->role === 'super_admin' && $request->user()->role !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify super admin user'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'username' => 'sometimes|string|unique:users,username,' . $id . '|max:100|regex:/^[a-zA-Z0-9_]+$/',
            'email' => 'sometimes|email|unique:users,email,' . $id . '|max:255',
            'password' => 'sometimes|string|min:6|confirmed',
            'name' => 'sometimes|string|max:255',
            'full_name' => 'sometimes|string|max:255',
            'role' => 'sometimes|in:super_admin,admin,sk,sr,mgrt,gas_in,pic,tracer',
            'is_active' => 'sometimes|boolean'
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
            $oldData = $user->toArray();

            $updateData = $request->only(['username', 'email', 'name', 'full_name', 'role', 'is_active']);

            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            // Create audit log
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'update',
                'model_type' => 'User',
                'model_id' => $user->id,
                'old_values' => $oldData,
                'new_values' => $user->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'description' => "Updated user: {$user->username}"
            ]);

            DB::commit();

            Log::info('User updated', [
                'user_id' => $user->id,
                'username' => $user->username,
                'changes' => array_diff_assoc($user->toArray(), $oldData),
                'updated_by' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ]);

        } catch (Exception $e) {
            DB::rollback();

            Log::error('Error updating user', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'admin_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating user'
            ], 500);
        }
    }

    /**
     * Toggle user active status
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function toggleUserStatus(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent deactivating super admin
        if ($user->role === 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot deactivate super admin user'
            ], 403);
        }

        // Prevent user from deactivating themselves
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot deactivate your own account'
            ], 403);
        }

        DB::beginTransaction();

        try {
            $oldStatus = $user->is_active;
            $newStatus = !$oldStatus;

            $user->update(['is_active' => $newStatus]);

            // Revoke all tokens if deactivating
            if (!$newStatus) {
                $user->tokens()->delete();
            }

            // Create audit log
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'update',
                'model_type' => 'User',
                'model_id' => $user->id,
                'old_values' => ['is_active' => $oldStatus],
                'new_values' => ['is_active' => $newStatus],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'description' => "User status changed from " . ($oldStatus ? 'active' : 'inactive') . " to " . ($newStatus ? 'active' : 'inactive')
            ]);

            DB::commit();

            Log::info('User status toggled', [
                'user_id' => $user->id,
                'username' => $user->username,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => "User " . ($newStatus ? 'activated' : 'deactivated') . " successfully",
                'data' => [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'is_active' => $newStatus
                ]
            ]);

        } catch (Exception $e) {
            DB::rollback();

            Log::error('Error toggling user status', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'admin_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating user status'
            ], 500);
        }
    }

    /**
     * Get comprehensive system statistics
     *
     * @return JsonResponse
     */
    public function getSystemStats(): JsonResponse
    {
        try {
            $stats = [
                'users' => $this->getUserStats(),
                'customers' => $this->getCustomerStats(),
                'modules' => $this->getModuleStats(),
                'photos' => $this->getPhotoStats(),
                'storage' => $this->getStorageStats(),
                'performance' => $this->getPerformanceStats(),
                'recent_activities' => $this->getRecentActivities()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'generated_at' => now()->toISOString()
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching system stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching system statistics'
            ], 500);
        }
    }

    /**
     * Test all system integrations
     *
     * @return JsonResponse
     */
    public function testIntegrations(): JsonResponse
    {
        $results = [];

        try {
            // Test Telegram
            Log::info('Testing Telegram integration');
            $telegramResult = $this->telegramService->testConnection();
            $results['telegram'] = [
                'status' => $telegramResult ? 'success' : 'failed',
                'message' => $telegramResult ? 'Connection successful' : 'Connection failed',
                'tested_at' => now()->toISOString()
            ];

        } catch (Exception $e) {
            $results['telegram'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'tested_at' => now()->toISOString()
            ];
        }

        try {
            // Test OpenAI
            Log::info('Testing OpenAI integration');
            $openAIResult = $this->openAIService->testConnection();
            $results['openai'] = [
                'status' => $openAIResult['success'] ? 'success' : 'failed',
                'message' => $openAIResult['message'],
                'models_available' => $openAIResult['available_models'] ?? [],
                'tested_at' => now()->toISOString()
            ];

        } catch (Exception $e) {
            $results['openai'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'tested_at' => now()->toISOString()
            ];
        }

        try {
            // Test Google Drive
            Log::info('Testing Google Drive integration');
            $googleDriveResult = $this->googleDriveService->testConnection();
            $results['google_drive'] = [
                'status' => $googleDriveResult['success'] ? 'success' : 'failed',
                'message' => $googleDriveResult['message'],
                'user_email' => $googleDriveResult['user_email'] ?? null,
                'storage_stats' => isset($googleDriveResult['storage_used']) ? [
                    'used' => $googleDriveResult['storage_used'],
                    'limit' => $googleDriveResult['storage_limit']
                ] : null,
                'tested_at' => now()->toISOString()
            ];

        } catch (Exception $e) {
            $results['google_drive'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'tested_at' => now()->toISOString()
            ];
        }

        // Test Database
        try {
            DB::connection()->getPdo();
            $results['database'] = [
                'status' => 'success',
                'message' => 'Database connection successful',
                'tested_at' => now()->toISOString()
            ];
        } catch (Exception $e) {
            $results['database'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'tested_at' => now()->toISOString()
            ];
        }

        $overallStatus = collect($results)->every(fn($result) => $result['status'] === 'success') ? 'success' : 'partial';

        return response()->json([
            'success' => true,
            'overall_status' => $overallStatus,
            'results' => $results,
            'tested_at' => now()->toISOString()
        ]);
    }

    /**
     * Get Google Drive storage statistics
     *
     * @return JsonResponse
     */
    public function getGoogleDriveStats(): JsonResponse
    {
        try {
            $stats = $this->googleDriveService->getStorageStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching Google Drive stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Google Drive statistics'
            ], 500);
        }
    }

    // Private helper methods

    private function getUserStats(): array
    {
        return [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'by_role' => User::selectRaw('role, COUNT(*) as count')
                            ->groupBy('role')
                            ->pluck('count', 'role')
                            ->toArray(),
            'recent_logins' => User::whereNotNull('last_login')
                                 ->where('last_login', '>=', now()->subDays(7))
                                 ->count()
        ];
    }

    private function getCustomerStats(): array
    {
        return [
            'total' => CalonPelanggan::count(),
            'by_status' => CalonPelanggan::selectRaw('status, COUNT(*) as count')
                                       ->groupBy('status')
                                       ->pluck('count', 'status')
                                       ->toArray(),
            'by_progress' => CalonPelanggan::selectRaw('progress_status, COUNT(*) as count')
                                          ->groupBy('progress_status')
                                          ->pluck('count', 'progress_status')
                                          ->toArray(),
            'recent_registrations' => CalonPelanggan::whereDate('tanggal_registrasi', '>=', now()->subDays(7))->count()
        ];
    }

    private function getModuleStats(): array
    {
        return [
            'sk' => [
                'total' => SkData::count(),
                'completed' => SkData::where('module_status', 'completed')->count(),
                'in_progress' => SkData::whereIn('module_status', ['draft', 'ai_validation', 'tracer_review', 'cgp_review'])->count()
            ],
            'sr' => [
                'total' => SrData::count(),
                'completed' => SrData::where('module_status', 'completed')->count(),
                'in_progress' => SrData::whereIn('module_status', ['draft', 'ai_validation', 'tracer_review', 'cgp_review'])->count()
            ],
            'mgrt' => [
                'total' => MgrtData::count(),
                'completed' => MgrtData::where('module_status', 'completed')->count(),
                'in_progress' => MgrtData::whereIn('module_status', ['draft', 'ai_validation', 'tracer_review', 'cgp_review'])->count()
            ],
            'gas_in' => [
                'total' => GasInData::count(),
                'completed' => GasInData::where('module_status', 'completed')->count(),
                'in_progress' => GasInData::whereIn('module_status', ['draft', 'ai_validation', 'tracer_review', 'cgp_review'])->count()
            ]
        ];
    }

    private function getPhotoStats(): array
    {
        $total = PhotoApproval::count();

        return [
            'total' => $total,
            'by_status' => PhotoApproval::selectRaw('photo_status, COUNT(*) as count')
                                       ->groupBy('photo_status')
                                       ->pluck('count', 'photo_status')
                                       ->toArray(),
            'ai_approval_rate' => $total > 0 ? round((PhotoApproval::whereIn('photo_status', [
                'ai_approved', 'tracer_pending', 'tracer_approved', 'cgp_pending', 'cgp_approved'
            ])->count() / $total) * 100, 2) : 0,
            'pending_tracer' => PhotoApproval::where('photo_status', 'tracer_pending')->count(),
            'pending_cgp' => PhotoApproval::where('photo_status', 'cgp_pending')->count(),
            'processed_today' => PhotoApproval::whereDate('updated_at', today())->count()
        ];
    }

    private function getStorageStats(): array
    {
        $totalFiles = FileStorage::count();
        $totalSize = FileStorage::sum('file_size');

        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'by_module' => FileStorage::selectRaw('module_name, COUNT(*) as count, SUM(file_size) as total_size')
                                     ->groupBy('module_name')
                                     ->get()
                                     ->map(function ($item) {
                                         return [
                                             'count' => $item->count,
                                             'size' => $item->total_size,
                                             'size_human' => $this->formatBytes($item->total_size)
                                         ];
                                     })
                                     ->keyBy('module_name')
                                     ->toArray(),
            'recent_uploads' => FileStorage::whereDate('created_at', '>=', now()->subDays(7))->count()
        ];
    }

    private function getPerformanceStats(): array
    {
        $now = now();
        $yesterday = $now->copy()->subDay();

        return [
            'daily_completions' => SkData::where('module_status', 'completed')
                                        ->whereDate('cgp_approved_at', $yesterday)
                                        ->count(),
            'avg_processing_time' => $this->getAverageProcessingTime(),
            'sla_compliance' => $this->getSlaCompliance(),
            'system_uptime' => $this->getSystemUptime()
        ];
    }

    private function getRecentActivities(): array
    {
        return AuditLog::with('user')
                       ->orderBy('created_at', 'desc')
                       ->take(10)
                       ->get()
                       ->map(function ($log) {
                           return [
                               'id' => $log->id,
                               'action' => $log->action,
                               'model_type' => $log->model_type,
                               'user' => $log->user->full_name ?? 'System',
                               'description' => $log->description,
                               'created_at' => $log->created_at,
                               'created_at_human' => $log->created_at->diffForHumans()
                           ];
                       })
                       ->toArray();
    }

    private function getAverageProcessingTime(): float
    {
        // Calculate average time from AI approval to CGP approval
        $completedPhotos = PhotoApproval::where('photo_status', 'cgp_approved')
                                       ->whereNotNull('ai_approved_at')
                                       ->whereNotNull('cgp_approved_at')
                                       ->selectRaw('TIMESTAMPDIFF(HOUR, ai_approved_at, cgp_approved_at) as processing_hours')
                                       ->get();

        return $completedPhotos->avg('processing_hours') ?? 0;
    }

    private function getSlaCompliance(): array
    {
        $tracerSlaViolations = PhotoApproval::where('photo_status', 'tracer_pending')
                                           ->where('ai_approved_at', '<', now()->subHours(24))
                                           ->count();

        $cgpSlaViolations = PhotoApproval::where('photo_status', 'cgp_pending')
                                        ->where('tracer_approved_at', '<', now()->subHours(48))
                                        ->count();

        $totalPending = PhotoApproval::whereIn('photo_status', ['tracer_pending', 'cgp_pending'])->count();

        return [
            'tracer_violations' => $tracerSlaViolations,
            'cgp_violations' => $cgpSlaViolations,
            'total_violations' => $tracerSlaViolations + $cgpSlaViolations,
            'compliance_rate' => $totalPending > 0 ? round((($totalPending - $tracerSlaViolations - $cgpSlaViolations) / $totalPending) * 100, 2) : 100
        ];
    }

    private function getSystemUptime(): string
    {
        // Simple uptime calculation based on oldest log entry
        $oldestLog = AuditLog::orderBy('created_at')->first();
        if ($oldestLog) {
            return $oldestLog->created_at->diffForHumans();
        }
        return 'Unknown';
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
