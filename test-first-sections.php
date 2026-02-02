<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$content = file_get_contents(resource_path('views/index.blade.php'));
$lines = explode("\n", $content);

// Test just the first 100 lines
$testLines = array_slice($lines, 0, 100);
$testContent = implode("\n", $testLines) . "\n</head><body></body></html>";

file_put_contents(resource_path('views/test-first100.blade.php'), $testContent);

try {
    view('test-first100', [])->render();
    echo "First 100 lines: ✓ OK\n";
} catch (\Exception $e) {
    echo "First 100 lines: ✗ ERROR\n";
    echo $e->getMessage() . "\n";
}

unlink(resource_path('views/test-first100.blade.php'));

// Test lines 100-200
$testLines = array_slice($lines, 100, 100);
$testContent = "<!DOCTYPE html><html><head>\n" . implode("\n", $testLines) . "\n</head><body></body></html>";

file_put_contents(resource_path('views/test-100-200.blade.php'), $testContent);

try {
    view('test-100-200', [])->render();
    echo "Lines 100-200: ✓ OK\n";
} catch (\Exception $e) {
    echo "Lines 100-200: ✗ ERROR\n";
    echo $e->getMessage() . "\n";
}

unlink(resource_path('views/test-100-200.blade.php'));

// Test lines 200-257
$testLines = array_slice($lines, 200, 57);
$testContent = "<!DOCTYPE html><html><head>\n" . implode("\n", $testLines) . "\n</head><body></body></html>";

file_put_contents(resource_path('views/test-200-257.blade.php'), $testContent);

try {
    view('test-200-257', [])->render();
    echo "Lines 200-257: ✓ OK\n";
} catch (\Exception $e) {
    echo "Lines 200-257: ✗ ERROR\n";
    echo $e->getMessage() . "\n";
}

unlink(resource_path('views/test-200-257.blade.php'));
