<?php

namespace App\Http\Controllers\DeliveryPartner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the partner's orders
     */
    public function index()
    {
        $partner = auth('delivery_partner')->user();
        
        // Fetch all order types assigned to this partner
        $standardOrders = \App\Models\Order::where('delivery_partner_id', $partner->id)
            ->with(['user', 'sellerUser'])
            ->get()
            ->map(function ($order) {
                $order->type = 'standard';
                $order->normalized_id = $order->id;
                $order->normalized_order_number = 'ORD-' . $order->id;
                $order->customer_name_display = $order->user->name ?? 'Customer';
                $order->delivery_address_display = $order->delivery_address;
                $order->total_amount_display = $order->amount;
                
                $order->pickup_name_display = $order->sellerUser->name ?? 'Seller';
                $order->pickup_address_display = $order->sellerUser->billing_address ?? 'Contact support';
                
                return $order;
            });
            
        $foodOrders = \App\Models\FoodOrder::where('delivery_partner_id', $partner->id)
            ->with(['hotelOwner'])
            ->get()
            ->map(function ($order) {
                $order->type = 'food';
                $order->normalized_id = $order->id;
                $order->normalized_order_number = 'FOOD-' . $order->id;
                $order->customer_name_display = $order->customer_name;
                $order->delivery_address_display = $order->delivery_address;
                $order->total_amount_display = $order->total_amount;
                
                $order->pickup_name_display = $order->shop_name ?? ($order->hotelOwner->restaurant_name ?? 'Restaurant');
                $order->pickup_address_display = $order->shop_address ?? ($order->hotelOwner->restaurant_address ?? 'Address not found');
                
                return $order;
            });
            
        $tenMinOrders = \App\Models\TenMinOrder::where('delivery_partner_id', $partner->id)
            ->with(['seller'])
            ->get()
            ->map(function ($order) {
                $order->type = 'ten_min';
                $order->normalized_id = $order->id;
                $order->normalized_order_number = 'TM-' . $order->id;
                $order->customer_name_display = $order->customer_name;
                $order->delivery_address_display = $order->delivery_address;
                $order->total_amount_display = $order->total_amount;
                
                $pickupName = 'Shop';
                $pickupAddress = 'Address not found';
                if ($order->seller) {
                    $sellerProfile = \App\Models\Seller::where('email', $order->seller->email)->first();
                    $pickupName = $sellerProfile->store_name ?? ($order->seller->name ?? 'Shop');
                    $pickupAddress = $sellerProfile->store_address ?? ($order->seller->billing_address ?? 'Address not found');
                }
                $order->pickup_name_display = $pickupName;
                $order->pickup_address_display = $pickupAddress;
                
                return $order;
            });
            
        $orders = $standardOrders->concat($foodOrders)->concat($tenMinOrders)
            ->sortByDesc('created_at');
        
        return view('delivery-partner.orders.index', compact('orders'));
    }

    /**
     * Display available orders for pickup
     */
    public function available()
    {
        $partner = auth('delivery_partner')->user();
        
        // Check if partner is available for delivery
        if (!$partner->isAvailableForDelivery()) {
            $availableOrders = collect();
            return view('delivery-partner.orders.available', compact('availableOrders', 'partner'));
        }
        
        // Fetch available orders (unassigned)
        $standardOrders = \App\Models\Order::whereNull('delivery_partner_id')
            ->where('delivery_status', 'pending')
            ->where('order_status', 'confirmed')
            ->with(['user', 'sellerUser'])
            ->get()
            ->map(function ($order) {
                $order->type = 'standard';
                $order->normalized_id = $order->id;
                $order->normalized_order_number = 'ORD-' . $order->id;
                $order->customer_name_display = $order->user->name ?? 'Customer';
                $order->delivery_address_display = $order->delivery_address;
                $order->total_amount_display = $order->amount;
                
                $order->pickup_name_display = $order->sellerUser->name ?? 'Seller';
                $order->pickup_address_display = $order->sellerUser->billing_address ?? 'Contact support';
                
                return $order;
            });
            
        $foodOrders = \App\Models\FoodOrder::whereNull('delivery_partner_id')
            ->whereIn('status', ['pending', 'ready', 'cooking', 'preparing', 'paid', 'accepted'])
            ->with(['hotelOwner'])
            ->get()
            ->map(function ($order) {
                $order->type = 'food';
                $order->normalized_id = $order->id;
                $order->normalized_order_number = 'FOOD-' . $order->id;
                $order->customer_name_display = $order->customer_name;
                $order->delivery_address_display = $order->delivery_address;
                $order->total_amount_display = $order->total_amount;
                
                $order->pickup_name_display = $order->shop_name ?? ($order->hotelOwner->restaurant_name ?? 'Restaurant');
                $order->pickup_address_display = $order->shop_address ?? ($order->hotelOwner->restaurant_address ?? 'Address not found');
                
                return $order;
            });
            
        $tenMinOrders = \App\Models\TenMinOrder::whereNull('delivery_partner_id')
            ->whereIn('status', ['pending', 'confirmed', 'packing', 'ready', 'paid'])
            ->with(['seller'])
            ->get()
            ->map(function ($order) {
                $order->type = 'ten_min';
                $order->normalized_id = $order->id;
                $order->normalized_order_number = 'TM-' . $order->id;
                $order->customer_name_display = $order->customer_name;
                $order->delivery_address_display = $order->delivery_address;
                $order->total_amount_display = $order->total_amount;
                
                $pickupName = 'Shop';
                $pickupAddress = 'Address not found';
                if ($order->seller) {
                    $sellerProfile = \App\Models\Seller::where('email', $order->seller->email)->first();
                    $pickupName = $sellerProfile->store_name ?? ($order->seller->name ?? 'Shop');
                    $pickupAddress = $sellerProfile->store_address ?? ($order->seller->billing_address ?? 'Address not found');
                }
                $order->pickup_name_display = $pickupName;
                $order->pickup_address_display = $pickupAddress;
                
                return $order;
            });
            
        $availableOrders = $standardOrders->concat($foodOrders)->concat($tenMinOrders)
            ->sortBy('created_at');
        
        return view('delivery-partner.orders.available', compact('availableOrders'));
    }

    /**
     * Display the specified order
     */
    public function show(Request $request, $orderId)
    {
        $partner = auth('delivery_partner')->user();
        $type = $request->query('type', 'standard');
        
        $order = null;
        try {
            if ($type === 'food') {
                $order = \App\Models\FoodOrder::with(['items.foodItem', 'hotelOwner'])->findOrFail($orderId);
                $order->type = 'food';
                $order->pickup_name_display = $order->shop_name ?? ($order->hotelOwner->restaurant_name ?? 'Restaurant');
                $order->pickup_address_display = $order->shop_address ?? ($order->hotelOwner->restaurant_address ?? 'Address not found');
            } elseif ($type === 'ten_min') {
                $order = \App\Models\TenMinOrder::with(['items.product', 'seller'])->findOrFail($orderId);
                $order->type = 'ten_min';
                
                $pickupName = 'Shop';
                $pickupAddress = 'Address not found';
                if ($order->seller) {
                    $sellerProfile = \App\Models\Seller::where('email', $order->seller->email)->first();
                    $pickupName = $sellerProfile->store_name ?? ($order->seller->name ?? 'Shop');
                    $pickupAddress = $sellerProfile->store_address ?? ($order->seller->billing_address ?? 'Address not found');
                }
                $order->pickup_name_display = $pickupName;
                $order->pickup_address_display = $pickupAddress;
            } else {
                $order = \App\Models\Order::with(['user', 'orderItems.product', 'sellerUser'])->findOrFail($orderId);
                $order->type = 'standard';
                $order->pickup_name_display = $order->sellerUser->name ?? 'Seller';
                $order->pickup_address_display = $order->sellerUser->billing_address ?? 'Contact support';
                
                // Also get delivery request if exists
                $order->deliveryRequest = \App\Models\DeliveryRequest::where('order_id', $order->id)
                    ->where('delivery_partner_id', $partner->id)
                    ->first();
            }
        } catch (\Exception $e) {
            return redirect()->route('delivery-partner.dashboard')->with('error', 'Order not found.');
        }
        
        return view('delivery-partner.orders.show', compact('order', 'partner'));
    }

    /**
     * Accept an order
     */
    public function accept(Request $request, $orderId)
    {
        $partner = auth('delivery_partner')->user();
        $type = $request->input('type', 'standard'); // Default to standard

        try {
            // Check if partner is eligible to accept orders
            if (!$partner->isAvailableForDelivery()) {
                $msg = !$partner->is_online ? 'You must be online to accept orders.' : 'You are currently busy or unavailable.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg]);
                }
                return redirect()->back()->with('error', $msg);
            }

            // Check if partner is already busy
            if ($partner->current_order_id) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'You already have an active order.']);
                }
                return redirect()->back()->with('error', 'You already have an active order.');
            }

            if ($type === 'standard') {
                $order = \App\Models\Order::findOrFail($orderId);
                if ($order->delivery_partner_id) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => 'Order already accepted by another partner.']);
                    }
                    return redirect()->back()->with('error', 'Order already accepted by another partner.');
                }
                
                $partner->assignOrder($order);
                $order->update(['delivery_status' => 'assigned']); // Sync status
                
            } elseif ($type === 'food') {
                $order = \App\Models\FoodOrder::findOrFail($orderId);
                if ($order->delivery_partner_id) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => 'Order already accepted by another partner.']);
                    }
                    return redirect()->back()->with('error', 'Order already accepted by another partner.');
                }
                
                $partner->assignOrder($order);
                $order->update(['status' => 'accepted']); 

            } elseif ($type === 'ten_min') {
                $order = \App\Models\TenMinOrder::findOrFail($orderId);
                if ($order->delivery_partner_id) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => 'Order already accepted by another partner.']);
                    }
                    return redirect()->back()->with('error', 'Order already accepted by another partner.');
                }
                
                $partner->assignOrder($order);
                $order->update(['status' => 'confirmed']); 
            }

            // Send notification
            \App\Services\NotificationService::sendOrderAssignedToPartner($partner, $order);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Order accepted successfully!']);
            }
            return redirect()->route('delivery-partner.dashboard')->with('success', 'Order accepted successfully!');

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to accept order: ' . $e->getMessage()]);
            }
            return redirect()->back()->with('error', 'Failed to accept order: ' . $e->getMessage());
        }
    }

    /**
     * Mark order as picked up
     */
    /**
     * Mark order as picked up
     */
    public function pickup(Request $request, $orderId)
    {
        $partner = auth('delivery_partner')->user();
        $type = $request->input('type', 'standard');

        try {
            if ($type === 'food') {
                $order = \App\Models\FoodOrder::findOrFail($orderId);
                $order->status = 'picked_up';
                $order->save();
            } elseif ($type === 'ten_min') {
                \Log::info("Picking up ten_min order: " . $orderId);
                $order = \App\Models\TenMinOrder::findOrFail($orderId);
                $order->status = 'picked_up';
                $order->save();
                \Log::info("Ten_min order status updated to picked_up: " . $orderId);
            } else {
                $order = \App\Models\Order::findOrFail($orderId);
                $order->delivery_status = 'picked_up';
                $order->save();
                
                // Update DeliveryRequest if exists
                \App\Models\DeliveryRequest::where('order_id', $order->id)
                    ->where('delivery_partner_id', $partner->id)
                    ->update(['status' => 'picked_up', 'pickup_at' => now()]);
            }

            return redirect()->back()->with('success', 'Order marked as picked up!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update order: ' . $e->getMessage());
        }
    }

    /**
     * Mark order as delivered
     */
    public function deliver(Request $request, $orderId)
    {
        $partner = auth('delivery_partner')->user();
        $type = $request->input('type', 'standard');

        try {
            if ($type === 'food') {
                $order = \App\Models\FoodOrder::findOrFail($orderId);
                $order->status = 'delivered';
                $order->save();
            } elseif ($type === 'ten_min') {
                \Log::info("Delivering ten_min order: " . $orderId);
                $order = \App\Models\TenMinOrder::findOrFail($orderId);
                $order->status = 'delivered';
                $order->save();
                \Log::info("Ten_min order status updated to delivered: " . $orderId);
            } else {
                $order = \App\Models\Order::findOrFail($orderId);
                $order->delivery_status = 'delivered';
                $order->save();
                
                // Update DeliveryRequest if exists
                \App\Models\DeliveryRequest::where('order_id', $order->id)
                    ->where('delivery_partner_id', $partner->id)
                    ->update(['status' => 'completed', 'delivered_at' => now()]);
            }

            $partner->clearOrder();

            return redirect()->route('delivery-partner.dashboard')->with('success', 'Order delivered successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to complete delivery: ' . $e->getMessage());
        }
    }

    /**
     * Cancel an order
     */
    public function cancel(Request $request, $orderId)
    {
        $partner = auth('delivery_partner')->user();
        $type = $request->input('type', 'standard');
        $reason = $request->input('reason', 'Cancelled by driver');

        try {
            if ($type === 'food') {
                $order = \App\Models\FoodOrder::findOrFail($orderId);
                // We might want a specific status for driver cancellation or just 'pending' to re-assign
                $order->delivery_partner_id = null;
                $order->status = 'pending'; // Changed from 'ready' to 'pending'
                $order->save();
            } elseif ($type === 'ten_min') {
                $order = \App\Models\TenMinOrder::findOrFail($orderId);
                $order->delivery_partner_id = null;
                $order->status = 'pending'; // Changed from 'ready' to 'pending'
                $order->save();
            } else {
                $order = \App\Models\Order::findOrFail($orderId);
                $order->delivery_partner_id = null;
                $order->delivery_status = 'pending';
                $order->save();
                
                // Update DeliveryRequest if exists
                \App\Models\DeliveryRequest::where('order_id', $order->id)
                    ->where('delivery_partner_id', $partner->id)
                    ->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancellation_reason' => $reason]);
            }

            $partner->clearOrder();

            return redirect()->route('delivery-partner.dashboard')->with('success', 'Order delivery cancelled.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to cancel delivery: ' . $e->getMessage());
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $orderId)
    {
        $type = $request->input('type', 'standard');
        $status = $request->input('status');

        try {
            if ($type === 'food') {
                \App\Models\FoodOrder::where('id', $orderId)->update(['status' => $status]);
            } elseif ($type === 'ten_min') {
                \App\Models\TenMinOrder::where('id', $orderId)->update(['status' => $status]);
            } else {
                \App\Models\Order::where('id', $orderId)->update(['status' => $status]);
            }
            return redirect()->back()->with('success', 'Order status updated!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update status.');
        }
    }
}
