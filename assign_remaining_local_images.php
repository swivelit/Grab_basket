<?php
// This script will assign remaining local images to products without images
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

echo "Assigning local images to products without images...\n";
echo "===================================================\n\n";

// Get all available local images from both directories
$imgDirs = [
    __DIR__ . '/SRM IMG',
    __DIR__ . '/images',
];

$availableImages = [];
foreach ($imgDirs as $dir) {
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if (preg_match('/\.(jpg|jpeg|png|webp)$/i', $file) && is_file($dir . '/' . $file)) {
                $availableImages[] = [
                    'path' => $dir,
                    'file' => $file,
                    'web' => (basename($dir) === 'images' ? '/images/' : '/SRM IMG/') . $file
                ];
            }
        }
    }
}

echo "Found " . count($availableImages) . " available local images\n\n";

// Get products that don't have images
$productsWithoutImages = \App\Models\Product::where(function($query) {
    $query->whereNull('image')
          ->orWhere('image', '')
          ->orWhere('image', 'https://via.placeholder.com/200?text=No+Image');
})->get();

echo "Found " . count($productsWithoutImages) . " products without images\n\n";

$assigned = 0;
$imageIndex = 0;

foreach ($productsWithoutImages as $product) {
    if ($imageIndex < count($availableImages)) {
        $image = $availableImages[$imageIndex];
        
        // Assign the image to the product
        $product->image = $image['web'];
        $product->save();
        
        echo "[ASSIGNED] {$product->name} -> {$image['web']}\n";
        $assigned++;
        $imageIndex++;
    } else {
        echo "[NO IMAGE] {$product->name} (no more images available)\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Total images assigned: $assigned\n";
echo "Products still without images: " . (count($productsWithoutImages) - $assigned) . "\n";

// Show updated status
echo "\n=== UPDATED STATUS ===\n";
$totalProducts = \App\Models\Product::count();
$productsWithImages = \App\Models\Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->where('image', '!=', 'https://via.placeholder.com/200?text=No+Image')
    ->count();

echo "Total Products: $totalProducts\n";
echo "Products WITH Images: $productsWithImages\n";
echo "Products WITHOUT Images: " . ($totalProducts - $productsWithImages) . "\n";

// Show category breakdown
echo "\nCategory breakdown (with images):\n";
$categories = \App\Models\Category::all();
foreach($categories as $category) {
    $categoryProductsWithImages = \App\Models\Product::where('category_id', $category->id)
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', '!=', 'https://via.placeholder.com/200?text=No+Image')
        ->count();
    
    $totalCategoryProducts = \App\Models\Product::where('category_id', $category->id)->count();
    
    if ($totalCategoryProducts > 0) {
        echo "- {$category->name}: $categoryProductsWithImages/$totalCategoryProducts\n";
    }
}