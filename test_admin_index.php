<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::create('/admin/delivery-partners', 'GET')
);

echo "Status: " . $response->getStatusCode() . "\n";
echo "URL: /admin/delivery-partners\n";

if ($response->getStatusCode() !== 200) {
    echo "Error Content:\n";
    echo substr($response->getContent(), 0, 500);
}

$kernel->terminate($request, $response);
