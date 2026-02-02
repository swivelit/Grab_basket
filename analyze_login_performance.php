<?php
/**
 * Login Performance Analysis Script
 * Analyzes database queries and system performance for delivery partner login
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\DeliveryPartner;

echo "=== DELIVERY PARTNER LOGIN PERFORMANCE ANALYSIS ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Database Performance Analysis
echo "1. DATABASE PERFORMANCE ANALYSIS\n";
echo str_repeat('-', 50) . "\n";

// Check indexes
echo "Checking delivery_partners table indexes...\n";
$indexes = DB::select("SHOW INDEX FROM delivery_partners");
foreach ($indexes as $index) {
    if (in_array($index->Key_name, ['delivery_partners_email_password_index', 'delivery_partners_phone_password_index'])) {
        echo "✓ {$index->Key_name} - Column: {$index->Column_name}\n";
    }
}

// Test query performance
echo "\nTesting login query performance...\n";
$testEmail = 'test@example.com';
$testPhone = '9876543210';

// Email login query timing
$startTime = microtime(true);
$emailUser = DeliveryPartner::where('email', $testEmail)->first();
$emailTime = (microtime(true) - $startTime) * 1000;
echo "Email query time: " . round($emailTime, 2) . "ms\n";

// Phone login query timing
$startTime = microtime(true);
$phoneUser = DeliveryPartner::where('phone', $testPhone)->first();
$phoneTime = (microtime(true) - $startTime) * 1000;
echo "Phone query time: " . round($phoneTime, 2) . "ms\n";

// 2. Database Connection Analysis
echo "\n2. DATABASE CONNECTION ANALYSIS\n";
echo str_repeat('-', 50) . "\n";

$config = config('database.connections.mysql');
echo "Persistent connections: " . ($config['options'][PDO::ATTR_PERSISTENT] ?? 'false') . "\n";
echo "Connection timeout: " . ($config['options'][PDO::ATTR_TIMEOUT] ?? 'default') . "s\n";

// Test connection time
$startTime = microtime(true);
DB::connection()->getPdo();
$connectionTime = (microtime(true) - $startTime) * 1000;
echo "Database connection time: " . round($connectionTime, 2) . "ms\n";

// 3. Authentication Configuration Analysis
echo "\n3. AUTHENTICATION CONFIGURATION ANALYSIS\n";
echo str_repeat('-', 50) . "\n";

$hashConfig = config('hashing');
echo "Hash driver: " . $hashConfig['driver'] . "\n";
echo "Bcrypt rounds: " . ($hashConfig['bcrypt']['rounds'] ?? 'default') . "\n";

// Test password hashing performance
$testPassword = 'testpassword123';
$startTime = microtime(true);
$hashedPassword = bcrypt($testPassword);
$hashTime = (microtime(true) - $startTime) * 1000;
echo "Password hashing time: " . round($hashTime, 2) . "ms\n";

// Test password verification performance
$startTime = microtime(true);
$verified = password_verify($testPassword, $hashedPassword);
$verifyTime = (microtime(true) - $startTime) * 1000;
echo "Password verification time: " . round($verifyTime, 2) . "ms\n";

// 4. System Resource Analysis
echo "\n4. SYSTEM RESOURCE ANALYSIS\n";
echo str_repeat('-', 50) . "\n";

echo "PHP Memory usage: " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB\n";
echo "PHP Memory peak: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Laravel Version: " . app()->version() . "\n";

// 5. Session Configuration Analysis
echo "\n5. SESSION CONFIGURATION ANALYSIS\n";
echo str_repeat('-', 50) . "\n";

$sessionConfig = config('session');
echo "Session driver: " . $sessionConfig['driver'] . "\n";
echo "Session lifetime: " . $sessionConfig['lifetime'] . " minutes\n";
echo "Session cookie secure: " . ($sessionConfig['secure'] ? 'true' : 'false') . "\n";

// 6. Delivery Partners Statistics
echo "\n6. DELIVERY PARTNERS STATISTICS\n";
echo str_repeat('-', 50) . "\n";

$totalPartners = DeliveryPartner::count();
$activePartners = DeliveryPartner::where('status', 'active')->count();
$pendingPartners = DeliveryPartner::where('status', 'pending')->count();

echo "Total partners: {$totalPartners}\n";
echo "Active partners: {$activePartners}\n";
echo "Pending partners: {$pendingPartners}\n";

// Check for duplicate emails/phones that could slow down login
$duplicateEmails = DB::select("
    SELECT email, COUNT(*) as count 
    FROM delivery_partners 
    WHERE email IS NOT NULL 
    GROUP BY email 
    HAVING COUNT(*) > 1
");

$duplicatePhones = DB::select("
    SELECT phone, COUNT(*) as count 
    FROM delivery_partners 
    WHERE phone IS NOT NULL 
    GROUP BY phone 
    HAVING COUNT(*) > 1
");

if (count($duplicateEmails) > 0) {
    echo "⚠️  WARNING: Found " . count($duplicateEmails) . " duplicate emails\n";
}

if (count($duplicatePhones) > 0) {
    echo "⚠️  WARNING: Found " . count($duplicatePhones) . " duplicate phones\n";
}

// 7. Performance Recommendations
echo "\n7. PERFORMANCE RECOMMENDATIONS\n";
echo str_repeat('-', 50) . "\n";

$recommendations = [];

if ($emailTime > 50) {
    $recommendations[] = "Email query is slow ({$emailTime}ms) - consider optimizing indexes";
}

if ($phoneTime > 50) {
    $recommendations[] = "Phone query is slow ({$phoneTime}ms) - consider optimizing indexes";
}

if ($connectionTime > 10) {
    $recommendations[] = "Database connection is slow ({$connectionTime}ms) - check network/server";
}

if ($hashTime > 100) {
    $recommendations[] = "Password hashing is slow ({$hashTime}ms) - consider reducing bcrypt rounds";
}

if ($verifyTime > 100) {
    $recommendations[] = "Password verification is slow ({$verifyTime}ms) - consider reducing bcrypt rounds";
}

if (count($duplicateEmails) > 0 || count($duplicatePhones) > 0) {
    $recommendations[] = "Clean up duplicate email/phone entries to improve query performance";
}

if (empty($recommendations)) {
    echo "✅ No performance issues detected. System is optimized!\n";
} else {
    foreach ($recommendations as $i => $recommendation) {
        echo ($i + 1) . ". {$recommendation}\n";
    }
}

// 8. Recent Login Performance from Logs
echo "\n8. RECENT LOGIN PERFORMANCE FROM LOGS\n";
echo str_repeat('-', 50) . "\n";

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $loginLogs = [];
    
    // Extract login performance logs
    preg_match_all('/DeliveryPartner Login.*?total_time_ms.*?(\d+\.?\d*)/', $logContent, $matches);
    
    if (!empty($matches[1])) {
        $times = array_map('floatval', $matches[1]);
        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);
        
        echo "Recent login attempts: " . count($times) . "\n";
        echo "Average login time: " . round($avgTime, 2) . "ms\n";
        echo "Fastest login: " . round($minTime, 2) . "ms\n";
        echo "Slowest login: " . round($maxTime, 2) . "ms\n";
        
        if ($avgTime > 1000) {
            echo "⚠️  WARNING: Average login time exceeds 1 second!\n";
        }
    } else {
        echo "No recent login performance data found in logs.\n";
        echo "Try logging in to generate performance data.\n";
    }
} else {
    echo "Laravel log file not found.\n";
}

echo "\n=== ANALYSIS COMPLETE ===\n";
echo "For real-time monitoring, check: https://grabbaskets.laravel.cloud/delivery-partner/login\n";
echo "Log file location: {$logFile}\n";
?>