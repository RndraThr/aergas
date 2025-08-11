<?php

// database/migrations/2025_08_11_000001_add_kelurahan_padukuhan_to_calon_pelanggan.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('calon_pelanggan', function (Blueprint $table) {
            $table->string('kelurahan', 120)->nullable()->after('no_telepon');
            $table->string('padukuhan', 120)->nullable()->after('kelurahan');

            // kalau kolom lama tidak dipakai lagi, boleh di-drop:
            if (Schema::hasColumn('calon_pelanggan','wilayah_area')) {
                $table->dropColumn('wilayah_area');
            }
        });
    }

    public function down(): void
    {
        Schema::table('calon_pelanggan', function (Blueprint $table) {
            // rollback
            $table->dropColumn(['kelurahan','padukuhan']);
            // $table->string('wilayah_area',100)->nullable();
        });
    }
};
