<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .header { background: #ff6b6b; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { padding: 20px; }
        .footer { background: #333; color: white; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; }
        .btn { background: #4CAF50; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>GRAB BASKETS</h1>
            <p>Amazing Deals Just for You!</p>
        </div>
        
        <div class="content">
            <h2>Hi {{ $user->name }}! 👋</h2>
            
            <h3>{{ $title }}</h3>
            <p>{{ $message }}</p>
            
            @if(isset($promotionData['type']) && $promotionData['type'] === 'daily_deals')
                <div style="background: #fffacd; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <h4>🔥 Today's Special Offers:</h4>
                    <ul>
                        <li>Up to 50% OFF on Electronics</li>
                        <li>Buy 2 Get 1 FREE on Fashion</li>
                        <li>Extra 20% OFF on Home & Garden</li>
                    </ul>
                </div>
            @endif
            
            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}" class="btn">🛒 Shop Now</a>
            </p>
            
            <p><strong>Why Choose GRAB BASKETS?</strong></p>
            <ul>
                <li>✓ Fast & Free Delivery</li>
                <li>✓ 30-Day Easy Returns</li>
                <li>✓ Secure Payment Options</li>
                <li>✓ 24/7 Customer Support</li>
            </ul>
        </div>
        
        <div class="footer">
            <p><strong>GRAB BASKETS</strong></p>
            <p>Your trusted online shopping destination</p>
            <p style="font-size: 12px; margin-top: 10px;">
                You received this email because you're a valued customer.
            </p>
        </div>
    </div>
</body>
</html>