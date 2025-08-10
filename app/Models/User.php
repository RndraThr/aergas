<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'name',
        'full_name',
        'role',
        'is_active',
        'last_login'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    // Role checking methods
    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin()
    {
        return in_array($this->role, ['super_admin', 'admin']);
    }

    public function isTracer()
    {
        return $this->role === 'tracer';
    }

    public function canAccessModule($module)
    {
        $moduleAccess = [
            'super_admin' => ['all'],
            'admin' => ['all'],
            'tracer' => ['all'],
            'sk' => ['sk'],
            'sr' => ['sr'],
            'mgrt' => ['mgrt'],
            'gas_in' => ['gas_in'],
            'pic' => ['jalur_pipa', 'penyambungan'],
        ];

        $userAccess = $moduleAccess[$this->role] ?? [];
        return in_array('all', $userAccess) || in_array($module, $userAccess);
    }

    // Relationships
    public function photoApprovalsAsTracer()
    {
        return $this->hasMany(PhotoApproval::class, 'tracer_user_id');
    }

    public function photoApprovalsAsCgp()
    {
        return $this->hasMany(PhotoApproval::class, 'cgp_user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function fileUploads()
    {
        return $this->hasMany(FileStorage::class, 'uploaded_by');
    }
}
