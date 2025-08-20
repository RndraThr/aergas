<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('sr_data', function (Blueprint $t) {
            if (Schema::hasColumn('sr_data', 'nomor_sr')) {
                $t->dropColumn('nomor_sr');
            }

            if (!Schema::hasColumn('sr_data','jenis_tapping')) {
                $t->string('jenis_tapping', 20)->nullable()->after('notes');
            }

            if (!Schema::hasColumn('sr_data','qty_tapping_saddle')) {
                $t->unsignedSmallInteger('qty_tapping_saddle')->default(0)->after('jenis_tapping');
            }
            if (!Schema::hasColumn('sr_data','qty_coupler_20mm')) {
                $t->unsignedSmallInteger('qty_coupler_20mm')->default(0)->after('qty_tapping_saddle');
            }
            if (!Schema::hasColumn('sr_data','panjang_pipa_pe_20mm_m')) {
                $t->decimal('panjang_pipa_pe_20mm_m', 8, 2)->nullable()->after('qty_coupler_20mm');
            }
            if (!Schema::hasColumn('sr_data','qty_elbow_90x20')) {
                $t->unsignedSmallInteger('qty_elbow_90x20')->default(0)->after('panjang_pipa_pe_20mm_m');
            }
            if (!Schema::hasColumn('sr_data','qty_transition_fitting')) {
                $t->unsignedSmallInteger('qty_transition_fitting')->default(0)->after('qty_elbow_90x20');
            }
            if (!Schema::hasColumn('sr_data','panjang_pondasi_tiang_sr_m')) {
                $t->decimal('panjang_pondasi_tiang_sr_m', 8, 2)->nullable()->after('qty_transition_fitting');
            }
            if (!Schema::hasColumn('sr_data','panjang_pipa_galvanize_3_4_m')) {
                $t->decimal('panjang_pipa_galvanize_3_4_m', 8, 2)->nullable()->after('panjang_pondasi_tiang_sr_m');
            }
            if (!Schema::hasColumn('sr_data','qty_klem_pipa')) {
                $t->unsignedSmallInteger('qty_klem_pipa')->default(0)->after('panjang_pipa_galvanize_3_4_m');
            }
            if (!Schema::hasColumn('sr_data','qty_ball_valve_3_4')) {
                $t->unsignedSmallInteger('qty_ball_valve_3_4')->default(0)->after('qty_klem_pipa');
            }
            if (!Schema::hasColumn('sr_data','qty_double_nipple_3_4')) {
                $t->unsignedSmallInteger('qty_double_nipple_3_4')->default(0)->after('qty_ball_valve_3_4');
            }
            if (!Schema::hasColumn('sr_data','qty_long_elbow_3_4')) {
                $t->unsignedSmallInteger('qty_long_elbow_3_4')->default(0)->after('qty_double_nipple_3_4');
            }
            if (!Schema::hasColumn('sr_data','qty_regulator_service')) {
                $t->unsignedSmallInteger('qty_regulator_service')->default(0)->after('qty_long_elbow_3_4');
            }
            if (!Schema::hasColumn('sr_data','qty_coupling_mgrt')) {
                $t->unsignedSmallInteger('qty_coupling_mgrt')->default(0)->after('qty_regulator_service');
            }
            if (!Schema::hasColumn('sr_data','qty_meter_gas_rumah_tangga')) {
                $t->unsignedSmallInteger('qty_meter_gas_rumah_tangga')->default(0)->after('qty_coupling_mgrt');
            }
            if (!Schema::hasColumn('sr_data','panjang_casing_1_inch_m')) {
                $t->decimal('panjang_casing_1_inch_m', 8, 2)->nullable()->after('qty_meter_gas_rumah_tangga');
            }
            if (!Schema::hasColumn('sr_data','qty_sealtape')) {
                $t->unsignedSmallInteger('qty_sealtape')->default(0)->after('panjang_casing_1_inch_m');
            }

            if (!Schema::hasColumn('sr_data','no_seri_mgrt')) {
                $t->string('no_seri_mgrt', 50)->nullable()->after('qty_sealtape');
            }
            if (!Schema::hasColumn('sr_data','merk_brand_mgrt')) {
                $t->string('merk_brand_mgrt', 50)->nullable()->after('no_seri_mgrt');
            }

            if (!Schema::hasColumn('sr_data','panjang_pipa_pe_m')) {
                $t->decimal('panjang_pipa_pe_m', 10, 2)->nullable()->after('qty_sealtape');
            }
            if (!Schema::hasColumn('sr_data','panjang_casing_crossing_m')) {
                $t->decimal('panjang_casing_crossing_m', 10, 2)->nullable()->after('panjang_pipa_pe_m');
            }

            if (!Schema::hasColumn('sr_data','panjang_pipa_pe')) {
                $t->decimal('panjang_pipa_pe', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('sr_data','panjang_casing_crossing_sr')) {
                $t->decimal('panjang_casing_crossing_sr', 10, 2)->nullable();
            }
        });
    }

    public function down(): void {
        Schema::table('sr_data', function (Blueprint $t) {
            $t->string('nomor_sr')->nullable()->unique();

            $t->dropColumn([
                'qty_tapping_saddle',
                'qty_coupler_20mm',
                'panjang_pipa_pe_20mm_m',
                'qty_elbow_90x20',
                'qty_transition_fitting',
                'panjang_pondasi_tiang_sr_m',
                'panjang_pipa_galvanize_3_4_m',
                'qty_klem_pipa',
                'qty_ball_valve_3_4',
                'qty_double_nipple_3_4',
                'qty_long_elbow_3_4',
                'qty_regulator_service',
                'qty_coupling_mgrt',
                'qty_meter_gas_rumah_tangga',
                'panjang_casing_1_inch_m',
                'qty_sealtape',
                'no_seri_mgrt',
                'merk_brand_mgrt',
                'jenis_tapping',
                'panjang_pipa_pe_m',
                'panjang_casing_crossing_m',
                'panjang_pipa_pe',
                'panjang_casing_crossing_sr'
            ]);
        });
    }
};
