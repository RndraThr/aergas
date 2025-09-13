<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JalurLineNumber extends Model
{
    use HasFactory;

    protected $table = 'jalur_line_numbers';
    protected $guarded = [];

    protected $casts = [
        'estimasi_panjang' => 'decimal:2',
        'actual_mc100' => 'decimal:2',
        'total_penggelaran' => 'decimal:2',
        'is_active' => 'boolean',
        'diameter' => 'string', // Explicitly cast diameter to string for ENUM compatibility
    ];

    // Relations
    public function cluster(): BelongsTo
    {
        return $this->belongsTo(JalurCluster::class, 'cluster_id');
    }

    public function loweringData(): HasMany
    {
        return $this->hasMany(\App\Models\JalurLoweringData::class, 'line_number_id');
    }

    public function jointData(): HasMany
    {
        return $this->hasMany(\App\Models\JalurJointData::class, 'line_number_id');
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

    public function scopeByDiameter($query, string $diameter)
    {
        return $query->where('diameter', $diameter);
    }

    public function scopeByCluster($query, int $clusterId)
    {
        return $query->where('cluster_id', $clusterId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status_line', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status_line', 'in_progress');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        
        return $query->where(function ($q) use ($term) {
            $q->where('line_number', 'like', "%{$term}%")
              ->orWhere('line_code', 'like', "%{$term}%")
              ->orWhereHas('cluster', function($qq) use ($term) {
                  $qq->where('nama_cluster', 'like', "%{$term}%")
                     ->orWhere('code_cluster', 'like', "%{$term}%");
              });
        });
    }

    // Helper methods
    public function updateTotalPenggelaran(): void
    {
        $total = $this->loweringData()->sum('penggelaran');
        $this->update(['total_penggelaran' => $total]);
    }

    public function updateStatus(): void
    {
        $status = 'draft';
        
        if ($this->loweringData()->count() > 0) {
            $status = 'in_progress';
        }
        
        if ($this->actual_mc100 !== null) {
            $status = 'completed';
        }

        $this->update(['status_line' => $status]);
    }

    public function getProgressPercentage(): float
    {
        if ($this->estimasi_panjang <= 0) {
            return 0;
        }

        $progress = ($this->total_penggelaran / $this->estimasi_panjang) * 100;
        return min(100, $progress);
    }

    public function getVarianceFromEstimate(): float
    {
        if (!$this->actual_mc100 || $this->estimasi_panjang <= 0) {
            return 0;
        }

        return $this->actual_mc100 - $this->estimasi_panjang;
    }

    public function getVariancePercentage(): float
    {
        if (!$this->actual_mc100 || $this->estimasi_panjang <= 0) {
            return 0;
        }

        return (($this->actual_mc100 - $this->estimasi_panjang) / $this->estimasi_panjang) * 100;
    }

    public function canUpdateMC100(): bool
    {
        return $this->loweringData()->count() > 0 && $this->status_line !== 'draft';
    }

    public function getFormattedLineNumberAttribute(): string
    {
        return $this->line_number;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_line) {
            'draft' => 'Draft',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            default => ucfirst($this->status_line),
        };
    }
}