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
        Schema::create('pilot_comparisons', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->index(); // Group upload per batch
            $table->string('reff_id_pelanggan')->index();
            $table->string('nama_pelanggan')->nullable();
            $table->text('alamat')->nullable();

            // Data dari PILOT sheet
            $table->date('pilot_tanggal_sk')->nullable();
            $table->date('pilot_tanggal_sr')->nullable();
            $table->date('pilot_tanggal_gas_in')->nullable();
            $table->string('pilot_status_sk')->nullable();
            $table->string('pilot_status_sr')->nullable();
            $table->string('pilot_status_gas_in')->nullable();
            $table->json('pilot_raw_data')->nullable(); // Store all pilot data as JSON

            // Data dari Database
            $table->date('db_tanggal_sk')->nullable();
            $table->date('db_tanggal_sr')->nullable();
            $table->date('db_tanggal_gas_in')->nullable();
            $table->string('db_status_sk')->nullable();
            $table->string('db_status_sr')->nullable();
            $table->string('db_status_gas_in')->nullable();

            // Comparison Result
            $table->enum('comparison_status', [
                'match',           // Semua cocok
                'date_mismatch',   // Ada perbedaan tanggal
                'status_mismatch', // Ada perbedaan status
                'missing_in_db',   // Ada di PILOT tapi tidak di DB
                'missing_in_pilot' // Ada di DB tapi tidak di PILOT (optional)
            ]);

            // Details of differences
            $table->json('differences')->nullable(); // Detail perbedaan apa saja

            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilot_comparisons');
    }
};
