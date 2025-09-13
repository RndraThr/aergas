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
        Schema::table('jalur_lowering_data', function (Blueprint $table) {
            // Convert existing decimal values (in meters) to integer (in cm)
            // Multiply by 100 to convert meters to cm
            DB::statement('UPDATE jalur_lowering_data SET kedalaman_lowering = ROUND(kedalaman_lowering * 100)');
            
            // Change column type to integer
            $table->integer('kedalaman_lowering')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jalur_lowering_data', function (Blueprint $table) {
            // Change column type back to decimal
            $table->decimal('kedalaman_lowering', 10, 2)->change();
            
            // Convert existing integer values (in cm) to decimal (in meters)
            // Divide by 100 to convert cm back to meters
            DB::statement('UPDATE jalur_lowering_data SET kedalaman_lowering = kedalaman_lowering / 100');
        });
    }
};
