<?php
/**
 * Step-by-step GasIn test - place in public/
 * Access via: http://yourdomain.com/test_gasin_steps.php
 */

// Include Laravel bootstrap to access models and routes
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CalonPelanggan;
use App\Models\GasInData;
use Illuminate\Http\Request;

echo "<h2>GasIn Step-by-Step Test</h2>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

// Test 1: Check if sample customer exists
echo "<h3>Test 1: Customer Data Check</h3>";
try {
    $customers = CalonPelanggan::take(3)->get(['reff_id_pelanggan', 'nama_pelanggan']);
    if ($customers->count() > 0) {
        echo "<pre>";
        echo "✓ Found " . $customers->count() . " customers:\n";
        foreach ($customers as $customer) {
            echo "  - {$customer->reff_id_pelanggan}: {$customer->nama_pelanggan}\n";
        }
        echo "</pre>";
        
        $testReffId = $customers->first()->reff_id_pelanggan;
        echo "<p><strong>Using test reff_id: {$testReffId}</strong></p>";
    } else {
        echo "<p>✗ No customers found in database</p>";
        $testReffId = 'TEST123'; // fallback
    }
} catch (Exception $e) {
    echo "<p>✗ Database error: " . $e->getMessage() . "</p>";
    $testReffId = 'TEST123';
}

// Test 2: Check GasIn validation rules
echo "<h3>Test 2: Validation Rules Test</h3>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_step'])) {
    
    if ($_POST['test_step'] === 'validate_fields') {
        echo "<pre>";
        echo "Testing field validation...\n";
        
        $data = [
            'reff_id_pelanggan' => $_POST['reff_id_pelanggan'] ?? '',
            'tanggal_gas_in' => $_POST['tanggal_gas_in'] ?? '',
            'notes' => $_POST['notes'] ?? ''
        ];
        
        echo "Data to validate:\n";
        print_r($data);
        
        // Manual validation check
        $errors = [];
        
        if (empty($data['reff_id_pelanggan'])) {
            $errors[] = 'reff_id_pelanggan is required';
        } elseif (strlen($data['reff_id_pelanggan']) > 50) {
            $errors[] = 'reff_id_pelanggan max 50 chars';
        }
        
        if (empty($data['tanggal_gas_in'])) {
            $errors[] = 'tanggal_gas_in is required';
        } elseif (!strtotime($data['tanggal_gas_in'])) {
            $errors[] = 'tanggal_gas_in invalid date format';
        }
        
        // Check if reff_id exists in customers table
        try {
            $customerExists = CalonPelanggan::where('reff_id_pelanggan', $data['reff_id_pelanggan'])->exists();
            if (!$customerExists) {
                $errors[] = 'reff_id_pelanggan does not exist in customers table';
            }
            
            // Check if GasIn already exists for this reff_id
            $gasInExists = GasInData::where('reff_id_pelanggan', $data['reff_id_pelanggan'])->exists();
            if ($gasInExists) {
                $errors[] = 'GasIn already exists for this reff_id (unique constraint)';
            }
            
        } catch (Exception $e) {
            $errors[] = 'Database check failed: ' . $e->getMessage();
        }
        
        if (empty($errors)) {
            echo "✓ All validations passed!\n";
            echo "This data should be accepted by Laravel\n";
        } else {
            echo "✗ Validation errors found:\n";
            foreach ($errors as $error) {
                echo "  - {$error}\n";
            }
            echo "\nThese errors would cause 422 response\n";
        }
        
        echo "</pre>";
    }
    
    if ($_POST['test_step'] === 'check_existing_gasin') {
        echo "<pre>";
        echo "Checking existing GasIn records...\n";
        
        try {
            $existingCount = GasInData::count();
            echo "Total GasIn records: {$existingCount}\n";
            
            if (isset($_POST['reff_id_pelanggan'])) {
                $reffId = $_POST['reff_id_pelanggan'];
                $existing = GasInData::where('reff_id_pelanggan', $reffId)->first();
                
                if ($existing) {
                    echo "✗ GasIn already exists for {$reffId}:\n";
                    echo "  ID: {$existing->id}\n";
                    echo "  Status: {$existing->status}\n";
                    echo "  Created: {$existing->created_at}\n";
                    echo "\nThis would cause 422 'duplikat' error\n";
                } else {
                    echo "✓ No existing GasIn found for {$reffId}\n";
                    echo "This reff_id can be used for new GasIn\n";
                }
            }
            
        } catch (Exception $e) {
            echo "✗ Database error: " . $e->getMessage() . "\n";
        }
        
        echo "</pre>";
    }
}

?>

<!-- Test Forms -->
<h3>Test Forms:</h3>

<!-- Test Form 1: Field Validation -->
<div style="border: 2px solid #007cba; padding: 15px; margin: 15px 0;">
    <h4>Step 1: Test Field Validation</h4>
    <form method="POST">
        <input type="hidden" name="test_step" value="validate_fields">
        
        <label>Reff ID Pelanggan: 
            <input type="text" name="reff_id_pelanggan" value="<?= $testReffId ?? 'TEST123' ?>" style="width: 200px;">
        </label><br><br>
        
        <label>Tanggal Gas In: 
            <input type="date" name="tanggal_gas_in" value="<?= date('Y-m-d') ?>">
        </label><br><br>
        
        <label>Notes: 
            <textarea name="notes">Test notes from debug</textarea>
        </label><br><br>
        
        <button type="submit">Test Field Validation</button>
    </form>
</div>

<!-- Test Form 2: Check Existing -->
<div style="border: 2px solid #28a745; padding: 15px; margin: 15px 0;">
    <h4>Step 2: Check Duplicate GasIn</h4>
    <form method="POST">
        <input type="hidden" name="test_step" value="check_existing_gasin">
        
        <label>Reff ID to Check: 
            <input type="text" name="reff_id_pelanggan" value="<?= $testReffId ?? 'TEST123' ?>" style="width: 200px;">
        </label><br><br>
        
        <button type="submit">Check Existing GasIn</button>
    </form>
</div>

<h3>Analysis:</h3>
<ul>
    <li><strong>Step 1</strong>: Tests the same validation rules as GasInController::store()</li>
    <li><strong>Step 2</strong>: Checks for duplicate GasIn (common cause of 422)</li>
    <li><strong>Common 422 causes</strong>:
        <ul>
            <li>Customer reff_id doesn't exist</li>
            <li>GasIn already exists for this reff_id</li>
            <li>Invalid date format</li>
            <li>Field length exceeded</li>
        </ul>
    </li>
</ul>

<p><strong>Next:</strong> If both steps pass, the issue is likely in file upload phase.</p>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f8f9fa; padding: 15px; border-left: 4px solid #007cba; overflow-x: auto; }
form { background: #f8f9fa; padding: 15px; border-radius: 5px; }
label { display: block; margin: 8px 0; font-weight: bold; }
input, textarea, button { margin: 5px 0; padding: 8px; }
button { background: #007cba; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; }
button:hover { background: #0056b3; }
</style>