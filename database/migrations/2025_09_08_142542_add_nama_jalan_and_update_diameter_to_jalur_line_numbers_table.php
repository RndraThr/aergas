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
        Schema::table('jalur_line_numbers', function (Blueprint $table) {
            // Add nama_jalan column
            $table->string('nama_jalan')->after('line_number');
        });
        
        // Update diameter enum to include all sizes
        DB::statement("ALTER TABLE jalur_line_numbers MODIFY COLUMN diameter ENUM('63', '90', '110', '160', '180', '200')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jalur_line_numbers', function (Blueprint $table) {
            $table->dropColumn('nama_jalan');
        });
        
        // Revert diameter enum back to original
        DB::statement("ALTER TABLE jalur_line_numbers MODIFY COLUMN diameter ENUM('63', '180')");
    }
};
