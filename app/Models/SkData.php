<?php

namespace App\Models;

class SkData extends BaseModuleModel
{
    protected $table = 'sk_data';

    protected $fillable = [
        'reff_id_pelanggan',
        'user_id',,
        'tanggal_instalasi',
        'catatan_tambahan',
        // Material tracking
        'pipa_hot_drip_meter',
        'long_elbow_34_pcs',
        'elbow_34_to_12_pcs',
        'elbow_12_pcs',
        'ball_valve_12_pcs',
        'double_nipple_12_pcs',
        'sock_draft_galvanis_12_pcs',
        'klem_pipa_12_pcs',
        'seal_tape_roll',
        // Photos
        'foto_berita_acara_url',
        'foto_pneumatic_sk_url',
        'foto_valve_krunchis_url',
        'foto_isometrik_sk_url',
        // Approval
        'tracer_approved_by',
        'tracer_approved_at',
        'cgp_approved_by',
        'cgp_approved_at',
        'overall_photo_status',
        'module_status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function getRouteKeyName()
    {
        return 'reff_id_pelanggan';
    }

    public function getCasts()
    {
        return array_merge([
            'tanggal_instalasi' => 'date',
        ], parent::getCasts());
    }


    public function getRequiredPhotos(): array
    {
        return [
            'foto_berita_acara_url',
            'foto_pneumatic_sk_url',
            'foto_valve_krunchis_url',
            'foto_isometrik_sk_url'
        ];
    }

    public function getModuleName(): string
    {
        return 'sk';
    }

    public function getTotalMaterialCost()
    {
        // Implement material cost calculation if needed
        // This is just example logic
        $costs = [
            'pipa_hot_drip_meter' => 15000,
            'long_elbow_34_pcs' => 5000,
            'elbow_34_to_12_pcs' => 4000,
            'elbow_12_pcs' => 3000,
            'ball_valve_12_pcs' => 25000,
            'double_nipple_12_pcs' => 2000,
            'sock_draft_galvanis_12_pcs' => 3000,
            'klem_pipa_12_pcs' => 1000,
            'seal_tape_roll' => 5000,
        ];

        $total = 0;
        foreach ($costs as $item => $price) {
            $quantity = $this->$item ?? 0;
            $total += $quantity * $price;
        }

        return $total;
    }
}
