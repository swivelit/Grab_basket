<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/debug-create-delivery-partner', 'GET');
$response = $kernel->handle($request);

// Pretty print JSON if possible
$content = $response->getContent();
$json = json_decode($content, true);
if ($json) {
    echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} else {
    echo $content . "\n";
}

$kernel->terminate($request, $response);
