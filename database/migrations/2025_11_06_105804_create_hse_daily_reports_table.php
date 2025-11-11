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
        Schema::create('hse_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_laporan');
            $table->string('nama_proyek')->default('Pembangunan Jargas Gaskita Di Kabupaten Sleman');
            $table->string('pemberi_pekerjaan')->default('PGN - CGP');
            $table->string('kontraktor')->default('PT. KIAN SANTANG MULIATAMA TBK');
            $table->string('sub_kontraktor')->nullable();
            $table->enum('cuaca', ['cerah', 'berawan', 'mendung', 'hujan', 'hujan_lebat'])->default('cerah');
            $table->integer('jka_hari_ini')->default(0)->comment('Jam Kerja Aman hari ini (jam)');
            $table->integer('jka_kumulatif')->default(0)->comment('Jam Kerja Aman kumulatif total');
            $table->integer('total_pekerja')->default(0);
            $table->text('catatan')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('tanggal_laporan');
            $table->index('status');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hse_daily_reports');
    }
};
