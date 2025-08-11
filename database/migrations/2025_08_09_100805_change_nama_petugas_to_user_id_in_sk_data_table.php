<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sk_data', function (Blueprint $table) {
            // Tambah user_id + FK
            if (!Schema::hasColumn('sk_data', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('reff_id_pelanggan');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            }

            // Hapus kolom lama (nama_petugas_sk)
            if (Schema::hasColumn('sk_data', 'nama_petugas_sk')) {
                $table->dropColumn('nama_petugas_sk');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sk_data', function (Blueprint $table) {
            // rollback: lepas FK & drop user_id
            if (Schema::hasColumn('sk_data', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }

            // kembalikan nama_petugas_sk
            if (!Schema::hasColumn('sk_data', 'nama_petugas_sk')) {
                $table->string('nama_petugas_sk', 255)->after('reff_id_pelanggan');
            }
        });
    }
};
