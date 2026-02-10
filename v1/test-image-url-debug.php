<?php

require_once 'vendor/autoload.php';

echo "🔍 IMAGE URL DEBUGGING\n";
echo "======================\n\n";

try {
    // Load Laravel app
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "✅ Laravel application loaded\n\n";
    
    // Get first few products and check their image handling
    $products = \App\Models\Product::limit(5)->get();
    
    foreach ($products as $product) {
        echo "📦 Product: {$product->name} (ID: {$product->id})\n";
        echo "   Image field: " . ($product->image ?? 'NULL') . "\n";
        
        try {
            $imageUrl = $product->image_url;
            echo "   Image URL: " . ($imageUrl ?? 'NULL') . "\n";
        } catch (Exception $e) {
            echo "   ❌ Image URL error: " . $e->getMessage() . "\n";
        }
        
        // Test if image file exists
        if ($product->image) {
            $imagePath = $product->image;
            echo "   Testing paths:\n";
            
            // Check public storage
            if (file_exists(public_path('storage/' . $imagePath))) {
                echo "     ✅ Found in: public/storage/$imagePath\n";
            } else {
                echo "     ❌ Not found in: public/storage/$imagePath\n";
            }
            
            // Check direct public path
            if (file_exists(public_path($imagePath))) {
                echo "     ✅ Found in: public/$imagePath\n";
            } else {
                echo "     ❌ Not found in: public/$imagePath\n";
            }
            
            // Check images folder
            if (file_exists(public_path('images/' . basename($imagePath)))) {
                echo "     ✅ Found in: public/images/" . basename($imagePath) . "\n";
            } else {
                echo "     ❌ Not found in: public/images/" . basename($imagePath) . "\n";
            }
            
            // Test R2 storage
            try {
                if (\Illuminate\Support\Facades\Storage::disk('r2')->exists($imagePath)) {
                    echo "     ✅ Found in R2 storage\n";
                } else {
                    echo "     ❌ Not found in R2 storage\n";
                }
            } catch (Exception $e) {
                echo "     ⚠️  R2 check failed: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}