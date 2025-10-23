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
            $table->timestamp('reverted_at')->nullable()->after('cgp_rejected_at');
            $table->unsignedBigInteger('reverted_by')->nullable()->after('reverted_at');
            $table->text('revert_reason')->nullable()->after('reverted_by');

            $table->foreign('reverted_by')->references('id')->on('users')->onDelete('set null');
            $table->index('reverted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photo_approvals', function (Blueprint $table) {
            $table->dropForeign(['reverted_by']);
            $table->dropIndex(['reverted_at']);
            $table->dropColumn(['reverted_at', 'reverted_by', 'revert_reason']);
        });
    }
};
