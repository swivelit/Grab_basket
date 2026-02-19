<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

$testPath = 'food-items/hotel-1/chicken-biriyani-1735654510.webp'; // Example path
echo "Checking path: $testPath\n";

try {
    $exists = Storage::disk('r2')->exists($testPath);
    echo "Exists in R2: " . ($exists ? "YES" : "NO") . "\n";
    
    if (!$exists) {
        $files = Storage::disk('r2')->files('food-items');
        echo "Files in food-items/: \n";
        print_r(array_slice($files, 0, 5));
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
