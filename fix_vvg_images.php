<?php

/**
 * Fix VVG Stores Missing Images
 * 
 * This script will:
 * 1. Check which images are missing from R2
 * 2. Look for them in local storage
 * 3. Re-upload them to R2
 * 4. Verify they're accessible
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║        FIX VVG STORES MISSING PRODUCT IMAGES             ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Get VVG Stores seller
$seller = DB::table('users')->where('email', 'vvgstores@yahoo.in')->first();

if (!$seller) {
    echo "❌ VVG Stores seller not found!\n";
    exit(1);
}

echo "Found seller: {$seller->name} (ID: {$seller->id})\n\n";

// Get all products with images
$products = \App\Models\Product::where('seller_id', $seller->id)
    ->whereNotNull('image')
    ->where('image', '!=', '')
    ->get();

echo "Total products with image paths: " . $products->count() . "\n\n";

// Check which images are missing
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║  PHASE 1: CHECKING IMAGE ACCESSIBILITY                   ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$missing = [];
$accessible = [];
$checked = 0;

foreach ($products as $product) {
    $checked++;
    $imageUrl = $product->image_url;
    
    // Test accessibility
    $ch = curl_init($imageUrl);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "✅ [{$checked}/{$products->count()}] {$product->name}\n";
        $accessible[] = $product;
    } else {
        echo "❌ [{$checked}/{$products->count()}] {$product->name} - HTTP {$httpCode}\n";
        $missing[] = $product;
    }
}

echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║  RESULTS                                                  ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";
echo "✅ Accessible: " . count($accessible) . "\n";
echo "❌ Missing: " . count($missing) . "\n\n";

if (empty($missing)) {
    echo "🎉 All images are accessible! No fix needed.\n";
    exit(0);
}

// Phase 2: Try to find and upload missing images
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║  PHASE 2: ATTEMPTING TO RE-UPLOAD MISSING IMAGES         ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$fixed = 0;
$stillMissing = 0;

foreach ($missing as $product) {
    $imagePath = $product->image;
    $filename = basename($imagePath);
    
    echo "\n📦 Product: {$product->name}\n";
    echo "   Database path: {$imagePath}\n";
    echo "   Filename: {$filename}\n";
    
    // Try to find file in different locations
    $possibleLocations = [
        storage_path('app/public/' . $imagePath),
        public_path('storage/' . $imagePath),
        public_path($imagePath),
        storage_path('app/' . $imagePath),
    ];
    
    $foundFile = null;
    foreach ($possibleLocations as $location) {
        if (file_exists($location)) {
            $foundFile = $location;
            echo "   ✓ Found locally: {$location}\n";
            break;
        }
    }
    
    if (!$foundFile) {
        echo "   ✗ File not found locally - cannot re-upload\n";
        echo "   📝 Action: Seller must manually upload this image\n";
        $stillMissing++;
        continue;
    }
    
    // Re-upload to R2
    try {
        echo "   ⬆️  Uploading to R2...\n";
        
        $fileContent = file_get_contents($foundFile);
        $uploaded = Storage::disk('r2')->put($imagePath, $fileContent);
        
        if ($uploaded) {
            // Verify it's accessible now
            sleep(1); // Give R2 a moment
            
            $ch = curl_init($product->image_url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200) {
                echo "   ✅ SUCCESS! Image now accessible\n";
                echo "   🔗 URL: {$product->image_url}\n";
                $fixed++;
            } else {
                echo "   ⚠️  Uploaded but still returns HTTP {$httpCode}\n";
                $stillMissing++;
            }
        } else {
            echo "   ❌ Upload failed\n";
            $stillMissing++;
        }
        
    } catch (\Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $stillMissing++;
    }
}

echo "\n\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║  FINAL SUMMARY                                            ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";
echo "Total products checked: {$products->count()}\n";
echo "Already accessible: " . count($accessible) . "\n";
echo "Were missing: " . count($missing) . "\n";
echo "✅ Fixed by script: {$fixed}\n";
echo "❌ Still missing: {$stillMissing}\n\n";

if ($stillMissing > 0) {
    echo "⚠️  SELLER ACTION REQUIRED:\n";
    echo "   {$stillMissing} images could not be found locally\n";
    echo "   The seller must manually re-upload these images:\n\n";
    
    echo "   Steps for seller:\n";
    echo "   1. Login to seller dashboard\n";
    echo "   2. Go to Products page\n";
    echo "   3. Edit each product listed below\n";
    echo "   4. Upload the image again\n";
    echo "   5. Save\n\n";
    
    echo "   Products needing manual re-upload:\n";
    foreach ($missing as $product) {
        // Check if we already fixed it
        if ($fixed > 0) {
            $ch = curl_init($product->image_url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200) continue; // Skip if fixed
        }
        
        echo "   - {$product->name} (ID: {$product->id})\n";
        echo "     Edit URL: " . url('/seller/products/' . $product->id . '/edit') . "\n\n";
    }
}

if ($fixed > 0) {
    echo "✨ SUCCESS! {$fixed} images have been restored!\n";
    echo "   Visit the products page to verify:\n";
    echo "   " . url('/products') . "\n\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "Script completed at " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════\n\n";
