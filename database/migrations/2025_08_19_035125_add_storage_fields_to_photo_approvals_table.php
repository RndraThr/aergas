<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('photo_approvals', function (Blueprint $table) {
            // Fix: Tambah kolom storage yang hilang
            if (!Schema::hasColumn('photo_approvals', 'storage_disk')) {
                $table->string('storage_disk', 20)->default('public')->after('photo_url');
            }

            if (!Schema::hasColumn('photo_approvals', 'storage_path')) {
                $table->string('storage_path', 500)->nullable()->after('storage_disk');
            }

            if (!Schema::hasColumn('photo_approvals', 'drive_file_id')) {
                $table->string('drive_file_id', 100)->nullable()->after('storage_path');
            }

            if (!Schema::hasColumn('photo_approvals', 'drive_link')) {
                $table->string('drive_link', 500)->nullable()->after('drive_file_id');
            }

            if (!Schema::hasColumn('photo_approvals', 'uploaded_by')) {
                $table->unsignedBigInteger('uploaded_by')->nullable()->after('drive_link');
                $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('photo_approvals', 'uploaded_at')) {
                $table->timestamp('uploaded_at')->nullable()->after('uploaded_by');
            }

            if (!Schema::hasColumn('photo_approvals', 'ai_status')) {
                $table->enum('ai_status', ['pending', 'passed', 'failed'])->default('pending')->after('uploaded_at');
            }

            if (!Schema::hasColumn('photo_approvals', 'ai_score')) {
                $table->decimal('ai_score', 5, 2)->nullable()->after('ai_status');
            }

            if (!Schema::hasColumn('photo_approvals', 'ai_checks')) {
                $table->json('ai_checks')->nullable()->after('ai_score');
            }

            if (!Schema::hasColumn('photo_approvals', 'ai_notes')) {
                $table->text('ai_notes')->nullable()->after('ai_checks');
            }

            if (!Schema::hasColumn('photo_approvals', 'ai_last_checked_at')) {
                $table->timestamp('ai_last_checked_at')->nullable()->after('ai_notes');
            }
        });
    }

    public function down()
    {
        Schema::table('photo_approvals', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->dropColumn([
                'storage_disk', 'storage_path', 'drive_file_id', 'drive_link',
                'uploaded_by', 'uploaded_at', 'ai_status', 'ai_score',
                'ai_checks', 'ai_notes', 'ai_last_checked_at'
            ]);
        });
    }
};
