<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$hotel = App\Models\HotelOwner::first();
if (!$hotel) {
    echo "No hotel owner found in DB.\n";
    exit(0);
}

Auth::guard('hotel_owner')->setUser(App\Models\HotelOwner::find($hotel->id));
$ctrl = new App\Http\Controllers\HotelOwner\EarningsController();
$view = $ctrl->index();

echo "Returned: " . get_class($view) . "\n";
try {
    echo "Rendering earnings view...\n";
    $html = $view->render();
    echo "Rendered length: " . strlen($html) . "\n";
    echo "Snippet:\n" . substr(strip_tags($html), 0, 400) . "\n";
} catch (Exception $e) {
    echo "Render error: " . $e->getMessage() . "\n";
}
