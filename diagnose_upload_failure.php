<?php

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

echo "Checking Add/Edit Product Image Upload Issues\n";
echo "========================================\n\n";

// Check storage disk configuration
echo "1. STORAGE CONFIGURATION\n";
echo "-------------------\n";
echo "Public disk root: " . config('filesystems.disks.public.root') . "\n";
echo "R2 configured: " . (config('filesystems.disks.r2.key') ? 'YES' : 'NO') . "\n";
echo "R2 URL: " . (config('filesystems.disks.r2.url') ?: 'Not set') . "\n\n";

// Check if storage directories exist
echo "2. STORAGE DIRECTORIES\n";
echo "-------------------\n";

$publicRoot = storage_path('app/public');
$productsDir = storage_path('app/public/products');

echo "Public root exists: " . (file_exists($publicRoot) ? 'YES' : 'NO') . "\n";
echo "Products folder exists: " . (file_exists($productsDir) ? 'YES' : 'NO') . "\n";

if (file_exists($publicRoot)) {
    echo "Public root writable: " . (is_writable($publicRoot) ? 'YES' : 'NO') . "\n";
}

if (file_exists($productsDir)) {
    echo "Products folder writable: " . (is_writable($productsDir) ? 'YES' : 'NO') . "\n";
    
    // List seller folders
    $sellerFolders = glob($productsDir . '/seller-*');
    echo "Seller folders found: " . count($sellerFolders) . "\n";
    foreach ($sellerFolders as $folder) {
        $folderName = basename($folder);
        $writable = is_writable($folder);
        echo "  - {$folderName}: " . ($writable ? 'WRITABLE' : 'READ-ONLY') . "\n";
    }
} else {
    echo "⚠️ Products folder does NOT exist!\n";
}

echo "\n";

// Check storage symlink
echo "3. STORAGE SYMLINK\n";
echo "-------------------\n";
$symlinkPath = public_path('storage');
echo "Symlink path: {$symlinkPath}\n";
echo "Symlink exists: " . (file_exists($symlinkPath) ? 'YES' : 'NO') . "\n";
if (file_exists($symlinkPath)) {
    echo "Is symlink: " . (is_link($symlinkPath) ? 'YES' : 'NO') . "\n";
    if (is_link($symlinkPath)) {
        echo "Points to: " . readlink($symlinkPath) . "\n";
    }
}

echo "\n";

// Check recent upload failures
echo "4. RECENT UPLOAD ATTEMPTS\n";
echo "-------------------\n";

$recentProducts = Product::orderBy('created_at', 'desc')->take(5)->get();
foreach ($recentProducts as $product) {
    echo "Product #{$product->id}: {$product->name}\n";
    echo "  Created: {$product->created_at}\n";
    echo "  Legacy image field: " . ($product->image ?: 'NULL') . "\n";
    echo "  ProductImages count: {$product->productImages->count()}\n";
    
    if ($product->productImages->count() > 0) {
        foreach ($product->productImages as $img) {
            echo "    - {$img->image_path}\n";
            echo "      Public: " . (Storage::disk('public')->exists($img->image_path) ? 'YES' : 'NO') . "\n";
            echo "      R2: " . (Storage::disk('r2')->exists($img->image_path) ? 'YES' : 'NO') . "\n";
        }
    }
    echo "\n";
}

// Check Laravel logs for upload errors
echo "5. RECENT UPLOAD ERRORS IN LOGS\n";
echo "-------------------\n";

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logLines = file($logFile);
    $recentErrors = [];
    
    foreach (array_reverse($logLines) as $line) {
        if (str_contains($line, 'upload') && 
            (str_contains($line, 'ERROR') || str_contains($line, 'failed') || str_contains($line, 'error'))) {
            $recentErrors[] = trim($line);
            if (count($recentErrors) >= 10) break;
        }
    }
    
    if (count($recentErrors) > 0) {
        echo "Found " . count($recentErrors) . " recent upload errors:\n\n";
        foreach ($recentErrors as $error) {
            echo substr($error, 0, 200) . "...\n\n";
        }
    } else {
        echo "No recent upload errors found\n";
    }
} else {
    echo "Log file not found\n";
}

echo "\n";

echo "6. RECOMMENDATIONS\n";
echo "-------------------\n";

$issues = [];

if (!file_exists($productsDir)) {
    $issues[] = "Create products folder: storage/app/public/products/";
}

if (!file_exists($symlinkPath)) {
    $issues[] = "Create storage symlink: php artisan storage:link";
}

if (file_exists($productsDir) && !is_writable($productsDir)) {
    $issues[] = "Fix permissions: chmod -R 775 storage/app/public/";
}

if (count($issues) > 0) {
    echo "⚠️ Issues found:\n";
    foreach ($issues as $issue) {
        echo "  • {$issue}\n";
    }
} else {
    echo "✅ Storage configuration looks good\n";
    echo "Issue might be in controller code or validation\n";
}
