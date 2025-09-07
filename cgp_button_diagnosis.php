<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== 🔍 CGP BUTTON DIAGNOSIS ===\n\n";

echo "Input reff_id yang tidak muncul CGP button (contoh: 416009): ";
$handle = fopen("php://stdin", "r");
$reffId = trim(fgets($handle));
fclose($handle);

if (empty($reffId)) {
    echo "❌ Reff ID tidak boleh kosong\n";
    exit(1);
}

echo "\n--- Checking reff_id: {$reffId} ---\n\n";

// Check all modules for this reff_id
$modules = [
    'sk_data' => 'SK',
    'sr_data' => 'SR', 
    'gas_in_data' => 'Gas In'
];

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
    echo "  Status: {$record->status}\n";
    echo "  Module Status: " . ($record->module_status ?? 'N/A') . "\n";
    echo "  Tracer Approved: " . ($record->tracer_approved_at ? '✅ ' . $record->tracer_approved_at : '❌ Not approved') . "\n";
    echo "  CGP Approved: " . ($record->cgp_approved_at ? '✅ ' . $record->cgp_approved_at : '❌ Not approved') . "\n";
    
    // Check if can approve CGP (based on model logic)
    $canApproveCgp = ($record->status === 'tracer_approved');
    echo "  Can Approve CGP: " . ($canApproveCgp ? '✅ YES' : '❌ NO') . "\n";
    
    if (!$canApproveCgp) {
        echo "  ⚠️  Reason: Status must be 'tracer_approved', currently '{$record->status}'\n";
    }
    echo "\n";
}

// Check photo approvals
echo "--- Photo Approvals for {$reffId} ---\n";
$photos = DB::table('photo_approvals')
    ->where('reff_id_pelanggan', $reffId)
    ->get();
    
if ($photos->isEmpty()) {
    echo "⚪ No photo approvals found\n";
} else {
    foreach ($photos as $photo) {
        echo "📸 {$photo->module_name} - {$photo->photo_field_name}:\n";
        echo "  Status: {$photo->photo_status}\n";
        echo "  Tracer Approved: " . ($photo->tracer_approved_at ? '✅ ' . $photo->tracer_approved_at : '❌') . "\n";
        echo "  CGP Approved: " . ($photo->cgp_approved_at ? '✅ ' . $photo->cgp_approved_at : '❌') . "\n";
        echo "\n";
    }
}

echo "=== 🎯 DIAGNOSIS SUMMARY ===\n";
echo "For CGP buttons to appear, modules must have:\n";
echo "1. Status = 'tracer_approved'\n"; 
echo "2. tracer_approved_at not null\n";
echo "3. cgp_approved_at is null\n";
echo "\n=== END DIAGNOSIS ===\n";