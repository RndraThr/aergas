<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role',
        // 'is_active',
        'assigned_at',
        'assigned_by'
    ];

    protected $casts = [
        // 'is_active' => 'boolean',
        'assigned_at' => 'datetime'
    ];

    // Available roles
    public const AVAILABLE_ROLES = [
        'super_admin',
        'admin',
        'sk',
        'sr',
        'gas_in',
        'cgp',
        'tracer',
        'jalur'
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // // Scopes
    // public function scopeActive($query)
    // {
    //     return $query->where('is_active', true);
    // }

    // public function scopeInactive($query)
    // {
    //     return $query->where('is_active', false);
    // }

    // public function scopeRole($query, string $role)
    // {
    //     return $query->where('role', $role);
    // }

    // // Methods
    // public function activate(): bool
    // {
    //     $this->is_active = true;
    //     return $this->save();
    // }

    // public function deactivate(): bool
    // {
    //     $this->is_active = false;
    //     return $this->save();
    // }

    // public function isActive(): bool
    // {
    //     return $this->is_active === true;
    // }
}