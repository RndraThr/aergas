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
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', [
                'super_admin',
                'admin',
                'sk',
                'sr',
                'mgrt',
                'gas_in',
                'pic',
                'tracer',
                'jalur'
            ]);
            $table->boolean('is_active')->default(true);
            $table->timestamp('assigned_at')->useCurrent();
            $table->foreignId('assigned_by')->nullable()->constrained('users');
            $table->timestamps();

            // Prevent duplicate role assignments
            $table->unique(['user_id', 'role'], 'user_role_unique');

            // Index for performance
            $table->index(['user_id', 'is_active']);
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
