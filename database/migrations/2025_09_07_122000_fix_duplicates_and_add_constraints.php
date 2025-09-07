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
        echo "\n🔧 Starting duplicate cleanup and constraint addition...\n";

        // Step 1: Hard delete soft deleted records to avoid constraint violations
        $this->cleanupSoftDeleted();
        
        // Step 2: Handle remaining duplicates
        $this->handleDuplicates('sk_data', 'SK');
        $this->handleDuplicates('sr_data', 'SR');
        $this->handleDuplicates('gas_in_data', 'GAS_IN');

        // Step 3: Add unique constraints (skip if already exists)
        $this->addUniqueConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('sk_data', function (Blueprint $table) {
                $table->dropUnique('unique_sk_reff_id');
            });
        } catch (Exception $e) {
            // Ignore if constraint doesn't exist
        }

        try {
            Schema::table('sr_data', function (Blueprint $table) {
                $table->dropUnique('unique_sr_reff_id');
            });
        } catch (Exception $e) {
            // Ignore if constraint doesn't exist
        }

        try {
            Schema::table('gas_in_data', function (Blueprint $table) {
                $table->dropUnique('unique_gasin_reff_id');
            });
        } catch (Exception $e) {
            // Ignore if constraint doesn't exist
        }
    }

    /**
     * Hard delete soft deleted records to avoid constraint conflicts
     */
    private function cleanupSoftDeleted(): void
    {
        $tables = ['sk_data', 'sr_data', 'gas_in_data'];
        
        foreach ($tables as $table) {
            $deleted = DB::table($table)
                ->whereNotNull('deleted_at')
                ->delete();
                
            if ($deleted > 0) {
                echo "  🗑️  Hard deleted {$deleted} soft-deleted records from {$table}\n";
            }
        }
    }

    /**
     * Handle duplicate records for a module table
     */
    private function handleDuplicates(string $tableName, string $moduleName): void
    {
        echo "\n🔍 Checking duplicates in {$tableName}...\n";

        // Find duplicates (now only active records since soft deleted are gone)
        $duplicates = DB::select("
            SELECT reff_id_pelanggan, COUNT(*) as count 
            FROM {$tableName}
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
                ->orderBy('created_at', 'asc')
                ->get();

            if ($records->count() > 1) {
                // Keep the first (oldest) record, delete the rest
                $recordsToDelete = $records->skip(1);
                
                foreach ($recordsToDelete as $record) {
                    DB::table($tableName)
                        ->where('id', $record->id)
                        ->delete();
                    
                    // Also delete related photo_approvals
                    $moduleNameLower = strtolower($moduleName);
                    DB::table('photo_approvals')
                        ->where('reff_id_pelanggan', $reffId)
                        ->where('module_name', $moduleNameLower)
                        ->where('created_at', '>=', $record->created_at)
                        ->delete();

                    $totalCleaned++;
                    echo "  🗑️  Deleted duplicate ID {$record->id} for reff_id: {$reffId}\n";
                }
            }
        }

        echo "✅ Cleaned {$totalCleaned} duplicate records from {$tableName}\n";
    }

    /**
     * Add unique constraints if they don't exist
     */
    private function addUniqueConstraints(): void
    {
        echo "\n🔒 Adding unique constraints...\n";

        $constraints = [
            'sk_data' => 'unique_sk_reff_id',
            'sr_data' => 'unique_sr_reff_id', 
            'gas_in_data' => 'unique_gasin_reff_id'
        ];

        foreach ($constraints as $tableName => $constraintName) {
            if (!$this->hasUniqueConstraint($tableName, $constraintName)) {
                Schema::table($tableName, function (Blueprint $table) use ($constraintName) {
                    $table->unique('reff_id_pelanggan', $constraintName);
                });
                echo "  ✅ Added constraint {$constraintName} to {$tableName}\n";
            } else {
                echo "  ⏭️  Constraint {$constraintName} already exists on {$tableName}\n";
            }
        }
    }

    /**
     * Check if unique constraint already exists
     */
    private function hasUniqueConstraint(string $tableName, string $constraintName): bool
    {
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_NAME = ?
        ", [$tableName, $constraintName]);

        return !empty($constraints);
    }
};