<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\InfobipSmsService;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SmsController extends Controller
{
    private $smsService;

    public function __construct()
    {
        $this->smsService = new InfobipSmsService();
    }

    /**
     * Show SMS management dashboard
     */
    public function index()
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        $smsService = new InfobipSmsService();
        $accountStatus = $smsService->getAccountStatus();

        $stats = [
            'total_buyers' => User::whereHas('orders', function($query) {
                $query->where('buyer_id', '!=', null);
            })->whereNotNull('phone')->count(),
            'total_sellers' => User::whereHas('products')->whereNotNull('phone')->count(),
            'pending_orders' => Order::whereIn('status', ['paid', 'confirmed'])->count(),
            'shipped_orders' => Order::where('status', 'shipped')->count()
        ];

        return view('admin.sms-management', compact('stats', 'accountStatus'));
    }

    /**
     * Send bulk promotional SMS
     */
    public function sendBulkPromotion(Request $request)
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        $validator = Validator::make($request->all(), [
            'target_audience' => 'required|in:buyers,sellers,all',
            'message' => 'required|string|max:160',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $customers = collect();
            
            if ($request->target_audience === 'buyers' || $request->target_audience === 'all') {
                $buyers = User::whereHas('orders', function($query) {
                    $query->where('buyer_id', '!=', null);
                })->whereNotNull('phone')->get();
                $customers = $customers->merge($buyers);
            }
            
            if ($request->target_audience === 'sellers' || $request->target_audience === 'all') {
                $sellers = User::whereHas('products')->whereNotNull('phone')->get();
                $customers = $customers->merge($sellers);
            }

            if ($customers->isEmpty()) {
                return back()->with('error', 'No customers found with phone numbers.');
            }

            $result = $this->smsService->sendPromotionalSms($customers, $request->message);

            if ($result['success']) {
                Log::info('Bulk promotional SMS sent', [
                    'target_audience' => $request->target_audience,
                    'recipients_count' => $customers->count(),
                    'message' => $request->message
                ]);
                
                return back()->with('success', "Promotional SMS sent to {$customers->count()} customers successfully!");
            } else {
                return back()->with('error', 'Failed to send promotional SMS: ' . $result['error']);
            }
        } catch (\Exception $e) {
            Log::error('Bulk SMS error', ['error' => $e->getMessage()]);
            return back()->with('error', 'An error occurred while sending SMS: ' . $e->getMessage());
        }
    }

    /**
     * Send order reminder SMS
     */
    public function sendOrderReminders(Request $request)
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        try {
            // Get orders that are paid but not shipped for more than 2 days
            $pendingOrders = Order::with(['buyerUser', 'product'])
                ->whereIn('status', ['paid', 'confirmed'])
                ->where('created_at', '<', now()->subDays(2))
                ->get();

            $sentCount = 0;
            foreach ($pendingOrders as $order) {
                if ($order->buyerUser && $order->buyerUser->phone) {
                    $message = "📦 Order Update: Hi {$order->buyerUser->name}, your order #{$order->id} is being processed. We'll notify you once it ships. Track: " . route('orders.show', $order->id) . " - GrabBasket";
                    
                    $result = $this->smsService->sendSms($order->buyerUser->phone, $message);
                    if ($result['success']) {
                        $sentCount++;
                    }
                }
            }

            Log::info('Order reminder SMS sent', ['sent_count' => $sentCount, 'total_orders' => $pendingOrders->count()]);
            
            return back()->with('success', "Order reminder SMS sent to {$sentCount} customers.");
        } catch (\Exception $e) {
            Log::error('Order reminder SMS error', ['error' => $e->getMessage()]);
            return back()->with('error', 'An error occurred while sending order reminders: ' . $e->getMessage());
        }
    }

    /**
     * Test SMS configuration
     */
    public function testSms(Request $request)
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        $validator = Validator::make($request->all(), [
            'test_phone' => 'required|string|max:15',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $testMessage = "🧪 Test SMS from GrabBasket! Your SMS configuration is working correctly. Time: " . now()->format('Y-m-d H:i:s');
            
            $result = $this->smsService->sendSms($request->test_phone, $testMessage);

            if ($result['success']) {
                Log::info('Test SMS sent', ['phone' => $request->test_phone]);
                return back()->with('success', 'Test SMS sent successfully! Check your phone.');
            } else {
                return back()->with('error', 'Test SMS failed: ' . $result['error']);
            }
        } catch (\Exception $e) {
            Log::error('Test SMS error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Test SMS error: ' . $e->getMessage());
        }
    }

    /**
     * Test notifications with current sellers
     */
    public function testWithCurrentSellers(Request $request)
    {
        if (!session('is_admin')) {
            return redirect()->route('admin.login');
        }

        try {
            // Get sellers with phone numbers
            $sellers = User::whereHas('products')->whereNotNull('phone')->get();
            
            if ($sellers->isEmpty()) {
                return back()->with('error', 'No sellers found with phone numbers.');
            }

            $successCount = 0;
            $errorCount = 0;
            $results = [];

            foreach ($sellers as $seller) {
                // Create a mock order for testing
                $mockOrder = (object) [
                    'id' => 'TEST-' . rand(1000, 9999),
                    'amount' => rand(100, 1000),
                    'product' => (object) ['name' => 'Test Product for SMS']
                ];

                $testMessage = "🧪 SMS Test Alert! Hi {$seller->name}, this is a test notification from GrabBasket. Your SMS integration is working! You would receive order alerts like this. - GrabBasket";
                
                $result = $this->smsService->sendSms($seller->phone, $testMessage);
                
                if ($result['success']) {
                    $successCount++;
                    $results[] = "✅ {$seller->name} ({$seller->phone}) - Success";
                    Log::info('Test SMS sent to seller', ['seller_id' => $seller->id, 'phone' => $seller->phone]);
                } else {
                    $errorCount++;
                    $results[] = "❌ {$seller->name} ({$seller->phone}) - Failed: " . $result['error'];
                    Log::error('Test SMS failed for seller', ['seller_id' => $seller->id, 'error' => $result['error']]);
                }
            }

            $message = "SMS Test Completed!\n";
            $message .= "✅ Successful: {$successCount}\n";
            $message .= "❌ Failed: {$errorCount}\n\n";
            $message .= "Details:\n" . implode("\n", $results);

            return back()->with('success', $message);
            
        } catch (\Exception $e) {
            Log::error('Seller SMS test error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Test failed: ' . $e->getMessage());
        }
    }
}
