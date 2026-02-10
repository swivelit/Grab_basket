<?php

use Illuminate\Support\Facades\Storage;

$path = 'products/seller-2/srm341-1760335687.jpg';

echo "Checking: $path\n\n";

echo "R2 exists: " . (Storage::disk('r2')->exists($path) ? 'YES ✅' : 'NO ❌') . "\n";
echo "Public exists: " . (Storage::disk('public')->exists($path) ? 'YES ✅' : 'NO ❌') . "\n\n";

if (Storage::disk('r2')->exists($path)) {
    echo "Getting from R2...\n";
    try {
        $content = Storage::disk('r2')->get($path);
        echo "R2 file size: " . strlen($content) . " bytes\n";
        
        echo "Saving to public disk...\n";
        Storage::disk('public')->put($path, $content);
        
        echo "Verifying...\n";
        if (Storage::disk('public')->exists($path)) {
            $publicSize = Storage::disk('public')->size($path);
            echo "✅ SUCCESS! Public file size: $publicSize bytes\n";
        } else {
            echo "❌ FAILED - File not found after save\n";
        }
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ File not in R2!\n";
}
