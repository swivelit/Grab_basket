<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
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
            background-color: #2196F3;
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
        .order-item {
            background-color: white;
            padding: 15px;
            margin: 10px 0;
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
            color: #2196F3;
            font-weight: bold;
        }
        .total {
            background-color: #e8f5e8;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Order Confirmed!</h1>
    </div>
    
    <div class="content">
        <h2>Hello {{ $user->name }},</h2>
        
        <p>Thank you for your order! Your payment has been processed successfully and your order is confirmed.</p>
        
    @if($orders && count($orders) > 1)
            <h3>Order Summary:</h3>
            @foreach($orders as $orderItem)
                <div class="order-item">
                    <h4>{{ $orderItem->product->name }}</h4>
                    <p><strong>Order ID:</strong> #{{ $orderItem->id }}</p>
                    <p><strong>Amount:</strong> ₹{{ number_format($orderItem->amount, 2) }}</p>
                    <p><strong>Seller:</strong> {{ $orderItem->sellerUser->name ?? 'N/A' }}</p>
                    @if($orderItem->product->gift_option === 'yes')
                        <p><span style="color: #ff6b6b;">🎁 Gift Option Available</span></p>
                    @endif
                </div>
            @endforeach
            
            <div class="total">
                Total Amount: ₹{{ number_format(collect($orders)->sum('amount'), 2) }}
            </div>
        @endif

        @if(isset($order) && $order->tracking_number)
            <div class="order-item">
                <h3>Tracking Number:</h3>
                <p><span class="highlight">{{ $order->tracking_number }}</span></p>
                <p>You can use this number to track your shipment.</p>
            </div>
        @endif
        @else
            <div class="order-item">
                <h3>Order Details:</h3>
                <p><strong>Product:</strong> {{ $order->product->name }}</p>
                <p><strong>Order ID:</strong> #{{ $order->id }}</p>
                <p><strong>Amount:</strong> <span class="highlight">₹{{ number_format($order->amount, 2) }}</span></p>
                <p><strong>Seller:</strong> {{ $order->sellerUser->name ?? 'N/A' }}</p>
                <p><strong>Order Date:</strong> {{ $order->created_at->format('d M Y, h:i A') }}</p>
                @if($order->product->gift_option === 'yes')
                    <p><span style="color: #ff6b6b;">🎁 Gift Option Available</span></p>
                @endif
            </div>
        @endif
        
        <div class="order-item">
            <h3>Delivery Address:</h3>
            <p>{{ $order->delivery_address }}<br>
            {{ $order->delivery_city }}, {{ $order->delivery_state }}<br>
            PIN: {{ $order->delivery_pincode }}</p>
        </div>
        
        <p>You can track your order status and view details in your account.</p>
        
        <p style="margin-top: 30px;">
            <a href="{{ config('app.url') . route('orders.track', [], false) }}" style="background-color: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Track Your Order</a>
        </p>
    </div>
    
    <div class="footer">
        <p>Thank you for shopping with {{ config('app.name') }}!</p>
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>If you have any questions, please contact our support team.</p>
    </div>
</body>
</html>