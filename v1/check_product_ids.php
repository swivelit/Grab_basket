<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

$products = \App\Models\Product::take(10)->get();
echo "Product ID structure:\n";
foreach($products as $p) {
    echo "ID: {$p->id}, Unique ID: {$p->unique_id}, Name: {$p->name}\n";
}

echo "\nLocal image files in SRM IMG:\n";
$srmFiles = scandir(__DIR__ . '/SRM IMG');
foreach(array_slice($srmFiles, 2, 10) as $file) {
    if(preg_match('/\.(jpg|jpeg|png|webp)$/i', $file)) {
        echo "File: {$file}\n";
    }
}

echo "\nLocal image files in images/:\n";
$imgFiles = scandir(__DIR__ . '/images');
foreach(array_slice($imgFiles, 2, 10) as $file) {
    if(preg_match('/\.(jpg|jpeg|png|webp)$/i', $file)) {
        echo "File: {$file}\n";
    }
}