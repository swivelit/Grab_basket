<?php

// Debug password reset route
Route::get('/debug-password-reset', function () {
    try {
        echo '<h2>Password Reset Debug</h2>';
        
        // Test 1: Get a user with email
        $user = App\Models\User::whereNotNull('email')->first();
        if (!$user) {
            echo '<p>❌ No users with email found</p>';
            return;
        }
        
        echo "<p>✓ Testing with user: {$user->email} (ID: {$user->id})</p>";
        
        // Test 2: Check mail configuration
        echo '<h3>Mail Configuration:</h3>';
        echo '<ul>';
        echo '<li>Driver: ' . config('mail.default') . '</li>';
        echo '<li>Host: ' . config('mail.mailers.smtp.host') . '</li>';
        echo '<li>Port: ' . config('mail.mailers.smtp.port') . '</li>';
        echo '<li>Username: ' . config('mail.mailers.smtp.username') . '</li>';
        echo '<li>From: ' . config('mail.from.address') . '</li>';
        echo '<li>Queue: ' . config('queue.default') . '</li>';
        echo '</ul>';
        
        // Test 3: Send password reset
        echo '<h3>Password Reset Test:</h3>';
        
        $status = Password::sendResetLink(['email' => $user->email]);
        
        echo "<p>Reset status: <strong>{$status}</strong></p>";
        
        if ($status == Password::RESET_LINK_SENT) {
            echo '<p style="color: green;">✓ Password reset link sent successfully!</p>';
        } else {
            echo '<p style="color: red;">❌ Password reset failed</p>';
            echo "<p>Possible reasons:</p>";
            echo "<ul>";
            echo "<li>Email server configuration issue</li>";
            echo "<li>User not found in password_resets table structure</li>";
            echo "<li>Mail template missing</li>";
            echo "<li>SMTP authentication failure</li>";
            echo "</ul>";
        }
        
        // Test 4: Try sending a basic test email
        echo '<h3>Basic Email Test:</h3>';
        try {
            Mail::raw('Test email from ' . config('app.name'), function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Test Email - ' . date('Y-m-d H:i:s'))
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });
            echo '<p style="color: green;">✓ Test email sent successfully!</p>';
        } catch (Exception $e) {
            echo '<p style="color: red;">❌ Test email failed: ' . $e->getMessage() . '</p>';
        }
        
        // Test 5: Check password_resets table
        echo '<h3>Password Resets Table:</h3>';
        try {
            $resets = DB::table('password_resets')->where('email', $user->email)->latest()->first();
            if ($resets) {
                echo '<p>✓ Password reset entry created in database</p>';
                echo '<p>Token created at: ' . $resets->created_at . '</p>';
            } else {
                echo '<p>❌ No password reset entry found in database</p>';
            }
        } catch (Exception $e) {
            echo '<p style="color: red;">❌ Error checking password_resets table: ' . $e->getMessage() . '</p>';
        }
        
    } catch (Exception $e) {
        echo '<p style="color: red;">❌ Debug error: ' . $e->getMessage() . '</p>';
        echo '<p>File: ' . $e->getFile() . ' Line: ' . $e->getLine() . '</p>';
    }
});