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
        Schema::create('map_geometric_features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('feature_type', ['line', 'polygon', 'circle']);
            $table->foreignId('line_number_id')->nullable()->constrained('jalur_line_numbers')->onDelete('cascade');
            $table->foreignId('cluster_id')->nullable()->constrained('jalur_clusters')->onDelete('cascade');
            $table->json('geometry');
            $table->json('style_properties')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->integer('display_order')->default(0);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for performance
            $table->index(['feature_type']);
            $table->index(['line_number_id']);
            $table->index(['cluster_id']);
            $table->index(['is_visible']);
            $table->index(['display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('map_geometric_features');
    }
};
