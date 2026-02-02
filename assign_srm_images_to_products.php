<?php
// This script will assign images from SRM IMG to products in order, and remove all products that do not get an image
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

$imgDir = __DIR__ . '/SRM IMG';
$imgFiles = array_values(array_filter(scandir($imgDir), function($f) {
    return preg_match('/\.(jpg|jpeg|png|webp)$/i', $f);
}));

$products = \App\Models\Product::all();
$imgCount = count($imgFiles);
$assigned = 0;
$deleted = 0;

foreach ($products as $i => $product) {
    if ($i < $imgCount) {
        $product->image = '/SRM IMG/' . $imgFiles[$i];
        $product->save();
        $assigned++;
        echo "Assigned: {$product->name} -> {$imgFiles[$i]}\n";
    } else {
        $product->delete();
        $deleted++;
        echo "Deleted: {$product->name}\n";
    }
}
echo "\nTotal assigned: $assigned\nTotal deleted: $deleted\n";
