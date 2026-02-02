<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Config;

class TestEmailWithLog extends Command
{
    protected $signature = 'test:email-log';
    protected $description = 'Test promotional emails using log driver';

    public function handle()
    {
        $this->info('🧪 Testing Promotional System with Log Driver...');
        
        // Temporarily switch to log driver
        Config::set('mail.default', 'log');
        
        $buyers = User::where('role', 'buyer')->get();
        $this->info("Found {$buyers->count()} buyers");
        
        foreach ($buyers as $buyer) {
            $this->info("- {$buyer->name} ({$buyer->email})");
        }
        
        try {
            $sentCount = NotificationService::sendPromotionalEmailToBuyers(
                '🧪 Test Promotional Email - Log Mode',
                'This is a test promotional email using log driver to verify the system works.',
                ['type' => 'test']
            );
            
            $this->info("✅ Successfully processed {$sentCount} promotional emails");
            $this->info("📝 Check storage/logs/laravel.log for email content");
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
        
        return 0;
    }
}