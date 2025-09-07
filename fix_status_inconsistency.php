<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== 🔧 FIX STATUS INCONSISTENCY ===\n\n";

echo "Input reff_id to fix (contoh: 416009): ";
$handle = fopen("php://stdin", "r");
$reffId = trim(fgets($handle));
fclose($handle);

if (empty($reffId)) {
    echo "❌ Reff ID tidak boleh kosong\n";
    exit(1);
}

echo "\n--- Fixing reff_id: {$reffId} ---\n\n";

$modules = [
    'sk_data' => 'SK',
    'sr_data' => 'SR', 
    'gas_in_data' => 'Gas In'
];

$totalFixed = 0;

foreach ($modules as $table => $moduleName) {
    $record = DB::table($table)
        ->where('reff_id_pelanggan', $reffId)
        ->whereNull('deleted_at')
        ->first();
        
    if (!$record) {
        echo "⚪ {$moduleName}: No record found\n";
        continue;
    }
    
    echo "📋 {$moduleName} (ID: {$record->id}):\n";
    echo "  Current Status: {$record->status}\n";
    echo "  Current Module Status: " . ($record->module_status ?? 'N/A') . "\n";
    echo "  Tracer Approved: " . ($record->tracer_approved_at ? 'YES' : 'NO') . "\n";
    echo "  CGP Approved: " . ($record->cgp_approved_at ? 'YES' : 'NO') . "\n";
    
    // Determine correct status based on approval timestamps
    $correctStatus = 'draft';
    $correctModuleStatus = 'draft';
    
    if ($record->cgp_approved_at) {
        $correctStatus = 'cgp_approved';
        $correctModuleStatus = 'completed';
    } elseif ($record->tracer_approved_at) {
        $correctStatus = 'tracer_approved';
        $correctModuleStatus = 'cgp_review';
    }
    
    echo "  Should be Status: {$correctStatus}\n";
    echo "  Should be Module Status: {$correctModuleStatus}\n";
    
    // Check if needs fixing
    $needsStatusFix = ($record->status !== $correctStatus);
    $needsModuleStatusFix = ($record->module_status !== $correctModuleStatus);
    
    if ($needsStatusFix || $needsModuleStatusFix) {
        echo "  🔧 FIXING...\n";
        
        $updates = [];
        if ($needsStatusFix) {
            $updates['status'] = $correctStatus;
        }
        if ($needsModuleStatusFix) {
            $updates['module_status'] = $correctModuleStatus;
        }
        
        DB::table($table)
            ->where('id', $record->id)
            ->update($updates);
            
        echo "  ✅ FIXED: " . implode(', ', array_keys($updates)) . "\n";
        $totalFixed++;
    } else {
        echo "  ✅ Already correct\n";
    }
    
    echo "\n";
}

echo "=== ✅ FIX COMPLETED ===\n";
echo "Total modules fixed: {$totalFixed}\n";
echo "\n🚀 Now check CGP Review page - buttons should appear!\n";