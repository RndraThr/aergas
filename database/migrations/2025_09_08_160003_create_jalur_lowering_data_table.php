<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jalur_lowering_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('line_number_id');
            $table->string('nama_jalan');
            $table->date('tanggal_jalur');
            $table->enum('tipe_bongkaran', [
                'Manual Boring', 
                'Open Cut', 
                'Crossing', 
                'Zinker', 
                'HDD', 
                'Manual Boring - PK', 
                'Crossing - PK'
            ]);
            $table->decimal('penggelaran', 10, 2); // dalam meter
            $table->decimal('bongkaran', 10, 2); // dalam meter
            $table->decimal('kedalaman_lowering', 10, 2); // dalam meter
            
            // Aksesoris (conditional berdasarkan tipe_bongkaran)
            $table->boolean('aksesoris_cassing')->default(false); // untuk Crossing/Zinker
            $table->boolean('aksesoris_marker_tape')->default(false); // untuk Open Cut
            $table->boolean('aksesoris_concrete_slab')->default(false); // untuk Open Cut
            
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

            $table->foreign('line_number_id')->references('id')->on('jalur_line_numbers')->cascadeOnDelete();
            $table->foreign('tracer_approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cgp_approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            
            $table->index(['line_number_id', 'tanggal_jalur']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jalur_lowering_data');
    }
};