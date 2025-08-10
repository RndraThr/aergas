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
        Schema::create('ba_batal_data', function (Blueprint $table) {
            $table->id();
            $table->string('reff_id_pelanggan', 50);

            // BA Batal Form Fields
            $table->text('alasan_batal');
            $table->string('foto_ba_url', 500)->nullable();
            $table->string('foto_pelanggan_url', 500)->nullable();
            $table->timestamp('tanggal_pembatalan');
            $table->unsignedBigInteger('processed_by');

            $table->timestamps();

            $table->foreign('reff_id_pelanggan')->references('reff_id_pelanggan')->on('calon_pelanggan')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ba_batal_data');
    }
};
