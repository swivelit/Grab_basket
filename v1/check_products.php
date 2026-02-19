<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

echo "Sample products with missing images:\n";
echo "ID | Name | Image | Unique ID\n";
echo "----------------------------------------\n";

$products = App\Models\Product::whereNull('image')
    ->orWhere('image', '')
    ->orWhere('image', 'like', '%not found%')
    ->take(10)
    ->get(['id', 'name', 'image', 'unique_id']);

foreach($products as $p) {
    echo $p->id . ' | ' . substr($p->name, 0, 25) . ' | ' . $p->image . ' | ' . $p->unique_id . "\n";
}

echo "\nSample products with images:\n";
echo "ID | Name | Image | Unique ID\n";
echo "----------------------------------------\n";

$withImages = App\Models\Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->where('image', 'not like', '%not found%')
    ->take(5)
    ->get(['id', 'name', 'image', 'unique_id']);

foreach($withImages as $p) {
    echo $p->id . ' | ' . substr($p->name, 0, 25) . ' | ' . $p->image . ' | ' . $p->unique_id . "\n";
}
?>