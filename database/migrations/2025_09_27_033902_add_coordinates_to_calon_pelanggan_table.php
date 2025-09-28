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
            $table->decimal('latitude', 10, 8)->nullable()->after('alamat')->comment('Koordinat latitude pelanggan');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude')->comment('Koordinat longitude pelanggan');
            $table->string('coordinate_source', 50)->nullable()->after('longitude')->comment('Sumber koordinat: manual, gps, geocoding');
            $table->timestamp('coordinate_updated_at')->nullable()->after('coordinate_source')->comment('Terakhir update koordinat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calon_pelanggan', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'coordinate_source', 'coordinate_updated_at']);
        });
    }
};
