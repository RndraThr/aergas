<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle a login request to the application.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'username' => 'required|string|max:100',
                'password' => 'required|string|min:3',
                'remember' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data yang dimasukkan tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user exists
            $user = User::where('username', $request->username)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Username tidak ditemukan'
                ], 401);
            }

            // Check password
            if (!Hash::check($request->password, $user->password)) {
                // Log failed login attempt
                Log::warning('Failed login attempt', [
                    'username' => $request->username,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Username atau password salah'
                ], 401);
            }

            // Check if user is active
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.'
                ], 403);
            }

            // Login user using Laravel Auth
            $credentials = [
                'username' => $request->username,
                'password' => $request->password
            ];

            $remember = $request->boolean('remember', false);

            if (Auth::attempt($credentials, $remember)) {
                $request->session()->regenerate();

                $isFirstLogin = $user->last_login === null;

                $user = Auth::user();
                $user->forceFill(['last_login' => now()])->save();

                $redirectUrl = $this->computeRedirectUrlFor($user, $isFirstLogin);

                return response()->json([
                    'success' => true,
                    'message' => 'Login berhasil',
                    'user' => [
                        'id'           => $user->id,
                        'name'         => $user->name,
                        'username'     => $user->username,
                        'email'        => $user->email,
                        'role'         => $user->role,
                        'full_name'    => $user->full_name,
                        'last_login'   => $user->last_login,
                        'active_roles' => $user->getAllActiveRoles(),
                    ],
                    'redirect' => $redirectUrl,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan login. Silakan coba lagi.'
            ], 401);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data yang dimasukkan tidak valid',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'username' => $request->username ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id'           => $user->id,
                    'name'         => $user->name,
                    'username'     => $user->username,
                    'email'        => $user->email,
                    'role'         => $user->role,
                    'full_name'    => $user->full_name,
                    'is_active'    => $user->is_active,
                    'last_login'   => $user->last_login,
                    'created_at'   => $user->created_at,
                    'active_roles' => $user->getAllActiveRoles(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get user info error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil informasi user'
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = Auth::user();

            // Log logout
            if ($user) {
                Log::info('User logged out', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'session_duration' => $user->last_login ?
                        $user->last_login->diffInMinutes(now()) . ' minutes' : 'unknown'
                ]);
            }

            // Logout user
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Check if AJAX request
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Logout berhasil',
                    'redirect' => '/login'
                ]);
            }

            // Direct redirect for non-AJAX
            return redirect('/login')->with('success', 'Logout berhasil');

        } catch (\Exception $e) {
            Log::error('Logout error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat logout'
                ], 500);
            }

            return redirect('/login')->with('error', 'Terjadi kesalahan saat logout');
        }
    }

    /**
     * Check if user is authenticated (for AJAX requests)
     *
     * @return JsonResponse
     */
    public function check(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'authenticated' => (bool) $user,
            'user' => $user ? [
                'id'           => $user->id,
                'name'         => $user->name,
                'username'     => $user->username,
                'email'        => $user->email,
                'role'         => $user->role,
                'full_name'    => $user->full_name,
                'is_active'    => $user->is_active,
                'last_login'   => $user->last_login,
                'created_at'   => $user->created_at,
                'active_roles' => $user->getAllActiveRoles(),
            ] : null
        ]);
    }

    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $redirect = $this->computeRedirectUrlFor($user, false);
            return redirect($redirect);
        }
        return view('auth.login');
    }

    /**
     * Get redirect URL based on user role
     *
     * @param string $role
     * @return string
     */
    // private function getRedirectUrl(string $role): string
    // {
    //     return match($role) {
    //         'super_admin', 'admin' => '/dashboard',
    //         'tracer' => '/dashboard',
    //         'sk' => '/sk',
    //         'sr' => '/sr',
    //         'mgrt' => '/mgrt',
    //         'gas_in' => '/gas-in',
    //         'pic' => '/dashboard',
    //         default => '/dashboard'
    //     };
    // }

    /**
     * Update user profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'full_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
                'current_password' => 'required_with:new_password|string',
                'new_password' => 'sometimes|string|min:6|confirmed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data yang dimasukkan tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $request->only(['name', 'full_name', 'email']);

            // Handle password change
            if ($request->has('new_password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Password saat ini tidak benar'
                    ], 422);
                }

                $updateData['password'] = Hash::make($request->new_password);
            }

            $user->update($updateData);

            Log::info('User profile updated', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($updateData)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile berhasil diperbarui',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'full_name' => $user->full_name
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Update profile error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui profile'
            ], 500);
        }
    }

    /**
     * Change password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data yang dimasukkan tidak valid',
                    'errors' => $validator->errors()
                ], 422);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password saat ini tidak benar'
                ], 422);
            }

            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            Log::info('User password changed', [
                'user_id' => $user->id,
                'username' => $user->username
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diubah'
            ]);

        } catch (\Exception $e) {
            Log::error('Change password error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengubah password'
            ], 500);
        }
    }

    private function computeRedirectUrlFor(User $user, bool $isFirstLogin = false): string
    {
        $roles = $user->getAllActiveRoles();
        if (empty($roles) && $user->role) {
            $roles = [$user->role];
        }

        $priority = ['cgp','sk','sr','gas_in','jalur','admin','tracer','super_admin'];

        usort($roles, fn($a, $b) => array_search($a, $priority, true) <=> array_search($b, $priority, true));
        $top = $roles[0] ?? $user->role ?? 'admin';

        return match ($top) {
            'cgp'       => '/approvals/cgp',
            'sk'        => $isFirstLogin ? '/sk/create'     : '/sk',
            'sr'        => $isFirstLogin ? '/sr/create'     : '/sr',
            'gas_in'    => $isFirstLogin ? '/gas-in/create' : '/gas-in',
            'jalur'     => '/jalur',
            default     => '/dashboard',
        };
    }
}
