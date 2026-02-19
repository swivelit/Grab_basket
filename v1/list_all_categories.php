<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Product;

echo "=== ALL CATEGORIES ===\n\n";

$categories = Category::orderBy('id')->get();

if ($categories->isEmpty()) {
    echo "❌ No categories found in database\n";
    exit;
}

echo "Total categories: " . $categories->count() . "\n\n";

foreach ($categories as $cat) {
    $productCount = Product::where('category_id', $cat->id)->count();
    echo "ID: {$cat->id}\n";
    echo "Name: {$cat->name}\n";
    echo "Slug: {$cat->slug}\n";
    echo "Products: {$productCount}\n";
    echo "URL: https://grabbaskets.com/buyer/category/{$cat->id}\n";
    echo str_repeat('-', 60) . "\n";
}
