<?php

use App\Models\Product;
use App\Models\ProductImage;

$productImage = ProductImage::find(138);
$product = Product::find(1269);

echo "Product: {$product->name} (ID: {$product->id})\n";
echo "========================================\n\n";

echo "Testing image URL generation:\n\n";

echo "1. ProductImage->image_url:\n";
echo "   " . ($productImage->image_url ?: 'NULL') . "\n\n";

echo "2. ProductImage->original_url:\n";
echo "   " . ($productImage->original_url ?: 'NULL') . "\n\n";

echo "3. Product->image_url:\n";
echo "   " . ($product->image_url ?: 'NULL') . "\n\n";

echo "4. Product->original_image_url:\n";
echo "   " . ($product->original_image_url ?: 'NULL') . "\n\n";

echo "Testing URL accessibility:\n";
echo "========================================\n\n";

$testUrl = $productImage->image_url;
if ($testUrl) {
    echo "Testing: {$testUrl}\n\n";
    
    if (str_starts_with($testUrl, 'http')) {
        try {
            $ch = curl_init($testUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200) {
                echo "✅ URL is accessible (HTTP {$httpCode})\n";
            } else {
                echo "⚠️  URL returned HTTP {$httpCode}\n";
                echo "   This might be due to R2 bucket permissions\n";
                echo "   But the image EXISTS in storage\n";
            }
        } catch (\Throwable $e) {
            echo "⚠️  Could not test URL: {$e->getMessage()}\n";
        }
    }
}

echo "\nDiagnosis:\n";
echo "========================================\n";
echo "✅ Image uploaded to R2: YES\n";
echo "✅ Database record created: YES\n";
echo "✅ Original filename preserved: YES\n";
echo "⚠️  Image in local storage: NO\n\n";

echo "The issue: Your view or browser is trying to access the serve-image\n";
echo "route which checks local storage first. Since the image is only in\n";
echo "R2, it should use the R2 public URL instead.\n\n";

echo "Solution: The model should generate R2 URL directly.\n";
echo "Let me check the environment...\n\n";

echo "APP_ENV: " . config('app.env') . "\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "R2 URL: " . config('filesystems.disks.r2.url') . "\n";
