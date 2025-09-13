<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jalur_joint_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cluster_id');
            $table->unsignedBigInteger('fitting_type_id');
            $table->string('nomor_joint')->unique(); // KRG-CP001, KRG-CP002, etc
            $table->string('joint_code', 10); // 001, 002, 003, etc
            $table->boolean('is_used')->default(false);
            $table->unsignedBigInteger('used_by_joint_id')->nullable(); // FK to jalur_joint_data
            $table->timestamp('used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('cluster_id')->references('id')->on('jalur_clusters')->cascadeOnDelete();
            $table->foreign('fitting_type_id')->references('id')->on('jalur_fitting_types')->cascadeOnDelete();
            $table->foreign('used_by_joint_id')->references('id')->on('jalur_joint_data')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            
            $table->index(['cluster_id', 'fitting_type_id']);
            $table->index(['is_used', 'is_active']);
        });

        // Add foreign key constraint from jalur_joint_data to jalur_joint_numbers
        Schema::table('jalur_joint_data', function (Blueprint $table) {
            $table->foreign('joint_number_id')->references('id')->on('jalur_joint_numbers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        // Drop foreign key constraint first
        Schema::table('jalur_joint_data', function (Blueprint $table) {
            $table->dropForeign(['joint_number_id']);
        });

        Schema::dropIfExists('jalur_joint_numbers');
    }
};