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
            // Add 'Beton' to tipe_material enum
            $table->enum('tipe_material', ['Aspal', 'Tanah', 'Paving', 'Beton'])
                  ->nullable()
                  ->change();

            // Make tipe_bongkaran nullable (not required)
            $table->enum('tipe_bongkaran', [
                'Manual Boring',
                'Open Cut',
                'Crossing',
                'Zinker',
                'HDD',
                'Manual Boring - PK',
                'Crossing - PK'
            ])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jalur_lowering_data', function (Blueprint $table) {
            // Revert tipe_material enum to original values
            $table->enum('tipe_material', ['Aspal', 'Tanah', 'Paving'])
                  ->nullable()
                  ->change();

            // Revert tipe_bongkaran to required
            $table->enum('tipe_bongkaran', [
                'Manual Boring',
                'Open Cut',
                'Crossing',
                'Zinker',
                'HDD',
                'Manual Boring - PK',
                'Crossing - PK'
            ])->change();
        });
    }
};
