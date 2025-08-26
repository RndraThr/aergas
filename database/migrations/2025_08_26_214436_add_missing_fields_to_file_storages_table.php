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
        Schema::table('file_storages', function (Blueprint $table) {
            if (!Schema::hasColumn('file_storages', 'file_size')) {
                $table->bigInteger('file_size')->default(0)->after('mime_type');
            }
            if (!Schema::hasColumn('file_storages', 'size_bytes')) {
                $table->bigInteger('size_bytes')->default(0)->after('file_size');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_storages', function (Blueprint $table) {
            //
        });
    }
};
