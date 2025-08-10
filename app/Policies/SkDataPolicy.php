<?php

namespace App\Policies;

use App\Models\SkData;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SkDataPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessModule('sk');
    }

    public function view(User $user, SkData $skData): bool
    {
        // Izinkan jika user punya akses umum ke modul SK (Admin, Tracer, dll)
        if ($user->canAccessModule('sk')) {
            return true;
        }

        // Atau jika ID user sama dengan user_id yang mengerjakan data SK ini
        return $user->id === $skData->user_id;
    }

    public function create(User $user): bool
    {
        return $user->canAccessModule('sk');
    }

    public function update(User $user, SkData $skData): bool
    {
        if (!in_array($skData->module_status, ['draft', 'rejected'])) {
            return false;
        }

        return $user->canAccessModule('sk');
    }

    public function delete(User $user, SkData $skData): bool
    {
        return $user->isAdmin();
    }
}
