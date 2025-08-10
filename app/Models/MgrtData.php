<?php

namespace App\Models;

class MgrtData extends BaseModuleModel
{
    protected $table = 'mgrt_data';

    protected $fillable = [
        'reff_id_pelanggan',
        'no_seri_mgrt',
        'merk_brand_mgrt',
        'foto_mgrt_url',
        'foto_pondasi_url',
        // Approval
        'tracer_approved_by',
        'tracer_approved_at',
        'cgp_approved_by',
        'cgp_approved_at',
        'overall_photo_status',
        'module_status'
    ];

    public function getCasts()
    {
        return parent::getCasts();
    }

    public function getRequiredPhotos(): array
    {
        return [
            'foto_mgrt_url',
            'foto_pondasi_url'
        ];
    }

    public function getModuleName(): string
    {
        return 'mgrt';
    }
}
