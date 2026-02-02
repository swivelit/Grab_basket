<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$content = file_get_contents(resource_path('views/index.blade.php'));
$lines = explode("\n", $content);
$totalLines = count($lines);

echo "Testing different sections of the view...\n\n";

// Test last 1000 lines
$lastLines = array_slice($lines, -1000);
$testContent = "<!DOCTYPE html><html><head><title>Test</title></head><body>\n" . implode("\n", $lastLines) . "\n</body></html>";

file_put_contents(resource_path('views/test-last.blade.php'), $testContent);

try {
    view('test-last', [])->render();
    echo "Last 1000 lines: ✓ OK\n\n";
} catch (\Exception $e) {
    echo "Last 1000 lines: ✗ ERROR\n";
    echo "Error: " . $e->getMessage() . "\n\n";
}

unlink(resource_path('views/test-last.blade.php'));

// Now test everything EXCEPT last 1000 lines
$firstLines = array_slice($lines, 0, -1000);
$testContent = implode("\n", $firstLines) . "\n</body></html>";

file_put_contents(resource_path('views/test-first.blade.php'), $testContent);

try {
    view('test-first', [])->render();
    echo "First " . ($totalLines - 1000) . " lines: ✓ OK\n\n";
} catch (\Exception $e) {
    echo "First " . ($totalLines - 1000) . " lines: ✗ ERROR\n";
    echo "Error: " . $e->getMessage() . "\n\n";
}

unlink(resource_path('views/test-first.blade.php'));
