<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InfobipSmsService;
use App\Models\User;

class SimulateSmsReceived extends Command
{
    protected $signature = 'sms:simulate-received';
    protected $description = 'Simulate what SMS messages would look like when received';

    public function handle()
    {
        $this->info('📱 Simulating SMS Messages (What you would receive)...');
        $this->newLine();

        $smsService = new InfobipSmsService();
        $seller = User::whereHas('products')->whereNotNull('phone')->first();
        
        if (!$seller) {
            $this->warn('No sellers found. Creating sample messages...');
            $seller = (object) ['name' => 'maha', 'phone' => '7010299714'];
        }

        // Simulate different types of SMS
        $messages = [
            [
                'type' => 'Payment Confirmation',
                'message' => $this->getPaymentConfirmationMessage($seller)
            ],
            [
                'type' => 'Order Notification',
                'message' => $this->getOrderNotificationMessage($seller)
            ],
            [
                'type' => 'Shipping Update',
                'message' => $this->getShippingUpdateMessage($seller)
            ],
            [
                'type' => 'Test Message',
                'message' => $this->getTestMessage($seller)
            ]
        ];

        foreach ($messages as $msg) {
            $this->info("📋 {$msg['type']}:");
            $this->info("📱 To: +91{$seller->phone}");
            $this->newLine();
            
            // Display message in a box
            $lines = explode("\n", $msg['message']);
            $maxLength = max(array_map('strlen', $lines));
            $boxWidth = min($maxLength + 4, 80);
            
            $this->info('┌' . str_repeat('─', $boxWidth - 2) . '┐');
            foreach ($lines as $line) {
                $padding = $boxWidth - strlen($line) - 3;
                $this->info('│ ' . $line . str_repeat(' ', $padding) . '│');
            }
            $this->info('└' . str_repeat('─', $boxWidth - 2) . '┘');
            $this->newLine();
        }

        $this->warn('💡 These are the messages that WOULD be delivered to your phone once you:');
        $this->warn('   1. Whitelist +917010299714 in Infobip portal, OR');
        $this->warn('   2. Add credits to your Infobip account');
        $this->newLine();
        
        $this->info('🔧 Quick Setup:');
        $this->info('   • Go to https://portal.infobip.com');
        $this->info('   • Login with your Infobip credentials');
        $this->info('   • Add +917010299714 to SMS whitelist');
        $this->info('   • Test with: php artisan sms:test-demo');
    }

    private function getPaymentConfirmationMessage($seller)
    {
        return "💰 Payment Received - GrabBasket\n\n" .
               "Hello {$seller->name}!\n\n" .
               "✅ Payment confirmed for order #GB-" . rand(1000, 9999) . "\n" .
               "Amount: ₹" . rand(100, 1000) . "\n" .
               "Product: Sample Product\n" .
               "Time: " . now()->format('d/m/Y H:i') . "\n\n" .
               "Thank you for your business!\n" .
               "- GrabBasket Team";
    }

    private function getOrderNotificationMessage($seller)
    {
        return "🛍️ New Order Alert - GrabBasket\n\n" .
               "Dear {$seller->name},\n\n" .
               "You have a new order!\n\n" .
               "Order #GB-" . rand(1000, 9999) . "\n" .
               "Customer: Sample Buyer\n" .
               "Product: Your Product Name\n" .
               "Amount: ₹" . rand(200, 800) . "\n\n" .
               "Please process this order promptly.\n" .
               "Login to your dashboard for details.";
    }

    private function getShippingUpdateMessage($seller)
    {
        return "🚚 Shipping Update - GrabBasket\n\n" .
               "Order #GB-" . rand(1000, 9999) . " has been shipped!\n\n" .
               "Tracking: " . strtoupper(substr(md5(rand()), 0, 12)) . "\n" .
               "Courier: BlueDart Express\n" .
               "Expected delivery: " . now()->addDays(3)->format('d/m/Y') . "\n\n" .
               "Track your package:\n" .
               "https://grabbasket.com/track\n\n" .
               "Thank you for shopping with us!";
    }

    private function getTestMessage($seller)
    {
        return "🎯 SMS Test - GrabBasket\n\n" .
               "Hi {$seller->name}!\n\n" .
               "This is a test message from your\n" .
               "GrabBasket SMS integration.\n\n" .
               "✅ If you received this, SMS is working!\n" .
               "Time: " . now()->format('H:i:s') . "\n\n" .
               "Your SMS notifications are now active! 🎉";
    }
}