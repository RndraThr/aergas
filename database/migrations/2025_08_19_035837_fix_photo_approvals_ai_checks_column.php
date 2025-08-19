<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('photo_approvals', function (Blueprint $table) {
            // Change ai_checks from json to text to handle array-to-string conversion
            if (Schema::hasColumn('photo_approvals', 'ai_checks')) {
                $table->text('ai_checks')->nullable()->change();
            } else {
                $table->text('ai_checks')->nullable()->after('ai_score');
            }
        });
    }

    public function down()
    {
        Schema::table('photo_approvals', function (Blueprint $table) {
            $table->json('ai_checks')->nullable()->change();
        });
    }
};
