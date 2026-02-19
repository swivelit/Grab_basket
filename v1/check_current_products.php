<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

echo "Current Products in Database:\n";
echo "=============================\n";

$products = \App\Models\Product::with('category')->take(15)->get();
foreach($products as $p) {
    $categoryName = $p->category ? $p->category->name : 'No Category';
    echo "ID: {$p->id} | Unique: {$p->unique_id} | Category: {$categoryName} | Name: {$p->name}\n";
}

echo "\nCategories in Database:\n";
echo "======================\n";
$categories = \App\Models\Category::all();
foreach($categories as $cat) {
    echo "Category ID: {$cat->id} | Name: {$cat->name}\n";
}