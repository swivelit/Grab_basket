<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Storage;

echo "🔍 Checking WHERE images are actually stored\n";
echo str_repeat("=", 70) . "\n\n";

$testPaths = [
    'products/SRM702_1759987268.jpg',
    'products/SRM367_1760343234.jpg',
    'products/seller-2/srm340-1760342455.jpg'
];

foreach ($testPaths as $path) {
    echo "Testing: $path\n";
    
    // Check public disk
    $publicExists = Storage::disk('public')->exists($path);
    echo "  Public disk: " . ($publicExists ? "✅ EXISTS" : "❌ NOT FOUND") . "\n";
    
    // Check R2 disk
    try {
        $r2Exists = Storage::disk('r2')->exists($path);
        echo "  R2 disk: " . ($r2Exists ? "✅ EXISTS" : "❌ NOT FOUND") . "\n";
        
        if ($r2Exists) {
            $size = Storage::disk('r2')->size($path);
            echo "  R2 file size: " . number_format($size / 1024, 2) . " KB\n";
        }
    } catch (\Exception $e) {
        echo "  R2 disk: ❌ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo str_repeat("=", 70) . "\n";
echo "\n📊 CONCLUSION:\n\n";

// Count where images are
$publicCount = 0;
$r2Count = 0;

foreach ($testPaths as $path) {
    if (Storage::disk('public')->exists($path)) $publicCount++;
    if (Storage::disk('r2')->exists($path)) $r2Count++;
}

if ($r2Count > 0 && $publicCount == 0) {
    echo "✅ Images are in R2 storage ONLY\n";
    echo "⚠️  Solution: Use R2 URLs or serve-image route\n";
    echo "❌ Storage symlink won't work (images not in public disk)\n";
} elseif ($publicCount > 0 && $r2Count == 0) {
    echo "✅ Images are in PUBLIC disk ONLY\n";
    echo "⚠️  Solution: Storage symlink should work\n";
    echo "❌ But Laravel Cloud might not support symlinks\n";
} elseif ($publicCount > 0 && $r2Count > 0) {
    echo "✅ Images are in BOTH public and R2\n";
    echo "💡 Can use either method\n";
} else {
    echo "❌ Images NOT FOUND in either location!\n";
    echo "🚨 Images might be lost or in different location\n";
}

echo "\n";
