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
        Schema::table('jalur_joint_data', function (Blueprint $table) {
            // Add optional third line for Equal Tee (3-way connection)
            $table->string('joint_line_optional')->nullable()->after('joint_line_to')->comment('Third line for Equal Tee connection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jalur_joint_data', function (Blueprint $table) {
            $table->dropColumn('joint_line_optional');
        });
    }
};
