<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        .header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        .content {
            padding: 2rem;
        }
        .greeting {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
        .main-message {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 1.5rem 0;
            text-align: center;
        }
        .main-message h2 {
            margin: 0 0 1rem 0;
            font-size: 1.5rem;
        }
        .main-message p {
            margin: 0;
            font-size: 1.1rem;
            opacity: 0.95;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin: 1.5rem 0;
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }
        .product-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            background: #f9f9f9;
        }
        .product-card h4 {
            margin: 0.5rem 0;
            font-size: 0.9rem;
            color: #2c3e50;
        }
        .product-price {
            color: #e74c3c;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .footer {
            background-color: #2c3e50;
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        .footer p {
            margin: 0.5rem 0;
            opacity: 0.8;
        }
        .social-links {
            margin: 1rem 0;
        }
        .social-links a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            opacity: 0.8;
        }
        .unsubscribe {
            font-size: 0.8rem;
            opacity: 0.6;
            margin-top: 1rem;
        }
        .highlight {
            background: linear-gradient(120deg, #ffd700 0%, #ffed4a 100%);
            padding: 2px 8px;
            border-radius: 4px;
            color: #2c3e50;
            font-weight: 600;
        }
        .urgent {
            background: #e74c3c;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 600;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ config('app.name', 'E-Commerce') }}</h1>
            <p>Amazing deals just for you!</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Hi {{ $user->name ?? 'Valued Customer' }}! 👋
            </div>

            <div class="main-message">
                <h2>{{ $title ?? 'Special Offer' }}</h2>
                <p>{{ $message ?? 'We have amazing deals waiting for you!' }}</p>
            </div>

            @if(isset($promotionData['type']) && $promotionData['type'] === 'flash_sale')
                <div style="text-align: center;">
                    <div class="urgent">
                        ⏰ FLASH SALE - Only 2 Hours Left!
                    </div>
                </div>
            @endif

            @if(isset($promotionData['type']) && $promotionData['type'] === 'daily_deals')
                <div style="text-align: center;">
                    <p>🔥 Today's Featured Deals:</p>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                        <p style="margin: 0; font-size: 1.1rem;">
                            <span class="highlight">Up to 50% OFF</span> on Electronics<br>
                            <span class="highlight">Buy 2 Get 1 FREE</span> on Fashion<br>
                            <span class="highlight">Extra 20% OFF</span> on Home & Garden
                        </p>
                    </div>
                </div>
            @endif

            @if(isset($promotionData['type']) && $promotionData['type'] === 'weekly_newsletter')
                <div style="text-align: center;">
                    <p>📈 This Week's Highlights:</p>
                    <div style="background: #e8f5e8; padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                        <p style="margin: 0;">
                            ✅ {{ $promotionData['new_products_count'] ?? 0 }} New Products Added<br>
                            ✅ 150+ Items on Sale<br>
                            ✅ Free Delivery on Orders ₹500+
                        </p>
                    </div>
                </div>
            @endif

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}" class="cta-button">
                    🛒 Shop Now & Save Big!
                </a>
            </div>

            <div style="background: #f0f8ff; padding: 1rem; border-radius: 8px; border-left: 4px solid #007bff; margin: 1.5rem 0;">
                <h3 style="margin: 0 0 0.5rem 0; color: #007bff;">💡 Why Shop With Us?</h3>
                <p style="margin: 0;">
                    ✓ Fast & Free Delivery<br>
                    ✓ 30-Day Easy Returns<br>
                    ✓ Secure Payment Options<br>
                    ✓ 24/7 Customer Support
                </p>
            </div>

            <div style="text-align: center; margin: 2rem 0;">
                <p style="color: #666; font-size: 0.9rem;">
                    Don't miss out on these amazing deals! Offer valid for limited time only.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>{{ config('app.name', 'E-Commerce') ?? 'GRAB BASKETS' }}</strong></p>
            <p>Your trusted online shopping destination</p>
            
            <div class="social-links">
                <a href="#">📘 Facebook</a>
                <a href="#">📷 Instagram</a>
                <a href="#">🐦 Twitter</a>
            </div>

            <div class="unsubscribe">
                <p>You received this email because you're a valued customer.</p>
                <p>If you no longer wish to receive promotional emails, <a href="#" style="color: #ccc;">unsubscribe here</a>.</p>
            </div>
        </div>
    </div>
</body>
</html>