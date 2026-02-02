<?php

require_once 'vendor/autoload.php';

echo "🔍 FINDING PRODUCTS WITH IMAGES\n";
echo "===============================\n\n";

try {
    // Load Laravel app
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "✅ Laravel application loaded\n\n";
    
    // Count products with images
    $withImages = \App\Models\Product::whereNotNull('image')->count();
    $withoutImages = \App\Models\Product::whereNull('image')->count();
    $total = \App\Models\Product::count();
    
    echo "📊 Products Statistics:\n";
    echo "   Total products: $total\n";
    echo "   With images: $withImages\n";
    echo "   Without images: $withoutImages\n\n";
    
    if ($withImages > 0) {
        echo "📦 Products with Images:\n";
        $productsWithImages = \App\Models\Product::whereNotNull('image')->take(5)->get();
        
        foreach ($productsWithImages as $product) {
            echo "   ID: {$product->id} - {$product->name}\n";
            echo "   Image: {$product->image}\n";
            
            // Test the image URL
            try {
                $imageUrl = $product->image_url;
                echo "   Image URL: " . ($imageUrl ?? 'NULL') . "\n";
            } catch (Exception $e) {
                echo "   ❌ Image URL error: " . $e->getMessage() . "\n";
            }
            echo "\n";
        }
    } else {
        echo "⚠️  No products have images assigned!\n";
        echo "This explains why the edit product form might appear to not be working.\n";
        echo "The form is probably working fine, but there are no images to display.\n\n";
        
        echo "🔧 Let's test by adding an image to a product:\n";
        $testProduct = \App\Models\Product::first();
        if ($testProduct) {
            // Create a test image path
            $testImagePath = 'products/test-image.jpg';
            
            echo "   Testing with product: {$testProduct->name} (ID: {$testProduct->id})\n";
            echo "   Setting image path to: $testImagePath\n";
            
            $testProduct->image = $testImagePath;
            $testProduct->save();
            
            echo "   ✅ Image path saved to product\n";
            
            // Test the image URL
            try {
                $imageUrl = $testProduct->image_url;
                echo "   Generated Image URL: " . ($imageUrl ?? 'NULL') . "\n";
            } catch (Exception $e) {
                echo "   ❌ Image URL error: " . $e->getMessage() . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}