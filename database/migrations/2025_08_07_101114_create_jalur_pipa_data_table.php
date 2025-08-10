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
        Schema::create('jalur_pipa_data', function (Blueprint $table) {
            $table->id();
            $table->string('reff_id_pelanggan', 50);

            // Jalur Pipa Form Fields
            $table->string('foto_kedalaman_pipa_url', 500)->nullable();
            $table->decimal('kedalaman_pipa', 8, 2)->nullable();
            $table->string('foto_lowering_pipa_url', 500)->nullable();
            $table->decimal('panjang_pipa', 8, 2)->nullable();
            $table->string('foto_casing_crossing_url', 500)->nullable();
            $table->decimal('panjang_casing', 8, 2)->nullable();
            $table->enum('jenis_galian', ['Manual Boring/Rojok', 'Open Cut', 'Zinker', 'HDD'])->nullable();
            $table->enum('diameter_pipa', ['63', '90'])->nullable();
            $table->string('jenis_perkerasan', 100)->nullable();
            $table->string('foto_urugan_url', 500)->nullable();
            $table->string('foto_concrete_slab_url', 500)->nullable();
            $table->string('foto_marker_tape_url', 500)->nullable();
            $table->string('line_number', 50)->nullable();

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
        Schema::dropIfExists('jalur_pipa_data');
    }
};
