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
        Schema::table('jalur_lowering_data', function (Blueprint $table) {
            // Add foto columns for Open Cut accessories
            $table->string('foto_evidence_marker_tape')->nullable()->after('foto_evidence_cassing_link');
            $table->text('foto_evidence_marker_tape_link')->nullable()->after('foto_evidence_marker_tape');
            $table->string('foto_evidence_concrete_slab')->nullable()->after('foto_evidence_marker_tape_link');
            $table->text('foto_evidence_concrete_slab_link')->nullable()->after('foto_evidence_concrete_slab');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jalur_lowering_data', function (Blueprint $table) {
            $table->dropColumn([
                'foto_evidence_marker_tape',
                'foto_evidence_marker_tape_link',
                'foto_evidence_concrete_slab',
                'foto_evidence_concrete_slab_link'
            ]);
        });
    }
};
