<?php
require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Models\CartItem;
use App\Models\Product;

// Bootstrap Laravel
$app = new Application(realpath(__DIR__));
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);
$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Handle the request
$request = Request::capture();
$kernel->handle($request);

echo "🛒 CART IMAGE VERIFICATION TEST\n";
echo "==============================\n\n";

try {
    // Get a sample of cart items with products
    $cartItems = CartItem::with(['product', 'product.productImages'])
                        ->limit(10)
                        ->get();
    
    if ($cartItems->isEmpty()) {
        echo "ℹ️  No cart items found in database.\n";
        echo "\nLet's check some products instead for image URL testing:\n\n";
        
        $products = Product::limit(5)->get();
        foreach ($products as $product) {
            echo "Product ID: {$product->id}\n";
            echo "Name: {$product->name}\n";
            echo "Image URL: " . ($product->image_url ?: 'NULL') . "\n";
            echo "Has Image: " . ($product->image ? '✅' : '❌') . "\n";
            echo "Has Image Data: " . ($product->image_data ? '✅' : '❌') . "\n";
            echo "Product Images Count: " . $product->productImages()->count() . "\n";
            echo "---\n";
        }
    } else {
        echo "Found " . $cartItems->count() . " cart items to test:\n\n";
        
        foreach ($cartItems as $item) {
            echo "Cart Item ID: {$item->id}\n";
            echo "User ID: {$item->user_id}\n";
            echo "Quantity: {$item->quantity}\n";
            
            if ($item->product) {
                echo "Product: {$item->product->name} (ID: {$item->product->id})\n";
                
                // Test image URL generation
                $imageUrl = $item->product->image_url;
                echo "Generated Image URL: " . ($imageUrl ?: 'NULL') . "\n";
                
                // Check if URL is accessible (basic check)
                if ($imageUrl) {
                    if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        echo "URL Format: ✅ Valid\n";
                        
                        // Check if it's a placeholder
                        if (str_contains($imageUrl, 'placeholder')) {
                            echo "Image Type: 📝 Placeholder\n";
                        } elseif (str_starts_with($imageUrl, 'data:')) {
                            echo "Image Type: 📦 Base64 Data\n";
                        } elseif (str_contains($imageUrl, 'laravel.cloud')) {
                            echo "Image Type: ☁️  Laravel Cloud\n";
                        } else {
                            echo "Image Type: 🔗 External/Asset\n";
                        }
                    } else {
                        echo "URL Format: ❌ Invalid\n";
                    }
                } else {
                    echo "URL Format: ⚠️  NULL/Empty\n";
                }
                
            } else {
                echo "Product: ❌ NULL (orphaned cart item)\n";
            }
            
            echo "---\n";
        }
    }
    
    echo "\n🔍 CART TEMPLATE VERIFICATION:\n";
    $cartTemplatePath = __DIR__ . '/resources/views/cart/index.blade.php';
    
    if (file_exists($cartTemplatePath)) {
        $content = file_get_contents($cartTemplatePath);
        
        // Check for image handling
        $hasImageCheck = str_contains($content, '$item->product->image_url');
        $hasPlaceholder = str_contains($content, 'placeholder');
        $hasErrorHandling = str_contains($content, 'onerror');
        $hasProductCheck = str_contains($content, '@if($item->product)');
        
        echo "✅ Cart template exists\n";
        echo "Image URL Usage: " . ($hasImageCheck ? '✅' : '❌') . "\n";
        echo "Placeholder Fallback: " . ($hasPlaceholder ? '✅' : '❌') . "\n";
        echo "Error Handling: " . ($hasErrorHandling ? '✅' : '❌') . "\n";
        echo "Product Existence Check: " . ($hasProductCheck ? '✅' : '❌') . "\n";
    } else {
        echo "❌ Cart template not found\n";
    }
    
    echo "\n✅ Cart image verification completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}