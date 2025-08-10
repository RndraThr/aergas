<?php

namespace Database\Seeders;

use App\Models\MgrtData;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class MgrtDataSeeder extends Seeder
{
    public function run()
    {
        $tracer = User::where('role', 'tracer')->first();
        $admin = User::where('role', 'admin')->first();

        $mgrtDataList = [
            [
                'reff_id_pelanggan' => 'AER005',
                'no_seri_mgrt' => 'MGRT-2024-001',
                'merk_brand_mgrt' => 'Sensus',
                'foto_mgrt_url' => '/storage/mgrt/AER005/foto_mgrt.jpg',
                'foto_pondasi_url' => '/storage/mgrt/AER005/foto_pondasi.jpg',
                'overall_photo_status' => 'draft',
                'module_status' => 'draft',
            ],
            [
                'reff_id_pelanggan' => 'AER006',
                'no_seri_mgrt' => 'MGRT-2024-002',
                'merk_brand_mgrt' => 'Itron',
                'foto_mgrt_url' => '/storage/mgrt/AER006/foto_mgrt.jpg',
                'foto_pondasi_url' => '/storage/mgrt/AER006/foto_pondasi.jpg',
                'tracer_approved_by' => $tracer->id,
                'tracer_approved_at' => Carbon::now()->subDays(25),
                'cgp_approved_by' => $admin->id,
                'cgp_approved_at' => Carbon::now()->subDays(24),
                'overall_photo_status' => 'completed',
                'module_status' => 'completed',
            ]
        ];

        foreach ($mgrtDataList as $data) {
            MgrtData::create($data);
        }

        $this->command->info('Created ' . count($mgrtDataList) . ' MGRT data records successfully.');
    }
}
