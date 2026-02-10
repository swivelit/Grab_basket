<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Running Delivery Partner Login Diagnostic Test\n";
echo "==========================================\n\n";

try {
    // 1. Check Session Configuration
    echo "1. Checking Session Configuration...\n";
    $sessionConfig = config('session');
    echo "Session Driver: " . $sessionConfig['driver'] . "\n";
    echo "Session Lifetime: " . $sessionConfig['lifetime'] . " minutes\n\n";

    // 2. Check Auth Guards Configuration
    echo "2. Checking Auth Guards Configuration...\n";
    $guardConfig = config('auth.guards.delivery_partner');
    echo "Guard Provider: " . $guardConfig['provider'] . "\n";
    echo "Guard Driver: " . $guardConfig['driver'] . "\n\n";

    // 3. Test Database Connection
    echo "3. Testing Database Connection...\n";
    try {
        \DB::connection()->getPdo();
        echo "✓ Database connection successful\n\n";
    } catch (\Exception $e) {
        echo "✗ Database connection failed: " . $e->getMessage() . "\n\n";
    }

    // 4. Test Auth Controller Instantiation
    echo "4. Testing Auth Controller...\n";
    try {
        $controller = new \App\Http\Controllers\DeliveryPartner\SuperFastAuthController();
        echo "✓ Auth Controller instantiated successfully\n\n";
    } catch (\Exception $e) {
        echo "✗ Auth Controller instantiation failed: " . $e->getMessage() . "\n\n";
    }

    // 5. Test Dashboard Controller
    echo "5. Testing Dashboard Controller...\n";
    try {
        $dashboardController = new \App\Http\Controllers\DeliveryPartner\DashboardController();
        echo "✓ Dashboard Controller instantiated successfully\n\n";
    } catch (\Exception $e) {
        echo "✗ Dashboard Controller instantiation failed: " . $e->getMessage() . "\n\n";
    }

    // 6. Check Route Configuration
    echo "6. Checking Route Configuration...\n";
    $routes = \Route::getRoutes();
    $loginRoute = null;
    $dashboardRoute = null;

    foreach ($routes as $route) {
        if ($route->getName() === 'delivery-partner.login.post') {
            $loginRoute = $route;
        }
        if ($route->getName() === 'delivery-partner.dashboard') {
            $dashboardRoute = $route;
        }
    }

    echo $loginRoute ? "✓ Login route exists\n" : "✗ Login route missing\n";
    echo $dashboardRoute ? "✓ Dashboard route exists\n\n" : "✗ Dashboard route missing\n\n";

    // 7. Test Login Flow
    echo "7. Testing Login Flow...\n";
    // Create a test request
    $request = \Illuminate\Http\Request::create('/delivery-partner/login', 'POST', [
        'login' => '9659993496',
        'password' => 'test123'
    ]);

    try {
        $controller = new \App\Http\Controllers\DeliveryPartner\SuperFastAuthController();
        $response = $controller->login($request);
        
        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            echo "✓ Login returns proper redirect response\n";
            echo "Redirect Target: " . $response->getTargetUrl() . "\n\n";
        } else {
            echo "✗ Login does not return redirect response\n";
            echo "Response Type: " . get_class($response) . "\n\n";
        }
    } catch (\Exception $e) {
        echo "✗ Login test failed: " . $e->getMessage() . "\n\n";
    }

    // 8. Memory Usage Check
    echo "8. Checking Memory Usage...\n";
    $memoryUsage = memory_get_usage(true) / 1024 / 1024;
    echo "Current Memory Usage: " . round($memoryUsage, 2) . " MB\n";
    
    $peakMemory = memory_get_peak_usage(true) / 1024 / 1024;
    echo "Peak Memory Usage: " . round($peakMemory, 2) . " MB\n\n";

    // 9. Cache Configuration
    echo "9. Checking Cache Configuration...\n";
    $cacheDriver = config('cache.default');
    $cacheStore = config('cache.stores.' . $cacheDriver);
    echo "Cache Driver: " . $cacheDriver . "\n";
    echo "Cache Store Type: " . ($cacheStore['driver'] ?? 'undefined') . "\n\n";

} catch (\Exception $e) {
    echo "\n❌ Fatal Error:\n";
    echo $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}