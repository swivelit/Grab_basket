<?php

require_once 'vendor/autoload.php';

try {
    // Simple test to see if Product model works
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "Laravel bootstrapped successfully\n";
    
    // Test database connection
    $pdo = \DB::connection()->getPdo();
    echo "Database connected successfully\n";
    
    // Test Product model loading
    $product = \App\Models\Product::find(28);
    if ($product) {
        echo "Product 28 found: " . $product->name . "\n";
        echo "Raw image: " . $product->image . "\n";
        
        // Test the problematic method
        try {
            $imageUrl = $product->image_url;
            echo "Image URL generated successfully: " . $imageUrl . "\n";
        } catch (Exception $e) {
            echo "ERROR in getImageUrlAttribute: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
        }
    } else {
        echo "Product 28 not found\n";
    }
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

?>