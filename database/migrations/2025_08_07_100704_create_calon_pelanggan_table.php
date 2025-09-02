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
        Schema::create('calon_pelanggan', function (Blueprint $table) {
            $table->string('reff_id_pelanggan', 50)->primary();
            $table->string('nama_pelanggan', 255);
            $table->text('alamat');
            $table->string('no_telepon', 20);
            $table->enum('status', ['validated', 'in_progress', 'lanjut', 'batal', 'pending'])->default('pending');
            $table->enum('progress_status', [
                'validasi', 'sk', 'sr', 'gas_in', 'done', 'batal'
            ])->default('validasi');
            $table->text('keterangan')->nullable();
            $table->string('wilayah_area', 100)->nullable();
            $table->string('jenis_pelanggan', 50)->default('residensial');
            $table->timestamp('tanggal_registrasi')->useCurrent();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('calon_pelanggan');
    }
};
