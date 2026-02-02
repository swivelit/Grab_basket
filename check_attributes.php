<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FoodItem;

try {
    $food = FoodItem::first();
    if ($food) {
        echo "Found Food: " . $food->id . "\n";
        print_r($food->getAttributes());
    } else {
        echo "No food items found.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
