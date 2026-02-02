<?php
// test_edit_product_form.php
// Usage: php test_edit_product_form.php <product_id>

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

if ($argc < 2) {
    echo "Usage: php test_edit_product_form.php <product_id>\n";
    exit(1);
}

$productId = $argv[1];

try {
    $product = \App\Models\Product::find($productId);
    if (!$product) {
        echo "❌ Product not found (ID: $productId)\n";
        exit(1);
    }
    echo "✅ Product found: ID {$product->id}, Name: {$product->name}\n";
    $sellerId = $product->seller_id;
    echo "Product seller_id: $sellerId\n";
    $user = \App\Models\User::find($sellerId);
    if (!$user) {
        echo "❌ Seller (user) not found for product.\n";
        exit(1);
    }
    echo "Logging in as user ID: {$user->id}, email: {$user->email}\n";
    Auth::login($user);
    $controller = app(\App\Http\Controllers\SellerController::class);
    try {
        $response = $controller->editProduct($product);
        if (method_exists($response, 'getContent')) {
            $content = $response->getContent();
            if (strpos($content, 'Edit Product') !== false || strpos($content, 'edit-product') !== false) {
                echo "✅ Edit product form rendered successfully.\n";
            } else {
                echo "⚠️  Edit product form did not render expected content.\n";
            }
        } elseif (method_exists($response, 'getTargetUrl')) {
            echo "⚠️  Controller returned a redirect to: " . $response->getTargetUrl() . "\n";
            if (session('error')) {
                echo "Session error message: " . session('error') . "\n";
            }
        } else {
            echo "⚠️  Controller did not return a view or redirect response.\n";
        }
    } catch (\Throwable $e) {
        echo "❌ Exception thrown by controller: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
} catch (Throwable $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
