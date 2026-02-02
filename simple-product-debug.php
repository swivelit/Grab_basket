<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$product = Product::with('category', 'subcategory', 'seller')->find(2);
if (!$product) {
    echo "No product found\n";
    exit(1);
}

echo "Product: {$product->name}\n";
echo "Seller: " . ($product->seller->name ?? 'NULL') . "\n";
echo "Category: " . ($product->category->name ?? 'NULL') . "\n";
echo "Image: {$product->image}\n";
echo "Image URL: {$product->image_url}\n";

// Simple HTML template
$html = <<<HTML
<!DOCTYPE html>
<html>
<head><title>Product Test</title></head>
<body>
    <h1>{$product->name}</h1>
    <p>Image URL: {$product->image_url}</p>
    <img src="{$product->image_url}" alt="{$product->name}" style="max-width: 300px;">
</body>
</html>
HTML;

file_put_contents('simple_product.html', $html);

echo "\nSimple HTML with img tag created\n";
echo "Image tag will be:\n";
echo "<img src=\"{$product->image_url}\" alt=\"{$product->name}\" style=\"max-width: 300px;\">\n";