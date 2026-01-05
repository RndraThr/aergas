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
        Schema::table('calon_pelanggan', function (Blueprint $table) {
            $table->string('kota_kabupaten', 100)->nullable()->after('kelurahan');
            $table->string('kecamatan', 100)->nullable()->after('kota_kabupaten');
            $table->string('no_ktp', 20)->nullable()->after('no_telepon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calon_pelanggan', function (Blueprint $table) {
            $table->dropColumn(['kota_kabupaten', 'kecamatan', 'no_ktp']);
        });
    }
};
