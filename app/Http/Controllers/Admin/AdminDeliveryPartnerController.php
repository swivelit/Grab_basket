<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\DeliveryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\DeliveryJobAssigned;
use Carbon\Carbon;

class AdminDeliveryPartnerController extends Controller
{
    /**
     * Show delivery partners dashboard with overview stats
     */
    public function dashboard()
    {
        try {
            $stats = [
                'total_partners' => DeliveryPartner::count(),
                'online_partners' => DeliveryPartner::where('is_online', true)->count(),
                'available_partners' => DeliveryPartner::where('is_available', true)->count(),
                'pending_partners' => DeliveryPartner::where('status', 'pending')->count(),
                'active_deliveries' => 0,
                'completed_today' => 0,
            ];

            // Try to get delivery request stats if model/table exists
            try {
                if (class_exists('\App\Models\DeliveryRequest')) {
                    $stats['active_deliveries'] = DeliveryRequest::whereIn('status', ['accepted', 'picked_up'])->count();
                    $stats['completed_today'] = DeliveryRequest::where('status', 'completed')
                        ->whereDate('completed_at', today())
                        ->count();
                }
            } catch (\Exception $e) {
                Log::warning('Could not load delivery request stats: ' . $e->getMessage());
            }

            // Try to get recent activity
            $recentActivity = [];
            try {
                if (class_exists('\App\Models\DeliveryRequest')) {
                    $recentActivity = DeliveryRequest::with(['deliveryPartner', 'order'])
                        ->orderBy('updated_at', 'desc')
                        ->limit(10)
                        ->get();
                }
            } catch (\Exception $e) {
                Log::warning('Could not load recent activity: ' . $e->getMessage());
            }

            // Get online partners
            $onlinePartners = DeliveryPartner::where('is_online', true)
                ->orderBy('updated_at', 'desc')
                ->limit(15)
                ->get();

            return view('admin.delivery-partners.dashboard', compact('stats', 'recentActivity', 'onlinePartners'));
        } catch (\Exception $e) {
            Log::error('Admin Delivery Partner Dashboard Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()->with('error', 'Error loading dashboard: ' . $e->getMessage());
        }
    }

    /**
     * List all delivery partners with filters
     */
    public function index(Request $request)
    {
        $query = DeliveryPartner::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by online status
        if ($request->filled('is_online')) {
            $query->where('is_online', (bool) $request->is_online);
        }

        // Filter by availability
        if ($request->filled('is_available')) {
            $query->where('is_available', (bool) $request->is_available);
        }

        // Search by name or phone
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $partners = $query->paginate(15);

        return view('admin.delivery-partners.index', compact('partners'));
    }

    /**
     * Show detailed view of a specific delivery partner
     */
    public function show(DeliveryPartner $deliveryPartner)
    {
        // Try to load relationships, but handle missing ones gracefully
        try {
            $partner = $deliveryPartner->load([
                'deliveryRequests' => function ($q) {
                    $q->orderBy('created_at', 'desc')->limit(20);
                }
            ]);
        } catch (\Exception $e) {
            $partner = $deliveryPartner;
            Log::warning('Could not load delivery partner relationships: ' . $e->getMessage());
        }

        // Get statistics with error handling
        $stats = [
            'total_deliveries' => 0,
            'completed_deliveries' => 0,
            'pending_deliveries' => 0,
            'today_deliveries' => 0,
            'today_earnings' => 0,
            'total_earnings' => 0,
            'avg_rating' => $partner->rating ?? 0,
            'completion_rate' => 0,
        ];

        try {
            $stats['total_deliveries'] = $partner->total_deliveries_count;
            $stats['completed_deliveries'] = $partner->completed_deliveries_count;
            $stats['pending_deliveries'] = $partner->pending_deliveries_count_all;
            $stats['today_deliveries'] = $partner->today_deliveries_count;
            $stats['today_earnings'] = $partner->today_earnings;
            $stats['total_earnings'] = $partner->total_earnings_all;
            $stats['completion_rate'] = $partner->total_deliveries_count > 0
                ? round(($partner->completed_deliveries_count / $partner->total_deliveries_count) * 100, 2)
                : 0;
        } catch (\Exception $e) {
            Log::warning('Could not load delivery statistics: ' . $e->getMessage());
        }

        // Get pending orders available for assignment
        $availableOrders = collect([]);
        try {
            $availableOrders = Order::where('delivery_status', 'pending')
                ->whereNull('delivery_partner_id')
                ->where('order_status', 'confirmed')
                ->with(['user', 'orderItems.product'])
                ->orderBy('created_at', 'asc')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::warning('Could not load available orders: ' . $e->getMessage());
        }

        return view('admin.delivery-partners.show', compact('partner', 'stats', 'availableOrders'));
    }

    /**
     * Assign a delivery job to a partner
     */
    public function assignJob(Request $request, DeliveryPartner $deliveryPartner)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $order = Order::findOrFail($request->order_id);

            // Check if order is available
            if ($order->delivery_status !== 'pending' || $order->delivery_partner_id !== null) {
                return back()->with('error', 'Order is no longer available for assignment.');
            }

            // Create delivery request
            $deliveryRequest = DeliveryRequest::create([
                'order_id' => $order->id,
                'delivery_partner_id' => $deliveryPartner->id,
                'status' => 'pending',
                'assigned_at' => now(),
                'notes' => $request->notes,
            ]);

            // Update order
            $order->update([
                'delivery_partner_id' => $deliveryPartner->id,
                'delivery_status' => 'assigned'
            ]);

            // Send notification to delivery partner
            $deliveryPartner->notify(new DeliveryJobAssigned($order, $deliveryRequest));

            Log::info('Delivery job assigned', [
                'order_id' => $order->id,
                'delivery_partner_id' => $deliveryPartner->id,
                'admin_id' => auth('admin')->id() ?? session('admin_id'),
            ]);

            return back()->with('success', "Job assigned to {$deliveryPartner->name} successfully!");

        } catch (\Exception $e) {
            Log::error('Failed to assign delivery job', [
                'error' => $e->getMessage(),
                'order_id' => $request->order_id,
                'delivery_partner_id' => $deliveryPartner->id,
            ]);

            return back()->with('error', 'Failed to assign job. Please try again.');
        }
    }

    /**
     * Bulk assign jobs to available partners
     */
    public function bulkAssignJobs(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id'
        ]);

        try {
            $orders = Order::whereIn('id', $request->order_ids)
                ->where('delivery_status', 'pending')
                ->whereNull('delivery_partner_id')
                ->get();

            $assigned = 0;
            $failed = 0;

            foreach ($orders as $order) {
                // Find best available partner
                $partner = $this->findBestAvailablePartner($order);

                if ($partner) {
                    DeliveryRequest::create([
                        'order_id' => $order->id,
                        'delivery_partner_id' => $partner->id,
                        'status' => 'pending',
                        'assigned_at' => now(),
                    ]);

                    $order->update([
                        'delivery_partner_id' => $partner->id,
                        'delivery_status' => 'assigned'
                    ]);

                    $partner->notify(new DeliveryJobAssigned($order, null));
                    $assigned++;
                } else {
                    $failed++;
                }
            }

            Log::info('Bulk job assignment completed', [
                'assigned' => $assigned,
                'failed' => $failed,
                'total' => count($orders)
            ]);

            return back()->with('success', "Assigned $assigned jobs. $failed could not be assigned.");

        } catch (\Exception $e) {
            Log::error('Bulk job assignment failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Bulk assignment failed. Please try again.');
        }
    }

    /**
     * Update delivery partner status
     */
    public function updateStatus(Request $request, DeliveryPartner $deliveryPartner)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,suspended,inactive'
        ]);

        try {
            $oldStatus = $deliveryPartner->status;
            $deliveryPartner->status = $request->status;
            $deliveryPartner->save();

            Log::info('Delivery partner status updated', [
                'partner_id' => $deliveryPartner->id,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
            ]);

            return back()->with('success', 'Status updated successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to update partner status', [
                'error' => $e->getMessage(),
                'partner_id' => $deliveryPartner->id
            ]);
            return back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    /**
     * Track delivery partner location and status in real-time
     */
    public function track(DeliveryPartner $deliveryPartner)
    {
        $currentDeliveries = collect([]);
        $locationHistory = collect([]);

        try {
            $currentDeliveries = DeliveryRequest::where('delivery_partner_id', $deliveryPartner->id)
                ->whereIn('status', ['accepted', 'picked_up'])
                ->with('order')
                ->get();
        } catch (\Exception $e) {
            Log::warning('Could not load current deliveries: ' . $e->getMessage());
        }

        try {
            $locationHistory = DeliveryRequest::where('delivery_partner_id', $deliveryPartner->id)
                ->where('status', 'completed')
                ->whereDate('completed_at', today())
                ->with('order')
                ->orderBy('completed_at', 'desc')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::warning('Could not load location history: ' . $e->getMessage());
        }

        return view('admin.delivery-partners.track', compact('deliveryPartner', 'currentDeliveries', 'locationHistory'));
    }

    /**
     * Get available delivery partners for assignment
     */
    public function getAvailablePartners(Request $request)
    {
        $partners = DeliveryPartner::where('is_online', true)
            ->where('is_available', true)
            ->where('status', 'approved')  // Only approved partners can receive orders
            ->with('wallet')
            ->get()
            ->map(function ($partner) {
                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'phone' => $partner->phone,
                    'is_online' => $partner->is_online,
                    'is_available' => $partner->is_available,
                    'rating' => $partner->rating,
                    'active_deliveries' => DeliveryRequest::where('delivery_partner_id', $partner->id)
                        ->whereIn('status', ['accepted', 'picked_up'])
                        ->count(),
                    'earnings_today' => $partner->wallet?->today_earnings ?? 0,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $partners
        ]);
    }

    /**
     * Send notification to delivery partner
     */
    public function sendNotification(Request $request, DeliveryPartner $deliveryPartner)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'message' => 'required|string|max:500',
            'type' => 'required|in:info,warning,success,error'
        ]);

        try {
            // Create notification in database
            DB::table('notifications')->insert([
                'notifiable_type' => DeliveryPartner::class,
                'notifiable_id' => $deliveryPartner->id,
                'type' => 'App\\Notifications\\AdminNotification',
                'data' => json_encode([
                    'title' => $request->title,
                    'message' => $request->message,
                    'type' => $request->type,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Notification sent to delivery partner', [
                'partner_id' => $deliveryPartner->id,
                'title' => $request->title,
            ]);

            return back()->with('success', 'Notification sent successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to send notification', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to send notification.');
        }
    }

    /**
     * Get delivery statistics
     */
    public function getStatistics(Request $request)
    {
        $period = $request->get('period', 'today'); // today, week, month

        $query = DeliveryRequest::where('status', 'completed');

        if ($period === 'today') {
            $query->whereDate('completed_at', today());
        } elseif ($period === 'week') {
            $query->where('completed_at', '>=', now()->startOfWeek());
        } elseif ($period === 'month') {
            $query->where('completed_at', '>=', now()->startOfMonth());
        }

        $stats = [
            'total_completed' => $query->count(),
            'total_earnings' => $query->sum('delivery_fee') ?? 0,
            'avg_delivery_time' => $query->avg(DB::raw('TIMESTAMPDIFF(MINUTE, created_at, completed_at)')) ?? 0,
            'top_partners' => DeliveryPartner::withCount([
                'deliveryRequests' => function ($q) use ($period) {
                    $q->where('status', 'completed');
                    if ($period === 'today') {
                        $q->whereDate('completed_at', today());
                    } elseif ($period === 'week') {
                        $q->where('completed_at', '>=', now()->startOfWeek());
                    } elseif ($period === 'month') {
                        $q->where('completed_at', '>=', now()->startOfMonth());
                    }
                }
            ])
                ->orderByDesc('delivery_requests_count')
                ->limit(5)
                ->get(['id', 'name', 'phone', 'delivery_requests_count']),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Helper function: Find best available partner for an order
     */
    private function findBestAvailablePartner(Order $order)
    {
        return DeliveryPartner::where('is_online', true)
            ->where('is_available', true)
            ->where('status', 'approved')  // Only approved partners can receive orders
            ->withCount([
                'deliveryRequests' => function ($q) {
                    $q->whereIn('status', ['accepted', 'picked_up']);
                }
            ])
            ->orderBy('delivery_requests_count', 'asc')
            ->orderByDesc('rating')
            ->first();
    }

    /**
     * Helper function: Calculate completion rate
     */
    private function getCompletionRate($partnerId)
    {
        $total = DeliveryRequest::where('delivery_partner_id', $partnerId)->count();
        if ($total === 0) {
            return 0;
        }

        $completed = DeliveryRequest::where('delivery_partner_id', $partnerId)
            ->where('status', 'completed')
            ->count();

        return round(($completed / $total) * 100, 2);
    }
}
