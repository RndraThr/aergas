<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('file_storages', function (Blueprint $table) {
            // Ensure photo_field_name exists with default
            if (!Schema::hasColumn('file_storages', 'photo_field_name')) {
                $table->string('photo_field_name', 100)->default('unknown')->after('module_name');
            } else {
                // Alter existing column to have default
                $table->string('photo_field_name', 100)->default('unknown')->change();
            }
        });
    }

    public function down()
    {
        Schema::table('file_storages', function (Blueprint $table) {
            $table->string('photo_field_name', 100)->nullable(false)->change();
        });
    }
};
