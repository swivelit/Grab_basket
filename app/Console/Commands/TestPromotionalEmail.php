<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;
use App\Models\User;
use App\Mail\SimpleProlotionalMail;
use Illuminate\Support\Facades\Mail;

class TestPromotionalEmail extends Command
{
    protected $signature = 'test:promotional-email {email : Email address to send test email}';
    protected $description = 'Send a test promotional email to verify email functionality';

    public function handle()
    {
        $email = $this->argument('email');
        
        // Create a dummy user for testing
        $testUser = new User();
        $testUser->name = 'Test User';
        $testUser->email = $email;
        
        $title = "🔥 Test Promotional Email - Daily Deals!";
        $message = "This is a test promotional email to verify the email system is working correctly. Amazing deals await you!";
        $promotionData = [
            'type' => 'daily_deals',
            'test' => true
        ];

        try {
            $this->info("Sending test promotional email to: {$email}");
            
            Mail::to($email)->send(new SimpleProlotionalMail($testUser, $title, $message, $promotionData));
            
            $this->info("✅ Test promotional email sent successfully!");
            $this->info("Check your inbox for the email with subject: {$title}");
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to send email: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}