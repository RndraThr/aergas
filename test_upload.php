<?php
/**
 * Simple upload test script
 * Place this in public/ directory and access via browser
 * URL: http://yourdomain.com/test_upload.php
 */

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>PHP Upload Test</h2>";
echo "<p>Date: " . date('Y-m-d H:i:s') . "</p>";

// Display current PHP configuration
echo "<h3>Current PHP Configuration:</h3>";
echo "<ul>";
echo "<li><strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "</li>";
echo "<li><strong>post_max_size:</strong> " . ini_get('post_max_size') . "</li>";
echo "<li><strong>max_execution_time:</strong> " . ini_get('max_execution_time') . "</li>";
echo "<li><strong>memory_limit:</strong> " . ini_get('memory_limit') . "</li>";
echo "<li><strong>max_input_time:</strong> " . ini_get('max_input_time') . "</li>";
echo "</ul>";

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h3>Upload Results:</h3>";
    
    $file = $_FILES['test_file'];
    
    echo "<pre>";
    echo "File info:\n";
    print_r($file);
    echo "\n";
    
    echo "POST data size: " . strlen(http_build_query($_POST)) . " bytes\n";
    echo "Upload error code: " . $file['error'] . "\n";
    
    // Decode error codes
    $errors = [
        UPLOAD_ERR_OK => 'No error',
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE from form',
        UPLOAD_ERR_PARTIAL => 'File partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'No temporary directory',
        UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
        UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
    ];
    
    echo "Error meaning: " . ($errors[$file['error']] ?? 'Unknown error') . "\n";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        echo "✓ Upload successful!\n";
        echo "File size: " . number_format($file['size']) . " bytes (" . round($file['size']/1024/1024, 2) . " MB)\n";
        echo "Temporary file: " . $file['tmp_name'] . "\n";
        echo "File exists: " . (file_exists($file['tmp_name']) ? 'Yes' : 'No') . "\n";
        
        if (file_exists($file['tmp_name'])) {
            echo "Actual file size: " . number_format(filesize($file['tmp_name'])) . " bytes\n";
        }
    } else {
        echo "✗ Upload failed!\n";
    }
    
    echo "</pre>";
}

?>

<h3>Upload Test Form:</h3>
<form method="POST" enctype="multipart/form-data" style="border: 1px solid #ccc; padding: 20px; margin: 20px 0;">
    <input type="hidden" name="MAX_FILE_SIZE" value="36700160"> <!-- 35MB -->
    <p>
        <label for="test_file">Choose file to upload (max 35MB):</label><br>
        <input type="file" name="test_file" id="test_file" accept="image/*,application/pdf">
    </p>
    <p>
        <input type="submit" value="Upload Test File" style="padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer;">
    </p>
</form>

<h3>Instructions:</h3>
<ol>
    <li>Select a file larger than 20MB but smaller than 35MB</li>
    <li>Click "Upload Test File"</li>
    <li>Check the results above</li>
    <li>If successful, the issue is in Laravel application</li>
    <li>If failed, the issue is in server configuration</li>
</ol>

<p><strong>Note:</strong> Delete this file after testing for security reasons.</p>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f5f5f5; padding: 10px; border-left: 4px solid #007cba; }
ul li { margin: 5px 0; }
</style>