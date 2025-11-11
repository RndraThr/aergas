<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HseDailyReport extends Model
{
    protected $fillable = [
        'tanggal_laporan',
        'nama_proyek',
        'pemberi_pekerjaan',
        'kontraktor',
        'sub_kontraktor',
        'cuaca',
        'jka_hari_ini',
        'jka_kumulatif',
        'total_pekerja',
        'catatan',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'tanggal_laporan' => 'date',
        'jka_hari_ini' => 'integer',
        'jka_kumulatif' => 'integer',
        'total_pekerja' => 'integer',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function pekerjaanHarian(): HasMany
    {
        return $this->hasMany(HsePekerjaanHarian::class, 'daily_report_id');
    }

    public function tenagaKerja(): HasMany
    {
        return $this->hasMany(HseTenagaKerja::class, 'daily_report_id');
    }

    public function toolboxMeeting(): HasOne
    {
        return $this->hasOne(HseToolboxMeeting::class, 'daily_report_id');
    }

    public function programHarian(): HasMany
    {
        return $this->hasMany(HseProgramHarian::class, 'daily_report_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(HsePhoto::class, 'daily_report_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('tanggal_laporan', $date);
    }

    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('tanggal_laporan', $year)
                     ->whereMonth('tanggal_laporan', $month);
    }

    // Helper Methods
    public function calculateTotalPekerja(): int
    {
        return $this->tenagaKerja()->sum('jumlah_orang');
    }

    public function calculateJkaHariIni(): int
    {
        // JKA = Total Pekerja x 8 jam
        return $this->calculateTotalPekerja() * 8;
    }

    public function updateTotals(): void
    {
        $this->update([
            'total_pekerja' => $this->calculateTotalPekerja(),
            'jka_hari_ini' => $this->calculateJkaHariIni(),
        ]);
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'draft' => 'bg-gray-100 text-gray-700',
            'submitted' => 'bg-blue-100 text-blue-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    public function getCuacaIcon(): string
    {
        return match($this->cuaca) {
            'cerah' => '☀️',
            'berawan' => '⛅',
            'mendung' => '☁️',
            'hujan' => '🌧️',
            'hujan_lebat' => '⛈️',
            default => '☀️',
        };
    }

    public function canEdit(): bool
    {
        return $this->status === 'draft' || $this->status === 'rejected';
    }

    public function canSubmit(): bool
    {
        return $this->status === 'draft' &&
               $this->pekerjaanHarian()->count() > 0 &&
               $this->tenagaKerja()->count() > 0;
    }

    public function canApprove(): bool
    {
        return $this->status === 'submitted';
    }

    // Actions
    public function submit(): bool
    {
        if (!$this->canSubmit()) {
            return false;
        }

        return $this->update(['status' => 'submitted']);
    }

    public function approve(int $userId): bool
    {
        if (!$this->canApprove()) {
            return false;
        }

        return $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function reject(): bool
    {
        if ($this->status !== 'submitted') {
            return false;
        }

        return $this->update(['status' => 'rejected']);
    }

    // Aggregates
    public function getTotalPekerjaByKategori(string $kategori): int
    {
        return $this->tenagaKerja()
                    ->where('kategori_team', $kategori)
                    ->sum('jumlah_orang');
    }

    public function getPhotosByCategory(string $category)
    {
        return $this->photos()->where('photo_category', $category)->get();
    }
}
