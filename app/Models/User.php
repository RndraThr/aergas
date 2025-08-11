<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'role'              => 'string', // super_admin, admin, sk, sr, mgrt, gas_in, pic, tracer
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

    public function hasRole(string $role): bool
    {
        // Super admin lewat semua
        if ($this->isSuperAdmin()) return true;
        return $this->role === $role;
    }

    public function hasAnyRole(array|string $roles): bool
    {
        if (is_string($roles)) {
            // dukung pemisah koma/pipe/titik koma
            $roles = array_map('trim', preg_split('/[,\|;]+/', $roles));
        }
        if ($this->isSuperAdmin()) return true;
        return in_array($this->role, $roles, true);
    }

    // Akses modul (dipakai di Blade: canAccessModule('sk'), dll)
    public function canAccessModule(string $module): bool
    {
        if ($this->isSuperAdmin()) return true;

        $map = [
            'customers'   => ['admin', 'tracer'],
            'sk'          => ['admin', 'sk'],
            'sr'          => ['admin', 'sr'],
            'mgrt'        => ['admin', 'mgrt'],
            'gas_in'      => ['admin', 'gas_in'],
            'validasi'    => ['admin', 'tracer'],
            'jalur_pipa'  => ['admin', 'sk', 'sr'], // sesuaikan
            'penyambungan'=> ['admin', 'sr'],       // sesuaikan
        ];

        return in_array($this->role, $map[$module] ?? [], true);
    }
}
