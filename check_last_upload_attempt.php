<?php

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

echo "\n=== CHECKING LAST PRODUCT CREATION ATTEMPT ===\n\n";

// 1. Get the absolute latest product
$latestProduct = Product::orderBy('created_at', 'desc')->first();

if ($latestProduct) {
    echo "Latest Product:\n";
    echo "   ID: {$latestProduct->id}\n";
    echo "   Name: {$latestProduct->name}\n";
    echo "   Created: {$latestProduct->created_at}\n";
    echo "   Seller ID: {$latestProduct->seller_id}\n";
    echo "   Legacy Image: " . ($latestProduct->image ?: 'NONE') . "\n";
    echo "   ProductImages Count: " . $latestProduct->productImages->count() . "\n\n";
    
    if ($latestProduct->productImages->count() > 0) {
        echo "   ProductImages:\n";
        foreach ($latestProduct->productImages as $img) {
            echo "      - Path: {$img->image_path}\n";
            echo "        Original: {$img->original_name}\n";
            echo "        Size: {$img->file_size} bytes\n";
            echo "        R2: " . (Storage::disk('r2')->exists($img->image_path) ? 'EXISTS ✅' : 'MISSING ❌') . "\n";
            echo "        Public: " . (Storage::disk('public')->exists($img->image_path) ? 'EXISTS ✅' : 'MISSING ❌') . "\n";
        }
    }
} else {
    echo "No products found!\n";
}

echo "\n=== CHECK PHP EXTENSIONS ===\n\n";

// Check if fileinfo extension is loaded (required for file uploads)
echo "fileinfo extension: " . (extension_loaded('fileinfo') ? 'LOADED ✅' : 'NOT LOADED ❌') . "\n";
echo "gd extension: " . (extension_loaded('gd') ? 'LOADED ✅' : 'NOT LOADED ❌') . "\n";
echo "exif extension: " . (extension_loaded('exif') ? 'LOADED ✅' : 'NOT LOADED ❌') . "\n";

echo "\n=== CHECK UPLOAD LIMITS ===\n\n";

echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";

echo "\n=== CHECK R2 CONNECTION ===\n\n";

try {
    $testContent = 'Test connection - ' . date('Y-m-d H:i:s');
    $testPath = 'test-connection-' . time() . '.txt';
    
    $r2Result = Storage::disk('r2')->put($testPath, $testContent);
    if ($r2Result) {
        echo "R2 Connection: SUCCESS ✅\n";
        echo "   Test file created: $testPath\n";
        
        // Try to read it back
        $readBack = Storage::disk('r2')->get($testPath);
        echo "   Read back: " . ($readBack === $testContent ? 'SUCCESS ✅' : 'FAILED ❌') . "\n";
        
        // Delete test file
        Storage::disk('r2')->delete($testPath);
        echo "   Cleanup: Done\n";
    } else {
        echo "R2 Connection: FAILED ❌\n";
    }
} catch (\Exception $e) {
    echo "R2 Connection: ERROR ❌\n";
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n=== END CHECK ===\n";
