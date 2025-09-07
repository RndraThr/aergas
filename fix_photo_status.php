<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== 🔧 FIX PHOTO STATUS INCONSISTENCY ===\n\n";

echo "Input reff_id to fix (contoh: 416009): ";
$handle = fopen("php://stdin", "r");
$reffId = trim(fgets($handle));
fclose($handle);

if (empty($reffId)) {
    echo "❌ Reff ID tidak boleh kosong\n";
    exit(1);
}

echo "\n--- Fixing photo status for reff_id: {$reffId} ---\n\n";

// Get all photos for this reff_id
$photos = DB::table('photo_approvals')
    ->where('reff_id_pelanggan', $reffId)
    ->get();

if ($photos->isEmpty()) {
    echo "❌ No photos found for reff_id: {$reffId}\n";
    exit(1);
}

$totalFixed = 0;

foreach ($photos as $photo) {
    echo "📸 {$photo->module_name} - {$photo->photo_field_name}:\n";
    echo "  Current Status: {$photo->photo_status}\n";
    echo "  Tracer Approved: " . ($photo->tracer_approved_at ? 'YES' : 'NO') . "\n";
    echo "  CGP Approved: " . ($photo->cgp_approved_at ? 'YES' : 'NO') . "\n";
    
    // Determine correct status based on approval timestamps
    $correctStatus = 'draft';
    
    if ($photo->cgp_approved_at) {
        $correctStatus = 'cgp_approved';
    } elseif ($photo->tracer_approved_at) {
        $correctStatus = 'cgp_pending';  // Should be cgp_pending if tracer approved
    } else {
        $correctStatus = 'draft';
    }
    
    echo "  Should be Status: {$correctStatus}\n";
    
    // Check if needs fixing
    if ($photo->photo_status !== $correctStatus) {
        echo "  🔧 FIXING...\n";
        
        DB::table('photo_approvals')
            ->where('id', $photo->id)
            ->update(['photo_status' => $correctStatus]);
            
        echo "  ✅ FIXED: {$photo->photo_status} → {$correctStatus}\n";
        $totalFixed++;
    } else {
        echo "  ✅ Already correct\n";
    }
    
    echo "\n";
}

echo "=== ✅ FIX COMPLETED ===\n";
echo "Total photos fixed: {$totalFixed}\n";
echo "\n🚀 Now check CGP Review page - buttons should appear!\n";