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
        // Update enum to include jalur_lowering
        DB::statement("ALTER TABLE photo_approvals MODIFY COLUMN module_name ENUM('sk', 'sr', 'gas_in', 'jalur_lowering')");
        
        // Remove foreign key constraint for reff_id_pelanggan (jalur lowering uses different reference)
        Schema::table('photo_approvals', function (Blueprint $table) {
            $table->dropForeign(['reff_id_pelanggan']);
            $table->string('reff_id_pelanggan', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore enum to original values
        DB::statement("ALTER TABLE photo_approvals MODIFY COLUMN module_name ENUM('sk', 'sr', 'gas_in')");
        
        // Restore foreign key constraint
        Schema::table('photo_approvals', function (Blueprint $table) {
            $table->string('reff_id_pelanggan', 50)->nullable(false)->change();
            $table->foreign('reff_id_pelanggan')->references('reff_id_pelanggan')->on('calon_pelanggan')->onDelete('cascade');
        });
    }
};
