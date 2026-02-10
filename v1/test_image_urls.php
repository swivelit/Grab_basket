<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

echo "🌐 TESTING IMAGE URL ACCESSIBILITY\n";
echo "==================================\n\n";

// Get a few sample products
$products = \App\Models\Product::whereNotNull('image')
    ->where('image', '!=', '')
    ->take(3)
    ->get();

foreach($products as $product) {
    echo "📦 Product: {$product->name}\n";
    echo "📁 Image path: {$product->image}\n";
    
    // Test if file exists locally
    $fullPath = storage_path('app/public/' . $product->image);
    $exists = file_exists($fullPath);
    echo "📂 File exists locally: " . ($exists ? 'YES' : 'NO') . "\n";
    
    if ($exists) {
        echo "📏 File size: " . number_format(filesize($fullPath)) . " bytes\n";
    }
    
    // Get the image URL
    echo "🌐 Generated URL: {$product->image_url}\n";
    
    // Test different URL patterns
    $imagePath = $product->image;
    echo "\n🔧 URL Variations:\n";
    echo "1. Storage: " . asset('storage/' . $imagePath) . "\n";
    echo "2. Direct: " . config('app.url') . '/storage/' . $imagePath . "\n";
    
    // Test serve-image route if applicable
    $pathParts = explode('/', $imagePath, 2);
    if (count($pathParts) === 2) {
        echo "3. Serve-image: " . config('app.url') . '/serve-image/' . $pathParts[0] . '/' . $pathParts[1] . "\n";
    }
    
    echo "\n" . str_repeat("-", 80) . "\n\n";
}

// Check if serve-image route exists
echo "🔍 Checking serve-image route:\n";
try {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $serveImageRoute = null;
    
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'serve-image')) {
            $serveImageRoute = $route;
            break;
        }
    }
    
    if ($serveImageRoute) {
        echo "✅ Serve-image route found: " . $serveImageRoute->uri() . "\n";
    } else {
        echo "❌ Serve-image route NOT found\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error checking routes: " . $e->getMessage() . "\n";
}

// Check public storage directory
echo "\n📂 Storage directory info:\n";
$publicStoragePath = public_path('storage');
echo "Public storage path: {$publicStoragePath}\n";
echo "Exists: " . (file_exists($publicStoragePath) ? 'YES' : 'NO') . "\n";
echo "Is link: " . (is_link($publicStoragePath) ? 'YES' : 'NO') . "\n";
echo "Is directory: " . (is_dir($publicStoragePath) ? 'YES' : 'NO') . "\n";

if (is_link($publicStoragePath)) {
    echo "Link target: " . readlink($publicStoragePath) . "\n";
}