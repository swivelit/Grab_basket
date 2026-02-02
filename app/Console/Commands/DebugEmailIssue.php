<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\NotificationService;

class DebugEmailIssue extends Command
{
    protected $signature = 'debug:email-issue';
    protected $description = 'Debug why promotional emails are not being sent';

    public function handle()
    {
        $this->info('🔍 Debugging Email Issue...');
        
        // Check users
        $totalUsers = User::count();
        $buyers = User::where('role', 'buyer')->count();
        $sellers = User::where('role', 'seller')->count();
        
        $this->info("👥 Total Users: {$totalUsers}");
        $this->info("🛒 Buyers: {$buyers}");
        $this->info("🏪 Sellers: {$sellers}");
        
        if ($buyers === 0) {
            $this->warn('⚠️ No buyers found in database! This is why emails aren\'t being sent.');
            $this->info('Creating a test buyer...');
            
            // Create a test buyer
            $testBuyer = User::create([
                'name' => 'Test Buyer',
                'email' => 'testbuyer@example.com',
                'password' => bcrypt('password123'),
                'role' => 'buyer',
                'email_verified_at' => now()
            ]);
            
            $this->info("✅ Created test buyer: {$testBuyer->email}");
        }
        
        // Check mail configuration
        $this->info('📧 Mail Configuration:');
        $this->info('MAIL_MAILER: ' . config('mail.default'));
        $this->info('MAIL_HOST: ' . config('mail.mailers.smtp.host'));
        $this->info('MAIL_FROM: ' . config('mail.from.address'));
        
        // Test notification service
        $this->info('🧪 Testing NotificationService...');
        
        $updatedBuyers = User::where('role', 'buyer')->get();
        $this->info("Found {$updatedBuyers->count()} buyers in database");
        
        foreach ($updatedBuyers as $buyer) {
            $this->info("- {$buyer->name} ({$buyer->email})");
        }
        
        if ($updatedBuyers->count() > 0) {
            $this->info('Attempting to send test emails...');
            
            try {
                $sentCount = NotificationService::sendPromotionalEmailToBuyers(
                    '🧪 Test Email - System Check',
                    'This is a test email to verify the promotional email system is working correctly.',
                    ['type' => 'test']
                );
                
                $this->info("📬 Successfully sent emails to {$sentCount} buyers");
            } catch (\Exception $e) {
                $this->error("❌ Error sending emails: " . $e->getMessage());
                $this->error("Stack trace: " . $e->getTraceAsString());
            }
        }
        
        return 0;
    }
}