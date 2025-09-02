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
        // Update any progress_status that uses removed modules to 'done'
        DB::table('calon_pelanggan')
            ->whereIn('progress_status', ['jalur_pipa', 'penyambungan'])
            ->update(['progress_status' => 'done']);

        // Modify the enum to remove jalur_pipa, penyambungan, and mgrt
        // New simplified flow: validasi -> sk -> sr -> gas_in -> done -> batal
        DB::statement("ALTER TABLE calon_pelanggan MODIFY COLUMN progress_status ENUM('validasi', 'sk', 'sr', 'gas_in', 'done', 'batal') NOT NULL DEFAULT 'validasi'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the removed modules
        DB::statement("ALTER TABLE calon_pelanggan MODIFY COLUMN progress_status ENUM('validasi', 'sk', 'sr', 'mgrt', 'gas_in', 'jalur_pipa', 'penyambungan', 'done', 'batal') NOT NULL DEFAULT 'validasi'");
    }
};