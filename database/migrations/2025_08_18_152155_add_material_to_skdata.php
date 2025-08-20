<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sk_data', function (Blueprint $table) {
            if (Schema::hasColumn('sk_data', 'nomor_sk')) {
                $table->dropColumn('nomor_sk');
            }

            if (!Schema::hasColumn('sk_data', 'panjang_pipa_gl_medium_m')) {
                $table->decimal('panjang_pipa_gl_medium_m', 8, 2)
                      ->nullable()
                      ->after('notes');
            }

            if (!Schema::hasColumn('sk_data', 'qty_elbow_1_2_galvanis')) {
                $table->unsignedSmallInteger('qty_elbow_1_2_galvanis')
                      ->default(0)
                      ->after('panjang_pipa_gl_medium_m');
            }

            if (!Schema::hasColumn('sk_data', 'qty_sockdraft_galvanis_1_2')) {
                $table->unsignedSmallInteger('qty_sockdraft_galvanis_1_2')
                      ->default(0)
                      ->after('qty_elbow_1_2_galvanis');
            }

            if (!Schema::hasColumn('sk_data', 'qty_ball_valve_1_2')) {
                $table->unsignedSmallInteger('qty_ball_valve_1_2')
                      ->default(0)
                      ->after('qty_sockdraft_galvanis_1_2');
            }

            if (!Schema::hasColumn('sk_data', 'qty_nipel_selang_1_2')) {
                $table->unsignedSmallInteger('qty_nipel_selang_1_2')
                      ->default(0)
                      ->after('qty_ball_valve_1_2');
            }

            if (!Schema::hasColumn('sk_data', 'qty_elbow_reduce_3_4_1_2')) {
                $table->unsignedSmallInteger('qty_elbow_reduce_3_4_1_2')
                      ->default(0)
                      ->after('qty_nipel_selang_1_2');
            }

            if (!Schema::hasColumn('sk_data', 'qty_long_elbow_3_4_male_female')) {
                $table->unsignedSmallInteger('qty_long_elbow_3_4_male_female')
                      ->default(0)
                      ->after('qty_elbow_reduce_3_4_1_2');
            }

            if (!Schema::hasColumn('sk_data', 'qty_klem_pipa_1_2')) {
                $table->unsignedSmallInteger('qty_klem_pipa_1_2')
                      ->default(0)
                      ->after('qty_long_elbow_3_4_male_female');
            }

            if (!Schema::hasColumn('sk_data', 'qty_double_nipple_1_2')) {
                $table->unsignedSmallInteger('qty_double_nipple_1_2')
                      ->default(0)
                      ->after('qty_klem_pipa_1_2');
            }

            if (!Schema::hasColumn('sk_data', 'qty_seal_tape')) {
                $table->unsignedSmallInteger('qty_seal_tape')
                      ->default(0)
                      ->after('qty_double_nipple_1_2');
            }

            if (!Schema::hasColumn('sk_data', 'qty_tee_1_2')) {
                $table->unsignedSmallInteger('qty_tee_1_2')
                      ->nullable()
                      ->after('qty_seal_tape');
            }
        });
    }

    public function down()
    {
        Schema::table('sk_data', function (Blueprint $table) {
            $table->string('nomor_sk')->nullable()->unique();

            $table->dropColumn([
                'panjang_pipa_gl_medium_m',
                'qty_elbow_1_2_galvanis',
                'qty_sockdraft_galvanis_1_2',
                'qty_ball_valve_1_2',
                'qty_nipel_selang_1_2',
                'qty_elbow_reduce_3_4_1_2',
                'qty_long_elbow_3_4_male_female',
                'qty_klem_pipa_1_2',
                'qty_double_nipple_1_2',
                'qty_seal_tape',
                'qty_tee_1_2'
            ]);
        });
    }
};
