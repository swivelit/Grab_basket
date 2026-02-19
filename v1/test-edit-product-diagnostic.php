<?php

require_once 'vendor/autoload.php';

echo "🔍 EDIT PRODUCT FORM DIAGNOSTIC\n";
echo "===============================\n\n";

try {
    // Load Laravel app
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "✅ Laravel application loaded\n\n";
    
    // Test 1: Check if Product model works
    echo "1. 📦 Testing Product Model:\n";
    $productCount = \App\Models\Product::count();
    echo "   Products in database: $productCount\n";
    
    if ($productCount > 0) {
        $testProduct = \App\Models\Product::first();
        echo "   ✅ First product: {$testProduct->name} (ID: {$testProduct->id})\n";
        
        // Test 2: Check image_url attribute
        echo "\n2. 🖼️  Testing Image URL Attribute:\n";
        try {
            $imageUrl = $testProduct->image_url;
            echo "   ✅ Image URL generated: " . ($imageUrl ?? 'NULL') . "\n";
        } catch (Exception $e) {
            echo "   ❌ Image URL failed: " . $e->getMessage() . "\n";
        }
        
        // Test 3: Check edit product route
        echo "\n3. 🛣️  Testing Route Generation:\n";
        try {
            $editRoute = route('seller.editProduct', $testProduct);
            echo "   ✅ Edit route: $editRoute\n";
        } catch (Exception $e) {
            echo "   ❌ Route generation failed: " . $e->getMessage() . "\n";
        }
        
        // Test 4: Check update route
        try {
            $updateRoute = route('seller.updateProduct', $testProduct);
            echo "   ✅ Update route: $updateRoute\n";
        } catch (Exception $e) {
            echo "   ❌ Update route failed: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "   ⚠️  No products in database\n";
    }
    
    // Test 5: Check Categories and Subcategories
    echo "\n4. 📂 Testing Categories:\n";
    $categoryCount = \App\Models\Category::count();
    $subcategoryCount = \App\Models\Subcategory::count();
    echo "   Categories: $categoryCount\n";
    echo "   Subcategories: $subcategoryCount\n";
    
    // Test 6: Check Storage configuration
    echo "\n5. 💾 Testing Storage Configuration:\n";
    try {
        $defaultDisk = config('filesystems.default');
        echo "   Default disk: $defaultDisk\n";
        
        $disks = config('filesystems.disks');
        $availableDisks = array_keys($disks);
        echo "   Available disks: " . implode(', ', $availableDisks) . "\n";
        
        // Test if storage works
        \Illuminate\Support\Facades\Storage::disk('public')->put('test-diagnostic.txt', 'Test content');
        echo "   ✅ Public storage working\n";
        
        try {
            \Illuminate\Support\Facades\Storage::disk('r2')->put('test-diagnostic.txt', 'Test content');
            echo "   ✅ R2 storage working\n";
        } catch (Exception $e) {
            echo "   ⚠️  R2 storage not available (normal in development)\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Storage test failed: " . $e->getMessage() . "\n";
    }
    
    // Test 7: Check if user authentication is working
    echo "\n6. 👤 Testing Authentication:\n";
    echo "   Auth guard: " . config('auth.defaults.guard') . "\n";
    echo "   User provider: " . config('auth.providers.users.driver') . "\n";
    
    echo "\n🎯 Diagnostic complete!\n";
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}