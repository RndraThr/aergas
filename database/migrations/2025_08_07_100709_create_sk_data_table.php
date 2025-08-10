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
        Schema::create('sk_data', function (Blueprint $table) {
            $table->id();
            $table->string('reff_id_pelanggan', 50);
            $table->string('nama_petugas_sk', 255);
            $table->date('tanggal_instalasi');
            $table->text('catatan_tambahan')->nullable();

            // Material Tracking
            $table->decimal('pipa_hot_drip_meter', 8, 2)->nullable();
            $table->integer('long_elbow_34_pcs')->nullable();
            $table->integer('elbow_34_to_12_pcs')->nullable();
            $table->integer('elbow_12_pcs')->nullable();
            $table->integer('ball_valve_12_pcs')->nullable();
            $table->integer('double_nipple_12_pcs')->nullable();
            $table->integer('sock_draft_galvanis_12_pcs')->nullable();
            $table->integer('klem_pipa_12_pcs')->nullable();
            $table->integer('seal_tape_roll')->nullable();

            // Photo URLs (4 foto wajib)
            $table->string('foto_berita_acara_url', 500)->nullable();
            $table->string('foto_pneumatic_sk_url', 500)->nullable();
            $table->string('foto_valve_krunchis_url', 500)->nullable();
            $table->string('foto_isometrik_sk_url', 500)->nullable();

            // Approval System
            $table->unsignedBigInteger('tracer_approved_by')->nullable();
            $table->timestamp('tracer_approved_at')->nullable();
            $table->unsignedBigInteger('cgp_approved_by')->nullable();
            $table->timestamp('cgp_approved_at')->nullable();
            $table->enum('overall_photo_status', [
                'draft', 'ai_validation', 'tracer_review',
                'cgp_review', 'completed', 'rejected'
            ])->default('draft');
            $table->enum('module_status', [
                'not_started', 'draft', 'ai_validation',
                'tracer_review', 'cgp_review', 'completed', 'rejected'
            ])->default('not_started');

            $table->timestamps();

            $table->foreign('reff_id_pelanggan')->references('reff_id_pelanggan')->on('calon_pelanggan')->onDelete('cascade');
            $table->foreign('tracer_approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('cgp_approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sk_data');
    }
};
