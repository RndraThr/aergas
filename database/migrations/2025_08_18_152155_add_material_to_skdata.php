<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('sk_data', function (Blueprint $table) {
            // =====================================
            // MATERIAL FIELDS SESUAI ISOMETRIC SK
            // =====================================

            // --- PIPA UTAMA (WAJIB) ---
            $table->decimal('panjang_pipa_gl_medium_m', 8, 2)
                  ->nullable()
                  ->after('notes')
                  ->comment('Panjang Pipa 1/2" GL Medium dalam meter');

            // --- FITTING & SAMBUNGAN (WAJIB) ---
            $table->unsignedSmallInteger('qty_elbow_1_2_galvanis')
                  ->default(0)
                  ->after('panjang_pipa_gl_medium_m')
                  ->comment('Jumlah Elbow 1/2" Galvanis (Pcs)');

            $table->unsignedSmallInteger('qty_sockdraft_galvanis_1_2')
                  ->default(0)
                  ->after('qty_elbow_1_2_galvanis')
                  ->comment('Jumlah SockDraft Galvanis Dia 1/2" (Pcs)');

            $table->unsignedSmallInteger('qty_ball_valve_1_2')
                  ->default(0)
                  ->after('qty_sockdraft_galvanis_1_2')
                  ->comment('Jumlah Ball Valve 1/2" (Pcs)');

            $table->unsignedSmallInteger('qty_nipel_selang_1_2')
                  ->default(0)
                  ->after('qty_ball_valve_1_2')
                  ->comment('Jumlah Nipel Selang 1/2" (Pcs)');

            $table->unsignedSmallInteger('qty_elbow_reduce_3_4_1_2')
                  ->default(0)
                  ->after('qty_nipel_selang_1_2')
                  ->comment('Jumlah Elbow Reduce 3/4" x 1/2" (Pcs)');

            $table->unsignedSmallInteger('qty_long_elbow_3_4_male_female')
                  ->default(0)
                  ->after('qty_elbow_reduce_3_4_1_2')
                  ->comment('Jumlah Long Elbow 3/4" Male Female (Pcs)');

            $table->unsignedSmallInteger('qty_klem_pipa_1_2')
                  ->default(0)
                  ->after('qty_long_elbow_3_4_male_female')
                  ->comment('Jumlah Klem Pipa 1/2" (Pcs)');

            $table->unsignedSmallInteger('qty_double_nipple_1_2')
                  ->default(0)
                  ->after('qty_klem_pipa_1_2')
                  ->comment('Jumlah Double Nipple 1/2" (Pcs)');

            $table->unsignedSmallInteger('qty_seal_tape')
                  ->default(0)
                  ->after('qty_double_nipple_1_2')
                  ->comment('Jumlah Seal Tape (Pcs)');

            // --- TEE (OPSIONAL) ---
            $table->unsignedSmallInteger('qty_tee_1_2')
                  ->nullable()
                  ->after('qty_seal_tape')
                  ->comment('Jumlah Tee 1/2" (Pcs) - OPSIONAL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('sk_data', function (Blueprint $table) {
            $table->dropColumn([
                // Pipa
                'panjang_pipa_gl_medium_m',

                // Fitting & Sambungan (WAJIB)
                'qty_elbow_1_2_galvanis',
                'qty_sockdraft_galvanis_1_2',
                'qty_ball_valve_1_2',
                'qty_nipel_selang_1_2',
                'qty_elbow_reduce_3_4_1_2',
                'qty_long_elbow_3_4_male_female',
                'qty_klem_pipa_1_2',
                'qty_double_nipple_1_2',
                'qty_seal_tape',

                // Tee (OPSIONAL)
                'qty_tee_1_2'
            ]);
        });
    }
};
