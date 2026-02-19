<?php

use App\Models\Product;
use Illuminate\Support\Facades\Storage;

// Run with: php artisan tinker --execute="require base_path('apply_aws_images_to_products.php');"

$extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$updated = 0;
$notFound = [];

foreach (Product::all() as $product) {
    if (empty($product->unique_id)) continue;
    $found = false;
    foreach ($extensions as $ext) {
        $awsPath = 'products/' . $product->unique_id . '.' . $ext;
        if (Storage::disk('r2')->exists($awsPath)) {
            $product->image = $awsPath;
            $product->save();
            $found = true;
            echo "Updated: {$product->id} -> $awsPath\n";
            $updated++;
            break;
        }
    }
    if (!$found) {
        $notFound[] = $product->unique_id;
    }
}
echo "\nTotal products updated: $updated\n";
if (count($notFound)) {
    echo "No AWS image found for: " . implode(", ", $notFound) . "\n";
}
