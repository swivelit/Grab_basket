<?php
// Test SRM product images specifically

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

echo "🖼️ SRM PRODUCT IMAGE TEST\n";
echo "=========================\n\n";

// Get SRM products (the ones we created recently)
$srmProducts = Product::where('unique_id', 'LIKE', 'SRM%')
    ->whereNotNull('image')
    ->orderBy('unique_id')
    ->take(5)
    ->get();

if ($srmProducts->count() == 0) {
    echo "❌ No SRM products with images found\n";
    exit;
}

foreach ($srmProducts as $product) {
    echo "Product: {$product->name}\n";
    echo "Unique ID: {$product->unique_id}\n";
    echo "Image Path: {$product->image}\n";
    
    $imagePath = $product->image;
    
    // Test storage path
    $storagePath = public_path('storage/' . $imagePath);
    $directStoragePath = storage_path('app/public/' . $imagePath);
    
    echo "Public Storage Path: {$storagePath}\n";
    echo "Direct Storage Path: {$directStoragePath}\n";
    echo "Public Path Exists: " . (file_exists($storagePath) ? '✅ YES' : '❌ NO') . "\n";
    echo "Direct Storage Exists: " . (file_exists($directStoragePath) ? '✅ YES' : '❌ NO') . "\n";
    
    if (file_exists($storagePath)) {
        echo "✅ Image accessible via web!\n";
        echo "URL: " . asset('storage/' . $imagePath) . "\n";
    }
    
    echo "---\n";
}

// Test storage symlink
echo "\nSTORAGE SYMLINK STATUS:\n";
$storageLink = public_path('storage');
if (is_link($storageLink)) {
    echo "✅ Storage symlink exists\n";
    echo "Points to: " . readlink($storageLink) . "\n";
} else {
    echo "❌ Storage symlink missing\n";
}

echo "\n✅ Test complete!\n";
?>