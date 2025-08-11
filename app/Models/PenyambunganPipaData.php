<?php

namespace App\Models;

class PenyambunganPipaData extends BaseModuleModel
{
    protected $table = 'penyambungan_pipa_data';

    protected $fillable = [
        'reff_id_pelanggan',
        'nomor_joint',
        'diameter_pipa',
        'metode_penyambungan',
        'jenis_penyambungan',
        'material_fitting',
        'foto_penyambungan_url',
        'foto_name_tag_url',
        // Approval
        'tracer_approved_by',
        'tracer_approved_at',
        'cgp_approved_by',
        'cgp_approved_at',
        'overall_photo_status',
        'module_status'
    ];

    // protected $casts = [
    //     ...$this->approvalCasts
    // ];

    public function getRequiredPhotos(): array
    {
        return [
            'foto_penyambungan_url',
            'foto_name_tag_url'
        ];
    }

    public function getModuleName(): string
    {
        return 'penyambungan';
    }
}
