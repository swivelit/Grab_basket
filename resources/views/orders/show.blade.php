<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Order Details #{{ $orderData['order_number'] }} - GrabBaskets</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #edf1f7;
            font-family: 'ProximaNova', arial, 'Helvetica Neue', sans-serif;
            color: #282c3f;
            padding-bottom: 50px;
        }

        /* Header */
        .simple-header {
            background: #fff;
            padding: 15px 0;
            box-shadow: 0 15px 40px -20px rgba(40,44,63,0.15);
            margin-bottom: 30px;
        }   
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo-text {
            font-weight: 800;
            font-size: 24px;
            color: #fc8019;
            text-decoration: none;
        }
        .nav-link {
            font-weight: 600;
            color: #3d4152;
            text-decoration: none;
        }

        /* Layout */
        .container-box {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #282c3f;
        }
        .back-btn {
            color: #fc8019;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .back-btn:hover { color: #e37112; }

        /* Detail Card */
        .detail-card {
            background: #fff;
            border: 1px solid #d4d5d9; /* Clean border */
            margin-bottom: 20px;
            /* No heavy shadow, just border like index */
        }
        
        .card-header-clean {
            padding: 20px;
            border-bottom: 1px solid #e9e9eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
        }
        .card-header-clean h3 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            text-transform: uppercase;
            color: #7e808c;
            letter-spacing: 0.5px;
        }

        .card-body-clean {
            padding: 20px;
        }

        /* Items */
        .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .item-info {
            display: flex;
            gap: 10px;
        }
        .veg-icon {
            font-size: 10px;
            color: #60b246; /* Veg Green */
            border: 1px solid #60b246;
            padding: 2px;
            display: inline-block;
            width: 14px;
            height: 14px;
            text-align: center;
            line-height: 8px;
        }
        
        /* Summary Lines */
        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #686b78;
            margin-bottom: 8px;
        }
        .summary-total {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #e9e9eb;
            padding-top: 15px;
            margin-top: 10px;
            font-size: 16px;
            font-weight: 700;
            color: #282c3f;
        }

        /* Buttons */
        .btn-cancel {
            background: #fff;
            color: #e46d47;
            border: 1px solid #e46d47;
            padding: 10px 0;
            width: 100%;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 20px;
        }
        .btn-cancel:hover {
            background: #fff5f2;
        }

        .status-badge {
            background: #e9e9eb;
            color: #3d4152;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }
        .status-badge.delivered { background: #60b246; color: #fff; }
        .status-badge.cancelled { background: #e46d47; color: #fff; }

        @media(max-width: 768px) {
            .row-flex { flex-direction: column; }
            .col-left { margin-bottom: 20px; }
        }
    </style>
</head>
<body>

    <header class="simple-header">
        <div class="container header-inner">
            <a href="/" class="logo-text">
                <i class="fa-solid fa-basket-shopping"></i> GrabBaskets
            </a>
            <a href="{{ route('orders.index') }}" class="nav-link">
                My Orders
            </a>
        </div>
    </header>

    <div class="container-box">
        <div class="page-header">
            <div>
                <h1 class="page-title">Order #{{ $orderData['order_number'] }}</h1>
                <p class="text-muted mb-0" style="font-size: 13px;">
                    Placed on {{ is_string($orderData['date']) ? $orderData['date'] : $orderData['date']->format('M d, Y, h:i A') }}
                </p>
            </div>
            <a href="{{ route('orders.index') }}" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="row g-4">
            <!-- Left Column: Items & Delivery -->
            <div class="col-lg-8">
                <!-- Status Card -->
                <div class="detail-card">
                    <div class="card-body-clean d-flex justify-content-between align-items-center">
                        <div>
                            <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 4px;">{{ $orderData['seller_name'] ?? 'GrabBaskets Store' }}</h3>
                            <span class="text-muted" style="font-size: 13px;">{{ $orderData['type'] }}</span>
                        </div>
                        <span class="status-badge {{ strtolower($orderData['status']) }}">
                            {{ ucfirst($orderData['status']) }}
                        </span>
                    </div>
                </div>

                <!-- Items Card -->
                <div class="detail-card">
                    <div class="card-header-clean">
                        <h3>Items</h3>
                        <span>{{ count($orderData['items'] ?? []) }} ITEMS</span>
                    </div>
                    <div class="card-body-clean">
                        @foreach($orderData['items'] ?? [] as $item)
                        <div class="item-row">
                            <div class="item-info">
                                <div class="veg-icon"><i class="fas fa-circle" style="font-size: 6px; vertical-align: middle;"></i></div>
                                <div>
                                    <div style="font-weight: 500;">
                                        {{ is_array($item) ? ($item['product_name'] ?? 'Product') : ($item->product->name ?? 'Product') }}
                                    </div>
                                    <div style="font-size: 12px; color: #93959f;">
                                        Quantity: {{ is_array($item) ? $item['quantity'] : $item->quantity }}
                                    </div>
                                </div>
                            </div>
                            <div style="font-size: 13px; color: #3d4152;">
                                ₹{{ number_format(is_array($item) ? ($item['total_price'] ?? 0) : ($item->price * $item->quantity), 2) }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Tracking / Delivery -->
                @if(isset($orderData['tracking_number']) && $orderData['tracking_number'])
                <div class="detail-card">
                    <div class="card-header-clean">
                        <h3>Tracking</h3>
                    </div>
                    <div class="card-body-clean">
                        <p style="margin-bottom: 0; font-size: 14px;">
                            <strong>ID:</strong> {{ $orderData['tracking_number'] }}
                            @if(isset($orderData['courier_name']) && $orderData['courier_name'])
                             (Via {{ $orderData['courier_name'] }})
                            @endif
                        </p>
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column: Summary -->
            <div class="col-lg-4">
                <div class="detail-card">
                    <div class="card-header-clean">
                        <h3>Bill Details</h3>
                    </div>
                    <div class="card-body-clean">
                        <div class="summary-row">
                            <span>Item Total</span>
                            <span>₹{{ number_format($orderData['total_amount'], 2) }}</span>
                        </div>
                        
                        @if($type === 'express' && isset($orderData['delivery_fee']) && $orderData['delivery_fee'] > 0)
                        <div class="summary-row">
                            <span>Delivery Fee</span>
                            <span>₹{{ number_format($orderData['delivery_fee'], 2) }}</span>
                        </div>
                        @endif

                        @if($type === 'express' && isset($orderData['tax']) && $orderData['tax'] > 0)
                        <div class="summary-row">
                            <span>Govt Taxes</span>
                            <span>₹{{ number_format($orderData['tax'], 2) }}</span>
                        </div>
                        @endif

                        <div class="summary-total">
                            <span>To Pay</span>
                            <span>₹{{ number_format($orderData['total_amount'], 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="card-header-clean">
                        <h3>Delivery Address</h3>
                    </div>
                    <div class="card-body-clean" style="font-size: 13px; color: #686b78; line-height: 1.5;">
                        <i class="fas fa-home me-2" style="font-size: 14px;"></i> {{ $orderData['delivery_address'] }}<br>
                        @if(isset($orderData['delivery_city'])) {{ $orderData['delivery_city'] }}, @endif
                        @if(isset($orderData['delivery_pincode'])) {{ $orderData['delivery_pincode'] }} @endif
                    </div>
                </div>

                @if($orderData['status'] === 'pending')
                    <button class="btn-cancel" onclick="cancelOrder('{{ $type }}', {{ $orderData['id'] }})">
                        Cancel Order
                    </button>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cancelOrder(type, orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                const btn = document.querySelector('.btn-cancel');
                const originalText = btn.innerHTML;
                btn.innerHTML = 'Cancelling...';
                btn.disabled = true;

                fetch(`/orders/${type}/${orderId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '{{ route("orders.index") }}';
                    } else {
                        alert(data.message || 'Error cancelling order.');
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error cancelling order.');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            }
        }
    </script>
</body>
</html>
