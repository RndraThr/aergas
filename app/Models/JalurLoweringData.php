<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JalurLoweringData extends BaseModuleModel
{
    use HasFactory, SoftDeletes;

    private static array $originalDates = [];

    protected $table = 'jalur_lowering_data';
    protected $guarded = [];

    protected $casts = [
        'tanggal_jalur' => 'date',
        'penggelaran' => 'decimal:2',
        'bongkaran' => 'decimal:2',
        'kedalaman_lowering' => 'integer',
        'aksesoris_cassing' => 'boolean',
        'aksesoris_marker_tape' => 'boolean',
        'aksesoris_concrete_slab' => 'boolean',
        'marker_tape_quantity' => 'decimal:2',
        'concrete_slab_quantity' => 'integer',
        'cassing_quantity' => 'decimal:2',
        'cassing_type' => 'string',
        'tipe_material' => 'string',
        'tracer_approved_at' => 'datetime',
        'cgp_approved_at' => 'datetime',
    ];

    // Relations
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
        return $this->hasMany(PhotoApproval::class, 'module_record_id')
                   ->where('module_name', 'jalur_lowering');
    }

    // Scopes
    public function scopeByLineNumber($query, int $lineNumberId)
    {
        return $query->where('line_number_id', $lineNumberId);
    }

    public function scopeByTipeBongkaran($query, string $tipe)
    {
        return $query->where('tipe_bongkaran', $tipe);
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
        return 'jalur_lowering';
    }

    public function getRequiredPhotos(): array
    {
        $photos = [
            'foto_evidence_penggelaran_bongkaran',
            'foto_evidence_kedalaman_lowering',
        ];
        
        // Add accessory photos based on tipe_bongkaran
        if ($this->tipe_bongkaran === 'Open Cut') {
            $photos[] = 'foto_evidence_marker_tape';
            $photos[] = 'foto_evidence_concrete_slab';
            $photos[] = 'foto_evidence_cassing';
        } elseif (in_array($this->tipe_bongkaran, ['Crossing', 'Zinker'])) {
            $photos[] = 'foto_evidence_cassing';
        }
        
        return $photos;
    }

    // Helper methods
    public function getAksesorisRequired(): array
    {
        return match ($this->tipe_bongkaran) {
            'Open Cut' => ['marker_tape', 'concrete_slab', 'cassing'],
            'Crossing', 'Zinker' => ['cassing'],
            default => [],
        };
    }

    public function shouldShowAksesoris(string $aksesoris): bool
    {
        $required = $this->getAksesorisRequired();
        return in_array($aksesoris, $required);
    }

    public function updateLineNumberTotals(): void
    {
        $this->lineNumber->updateTotalPenggelaran();
        $this->lineNumber->updateStatus();
    }

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

        $this->updateLineNumberTotals();
        
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

        $this->updateLineNumberTotals();
        
        return true;
    }

    protected static function booted(): void
    {
        static::created(function (JalurLoweringData $lowering) {
            $lowering->updateLineNumberTotals();
        });

        static::updating(function (JalurLoweringData $lowering) {
            // Store original date before update for comparison using static array
            $originalDate = $lowering->getOriginal('tanggal_jalur');
            if ($originalDate) {
                self::$originalDates[$lowering->id] = $originalDate;
            }
        });

        static::updated(function (JalurLoweringData $lowering) {
            $lowering->updateLineNumberTotals();

            // Check if tanggal_jalur was changed and trigger folder reorganization
            if (isset(self::$originalDates[$lowering->id])) {
                $oldDate = self::$originalDates[$lowering->id];
                $newDate = $lowering->tanggal_jalur->format('Y-m-d');

                // Convert old date to string format if it's a Carbon instance
                if ($oldDate instanceof \Carbon\Carbon) {
                    $oldDate = $oldDate->format('Y-m-d');
                }

                if ($oldDate !== $newDate) {
                    $lowering->handleDateChangeFolderReorganization($oldDate, $newDate);
                }

                // Clean up static array entry
                unset(self::$originalDates[$lowering->id]);
            }
        });

        static::deleted(function (JalurLoweringData $lowering) {
            $lowering->updateLineNumberTotals();
        });
    }

    /**
     * Handle folder reorganization when tanggal_jalur changes
     */
    protected function handleDateChangeFolderReorganization(string $oldDate, string $newDate): void
    {
        try {
            // Only reorganize if there are photos to move
            $hasPhotos = \App\Models\PhotoApproval::where('module_name', 'jalur_lowering')
                ->where('module_record_id', $this->id)
                ->exists();

            if (!$hasPhotos) {
                \Log::info('No photos to reorganize for date change', [
                    'lowering_id' => $this->id,
                    'old_date' => $oldDate,
                    'new_date' => $newDate
                ]);
                return;
            }

            // Get Google Drive service
            $googleDriveService = app(\App\Services\GoogleDriveService::class);

            // Dispatch reorganization job
            \App\Jobs\ReorganizeJalurLoweringFolder::dispatch(
                $this->id,
                $oldDate,
                $newDate
            )->onQueue('default');

            \Log::info('Folder reorganization job queued', [
                'lowering_id' => $this->id,
                'old_date' => $oldDate,
                'new_date' => $newDate
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to queue folder reorganization', [
                'lowering_id' => $this->id,
                'old_date' => $oldDate,
                'new_date' => $newDate,
                'error' => $e->getMessage()
            ]);
        }
    }
}