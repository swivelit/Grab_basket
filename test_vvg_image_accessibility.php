<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== VVG STORES IMAGE ACCESSIBILITY TEST ===\n";
echo "Testing if product images are actually visible...\n\n";

// Get VVG Stores seller
$seller = DB::table('users')->where('email', 'vvgstores@yahoo.in')->first();

if (!$seller) {
    echo "❌ Seller not found!\n";
    exit(1);
}

// Get products with images
$products = \App\Models\Product::where('seller_id', $seller->id)
    ->whereNotNull('image')
    ->where('image', '!=', '')
    ->limit(10)
    ->get();

echo "Testing first 10 products with images...\n\n";

$accessible = 0;
$notAccessible = 0;
$errors = [];

foreach ($products as $product) {
    $imageUrl = $product->image_url;
    
    echo "Product: {$product->name}\n";
    echo "  Database path: {$product->image}\n";
    echo "  Generated URL: {$imageUrl}\n";
    
    // Test HTTP accessibility
    $ch = curl_init($imageUrl);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "  ✅ ACCESSIBLE (HTTP $httpCode)\n\n";
        $accessible++;
    } else {
        echo "  ❌ NOT ACCESSIBLE (HTTP $httpCode)\n";
        if ($error) {
            echo "  Error: $error\n";
        }
        echo "\n";
        $notAccessible++;
        $errors[] = [
            'product' => $product->name,
            'url' => $imageUrl,
            'code' => $httpCode,
            'error' => $error
        ];
    }
}

echo "\n=== SUMMARY ===\n";
echo "Total tested: " . ($accessible + $notAccessible) . "\n";
echo "✅ Accessible: $accessible\n";
echo "❌ Not accessible: $notAccessible\n\n";

if ($accessible > 0) {
    echo "🎉 GOOD NEWS: Images ARE accessible!\n";
    echo "The images are working correctly on R2 storage.\n";
    echo "If you can't see them in browser:\n";
    echo "  1. Hard refresh (Ctrl+Shift+R)\n";
    echo "  2. Clear browser cache\n";
    echo "  3. Check if you're viewing the correct page\n";
    echo "  4. Open browser DevTools → Network tab to see actual URLs\n\n";
}

if ($notAccessible > 0) {
    echo "⚠️ ISSUE FOUND: Some images are not accessible\n";
    echo "This could mean:\n";
    echo "  1. Images not uploaded to R2 yet\n";
    echo "  2. R2 bucket not public\n";
    echo "  3. Wrong URLs in database\n\n";
    
    if (!empty($errors)) {
        echo "Failed images:\n";
        foreach ($errors as $err) {
            echo "  - {$err['product']}: {$err['url']} (HTTP {$err['code']})\n";
        }
    }
}

// Check if ProductImages table has entries
echo "\n=== PRODUCT IMAGES TABLE CHECK ===\n";
$productIds = $products->pluck('id')->toArray();
$productImages = DB::table('product_images')
    ->whereIn('product_id', $productIds)
    ->get();

echo "Product Images records: " . $productImages->count() . "\n";

if ($productImages->count() > 0) {
    echo "Testing ProductImage model accessor...\n\n";
    
    $testProductImage = \App\Models\ProductImage::whereIn('product_id', $productIds)->first();
    if ($testProductImage) {
        echo "Sample ProductImage:\n";
        echo "  Database path: {$testProductImage->image_path}\n";
        echo "  Generated URL: {$testProductImage->image_url}\n";
        
        $ch = curl_init($testProductImage->image_url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            echo "  ✅ ACCESSIBLE via ProductImage model!\n";
        } else {
            echo "  ❌ NOT ACCESSIBLE (HTTP $httpCode)\n";
        }
    }
}

echo "\n=== END TEST ===\n";
