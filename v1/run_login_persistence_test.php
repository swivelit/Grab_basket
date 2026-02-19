<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

echo "\n=== DELIVERY PARTNER LOGIN PERSISTENCE TEST ===\n";

try {
    // 1. GET the login page to start session and generate CSRF token
    $get = Request::create('/delivery-partner/login', 'GET');
    $resp1 = $kernel->handle($get);
    echo "GET /delivery-partner/login => " . $resp1->getStatusCode() . "\n";
    // Debug: list cookies set by GET response
    echo "Cookies set by GET response:\n";
    foreach ($resp1->headers->getCookies() as $c) {
        echo " - " . $c->getName() . " = " . $c->getValue() . "\n";
    }

    // Try to read CSRF token from session
    $token = null;
    try {
        $token = session()->token();
    } catch (\Throwable $e) {
        // fallback to csrf_token()
        $token = csrf_token();
    }
    echo "CSRF token: " . ($token ? substr($token, 0, 8) . '...' : 'NULL') . "\n";

    // 2. POST login with valid credentials
    $post = Request::create('/delivery-partner/login', 'POST', [
        'login' => 'test@delivery.com',
        'password' => 'password123',
        '_token' => $token
    ]);
    // copy cookies from previous response to the new request (session cookie)
    foreach ($resp1->headers->getCookies() as $cookie) {
        $post->cookies->set($cookie->getName(), $cookie->getValue());
    }

    $resp2 = $kernel->handle($post);
    echo "POST /delivery-partner/login => " . $resp2->getStatusCode() . "\n";
    echo "Cookies set by POST response:\n";
    foreach ($resp2->headers->getCookies() as $c) {
        echo " - " . $c->getName() . " = " . $c->getValue() . "\n";
    }
    // Debug: Location header and content preview
    $loc = $resp2->headers->get('Location');
    echo "POST redirect location: " . ($loc ?? 'NONE') . "\n";
    echo "POST response preview: " . substr($resp2->getContent(), 0, 300) . "\n";

    // After login, try to access a protected page
    // copy cookies from login response too
    foreach ($resp2->headers->getCookies() as $cookie) {
        $post->cookies->set($cookie->getName(), $cookie->getValue());
    }

    $dashboardReq = Request::create('/delivery-partner/dashboard', 'GET');
    // set cookies to dashboard request
    foreach ($post->cookies->all() as $name => $value) {
        $dashboardReq->cookies->set($name, $value);
    }

    $resp3 = $kernel->handle($dashboardReq);
    echo "GET /delivery-partner/dashboard => " . $resp3->getStatusCode() . "\n";
    echo "Cookies present on dashboard request:\n";
    foreach ($dashboardReq->cookies->all() as $n => $v) {
        echo " - $n = $v\n";
    }

    if ($resp3->getStatusCode() == 200) {
        echo "✅ Login persisted; dashboard accessible.\n";
    } else {
        echo "⚠️  Dashboard not accessible after login. Response status: " . $resp3->getStatusCode() . "\n";
        echo "Response preview: " . substr($resp3->getContent(), 0, 300) . "\n";
    }

    $kernel->terminate($get, $resp1);
    $kernel->terminate($post, $resp2);
    $kernel->terminate($dashboardReq, $resp3);

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
