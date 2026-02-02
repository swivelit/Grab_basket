<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

// Clear all caches first
\Artisan::call('cache:clear');
\Artisan::call('config:clear');
\Artisan::call('route:clear');
\Artisan::call('view:clear');

echo "Cache cleared\n";

// Check session configuration
echo "\nSession Configuration:\n";
echo "Driver: " . config('session.driver') . "\n";
echo "Lifetime: " . config('session.lifetime') . " minutes\n";
echo "Domain: " . (config('session.domain') ?? 'null') . "\n";
echo "Secure: " . (config('session.secure') ? 'true' : 'false') . "\n";
echo "Path: " . config('session.path') . "\n";

// Check if sessions table exists and has proper structure
$hasSessionsTable = DB::getSchemaBuilder()->hasTable('sessions');
echo "\nSessions Table:\n";
echo "Exists: " . ($hasSessionsTable ? 'Yes' : 'No') . "\n";

if ($hasSessionsTable) {
    $sessionColumns = DB::getSchemaBuilder()->getColumnListing('sessions');
    echo "Columns: " . implode(', ', $sessionColumns) . "\n";
    echo "Total active sessions: " . DB::table('sessions')->count() . "\n";
}

// Check delivery partner authentication configuration
echo "\nDelivery Partner Auth Configuration:\n";
$authConfig = config('auth.guards.delivery_partner');
echo "Guard Provider: " . $authConfig['provider'] . "\n";
echo "Guard Driver: " . $authConfig['driver'] . "\n";

// Check delivery partner provider configuration
$providerConfig = config('auth.providers.' . $authConfig['provider']);
echo "Model: " . $providerConfig['model'] . "\n";

// Check middleware configuration
echo "\nMiddleware Configuration:\n";
$kernel = $app->make(\App\Http\Kernel::class);
$middleware = $kernel->getMiddlewareGroups()['web'];
echo "Web middleware count: " . count($middleware) . "\n";
foreach ($middleware as $m) {
    echo "- " . $m . "\n";
}

// Performance test
echo "\nPerformance Test:\n";
$start = microtime(true);
$sessionStart = microtime(true);
Session::start();
$sessionTime = (microtime(true) - $sessionStart) * 1000;
echo "Session start time: {$sessionTime}ms\n";

// Test database connection
$dbStart = microtime(true);
try {
    DB::connection()->getPdo();
    $dbTime = (microtime(true) - $dbStart) * 1000;
    echo "Database connection time: {$dbTime}ms\n";
} catch (\Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}

// Check for stuck sessions
if ($hasSessionsTable) {
    $oldSessions = DB::table('sessions')
        ->where('last_activity', '<', time() - (config('session.lifetime') * 60))
        ->count();
    echo "\nStuck/Expired Sessions: {$oldSessions}\n";
    
    // Clean up old sessions
    DB::table('sessions')
        ->where('last_activity', '<', time() - (config('session.lifetime') * 60))
        ->delete();
    echo "Cleaned up expired sessions\n";
}