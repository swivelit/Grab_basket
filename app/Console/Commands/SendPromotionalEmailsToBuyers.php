<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;

class SendPromotionalEmailsToBuyers extends Command
{
    protected $signature = 'email:send-promotional {--type=daily : Type of promotional email}';
    protected $description = 'Send promotional emails to all buyers';

    public function handle()
    {
        $type = $this->option('type');
        
        $this->info("📧 Sending {$type} promotional emails to all buyers...");
        
        try {
            $sentCount = NotificationService::sendPromotionalEmailToBuyers(
                '🔥 Special Offer from GRAB BASKETS!',
                'Amazing deals and discounts are waiting for you. Don\'t miss out on these limited-time offers!',
                ['type' => $type]
            );
            
            $this->info("✅ Successfully sent promotional emails to {$sentCount} buyers!");
            $this->info("📬 All buyers have been notified about the special offers.");
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to send emails: " . $e->getMessage());
        }
        
        return 0;
    }
}