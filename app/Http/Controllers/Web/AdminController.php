<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CalonPelanggan;
use App\Models\PhotoApproval;
use App\Models\SkData;
use App\Models\SrData;
use App\Models\GasInData;
use App\Models\FileStorage;
use App\Models\AuditLog;
use App\Services\TelegramService;
use App\Services\OpenAIService;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Exception;

class AdminController extends Controller
{
    private TelegramService $telegramService;
    private OpenAIService $openAIService;

    public function __construct(
        TelegramService $telegramService,
        OpenAIService $openAIService
    ) {
        $this->telegramService = $telegramService;
        $this->openAIService = $openAIService;
    }

    /**
     * Get Google Drive service instance with error handling
     */
    private function getGoogleDriveService(): ?GoogleDriveService
    {
        try {
            return app(GoogleDriveService::class);
        } catch (Exception $e) {
            Log::warning('Failed to get Google Drive service', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get all users with filters
     */
    public function getUsers(Request $request): JsonResponse
    {
        try {
            $query = User::query();

            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }
            if ($request->filled('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }
            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(function ($q) use ($s) {
                    $q->where('full_name', 'LIKE', "%{$s}%")
                      ->orWhere('username', 'LIKE', "%{$s}%")
                      ->orWhere('email', 'LIKE', "%{$s}%");
                });
            }

            $allowedSort = ['created_at','updated_at','name','username','email','role','last_login'];
            $sortBy = in_array($request->get('sort_by'), $allowedSort, true) ? $request->get('sort_by') : 'created_at';
            $sortDir = $request->get('sort_direction', 'desc');

            $query->orderBy($sortBy, $sortDir);

            $perPage = min((int) $request->get('per_page', 15), 100);
            $users = $query->paginate($perPage);

            $users->getCollection()->transform(function ($user) {
                $user->last_login_human = $user->last_login ? $user->last_login->diffForHumans() : 'Never';
                $user->created_at_human = $user->created_at->diffForHumans();
                return $user;
            });

            return response()->json([
                'success' => true,
                'data'    => $users,
                'stats'   => $this->getUserStats(),
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching users', ['error' => $e->getMessage(), 'admin_id' => $request->user()?->id]);
            return response()->json(['success' => false, 'message' => 'An error occurred while fetching users'], 500);
        }
    }

    /**
     * Create new user
     */
    public function createUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username'  => 'required|string|unique:users,username|max:100|regex:/^[a-zA-Z0-9_]+$/',
            'email'     => 'required|email|unique:users,email|max:255',
            'password'  => 'required|string|min:6|confirmed',
            'name'      => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'role'      => 'required|in:super_admin,admin,sk,sr,gas_in,pic,tracer',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            Log::error('User creation validation failed', [
                'errors' => $validator->errors()->toArray(),
                'data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'username'  => $request->username,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'name'      => $request->name,
                'full_name' => $request->full_name,
                'role'      => $request->role,
                'is_active' => $request->boolean('is_active', true),
            ]);

            AuditLog::create([
                'user_id'           => $request->user()->id,
                'action'            => 'create',
                'model_type'        => 'User',
                'model_id'          => $user->id,
                'new_values'        => $user->toArray(),
                'ip_address'        => $request->ip(),
                'user_agent'        => $request->userAgent(),
                'description'       => "Created new user: {$user->username} with role {$user->role}",
            ]);

            DB::commit();

            Log::info('New user created', ['user_id'=>$user->id,'username'=>$user->username,'role'=>$user->role,'created_by'=>$request->user()->id]);

            return response()->json(['success'=>true,'message'=>'User created successfully','data'=>$user], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating user', ['error'=>$e->getMessage(),'data'=>$request->only(['username','email','role']),'admin_id'=>$request->user()->id]);
            return response()->json(['success'=>false,'message'=>'An error occurred while creating user'], 500);
        }
    }

    public function settings()
    {
        return view('admin.settings');
    }

    public function usersIndex()
    {
        return view('admin.users');
    }

    public function getUser(int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            return response()->json(['success' => true, 'data' => $user]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }
    }

    public function deleteUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->role === 'super_admin') {
            return response()->json(['success' => false, 'message' => 'Cannot delete super admin'], 403);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Cannot delete your own account'], 403);
        }

        DB::beginTransaction();
        try {
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'delete',
                'model_type' => 'User',
                'model_id' => $user->id,
                'old_values' => $user->toArray(),
                'description' => "Deleted user: {$user->username}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $user->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'User deleted successfully']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error deleting user'], 500);
        }
    }

    /**
     * Update existing user
     */
    public function updateUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->role === 'super_admin' && $request->user()->role !== 'super_admin') {
            return response()->json(['success'=>false,'message'=>'Cannot modify super admin user'], 403);
        }

        $validator = Validator::make($request->all(), [
            'username'  => 'sometimes|string|unique:users,username,' . $id . '|max:100|regex:/^[a-zA-Z0-9_]+$/',
            'email'     => 'sometimes|email|unique:users,email,' . $id . '|max:255',
            'password'  => 'sometimes|string|min:6|confirmed',
            'name'      => 'sometimes|string|max:255',
            'full_name' => 'sometimes|string|max:255',
            'role'      => 'sometimes|in:super_admin,admin,sk,sr,gas_in,pic,tracer',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success'=>false,'message'=>'Validation error','errors'=>$validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $old = $user->toArray();

            $update = $request->only(['username','email','name','full_name','role','is_active']);
            if ($request->filled('password')) {
                $update['password'] = Hash::make($request->password);
            }
            $user->update($update);

            AuditLog::create([
                'user_id'     => $request->user()->id,
                'action'      => 'update',
                'model_type'  => 'User',
                'model_id'    => $user->id,
                'old_values'  => $old,
                'new_values'  => $user->toArray(),
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'description' => "Updated user: {$user->username}",
            ]);

            DB::commit();

            Log::info('User updated', [
                'user_id'   => $user->id,
                'username'  => $user->username,
                'changes'   => array_diff_assoc($user->toArray(), $old),
                'updated_by'=> $request->user()->id,
            ]);

            return response()->json(['success'=>true,'message'=>'User updated successfully','data'=>$user]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating user', ['user_id'=>$id,'error'=>$e->getMessage(),'admin_id'=>$request->user()->id]);
            return response()->json(['success'=>false,'message'=>'An error occurred while updating user'], 500);
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleUserStatus(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->role === 'super_admin') {
            return response()->json(['success'=>false,'message'=>'Cannot deactivate super admin user'], 403);
        }
        if ($user->id === $request->user()->id) {
            return response()->json(['success'=>false,'message'=>'Cannot deactivate your own account'], 403);
        }

        DB::beginTransaction();
        try {
            $old = (bool) $user->is_active;
            $new = !$old;

            $user->update(['is_active'=>$new]);

            // Revoke tokens (jika Sanctum dipakai)
            if (method_exists($user, 'tokens') && !$new) {
                $user->tokens()->delete();
            }

            AuditLog::create([
                'user_id'     => $request->user()->id,
                'action'      => 'update',
                'model_type'  => 'User',
                'model_id'    => $user->id,
                'old_values'  => ['is_active'=>$old],
                'new_values'  => ['is_active'=>$new],
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'description' => "User status changed from " . ($old ? 'active' : 'inactive') . " to " . ($new ? 'active' : 'inactive'),
            ]);

            DB::commit();

            Log::info('User status toggled', [
                'user_id'    => $user->id,
                'username'   => $user->username,
                'old_status' => $old,
                'new_status' => $new,
                'changed_by' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => "User " . ($new ? 'activated' : 'deactivated') . " successfully",
                'data'    => ['user_id'=>$user->id,'username'=>$user->username,'is_active'=>$new],
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error toggling user status', ['user_id'=>$id,'error'=>$e->getMessage(),'admin_id'=>$request->user()->id]);
            return response()->json(['success'=>false,'message'=>'An error occurred while updating user status'], 500);
        }
    }

    /**
     * Get comprehensive system statistics
     */
    public function getSystemStats(): JsonResponse
    {
        try {
            $stats = [
                'users'             => $this->getUserStats(),
                'customers'         => $this->getCustomerStats(),
                'modules'           => $this->getModuleStats(),
                'photos'            => $this->getPhotoStats(),
                'storage'           => $this->getStorageStats(),
                'performance'       => $this->getPerformanceStats(),
                'recent_activities' => $this->getRecentActivities(),
            ];

            return response()->json([
                'success'      => true,
                'data'         => $stats,
                'generated_at' => now()->toISOString(),
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching system stats', ['error'=>$e->getMessage()]);
            return response()->json(['success'=>false,'message'=>'An error occurred while fetching system statistics'], 500);
        }
    }

    /**
     * Test all system integrations
     */
    public function testIntegrations(): JsonResponse
    {
        $results = [];

        // Telegram
        try {
            $ok = method_exists($this->telegramService, 'testConnection')
                ? (bool) $this->telegramService->testConnection()
                : false;

            $results['telegram'] = [
                'status'    => $ok ? 'success' : 'failed',
                'message'   => $ok ? 'Connection successful' : 'Connection failed or not implemented',
                'tested_at' => now()->toISOString(),
            ];
        } catch (Exception $e) {
            $results['telegram'] = ['status'=>'error','message'=>$e->getMessage(),'tested_at'=>now()->toISOString()];
        }

        // OpenAI
        try {
            if (method_exists($this->openAIService, 'testConnection')) {
                $r = $this->openAIService->testConnection();
                $results['openai'] = [
                    'status'           => !empty($r['success']) ? 'success' : 'failed',
                    'message'          => $r['message'] ?? '',
                    'models_available' => $r['available_models'] ?? [],
                    'tested_at'        => now()->toISOString(),
                ];
            } else {
                $results['openai'] = ['status'=>'failed','message'=>'Not implemented','tested_at'=>now()->toISOString()];
            }
        } catch (Exception $e) {
            $results['openai'] = ['status'=>'error','message'=>$e->getMessage(),'tested_at'=>now()->toISOString()];
        }

        // Google Drive
        try {
            $googleDriveService = $this->getGoogleDriveService();
            if ($googleDriveService && method_exists($googleDriveService, 'testConnection')) {
                $g = $googleDriveService->testConnection();
                $results['google_drive'] = [
                    'status'        => !empty($g['success']) ? 'success' : 'failed',
                    'message'       => $g['message'] ?? '',
                    'user_email'    => $g['user_email'] ?? null,
                    'storage_stats' => isset($g['storage_used']) ? [
                        'used'  => $g['storage_used'],
                        'limit' => $g['storage_limit'] ?? null,
                    ] : null,
                    'tested_at'     => now()->toISOString(),
                ];
            } else {
                $results['google_drive'] = [
                    'status' => 'failed',
                    'message' => 'Google Drive service not available',
                    'tested_at' => now()->toISOString()
                ];
            }
        } catch (Exception $e) {
            $results['google_drive'] = ['status'=>'error','message'=>$e->getMessage(),'tested_at'=>now()->toISOString()];
        }

        // Database
        try {
            DB::connection()->getPdo();
            $results['database'] = ['status'=>'success','message'=>'Database connection successful','tested_at'=>now()->toISOString()];
        } catch (Exception $e) {
            $results['database'] = ['status'=>'error','message'=>$e->getMessage(),'tested_at'=>now()->toISOString()];
        }

        $overall = collect($results)->every(fn($x) => $x['status'] === 'success') ? 'success' : 'partial';

        return response()->json([
            'success'        => true,
            'overall_status' => $overall,
            'results'        => $results,
            'tested_at'      => now()->toISOString(),
        ]);
    }

    /**
     * Google Drive storage statistics
     */
    public function getGoogleDriveStats(): JsonResponse
    {
        try {
            $googleDriveService = $this->getGoogleDriveService();
            
            if (!$googleDriveService) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Drive service not available'
                ], 503);
            }

            if (!method_exists($googleDriveService, 'getStorageStats')) {
                return response()->json(['success'=>false,'message'=>'getStorageStats() not implemented in GoogleDriveService'], 501);
            }

            $stats = $googleDriveService->getStorageStats();
            return response()->json(['success'=>true,'data'=>$stats]);
        } catch (Exception $e) {
            Log::error('Error fetching Google Drive stats', ['error'=>$e->getMessage()]);
            return response()->json(['success'=>false,'message'=>'Failed to fetch Google Drive statistics'], 500);
        }
    }

    // ------------------- Private helpers -------------------

    private function getUserStats(): array
    {
        return [
            'total'        => User::count(),
            'active'       => User::where('is_active', true)->count(),
            'inactive'     => User::where('is_active', false)->count(),
            'by_role'      => User::selectRaw('role, COUNT(*) as count')->groupBy('role')->pluck('count','role')->toArray(),
            'recent_logins'=> User::whereNotNull('last_login')->where('last_login','>=',now()->subDays(7))->count(),
        ];
    }

    private function getCustomerStats(): array
    {
        return [
            'total'               => CalonPelanggan::count(),
            'by_status'           => CalonPelanggan::selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count','status')->toArray(),
            'by_progress'         => CalonPelanggan::selectRaw('progress_status, COUNT(*) as count')->groupBy('progress_status')->pluck('count','progress_status')->toArray(),
            'recent_registrations'=> CalonPelanggan::whereDate('tanggal_registrasi','>=',now()->subDays(7))->count(),
        ];
    }

    private function getModuleStats(): array
    {
        return [
            'sk' => [
                'total'      => SkData::count(),
                'completed'  => SkData::where('module_status','completed')->count(),
                'in_progress'=> SkData::whereIn('module_status',['draft','ai_validation','tracer_review','cgp_review'])->count(),
            ],
            'sr' => [
                'total'      => SrData::count(),
                'completed'  => SrData::where('module_status','completed')->count(),
                'in_progress'=> SrData::whereIn('module_status',['draft','ai_validation','tracer_review','cgp_review'])->count(),
            ],
            'gas_in' => [
                'total'      => GasInData::count(),
                'completed'  => GasInData::where('module_status','completed')->count(),
                'in_progress'=> GasInData::whereIn('module_status',['draft','ai_validation','tracer_review','cgp_review'])->count(),
            ],
        ];
    }

    private function getPhotoStats(): array
    {
        $total = PhotoApproval::count();
        return [
            'total'          => $total,
            'by_status'      => PhotoApproval::selectRaw('photo_status, COUNT(*) as count')->groupBy('photo_status')->pluck('count','photo_status')->toArray(),
            'ai_approval_rate' => $total > 0
                ? round((PhotoApproval::whereIn('photo_status', ['ai_approved','tracer_pending','tracer_approved','cgp_pending','cgp_approved'])->count() / $total) * 100, 2)
                : 0,
            'pending_tracer' => PhotoApproval::where('photo_status','tracer_pending')->count(),
            'pending_cgp'    => PhotoApproval::where('photo_status','cgp_pending')->count(),
            'processed_today'=> PhotoApproval::whereDate('updated_at', today())->count(),
        ];
    }

    private function getStorageStats(): array
    {
        $totalFiles = FileStorage::count();
        $totalSize  = (int) FileStorage::sum('file_size');

        return [
            'total_files'     => $totalFiles,
            'total_size'      => $totalSize,
            'total_size_human'=> $this->formatBytes($totalSize),
            'by_module'       => FileStorage::selectRaw('module_name, COUNT(*) as count, SUM(file_size) as total_size')
                                ->groupBy('module_name')
                                ->get()
                                ->map(function ($row) {
                                    $size = (int) $row->total_size;
                                    return [
                                        'count'      => (int) $row->count,
                                        'size'       => $size,
                                        'size_human' => $this->formatBytes($size),
                                    ];
                                })
                                ->keyBy('module_name')
                                ->toArray(),
            'recent_uploads'  => FileStorage::whereDate('created_at','>=',now()->subDays(7))->count(),
        ];
    }

    private function getPerformanceStats(): array
    {
        return [
            'daily_completions' => SkData::where('module_status','completed')->whereDate('cgp_approved_at', now()->subDay()->toDateString())->count(),
            'avg_processing_time'=> $this->getAverageProcessingTime(),
            'sla_compliance'     => $this->getSlaCompliance(),
            'system_uptime'      => $this->getSystemUptime(),
        ];
    }

    private function getRecentActivities(): array
    {
        return AuditLog::with('user')
            ->orderBy('created_at','desc')
            ->take(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id'               => $log->id,
                    'action'           => $log->action,
                    'model_type'       => $log->model_type,
                    'user'             => $log->user->full_name ?? 'System',
                    'description'      => $log->description,
                    'created_at'       => $log->created_at,
                    'created_at_human' => $log->created_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    private function getAverageProcessingTime(): float
    {
        $rows = PhotoApproval::where('photo_status','cgp_approved')
            ->whereNotNull('ai_approved_at')
            ->whereNotNull('cgp_approved_at')
            ->selectRaw('TIMESTAMPDIFF(HOUR, ai_approved_at, cgp_approved_at) as processing_hours')
            ->get();

        return (float) ($rows->avg('processing_hours') ?? 0);
    }

    private function getSlaCompliance(): array
    {
        $tracerViol = PhotoApproval::where('photo_status','tracer_pending')
            ->where('ai_approved_at','<', now()->subHours(config('services.sla_tracer_hours',24)))
            ->count();

        $cgpViol = PhotoApproval::where('photo_status','cgp_pending')
            ->where('tracer_approved_at','<', now()->subHours(config('services.sla_cgp_hours',48)))
            ->count();

        $totalPending = PhotoApproval::whereIn('photo_status',['tracer_pending','cgp_pending'])->count();
        $compliance = $totalPending > 0 ? round((($totalPending - $tracerViol - $cgpViol) / $totalPending) * 100, 2) : 100;

        return [
            'tracer_violations' => $tracerViol,
            'cgp_violations'    => $cgpViol,
            'total_violations'  => $tracerViol + $cgpViol,
            'compliance_rate'   => $compliance,
        ];
    }

    private function getSystemUptime(): string
    {
        $oldest = AuditLog::orderBy('created_at')->first();
        return $oldest ? $oldest->created_at->diffForHumans() : 'Unknown';
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B','KB','MB','GB','TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return number_format($bytes, 2) . ' ' . $units[$i];
    }
}
