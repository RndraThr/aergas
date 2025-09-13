<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $fittingTypes = [
            ['nama_fitting' => 'Coupler', 'code_fitting' => 'CP'],
            ['nama_fitting' => 'Elbow 90', 'code_fitting' => 'EL'],
            ['nama_fitting' => 'Elbow 45', 'code_fitting' => 'E45'],
            ['nama_fitting' => 'Equal Tee', 'code_fitting' => 'ET'],
            ['nama_fitting' => 'End Cap', 'code_fitting' => 'EC'],
            ['nama_fitting' => 'Reducer', 'code_fitting' => 'RD'],
            ['nama_fitting' => 'Flange Adaptor', 'code_fitting' => 'FA'],
            ['nama_fitting' => 'Valve', 'code_fitting' => 'VL'],
            ['nama_fitting' => 'Transition Fitting', 'code_fitting' => 'TF'],
            ['nama_fitting' => 'Tapping Saddle', 'code_fitting' => 'TS'],
        ];

        foreach ($fittingTypes as $fitting) {
            DB::table('jalur_fitting_types')->insert([
                'nama_fitting' => $fitting['nama_fitting'],
                'code_fitting' => $fitting['code_fitting'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('jalur_fitting_types')->truncate();
    }
};