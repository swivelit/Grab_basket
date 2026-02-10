<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Environment Detection Test\n";
echo "===============================================\n\n";

echo "📋 Environment Variables:\n";
echo "   APP_ENV: " . env('APP_ENV') . "\n";
echo "   APP_URL: " . env('APP_URL') . "\n";
echo "   Request Host: " . (request()->getHost() ?? 'CLI mode') . "\n";
echo "   Server Name: " . ($_SERVER['SERVER_NAME'] ?? 'Not set') . "\n";
echo "   Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Not set') . "\n\n";

echo "🔍 Detection Logic:\n";

// Current logic
$isLaravelCloud1 = app()->environment('production') && 
                   (request()->getHost() === 'grabbaskets.laravel.cloud' || 
                    str_contains(request()->getHost() ?? '', '.laravel.cloud'));
echo "   Current logic (request host): " . ($isLaravelCloud1 ? '✅ Laravel Cloud' : '❌ Not Laravel Cloud') . "\n";

// Improved logic 1: Check for Laravel Cloud specific env var
$isLaravelCloud2 = env('LARAVEL_CLOUD_DEPLOYMENT', false) === true || 
                   env('VAPOR_ENVIRONMENT') !== null;
echo "   Check LARAVEL_CLOUD_DEPLOYMENT: " . ($isLaravelCloud2 ? '✅ Laravel Cloud' : '❌ Not Laravel Cloud') . "\n";

// Improved logic 2: Check if running in actual cloud (not just config)
$isLaravelCloud3 = app()->environment('production') && 
                   php_sapi_name() !== 'cli' &&
                   isset($_SERVER['SERVER_NAME']) &&
                   str_contains($_SERVER['SERVER_NAME'] ?? '', '.laravel.cloud');
echo "   Check SERVER_NAME: " . ($isLaravelCloud3 ? '✅ Laravel Cloud' : '❌ Not Laravel Cloud') . "\n";

// Recommended: Combine checks
$isLaravelCloud = (
    env('LARAVEL_CLOUD_DEPLOYMENT') === true || 
    (app()->environment('production') && 
     isset($_SERVER['SERVER_NAME']) && 
     str_contains($_SERVER['SERVER_NAME'], '.laravel.cloud'))
);
echo "   RECOMMENDED: " . ($isLaravelCloud ? '✅ Laravel Cloud' : '❌ Not Laravel Cloud') . "\n\n";

echo "💡 Recommendation:\n";
if (!$isLaravelCloud && app()->environment('production')) {
    echo "   ⚠️ You're in production mode locally!\n";
    echo "   📝 Current behavior: Trying to save to R2 (because APP_ENV=production)\n";
    echo "   ✅ Solution: Images will save to R2 locally (works, but not ideal)\n\n";
    echo "   💡 To fix (optional):\n";
    echo "      1. Change APP_ENV=local in your .env\n";
    echo "      2. OR: Add LARAVEL_CLOUD_DEPLOYMENT=false to your .env\n";
    echo "      3. Then images will save to local storage for testing\n";
} else {
    echo "   ✅ Environment detection is correct!\n";
}

echo "\n===============================================\n";
