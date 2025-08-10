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
        Schema::create('gas_in_data', function (Blueprint $table) {
            $table->id();
            $table->string('reff_id_pelanggan', 50);

            // Gas In Form Fields
            $table->string('ba_gas_in_url', 500)->nullable();
            $table->string('foto_bubble_test_sk_url', 500)->nullable();
            $table->string('foto_regulator_url', 500)->nullable();
            $table->string('foto_kompor_menyala_url', 500)->nullable();

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
        Schema::dropIfExists('gas_in_data');
    }
};
