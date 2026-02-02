<?php

namespace App\Http\Controllers\DeliveryPartner;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\FoodOrder;
use App\Models\TenMinOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Show the delivery partner dashboard with minimal initial data.
     */
    public function index()
    {
        try {
            $partner = Auth::guard('delivery_partner')->user();

            if (!$partner) {
                \Illuminate\Support\Facades\Log::warning('Dashboard accessed without authentication', [
                    'session_id' => session()->getId(),
                    'guard_check' => Auth::guard('delivery_partner')->check(),
                    'user_agent' => request()->userAgent()
                ]);

                return redirect()->route('delivery-partner.login')
                    ->with('error', 'Please login to access the dashboard.');
            }

            \Illuminate\Support\Facades\Log::info('Dashboard loaded successfully', [
                'partner_id' => $partner->id,
                'partner_name' => $partner->name
            ]);

            $stats = $this->getDashboardStats($partner);
            $notifications = $this->getNotifications($partner, 10);
            $availableOrders = $this->getAvailableOrders($partner, 10);
            $recentOrders = $this->getRecentOrders($partner, 5);

            $activeOrder = $this->getActiveOrder($partner);

            // Update pulse
            if ($partner->is_online) {
                $partner->touchActivity();
            }

            return view('delivery-partner.dashboard.index', [
                'partner' => $partner,
                'initial_stats' => [
                    'name' => $partner->name,
                    'status' => $partner->status,
                    'is_online' => $partner->is_online,
                    'rating' => $partner->rating ?? 4.5,
                ],
                'stats' => $stats,
                'notifications' => $notifications,
                'availableOrders' => $availableOrders,
                'recentOrders' => $recentOrders,
                'activeOrder' => $activeOrder,
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Dashboard loading failed completely', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->view('errors.500', [
                'message' => 'Unable to load dashboard. Please try again later.'
            ], 500);
        }
    }

    /**
     * Get dashboard statistics.
     */
    private function getDashboardStats($partner): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        // Get wallet information
        $wallet = $partner->wallet;

        // --- Aggregated Stats ---
        $totalOrdersAll = $partner->total_deliveries_count;
        $completedOrdersAll = $partner->completed_deliveries_count;
        $allToday = $partner->today_deliveries_count;
        $totalPendingAll = $partner->pending_deliveries_count_all;

        return [
            'total_orders' => $totalOrdersAll,
            'completed_orders' => $completedOrdersAll,
            'completion_rate' => $totalOrdersAll > 0 ? round(($completedOrdersAll / $totalOrdersAll) * 100, 1) : 0,
            'rating' => $partner->rating ?? 4.5,
            'total_earnings' => $partner->total_earnings_all,
            'this_month_earnings' => $partner->this_month_earnings, // Still using model field for now
            'today_earnings' => $partner->today_earnings,
            'pending_orders' => $totalPendingAll,
            'today_deliveries' => $allToday,
            'week_deliveries' => 0,
            'month_deliveries' => 0,
            'active_hours' => $this->getActiveHours($partner),
            'wallet_balance' => $wallet ? $wallet->balance : 0,
            'total_withdrawals' => $wallet ? $wallet->total_withdrawals : 0,
            'available_earnings' => $wallet ? $wallet->available_earnings : 0,
        ];
    }

    /**
     * Get recent delivery requests for the partner.
     */
    private function getRecentOrders($partner, int $limit = 5)
    {
        $standardOrders = \App\Models\DeliveryRequest::where('delivery_partner_id', $partner->id)
            ->with(['order'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($req) {
                // DeliveryRequest wraps an Order.
                // We mock the structure to look like a direct order for uniformity or extract the order.
                // The current view expects 'order' object logic. 
                // Let's attach the normalized fields to the request's order if possible, 
                // OR wrap this whole thing.
                // ACTUALLY, the view uses $order directly in the loop.
                // `foreach($recentOrders->take(3) as $order)`
    
                // Let's check getRecentOrders implementation. 
                // Original returned `DeliveryRequest` collection.
                // But wait, the view iterates it and accesses `$order->order_number` etc.
                // That implies `$order` in the view is a `DeliveryRequest` instance, 
                // and it accesses `$order->order_number`? 
                // `DeliveryRequest` does NOT have `order_number`. 
                // It has `order` relationship.
                // BUT the view does `$order->order_number ?? 'ORD-' . $order->id`.
                // If `$order` is a DeliveryRequest, `$order->id` is the request ID.
                // So the view might be relying on direct properties if it was passing Order objects,
                // OR `DeliveryRequest` delegates, OR the previous code was weird.
                // Looking at the view: `@foreach($recentOrders->take(3) as $order)`
                // `{{ $order->order_number ?? ... }}`.
                // If `recentOrders` returns `DeliveryRequest`, this would fail unless `DeliveryRequest` has those attributes.
                // The original code returned `DeliveryRequest`.
    
                // Let's stick to returning Order objects (or normalized objects) to be safe and consistent with Available Orders.
    
                if ($req->order) {
                    $o = $req->order;
                    $o->type = 'standard';
                    $o->normalized_id = $o->id;
                    $o->normalized_order_number = $o->order_number;
                    $o->customer_name_display = $o->user->name ?? 'Customer';
                    $o->delivery_status_display = $req->status; // Use request status
                    $o->total_amount_display = $o->total_amount;
                    $o->created_at_display = $req->created_at; // Use request time
                    return $o;
                }
                return null;
            })->filter();

        // Food Orders (Direct relationship)
        $foodOrders = $partner->foodOrders()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                $order->type = 'food';
                $order->normalized_id = $order->id;
                $order->normalized_order_number = 'FOOD-' . $order->id;
                $order->customer_name_display = $order->customer_name;
                $order->delivery_status_display = $order->status;
                $order->total_amount_display = $order->total_amount;
                $order->created_at_display = $order->created_at;
                return $order;
            });

        // 10-Min Orders (Direct relationship)
        $tenMinOrders = $partner->tenMinOrders()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                $order->type = 'ten_min';
                $order->normalized_id = $order->id;
                $order->normalized_order_number = 'TM-' . $order->id;
                $order->customer_name_display = $order->customer_name;
                $order->delivery_status_display = $order->status;
                $order->total_amount_display = $order->total_amount;
                $order->created_at_display = $order->created_at;
                return $order;
            });

        return $standardOrders->concat($foodOrders)->concat($tenMinOrders)
            ->sortByDesc('created_at_display')
            ->take($limit);
    }

    /**
     * Get available delivery requests nearby.
     */
    private function getAvailableOrders($partner, int $limit = 10)
    {
        // Check if partner handles deliveries
        if (!$partner->isAvailableForDelivery()) {
            return collect();
        }

        // 1. Standard Orders
        $standardOrders = Order::with(['user', 'sellerUser'])
            ->where('delivery_status', 'pending')
            ->whereNull('delivery_partner_id')
            ->where('order_status', 'confirmed')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                $order->type = 'standard';
                $order->normalized_id = $order->id;
                $order->normalized_order_number = $order->order_number;
                $order->customer_name_display = $order->user->name ?? 'Customer';
                $order->delivery_address_display = $order->delivery_address;
                $order->total_amount_display = $order->total_amount;

                // Pickup info
                $order->pickup_name_display = $order->sellerUser->name ?? 'Seller';
                $order->pickup_address_display = $order->sellerUser->billing_address ?? 'Contact support';

                return $order;
            });

        // 2. Food Orders
        $foodOrders = FoodOrder::with(['hotelOwner'])->whereIn('status', ['pending', 'ready', 'cooking', 'preparing', 'paid', 'accepted'])
            ->whereNull('delivery_partner_id')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                $order->type = 'food';
                $order->normalized_id = $order->id;
                $order->normalized_order_number = 'FOOD-' . $order->id;
                $order->customer_name_display = $order->customer_name;
                $order->delivery_address_display = $order->delivery_address;
                $order->total_amount_display = $order->total_amount;

                // Pickup info
                $order->pickup_name_display = $order->shop_name ?? ($order->hotelOwner->restaurant_name ?? 'Restaurant');
                $order->pickup_address_display = $order->shop_address ?? ($order->hotelOwner->restaurant_address ?? 'Address not found');

                return $order;
            });

        // 3. 10-Min Orders
        $tenMinOrders = TenMinOrder::with(['seller'])->whereIn('status', ['pending', 'confirmed', 'packing', 'ready', 'paid'])
            ->whereNull('delivery_partner_id')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                $order->type = 'ten_min';
                $order->normalized_id = $order->id;
                $order->normalized_order_number = 'TM-' . $order->id;
                $order->customer_name_display = $order->customer_name;
                $order->delivery_address_display = $order->delivery_address;
                $order->total_amount_display = $order->total_amount;

                // Pickup info - Lookup Seller profile by user email
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

        // Merge and sort by creation time
        $allOrders = $standardOrders->concat($foodOrders)->concat($tenMinOrders)
            ->sortBy('created_at')
            ->take($limit);

        return $allOrders->filter(function ($order) use ($partner) {
            // Check if partner can deliver to this location
            // Normalized address check - assuming lat/long availability varies or requires address geocoding
            // For now, bypassing strict lat/long check if not present on all models, 
            // or we need to ensure models have these fields.
            // Standard Order has delivery_latitude/longitude.
            // FoodOrder/TenMinOrder might strictly rely on address string or we need to verify schema.
            // Based on file read, they have delivery_address.

            if (isset($order->delivery_latitude) && isset($order->delivery_longitude) && $order->delivery_latitude && $order->delivery_longitude) {
                return $partner->canDeliverTo(
                    $order->delivery_latitude,
                    $order->delivery_longitude
                );
            }
            return true; // If no coordinates, assume deliverable for now
        });
    }

    /**
     * Get today's earnings.
     */
    private function getTodayEarnings($partner): float
    {
        return $partner->today_earnings;
    }

    /**
     * Get notifications for the partner.
     */
    private function getNotifications($partner, int $limit = 5): array
    {
        $notifications = [];

        // Account status notification
        if ($partner->status === 'pending') {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Account Under Review',
                'message' => 'Your account is being reviewed. You will be notified once approved.',
                'icon' => 'fas fa-clock',
                'time' => $partner->created_at->diffForHumans(),
            ];
        } elseif ($partner->status === 'rejected') {
            $notifications[] = [
                'type' => 'danger',
                'title' => 'Account Rejected',
                'message' => 'Your account has been rejected. Please contact support.',
                'icon' => 'fas fa-times-circle',
                'time' => $partner->updated_at->diffForHumans(),
            ];
        }

        // Document expiry warnings
        if ($partner->license_expiry && Carbon::parse($partner->license_expiry)->diffInDays(today()) <= 30) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'License Expiry Warning',
                'message' => 'Your license expires on ' . Carbon::parse($partner->license_expiry)->format('d M Y'),
                'icon' => 'fas fa-id-card',
                'time' => 'Important',
            ];
        }

        if ($partner->insurance_expiry && Carbon::parse($partner->insurance_expiry)->diffInDays(today()) <= 30) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Insurance Expiry Warning',
                'message' => 'Your insurance expires on ' . Carbon::parse($partner->insurance_expiry)->format('d M Y'),
                'icon' => 'fas fa-shield-alt',
                'time' => 'Important',
            ];
        }

        // Rating notifications
        if ($partner->rating < 3.5 && $partner->total_orders > 10) {
            $notifications[] = [
                'type' => 'info',
                'title' => 'Improve Your Rating',
                'message' => 'Your current rating is ' . $partner->rating . '. Focus on timely deliveries and customer service.',
                'icon' => 'fas fa-star',
                'time' => 'Tip',
            ];
        }

        // Real database notifications
        $dbNotifications = \App\Models\Notification::where('notifiable_type', get_class($partner))
            ->where('notifiable_id', $partner->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->type === 'order_assigned' ? 'success' : 'info',
                    'title' => $n->title,
                    'message' => $n->message,
                    'icon' => $n->type === 'order_assigned' ? 'fas fa-bell' : 'fas fa-info-circle',
                    'time' => $n->created_at->diffForHumans(),
                ];
            })
            ->toArray();

        $notifications = array_merge($dbNotifications, $notifications);

        return array_slice($notifications, 0, $limit);
    }

    /**
     * Get active hours for the partner.
     */
    private function getActiveHours(DeliveryPartner $partner): string
    {
        if (!$partner->last_active_at) {
            return '0h 0m';
        }

        $today = Carbon::today();
        $lastActive = $partner->last_active_at;

        if ($lastActive->isToday()) {
            $hours = $lastActive->diffInHours($today->copy()->endOfDay());
            $minutes = $lastActive->diffInMinutes($today->copy()->endOfDay()) % 60;
            return "{$hours}h {$minutes}m";
        }

        return '0h 0m';
    }

    /**
     * Get quick stats for mobile API.
     */
    public function quickStats(): JsonResponse
    {
        $partner = Auth::guard('delivery_partner')->user();
        $stats = $this->getDashboardStats($partner);

        return response()->json([
            'success' => true,
            'data' => [
                'is_online' => $partner->is_online,
                'is_available' => $partner->is_available,
                'pending_orders' => $stats['pending_orders'],
                'today_earnings' => $stats['today_earnings'],
                'rating' => $stats['rating'],
                'completion_rate' => $stats['completion_rate'],
            ]
        ]);
    }

    /**
     * Search for orders or other data.
     */
    public function search(Request $request): JsonResponse
    {
        $partner = Auth::guard('delivery_partner')->user();
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Search query must be at least 2 characters long.'
            ]);
        }

        // Search in orders
        $orders = $partner->orders()
            ->with(['user', 'orderItems.product'])
            ->where(function ($q) use ($query) {
                $q->where('order_number', 'like', "%{$query}%")
                    ->orWhere('delivery_address', 'like', "%{$query}%")
                    ->orWhereHas('user', function ($userQuery) use ($query) {
                        $userQuery->where('name', 'like', "%{$query}%")
                            ->orWhere('phone', 'like', "%{$query}%");
                    });
            })
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->user->name,
                        'delivery_address' => $order->delivery_address,
                        'total_amount' => $order->total_amount,
                        'delivery_status' => $order->delivery_status,
                        'created_at' => $order->created_at->format('d M Y, h:i A'),
                    ];
                }),
            ]
        ]);
    }

    /**
     * Get notifications via API.
     */
    public function notifications(): JsonResponse
    {
        $partner = Auth::guard('delivery_partner')->user();
        $notifications = $this->getNotifications($partner, 20);

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Update partner's working status.
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $partner = Auth::guard('delivery_partner')->user();

        $request->validate([
            'status' => 'required|in:online,offline,available,busy'
        ]);

        $status = $request->status;

        try {
            switch ($status) {
                case 'online':
                    if (!$partner->isAvailableForDelivery()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Your account must be approved to go online.'
                        ]);
                    }
                    $partner->goOnline();
                    $partner->touchActivity();
                    break;

                case 'offline':
                    $partner->goOffline();
                    break;

                case 'available':
                    if (!$partner->is_online) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You must be online to become available.'
                        ]);
                    }
                    $partner->update(['is_available' => true]);
                    break;

                case 'busy':
                    $partner->update(['is_available' => false]);
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.',
                'data' => [
                    'is_online' => $partner->is_online,
                    'is_available' => $partner->is_available,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status. Please try again.'
            ]);
        }
    }

    /**
     * Get the current active order for the partner.
     */
    private function getActiveOrder($partner)
    {
        $order = $partner->currentOrder;

        if (!$order) {
            return null;
        }

        // Normalize order data
        if ($order instanceof FoodOrder) {
            $order->type = 'food';
            $order->normalized_order_number = 'FOOD-' . $order->id;
            $order->customer_name_display = $order->customer_name;
            $order->delivery_address_display = $order->delivery_address;
            $order->pickup_name_display = $order->shop_name ?? ($order->hotelOwner->restaurant_name ?? 'Restaurant');
            $order->pickup_address_display = $order->shop_address ?? ($order->hotelOwner->restaurant_address ?? 'Address not found');
        } elseif ($order instanceof TenMinOrder) {
            $order->type = 'ten_min';
            $order->normalized_order_number = 'TM-' . $order->id;
            $order->customer_name_display = $order->customer_name;
            $order->delivery_address_display = $order->delivery_address;

            $pickupName = 'Shop';
            $pickupAddress = 'Address not found';
            if ($order->seller) {
                $sellerProfile = \App\Models\Seller::where('email', $order->seller->email)->first();
                $pickupName = $sellerProfile->store_name ?? ($order->seller->name ?? 'Shop');
                $pickupAddress = $sellerProfile->store_address ?? ($order->seller->billing_address ?? 'Address not found');
            }
            $order->pickup_name_display = $pickupName;
            $order->pickup_address_display = $pickupAddress;
        } elseif ($order instanceof Order) {
            $order->type = 'standard';
            $order->normalized_order_number = $order->order_number;
            $order->customer_name_display = $order->user->name ?? 'Customer';
            $order->delivery_address_display = $order->delivery_address;
            $order->pickup_name_display = $order->sellerUser->name ?? 'Seller';
            $order->pickup_address_display = $order->sellerUser->billing_address ?? 'Address not found';
        }

        return $order;
    }

    /**
     * Refresh available orders list (AJAX).
     */
    public function refreshAvailableOrders(Request $request)
    {
        $partner = Auth::guard('delivery_partner')->user();

        if (!$partner) {
            return response()->json(['html' => '']);
        }

        // Limit to 5 for the dashboard widget
        $availableOrders = $this->getAvailableOrders($partner, 5);

        $html = view('delivery-partner.dashboard.partials.available-orders', compact('availableOrders', 'partner'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'count' => $availableOrders->count()
        ]);
    }
}
