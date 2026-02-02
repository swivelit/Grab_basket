<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

echo "\n=== TEST PUBLIC DISK WRITE ===\n\n";

// Test simple write
$testPath = 'test-write-' . time() . '.txt';
echo "Test 1: Simple write to root\n";
try {
    $result = Storage::disk('public')->put($testPath, 'Test content');
    if ($result) {
        echo "   ✅ Write successful: $testPath\n";
        echo "   Exists: " . (Storage::disk('public')->exists($testPath) ? 'YES' : 'NO') . "\n";
        Storage::disk('public')->delete($testPath);
        echo "   Cleanup: Done\n\n";
    } else {
        echo "   ❌ Write FAILED (returned false)\n\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n\n";
}

// Test write to subfolder
$testPath2 = 'products/seller-2/test-' . time() . '.txt';
echo "Test 2: Write to products/seller-2/\n";
try {
    $result = Storage::disk('public')->put($testPath2, 'Test content in subfolder');
    if ($result) {
        echo "   ✅ Write successful: $testPath2\n";
        echo "   Exists: " . (Storage::disk('public')->exists($testPath2) ? 'YES' : 'NO') . "\n";
        Storage::disk('public')->delete($testPath2);
        echo "   Cleanup: Done\n\n";
    } else {
        echo "   ❌ Write FAILED (returned false)\n\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n\n";
}

// Test storeAs (like we use in upload)
echo "Test 3: Using storeAs method\n";
try {
    $testContent = 'Test using storeAs';
    $tempFile = tmpfile();
    fwrite($tempFile, $testContent);
    $tempPath = stream_get_meta_data($tempFile)['uri'];
    
    $uploadedFile = new \Illuminate\Http\UploadedFile(
        $tempPath,
        'test-file.txt',
        'text/plain',
        null,
        true
    );
    
    $result = $uploadedFile->storeAs('products/seller-2', 'test-storesas-' . time() . '.txt', 'public');
    
    if ($result) {
        echo "   ✅ StoreAs successful: $result\n";
        echo "   Exists: " . (Storage::disk('public')->exists($result) ? 'YES' : 'NO') . "\n";
        Storage::disk('public')->delete($result);
        echo "   Cleanup: Done\n\n";
    } else {
        echo "   ❌ StoreAs FAILED (returned false)\n\n";
    }
    
    fclose($tempFile);
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n\n";
}

echo "=== END TEST ===\n";
