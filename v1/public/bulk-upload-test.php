<?php
// Test script for debugging bulk upload issues
require_once __DIR__ . '/../vendor/autoload.php';

// Test 1: Check PHP Configuration
echo "<h2>1. PHP Configuration Check</h2>";
$config_tests = [
    'upload_max_filesize' => '50M',
    'post_max_size' => '50M', 
    'max_execution_time' => 300,
    'memory_limit' => '512M',
    'max_file_uploads' => 100
];

foreach ($config_tests as $setting => $required) {
    $current = ini_get($setting);
    echo "$setting: $current (Required: $required)<br>";
}

// Test 2: Check Extensions
echo "<h2>2. Required Extensions</h2>";
$required_extensions = ['zip', 'gd', 'fileinfo'];
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '✓ Loaded' : '✗ Missing';
    echo "$ext: $status<br>";
}

// Test 3: Memory Test
echo "<h2>3. Memory Test</h2>";
echo "Current memory usage: " . number_format(memory_get_usage() / 1024 / 1024, 2) . " MB<br>";
echo "Peak memory usage: " . number_format(memory_get_peak_usage() / 1024 / 1024, 2) . " MB<br>";

// Test 4: Directory Permissions
echo "<h2>4. Directory Permissions</h2>";
$dirs = [
    '../storage/app' => storage_path('app'),
    '../storage/app/public' => storage_path('app/public'),
    '../storage/logs' => storage_path('logs'),
    '../public/images' => public_path('images')
];

foreach ($dirs as $name => $path) {
    if (is_dir($path)) {
        $writable = is_writable($path) ? '✓ Writable' : '✗ Not Writable';
        echo "$name: $writable<br>";
    } else {
        echo "$name: ✗ Does not exist<br>";
    }
}

// Test 5: ZIP File Test
echo "<h2>5. ZIP File Processing Test</h2>";
if (class_exists('ZipArchive')) {
    echo "ZipArchive class: ✓ Available<br>";
    
    // Create a test ZIP
    $zip = new ZipArchive();
    $testZipPath = '../storage/app/test.zip';
    
    if ($zip->open($testZipPath, ZipArchive::CREATE) === TRUE) {
        $zip->addFromString('test.txt', 'This is a test file');
        $zip->close();
        echo "Test ZIP creation: ✓ Success<br>";
        
        // Test reading
        if ($zip->open($testZipPath) === TRUE) {
            echo "Test ZIP reading: ✓ Success<br>";
            echo "Files in ZIP: " . $zip->numFiles . "<br>";
            $zip->close();
        } else {
            echo "Test ZIP reading: ✗ Failed<br>";
        }
        
        // Clean up
        if (file_exists($testZipPath)) {
            unlink($testZipPath);
        }
    } else {
        echo "Test ZIP creation: ✗ Failed<br>";
    }
} else {
    echo "ZipArchive class: ✗ Not Available<br>";
}

// Test 6: Image Processing Test
echo "<h2>6. Image Processing Test</h2>";
$testImageData = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=');

$tempImage = tempnam(sys_get_temp_dir(), 'test_');
file_put_contents($tempImage, $testImageData);

$imageInfo = getimagesize($tempImage);
if ($imageInfo) {
    echo "Image processing: ✓ Working<br>";
    echo "Test image dimensions: {$imageInfo[0]}x{$imageInfo[1]}<br>";
} else {
    echo "Image processing: ✗ Failed<br>";
}

unlink($tempImage);

// Test 7: Server Information
echo "<h2>7. Server Information</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "Operating System: " . PHP_OS . "<br>";
echo "Current Time: " . date('Y-m-d H:i:s') . "<br>";

// Test 8: Error Log Check
echo "<h2>8. Recent Error Logs</h2>";
$logPath = '../storage/logs/laravel.log';
if (file_exists($logPath)) {
    $logs = file_get_contents($logPath);
    $recent_logs = array_slice(explode("\n", $logs), -20); // Last 20 lines
    echo "<pre style='background: #f5f5f5; padding: 10px; font-size: 12px;'>";
    echo "Last 20 lines from laravel.log:\n";
    echo implode("\n", $recent_logs);
    echo "</pre>";
} else {
    echo "Laravel log file not found<br>";
}

echo "<h2>Summary</h2>";
echo "<p>Check all the items above. Any issues marked with ✗ need to be fixed for bulk upload to work properly.</p>";
echo "<p>If everything looks good but you're still getting 502 errors, the issue might be:</p>";
echo "<ul>";
echo "<li>Web server (Apache/Nginx) timeout settings</li>";
echo "<li>Reverse proxy timeout (if using one)</li>";
echo "<li>File upload too large for server to handle</li>";
echo "<li>Server resource limits (CPU, disk space)</li>";
echo "</ul>";
?>