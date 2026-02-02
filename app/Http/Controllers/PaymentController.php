<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\InfobipSmsService;
use App\Notifications\BuyerPurchaseConfirmation;
use App\Notifications\SellerNewOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class PaymentController extends Controller
{
    private $razorpayId;
    private $razorpayKey;

    public function __construct()
    {
        $this->razorpayId = config('services.razorpay.key');
        $this->razorpayKey = config('services.razorpay.secret');
    }

    public function createOrder(Request $request)
    {
        try {
            // Enhanced validation
            $request->validate([
                'address' => 'nullable|string|max:255',
                'new_address' => 'nullable|string|max:255',
                'address_type' => 'nullable|in:home,office,other',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'pincode' => ['required', 'string', 'max:10', 'regex:/^[0-9]{5,10}$/'],
            ]);

            // Check if user is authenticated
            if (!Auth::check()) {
                return response()->json(['error' => 'Please login to continue'], 401);
            }

            $items = CartItem::with('product')
                ->where('user_id', Auth::id())
                ->get();

            if ($items->isEmpty()) {
                return response()->json(['error' => 'Your cart is empty'], 400);
            }

            $totals = $this->calculateTotals($items);
            $address = $request->new_address ?: $request->address;

            // Wallet discount logic removed as per request
            $useWallet = false;
            $walletDiscount = 0;

            // Validate that we have a valid address
            if (empty($address)) {
                return response()->json(['error' => 'Please provide a delivery address'], 400);
            }

            // Save new address if provided
            if ($request->new_address) {
                try {
                    \App\Models\Address::create([
                        'user_id' => Auth::id(),
                        'address' => $address,
                        'city' => $request->city,
                        'state' => $request->state,
                        'pincode' => $request->pincode,
                        'type' => $request->address_type,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to save address: ' . $e->getMessage());
                    // Continue with payment even if address save fails
                }
            }

            // Validate Razorpay credentials
            if (empty($this->razorpayId) || empty($this->razorpayKey)) {
                Log::error('Razorpay credentials not configured', [
                    'key_present' => !empty($this->razorpayId),
                    'secret_present' => !empty($this->razorpayKey),
                    'key_value' => $this->razorpayId ? substr($this->razorpayId, 0, 10) . '...' : 'null',
                    'config_key' => config('services.razorpay.key') ? 'loaded' : 'null',
                    'env_key' => env('RAZORPAY_KEY_ID') ? 'set' : 'not set'
                ]);
                return response()->json(['error' => 'Payment system not configured. Please contact support.'], 500);
            }

            // Create Razorpay order with error handling
            $api = new Api($this->razorpayId, $this->razorpayKey);

            $orderData = [
                'receipt' => 'order_' . uniqid() . '_' . Auth::id(),
                'amount' => (int) ($totals['total'] * 100), // Convert to paise and ensure integer
                'currency' => 'INR',
                'payment_capture' => 1
            ];

            Log::info('Creating Razorpay order', [
                'user_id' => Auth::id(),
                'amount' => $orderData['amount'],
                'receipt' => $orderData['receipt']
            ]);

            $razorpayOrder = $api->order->create($orderData);

            if (!$razorpayOrder || !isset($razorpayOrder['id'])) {
                Log::error('Failed to create Razorpay order', ['response' => $razorpayOrder]);
                return response()->json(['error' => 'Failed to initialize payment. Please try again.'], 500);
            }

            // Store order details in session for later use
            session([
                'checkout_data' => [
                    'address' => $address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'pincode' => $request->pincode,
                    'items' => $items->toArray(),
                    'totals' => $totals,
                    'razorpay_order_id' => $razorpayOrder['id'],
                    'use_wallet' => $useWallet,
                    'wallet_discount' => $walletDiscount
                ]
            ]);

            Log::info('Razorpay order created successfully', [
                'order_id' => $razorpayOrder['id'],
                'amount' => $orderData['amount'],
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'order_id' => $razorpayOrder['id'],
                'amount' => $orderData['amount'],
                'currency' => 'INR',
                'name' => config('app.name', 'GrabBaskets'),
                'description' => 'Payment for order - ' . count($items) . ' items',
                'key' => $this->razorpayId, // Add Razorpay key to response
                'prefill' => [
                    'name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                    'contact' => Auth::user()->phone ?? '',
                ]
            ]);
        } catch (\Razorpay\Api\Errors\Error $e) {
            Log::error('Razorpay API Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'error_code' => $e->getCode()
            ]);
            return response()->json(['error' => 'Payment initialization failed. Please try again or contact support.'], 500);
        } catch (\Exception $e) {
            Log::error('Payment creation error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'An error occurred while processing your request. Please try again.'], 500);
        }
    }

    public function verifyPayment(Request $request)
    {
        try {
            // Check if user is authenticated
            if (!Auth::check()) {
                Log::warning('Unauthenticated payment verification attempt');
                return response()->json(['error' => 'Please login to continue'], 401);
            }

            // Get checkout data from session
            $checkoutData = session('checkout_data');

            if (!$checkoutData) {
                Log::warning('Checkout session expired or missing', [
                    'user_id' => Auth::id(),
                    'session_id' => session()->getId(),
                    'has_session' => session()->has('checkout_data')
                ]);
                return response()->json([
                    'error' => 'Your checkout session has expired. Please go back to your cart and try again.',
                    'redirect' => route('cart.index')
                ], 400);
            }

            // Validate required session data
            if (!isset($checkoutData['items']) || empty($checkoutData['items'])) {
                Log::error('Checkout data missing items', ['user_id' => Auth::id()]);
                return response()->json([
                    'error' => 'Cart items missing. Please try again.',
                    'redirect' => route('cart.index')
                ], 400);
            }

            // Validate payment input
            $request->validate([
                'razorpay_payment_id' => 'required',
                'razorpay_order_id' => 'required',
                'razorpay_signature' => 'required',
            ]);

            // Stock validation before payment processing
            foreach ($checkoutData['items'] as $itemData) {
                $item = (object) $itemData;
                $product = Product::find($item->product_id);
                $qty = $item->quantity ?? 1;

                if (!$product) {
                    return response()->json([
                        'error' => "Product no longer available. Please update your cart."
                    ], 400);
                }

                if ($product->stock < $qty) {
                    return response()->json([
                        'error' => "Product '{$product->name}' is out of stock. Please update your cart."
                    ], 400);
                }
            }

            $api = new Api($this->razorpayId, $this->razorpayKey);

            // Verify payment signature
            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature
            ];

            Log::info('Verifying payment signature', [
                'user_id' => Auth::id(),
                'order_id' => $request->razorpay_order_id,
                'payment_id' => $request->razorpay_payment_id
            ]);

            $api->utility->verifyPaymentSignature($attributes);

            Log::info('Payment signature verified successfully', ['user_id' => Auth::id()]);

            // Payment verified, create orders
            $orders = [];
            foreach ($checkoutData['items'] as $itemData) {
                $item = (object) $itemData;
                $amount = $this->lineAmountFromData($item);

                $order = Order::create([
                    'product_id' => $item->product_id,
                    'seller_id' => $item->seller_id,
                    'buyer_id' => Auth::id(),
                    'amount' => $amount,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_reference' => $request->razorpay_payment_id,
                    'delivery_address' => $checkoutData['address'],
                    'delivery_city' => $checkoutData['city'],
                    'delivery_state' => $checkoutData['state'],
                    'delivery_pincode' => $checkoutData['pincode'],
                    'payment_method' => 'razorpay',
                ]);

                $orders[] = $order;

                // Create notification for seller
                $product = Product::find($item->product_id);
                // Decrease product stock
                if ($product && $product->stock > 0) {
                    $product->stock = max(0, $product->stock - ($item->quantity ?? 1));
                    $product->save();
                }
                $seller = User::find($item->seller_id);

                if ($seller) {
                    // Use NotificationService for Amazon-like notifications
                    NotificationService::sendNewOrderToSeller($seller, $order);

                    // Send email to seller
                    $this->sendOrderNotificationEmail($seller, $order, $product, 'seller');

                    // Send SMS notification to seller via Twilio
                    $smsService = new \App\Services\SmsService();
                    $smsResult = $smsService->sendOrderConfirmationToSeller($seller, $order);
                    if ($smsResult['success']) {
                        Log::info('SMS sent to seller via Twilio', ['seller_id' => $seller->id, 'order_id' => $order->id]);
                    } else {
                        Log::warning('Failed to send SMS to seller', ['seller_id' => $seller->id, 'error' => $smsResult['error'] ?? 'Unknown error']);
                    }
                }

                // Create notification for buyer using NotificationService
                NotificationService::sendOrderPlaced(Auth::user(), $order);
                NotificationService::sendPaymentConfirmed(Auth::user(), $order);
            }

            // Send email to buyer
            $this->sendOrderNotificationEmail(Auth::user(), $orders[0], null, 'buyer', $orders);

            // Send SMS payment confirmation to buyer via Twilio
            $smsService = new \App\Services\SmsService();
            $buyerSmsResult = $smsService->sendPaymentConfirmationToBuyer(Auth::user(), $orders[0]);
            if ($buyerSmsResult['success']) {
                Log::info('Payment confirmation SMS sent to buyer via Twilio', ['buyer_id' => Auth::id(), 'order_count' => count($orders)]);
            } else {
                Log::warning('Failed to send payment confirmation SMS to buyer', ['buyer_id' => Auth::id(), 'error' => $buyerSmsResult['error'] ?? 'Unknown error']);
            }

            // Send SMS notification to admin numbers for each order
            foreach ($orders as $order) {
                $seller = User::find($order->seller_id);
                $adminSmsResult = $smsService->sendNewOrderNotificationToAdmins($order, Auth::user(), $seller);
                if ($adminSmsResult['success']) {
                    Log::info('Order notification SMS sent to admins', [
                        'order_id' => $order->id,
                        'successful_sends' => count(array_filter($adminSmsResult['results'], fn($r) => $r['success']))
                    ]);
                }
            }

            // Wallet point logic removed as per request (Previously handles points for purchases > 2000)

            // Clear cart and session
            CartItem::where('user_id', Auth::id())->delete();
            session()->forget('checkout_data');

            Log::info('Orders created successfully', [
                'user_id' => Auth::id(),
                'order_count' => count($orders),
                'payment_id' => $request->razorpay_payment_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment successful! Your orders have been placed.',
                'redirect' => route('orders.track')
            ]);
        } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
            Log::error('Payment signature verification failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'payment_id' => $request->razorpay_payment_id ?? 'N/A'
            ]);
            return response()->json([
                'error' => 'Payment verification failed. Your payment may not have been processed correctly. Please contact support with your payment details.'
            ], 400);
        } catch (\Exception $e) {
            Log::error('Payment verification error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'An error occurred while processing your payment. Please contact support.'
            ], 500);
        }
    }

    private function calculateTotals($items)
    {
        $subtotal = 0;
        $discountTotal = 0;
        $deliveryTotal = 0;

        foreach ($items as $item) {
            $price = (float) $item->price;
            $discPct = (float) $item->discount;
            $qty = (int) $item->quantity;
            $delivery = (float) $item->delivery_charge;

            $lineBase = $price * $qty;
            $lineDisc = $discPct > 0 ? ($lineBase * ($discPct / 100)) : 0;
            $subtotal += $lineBase;
            $discountTotal += $lineDisc;
            $deliveryTotal += $delivery;
        }

        $total = $subtotal - $discountTotal + $deliveryTotal;
        return compact('subtotal', 'discountTotal', 'deliveryTotal', 'total');
    }

    private function lineAmountFromData($item): float
    {
        $price = (float) $item->price;
        $discPct = (float) $item->discount;
        $qty = (int) $item->quantity;
        $delivery = (float) $item->delivery_charge;

        $lineBase = $price * $qty;
        $lineDisc = $discPct > 0 ? ($lineBase * ($discPct / 100)) : 0;
        return $lineBase - $lineDisc + $delivery;
    }

    private function sendOrderNotificationEmail($user, $order, $product, $type, $allOrders = null)
    {
        try {
            if ($type === 'seller') {
                // Send seller new order notification
                $user->notify(new SellerNewOrder($order, $product));
                Log::info('Seller new order notification sent', [
                    'seller_id' => $user->id,
                    'order_id' => $order->id
                ]);
            } else {
                // Send buyer purchase confirmation
                $user->notify(new BuyerPurchaseConfirmation($order, $product, $allOrders));
                Log::info('Buyer purchase confirmation sent', [
                    'buyer_id' => $user->id,
                    'order_id' => $order->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send order notification: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    private $razorpayKeyId = 'rzp_live_RZLX30zmmnhHum';
    private $razorpayKeySecret = 'XKmsdH5PbR49EiT74CgehYYi';

    // Show payment button
    public function showButton()
    {
        return view('razorpay-button');
    }

    // Create Razorpay order
    public function createOrder1(Request $request)
    {
        $api = new Api($this->razorpayKeyId, $this->razorpayKeySecret);

        $amountInPaise = $request->amount * 100; // convert rupees to paise

        $order = $api->order->create([
            'receipt' => 'order_rcptid_' . rand(1000, 9999),
            'amount' => $amountInPaise,
            'currency' => 'INR',
        ]);

        return response()->json([
            'order_id' => $order['id'],
            'key' => $this->razorpayKeyId,
            'amount' => $amountInPaise,

            'customer' => [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ]
        ]);
    }



    // Payment success
    public function paymentSuccess(Request $request)
    {
        return "Payment Success! Payment ID: " . $request->razorpay_payment_id;
    }
}
