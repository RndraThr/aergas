<?php

namespace Database\Seeders;

use App\Models\SrData;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SrDataSeeder extends Seeder
{
    public function run()
    {
        $tracer = User::where('role', 'tracer')->first();
        $admin = User::where('role', 'admin')->first();

        $srDataList = [
            [
                'reff_id_pelanggan' => 'AER002',
                'foto_pneumatic_start_sr_url' => '/storage/sr/AER002/foto_pneumatic_start.jpg',
                'foto_pneumatic_finish_sr_url' => '/storage/sr/AER002/foto_pneumatic_finish.jpg',
                'jenis_tapping' => '63x20',
                'panjang_pipa_pe' => 15.5,
                'foto_kedalaman_url' => '/storage/sr/AER002/foto_kedalaman.jpg',
                'foto_isometrik_sr_url' => '/storage/sr/AER002/foto_isometrik_sr.jpg',
                'panjang_casing_crossing_sr' => 2.0,
                'tracer_approved_by' => $tracer->id,
                'tracer_approved_at' => Carbon::now()->subDays(5),
                'cgp_approved_by' => $admin->id,
                'cgp_approved_at' => Carbon::now()->subDays(4),
                'overall_photo_status' => 'completed',
                'module_status' => 'completed',
            ],
            [
                'reff_id_pelanggan' => 'AER004',
                'foto_pneumatic_start_sr_url' => '/storage/sr/AER004/foto_pneumatic_start.jpg',
                'foto_pneumatic_finish_sr_url' => '/storage/sr/AER004/foto_pneumatic_finish.jpg',
                'jenis_tapping' => '90x20',
                'panjang_pipa_pe' => 22.3,
                'foto_kedalaman_url' => '/storage/sr/AER004/foto_kedalaman.jpg',
                'foto_isometrik_sr_url' => '/storage/sr/AER004/foto_isometrik_sr.jpg',
                'panjang_casing_crossing_sr' => 3.5,
                'tracer_approved_by' => $tracer->id,
                'tracer_approved_at' => Carbon::now()->subDays(15),
                'cgp_approved_by' => $admin->id,
                'cgp_approved_at' => Carbon::now()->subDays(14),
                'overall_photo_status' => 'completed',
                'module_status' => 'completed',
            ]
        ];

        foreach ($srDataList as $data) {
            SrData::create($data);
        }

        $this->command->info('Created ' . count($srDataList) . ' SR data records successfully.');
    }
}
