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
        // Drop and recreate foreign keys with ON UPDATE CASCADE
        
        // SK Data
        try {
            Schema::table('sk_data', function (Blueprint $table) {
                $table->dropForeign('fk_sk_reff_pelanggan');
            });
        } catch (Exception $e) {
            // Continue if constraint doesn't exist
        }
        Schema::table('sk_data', function (Blueprint $table) {
            $table->foreign('reff_id_pelanggan', 'fk_sk_reff_pelanggan')
                  ->references('reff_id_pelanggan')->on('calon_pelanggan')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
        });

        // SR Data
        try {
            Schema::table('sr_data', function (Blueprint $table) {
                $table->dropForeign('fk_sr_reff_pelanggan');
            });
        } catch (Exception $e) {
            // Continue if constraint doesn't exist
        }
        Schema::table('sr_data', function (Blueprint $table) {
            $table->foreign('reff_id_pelanggan', 'fk_sr_reff_pelanggan')
                  ->references('reff_id_pelanggan')->on('calon_pelanggan')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
        });

        // Gas In Data  
        try {
            Schema::table('gas_in_data', function (Blueprint $table) {
                $table->dropForeign('fk_gasin_reff_pelanggan');
            });
        } catch (Exception $e) {
            // Continue if constraint doesn't exist
        }
        Schema::table('gas_in_data', function (Blueprint $table) {
            $table->foreign('reff_id_pelanggan', 'fk_gasin_reff_pelanggan')
                  ->references('reff_id_pelanggan')->on('calon_pelanggan')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
        });

        // File Storages
        try {
            Schema::table('file_storages', function (Blueprint $table) {
                $table->dropForeign(['reff_id_pelanggan']);
            });
        } catch (Exception $e) {
            // Continue if constraint doesn't exist
        }
        Schema::table('file_storages', function (Blueprint $table) {
            $table->foreign('reff_id_pelanggan')
                  ->references('reff_id_pelanggan')->on('calon_pelanggan')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
        });

        // Photo Approvals
        try {
            Schema::table('photo_approvals', function (Blueprint $table) {
                $table->dropForeign(['reff_id_pelanggan']);
            });
        } catch (Exception $e) {
            // Continue if constraint doesn't exist  
        }
        Schema::table('photo_approvals', function (Blueprint $table) {
            $table->foreign('reff_id_pelanggan')
                  ->references('reff_id_pelanggan')->on('calon_pelanggan')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration updates existing foreign keys to add CASCADE UPDATE
        // Rollback would be removing CASCADE UPDATE, but that might break existing functionality
        // It's safer to not rollback this change
    }
};
