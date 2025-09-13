<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jalur_line_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('line_number')->unique(); // Format: 63-KRG-LN013
            $table->enum('diameter', ['63', '180']);
            $table->unsignedBigInteger('cluster_id');
            $table->string('line_code', 10); // LN013, LN014, dst
            $table->decimal('estimasi_panjang', 10, 2); // dalam meter
            $table->decimal('actual_mc100', 10, 2)->nullable(); // Penggelaran MC-100 final
            $table->decimal('total_penggelaran', 10, 2)->default(0); // Sum dari lowering entries
            $table->enum('status_line', ['draft', 'in_progress', 'completed'])->default('draft');
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('cluster_id')->references('id')->on('jalur_clusters')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            
            $table->index(['diameter', 'cluster_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jalur_line_numbers');
    }
};