<?php

use Illuminate\Support\Facades\Storage;

echo "\n=== LIST R2 FILES IN products/seller-2/ ===\n\n";

try {
    $files = Storage::disk('r2')->files('products/seller-2');
    
    if (count($files) > 0) {
        echo "Found " . count($files) . " files:\n\n";
        foreach ($files as $f) {
            $size = Storage::disk('r2')->size($f);
            echo "  - $f (" . number_format($size) . " bytes)\n";
        }
    } else {
        echo "No files found in products/seller-2/\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== LIST PUBLIC FILES IN products/seller-2/ ===\n\n";

try {
    $files = Storage::disk('public')->files('products/seller-2');
    
    if (count($files) > 0) {
        echo "Found " . count($files) . " files:\n\n";
        foreach ($files as $f) {
            $size = Storage::disk('public')->size($f);
            echo "  - $f (" . number_format($size) . " bytes)\n";
        }
    } else {
        echo "No files found in products/seller-2/\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
