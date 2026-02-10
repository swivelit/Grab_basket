<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

try {
    $files = Storage::disk('r2')->allFiles('');
    $foodFiles = array_filter($files, function($f) {
        return strpos($f, 'food') !== false;
    });
    echo "Found " . count($foodFiles) . " food-related files in R2.\n";
    print_r(array_slice($foodFiles, 0, 10));
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
