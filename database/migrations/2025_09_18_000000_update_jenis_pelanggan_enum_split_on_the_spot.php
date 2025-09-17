<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing 'on_the_spot' records to 'on_the_spot_penetrasi' (as default)
        DB::table('calon_pelanggan')
            ->where('jenis_pelanggan', 'on_the_spot')
            ->update(['jenis_pelanggan' => 'on_the_spot_penetrasi']);

        // Now modify the column to include the new enum values
        DB::statement("ALTER TABLE calon_pelanggan MODIFY COLUMN jenis_pelanggan ENUM('pengembangan', 'penetrasi', 'on_the_spot_penetrasi', 'on_the_spot_pengembangan') DEFAULT 'pengembangan'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback: convert both on_the_spot variants back to single 'on_the_spot'
        DB::table('calon_pelanggan')
            ->whereIn('jenis_pelanggan', ['on_the_spot_penetrasi', 'on_the_spot_pengembangan'])
            ->update(['jenis_pelanggan' => 'on_the_spot']);

        // Restore the original enum
        DB::statement("ALTER TABLE calon_pelanggan MODIFY COLUMN jenis_pelanggan ENUM('pengembangan', 'penetrasi', 'on_the_spot') DEFAULT 'pengembangan'");
    }
};