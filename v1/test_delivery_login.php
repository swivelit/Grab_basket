<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\DeliveryPartner;

// Load Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Testing Delivery Partner Login Process ===\n\n";

// Test 1: Check if we can find a delivery partner
echo "1. Checking delivery partners in database:\n";
$request = Request::create('/debug-delivery-partners', 'GET');

try {
    $response = $kernel->handle($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() == 200) {
        echo "Debug page accessible. Check browser for details.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n2. Testing dashboard controller directly:\n";

// Test 2: Try to instantiate dashboard controller
try {
    $controller = new \App\Http\Controllers\DeliveryPartner\DashboardController();
    echo "✓ DashboardController instantiated successfully\n";
} catch (Exception $e) {
    echo "✗ Error instantiating DashboardController: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "\n3. Testing Auth guard:\n";

try {
    $guard = Auth::guard('delivery_partner');
    echo "✓ Delivery partner guard created successfully\n";
    echo "Guard driver: " . get_class($guard) . "\n";
} catch (Exception $e) {
    echo "✗ Error with auth guard: " . $e->getMessage() . "\n";
}

echo "\n4. Checking delivery partner model:\n";

try {
    $partners = DeliveryPartner::take(1)->get();
    echo "✓ DeliveryPartner model accessible\n";
    echo "Partners in database: " . DeliveryPartner::count() . "\n";
    
    if ($partners->count() > 0) {
        $partner = $partners->first();
        echo "Sample partner: ID {$partner->id}, Phone: {$partner->phone}, Status: {$partner->status}\n";
    }
} catch (Exception $e) {
    echo "✗ Error with DeliveryPartner model: " . $e->getMessage() . "\n";
}

$kernel->terminate($request, $response ?? null);