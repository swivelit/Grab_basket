<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestInfobipDirectApi extends Command
{
    protected $signature = 'sms:test-direct-api {phone}';
    protected $description = 'Test Infobip API directly with different configurations';

    public function handle()
    {
        $phone = $this->argument('phone');
        $this->info("🔍 Testing Infobip Direct API with phone: {$phone}");
        $this->newLine();

        $apiKey = config('services.infobip.api_key');
        
        // Test with different base URLs and configurations
        $configs = [
            [
                'name' => 'Current Config (g93q8r subdomain)',
                'base_url' => 'https://g93q8r.api.infobip.com',
                'sender' => 'GrabBasket'
            ],
            [
                'name' => 'Global API (api.infobip.com)',
                'base_url' => 'https://api.infobip.com',
                'sender' => 'GrabBasket'
            ],
            [
                'name' => 'Test with Numeric Sender',
                'base_url' => 'https://api.infobip.com',
                'sender' => '447491163443'
            ]
        ];

        foreach ($configs as $config) {
            $this->info("📡 Testing: " . $config['name']);
            $this->info("URL: " . $config['base_url']);
            $this->info("Sender: " . $config['sender']);
            
            $formattedPhone = $this->formatPhoneNumber($phone);
            $this->info("Formatted Phone: {$formattedPhone}");
            
            $message = "🎯 Direct API Test from GrabBasket!\n\n";
            $message .= "Testing with " . $config['name'] . "\n";
            $message .= "Phone: {$formattedPhone}\n";
            $message .= "Time: " . now()->format('H:i:s') . "\n\n";
            $message .= "If you receive this, the configuration is working!";

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'App ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])->timeout(30)->post($config['base_url'] . '/sms/2/text/advanced', [
                    'messages' => [
                        [
                            'destinations' => [
                                ['to' => $formattedPhone]
                            ],
                            'from' => $config['sender'],
                            'text' => $message
                        ]
                    ]
                ]);

                $this->info("Status Code: " . $response->status());
                
                if ($response->successful()) {
                    $result = $response->json();
                    $this->info("✅ SUCCESS!");
                    
                    if (isset($result['messages'][0])) {
                        $msg = $result['messages'][0];
                        $this->info("Message ID: " . ($msg['messageId'] ?? 'N/A'));
                        $this->info("Status: " . ($msg['status']['name'] ?? 'Unknown'));
                        $this->info("Description: " . ($msg['status']['description'] ?? 'No description'));
                        
                        // Check if it's demo mode
                        if (($msg['status']['name'] ?? '') === 'PENDING_ACCEPTED') {
                            $this->warn("⚠️ DEMO MODE: Message accepted but may not be delivered");
                        }
                    }
                    
                    $this->info("Full Response: " . json_encode($result, JSON_PRETTY_PRINT));
                } else {
                    $this->error("❌ FAILED!");
                    $this->error("Response: " . $response->body());
                }

            } catch (\Exception $e) {
                $this->error("💥 Exception: " . $e->getMessage());
            }
            
            $this->newLine();
            $this->info(str_repeat('-', 60));
            $this->newLine();
        }

        // Test with the exact configuration from your PHP code
        $this->info("🔬 Testing with your exact PHP configuration...");
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'App ' . $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->timeout(30)->post('https://api.infobip.com/sms/2/text/advanced', [
                'messages' => [
                    [
                        'destinations' => [
                            ['to' => $this->formatPhoneNumber($phone)]
                        ],
                        'from' => '447491163443',
                        'text' => 'Congratulations on sending your first message. Go ahead and check the delivery report in the next step.'
                    ]
                ]
            ]);

            $this->info("Exact PHP Code Test:");
            $this->info("Status: " . $response->status());
            $this->info("Response: " . $response->body());

        } catch (\Exception $e) {
            $this->error("PHP Code Test Error: " . $e->getMessage());
        }

        $this->newLine();
        $this->info("🔔 Remember: If account is in demo mode, messages won't be delivered unless:");
        $this->info("1. Phone number is whitelisted in Infobip portal");
        $this->info("2. Account has credits added");
    }

    private function formatPhoneNumber($phoneNumber)
    {
        // Remove any spaces, dashes, or special characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If it already starts with country code, return as is
        if (strlen($phone) > 10) {
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
}