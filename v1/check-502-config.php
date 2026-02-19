<?php

// Check Server Configuration for 502 Errors
echo "=================================================\n";
echo "SERVER CONFIGURATION CHECK - 502 Error Analysis\n";
echo "=================================================\n\n";

// 1. PHP Settings
echo "1. PHP Configuration\n";
echo "-------------------\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . " seconds\n";
echo "Max Input Time: " . ini_get('max_input_time') . " seconds\n";
echo "Post Max Size: " . ini_get('post_max_size') . "\n";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
echo "Default Socket Timeout: " . ini_get('default_socket_timeout') . " seconds\n";
echo "\n";

// 2. Check if FastCGI
echo "2. Server API\n";
echo "-------------\n";
echo "SAPI: " . php_sapi_name() . "\n";

if (function_exists('apache_get_modules')) {
    echo "Apache Modules: " . implode(', ', apache_get_modules()) . "\n";
}
echo "\n";

// 3. Memory Usage
echo "3. Current Memory Usage\n";
echo "----------------------\n";
echo "Current: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
echo "Peak: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
echo "\n";

// 4. Check for common 502 causes
echo "4. Common 502 Error Causes\n";
echo "-------------------------\n";

$issues = [];

// Check memory
$memLimit = ini_get('memory_limit');
if (preg_match('/(\d+)M/', $memLimit, $matches)) {
    if ($matches[1] < 512) {
        $issues[] = "⚠️  Memory limit ({$memLimit}) may be too low for large PDF exports";
    } else {
        echo "✅ Memory limit sufficient: {$memLimit}\n";
    }
}

// Check execution time
$execTime = ini_get('max_execution_time');
if ($execTime > 0 && $execTime < 300) {
    $issues[] = "⚠️  Max execution time ({$execTime}s) may be too low for large exports";
} else {
    echo "✅ Max execution time adequate: " . ($execTime == 0 ? 'unlimited' : $execTime . 's') . "\n";
}

// Check for opcache
if (function_exists('opcache_get_status')) {
    $opcache = opcache_get_status();
    if ($opcache && isset($opcache['opcache_enabled']) && $opcache['opcache_enabled']) {
        echo "✅ OPcache enabled\n";
    }
}

echo "\n";

// 5. Test PDF generation capacity
echo "5. PDF Generation Test\n";
echo "---------------------\n";

try {
    require __DIR__.'/vendor/autoload.php';
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    $seller = \App\Models\User::where('role', 'seller')->first();
    if ($seller) {
        $productCount = \App\Models\Product::where('seller_id', $seller->id)->count();
        echo "Products to export: {$productCount}\n";
        
        if ($productCount > 500) {
            echo "⚠️  WARNING: {$productCount} products may cause timeout\n";
            echo "   Recommended: Export in batches of 100-200 products\n";
        } else if ($productCount > 200) {
            echo "⚠️  CAUTION: {$productCount} products may take 2-5 minutes\n";
        } else {
            echo "✅ Product count manageable\n";
        }
    }
} catch (\Exception $e) {
    echo "Could not check product count: " . $e->getMessage() . "\n";
}

echo "\n";

// 6. Recommendations
echo "6. Recommendations for 502 Errors\n";
echo "--------------------------------\n";

if (!empty($issues)) {
    foreach ($issues as $issue) {
        echo $issue . "\n";
    }
    echo "\n";
}

echo "✅ RECOMMENDED PHP.INI SETTINGS:\n";
echo "   memory_limit = 2G\n";
echo "   max_execution_time = 900\n";
echo "   max_input_time = 900\n";
echo "   default_socket_timeout = 900\n";
echo "\n";

echo "✅ IF USING NGINX (check nginx.conf):\n";
echo "   fastcgi_read_timeout 900;\n";
echo "   fastcgi_send_timeout 900;\n";
echo "   proxy_read_timeout 900;\n";
echo "   proxy_send_timeout 900;\n";
echo "\n";

echo "✅ IF USING APACHE (check httpd.conf):\n";
echo "   Timeout 900\n";
echo "   ProxyTimeout 900\n";
echo "   FcgidIOTimeout 900\n";
echo "\n";

echo "✅ LARAVEL OPTIMIZATION:\n";
echo "   php artisan config:cache\n";
echo "   php artisan route:cache\n";
echo "   php artisan view:cache\n";
echo "\n";

// 7. Check Laravel logs for errors
echo "7. Recent Laravel Errors\n";
echo "-----------------------\n";
$logFile = __DIR__ . '/storage/logs/laravel.log';
if (file_exists($logFile) && filesize($logFile) > 0) {
    $lines = file($logFile);
    $recentErrors = array_slice($lines, -20);
    
    $hasErrors = false;
    foreach ($recentErrors as $line) {
        if (stripos($line, 'error') !== false || stripos($line, 'fatal') !== false) {
            echo substr($line, 0, 150) . "...\n";
            $hasErrors = true;
        }
    }
    
    if (!$hasErrors) {
        echo "✅ No recent errors in Laravel log\n";
    }
} else {
    echo "ℹ️  No Laravel log file or empty\n";
}

echo "\n";
echo "=================================================\n";
echo "DIAGNOSIS COMPLETE\n";
echo "=================================================\n";
