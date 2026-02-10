<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\DeliveryPartner;

// Load Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Simulating Full Login Process ===\n\n";

// Get the test delivery partner
$partner = DeliveryPartner::first();

if (!$partner) {
    echo "No delivery partner found!\n";
    exit;
}

echo "Using delivery partner: ID {$partner->id}, Phone: {$partner->phone}, Status: {$partner->status}\n\n";

// Simulate login
echo "1. Logging in delivery partner...\n";
Auth::guard('delivery_partner')->login($partner);

if (Auth::guard('delivery_partner')->check()) {
    echo "✓ Login successful!\n";
    $user = Auth::guard('delivery_partner')->user();
    echo "Authenticated user type: " . get_class($user) . "\n";
    echo "User ID: " . $user->id . "\n\n";
} else {
    echo "✗ Login failed!\n";
    exit;
}

// Test dashboard access
echo "2. Testing dashboard access...\n";

$request = Request::create('/delivery-partner/dashboard', 'GET');
$request->setUserResolver(function () {
    return Auth::guard('delivery_partner')->user();
});

// Set the session
$session = app('session');
$session->start();
Auth::guard('delivery_partner')->login($partner);

try {
    $response = $kernel->handle($request);
    echo "Dashboard response status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() == 200) {
        echo "✓ Dashboard loaded successfully!\n";
    } elseif ($response->getStatusCode() == 302) {
        $location = $response->headers->get('Location');
        echo "Dashboard redirected to: " . $location . "\n";
    } else {
        echo "Dashboard error content:\n";
        echo substr($response->getContent(), 0, 500) . "...\n";
    }
} catch (Exception $e) {
    echo "✗ Dashboard error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

$kernel->terminate($request, $response ?? null);