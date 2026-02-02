<?php
// check_product_integrity.php
// Usage: php check_product_integrity.php <product_id>

use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

if ($argc < 2) {
    echo "Usage: php check_product_integrity.php <product_id>\n";
    exit(1);
}

$productId = $argv[1];

$product = \App\Models\Product::find($productId);
if (!$product) {
    echo "❌ Product not found (ID: $productId)\n";
    exit(1);
}
echo "✅ Product found: ID {$product->id}, Name: {$product->name}\n";

$categoryId = $product->category_id;
$subcategoryId = $product->subcategory_id;

$category = $categoryId ? \App\Models\Category::find($categoryId) : null;
$subcategory = $subcategoryId ? \App\Models\Subcategory::find($subcategoryId) : null;

if (!$category) {
    echo "❌ Category not found for product (category_id: $categoryId)\n";
} else {
    echo "✅ Category found: ID {$category->id}, Name: {$category->name}\n";
}

if (!$subcategory) {
    echo "❌ Subcategory not found for product (subcategory_id: $subcategoryId)\n";
} else {
    echo "✅ Subcategory found: ID {$subcategory->id}, Name: {$subcategory->name}\n";
    if ($subcategory->category_id != $categoryId) {
        echo "⚠️  Subcategory's category_id ({$subcategory->category_id}) does not match product's category_id ($categoryId)\n";
    }
}

if ($product->seller_id === null) {
    echo "❌ Product seller_id is null\n";
} else {
    $user = \App\Models\User::find($product->seller_id);
    if (!$user) {
        echo "❌ Seller (user) not found for product (seller_id: {$product->seller_id})\n";
    } else {
        echo "✅ Seller found: ID {$user->id}, Email: {$user->email}\n";
    }
}

echo "\nIntegrity check complete.\n";
