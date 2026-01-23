<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class CalonPelanggan
 *
 * @property string $reff_id_pelanggan
 * @property string $nama_pelanggan
 * @property string $alamat
 * @property string $no_telepon
 * @property string|null $no_bagi
 * @property string|null $email
 * @property string|null $kelurahan
 * @property string|null $padukuhan
 * @property string|null $rt
 * @property string|null $rw
 * @property string $status
 * @property string $progress_status
 * @property string|null $jenis_pelanggan
 * @property string|null $keterangan
 * @property \Carbon\Carbon|null $tanggal_registrasi
 * @property \Carbon\Carbon|null $validated_at
 * @property int|null $validated_by
 * @property string|null $validation_notes
 * @property \Carbon\Carbon|null $last_login
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @property-read \App\Models\SkData|null $skData
 * @property-read \App\Models\SrData|null $srData
 * @property-read \App\Models\GasInData|null $gasInData
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PhotoApproval[] $photoApprovals
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AuditLog[] $auditLogs
 * @property-read \App\Models\User|null $validatedBy
 * @property-read string $display_reff_id
 */
class CalonPelanggan extends Model
{
    use HasFactory;

    protected $table = 'calon_pelanggan';
    protected $primaryKey = 'reff_id_pelanggan';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'tanggal_registrasi' => 'datetime',
        'validated_at' => 'datetime',
        'last_login' => 'datetime',
        'coordinate_updated_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'progress_percentage' => 'decimal:2',    // 0.00 to 100.00 (incremental based on CGP-approved photos)
        'status' => 'string',        // pending | lanjut | in_progress | batal
        'progress_status' => 'string',        // validasi | sk | sr | gas_in | done | batal
    ];

    /* =========================
     * RELATIONS (1:1 per modul)
     * ========================= */
    public function skData(): HasOne
    {
        return $this->hasOne(SkData::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }
    public function srData(): HasOne
    {
        return $this->hasOne(SrData::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }
    public function gasInData(): HasOne
    {
        return $this->hasOne(GasInData::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function photoApprovals(): HasMany
    {
        return $this->hasMany(PhotoApproval::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
    public function getRouteKeyName()
    {
        return 'reff_id_pelanggan';
    }

    /* =========================
     * SCOPES
     * ========================= */
    public function scopeSearch($q, ?string $term)
    {
        if (!$term)
            return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('nama_pelanggan', 'like', "%{$term}%")
                ->orWhere('reff_id_pelanggan', 'like', "%{$term}%")
                ->orWhere('alamat', 'like', "%{$term}%")
                ->orWhere('no_telepon', 'like', "%{$term}%");
        });
    }

    /**
     * Scope to exclude cancelled customers
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'batal');
    }

    /**
     * Scope to get customers with coordinates
     */
    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0);
    }

    /**
     * Check if customer is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'batal';
    }

    /**
     * Check if customer can proceed to next module
     */
    public function canProceedToModule(string $module): bool
    {
        if ($this->isCancelled()) {
            return false;
        }

        return $this->getNextAvailableModule() === $module;
    }

    /**
     * Check if customer has coordinates
     */
    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude)
            && $this->latitude != 0 && $this->longitude != 0;
    }

    /**
     * Get coordinates array
     */
    public function getCoordinates(): ?array
    {
        if (!$this->hasCoordinates()) {
            return null;
        }

        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude
        ];
    }

    /**
     * Set coordinates
     */
    public function setCoordinates(float $lat, float $lng, string $source = 'manual'): void
    {
        $this->update([
            'latitude' => $lat,
            'longitude' => $lng,
            'coordinate_source' => $source,
            'coordinate_updated_at' => now()
        ]);
    }

    /**
     * Get marker info for maps
     */
    public function getMarkerInfo(): array
    {
        return [
            'id' => $this->reff_id_pelanggan,
            'reff_id' => $this->display_reff_id,
            'title' => $this->nama_pelanggan,
            'alamat' => $this->alamat,
            'kelurahan' => $this->kelurahan,
            'padukuhan' => $this->padukuhan,
            'status' => $this->status,
            'progress_status' => $this->progress_status,
            'progress_percentage' => $this->getProgressPercentage(),
            'coordinates' => $this->getCoordinates(),
            'tanggal_registrasi' => $this->tanggal_registrasi?->format('Y-m-d'),
        ];
    }

    protected $fillable = [
        'reff_id_pelanggan',
        'nama_pelanggan',
        'alamat',
        'no_telepon',
        'no_bagi',
        'email',
        'kelurahan',
        'padukuhan',
        'rt',
        'rw',
        'status',
        'progress_status',
        'jenis_pelanggan',
        'keterangan',
        'tanggal_registrasi',
        'validated_at',
        'validated_by',
        'validation_notes',
        'latitude',
        'longitude',
        'coordinate_source',
        'coordinate_updated_at',
    ];

    /* =========================
     * HELPERS — Progress & Dependency
     * ========================= */

    public function getProgressPercentage(): int
    {
        if ($this->status === 'batal') {
            return 0;
        }

        // Parallel Workflow Percentage Calculation
        $totalModules = 3; // SK, SR, Gas In
        $completed = 0;

        if ($this->skData && $this->skData->module_status === 'completed')
            $completed++;
        if ($this->srData && $this->srData->module_status === 'completed')
            $completed++;
        if ($this->gasInData && $this->gasInData->module_status === 'completed')
            $completed++;

        return (int) round(($completed / $totalModules) * 100);
    }

    public function getNextAvailableModule(): ?string
    {
        // In parallel workflow, "Next Available" is ambiguous.
        // We will return null to disable the single "Next" button in UI.
        // The UI will likely show individual buttons for each module.
        return null;
    }

    /**
     * Get URL for next available module
     */
    public function getNextModuleUrl(): ?string
    {
        $nextModule = $this->getNextAvailableModule();

        if (!$nextModule) {
            return null;
        }

        $moduleRoutes = [
            'sk' => '/sk/create',
            'sr' => '/sr/create',
            'gas_in' => '/gas-in/create',
        ];

        $baseUrl = $moduleRoutes[$nextModule] ?? null;

        return $baseUrl ? $baseUrl . '?reff_id=' . $this->reff_id_pelanggan : null;
    }


    /**
     * Check if customer is validated
     */
    public function isValidated(): bool
    {
        return $this->status !== 'pending';
    }

    /**
     * Validate customer
     */
    public function validateCustomer(?int $userId = null, ?string $notes = null): bool
    {
        if ($this->status !== 'pending') {
            return false; // Already validated
        }

        $this->update([
            'status' => 'lanjut',
            'validated_at' => now(),
            'validated_by' => $userId,
            'validation_notes' => $notes,
        ]);

        return true;
    }

    /**
     * Reject customer validation
     */
    public function rejectValidation(?int $userId = null, ?string $notes = null): bool
    {
        $this->update([
            'status' => 'batal',
            'validated_at' => now(),
            'validated_by' => $userId,
            'validation_notes' => $notes,
        ]);

        return true;
    }

    /**
     * Get formatted display Reference ID (add 00 prefix for 6-digit numbers)
     */
    public function getDisplayReffIdAttribute(): string
    {
        if (!$this->reff_id_pelanggan) {
            return '';
        }

        $value = trim($this->reff_id_pelanggan);

        // Check if input is exactly 6 digits
        if (preg_match('/^\d{6}$/', $value)) {
            return '00' . $value;
        }

        return strtoupper($value);
    }

    /**
     * Static helper for formatting any reference ID
     */
    public static function formatReffId(?string $reffId): string
    {
        if (!$reffId) {
            return '';
        }

        $value = trim($reffId);

        // Check if input is exactly 6 digits
        if (preg_match('/^\d{6}$/', $value)) {
            return '00' . $value;
        }

        return strtoupper($value);
    }


    /**
     * Get marker color based on status
     */
    public function getMarkerColor(): string
    {
        return match ($this->status) {
            'pending' => '#3B82F6',      // blue
            'lanjut' => '#10B981',       // green
            'in_progress' => '#F59E0B',  // yellow
            'batal' => '#EF4444',        // red
            default => '#6B7280',        // gray
        };
    }

    /**
     * Get marker icon based on progress status
     */
    public function getMarkerIcon(): string
    {
        return match ($this->progress_status) {
            'validasi' => 'fa-user-check',
            'sk' => 'fa-wrench',
            'sr' => 'fa-home',
            'gas_in' => 'fa-fire',
            'done' => 'fa-check-circle',
            'batal' => 'fa-times-circle',
            default => 'fa-map-marker',
        };
    }
}
