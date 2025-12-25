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
            $table->integer('total_joint_from')->default(0)->after('total_penggelaran')
                ->comment('Count of joints where this line is joint_line_from');
            $table->integer('total_joint_to')->default(0)->after('total_joint_from')
                ->comment('Count of joints where this line is joint_line_to');
            $table->integer('total_joint_optional')->default(0)->after('total_joint_to')
                ->comment('Count of joints where this line is joint_line_optional (for Equal Tee)');
            $table->integer('total_joint')->default(0)->after('total_joint_optional')
                ->comment('Total count of all joints connected to this line');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jalur_line_numbers', function (Blueprint $table) {
            $table->dropColumn(['total_joint_from', 'total_joint_to', 'total_joint_optional', 'total_joint']);
        });
    }
};
