<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 100); // 'create', 'update', 'delete', 'approve', 'reject'
            $table->string('model_type', 100); // 'SkData', 'PhotoApproval', etc.
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('reff_id_pelanggan', 50)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['user_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index('reff_id_pelanggan');
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
};
