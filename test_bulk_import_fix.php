<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

echo "🧪 TESTING FUTURE BULK IMPORT IMAGE ASSIGNMENT\n";
echo "===============================================\n\n";

// Test the updated handleImageUpload method
$importInstance = new \App\Imports\ProductsImport(null, 1); // No zip file, seller ID 1

// Simulate a row without specific image data
$testRow = [
    'name' => 'Test Product',
    'unique_id' => 'TEST-001',
    'image' => null, // No specific image
];

echo "🔧 Testing handleImageUpload method with test data:\n";
echo "Product name: {$testRow['name']}\n";
echo "Unique ID: {$testRow['unique_id']}\n";
echo "Image column: " . ($testRow['image'] ?: 'NULL') . "\n\n";

// Use reflection to call the protected method
$reflection = new ReflectionClass($importInstance);
$handleImageUploadMethod = $reflection->getMethod('handleImageUpload');
$handleImageUploadMethod->setAccessible(true);

try {
    $result = $handleImageUploadMethod->invoke($importInstance, $testRow, 2);
    
    if ($result) {
        echo "✅ SUCCESS: Image assigned - {$result}\n";
        echo "🎯 This confirms that future bulk imports will get images even without ZIP files\n";
    } else {
        echo "❌ FAILED: No image was assigned\n";
        echo "🐛 The fallback logic may not be working correctly\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n📝 Next Steps:\n";
echo "- Test with a real Excel file upload\n";
echo "- Verify that both existing and new products get images\n";
echo "- Monitor the logs during bulk import operations\n";