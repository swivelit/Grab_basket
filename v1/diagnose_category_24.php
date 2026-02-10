<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

echo "=== DIAGNOSING CATEGORY 24 ERROR ===\n\n";

// Check if category exists
$category = Category::find(24);
if (!$category) {
    echo "❌ Category 24 does NOT exist\n";
    exit(1);
}

echo "✅ Category 24 EXISTS\n";
echo "   Name: {$category->name}\n";
echo "   Slug: {$category->slug}\n\n";

// Check products
$totalProducts = Product::where('category_id', 24)->count();
echo "📦 Total products: {$totalProducts}\n";

$validProducts = Product::where('category_id', 24)
    ->whereNotNull('image')
    ->where('image', '!=', '')
    ->where('image', 'NOT LIKE', '%unsplash%')
    ->where('image', 'NOT LIKE', '%placeholder%')
    ->where('image', 'NOT LIKE', '%via.placeholder%')
    ->count();

echo "🖼️  Products with valid images: {$validProducts}\n\n";

// Test the actual query that the controller uses
echo "=== TESTING CONTROLLER QUERY ===\n";
try {
    $query = Product::with(['category', 'subcategory'])
        ->where('category_id', 24)
        ->whereNotNull('image')
        ->where('image', '!=', '')
        ->where('image', 'NOT LIKE', '%unsplash%')
        ->where('image', 'NOT LIKE', '%placeholder%')
        ->where('image', 'NOT LIKE', '%via.placeholder%');
    
    $products = $query->paginate(12);
    echo "✅ Query executed successfully\n";
    echo "   Results: {$products->total()} products\n";
    
    if ($products->count() > 0) {
        echo "\nSample products:\n";
        foreach ($products->take(3) as $product) {
            echo "  - ID: {$product->id}, Name: " . substr($product->name, 0, 40) . "\n";
            echo "    Image: {$product->image}\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ QUERY FAILED: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

// Check recent logs
echo "\n=== CHECKING RECENT LOGS ===\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recentLines = array_slice($lines, -50); // Last 50 lines
    $categoryErrors = array_filter($recentLines, function($line) {
        return stripos($line, 'category') !== false || stripos($line, 'error') !== false;
    });
    
    if (!empty($categoryErrors)) {
        echo "Recent errors found:\n";
        foreach (array_slice($categoryErrors, -10) as $line) {
            echo $line;
        }
    } else {
        echo "No recent category errors in logs\n";
    }
} else {
    echo "Log file not found\n";
}

echo "\nDone!\n";
