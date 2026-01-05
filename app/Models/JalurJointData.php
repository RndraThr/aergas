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

        // Reorganize photos to jalur_joint_approved folder after CGP approval
        try {
            $folderOrgService = app(\App\Services\FolderOrganizationService::class);

            // Determine line_number_id for folder organization
            // Priority: 1) line_number_id (FK), 2) lookup from joint_line_from
            $lineNumberId = $this->line_number_id;

            if (!$lineNumberId && $this->joint_line_from) {
                $lineNumber = \App\Models\JalurLineNumber::where('line_number', $this->joint_line_from)->first();
                $lineNumberId = $lineNumber?->id;
            }

            if ($lineNumberId) {
                $result = $folderOrgService->organizeJalurPhotosAfterCgpApproval(
                    $lineNumberId,
                    $this->tanggal_joint->format('Y-m-d'),
                    'jalur_joint'
                );

                \Log::info('Jalur joint photos reorganized after CGP approval', [
                    'joint_id' => $this->id,
                    'line_number_id' => $lineNumberId,
                    'date' => $this->tanggal_joint->format('Y-m-d'),
                    'result' => $result
                ]);
            } else {
                \Log::warning('Cannot reorganize joint photos - no line_number_id found', [
                    'joint_id' => $this->id,
                    'joint_line_from' => $this->joint_line_from
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the approval process
            \Log::error('Failed to reorganize jalur joint photos after CGP approval', [
                'joint_id' => $this->id,
                'line_number_id' => $this->line_number_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return true;
    }

    /**
     * Sync module status based on photo approval statuses
     * Called by PhotoApprovalService after photo approval/rejection
     *
     * Override parent method because Jalur modules use different status system
     * (status_laporan instead of module_status)
     */
    public function syncModuleStatusFromPhotos(bool $save = true): string
    {
        $photos = $this->photoApprovals;

        // If no photos, keep status as draft
        if ($photos->isEmpty()) {
            if ($save) {
                $this->update(['status_laporan' => 'draft']);
            } else {
                $this->status_laporan = 'draft';
            }
            return 'draft';
        }

        // Count photo statuses
        $totalPhotos = $photos->count();
        $tracerApproved = $photos->where('photo_status', 'tracer_approved')->count();
        $cgpApproved = $photos->where('photo_status', 'cgp_approved')->count();
        $tracerRejected = $photos->where('photo_status', 'tracer_rejected')->count();
        $cgpRejected = $photos->where('photo_status', 'cgp_rejected')->count();
        $pending = $photos->whereIn('photo_status', ['draft', 'tracer_pending'])->count();

        // Determine status based on photo statuses
        $newStatus = 'draft';

        if ($cgpRejected > 0) {
            // If any photo rejected by CGP
            $newStatus = 'revisi_cgp';
        } elseif ($cgpApproved === $totalPhotos) {
            // All photos approved by CGP
            $newStatus = 'acc_cgp';
        } elseif ($tracerRejected > 0) {
            // If any photo rejected by Tracer
            $newStatus = 'revisi_tracer';
        } elseif ($tracerApproved === $totalPhotos) {
            // All photos approved by Tracer
            $newStatus = 'acc_tracer';
        } elseif ($tracerApproved > 0 && $pending > 0) {
            // Some approved, some pending - keep as draft
            $newStatus = 'draft';
        } else {
            // Default to draft
            $newStatus = 'draft';
        }

        // Update status if changed
        if ($this->status_laporan !== $newStatus) {
            if ($save) {
                $this->update(['status_laporan' => $newStatus]);
            } else {
                $this->status_laporan = $newStatus;
            }

            \Log::info('Joint status updated from photos', [
                'joint_id' => $this->id,
                'old_status' => $this->status_laporan,
                'new_status' => $newStatus,
                'photo_stats' => [
                    'total' => $totalPhotos,
                    'tracer_approved' => $tracerApproved,
                    'cgp_approved' => $cgpApproved,
                    'tracer_rejected' => $tracerRejected,
                    'cgp_rejected' => $cgpRejected,
                    'pending' => $pending
                ]
            ]);
        }

        return $newStatus;
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