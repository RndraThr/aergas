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
        // Clean up unused columns in sr_data table
        Schema::table('sr_data', function (Blueprint $table) {
            // These columns were added but not used in forms/views anymore
            $table->dropColumn([
                'panjang_pipa_pe_m',
                'panjang_casing_crossing_m'
            ]);
        });

        // Clean up any remaining 'validated' status in calon_pelanggan
        // (in case migration order causes issues)
        DB::table('calon_pelanggan')
            ->where('status', 'validated')
            ->update(['status' => 'lanjut']);

        // Clean up progress_status with removed modules
        DB::table('calon_pelanggan')
            ->where('progress_status', 'mgrt')
            ->update(['progress_status' => 'gas_in']);

        DB::table('calon_pelanggan')
            ->whereIn('progress_status', ['jalur_pipa', 'penyambungan'])
            ->update(['progress_status' => 'done']);

        // Update photo_approvals enum to remove old modules if still exists
        try {
            DB::statement("ALTER TABLE photo_approvals MODIFY COLUMN module_name ENUM('sk', 'sr', 'gas_in') NOT NULL");
        } catch (Exception $e) {
            // Ignore if already updated
        }

        // Update file_storages enum to remove old modules if still exists  
        try {
            DB::statement("ALTER TABLE file_storages MODIFY COLUMN module_name ENUM('sk', 'sr', 'gas_in') NOT NULL");
        } catch (Exception $e) {
            // Ignore if already updated
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the removed columns to sr_data
        Schema::table('sr_data', function (Blueprint $table) {
            $table->decimal('panjang_pipa_pe_m', 8, 2)->nullable();
            $table->decimal('panjang_casing_crossing_m', 8, 2)->nullable();
        });
    }
};