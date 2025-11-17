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
        Schema::table('pilot_comparisons', function (Blueprint $table) {
            $table->string('reff_id_pelanggan')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pilot_comparisons', function (Blueprint $table) {
            $table->string('reff_id_pelanggan')->nullable(false)->change();
        });
    }
};
