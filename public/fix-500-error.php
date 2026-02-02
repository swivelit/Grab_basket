<?php
/**
 * Quick Fix Script for 500 Error
 * Upload this file to /public directory on your server
 * Access via: https://grabbaskets.com/fix-500-error.php
 * DELETE THIS FILE IMMEDIATELY AFTER USE!
 */

// Change to Laravel root directory
chdir(__DIR__ . '/..');

// Security check - remove in production after first use
$secret = isset($_GET['secret']) ? $_GET['secret'] : '';
if ($secret !== 'grabbaskets2025') {
    die('Access denied. Add ?secret=grabbaskets2025 to URL');
}

echo "<h1>GrabBaskets - Emergency Fix Script</h1>";
echo "<pre>";

echo "============================================\n";
echo "Starting Emergency Fix...\n";
echo "============================================\n\n";

// Step 1: Clear Config Cache
echo "1. Clearing config cache...\n";
exec('php artisan config:clear 2>&1', $output1, $return1);
echo "   " . implode("\n   ", $output1) . "\n";
echo "   Status: " . ($return1 === 0 ? "✅ Success" : "❌ Failed") . "\n\n";

// Step 2: Clear Application Cache
echo "2. Clearing application cache...\n";
exec('php artisan cache:clear 2>&1', $output2, $return2);
echo "   " . implode("\n   ", $output2) . "\n";
echo "   Status: " . ($return2 === 0 ? "✅ Success" : "❌ Failed") . "\n\n";

// Step 3: Clear Route Cache
echo "3. Clearing route cache...\n";
exec('php artisan route:clear 2>&1', $output3, $return3);
echo "   " . implode("\n   ", $output3) . "\n";
echo "   Status: " . ($return3 === 0 ? "✅ Success" : "❌ Failed") . "\n\n";

// Step 4: Clear View Cache
echo "4. Clearing view cache...\n";
exec('php artisan view:clear 2>&1', $output4, $return4);
echo "   " . implode("\n   ", $output4) . "\n";
echo "   Status: " . ($return4 === 0 ? "✅ Success" : "❌ Failed") . "\n\n";

// Step 5: Clear Event Cache
echo "5. Clearing event cache...\n";
exec('php artisan event:clear 2>&1', $output5, $return5);
echo "   " . implode("\n   ", $output5) . "\n";
echo "   Status: " . ($return5 === 0 ? "✅ Success" : "❌ Failed") . "\n\n";

// Step 6: Rebuild Config Cache
echo "6. Rebuilding config cache...\n";
exec('php artisan config:cache 2>&1', $output6, $return6);
echo "   " . implode("\n   ", $output6) . "\n";
echo "   Status: " . ($return6 === 0 ? "✅ Success" : "❌ Failed") . "\n\n";

// Step 7: Rebuild Route Cache
echo "7. Rebuilding route cache...\n";
exec('php artisan route:cache 2>&1', $output7, $return7);
echo "   " . implode("\n   ", $output7) . "\n";
echo "   Status: " . ($return7 === 0 ? "✅ Success" : "❌ Failed") . "\n\n";

// Step 8: Optimize
echo "8. Running optimization...\n";
exec('php artisan optimize 2>&1', $output8, $return8);
echo "   " . implode("\n   ", $output8) . "\n";
echo "   Status: " . ($return8 === 0 ? "✅ Success" : "❌ Failed") . "\n\n";

echo "============================================\n";
echo "System Diagnostics:\n";
echo "============================================\n\n";

// Check PHP Version
echo "PHP Version: " . phpversion() . "\n";

// Check Laravel Version
try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "Laravel Version: " . app()->version() . "\n";
    
    // Check Database Connection
    echo "\nDatabase Connection: ";
    try {
        DB::connection()->getPdo();
        echo "✅ Connected\n";
        echo "Categories Count: " . App\Models\Category::count() . "\n";
        echo "Products Count: " . App\Models\Product::count() . "\n";
        echo "Banners Count: " . App\Models\Banner::count() . "\n";
    } catch (\Exception $e) {
        echo "❌ Failed\n";
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    // Check Storage Permissions
    echo "\nStorage Permissions:\n";
    $dirs = [
        'storage/logs' => is_writable(__DIR__ . '/../storage/logs'),
        'storage/framework/sessions' => is_writable(__DIR__ . '/../storage/framework/sessions'),
        'storage/framework/views' => is_writable(__DIR__ . '/../storage/framework/views'),
        'storage/framework/cache' => is_writable(__DIR__ . '/../storage/framework/cache'),
        'bootstrap/cache' => is_writable(__DIR__ . '/../bootstrap/cache'),
    ];
    
    foreach ($dirs as $dir => $writable) {
        echo "   {$dir}: " . ($writable ? "✅ Writable" : "❌ Not Writable") . "\n";
    }
    
    // Test HomeController
    echo "\nTesting HomeController:\n";
    try {
        $controller = new App\Http\Controllers\HomeController();
        $response = $controller->index();
        echo "   ✅ HomeController executed successfully\n";
    } catch (\Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Bootstrap Error: " . $e->getMessage() . "\n";
}

echo "\n============================================\n";
echo "Fix Complete!\n";
echo "============================================\n\n";

echo "Next Steps:\n";
echo "1. Visit: https://grabbaskets.com/\n";
echo "2. Check if the homepage loads\n";
echo "3. DELETE THIS FILE IMMEDIATELY: /public/fix-500-error.php\n";
echo "4. Set APP_DEBUG=false in .env for production\n\n";

echo "If still showing 500 error:\n";
echo "1. Check storage/logs/laravel.log for errors\n";
echo "2. Verify .htaccess is in /public directory\n";
echo "3. Check file permissions (755 for directories, 644 for files)\n";
echo "4. Contact hosting support if database connection fails\n";

echo "</pre>";
?>
