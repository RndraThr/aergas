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
        // Add module_status column to sk_data table
        Schema::table('sk_data', function (Blueprint $table) {
            $table->string('module_status', 50)->default('draft')->after('status');
            $table->string('overall_photo_status', 50)->nullable()->after('module_status');
        });

        // Add module_status column to sr_data table  
        Schema::table('sr_data', function (Blueprint $table) {
            $table->string('module_status', 50)->default('draft')->after('status');
            $table->string('overall_photo_status', 50)->nullable()->after('module_status');
        });

        // Add module_status column to gas_in_data table
        Schema::table('gas_in_data', function (Blueprint $table) {
            $table->string('module_status', 50)->default('draft')->after('status');
            $table->string('overall_photo_status', 50)->nullable()->after('module_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sk_data', function (Blueprint $table) {
            $table->dropColumn(['module_status', 'overall_photo_status']);
        });

        Schema::table('sr_data', function (Blueprint $table) {
            $table->dropColumn(['module_status', 'overall_photo_status']);
        });

        Schema::table('gas_in_data', function (Blueprint $table) {
            $table->dropColumn(['module_status', 'overall_photo_status']);
        });
    }
};
