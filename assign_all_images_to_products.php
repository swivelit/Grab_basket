<?php
// This script will assign images from SRM IMG and images/ to products in order, and remove all products that do not get an image
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();


// Gather all images from both directories
$imgDirs = [
    __DIR__ . '/SRM IMG',
    __DIR__ . '/images',
];
$imgFiles = [];
foreach ($imgDirs as $dir) {
    foreach (array_filter(scandir($dir), function($f) use ($dir) {
        return preg_match('/\.(jpg|jpeg|png|webp)$/i', $f) && is_file($dir . '/' . $f);
    }) as $f) {
        $imgFiles[] = [
            'path' => $dir,
            'file' => $f,
            'web' => (basename($dir) === 'images' ? '/images/' : '/SRM IMG/') . $f,
            'basename' => strtolower(pathinfo($f, PATHINFO_FILENAME)),
        ];
    }
}

// Helper: normalize string for matching
function normalize($str) {
    return strtolower(preg_replace('/[^a-z0-9]/', '', $str));
}

$products = \App\Models\Product::all();
$assigned = 0;
$deleted = 0;
$usedImages = [];

// 1. Try to assign by best name match
foreach ($products as $product) {
    $productName = normalize($product->name);
    $bestIdx = null;
    $bestScore = 0;
    foreach ($imgFiles as $idx => $img) {
        if (in_array($idx, $usedImages)) continue;
        $imgName = $img['basename'];
        similar_text($productName, $imgName, $percent);
        if ($percent > $bestScore) {
            $bestScore = $percent;
            $bestIdx = $idx;
        }
    }
    // If a good match (over 60% similarity), assign it
    if ($bestIdx !== null && $bestScore > 60) {
        $product->image = $imgFiles[$bestIdx]['web'];
        $product->save();
        $usedImages[] = $bestIdx;
        $assigned++;
        echo "[MATCH] {$product->name} -> {$imgFiles[$bestIdx]['web']}\n";
    }
}

// 2. Assign remaining images in order to products without image
$imgLeft = array_diff(array_keys($imgFiles), $usedImages);
$productsLeft = \App\Models\Product::whereNull('image')->get();
foreach ($productsLeft as $product) {
    $imgIdx = array_shift($imgLeft);
    if ($imgIdx !== null) {
        $product->image = $imgFiles[$imgIdx]['web'];
        $product->save();
        $usedImages[] = $imgIdx;
        $assigned++;
        echo "[ORDER] {$product->name} -> {$imgFiles[$imgIdx]['web']}\n";
    }
}

// 3. Delete any products that still have no image
$productsNoImage = \App\Models\Product::whereNull('image')->get();
foreach ($productsNoImage as $product) {
    $product->delete();
    $deleted++;
    echo "[DELETED] {$product->name}\n";
}

echo "\nTotal assigned: $assigned\nTotal deleted: $deleted\n";
