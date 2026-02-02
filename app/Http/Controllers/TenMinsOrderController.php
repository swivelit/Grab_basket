<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TenMinOrder;
use App\Models\TenMinOrderItem; // 👈 ADD THIS IMPORT

class TenMinsOrderController extends Controller
{
   public function index()
{
    $sellerId = auth()->id();

    $sellerItems = TenMinOrderItem::with(['order', 'product'])
        ->whereHas('product', function ($query) use ($sellerId) {
            $query->where('seller_id', $sellerId); // ✅ 'seller_id', NOT 'user_id'
        })
        ->orderByDesc('created_at')
        ->get();

    $ordersGrouped = $sellerItems->groupBy('ten_min_order_id');

    $ordersForSeller = $ordersGrouped->map(function ($items, $orderId) {
        $firstItem = $items->first();
        $order = $firstItem->order;

        $sellerTotal = $items->sum(fn($item) => $item->price * $item->quantity);

        return (object)[
            'id' => $orderId,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'payment_method' => $order->payment_method,
            'status' => $order->status,
            'estimated_delivery_time' => $order->estimated_delivery_time,
            'created_at' => $order->created_at,
            'items' => $items,
            'total_amount' => $sellerTotal,
            'first_item' => $firstItem,
        ];
    })->values();

    return view('seller.ten-mins-orders', compact('ordersForSeller')); // ✅
}

    public function updateStatus(Request $request, $id)
    {
        $sellerId = auth()->id();

        $order = TenMinOrder::with('items.product')->findOrFail($id);

        // Check if this order has any items from this seller
       $hasSellerItems = $order->items->contains(function ($item) use ($sellerId) {
    return $item->product && $item->product->seller_id == $sellerId; // ✅
});

        if (!$hasSellerItems) {
            return response()->json(['success' => false, 'error' => 'No permission'], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,confirmed,delivered,cancelled'
        ]);

        $order->status = $request->status;
        $order->save();

        return response()->json(['success' => true]);
    }

    public function show($id)
    {
        $sellerId = auth()->id();

        $order = TenMinOrder::with(['items.product'])
            ->findOrFail($id);

        // Filter items to only those belonging to this seller
     $sellerItems = $order->items->filter(function ($item) use ($sellerId) {
    return $item->product && $item->product->seller_id == $sellerId; // ✅
});

        // If no items for this seller, show error
        if ($sellerItems->isEmpty()) {
            abort(403, 'You do not have permission to view this order.');
        }

        return view('seller.order-detail', compact('order', 'sellerItems'));
    }
}