<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HseEmergencyContact extends Model
{
    protected $fillable = [
        'jabatan',
        'nama_petugas',
        'nomor_telepon',
        'kategori',
        'urutan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByKategori($query, string $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('urutan')->orderBy('jabatan');
    }

    public function getFormattedPhone(): string
    {
        // Format nomor telepon (contoh: 0812-3456-7890)
        $phone = preg_replace('/[^0-9]/', '', $this->nomor_telepon);

        if (strlen($phone) >= 11) {
            return substr($phone, 0, 4) . '-' . substr($phone, 4, 4) . '-' . substr($phone, 8);
        }

        return $this->nomor_telepon;
    }
}
