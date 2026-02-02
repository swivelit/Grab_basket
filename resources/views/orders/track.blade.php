<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Orders | GrabBaskets</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #fc8019;
            --primary-light: #ff9f4d;
            --secondary: #1e1e37;
            --bg-light: #f4f7fe;
            --white: #ffffff;
            --text-dark: #282c3f;
            --text-muted: #686b78;
            --success: #60b246;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            --border-radius: 20px;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
        }

        /* Navbar Customization */
        .navbar {
            background: var(--secondary) !important;
            padding: 1rem 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand img {
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            padding: 5px;
        }

        .main-content {
            padding-top: 40px;
            padding-bottom: 80px;
        }

        .header-section {
            margin-bottom: 40px;
        }

        .page-title {
            font-weight: 800;
            font-size: 2.2rem;
            color: var(--secondary);
            letter-spacing: -0.5px;
        }

        /* Order Card Styling */
        .tracking-card {
            background: var(--white);
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .tracking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        }

        .card-top {
            padding: 25px 30px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(to right, #ffffff, #fafafa);
        }

        .order-info h5 {
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--secondary);
        }

        .order-meta {
            font-size: 0.85rem;
            color: var(--text-muted);
            display: flex;
            gap: 15px;
        }

        /* Status Badges */
        .status-pill {
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed,
        .status-paid {
            background: #e3f2fd;
            color: #0d47a1;
        }

        .status-shipped {
            background: #e8f5e9;
            color: #1b5e20;
        }

        .status-delivered {
            background: #60b246;
            color: #ffffff;
        }

        .status-cancelled {
            background: #ffebee;
            color: #b71c1c;
        }

        .card-body-content {
            padding: 30px;
        }

        .product-preview {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 30px;
        }

        .product-img-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            background: #f8f9fa;
        }

        .product-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-details h6 {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .amount-tag {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--primary);
        }

        /* Premium Timeline */
        .timeline-container {
            position: relative;
            padding: 20px 0;
            margin-top: 20px;
        }

        .timeline-rail {
            position: absolute;
            top: 40px;
            left: 5%;
            right: 5%;
            height: 4px;
            background: #e9ecef;
            z-index: 1;
        }

        .timeline-rail-progress {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background: var(--primary);
            transition: width 1s ease;
        }

        .timeline-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            z-index: 2;
        }

        .timeline-step {
            text-align: center;
            width: 20%;
        }

        .step-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #fff;
            border: 4px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            font-size: 1.1rem;
            color: var(--text-muted);
        }

        .timeline-step.completed .step-icon-box {
            border-color: var(--primary);
            background: var(--primary);
            color: #fff;
            transform: scale(1.1);
        }

        .timeline-step.active .step-icon-box {
            border-color: var(--primary);
            color: var(--primary);
            animation: pulse-primary 2s infinite;
        }

        .step-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .timeline-step.active .step-label,
        .timeline-step.completed .step-label {
            color: var(--secondary);
        }

        @keyframes pulse-primary {
            0% {
                box-shadow: 0 0 0 0 rgba(252, 128, 25, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(252, 128, 25, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(252, 128, 25, 0);
            }
        }

        /* Delivery Partner Area */
        .delivery-partner-toast {
            background: #f8f9ff;
            border-radius: 12px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 30px;
            border-left: 4px solid var(--primary);
        }

        .partner-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--secondary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .partner-info h6 {
            margin: 0;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .partner-info span {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* Card Actions */
        .card-footer-actions {
            padding: 20px 30px;
            background: #fafafa;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        .btn-track {
            padding: 10px 25px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }

        .btn-track-primary {
            background: var(--primary);
            border: none;
            color: white;
        }

        .btn-track-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(252, 128, 25, 0.3);
        }

        .btn-track-outline {
            background: transparent;
            border: 2px solid #e9ecef;
            color: var(--text-muted);
        }

        .btn-track-outline:hover {
            border-color: var(--secondary);
            color: var(--secondary);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }

        .empty-illustration {
            width: 200px;
            margin-bottom: 30px;
            opacity: 0.8;
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .page-title {
                font-size: 1.8rem;
            }

            .card-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .timeline-rail {
                display: none;
            }

            .timeline-steps {
                flex-direction: column;
                gap: 20px;
                border-left: 2px solid #e9ecef;
                padding-left: 20px;
            }

            .timeline-step {
                text-align: left;
                width: 100%;
                display: flex;
                align-items: center;
                gap: 15px;
            }

            .step-icon-box {
                margin: 0;
                width: 36px;
                height: 36px;
                border-width: 3px;
            }

            .timeline-step.completed {
                border-left: 2px solid var(--primary);
                margin-left: -22px;
                padding-left: 20px;
            }

            .product-preview {
                flex-direction: column;
                align-items: flex-start;
            }

            .card-footer-actions {
                flex-direction: column;
            }

            .btn-track {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <x-back-button />
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a href="{{ url('/') }}" class="navbar-brand">
                <img src="{{ asset('asset/images/logo-image.png') }}" alt="Logo" height="40">
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                data-bs-target="#navContent">
                <span class="bi bi-list fs-2 text-light"></span>
            </button>

            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-lg-4">
                        <a href="{{ url('/') }}" class="nav-link text-white fw-semibold">Home</a>
                    </li>
                    <li class="nav-item me-lg-4">
                        <a href="{{ url('/profile') }}" class="nav-link text-white fw-semibold">Profile</a>
                    </li>
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="btn btn-warning btn-sm fw-bold px-3 rounded-pill">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container main-content">
        <div class="header-section animate__animated animate__fadeIn">
            <p class="text-primary fw-bold mb-1 text-uppercase small tracking-wider">Order Management</p>
            <h1 class="page-title">Live Tracking</h1>
        </div>

        @if($orders->isEmpty())
            <div class="empty-state animate__animated animate__zoomIn">
                <img src="https://cdni.iconscout.com/illustration/premium/thumb/empty-cart-5521508-4610092.png"
                    class="empty-illustration" alt="No orders">
                <h3>No Active Orders</h3>
                <p class="text-muted mb-4">You don't have any active orders to track right now. Let's find something
                    delicious!</p>
                <a href="{{ url('/') }}" class="btn btn-warning fw-bold px-4 py-2 rounded-pill">Explore Market</a>
            </div>
        @else
            @foreach($orders as $order)
                <div class="tracking-card animate__animated animate__fadeInUp">
                    <!-- Card Header -->
                    <div class="card-top">
                        <div class="order-info">
                            <h5>Order #{{ $order['order_number'] }}</h5>
                            <div class="order-meta">
                                <span><i class="bi bi-calendar3 me-1"></i> {{ $order['date']->format('d M, Y') }}</span>
                                <span><i class="bi bi-clock me-1"></i> {{ $order['date']->format('h:i A') }}</span>
                                <span class="d-none d-md-inline"><i class="bi bi-shop me-1"></i>
                                    {{ $order['seller_name'] }}</span>
                            </div>
                        </div>
                        <div class="status-badge-container">
                            <span class="status-pill status-{{ strtolower($order['status']) }}">
                                {{ $order['status'] }}
                            </span>
                        </div>
                    </div>

                    <div class="card-body-content">
                        <div class="row">
                            <div class="col-lg-7">
                                <!-- Product Info -->
                                <div class="product-preview">
                                    <div class="product-img-wrapper">
                                        <img src="{{ $order['product_image'] ?? 'https://via.placeholder.com/150' }}"
                                            alt="Product">
                                    </div>
                                    <div class="product-details">
                                        <h6>{{ $order['product_name'] }}</h6>
                                        <p class="text-muted small mb-2">{{ $order['type'] }}</p>
                                        <div class="amount-tag">₹{{ number_format($order['total_amount'], 2) }}</div>
                                    </div>
                                </div>

                                <!-- Timeline -->
                                <div class="timeline-container">
                                    @php
                                        $status = strtolower($order['status']);
                                        $steps = [
                                            'pending' => 1,
                                            'confirmed' => 2,
                                            'paid' => 2,
                                            'accepted' => 2,
                                            'packing' => 2,
                                            'ready' => 2,
                                            'assigned' => 2,
                                            'shipped' => 3,
                                            'picked_up' => 3,
                                            'out_for_delivery' => 3,
                                            'on_the_way' => 3,
                                            'delivered' => 4
                                        ];
                                        $currentStep = $steps[$status] ?? 1;
                                        $progress = (($currentStep - 1) / 3) * 100;
                                        if ($status === 'cancelled')
                                            $progress = 0;
                                    @endphp

                                    <div class="timeline-rail">
                                        <div class="timeline-rail-progress" style="width: {{ $progress }}%"></div>
                                    </div>

                                    <div class="timeline-steps">
                                        <div
                                            class="timeline-step {{ $currentStep >= 1 ? 'completed' : '' }} {{ $currentStep == 1 ? 'active' : '' }}">
                                            <div class="step-icon-box"><i class="bi bi-receipt"></i></div>
                                            <div class="step-label">Ordered</div>
                                        </div>
                                        <div
                                            class="timeline-step {{ $currentStep >= 2 ? 'completed' : '' }} {{ $currentStep == 2 ? 'active' : '' }}">
                                            <div class="step-icon-box"><i class="bi bi-patch-check"></i></div>
                                            <div class="step-label">Confirmed</div>
                                        </div>
                                        <div
                                            class="timeline-step {{ $currentStep >= 3 ? 'completed' : '' }} {{ $currentStep == 3 ? 'active' : '' }}">
                                            <div class="step-icon-box"><i class="bi bi-box-seam"></i></div>
                                            <div class="step-label">On the Way</div>
                                        </div>
                                        <div
                                            class="timeline-step {{ $currentStep >= 4 ? 'completed' : '' }} {{ $currentStep == 4 ? 'active' : '' }}">
                                            <div class="step-icon-box"><i class="bi bi-house-heart"></i></div>
                                            <div class="step-label">Delivered</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5 mt-4 mt-lg-0">
                                <!-- Delivery Partner Info -->
                                @if($order['delivery_partner'])
                                    <div class="delivery-partner-toast">
                                        <div class="partner-avatar">
                                            <i class="bi bi-person-fill"></i>
                                        </div>
                                        <div class="partner-info">
                                            <h6>{{ $order['delivery_partner']->name }}</h6>
                                            <span>Your Delivery Executive is on the way</span>
                                            <div class="mt-2">
                                                <a href="tel:{{ $order['delivery_partner']->phone }}"
                                                    class="btn btn-sm btn-white border rounded-pill shadow-sm py-1 px-3">
                                                    <i class="bi bi-telephone-fill me-1 text-primary"></i> Call Partner
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @elseif($order['status'] !== 'delivered' && $order['status'] !== 'cancelled')
                                    <div class="delivery-partner-toast shadow-none bg-light border-0">
                                        <div class="partner-avatar bg-secondary opacity-50">
                                            <i class="bi bi-search"></i>
                                        </div>
                                        <div class="partner-info">
                                            <h6 class="text-muted">Finding delivery partner...</h6>
                                            <span class="small">We'll update you as soon as someone is assigned.</span>
                                        </div>
                                    </div>
                                @endif

                                @if($order['tracking_number'])
                                    <div class="mt-4 p-3 border rounded-4 bg-light">
                                        <small class="text-uppercase fw-bold text-muted d-block mb-1">Tracking Number</small>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold">{{ $order['tracking_number'] }}</span>
                                            <button class="btn btn-sm btn-link text-primary p-0"
                                                onclick="copyToClipboard('{{ $order['tracking_number'] }}')">Copy</button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="card-footer-actions">
                        @if(in_array(strtolower($order['status']), ['pending', 'confirmed', 'paid']))
                            <form
                                action="{{ route('orders.cancel', ['type' => $order['type'] === 'Food Delivery' ? 'food' : 'express', 'id' => $order['id']]) }}"
                                method="POST" onsubmit="return confirm('Are you sure you want to cancel?')">
                                @csrf
                                <button type="submit" class="btn btn-track btn-track-outline border-danger text-danger">Cancel
                                    Order</button>
                            </form>
                        @endif
                        <a href="{{ route('orders.show', ['type' => $order['type'] === 'Food Delivery' ? 'food' : 'express', 'id' => $order['id']]) }}"
                            class="btn btn-track btn-track-outline">View Order Details</a>
                        @if($order['status'] === 'shipped')
                            <a href="#" class="btn btn-track btn-track-primary">Live Map Track</a>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- Footer -->
    <footer class="py-5 bg-white border-top mt-5">
        <div class="container text-center">
            <img src="{{ asset('asset/images/logo-image.png') }}" alt="Logo" height="35" class="mb-4 grayscale">
            <p class="text-muted small mb-0">© 2025 GrabBaskets India. All rights reserved.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Tracking number copied to clipboard!');
            });
        }
    </script>
</body>

</html>