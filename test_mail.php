<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use App\Models\User;

// Test mail functionality
echo "=== Testing Email Functionality ===\n\n";

// Test 1: Check if we can send a basic test email
echo "1. Testing basic email sending...\n";
try {
    Mail::raw('This is a test email from your Laravel application.', function ($message) {
        $message->to('test@example.com')
                ->subject('Test Email')
                ->from(config('mail.from.address'), config('mail.from.name'));
    });
    echo "✓ Basic email queued successfully\n";
} catch (Exception $e) {
    echo "✗ Error sending basic email: " . $e->getMessage() . "\n";
}

echo "\n2. Testing password reset functionality...\n";

// Test 2: Find a user to test with
$user = User::first();
if ($user) {
    echo "Using user: {$user->email} (ID: {$user->id})\n";
    
    try {
        $status = Password::sendResetLink(['email' => $user->email]);
        echo "Password reset status: " . $status . "\n";
        
        if ($status == Password::RESET_LINK_SENT) {
            echo "✓ Password reset link sent successfully!\n";
        } else {
            echo "✗ Password reset failed: " . $status . "\n";
        }
    } catch (Exception $e) {
        echo "✗ Error sending password reset: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    }
} else {
    echo "No users found in database\n";
}

echo "\n3. Checking mail configuration...\n";
echo "Mail driver: " . config('mail.default') . "\n";
echo "SMTP Host: " . config('mail.mailers.smtp.host') . "\n";
echo "SMTP Port: " . config('mail.mailers.smtp.port') . "\n";
echo "From Address: " . config('mail.from.address') . "\n";
echo "From Name: " . config('mail.from.name') . "\n";

echo "\n4. Checking queue configuration...\n";
echo "Queue driver: " . config('queue.default') . "\n";