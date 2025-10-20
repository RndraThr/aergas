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

    protected $appends = ['status_label', 'display_info'];

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

        // Check if there's any lowering data
        if ($this->loweringData()->count() > 0) {
            $status = 'in_progress';
        }

        // Mark as completed if:
        // 1. actual_mc100 is set (final measurement), OR
        // 2. total_penggelaran reaches or exceeds estimasi_panjang (work complete)
        if ($this->actual_mc100 !== null) {
            $status = 'completed';
        } elseif ($this->estimasi_panjang > 0 && $this->total_penggelaran >= $this->estimasi_panjang) {
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

    /**
     * Get accessor for MC-0 (formerly Estimasi)
     */
    public function getMc0Attribute(): float
    {
        return $this->estimasi_panjang;
    }

    /**
     * Get accessor for Actual Lowering (formerly Penggelaran Total)
     */
    public function getActualLoweringAttribute(): float
    {
        return $this->total_penggelaran;
    }

    /**
     * Get accessor for MC-100
     */
    public function getMc100Attribute(): ?float
    {
        return $this->actual_mc100;
    }

    /**
     * Get formatted display labels with new terminology
     */
    public function getDisplayLabels(): array
    {
        return [
            'mc0' => [
                'label' => 'MC-0',
                'value' => $this->estimasi_panjang,
                'unit' => 'm',
                'color' => 'blue'
            ],
            'actual_lowering' => [
                'label' => 'Actual Lowering',
                'value' => $this->total_penggelaran,
                'unit' => 'm',
                'percentage' => $this->getProgressPercentage(),
                'color' => 'indigo'
            ],
            'mc100' => [
                'label' => 'MC-100',
                'value' => $this->actual_mc100,
                'unit' => 'm',
                'color' => $this->actual_mc100 ? 'green' : 'gray',
                'is_set' => !is_null($this->actual_mc100)
            ],
            'variance' => [
                'label' => 'Variance',
                'value' => $this->getVarianceFromEstimate(),
                'percentage' => $this->getVariancePercentage(),
                'unit' => 'm',
                'color' => $this->getVarianceFromEstimate() < 0 ? 'red' : 'green'
            ]
        ];
    }

    /**
     * Get display info array for API responses
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'mc0' => number_format($this->estimasi_panjang, 2) . ' m',
            'actual_lowering' => number_format($this->total_penggelaran, 2) . ' m (' . number_format($this->getProgressPercentage(), 2) . '%)',
            'mc100' => $this->actual_mc100 ? number_format($this->actual_mc100, 2) . ' m' : 'Not Set',
            'variance' => $this->actual_mc100 ?
                ($this->getVarianceFromEstimate() > 0 ? '+' : '') . number_format($this->getVarianceFromEstimate(), 2) . ' m (' .
                ($this->getVariancePercentage() > 0 ? '+' : '') . number_format($this->getVariancePercentage(), 2) . '%)' :
                '-',
            'status' => $this->status_label
        ];
    }
}