<?php

namespace App\Http\Controllers\HotelOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HotelOwnerWallet;

class WalletController extends Controller
{
    public function index()
    {
        $hotelOwner = Auth::guard('hotel_owner')->user();
        $wallet = HotelOwnerWallet::firstOrCreate([
            'hotel_owner_id' => $hotelOwner->id,
        ], [
            'balance' => 0,
            'currency' => 'INR'
        ]);

        return view('hotel-owner.wallet.index', compact('wallet', 'hotelOwner'));
    }

    public function withdraw(Request $request)
    {
        $hotelOwner = Auth::guard('hotel_owner')->user();
        $wallet = HotelOwnerWallet::firstOrCreate([
            'hotel_owner_id' => $hotelOwner->id,
        ], [
            'balance' => 0,
            'currency' => 'INR'
        ]);

        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $amount = (float)$request->input('amount');
        if ($wallet->balance < $amount) {
            return back()->with('error', 'Insufficient balance for withdrawal.');
        }

        $withdrawal = $wallet->requestWithdrawal($amount, $request->input('notes'));

        if (!$withdrawal) {
            return back()->with('error', 'Unable to create withdrawal request.');
        }

        return back()->with('success', 'Withdrawal requested successfully and is pending approval.');
    }
}
