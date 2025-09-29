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
        // 'role'              => 'string', // super_admin, admin, sk, sr, gas_in, cgp, tracer, jalur
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
    private function hasRoleInDatabase(string $role): bool
    {
        return $this->userRoles()->where('role', $role)->exists();
    }
    
    public function isSuperAdmin(): bool
    {
        return $this->hasRoleInDatabase('super_admin');
    }

    // admin saja (tidak termasuk super admin)
    public function isAdmin(): bool
    {
        return $this->hasRoleInDatabase('admin');
    }

    // admin atau super admin
    public function isAdminLike(): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }

    public function isCgp(): bool
    {
        return $this->hasRoleInDatabase('cgp');
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
        // Super admin selalu lolos
        if ($this->isSuperAdmin()) return true;
        
        // Langsung cek ke relasi, tidak ada fallback
        return $this->hasRoleInDatabase($role);
    }

    public function hasAnyRole(array|string $roles): bool
    {
        if (is_string($roles)) {
            $roles = array_map('trim', preg_split('/[,\|;]+/', $roles));
        }

        if ($this->isSuperAdmin()) return true;

        // Langsung cek ke relasi, tidak ada fallback
        return $this->userRoles()->whereIn('role', $roles)->exists();
    }

    public function getAllActiveRoles(): array
    {
        return $this->userRoles()->pluck('role')->toArray();
    }

    // Metode ini sedikit disesuaikan
    public function assignRole(string $role, ?int $assignedBy = null): UserRole
    {
        return $this->userRoles()->firstOrCreate(
            ['role' => $role],
            [
                'user_id'     => $this->id,
                'assigned_by' => $assignedBy,
                'assigned_at' => now()
            ]
        );
    }

    public function removeRole(string $role): bool
    {
        return $this->userRoles()->where('role', $role)->delete() > 0;
    }

    public function syncRoles(array $roles, ?int $assignedBy = null): void
    {
        $this->userRoles()->delete();

        foreach ($roles as $role) {
            UserRole::create([
                'user_id'     => $this->id,
                'role'        => $role,
                'assigned_by' => $assignedBy,
                'assigned_at' => now(),
            ]);
        }
    }

    // Akses modul (dipakai di Blade: canAccessModule('sk'), dll)
    public function canAccessModule(string $module): bool
    {
        if ($this->isSuperAdmin()) return true;

        $map = [
            // Data Pelanggan boleh untuk SK, SR, Gas In, CGP, Admin, Tracer
            'customers'    => ['admin', 'tracer', 'sk', 'sr', 'gas_in', 'cgp'],

            // Modul masing-masing
            'sk'           => ['admin', 'sk', 'tracer'],    
            'sr'           => ['admin', 'sr', 'tracer'],
            'gas_in'       => ['admin', 'gas_in', 'tracer'],

            // Jalur management
            'jalur'        => ['admin', 'jalur'],            // super_admin bypass di awal

            // Validasi/approval internal (tetap seperti sebelumnya bila dipakai)
            'validasi'     => ['admin', 'tracer'],

            // CGP review: HANYA CGP (admin tidak boleh)
            'cgp_review'   => ['cgp'],
        ];

        $allowedRoles = $map[$module] ?? [];

        // hormati multi-role
        $userRoles = $this->getAllActiveRoles();
        return !empty(array_intersect($userRoles, $allowedRoles));
    }
}
