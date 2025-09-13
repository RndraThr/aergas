<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JalurCluster extends Model
{
    use HasFactory;

    protected $table = 'jalur_clusters';
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relations
    public function lineNumbers(): HasMany
    {
        return $this->hasMany(JalurLineNumber::class, 'cluster_id');
    }

    public function jointData(): HasMany
    {
        return $this->hasMany(JalurJointData::class, 'cluster_id');
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
            $q->where('nama_cluster', 'like', "%{$term}%")
              ->orWhere('code_cluster', 'like', "%{$term}%");
        });
    }

    // Helper methods
    public function generateLineNumber(string $diameter, string $lineCode): string
    {
        return "{$diameter}-{$this->code_cluster}-{$lineCode}";
    }

    public function generateJointNumber(string $fittingCode, string $jointCode): string
    {
        return "{$this->code_cluster}-{$fittingCode}{$jointCode}";
    }

    public function getTotalLineNumbersAttribute(): int
    {
        return $this->lineNumbers()->count();
    }

    public function getActiveLineNumbersAttribute(): int
    {
        return $this->lineNumbers()->where('is_active', true)->count();
    }
}