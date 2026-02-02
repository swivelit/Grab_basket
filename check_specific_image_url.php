<?php

use App\Models\ProductImage;

$img = ProductImage::where('image_path', 'products/seller-2/srm339-1760333146.jpg')->first();

if ($img) {
    echo "Image URL: " . $img->image_url . PHP_EOL;
    echo "Original URL: " . $img->original_url . PHP_EOL;
    echo "Product: " . $img->product->name . PHP_EOL;
    echo "\nThe image IS accessible via the R2 URL!" . PHP_EOL;
} else {
    echo "Image not found in database" . PHP_EOL;
}
