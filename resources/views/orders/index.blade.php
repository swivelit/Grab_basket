<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders - GrabBaskets</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #edf1f7; /* Swiggy-like light gray background */
            font-family: 'ProximaNova', arial, 'Helvetica Neue', sans-serif;
            color: #282c3f;
        }

        
        /* Navbar (Simple for context) */
        .simple-header {
            background: #fff;
            padding: 15px 0;
            box-shadow: 0 15px 40px -20px rgba(40,44,63,0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo-text {
            font-weight: 800;
            font-size: 24px;
            color: #fc8019; /* Swiggy Orange */
            text-decoration: none;
        }
        .nav-link {
            font-weight: 600;
            color: #3d4152;
            text-decoration: none;
            margin-left: 30px;
        }

        /* Layout */
        .main-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: flex;
            gap: 30px;
        }
        
        /* Sidebar (Optional context) */
        .sidebar {
            width: 250px;
            flex-shrink: 0;
            background: #fff; /* In Swiggy this is often just part of the white bg */
            display: none; /* Hiding for now to focus on main content */
        }

        /* Content Area */
        .content-area {
            flex-grow: 1;
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: #282c3f;
            margin-bottom: 20px;
        }

        /* Order Card */
        .order-card {
            background: #fff;
            border: 1px solid #d4d5d9;
            margin-bottom: 24px;
            padding: 24px 24px 0; /* No bottom padding because actions bar is separate? No, typically inside. Reference shows padding. */
            padding-bottom: 20px;
        }

        /* Card Top */
        .card-top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .store-info {
            display: flex;
            gap: 15px;
        }

        .store-img {
            width: 50px;
            height: 50px;
            background: #f2f6fc;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px; /* Slightly rounded */
        }
        .store-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .store-details h3 {
            font-size: 16px;
            font-weight: 600;
            color: #282c3f;
            margin: 0 0 2px;
        }
        .store-details h3:hover { color: #fc8019; cursor: pointer; }

        .store-location {
            font-size: 13px;
            color: #686b78;
            margin-bottom: 4px;
        }

        .order-meta {
            font-size: 12px;
            color: #93959f; /* Lighter text for order # */
        }

        .view-details-link {
            font-size: 13px;
            font-weight: 600;
            color: #fc8019;
            text-transform: uppercase;
            text-decoration: none;
            display: inline-block;
            margin-top: 8px;
            border-bottom: 1px solid transparent;
        }
        .view-details-link:hover {
            border-bottom-color: #fc8019;
        }

        .delivery-status {
            text-align: right;
            font-size: 13px;
            color: #686b78;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .check-icon {
            color: #fff;
            background: #60b246; /* Swiggy Green */
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }

        /* Divider */
        .dotted-divider {
            border-top: 1px dashed #d4d5d9;
            margin: 0 0 15px;
        }

        /* Items Row */
        .items-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .items-list {
            color: #282c3f;
            flex: 1;
            padding-right: 20px;
        }

        .total-paid {
            font-weight: 600;
            color: #282c3f;
            white-space: nowrap;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 20px;
        }

        .btn-reorder {
            background-color: #fc8019;
            color: #fff;
            border: 1px solid #fc8019;
            padding: 11px 24px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-reorder:hover {
            background-color: #e37112;
            color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .btn-help {
            background-color: #fff;
            color: #fc8019;
            border: 1px solid #fc8019;
            padding: 11px 30px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-help:hover {
            background-color: #fff9f2;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 0;
        }
        .empty-img {
            width: 250px;
            opacity: 0.6;
            margin-bottom: 20px;
        }

        @media(max-width: 768px) {
            .card-top {
                flex-direction: column;
            }
            .delivery-status {
                margin-top: 15px;
                justify-content: flex-start; /* Align left on mobile */
                text-align: left;
            }
            .items-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>

    <!-- Simple Header -->
    <header class="simple-header">
        <div class="container header-inner">
            <a href="/" class="logo-text">
                <i class="fa-solid fa-basket-shopping"></i> GrabBaskets
            </a>
            <div class="d-flex align-items-center">
                <a href="{{ route('home') }}" class="nav-link">
                    <i class="fa-solid fa-search"></i> Search
                </a>
                <a href="#" class="nav-link">
                    <i class="fa-solid fa-user"></i> {{ Auth::user()->name }}
                </a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <!-- Main Content -->
        <div class="content-area">
            <h1 class="page-title">Past Orders</h1>

            @if($orders->count() > 0)
                @foreach($orders as $order)
                    <div class="order-card">
                        <!-- Top Section -->
                        <div class="card-top">
                            <div class="store-info">
                                <div class="store-img">
                                    @if($order['type'] === 'Food Delivery')
                                        <img src="https://media-assets.swiggy.com/swiggy/image/upload/fl_lossy,f_auto,q_auto,w_100,h_100,c_fill/e0vvulfbahjxjz6k4u77" alt="Food">
                                    @else
                                        <img src="https://media-assets.swiggy.com/swiggy/image/upload/fl_lossy,f_auto,q_auto,w_100,h_100,c_fill/instamart-assets-images/instamart_logo_v2" alt="Express">
                                    @endif
                                </div>
                                <div class="store-details">
                                    <h3>{{ $order['seller_name'] ?? 'GrabBaskets Store' }}</h3>
                                    <div class="store-location">{{ $order['type'] }}</div>
                                    <div class="order-meta">
                                        ORDER #{{ $order['order_number'] }} | {{ $order['date']->format('D, M d, Y, h:i A') }}
                                    </div>
                                    <a href="{{ route('orders.show', ['type' => $order['type'] === 'Food Delivery' ? 'food' : 'express', 'id' => $order['id']]) }}" class="view-details-link">
                                        VIEW DETAILS
                                    </a>
                                </div>
                            </div>

                            <div class="delivery-status">
                                @if($order['delivery_partner_name'])
                                    <div>
                                        <i class="fas fa-motorcycle"></i> 
                                        <strong>{{ $order['delivery_partner_name'] }}</strong><br>
                                        <small>{{ $order['delivery_partner_phone'] }}</small>
                                    </div>
                                @else
                                    <span class="text-info">Delivery partner will be assigned soon</span>
                                @endif
                                <div class="ms-3">
                                    {{ ucfirst($order['status']) }}
                                    <div class="check-icon d-inline-flex ms-1">
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="dotted-divider"></div>

                        <!-- Items & Total -->
                        <div class="items-row">
                            <div class="items-list">
                                @php
                                    $itemTexts = [];
                                    foreach($order['items'] as $item) {
                                        $itemTexts[] = $item['product_name'] . ' x ' . $item['quantity'];
                                    }
                                    echo implode(', ', $itemTexts);
                                @endphp
                            </div>
                            <div class="total-paid">
                                Total Paid: ₹ {{ number_format($order['total_amount'], 0) }}
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <!-- Helper function to reorder (dummy link for now) -->
                            <a href="/ten-min-products" class="btn-reorder">REORDER</a>
                            <a href="#" class="btn-help">HELP</a>
                        </div>
                    </div>
                @endforeach
                
                <!-- Pagination -->
                @if($orders->hasPages())
                    <div class="d-flex justify-content-center mt-5">
                        {{ $orders->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state">
                    <img src="https://res.cloudinary.com/swiggy/image/upload/fl_lossy,f_auto,q_auto/2xempty_cart_ybi7ss" class="empty-img" alt="Empty">
                    <h3>No orders found</h3>
                    <p class="text-muted">You haven't placed any orders yet.</p>
                    <a href="/" class="btn-reorder mt-3">GO TO HOME</a>
                </div>
            @endif
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
