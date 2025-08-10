<?php

namespace App\Models;

class SrData extends BaseModuleModel
{
    protected $table = 'sr_data';

    protected $fillable = [
        'reff_id_pelanggan',
        'foto_pneumatic_start_sr_url',
        'foto_pneumatic_finish_sr_url',
        'jenis_tapping',
        'panjang_pipa_pe',
        'foto_kedalaman_url',
        'foto_isometrik_sr_url',
        'panjang_casing_crossing_sr',
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
        return array_merge([
            'panjang_pipa_pe' => 'decimal:2',
            'panjang_casing_crossing_sr' => 'decimal:2',
        ], parent::getCasts());
    }


    public function getRequiredPhotos(): array
    {
        return [
            'foto_pneumatic_start_sr_url',
            'foto_pneumatic_finish_sr_url',
            'foto_kedalaman_url',
            'foto_isometrik_sr_url'
        ];
    }

    public function getModuleName(): string
    {
        return 'sr';
    }
}
