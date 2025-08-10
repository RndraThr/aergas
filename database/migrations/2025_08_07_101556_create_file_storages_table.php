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
        Schema::create('file_storages', function (Blueprint $table) {
            $table->id();
            $table->string('reff_id_pelanggan', 50);
            $table->enum('module_name', ['sk', 'sr', 'mgrt', 'gas_in', 'jalur_pipa', 'penyambungan', 'ba_batal']);
            $table->string('field_name', 100);
            $table->string('original_filename', 255);
            $table->string('stored_filename', 255);
            $table->string('file_path', 500);
            $table->string('google_drive_id', 100)->nullable();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->string('file_hash', 64); // SHA256 for duplicate detection
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();

            $table->foreign('reff_id_pelanggan')->references('reff_id_pelanggan')->on('calon_pelanggan')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');

            $table->index(['reff_id_pelanggan', 'module_name']);
            $table->index('file_hash');
        });
    }

    public function down()
    {
        Schema::dropIfExists('file_storages');
    }
};
