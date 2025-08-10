<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalonPelanggan extends Model
{
    use HasFactory;

    protected $table = 'calon_pelanggan';
    protected $primaryKey = 'reff_id_pelanggan';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'reff_id_pelanggan',
        'nama_pelanggan',
        'alamat',
        'no_telepon',
        'status',
        'progress_status',
        'keterangan',
        'wilayah_area',
        'jenis_pelanggan',
        'tanggal_registrasi'
    ];

    protected $casts = [
        'tanggal_registrasi' => 'datetime',
    ];

    // Relationships
    public function skData()
    {
        return $this->hasOne(SkData::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function srData()
    {
        return $this->hasOne(SrData::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function mgrtData()
    {
        return $this->hasOne(MgrtData::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function gasInData()
    {
        return $this->hasOne(GasInData::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function jalurPipaData()
    {
        return $this->hasOne(JalurPipaData::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function penyambunganPipaData()
    {
        return $this->hasOne(PenyambunganPipaData::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function baBatalData()
    {
        return $this->hasOne(BaBatalData::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function photoApprovals()
    {
        return $this->hasMany(PhotoApproval::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function fileStorages()
    {
        return $this->hasMany(FileStorage::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'reff_id_pelanggan', 'reff_id_pelanggan');
    }

    // Helper Methods
    public function getProgressPercentage()
    {
        $steps = ['validasi', 'sk', 'sr', 'mgrt', 'gas_in', 'jalur_pipa', 'penyambungan', 'done'];
        $currentIndex = array_search($this->progress_status, $steps);
        return $currentIndex !== false ? (($currentIndex + 1) / count($steps)) * 100 : 0;
    }

    public function canProceedToModule($module)
    {
        $dependencies = [
            'sk' => ['validasi'],
            'sr' => ['sk'],
            'mgrt' => ['validasi'],
            'gas_in' => ['sk', 'sr'],
            'jalur_pipa' => ['validasi'],
            'penyambungan' => ['jalur_pipa'],
        ];

        if (!isset($dependencies[$module])) {
            return true;
        }

        $requiredSteps = $dependencies[$module];

        // Check if current progress allows this module
        foreach ($requiredSteps as $requiredStep) {
            if ($requiredStep === 'sk' && $this->skData && $this->skData->module_status === 'completed') {
                continue;
            }
            if ($requiredStep === 'sr' && $this->srData && $this->srData->module_status === 'completed') {
                continue;
            }
            if ($requiredStep === 'validasi' && in_array($this->status, ['validated', 'in_progress'])) {
                continue;
            }
            if ($requiredStep === 'jalur_pipa' && $this->jalurPipaData && $this->jalurPipaData->module_status === 'completed') {
                continue;
            }

            return false; // Dependency not met
        }

        return true;
    }

    public function getNextAvailableModule()
    {
        $modules = ['sk', 'sr', 'mgrt', 'gas_in', 'jalur_pipa', 'penyambungan'];

        foreach ($modules as $module) {
            if ($this->canProceedToModule($module)) {
                $moduleData = $this->{$module === 'jalur_pipa' ? 'jalurPipaData' : $module . 'Data'};
                if (!$moduleData || $moduleData->module_status === 'not_started') {
                    return $module;
                }
            }
        }

        return null; // All modules completed or no available module
    }
}
