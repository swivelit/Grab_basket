<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ManageInfobipWhitelist extends Command
{
    protected $signature = 'sms:whitelist {action} {phone?}';
    protected $description = 'Manage Infobip SMS demo whitelist';

    public function handle()
    {
        $action = $this->argument('action');
        $phone = $this->argument('phone');
        
        $apiKey = config('services.infobip.api_key');
        $baseUrl = config('services.infobip.base_url');
        
        switch ($action) {
            case 'list':
                $this->listWhitelistedNumbers();
                break;
                
            case 'add':
                if (!$phone) {
                    $phone = $this->ask('Enter phone number to add to whitelist (format: 917010299714)');
                }
                $this->addToWhitelist($phone);
                break;
                
            case 'info':
                $this->showAccountInfo();
                break;
                
            default:
                $this->error('Invalid action. Use: list, add, or info');
                $this->info('Examples:');
                $this->info('php artisan sms:whitelist list');
                $this->info('php artisan sms:whitelist add 917010299714');
                $this->info('php artisan sms:whitelist info');
        }
    }
    
    private function listWhitelistedNumbers()
    {
        $this->info('📋 Fetching SMS demo whitelist...');
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'App ' . config('services.infobip.api_key'),
                'Accept' => 'application/json'
            ])->get(config('services.infobip.base_url') . '/sms/1/destinations');
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info('✅ Whitelisted numbers:');
                
                if (isset($data['destinations']) && count($data['destinations']) > 0) {
                    foreach ($data['destinations'] as $destination) {
                        $this->info('📱 ' . $destination['to']);
                    }
                } else {
                    $this->warn('⚠️  No numbers in whitelist');
                }
            } else {
                $this->error('❌ Failed to fetch whitelist: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->error('💥 Error: ' . $e->getMessage());
        }
    }
    
    private function addToWhitelist($phone)
    {
        $formattedPhone = $this->formatPhoneNumber($phone);
        $this->info("📱 Adding {$formattedPhone} to SMS demo whitelist...");
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'App ' . config('services.infobip.api_key'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post(config('services.infobip.base_url') . '/sms/1/destinations', [
                'destinations' => [
                    ['to' => $formattedPhone]
                ]
            ]);
            
            if ($response->successful()) {
                $this->info('✅ Successfully added to whitelist!');
                $this->info('📦 Response: ' . $response->body());
                
                // Send test SMS
                $this->info('📨 Sending test SMS...');
                $this->sendTestSms($formattedPhone);
                
            } else {
                $this->error('❌ Failed to add to whitelist: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->error('💥 Error: ' . $e->getMessage());
        }
    }
    
    private function sendTestSms($phone)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'App ' . config('services.infobip.api_key'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post(config('services.infobip.base_url') . '/sms/2/text/advanced', [
                'messages' => [
                    [
                        'from' => config('services.infobip.sender'),
                        'destinations' => [
                            ['to' => $phone]
                        ],
                        'text' => '🎉 Success! Your number is now whitelisted for GrabBasket SMS notifications. Welcome!'
                    ]
                ]
            ]);
            
            if ($response->successful()) {
                $this->info('✅ Test SMS sent successfully!');
            } else {
                $this->error('❌ Test SMS failed: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->error('💥 Test SMS error: ' . $e->getMessage());
        }
    }
    
    private function showAccountInfo()
    {
        $this->info('🏢 Fetching Infobip account information...');
        
        try {
            // Account balance
            $balanceResponse = Http::withHeaders([
                'Authorization' => 'App ' . config('services.infobip.api_key'),
                'Accept' => 'application/json'
            ])->get(config('services.infobip.base_url') . '/account/1/balance');
            
            if ($balanceResponse->successful()) {
                $balance = $balanceResponse->json();
                $this->info('💰 Balance: ' . $balance['balance'] . ' ' . $balance['currency']);
            }
            
            // Account profile
            $profileResponse = Http::withHeaders([
                'Authorization' => 'App ' . config('services.infobip.api_key'),
                'Accept' => 'application/json'
            ])->get(config('services.infobip.base_url') . '/account/1/profile');
            
            if ($profileResponse->successful()) {
                $profile = $profileResponse->json();
                $this->info('📋 Account Type: ' . ($profile['accountType'] ?? 'Unknown'));
                $this->info('🏢 Company: ' . ($profile['companyName'] ?? 'Unknown'));
            }
            
            // Check demo status
            $this->info('🔍 Demo Account Status:');
            $this->warn('⚠️  Your account appears to be in DEMO mode');
            $this->warn('📱 Only whitelisted numbers can receive SMS');
            $this->info('💡 To send to any number, upgrade to a paid plan');
            
        } catch (\Exception $e) {
            $this->error('💥 Error: ' . $e->getMessage());
        }
    }
    
    private function formatPhoneNumber($phoneNumber)
    {
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        if (substr($phone, 0, 2) === '91') {
            return $phone;
        }
        
        if (substr($phone, 0, 1) === '0') {
            return '91' . substr($phone, 1);
        }
        
        if (strlen($phone) === 10) {
            return '91' . $phone;
        }
        
        return $phone;
    }
}