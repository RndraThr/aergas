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
    public function up()
    {
        Schema::table('file_storages', function (Blueprint $table) {
            // Check if file_hash column exists and modify it to have a default value
            if (Schema::hasColumn('file_storages', 'file_hash')) {
                // Modify the existing column to be nullable temporarily to avoid the error
                $table->string('file_hash', 64)->nullable()->change();
            }
        });

        // Update any existing records that might have null file_hash
        DB::table('file_storages')
            ->whereNull('file_hash')
            ->update(['file_hash' => 'legacy_hash_' . time()]);

        // Now make it non-nullable again with a proper default
        Schema::table('file_storages', function (Blueprint $table) {
            if (Schema::hasColumn('file_storages', 'file_hash')) {
                $table->string('file_hash', 64)->default('default_hash')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('file_storages', function (Blueprint $table) {
            if (Schema::hasColumn('file_storages', 'file_hash')) {
                // Restore to original state (non-nullable, no default)
                $table->string('file_hash', 64)->change();
            }
        });
    }
};