<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;

echo "\n=== DEBUG PASSWORD RESET ROUTE TEST (/debug-password-reset) ===\n";

try {
    $req = Request::create('/debug-password-reset', 'GET');
    $resp = $kernel->handle($req);
    echo "Status: " . $resp->getStatusCode() . "\n";
    echo "Response (truncated):\n" . substr($resp->getContent(), 0, 2000) . "\n";
    $kernel->terminate($req, $resp);
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
