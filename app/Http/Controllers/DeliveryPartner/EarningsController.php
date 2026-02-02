<?php

namespace App\Http\Controllers\DeliveryPartner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EarningsController extends Controller
{
    /**
     * Display earnings dashboard
     */
    public function index()
    {
        $partner = auth('delivery_partner')->user();
        $wallet = $partner->wallet;

        $earnings = [
            'today' => $partner->today_earnings,
            'week' => 0, // Placeholder for week if not in model
            'month' => $partner->this_month_earnings,
            'total' => $partner->total_earnings_all,
            'pending' => $partner->pending_deliveries_count_all * 30,
            'withdrawn' => $wallet ? $wallet->total_withdrawals : 0,
        ];

        // Fetch recent deliveries for display
        $recentEarnings = collect();

        // Standard Orders
        $standard = $partner->deliveryRequests()
            ->where('status', 'completed')
            ->orderBy('delivered_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($req) {
                return [
                    'date' => $req->delivered_at,
                    'order_id' => $req->order_id,
                    'amount' => 30,
                    'type' => 'Standard Delivery'
                ];
            });

        // Food Orders
        $food = $partner->foodOrders()
            ->where('status', 'delivered')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'date' => $order->updated_at,
                    'order_id' => $order->id,
                    'amount' => 30,
                    'type' => 'Food Delivery'
                ];
            });

        // TenMin Orders
        $tenMin = $partner->tenMinOrders()
            ->where('status', 'delivered')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'date' => $order->updated_at,
                    'order_id' => $order->id,
                    'amount' => 30,
                    'type' => 'Express Delivery'
                ];
            });

        $recentEarnings = $standard->concat($food)->concat($tenMin)
            ->sortByDesc('date')
            ->take(15);

        return view('delivery-partner.earnings.index', compact('earnings', 'recentEarnings'));
    }

    /**
     * Display weekly earnings
     */
    public function weekly()
    {
        $partner = auth('delivery_partner')->user();

        // TODO: Implement weekly earnings breakdown
        $weeklyData = [];

        return view('delivery-partner.earnings.weekly', compact('weeklyData'));
    }

    /**
     * Display monthly earnings
     */
    public function monthly()
    {
        $partner = auth('delivery_partner')->user();

        // TODO: Implement monthly earnings breakdown
        $monthlyData = [];

        return view('delivery-partner.earnings.monthly', compact('monthlyData'));
    }

    /**
     * Process withdrawal request
     */
    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
        ]);

        // TODO: Implement withdrawal logic
        // - Check minimum balance
        // - Create withdrawal request
        // - Update partner balance

        return redirect()->back()->with('success', 'Withdrawal request submitted successfully!');
    }
}
