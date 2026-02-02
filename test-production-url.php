<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Force environment to production for this run
$app->detectEnvironment(function(){ return 'production'; });

use App\Models\Product;

$p = Product::find(2);
echo "Image URL (production): " . $p->image_url . PHP_EOL;