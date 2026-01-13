<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jalur_clusters', function (Blueprint $table) {
            // RS Sektor/Location name (e.g., "RSUP SARDJITO")
            $table->string('rs_sektor')->nullable()->after('code_cluster');

            // SPK Project Name (default: "City Gas 5 Tahap 2")
            $table->string('spk_name')->default('City Gas 5 Tahap 2')->after('rs_sektor');

            // Test Package Code (e.g., "TP-PK-KI")
            $table->string('test_package_code')->nullable()->after('spk_name');

            // Cluster name for sheet display (if different from nama_cluster)
            $table->string('sheet_cluster_name')->nullable()->after('test_package_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jalur_clusters', function (Blueprint $table) {
            $table->dropColumn(['rs_sektor', 'spk_name', 'test_package_code', 'sheet_cluster_name']);
        });
    }
};
