<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix SK Data table
        DB::statement('ALTER TABLE sk_data DROP INDEX unique_sk_reff_id');
        DB::statement('ALTER TABLE sk_data ADD UNIQUE KEY unique_sk_reff_id_soft_delete (reff_id_pelanggan, deleted_at)');

        // Fix SR Data table
        DB::statement('ALTER TABLE sr_data DROP INDEX unique_sr_reff_id');
        DB::statement('ALTER TABLE sr_data ADD UNIQUE KEY unique_sr_reff_id_soft_delete (reff_id_pelanggan, deleted_at)');

        // Fix Gas In Data table
        DB::statement('ALTER TABLE gas_in_data DROP INDEX unique_gasin_reff_id');
        DB::statement('ALTER TABLE gas_in_data ADD UNIQUE KEY unique_gasin_reff_id_soft_delete (reff_id_pelanggan, deleted_at)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore original constraints
        DB::statement('ALTER TABLE sk_data DROP INDEX unique_sk_reff_id_soft_delete');
        DB::statement('ALTER TABLE sk_data ADD UNIQUE KEY unique_sk_reff_id (reff_id_pelanggan)');

        DB::statement('ALTER TABLE sr_data DROP INDEX unique_sr_reff_id_soft_delete');
        DB::statement('ALTER TABLE sr_data ADD UNIQUE KEY unique_sr_reff_id (reff_id_pelanggan)');

        DB::statement('ALTER TABLE gas_in_data DROP INDEX unique_gasin_reff_id_soft_delete');
        DB::statement('ALTER TABLE gas_in_data ADD UNIQUE KEY unique_gasin_reff_id (reff_id_pelanggan)');
    }
};
