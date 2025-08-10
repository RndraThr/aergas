<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Validasi extends Model
{
    use HasFactory;

    protected $table = 'validasi';

    // REDESIGNED: Only validation-specific fields, NO duplication
    protected $fillable = [
        'reff_id_pelanggan',
        'hasil_validasi',
        'status_validasi',
        'catatan_validasi',
        'dokumen_pendukung',
        'alamat_sesuai',
        'identitas_valid',
        'akses_lokasi_memadai',
        'persyaratan_teknis_terpenuhi',
        'catatan_teknis',
        'keputusan_akhir',
        'alasan_keputusan',
        'tanggal_keputusan',
        'ada_kesalahan_data',
        'deskripsi_kesalahan',
        'status_laporan',
        'validated_by',
        'validated_at'
    ];

    protected $casts = [
        'tanggal_keputusan' => 'date',
        'validated_at' => 'datetime',
        'dokumen_pendukung' => 'array',
        'alamat_sesuai' => 'boolean',
        'identitas_valid' => 'boolean',
        'akses_lokasi_memadai' => 'boolean',
        'persyaratan_teknis_terpenuhi' => 'boolean',
        'ada_kesalahan_data' => 'boolean',
    ];

    // ADDED: Validation rules
    public static function rules($id = null): array
    {
        return [
            'reff_id_pelanggan' => 'required|string|exists:calon_pelanggan,reff_id_pelanggan',  $id ?'':'unique:validasi,reff_id_pelanggan',
            'hasil_validasi' => 'nullable|string',
            'status_validasi' => 'required|in:pending,valid,invalid,need_review',
            'catatan_validasi' => 'nullable|string',
            'dokumen_pendukung' => 'nullable|array',
            'alamat_sesuai' => 'nullable|boolean',
            'identitas_valid' => 'nullable|boolean',
            'akses_lokasi_memadai' => 'nullable|boolean',
            'persyaratan_teknis_terpenuhi' => 'nullable|boolean',
            'catatan_teknis' => 'nullable|string',
            'keputusan_akhir' => 'nullable|in:lanjut,tidak_lanjut,pending_review',
            'alasan_keputusan' => 'nullable|string',
            'tanggal_keputusan' => 'nullable|date',
            'deskripsi_kesalahan' => 'nullable|string',
            'status_laporan' => 'nullable|in:none,reported,in_review,resolved',
        ];
    }

    // ADDED: Validation messages
    public static function messages(): array
    {
        return [
            'reff_id_pelanggan.required' => 'Reference ID pelanggan harus diisi',
            'reff_id_pelanggan.exists' => 'Reference ID pelanggan tidak valid',
            'status_validasi.required' => 'Status validasi harus dipilih',
            'status_validasi.in' => 'Status validasi tidak valid',
            'keputusan_akhir.in' => 'Keputusan akhir tidak valid',
            'status_laporan.in' => 'Status laporan tidak valid',
        ];
    }

    // Relationships
    public function calonPelanggan(): BelongsTo
    {
        return $this->belongsTo(CalonPelanggan::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function fileStorages(): HasMany
    {
        return $this->hasMany(FileStorage::class, 'related_id', 'id')
                    ->where('related_table', 'validasi');
    }

    // ADDED: Audit logs relation
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'record_id', 'id')
                    ->where('table_name', 'validasi');
    }

    // Scopes
    public function scopeByReffId($query, $reffId)
    {
        return $query->where('reff_id_pelanggan', $reffId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status_validasi', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status_validasi', 'pending');
    }

    public function scopeNeedReview($query)
    {
        return $query->where('status_validasi', 'need_review')
                    ->orWhere('ada_kesalahan_data', true);
    }

    public function scopeByKeputusan($query, $keputusan)
    {
        return $query->where('keputusan_akhir', $keputusan);
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->hasRole('validasi')) {
            return $query->where('validated_by', $user->id);
        }
        
        return $query; // Admin/Super Admin can see all
    }

    public function scopeWithCustomerData($query)
    {
        return $query->with('calonPelanggan');
    }

    // Accessors
    public function getStatusValidasiBadgeAttribute(): string
    {
        $badges = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'valid' => 'bg-green-100 text-green-800',
            'invalid' => 'bg-red-100 text-red-800',
            'need_review' => 'bg-orange-100 text-orange-800',
        ];

        return $badges[$this->status_validasi] ?? 'bg-gray-100 text-gray-800';
    }

    public function getKeputusanBadgeAttribute(): string
    {
        $badges = [
            'lanjut' => 'bg-green-100 text-green-800',
            'tidak_lanjut' => 'bg-red-100 text-red-800',
            'pending_review' => 'bg-yellow-100 text-yellow-800',
        ];

        return $badges[$this->keputusan_akhir] ?? 'bg-gray-100 text-gray-800';
    }

    public function getValidasiScoreAttribute(): float
    {
        $criteriaFields = [
            'alamat_sesuai',
            'identitas_valid', 
            'akses_lokasi_memadai',
            'persyaratan_teknis_terpenuhi'
        ];

        $score = 0;
        $total = 0;

        foreach ($criteriaFields as $field) {
            if (!is_null($this->$field)) {
                $total++;
                if ($this->$field) {
                    $score++;
                }
            }
        }

        return $total > 0 ? round(($score / $total) * 100, 2) : 0;
    }

    // REDESIGNED: Get customer data through relationship
    public function getCustomerNameAttribute(): ?string
    {
        return $this->calonPelanggan?->nama_pelanggan;
    }

    public function getCustomerAddressAttribute(): ?string
    {
        if (!$this->calonPelanggan) return null;
        
        $customer = $this->calonPelanggan;
        $rt = $customer->rt ?: 'N/A';
        $rw = $customer->rw ?: 'N/A';
        
        return "{$customer->alamat}, RT {$rt}/RW {$rw}, {$customer->kelurahan}";
    }

    public function getCustomerPhoneAttribute(): ?string
    {
        return $this->calonPelanggan?->no_telepon_pelanggan;
    }

    // ADDED: Check if validation is complete
    public function getIsCompleteAttribute(): bool
    {
        $requiredCriteria = [
            'alamat_sesuai',
            'identitas_valid',
            'akses_lokasi_memadai',
            'persyaratan_teknis_terpenuhi'
        ];

        foreach ($requiredCriteria as $criteria) {
            if (is_null($this->$criteria)) {
                return false;
            }
        }

        return !empty($this->keputusan_akhir);
    }

    // ADDED: Get missing validations
    public function getMissingValidationsAttribute(): array
    {
        $missing = [];
        $criteria = [
            'alamat_sesuai' => 'Validasi Alamat',
            'identitas_valid' => 'Validasi Identitas',
            'akses_lokasi_memadai' => 'Validasi Akses Lokasi',
            'persyaratan_teknis_terpenuhi' => 'Validasi Persyaratan Teknis'
        ];

        foreach ($criteria as $field => $label) {
            if (is_null($this->$field)) {
                $missing[] = $label;
            }
        }

        if (empty($this->keputusan_akhir)) {
            $missing[] = 'Keputusan Akhir';
        }

        return $missing;
    }

    // Methods
    public function canBeEditedBy(User $user): bool
    {
        // Validasi role can edit if they are assigned or created it
        if ($user->hasRole('validasi') && $this->validated_by === $user->id) {
            return true;
        }

        // Admin and Super Admin can always edit
        return $user->hasRole(['admin', 'super_admin']);
    }

    public function canBeDeletedBy(User $user): bool
    {
        // Only allow deletion if pending status
        if ($this->status_validasi !== 'pending') {
            return false;
        }

        // Validasi role can delete their own pending validation
        if ($user->hasRole('validasi') && $this->validated_by === $user->id) {
            return true;
        }

        // Admin and Super Admin can delete any pending validation
        return $user->hasRole(['admin', 'super_admin']);
    }

    public function getEditableFieldsFor(User $user): array
    {
        // Fields that can be edited by validasi role
        $validasiEditableFields = [
            'hasil_validasi',
            'status_validasi',
            'catatan_validasi',
            'alamat_sesuai',
            'identitas_valid',
            'akses_lokasi_memadai',
            'persyaratan_teknis_terpenuhi',
            'catatan_teknis',
            'keputusan_akhir',
            'alasan_keputusan',
            'tanggal_keputusan',
            'ada_kesalahan_data',
            'deskripsi_kesalahan'
        ];

        if ($user->hasRole('validasi')) {
            return $validasiEditableFields;
        }

        // Admin and Super Admin can edit all fields
        if ($user->hasRole(['admin', 'super_admin'])) {
            return array_keys($this->fillable);
        }

        return [];
    }

    // IMPROVED: Better error handling
    public function validateCustomer(User $user): array
    {
        if (!$this->canBeEditedBy($user)) {
            return ['success' => false, 'message' => 'Anda tidak memiliki permission untuk validasi data ini'];
        }

        // Check if validation data is complete
        if (!$this->is_complete) {
            return [
                'success' => false, 
                'message' => 'Data validasi belum lengkap',
                'missing' => $this->missing_validations
            ];
        }

        // Check if all validation criteria are met
        $requiredCriteria = [
            'alamat_sesuai',
            'identitas_valid',
            'akses_lokasi_memadai',
            'persyaratan_teknis_terpenuhi'
        ];

        $allValid = true;
        foreach ($requiredCriteria as $criteria) {
            if (!$this->$criteria) {
                $allValid = false;
                break;
            }
        }

        // Update status based on validation
        if ($allValid) {
            $this->update([
                'status_validasi' => 'valid',
                'keputusan_akhir' => 'lanjut',
                'tanggal_keputusan' => now(),
                'validated_by' => $user->id,
                'validated_at' => now()
            ]);
            
            return ['success' => true, 'message' => 'Validasi berhasil - Status: LANJUT'];
        } else {
            $this->update([
                'status_validasi' => 'invalid',
                'keputusan_akhir' => 'tidak_lanjut',
                'tanggal_keputusan' => now(),
                'validated_by' => $user->id,
                'validated_at' => now()
            ]);
            
            return ['success' => true, 'message' => 'Validasi selesai - Status: TIDAK LANJUT'];
        }
    }

    public function reportDataError(string $description, User $user): array
    {
        if (!$this->canBeEditedBy($user)) {
            return ['success' => false, 'message' => 'Anda tidak memiliki permission untuk report error'];
        }

        $this->update([
            'ada_kesalahan_data' => true,
            'deskripsi_kesalahan' => $description,
            'status_laporan' => 'reported',
            'status_validasi' => 'need_review'
        ]);

        // TODO: Send notification to admin

        return ['success' => true, 'message' => 'Kesalahan data berhasil dilaporkan ke admin'];
    }

    public function resolveDataError(User $admin): array
    {
        if (!$admin->hasRole(['admin', 'super_admin'])) {
            return ['success' => false, 'message' => 'Hanya admin yang bisa resolve error data'];
        }

        $this->update([
            'status_laporan' => 'resolved',
            'status_validasi' => 'pending', // Reset to pending for re-validation
            'ada_kesalahan_data' => false,
            'deskripsi_kesalahan' => null
        ]);

        return ['success' => true, 'message' => 'Error data berhasil di-resolve, status reset ke pending'];
    }

    // REDESIGNED: Create validation record for existing customer
    public static function createForCustomer(string $reffId, User $validator): array
    {
        // Check if customer exists
        $customer = CalonPelanggan::where('reff_id_pelanggan', $reffId)->first();
        
        if (!$customer) {
            return ['success' => false, 'message' => 'Customer dengan Reference ID tersebut tidak ditemukan'];
        }

        // Check if validation already exists
        $existingValidation = static::where('reff_id_pelanggan', $reffId)->first();
        
        if ($existingValidation) {
            return ['success' => false, 'message' => 'Validasi untuk customer ini sudah ada'];
        }

        $validation = static::create([
            'reff_id_pelanggan' => $reffId,
            'status_validasi' => 'pending',
            'validated_by' => $validator->id,
        ]);

        return [
            'success' => true, 
            'message' => 'Record validasi berhasil dibuat',
            'validation' => $validation
        ];
    }

    // ADDED: Reset validation
    public function resetValidation(User $user): array
    {
        if (!$user->hasRole(['admin', 'super_admin'])) {
            return ['success' => false, 'message' => 'Hanya admin yang bisa reset validasi'];
        }

        $this->update([
            'status_validasi' => 'pending',
            'alamat_sesuai' => null,
            'identitas_valid' => null,
            'akses_lokasi_memadai' => null,
            'persyaratan_teknis_terpenuhi' => null,
            'keputusan_akhir' => null,
            'tanggal_keputusan' => null,
            'alasan_keputusan' => null,
            'validated_at' => null,
            'catatan_validasi' => null,
            'catatan_teknis' => null
        ]);

        return ['success' => true, 'message' => 'Validasi berhasil di-reset'];
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();
        
        // Auto-assign current user when creating
        static::creating(function ($model) {
            if (empty($model->validated_by) && Auth::check()) {
                $model->validated_by = Auth::id();
            }
        });
    }
}