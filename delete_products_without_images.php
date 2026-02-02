<?php
// This script will delete all products that do not have an image set
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

$products = \App\Models\Product::all();
$deleted = 0;
foreach ($products as $product) {
    if (empty($product->image)) {
        $product->delete();
        echo "Deleted: {$product->name}\n";
        $deleted++;
    }
}
echo "\nTotal products deleted: $deleted\n";
