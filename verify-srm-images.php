<?php

require_once 'vendor/autoload.php';

echo "🔍 SRM IMAGE VERIFICATION SCRIPT\n";
echo "================================\n\n";

try {
    // Load Laravel app
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "✅ Laravel application loaded\n\n";
    
    // Get products with SRM images
    $srmProducts = \App\Models\Product::where('image', 'LIKE', 'images/SRM%')
        ->orderBy('id')
        ->get();
    
    echo "📊 Found " . $srmProducts->count() . " products with SRM images\n\n";
    
    $workingCount = 0;
    $brokenCount = 0;
    
    echo "🔍 Verifying image URLs and file existence:\n\n";
    
    foreach ($srmProducts as $product) {
        $imagePath = $product->image;
        $imageUrl = $product->image_url;
        $publicPath = public_path($imagePath);
        
        $exists = file_exists($publicPath);
        $status = $exists ? "✅" : "❌";
        
        if ($exists) {
            $workingCount++;
        } else {
            $brokenCount++;
        }
        
        echo "{$status} ID {$product->id}: {$product->name}\n";
        echo "    Image: {$imagePath}\n";
        echo "    URL: {$imageUrl}\n";
        echo "    File exists: " . ($exists ? "YES" : "NO") . "\n";
        
        if ($exists) {
            $fileSize = filesize($publicPath);
            echo "    Size: " . number_format($fileSize / 1024, 2) . " KB\n";
        }
        echo "\n";
    }
    
    echo "📊 VERIFICATION SUMMARY:\n";
    echo "========================\n";
    echo "✅ Working images: {$workingCount}\n";
    echo "❌ Broken images: {$brokenCount}\n";
    echo "📈 Success rate: " . round(($workingCount / $srmProducts->count()) * 100, 2) . "%\n\n";
    
    // Show some examples for testing
    echo "🌐 Test URLs (copy to browser):\n";
    echo "===============================\n";
    $testProducts = $srmProducts->take(5);
    foreach ($testProducts as $product) {
        echo "Product ID {$product->id}: " . url($product->image_url) . "\n";
    }
    
    echo "\n🎯 Verification complete!\n";
    echo "You can now test the edit product form with these updated images.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}