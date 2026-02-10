<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InfobipSmsService;
use App\Models\User;

class TestSmsWithDemoMode extends Command
{
    protected $signature = 'sms:test-demo';
    protected $description = 'Test SMS with demo mode detection and instructions';

    public function handle()
    {
        $this->info('🔍 Testing SMS Integration with Demo Mode Detection...');
        $this->newLine();

        $smsService = new InfobipSmsService();
        
        // Check account status
        $this->info('📊 Checking Infobip Account Status...');
        $accountStatus = $smsService->getAccountStatus();
        
        $this->table(
            ['Property', 'Value'],
            [
                ['Demo Mode', $accountStatus['is_demo'] ? '⚠️ YES' : '✅ NO'],
                ['Balance', '$' . $accountStatus['balance'] . ' ' . $accountStatus['currency']],
                ['Account Type', $accountStatus['is_demo'] ? 'Demo/Trial' : 'Paid']
            ]
        );

        if ($accountStatus['is_demo']) {
            $this->newLine();
            $this->warn('⚠️ DEMO MODE DETECTED');
            $this->warn('SMS will only be delivered to whitelisted numbers.');
            $this->newLine();
            
            $instructions = $accountStatus['demo_instructions'];
            $this->info('📋 ' . $instructions['title']);
            $this->info($instructions['message']);
            $this->newLine();
            
            $this->info('🔧 Setup Instructions:');
            foreach ($instructions['steps'] as $step) {
                $this->info('   ' . $step);
            }
            $this->newLine();
            
            $this->info('📱 Phone Format: ' . $instructions['phone_format']);
            $this->info('🌐 Portal: ' . $instructions['portal_url']);
            $this->info('📞 Support: ' . $instructions['support_url']);
            $this->newLine();
        }

        // Test with current sellers
        $this->info('🧪 Testing SMS with Current Sellers...');
        $sellers = User::whereHas('products')->whereNotNull('phone')->get(['name', 'phone']);
        
        if ($sellers->isEmpty()) {
            $this->warn('No sellers found with phone numbers.');
            return;
        }

        $this->info("Found {$sellers->count()} seller(s) with phone numbers:");
        $this->newLine();

        $results = [];
        foreach ($sellers as $seller) {
            $this->info("📱 Testing SMS to {$seller->name} ({$seller->phone})...");
            
            $testMessage = "🎯 DEMO TEST from GrabBasket!\n\n";
            $testMessage .= "Hi {$seller->name}! This is a test SMS.\n";
            $testMessage .= "Time: " . now()->format('H:i:s') . "\n\n";
            
            if ($accountStatus['is_demo']) {
                $testMessage .= "⚠️ Note: If you received this message, your number is whitelisted in Infobip demo mode.\n\n";
            }
            
            $testMessage .= "✅ SMS system is working!";

            $result = $smsService->sendSms($seller->phone, $testMessage);
            
            $status = '❌ Failed';
            $details = '';
            
            if ($result['success']) {
                $messageStatus = $result['status'] ?? 'Unknown';
                
                if ($result['demo_warning'] ?? false) {
                    $status = '⚠️ API Success (Demo Mode)';
                    $details = 'Message sent to API but may not be delivered (demo account)';
                } else {
                    $status = '✅ Success';
                    $details = 'Message sent successfully';
                }
                
                $details .= " | Status: {$messageStatus}";
                if (isset($result['message_id'])) {
                    $details .= " | ID: " . substr($result['message_id'], 0, 10) . '...';
                }
            } else {
                $details = $result['error'] ?? 'Unknown error';
            }

            $results[] = [
                'Seller' => $seller->name,
                'Phone' => $seller->phone,
                'Status' => $status,
                'Details' => $details
            ];
            
            $this->info("   Result: {$status}");
            $this->info("   Details: {$details}");
            $this->newLine();
        }

        // Summary table
        $this->info('📊 Test Results Summary:');
        $this->table(['Seller', 'Phone', 'Status', 'Details'], $results);

        // Final instructions
        $this->newLine();
        if ($accountStatus['is_demo']) {
            $this->warn('🔔 IMPORTANT: Messages may appear successful in API but not be delivered.');
            $this->warn('To receive actual SMS:');
            $this->warn('1. Add recipient numbers to Infobip whitelist at https://portal.infobip.com');
            $this->warn('2. OR add credits to your account for unlimited SMS delivery');
            $this->warn('3. Check delivery reports in Infobip portal for actual delivery status');
        } else {
            $this->info('🎉 Your account has credits! SMS should be delivered normally.');
        }
        
        $this->newLine();
        $this->info('💡 Pro Tip: Check your phone now to see if the test message was received!');
    }
}