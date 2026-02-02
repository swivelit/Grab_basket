<?php

use Illuminate\Support\Facades\Storage;

$testPath = 'products/seller-2/srm339-1760334028.jpg';

echo "Testing serve-image route logic\n";
echo "========================================\n\n";

echo "Image path: {$testPath}\n\n";

// Test public disk
echo "Public disk check:\n";
$publicExists = Storage::disk('public')->exists($testPath);
echo "  Exists: " . ($publicExists ? 'YES' : 'NO') . "\n";

if ($publicExists) {
    $fullPath = Storage::disk('public')->path($testPath);
    echo "  Full path: {$fullPath}\n";
    echo "  File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
    if (file_exists($fullPath)) {
        echo "  File size: " . filesize($fullPath) . " bytes\n";
        echo "  Readable: " . (is_readable($fullPath) ? 'YES' : 'NO') . "\n";
    }
}

echo "\n";

// Test R2 disk
echo "R2 disk check:\n";
$r2Exists = Storage::disk('r2')->exists($testPath);
echo "  Exists: " . ($r2Exists ? 'YES' : 'NO') . "\n";

if ($r2Exists) {
    try {
        $content = Storage::disk('r2')->get($testPath);
        echo "  Can read: YES\n";
        echo "  Size: " . strlen($content) . " bytes\n";
    } catch (\Throwable $e) {
        echo "  Can read: NO - {$e->getMessage()}\n";
    }
}

echo "\n";

// Simulate serve-image route logic
echo "Simulating serve-image route for:\n";
echo "  URL: /serve-image/products/seller-2/srm339-1760334028.jpg\n";
echo "  Type: products\n";
echo "  Path: seller-2/srm339-1760334028.jpg\n";
echo "  Storage path: products/seller-2/srm339-1760334028.jpg\n\n";

if ($publicExists) {
    echo "✅ Route should find image in public disk and serve it\n";
} elseif ($r2Exists) {
    echo "✅ Route should find image in R2 disk and serve it\n";
} else {
    echo "❌ Route will return 404 - image not found\n";
}
