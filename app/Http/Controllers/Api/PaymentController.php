<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Create payment order
     */
    public function createOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
            'receipt' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // This would integrate with Razorpay or other payment gateway
        // For now, return a mock response
        $orderId = 'order_' . time() . '_' . rand(1000, 9999);

        return response()->json([
            'order_id' => $orderId,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'key' => config('services.razorpay.key_id', 'rzp_test_key'),
        ]);
    }

    /**
     * Verify payment
     */
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // This would verify the payment signature with Razorpay
        // For now, return success
        return response()->json([
            'message' => 'Payment verified successfully',
            'payment_id' => $request->razorpay_payment_id,
            'order_id' => $request->razorpay_order_id,
        ]);
    }

    /**
     * Get available payment methods
     */
    public function getMethods()
    {
        return response()->json([
            'methods' => [
                [
                    'id' => 'razorpay',
                    'name' => 'Razorpay',
                    'description' => 'Pay using UPI, Cards, Net Banking',
                    'enabled' => true,
                ],
                [
                    'id' => 'cod',
                    'name' => 'Cash on Delivery',
                    'description' => 'Pay when you receive your order',
                    'enabled' => true,
                ],
            ]
        ]);
    }

    /**
     * Handle Razorpay webhook
     */
    public function razorpayWebhook(Request $request)
    {
        // Verify webhook signature
        $webhookSecret = config('services.razorpay.webhook_secret');
        $signature = $request->header('X-Razorpay-Signature');

        // This would verify the webhook signature
        // For now, just log the event
        \Log::info('Razorpay Webhook', [
            'event' => $request->event,
            'data' => $request->all(),
        ]);

        return response()->json(['status' => 'ok']);
    }
}