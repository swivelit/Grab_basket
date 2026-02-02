<?php

use Illuminate\Support\Facades\Storage;

$imagePath = 'products/seller-2/srm339-1760333146.jpg';

echo "Testing R2 storage access:\n";
echo "Path: {$imagePath}\n\n";

try {
    $exists = Storage::disk('r2')->exists($imagePath);
    echo "Exists check: " . ($exists ? 'YES' : 'NO') . "\n";
    
    if ($exists) {
        echo "Attempting to get file content...\n";
        $content = Storage::disk('r2')->get($imagePath);
        echo "Content size: " . strlen($content) . " bytes\n";
        echo "First 20 bytes: " . bin2hex(substr($content, 0, 20)) . "\n";
        
        // Try to get via URL
        $r2Url = config('filesystems.disks.r2.url') . '/' . $imagePath;
        echo "\nR2 Public URL: {$r2Url}\n";
        echo "This URL should be accessible in browser\n";
    } else {
        echo "\nFile does NOT exist according to R2 disk check\n";
        echo "But we know it's there from previous check!\n";
        
        // Try different variations
        $variations = [
            $imagePath,
            ltrim($imagePath, '/'),
            '/' . ltrim($imagePath, '/'),
        ];
        
        echo "\nTrying variations:\n";
        foreach ($variations as $var) {
            $check = Storage::disk('r2')->exists($var);
            echo "  '{$var}': " . ($check ? 'EXISTS' : 'NOT FOUND') . "\n";
        }
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
