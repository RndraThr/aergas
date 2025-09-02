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
        // Drop foreign keys first to avoid constraint issues, but only if tables exist
        if (Schema::hasTable('material_request_items')) {
            try {
                Schema::table('material_request_items', function (Blueprint $table) {
                    $table->dropForeign(['material_request_id']);
                    $table->dropForeign(['gudang_item_id']);
                });
            } catch (Exception $e) {
                // Ignore if foreign keys don't exist
            }
        }

        if (Schema::hasTable('gudang_transactions')) {
            try {
                Schema::table('gudang_transactions', function (Blueprint $table) {
                    $table->dropForeign(['gudang_item_id']);
                    $table->dropForeign(['user_id']);
                });
            } catch (Exception $e) {
                // Ignore if foreign keys don't exist
            }
        }

        // Drop unused tables from removed modules
        Schema::dropIfExists('material_request_items');
        Schema::dropIfExists('material_requests');
        Schema::dropIfExists('gudang_transactions');
        Schema::dropIfExists('gudang_stock_balances'); // This is a view, but let's try
        Schema::dropIfExists('gudang_items');
        Schema::dropIfExists('jalur_pipa_data');
        Schema::dropIfExists('penyambungan_pipa_data');
        Schema::dropIfExists('ba_batal_data');

        // Clean up any records in photo_approvals that reference removed modules
        DB::table('photo_approvals')
            ->whereIn('module_name', ['jalur_pipa', 'penyambungan', 'mgrt', 'ba_batal'])
            ->delete();

        // Clean up any records in file_storages that reference removed modules
        DB::table('file_storages')
            ->whereIn('module_name', ['jalur_pipa', 'penyambungan', 'mgrt', 'ba_batal'])
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is destructive - we won't recreate the tables
        // If rollback is needed, use backup to restore data
        $this->comment('This migration is destructive. Use database backup to restore if needed.');
    }
};