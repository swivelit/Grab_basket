<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\View;

echo "=== Testing Blade View Compilation ===\n\n";

try {
    // Prepare test data
    $sellers = User::where('role', 'seller')
        ->withCount(['products' => function($query) {
            $query->whereNotNull('image');
        }])
        ->orderBy('products_count', 'desc')
        ->get();
    
    $products = null;
    $selectedSellerInfo = null;
    $search = null;
    $selectedSeller = null;
    
    echo "Step 1: Checking if view exists...\n";
    if (View::exists('admin.products-by-seller')) {
        echo "✅ View file exists\n\n";
    } else {
        echo "❌ View file NOT found!\n";
        exit(1);
    }
    
    echo "Step 2: Attempting to compile view...\n";
    $view = view('admin.products-by-seller', compact(
        'sellers',
        'products',
        'selectedSellerInfo',
        'search',
        'selectedSeller'
    ));
    
    echo "✅ View compiled successfully\n\n";
    
    echo "Step 3: Attempting to render view...\n";
    $html = $view->render();
    
    echo "✅ View rendered successfully\n";
    echo "HTML length: " . strlen($html) . " bytes\n\n";
    
    // Check for common issues
    if (strpos($html, '500') !== false) {
        echo "⚠️  Warning: '500' text found in HTML\n";
    }
    if (strpos($html, 'error') !== false) {
        echo "⚠️  Warning: 'error' text found in HTML\n";
    }
    if (strpos($html, 'Exception') !== false) {
        echo "⚠️  Warning: 'Exception' text found in HTML\n";
    }
    
    echo "\n✅ VIEW TEST PASSED - No compilation errors!\n";
    
} catch (\Exception $e) {
    echo "\n❌ VIEW ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
