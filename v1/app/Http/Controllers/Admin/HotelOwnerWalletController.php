<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotelOwnerWallet;
use Illuminate\Http\Request;

class HotelOwnerWalletController extends Controller
{
    /**
     * Display a listing of hotel owner wallets
     */
    public function index()
    {
        $wallets = HotelOwnerWallet::with('hotelOwner')
            ->orderBy('balance', 'desc')
            ->paginate(20);

        return view('admin.hotel-owner-wallets.index', compact('wallets'));
    }

    /**
     * Display a specific wallet
     */
    public function show(HotelOwnerWallet $wallet)
    {
        $wallet->load(['hotelOwner', 'withdrawals' => function ($query) {
            $query->orderBy('created_at', 'desc')
                ->limit(10);
        }]);

        return view('admin.hotel-owner-wallets.show', compact('wallet'));
    }
}