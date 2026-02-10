<?php
// Usage: php artisan tinker --execute="require base_path('auto_fix_and_gallery.php');"

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

$fixed = 0;
$removed = 0;

// 1. Auto-fix: Remove broken image references in products and product_images
$products = Product::all();
foreach ($products as $product) {
    if ($product->image && !str_contains($product->image, 'via.placeholder')) {
        $exists = Storage::disk('public')->exists($product->image) || Storage::disk('r2')->exists($product->image);
        if (!$exists) {
            $product->image = null;
            $product->save();
            $removed++;
        }
    }
}

$images = ProductImage::all();
foreach ($images as $img) {
    if ($img->image_path && !str_contains($img->image_path, 'via.placeholder')) {
        $exists = Storage::disk('public')->exists($img->image_path) || Storage::disk('r2')->exists($img->image_path);
        if (!$exists) {
            $img->delete();
            $removed++;
        }
    }
}

echo "✅ Fixed missing image references.\n";
echo "Removed/cleaned: $removed\n";

// 2. Gallery: List all available images for each seller
$sellers = \App\Models\User::where('role', 'seller')->get();
foreach ($sellers as $seller) {
    echo "\nSeller: {$seller->name} (ID: {$seller->id})\n";
    $gallery = [];
    $files = Storage::disk('public')->files('products');
    foreach ($files as $file) {
        if (Str::contains($file, (string)$seller->id)) {
            $gallery[] = $file;
        }
    }
    if (empty($gallery)) {
        echo "  No images found for this seller.\n";
    } else {
        echo "  Images:\n";
        foreach ($gallery as $img) {
            echo "    - $img\n";
        }
    }
}
