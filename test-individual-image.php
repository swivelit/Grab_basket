<?php
// Test individual product image access

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

echo "🖼️ INDIVIDUAL PRODUCT IMAGE TEST\n";
echo "=================================\n\n";

// Get a product with an image
$product = Product::whereNotNull('image')->first();

if (!$product) {
    echo "❌ No products with images found\n";
    exit;
}

echo "Testing Product: {$product->name}\n";
echo "Image Path: {$product->image}\n";
echo "Product ID: {$product->id}\n\n";

$imagePath = $product->image;

// Test different access methods
$tests = [
    'Storage Symlink' => "storage/{$imagePath}",
    'Direct Path' => $imagePath,
    'Images Folder' => "images/" . basename($imagePath),
];

foreach ($tests as $method => $path) {
    $fullPath = public_path($path);
    $url = asset($path);
    
    echo "{$method}:\n";
    echo "  Path: {$path}\n";
    echo "  Full Path: {$fullPath}\n";
    echo "  URL: {$url}\n";
    echo "  File Exists: " . (file_exists($fullPath) ? '✅ YES' : '❌ NO') . "\n";
    
    if (file_exists($fullPath)) {
        $fileSize = filesize($fullPath);
        echo "  File Size: " . number_format($fileSize) . " bytes\n";
        echo "  ✅ THIS PATH WORKS!\n";
    }
    echo "\n";
}

// Test the storage symlink specifically
echo "STORAGE SYMLINK TEST:\n";
$storageLink = public_path('storage');
if (is_link($storageLink)) {
    echo "✅ Storage is a symlink pointing to: " . readlink($storageLink) . "\n";
} elseif (is_dir($storageLink)) {
    echo "⚠️ Storage is a directory (not symlink)\n";
} else {
    echo "❌ Storage does not exist\n";
}

// Test direct product storage path
$productStoragePath = storage_path('app/public/' . $imagePath);
echo "\nDIRECT STORAGE TEST:\n";
echo "Storage file path: {$productStoragePath}\n";
echo "Storage file exists: " . (file_exists($productStoragePath) ? '✅ YES' : '❌ NO') . "\n";

if (file_exists($productStoragePath)) {
    echo "Storage file size: " . number_format(filesize($productStoragePath)) . " bytes\n";
}

echo "\n✅ Test complete!\n";

// Generate a test URL
$testUrl = url("test-image-display.php");
echo "\n🌐 View web test at: {$testUrl}\n";
?>