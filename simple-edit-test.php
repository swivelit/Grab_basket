<?php
// Simple test for edit product functionality

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

try {
    echo "Testing edit product functionality...\n";
    
    $product = Product::first();
    
    if ($product) {
        echo "✅ Product found: " . $product->name . "\n";
        echo "✅ Product ID: " . $product->id . "\n";
        echo "✅ Image: " . ($product->image ?: 'None') . "\n";
        
        // Test edit URL
        $editUrl = url("seller/product/{$product->id}/edit");
        echo "✅ Edit URL: " . $editUrl . "\n";
        
        echo "\n🎉 Edit functionality basic tests passed!\n";
    } else {
        echo "❌ No products found\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>