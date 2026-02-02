<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FoodItem;

$food = FoodItem::latest()->first();
if ($food) {
    echo "ID: " . $food->id . "\n";
    echo "Name: " . $food->name . "\n";
    echo "Stored Path: " . $food->getRawOriginal('image') . "\n";
    echo "Generated URL: " . $food->first_image_url . "\n";
} else {
    echo "No food items found.\n";
}
