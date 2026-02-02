<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotelOwnerWithdrawal;
use Illuminate\Http\Request;
use Exception;

class HotelOwnerWithdrawalController extends Controller
{
    /**
     * Display a listing of withdrawals
     */
    public function index(Request $request)
    {
        $query = HotelOwnerWithdrawal::with(['wallet.hotelOwner'])
            ->orderBy('created_at', 'desc');

        // Filter by status if provided
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $withdrawals = $query->paginate(20);

        return view('admin.withdrawals.index', compact('withdrawals'));
    }

    /**
     * Display a specific withdrawal
     */
    public function show(HotelOwnerWithdrawal $withdrawal)
    {
        $withdrawal->load(['wallet.hotelOwner']);
        return view('admin.withdrawals.show', compact('withdrawal'));
    }

    /**
     * Approve a withdrawal request
     */
    public function approve(HotelOwnerWithdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return redirect()
                ->route('admin.withdrawals.show', $withdrawal)
                ->with('error', 'Only pending withdrawals can be approved.');
        }

        try {
            $withdrawal->wallet->approveWithdrawal($withdrawal);

            return redirect()
                ->route('admin.withdrawals.show', $withdrawal)
                ->with('success', 'Withdrawal has been approved successfully.');
        } catch (Exception $e) {
            return redirect()
                ->route('admin.withdrawals.show', $withdrawal)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Reject a withdrawal request
     */
    public function reject(Request $request, HotelOwnerWithdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return redirect()
                ->route('admin.withdrawals.show', $withdrawal)
                ->with('error', 'Only pending withdrawals can be rejected.');
        }

        $request->validate([
            'reason' => 'required|string|max:1000'
        ]);

        try {
            $withdrawal->wallet->rejectWithdrawal($withdrawal, $request->reason);

            return redirect()
                ->route('admin.withdrawals.show', $withdrawal)
                ->with('success', 'Withdrawal has been rejected successfully.');
        } catch (Exception $e) {
            return redirect()
                ->route('admin.withdrawals.show', $withdrawal)
                ->with('error', $e->getMessage());
        }
    }
}