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
        Schema::create('hse_pekerjaan_harian', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('daily_report_id');
            $table->string('jenis_pekerjaan'); // SK, SR, Konversi, Flushing, dll
            $table->text('deskripsi_pekerjaan');
            $table->text('lokasi_detail');
            $table->text('google_maps_link')->nullable();
            $table->timestamps();

            $table->foreign('daily_report_id')->references('id')->on('hse_daily_reports')->onDelete('cascade');
            $table->index('daily_report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hse_pekerjaan_harian');
    }
};
