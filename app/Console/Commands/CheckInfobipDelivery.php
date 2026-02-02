<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckInfobipDelivery extends Command
{
    protected $signature = 'sms:check-delivery';
    protected $description = 'Check SMS delivery reports and account configuration';

    public function handle()
    {
        $this->info('🔍 Checking SMS Delivery Reports...');
        $this->newLine();

        $apiKey = config('services.infobip.api_key');
        $baseUrl = config('services.infobip.base_url');
        
        try {
            // Get delivery reports for recent messages
            $this->info('📊 Fetching recent delivery reports...');
            $response = Http::withHeaders([
                'Authorization' => 'App ' . $apiKey,
                'Accept' => 'application/json'
            ])->get($baseUrl . '/sms/1/reports', [
                'limit' => 10
            ]);

            if ($response->successful()) {
                $reports = $response->json();
                
                if (isset($reports['results']) && count($reports['results']) > 0) {
                    $this->info('📋 Recent SMS Delivery Reports:');
                    $this->newLine();
                    
                    foreach ($reports['results'] as $report) {
                        $phone = $report['to'] ?? 'Unknown';
                        $status = $report['status']['name'] ?? 'Unknown';
                        $description = $report['status']['description'] ?? 'No description';
                        $sentAt = $report['sentAt'] ?? 'Unknown';
                        $error = isset($report['error']) ? $report['error']['description'] : 'None';
                        
                        $this->info("📱 To: {$phone}");
                        $this->info("📅 Sent: {$sentAt}");
                        $this->info("📊 Status: {$status}");
                        $this->info("📝 Description: {$description}");
                        if ($error !== 'None') {
                            $this->error("❌ Error: {$error}");
                        }
                        $this->newLine();
                    }
                } else {
                    $this->warn('No delivery reports found.');
                }
            } else {
                $this->error('Failed to fetch delivery reports: ' . $response->body());
            }

            // Check account limits
            $this->info('🏢 Checking account configuration...');
            $profileResponse = Http::withHeaders([
                'Authorization' => 'App ' . $apiKey,
                'Accept' => 'application/json'
            ])->get($baseUrl . '/account/1/profile');

            if ($profileResponse->successful()) {
                $profile = $profileResponse->json();
                $this->info('Account Type: ' . ($profile['accountType'] ?? 'Unknown'));
                $this->info('Company: ' . ($profile['companyName'] ?? 'Unknown'));
            }

            // Try to get application info
            $this->info('🔧 Checking application configuration...');
            $applicationsResponse = Http::withHeaders([
                'Authorization' => 'App ' . $apiKey,
                'Accept' => 'application/json'
            ])->get($baseUrl . '/platform/1/applications');

            if ($applicationsResponse->successful()) {
                $apps = $applicationsResponse->json();
                $this->info('Applications configured: ' . count($apps['applications'] ?? []));
            }

            $this->newLine();
            $this->warn('🔔 DEMO MODE SOLUTION:');
            $this->warn('Since your account is in demo mode, here are your options:');
            $this->newLine();
            
            $this->info('Option 1: Whitelist Numbers (Free)');
            $this->info('• Login to https://portal.infobip.com');
            $this->info('• Go to SMS → Demo numbers');
            $this->info('• Add +917010299714 to whitelist');
            $this->newLine();
            
            $this->info('Option 2: Add Credits (Recommended)');
            $this->info('• Login to https://portal.infobip.com');
            $this->info('• Go to Account → Billing');
            $this->info('• Add minimum $10 credits');
            $this->info('• SMS will work for any number');
            $this->newLine();
            
            $this->info('Option 3: Test with Simulator');
            $this->info('• Use a test phone number like +385916242493');
            $this->info('• This is Infobip\'s test number that always works');

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}