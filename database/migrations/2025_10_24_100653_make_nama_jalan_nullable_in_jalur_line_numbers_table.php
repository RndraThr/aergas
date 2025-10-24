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
        Schema::table('jalur_line_numbers', function (Blueprint $table) {
            // Make nama_jalan nullable since it can be filled later after line number creation
            $table->string('nama_jalan')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jalur_line_numbers', function (Blueprint $table) {
            // Revert to NOT NULL (requires data cleanup first)
            $table->string('nama_jalan')->nullable(false)->change();
        });
    }
};
