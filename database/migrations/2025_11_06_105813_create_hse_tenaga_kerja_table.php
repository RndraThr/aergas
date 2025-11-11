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
        Schema::create('hse_tenaga_kerja', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('daily_report_id');
            $table->enum('kategori_team', ['PGN-CGP', 'OMM', 'KSM'])->default('KSM');
            $table->string('role_name'); // PM, HSE, SPV, dll (27 roles)
            $table->integer('jumlah_orang')->default(0);
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
        Schema::dropIfExists('hse_tenaga_kerja');
    }
};
