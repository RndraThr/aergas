<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jalur_joint_data', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_joint');
            $table->string('nomor_joint'); // Format: KRG-CP001
            $table->unsignedBigInteger('cluster_id');
            $table->unsignedBigInteger('fitting_type_id');
            $table->unsignedBigInteger('joint_number_id')->nullable();
            $table->string('joint_code', 10); // CP001, EL002, dst
            $table->string('joint_line_from'); // Line number asal
            $table->string('joint_line_to'); // Line number tujuan
            $table->enum('tipe_penyambungan', ['EF', 'BF']);
            
            $table->enum('status_laporan', [
                'draft',
                'acc_tracer', 
                'acc_cgp', 
                'revisi_tracer', 
                'revisi_cgp'
            ])->default('draft');
            
            // Approval fields
            $table->timestamp('tracer_approved_at')->nullable();
            $table->unsignedBigInteger('tracer_approved_by')->nullable();
            $table->text('tracer_notes')->nullable();
            $table->timestamp('cgp_approved_at')->nullable();
            $table->unsignedBigInteger('cgp_approved_by')->nullable();
            $table->text('cgp_notes')->nullable();
            
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('cluster_id')->references('id')->on('jalur_clusters')->cascadeOnDelete();
            $table->foreign('fitting_type_id')->references('id')->on('jalur_fitting_types')->cascadeOnDelete();
            $table->foreign('tracer_approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cgp_approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            
            $table->unique('nomor_joint');
            $table->index(['cluster_id', 'fitting_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jalur_joint_data');
    }
};