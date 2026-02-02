<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

echo "🔧 FIXING PRODUCTS WITH MISSING IMAGE FILES\n";
echo "===========================================\n\n";

// Find products with image paths that don't exist locally
$productsWithMissingFiles = \App\Models\Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->get()
    ->filter(function($product) {
        $imagePath = $product->image;
        $fullPath = storage_path('app/public/' . $imagePath);
        return !file_exists($fullPath);
    });

echo "📋 Products with missing image files: " . $productsWithMissingFiles->count() . "\n\n";

if ($productsWithMissingFiles->count() === 0) {
    echo "✅ All products have valid image files!\n";
    exit;
}

// Get available images that exist in storage
$availableImages = collect(\Illuminate\Support\Facades\Storage::disk('public')->files('products'))
    ->filter(function($file) {
        return preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file);
    })
    ->filter(function($file) {
        $fullPath = storage_path('app/public/' . $file);
        return file_exists($fullPath);
    })
    ->toArray();

echo "📁 Available valid images: " . count($availableImages) . "\n\n";

$fixed = 0;
$errors = 0;

foreach($productsWithMissingFiles as $product) {
    try {
        $oldImagePath = $product->image;
        
        // Get an available image
        if (!empty($availableImages)) {
            $newImagePath = $availableImages[array_rand($availableImages)];
            $fullPath = storage_path('app/public/' . $newImagePath);
            
            // Update product
            $product->image = $newImagePath;
            $product->save();
            
            // Remove any existing ProductImage records for this product
            \App\Models\ProductImage::where('product_id', $product->id)->delete();
            
            // Create new ProductImage record
            \App\Models\ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $newImagePath,
                'original_name' => basename($newImagePath),
                'mime_type' => null,
                'file_size' => filesize($fullPath),
                'sort_order' => 1,
                'is_primary' => true,
            ]);
            
            $fixed++;
            echo "✅ Fixed: {$product->name}\n";
            echo "   Old: {$oldImagePath}\n";
            echo "   New: {$newImagePath}\n\n";
            
        } else {
            echo "❌ No available images to assign to: {$product->name}\n";
        }
        
    } catch (\Exception $e) {
        $errors++;
        echo "❌ Error fixing {$product->name}: " . $e->getMessage() . "\n";
    }
}

echo "\n📊 Summary:\n";
echo "Products fixed: {$fixed}\n";
echo "Errors: {$errors}\n";

// Final verification
$stillMissingFiles = \App\Models\Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->get()
    ->filter(function($product) {
        $imagePath = $product->image;
        $fullPath = storage_path('app/public/' . $imagePath);
        return !file_exists($fullPath);
    })
    ->count();

echo "Products still with missing files: {$stillMissingFiles}\n";

if ($stillMissingFiles === 0) {
    echo "\n🎉 SUCCESS: All products now have valid image files!\n";
}