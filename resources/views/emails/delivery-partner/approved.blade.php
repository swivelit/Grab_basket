<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Approved!</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 3px solid #4CAF50; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #4CAF50; margin: 0; }
        .content { margin-bottom: 30px; }
        .success-box { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 30px; border-radius: 10px; text-align: center; margin: 20px 0; }
        .btn { display: inline-block; padding: 15px 40px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; font-size: 16px; font-weight: bold; }
        .btn:hover { background-color: #45a049; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px; }
        .feature-list { background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .feature-list li { margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎊 Congratulations {{ $partner->name }}!</h1>
        </div>

        <div class="content">
            <div class="success-box">
                <h2 style="margin-top: 0; font-size: 28px;">✅ Your Account Has Been Approved!</h2>
                <p style="font-size: 18px;">You can now start delivering and earning with {{ config('app.name') }}.</p>
            </div>

            <p>Dear <strong>{{ $partner->name }}</strong>,</p>

            <p>Great news! Your delivery partner application has been approved by our admin team. Welcome to the {{ config('app.name') }} delivery family!</p>

            <div style="background-color: #e8f5e9; border-left: 4px solid #4CAF50; padding: 20px; margin: 20px 0;">
                <h3 style="margin-top: 0; color: #2e7d32;">🚀 You're Ready to Start!</h3>
                <p><strong>Your account is now active and you can:</strong></p>
                <ul style="margin: 10px 0;">
                    <li>✅ Log in to your dashboard</li>
                    <li>✅ View and accept delivery orders</li>
                    <li>✅ Track your earnings in real-time</li>
                    <li>✅ Manage your profile and availability</li>
                </ul>
            </div>

            <div style="text-align: center; margin: 40px 0;">
                <a href="{{ $dashboardUrl }}" class="btn">🚀 Log In to Dashboard Now</a>
            </div>

            <div class="feature-list">
                <h3 style="color: #4CAF50; margin-top: 0;">💰 Earnings & Benefits</h3>
                <ul>
                    <li><strong>Competitive Commissions:</strong> Earn on every successful delivery</li>
                    <li><strong>Flexible Schedule:</strong> Work when you want, where you want</li>
                    <li><strong>Instant Notifications:</strong> Get notified of new delivery requests</li>
                    <li><strong>Weekly Payouts:</strong> Withdraw your earnings every week</li>
                    <li><strong>Performance Bonuses:</strong> Top performers earn extra rewards</li>
                </ul>
            </div>

            <p><strong>Getting Started Tips:</strong></p>
            <ol>
                <li>Log in to your dashboard and complete your profile</li>
                <li>Set your availability status to "Online" to receive orders</li>
                <li>Accept delivery requests and follow the pickup/delivery workflow</li>
                <li>Track your earnings and performance metrics</li>
                <li>Contact support anytime if you need help</li>
            </ol>

            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
                <strong>⚠️ Important:</strong> Please maintain professional behavior and provide excellent service. Misbehavior or policy violations may result in account suspension.
            </div>

            <p>We're excited to have you as part of our team. Let's deliver excellence together!</p>

            <p>Best regards,<br>
            <strong>The {{ config('app.name') }} Team</strong></p>
        </div>

        <div class="footer">
            <p>Need help? Contact us at {{ config('mail.support_email', 'support@grabbaskets.com') }}</p>
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
