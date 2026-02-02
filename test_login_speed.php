<?php
/**
 * Quick Login Test Script
 * Tests the actual delivery partner login speed
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\DeliveryPartner;

echo "=== DELIVERY PARTNER LOGIN SPEED TEST ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Test 1: Database connection speed
echo "1. DATABASE CONNECTION TEST\n";
echo str_repeat('-', 40) . "\n";

$startTime = microtime(true);
$connection = DB::connection()->getPdo();
$connectionTime = (microtime(true) - $startTime) * 1000;
echo "Database connection: " . round($connectionTime, 2) . "ms\n\n";

// Test 2: User lookup speed (raw query)
echo "2. USER LOOKUP SPEED (Raw Query)\n";
echo str_repeat('-', 40) . "\n";

$testEmail = 'test@example.com';
$testPhone = '9876543210';

// Test email lookup
$startTime = microtime(true);
$emailUser = DB::table('delivery_partners')
    ->select(['id', 'name', 'email', 'phone', 'password', 'status'])
    ->where('email', $testEmail)
    ->first();
$emailTime = (microtime(true) - $startTime) * 1000;
echo "Email lookup (raw): " . round($emailTime, 2) . "ms\n";

// Test phone lookup
$startTime = microtime(true);
$phoneUser = DB::table('delivery_partners')
    ->select(['id', 'name', 'email', 'phone', 'password', 'status'])
    ->where('phone', $testPhone)
    ->first();
$phoneTime = (microtime(true) - $startTime) * 1000;
echo "Phone lookup (raw): " . round($phoneTime, 2) . "ms\n\n";

// Test 3: Password verification speed
echo "3. PASSWORD VERIFICATION SPEED\n";
echo str_repeat('-', 40) . "\n";

$testPassword = 'testpassword123';
$hashedPassword = '$2y$06$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // bcrypt with 6 rounds

$startTime = microtime(true);
$verified = Hash::check($testPassword, $hashedPassword);
$verifyTime = (microtime(true) - $startTime) * 1000;
echo "Password verification: " . round($verifyTime, 2) . "ms (result: " . ($verified ? 'true' : 'false') . ")\n\n";

// Test 4: Full login simulation
echo "4. FULL LOGIN SIMULATION\n";
echo str_repeat('-', 40) . "\n";

$simulationStartTime = microtime(true);

// Step 1: Input processing
$login = 'test@example.com';
$password = 'password123';
$isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
$loginField = $isEmail ? 'email' : 'phone';
$processTime = (microtime(true) - $simulationStartTime) * 1000;

// Step 2: Database lookup
$lookupStartTime = microtime(true);
$user = DB::table('delivery_partners')
    ->select(['id', 'name', 'email', 'phone', 'password', 'status'])
    ->where($loginField, $login)
    ->first();
$lookupTime = (microtime(true) - $lookupStartTime) * 1000;

// Step 3: Password verification (if user exists)
$passwordCheckTime = 0;
if ($user) {
    $passwordStartTime = microtime(true);
    $passwordValid = Hash::check($password, $user->password);
    $passwordCheckTime = (microtime(true) - $passwordStartTime) * 1000;
}

$totalSimulationTime = (microtime(true) - $simulationStartTime) * 1000;

echo "Input processing: " . round($processTime, 2) . "ms\n";
echo "Database lookup: " . round($lookupTime, 2) . "ms\n";
echo "Password check: " . round($passwordCheckTime, 2) . "ms\n";
echo "TOTAL SIMULATION: " . round($totalSimulationTime, 2) . "ms\n\n";

// Test 5: Performance Analysis
echo "5. PERFORMANCE ANALYSIS\n";
echo str_repeat('-', 40) . "\n";

$bottlenecks = [];

if ($connectionTime > 10) {
    $bottlenecks[] = "Database connection slow ({$connectionTime}ms)";
}

if ($emailTime > 50 || $phoneTime > 50) {
    $bottlenecks[] = "Database queries slow (Email: {$emailTime}ms, Phone: {$phoneTime}ms)";
}

if ($verifyTime > 20) {
    $bottlenecks[] = "Password verification slow ({$verifyTime}ms)";
}

if ($totalSimulationTime > 200) {
    $bottlenecks[] = "Total login process slow ({$totalSimulationTime}ms)";
}

if (empty($bottlenecks)) {
    echo "✅ No performance bottlenecks detected!\n";
    echo "Expected login time: ~" . round($totalSimulationTime, 0) . "ms\n";
} else {
    echo "⚠️  Performance bottlenecks found:\n";
    foreach ($bottlenecks as $i => $bottleneck) {
        echo "   " . ($i + 1) . ". {$bottleneck}\n";
    }
}

echo "\n=== SPEED TEST COMPLETE ===\n";
echo "Recommendation: Login should complete in " . round($totalSimulationTime, 0) . "ms or less\n";
?>