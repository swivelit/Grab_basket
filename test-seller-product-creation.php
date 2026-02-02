<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

echo "🧪 TESTING SELLER PRODUCT CREATION FUNCTIONALITY\n";
echo "===============================================\n\n";

try {
    // Test 1: Check Categories
    echo "1. 📦 Testing Categories:\n";
    $categories = Category::all();
    echo "   Categories available: " . $categories->count() . "\n";
    if ($categories->count() > 0) {
        echo "   ✅ First category: " . $categories->first()->name . " (ID: " . $categories->first()->id . ")\n";
    } else {
        echo "   ❌ No categories found! This will prevent product creation.\n";
    }

    // Test 2: Check Subcategories
    echo "\n2. 📂 Testing Subcategories:\n";
    $subcategories = Subcategory::all();
    echo "   Subcategories available: " . $subcategories->count() . "\n";
    if ($subcategories->count() > 0) {
        echo "   ✅ First subcategory: " . $subcategories->first()->name . " (ID: " . $subcategories->first()->id . ")\n";
    } else {
        echo "   ❌ No subcategories found! This will prevent product creation.\n";
    }

    // Test 3: Check Sellers
    echo "\n3. 👤 Testing Sellers:\n";
    $sellers = User::where('role', 'seller')->get();
    echo "   Sellers available: " . $sellers->count() . "\n";
    if ($sellers->count() > 0) {
        echo "   ✅ First seller: " . $sellers->first()->name . " (ID: " . $sellers->first()->id . ")\n";
        $testSeller = $sellers->first();
    } else {
        echo "   ❌ No sellers found! Products can't be created without sellers.\n";
        exit;
    }

    // Test 4: Check Storage Configuration
    echo "\n4. 💾 Testing Storage Configuration:\n";
    try {
        $disk = Storage::disk('public');
        echo "   ✅ Public storage disk is accessible\n";
        
        // Check if directories exist
        $productsDir = storage_path('app/public/products');
        if (is_dir($productsDir)) {
            echo "   ✅ Products directory exists: $productsDir\n";
        } else {
            echo "   ⚠️  Products directory doesn't exist. Creating it...\n";
            mkdir($productsDir, 0755, true);
            echo "   ✅ Products directory created\n";
        }
        
        // Check if it's writable
        if (is_writable($productsDir)) {
            echo "   ✅ Products directory is writable\n";
        } else {
            echo "   ❌ Products directory is not writable!\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Storage configuration error: " . $e->getMessage() . "\n";
    }

    // Test 5: Test Product Creation Process
    echo "\n5. 🛒 Testing Product Creation Process:\n";
    
    if ($categories->count() > 0 && $subcategories->count() > 0) {
        $testCategory = $categories->first();
        $testSubcategory = $subcategories->first();
        
        echo "   Testing with Category: " . $testCategory->name . "\n";
        echo "   Testing with Subcategory: " . $testSubcategory->name . "\n";
        echo "   Testing with Seller: " . $testSeller->name . "\n";
        
        // Create a test product without image first
        try {
            $testProduct = new Product();
            $testProduct->name = "Test Product - " . date('Y-m-d H:i:s');
            $testProduct->unique_id = strtoupper(substr(md5(uniqid()), 0, 3));
            $testProduct->category_id = $testCategory->id;
            $testProduct->subcategory_id = $testSubcategory->id;
            $testProduct->seller_id = $testSeller->id;
            $testProduct->description = "Test product description";
            $testProduct->price = 99.99;
            $testProduct->discount = 0;
            $testProduct->delivery_charge = 0;
            $testProduct->gift_option = 'yes';
            $testProduct->stock = 10;
            
            $testProduct->save();
            
            echo "   ✅ Product created successfully: " . $testProduct->name . " (ID: " . $testProduct->id . ")\n";
            echo "   Product unique_id: " . $testProduct->unique_id . "\n";
            
            // Clean up - delete the test product
            $testProduct->delete();
            echo "   🧹 Test product cleaned up\n";
            
        } catch (Exception $e) {
            echo "   ❌ Product creation failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ⚠️  Skipping product creation test - missing categories or subcategories\n";
    }

    // Test 6: Check Form Requirements
    echo "\n6. 📝 Testing Form Requirements:\n";
    
    $requiredFields = [
        'name' => 'string|max:255',
        'category_id' => 'exists:categories,id',
        'subcategory_id' => 'exists:subcategories,id',
        'description' => 'string',
        'price' => 'numeric|min:0',
        'gift_option' => 'in:yes,no',
        'stock' => 'integer|min:0'
    ];
    
    echo "   Required fields for product creation:\n";
    foreach ($requiredFields as $field => $rule) {
        echo "   - $field: $rule\n";
    }
    
    $optionalFields = [
        'discount' => 'numeric|min:0',
        'delivery_charge' => 'numeric|min:0',
        'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
    ];
    
    echo "   Optional fields:\n";
    foreach ($optionalFields as $field => $rule) {
        echo "   - $field: $rule\n";
    }

    // Test 7: Check Recent Products
    echo "\n7. 📊 Recent Products Analysis:\n";
    $recentProducts = Product::latest()->take(5)->get();
    echo "   Recent products count: " . $recentProducts->count() . "\n";
    
    foreach ($recentProducts as $product) {
        echo "   - " . $product->name . " (ID: " . $product->id . ", Seller: " . $product->seller_id . ")\n";
        echo "     Image: " . ($product->image ? '✅ ' . $product->image : '❌ No image') . "\n";
        echo "     Price: ₹" . $product->price . ", Stock: " . $product->stock . "\n";
    }

    echo "\n✅ DIAGNOSTIC COMPLETE\n";
    echo "===================\n";

} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}