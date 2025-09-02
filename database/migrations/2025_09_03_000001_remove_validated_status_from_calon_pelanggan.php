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
        // Update all 'validated' status to 'lanjut'
        DB::table('calon_pelanggan')
            ->where('status', 'validated')
            ->update(['status' => 'lanjut']);

        // Modify the enum to remove 'validated' and keep the new flow:
        // pending -> lanjut -> in_progress -> batal
        DB::statement("ALTER TABLE calon_pelanggan MODIFY COLUMN status ENUM('pending', 'lanjut', 'in_progress', 'batal') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add 'validated' back to enum
        DB::statement("ALTER TABLE calon_pelanggan MODIFY COLUMN status ENUM('pending', 'validated', 'lanjut', 'in_progress', 'batal') NOT NULL DEFAULT 'pending'");

        // Convert 'lanjut' back to 'validated' (only if they don't have progress_status = 'done')
        DB::table('calon_pelanggan')
            ->where('status', 'lanjut')
            ->where('progress_status', '!=', 'done')
            ->update(['status' => 'validated']);
    }
};