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
            $table->timestamp('organized_at')->nullable()->after('cgp_approved_at');
            $table->string('organized_folder')->nullable()->after('organized_at');
            $table->string('stored_filename')->nullable()->after('photo_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photo_approvals', function (Blueprint $table) {
            $table->dropColumn(['organized_at', 'organized_folder', 'stored_filename']);
        });
    }
};
