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
        // Update existing data to new enum values
        DB::table('calon_pelanggan')->where('jenis_pelanggan', 'residensial')->update(['jenis_pelanggan' => 'pengembangan']);
        DB::table('calon_pelanggan')->where('jenis_pelanggan', 'komersial')->update(['jenis_pelanggan' => 'penetrasi']);
        DB::table('calon_pelanggan')->where('jenis_pelanggan', 'industri')->update(['jenis_pelanggan' => 'on_the_spot']);

        // Update null values to default
        DB::table('calon_pelanggan')->whereNull('jenis_pelanggan')->update(['jenis_pelanggan' => 'pengembangan']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback to old enum values
        DB::table('calon_pelanggan')->where('jenis_pelanggan', 'pengembangan')->update(['jenis_pelanggan' => 'residensial']);
        DB::table('calon_pelanggan')->where('jenis_pelanggan', 'penetrasi')->update(['jenis_pelanggan' => 'komersial']);
        DB::table('calon_pelanggan')->where('jenis_pelanggan', 'on_the_spot')->update(['jenis_pelanggan' => 'industri']);
    }
};
