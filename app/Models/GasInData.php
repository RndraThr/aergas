<?php

namespace App\Models;

class GasInData extends BaseModuleModel
{
    protected $table = 'gas_in_data';

    protected $fillable = [
        'reff_id_pelanggan',
        'ba_gas_in_url',
        'foto_bubble_test_sk_url',
        'foto_regulator_url',
        'foto_kompor_menyala_url',
        // Approval
        'tracer_approved_by',
        'tracer_approved_at',
        'cgp_approved_by',
        'cgp_approved_at',
        'overall_photo_status',
        'module_status'
    ];

    // public function getCasts()
    // {
    //     return parent::getCasts();
    // }


    public function getRequiredPhotos(): array
    {
        return [
            'ba_gas_in_url',
            'foto_bubble_test_sk_url',
            'foto_regulator_url',
            'foto_kompor_menyala_url'
        ];
    }

    public function getModuleName(): string
    {
        return 'gas_in';
    }
}
