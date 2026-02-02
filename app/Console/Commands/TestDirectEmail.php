<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Mail\SimpleProlotionalMail;
use Illuminate\Support\Facades\Mail;

class TestDirectEmail extends Command
{
    protected $signature = 'test:direct-email';
    protected $description = 'Test sending email directly to see actual error';

    public function handle()
    {
        $this->info('🧪 Testing Direct Email Send...');
        
        // Get a test buyer
        $buyer = User::where('role', 'buyer')->first();
        
        if (!$buyer) {
            $this->error('No buyers found!');
            return 1;
        }
        
        $this->info("Sending test email to: {$buyer->email}");
        
        try {
            $title = '🧪 Direct Test Email';
            $message = 'This is a direct test email to check for errors.';
            $data = ['type' => 'test'];
            
            $mail = new SimpleProlotionalMail($buyer, $title, $message, $data);
            Mail::to($buyer->email)->send($mail);
            
            $this->info('✅ Email sent successfully!');
            
        } catch (\Exception $e) {
            $this->error("❌ Email failed: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . " Line: " . $e->getLine());
            $this->info("Full error: " . $e->getTraceAsString());
        }
        
        return 0;
    }
}