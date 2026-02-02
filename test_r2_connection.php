<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Testing R2 Storage Connection\n";
echo "===============================================\n\n";

// Check configuration
echo "📋 R2 Configuration:\n";
echo "   Bucket: " . config('filesystems.disks.r2.bucket') . "\n";
echo "   Endpoint: " . config('filesystems.disks.r2.endpoint') . "\n";
echo "   Region: " . config('filesystems.disks.r2.region') . "\n";
echo "   Key: " . (config('filesystems.disks.r2.key') ? '***' . substr(config('filesystems.disks.r2.key'), -4) : 'NOT SET') . "\n";
echo "   Secret: " . (config('filesystems.disks.r2.secret') ? '***' . substr(config('filesystems.disks.r2.secret'), -4) : 'NOT SET') . "\n\n";

// Check if we're on Laravel Cloud
$isLaravelCloud = app()->environment('production') && 
                  (request()->getHost() === 'grabbaskets.laravel.cloud' || 
                   str_contains(request()->getHost() ?? '', '.laravel.cloud'));

echo "🌍 Environment Detection:\n";
echo "   APP_ENV: " . app()->environment() . "\n";
echo "   Request Host: " . (request()->getHost() ?? 'CLI mode') . "\n";
echo "   Detected as Laravel Cloud: " . ($isLaravelCloud ? '✅ YES' : '❌ NO') . "\n\n";

// Test R2 connection
echo "🧪 Testing R2 Connection:\n";
try {
    // Try to list files
    $files = Storage::disk('r2')->files('products', false);
    echo "   ✅ R2 connection successful!\n";
    echo "   📁 Found " . count($files) . " files in products/\n";
    
    if (count($files) > 0) {
        echo "\n   First 5 files:\n";
        foreach (array_slice($files, 0, 5) as $file) {
            echo "     - {$file}\n";
        }
    }
} catch (\Throwable $e) {
    echo "   ❌ R2 connection FAILED!\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   Class: " . get_class($e) . "\n";
    
    if ($e->getPrevious()) {
        echo "   Previous Error: " . $e->getPrevious()->getMessage() . "\n";
    }
}

// Test write permission
echo "\n🧪 Testing R2 Write Permission:\n";
try {
    $testContent = 'Test file created at ' . date('Y-m-d H:i:s');
    $testPath = 'test-' . time() . '.txt';
    
    Storage::disk('r2')->put($testPath, $testContent);
    
    if (Storage::disk('r2')->exists($testPath)) {
        echo "   ✅ Write test successful!\n";
        echo "   📝 Created: {$testPath}\n";
        
        // Read it back
        $readContent = Storage::disk('r2')->get($testPath);
        if ($readContent === $testContent) {
            echo "   ✅ Read test successful!\n";
        }
        
        // Delete test file
        Storage::disk('r2')->delete($testPath);
        echo "   🗑️ Test file cleaned up\n";
    }
} catch (\Throwable $e) {
    echo "   ❌ Write test FAILED!\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   Class: " . get_class($e) . "\n";
    
    if (method_exists($e, 'getResponse') && $e->getResponse()) {
        echo "   Response: " . $e->getResponse()->getBody() . "\n";
    }
}

// Check Laravel filesystem config
echo "\n📋 Filesystem Configuration:\n";
echo "   Default disk: " . config('filesystems.default') . "\n";
echo "   Public disk root: " . config('filesystems.disks.public.root') . "\n";
echo "   R2 driver: " . config('filesystems.disks.r2.driver') . "\n";

echo "\n===============================================\n";
echo "💡 If R2 connection failed, check:\n";
echo "   1. AWS_ACCESS_KEY_ID in .env\n";
echo "   2. AWS_SECRET_ACCESS_KEY in .env\n";
echo "   3. AWS_ENDPOINT in .env\n";
echo "   4. AWS_BUCKET in .env\n";
echo "   5. Internet connection\n";
