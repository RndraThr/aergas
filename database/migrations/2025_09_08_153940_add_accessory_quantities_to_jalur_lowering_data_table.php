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
            // Add quantity fields for Open Cut accessories
            $table->decimal('marker_tape_quantity', 10, 2)->nullable()->after('aksesoris_marker_tape');
            $table->integer('concrete_slab_quantity')->nullable()->after('aksesoris_concrete_slab');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jalur_lowering_data', function (Blueprint $table) {
            $table->dropColumn(['marker_tape_quantity', 'concrete_slab_quantity']);
        });
    }
};
