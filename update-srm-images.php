<?php

require_once 'vendor/autoload.php';

echo "🖼️ SRM PRODUCT IMAGE UPDATE SCRIPT\n";
echo "==================================\n\n";

try {
    // Load Laravel app
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "✅ Laravel application loaded\n\n";
    
    // Get SRM images from the folder
    $srmImagesPath = 'SRM IMG';
    $srmImages = [];
    
    if (is_dir($srmImagesPath)) {
        $files = scandir($srmImagesPath);
        foreach ($files as $file) {
            if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'webp'])) {
                $srmImages[] = $file;
            }
        }
        echo "📂 Found " . count($srmImages) . " SRM images\n";
    } else {
        echo "❌ SRM IMG folder not found\n";
        exit;
    }
    
    // Get products that need images (products without images first)
    $productsWithoutImages = \App\Models\Product::whereNull('image')->take(30)->get();
    $productsWithImages = \App\Models\Product::whereNotNull('image')->take(36)->get();
    
    echo "📊 Products Status:\n";
    echo "   Without images: " . $productsWithoutImages->count() . "\n";
    echo "   With images: " . $productsWithImages->count() . "\n\n";
    
    $updateCount = 0;
    $imageIndex = 0;
    
    echo "🔄 Starting image updates...\n\n";
    
    // First, update products without images
    foreach ($productsWithoutImages as $product) {
        if ($imageIndex >= count($srmImages)) break;
        
        $srmImage = $srmImages[$imageIndex];
        $sourcePath = $srmImagesPath . '/' . $srmImage;
        
        // Copy image to public storage
        $destinationFolder = 'images';
        $destinationPath = $destinationFolder . '/' . $srmImage;
        
        // Ensure images directory exists
        if (!is_dir('public/images')) {
            mkdir('public/images', 0755, true);
        }
        
        if (copy($sourcePath, 'public/' . $destinationPath)) {
            // Update product with image path
            $product->image = $destinationPath;
            $product->save();
            
            echo "✅ Updated Product ID {$product->id}: {$product->name}\n";
            echo "   Image: {$srmImage} -> {$destinationPath}\n\n";
            
            $updateCount++;
            $imageIndex++;
        } else {
            echo "❌ Failed to copy {$srmImage}\n";
        }
    }
    
    // Then update some products that already have images (replace them)
    foreach ($productsWithImages as $product) {
        if ($imageIndex >= count($srmImages)) break;
        if ($updateCount >= 50) break; // Limit total updates
        
        $srmImage = $srmImages[$imageIndex];
        $sourcePath = $srmImagesPath . '/' . $srmImage;
        
        // Copy image to public storage
        $destinationFolder = 'images';
        $destinationPath = $destinationFolder . '/' . $srmImage;
        
        if (copy($sourcePath, 'public/' . $destinationPath)) {
            // Update product with new image path
            $oldImage = $product->image;
            $product->image = $destinationPath;
            $product->save();
            
            echo "🔄 Replaced Product ID {$product->id}: {$product->name}\n";
            echo "   Old: {$oldImage}\n";
            echo "   New: {$destinationPath}\n\n";
            
            $updateCount++;
            $imageIndex++;
        }
    }
    
    echo "🎉 IMAGE UPDATE COMPLETE!\n";
    echo "========================\n";
    echo "📊 Summary:\n";
    echo "   Total images processed: {$updateCount}\n";
    echo "   SRM images used: {$imageIndex} of " . count($srmImages) . "\n";
    echo "   Products updated: {$updateCount}\n\n";
    
    // Show some updated products
    echo "📦 Recently Updated Products:\n";
    $recentlyUpdated = \App\Models\Product::whereNotNull('image')
        ->where('image', 'LIKE', 'images/SRM%')
        ->take(10)
        ->get();
    
    foreach ($recentlyUpdated as $product) {
        echo "   ID {$product->id}: {$product->name} - {$product->image}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}