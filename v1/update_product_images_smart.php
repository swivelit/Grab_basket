<?php
// This script will update product images using local SRM IMG files if available, otherwise fetch from Google Custom Search API
// Requirements: composer require guzzlehttp/guzzle

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use GuzzleHttp\Client;

$googleApiKey = 'AIzaSyCpNq0PrC0QhLaXcWV-VtzM0rOWNF0njP4';
$googleCseId = 'e0705b44ba5784dea'; // <-- User's provided CSE ID
$localImgDir = __DIR__ . '/SRM IMG';
$localImgUrlPrefix = '/SRM IMG/'; // For web access, adjust if needed

function findLocalImage($productName) {
    global $localImgDir;
    $files = scandir($localImgDir);
    $name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $productName));
    foreach ($files as $file) {
        if (preg_match('/\.(jpg|jpeg|png|webp)$/i', $file)) {
            $base = strtolower(pathinfo($file, PATHINFO_FILENAME));
            if (strpos($name, $base) !== false || strpos($base, $name) !== false) {
                return $file;
            }
        }
    }
    return null;
}

function fetchGoogleImage($query, $apiKey, $cseId) {
    $client = new Client();
    $url = 'https://www.googleapis.com/customsearch/v1';
    try {
        $response = $client->get($url, [
            'query' => [
                'q' => $query,
                'cx' => $cseId,
                'key' => $apiKey,
                'searchType' => 'image',
                'num' => 1
            ]
        ]);
        $data = json_decode($response->getBody(), true);
        if (!empty($data['items'][0]['link'])) {
            return $data['items'][0]['link'];
        }
    } catch (Exception $e) {
        echo "Google API error for $query: " . $e->getMessage() . "\n";
    }
    return null;
}

$products = \App\Models\Product::all();
$updated = 0;
foreach ($products as $product) {
    $localImg = findLocalImage($product->name);
    if ($localImg) {
        $product->image = $localImgUrlPrefix . $localImg;
        $product->save();
        echo "[LOCAL] {$product->name} -> $localImg\n";
        $updated++;
        continue;
    }
    $query = $product->name;
    if ($product->category && $product->category->name) {
        $query .= ' ' . $product->category->name;
    }
    $imgUrl = fetchGoogleImage($query, $googleApiKey, $googleCseId);
    if ($imgUrl) {
        $product->image = $imgUrl;
        $product->save();
        echo "[GOOGLE] {$product->name} -> $imgUrl\n";
        $updated++;
    } else {
        echo "[NO IMAGE] {$product->name}\n";
    }
    // To avoid hitting API rate limits
    usleep(300000); // 0.3s
}
echo "\nTotal products updated: $updated\n";
