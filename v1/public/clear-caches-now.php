<?php

/**
 * Emergency Cache Clear Script
 * 
 * This script clears all Laravel caches when accessed via browser
 * URL: https://grabbaskets.laravel.cloud/clear-caches-now.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "<html><head><title>Cache Clearing</title></head><body>";
echo "<h1>🧹 Emergency Cache Clear</h1>";
echo "<pre>";

echo "Clearing Application Cache...\n";
\Illuminate\Support\Facades\Artisan::call('cache:clear');
echo "✅ Application cache cleared\n\n";

echo "Clearing Configuration Cache...\n";
\Illuminate\Support\Facades\Artisan::call('config:clear');
echo "✅ Configuration cache cleared\n\n";

echo "Clearing Route Cache...\n";
\Illuminate\Support\Facades\Artisan::call('route:clear');
echo "✅ Route cache cleared\n\n";

echo "Clearing View Cache...\n";
\Illuminate\Support\Facades\Artisan::call('view:clear');
echo "✅ View cache cleared\n\n";

echo "Clearing Compiled Classes...\n";
\Illuminate\Support\Facades\Artisan::call('clear-compiled');
echo "✅ Compiled classes cleared\n\n";

echo "Optimizing...\n";
\Illuminate\Support\Facades\Artisan::call('optimize:clear');
echo "✅ Optimization cache cleared\n\n";

echo "</pre>";
echo "<h2>✅ ALL CACHES CLEARED!</h2>";
echo "<p>Images should now display correctly.</p>";
echo "<p><a href='/seller/dashboard'>Go to Dashboard</a></p>";
echo "</body></html>";
