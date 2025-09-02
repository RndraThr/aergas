<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'validated_at'       => 'datetime',
        'last_login'         => 'datetime',
        'status'             => 'string',        // pending | lanjut | in_progress | batal
        'progress_status'    => 'string',        // validasi | sk | sr | gas_in | done | batal
    ];

    /* =========================
     * RELATIONS (1:1 per modul)
     * ========================= */
    public function skData(): HasOne { return $this->hasOne(SkData::class, 'reff_id_pelanggan', 'reff_id_pelanggan'); }
    public function srData(): HasOne { return $this->hasOne(SrData::class, 'reff_id_pelanggan', 'reff_id_pelanggan'); }
    public function gasInData(): HasOne { return $this->hasOne(GasInData::class, 'reff_id_pelanggan', 'reff_id_pelanggan'); }

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
        if (!$term) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('nama_pelanggan', 'like', "%{$term}%")
               ->orWhere('reff_id_pelanggan', 'like', "%{$term}%")
               ->orWhere('alamat', 'like', "%{$term}%")
               ->orWhere('no_telepon', 'like', "%{$term}%");
        });
    }

    protected $fillable = [
        'reff_id_pelanggan','nama_pelanggan','alamat','no_telepon','email',
        'kelurahan','padukuhan','status','progress_status','jenis_pelanggan','keterangan',
        'tanggal_registrasi','validated_at','validated_by','validation_notes',
    ];

    /* =========================
     * HELPERS — Progress & Dependency
     * ========================= */

    public function getProgressPercentage(): int
    {
        $steps = ['validasi','sk','sr','gas_in','done'];
        $idx = array_search($this->progress_status, $steps, true);
        if ($idx === false) return 0;
        $max = count($steps) - 1;
        return (int) round(($idx / $max) * 100);
    }

    public function getNextAvailableModule(): ?string
    {
        if ($this->status === 'batal' || $this->progress_status === 'batal') return null;

        $order = ['validasi','sk','sr','gas_in','done'];
        $pos = array_search($this->progress_status, $order, true);
        if ($pos === false || $this->progress_status === 'done') return null;

        // Naikkan kalau dependency sudah terpenuhi
        $next = $order[$pos] === 'validasi' ? 'sk' : $order[$pos]; // dari validasi → sk

        // Hard dependency minimal:
        if ($next === 'sr' && !$this->skData?->module_status === 'completed') return null;
        if ($next === 'gas_in' && (!($this->skData?->module_status === 'completed') || !($this->srData?->module_status === 'completed'))) return null;

        return $next;
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
     * Boleh lanjut ke modul X?
     * - sk: status pelanggan harus validated/in_progress
     * - sr: SK completed
     * - gas_in: SK & SR completed
     * - mgrt, jalur_pipa, penyambungan: (sementara) true atau tambahkan rule saat sudah fix
     */
    public function canProceedToModule(string $module): bool
    {
        $module = strtolower($module);

        return match ($module) {
            'sk' => in_array($this->status, ['lanjut','in_progress'], true),
            'sr' => ($this->skData?->module_status === 'completed'),
            'gas_in' => ($this->skData?->module_status === 'completed') && ($this->srData?->module_status === 'completed'),
            default => false,
        };
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
}
