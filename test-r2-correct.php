<?php

require_once 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

echo "🌥️  R2 ENDPOINT FORMAT TEST\n";
echo "===========================\n\n";

$accountId = "367be3a2035528943240074d0096e0cd";
$bucketName = "fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f";
$accessKey = "6ecf617d161013ce4416da9f1b2326e2";
$secretKey = "196740bf5f4ca18f7ee34893d3b5acf90d077477ca96b147730a8a65faf2d7a4";

// Correct R2 endpoint format according to Cloudflare docs
$correctEndpoint = "https://{$accountId}.r2.cloudflarestorage.com";

echo "📋 Configuration:\n";
echo "   Account ID: $accountId\n";
echo "   Bucket: $bucketName\n";
echo "   Correct Endpoint: $correctEndpoint\n\n";

echo "🔗 Testing correct R2 endpoint format...\n";

try {
    $s3Client = new S3Client([
        'version' => 'latest',
        'region' => 'auto',
        'endpoint' => $correctEndpoint,
        'use_path_style_endpoint' => true,
        'credentials' => [
            'key' => $accessKey,
            'secret' => $secretKey,
        ],
    ]);

    // Try to list objects
    $result = $s3Client->listObjectsV2([
        'Bucket' => $bucketName,
        'MaxKeys' => 5
    ]);
    
    echo "   ✅ SUCCESS! R2 connection established\n";
    echo "   📁 Bucket is accessible\n";
    
    if (isset($result['Contents']) && count($result['Contents']) > 0) {
        echo "   📄 Found " . count($result['Contents']) . " objects:\n";
        foreach ($result['Contents'] as $object) {
            echo "      - " . $object['Key'] . " (" . $object['Size'] . " bytes)\n";
        }
    } else {
        echo "   📂 Bucket is empty\n";
    }
    
    // Test upload
    echo "\n🔼 Testing file upload...\n";
    $testContent = "Hello from PHP! " . date('Y-m-d H:i:s');
    $testKey = 'test-uploads/php-test-' . time() . '.txt';
    
    $uploadResult = $s3Client->putObject([
        'Bucket' => $bucketName,
        'Key' => $testKey,
        'Body' => $testContent,
        'ContentType' => 'text/plain'
    ]);
    
    echo "   ✅ Upload successful!\n";
    echo "   📄 File: $testKey\n";
    echo "   🔗 ETag: " . $uploadResult['ETag'] . "\n";
    
} catch (AwsException $e) {
    echo "   ❌ AWS Error: " . $e->getAwsErrorMessage() . "\n";
    echo "   🔍 Error Code: " . $e->getAwsErrorCode() . "\n";
} catch (Exception $e) {
    echo "   ❌ General Error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Test complete!\n";