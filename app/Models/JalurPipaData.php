<?php

namespace App\Models;

class JalurPipaData extends BaseModuleModel
{
    protected $table = 'jalur_pipa_data';

    protected $fillable = [
        'reff_id_pelanggan',
        'foto_kedalaman_pipa_url',
        'kedalaman_pipa',
        'foto_lowering_pipa_url',
        'panjang_pipa',
        'foto_casing_crossing_url',
        'panjang_casing',
        'jenis_galian',
        'diameter_pipa',
        'jenis_perkerasan',
        'foto_urugan_url',
        'foto_concrete_slab_url',
        'foto_marker_tape_url',
        'line_number',
        // Approval
        'tracer_approved_by',
        'tracer_approved_at',
        'cgp_approved_by',
        'cgp_approved_at',
        'overall_photo_status',
        'module_status'
    ];

    protected $casts = [
        'kedalaman_pipa' => 'decimal:2',
        'panjang_pipa' => 'decimal:2',
        'panjang_casing' => 'decimal:2',
        ...$this->approvalCasts
    ];

    public function getRequiredPhotos(): array
    {
        $requiredPhotos = [
            'foto_kedalaman_pipa_url',
            'foto_lowering_pipa_url'
        ];

        // Conditional photos based on jenis_galian
        if ($this->jenis_galian === 'Open Cut') {
            $requiredPhotos = array_merge($requiredPhotos, [
                'foto_urugan_url',
                'foto_concrete_slab_url',
                'foto_marker_tape_url'
            ]);
        }

        // Add casing photo if there's crossing
        if ($this->panjang_casing > 0) {
            $requiredPhotos[] = 'foto_casing_crossing_url';
        }

        return $requiredPhotos;
    }

    public function getModuleName(): string
    {
        return 'jalur_pipa';
    }
}
