<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;

echo "🔍 DIAGNOSING EDIT PRODUCT 500 ERROR\n";
echo "====================================\n\n";

try {
    // Test 1: Check if we can fetch a product
    echo "1. 📦 Testing Product Retrieval:\n";
    $product = Product::first();
    
    if (!$product) {
        echo "   ❌ No products found in database\n";
        exit;
    }
    
    echo "   ✅ Found product: {$product->name} (ID: {$product->id})\n";
    echo "   Product seller_id: {$product->seller_id}\n";
    echo "   Product category_id: {$product->category_id}\n";
    echo "   Product subcategory_id: {$product->subcategory_id}\n";
    echo "   Product image: " . ($product->image ?: 'NULL') . "\n";

    // Test 2: Check relationships
    echo "\n2. 🔗 Testing Product Relationships:\n";
    
    try {
        $category = $product->category;
        echo "   Category: " . ($category ? $category->name : 'NULL') . "\n";
    } catch (\Exception $e) {
        echo "   ❌ Category relationship error: " . $e->getMessage() . "\n";
    }
    
    try {
        $subcategory = $product->subcategory;
        echo "   Subcategory: " . ($subcategory ? $subcategory->name : 'NULL') . "\n";
    } catch (\Exception $e) {
        echo "   ❌ Subcategory relationship error: " . $e->getMessage() . "\n";
    }
    
    try {
        $seller = $product->seller;
        echo "   Seller: " . ($seller ? $seller->name : 'NULL') . "\n";
    } catch (\Exception $e) {
        echo "   ❌ Seller relationship error: " . $e->getMessage() . "\n";
    }

    // Test 3: Check if we can fetch categories and subcategories
    echo "\n3. 📂 Testing Categories and Subcategories:\n";
    $categories = Category::all();
    $subcategories = Subcategory::all();
    
    echo "   Categories count: " . $categories->count() . "\n";
    echo "   Subcategories count: " . $subcategories->count() . "\n";

    // Test 4: Test the edit product controller logic
    echo "\n4. 🎛️ Testing Edit Product Logic:\n";
    
    // Simulate the controller logic
    $testProduct = Product::find($product->id);
    $testCategories = Category::all();
    $testSubcategories = Subcategory::all();
    
    if ($testProduct && $testCategories->count() > 0 && $testSubcategories->count() > 0) {
        echo "   ✅ All required data available for edit form\n";
    } else {
        echo "   ❌ Missing required data:\n";
        echo "     Product: " . ($testProduct ? 'OK' : 'MISSING') . "\n";
        echo "     Categories: " . ($testCategories->count() > 0 ? 'OK' : 'MISSING') . "\n";
        echo "     Subcategories: " . ($testSubcategories->count() > 0 ? 'OK' : 'MISSING') . "\n";
    }

    // Test 5: Check for potential view issues
    echo "\n5. 👁️ Testing View Requirements:\n";
    
    $viewPath = resource_path('views/seller/edit-product.blade.php');
    echo "   View file exists: " . (file_exists($viewPath) ? 'YES' : 'NO') . "\n";
    
    if (file_exists($viewPath)) {
        $viewContent = file_get_contents($viewPath);
        echo "   View file size: " . strlen($viewContent) . " bytes\n";
        
        // Check for potential PHP syntax issues in the view
        if (strpos($viewContent, '<?php') !== false) {
            echo "   ⚠️ View contains PHP code - checking for syntax issues\n";
            
            // Extract PHP blocks and check them
            preg_match_all('/<?php(.*?)(\?>|$)/s', $viewContent, $matches);
            foreach ($matches[1] as $index => $phpCode) {
                $testCode = "<?php " . $phpCode;
                if (!@eval('return true; ' . $phpCode)) {
                    echo "   ❌ PHP syntax error in block " . ($index + 1) . "\n";
                }
            }
        }
    }

    echo "\n6. 🌐 Testing URL Generation:\n";
    
    try {
        $editUrl = route('seller.editProduct', ['product' => $product->id]);
        echo "   Edit URL: {$editUrl}\n";
        echo "   ✅ Route generation successful\n";
    } catch (\Exception $e) {
        echo "   ❌ Route generation error: " . $e->getMessage() . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✅ Diagnostic complete!\n";