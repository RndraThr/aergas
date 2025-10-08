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
        Schema::table('calon_pelanggan', function (Blueprint $table) {
            $table->decimal('progress_percentage', 5, 2)->default(0)->after('progress_status')
                ->comment('Incremental progress percentage based on CGP-approved photos (0-100)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calon_pelanggan', function (Blueprint $table) {
            $table->dropColumn('progress_percentage');
        });
    }
};
