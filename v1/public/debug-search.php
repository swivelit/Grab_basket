<?php
/**
 * Web-based diagnostic for guest search 500 error
 * This should be accessed via web browser to test the actual route
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Guest Search 500 Error Web Diagnostic</h1>";
echo "<hr>";

try {
    // Test basic PHP functionality
    echo "<h2>1. Basic PHP Test</h2>";
    echo "✅ PHP is working: " . PHP_VERSION . "<br>";
    echo "✅ Current time: " . date('Y-m-d H:i:s') . "<br><br>";

    // Test if we can include Laravel files
    echo "<h2>2. Laravel Bootstrap Test</h2>";
    
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        echo "✅ Composer autoloader: EXISTS<br>";
        require_once __DIR__ . '/vendor/autoload.php';
        echo "✅ Autoloader: LOADED<br>";
    } else {
        echo "❌ Composer autoloader: MISSING<br>";
        exit;
    }

    if (file_exists(__DIR__ . '/bootstrap/app.php')) {
        echo "✅ Laravel bootstrap: EXISTS<br>";
        $app = require_once __DIR__ . '/bootstrap/app.php';
        echo "✅ Laravel app: LOADED<br>";
    } else {
        echo "❌ Laravel bootstrap: MISSING<br>";
        exit;
    }

    // Test kernel
    echo "<h2>3. Laravel Kernel Test</h2>";
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "✅ HTTP Kernel: CREATED<br>";

    // Simulate a web request
    $request = Illuminate\Http\Request::create('/products?q=maya+oils', 'GET');
    echo "✅ Test request: CREATED<br>";
    echo "📍 Request URL: " . $request->fullUrl() . "<br>";
    echo "📍 Request method: " . $request->method() . "<br>";
    echo "📍 Query params: " . json_encode($request->query()) . "<br><br>";

    // Test the route resolution
    echo "<h2>4. Route Resolution Test</h2>";
    try {
        $response = $kernel->handle($request);
        echo "✅ Route handled successfully<br>";
        echo "📍 Response status: " . $response->getStatusCode() . "<br>";
        
        if ($response->getStatusCode() == 500) {
            echo "❌ 500 Internal Server Error detected<br>";
            echo "📋 Response content preview:<br>";
            echo "<pre style='background:#f5f5f5;padding:10px;max-height:300px;overflow:auto;'>";
            echo htmlspecialchars(substr($response->getContent(), 0, 2000));
            echo "</pre>";
        } else {
            echo "✅ Response looks good<br>";
        }
        
    } catch (\Exception $e) {
        echo "❌ Route handling failed<br>";
        echo "🔍 Error: " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
        echo "📋 Stack trace:<br>";
        echo "<pre style='background:#f5f5f5;padding:10px;max-height:300px;overflow:auto;'>";
        echo htmlspecialchars($e->getTraceAsString());
        echo "</pre>";
    }

    echo "<br><h2>5. Direct Controller Test</h2>";
    try {
        // Test direct controller instantiation
        $controller = new App\Http\Controllers\OptimizedBuyerController();
        echo "✅ OptimizedBuyerController: INSTANTIATED<br>";
        
        // Test method call
        $testRequest = new Illuminate\Http\Request();
        $testRequest->merge(['q' => 'maya oils']);
        
        $result = $controller->guestSearch($testRequest);
        echo "✅ guestSearch method: EXECUTED<br>";
        echo "📍 Result type: " . get_class($result) . "<br>";
        
    } catch (\Exception $e) {
        echo "❌ Direct controller test failed<br>";
        echo "🔍 Error: " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
        echo "📋 Stack trace:<br>";
        echo "<pre style='background:#f5f5f5;padding:10px;max-height:300px;overflow:auto;'>";
        echo htmlspecialchars($e->getTraceAsString());
        echo "</pre>";
    }

    echo "<br><h2>6. Environment Check</h2>";
    echo "✅ App environment: " . (app()->environment() ?? 'unknown') . "<br>";
    echo "✅ Debug mode: " . (config('app.debug') ? 'ON' : 'OFF') . "<br>";
    echo "✅ App URL: " . (config('app.url') ?? 'not set') . "<br>";

} catch (\Exception $e) {
    echo "<h2>❌ Critical Error</h2>";
    echo "Error: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<pre style='background:#f5f5f5;padding:10px;'>";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
}

echo "<br><hr>";
echo "<p>Diagnostic completed at: " . date('Y-m-d H:i:s') . "</p>";
?>