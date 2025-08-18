<?php
// app/Http/Middleware/CheckRole.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    /**
     * Handle role-based access control
     *
     * Usage: middleware('role:admin,tracer') or middleware('role:admin|tracer')
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Must be authenticated first
        if (!Auth::check()) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Authentication required'], 401)
                : redirect()->route('login');
        }

        $user = Auth::user();

        // Super admin bypasses all role checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Parse roles - support both comma and pipe separation
        $allowedRoles = $this->parseRoles($roles);

        // Check if user has any required role
        if ($this->userHasRequiredRole($user, $allowedRoles)) {
            return $next($request);
        }

        // Access denied
        Log::warning('Role access denied', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'required_roles' => $allowedRoles,
            'route' => $request->route()?->getName(),
            'ip' => $request->ip(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient privileges for this action'
            ], 403);
        }

        abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
    }

    /**
     * Parse role parameters into clean array
     */
    private function parseRoles(array $roles): array
    {
        $allowedRoles = [];

        foreach ($roles as $role) {
            // Support both comma and pipe separated roles: 'admin,tracer' or 'admin|tracer'
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
            // Primary method: Use User model's hasAnyRole if available
            if (method_exists($user, 'hasAnyRole')) {
                return $user->hasAnyRole($allowedRoles);
            }

            // Fallback: Direct role property check
            if (property_exists($user, 'role') && in_array($user->role, $allowedRoles, true)) {
                return true;
            }

            // Additional fallback: Check individual role methods
            foreach ($allowedRoles as $role) {
                $methodName = 'is' . ucfirst(str_replace(['_', '-'], '', $role));
                if (method_exists($user, $methodName) && $user->$methodName()) {
                    return true;
                }
            }

            return false;

        } catch (\Throwable $e) {
            Log::error('Role check failed in middleware', [
                'user_id' => $user->id ?? null,
                'roles' => $allowedRoles,
                'error' => $e->getMessage()
            ]);

            // Deny access on error for security
            return false;
        }
    }
}
