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
        $this->handleDuplicates('sk_data', 'SK');
        $this->handleDuplicates('sr_data', 'SR');
        $this->handleDuplicates('gas_in_data', 'GAS_IN');

        // Add unique constraints
        Schema::table('sk_data', function (Blueprint $table) {
            $table->unique('reff_id_pelanggan', 'unique_sk_reff_id');
        });

        Schema::table('sr_data', function (Blueprint $table) {
            $table->unique('reff_id_pelanggan', 'unique_sr_reff_id');
        });

        Schema::table('gas_in_data', function (Blueprint $table) {
            $table->unique('reff_id_pelanggan', 'unique_gasin_reff_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sk_data', function (Blueprint $table) {
            $table->dropUnique('unique_sk_reff_id');
        });

        Schema::table('sr_data', function (Blueprint $table) {
            $table->dropUnique('unique_sr_reff_id');
        });

        Schema::table('gas_in_data', function (Blueprint $table) {
            $table->dropUnique('unique_gasin_reff_id');
        });
    }

    /**
     * Handle duplicate records for a module table
     */
    private function handleDuplicates(string $tableName, string $moduleName): void
    {
        echo "\n🔍 Checking duplicates in {$tableName}...\n";

        // Find duplicates
        $duplicates = DB::select("
            SELECT reff_id_pelanggan, COUNT(*) as count 
            FROM {$tableName} 
            WHERE deleted_at IS NULL 
            GROUP BY reff_id_pelanggan 
            HAVING count > 1
        ");

        if (empty($duplicates)) {
            echo "✅ No duplicates found in {$tableName}\n";
            return;
        }

        echo "⚠️  Found " . count($duplicates) . " duplicate groups in {$tableName}\n";

        $totalCleaned = 0;

        foreach ($duplicates as $duplicate) {
            $reffId = $duplicate->reff_id_pelanggan;
            
            // Get all records for this reff_id, ordered by creation date (keep oldest)
            $records = DB::table($tableName)
                ->where('reff_id_pelanggan', $reffId)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'asc')
                ->get();

            if ($records->count() > 1) {
                // Keep the first (oldest) record, soft delete the rest
                $recordsToDelete = $records->skip(1);
                
                foreach ($recordsToDelete as $record) {
                    DB::table($tableName)
                        ->where('id', $record->id)
                        ->update(['deleted_at' => now()]);
                    
                    // Also soft delete related photo_approvals for newer records
                    $moduleNameLower = strtolower($moduleName);
                    $deletedPhotos = DB::table('photo_approvals')
                        ->where('reff_id_pelanggan', $reffId)
                        ->where('module_name', $moduleNameLower)
                        ->whereNull('deleted_at')
                        ->where('created_at', '>=', $record->created_at)
                        ->update(['deleted_at' => now()]);

                    $totalCleaned++;
                    echo "  🗑️  Cleaned duplicate ID {$record->id} for reff_id: {$reffId}\n";
                }
            }
        }

        echo "✅ Cleaned {$totalCleaned} duplicate records from {$tableName}\n";
    }
};