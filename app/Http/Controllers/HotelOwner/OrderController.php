<?php

namespace App\Http\Controllers\HotelOwner;

use App\Http\Controllers\Controller;
use App\Models\FoodOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = FoodOrder::where('hotel_owner_id', Auth::guard('hotel_owner')->id())
            ->with(['items.foodItem']); // Eager load items and foodItem for performance

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(10);

        return view('hotel-owner.orders.index', compact('orders'));
    }

    /**
     * Display the specified resource.
     */
    public function show(FoodOrder $order)
    {
        // Ensure the order belongs to the authenticated hotel owner
        if ($order->hotel_owner_id !== Auth::guard('hotel_owner')->id()) {
            abort(403);
        }

        $order->load(['items.foodItem', 'deliveryPartner']);

        return view('hotel-owner.orders.show', compact('order'));
    }

    /**
     * Update the status of the order.
     */
    public function updateStatus(Request $request, FoodOrder $order)
    {
        // Ensure the order belongs to the authenticated hotel owner
        if ($order->hotel_owner_id !== Auth::guard('hotel_owner')->id()) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:pending,accepted,assigned,preparing,ready,delivered,cancelled',
        ]);

        $order->update(['status' => $request->status]);

        return back()->with('success', 'Order status updated successfully.');
    }
}
