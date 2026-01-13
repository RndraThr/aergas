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
        Schema::table('jalur_joint_data', function (Blueprint $table) {
            $table->string('diameter', 10)->nullable()->after('fitting_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jalur_joint_data', function (Blueprint $table) {
            $table->dropColumn('diameter');
        });
    }
};
