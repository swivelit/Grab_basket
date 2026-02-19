<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\TenMinOrder;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Services\InfobipSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Cancel an order (buyer) - supports both Food and Express orders
     */
    public function cancel(Request $request, $type, $id)
    {
        $user = Auth::user();

        if ($type === 'food') {
            $order = Order::where('buyer_id', $user->id)->findOrFail($id);

            // Only allow cancel if status is not shipped or delivered
            $cancellableStatuses = ['pending', 'paid', 'confirmed'];
            if (!in_array($order->status, $cancellableStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be cancelled at this stage.'
                ], 400);
            }


            $order->status = 'cancelled';
            $order->save();

            // Send notification
            if (class_exists('App\Services\NotificationService')) {
                \App\Services\NotificationService::sendOrderStatusUpdate($user, $order, 'cancelled');
            }

            // Notify seller
            $seller = $order->sellerUser;
            if ($seller && $seller->email) {
                $subject = 'Order Cancelled by Buyer';
                $message = "Order #{$order->id} has been cancelled by the buyer.";
                try {
                    Mail::raw($message, function ($mail) use ($seller, $subject) {
                        $mail->to($seller->email)->subject($subject);
                    });
                } catch (\Exception $e) {
                    Log::warning('Failed to send cancellation email to seller: ' . $e->getMessage());
                }
            }

        } elseif ($type === 'express') {
            $order = \App\Models\TenMinOrder::where('user_id', $user->id)->findOrFail($id);

            // Only allow cancel if status is not shipped or delivered
            $cancellableStatuses = ['pending', 'confirmed'];
            if (!in_array($order->status, $cancellableStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be cancelled at this stage.'
                ], 400);
            }

            $order->status = 'cancelled';
            $order->save();

        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid order type.'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully.'
        ]);
    }
    // Show all orders for the logged-in seller
    public function sellerOrders()
    {
        $orders = Order::with(['product', 'buyerUser'])
            ->where('seller_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('orders.seller-orders', compact('orders'));
    }
    public function track()
    {
        $user = Auth::user();

        // Standard Orders (Food/Grocery)
        $standardOrders = Order::with(['product', 'sellerUser', 'deliveryPartner'])
            ->where('buyer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'type' => 'Food Delivery',
                    'order_number' => 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                    'date' => $order->created_at,
                    'status' => $order->delivery_status ?? $order->order_status ?? $order->status ?? 'pending',
                    'total_amount' => $order->amount,
                    'product_name' => $order->product->name ?? 'Standard Order',
                    'product_image' => $order->product ? $order->product->image_url : null,
                    'seller_name' => $order->sellerUser->name ?? 'GrabBaskets',
                    'delivery_partner' => $order->deliveryPartner,
                    'tracking_number' => $order->tracking_number
                ];
            });

        // 10-Min Orders
        // $tenMinOrders = TenMinOrder::with(['items.product', 'seller', 'deliveryPartner'])
        //     ->where('user_id', $user->id)
        //     ->orderBy('created_at', 'desc')
        //     ->get()
        //     ->map(function ($order) {
        //         $firstItem = $order->items->first();
        //         return [
        //             'id' => $order->id,
        //             'type' => 'Express Delivery',
        //             'order_number' => 'EXP-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
        //             'date' => $order->created_at,
        //             'status' => $order->status,
        //             'total_amount' => $order->total_amount,
        //             'product_name' => $firstItem && $firstItem->product ? $firstItem->product->product_name : '10-Min Order',
        //             'product_image' => $firstItem && $firstItem->product ? $firstItem->product->first_image_url : null,
        //             'seller_name' => $order->seller->name ?? 'Partner Store',
        //             'delivery_partner' => $order->deliveryPartner,
        //             'tracking_number' => null
        //         ];
        //     });

        $orders = $standardOrders->sortByDesc('date');

        return view('orders.track', compact('orders'));
    }
    /**
     * Show individual order details (supports both Food and Express orders)
     */
    public function show($type, $id)
    {
        $user = Auth::user();

        if ($type === 'food') {
            $order = Order::where('buyer_id', $user->id)
                ->with(['product', 'sellerUser', 'orderItems.product'])
                ->findOrFail($id);

            $orderData = [
                'id' => $order->id,
                'order_number' => 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                'type' => 'Food Delivery',
                'type_badge' => 'primary',
                'date' => $order->created_at,
                'status' => $order->status,
                'total_amount' => $order->amount,
                'payment_method' => $order->payment_method,
                'payment_reference' => $order->payment_reference,
                'delivery_address' => $order->delivery_address,
                'delivery_city' => $order->delivery_city,
                'delivery_state' => $order->delivery_state,
                'delivery_pincode' => $order->delivery_pincode,
                'items' => $order->orderItems && $order->orderItems->count() > 0
                    ? $order->orderItems
                    : collect([
                        (object) [
                            'product' => $order->product,
                            'quantity' => $order->quantity ?? 1,
                            'price' => $order->amount,
                        ]
                    ]),
                'seller' => $order->sellerUser,
                'tracking_number' => $order->tracking_number,
                'courier_name' => $order->courier_name,
            ];
        } elseif ($type === 'express') {
            $order = \App\Models\TenMinOrder::where('user_id', $user->id)
                ->with(['items.product', 'user'])
                ->findOrFail($id);

            $orderData = [
                'id' => $order->id,
                'order_number' => 'EXP-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                'type' => '10-Mins Express',
                'type_badge' => 'success',
                'date' => $order->created_at,
                'status' => $order->status,
                'order_total' => $order->order_total,
                'delivery_fee' => $order->delivery_fee,
                'tax' => $order->tax ?? 0,
                'wallet_discount' => $order->wallet_discount ?? 0,
                'total_amount' => $order->total_amount,
                'payment_method' => $order->payment_method,
                'payment_reference' => $order->payment_reference,
                'delivery_address' => $order->delivery_address,
                'items' => $order->items,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'customer_email' => $order->customer_email,
                'estimated_delivery_time' => $order->estimated_delivery_time,
            ];
        } else {
            abort(404, 'Invalid order type');
        }

        return view('orders.show', compact('orderData', 'type'));
    }
    public function updateTracking(Request $request, Order $order)
    {
        $request->validate([
            'tracking_number' => 'required|string|max:255',
            'courier_name' => 'nullable|string|max:255',
        ]);

        // Allow if user is the seller of this product or is an admin
        $user = Auth::user();
        $isSeller = $order->product->seller_id === $user->id;
        $isAdmin = $user->role === 'admin';
        if (!($isSeller || $isAdmin)) {
            abort(403);
        }

        $order->tracking_number = $request->tracking_number;
        $order->courier_name = $request->courier_name ?? 'Unknown Courier';
        $order->save();

        // Create Amazon-style tracking notification
        $buyer = $order->buyerUser;
        if ($buyer) {
            // Create in-app notification
            Notification::create([
                'user_id' => $buyer->id,
                'title' => 'Package Shipped! 📦',
                'message' => "Great news! Your order #{$order->id} has been shipped via {$order->courier_name}. Track it with number: {$order->tracking_number}",
                'type' => 'order_shipped',
                'data' => json_encode([
                    'order_id' => $order->id,
                    'tracking_number' => $order->tracking_number,
                    'courier_name' => $order->courier_name,
                    'tracking_url' => route('tracking.form') . '?tracking_number=' . $order->tracking_number
                ])
            ]);

            // Send email notification
            if ($buyer->email) {
                $subject = 'Your Order Has Been Shipped! 🚚';
                $trackingUrl = route('tracking.form') . '?tracking_number=' . $order->tracking_number;
                $message = "
Dear {$buyer->name},

Exciting news! Your order #{$order->id} has been shipped and is on its way to you.

📦 Tracking Details:
• Courier: {$order->courier_name}
• Tracking Number: {$order->tracking_number}
• Track Your Package: {$trackingUrl}

You can track your package in real-time using our tracking system. Just click the link above or enter your tracking number on our website.

Thank you for shopping with us!

Best regards,
Grabbasket Team
                ";

                Mail::raw($message, function ($mail) use ($buyer, $subject) {
                    $mail->to($buyer->email)
                        ->subject($subject);
                });
            }
        }

        // Update order status to shipped if it's not already
        if ($order->status !== 'shipped' && $order->status !== 'delivered') {
            $order->status = 'shipped';
            $order->save();
        }

        // 📱 Send SMS shipping notification to buyer
        if ($buyer && $buyer->phone) {
            $smsService = new InfobipSmsService();
            $smsResult = $smsService->sendShippingNotificationToBuyer($buyer, $order);
            if ($smsResult['success']) {
                Log::info('Shipping SMS sent to buyer', ['buyer_id' => $buyer->id, 'order_id' => $order->id]);
            } else {
                Log::warning('Failed to send shipping SMS to buyer', ['buyer_id' => $buyer->id, 'error' => $smsResult['error']]);
            }
        }

        return back()->with('success', 'Tracking information updated successfully.');
    }

    // Show all orders for authenticated user (Food + Express)
    public function index()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return redirect()->route('login')
                    ->with('error', 'Please login to view your orders.');
            }

            // Fetch Food/Standard Orders
            $foodOrders = Order::where('buyer_id', $user->id)
                ->with(['product', 'sellerUser', 'orderItems.product', 'deliveryPartner'])
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => 'ORD-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                        'type' => 'Food Delivery',
                        'type_badge' => 'primary',
                        'date' => $order->created_at,
                        'status' => $order->status,
                        'total_amount' => $order->amount,
                        'payment_method' => $order->payment_method ?? 'N/A',
                        'delivery_address' => $order->delivery_address,
                        'items' => $order->orderItems && $order->orderItems->count() > 0
                            ? $order->orderItems->map(function ($item) {
                                return [
                                    'product_name' => $item->product->name ?? 'Product Not Found',
                                    'product_image' => $item->product->image_url ?? null,
                                    'quantity' => $item->quantity,
                                    'price' => $item->price,
                                    'total_price' => $item->total_price ?? ($item->price * $item->quantity),
                                ];
                            })
                            : collect([
                                [
                                    'product_name' => $order->product->name ?? 'Product Not Found',
                                    'product_image' => $order->product->image_url ?? null,
                                    'quantity' => $order->quantity ?? 1,
                                    'price' => $order->amount,
                                    'total_price' => $order->amount,
                                ]
                            ]),
                        'seller_name' => $order->sellerUser->name ?? 'N/A',
                        'tracking_number' => $order->tracking_number,
                        'courier_name' => $order->courier_name,
                        'delivery_partner_name' => $order->deliveryPartner->name ?? null,
                        'delivery_partner_phone' => $order->deliveryPartner->phone ?? null,
                        'original_order' => $order
                    ];
                });

            // Fetch Express/10-Min Orders
            $expressOrders = \App\Models\TenMinOrder::where('user_id', $user->id)
                ->with(['items.product', 'user', 'deliveryPartner'])
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => 'EXP-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                        'type' => '10-Mins Express',
                        'type_badge' => 'success',
                        'date' => $order->created_at,
                        'status' => $order->status,
                        'total_amount' => $order->total_amount,
                        'payment_method' => $order->payment_method ?? 'N/A',
                        'delivery_address' => $order->delivery_address,
                        'items' => $order->items->map(function ($item) {
                            return [
                                'product_name' => $item->product_name,
                                'product_image' => optional($item->product)->image_url ?? null,
                                'quantity' => $item->quantity,
                                'price' => $item->price,
                                'total_price' => $item->price * $item->quantity,
                            ];
                        }),
                        'seller_name' => 'Multiple Sellers',
                        'delivery_fee' => $order->delivery_fee,
                        'tax' => $order->tax ?? 0,
                        'wallet_discount' => $order->wallet_discount ?? 0,
                        'estimated_delivery_time' => $order->estimated_delivery_time,
                        'delivery_partner_name' => $order->deliveryPartner->name ?? null,
                        'delivery_partner_phone' => $order->deliveryPartner->phone ?? null,
                        'original_order' => $order
                    ];
                });

            // Merge and sort by date
            $allOrders = $foodOrders->concat($expressOrders)
                ->sortByDesc('date')
                ->values();

            // Manual pagination
            $perPage = 10;
            $currentPage = request()->get('page', 1);
            $orders = new \Illuminate\Pagination\LengthAwarePaginator(
                $allOrders->forPage($currentPage, $perPage),
                $allOrders->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            return view('orders.index', compact('orders'));
        } catch (\Exception $e) {
            Log::error('Orders Index Error: ' . $e->getMessage());

            return view('orders.index', [
                'orders' => new \Illuminate\Pagination\LengthAwarePaginator(
                    collect([]),
                    0,
                    10,
                    1,
                    ['path' => request()->url()]
                ),
                'error' => 'Unable to load orders. Please try again later.'
            ]);
        }
    }

    /**
     * Update order status with Amazon-like notifications
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|string|in:confirmed,shipped,out_for_delivery,delivered'
        ]);

        // Allow if user is the seller of this product or is an admin
        $user = Auth::user();
        $isSeller = $order->product->seller_id === $user->id;
        $isAdmin = $user->role === 'admin';
        if (!($isSeller || $isAdmin)) {
            abort(403);
        }

        $oldStatus = $order->status;
        $newStatus = $request->status;

        $order->status = $newStatus;
        $order->save();

        // Send notification to buyer
        NotificationService::sendOrderStatusUpdate($order->buyerUser, $order, $newStatus);

        // Send SMS notification to buyer about status change
        $smsService = new \App\Services\SmsService();
        $smsResult = $smsService->sendOrderStatusUpdateToBuyer($order->buyerUser, $order, $newStatus);
        if ($smsResult['success']) {
            \Illuminate\Support\Facades\Log::info('Order status update SMS sent to buyer', [
                'buyer_id' => $order->buyer_id,
                'order_id' => $order->id,
                'status' => $newStatus
            ]);
        }

        // Special handling for delivery completion
        if ($newStatus === 'delivered') {
            // Send review request after 24 hours (in real app, use a queue/job)
            NotificationService::sendReviewRequest($order->buyerUser, $order);
        }

        return back()->with('success', "Order status updated to {$newStatus} and buyer notified.");
    }

    /**
     * Send promotional notifications
     */
    public function sendPromotion(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:500',
            'user_type' => 'required|in:all,buyers,sellers'
        ]);

        // Get users based on type
        $users = collect();
        if ($request->user_type === 'all') {
            $users = \App\Models\User::all();
        } elseif ($request->user_type === 'buyers') {
            $users = \App\Models\User::where('role', 'buyer')->get();
        } elseif ($request->user_type === 'sellers') {
            $users = \App\Models\User::where('role', 'seller')->get();
        }

        // Send bulk notifications
        NotificationService::sendBulkNotification(
            $users->pluck('id')->toArray(),
            'promotion',
            $request->title,
            $request->message
        );

        return back()->with('success', "Promotional notification sent to {$users->count()} users.");
    }

    /**
     * Show live tracking page for an order
     */
    public function liveTracking(Order $order)
    {
        // Verify buyer owns this order
        if ($order->buyer_id !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }

        return view('orders.live-tracking', compact('order'));
    }

    /**
     * Check if quick delivery is available for given address
     */
    public function checkQuickDelivery(Request $request)
    {
        $request->validate([
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'pincode' => 'required|string',
            'store_id' => 'required|integer'
        ]);

        // Get coordinates from address
        $coordinates = \App\Services\QuickDeliveryService::getCoordinates(
            $request->address,
            $request->city,
            $request->state,
            $request->pincode
        );

        if (!$coordinates) {
            return response()->json([
                'eligible' => false,
                'message' => 'Unable to verify address. Please check and try again.'
            ], 400);
        }

        // Get warehouse coordinates
        // GrabBaskets Warehouse: Mahatma Gandhi Nagar Rd, Near Annai Therasa English School
        // MRR Nagar, Palani Chettipatti, Theni, Tamil Nadu 625531
        $storeLatitude = 10.0103; // Theni, Tamil Nadu
        $storeLongitude = 77.4773; // Theni, Tamil Nadu

        // Check eligibility
        $eligibility = \App\Services\QuickDeliveryService::checkEligibility(
            $coordinates['latitude'],
            $coordinates['longitude'],
            $storeLatitude,
            $storeLongitude
        );

        return response()->json($eligibility);
    }

    /**
     * Assign delivery partner to order
     */
    public function assignDelivery(Order $order)
    {
        // Only seller can assign delivery
        if ($order->seller_id !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }

        $partner = \App\Services\QuickDeliveryService::assignDeliveryPartner($order);

        // Update order status to shipped
        $order->update(['status' => 'shipped']);

        return back()->with('success', 'Delivery partner assigned: ' . $partner['name']);
    }

    /**
     * API: Get current tracking data for order
     */
    public function apiTrackOrder(Order $order)
    {
        // In production, add proper authentication

        return response()->json([
            'order_id' => $order->id,
            'status' => $order->status,
            'delivery_type' => $order->delivery_type,
            'latitude' => $order->delivery_latitude,
            'longitude' => $order->delivery_longitude,
            'eta_minutes' => $order->eta_minutes,
            'distance_km' => $order->distance_km,
            'delivery_partner' => [
                'name' => $order->delivery_partner_name,
                'phone' => $order->delivery_partner_phone,
                'vehicle' => $order->delivery_partner_vehicle,
            ],
            'location_updated_at' => $order->location_updated_at,
        ]);
    }

    /**
     * API: Update delivery partner location (called by delivery partner app)
     */
    public function apiUpdateLocation(Request $request, Order $order)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric'
        ]);

        \App\Services\QuickDeliveryService::updateDeliveryLocation(
            $order,
            $request->latitude,
            $request->longitude
        );

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'eta_minutes' => $order->fresh()->eta_minutes
        ]);
    }

    /**
     * Show live tracking page with Google Maps
     */
    public function liveTrack()
    {
        $orders = Order::with(['product', 'sellerUser', 'deliveryPartner'])
            ->where('buyer_id', Auth::id())
            ->whereIn('status', ['paid', 'confirmed', 'shipped'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('orders.live-track', compact('orders'));
    }

    /**
     * Get real-time location data for order (API endpoint)
     */
    public function getLocation($id)
    {
        $order = Order::with('deliveryPartner')->findOrFail($id);

        // Verify user owns this order
        if ($order->buyer_id !== Auth::id()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $data = [
            'success' => true,
            'status' => $order->status,
            'delivery_lat' => null,
            'delivery_lng' => null,
        ];

        // If delivery partner is assigned, get their current location
        if ($order->deliveryPartner) {
            $data['delivery_lat'] = $order->deliveryPartner->latitude;
            $data['delivery_lng'] = $order->deliveryPartner->longitude;
            $data['delivery_partner'] = [
                'name' => $order->deliveryPartner->name,
                'phone' => $order->deliveryPartner->phone,
            ];
        }

        return response()->json($data);
    }
}

