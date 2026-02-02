<?php
// Usage: php artisan tinker --execute="require base_path('diagnose_missing_product_images.php');"

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

$missing = [];

// Check legacy product image field
$products = Product::all();
foreach ($products as $product) {
    if ($product->image && !str_contains($product->image, 'via.placeholder')) {
        $exists = Storage::disk('public')->exists($product->image) || Storage::disk('r2')->exists($product->image);
        if (!$exists) {
            $missing[] = [
                'type' => 'product',
                'id' => $product->id,
                'name' => $product->name,
                'image' => $product->image,
            ];
        }
    }
}

// Check product_images table
$images = ProductImage::all();
foreach ($images as $img) {
    if ($img->image_path && !str_contains($img->image_path, 'via.placeholder')) {
        $exists = Storage::disk('public')->exists($img->image_path) || Storage::disk('r2')->exists($img->image_path);
        if (!$exists) {
            $missing[] = [
                'type' => 'product_image',
                'id' => $img->id,
                'product_id' => $img->product_id,
                'image_path' => $img->image_path,
            ];
        }
    }
}

if (empty($missing)) {
    echo "✅ All product images are present in storage.\n";
} else {
    echo "❌ Missing images found:\n";
    foreach ($missing as $m) {
        echo json_encode($m, JSON_PRETTY_PRINT) . "\n";
    }
    echo "\nTotal missing: " . count($missing) . "\n";
}
