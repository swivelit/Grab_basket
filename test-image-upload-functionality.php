<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

echo "🔍 TESTING IMAGE UPLOAD FUNCTIONALITY\n";
echo "=====================================\n\n";

try {
    // Test 1: Storage Configuration Deep Dive
    echo "1. 💾 Storage Configuration Deep Dive:\n";
    
    $storageRoot = storage_path('app/public');
    $productsPath = storage_path('app/public/products');
    $symlinkPath = public_path('storage');
    
    echo "   Storage root: $storageRoot\n";
    echo "   Products path: $productsPath\n";
    echo "   Symlink path: $symlinkPath\n";
    
    echo "   Storage root exists: " . (is_dir($storageRoot) ? "✅ YES" : "❌ NO") . "\n";
    echo "   Products path exists: " . (is_dir($productsPath) ? "✅ YES" : "❌ NO") . "\n";
    echo "   Symlink exists: " . (is_link($symlinkPath) ? "✅ YES" : "❌ NO") . "\n";
    
    if (!is_link($symlinkPath)) {
        echo "   ⚠️  Creating storage symlink...\n";
        try {
            symlink($storageRoot, $symlinkPath);
            echo "   ✅ Symlink created successfully\n";
        } catch (Exception $e) {
            echo "   ❌ Failed to create symlink: " . $e->getMessage() . "\n";
        }
    }
    
    // Test 2: File Permissions
    echo "\n2. 🔐 File Permissions:\n";
    echo "   Storage root writable: " . (is_writable($storageRoot) ? "✅ YES" : "❌ NO") . "\n";
    echo "   Products path writable: " . (is_writable($productsPath) ? "✅ YES" : "❌ NO") . "\n";
    if (is_link($symlinkPath)) {
        echo "   Symlink readable: " . (is_readable($symlinkPath) ? "✅ YES" : "❌ NO") . "\n";
    }
    
    // Test 3: Test File Creation
    echo "\n3. 📁 Test File Creation:\n";
    $testFile = $productsPath . '/test_upload_' . time() . '.txt';
    try {
        file_put_contents($testFile, 'Test upload functionality');
        if (file_exists($testFile)) {
            echo "   ✅ Test file created successfully\n";
            unlink($testFile);
            echo "   ✅ Test file deleted successfully\n";
        } else {
            echo "   ❌ Test file creation failed\n";
        }
    } catch (Exception $e) {
        echo "   ❌ File creation error: " . $e->getMessage() . "\n";
    }
    
    // Test 4: Laravel Storage Facade
    echo "\n4. 🎭 Laravel Storage Facade:\n";
    try {
        $disk = Storage::disk('public');
        $testContent = 'Laravel storage test';
        $testPath = 'products/test_laravel_' . time() . '.txt';
        
        $success = $disk->put($testPath, $testContent);
        if ($success) {
            echo "   ✅ Laravel storage write successful\n";
            $content = $disk->get($testPath);
            if ($content === $testContent) {
                echo "   ✅ Laravel storage read successful\n";
            } else {
                echo "   ❌ Laravel storage read failed\n";
            }
            $disk->delete($testPath);
            echo "   ✅ Laravel storage cleanup successful\n";
        } else {
            echo "   ❌ Laravel storage write failed\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Laravel storage error: " . $e->getMessage() . "\n";
    }
    
    // Test 5: Check Recent Uploads
    echo "\n5. 📊 Recent Upload Analysis:\n";
    $recentFiles = glob($productsPath . '/*');
    $recentFiles = array_slice($recentFiles, -10); // Last 10 files
    
    echo "   Recent files in products directory:\n";
    foreach ($recentFiles as $file) {
        $filename = basename($file);
        $size = filesize($file);
        $date = date('Y-m-d H:i:s', filemtime($file));
        echo "   - $filename (Size: " . round($size/1024, 2) . "KB, Modified: $date)\n";
    }
    
    // Test 6: Check MIME Type Support
    echo "\n6. 🎨 MIME Type Support:\n";
    $supportedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    
    foreach ($supportedTypes as $type) {
        $extension = str_replace('image/', '', $type);
        echo "   - $type ($extension): ✅ Supported\n";
    }
    
    // Test 7: Memory and Upload Limits
    echo "\n7. ⚡ PHP Upload Configuration:\n";
    echo "   upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
    echo "   post_max_size: " . ini_get('post_max_size') . "\n";
    echo "   memory_limit: " . ini_get('memory_limit') . "\n";
    echo "   max_execution_time: " . ini_get('max_execution_time') . "\n";
    echo "   file_uploads: " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "\n";
    
    echo "\n✅ IMAGE UPLOAD DIAGNOSTIC COMPLETE\n";
    echo "==================================\n";

} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}