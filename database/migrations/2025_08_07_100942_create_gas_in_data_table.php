<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('gas_in_data', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();

            $table->string('reff_id_pelanggan', 50);
            $table->foreign('reff_id_pelanggan', 'fk_gasin_reff_pelanggan')
              ->references('reff_id_pelanggan')->on('calon_pelanggan')
              ->cascadeOnDelete();

            $table->enum('status', [
                'draft',
                'ready_for_tracer',
                'tracer_approved',
                'tracer_rejected',
                'cgp_approved',
                'cgp_rejected',
                'approved_scheduled',
                'completed',
                'canceled',
            ])->default('draft')->index();

            $table->date('tanggal_gas_in')->nullable();
            $table->text('notes')->nullable();

            $table->enum('ai_overall_status', ['pending','passed','flagged'])->default('pending')->index();
            $table->timestamp('ai_checked_at')->nullable();

            $table->timestamp('tracer_approved_at')->nullable();
            $table->unsignedBigInteger('tracer_approved_by')->nullable();
            $table->foreign('tracer_approved_by', 'fk_gasin_tracer_by')->references('id')->on('users')->nullOnDelete();
            $table->text('tracer_notes')->nullable();

            $table->timestamp('cgp_approved_at')->nullable();
            $table->unsignedBigInteger('cgp_approved_by')->nullable();
            $table->foreign('cgp_approved_by', 'fk_gasin_cgp_by')->references('id')->on('users')->nullOnDelete();
            $table->text('cgp_notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by', 'fk_gasin_created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'fk_gasin_updated_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['reff_id_pelanggan', 'status'], 'idx_gasin_reff_status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('gas_in_data');
    }
};
