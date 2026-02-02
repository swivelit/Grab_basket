<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

echo "🔄 BULK ASSIGN IMAGES TO EXISTING PRODUCTS\n";
echo "==========================================\n\n";

// Get all products without images
$productsWithoutImages = \App\Models\Product::whereNull('image')->get();
echo "📋 Products without images: " . $productsWithoutImages->count() . "\n";

if ($productsWithoutImages->count() === 0) {
    echo "✅ All products already have images!\n";
    exit;
}

// Get available images from storage
$availableImages = collect(\Illuminate\Support\Facades\Storage::disk('public')->files('products'))
    ->filter(function($file) {
        return preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file);
    })
    ->toArray();

echo "📁 Available images in storage: " . count($availableImages) . "\n\n";

if (empty($availableImages)) {
    echo "❌ No images available in storage\n";
    exit;
}

// Get already used images
$usedImages = \App\Models\Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->pluck('image')
    ->toArray();

echo "🔗 Already used images: " . count($usedImages) . "\n";

$assigned = 0;
$errors = 0;

foreach ($productsWithoutImages as $product) {
    try {
        // Find an unused image first
        $unusedImages = array_diff($availableImages, $usedImages);
        
        if (!empty($unusedImages)) {
            $selectedImage = reset($unusedImages);
            $usedImages[] = $selectedImage; // Mark as used
        } else {
            // If all images are used, pick one randomly
            $selectedImage = $availableImages[array_rand($availableImages)];
        }
        
        // Update product
        $product->image = $selectedImage;
        $product->save();
        
        // Create ProductImage record
        $imageFullPath = storage_path('app/public/' . $selectedImage);
        $fileSize = file_exists($imageFullPath) ? filesize($imageFullPath) : null;
        
        \App\Models\ProductImage::create([
            'product_id' => $product->id,
            'image_path' => $selectedImage,
            'original_name' => basename($selectedImage),
            'mime_type' => null,
            'file_size' => $fileSize,
            'sort_order' => 1,
            'is_primary' => true,
        ]);
        
        $assigned++;
        echo "✅ {$product->name} -> {$selectedImage}\n";
        
    } catch (\Exception $e) {
        $errors++;
        echo "❌ Failed to assign image to {$product->name}: " . $e->getMessage() . "\n";
    }
}

echo "\n📊 Summary:\n";
echo "Images assigned: {$assigned}\n";
echo "Errors: {$errors}\n";

// Check final status
$finalWithoutImages = \App\Models\Product::whereNull('image')->count();
echo "Products still without images: {$finalWithoutImages}\n";

if ($finalWithoutImages === 0) {
    echo "\n🎉 SUCCESS: All products now have images!\n";
} else {
    echo "\n⚠️  Some products still need images\n";
}