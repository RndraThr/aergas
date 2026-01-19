<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jalur_lowering_data', function (Blueprint $table) {
            $table->boolean('aksesoris_landasan')->default(false)->after('concrete_slab_quantity');
            $table->decimal('landasan_quantity', 10, 2)->nullable()->after('aksesoris_landasan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jalur_lowering_data', function (Blueprint $table) {
            $table->dropColumn(['aksesoris_landasan', 'landasan_quantity']);
        });
    }
};
