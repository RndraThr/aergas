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
        Schema::table('jalur_joint_numbers', function (Blueprint $table) {
            // Make fitting_type_id nullable to support diameter 90 pipes (direct pipe-to-pipe joint)
            $table->unsignedBigInteger('fitting_type_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jalur_joint_numbers', function (Blueprint $table) {
            // Revert back to non-nullable
            $table->unsignedBigInteger('fitting_type_id')->nullable(false)->change();
        });
    }
};
