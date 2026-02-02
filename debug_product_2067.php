<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\DB;

echo "=== INVESTIGATING PRODUCT 2067 ===\n\n";

// Get product directly from database
$productData = DB::table('products')->where('id', 2067)->first();

if (!$productData) {
    echo "❌ Product 2067 not found\n";
    exit;
}

echo "Product 2067 data:\n";
foreach ($productData as $key => $value) {
    echo "  {$key}: " . ($value ?? 'NULL') . "\n";
}

echo "\n=== CHECKING RELATIONSHIPS ===\n";

// Check category
if ($productData->category_id) {
    $category = DB::table('categories')->where('id', $productData->category_id)->first();
    if ($category) {
        echo "✅ Category {$productData->category_id} exists: {$category->name}\n";
    } else {
        echo "❌ Category {$productData->category_id} does NOT exist\n";
    }
}

// Check subcategory
if ($productData->subcategory_id) {
    $subcategory = DB::table('subcategories')->where('id', $productData->subcategory_id)->first();
    if ($subcategory) {
        echo "✅ Subcategory {$productData->subcategory_id} exists: {$subcategory->name}\n";
    } else {
        echo "❌ Subcategory {$productData->subcategory_id} does NOT exist\n";
    }
} else {
    echo "⚠️  No subcategory assigned\n";
}

// Check seller
if ($productData->seller_id) {
    $seller = DB::table('sellers')->where('id', $productData->seller_id)->first();
    if ($seller) {
        echo "✅ Seller {$productData->seller_id} exists\n";
    } else {
        echo "❌ Seller {$productData->seller_id} does NOT exist\n";
    }
}

echo "\n=== TRYING TO LOAD WITH ELOQUENT ===\n";
try {
    $product = Product::with(['category', 'subcategory'])->find(2067);
    
    if ($product) {
        echo "✅ Product loaded\n";
        echo "  Category relation: " . ($product->category ? "✅ OK" : "❌ NULL") . "\n";
        echo "  Subcategory relation: " . ($product->subcategory ? "✅ OK" : "❌ NULL") . "\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR loading product:\n";
    echo "  " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== CHECKING ALL PRODUCTS IN CATEGORY 24 ===\n";
$products = DB::table('products')->where('category_id', 24)->get(['id', 'name', 'category_id', 'subcategory_id']);

foreach ($products as $p) {
    echo "Product {$p->id}: {$p->name}\n";
    echo "  Category: {$p->category_id}, Subcategory: " . ($p->subcategory_id ?? 'NULL') . "\n";
    
    // Check if category exists
    $catExists = DB::table('categories')->where('id', $p->category_id)->exists();
    echo "  Category exists: " . ($catExists ? '✅' : '❌') . "\n";
    
    // Check if subcategory exists (if set)
    if ($p->subcategory_id) {
        $subExists = DB::table('subcategories')->where('id', $p->subcategory_id)->exists();
        echo "  Subcategory exists: " . ($subExists ? '✅' : '❌') . "\n";
    }
    echo "\n";
}
