<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SrData;
use Illuminate\Auth\Access\HandlesAuthorization;

class SrDataPolicy
{
    use HandlesAuthorization;

    /**
     * Super admin: full access.
     */
    public function before(User $user, string $ability)
    {
        if ($this->hasAnyRole($user, ['super_admin'])) {
            return true;
        }
        return null;
    }

    /**
     * List SR.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, ['admin','tracer','sr']);
    }

    /**
     * Lihat 1 SR.
     */
    public function view(User $user, SrData $sr): bool
    {
        return $this->hasAnyRole($user, ['admin','tracer','sr']);
    }

    /**
     * Buat SR.
     */
    public function create(User $user): bool
    {
        return $this->hasAnyRole($user, ['admin','tracer','sr']);
    }

    /**
     * Update SR (tidak boleh bila completed).
     */
    public function update(User $user, SrData $sr): bool
    {
        if ($sr->module_status === 'completed') {
            return false;
        }
        return $this->hasAnyRole($user, ['admin','tracer','sr']);
    }

    /**
     * Hapus SR: admin saja & bukan completed.
     */
    public function delete(User $user, SrData $sr): bool
    {
        if ($sr->module_status === 'completed') {
            return false;
        }
        return $this->hasAnyRole($user, ['admin']);
    }

    public function restore(User $user, SrData $sr): bool
    {
        return $this->hasAnyRole($user, ['admin']);
    }

    public function forceDelete(User $user, SrData $sr): bool
    {
        return $this->hasAnyRole($user, ['admin']);
    }

    /**
     * Aksi non-resource
     */
    public function uploadPhoto(User $user, SrData $sr): bool
    {
        return $this->update($user, $sr);
    }

    public function deletePhoto(User $user, SrData $sr): bool
    {
        return $this->update($user, $sr);
    }

    public function submit(User $user, SrData $sr): bool
    {
        if ($sr->module_status === 'completed') {
            return false;
        }
        return $this->hasAnyRole($user, ['admin','tracer','sr']);
    }

    /* ================= helpers ================= */

    private function hasAnyRole(User $user, array $roles): bool
    {
        try {
            if (method_exists($user, 'hasAnyRole')) {
                return $user->hasAnyRole(...$roles);
            }
        } catch (\Throwable) { /* ignore */ }

        if (property_exists($user, 'role') && in_array($user->role, $roles, true)) {
            return true;
        }

        if (method_exists($user, 'getRoleNames')) {
            return count(array_intersect($roles, $user->getRoleNames()->toArray())) > 0;
        }
        if (method_exists($user, 'roles')) {
            $userRoles = $user->roles()->pluck('name')->toArray();
            return count(array_intersect($roles, $userRoles)) > 0;
        }

        foreach ($roles as $r) {
            $m = 'is' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $r)));
            if (method_exists($user, $m) && $user->{$m}()) {
                return true;
            }
        }

        return false;
    }
}
