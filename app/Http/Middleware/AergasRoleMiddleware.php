<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;
use Symfony\Component\HttpFoundation\Response;

class AergasRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return $this->handleUnauthenticated($request);
        }

        $user = Auth::user();

        // Parse and validate roles
        $allowedRoles = $this->parseRoles($roles);

        // Check if user has required permissions
        if (!$this->userHasRequiredRole($user, $allowedRoles)) {
            return $this->handleUnauthorized($request, $user, $allowedRoles);
        }

        // Log sensitive route access
        if ($this->isSensitiveRoute($request)) {
            $this->logSensitiveAccess($request, $user);
        }

        return $next($request);
    }

    /**
     * Handle unauthenticated requests
     */
    private function handleUnauthenticated(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        return redirect()->route('login')
                        ->with('error', 'Anda harus login terlebih dahulu');
    }

    /**
     * Handle unauthorized requests
     */
    private function handleUnauthorized(Request $request, $user, array $allowedRoles): Response
    {
        // Log unauthorized access attempt
        $this->logUnauthorizedAccess($request, $user, $allowedRoles);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient privileges for this action',
                'required_roles' => $allowedRoles
            ], 403);
        }

        abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
    }

    /**
     * Parse role parameters into array
     */
    private function parseRoles(array $roles): array
    {
        $allowedRoles = [];

        foreach ($roles as $role) {
            // Support both comma and pipe separated roles
            $parsed = preg_split('/[,|]/', $role);
            $allowedRoles = array_merge($allowedRoles, array_map('trim', $parsed));
        }

        return array_filter(array_unique($allowedRoles));
    }

    /**
     * Check if user has any of the required roles
     */
    private function userHasRequiredRole($user, array $allowedRoles): bool
    {
        try {
            // Primary method: Use Spatie Laravel Permission
            if (method_exists($user, 'hasAnyRole')) {
                return $user->hasAnyRole($allowedRoles);
            }

            // Secondary method: Check individual roles
            if (method_exists($user, 'hasRole')) {
                foreach ($allowedRoles as $role) {
                    if ($user->hasRole($role)) {
                        return true;
                    }
                }
            }

            // Fallback: Direct relationship check
            if (method_exists($user, 'roles')) {
                $userRoles = $user->roles()->pluck('name')->toArray();
                return !empty(array_intersect($allowedRoles, $userRoles));
            }

            // Last resort: Property/method patterns
            return $this->checkRolePatterns($user, $allowedRoles);

        } catch (\Exception $e) {
            Log::error('Role check failed in middleware', [
                'user_id' => $user->id ?? null,
                'roles' => $allowedRoles,
                'error' => $e->getMessage()
            ]);

            return false; // Deny access on error for security
        }
    }

    /**
     * Check role patterns as fallback
     */
    private function checkRolePatterns($user, array $allowedRoles): bool
    {
        foreach ($allowedRoles as $role) {
            // Pattern: isRoleName() method
            $methodName = 'is' . ucfirst(str_replace('_', '', $role));
            if (method_exists($user, $methodName) && $user->$methodName()) {
                return true;
            }

            // Primary: Spatie Laravel Permission
            if (method_exists($user, 'hasAnyRole')) {
                return $user->hasAnyRole($allowedRoles);
            }

            // Pattern: role property
            if (property_exists($user, 'role') && $user->role === $role) {
                return true;
            }

            // Pattern: role_name property
            if (property_exists($user, 'role_name') && $user->role_name === $role) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get user roles safely for logging
     */
    private function getUserRoles($user): array
    {
        try {
            // Primary method: Spatie package
            if (method_exists($user, 'getRoleNames')) {
                return $user->getRoleNames()->toArray();
            }

            // Secondary method: Direct relationship
            if (method_exists($user, 'roles')) {
                return $user->roles()->pluck('name')->toArray();
            }

            // Fallback: Scan for role patterns
            $possibleRoles = [
                'super_admin', 'admin', 'sk', 'sr', 'gas_in',
                'validasi', 'treace', 'gudang', 'finance'
            ];

            $userRoles = [];
            foreach ($possibleRoles as $role) {
                if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                    $userRoles[] = $role;
                }
            }

            // Check properties
            if (property_exists($user, 'role') && !empty($user->role)) {
                $userRoles[] = $user->role;
            }

            return array_unique($userRoles);

        } catch (\Exception $e) {
            Log::warning('Failed to retrieve user roles', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);

            return ['unknown'];
        }
    }

    /**
     * Check if route is considered sensitive
     */
    private function isSensitiveRoute(Request $request): bool
    {
        $sensitivePatterns = [
            'aergas/admin',
            'aergas/users',
            'aergas/export',
            'aergas/bulk',
            'aergas/approve',
            'aergas/reject',
            'aergas/delete',
            'aergas/statistics',
            'aergas/audit',
            'aergas/system'
        ];

        $path = $request->path();
        $routeName = $request->route()?->getName() ?? '';

        foreach ($sensitivePatterns as $pattern) {
            if (str_contains($path, $pattern) || str_contains($routeName, 'admin')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log unauthorized access attempts
     */
    private function logUnauthorizedAccess(Request $request, $user, array $allowedRoles): void
    {
        try {
            $userRoles = $this->getUserRoles($user);

            AuditLog::logAction(
                'UNAUTHORIZED_ACCESS',
                'middleware',
                null,
                null,
                null,
                "Unauthorized access attempt by {$user->name} to {$request->path()}",
                [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'required_roles' => $allowedRoles,
                    'user_roles' => $userRoles,
                    'route_name' => $request->route()?->getName(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ],
                $user
            );

        } catch (\Exception $e) {
            // Fallback to regular logging if AuditLog fails
            Log::warning('Unauthorized access attempt', [
                'user_id' => $user->id ?? null,
                'user_email' => $user->email ?? null,
                'required_roles' => $allowedRoles,
                'route' => $request->route()?->getName(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log sensitive route access
     */
    private function logSensitiveAccess(Request $request, $user): void
    {
        try {
            $userRoles = $this->getUserRoles($user);

            AuditLog::logAction(
                'SENSITIVE_ACCESS',
                'middleware',
                null,
                null,
                null,
                "Sensitive route accessed by {$user->name}: {$request->path()}",
                [
                    'user_id' => $user->id,
                    'route_name' => $request->route()?->getName(),
                    'method' => $request->method(),
                    'user_roles' => $userRoles,
                    'ip' => $request->ip()
                ],
                $user
            );

        } catch (\Exception $e) {
            Log::info('Sensitive route access', [
                'user_id' => $user->id ?? null,
                'route' => $request->route()?->getName(),
                'path' => $request->path(),
                'method' => $request->method(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
