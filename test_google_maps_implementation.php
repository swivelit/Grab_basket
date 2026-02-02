<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Google Maps Service Provider Implementation\n";
echo "-----------------------------------------------\n\n";

try {
    $mapsService = app('google.maps');
    echo "✓ Service Provider loaded successfully\n\n";

    // Test domain validation
    echo "Testing Domain Validation:\n";
    $validDomain = "grabbaskets.com";
    $testDomains = [
        "https://$validDomain" => true,
        "https://invalid-domain.com" => false,
    ];

    foreach ($testDomains as $domain => $expectedResult) {
        $result = $mapsService->validateDomain($domain);
        $status = $result === $expectedResult ? "✓" : "✗";
        echo "$status Domain validation for $domain: " . ($result ? "Allowed" : "Blocked") . "\n";
    }
    echo "\n";

    // Test geocoding
    echo "Testing Geocoding:\n";
    $address = "Thanjavur, Tamil Nadu, India";
    echo "Geocoding address: $address\n";
    $result = $mapsService->geocode($address);
    if ($result['status'] === 'OK') {
        $location = $result['results'][0]['geometry']['location'];
        echo "✓ Location found: {$location['lat']}, {$location['lng']}\n";
    } else {
        echo "✗ Geocoding failed: {$result['status']}\n";
    }
    echo "\n";

    // Test reverse geocoding
    echo "Testing Reverse Geocoding:\n";
    $lat = 10.7870;
    $lng = 79.1378;
    echo "Reverse geocoding coordinates: $lat, $lng\n";
    $result = $mapsService->reverseGeocode($lat, $lng);
    if ($result['status'] === 'OK') {
        $address = $result['results'][0]['formatted_address'];
        echo "✓ Address found: $address\n";
    } else {
        echo "✗ Reverse geocoding failed: {$result['status']}\n";
    }
    echo "\n";

    // Test distance matrix
    echo "Testing Distance Matrix:\n";
    $origin = "Thanjavur, Tamil Nadu";
    $destination = "Chennai, Tamil Nadu";
    echo "Calculating distance from $origin to $destination\n";
    $result = $mapsService->getDistance($origin, $destination);
    if ($result['status'] === 'OK') {
        $distance = $result['rows'][0]['elements'][0]['distance']['text'];
        $duration = $result['rows'][0]['elements'][0]['duration']['text'];
        echo "✓ Distance: $distance, Duration: $duration\n";
    } else {
        echo "✗ Distance calculation failed: {$result['status']}\n";
    }
    echo "\n";

    // Test directions
    echo "Testing Directions:\n";
    $result = $mapsService->getDirections($origin, $destination);
    if ($result['status'] === 'OK') {
        $steps = count($result['routes'][0]['legs'][0]['steps']);
        echo "✓ Route found with $steps steps\n";
    } else {
        echo "✗ Directions failed: {$result['status']}\n";
    }
    echo "\n";

    // Test caching
    echo "Testing Caching:\n";
    $startTime = microtime(true);
    $result1 = $mapsService->geocode($address);
    $firstCallTime = microtime(true) - $startTime;

    $startTime = microtime(true);
    $result2 = $mapsService->geocode($address);
    $secondCallTime = microtime(true) - $startTime;

    if ($firstCallTime > $secondCallTime) {
        echo "✓ Caching working as expected (Second call faster)\n";
        echo "  First call: " . number_format($firstCallTime * 1000, 2) . "ms\n";
        echo "  Second call: " . number_format($secondCallTime * 1000, 2) . "ms\n";
    } else {
        echo "✗ Caching might not be working as expected\n";
    }

    echo "\nAll tests completed!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}