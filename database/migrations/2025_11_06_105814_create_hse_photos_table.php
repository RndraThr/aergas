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
        Schema::create('hse_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('daily_report_id');
            $table->string('photo_category')->default('pekerjaan'); // pekerjaan/tbm/kondisi_site/apd/housekeeping/incident
            $table->string('photo_url');
            $table->string('drive_file_id')->nullable();
            $table->text('drive_link')->nullable();
            $table->text('storage_path')->nullable();
            $table->string('storage_disk')->default('gdrive');
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();

            $table->foreign('daily_report_id')->references('id')->on('hse_daily_reports')->onDelete('cascade');
            $table->index('daily_report_id');
            $table->index('photo_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hse_photos');
    }
};
