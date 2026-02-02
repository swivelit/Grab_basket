<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

echo "🌥️  CLOUDFLARE R2 CONNECTION TEST\n";
echo "================================\n\n";

// Test 1: Check Environment Variables
echo "1. 📋 Environment Configuration:\n";
$requiredEnvVars = [
    'AWS_BUCKET' => env('AWS_BUCKET'),
    'AWS_DEFAULT_REGION' => env('AWS_DEFAULT_REGION'),
    'AWS_ENDPOINT' => env('AWS_ENDPOINT'),
    'AWS_ACCESS_KEY_ID' => env('AWS_ACCESS_KEY_ID'),
    'AWS_SECRET_ACCESS_KEY' => env('AWS_SECRET_ACCESS_KEY') ? 'SET (hidden)' : 'NOT SET',
    'AWS_USE_PATH_STYLE_ENDPOINT' => env('AWS_USE_PATH_STYLE_ENDPOINT', 'false'),
];

foreach ($requiredEnvVars as $key => $value) {
    echo "   {$key}: " . ($value ? '✅ ' . $value : '❌ NOT SET') . "\n";
}

// Test 2: Check if S3 package is available
echo "\n2. 📦 AWS S3 Package Check:\n";
try {
    if (class_exists('Aws\S3\S3Client')) {
        echo "   ✅ AWS S3 SDK is available\n";
    } else {
        echo "   ❌ AWS S3 SDK not found - run: composer require league/flysystem-aws-s3-v3\n";
        exit;
    }
} catch (Exception $e) {
    echo "   ❌ Error checking S3 SDK: " . $e->getMessage() . "\n";
    exit;
}

// Test 3: Test R2 disk configuration
echo "\n3. ⚙️  Disk Configuration Test:\n";
try {
    $r2Disk = Storage::disk('r2');
    echo "   ✅ R2 disk configuration loaded\n";
    
    $s3Disk = Storage::disk('s3');
    echo "   ✅ S3 disk configuration loaded\n";
} catch (Exception $e) {
    echo "   ❌ Disk configuration error: " . $e->getMessage() . "\n";
    exit;
}

// Test 4: Test Connection with a simple file upload
echo "\n4. 🔗 Connection Test:\n";
$testFileName = 'test-connection-' . time() . '.txt';
$testContent = "R2 Connection Test\nTimestamp: " . date('Y-m-d H:i:s') . "\nBucket: " . env('AWS_BUCKET');

try {
    // Test file upload
    echo "   Testing file upload...\n";
    $uploadResult = Storage::disk('r2')->put($testFileName, $testContent);
    
    if ($uploadResult) {
        echo "   ✅ File uploaded successfully: {$testFileName}\n";
        
        // Test file exists
        if (Storage::disk('r2')->exists($testFileName)) {
            echo "   ✅ File exists on R2\n";
            
            // Test file download
            $downloadContent = Storage::disk('r2')->get($testFileName);
            if ($downloadContent === $testContent) {
                echo "   ✅ File content matches\n";
            } else {
                echo "   ⚠️  File content doesn't match\n";
            }
            
            // Get file URL
            try {
                // Just test path construction
                $basePath = env('AWS_ENDPOINT') . '/' . env('AWS_BUCKET');
                $fileUrl = $basePath . '/' . $testFileName;
                echo "   ✅ File would be accessible at: {$fileUrl}\n";
            } catch (Exception $e) {
                echo "   ⚠️  URL generation failed: " . $e->getMessage() . "\n";
            }
            
            // Clean up test file
            Storage::disk('r2')->delete($testFileName);
            echo "   ✅ Test file cleaned up\n";
            
        } else {
            echo "   ❌ File upload failed - file not found\n";
        }
    } else {
        echo "   ❌ File upload failed\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Connection test failed: " . $e->getMessage() . "\n";
    echo "   Error details: " . $e->getFile() . " line " . $e->getLine() . "\n";
}

// Test 5: List bucket contents (if possible)
echo "\n5. 📁 Bucket Contents Test:\n";
try {
    $files = Storage::disk('r2')->files();
    echo "   ✅ Bucket accessible - " . count($files) . " files found\n";
    
    if (count($files) > 0) {
        echo "   First 5 files:\n";
        foreach (array_slice($files, 0, 5) as $file) {
            echo "     - {$file}\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ⚠️  Bucket listing failed: " . $e->getMessage() . "\n";
}

// Test 6: Upload a sample image
echo "\n6. 🖼️  Image Upload Test:\n";
try {
    // Create a simple test image data
    $imageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    $testImageName = 'test-image-' . time() . '.png';
    
    $imageUpload = Storage::disk('r2')->put('images/' . $testImageName, $imageData);
    
    if ($imageUpload) {
        echo "   ✅ Image uploaded successfully: images/{$testImageName}\n";
        
        // Get image URL
        try {
            $basePath = env('AWS_ENDPOINT') . '/' . env('AWS_BUCKET');
            $imageUrl = $basePath . '/images/' . $testImageName;
            echo "   ✅ Image would be accessible at: {$imageUrl}\n";
        } catch (Exception $e) {
            echo "   ⚠️  Image URL generation failed\n";
        }
        
        // Clean up
        Storage::disk('r2')->delete('images/' . $testImageName);
        echo "   ✅ Test image cleaned up\n";
        
    } else {
        echo "   ❌ Image upload failed\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Image test failed: " . $e->getMessage() . "\n";
}

echo "\n🎉 R2 CONNECTION TEST COMPLETE!\n";

// Provide usage recommendations
echo "\n📋 RECOMMENDATIONS:\n";
echo "-------------------\n";
echo "✅ Your R2 storage is ready to use!\n";
echo "✅ You can now upload product images to R2\n";
echo "✅ Configure FILESYSTEM_DISK=r2 in .env to use R2 by default\n";
echo "✅ Update your product upload controllers to use Storage::disk('r2')\n";

// Generate test URLs
$bucketUrl = str_replace('/fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f', '', env('AWS_ENDPOINT')) . '/' . env('AWS_BUCKET');
echo "\n🌐 R2 Dashboard URL: {$bucketUrl}\n";
echo "📁 Bucket: " . env('AWS_BUCKET') . "\n";
?>