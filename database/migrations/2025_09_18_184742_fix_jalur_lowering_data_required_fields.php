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
            // Make tipe_bongkaran required again (not nullable)
            $table->enum('tipe_bongkaran', [
                'Manual Boring',
                'Open Cut',
                'Crossing',
                'Zinker',
                'HDD',
                'Manual Boring - PK',
                'Crossing - PK'
            ])->change();

            // Keep tipe_material nullable (not required) with Beton option
            $table->enum('tipe_material', ['Aspal', 'Tanah', 'Paving', 'Beton'])
                  ->nullable()
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jalur_lowering_data', function (Blueprint $table) {
            // Revert tipe_bongkaran to nullable
            $table->enum('tipe_bongkaran', [
                'Manual Boring',
                'Open Cut',
                'Crossing',
                'Zinker',
                'HDD',
                'Manual Boring - PK',
                'Crossing - PK'
            ])->nullable()->change();

            // Revert tipe_material to required without Beton
            $table->enum('tipe_material', ['Aspal', 'Tanah', 'Paving'])
                  ->change();
        });
    }
};
