<?php

namespace Database\Seeders;

use App\Models\SkData;
use App\Models\CalonPelanggan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SkDataSeeder extends Seeder
{
    public function run()
    {
        $petugasSk = User::where('role', 'sk')->first();
        $tracer = User::where('role', 'tracer')->first();
        $admin = User::where('role', 'admin')->first();

        $skDataList = [
            [
                'reff_id_pelanggan' => 'AER001',
                'nama_petugas_sk' => $petugasSk->full_name,
                'tanggal_instalasi' => Carbon::now()->subDays(9),
                'catatan_tambahan' => 'Instalasi berjalan lancar, tidak ada kendala',
                // Material quantities
                'pipa_hot_drip_meter' => 5.5,
                'long_elbow_34_pcs' => 2,
                'elbow_34_to_12_pcs' => 1,
                'elbow_12_pcs' => 3,
                'ball_valve_12_pcs' => 1,
                'double_nipple_12_pcs' => 2,
                'sock_draft_galvanis_12_pcs' => 1,
                'klem_pipa_12_pcs' => 8,
                'seal_tape_roll' => 1,
                // Photo URLs (dummy URLs for testing)
                'foto_berita_acara_url' => '/storage/sk/AER001/foto_berita_acara.jpg',
                'foto_pneumatic_sk_url' => '/storage/sk/AER001/foto_pneumatic_sk.jpg',
                'foto_valve_krunchis_url' => '/storage/sk/AER001/foto_valve_krunchis.jpg',
                'foto_isometrik_sk_url' => '/storage/sk/AER001/foto_isometrik_sk.jpg',
                // Approval status
                'overall_photo_status' => 'tracer_review',
                'module_status' => 'tracer_review',
            ],
            [
                'reff_id_pelanggan' => 'AER002',
                'nama_petugas_sk' => $petugasSk->full_name,
                'tanggal_instalasi' => Carbon::now()->subDays(7),
                'catatan_tambahan' => 'Lokasi agak sempit tapi berhasil diinstal dengan baik',
                'pipa_hot_drip_meter' => 4.2,
                'long_elbow_34_pcs' => 3,
                'elbow_34_to_12_pcs' => 2,
                'elbow_12_pcs' => 2,
                'ball_valve_12_pcs' => 1,
                'double_nipple_12_pcs' => 1,
                'sock_draft_galvanis_12_pcs' => 1,
                'klem_pipa_12_pcs' => 6,
                'seal_tape_roll' => 1,
                'foto_berita_acara_url' => '/storage/sk/AER002/foto_berita_acara.jpg',
                'foto_pneumatic_sk_url' => '/storage/sk/AER002/foto_pneumatic_sk.jpg',
                'foto_valve_krunchis_url' => '/storage/sk/AER002/foto_valve_krunchis.jpg',
                'foto_isometrik_sk_url' => '/storage/sk/AER002/foto_isometrik_sk.jpg',
                'tracer_approved_by' => $tracer->id,
                'tracer_approved_at' => Carbon::now()->subDays(6),
                'cgp_approved_by' => $admin->id,
                'cgp_approved_at' => Carbon::now()->subDays(5),
                'overall_photo_status' => 'completed',
                'module_status' => 'completed',
            ],
            [
                'reff_id_pelanggan' => 'AER004',
                'nama_petugas_sk' => $petugasSk->full_name,
                'tanggal_instalasi' => Carbon::now()->subDays(18),
                'catatan_tambahan' => 'Instalasi sempurna, pelanggan sangat puas',
                'pipa_hot_drip_meter' => 6.0,
                'long_elbow_34_pcs' => 2,
                'elbow_34_to_12_pcs' => 1,
                'elbow_12_pcs' => 4,
                'ball_valve_12_pcs' => 1,
                'double_nipple_12_pcs' => 2,
                'sock_draft_galvanis_12_pcs' => 1,
                'klem_pipa_12_pcs' => 10,
                'seal_tape_roll' => 1,
                'foto_berita_acara_url' => '/storage/sk/AER004/foto_berita_acara.jpg',
                'foto_pneumatic_sk_url' => '/storage/sk/AER004/foto_pneumatic_sk.jpg',
                'foto_valve_krunchis_url' => '/storage/sk/AER004/foto_valve_krunchis.jpg',
                'foto_isometrik_sk_url' => '/storage/sk/AER004/foto_isometrik_sk.jpg',
                'tracer_approved_by' => $tracer->id,
                'tracer_approved_at' => Carbon::now()->subDays(17),
                'cgp_approved_by' => $admin->id,
                'cgp_approved_at' => Carbon::now()->subDays(16),
                'overall_photo_status' => 'completed',
                'module_status' => 'completed',
            ]
        ];

        foreach ($skDataList as $data) {
            SkData::create($data);
        }

        $this->command->info('Created ' . count($skDataList) . ' SK data records successfully.');
    }
}
