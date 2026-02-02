<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Storage;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing image file accessibility...\n\n";

// Test a specific image file
$imagePath = 'products/0Rc193BfOQ4pDAtqAYBc1SLfKm2E9Hoklwo643Fz.jpg';

echo "Testing image path: {$imagePath}\n";

// Check if file exists in public disk
$exists = Storage::disk('public')->exists($imagePath);
echo "File exists in public disk: " . ($exists ? 'YES' : 'NO') . "\n";

if ($exists) {
    // Get full path
    $fullPath = Storage::disk('public')->path($imagePath);
    echo "Full file path: {$fullPath}\n";
    
    // Check if file actually exists on filesystem
    $fileExists = file_exists($fullPath);
    echo "File exists on filesystem: " . ($fileExists ? 'YES' : 'NO') . "\n";
    
    if ($fileExists) {
        $fileSize = filesize($fullPath);
        echo "File size: {$fileSize} bytes\n";
        
        // Get file permissions
        $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
        echo "File permissions: {$perms}\n";
    }
}

// Test storage URL generation
$storageUrl = asset('storage/' . $imagePath);
echo "Storage URL: {$storageUrl}\n";

// Test manual URL construction
$appUrl = config('app.url');
$manualUrl = rtrim($appUrl, '/') . '/storage/' . $imagePath;
echo "Manual URL: {$manualUrl}\n";

echo "\nDone!\n";