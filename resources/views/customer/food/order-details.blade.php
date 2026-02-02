<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Order #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }} details">
    <title>Order #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }} | 10-Mins Food</title>

    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --primary: #ff6b35;
            --primary-dark: #e05a2a;
            --primary-light: #ffb380;
            --success: #4CAF50;
            --warning: #FFC107;
            --danger: #f44336;
            --light: #f8f9fa;
            --dark: #212529;
            --gray-100: #f1f3f6;
            --gray-200: #e9ecef;
            --gray-700: #495057;
            --border-radius: 12px;
            --card-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s ease;
        }

        [data-bs-theme="dark"] {
            --gray-100: #1e293b;
            --gray-200: #334155;
            --light: #0f172a;
            --dark: #e2e8f0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--gray-100);
            color: var(--dark);
            padding-top: 72px;
            transition: var(--transition);
        }

        /* Navbar */
        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            background: white;
            z-index: 1030;
            position: fixed;
            top: 0;
            width: 100%;
            transition: var(--transition);
        }

        [data-bs-theme="dark"] .navbar {
            background: #0f172a;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary) !important;
            font-size: 1.4rem;
        }

        .navbar-brand i {
            font-size: 1.6rem;
            margin-right: 6px;
        }

        /* Order Header */
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items-center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 1.5rem;
        }

        .order-id {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
        }

        .status-badge {
            font-size: 0.85rem;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .badge-delivered {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .badge-onway {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-pending {
            background-color: #e2e3e5;
            color: #383d41;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: var(--transition);
            background: white;
            border: 1px solid var(--gray-200);
        }

        [data-bs-theme="dark"] .card {
            background: #1e293b;
            border-color: #334155;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: white !important;
            border-bottom: 1px solid var(--gray-200);
            padding: 16px 20px;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        [data-bs-theme="dark"] .card-header {
            background: #0f172a !important;
            border-color: #334155;
        }

        .card-body {
            padding: 20px;
        }

        /* Items List */
        .list-group-item {
            border: none;
            padding: 16px 20px;
            background-color: #fafafa;
            border-radius: 8px;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
            transition: background 0.2s;
        }

        [data-bs-theme="dark"] .list-group-item {
            background-color: #0f172a;
        }

        .list-group-item:hover {
            background-color: #f1f5f9;
        }

        [data-bs-theme="dark"] .list-group-item:hover {
            background-color: #1e293b;
        }

        .food-item-thumb {
            width: 56px;
            height: 56px;
            border-radius: 8px;
            background: linear-gradient(135deg, #ff9a57, var(--primary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.1rem;
            flex-shrink: 0;
            margin-right: 14px;
        }

        .food-details {
            flex: 1;
        }

        .food-name {
            font-weight: 600;
            font-size: 1.05rem;
            margin-bottom: 4px;
            color: var(--dark);
        }

        .food-meta {
            font-size: 0.8rem;
            color: var(--gray-700);
            display: flex;
            gap: 8px;
        }

        .food-meta::before {
            content: "•";
        }

        .food-price-group {
            text-align: right;
            min-width: 80px;
        }

        .food-price {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--dark);
        }

        .food-qty {
            font-size: 0.85rem;
            color: var(--gray-700);
            margin-top: 2px;
        }

        /* Totals */
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 1rem;
        }

        .summary-row.label {
            font-weight: 600;
            color: var(--dark);
        }

        .summary-row.total {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            border-top: 2px dashed var(--gray-200);
            padding-top: 12px;
            margin-top: 8px;
        }

        .discount-row {
            color: var(--success);
        }

        /* Back Button */
        .back-link {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: var(--primary);
            font-weight: 500;
            transition: var(--transition);
            padding: 6px 12px;
            border-radius: 8px;
        }

        .back-link:hover {
            background-color: rgba(255, 107, 53, 0.08);
            color: var(--primary-dark);
            text-decoration: none;
        }

        .back-link i {
            margin-right: 6px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding-top: 64px;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-id {
                font-size: 1.5rem;
            }

            .food-item {
                flex-direction: column !important;
                align-items: flex-start;
            }

            .food-price-group {
                width: 100%;
                margin-top: 10px;
                text-align: right;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <!-- Brand -->
            <a class="navbar-brand d-flex align-items-center" href="{{ route('customer.food.index') }}">
                <i class="bi bi-truck"></i>
                <span>10-Mins Food</span>
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">

                <ul class="navbar-nav">
                    @auth
                        <li class="nav-item">
                            <div class="theme-toggle" id="themeToggle" title="Toggle Dark Mode"></div>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <!-- Back Link -->
                <div class="mb-4">
                    <a href="{{ route('food.my-orders') }}" class="back-link">
                        <i class="bi bi-arrow-left"></i> Back to Orders
                    </a>
                </div>

                <!-- Order Header -->
                <div class="order-header">
                    <h2 class="order-id">Order #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</h2>
                    <span class="status-badge
                        @if($order->status === 'Delivered') badge-delivered
                        @elseif($order->status === 'Cancelled') badge-cancelled
                        @elseif($order->status === 'On the way') badge-onway
                        @else badge-pending @endif">
                        @if($order->status === 'Delivered')
                            <i class="bi bi-check-circle-fill"></i>
                        @elseif($order->status === 'Cancelled')
                            <i class="bi bi-x-circle-fill"></i>
                        @elseif($order->status === 'On the way')
                            <i class="bi bi-truck"></i>
                        @else
                            <i class="bi bi-clock"></i>
                        @endif
                        {{ $order->status }}
                    </span>
                </div>

                <!-- Summary Card -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-clipboard-data"></i> Order Summary
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div><strong>📅 Placed on:</strong></div>
                                <div class="text-muted">{{ $order->created_at->format('M d, Y \a\t h:i A') }}</div>
                            </div>
                            <div class="col-md-6">
                                <div><strong>🚚 Est. Delivery:</strong></div>
                                <div class="text-muted">
                                    @if($order->estimated_delivery_time)
                                        {{ $order->estimated_delivery_time->format('M d, Y \a\t h:i A') }}
                                    @else
                                        <span class="text-warning">Calculating...</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12">
                                <div><strong>💳 Payment Method:</strong></div>
                                <div>
                                    {{ ucfirst($order->payment_method) }}
                                    @if($order->payment_reference)
                                        <span class="badge bg-light text-dark ms-2">Ref:
                                            {{ substr($order->payment_reference, -6) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Restaurant Card -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-shop"></i> {{ $order->shop_name ?? 'Restaurant' }}
                    </div>
                    <div class="card-body">
                        <div class="d-flex">
                            <i class="bi bi-geo-alt text-muted mt-1 me-2"></i>
                            <div>{{ $order->shop_address ?? '—' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Delivery Address Card -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-house-door"></i> Delivery Address
                    </div>
                    <div class="card-body">
                        <div><strong>{{ $order->customer_name }}</strong></div>
                        <div class="mt-1">
                            <i class="bi bi-telephone text-muted me-1"></i>
                            {{ $order->customer_phone }}
                        </div>
                        <div class="mt-2">
                            <i class="bi bi-geo-alt text-muted me-1"></i>
                            {{ $order->delivery_address ?? '—' }}
                        </div>
                    </div>
                </div>

                <!-- Delivery Partner Card -->
                @if($order->deliveryPartner)
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <i class="bi bi-person-badge"></i> Delivery Partner
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border"
                                        style="width: 60px; height: 60px;">
                                        <i class="bi bi-person fs-3 text-secondary"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-1 fw-bold">{{ $order->deliveryPartner->name }}</h5>
                                    <div class="text-muted d-flex align-items-center mb-1">
                                        <i class="bi bi-telephone me-2"></i> {{ $order->deliveryPartner->phone }}
                                    </div>
                                    <div class="text-muted d-flex align-items-center">
                                        <i class="bi bi-bicycle me-2"></i>
                                        {{ $order->deliveryPartner->vehicle_number ?? 'Vehicle Not Listed' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Items Card -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-basket2"></i> Items ({{ $order->items->count() }})
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach($order->items as $item)
                            <li class="list-group-item d-flex align-items-start">
                                <div class="food-item-thumb">
                                    {{ strtoupper(substr($item->food_name, 0, 1)) }}
                                </div>
                                <div class="food-details">
                                    <div class="food-name">{{ $item->food_name }}</div>
                                    <div class="food-meta">
                                        {{ $item->category ?? '—' }}
                                        {{ $item->food_type ? '• ' . ucfirst($item->food_type) : '' }}
                                    </div>
                                </div>
                                <div class="food-price-group">
                                    <div class="food-price">₹{{ number_format($item->price, 2) }}</div>
                                    @if($item->quantity > 1)
                                        <div class="food-qty">×{{ $item->quantity }}</div>
                                        <div class="food-total mt-1 fw-bold">
                                            ₹{{ number_format($item->price * $item->quantity, 2) }}
                                        </div>
                                    @else
                                        <div class="mt-3"></div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Totals Card -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-receipt"></i> Total Amount
                    </div>
                    <div class="card-body">
                        <div class="summary-row">
                            <span>Food Total</span>
                            <span>₹{{ number_format($order->food_total, 2) }}</span>
                        </div>
                        <div class="summary-row">
                            <span>Delivery Fee</span>
                            <span>₹{{ number_format($order->delivery_fee, 2) }}</span>
                        </div>
                        @if($order->wallet_discount > 0)
                            <div class="summary-row discount-row">
                                <span><i class="bi bi-wallet me-1"></i> Wallet Discount</span>
                                <span>-₹{{ number_format($order->wallet_discount, 2) }}</span>
                            </div>
                        @endif
                        <div class="summary-row total">
                            <span>Total Paid</span>
                            <span>₹{{ number_format($order->total_amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mb-4">
                    <a href="{{ route('food.my-orders') }}" class="btn btn-outline-primary px-4">
                        <i class="bi bi-receipt me-1"></i> All Orders
                    </a>
                    @if($order->status === 'Delivered')
                        <button class="btn btn-success px-4" disabled>
                            <i class="bi bi-check-circle me-1"></i> Delivered
                        </button>
                    @elseif($order->status === 'On the way')
                        <button class="btn btn-warning px-4" disabled>
                            <i class="bi bi-truck me-1"></i> On the Way
                        </button>
                    @endif
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Theme Toggle
            const toggle = document.getElementById('themeToggle');
            const savedTheme = localStorage.getItem('theme') || 'light';

            if (savedTheme === 'dark') {
                document.documentElement.setAttribute('data-bs-theme', 'dark');
                toggle.classList.add('active');
            }

            toggle?.addEventListener('click', function () {
                const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
                document.documentElement.setAttribute('data-bs-theme', isDark ? 'light' : 'dark');
                localStorage.setItem('theme', isDark ? 'light' : 'dark');
                toggle.classList.toggle('active');
            });
        });
    </script>
</body>

</html>