<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\Storage;

echo "🔍 TESTING ADD PRODUCT IMAGE UPLOAD\n";
echo "====================================\n\n";

// Check the latest products added
$latestProducts = Product::latest()->take(5)->get();

echo "📦 LATEST 5 PRODUCTS:\n";
echo "--------------------\n";

foreach ($latestProducts as $product) {
    echo "ID: {$product->id}\n";
    echo "Name: {$product->name}\n";
    echo "Seller ID: {$product->seller_id}\n";
    echo "Image Path: " . ($product->image ? $product->image : 'NULL') . "\n";
    
    if ($product->image) {
        $fullPath = storage_path('app/public/' . $product->image);
        $publicPath = public_path('storage/' . $product->image);
        
        echo "Storage Path: {$fullPath}\n";
        echo "Public Path: {$publicPath}\n";
        echo "Storage Exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
        echo "Public Exists: " . (file_exists($publicPath) ? 'YES' : 'NO') . "\n";
        
        // Check if storage link exists
        $storageLink = public_path('storage');
        echo "Storage Link: " . (is_link($storageLink) ? 'SYMLINK EXISTS' : 'NO SYMLINK') . "\n";
        echo "Storage Link Target: " . (is_link($storageLink) ? readlink($storageLink) : 'N/A') . "\n";
    }
    
    echo "---\n";
}

echo "\n🔧 STORAGE DIAGNOSTICS:\n";
echo "-----------------------\n";

// Check storage configuration
echo "Storage disk 'public' path: " . Storage::disk('public')->path('') . "\n";
echo "Public storage path: " . public_path('storage') . "\n";

// Check if storage link is properly created
$storageLink = public_path('storage');
if (file_exists($storageLink)) {
    if (is_link($storageLink)) {
        echo "✅ Storage symlink exists and points to: " . readlink($storageLink) . "\n";
    } else {
        echo "❌ Storage exists but is not a symlink\n";
    }
} else {
    echo "❌ Storage symlink does not exist\n";
    echo "💡 Run: php artisan storage:link\n";
}

echo "\n📁 SELLER DIRECTORIES:\n";
echo "----------------------\n";

$sellerDirs = glob(storage_path('app/public/seller/*'), GLOB_ONLYDIR);
foreach ($sellerDirs as $dir) {
    $sellerId = basename($dir);
    echo "Seller {$sellerId}:\n";
    
    $categoryDirs = glob($dir . '/*', GLOB_ONLYDIR);
    foreach ($categoryDirs as $catDir) {
        $categoryId = basename($catDir);
        $subcatDirs = glob($catDir . '/*', GLOB_ONLYDIR);
        foreach ($subcatDirs as $subcatDir) {
            $subcategoryId = basename($subcatDir);
            $images = glob($subcatDir . '/*');
            echo "  - Category {$categoryId}/Subcategory {$subcategoryId}: " . count($images) . " files\n";
        }
    }
}

echo "\n✅ Diagnostic complete!\n";