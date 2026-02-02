<?php
/**
 * Database Query Optimization Script
 * Adds indexes and analyzes slow queries for delivery partner authentication
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DELIVERY PARTNER DATABASE OPTIMIZATION ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Check and optimize indexes
echo "1. CHECKING CURRENT INDEXES\n";
echo str_repeat('-', 50) . "\n";

$indexes = DB::select("SHOW INDEX FROM delivery_partners");
$existingIndexes = [];
foreach ($indexes as $index) {
    $existingIndexes[] = $index->Key_name;
}

echo "Existing indexes: " . implode(', ', array_unique($existingIndexes)) . "\n\n";

// 2. Add optimized single-column indexes for faster lookups
echo "2. ADDING OPTIMIZED SINGLE-COLUMN INDEXES\n";
echo str_repeat('-', 50) . "\n";

try {
    // Email index (if not exists)
    if (!in_array('delivery_partners_email_optimized_index', $existingIndexes)) {
        DB::statement("CREATE INDEX delivery_partners_email_optimized_index ON delivery_partners (email)");
        echo "✓ Added delivery_partners_email_optimized_index\n";
    } else {
        echo "✓ delivery_partners_email_optimized_index already exists\n";
    }

    // Phone index (if not exists)  
    if (!in_array('delivery_partners_phone_optimized_index', $existingIndexes)) {
        DB::statement("CREATE INDEX delivery_partners_phone_optimized_index ON delivery_partners (phone)");
        echo "✓ Added delivery_partners_phone_optimized_index\n";
    } else {
        echo "✓ delivery_partners_phone_optimized_index already exists\n";
    }

    // Status index for quick filtering
    if (!in_array('delivery_partners_status_index', $existingIndexes)) {
        DB::statement("CREATE INDEX delivery_partners_status_index ON delivery_partners (status)");
        echo "✓ Added delivery_partners_status_index\n";
    } else {
        echo "✓ delivery_partners_status_index already exists\n";
    }

    // Combined status + verified index
    if (!in_array('delivery_partners_status_verified_index', $existingIndexes)) {
        DB::statement("CREATE INDEX delivery_partners_status_verified_index ON delivery_partners (status, is_verified)");
        echo "✓ Added delivery_partners_status_verified_index\n";
    } else {
        echo "✓ delivery_partners_status_verified_index already exists\n";
    }

} catch (Exception $e) {
    echo "⚠️  Index creation error: " . $e->getMessage() . "\n";
}

// 3. Analyze table statistics
echo "\n3. TABLE STATISTICS ANALYSIS\n";
echo str_repeat('-', 50) . "\n";

$tableStats = DB::select("ANALYZE TABLE delivery_partners");
foreach ($tableStats as $stat) {
    echo "Table: {$stat->Table}, Status: {$stat->Msg_text}\n";
}

// 4. Test optimized queries
echo "\n4. TESTING OPTIMIZED QUERY PERFORMANCE\n";
echo str_repeat('-', 50) . "\n";

// Test email lookup
$startTime = microtime(true);
$emailResult = DB::select("SELECT id, name, email, phone, password, status, is_verified, last_active_at FROM delivery_partners WHERE email = ? LIMIT 1", ['test@example.com']);
$emailTime = (microtime(true) - $startTime) * 1000;
echo "Optimized email query: " . round($emailTime, 2) . "ms\n";

// Test phone lookup  
$startTime = microtime(true);
$phoneResult = DB::select("SELECT id, name, email, phone, password, status, is_verified, last_active_at FROM delivery_partners WHERE phone = ? LIMIT 1", ['9876543210']);
$phoneTime = (microtime(true) - $startTime) * 1000;
echo "Optimized phone query: " . round($phoneTime, 2) . "ms\n";

// 5. Query plan analysis
echo "\n5. QUERY EXECUTION PLAN ANALYSIS\n";
echo str_repeat('-', 50) . "\n";

$emailExplain = DB::select("EXPLAIN SELECT id, name, email, phone, password, status, is_verified, last_active_at FROM delivery_partners WHERE email = 'test@example.com' LIMIT 1");
echo "Email query execution plan:\n";
foreach ($emailExplain as $plan) {
    echo "  - Type: {$plan->select_type}, Key: " . ($plan->key ?? 'none') . ", Rows: {$plan->rows}\n";
}

$phoneExplain = DB::select("EXPLAIN SELECT id, name, email, phone, password, status, is_verified, last_active_at FROM delivery_partners WHERE phone = '9876543210' LIMIT 1");
echo "\nPhone query execution plan:\n";
foreach ($phoneExplain as $plan) {
    echo "  - Type: {$plan->select_type}, Key: " . ($plan->key ?? 'none') . ", Rows: {$plan->rows}\n";
}

// 6. Table optimization
echo "\n6. TABLE OPTIMIZATION\n";
echo str_repeat('-', 50) . "\n";

try {
    $optimize = DB::select("OPTIMIZE TABLE delivery_partners");
    foreach ($optimize as $result) {
        echo "Optimize result: {$result->Msg_text}\n";
    }
} catch (Exception $e) {
    echo "⚠️  Table optimization error: " . $e->getMessage() . "\n";
}

// 7. Final recommendations
echo "\n7. PERFORMANCE RECOMMENDATIONS\n";
echo str_repeat('-', 50) . "\n";

$recommendations = [];

if ($emailTime > 20) {
    $recommendations[] = "Email query still slow ({$emailTime}ms) - check database server performance";
}

if ($phoneTime > 20) {
    $recommendations[] = "Phone query still slow ({$phoneTime}ms) - check database server performance";
}

// Check for data issues
$totalRows = DB::scalar("SELECT COUNT(*) FROM delivery_partners");
$duplicateEmails = DB::scalar("SELECT COUNT(*) - COUNT(DISTINCT email) FROM delivery_partners WHERE email IS NOT NULL");
$duplicatePhones = DB::scalar("SELECT COUNT(*) - COUNT(DISTINCT phone) FROM delivery_partners WHERE phone IS NOT NULL");

if ($duplicateEmails > 0) {
    $recommendations[] = "Found {$duplicateEmails} duplicate emails - clean up for better performance";
}

if ($duplicatePhones > 0) {
    $recommendations[] = "Found {$duplicatePhones} duplicate phones - clean up for better performance";
}

if ($totalRows > 10000) {
    $recommendations[] = "Large table ({$totalRows} rows) - consider partitioning or archiving old data";
}

if (empty($recommendations)) {
    echo "✅ Database is optimally configured for fast authentication!\n";
    echo "Email queries: ~" . round($emailTime, 1) . "ms\n";
    echo "Phone queries: ~" . round($phoneTime, 1) . "ms\n";
} else {
    foreach ($recommendations as $i => $recommendation) {
        echo ($i + 1) . ". {$recommendation}\n";
    }
}

echo "\n=== DATABASE OPTIMIZATION COMPLETE ===\n";
echo "Next: Test login performance at https://grabbaskets.laravel.cloud/delivery-partner/login\n";
?>