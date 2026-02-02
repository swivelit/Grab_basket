<?php

use Illuminate\Support\Facades\Storage;
use App\Models\Product;
use App\Models\ProductImage;

echo "\n=== SYMLINK & UPLOAD VERIFICATION ===\n\n";

// 1. Check symlink status
$symlinkPath = public_path('storage');
echo "1. Storage Symlink Check:\n";
echo "   Path: $symlinkPath\n";
echo "   Exists: " . (file_exists($symlinkPath) ? 'YES' : 'NO') . "\n";
echo "   Is Directory: " . (is_dir($symlinkPath) ? 'YES' : 'NO') . "\n";
echo "   Is Link: " . (is_link($symlinkPath) ? 'YES ✅' : 'NO ❌') . "\n";
echo "   Target: " . (is_link($symlinkPath) ? readlink($symlinkPath) : 'N/A') . "\n";
echo "   Real Path: " . realpath($symlinkPath) . "\n\n";

// 2. Test file write
echo "2. Test Write to Storage:\n";
$testFolder = 'products/seller-' . 2;
$testFile = $testFolder . '/test-' . time() . '.txt';
try {
    Storage::disk('public')->put($testFile, 'Test content from verification script');
    echo "   ✅ Successfully wrote test file: $testFile\n";
    
    // Check if file exists via public path
    $publicPath = public_path('storage/' . $testFile);
    echo "   Public path: $publicPath\n";
    echo "   Accessible via public: " . (file_exists($publicPath) ? 'YES ✅' : 'NO ❌') . "\n";
    
    // Clean up
    Storage::disk('public')->delete($testFile);
    echo "   Cleanup: Test file deleted\n\n";
} catch (\Exception $e) {
    echo "   ❌ FAILED: " . $e->getMessage() . "\n\n";
}

// 3. Check recent products and their images
echo "3. Recent Products Analysis:\n";
$recentProducts = Product::with('productImages')
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

foreach ($recentProducts as $product) {
    $imagesCount = $product->productImages->count();
    echo "   Product #{$product->id} ({$product->name}):\n";
    echo "      Created: {$product->created_at}\n";
    echo "      Seller ID: {$product->seller_id}\n";
    echo "      Legacy Image: " . ($product->image ?: 'NONE') . "\n";
    echo "      ProductImages: $imagesCount " . ($imagesCount > 0 ? '✅' : '❌') . "\n";
    
    if ($imagesCount > 0) {
        foreach ($product->productImages as $img) {
            $existsR2 = Storage::disk('r2')->exists($img->image_path);
            $existsPublic = Storage::disk('public')->exists($img->image_path);
            echo "         - Path: {$img->image_path}\n";
            echo "           R2: " . ($existsR2 ? 'EXISTS ✅' : 'MISSING ❌') . "\n";
            echo "           Public: " . ($existsPublic ? 'EXISTS ✅' : 'MISSING ❌') . "\n";
        }
    }
    echo "\n";
}

// 4. Storage disk configuration check
echo "4. Storage Configuration:\n";
echo "   Public Disk Root: " . config('filesystems.disks.public.root') . "\n";
echo "   Public Disk URL: " . config('filesystems.disks.public.url') . "\n";
echo "   R2 Configured: " . (config('filesystems.disks.r2') ? 'YES ✅' : 'NO ❌') . "\n\n";

echo "=== END VERIFICATION ===\n";
