<?php
/**
 * Clear All Laravel Caches
 * This will clear config, route, view caches and more
 */

echo "=== CLEARING ALL LARAVEL CACHES ===\n\n";

// Change to the Laravel directory
chdir(__DIR__);

$commands = [
    'php artisan config:clear' => 'Config cache',
    'php artisan cache:clear' => 'Application cache',
    'php artisan route:clear' => 'Route cache',
    'php artisan view:clear' => 'View cache',
    'php artisan optimize:clear' => 'Optimization cache',
];

foreach ($commands as $command => $description) {
    echo "Clearing {$description}...\n";
    exec($command . ' 2>&1', $output, $return_var);
    
    if ($return_var === 0) {
        echo "✅ {$description} cleared successfully\n";
    } else {
        echo "⚠️  {$description}: " . implode("\n", $output) . "\n";
    }
    echo "\n";
    $output = [];
}

echo "=== VERIFYING APP_URL ===\n";
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$appUrl = config('app.url');
echo "Current APP_URL: {$appUrl}\n";

if (strpos($appUrl, 'grabbaskets.com') !== false) {
    echo "✅ APP_URL is correctly set to grabbaskets.com\n";
} else {
    echo "❌ WARNING: APP_URL is NOT set to grabbaskets.com!\n";
    echo "   Please update APP_URL in .env file\n";
}

echo "\n=== CACHE CLEARING COMPLETE ===\n";
echo "All caches have been cleared.\n";
echo "The application should now use: https://grabbaskets.com\n";
