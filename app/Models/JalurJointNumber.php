<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JalurJointNumber extends Model
{
    use HasFactory;

    protected $table = 'jalur_joint_numbers';
    protected $guarded = [];

    protected $casts = [
        'is_used' => 'boolean',
        'is_active' => 'boolean',
        'used_at' => 'datetime',
    ];

    // Relations
    public function cluster(): BelongsTo
    {
        return $this->belongsTo(JalurCluster::class, 'cluster_id');
    }

    public function fittingType(): BelongsTo
    {
        return $this->belongsTo(JalurFittingType::class, 'fitting_type_id');
    }

    public function usedByJoint(): BelongsTo
    {
        return $this->belongsTo(JalurJointData::class, 'used_by_joint_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('is_used', false)->where('is_active', true);
    }

    public function scopeUsed($query)
    {
        return $query->where('is_used', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCluster($query, int $clusterId)
    {
        return $query->where('cluster_id', $clusterId);
    }

    public function scopeByFittingType($query, int $fittingTypeId)
    {
        return $query->where('fitting_type_id', $fittingTypeId);
    }

    public function scopeForSelection($query, int $clusterId, int $fittingTypeId)
    {
        return $query->byCluster($clusterId)
                    ->byFittingType($fittingTypeId)
                    ->available()
                    ->orderBy('joint_code');
    }

    // Helper methods
    public function markAsUsed(int $jointId): void
    {
        $this->update([
            'is_used' => true,
            'used_by_joint_id' => $jointId,
            'used_at' => now(),
            'updated_by' => auth()->id(),
        ]);
    }

    public function markAsAvailable(): void
    {
        $this->update([
            'is_used' => false,
            'used_by_joint_id' => null,
            'used_at' => null,
            'updated_by' => auth()->id(),
        ]);
    }

    public function getStatusLabelAttribute(): string
    {
        if (!$this->is_active) return 'Tidak Aktif';
        return $this->is_used ? 'Digunakan' : 'Tersedia';
    }

    public function getStatusColorAttribute(): string
    {
        if (!$this->is_active) return 'gray';
        return $this->is_used ? 'red' : 'green';
    }
}