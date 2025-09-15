<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

trait HasMultiRoleAuth
{
    /**
     * Check if current user has any of the specified roles
     */
    protected function requiresAnyRole(array|string $roles): bool|JsonResponse|RedirectResponse
    {
        if (!Auth::check()) {
            return $this->unauthorizedResponse('Authentication required');
        }

        $user = Auth::user();

        if ($user->hasAnyRole($roles)) {
            return true;
        }

        return $this->forbiddenResponse('Insufficient privileges');
    }

    /**
     * Check if current user has specific role
     */
    protected function requiresRole(string $role): bool|JsonResponse|RedirectResponse
    {
        return $this->requiresAnyRole([$role]);
    }

    /**
     * Check if current user can access module
     */
    protected function requiresModuleAccess(string $module): bool|JsonResponse|RedirectResponse
    {
        if (!Auth::check()) {
            return $this->unauthorizedResponse('Authentication required');
        }

        $user = Auth::user();

        if ($user->canAccessModule($module)) {
            return true;
        }

        return $this->forbiddenResponse("Access denied to {$module} module");
    }

    /**
     * Get current user's active roles
     */
    protected function getCurrentUserRoles(): array
    {
        return Auth::check() ? Auth::user()->getAllActiveRoles() : [];
    }

    /**
     * Check if current user is admin-like (admin or super_admin)
     */
    protected function isCurrentUserAdmin(): bool
    {
        return Auth::check() && Auth::user()->isAdminLike();
    }

    /**
     * Unauthorized response helper
     */
    private function unauthorizedResponse(string $message): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 401);
        }

        return redirect()->route('login');
    }

    /**
     * Forbidden response helper
     */
    private function forbiddenResponse(string $message): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 403);
        }

        abort(403, $message);
    }
}