<?php
/**
 * Debug script for GasIn submission - place in public/ directory
 * Access via: http://yourdomain.com/debug_gasin_submit.php
 */

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Jakarta');

echo "<h2>GasIn Submit Debug</h2>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

// Display PHP config
echo "<h3>PHP Upload Configuration:</h3>";
echo "<ul>";
echo "<li>upload_max_filesize: " . ini_get('upload_max_filesize') . "</li>";
echo "<li>post_max_size: " . ini_get('post_max_size') . "</li>";
echo "<li>max_execution_time: " . ini_get('max_execution_time') . "</li>";
echo "<li>memory_limit: " . ini_get('memory_limit') . "</li>";
echo "</ul>";

// Test 1: Test basic POST submission (like GasIn store)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_type'])) {
    
    echo "<h3>POST Test Results:</h3>";
    
    if ($_POST['test_type'] === 'basic') {
        // Test basic form submission
        echo "<pre>";
        echo "Test Type: Basic Form Submission\n";
        echo "POST data received:\n";
        print_r($_POST);
        echo "\nPOST size: " . strlen(http_build_query($_POST)) . " bytes\n";
        echo "✓ Basic POST submission successful\n";
        echo "</pre>";
    }
    
    if ($_POST['test_type'] === 'file_upload' && isset($_FILES['test_file'])) {
        // Test file upload
        echo "<pre>";
        echo "Test Type: File Upload\n";
        echo "File info:\n";
        print_r($_FILES['test_file']);
        
        $file = $_FILES['test_file'];
        echo "\nFile analysis:\n";
        echo "- Upload error code: " . $file['error'] . "\n";
        echo "- File size: " . number_format($file['size']) . " bytes (" . round($file['size']/1024/1024, 2) . " MB)\n";
        echo "- File type: " . $file['type'] . "\n";
        echo "- Temp file exists: " . (file_exists($file['tmp_name']) ? 'Yes' : 'No') . "\n";
        
        // Error codes
        $errors = [
            UPLOAD_ERR_OK => 'No error',
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'No temporary directory',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];
        
        echo "- Error meaning: " . ($errors[$file['error']] ?? 'Unknown') . "\n";
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            echo "✓ File upload successful!\n";
            
            // Test file type validation (like Laravel)
            $mimeType = $file['type'];
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            $isValidMime = in_array($mimeType, $allowedMimes);
            echo "- MIME validation: " . ($isValidMime ? 'PASS' : 'FAIL') . "\n";
            
            // Test file size validation (like Laravel max:35840)
            $maxSizeBytes = 35 * 1024 * 1024; // 35MB
            $isValidSize = $file['size'] <= $maxSizeBytes;
            echo "- Size validation (max 35MB): " . ($isValidSize ? 'PASS' : 'FAIL') . "\n";
            
            if (!$isValidMime) {
                echo "✗ MIME type '{$mimeType}' not allowed\n";
            }
            if (!$isValidSize) {
                echo "✗ File size " . round($file['size']/1024/1024, 2) . "MB exceeds 35MB limit\n";
            }
            
        } else {
            echo "✗ File upload failed!\n";
        }
        echo "</pre>";
    }
    
    if ($_POST['test_type'] === 'simulate_gasin') {
        // Simulate GasIn submission
        echo "<pre>";
        echo "Test Type: Simulate GasIn Submission\n";
        
        // Required fields check
        $requiredFields = ['reff_id_pelanggan', 'tanggal_gas_in'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            echo "✗ Missing required fields: " . implode(', ', $missingFields) . "\n";
            echo "This would cause 422 validation error\n";
        } else {
            echo "✓ All required fields present\n";
            echo "- reff_id_pelanggan: " . $_POST['reff_id_pelanggan'] . "\n";
            echo "- tanggal_gas_in: " . $_POST['tanggal_gas_in'] . "\n";
            
            // Check if reff_id is reasonable format
            $reffId = trim($_POST['reff_id_pelanggan']);
            if (strlen($reffId) < 3) {
                echo "⚠ Warning: reff_id_pelanggan seems too short\n";
            }
            
            // Check date format
            $tanggal = $_POST['tanggal_gas_in'];
            $dateValid = date('Y-m-d', strtotime($tanggal)) === $tanggal;
            echo "- Date format valid: " . ($dateValid ? 'Yes' : 'No') . "\n";
        }
        
        echo "</pre>";
    }
}

?>

<!-- Test Forms -->
<h3>Test Forms:</h3>

<!-- Test 1: Basic POST -->
<div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0;">
    <h4>Test 1: Basic Form Submission</h4>
    <form method="POST">
        <input type="hidden" name="test_type" value="basic">
        <input type="text" name="sample_field" value="test data" readonly>
        <button type="submit">Test Basic POST</button>
    </form>
</div>

<!-- Test 2: File Upload -->
<div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0;">
    <h4>Test 2: File Upload (Max 35MB)</h4>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="MAX_FILE_SIZE" value="36700160">
        <input type="hidden" name="test_type" value="file_upload">
        <input type="file" name="test_file" accept="image/*,application/pdf">
        <button type="submit">Test File Upload</button>
    </form>
</div>

<!-- Test 3: Simulate GasIn Submission -->
<div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0;">
    <h4>Test 3: Simulate GasIn Form</h4>
    <form method="POST">
        <input type="hidden" name="test_type" value="simulate_gasin">
        <label>Reff ID Pelanggan: <input type="text" name="reff_id_pelanggan" value="TEST123"></label><br><br>
        <label>Tanggal Gas In: <input type="date" name="tanggal_gas_in" value="<?= date('Y-m-d') ?>"></label><br><br>
        <label>Notes: <textarea name="notes">Test notes</textarea></label><br><br>
        <button type="submit">Test GasIn Data</button>
    </form>
</div>

<!-- Test 4: Combined Upload -->
<div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; background-color: #f0f8ff;">
    <h4>Test 4: Combined GasIn + File Upload</h4>
    <p><strong>This simulates the exact GasIn submission process</strong></p>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="MAX_FILE_SIZE" value="36700160">
        <input type="hidden" name="test_type" value="file_upload">
        
        <label>Reff ID: <input type="text" name="reff_id_pelanggan" value="TEST456"></label><br><br>
        <label>Tanggal: <input type="date" name="tanggal_gas_in" value="<?= date('Y-m-d') ?>"></label><br><br>
        <label>PDF/Image File: <input type="file" name="test_file" accept="image/*,application/pdf"></label><br><br>
        <button type="submit">Test Complete Upload</button>
    </form>
</div>

<h3>Instructions:</h3>
<ol>
    <li><strong>Test 1</strong>: Checks basic form submission</li>
    <li><strong>Test 2</strong>: Upload file 25-30MB and check results</li>
    <li><strong>Test 3</strong>: Test form validation like GasIn</li>
    <li><strong>Test 4</strong>: Combined test with file + form data</li>
</ol>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f5f5f5; padding: 10px; border-left: 4px solid #007cba; overflow-x: auto; }
form { background: #fafafa; padding: 10px; }
label { display: inline-block; margin: 5px 0; }
input, textarea, button { margin: 5px; padding: 5px; }
button { background: #007cba; color: white; border: none; padding: 8px 15px; cursor: pointer; }
</style>