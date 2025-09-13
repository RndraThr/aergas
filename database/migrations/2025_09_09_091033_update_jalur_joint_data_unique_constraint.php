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
        // Drop existing unique constraint
        Schema::table('jalur_joint_data', function (Blueprint $table) {
            $table->dropUnique(['nomor_joint']);
        });
        
        // Add new unique constraint that excludes soft-deleted records
        DB::statement('ALTER TABLE jalur_joint_data ADD CONSTRAINT jalur_joint_data_nomor_joint_unique UNIQUE (nomor_joint, deleted_at)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the composite unique constraint
        DB::statement('ALTER TABLE jalur_joint_data DROP INDEX jalur_joint_data_nomor_joint_unique');
        
        // Restore original unique constraint (without soft delete consideration)
        Schema::table('jalur_joint_data', function (Blueprint $table) {
            $table->unique('nomor_joint');
        });
    }
};
