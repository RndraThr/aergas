<?php

namespace Database\Seeders;

use App\Models\GasInData;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class GasInDataSeeder extends Seeder
{
    public function run()
    {
        $tracer = User::where('role', 'tracer')->first();
        $admin = User::where('role', 'admin')->first();

        $gasInDataList = [
            [
                'reff_id_pelanggan' => 'AER004',
                'ba_gas_in_url' => '/storage/gas_in/AER004/ba_gas_in.pdf',
                'foto_bubble_test_sk_url' => '/storage/gas_in/AER004/foto_bubble_test.jpg',
                'foto_regulator_url' => '/storage/gas_in/AER004/foto_regulator.jpg',
                'foto_kompor_menyala_url' => '/storage/gas_in/AER004/foto_kompor_menyala.jpg',
                'overall_photo_status' => 'cgp_review',
                'module_status' => 'cgp_review',
                'tracer_approved_by' => $tracer->id,
                'tracer_approved_at' => Carbon::now()->subDays(2),
            ],
            [
                'reff_id_pelanggan' => 'AER006',
                'ba_gas_in_url' => '/storage/gas_in/AER006/ba_gas_in.pdf',
                'foto_bubble_test_sk_url' => '/storage/gas_in/AER006/foto_bubble_test.jpg',
                'foto_regulator_url' => '/storage/gas_in/AER006/foto_regulator.jpg',
                'foto_kompor_menyala_url' => '/storage/gas_in/AER006/foto_kompor_menyala.jpg',
                'tracer_approved_by' => $tracer->id,
                'tracer_approved_at' => Carbon::now()->subDays(23),
                'cgp_approved_by' => $admin->id,
                'cgp_approved_at' => Carbon::now()->subDays(22),
                'overall_photo_status' => 'completed',
                'module_status' => 'completed',
            ]
        ];

        foreach ($gasInDataList as $data) {
            GasInData::create($data);
        }

        $this->command->info('Created ' . count($gasInDataList) . ' Gas In data records successfully.');
    }
}
