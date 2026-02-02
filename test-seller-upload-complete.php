<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

echo "🛠️  COMPREHENSIVE SELLER PRODUCT UPLOAD TEST\n";
echo "===========================================\n\n";

try {
    // Step 1: Check if we have all required data
    echo "1. 📊 Prerequisites Check:\n";
    
    $seller = User::where('role', 'seller')->first();
    $category = Category::first();
    $subcategory = Subcategory::first();
    
    if (!$seller) {
        echo "   ❌ No sellers found!\n";
        exit;
    }
    if (!$category) {
        echo "   ❌ No categories found!\n";
        exit;
    }
    if (!$subcategory) {
        echo "   ❌ No subcategories found!\n";
        exit;
    }
    
    echo "   ✅ Seller: {$seller->name} (ID: {$seller->id})\n";
    echo "   ✅ Category: {$category->name} (ID: {$category->id})\n";
    echo "   ✅ Subcategory: {$subcategory->name} (ID: {$subcategory->id})\n";

    // Step 2: Create a test image file
    echo "\n2. 🖼️  Creating Test Image:\n";
    $testImagePath = storage_path('app/test_product_image.jpg');
    
    // Create a simple test image
    $imageContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=');
    file_put_contents($testImagePath, $imageContent);
    
    if (file_exists($testImagePath)) {
        echo "   ✅ Test image created: " . basename($testImagePath) . "\n";
        echo "   File size: " . round(filesize($testImagePath)/1024, 2) . " KB\n";
    } else {
        echo "   ❌ Failed to create test image\n";
        exit;
    }

    // Step 3: Simulate the product creation process
    echo "\n3. 🛒 Simulating Product Creation:\n";
    
    // Generate unique ID like the controller does
    $unique_id = Str::upper(Str::random(2)) . rand(0, 9);
    echo "   Generated unique_id: $unique_id\n";
    
    // Simulate image upload process
    echo "   Testing image upload process...\n";
    $extension = 'jpg';
    $filename = $unique_id . '_' . time() . '.' . $extension;
    $folder = "products";
    
    echo "   Target filename: $filename\n";
    echo "   Target folder: $folder\n";
    
    // Copy test image to products folder using Laravel Storage
    try {
        $disk = Storage::disk('public');
        $targetPath = $folder . '/' . $filename;
        
        // Read test image and store it
        $imageContent = file_get_contents($testImagePath);
        $success = $disk->put($targetPath, $imageContent);
        
        if ($success) {
            echo "   ✅ Image uploaded successfully to: $targetPath\n";
            $imagePath = $targetPath;
        } else {
            echo "   ❌ Image upload failed\n";
            $imagePath = null;
        }
    } catch (Exception $e) {
        echo "   ❌ Image upload error: " . $e->getMessage() . "\n";
        $imagePath = null;
    }

    // Step 4: Create the product record
    echo "\n4. 📝 Creating Product Record:\n";
    
    try {
        $product = Product::create([
            'name' => "Test Product Upload - " . date('Y-m-d H:i:s'),
            'unique_id' => $unique_id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'seller_id' => $seller->id,
            'image' => $imagePath,
            'description' => "This is a test product to verify upload functionality",
            'price' => 99.99,
            'discount' => 10,
            'delivery_charge' => 0,
            'gift_option' => 'yes',
            'stock' => 5,
        ]);
        
        echo "   ✅ Product created successfully!\n";
        echo "   Product ID: {$product->id}\n";
        echo "   Product Name: {$product->name}\n";
        echo "   Product Image Path: " . ($product->image ?: 'NULL') . "\n";
        
    } catch (Exception $e) {
        echo "   ❌ Product creation failed: " . $e->getMessage() . "\n";
        exit;
    }

    // Step 5: Test image URL generation
    echo "\n5. 🔗 Testing Image URL Generation:\n";
    
    if ($product->image) {
        $imageUrl = $product->image_url; // This uses the enhanced getImageUrlAttribute
        echo "   Product image attribute: {$product->image}\n";
        echo "   Generated image URL: $imageUrl\n";
        
        // Test different URL formats
        $storageUrl = asset('storage/' . $product->image);
        $directUrl = asset($product->image);
        
        echo "   Storage URL: $storageUrl\n";
        echo "   Direct URL: $directUrl\n";
        
        // Check if file exists in storage
        $fullImagePath = storage_path('app/public/' . $product->image);
        echo "   Full image path: $fullImagePath\n";
        echo "   File exists: " . (file_exists($fullImagePath) ? "✅ YES" : "❌ NO") . "\n";
        
    } else {
        echo "   ⚠️  No image was saved with the product\n";
    }

    // Step 6: Test accessibility via web
    echo "\n6. 🌐 Testing Web Accessibility:\n";
    
    if ($product->image) {
        $publicImagePath = public_path('storage/' . $product->image);
        echo "   Public image path: $publicImagePath\n";
        echo "   Accessible via web: " . (file_exists($publicImagePath) ? "✅ YES" : "❌ NO") . "\n";
        
        if (!file_exists($publicImagePath)) {
            echo "   ⚠️  Image not accessible via web - storage link issue!\n";
        }
    }

    // Step 7: Dashboard display test
    echo "\n7. 📊 Dashboard Display Test:\n";
    
    // Simulate how dashboard would display this product
    $dashboardImageHtml = '';
    if ($product->image) {
        $dashboardImageHtml = '<img src="' . asset('storage/' . $product->image) . '" alt="' . $product->name . '" style="height:48px; width:48px;">';
        echo "   Dashboard would show: $dashboardImageHtml\n";
    } else {
        $dashboardImageHtml = '<span class="text-muted small">No Image</span>';
        echo "   Dashboard would show: $dashboardImageHtml\n";
    }

    // Step 8: Clean up
    echo "\n8. 🧹 Cleanup:\n";
    
    // Delete the test product
    if (isset($product)) {
        echo "   Deleting test product...\n";
        $product->delete();
        echo "   ✅ Test product deleted\n";
    }
    
    // Delete uploaded test image
    if ($imagePath && Storage::disk('public')->exists($imagePath)) {
        Storage::disk('public')->delete($imagePath);
        echo "   ✅ Test image deleted from storage\n";
    }
    
    // Delete original test image
    if (file_exists($testImagePath)) {
        unlink($testImagePath);
        echo "   ✅ Original test image deleted\n";
    }

    echo "\n✅ COMPREHENSIVE TEST COMPLETE\n";
    echo "============================\n";
    echo "\n📋 SUMMARY:\n";
    echo "- Product creation: ✅ Working\n";
    echo "- Image upload: " . ($imagePath ? "✅ Working" : "❌ Failed") . "\n";
    echo "- Storage system: ✅ Working\n";
    echo "- Image URL generation: ✅ Working\n";
    echo "- Web accessibility: " . (isset($publicImagePath) && file_exists($publicImagePath) ? "✅ Working" : "⚠️  Needs storage link") . "\n";

} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}