<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing GET /hotel-owner/login\n";
$request = Illuminate\Http\Request::create('/hotel-owner/login', 'GET');
$response = $app->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
if ($response->isRedirection()) {
    echo "Redirect location: " . $response->headers->get('Location') . "\n";
}

// Try to render content (some responses are RedirectResponse)
try {
    $content = $response->getContent();
    $len = strlen($content);
    echo "Content length: {$len}\n";
    // Print small snippet
    echo "Snippet:\n" . substr(strip_tags($content), 0, 300) . "\n";
} catch (Exception $e) {
    echo "Could not render content: " . $e->getMessage() . "\n";
}

echo "Done\n";
