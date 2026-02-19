<?php

/**
 * Comprehensive Product Image Logic Diagnostic Script
 * Checks all aspects of image handling: models, controllers, routes, views, storage
 */

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "PRODUCT IMAGE LOGIC DIAGNOSTIC\n";
echo "========================================\n\n";

// 1. CHECK MODELS
echo "1. MODEL LOGIC CHECK\n";
echo "-------------------\n";

$testProduct = Product::with('productImages')->first();
if ($testProduct) {
    echo "Testing with Product ID: {$testProduct->id} - {$testProduct->name}\n\n";
    
    // Test image_url accessor
    echo "✓ image_url: " . ($testProduct->image_url ?: 'NULL (no placeholder)') . "\n";
    echo "✓ original_image_url: " . ($testProduct->original_image_url ?: 'NULL') . "\n";
    echo "✓ Legacy image field: " . ($testProduct->image ?: 'NULL') . "\n";
    echo "✓ ProductImages count: {$testProduct->productImages->count()}\n";
    
    if ($testProduct->productImages->count() > 0) {
        $firstImage = $testProduct->productImages->first();
        echo "✓ First ProductImage path: {$firstImage->image_path}\n";
        echo "✓ First ProductImage URL: " . ($firstImage->image_url ?: 'NULL') . "\n";
        echo "✓ First ProductImage original_url: " . ($firstImage->original_url ?: 'NULL') . "\n";
    }
    
    // Check for placeholder references
    $hasPlaceholder = false;
    if ($testProduct->image_url && str_contains($testProduct->image_url, 'placeholder')) {
        echo "❌ FOUND PLACEHOLDER in image_url!\n";
        $hasPlaceholder = true;
    }
    if ($testProduct->original_image_url && str_contains($testProduct->original_image_url, 'placeholder')) {
        echo "❌ FOUND PLACEHOLDER in original_image_url!\n";
        $hasPlaceholder = true;
    }
    
    if (!$hasPlaceholder) {
        echo "✓ No placeholder URLs in model accessors\n";
    }
} else {
    echo "⚠ No products found in database\n";
}

echo "\n";

// 2. CHECK DATABASE
echo "2. DATABASE CHECK\n";
echo "-------------------\n";

// Check for placeholder URLs in database
$placeholderProducts = Product::where('image', 'LIKE', '%placeholder%')->get();
echo "Products with placeholder in 'image' field: {$placeholderProducts->count()}\n";

$placeholderImages = ProductImage::where('image_path', 'LIKE', '%placeholder%')->get();
echo "ProductImages with placeholder in 'image_path': {$placeholderImages->count()}\n";

// Check seller-specific image paths
$sellerImages = ProductImage::where('image_path', 'LIKE', 'products/seller-%')->get();
echo "ProductImages with seller-specific paths: {$sellerImages->count()}\n";

// Check for original filenames preserved
$withOriginalName = ProductImage::whereNotNull('original_name')->get();
echo "ProductImages with original_name preserved: {$withOriginalName->count()}\n";

echo "\n";

// 3. CHECK STORAGE
echo "3. STORAGE CHECK\n";
echo "-------------------\n";

if ($testProduct && $testProduct->productImages->count() > 0) {
    $firstImage = $testProduct->productImages->first();
    $imagePath = $firstImage->image_path;
    
    echo "Testing image path: {$imagePath}\n";
    
    // Check public disk
    try {
        $existsPublic = Storage::disk('public')->exists($imagePath);
        echo "✓ Exists in public disk: " . ($existsPublic ? 'YES' : 'NO') . "\n";
        
        if ($existsPublic) {
            $fullPath = Storage::disk('public')->path($imagePath);
            echo "  Full path: {$fullPath}\n";
            echo "  File size: " . filesize($fullPath) . " bytes\n";
        }
    } catch (\Throwable $e) {
        echo "❌ Error checking public disk: {$e->getMessage()}\n";
    }
    
    // Check R2 disk
    try {
        $existsR2 = Storage::disk('r2')->exists($imagePath);
        echo "✓ Exists in R2 disk: " . ($existsR2 ? 'YES' : 'NO') . "\n";
    } catch (\Throwable $e) {
        echo "❌ Error checking R2 disk: {$e->getMessage()}\n";
    }
}

echo "\n";

// 4. CHECK SERVE-IMAGE ROUTE
echo "4. SERVE-IMAGE ROUTE CHECK\n";
echo "-------------------\n";

// Read the route file to check for placeholder references
$routeFile = base_path('routes/web.php');
$routeContent = file_get_contents($routeFile);

if (str_contains($routeContent, 'placeholder.com')) {
    echo "❌ FOUND placeholder.com reference in routes/web.php!\n";
} else {
    echo "✓ No placeholder.com reference in routes/web.php\n";
}

if (str_contains($routeContent, "response()->json(['error' => 'Image not found'")) {
    echo "✓ Returns 404 JSON when image not found (no redirect)\n";
} else {
    echo "⚠ Route fallback might not be returning proper 404\n";
}

echo "\n";

// 5. CHECK CONTROLLER LOGIC
echo "5. CONTROLLER LOGIC CHECK\n";
echo "-------------------\n";

$controllerFile = app_path('Http/Controllers/SellerController.php');
$controllerContent = file_get_contents($controllerFile);

// Check for seller-specific folders
if (str_contains($controllerContent, "products/seller-")) {
    echo "✓ Uses seller-specific folders (products/seller-{id}/)\n";
} else {
    echo "⚠ Not using seller-specific folders\n";
}

// Check for original filename preservation
if (str_contains($controllerContent, "original_name") && str_contains($controllerContent, "getClientOriginalName")) {
    echo "✓ Preserves original filenames\n";
} else {
    echo "⚠ Original filenames might not be preserved\n";
}

// Check for old image deletion
if (str_contains($controllerContent, "foreach (\$product->productImages as \$productImage)") 
    && str_contains($controllerContent, "->delete()")) {
    echo "✓ Deletes old images before uploading new ones\n";
} else {
    echo "⚠ Old images might not be deleted\n";
}

// Check for dual storage
if (str_contains($controllerContent, "storeAs(\$folder, \$filename, 'r2')") 
    && str_contains($controllerContent, "storeAs(\$folder, \$filename, 'public')")) {
    echo "✓ Uses dual storage (R2 + public)\n";
} else {
    echo "⚠ Dual storage might not be configured\n";
}

echo "\n";

// 6. SAMPLE PRODUCTS WITH IMAGES
echo "6. SAMPLE PRODUCTS WITH IMAGES\n";
echo "-------------------\n";

$productsWithImages = Product::with('productImages')
    ->whereHas('productImages')
    ->take(5)
    ->get();

foreach ($productsWithImages as $product) {
    echo "\nProduct #{$product->id}: {$product->name}\n";
    echo "  Image URL: " . ($product->image_url ?: 'NULL') . "\n";
    echo "  Legacy field: " . ($product->image ?: 'NULL') . "\n";
    echo "  Gallery images: {$product->productImages->count()}\n";
    
    foreach ($product->productImages->take(2) as $img) {
        echo "    - {$img->image_path} (Primary: " . ($img->is_primary ? 'YES' : 'NO') . ")\n";
    }
}

echo "\n";

// 7. CONFIGURATION CHECK
echo "7. CONFIGURATION CHECK\n";
echo "-------------------\n";

echo "APP_URL: " . config('app.url') . "\n";
echo "APP_ENV: " . config('app.env') . "\n";
echo "Public disk root: " . config('filesystems.disks.public.root') . "\n";
echo "R2 configured: " . (config('filesystems.disks.r2.key') ? 'YES' : 'NO') . "\n";
echo "R2 URL: " . (config('filesystems.disks.r2.url') ?: 'Not set') . "\n";

echo "\n";

// 8. RECOMMENDATIONS
echo "8. RECOMMENDATIONS\n";
echo "-------------------\n";

$issues = [];

if ($placeholderProducts->count() > 0) {
    $issues[] = "Remove placeholder URLs from products.image field";
}

if ($placeholderImages->count() > 0) {
    $issues[] = "Remove placeholder URLs from product_images.image_path field";
}

if ($sellerImages->count() === 0 && ProductImage::count() > 0) {
    $issues[] = "Consider migrating images to seller-specific folders";
}

if ($withOriginalName->count() === 0 && ProductImage::count() > 0) {
    $issues[] = "Original filenames are not being preserved";
}

if (empty($issues)) {
    echo "✓ All checks passed! Image logic is working correctly.\n";
} else {
    echo "⚠ Issues found:\n";
    foreach ($issues as $issue) {
        echo "  - {$issue}\n";
    }
}

echo "\n========================================\n";
echo "DIAGNOSTIC COMPLETE\n";
echo "========================================\n";
