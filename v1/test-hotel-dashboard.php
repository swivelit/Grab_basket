<?php

// Test hotel-owner dashboard

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Hotel Owner Dashboard...\n\n";

try {
    // Check if HotelOwner model exists
    if (!class_exists(\App\Models\HotelOwner::class)) {
        echo "✗ HotelOwner model does not exist!\n";
        exit(1);
    }
    
    // Get a test hotel owner (first one in database)
    $hotelOwner = \App\Models\HotelOwner::first();
    
    if (!$hotelOwner) {
        echo "✗ No hotel owners found in database\n";
        echo "  You need at least one hotel owner account to test the dashboard\n";
        exit(1);
    }
    
    echo "✓ Found hotel owner: {$hotelOwner->name} (ID: {$hotelOwner->id})\n\n";
    
    // Mock authentication
    Auth::guard('hotel_owner')->login($hotelOwner);
    
    // Test controller
    $controller = new \App\Http\Controllers\HotelOwner\DashboardController();
    $response = $controller->index();
    
    echo "✓ Controller executed successfully\n";
    echo "Response type: " . get_class($response) . "\n";
    
    if ($response instanceof \Illuminate\View\View) {
        echo "✓ Returned a View object\n";
        
        try {
            $rendered = $response->render();
            echo "✓ View rendered successfully\n";
            echo "Content size: " . strlen($rendered) . " bytes\n";
        } catch (\Exception $renderError) {
            echo "✗ View rendering failed!\n";
            echo "Error: " . $renderError->getMessage() . "\n";
            echo "File: " . $renderError->getFile() . "\n";
            echo "Line: " . $renderError->getLine() . "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "✗ Exception occurred\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . substr($e->getTraceAsString(), 0, 1000) . "\n";
}
