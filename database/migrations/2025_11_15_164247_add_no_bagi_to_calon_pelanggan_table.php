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
            // Add no_bagi column (rt and rw already exist)
            if (!Schema::hasColumn('calon_pelanggan', 'no_bagi')) {
                $table->string('no_bagi', 20)->nullable()->after('no_telepon');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calon_pelanggan', function (Blueprint $table) {
            if (Schema::hasColumn('calon_pelanggan', 'no_bagi')) {
                $table->dropColumn('no_bagi');
            }
        });
    }
};
