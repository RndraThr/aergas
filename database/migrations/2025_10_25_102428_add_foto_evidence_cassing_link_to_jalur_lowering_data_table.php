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
            $table->string('foto_evidence_cassing')->nullable()->after('cassing_type');
            $table->text('foto_evidence_cassing_link')->nullable()->after('foto_evidence_cassing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jalur_lowering_data', function (Blueprint $table) {
            $table->dropColumn(['foto_evidence_cassing', 'foto_evidence_cassing_link']);
        });
    }
};
