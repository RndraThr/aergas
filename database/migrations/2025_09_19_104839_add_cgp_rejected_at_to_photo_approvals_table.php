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
        Schema::table('photo_approvals', function (Blueprint $table) {
            $table->timestamp('cgp_rejected_at')->nullable()->after('cgp_approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photo_approvals', function (Blueprint $table) {
            $table->dropColumn('cgp_rejected_at');
        });
    }
};
