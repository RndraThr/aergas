<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'reff_id_pelanggan',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'description'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user() { return $this->belongsTo(User::class); }

    public function scopeByModel($q, $type, $id)
    {
        return $q->where('model_type', $type)->where('model_id', $id);
    }

    public function scopeByUser($q, $userId)
    {
        return $q->where('user_id', $userId);
    }

    public function scopeRecent($q)
    {
        return $q->orderByDesc('created_at');
    }

    public function pelanggan()
    {
        return $this->belongsTo(CalonPelanggan::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    // Helper method to get the actual model instance
    public function getModelInstance()
    {
        if ($this->model_type && $this->model_id) {
            $modelClass = "App\\Models\\{$this->model_type}";
            if (class_exists($modelClass)) {
                return $modelClass::find($this->model_id);
            }
        }
        return null;
    }
}
