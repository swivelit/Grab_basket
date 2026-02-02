<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

echo "🔧 IMAGE ASSIGNMENT TEST\n";
echo "========================\n\n";

// Get a product without an image
$product = \App\Models\Product::whereNull('image')->first();

if (!$product) {
    echo "❌ No products without images found\n";
    exit;
}

echo "🎯 Testing with product: {$product->name}\n";
echo "Product ID: {$product->id}\n";
echo "Current image: " . ($product->image ?: 'NULL') . "\n\n";

// Check available images in storage
$publicProductsPath = storage_path('app/public/products');
$imageFiles = glob($publicProductsPath . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);

echo "📁 Available images in storage: " . count($imageFiles) . "\n";

if (count($imageFiles) > 0) {
    $testImageFile = $imageFiles[0];
    $imageName = basename($testImageFile);
    $imagePath = 'products/' . $imageName;
    
    echo "📸 Test image: {$imageName}\n";
    echo "Full path: {$testImageFile}\n";
    echo "Storage path: {$imagePath}\n\n";
    
    // Test assigning the image to the product
    echo "🔗 Assigning image to product...\n";
    
    try {
        // Update product image field
        $product->image = $imagePath;
        $product->save();
        echo "✅ Product image field updated\n";
        
        // Create ProductImage record
        $productImage = \App\Models\ProductImage::create([
            'product_id' => $product->id,
            'image_path' => $imagePath,
            'original_name' => $imageName,
            'mime_type' => null,
            'file_size' => filesize($testImageFile),
            'sort_order' => 1,
            'is_primary' => true,
        ]);
        echo "✅ ProductImage record created (ID: {$productImage->id})\n";
        
        // Test image URL generation
        $product->refresh();
        echo "🌐 Generated image URL: " . $product->image_url . "\n";
        
        echo "\n✅ Image assignment test completed successfully!\n";
        
    } catch (\Exception $e) {
        echo "❌ Error during assignment: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
} else {
    echo "❌ No image files found in storage\n";
}

// Check for any file permission issues
echo "\n🔒 Permission check:\n";
echo "Storage directory writable: " . (is_writable($publicProductsPath) ? 'YES' : 'NO') . "\n";
echo "Storage directory readable: " . (is_readable($publicProductsPath) ? 'YES' : 'NO') . "\n";