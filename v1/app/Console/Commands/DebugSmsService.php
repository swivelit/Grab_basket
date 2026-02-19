<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InfobipSmsService;
use Illuminate\Support\Facades\Http;

class DebugSmsService extends Command
{
    protected $signature = 'sms:debug {phone}';
    protected $description = 'Debug SMS service with detailed logging';

    public function handle()
    {
        $phone = $this->argument('phone');
        
        $this->info('🔍 Debugging SMS Service...');
        $this->info('Phone: ' . $phone);
        
        // Check configuration
        $apiKey = config('services.infobip.api_key');
        $baseUrl = config('services.infobip.base_url');
        $sender = config('services.infobip.sender');
        
        $this->info('📋 Configuration:');
        $this->info('API Key: ' . substr($apiKey, 0, 10) . '...');
        $this->info('Base URL: ' . $baseUrl);
        $this->info('Sender: ' . $sender);
        
        // Format phone number
        $formattedPhone = $this->formatPhoneNumber($phone);
        $this->info('Formatted Phone: ' . $formattedPhone);
        
        // Test API connectivity
        $this->info('🌐 Testing API connectivity...');
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'App ' . $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->timeout(30)->post($baseUrl . '/sms/2/text/advanced', [
                'messages' => [
                    [
                        'from' => $sender,
                        'destinations' => [
                            ['to' => $formattedPhone]
                        ],
                        'text' => '🔧 Debug SMS Test from GrabBasket! If you receive this, SMS is working. Time: ' . now()->format('H:i:s')
                    ]
                ]
            ]);
            
            $this->info('📡 Response Status: ' . $response->status());
            $this->info('📄 Response Headers: ' . json_encode($response->headers(), JSON_PRETTY_PRINT));
            $this->info('📦 Response Body: ' . $response->body());
            
            if ($response->successful()) {
                $result = $response->json();
                $this->info('✅ SMS API call successful!');
                
                if (isset($result['messages'][0]['messageId'])) {
                    $messageId = $result['messages'][0]['messageId'];
                    $this->info('📱 Message ID: ' . $messageId);
                    
                    // Check message status
                    $this->info('🔍 Checking message status...');
                    sleep(2); // Wait a moment
                    
                    $statusResponse = Http::withHeaders([
                        'Authorization' => 'App ' . $apiKey,
                        'Accept' => 'application/json'
                    ])->get($baseUrl . '/sms/1/reports');
                    
                    if ($statusResponse->successful()) {
                        $this->info('📊 Status Response: ' . $statusResponse->body());
                    }
                }
                
            } else {
                $this->error('❌ SMS API call failed!');
                $this->error('Error: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->error('💥 Exception occurred: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
        }
        
        // Test with different base URLs
        $this->info('🔄 Testing alternative base URLs...');
        
        $alternativeUrls = [
            'https://api.infobip.com',
            'https://g93q8r.api.infobip.com',
            'https://gxv7l.api.infobip.com'
        ];
        
        foreach ($alternativeUrls as $url) {
            $this->info("Testing URL: {$url}");
            
            try {
                $testResponse = Http::withHeaders([
                    'Authorization' => 'App ' . $apiKey,
                    'Accept' => 'application/json'
                ])->timeout(10)->get($url . '/account/1/balance');
                
                $this->info("✅ {$url} - Status: " . $testResponse->status());
                
                if ($testResponse->successful()) {
                    $this->info("💰 Account info: " . $testResponse->body());
                }
                
            } catch (\Exception $e) {
                $this->error("❌ {$url} - Error: " . $e->getMessage());
            }
        }
    }
    
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
}