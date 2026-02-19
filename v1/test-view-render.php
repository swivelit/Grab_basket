<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing View Rendering...\n\n";

try {
    // Test if view file exists
    echo "1. Checking if index.blade.php exists...\n";
    $viewPath = resource_path('views/index.blade.php');
    if (file_exists($viewPath)) {
        echo "   ✓ View file exists\n";
        echo "   Size: " . number_format(filesize($viewPath)) . " bytes\n\n";
    } else {
        echo "   ✗ View file NOT found!\n\n";
        exit(1);
    }
    
    // Test view compilation
    echo "2. Testing view compilation...\n";
    try {
        view('index', [
            'categories' => collect([]),
            'products' => collect([]),
            'trending' => collect([]),
            'lookbookProduct' => null,
            'blogProducts' => collect([]),
            'categoryProducts' => [],
            'banners' => collect([]),
            'settings' => [
                'hero_title' => 'Welcome',
                'hero_subtitle' => 'Test',
                'theme_color' => '#FF6B00',
            ]
        ])->render();
        echo "   ✓ View compiled successfully\n\n";
    } catch (\Exception $e) {
        echo "   ✗ View compilation FAILED!\n";
        echo "   Error: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . "\n";
        echo "   Line: " . $e->getLine() . "\n\n";
        exit(1);
    }
    
    // Test HomeController with actual render
    echo "3. Testing full HomeController response...\n";
    try {
        $controller = new App\Http\Controllers\HomeController();
        $response = $controller->index();
        
        if ($response instanceof Illuminate\Http\JsonResponse) {
            echo "   ✗ Controller returned JSON error:\n";
            echo "   " . json_encode($response->getData(), JSON_PRETTY_PRINT) . "\n\n";
            exit(1);
        } else {
            echo "   ✓ HomeController returned view response\n";
            
            // Try to render the response
            $content = $response->render();
            echo "   ✓ View rendered successfully\n";
            echo "   Content size: " . number_format(strlen($content)) . " bytes\n\n";
        }
    } catch (\Exception $e) {
        echo "   ✗ Controller execution FAILED!\n";
        echo "   Error: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . "\n";
        echo "   Line: " . $e->getLine() . "\n";
        echo "\n   Stack trace:\n";
        echo "   " . str_replace("\n", "\n   ", $e->getTraceAsString()) . "\n\n";
        exit(1);
    }
    
    echo "✅ All tests passed! The homepage should work.\n\n";
    echo "If production still shows 500:\n";
    echo "1. Clear caches on production: php artisan optimize:clear\n";
    echo "2. Rebuild caches: php artisan optimize\n";
    echo "3. Check storage/logs/laravel.log on production\n";
    echo "4. Verify .env APP_DEBUG=true temporarily to see error\n";
    
} catch (\Exception $e) {
    echo "✗ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
