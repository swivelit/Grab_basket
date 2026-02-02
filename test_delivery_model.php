<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Checking delivery_partners table...\n";
    $count = \App\Models\DeliveryPartner::count();
    echo "Total delivery partners: $count\n\n";
    
    if ($count > 0) {
        echo "Trying to load with wallet...\n";
        $partner = \App\Models\DeliveryPartner::with('wallet')->first();
        echo "Partner loaded: " . $partner->name . "\n";
        echo "Wallet: " . ($partner->wallet ? "Exists" : "NULL") . "\n";
    }
    
    echo "\nTrying to paginate...\n";
    $partners = \App\Models\DeliveryPartner::with('wallet')->paginate(15);
    echo "Paginated successfully: " . $partners->count() . " partners\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
