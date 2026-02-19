<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Buyer Dashboard - Shop Now</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        /* Footer */
        footer {
            background-color: #343a40;
        }

        /* Footer Styles */
        footer {
            background-color: #343a40;
            color: #fff;
            width: 100%;
            margin-top: auto;
        }

        footer a {
            color: #fff;
            text-decoration: none;
        }

        footer a:hover {
            color: #ddd;
        }

        .footer-main-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr 1.2fr;
            gap: 3rem;
            align-items: start;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer {
            font-size: 0.9rem;
        }

        .footer h6 {
            font-size: 0.95rem;
            margin-bottom: 1rem;
            color: #fff;
        }

        .footer a:hover {
            color: #fff;
            text-decoration: underline;
        }

        .footer .social-icons i {
            font-size: 1.3rem;
            transition: color 0.3s;
        }

        .footer .social-icons i:hover {
            color: #fff;
        }


        .dashboard-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 40px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0px 4px 15px rgba(0, 123, 255, 0.3);
        }

        .product-card {
            border-radius: 12px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            background: white;
            border: none;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.12);
        }

        .product-img {
            height: 200px;
            object-fit: contain;
            width: 100%;
        }

        .price-tag {
            font-size: 1.2rem;
            font-weight: bold;
            color: #007bff;
        }

        .discount-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff4757;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            z-index: 10;
        }

        .gift-badge {
            background: #ffa726;
            color: white;
            font-size: 0.8rem;
        }

        .quick-stats {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>

<body>
    <x-back-button />
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color:rgb(30, 30, 55);">
        <div class="container-fluid">
            <!-- Logo -->
            <a href="{{ url('/') }}" class="navbar-brand d-flex align-items-center">
                <img src="{{ asset('asset/images/logo-image.png') }}" alt="Logo" width="150" class="me-2">
            </a>

            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
                aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navbar Content -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- Search Bar -->
                <form action="{{ route('products.index') }}" method="GET" class="d-flex mx-lg-auto my-3 my-lg-0"
                    role="search" style="max-width: 600px; width: 100%;">
                    <input class="form-control me-2" type="search" name="q"
                        placeholder="Search products, brands and more..." aria-label="Search">
                    <button class="btn btn-outline-warning" type="submit">Search</button>
                </form>

                <!-- Right Side -->
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item d-none d-lg-block me-2">
                        <a href="{{ route('profile.show') }}" class="text-light small text-decoration-none">Hello,
                            {{ Auth::user()->name }}</a>
                    </li>
                    <li class="nav-item d-flex align-items-center me-2">
                        <x-notification-bell />
                    </li> <!-- Account Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="btn btn-outline-warning btn-sm dropdown-toggle d-flex align-items-center gap-1"
                            href="#" id="accountDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> My Account
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="accountDropdown"
                            style="min-width: 220px;">
                            <li><a class="dropdown-item" href="{{ url('/profile') }}"><i class="bi bi-person"></i>
                                    Profile</a></li>
                            <li><a class="dropdown-item" href="{{ url('/cart') }}"><i class="bi bi-cart"></i> Cart</a>
                            </li>
                            <li><a class="dropdown-item" href="{{ route('buyer.dashboard') }}"><i
                                        class="bi bi-shop"></i> Shop</a></li>
                            <li><a class="dropdown-item" href="{{ url('/orders/track') }}"><i
                                        class="bi bi-briefcase"></i> My Orders</a></li>
                            <li><a class="dropdown-item" href="{{ url('/wishlist') }}"><i class="bi bi-heart"></i>
                                    Wishlist</a></li>
                            <li><a class="dropdown-item" href="{{ url('/') }}"><i class="bi bi-house"></i> Dashboard</a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a href="{{ route('logout') }}"
                            class="btn btn-outline-warning btn-sm d-flex align-items-center gap-1 w-100"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            @php
                $user = Auth::user();
                $gender = $user->sex ?? 'other';
                $greeting = match ($gender) {
                    'male' => "Welcome back, Mr. {$user->name}!",
                    'female' => "Welcome back, Ms. {$user->name}!",
                    default => "Welcome back, {$user->name}!"
                };
            @endphp
            <h2>{{ $greeting }}</h2>
            <p class="mb-0">Discover amazing products and great deals!</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="p-3">
                        <i class="bi bi-bag-check display-6 text-primary"></i>
                        <h5 class="mt-2">{{ Auth::user()?->buyerOrders()->count() ?? 0 }}</h5>
                        <small class="text-muted">Total Orders</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3">
                        <i class="bi bi-heart-fill display-6 text-danger"></i>
                        <h5 class="mt-2">{{ Auth::user()?->wishlists()->count() ?? 0 }}</h5>
                        <small class="text-muted">Wishlist Items</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3">
                        <i class="bi bi-cart-fill display-6 text-success"></i>
                        <h5 class="mt-2">{{ Auth::user()?->cartItems()->count() ?? 0 }}</h5>
                        <small class="text-muted">Cart Items</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3">
                        <i class="bi bi-bell-fill display-6 text-warning"></i>
                        <h5 class="mt-2">{{ Auth::user()?->notifications()->whereNull('read_at')->count() ?? 0 }}</h5>
                        <small class="text-muted">Unread Notifications</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories -->
        <div class="mb-4">
            <h4 class="mb-3"><i class="bi bi-grid-3x3-gap"></i> Browse by Categories</h4>
            <div class="row">
                @foreach($categories as $category)
                    <div class="col-md-2 col-sm-4 col-6 mb-3">
                        <a href="{{ route('buyer.productsByCategory', $category->id) }}"
                            class="btn btn-outline-primary w-100 py-3 text-center">
                            <i class="bi bi-tag d-block mb-2"></i>
                            {{ $category->name }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Latest Products -->
        <h4 class="mb-3"><i class="bi bi-star"></i> Latest Products</h4>
        <div class="row">
            @foreach($products as $product)
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="product-card position-relative">
                        @if($product->discount > 0)
                            <span class="discount-badge">{{ $product->discount }}% OFF</span>
                        @endif

                        @if($product->image || $product->image_data)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="product-img"
                                onerror="this.src='https://via.placeholder.com/200?text=No+Image'">
                        @else
                            <div class="product-img d-flex align-items-center justify-content-center bg-light">
                                <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                            </div>
                        @endif

                        <div class="p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0 flex-grow-1">{{ $product->name }}</h6>
                                <x-wishlist-heart :product="$product" />
                            </div>

                            <p class="text-muted small mb-2">{{ Str::limit($product->description, 60) }}</p>

                            <div class="mb-2">
                                @if($product->gift_option === 'yes')
                                    <span class="badge gift-badge me-1">
                                        <i class="bi bi-gift"></i> Gift Option
                                    </span>
                                @endif
                                @if($product->delivery_charge == 0)
                                    <span class="badge bg-success">Free Delivery</span>
                                @endif
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="price-tag">₹{{ number_format($product->price, 2) }}</span>
                                    @if($product->delivery_charge > 0)
                                        <small class="text-muted d-block">+ ₹{{ $product->delivery_charge }} delivery</small>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 d-grid gap-2">
                                <form method="POST" action="{{ route('cart.add') }}">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-cart-plus"></i> Add to Cart
                                    </button>
                                </form>
                                <a href="{{ route('product.details', $product->id) }}"
                                    class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $products->links() }}
        </div>
    </div>
    <footer class="footer bg-dark text-white pt-5 pb-4 mt-5">
        <div class="container">
            <div class="row">

                <!-- About -->
                <div class="col-md-2 col-6 mb-3">
                    <h6 class="fw-bold text-uppercase">About</h6>
                    <ul class="list-unstyled small">
                        <li><a href="#" class="text-white-50 text-decoration-none">Contact Us</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">About Us</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Careers</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Grabbasket Stories</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Corporate Info</a></li>
                    </ul>
                </div>

                <!-- Group Companies -->
                <div class="col-md-2 col-6 mb-3">
                    <h6 class="fw-bold text-uppercase">Quick Links</h6>
                    <ul class="list-unstyled small">
                        <li><a href="/cart" class="text-white-50 text-decoration-none">Cart</a></li>
                        <li><a href="/wishlist" class="text-white-50 text-decoration-none">Wishlist</a></li>
                        <li><a href="/orders/track" class="text-white-50 text-decoration-none">Orders</a></li>
                    </ul>
                </div>

                <!-- Help -->
                <div class="col-md-2 col-6 mb-3">
                    <h6 class="fw-bold text-uppercase">Help</h6>
                    <ul class="list-unstyled small">
                        <li><a href="#" class="text-white-50 text-decoration-none">Payments</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Shipping</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Cancellation & Returns</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">FAQ</a></li>
                    </ul>
                </div>

                <!-- Policy -->
                <div class="col-md-2 col-6 mb-3">
                    <h6 class="fw-bold text-uppercase">Consumer Policy</h6>
                    <ul class="list-unstyled small">
                        <li><a href="#" class="text-white-50 text-decoration-none">Return Policy</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Terms of Use</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Security</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Privacy</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Sitemap</a></li>
                    </ul>
                </div>

                <!-- Address -->
                <div class="col-md-4 col-12 mb-3">
                    <h6 class="fw-bold text-uppercase">Registered Office Address</h6>
                    <p class="text-white-50 small mb-1">
                        Swivel IT and Training Institute<br>
                        Mahatma Gandhi Nagar Rd, near Annai Therasa English School,<br>
                        MRR Nagar, Palani Chettipatti,,<br>
                        Theni, 625531, TamilNadu, India.
                    </p>
                    <!-- <p class="text-white-50 small mb-0">CIN: U51109KA2012PTC066107</p> -->
                    <p class="text-white-50 small mb-0">Contact us: <a href="tel:+91 8300504230"
                            class="text-white-50 text-decoration-none">+91 8300504230</a></p>
                </div>
            </div>

            <hr class="border-secondary">

            <!-- Bottom Row -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center text-white-50 small">
                <div class="mb-2 mb-md-0">
                    © 2025 grabbaskets.com
                </div>
                <div class="social-icons">
                    <a href="#" class="text-white-50 me-3"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-white-50 me-3"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="text-white-50 me-3"><i class="bi bi-youtube"></i></a>
                    <a href="https://www.instagram.com/grab_baskets/" class="text-white-50"><i
                            class="bi bi-instagram"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>