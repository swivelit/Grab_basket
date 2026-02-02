<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Suspended</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 3px solid #dc3545; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #dc3545; margin: 0; }
        .content { margin-bottom: 30px; }
        .alert-box { background-color: #f8d7da; border-left: 4px solid #dc3545; padding: 20px; margin: 20px 0; }
        .btn { display: inline-block; padding: 12px 30px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background-color: #5a6268; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Account Suspended</h1>
        </div>

        <div class="content">
            <p>Dear <strong>{{ $partner->name }}</strong>,</p>

            <div class="alert-box">
                <h3 style="margin-top: 0; color: #721c24;">Your Delivery Partner Account Has Been Suspended</h3>
                <p><strong>Reason:</strong> {{ $reason }}</p>
            </div>

            <p>We regret to inform you that your delivery partner account has been suspended due to the reason mentioned above.</p>

            <p><strong>What this means:</strong></p>
            <ul>
                <li>❌ You can no longer log in to your account</li>
                <li>❌ You will not receive any new delivery requests</li>
                <li>❌ Access to the delivery partner dashboard is disabled</li>
                <li>❌ Your earnings will be held pending review</li>
            </ul>

            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
                <strong>Appeal Process:</strong>
                <p>If you believe this suspension was made in error or would like to appeal this decision, please contact our support team immediately.</p>
            </div>

            <p><strong>Contact Support:</strong></p>
            <ul>
                <li>📧 Email: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></li>
                <li>📞 Phone: +91 XXX XXX XXXX (Business hours: 9 AM - 6 PM)</li>
            </ul>

            <p>Our team will review your case and respond within 2-3 business days.</p>

            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h4 style="margin-top: 0;">Maintaining Professional Standards</h4>
                <p>{{ config('app.name') }} is committed to providing excellent service to our customers. We expect all delivery partners to:</p>
                <ul>
                    <li>Maintain professional behavior with customers and staff</li>
                    <li>Follow delivery protocols and safety guidelines</li>
                    <li>Provide timely and reliable service</li>
                    <li>Comply with all platform policies</li>
                </ul>
            </div>

            <p>We take these matters seriously to ensure the best experience for all users of our platform.</p>

            <p>Sincerely,<br>
            <strong>The {{ config('app.name') }} Team</strong></p>
        </div>

        <div class="footer">
            <p>This is an official notification from {{ config('app.name') }}.</p>
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
