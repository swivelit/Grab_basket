<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$product = Product::find(2);
if (!$product) {
    echo "No product found\n";
    exit(1);
}

// Render buyer product details view if exists
$viewName = 'buyer.product-details';
if (!view()->exists($viewName)) {
    echo "View $viewName not found\n";
    exit(1);
}

$html = view($viewName, ['product' => $product])->render();

// Save to file
file_put_contents('rendered_product.html', $html);

// Find img tag for product
if (preg_match('/<img[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
    echo "Found img tag: src={$m[1]}\n";
} else {
    echo "No img tag found in rendered view\n";
}

// Print a small surrounding snippet where product->image_url appears
$pos = strpos($html, $product->image_url);
if ($pos !== false) {
    $start = max(0, $pos - 200);
    $snippet = substr($html, $start, 400);
    echo "\nSnippet around image_url:\n" . htmlspecialchars($snippet) . "\n";
} else {
    echo "product->image_url not found verbatim in rendered HTML\n";
}

echo "Rendered HTML saved to rendered_product.html\n";