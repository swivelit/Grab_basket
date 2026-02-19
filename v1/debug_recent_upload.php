<?php

/**
 * Debug Recent Image Upload Issue
 */

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

echo "========================================\n";
echo "DEBUG RECENT IMAGE UPLOAD\n";
echo "========================================\n\n";

$imagePath = 'products/seller-2/srm339-1760333146.jpg';

echo "1. STORAGE CHECK\n";
echo "-------------------\n";
echo "Image path: {$imagePath}\n\n";

// Check public disk
try {
    $publicExists = Storage::disk('public')->exists($imagePath);
    echo "Public disk exists: " . ($publicExists ? 'YES' : 'NO') . "\n";
    
    if ($publicExists) {
        $fullPath = Storage::disk('public')->path($imagePath);
        echo "  Full path: {$fullPath}\n";
        echo "  File exists on disk: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
        if (file_exists($fullPath)) {
            echo "  File size: " . filesize($fullPath) . " bytes\n";
        }
    } else {
        // Try to find if it's in a different path
        $publicRoot = Storage::disk('public')->path('');
        echo "  Public disk root: {$publicRoot}\n";
        
        // List seller-2 folder
        $sellerFolder = 'products/seller-2';
        if (Storage::disk('public')->exists($sellerFolder)) {
            echo "  Seller-2 folder exists\n";
            $files = Storage::disk('public')->files($sellerFolder);
            echo "  Files in seller-2: " . count($files) . "\n";
            foreach ($files as $file) {
                if (str_contains($file, 'srm339')) {
                    echo "    FOUND: {$file}\n";
                }
            }
        } else {
            echo "  Seller-2 folder does NOT exist\n";
        }
    }
} catch (\Throwable $e) {
    echo "  Error checking public disk: {$e->getMessage()}\n";
}

echo "\n";

// Check R2 disk
try {
    $r2Exists = Storage::disk('r2')->exists($imagePath);
    echo "R2 disk exists: " . ($r2Exists ? 'YES' : 'NO') . "\n";
    
    if (!$r2Exists) {
        // Try to list R2 files
        $sellerFolder = 'products/seller-2';
        try {
            $files = Storage::disk('r2')->files($sellerFolder);
            echo "  Files in R2 seller-2: " . count($files) . "\n";
            foreach ($files as $file) {
                if (str_contains($file, 'srm339')) {
                    echo "    FOUND in R2: {$file}\n";
                }
            }
        } catch (\Throwable $e2) {
            echo "  Could not list R2 files: {$e2->getMessage()}\n";
        }
    }
} catch (\Throwable $e) {
    echo "  Error checking R2 disk: {$e->getMessage()}\n";
}

echo "\n";

echo "2. DATABASE CHECK\n";
echo "-------------------\n";

// Check ProductImage table
$productImage = ProductImage::where('image_path', $imagePath)->first();
if ($productImage) {
    echo "ProductImage record FOUND:\n";
    echo "  ID: {$productImage->id}\n";
    echo "  Product ID: {$productImage->product_id}\n";
    echo "  Image path: {$productImage->image_path}\n";
    echo "  Original name: {$productImage->original_name}\n";
    echo "  Created: {$productImage->created_at}\n";
    echo "  Is primary: " . ($productImage->is_primary ? 'YES' : 'NO') . "\n";
} else {
    echo "ProductImage record NOT FOUND for exact path\n";
    
    // Try to find similar
    $similar = ProductImage::where('image_path', 'LIKE', '%srm339%')->latest()->get();
    if ($similar->count() > 0) {
        echo "\nFound similar images:\n";
        foreach ($similar as $img) {
            echo "  - {$img->image_path} (Product: {$img->product_id}, Created: {$img->created_at})\n";
        }
    } else {
        echo "No similar images found with 'srm339'\n";
    }
}

echo "\n";

// Check recent uploads (last 5 minutes)
echo "3. RECENT UPLOADS (Last 5 minutes)\n";
echo "-------------------\n";

$recentImages = ProductImage::where('created_at', '>=', now()->subMinutes(5))
    ->orderBy('created_at', 'desc')
    ->get();

if ($recentImages->count() > 0) {
    echo "Found {$recentImages->count()} recent uploads:\n";
    foreach ($recentImages as $img) {
        echo "  - {$img->image_path}\n";
        echo "    Product: {$img->product_id}, Created: {$img->created_at}\n";
        echo "    Public exists: " . (Storage::disk('public')->exists($img->image_path) ? 'YES' : 'NO') . "\n";
        echo "    R2 exists: " . (Storage::disk('r2')->exists($img->image_path) ? 'YES' : 'NO') . "\n";
        echo "\n";
    }
} else {
    echo "No recent uploads in last 5 minutes\n";
}

echo "\n";

echo "4. RECOMMENDATIONS\n";
echo "-------------------\n";

if (!$publicExists && !$r2Exists) {
    echo "⚠️ IMAGE FILE MISSING from both storage disks!\n";
    echo "\nPossible causes:\n";
    echo "1. Upload failed but database record was created\n";
    echo "2. File was uploaded with different name than expected\n";
    echo "3. Storage permissions issue\n";
    echo "4. File was deleted after upload\n";
    echo "\nSolutions:\n";
    echo "1. Re-upload the image\n";
    echo "2. Check storage/logs/laravel.log for upload errors\n";
    echo "3. Verify storage disk configuration\n";
} elseif ($publicExists && !$r2Exists) {
    echo "⚠️ Image exists in public disk but NOT in R2\n";
    echo "R2 upload may have failed. Check logs for R2 errors.\n";
} elseif (!$publicExists && $r2Exists) {
    echo "✓ Image exists in R2 but not in public disk\n";
    echo "This is acceptable - R2 is the primary storage.\n";
} else {
    echo "✓ Image exists in both storage disks\n";
}

echo "\n========================================\n";
echo "DEBUG COMPLETE\n";
echo "========================================\n";
