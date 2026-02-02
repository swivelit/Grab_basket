<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Order Confirmed - GrabBasket</title>

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary: #2f7a2f;
            --secondary: #4caf50;
            --bg: #f6f7fb;
            --card-bg: #fff;
            --radius: 14px;
            --shadow: 0 10px 25px rgba(0,0,0,0.07);
            --font: 'Inter', sans-serif;
        }
        body {
            background: var(--bg);
            color: #0b1720;
            font-family: var(--font);
            margin: 0;
            padding: 0;
        }
        .navbar {
            background: white;
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .brand {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 16px;
        }
        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 30px;
            text-align: center;
        }
        .check-icon {
            font-size: 60px;
            color: var(--primary);
            margin-bottom: 20px;
        }
        .btn-primary {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border: none;
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
        }
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            margin-top: 10px;
        }
        .order-details {
            background: #f0f9f0;
            padding: 16px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: left;
        }
        ul.order-items {
            list-style: none;
            padding: 0;
        }
        ul.order-items li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
        }
        ul.order-items li:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="brand">
        <i class="fa-solid fa-basket-shopping"></i> GrabBaskets
    </div>
    <div>
        @auth
            <span>Hello, {{ Auth::user()->name }}!</span>
        @endauth
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="container">
    <div class="card">
        <div class="check-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2>Order Placed Successfully!</h2>
        <p>Thank you! Your 10-minute delivery order is being prepared.</p>

        @if($order)
        <div class="order-details">
            <p><strong>Order ID:</strong> #{{ $order->id }}</p>
            <p><strong>Restaurant:</strong> {{ $order->shop_name ?? 'Quick Delivery' }}</p>
            <p><strong>Total Amount:</strong> ₹{{ number_format($order->total_amount, 2) }}</p>
            <p><strong>Estimated Delivery:</strong> {{ $order->estimated_delivery_time->format('d M Y, h:i A') }}</p>
        </div>

        <h4>Items Ordered</h4>
        <ul class="order-items">
            @foreach($order->items as $item)
            <li>
                <span>{{ $item->quantity }}x {{ $item->food_name }}</span>
                <span>₹{{ number_format($item->price * $item->quantity, 2) }}</span>
            </li>
            @endforeach
        </ul>
        @endif

        <div class="mt-4">
            <a href="{{ route('ten.min.products') }}" class="btn btn-primary">Continue Shopping</a>
            <br>
            <a href="{{ route('tenmin.cart.view') }}" class="btn btn-outline">View Cart</a>
        </div>
    </div>
</div>

</body>
</html>