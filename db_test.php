<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $item = \DB::table('food_items')->latest()->first();
    if ($item) {
        echo "ID: " . $item->id . "\n";
        echo "Image: " . ($item->image ?? 'NULL') . "\n";
    } else {
        echo "Empty table\n";
    }
} catch (\Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
