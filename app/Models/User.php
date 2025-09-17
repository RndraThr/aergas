<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Class User
 *
 * @property int $id
 * @property string $name
 * @property string $full_name
 * @property string $email
 * @property \Carbon\Carbon|null $email_verified_at
 * @property string $password
 * @property string $role
 * @property bool $is_active
 * @property \Carbon\Carbon|null $last_login
 * @property string|null $remember_token
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PhotoApproval[] $tracerApprovedPhotos
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PhotoApproval[] $cgpApprovedPhotos
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\UserRole[] $userRoles
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\UserRole[] $activeRoles
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';
    protected $guarded = []; // pertimbangkan ganti ke $fillable untuk produksi

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login'        => 'datetime',
        'is_active'         => 'boolean',
        'password'          => 'hashed', // Laravel 10+
        'role'              => 'string', // super_admin, admin, sk, sr, gas_in, pic, tracer, jalur
    ];

    // --------- Relations ---------
    public function tracerApprovedPhotos(): HasMany
    {
        return $this->hasMany(PhotoApproval::class, 'tracer_user_id');
    }

    public function cgpApprovedPhotos(): HasMany
    {
        return $this->hasMany(PhotoApproval::class, 'cgp_user_id');
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function activeRoles(): HasMany
    {
        return $this->hasMany(UserRole::class)->where('is_active', true);
    }

    // --------- Role helpers ---------
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    // admin saja (tidak termasuk super admin)
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // admin atau super admin
    public function isAdminLike(): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }

    public function isTracer(): bool
    {
        return $this->role === 'tracer';
    }

    public function isJalur(): bool
    {
        return $this->role === 'jalur';
    }

    public function hasRole(string $role): bool
    {
        // Super admin lewat semua (dari single role atau multi-role)
        if ($this->isSuperAdmin()) return true;

        // Check multi-role system first
        if ($this->activeRoles()->where('role', $role)->exists()) {
            return true;
        }

        // Fallback to single role system (backward compatibility)
        return $this->role === $role;
    }

    public function hasAnyRole(array|string $roles): bool
    {
        if (is_string($roles)) {
            // dukung pemisah koma/pipe/titik koma
            $roles = array_map('trim', preg_split('/[,\|;]+/', $roles));
        }

        // Super admin lewat semua
        if ($this->isSuperAdmin()) return true;

        // Check multi-role system first
        if ($this->activeRoles()->whereIn('role', $roles)->exists()) {
            return true;
        }

        // Fallback to single role system (backward compatibility)
        return in_array($this->role, $roles, true);
    }

    public function getAllActiveRoles(): array
    {
        $multiRoles = $this->activeRoles()->pluck('role')->toArray();

        // If no multi-roles, fallback to single role
        if (empty($multiRoles) && $this->role) {
            return [$this->role];
        }

        return $multiRoles;
    }

    public function assignRole(string $role, ?int $assignedBy = null): UserRole
    {
        return UserRole::firstOrCreate([
            'user_id' => $this->id,
            'role' => $role,
        ], [
            'is_active' => true,
            'assigned_at' => now(),
            'assigned_by' => $assignedBy,
        ]);
    }

    public function removeRole(string $role): bool
    {
        return $this->userRoles()->where('role', $role)->delete() > 0;
    }

    public function syncRoles(array $roles, ?int $assignedBy = null): void
    {
        // Deactivate all current roles
        $this->userRoles()->update(['is_active' => false]);

        // Assign new roles
        foreach ($roles as $role) {
            UserRole::updateOrCreate([
                'user_id' => $this->id,
                'role' => $role,
            ], [
                'is_active' => true,
                'assigned_at' => now(),
                'assigned_by' => $assignedBy,
            ]);
        }
    }

    // Akses modul (dipakai di Blade: canAccessModule('sk'), dll)
    public function canAccessModule(string $module): bool
    {
        if ($this->isSuperAdmin()) return true;

        $map = [
            'customers'   => ['admin', 'tracer'],
            'sk'          => ['admin', 'sk'],
            'sr'          => ['admin', 'sr'],
            'gas_in'      => ['admin', 'gas_in'],
            'validasi'    => ['admin', 'tracer'],
            'jalur'       => ['admin', 'jalur'],
            'jalur_pipa'  => ['admin', 'sk', 'sr'], // sesuaikan
            'penyambungan'=> ['admin', 'sr'],       // sesuaikan
        ];

        $allowedRoles = $map[$module] ?? [];

        // Check multi-role system
        $userRoles = $this->getAllActiveRoles();
        return !empty(array_intersect($userRoles, $allowedRoles));
    }
}
