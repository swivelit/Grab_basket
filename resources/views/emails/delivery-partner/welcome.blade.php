<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 3px solid #4CAF50; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #4CAF50; margin: 0; }
        .content { margin-bottom: 30px; }
        .highlight-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0; }
        .btn { display: inline-block; padding: 12px 30px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background-color: #45a049; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px; }
        .info-box { background-color: #e3f2fd; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Welcome to {{ config('app.name') }}!</h1>
        </div>

        <div class="content">
            <p>Dear <strong>{{ $partner->name }}</strong>,</p>
            
            <div class="highlight-box">
                <h2 style="margin-top: 0;">Thank You for Joining Our Delivery Team!</h2>
                <p>Your registration has been successfully submitted.</p>
            </div>

            <p>We're excited to have you on board as a delivery partner. Here's what happens next:</p>

            <div class="info-box">
                <strong>📋 Application Status:</strong> PENDING REVIEW
                <p style="margin: 10px 0 0 0;">Our admin team will review your application within 24-48 hours. You'll receive an email once your account is approved.</p>
            </div>

            <p><strong>What to expect:</strong></p>
            <ul>
                <li>✅ We'll verify your details and documents</li>
                <li>✅ You'll receive an approval email once verified</li>
                <li>✅ After approval, you can log in and start accepting orders</li>
                <li>✅ Earn competitive commissions on every delivery</li>
            </ul>

            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h3 style="margin-top: 0; color: #4CAF50;">Your Account Details</h3>
                <p><strong>Email:</strong> {{ $partner->email }}</p>
                <p><strong>Phone:</strong> {{ $partner->phone }}</p>
                <p><strong>Login URL:</strong> <a href="{{ $loginUrl }}">{{ $loginUrl }}</a></p>
            </div>

            <p><strong>Important Notes:</strong></p>
            <ul>
                <li>You cannot log in until your account is approved</li>
                <li>Keep your login credentials secure</li>
                <li>Update your profile and upload required documents after approval</li>
            </ul>

            <div style="text-align: center; margin: 30px 0;">
                <p>Once approved, you can log in here:</p>
                <a href="{{ $loginUrl }}" class="btn">Go to Login Page</a>
            </div>

            <p>If you have any questions or need assistance, feel free to contact our support team.</p>

            <p>Best regards,<br>
            <strong>The {{ config('app.name') }} Team</strong></p>
        </div>

        <div class="footer">
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
