<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class InfobipSmsService
{
    private $apiKey;
    private $baseUrl;
    private $sender;

    public function __construct()
    {
        $this->apiKey = config('services.infobip.api_key');
        $this->baseUrl = config('services.infobip.base_url');
        $this->sender = config('services.infobip.sender');
    }

    /**
     * Send SMS to a single recipient
     */
    public function sendSms($phoneNumber, $message)
    {
        try {
            // Format phone number (ensure it starts with country code)
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            $response = Http::withHeaders([
                'Authorization' => 'App ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($this->baseUrl . '/sms/2/text/advanced', [
                'messages' => [
                    [
                        'from' => $this->sender,
                        'destinations' => [
                            ['to' => $formattedPhone]
                        ],
                        'text' => $message
                    ]
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                
                // Check if message was accepted but might be in demo mode
                $messageStatus = $result['messages'][0]['status']['name'] ?? '';
                $isDemo = $messageStatus === 'PENDING_ACCEPTED';
                
                Log::info('SMS sent to API', [
                    'phone' => $formattedPhone,
                    'message_id' => $result['messages'][0]['messageId'] ?? null,
                    'status' => $messageStatus,
                    'demo_mode_detected' => $isDemo
                ]);
                
                return [
                    'success' => true,
                    'message_id' => $result['messages'][0]['messageId'] ?? null,
                    'status' => $messageStatus,
                    'demo_warning' => $isDemo,
                    'data' => $result
                ];
            } else {
                Log::error('SMS sending failed', [
                    'phone' => $formattedPhone,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return [
                    'success' => false,
                    'error' => 'Failed to send SMS: ' . $response->body()
                ];
            }
        } catch (Exception $e) {
            Log::error('SMS service error', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if account is in demo mode
     */
    public function isAccountInDemoMode()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'App ' . $this->apiKey,
                'Accept' => 'application/json'
            ])->get($this->baseUrl . '/account/1/balance');

            if ($response->successful()) {
                $balance = $response->json();
                return $balance['balance'] == 0;
            }
            
            return true; // Assume demo mode if we can't check
        } catch (\Exception $e) {
            return true; // Assume demo mode on error
        }
    }

    /**
     * Get account status and demo information
     */
    public function getAccountStatus()
    {
        try {
            $balanceResponse = Http::withHeaders([
                'Authorization' => 'App ' . $this->apiKey,
                'Accept' => 'application/json'
            ])->get($this->baseUrl . '/account/1/balance');

            $isDemo = true;
            $balance = 0;
            $currency = 'USD';
            
            if ($balanceResponse->successful()) {
                $balanceData = $balanceResponse->json();
                $balance = $balanceData['balance'] ?? 0;
                $currency = $balanceData['currency'] ?? 'USD';
                $isDemo = $balance == 0;
            }

            return [
                'is_demo' => $isDemo,
                'balance' => $balance,
                'currency' => $currency,
                'demo_instructions' => $isDemo ? $this->getDemoInstructions() : null
            ];
        } catch (\Exception $e) {
            return [
                'is_demo' => true,
                'balance' => 0,
                'currency' => 'USD',
                'error' => $e->getMessage(),
                'demo_instructions' => $this->getDemoInstructions()
            ];
        }
    }

    /**
     * Get instructions for demo mode
     */
    private function getDemoInstructions()
    {
        return [
            'title' => 'Infobip Demo Mode Active',
            'message' => 'Your account is in demo mode. SMS will only be delivered to whitelisted numbers.',
            'steps' => [
                '1. Login to your Infobip portal at https://portal.infobip.com',
                '2. Navigate to SMS → Demo numbers or Channels → SMS',
                '3. Add phone numbers to the whitelist (format: +917010299714)',
                '4. OR add credits to your account for unlimited SMS',
                '5. Contact Infobip support if you need assistance'
            ],
            'phone_format' => 'Use international format: +917010299714',
            'support_url' => 'https://www.infobip.com/contact',
            'portal_url' => 'https://portal.infobip.com'
        ];
    }

    /**
     * Send bulk SMS to multiple recipients
     */
    public function sendBulkSms($recipients, $message)
    {
        try {
            $destinations = [];
            foreach ($recipients as $recipient) {
                $destinations[] = ['to' => $this->formatPhoneNumber($recipient)];
            }

            $response = Http::withHeaders([
                'Authorization' => 'App ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($this->baseUrl . '/sms/2/text/advanced', [
                'messages' => [
                    [
                        'from' => $this->sender,
                        'destinations' => $destinations,
                        'text' => $message
                    ]
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Bulk SMS sent successfully', [
                    'recipients_count' => count($recipients),
                    'response' => $result
                ]);
                return [
                    'success' => true,
                    'data' => $result
                ];
            } else {
                Log::error('Bulk SMS sending failed', [
                    'recipients_count' => count($recipients),
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return [
                    'success' => false,
                    'error' => 'Failed to send bulk SMS: ' . $response->body()
                ];
            }
        } catch (Exception $e) {
            Log::error('Bulk SMS service error', [
                'recipients_count' => count($recipients),
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send payment confirmation SMS to buyer
     */
    public function sendPaymentConfirmationToBuyer($buyer, $order)
    {
        $message = "🎉 Payment Confirmed! Hi {$buyer->name}, your payment of ₹{$order->amount} for Order #{$order->id} has been received. Your order is being processed. Track: " . route('orders.show', $order->id) . " - GrabBasket";
        
        return $this->sendSms($buyer->phone, $message);
    }

    /**
     * Send order confirmation SMS to seller
     */
    public function sendOrderConfirmationToSeller($seller, $order)
    {
        $message = "💰 New Order Alert! Hi {$seller->name}, you received a new order #{$order->id} worth ₹{$order->amount}. Product: {$order->product->name}. Please process it soon. Dashboard: " . route('seller.orders') . " - GrabBasket";
        
        return $this->sendSms($seller->phone, $message);
    }

    /**
     * Send shipping notification to buyer
     */
    public function sendShippingNotificationToBuyer($buyer, $order)
    {
        $trackingInfo = $order->tracking_number ? "Tracking: {$order->tracking_number} via {$order->courier_name}" : "Tracking info will be updated soon";
        $message = "📦 Order Shipped! Hi {$buyer->name}, your order #{$order->id} has been shipped. {$trackingInfo}. Track: " . route('tracking.form') . " - GrabBasket";
        
        return $this->sendSms($buyer->phone, $message);
    }

    /**
     * Send delivery confirmation to buyer
     */
    public function sendDeliveryConfirmationToBuyer($buyer, $order)
    {
        $message = "✅ Order Delivered! Hi {$buyer->name}, your order #{$order->id} has been delivered successfully. Thank you for shopping with us! Rate your experience: " . route('orders.show', $order->id) . " - GrabBasket";
        
        return $this->sendSms($buyer->phone, $message);
    }

    /**
     * Send low stock alert to seller
     */
    public function sendLowStockAlert($seller, $product)
    {
        $message = "⚠️ Low Stock Alert! Hi {$seller->name}, your product '{$product->name}' is running low on stock (Qty: {$product->stock}). Please restock soon. Dashboard: " . route('seller.dashboard') . " - GrabBasket";
        
        return $this->sendSms($seller->phone, $message);
    }

    /**
     * Send promotional SMS to customers
     */
    public function sendPromotionalSms($customers, $promotionMessage)
    {
        $phones = [];
        foreach ($customers as $customer) {
            if ($customer->phone) {
                $phones[] = $customer->phone;
            }
        }
        
        $message = "🎁 {$promotionMessage} Shop now: " . url('/') . " - GrabBasket. Reply STOP to unsubscribe.";
        
        return $this->sendBulkSms($phones, $message);
    }

    /**
     * Send OTP SMS
     */
    public function sendOtp($phoneNumber, $otp)
    {
        $message = "🔐 Your GrabBasket verification code is: {$otp}. Valid for 10 minutes. Don't share this code with anyone. - GrabBasket";
        
        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Format phone number to international format
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Remove any spaces, dashes, or special characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If it starts with 91, it's already in international format
        if (substr($phone, 0, 2) === '91') {
            return $phone;
        }
        
        // If it starts with 0, remove it and add 91
        if (substr($phone, 0, 1) === '0') {
            return '91' . substr($phone, 1);
        }
        
        // If it's 10 digits, add 91 prefix (Indian numbers)
        if (strlen($phone) === 10) {
            return '91' . $phone;
        }
        
        // Return as is if already in proper format
        return $phone;
    }

    /**
     * Get SMS delivery report
     */
    public function getDeliveryReport($messageId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'App ' . $this->apiKey,
                'Accept' => 'application/json'
            ])->get($this->baseUrl . '/sms/1/reports');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to get delivery report'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}