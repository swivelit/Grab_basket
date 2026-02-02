<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .logo {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px 30px;
        }
        .content h2 {
            color: #2196F3;
            margin-top: 0;
        }
        .welcome-message {
            background-color: #E3F2FD;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #2196F3;
        }
        .features {
            margin: 30px 0;
        }
        .feature-item {
            display: flex;
            align-items: start;
            margin: 15px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .feature-icon {
            font-size: 24px;
            margin-right: 15px;
            min-width: 30px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        .cta-button:hover {
            transform: translateY(-2px);
        }
        .footer {
            background-color: #263238;
            color: #B0BEC5;
            padding: 30px;
            text-align: center;
        }
        .footer p {
            margin: 5px 0;
            font-size: 14px;
        }
        .social-links {
            margin: 20px 0;
        }
        .social-links a {
            color: #2196F3;
            text-decoration: none;
            margin: 0 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🛒</div>
            <h1>Welcome to {{ config('app.name') }}!</h1>
            <p>Your journey to amazing shopping starts here</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $user->name }}! 👋</h2>
            
            <div class="welcome-message">
                <strong>Thank you for joining {{ config('app.name') }}!</strong>
                <p style="margin: 10px 0 0;">We're thrilled to have you as part of our growing community. Get ready to discover amazing products from local sellers across India.</p>
            </div>
            
            <h3>What You Can Do:</h3>
            
            <div class="features">
                <div class="feature-item">
                    <div class="feature-icon">🛍️</div>
                    <div>
                        <strong>Shop from Local Sellers</strong>
                        <p style="margin: 5px 0 0; color: #666;">Browse thousands of products from verified sellers in your area and across India.</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">🚚</div>
                    <div>
                        <strong>Fast Delivery</strong>
                        <p style="margin: 5px 0 0; color: #666;">Track your orders in real-time with our advanced delivery tracking system.</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">💳</div>
                    <div>
                        <strong>Secure Payments</strong>
                        <p style="margin: 5px 0 0; color: #666;">Shop with confidence using our secure payment gateway powered by Razorpay.</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">🎁</div>
                    <div>
                        <strong>Gift Options</strong>
                        <p style="margin: 5px 0 0; color: #666;">Send gifts to your loved ones with special gift wrapping options.</p>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}" class="cta-button">Start Shopping Now</a>
            </div>
            
            <div style="background-color: #FFF3E0; padding: 20px; border-radius: 5px; margin: 20px 0;">
                <strong style="color: #F57C00;">💡 Pro Tip:</strong>
                <p style="margin: 10px 0 0; color: #666;">Add items to your wishlist to save them for later and get notified about price drops!</p>
            </div>
            
            <p style="margin-top: 30px; color: #666;">
                If you have any questions or need assistance, our support team is always here to help. Feel free to reach out!
            </p>
        </div>
        
        <div class="footer">
            <p><strong>{{ config('app.name') }}</strong></p>
            <p>Your Trusted Local Marketplace</p>
            <div class="social-links">
                <a href="#">Help Center</a> | 
                <a href="#">Contact Us</a> | 
                <a href="#">Terms</a>
            </div>
            <p style="margin-top: 20px; font-size: 12px;">
                This is an automated welcome email. Please do not reply to this message.<br>
                If you didn't create this account, please contact us immediately.
            </p>
        </div>
    </div>
</body>
</html>
