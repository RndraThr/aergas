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
            $table->unsignedBigInteger('joint_number_id')->nullable()->after('fitting_type_id');
            $table->foreign('joint_number_id')->references('id')->on('jalur_joint_numbers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jalur_joint_data', function (Blueprint $table) {
            $table->dropForeign(['joint_number_id']);
            $table->dropColumn('joint_number_id');
        });
    }
};
