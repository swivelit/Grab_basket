<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Buyer Dashboard - Grabbasket</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('asset/images/grabbasket.jpg') }}">
    
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #ff8c00;
            --primary-dark: #e67e00;
            --primary-light: #fff3e0;
            --secondary-color: #1e1e37;
            --accent-color: #6366f1;
            --bg-body: #f1f5f9;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: #1e293b;
            overflow-x: hidden;
            opacity: 0;
            animation: fadeIn 0.6s ease-out forwards;
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        /* Navbar Enhancements - COMPACT */
        .navbar {
            background-color: var(--secondary-color) !important;
            padding: 0 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            height: 100px; /* Fixed height as requested */
            position: relative;
            z-index: 1000;
            display: flex;
            align-items: center;
        }

        .navbar-brand {
            padding: 0;
            display: flex;
            align-items: center;
            height: 100%;
            overflow: visible;
        }


        .navbar-brand img {
            height: 110px; /* Large logo height */
            width: auto;
            object-fit: contain;
            transition: transform 0.2s ease;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.15));
            z-index: 1001;
        }

.navbar-brand:hover img {
    transform: scale(1.05);
}

        .search-container {
            position: relative;
            max-width: 600px;
            width: 100%;
        }

        .search-container .form-control {
            border-radius: 50px;
            padding-left: 1.25rem;
            padding-right: 3rem;
            border: none;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
            background-color: white;
            font-size: 0.875rem;
            height: 36px;
        }

        .search-container .btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            border-radius: 50px;
            padding: 0.35rem 1.25rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            font-size: 0.875rem;
            height: 30px;
        }

        .search-container .btn:hover {
            background-color: var(--primary-dark);
        }

        .nav-controls .btn {
            padding: 0.2rem 0.6rem !important;
            font-size: 0.8125rem;
            height: 32px;
            display: flex;
            align-items: center;
        }

        .nav-controls .btn i {
            font-size: 1rem;
        }

        /* Header / Hero Section */
        .dashboard-header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #2d2d5f 100%);
            color: white;
            padding: 3.5rem 0;
            position: relative;
            overflow: hidden;
            border-radius: 0 0 2rem 2rem;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 140, 0, 0.1) 0%, transparent 70%);
            z-index: 1;
        }

        .header-content {
            position: relative;
            z-index: 2;
            padding-left: 48px; /* Room for back button */
        }

        .back-btn {
            width: 36px;
            height: 36px;
            position: absolute;
            left: 0;
            top: 2px;
            z-index: 2;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            text-decoration: none;
            color: white;
        }

        .back-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
        }

        /* Custom Color Classes */
        .bg-orange-100 { background-color: var(--primary-light) !important; }
        .text-orange-600 { color: var(--primary-dark) !important; }
        .bg-red-100 { background-color: #fee2e2 !important; }
        .text-red-600 { color: #dc2626 !important; }
        .bg-green-100 { background-color: #dcfce7 !important; }
        .text-green-600 { color: #16a34a !important; }
        .bg-yellow-100 { background-color: #fef9c3 !important; }
        .text-yellow-600 { color: #ca8a04 !important; }

        /* Dashboard Cards */
        .dashboard-card {
            border: none;
            border-radius: 1.25rem;
            background: white;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            height: 100%;
            overflow: hidden;
        }

        .dashboard-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1.25rem;
            transition: var(--transition);
        }

        .dashboard-card:hover .stat-icon {
            transform: scale(1.1);
        }

        /* Quick Action Buttons */
        .action-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            background: white;
            color: #475569;
            text-decoration: none;
            transition: var(--transition);
            text-align: center;
            height: 100%;
        }

        .action-card:hover {
            border-color: var(--primary-color);
            background-color: var(--primary-light);
            color: var(--primary-dark);
            text-decoration: none;
        }

        .action-card i {
            font-size: 2rem;
            margin-bottom: 0.75rem;
        }

        /* Profile Card */
        .profile-avatar {
            width: 96px;
            height: 96px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            font-weight: 700;
            margin: 0 auto 1.5rem;
            box-shadow: 
                0 4px 12px rgba(255, 140, 0, 0.3),
                inset 0 2px 4px rgba(255, 255, 255, 0.2);
            border: 3px solid white;
        }

        /* Custom Button: Warning = Primary Brand */
        .btn-warning {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        .btn-warning:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            color: white;
        }

        /* Responsiveness */
        @media (max-width: 991.98px) {
            .navbar {
                height: 70px; /* Slimmer for tablet/mobile */
            }
            .navbar-brand {
                min-width: 140px;
            }
            .navbar-brand img {
                height: 75px;
            }
            .search-container {
                display: none !important; /* Hide search in navbar on mobile to save space */
            }
            .nav-controls .btn span {
                display: none; /* Hide text on buttons for mobile */
            }
        }

        @media (max-width: 767.98px) {
            .navbar {
                height: 64px;
                padding: 0;
            }
            .navbar .container-fluid {
                padding: 0 1rem;
            }
            .navbar-brand img {
                height: 60px;
            }
            
            .dashboard-header {
                padding: 2.5rem 0;
            }

            .header-content {
                padding-left: 48px !important;
            }

            .back-btn {
                width: 36px !important;
                height: 36px !important;
                left: 0 !important;
                top: 0 !important;
                padding-
            }

            .stat-icon {
                width: 44px;
                height: 44px;
                font-size: 1.25rem;
                margin-bottom: 0.75rem;
            }

            .dashboard-card .card-body {
                padding: 1rem !important;
            }

            .action-card {
                padding: 1rem !important;
            }
            .action-card i {
                font-size: 1.5rem;
            }
        }
        .navbar .container-fluid,
        .dashboard-header .container,
        main.container {
            max-width: 1200px;
            padding-left: 1.25rem;
            padding-right: 1.25rem;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <!-- Navbar -->
<nav class="navbar">
    <div class="container-fluid d-flex align-items-center">
     

        <!-- Logo -->
        <a href="{{ url('/') }}" class="navbar-brand d-flex align-items-center">
            <img src="{{ asset('asset/images/grabbasket.png') }}" 
                 alt="Grabbasket Logo" 
                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNDUiIGZpbGw9IiNmZjg3MDAiLz48dGV4dCB4PSI1MCIgeT0iNTAiIGZvbnQtZmFtaWx5PSJTYW5zLVNlcmlmIiBmb250LXdlaWdodD0iYm9sZCIgZm9udC1zaXplPSIzMiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0id2hpdGUiPkJSQU5EPC90ZXh0Pjwvc3ZnPg==';">
        </a>

        <!-- Search Bar -->
        <form class="search-container d-none d-lg-block" role="search">
            <input class="form-control" type="search" placeholder="Search for products, brands and more..." aria-label="Search">
            <button class="btn" type="submit">Search</button>
        </form>

        <!-- Navigation Controls -->
        <div class="nav-controls d-flex align-items-center gap-2">
            <span class="d-none d-xl-inline text-white opacity-75" style="font-size: 0.875rem;">Hello, {{ Auth::user()->name }}</span>
            
            <x-notification-bell />

            <!-- Account Dropdown -->
            <div class="dropdown">
                <button class="btn btn-outline-light btn-sm dropdown-toggle rounded-pill px-2" 
                        type="button" id="accountDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-1"></i> <span>Account</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2" aria-labelledby="accountDropdown">
                    <li class="px-3 py-2 border-bottom mb-2 d-lg-none">
                        <small class="text-muted d-block">Signed in as</small>
                        <span class="fw-bold">{{ Auth::user()->name }}</span>
                    </li>
                    <li><a class="dropdown-item py-2" href="{{ url('/profile') }}"><i class="bi bi-person me-2"></i> Profile</a></li>
                    <li><a class="dropdown-item py-2" href="{{ url('orders/track') }}"><i class="bi bi-bag-check me-2"></i> My Orders</a></li>
                    <li><a class="dropdown-item py-2" href="{{ url('/wishlist') }}"><i class="bi bi-heart me-2"></i> Wishlist</a></li>
                    <li><a class="dropdown-item py-2" href="{{ route('tracking.form') }}"><i class="bi bi-truck me-2"></i> Track Package</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                           <i class="bi bi-box-arrow-right me-2"></i> Logout</a>
                    </li>
                </ul>
            </div>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
    </div>
</nav>

    <!-- Header Section -->
    <header class="dashboard-header">
        <div class="container mt-2">
            <div class="row align-items-center">
               <div class="col-md-7 header-content">
    <!-- Back Button -->
    <a href="javascript:history.back()" class="back-btn">
        <i class="bi bi-arrow-left fs-5"></i>
    </a>

    <h1 class="display-6 fw-bold mb-1">Welcome back, {{ explode(' ', Auth::user()->name)[0] }}!</h1>
    <p class="opacity-75 mb-0" style="font-size: 0.95rem;">Experience the best shopping deals, exclusively for you.</p>
</div>
                <div class="col-md-5 d-none d-md-block text-end header-content">
                    <div class="header-stats d-inline-flex gap-4">
                        <div class="text-center">
                            <h5 class="fw-bold mb-0">{{ Auth::user()->orders()->count() }}</h5>
                            <small class="opacity-75">Orders</small>
                        </div>
                        <div class="text-center">
                            <h5 class="fw-bold mb-0">{{ Auth::user()->cartItems()->count() }}</h5>
                            <small class="opacity-75">In Cart</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container py-4 mt-2">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Quick Stats Grid -->
        <div class="row g-4 mb-4">
            <div class="col-6 col-md-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center py-3 px-3">
                        <div class="stat-icon bg-orange-100 text-orange-600 mx-auto">
                            <i class="bi bi-cart3"></i>
                        </div>
                        <h6 class="fw-semibold text-secondary mb-1">My Cart</h6>
                        <p class="h5 fw-bold mb-2">
                            {{ Auth::user()->cartItems()->count() }} 
                            <small class="text-muted fs-6">Items</small>
                        </p>
                        <a href="{{ url('/cart') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1">Explore</a>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center py-3 px-3">
                        <div class="stat-icon bg-red-100 text-red-600 mx-auto">
                            <i class="bi bi-heart"></i>
                        </div>
                        <h6 class="fw-semibold text-secondary mb-1">Wishlist</h6>
                        <p class="h5 fw-bold mb-2">
                            {{ Auth::user()->wishlists()->count() }} 
                            <small class="text-muted fs-6">Favorites</small>
                        </p>
                        <a href="{{ url('/wishlist') }}" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1">View</a>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center py-3 px-3">
                        <div class="stat-icon bg-green-100 text-green-600 mx-auto">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <h6 class="fw-semibold text-secondary mb-1">Orders</h6>
                        <p class="h5 fw-bold mb-2">
                            {{ Auth::user()->orders()->count() }} 
                            <small class="text-muted fs-6">Purchases</small>
                        </p>
                        <a href="{{ url('orders/track') }}" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1" id="orders-track-link">History</a>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card dashboard-card">
                    <div class="card-body text-center py-3 px-3">
                        <div class="stat-icon bg-yellow-100 text-yellow-600 mx-auto">
                            <i class="bi bi-bell"></i>
                        </div>
                        <h6 class="fw-semibold text-secondary mb-1">Alerts</h6>
                        @php $unreadCount = Auth::user()->notifications()->whereNull('read_at')->count(); @endphp
                        <p class="h5 fw-bold mb-2">
                            {{ $unreadCount }} 
                            <small class="text-muted fs-6">Unread</small>
                        </p>
                        <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-warning rounded-pill px-3 py-1">Updates</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Quick Actions Section -->
            <div class="col-lg-8">
                <div class="card dashboard-card">
                    <div class="card-header bg-transparent border-0 pt-3 px-4">
                        <h6 class="fw-bold mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <a href="{{ url('/') }}" class="action-card" style="padding: 1.25rem;">
                                    <i class="bi bi-shop text-primary"></i>
                                    <span class="fw-semibold">Browse Marketplace</span>
                                    <small class="text-muted d-block mt-1">Discover latest products</small>
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <a href="{{ url('/cart') }}" class="action-card" style="padding: 1.25rem;">
                                    <i class="bi bi-wallet2 text-success"></i>
                                    <span class="fw-semibold">Ready to Checkout</span>
                                    <small class="text-muted d-block mt-1">Complete your purchase</small>
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <a href="{{ url('/profile') }}" class="action-card" style="padding: 1.25rem;">
                                    <i class="bi bi-person-gear text-info"></i>
                                    <span class="fw-semibold">Account Settings</span>
                                    <small class="text-muted d-block mt-1">Manage personal info</small>
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <a href="{{ route('tracking.form') }}" class="action-card" style="padding: 1.25rem;">
                                    <i class="bi bi-geo-alt text-warning"></i>
                                    <span class="fw-semibold">Track Shipping</span>
                                    <small class="text-muted d-block mt-1">Real-time status</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Summary Section -->
            <div class="col-lg-4">
                <div class="card dashboard-card">
                    <div class="card-header bg-transparent border-0 pt-3 px-4">
                        <h6 class="fw-bold mb-0">Your Profile</h6>
                    </div>
                    <div class="card-body p-3 text-center">
                        <div class="profile-avatar" style="width: 88px; height: 88px; font-size: 2rem;">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <h6 class="fw-bold mb-1">{{ Auth::user()->name }}</h6>
                        <p class="text-muted mb-3" style="font-size: 0.9rem;">{{ Auth::user()->email }}</p>
                        
                        <div class="d-grid gap-2">
                            <a href="{{ url('/profile') }}" class="btn btn-warning rounded-pill py-1.5" style="font-size: 0.9rem;">
                                <i class="bi bi-pencil-square me-2"></i> Edit Detailed Profile
                            </a>
                            <a href="{{ route('buyer.referral') }}" class="btn btn-light rounded-pill py-1.5 border" style="font-size: 0.9rem;">
                                <i class="bi bi-gift me-2"></i> Referral Program
                            </a>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-0 py-2 text-center">
                        <small class="text-muted" style="font-size: 0.85rem;">Member since {{ Auth::user()->created_at->format('F Y') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>