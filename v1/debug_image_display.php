<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

echo "🖼️  IMAGE DISPLAY DIAGNOSTIC\n";
echo "============================\n\n";

// Get a few sample products with images
$products = \App\Models\Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->take(5)
    ->get();

foreach($products as $product) {
    echo "📦 Product: {$product->name}\n";
    echo "📁 Image field: {$product->image}\n";
    echo "🌐 Image URL (getImageUrlAttribute): {$product->image_url}\n";
    
    // Check if image file exists
    $imagePath = $product->image;
    $fullPath = storage_path('app/public/' . $imagePath);
    $exists = file_exists($fullPath);
    echo "📂 File exists: " . ($exists ? 'YES' : 'NO') . "\n";
    
    if ($exists) {
        echo "📏 File size: " . number_format(filesize($fullPath)) . " bytes\n";
    }
    
    // Check ProductImage records
    $productImages = \App\Models\ProductImage::where('product_id', $product->id)->get();
    echo "🏷️  ProductImage records: " . $productImages->count() . "\n";
    
    foreach($productImages as $pi) {
        echo "   - Path: {$pi->image_path}\n";
        echo "   - Primary: " . ($pi->is_primary ? 'Yes' : 'No') . "\n";
        echo "   - URL: {$pi->image_url}\n";
    }
    
    echo "\n" . str_repeat("-", 80) . "\n\n";
}

// Test URL generation manually
echo "🔧 Manual URL Testing:\n";
$testProduct = $products->first();
if ($testProduct) {
    $imagePath = $testProduct->image;
    echo "Image path: {$imagePath}\n";
    
    // Test different URL generation methods
    echo "\nURL Generation Methods:\n";
    echo "1. Storage URL: " . \Illuminate\Support\Facades\Storage::disk('public')->url($imagePath) . "\n";
    echo "2. Asset URL: " . asset('storage/' . $imagePath) . "\n";
    echo "3. App URL + storage: " . config('app.url') . '/storage/' . $imagePath . "\n";
    
    // Check if storage link exists
    $storageLinkPath = public_path('storage');
    echo "\n📂 Storage symlink exists: " . (is_link($storageLinkPath) || is_dir($storageLinkPath) ? 'YES' : 'NO') . "\n";
    
    // Test serve-image route
    $pathParts = explode('/', $imagePath, 2);
    if (count($pathParts) === 2) {
        $serveUrl = config('app.url') . '/serve-image/' . $pathParts[0] . '/' . $pathParts[1];
        echo "4. Serve-image URL: {$serveUrl}\n";
    }
}