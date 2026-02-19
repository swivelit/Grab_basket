<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

echo "\n=== VVG STORES PRODUCT INVESTIGATION ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Find VVG Stores seller
echo "1. FINDING VVG STORES SELLER:\n";
$seller = DB::table('users')
    ->where('email', 'vvgstores@yahoo.in')
    ->orWhere('name', 'LIKE', '%vvg%')
    ->first();

if (!$seller) {
    echo "   ❌ VVG Stores seller not found!\n";
    exit(1);
}

echo "   ✓ Found seller:\n";
echo "   ID: {$seller->id}\n";
echo "   Name: {$seller->name}\n";
echo "   Email: {$seller->email}\n";
echo "   Role: {$seller->role}\n";
echo "   Created: {$seller->created_at}\n";
echo "   Updated: {$seller->updated_at}\n\n";

// Check seller profile in sellers table
echo "2. SELLER PROFILE:\n";
$sellerProfile = DB::table('sellers')->where('email', $seller->email)->first();
if ($sellerProfile) {
    echo "   ✓ Seller profile exists:\n";
    echo "   Store Name: " . ($sellerProfile->store_name ?? 'Not set') . "\n";
    echo "   GST Number: " . ($sellerProfile->gst_number ?? 'Not set') . "\n";
    echo "   Store Address: " . ($sellerProfile->store_address ?? 'Not set') . "\n";
    echo "   Contact: " . ($sellerProfile->store_contact ?? 'Not set') . "\n\n";
} else {
    echo "   ⚠ No seller profile found in sellers table\n\n";
}

// Check products
echo "3. PRODUCTS ANALYSIS:\n";
$allProducts = DB::table('products')
    ->where('seller_id', $seller->id)
    ->get(['id', 'name', 'category_id', 'subcategory_id', 'price', 'image', 'created_at', 'updated_at']);

echo "   Total products: " . $allProducts->count() . "\n\n";

if ($allProducts->isEmpty()) {
    echo "   ❌ NO PRODUCTS FOUND for this seller!\n";
    echo "   Possible reasons:\n";
    echo "   - Products not created yet\n";
    echo "   - Wrong seller_id association\n";
    echo "   - Products were deleted\n\n";
} else {
    // Products with images
    $productsWithImages = $allProducts->filter(function($p) {
        return !empty($p->image);
    });
    
    // Products without images
    $productsWithoutImages = $allProducts->filter(function($p) {
        return empty($p->image);
    });
    
    echo "   Products WITH images: " . $productsWithImages->count() . "\n";
    echo "   Products WITHOUT images: " . $productsWithoutImages->count() . "\n\n";
    
    if ($productsWithImages->count() > 0) {
        echo "4. PRODUCTS WITH IMAGES:\n";
        foreach ($productsWithImages as $product) {
            echo "   Product: {$product->name}\n";
            echo "   ID: {$product->id}\n";
            echo "   Image: {$product->image}\n";
            echo "   Price: ₹{$product->price}\n";
            echo "   Created: {$product->created_at}\n";
            
            // Check if image is accessible
            if (filter_var($product->image, FILTER_VALIDATE_URL)) {
                $ch = curl_init($product->image);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode == 200) {
                    echo "   ✓ Image accessible (HTTP $httpCode)\n";
                } else {
                    echo "   ✗ Image NOT accessible (HTTP $httpCode)\n";
                }
            } else {
                echo "   ⚠ Not a valid URL\n";
            }
            echo "\n";
        }
    }
    
    if ($productsWithoutImages->count() > 0) {
        echo "5. PRODUCTS WITHOUT IMAGES:\n";
        $limit = min(10, $productsWithoutImages->count());
        echo "   Showing first $limit products:\n\n";
        
        foreach ($productsWithoutImages->take($limit) as $product) {
            echo "   Product: {$product->name}\n";
            echo "   ID: {$product->id}\n";
            echo "   Price: ₹{$product->price}\n";
            echo "   Created: {$product->created_at}\n";
            echo "   ❌ No image\n\n";
        }
    }
}

// Check product images table
echo "6. PRODUCT IMAGES TABLE:\n";
$productIds = $allProducts->pluck('id')->toArray();
if (!empty($productIds)) {
    $productImages = DB::table('product_images')
        ->whereIn('product_id', $productIds)
        ->get();
    
    echo "   Total images in product_images table: " . $productImages->count() . "\n";
    
    if ($productImages->count() > 0) {
        $grouped = $productImages->groupBy('product_id');
        echo "   Products with gallery images: " . $grouped->count() . "\n\n";
        
        foreach ($grouped->take(5) as $productId => $images) {
            $product = $allProducts->firstWhere('id', $productId);
            echo "   Product: " . ($product ? $product->name : "ID $productId") . "\n";
            echo "   Images: " . $images->count() . "\n";
            foreach ($images as $img) {
                echo "     - " . ($img->is_primary ? '⭐ ' : '  ') . $img->image_path . "\n";
            }
            echo "\n";
        }
    }
} else {
    echo "   No products to check\n\n";
}

// Check recent uploads
echo "7. RECENT UPLOAD ATTEMPTS:\n";
$recentProducts = DB::table('products')
    ->where('seller_id', $seller->id)
    ->whereDate('created_at', '>=', date('Y-m-d', strtotime('-7 days')))
    ->orderBy('created_at', 'desc')
    ->get(['id', 'name', 'image', 'created_at']);

if ($recentProducts->isEmpty()) {
    echo "   No products created in the last 7 days\n\n";
} else {
    echo "   Products created in last 7 days: " . $recentProducts->count() . "\n";
    foreach ($recentProducts as $product) {
        echo "   - {$product->name} (Created: {$product->created_at})\n";
        echo "     Image: " . ($product->image ?: '❌ None') . "\n";
    }
    echo "\n";
}

// Check categories used
echo "8. CATEGORIES ANALYSIS:\n";
$categoryCounts = DB::table('products')
    ->select('category_id', DB::raw('count(*) as count'))
    ->where('seller_id', $seller->id)
    ->groupBy('category_id')
    ->get();

if ($categoryCounts->isEmpty()) {
    echo "   No products in any category\n\n";
} else {
    echo "   Products by category:\n";
    foreach ($categoryCounts as $cat) {
        $category = DB::table('categories')->find($cat->category_id);
        $categoryName = $category ? $category->name : "Unknown (ID: {$cat->category_id})";
        echo "   - {$categoryName}: {$cat->count} products\n";
    }
    echo "\n";
}

// Storage check
echo "9. STORAGE CAPABILITY TEST:\n";
try {
    $testFile = 'test_vvg_' . time() . '.txt';
    Storage::disk('r2')->put($testFile, 'Test file for VVG Stores at ' . date('Y-m-d H:i:s'));
    
    if (Storage::disk('r2')->exists($testFile)) {
        echo "   ✓ Can write to R2 storage\n";
        $url = 'https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud/' . $testFile;
        echo "   Test URL: $url\n";
        
        // Check accessibility
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            echo "   ✓ File publicly accessible\n";
        } else {
            echo "   ⚠ File returned HTTP $httpCode\n";
        }
        
        Storage::disk('r2')->delete($testFile);
        echo "   ✓ Test file deleted\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Storage test failed: " . $e->getMessage() . "\n";
}

echo "\n10. RECOMMENDATIONS:\n";

if ($allProducts->isEmpty()) {
    echo "   ⚠ MAIN ISSUE: No products exist for this seller\n";
    echo "   → Seller needs to create products first\n";
    echo "   → Check if seller has access to add products\n";
    echo "   → Verify seller is logged in correctly\n\n";
} else {
    $noImagePercent = ($productsWithoutImages->count() / $allProducts->count()) * 100;
    
    if ($productsWithoutImages->count() > 0) {
        echo "   ⚠ {$productsWithoutImages->count()} products ({$noImagePercent}%) have no images\n";
        echo "   → Seller should add images to existing products\n";
        echo "   → Check bulk image upload feature\n";
        echo "   → Verify image upload functionality works\n\n";
    }
    
    if ($productsWithImages->count() > 0) {
        echo "   ✓ {$productsWithImages->count()} products already have images\n";
        echo "   → System is working for some products\n";
        echo "   → Check what's different about products without images\n\n";
    }
}

echo "=== END INVESTIGATION ===\n";
