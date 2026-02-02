<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FoodItem;
use Illuminate\Support\Facades\Storage;

$foods = FoodItem::whereNotNull('image')->where('image', '!=', '')->take(5)->get();
foreach ($foods as $food) {
    echo "ID: {$food->id}\n";
    echo "Raw Image: {$food->image}\n";
    echo "Generated URL: {$food->first_image_url}\n";
    
    $cleanPath = ltrim(str_replace('\\', '/', $food->image), '/');
    echo "Clean Path: $cleanPath\n";
    echo "Exists in R2: " . (Storage::disk('r2')->exists($cleanPath) ? "YES" : "NO") . "\n";
    echo "Exists in Public: " . (Storage::disk('public')->exists($cleanPath) ? "YES" : "NO") . "\n";
    echo "-------------------\n";
}
