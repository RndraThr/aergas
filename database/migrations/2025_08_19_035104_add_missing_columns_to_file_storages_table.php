<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('file_storages', function (Blueprint $table) {
            // Fix: Tambah kolom yang hilang
            if (!Schema::hasColumn('file_storages', 'photo_field_name')) {
                $table->string('photo_field_name', 100)->after('module_name');
            }

            if (!Schema::hasColumn('file_storages', 'storage_disk')) {
                $table->string('storage_disk', 20)->default('public')->after('photo_field_name');
            }

            if (!Schema::hasColumn('file_storages', 'storage_path')) {
                $table->string('storage_path', 500)->nullable()->after('storage_disk');
            }

            if (!Schema::hasColumn('file_storages', 'url')) {
                $table->string('url', 500)->nullable()->after('storage_path');
            }

            if (!Schema::hasColumn('file_storages', 'drive_file_id')) {
                $table->string('drive_file_id', 100)->nullable()->after('url');
            }

            if (!Schema::hasColumn('file_storages', 'drive_view_link')) {
                $table->string('drive_view_link', 500)->nullable()->after('drive_file_id');
            }

            if (!Schema::hasColumn('file_storages', 'size_bytes')) {
                $table->bigInteger('size_bytes')->default(0)->after('mime_type');
            }

            if (!Schema::hasColumn('file_storages', 'status')) {
                $table->enum('status', ['active', 'deleted'])->default('active')->after('size_bytes');
            }

            if (!Schema::hasColumn('file_storages', 'ai_status')) {
                $table->enum('ai_status', ['pending', 'passed', 'failed'])->nullable()->after('status');
            }

            // Update field_name to have default value
            if (Schema::hasColumn('file_storages', 'field_name')) {
                $table->string('field_name', 100)->default('unknown')->change();
            } else {
                $table->string('field_name', 100)->default('unknown')->after('module_name');
            }
        });
    }

    public function down()
    {
        Schema::table('file_storages', function (Blueprint $table) {
            $table->dropColumn([
                'photo_field_name', 'storage_disk', 'storage_path',
                'url', 'drive_file_id', 'drive_view_link',
                'size_bytes', 'status', 'ai_status'
            ]);
        });
    }
};
