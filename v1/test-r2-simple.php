<?php

require_once 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

echo "🌥️  SIMPLE R2 CONNECTION TEST\n";
echo "============================\n\n";

// R2 credentials
$credentials = [
    'access_key' => env('AWS_ACCESS_KEY_ID', '6ecf617d161013ce4416da9f1b2326e2'),
    'secret_key' => env('AWS_SECRET_ACCESS_KEY', '196740bf5f4ca18f7ee34893d3b5acf90d077477ca96b147730a8a65faf2d7a4'),
    'bucket' => env('AWS_BUCKET', 'fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f'),
    'region' => env('AWS_DEFAULT_REGION', 'auto'),
    'endpoint' => env('AWS_ENDPOINT', 'https://367be3a2035528943240074d0096e0cd.r2.cloudflarestorage.com')
];

echo "📋 Configuration:\n";
echo "   Bucket: {$credentials['bucket']}\n";
echo "   Region: {$credentials['region']}\n";
echo "   Endpoint: {$credentials['endpoint']}\n";
echo "   Access Key: {$credentials['access_key']}\n\n";

// Try different endpoint formats
$endpointVariations = [
    'https://367be3a2035528943240074d0096e0cd.r2.cloudflarestorage.com',
    'https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.367be3a2035528943240074d0096e0cd.r2.cloudflarestorage.com',
    'https://r2.cloudflarestorage.com',
    'https://367be3a2035528943240074d0096e0cd.r2.cloudflarestorage.com/' . $credentials['bucket']
];

foreach ($endpointVariations as $index => $endpoint) {
    echo "🔗 Testing endpoint " . ($index + 1) . ": $endpoint\n";
    
    try {
        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => $credentials['region'],
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $credentials['access_key'],
                'secret' => $credentials['secret_key'],
            ],
        ]);

        // Try to list objects
        $result = $s3Client->listObjectsV2([
            'Bucket' => $credentials['bucket'],
            'MaxKeys' => 1
        ]);
        
        echo "   ✅ Success! Connection works\n";
        echo "   📁 Bucket accessible\n";
        break;
        
    } catch (AwsException $e) {
        echo "   ❌ Failed: " . $e->getMessage() . "\n";
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "\n🎯 Test complete!\n";