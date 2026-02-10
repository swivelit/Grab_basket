<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Track your food orders with 10-Mins Food">
    <title>My Orders | 10-Mins Food</title>

    <!-- Bootstrap 5.3.3 (latest stable) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            background: white;
            z-index: 1030;
            position: fixed;
            top: 0;
            width: 100%;
            transition: var(--transition);
        }

        [data-bs-theme="dark"] .navbar {
            background: #0f172a;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
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

        .nav-link {
            font-weight: 500;
            transition: var(--transition);
        }

        .nav-link:hover {
            color: var(--primary) !important;
        }

        .nav-link.active {
            color: var(--primary) !important;
            position: relative;
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
        }

        /* Custom Elements */
        .status-badge {
            font-size: 0.8rem;
            padding: 6px 12px;
            font-weight: 600;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        .badge-delivered { background-color: #d4edda; color: #155724; }
        .badge-cancelled { background-color: #f8d7da; color: #721c24; }
        .badge-onway { background-color: #fff3cd; color: #856404; }
        .badge-pending { background-color: #e2e3e5; color: #383d41; }

        .order-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            border: 1px solid var(--gray-200);
        }

        [data-bs-theme="dark"] .order-card {
            background: #1e293b;
            border-color: #334155;
        }

        .order-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        .order-img {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--gray-200);
        }

        .shop-box {
            background: #f8f9fa;
            border-left: 4px solid var(--primary);
            padding: 12px;
            border-radius: 0 8px 8px 0;
        }

        [data-bs-theme="dark"] .shop-box {
            background: #0f172a;
        }

        .filter-box {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid var(--gray-200);
        }

        [data-bs-theme="dark"] .filter-box {
            background: #1e293b;
            border-color: #334155;
        }

        .search-section {
            background: white;
            padding: 16px;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid var(--gray-200);
        }

        [data-bs-theme="dark"] .search-section,
        [data-bs-theme="dark"] .filter-box {
            background: #1e293b;
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        /* Dark Mode Toggle */
        .theme-toggle {
            width: 42px;
            height: 24px;
            background: #ccc;
            border-radius: 12px;
            position: relative;
            cursor: pointer;
            transition: var(--transition);
        }

        .theme-toggle::before {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: white;
            top: 3px;
            left: 3px;
            transition: var(--transition);
        }

        .theme-toggle.active {
            background: var(--primary);
        }

        .theme-toggle.active::before {
            transform: translateX(18px);
        }

        /* Empty State */
        .empty-state {
            max-width: 500px;
            margin: 4rem auto;
            text-align: center;
        }

        .empty-icon {
            font-size: 5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding-top: 64px;
            }
            .order-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 12px;
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
                <!-- Left Nav -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('customer.food.index') ? 'active' : '' }}" 
                           href="{{ route('customer.food.index') }}">
                            <i class="bi bi-house me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('food.my-orders') ? 'active' : '' }}" 
                           href="{{ route('food.my-orders') }}">
                            <i class="bi bi-receipt me-1"></i> My Orders
                        </a>
                    </li>
                </ul>

                <!-- Right Nav -->
                <ul class="navbar-nav ms-auto d-flex align-items-center">
                    @auth
                        <!-- Search (visible on desktop only) -->
                        <li class="nav-item d-none d-lg-block me-2">
                            <form class="d-flex" method="GET" action="{{ route('food.my-orders') }}">
                                <input 
                                    type="text" 
                                    name="search" 
                                    class="form-control form-control-sm" 
                                    placeholder="Search orders..." 
                                    value="{{ request('search') }}"
                                    style="max-width: 200px;"
                                >
                            </form>
                        </li>

                        <!-- Cart -->
                        <li class="nav-item me-3">
                            <a class="nav-link position-relative" href="{{ route('customer.food.cart') }}">
                                <i class="bi bi-cart fs-5"></i>
                                @php
                                    $cartCount = session('cart.items', collect())->sum('quantity');
                                @endphp
                                @if($cartCount > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        {{ $cartCount }}
                                        <span class="visually-hidden">items</span>
                                    </span>
                                @endif
                            </a>
                        </li>

                        <!-- Theme Toggle -->
                        <li class="nav-item me-3">
                            <div class="theme-toggle" id="themeToggle" title="Toggle Dark Mode"></div>
                        </li>

                        <!-- User Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle fs-4 text-muted"></i>
                                <span class="d-none d-lg-inline ms-1">{{ Str::limit(Auth::user()->name, 10) }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/profile"><i class="bi bi-person me-2"></i> Profile</a></li>
                                <li><a class="dropdown-item" href="/buyer/referral"><i class="bi bi-wallet2 me-2"></i> Wallet</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('login') }}">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Login
                            </a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row">
            <!-- Filters (Desktop) -->
            <div class="col-lg-3 mb-4">
                <div class="filter-box sticky-top" style="top: 90px;">
                    <h6 class="fw-bold mb-3 d-flex align-items-center">
                        <i class="bi bi-funnel me-2"></i> Filters
                    </h6>

                    <div class="mb-4">
                        <p class="fw-semibold mb-2 d-flex align-items-center">
                            <i class="bi bi-tag me-2"></i> Order Status
                        </p>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="status-onway" value="On the way" 
                                {{ request('status') && in_array('On the way', explode(',', request('status'))) ? 'checked' : '' }}>
                            <label class="form-check-label" for="status-onway">On the way</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="status-delivered" value="Delivered"
                                {{ request('status') && in_array('Delivered', explode(',', request('status'))) ? 'checked' : '' }}>
                            <label class="form-check-label" for="status-delivered">Delivered</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="status-cancelled" value="Cancelled"
                                {{ request('status') && in_array('Cancelled', explode(',', request('status'))) ? 'checked' : '' }}>
                            <label class="form-check-label" for="status-cancelled">Cancelled</label>
                        </div>
                    </div>

                    <button class="btn btn-outline-primary w-100" id="applyFilters">Apply Filters</button>
                </div>
            </div>

            <!-- Orders List -->
            <div class="col-lg-9">
                <!-- Mobile Search -->
                <div class="d-lg-none mb-4">
                    <div class="search-section">
                        <div class="input-group">
                            <input 
                                type="text" 
                                class="form-control" 
                                id="mobileSearch" 
                                placeholder="Search by Order ID or Food" 
                                value="{{ request('search') }}"
                            >
                            <button class="btn btn-primary" id="mobileSearchBtn">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                @if($orders->count() > 0)
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-receipt-cutoff me-2"></i>
                            {{ $orders->count() }} Order{{ $orders->count() === 1 ? '' : 's' }} Found
                        </h5>
                        <small class="text-muted">
                            Sorted: Newest First
                        </small>
                    </div>

                    @foreach($orders as $order)
                        <div class="order-card animate-fade-in-up">
                            <!-- Order Header -->
                            <div class="p-4 border-bottom d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">
                                        Order #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
                                    </h6>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar me-1"></i>
                                        {{ $order->created_at->format('M d, Y') }} • 
                                        <i class="bi bi-clock me-1"></i>
                                        {{ $order->created_at->format('h:i A') }}
                                    </small>
                                </div>
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

                            <!-- Shop Info -->
                            <div class="shop-box mb-3 mx-4 mt-3">
                                <div class="fw-bold text-dark d-flex align-items-center">
                                    <i class="bi bi-shop me-2"></i>
                                    {{ $order->shop_name ?? 'Shop Name' }}
                                </div>
                                <div class="small text-muted d-flex align-items-start mt-1">
                                    <i class="bi bi-geo-alt me-2"></i>
                                    {{ Str::limit($order->shop_address ?? 'Address not available', 40) }}
                                </div>
                            </div>

                            <!-- Delivery Partner Info -->
                            @if($order->deliveryPartner)
                            <div class="shop-box mb-3 mx-4 mt-3 border-left-success" style="border-left-color: #28a745; background-color: #f0fff4;">
                                <div class="fw-bold text-dark d-flex align-items-center">
                                    <i class="bi bi-person-badge me-2"></i>
                                    Delivery Partner: {{ $order->deliveryPartner->name }}
                                </div>
                                <div class="small text-muted mt-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="bi bi-telephone me-2"></i> {{ $order->deliveryPartner->phone }}
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-bicycle me-2"></i> {{ $order->deliveryPartner->vehicle_number ?? 'Vehicle Not Listed' }}
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Items Preview -->
                            <div class="px-4 pb-3">
                                @foreach($order->items->take(2) as $item)
                                    <div class="d-flex align-items-center mb-3">
                                        <img 
                                            src="{{ $item->image ?? 'https://via.placeholder.com/72?text=Food' }}" 
                                            class="order-img me-3"
                                            alt="{{ $item->food_name }}"
                                            loading="lazy"
                                        >
                                        <div>
                                            <h6 class="mb-0" style="font-size: 1rem;">{{ $item->food_name }}</h6>
                                            <small class="text-muted d-block">{{ $item->category ?? '–' }}</small>
                                            <div class="mt-1">
                                                <strong>₹{{ number_format($item->price, 2) }}</strong>
                                                @if($item->quantity > 1)
                                                    <span class="text-muted ms-2">× {{ $item->quantity }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @if($order->items->count() > 2)
                                    <small class="text-muted d-block mb-2">
                                        +{{ $order->items->count() - 2 }} more item{{ $order->items->count() - 2 === 1 ? '' : 's' }}
                                    </small>
                                @endif

                                <!-- Footer -->
                                <div class="d-flex justify-content-between align-items-center pt-2 mt-3 border-top">
                                    <div>
                                        <small class="text-muted">
                                            Total: <strong class="text-primary">₹{{ number_format($order->total_amount, 2) }}</strong>
                                        </small>
                                    </div>
                                    <a href="{{ route('food.order.details', $order->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="text-center text-muted my-4 py-3">
                        <small class="d-inline-flex align-items-center">
                            <i class="bi bi-infinity me-1"></i> End of Orders
                        </small>
                    </div>

                @else
                    <!-- Empty State -->
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="bi bi-receipt-x"></i>
                        </div>
                        <h4 class="fw-bold">No Orders Yet</h4>
                        <p class="text-muted mb-4">
                            You haven’t placed any orders. Start exploring delicious meals — delivery in 10 minutes!
                        </p>
                        <a href="{{ route('customer.food.index') }}" class="btn btn-primary px-4 py-2">
                            <i class="bi bi-bag-plus me-2"></i> Browse Food
                        </a>
                    </div>
                @endif
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

            toggle.addEventListener('click', function() {
                const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
                document.documentElement.setAttribute('data-bs-theme', isDark ? 'light' : 'dark');
                localStorage.setItem('theme', isDark ? 'light' : 'dark');
                toggle.classList.toggle('active');
            });

            // Checkbox Filters
            const checkboxes = document.querySelectorAll('.form-check-input');
            const applyBtn = document.getElementById('applyFilters');

            applyBtn?.addEventListener('click', function() {
                const selected = Array.from(checkboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);
                
                const url = new URL(window.location);
                url.searchParams.delete('status');
                if (selected.length > 0) {
                    url.searchParams.set('status', selected.join(','));
                }
                window.location.href = url.toString();
            });

            // Mobile Search
            const mobileSearch = document.getElementById('mobileSearch');
            const mobileSearchBtn = document.getElementById('mobileSearchBtn');

            mobileSearchBtn?.addEventListener('click', function() {
                const query = mobileSearch.value.trim();
                const url = new URL(window.location);
                if (query) {
                    url.searchParams.set('search', query);
                } else {
                    url.searchParams.delete('search');
                }
                window.location.href = url.toString();
            });

            mobileSearch?.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') mobileSearchBtn.click();
            });
        });
    </script>
</body>
</html>