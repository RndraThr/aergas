<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SkData;
use Illuminate\Auth\Access\HandlesAuthorization;

class SkDataPolicy
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
     * List SK.
     */
    public function viewAny(User $user): bool
    {
        return $this->canAccessModuleSk($user);
    }

    /**
     * Lihat 1 SK.
     * Izinkan jika punya akses modul atau owner (user_id) dari data SK tsb.
     */
    public function view(User $user, SkData $sk): bool
    {
        if ($this->canAccessModuleSk($user)) {
            return true;
        }
        return (int)$user->id === (int)$sk->user_id;
    }

    /**
     * Buat SK.
     */
    public function create(User $user): bool
    {
        return $this->canAccessModuleSk($user);
    }

    /**
     * Update SK (hanya saat draft atau rejected).
     */
    public function update(User $user, SkData $sk): bool
    {
        if (!in_array($sk->module_status, ['draft', 'rejected'], true)) {
            return false;
        }
        return $this->canAccessModuleSk($user);
    }

    /**
     * Hapus SK: admin saja dan tidak boleh kalau sudah completed.
     */
    public function delete(User $user, SkData $sk): bool
    {
        if ($sk->module_status === 'completed') {
            return false;
        }
        return $this->hasAnyRole($user, ['admin']);
    }

    public function restore(User $user, SkData $sk): bool
    {
        return $this->hasAnyRole($user, ['admin']);
    }

    public function forceDelete(User $user, SkData $sk): bool
    {
        return $this->hasAnyRole($user, ['admin']);
    }

    /**
     * Aksi non-resource (opsional, kalau mau panggil ability spesifik).
     * Di controller kamu sudah pakai authorize('update', $sk_data), jadi ini opsional.
     */
    public function uploadPhoto(User $user, SkData $sk): bool
    {
        return $this->update($user, $sk);
    }

    public function deletePhoto(User $user, SkData $sk): bool
    {
        return $this->update($user, $sk);
    }

    public function submit(User $user, SkData $sk): bool
    {
        if ($sk->module_status === 'completed') {
            return false;
        }
        return $this->canAccessModuleSk($user);
    }

    /* ================= helpers ================= */

    private function canAccessModuleSk(User $user): bool
    {
        // Utamakan method yang sudah ada di User (kalau tersedia)
        if (method_exists($user, 'canAccessModule')) {
            try {
                return (bool) $user->canAccessModule('sk');
            } catch (\Throwable) {}
        }
        // Fallback: role standar untuk modul SK
        return $this->hasAnyRole($user, ['admin','tracer','sk']);
    }

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
