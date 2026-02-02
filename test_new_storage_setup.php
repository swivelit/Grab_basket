<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

echo "\n=== TESTING NEW STORAGE SETUP ===\n\n";

// Create a test file
$testContent = 'Test image content - ' . date('Y-m-d H:i:s');
$tempFile = tmpfile();
fwrite($tempFile, $testContent);
$tempPath = stream_get_meta_data($tempFile)['uri'];

$uploadedFile = new UploadedFile(
    $tempPath,
    'test-image.jpg',
    'image/jpeg',
    null,
    true
);

$folder = 'products/seller-2';
$filename = 'test-storage-' . time() . '.jpg';

echo "Test Configuration:\n";
echo "  Folder: $folder\n";
echo "  Filename: $filename\n";
echo "  Expected path: $folder/$filename\n\n";

// Test folder creation
$folderPath = storage_path('app/public/' . $folder);
echo "Step 1: Checking folder\n";
echo "  Path: $folderPath\n";
echo "  Exists before: " . (file_exists($folderPath) ? 'YES' : 'NO') . "\n";

if (!file_exists($folderPath)) {
    mkdir($folderPath, 0755, true);
    echo "  Created folder ✅\n";
} else {
    echo "  Folder already exists ✅\n";
}

echo "  Writable: " . (is_writable($folderPath) ? 'YES ✅' : 'NO ❌') . "\n\n";

// Test upload
echo "Step 2: Testing upload\n";
try {
    $result = $uploadedFile->storeAs($folder, $filename, 'public');
    
    if ($result) {
        echo "  Upload result: $result ✅\n";
        echo "  File exists: " . (Storage::disk('public')->exists($result) ? 'YES ✅' : 'NO ❌') . "\n";
        
        if (Storage::disk('public')->exists($result)) {
            $size = Storage::disk('public')->size($result);
            echo "  File size: $size bytes\n";
            
            // Test URL generation
            $url = url('serve-image/' . $result);
            echo "  URL: $url\n";
        }
        
        // Cleanup
        Storage::disk('public')->delete($result);
        echo "  Cleanup: Done ✅\n";
    } else {
        echo "  Upload FAILED ❌\n";
    }
} catch (\Exception $e) {
    echo "  ERROR: " . $e->getMessage() . " ❌\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
}

fclose($tempFile);

echo "\n=== TEST COMPLETE ===\n";
