<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== 🔍 PRODUCTION DIAGNOSIS - Duplicate Detection ===\n\n";

$tables = [
    'sk_data' => 'SK',
    'sr_data' => 'SR', 
    'gas_in_data' => 'Gas In'
];

$totalDuplicates = 0;

foreach ($tables as $table => $moduleName) {
    echo "--- {$moduleName} Table ({$table}) ---\n";
    
    // Check all records (including soft deleted)
    $allDuplicates = DB::select("
        SELECT reff_id_pelanggan, COUNT(*) as count 
        FROM {$table} 
        GROUP BY reff_id_pelanggan 
        HAVING count > 1
    ");
    
    // Check only active records
    $activeDuplicates = DB::select("
        SELECT reff_id_pelanggan, COUNT(*) as count 
        FROM {$table} 
        WHERE deleted_at IS NULL 
        GROUP BY reff_id_pelanggan 
        HAVING count > 1
    ");
    
    echo "  📊 All records duplicates: " . count($allDuplicates) . "\n";
    echo "  📊 Active records duplicates: " . count($activeDuplicates) . "\n";
    
    if (!empty($allDuplicates)) {
        echo "  ⚠️  DUPLICATE GROUPS FOUND:\n";
        
        foreach ($allDuplicates as $duplicate) {
            $reffId = $duplicate->reff_id_pelanggan;
            echo "\n    📋 reff_id: {$reffId} ({$duplicate->count} total records)\n";
            
            $records = DB::select("
                SELECT id, reff_id_pelanggan, created_at, deleted_at, status
                FROM {$table} 
                WHERE reff_id_pelanggan = ? 
                ORDER BY created_at ASC
            ", [$reffId]);
            
            foreach ($records as $record) {
                $deletedStatus = $record->deleted_at ? '🗑️ SOFT DELETED' : '✅ ACTIVE';
                $status = $record->status ?? 'N/A';
                echo "      - ID: {$record->id}, Status: {$status}, Created: {$record->created_at}, State: {$deletedStatus}\n";
            }
            $totalDuplicates++;
        }
    } else {
        echo "  ✅ No duplicates found\n";
    }
    echo "\n";
}

echo "=== 📋 SUMMARY ===\n";
echo "Total duplicate groups found: {$totalDuplicates}\n";
echo "\n=== 🛠️  RECOMMENDED ACTION ===\n";

if ($totalDuplicates > 0) {
    echo "1. Run: php production_cleanup.php\n";
    echo "2. Then: php artisan migrate\n";
} else {
    echo "✅ No action needed - ready to migrate!\n";
}

echo "\n=== END DIAGNOSIS ===\n";