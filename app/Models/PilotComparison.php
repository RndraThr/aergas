<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PilotComparison
 *
 * Model untuk menyimpan hasil perbandingan data PILOT sheet dengan database
 *
 * @property int $id
 * @property string $batch_id
 * @property string $reff_id_pelanggan
 * @property string|null $nama_pelanggan
 * @property string|null $alamat
 * @property \Carbon\Carbon|null $pilot_tanggal_sk
 * @property \Carbon\Carbon|null $pilot_tanggal_sr
 * @property \Carbon\Carbon|null $pilot_tanggal_gas_in
 * @property string|null $pilot_status_sk
 * @property string|null $pilot_status_sr
 * @property string|null $pilot_status_gas_in
 * @property array|null $pilot_raw_data
 * @property \Carbon\Carbon|null $db_tanggal_sk
 * @property \Carbon\Carbon|null $db_tanggal_sr
 * @property \Carbon\Carbon|null $db_tanggal_gas_in
 * @property string|null $db_status_sk
 * @property string|null $db_status_sr
 * @property string|null $db_status_gas_in
 * @property string $comparison_status
 * @property array|null $differences
 * @property int|null $uploaded_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @property-read \App\Models\User|null $uploader
 */
class PilotComparison extends Model
{
    protected $table = 'pilot_comparisons';

    protected $fillable = [
        'batch_id',
        'reff_id_pelanggan',
        'nama_pelanggan',
        'alamat',
        'pilot_tanggal_sk',
        'pilot_tanggal_sr',
        'pilot_tanggal_gas_in',
        'pilot_status_sk',
        'pilot_status_sr',
        'pilot_status_gas_in',
        'pilot_raw_data',
        'db_tanggal_sk',
        'db_tanggal_sr',
        'db_tanggal_gas_in',
        'db_status_sk',
        'db_status_sr',
        'db_status_gas_in',
        'comparison_status',
        'differences',
        'uploaded_by',
    ];

    protected $casts = [
        'pilot_tanggal_sk' => 'date',
        'pilot_tanggal_sr' => 'date',
        'pilot_tanggal_gas_in' => 'date',
        'db_tanggal_sk' => 'date',
        'db_tanggal_sr' => 'date',
        'db_tanggal_gas_in' => 'date',
        'pilot_raw_data' => 'array',
        'differences' => 'array',
    ];

    // Status constants
    public const STATUS_MATCH = 'match';
    public const STATUS_DATE_MISMATCH = 'date_mismatch';
    public const STATUS_STATUS_MISMATCH = 'status_mismatch';
    public const STATUS_MISSING_IN_DB = 'missing_in_db';
    public const STATUS_MISSING_IN_PILOT = 'missing_in_pilot';

    /**
     * Relation to uploader
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope to filter by batch
     */
    public function scopeByBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope to filter by comparison status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('comparison_status', $status);
    }

    /**
     * Get badge color for comparison status
     */
    public function getStatusBadgeColor(): string
    {
        return match ($this->comparison_status) {
            self::STATUS_MATCH => 'success',
            self::STATUS_DATE_MISMATCH => 'warning',
            self::STATUS_STATUS_MISMATCH => 'info',
            self::STATUS_MISSING_IN_DB => 'danger',
            self::STATUS_MISSING_IN_PILOT => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get human-readable status label
     */
    public function getStatusLabel(): string
    {
        return match ($this->comparison_status) {
            self::STATUS_MATCH => 'Data Cocok',
            self::STATUS_DATE_MISMATCH => 'Perbedaan Tanggal',
            self::STATUS_STATUS_MISMATCH => 'Perbedaan Status',
            self::STATUS_MISSING_IN_DB => 'Tidak Ada di Database',
            self::STATUS_MISSING_IN_PILOT => 'Tidak Ada di PILOT',
            default => 'Unknown',
        };
    }

    /**
     * Check if has any differences
     */
    public function hasDifferences(): bool
    {
        return !empty($this->differences) && count($this->differences) > 0;
    }

    /**
     * Get formatted differences for display
     */
    public function getFormattedDifferences(): array
    {
        if (!$this->hasDifferences()) {
            return [];
        }

        $formatted = [];
        foreach ($this->differences as $field => $diff) {
            $formatted[] = [
                'field' => $this->getFieldLabel($field),
                'pilot_value' => $diff['pilot'] ?? '-',
                'db_value' => $diff['db'] ?? '-',
            ];
        }

        return $formatted;
    }

    /**
     * Get human-readable field label
     */
    private function getFieldLabel(string $field): string
    {
        return match ($field) {
            'tanggal_sk' => 'Tanggal SK',
            'tanggal_sr' => 'Tanggal SR',
            'tanggal_gas_in' => 'Tanggal GAS IN',
            'status_sk' => 'Status SK',
            'status_sr' => 'Status SR',
            'status_gas_in' => 'Status GAS IN',
            'nama_pelanggan' => 'Nama Pelanggan',
            'alamat' => 'Alamat',
            default => ucwords(str_replace('_', ' ', $field)),
        };
    }
}
