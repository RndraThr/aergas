<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sr_data', function (Blueprint $t) {
            $t->engine = 'InnoDB';

            $t->id();

            // FK ke calon_pelanggan.reff_id_pelanggan (string PK)
            $t->string('reff_id_pelanggan', 50);
            $t->foreign('reff_id_pelanggan', 'fk_sr_reff_pelanggan')
              ->references('reff_id_pelanggan')->on('calon_pelanggan')
              ->cascadeOnDelete();

            // Identitas & status
            $t->string('nomor_sr')->nullable()->unique();
            $t->enum('status', [
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

            // Data teknis SR (tanpa material tracking)
            $t->decimal('panjang_pipa_pe', 10, 2)->nullable();
            $t->decimal('panjang_casing_crossing_sr', 10, 2)->nullable();
            $t->date('tanggal_pemasangan')->nullable();
            $t->text('notes')->nullable();

            // Ringkasan AI (opsional)
            $t->enum('ai_overall_status', ['pending','passed','flagged'])->default('pending')->index();
            $t->timestamp('ai_checked_at')->nullable();

            // Approval chain
            $t->timestamp('tracer_approved_at')->nullable();
            $t->unsignedBigInteger('tracer_approved_by')->nullable();
            $t->foreign('tracer_approved_by', 'fk_sr_tracer_by')->references('id')->on('users')->nullOnDelete();
            $t->text('tracer_notes')->nullable();

            $t->timestamp('cgp_approved_at')->nullable();
            $t->unsignedBigInteger('cgp_approved_by')->nullable();
            $t->foreign('cgp_approved_by', 'fk_sr_cgp_by')->references('id')->on('users')->nullOnDelete();
            $t->text('cgp_notes')->nullable();

            // Audit user
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->foreign('created_by', 'fk_sr_created_by')->references('id')->on('users')->nullOnDelete();
            $t->foreign('updated_by', 'fk_sr_updated_by')->references('id')->on('users')->nullOnDelete();

            $t->timestamps();
            $t->softDeletes();

            $t->index(['reff_id_pelanggan', 'status'], 'idx_sr_reff_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sr_data');
    }
};
