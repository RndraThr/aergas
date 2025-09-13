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
        Schema::table('photo_approvals', function (Blueprint $table) {
            // Add module_record_id for modules that don't use reff_id_pelanggan (like jalur)
            $table->unsignedBigInteger('module_record_id')->nullable()->after('reff_id_pelanggan');
            
            // Make reff_id_pelanggan nullable for jalur modules
            $table->string('reff_id_pelanggan', 50)->nullable()->change();
            
            // Add index for module queries
            $table->index(['module_name', 'module_record_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photo_approvals', function (Blueprint $table) {
            $table->dropIndex(['module_name', 'module_record_id']);
            $table->dropColumn('module_record_id');
            $table->string('reff_id_pelanggan', 50)->nullable(false)->change();
        });
    }
};
