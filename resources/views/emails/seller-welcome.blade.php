<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Seller - {{ config('app.name') }}</title>
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
            background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
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
            color: #4CAF50;
            margin-top: 0;
        }
        .welcome-message {
            background-color: #E8F5E9;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #4CAF50;
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
            background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
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
        .stats-box {
            background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%);
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
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
            color: #4CAF50;
            text-decoration: none;
            margin: 0 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🏪</div>
            <h1>Welcome to {{ config('app.name') }} Seller Dashboard!</h1>
            <p>Start selling and grow your business today</p>
        </div>
        
        <div class="content">
            <h2>Hello {{ $user->name }}! 🎉</h2>
            
            <div class="welcome-message">
                <strong>Congratulations on joining our seller community!</strong>
                <p style="margin: 10px 0 0;">You're now part of a thriving marketplace connecting local sellers with customers across India. Let's help you get started on your journey to success.</p>
            </div>
            
            <h3>Your Seller Benefits:</h3>
            
            <div class="features">
                <div class="feature-item">
                    <div class="feature-icon">📦</div>
                    <div>
                        <strong>Easy Product Management</strong>
                        <p style="margin: 5px 0 0; color: #666;">Upload and manage your products with our intuitive seller dashboard. Add photos, descriptions, and pricing in minutes.</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">💰</div>
                    <div>
                        <strong>Instant Payment Processing</strong>
                        <p style="margin: 5px 0 0; color: #666;">Get paid securely and quickly for every sale through our integrated payment system.</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">📊</div>
                    <div>
                        <strong>Sales Analytics</strong>
                        <p style="margin: 5px 0 0; color: #666;">Track your sales, monitor inventory, and analyze customer behavior with detailed reports.</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">🚀</div>
                    <div>
                        <strong>Marketing Support</strong>
                        <p style="margin: 5px 0 0; color: #666;">Get featured in our promotional campaigns and reach thousands of potential customers.</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">🔔</div>
                    <div>
                        <strong>Real-time Notifications</strong>
                        <p style="margin: 5px 0 0; color: #666;">Receive instant alerts for new orders via email and SMS so you never miss a sale.</p>
                    </div>
                </div>
            </div>
            
            <div class="stats-box">
                <h3 style="margin-top: 0; color: #F57C00;">🎯 Next Steps to Get Started</h3>
                <ol style="text-align: left; padding-left: 40px; color: #666;">
                    <li>Complete your seller profile</li>
                    <li>Add your first product with clear photos</li>
                    <li>Set competitive prices and delivery options</li>
                    <li>Start receiving orders!</li>
                </ol>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}/seller/dashboard" class="cta-button">Go to Seller Dashboard</a>
            </div>
            
            <div style="background-color: #E1F5FE; padding: 20px; border-radius: 5px; margin: 20px 0;">
                <strong style="color: #0277BD;">💡 Seller Tip:</strong>
                <p style="margin: 10px 0 0; color: #666;">Products with high-quality images and detailed descriptions sell 3x faster! Take time to showcase your products professionally.</p>
            </div>
            
            <p style="margin-top: 30px; color: #666;">
                Need help getting started? Our seller support team is ready to assist you. Check out our <a href="#" style="color: #4CAF50;">Seller Guide</a> or <a href="#" style="color: #4CAF50;">contact support</a>.
            </p>
        </div>
        
        <div class="footer">
            <p><strong>{{ config('app.name') }} Seller Hub</strong></p>
            <p>Empowering Local Businesses</p>
            <div class="social-links">
                <a href="#">Seller Guide</a> | 
                <a href="#">Support</a> | 
                <a href="#">Policies</a>
            </div>
            <p style="margin-top: 20px; font-size: 12px;">
                This is an automated welcome email. Please do not reply to this message.<br>
                For assistance, visit your seller dashboard or contact our support team.
            </p>
        </div>
    </div>
</body>
</html>
