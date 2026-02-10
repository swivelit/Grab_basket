<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Testing AWS R2 Storage Access\n";
echo "===============================================\n\n";

$disk = Storage::disk('r2');

// Test specific file
$testFile = 'products/SRM702_1759987268.jpg';
echo "Testing file: {$testFile}\n";

if ($disk->exists($testFile)) {
    echo "✅ File exists in R2 storage\n";
    echo "   URL: " . $disk->url($testFile) . "\n";
    echo "   Size: " . number_format($disk->size($testFile)) . " bytes\n";
} else {
    echo "❌ File NOT found in R2 storage\n\n";
    
    echo "Looking for similar files...\n";
    $files = $disk->files('products');
    foreach ($files as $file) {
        if (str_contains($file, 'SRM702')) {
            echo "  Found: {$file}\n";
        }
    }
}

echo "\n📊 Storage Statistics:\n";
echo "   Total files in products/: " . count($disk->files('products')) . "\n";
echo "   First 10 files:\n";
$files = $disk->files('products');
foreach (array_slice($files, 0, 10) as $file) {
    echo "     - {$file}\n";
}
