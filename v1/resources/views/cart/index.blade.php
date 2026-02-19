<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart | GrabBaskets</title>
    <link rel="icon" type="image/png" href="{{ asset('build/assets/icon (3).png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #ff6600;
            --primary-light: #fff0e6;
            --secondary-color: #1e1e37;
            --bg-color: #f8f9fc;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.05);
            --accent-pink: #ff4d94;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: #2d3436;
            padding-bottom: 100px; /* Space for sticky bar */
        }

        /* Navbar Styling */
        .navbar {
            background-color: var(--secondary-color) !important;
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .navbar-brand img {
            height: 40px;
            filter: brightness(1.2);
        }

        .nav-link-custom {
            color: white !important;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 12px;
            transition: 0.3s;
        }

        .nav-link-custom:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Cart Content Styling */
        .cart-header {
            margin: 40px 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-title {
            font-weight: 800;
            font-size: 2rem;
            color: var(--secondary-color);
            margin: 0;
        }

        .cart-count-badge {
            background: var(--primary-light);
            color: var(--primary-color);
            padding: 4px 12px;
            border-radius: 99px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        /* Item Card Styling */
        .cart-item-card {
            background: white;
            border-radius: 24px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(0,0,0,0.02);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .cart-item-card:hover {
            transform: translateY(-5px);
        }

        .product-image-container {
            width: 120px;
            height: 120px;
            border-radius: 20px;
            overflow: hidden;
            background: #f1f2f6;
            flex-shrink: 0;
        }

        .product-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details {
            flex-grow: 1;
            padding: 0 20px;
        }

        .item-name {
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 4px;
            color: var(--secondary-color);
        }

        .item-meta {
            font-size: 0.9rem;
            color: #636e72;
            margin-bottom: 12px;
        }

        /* Quantity Controls */
        .qty-controls {
            display: flex;
            align-items: center;
            background: #f1f2f6;
            border-radius: 14px;
            padding: 4px;
            width: fit-content;
        }

        .qty-btn {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            border: none;
            background: white;
            color: var(--secondary-color);
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .qty-btn:hover {
            background: var(--primary-color);
            color: white;
        }

        .qty-value {
            padding: 0 15px;
            font-weight: 700;
            font-size: 1rem;
        }

        .price-tag {
            text-align: right;
        }

        .line-total {
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--secondary-color);
            display: block;
        }

        .unit-price {
            font-size: 0.85rem;
            color: #b2bec3;
        }

        .action-btns {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }

        .action-icon-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: none;
            background: #fff5f5;
            color: #ff4757;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            transition: 0.2s;
        }

        .action-icon-btn:hover {
            background: #ff4757;
            color: white;
        }

        .wishlist-icon-btn {
            color: #ffa502;
            background: #fffaf0;
        }

        .wishlist-icon-btn:hover {
            background: #ffa502;
            color: white;
        }

        /* Summary Card */
        .summary-card {
            background: white;
            border-radius: 28px;
            padding: 30px;
            box-shadow: var(--card-shadow);
            position: sticky;
            top: 100px;
        }

        .summary-title {
            font-weight: 800;
            font-size: 1.5rem;
            margin-bottom: 24px;
        }

        .bill-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 14px;
            font-size: 1rem;
            color: #636e72;
        }

        .bill-row.total {
            color: var(--secondary-color);
            font-weight: 800;
            font-size: 1.4rem;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #f1f2f6;
        }

        .checkout-btn {
            background: linear-gradient(135deg, var(--primary-color), #ff8c00);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            margin-top: 24px;
            box-shadow: 0 10px 20px rgba(255, 102, 0, 0.2);
            transition: 0.3s;
        }

        .checkout-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 25px rgba(255, 102, 0, 0.3);
            color: white;
        }

        /* Mobile Sticky Bar */
        .mobile-checkout-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 16px 24px;
            box-shadow: 0 -10px 30px rgba(0,0,0,0.1);
            display: none;
            z-index: 1000;
            border-radius: 24px 24px 0 0;
        }

        .mobile-total-info {
            display: flex;
            flex-direction: column;
        }

        .mobile-total-label {
            font-size: 0.75rem;
            color: #636e72;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .mobile-total-val {
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--secondary-color);
        }

        /* Empty State */
        .empty-cart-state {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-icon {
            font-size: 5rem;
            color: #d1d8e0;
            margin-bottom: 24px;
        }

        @media (max-width: 991px) {
            .cart-title { font-size: 1.6rem; }
            .summary-card { margin-top: 30px; position: static; }
        }

        @media (max-width: 768px) {
            .mobile-checkout-bar { display: flex; align-items: center; justify-content: space-between; }
            .cart-header { margin-top: 24px; }
            .cart-item-card { flex-direction: row; align-items: flex-start; padding: 16px; }
            .product-image-container { width: 80px; height: 80px; border-radius: 14px; }
            .item-details { padding: 0 12px; }
            .item-name { font-size: 1rem; }
            .action-btns { top: 12px; right: 12px; }
            .action-icon-btn { width: 30px; height: 30px; font-size: 0.9rem; }
            .price-tag { align-self: flex-end; }
            .line-total { font-size: 1.1rem; }
            .navbar-brand img { height: 32px; }
            .qty-controls { padding: 2px; }
            .qty-btn { width: 28px; height: 28px; }
            .qty-value { padding: 0 10px; font-size: 0.9rem; }
            .desktop-summary { display: none; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a href="{{ url('/') }}" class="navbar-brand">
                <img src="{{ asset('asset/images/logo-image.png') }}" alt="GrabBaskets">
            </a>

            <div class="ms-auto d-flex align-items-center gap-2">
                <!-- Mobile Account Icon -->
                <div class="dropdown d-lg-none">
                    <button class="nav-link-custom border-0 bg-transparent" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-4"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end rounded-4 border-0 shadow-lg p-2">
                        <li><a class="dropdown-item rounded-3" href="{{ url('/profile') }}">Profile</a></li>
                        <li><a class="dropdown-item rounded-3" href="{{ route('buyer.dashboard') }}">Shop</a></li>
                        <li><a class="dropdown-item rounded-3" href="{{ url('/orders/track') }}">Orders</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="dropdown-item text-danger rounded-3">Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>

                <!-- Desktop Menu -->
                <div class="collapse navbar-collapse" id="navbarContent">
                    <ul class="navbar-nav align-items-center">
                        <li class="nav-item">
                            <a class="nav-link-custom" href="{{ route('buyer.dashboard') }}">
                                <i class="bi bi-shop"></i> Continue Shopping
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link-custom dropdown-toggle" href="#" id="accountDropdown" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> {{ Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end rounded-4 border-0 shadow-lg p-2" aria-labelledby="accountDropdown">
                                <li><a class="dropdown-item rounded-3" href="{{ url('/profile') }}"><i class="bi bi-person me-2"></i> Profile</a></li>
                                <li><a class="dropdown-item rounded-3" href="{{ url('/orders/track') }}"><i class="bi bi-briefcase me-2"></i> My Orders</a></li>
                                <li><a class="dropdown-item rounded-3" href="{{ url('/wishlist') }}"><i class="bi bi-heart me-2"></i> Wishlist</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button class="dropdown-item text-danger rounded-3"><i class="bi bi-box-arrow-right me-2"></i> Logout</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        @if(!$items->count())
            <!-- EMPTY STATE -->
            <div class="empty-cart-state">
                <i class="bi bi-cart-x empty-icon"></i>
                <h3 class="fw-bold">Your cart is feeling a bit light!</h3>
                <p class="text-muted mb-4">Add some magic to it by exploring our products.</p>
                <a href="{{ route('buyer.dashboard') }}" class="btn checkout-btn px-5 w-auto">Start Shopping</a>
            </div>
        @else
            <!-- CART HEADER -->
            <div class="cart-header">
                <div>
                    <h1 class="cart-title">My Basket</h1>
                    <span class="cart-count-badge">{{ $items->count() }} {{ Str::plural('item', $items->count()) }}</span>
                </div>
                
                <form method="POST" action="{{ route('cart.clear') }}" class="d-none d-md-block">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-link text-danger text-decoration-none fw-600">
                        <i class="bi bi-trash-fill me-1"></i> Clear Entire Basket
                    </button>
                </form>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row g-4">
                <!-- ITEMS LIST -->
                <div class="col-lg-8">
                    @foreach($items as $item)
                        <div class="cart-item-card d-flex">
                            <div class="product-image-container">
                                @php
                                    $productImage = ($item->product && ($item->product->image || $item->product->image_data)) ? 
                                                   $item->product->image_url : 
                                                   'https://via.placeholder.com/150/f1f2f6/636e72?text=No+Image';
                                @endphp
                                <img src="{{ $productImage }}" alt="{{ $item->product->name ?? 'Product' }}">
                            </div>

                            <div class="item-details d-flex flex-column justify-content-between">
                                <div>
                                    <h3 class="item-name">
                                        @if($item->product)
                                            <a href="{{ route('product.details', $item->product->id) }}" class="text-decoration-none text-dark">
                                                {{ $item->product->name }}
                                            </a>
                                        @else
                                            <span class="text-muted">Product Unavailable</span>
                                        @endif
                                    </h3>
                                    <div class="item-meta">
                                        Standard Delivery • ₹{{ number_format($item->price, 0) }} / unit
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    @if($item->product)
                                        <div class="qty-controls">
                                            <button class="qty-btn" onclick="updateQty({{ $item->id }}, -1)">−</button>
                                            <span class="qty-value" id="qty-{{ $item->id }}">{{ $item->quantity }}</span>
                                            <button class="qty-btn" onclick="updateQty({{ $item->id }}, 1)">+</button>
                                        </div>
                                        
                                        <!-- Hidden update forms -->
                                        <form id="update-form-{{ $item->id }}" method="POST" action="{{ route('cart.update', $item) }}" class="d-none">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="quantity" id="input-{{ $item->id }}" value="{{ $item->quantity }}">
                                        </form>
                                    @endif

                                    <div class="price-tag">
                                        @php
                                            $lineTotal = ((float)$item->price * (int)$item->quantity) - 
                                                        (((float)$item->price * (int)$item->quantity) * ((float)$item->discount / 100)) + 
                                                        (float)$item->delivery_charge;
                                        @endphp
                                        <span class="line-total">₹{{ number_format($lineTotal, 0) }}</span>
                                        @if($item->discount > 0)
                                            <span class="text-success small fw-700">{{ $item->discount }}% OFF</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="action-btns">
                                <form method="POST" action="{{ route('cart.moveToWishlist', $item) }}">
                                    @csrf
                                    <button class="action-icon-btn wishlist-icon-btn" title="Move to Wishlist">
                                        <i class="bi bi-heart"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('cart.remove', $item) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="action-icon-btn" title="Remove Item">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- BILL SUMMARY (DESKTOP) -->
                <div class="col-lg-4 desktop-summary">
                    <div class="summary-card">
                        <h2 class="summary-title">Bill Details</h2>
                        <div class="bill-row">
                            <span>Item Total</span>
                            <span>₹{{ number_format($totals['subtotal'], 0) }}</span>
                        </div>
                        <div class="bill-row">
                            <span>Product Discount</span>
                            <span class="text-success">-₹{{ number_format($totals['discountTotal'], 0) }}</span>
                        </div>
                        <div class="bill-row">
                            <span>Delivery Partner Fee</span>
                            <span>₹{{ number_format($totals['deliveryTotal'], 0) }}</span>
                        </div>
                        
                        <div class="bill-row total">
                            <span>To Pay</span>
                            <span>₹{{ number_format($totals['total'], 0) }}</span>
                        </div>

                        <a href="{{ route('cart.checkout.page') }}" class="btn checkout-btn">
                            Proceed to Checkout <i class="bi bi-arrow-right-short fs-4"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- MOBILE STICKY BAR -->
    @if($items->count())
        <div class="mobile-checkout-bar">
            <div class="mobile-total-info">
                <span class="mobile-total-label">Grand Total</span>
                <span class="mobile-total-val">₹{{ number_format($totals['total'], 0) }}</span>
            </div>
            <a href="{{ route('cart.checkout.page') }}" class="checkout-btn w-auto py-2 px-4 mt-0">
                Checkout <i class="bi bi-chevron-right ms-1"></i>
            </a>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateQty(id, delta) {
            const span = document.getElementById('qty-' + id);
            const input = document.getElementById('input-' + id);
            const form = document.getElementById('update-form-' + id);
            
            let current = parseInt(span.innerText);
            let next = current + delta;
            
            if (next >= 1 && next <= 10) {
                span.innerText = next;
                input.value = next;
                form.submit();
            }
        }
    </script>
</body>
</html>
