<?php

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

echo "\n=== TEST ADD PRODUCT SIMULATION ===\n\n";

// 1. Check if we have categories and subcategories
$categories = Category::count();
$subcategories = Subcategory::count();
echo "1. Database Check:\n";
echo "   Categories: $categories\n";
echo "   Subcategories: $subcategories\n\n";

// 2. Check storage setup
echo "2. Storage Configuration:\n";
$publicRoot = storage_path('app/public');
echo "   Public disk root: $publicRoot\n";
echo "   Exists: " . (is_dir($publicRoot) ? 'YES' : 'NO') . "\n";
echo "   Writable: " . (is_writable($publicRoot) ? 'YES ✅' : 'NO ❌') . "\n";

$sellerFolder = $publicRoot . '/products/seller-2';
echo "   Seller-2 folder: $sellerFolder\n";
echo "   Exists: " . (is_dir($sellerFolder) ? 'YES' : 'NO') . "\n";
echo "   Writable: " . (is_writable($sellerFolder) ? 'YES ✅' : 'NO ❌') . "\n\n";

// 3. Check R2 configuration
echo "3. R2 Storage:\n";
echo "   Configured: " . (config('filesystems.disks.r2') ? 'YES ✅' : 'NO ❌') . "\n";
echo "   Bucket: " . config('filesystems.disks.r2.bucket') . "\n";
echo "   Region: " . config('filesystems.disks.r2.region') . "\n\n";

// 4. Simulate image upload test
echo "4. Simulated Upload Test:\n";
try {
    $testContent = 'Test image content - ' . date('Y-m-d H:i:s');
    $testFilename = 'test-product-' . time() . '.txt';
    $testPath = 'products/seller-2/' . $testFilename;
    
    // Test public disk
    $publicResult = Storage::disk('public')->put($testPath, $testContent);
    echo "   Public disk write: " . ($publicResult ? 'SUCCESS ✅' : 'FAILED ❌') . "\n";
    
    // Test R2
    try {
        $r2Result = Storage::disk('r2')->put($testPath, $testContent);
        echo "   R2 write: " . ($r2Result ? 'SUCCESS ✅' : 'FAILED ❌') . "\n";
    } catch (\Exception $e) {
        echo "   R2 write: FAILED ❌ (" . $e->getMessage() . ")\n";
    }
    
    // Cleanup
    Storage::disk('public')->delete($testPath);
    try { Storage::disk('r2')->delete($testPath); } catch (\Exception $e) {}
    echo "   Cleanup: Done\n\n";
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n\n";
}

// 5. Check recent products
echo "5. Recent Products (Last 3):\n";
$recentProducts = Product::with('productImages')
    ->orderBy('created_at', 'desc')
    ->take(3)
    ->get();

foreach ($recentProducts as $p) {
    echo "   Product #{$p->id} - {$p->name}\n";
    echo "      Created: {$p->created_at}\n";
    echo "      Legacy Image: " . ($p->image ?: 'NONE') . "\n";
    echo "      ProductImages: " . $p->productImages->count() . "\n";
    if ($p->productImages->count() > 0) {
        foreach ($p->productImages as $img) {
            echo "         - {$img->image_path}\n";
        }
    }
    echo "\n";
}

echo "=== TEST COMPLETE ===\n";
echo "\nRECOMMENDATION: Try uploading a product via the web interface now.\n";
echo "The logs will show detailed information about the upload process.\n";
echo "Check: storage/logs/laravel.log for detailed logs.\n";
