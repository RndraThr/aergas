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
            $table->json('comparison_results')->nullable()->after('differences');
            $table->integer('total_records')->default(0)->after('comparison_results');
            $table->integer('without_reff_id')->default(0)->after('total_records');
            $table->integer('new_customers')->default(0)->after('without_reff_id');
            $table->integer('incomplete_installation')->default(0)->after('new_customers');
            $table->integer('ready_to_insert')->default(0)->after('incomplete_installation');
            $table->timestamp('compared_at')->nullable()->after('ready_to_insert');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pilot_comparisons', function (Blueprint $table) {
            $table->dropColumn([
                'comparison_results',
                'total_records',
                'without_reff_id',
                'new_customers',
                'incomplete_installation',
                'ready_to_insert',
                'compared_at',
            ]);
        });
    }
};
