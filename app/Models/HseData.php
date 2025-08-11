<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class HseData extends Model
{
    use HasFactory;

    protected $table = 'hse_data';

    protected $fillable = [
        'reff_id_pelanggan',
        'nomor_laporan_hse',
        'tanggal_laporan',
        'foto_hse_url',
        'original_filename',
        'file_size',
        'lokasi_foto',
        'catatan_singkat',
        'petugas_hse',
        'status',
        'created_by'
    ];

    protected $casts = [
        'tanggal_laporan' => 'date',
        'file_size' => 'integer',
    ];

    // Validation rules
    public static function rules($id = null): array
    {
        return [
            'reff_id_pelanggan' => 'required|string|exists:calon_pelanggan,reff_id_pelanggan',
            'tanggal_laporan' => 'required|date|before_or_equal:today',
            'foto_hse' => 'required|image|mimes:jpeg,png,jpg|max:10240', // 10MB max
            'lokasi_foto' => 'nullable|string|max:255',
            'catatan_singkat' => 'nullable|string|max:500',
            'petugas_hse' => 'required|string|max:255',
        ];
    }

    public static function messages(): array
    {
        return [
            'foto_hse.required' => 'Foto HSE harus diupload',
            'foto_hse.image' => 'File harus berupa gambar',
            'foto_hse.max' => 'Ukuran foto maksimal 10MB',
            'tanggal_laporan.before_or_equal' => 'Tanggal laporan tidak boleh lebih dari hari ini',
        ];
    }

    // Relationships
    public function calonPelanggan(): BelongsTo
    {
        return $this->belongsTo(CalonPelanggan::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'record_id', 'id')
                    ->where('table_name', 'hse_data');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByReffId($query, $reffId)
    {
        return $query->where('reff_id_pelanggan', $reffId);
    }

    public function scopeByTanggalLaporan($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal_laporan', [$startDate, $endDate]);
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->hasRole('hse') && !$user->hasRole(...['admin', 'super_admin'])) {
            return $query->where('created_by', $user->id);
        }

        return $query; // Admin/Super Admin can see all
    }

    public function scopeWithRelatedData($query)
    {
        return $query->with([
            'calonPelanggan:reff_id_pelanggan,nama_pelanggan,alamat',
            'createdBy:id,name'
        ]);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('tanggal_laporan', '>=', now()->subDays($days));
    }

    // Accessors
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'uploaded' => 'bg-blue-100 text-blue-800',
            'processed' => 'bg-green-100 text-green-800',
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFotoPreviewUrlAttribute(): string
    {
        // Generate preview URL for the photo
        return route('hse.preview', ['hseData' => $this->id]);
    }

    public function getFotoDownloadUrlAttribute(): string
    {
        // Generate download URL for the photo
        return route('hse.download', ['hseData' => $this->id]);
    }

    // Methods
    public function canBeEditedBy(User $user): bool
    {
        // HSE data biasanya tidak bisa diedit setelah diupload
        // Hanya bisa diedit oleh admin/super_admin dalam waktu 24 jam
        if ($user->hasRole(...['admin', 'super_admin'])) {
            return true;
        }

        // HSE user hanya bisa edit dalam 1 jam setelah upload
        if ($user->hasRole('hse') && $this->created_by === $user->id) {
            return $this->created_at->diffInHours(now()) <= 1;
        }

        return false;
    }

    public function canBeDeletedBy(User $user): bool
    {
        // Hanya super admin yang bisa delete HSE data
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // HSE user hanya bisa delete dalam 30 menit setelah upload
        if ($user->hasRole('hse') && $this->created_by === $user->id) {
            return $this->created_at->diffInMinutes(now()) <= 30;
        }

        return false;
    }

    public function canBeViewedBy(User $user): bool
    {
        // Admin dan super admin bisa lihat semua
        if ($user->hasRole(...['admin', 'super_admin'])) {
            return true;
        }

        // HSE user bisa lihat data mereka sendiri
        if ($user->hasRole('hse') && $this->created_by === $user->id) {
            return true;
        }

        // User lain dengan role yang relevan bisa lihat jika terkait dengan pelanggan mereka
        if ($this->calonPelanggan) {
            // Check if user has access to this customer through other roles
            $customer = $this->calonPelanggan;

            if ($user->hasRole('sk') && $customer->skData && $customer->skData->created_by === $user->id) {
                return true;
            }

            if ($user->hasRole('validasi') && $customer->validasi && $customer->validasi->validated_by === $user->id) {
                return true;
            }
        }

        return false;
    }

    public function markAsProcessed(): void
    {
        $this->update(['status' => 'processed']);
    }

    // Static methods
    public static function generateNomorLaporan(): string
    {
        $prefix = 'HSE-AERGAS-';
        $year = date('Y');
        $month = date('m');

        $maxAttempts = 10;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $lastRecord = static::where('nomor_laporan_hse', 'like', "{$prefix}{$year}{$month}%")
                               ->orderBy('nomor_laporan_hse', 'desc')
                               ->first();

            if ($lastRecord) {
                $lastNumber = (int) substr($lastRecord->nomor_laporan_hse, -4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            $nomorLaporan = $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

            if (!static::where('nomor_laporan_hse', $nomorLaporan)->exists()) {
                return $nomorLaporan;
            }
        }

        return $prefix . $year . $month . str_pad(time() % 10000, 4, '0', STR_PAD_LEFT);
    }

    public static function getUploadStats($userId = null, $days = 30): array
    {
        $query = static::query();

        if ($userId) {
            $query->where('created_by', $userId);
        }

        $recentQuery = clone $query;
        $recentQuery->where('tanggal_laporan', '>=', now()->subDays($days));

        return [
            'total' => $query->count(),
            'recent' => $recentQuery->count(),
            'uploaded' => (clone $query)->byStatus('uploaded')->count(),
            'processed' => (clone $query)->byStatus('processed')->count(),
            'total_file_size' => $query->sum('file_size'),
            'avg_file_size' => $query->avg('file_size'),
        ];
    }

    public static function getMonthlyStats($months = 6): array
    {
        return static::selectRaw('DATE_FORMAT(tanggal_laporan, "%Y-%m") as month, COUNT(*) as count, SUM(file_size) as total_size')
                     ->where('tanggal_laporan', '>=', now()->subMonths($months))
                     ->groupBy('month')
                     ->orderBy('month')
                     ->get()
                     ->toArray();
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->nomor_laporan_hse)) {
                $model->nomor_laporan_hse = static::generateNomorLaporan();
            }

            // Set default status if not provided
            if (empty($model->status)) {
                $model->status = 'uploaded';
            }
        });

        static::created(function ($model) {
            // Log creation automatically
            if (class_exists('App\Models\AuditLog')) {
                \App\Models\AuditLog::logCreate(
                    'hse_data',
                    (string)$model->id,
                    $model->toArray(),
                    "HSE Data uploaded for {$model->reff_id_pelanggan}",
                    Auth::user()
                );
            }
        });

        static::updating(function ($model) {
            // Track status changes
            if ($model->isDirty('status')) {
                $model->setAttribute('status_changed_at', now());
            }
        });

        static::updated(function ($model) {
            // Log updates automatically
            if (class_exists('App\Models\AuditLog')) {
                \App\Models\AuditLog::logUpdate(
                    'hse_data',
                    (string)$model->id,
                    $model->getOriginal(),
                    $model->toArray(),
                    "HSE Data updated",
                    Auth::user()
                );
            }
        });

        static::deleting(function ($model) {
            // Log deletion automatically
            if (class_exists('App\Models\AuditLog')) {
                \App\Models\AuditLog::logDelete(
                    'hse_data',
                    (string)$model->id,
                    $model->toArray(),
                    "HSE Data deleted",
                    Auth::user()
                );
            }
        });
    }

    // Additional helper methods
    public function isPhotoAccessible(): bool
    {
        return !empty($this->foto_hse_url) && filter_var($this->foto_hse_url, FILTER_VALIDATE_URL);
    }

    public function getPhotoMetadata(): array
    {
        return [
            'filename' => $this->original_filename,
            'size' => $this->file_size_human,
            'url' => $this->foto_hse_url,
            'uploaded_at' => $this->created_at->format('d/m/Y H:i:s'),
            'uploaded_by' => $this->createdBy ? $this->createdBy->name : 'Unknown'
        ];
    }

    public function hasLocation(): bool
    {
        return !empty($this->lokasi_foto);
    }

    public function hasNotes(): bool
    {
        return !empty($this->catatan_singkat);
    }

    public function isRecent($hours = 24): bool
    {
        return $this->created_at->diffInHours(now()) <= $hours;
    }

    public function getDaysOldAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    public function getIsEditableAttribute(): bool
    {
        $user = Auth::user();
        return $user ? $this->canBeEditedBy($user) : false;
    }

    public function getIsDeletableAttribute(): bool
    {
        $user = Auth::user();
        return $user ? $this->canBeDeletedBy($user) : false;
    }

    public function getIsViewableAttribute(): bool
    {
        $user = Auth::user();
        return $user ? $this->canBeViewedBy($user) : false;
    }
}
