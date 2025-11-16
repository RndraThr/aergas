<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pilots', function (Blueprint $table) {
            $table->id();

            // Basic Info (dari sheet: Nama, Nomor Kartu Identitas, Nomor Ponsel, dst)
            $table->string('nama')->nullable();
            $table->string('nomor_kartu_identitas')->nullable();
            $table->string('nomor_ponsel')->nullable();
            $table->text('alamat')->nullable();
            $table->string('rt')->nullable();
            $table->string('rw')->nullable();
            $table->string('id_kota_kab')->nullable();
            $table->string('id_kecamatan')->nullable();
            $table->string('id_kelurahan')->nullable();
            $table->string('padukuhan')->nullable();

            // ID Reference (ID REFF adalah primary identifier)
            $table->string('id_reff')->unique();

            // Status (PENETRASI / PENGEMBANGAN DAN ON THE SPOT)
            $table->text('penetrasi_pengembangan')->nullable();

            // Tanggal Pemasangan (merged header: TANGGAL TERPASANG, sub: SK, SR, GAS IN)
            $table->date('tanggal_terpasang_sk')->nullable();
            $table->date('tanggal_terpasang_sr')->nullable();
            $table->date('tanggal_terpasang_gas_in')->nullable();

            // Keterangan & Status
            $table->text('keterangan')->nullable();
            $table->string('batal')->nullable();
            $table->text('keterangan_batal')->nullable();
            $table->text('anomali')->nullable();

            // Material SK Terpasang (9 items)
            $table->integer('mat_sk_elbow_3_4_to_1_2')->nullable();
            $table->integer('mat_sk_double_nipple_1_2')->nullable();
            $table->integer('mat_sk_pipa_galvanize_1_2')->nullable();
            $table->integer('mat_sk_elbow_1_2')->nullable();
            $table->integer('mat_sk_ball_valve_1_2')->nullable();
            $table->integer('mat_sk_nipple_slang_1_2')->nullable();
            $table->integer('mat_sk_klem_pipa_1_2')->nullable();
            $table->integer('mat_sk_sockdraft_galvanis_1_2')->nullable();
            $table->integer('mat_sk_sealtape')->nullable();

            // Material SR Terpasang (15 items)
            $table->integer('mat_sr_ts_63x20mm')->nullable();
            $table->integer('mat_sr_coupler_20mm')->nullable();
            $table->integer('mat_sr_pipa_pe_20mm')->nullable();
            $table->integer('mat_sr_elbow_pe_20mm')->nullable();
            $table->integer('mat_sr_female_tf_pe_20mm')->nullable();
            $table->integer('mat_sr_pipa_galvanize_3_4')->nullable();
            $table->integer('mat_sr_klem_pipa_3_4')->nullable();
            $table->integer('mat_sr_ball_valves_3_4')->nullable();
            $table->integer('mat_sr_long_elbow_90_3_4')->nullable();
            $table->integer('mat_sr_double_nipple_3_4')->nullable();
            $table->integer('mat_sr_regulator')->nullable();
            $table->integer('mat_sr_meter_gas_rumah_tangga')->nullable();
            $table->integer('mat_sr_cassing_1')->nullable();
            $table->integer('mat_sr_coupling_mgrt')->nullable();
            $table->integer('mat_sr_sealtape')->nullable();

            // Evidence SK (5 photos)
            $table->text('ev_sk_foto_berita_acara_pemasangan')->nullable();
            $table->text('ev_sk_foto_pneumatik_start')->nullable();
            $table->text('ev_sk_foto_pneumatik_finish')->nullable();
            $table->text('ev_sk_foto_valve_sk')->nullable();
            $table->text('ev_sk_foto_isometrik_sk')->nullable();

            // Evidence SR (6 photos)
            $table->text('ev_sr_foto_pneumatik_start')->nullable();
            $table->text('ev_sr_foto_pneumatik_finish')->nullable();
            $table->text('ev_sr_foto_jenis_tapping')->nullable();
            $table->text('ev_sr_foto_kedalaman')->nullable();
            $table->text('ev_sr_foto_cassing')->nullable();
            $table->text('ev_sr_foto_isometrik_sr')->nullable();

            // Evidence MGRT dan Pondasi (3 items)
            $table->text('ev_mgrt_foto_meter_gas_rumah_tangga')->nullable();
            $table->text('ev_mgrt_foto_pondasi_mgrt')->nullable();
            $table->string('ev_mgrt_nomor_seri_mgrt')->nullable();

            // Evidence Konversi dan Gas In (7 items)
            $table->text('ev_gasin_berita_acara_gas_in')->nullable();
            $table->text('ev_gasin_rangkaian_meter_gas_pondasi')->nullable();
            $table->text('ev_gasin_foto_bubble_test')->nullable();
            $table->text('ev_gasin_foto_mgrt')->nullable();
            $table->text('ev_gasin_foto_kompor_menyala_pelanggan')->nullable();
            $table->text('ev_gasin_foto_stiker_sosialisasi')->nullable();
            $table->string('ev_gasin_nomor_seri_mgrt')->nullable();

            // Review CGP (3 items: SK, SR, GAS IN)
            $table->string('review_cgp_sk')->nullable();
            $table->string('review_cgp_sr')->nullable();
            $table->string('review_cgp_gas_in')->nullable();

            // Dokumen (4 items)
            $table->text('ba_gas_in')->nullable(); // BA GAS IN (BERITA ACARA GAS IN)
            $table->text('asbuilt_sk')->nullable(); // AS BUILD DRAWING SK (ASBUILT SK)
            $table->text('asbuilt_sr')->nullable(); // AS BUILD DRAWING SR (ASBUILT SR)
            $table->text('comment_cgp')->nullable(); // COMENT CGP

            // Metadata
            $table->string('batch_id')->nullable()->index();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilots');
    }
};
