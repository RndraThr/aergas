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
        Schema::create('photo_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('reff_id_pelanggan', 50);
            $table->enum('module_name', ['sk', 'sr', 'gas_in']);
            $table->string('photo_field_name', 100);
            $table->string('photo_url', 500);
            $table->decimal('ai_confidence_score', 5, 2)->nullable();
            $table->json('ai_validation_result')->nullable();
            $table->timestamp('ai_approved_at')->nullable();
            $table->unsignedBigInteger('tracer_user_id')->nullable();
            $table->timestamp('tracer_approved_at')->nullable();
            $table->text('tracer_notes')->nullable();
            $table->unsignedBigInteger('cgp_user_id')->nullable();
            $table->timestamp('cgp_approved_at')->nullable();
            $table->text('cgp_notes')->nullable();
            $table->enum('photo_status', [
                'draft', 'ai_pending', 'ai_rejected', 'ai_approved',
                'tracer_pending', 'tracer_rejected', 'tracer_approved',
                'cgp_pending', 'cgp_rejected', 'cgp_approved'
            ])->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('reff_id_pelanggan')->references('reff_id_pelanggan')->on('calon_pelanggan')->onDelete('cascade');
            $table->foreign('tracer_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('cgp_user_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['reff_id_pelanggan', 'module_name']);
            $table->index('photo_status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('photo_approvals');
    }
};
