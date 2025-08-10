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
        Schema::table('sk_data', function (Blueprint $table) {
            // 1. Tambahkan kolom baru 'user_id' setelah 'reff_id_pelanggan'
            $table->unsignedBigInteger('user_id')->nullable()->after('reff_id_pelanggan');

            // 2. Buat foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // 3. Hapus kolom lama
            $table->dropColumn('nama_petugas_sk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sk_data', function (Blueprint $table) {
            // Logika untuk mengembalikan perubahan jika diperlukan
            $table->string('nama_petugas_sk', 255)->after('reff_id_pelanggan');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
