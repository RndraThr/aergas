<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JalurJointData extends BaseModuleModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'jalur_joint_data';
    protected $guarded = [];

    protected $casts = [
        'tanggal_joint' => 'date',
        'tracer_approved_at' => 'datetime',
        'cgp_approved_at' => 'datetime',
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


    public function lineNumber(): BelongsTo
    {
        return $this->belongsTo(JalurLineNumber::class, 'line_number_id');
    }

    public function tracerApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tracer_approved_by');
    }

    public function cgpApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cgp_approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function photoApprovals(): HasMany
    {
        return $this->hasMany(PhotoApproval::class, 'module_record_id', 'id')
                    ->where('module_name', 'jalur_joint');
    }

    // Scopes
    public function scopeByCluster($query, int $clusterId)
    {
        return $query->where('cluster_id', $clusterId);
    }

    public function scopeByFittingType($query, int $fittingTypeId)
    {
        return $query->where('fitting_type_id', $fittingTypeId);
    }

    public function scopeByTipePenyambungan($query, string $tipe)
    {
        return $query->where('tipe_penyambungan', $tipe);
    }

    public function scopeAccTracer($query)
    {
        return $query->where('status_laporan', 'acc_tracer');
    }

    public function scopeAccCgp($query)
    {
        return $query->where('status_laporan', 'acc_cgp');
    }

    public function scopeNeedsRevision($query)
    {
        return $query->whereIn('status_laporan', ['revisi_tracer', 'revisi_cgp']);
    }

    // Implement abstract methods from BaseModuleModel
    public function getModuleName(): string
    {
        return 'jalur_joint';
    }

    public function getRequiredPhotos(): array
    {
        return [
            'foto_evidence_joint',
        ];
    }

    // Helper methods
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_laporan) {
            'draft' => 'Draft',
            'acc_tracer' => 'ACC Tracer',
            'acc_cgp' => 'ACC CGP',
            'revisi_tracer' => 'Revisi Tracer',
            'revisi_cgp' => 'Revisi CGP',
            default => ucfirst($this->status_laporan),
        };
    }

    public function canApproveByTracer(): bool
    {
        return $this->status_laporan === 'draft' && $this->tracer_approved_at === null;
    }

    public function canApproveByCgp(): bool
    {
        return $this->status_laporan === 'acc_tracer' && $this->cgp_approved_at === null;
    }

    public function approveByTracer(int $userId, ?string $notes = null): bool
    {
        if (!$this->canApproveByTracer()) {
            return false;
        }

        $this->update([
            'status_laporan' => 'acc_tracer',
            'tracer_approved_at' => now(),
            'tracer_approved_by' => $userId,
            'tracer_notes' => $notes,
        ]);
        
        return true;
    }

    public function approveByCgp(int $userId, ?string $notes = null): bool
    {
        if (!$this->canApproveByCgp()) {
            return false;
        }

        $this->update([
            'status_laporan' => 'acc_cgp',
            'cgp_approved_at' => now(),
            'cgp_approved_by' => $userId,
            'cgp_notes' => $notes,
        ]);
        
        return true;
    }

    public function parseJointLines(): array
    {
        $lines = explode(' -> ', $this->joint_line_to_line);
        return [
            'from' => $lines[0] ?? '',
            'to' => $lines[1] ?? '',
        ];
    }

    public function getFormattedJointLineAttribute(): string
    {
        $base = $this->joint_line_from . ' → ' . $this->joint_line_to;
        
        // Add optional line for Equal Tee (3-way connection)
        if ($this->joint_line_optional && $this->isEqualTee()) {
            $base .= ' → ' . $this->joint_line_optional;
        }
        
        return $base;
    }
    
    public function getAllJointLines(): array
    {
        $lines = [
            'from' => $this->joint_line_from,
            'to' => $this->joint_line_to,
        ];
        
        if ($this->joint_line_optional && $this->isEqualTee()) {
            $lines['optional'] = $this->joint_line_optional;
        }
        
        return array_filter($lines); // Remove empty values
    }
    
    public function isEqualTee(): bool
    {
        return $this->fittingType && $this->fittingType->code_fitting === 'ET';
    }
    
    public function requiresThirdLine(): bool
    {
        return $this->isEqualTee();
    }

    public static function generateNomorJoint(int $clusterId, int $fittingTypeId): string
    {
        $cluster = JalurCluster::find($clusterId);
        $fitting = JalurFittingType::find($fittingTypeId);
        
        if (!$cluster || !$fitting) {
            throw new \Exception('Invalid cluster or fitting type');
        }

        $nextCode = $fitting->getNextJointCode($cluster->code_cluster);
        
        return $cluster->generateJointNumber($fitting->code_fitting, $nextCode);
    }
}