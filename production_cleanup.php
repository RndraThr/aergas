<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== 🧹 PRODUCTION SAFE CLEANUP ===\n\n";

// Ask for confirmation
echo "⚠️  WARNING: This will clean up duplicate entries in production!\n";
echo "📋 This script will:\n";
echo "   1. Hard delete soft-deleted records\n";
echo "   2. Remove newer duplicate entries (keep oldest)\n";
echo "   3. Clean related photo_approvals\n\n";

echo "Continue? (yes/no): ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "❌ Operation cancelled\n";
    exit(1);
}

$tables = [
    'sk_data' => 'sk',
    'sr_data' => 'sr', 
    'gas_in_data' => 'gas_in'
];

$totalCleaned = 0;

foreach ($tables as $table => $moduleNameLower) {
    echo "\n--- Processing {$table} ---\n";
    
    // Step 1: Hard delete soft deleted records
    echo "🗑️  Hard deleting soft deleted records...\n";
    $softDeleted = DB::table($table)
        ->whereNotNull('deleted_at')
        ->delete();
    
    if ($softDeleted > 0) {
        echo "  ✅ Deleted {$softDeleted} soft-deleted records\n";
    }
    
    // Step 2: Find remaining duplicates
    $duplicates = DB::select("
        SELECT reff_id_pelanggan, COUNT(*) as count 
        FROM {$table}
        GROUP BY reff_id_pelanggan 
        HAVING count > 1
    ");
    
    if (empty($duplicates)) {
        echo "  ✅ No duplicates found\n";
        continue;
    }
    
    echo "  ⚠️  Found " . count($duplicates) . " duplicate groups\n";
    
    // Step 3: Clean duplicates
    foreach ($duplicates as $duplicate) {
        $reffId = $duplicate->reff_id_pelanggan;
        echo "    📋 Cleaning reff_id: {$reffId}\n";
        
        // Get all records ordered by creation time (keep oldest)
        $records = DB::table($table)
            ->where('reff_id_pelanggan', $reffId)
            ->orderBy('created_at', 'asc')
            ->get();
        
        if ($records->count() > 1) {
            // Keep first, delete rest
            $recordsToDelete = $records->skip(1);
            
            foreach ($recordsToDelete as $record) {
                // Delete the record
                DB::table($table)
                    ->where('id', $record->id)
                    ->delete();
                
                // Delete related photo approvals
                $photosDeleted = DB::table('photo_approvals')
                    ->where('reff_id_pelanggan', $reffId)
                    ->where('module_name', $moduleNameLower)
                    ->where('created_at', '>=', $record->created_at)
                    ->delete();
                
                echo "      🗑️  Deleted record ID {$record->id}\n";
                if ($photosDeleted > 0) {
                    echo "      📸 Deleted {$photosDeleted} related photos\n";
                }
                
                $totalCleaned++;
            }
        }
    }
}

echo "\n=== ✅ CLEANUP COMPLETED ===\n";
echo "Total records cleaned: {$totalCleaned}\n";
echo "\n🚀 Now you can safely run: php artisan migrate\n";