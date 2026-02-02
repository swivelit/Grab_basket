<?php

require_once 'vendor/autoload.php';

echo "🔍 EDIT PRODUCT FORM FUNCTIONALITY TEST\n";
echo "=======================================\n\n";

try {
    // Load Laravel app
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "✅ Laravel application loaded\n\n";
    
    // Test product with image
    $product = \App\Models\Product::where('id', 56)->first();
    
    if (!$product) {
        echo "❌ Product not found\n";
        exit;
    }
    
    echo "📦 Testing Product: {$product->name} (ID: {$product->id})\n\n";
    
    // Test 1: Check all required data for edit form
    echo "1. 📋 Form Data Verification:\n";
    echo "   Name: " . ($product->name ?? 'NULL') . "\n";
    echo "   Category ID: " . ($product->category_id ?? 'NULL') . "\n";
    echo "   Subcategory ID: " . ($product->subcategory_id ?? 'NULL') . "\n";
    echo "   Description: " . (substr($product->description ?? 'NULL', 0, 50)) . "...\n";
    echo "   Price: " . ($product->price ?? 'NULL') . "\n";
    echo "   Discount: " . ($product->discount ?? 'NULL') . "\n";
    echo "   Delivery Charge: " . ($product->delivery_charge ?? 'NULL') . "\n";
    echo "   Image: " . ($product->image ?? 'NULL') . "\n";
    echo "   Stock: " . ($product->stock ?? 'NULL') . "\n";
    echo "   Gift Option: " . ($product->gift_option ?? 'NULL') . "\n";
    
    // Test 2: Check relationships
    echo "\n2. 🔗 Relationship Verification:\n";
    try {
        $category = $product->category;
        echo "   Category: " . ($category ? $category->name : 'NULL') . "\n";
    } catch (Exception $e) {
        echo "   ❌ Category relationship failed: " . $e->getMessage() . "\n";
    }
    
    try {
        $subcategory = $product->subcategory;
        echo "   Subcategory: " . ($subcategory ? $subcategory->name : 'NULL') . "\n";
    } catch (Exception $e) {
        echo "   ❌ Subcategory relationship failed: " . $e->getMessage() . "\n";
    }
    
    // Test 3: Check if categories and subcategories exist for form
    echo "\n3. 📂 Form Dependencies:\n";
    $categories = \App\Models\Category::all();
    $subcategories = \App\Models\Subcategory::all();
    echo "   Available categories: " . $categories->count() . "\n";
    echo "   Available subcategories: " . $subcategories->count() . "\n";
    
    // Test 4: Check route generation
    echo "\n4. 🛣️  Route Testing:\n";
    try {
        $editRoute = route('seller.editProduct', $product);
        echo "   ✅ Edit route: $editRoute\n";
    } catch (Exception $e) {
        echo "   ❌ Edit route failed: " . $e->getMessage() . "\n";
    }
    
    try {
        $updateRoute = route('seller.updateProduct', $product);
        echo "   ✅ Update route: $updateRoute\n";
    } catch (Exception $e) {
        echo "   ❌ Update route failed: " . $e->getMessage() . "\n";
    }
    
    // Test 5: Check controller method availability
    echo "\n5. 🎛️  Controller Method Check:\n";
    try {
        $controller = new \App\Http\Controllers\SellerController();
        if (method_exists($controller, 'editProduct')) {
            echo "   ✅ editProduct method exists\n";
        } else {
            echo "   ❌ editProduct method missing\n";
        }
        
        if (method_exists($controller, 'updateProduct')) {
            echo "   ✅ updateProduct method exists\n";
        } else {
            echo "   ❌ updateProduct method missing\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Controller check failed: " . $e->getMessage() . "\n";
    }
    
    // Test 6: Check if view file exists
    echo "\n6. 👁️  View File Check:\n";
    $viewPath = resource_path('views/seller/edit-product.blade.php');
    if (file_exists($viewPath)) {
        echo "   ✅ Edit product view exists: $viewPath\n";
        echo "   File size: " . filesize($viewPath) . " bytes\n";
    } else {
        echo "   ❌ Edit product view missing: $viewPath\n";
    }
    
    echo "\n🎯 Functionality test complete!\n";
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}