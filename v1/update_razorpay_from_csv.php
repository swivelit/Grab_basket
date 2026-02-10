<?php
/**
 * Razorpay CSV Key Updater
 * Updates .env file with Razorpay keys from rzp.csv
 */

require_once 'vendor/autoload.php';

echo "=== RAZORPAY CSV KEY UPDATER ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Check if CSV file exists
$csvFile = 'rzp.csv';
if (!file_exists($csvFile)) {
    echo "❌ Error: rzp.csv file not found!\n";
    echo "Please ensure rzp.csv exists in the root directory.\n\n";
    exit(1);
}

// Read CSV file
echo "📖 Reading rzp.csv file...\n";
$csvContent = file_get_contents($csvFile);
$lines = explode("\n", trim($csvContent));

if (count($lines) < 2) {
    echo "❌ Error: Invalid CSV format!\n";
    echo "Expected format:\nkey_id,key_secret\nrzp_live_xxx,secret_xxx\n\n";
    exit(1);
}

// Parse CSV
$header = str_getcsv($lines[0]);
$data = str_getcsv($lines[1]);

if (count($header) !== 2 || count($data) !== 2) {
    echo "❌ Error: Invalid CSV structure!\n";
    echo "Expected 2 columns: key_id,key_secret\n\n";
    exit(1);
}

$keyId = trim($data[0]);
$keySecret = trim($data[1]);

echo "✅ CSV file parsed successfully\n";
echo "Key ID: " . substr($keyId, 0, 15) . "...\n";
echo "Key Secret: " . substr($keySecret, 0, 15) . "...\n\n";

// Validate key format
if (!preg_match('/^rzp_(test_|live_)[a-zA-Z0-9]+$/', $keyId)) {
    echo "⚠️  Warning: Key ID doesn't match expected Razorpay format\n";
}

if (strlen($keySecret) < 20) {
    echo "⚠️  Warning: Key secret seems too short\n";
}

// Read current .env file
$envFile = '.env';
if (!file_exists($envFile)) {
    echo "❌ Error: .env file not found!\n";
    exit(1);
}

echo "📝 Updating .env file...\n";
$envContent = file_get_contents($envFile);

// Update or add Razorpay keys
$keyIdPattern = '/^RAZORPAY_KEY_ID=.*$/m';
$keySecretPattern = '/^RAZORPAY_KEY_SECRET=.*$/m';

if (preg_match($keyIdPattern, $envContent)) {
    $envContent = preg_replace($keyIdPattern, "RAZORPAY_KEY_ID={$keyId}", $envContent);
    echo "✅ Updated existing RAZORPAY_KEY_ID\n";
} else {
    $envContent .= "\nRAZORPAY_KEY_ID={$keyId}\n";
    echo "✅ Added new RAZORPAY_KEY_ID\n";
}

if (preg_match($keySecretPattern, $envContent)) {
    $envContent = preg_replace($keySecretPattern, "RAZORPAY_KEY_SECRET={$keySecret}", $envContent);
    echo "✅ Updated existing RAZORPAY_KEY_SECRET\n";
} else {
    $envContent .= "RAZORPAY_KEY_SECRET={$keySecret}\n";
    echo "✅ Added new RAZORPAY_KEY_SECRET\n";
}

// Backup original .env
$backupFile = '.env.backup.' . date('Y-m-d_H-i-s');
copy($envFile, $backupFile);
echo "📦 Created backup: {$backupFile}\n";

// Write updated .env
file_put_contents($envFile, $envContent);
echo "✅ .env file updated successfully\n\n";

// Clear Laravel config cache
echo "🧹 Clearing Laravel configuration cache...\n";
$output = shell_exec('php artisan config:clear 2>&1');
echo $output;

// Test the new configuration
echo "🧪 Testing new Razorpay configuration...\n";
system('php test_razorpay.php');

echo "\n=== UPDATE COMPLETE ===\n";
echo "Razorpay keys have been updated from rzp.csv\n";
echo "Backup created: {$backupFile}\n";
echo "Don't forget to commit changes to git!\n";
?>