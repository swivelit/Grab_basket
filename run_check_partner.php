<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::create('/health-check','GET'));

use App\Models\DeliveryPartner;
use Illuminate\Support\Facades\Hash;

$partner = DeliveryPartner::where('email','test@delivery.com')->first();
if (!$partner) {
    echo "Partner not found\n";
    exit(1);
}

echo "Partner found: ID={$partner->id}, email={$partner->email}, phone={$partner->phone}, status={$partner->status}\n";
$ok = Hash::check('password123', $partner->password) ? 'YES' : 'NO';
echo "Password matches: $ok\n";
