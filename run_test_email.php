<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;

echo "\n=== MAIL SEND TEST (password reset simple route) ===\n";

try {
    $req = Request::create('/test-password-reset-simple', 'GET');
    $resp = $kernel->handle($req);
    echo "Status: " . $resp->getStatusCode() . "\n";
    echo "Response (truncated):\n" . substr($resp->getContent(), 0, 1000) . "\n";
    // Print any exceptions captured in route output
    $kernel->terminate($req, $resp);
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
