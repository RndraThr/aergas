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
        Schema::table('jalur_lowering_data', function (Blueprint $table) {
            // Add cassing fields for Open Cut (added to existing aksesoris)
            $table->decimal('cassing_quantity', 10, 2)->nullable()->after('concrete_slab_quantity')->comment('Cassing quantity in meters');
            $table->enum('cassing_type', ['4_inch', '8_inch'])->nullable()->after('cassing_quantity')->comment('Cassing pipe diameter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jalur_lowering_data', function (Blueprint $table) {
            $table->dropColumn(['cassing_quantity', 'cassing_type']);
        });
    }
};
