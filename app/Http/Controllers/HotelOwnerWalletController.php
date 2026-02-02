<?php

namespace App\Http\Controllers;

use App\Models\HotelOwnerWallet;
use App\Models\HotelOwnerWithdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class HotelOwnerWalletController extends Controller
{
    /**
     * Show wallet overview
     */
    public function index()
    {
        $wallet = Auth::user()->wallet;
        if (!$wallet) {
            $wallet = HotelOwnerWallet::create([
                'hotel_owner_id' => Auth::id(),
                'balance' => 0,
                'currency' => 'INR'
            ]);
        }

        return view('hotel-owner.wallet.index', compact('wallet'));
    }

    /**
     * Show earnings details
     */
    public function earnings()
    {
        $wallet = Auth::user()->wallet;
        if (!$wallet) {
            return redirect()->route('hotel-owner.wallet.index');
        }

        $earnings = Auth::user()->orders()
            ->whereIn('status', ['completed', 'delivered'])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as daily_total')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        return view('hotel-owner.wallet.earnings', compact('wallet', 'earnings'));
    }

    /**
     * Show withdrawals history
     */
    public function withdrawals()
    {
        $wallet = Auth::user()->wallet;
        if (!$wallet) {
            return redirect()->route('hotel-owner.wallet.index');
        }

        $withdrawals = $wallet->withdrawals()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('hotel-owner.wallet.withdrawals', compact('wallet', 'withdrawals'));
    }

    /**
     * Request a new withdrawal
     */
    public function requestWithdrawal(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100|max:50000',
            'notes' => 'nullable|string|max:1000'
        ]);

        $wallet = Auth::user()->wallet;
        if (!$wallet) {
            return redirect()
                ->route('hotel-owner.wallet.index')
                ->with('error', 'Wallet not found.');
        }

        try {
            $withdrawal = $wallet->requestWithdrawal($request->amount, $request->notes);

            return redirect()
                ->route('hotel-owner.withdrawals.index')
                ->with('success', 'Withdrawal request submitted successfully.');
        } catch (Exception $e) {
            return redirect()
                ->route('hotel-owner.withdrawals.index')
                ->with('error', $e->getMessage());
        }
    }
}