<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Smoke test: create wallet, set balance, request withdrawal
$hotel = App\Models\HotelOwner::first();
if (!$hotel) {
    echo "No hotel owner found, can't test withdrawal.\n";
    exit(0);
}

// Ensure wallet exists
$wallet = App\Models\HotelOwnerWallet::firstOrCreate(['hotel_owner_id' => $hotel->id], ['balance' => 1000, 'currency' => 'INR']);
$wallet->balance = 1000; $wallet->save();

echo "Starting balance: " . $wallet->balance . "\n";

// Simulate withdrawal request via controller
$request = Illuminate\Http\Request::create('/hotel-owner/wallet/withdraw', 'POST', ['amount' => 250, 'notes' => 'Test withdrawal']);
\Auth::guard('hotel_owner')->setUser(App\Models\HotelOwner::find($hotel->id));
$controller = new App\Http\Controllers\HotelOwner\WalletController();
$response = $controller->withdraw($request);

$wallet->refresh();

echo "After request balance: " . $wallet->balance . "\n";

$last = App\Models\HotelOwnerWithdrawal::where('hotel_owner_wallet_id', $wallet->id)->latest()->first();
if ($last) {
    echo "Last withdrawal: amount={$last->amount} status={$last->status} requested_at={$last->requested_at}\n";
} else {
    echo "No withdrawal record found.\n";
}

echo "Done\n";
