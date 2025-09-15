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
            $table->enum('tipe_material', ['Aspal', 'Tanah', 'Paving'])
                  ->after('tipe_bongkaran')
                  ->nullable()
                  ->comment('Tipe material yang akan dibongkar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jalur_lowering_data', function (Blueprint $table) {
            $table->dropColumn('tipe_material');
        });
    }
};
