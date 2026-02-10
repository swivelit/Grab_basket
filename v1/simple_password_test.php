<?php

// Simple password reset test route
Route::get('/test-password-reset-simple', function () {
    try {
        echo "<h2>Password Reset Test</h2>";
        
        // Find a user
        $user = App\Models\User::whereNotNull('email')->first();
        if (!$user) {
            return "No user with email found.";
        }
        
        echo "<p>Testing with: {$user->email}</p>";
        
        // Test direct password reset
        $status = Password::sendResetLink(['email' => $user->email]);
        
        echo "<p>Status: <strong>{$status}</strong></p>";
        
        if ($status === Password::RESET_LINK_SENT) {
            echo '<p style="color: green;">✓ SUCCESS: Reset link sent!</p>';
        } else {
            echo '<p style="color: red;">✗ FAILED: ' . $status . '</p>';
            
            // Additional debugging
            echo "<h3>Debugging Info:</h3>";
            echo "<ul>";
            echo "<li>Mail Driver: " . config('mail.default') . "</li>";
            echo "<li>SMTP Host: " . config('mail.mailers.smtp.host') . "</li>";
            echo "<li>From Address: " . config('mail.from.address') . "</li>";
            echo "<li>Queue Driver: " . config('queue.default') . "</li>";
            echo "</ul>";
            
            // Check if token was created in database
            $token = DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->latest('created_at')
                ->first();
                
            if ($token) {
                echo "<p>✓ Token created in database at: {$token->created_at}</p>";
            } else {
                echo "<p>✗ No token found in database</p>";
            }
        }
        
    } catch (Exception $e) {
        return "Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
    }
});