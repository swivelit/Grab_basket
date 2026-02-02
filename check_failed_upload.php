<?php

use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

echo "Checking recent image upload: srm339-1760334028.jpg\n";
echo "========================================\n\n";

$imagePath = 'products/seller-2/srm339-1760334028.jpg';

// Check database
$productImage = ProductImage::where('image_path', $imagePath)->first();

if ($productImage) {
    echo "✅ Found in database:\n";
    echo "   ID: {$productImage->id}\n";
    echo "   Product ID: {$productImage->product_id}\n";
    echo "   Path: {$productImage->image_path}\n";
    echo "   Original name: {$productImage->original_name}\n";
    echo "   Created: {$productImage->created_at}\n";
    echo "   Primary: " . ($productImage->is_primary ? 'YES' : 'NO') . "\n\n";
} else {
    echo "❌ NOT found in database\n\n";
    
    // Check for similar
    $similar = ProductImage::where('image_path', 'LIKE', '%srm339%')
        ->orderBy('created_at', 'desc')
        ->get();
    
    if ($similar->count() > 0) {
        echo "Found similar images:\n";
        foreach ($similar as $img) {
            echo "  - {$img->image_path} (Created: {$img->created_at})\n";
        }
    }
    echo "\n";
}

// Check storage
echo "Storage check:\n";
$publicExists = Storage::disk('public')->exists($imagePath);
$r2Exists = Storage::disk('r2')->exists($imagePath);

echo "  Public disk: " . ($publicExists ? '✅ YES' : '❌ NO') . "\n";
echo "  R2 disk: " . ($r2Exists ? '✅ YES' : '❌ NO') . "\n\n";

if (!$publicExists && !$r2Exists) {
    echo "❌ IMAGE FILE NOT FOUND IN ANY STORAGE!\n\n";
    echo "This means the upload failed. Checking recent uploads...\n\n";
    
    // Check all recent uploads in last 10 minutes
    $recentUploads = ProductImage::where('created_at', '>=', now()->subMinutes(10))
        ->orderBy('created_at', 'desc')
        ->get();
    
    echo "Recent uploads (last 10 minutes): {$recentUploads->count()}\n";
    foreach ($recentUploads as $img) {
        echo "  - {$img->image_path}\n";
        echo "    Created: {$img->created_at}\n";
        echo "    Public: " . (Storage::disk('public')->exists($img->image_path) ? 'YES' : 'NO') . "\n";
        echo "    R2: " . (Storage::disk('r2')->exists($img->image_path) ? 'YES' : 'NO') . "\n\n";
    }
}

// Check Laravel logs for errors
echo "Checking for recent upload errors in logs...\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $lines = explode("\n", $logContent);
    $recentErrors = [];
    
    foreach (array_reverse($lines) as $line) {
        if (str_contains($line, 'srm339') || 
            (str_contains($line, 'upload') && str_contains($line, 'error'))) {
            $recentErrors[] = $line;
            if (count($recentErrors) >= 5) break;
        }
    }
    
    if (count($recentErrors) > 0) {
        echo "\nRecent errors found:\n";
        foreach ($recentErrors as $error) {
            echo "  " . substr($error, 0, 150) . "...\n";
        }
    } else {
        echo "No recent errors found in logs\n";
    }
}
