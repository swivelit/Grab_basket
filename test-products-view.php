<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "Testing buyer.products view compilation...\n\n";

try {
    // Try to compile the view
    $view = app('view')->make('buyer.products', [
        'category' => (object)['name' => 'Test', 'id' => 1],
        'products' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12, 1),
        'categories' => collect([]),
        'subsByCategory' => collect([]),
        'activeCategoryId' => 1,
        'activeSubcategoryId' => null,
        'filters' => [],
        'searchQuery' => '',
        'totalResults' => 0,
        'matchedStores' => collect([]),
    ]);
    
    $content = $view->render();
    
    echo "✓ View compiled successfully\n";
    echo "Content size: " . strlen($content) . " bytes\n";
    
} catch (\Exception $e) {
    echo "✗ View compilation FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
