<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order Received</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .order-details {
            background-color: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border: 1px solid #eee;
        }
        .footer {
            background-color: #333;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 0 0 5px 5px;
        }
        .highlight {
            color: #4CAF50;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 New Order Received!</h1>
    </div>
    
    <div class="content">
        <h2>Hello {{ $user->name }},</h2>
        
        <p>Great news! You have received a new order for your product.</p>
        
        <div class="order-details">
            <h3>Order Details:</h3>
            <p><strong>Product:</strong> {{ $product->name }}</p>
            <p><strong>Order ID:</strong> #{{ $order->id }}</p>
            <p><strong>Amount:</strong> <span class="highlight">₹{{ number_format($order->amount, 2) }}</span></p>
            <p><strong>Buyer:</strong> {{ $order->buyerUser->name }}</p>
            <p><strong>Order Date:</strong> {{ $order->created_at->format('d M Y, h:i A') }}</p>
            <p><strong>Payment Status:</strong> <span class="highlight">{{ ucfirst($order->status) }}</span></p>
        </div>
        
        <div class="order-details">
            <h3>Delivery Address:</h3>
            <p>{{ $order->delivery_address }}<br>
            {{ $order->delivery_city }}, {{ $order->delivery_state }}<br>
            PIN: {{ $order->delivery_pincode }}</p>
        </div>
        
        <p>Please log in to your seller dashboard to view more details and update the order status.</p>
        <div class="order-details" style="background:#fffbe6; border:1px solid #ffe58f;">
            <strong>Reminder:</strong> After you courier the package, please enter the tracking number for this order in your dashboard. The buyer will be notified automatically.
        </div>
        <p style="margin-top: 30px;">
            <a href="{{ config('app.url') . route('seller.orders', [], false) }}" style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">View Order Details</a>
        </p>
    </div>
    
    <div class="footer">
        <p>Thank you for selling with {{ config('app.name') }}!</p>
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html>