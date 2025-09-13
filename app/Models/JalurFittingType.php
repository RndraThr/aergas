<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JalurFittingType extends Model
{
    use HasFactory;

    protected $table = 'jalur_fitting_types';
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relations
    public function jointData(): HasMany
    {
        return $this->hasMany(JalurJointData::class, 'fitting_type_id');
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
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        
        return $query->where(function ($q) use ($term) {
            $q->where('nama_fitting', 'like', "%{$term}%")
              ->orWhere('code_fitting', 'like', "%{$term}%");
        });
    }

    // Helper methods
    public function getNextJointCode(string $clusterCode): string
    {
        $lastJoint = JalurJointData::where('cluster_id', function($query) use ($clusterCode) {
                $query->select('id')
                      ->from('jalur_clusters')
                      ->where('code_cluster', $clusterCode)
                      ->limit(1);
            })
            ->where('fitting_type_id', $this->id)
            ->orderBy('joint_code', 'desc')
            ->first();

        if (!$lastJoint) {
            return '001';
        }

        // Extract number from joint_code (e.g., "CP001" -> 1)
        $lastNumber = (int) substr($lastJoint->joint_code, -3);
        $nextNumber = $lastNumber + 1;

        return str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function getTotalJointsAttribute(): int
    {
        return $this->jointData()->count();
    }
}