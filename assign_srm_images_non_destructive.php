<?php
// Safely assign SRM images to products without deleting any product
// - Copies images from "SRM IMG" into public/images/srm
// - Assigns copied image paths to products sequentially (by id asc)
// - Boosts discount to ensure they appear in "Deals of the Day"

use App\Models\Product;

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

$srcDir = __DIR__ . DIRECTORY_SEPARATOR . 'SRM IMG';
$destDir = public_path('images/srm');

if (!is_dir($srcDir)) {
    echo "SRM directory not found: {$srcDir}\n";
    exit(1);
}

if (!is_dir($destDir)) {
    if (!mkdir($destDir, 0775, true) && !is_dir($destDir)) {
        echo "Failed to create destination dir: {$destDir}\n";
        exit(1);
    }
}

// Collect image files from SRM IMG
$imgFiles = array_values(array_filter(scandir($srcDir), function($f) use ($srcDir) {
    return is_file($srcDir . DIRECTORY_SEPARATOR . $f) && preg_match('/\.(jpg|jpeg|png|webp)$/i', $f);
}));

if (empty($imgFiles)) {
    echo "No images found in SRM IMG folder.\n";
    exit(0);
}

// Get products sorted by id asc
$products = Product::orderBy('id', 'asc')->get();

$assigned = 0;
$copied = 0;
$boosted = 0;

foreach ($products as $i => $product) {
    if (!isset($imgFiles[$i])) break; // stop when images run out

    $filename = $imgFiles[$i];

    // Normalize filename (handle spaces and special chars by keeping original but also creating a safe copy name)
    $safeName = $filename; // keep original name; web servers can handle spaces, but we still normalize double spaces

    $srcPath = $srcDir . DIRECTORY_SEPARATOR . $filename;
    $dstPath = $destDir . DIRECTORY_SEPARATOR . $safeName;

    // Copy if not exists or different size
    $shouldCopy = !file_exists($dstPath);
    if (!$shouldCopy) {
        $shouldCopy = filesize($dstPath) !== filesize($srcPath);
    }

    if ($shouldCopy) {
        if (!@copy($srcPath, $dstPath)) {
            echo "Failed to copy: {$srcPath} -> {$dstPath}\n";
        } else {
            $copied++;
        }
    }

    // Assign product->image to use public/images path so Product::image_url resolves via /images
    $relative = 'images/srm/' . $safeName;
    $product->image = $relative;

    // Boost discount to ensure presence in Deals of the Day (which sorts by discount desc)
    // Set a minimum discount of 30% for the first 12, and at least 20% for the rest
    $current = (int)($product->discount ?? 0);
    $target = $i < 12 ? 30 : 20;
    if ($current < $target) {
        $product->discount = $target;
        $boosted++;
    }

    $product->save();
    $assigned++;

    echo sprintf("Assigned #%d: %s -> %s | discount: %s\n", $product->id, $product->name, $relative, $product->discount);
}

echo "\nSummary:\n";
echo "  Images found: " . count($imgFiles) . "\n";
echo "  Products processed: " . $products->count() . "\n";
echo "  Copied: {$copied}\n";
echo "  Assigned: {$assigned}\n";
echo "  Discounts boosted: {$boosted}\n";

// Optional: advise to clear caches
echo "\nTip: run 'php artisan optimize:clear' if images don't appear immediately.\n";
