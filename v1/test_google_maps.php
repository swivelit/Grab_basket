<?php

require __DIR__.'/vendor/autoload.php';

$key = getenv('GOOGLE_MAPS_API_KEY');
$testUrl = "https://maps.googleapis.com/maps/api/geocode/json?address=India&key=$key";
$response = @file_get_contents($testUrl);

if ($response === false) {
    echo "Error: Unable to contact Google Maps API\n";
    exit(1);
}

$result = json_decode($response);
if (!$result) {
    echo "Error: Invalid JSON response\n";
    exit(1);
}

echo "API Status: " . $result->status . "\n";
if ($result->status === "OK") {
    echo "API Key is working correctly!\n";
    exit(0);
} else {
    echo "API Error: " . $result->status . "\n";
    exit(1);
}