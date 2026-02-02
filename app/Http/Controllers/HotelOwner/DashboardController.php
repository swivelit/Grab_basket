<?php

namespace App\Http\Controllers\HotelOwner;

use App\Http\Controllers\Controller;
use App\Models\FoodItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $hotelOwner = Auth::guard('hotel_owner')->user();
            
            // Dashboard statistics - using safe queries
            $stats = [
                'total_food_items' => $hotelOwner->foodItems()->count(),
                'active_food_items' => $hotelOwner->foodItems()->where('is_available', true)->count(),
                'total_orders' => 0, // Food orders will be implemented later
                'pending_orders' => 0,
                'completed_orders' => 0,
                'total_revenue' => 0,
                'today_orders' => 0,
                'this_month_revenue' => 0,
            ];

            // Recent orders - empty for now until food order system is implemented
            $recentOrders = collect([]);

            // Popular food items - based on existing food items
            $popularItems = $hotelOwner->foodItems()
                ->where('is_available', true)
                ->limit(5)
                ->get();

            // Earnings: compute last 7 days sums from orders (if available)
            $earnings_last_7 = [];
            try {
                $today = \Carbon\Carbon::today();
                for ($i = 6; $i >= 0; $i--) {
                    $date = $today->copy()->subDays($i);
                    $sum = \App\Models\Order::where('seller_id', $hotelOwner->id)
                        ->where('status', 'completed')
                        ->whereDate('paid_at', $date)
                        ->sum('amount');
                    $earnings_last_7[] = (float) $sum;
                }
            } catch (\Exception $e) {
                // fallback to zeros
                for ($i = 0; $i < 7; $i++) {
                    $earnings_last_7[] = 0;
                }
            }

            return view('hotel-owner.dashboard', compact('stats', 'recentOrders', 'popularItems', 'hotelOwner', 'earnings_last_7'));
            
        } catch (\Exception $e) {
            Log::error('Hotel Owner Dashboard Error: ' . $e->getMessage());
            
            // Fallback data in case of error
            $hotelOwner = Auth::guard('hotel_owner')->user();
            $stats = [
                'total_food_items' => 0,
                'active_food_items' => 0,
                'total_orders' => 0,
                'pending_orders' => 0,
                'completed_orders' => 0,
                'total_revenue' => 0,
                'today_orders' => 0,
                'this_month_revenue' => 0,
            ];
            $recentOrders = collect([]);
            $popularItems = collect([]);
            
            return view('hotel-owner.dashboard', compact('stats', 'recentOrders', 'popularItems', 'hotelOwner', 'earnings_last_7'))
                ->with('error', 'Dashboard data temporarily unavailable. Please try again later.');
        }
    }

    public function profile()
    {
        $hotelOwner = Auth::guard('hotel_owner')->user();
        return view('hotel-owner.profile', compact('hotelOwner'));
    }

    public function updateProfile(Request $request)
    {
        $hotelOwner = Auth::guard('hotel_owner')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'restaurant_name' => 'required|string|max:255',
            'restaurant_address' => 'required|string',
            'restaurant_phone' => 'required|string|max:20',
            'cuisine_type' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|integer|min:0',
            'delivery_time' => 'nullable|integer|min:10|max:120',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'operating_days' => 'nullable|array',
            'operating_days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ]);

        $hotelOwner->update($validated);

        return back()->with('success', 'Profile updated successfully!');
    }
}
