<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - GrabBaskets</title>
    
    <!-- Fonts & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <style>
        :root {
            /* Brand Colors (From Index) */
            --primary: #3C096C;
            --primary-light: #5A189A;
            --secondary: #FF6B00;
            --accent: #FFD700; /* Gold */
            
            --brand-primary: #9333ea;
            --brand-secondary: #0c831f;
            --brand-yellow: #f8cb46;
            --text-dark: #111827;
            --text-gray: #6b7280;
            --bg-body: #f9fafb;
            --surface-white: #ffffff;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-default: 16px;
        }

        body {
            background: var(--bg-body);
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* HEADER - Matches Cart Design with Navy Refinement */
        header {
            background: linear-gradient(135deg, #0f172a 0%, #1e40af 100%);
            padding: 16px 24px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.3);
            display: flex;
            align-items: center;
            justify-content: space-between;
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.6rem;
            color: #fff;
            letter-spacing: -0.5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .logo:hover {
            color: #f3e8ff;
            transform: scale(1.01);
            transition: 0.3s;
        }

        .search-container {
            flex: 1;
            margin: 0 2rem;
            max-width: 600px;
            position: relative;
        }

        .search-box {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 8px; /* Minimal border radius */
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-gray);
            border: 2px solid transparent;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .search-box:focus-within {
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.2);
        }

        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            font-size: 0.95rem;
            color: #333;
        }

        .nav-actions {
            display: flex;
            gap: 24px;
            align-items: center;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px; /* Minimal border radius */
        }

        .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        .user-menu {
            cursor: pointer;
        }

        .cart-icon {
            position: relative;
            font-size: 1.1rem; /* Reduced font size */
            color: #fff;
            text-decoration: none;
            padding: 8px 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            border-radius: 8px; /* Minimal border radius */
            gap: 6px;
            background: rgba(255, 255, 255, 0.1);
        }

        .cart-icon span:not(.cart-badge) {
            font-size: 0.9rem; /* Smaller text */
        }

        .cart-badge {
            position: absolute;
            top: -2px;
            right: 0px;
            background: #ef4444;
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 50%;
            border: 2px solid #1e40af;
        }

        /* Wishlist Content */
        .page-header {
            padding: 40px 0 20px;
        }

        .page-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 2.2rem;
            color: var(--text-dark);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-subtitle {
            color: var(--text-gray);
            font-size: 1.1rem;
        }

        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 50px;
        }

        .wishlist-card {
            background: var(--surface-white);
            border-radius: var(--radius-default);
            padding: 15px;
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            height: 100%;
        }

        .wishlist-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--brand-primary);
        }

        .img-container {
            width: 100%;
            aspect-ratio: 1;
            border-radius: 12px;
            overflow: hidden;
            background: #f8fafc;
            margin-bottom: 12px;
            position: relative;
        }

        .product-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.5s ease;
            padding: 10px;
        }

        .wishlist-card:hover .product-img {
            transform: scale(1.1);
        }

        .remove-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ef4444;
            border: none;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s;
            z-index: 2;
        }

        .remove-btn:hover {
            background: #ef4444;
            color: white;
            transform: rotate(90deg);
        }

        .category-badge {
            font-size: 0.75rem;
            color: var(--brand-primary);
            background: #f3e8ff;
            padding: 4px 10px;
            border-radius: 99px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 8px;
            max-width: fit-content;
        }

        .product-name {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.05rem;
            color: var(--text-dark);
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 2.8em;
            text-decoration: none;
        }

        .product-name:hover {
            color: var(--brand-primary);
        }

        .price-container {
            margin-top: auto;
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 15px;
        }

        .current-price {
            font-weight: 800;
            font-size: 1.25rem;
            color: var(--text-dark);
        }

        .discount-badge {
            color: var(--brand-secondary);
            font-weight: 700;
            font-size: 0.85rem;
        }

        .card-actions {
            display: flex;
            gap: 8px;
        }

        .btn-add-cart {
            flex: 1;
            background: var(--brand-secondary);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-add-cart:hover {
            background: #15803d;
            box-shadow: 0 4px 12px rgba(21, 128, 61, 0.2);
        }

        .btn-view {
            width: 42px;
            height: 42px;
            background: #f1f5f9;
            color: var(--text-dark);
            border: none;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-view:hover {
            background: #e2e8f0;
            color: var(--brand-primary);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 100px 20px;
            background: white;
            border-radius: 24px;
            border: 2px dashed var(--border-color);
            margin: 40px 0;
        }

        .empty-icon {
            font-size: 5rem;
            color: #e2e8f0;
            margin-bottom: 24px;
            animation: float 4s ease-in-out infinite;
            display: inline-block;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        .empty-state h3 {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.8rem;
            margin-bottom: 12px;
        }

        .empty-state p {
            color: var(--text-gray);
            margin-bottom: 30px;
        }

        .btn-shop {
            background: var(--brand-primary);
            color: white;
            padding: 14px 40px;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            box-shadow: 0 4px 14px rgba(147, 51, 234, 0.3);
        }

        .btn-shop:hover {
            background: #7e22ce;
            color: white;
            transform: translateY(-2px);
        }

        /* ===== FOOTER STYLES (Exactly from Index) ===== */
        .site-footer {
            background: linear-gradient(135deg, #1a0b2e 0%, #2d1b4e 100%);
            color: rgba(255, 255, 255, 0.9);
            padding: 60px 0 0;
            margin-top: 80px;
            position: relative;
            overflow: hidden;
            text-align: left; /* Ensure left alignment for footer content */
        }

        .site-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light), var(--secondary), var(--accent));
        }

        .footer-brand {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-brand i {
            color: var(--accent);
        }

        .footer-tagline {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .footer-section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), var(--secondary));
            border-radius: 2px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .footer-links a:hover {
            color: var(--accent);
            transform: translateX(5px);
        }

        .footer-links a i {
            font-size: 0.85rem;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .social-link {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .social-link:hover {
            background: var(--accent);
            color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(255, 215, 0, 0.3);
        }

        .social-link i {
            font-size: 1.2rem;
        }

        .download-badges {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .app-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .app-badge:hover {
            background: white;
            color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .app-badge i {
            font-size: 1.8rem;
        }

        .app-badge-text small {
            display: block;
            font-size: 0.7rem;
            opacity: 0.8;
        }

        .app-badge-text strong {
            font-size: 0.95rem;
            font-weight: 700;
        }

        .footer-bottom {
            margin-top: 50px;
            padding: 25px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .footer-bottom-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .copyright {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        .footer-legal-links {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
        }

        .footer-legal-links a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .footer-legal-links a:hover {
            color: var(--accent);
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
        }

        .contact-item i {
            color: var(--accent);
            font-size: 1.1rem;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .contact-item a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .contact-item a:hover {
            color: var(--accent);
        }

        @media (max-width: 768px) {
            .site-footer {
                padding: 40px 0 0;
                margin-top: 50px;
            }

            .footer-brand {
                font-size: 1.5rem;
            }

            .footer-bottom-content {
                flex-direction: column;
                text-align: center;
            }

            .footer-legal-links {
                justify-content: center;
            }

            .download-badges {
                flex-direction: column;
            }

            .app-badge {
                width: 100%;
                justify-content: center;
            }
        }

        /* Responsive */
        @media (max-width: 1400px) {
            .wishlist-grid { grid-template-columns: repeat(4, 1fr); }
        }

        @media (max-width: 992px) {
            .wishlist-grid { grid-template-columns: repeat(3, 1fr); gap: 15px; }
            .search-container { display: none; }
            .logo { font-size: 1.4rem; }
            .page-title { font-size: 1.8rem; }
        }

        @media (max-width: 768px) {
            .wishlist-grid { grid-template-columns: repeat(2, 1fr); }
            header { padding: 12px 16px; }
            .nav-actions { gap: 12px; }
            .nav-link span { display: none; }
            .cart-icon span:not(.cart-badge) { display: none; }
        }

        @media (max-width: 500px) {
            .wishlist-grid { gap: 10px; }
            .wishlist-card { padding: 10px; }
            .product-name { font-size: 0.9rem; height: 2.6em; }
            .current-price { font-size: 1.1rem; }
            .btn-add-cart { font-size: 0.8rem; padding: 8px; }
            .category-badge { font-size: 0.65rem; padding: 2px 8px; }
        }
    </style>
</head>

<body>
    <header>
        <a href="{{ url('/') }}" class="logo">
            <i class="fa-solid fa-bag-shopping" style="color: var(--brand-yellow);"></i> 
            GrabBaskets
        </a>
        
        <!-- <div class="search-container">
            <form action="{{ route('products.index') }}" method="GET" class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="query" placeholder="Search for products, brands and more..." autocomplete="off">
            </form>
        </div> -->

        <div class="nav-actions">
            <a href="{{ url('/') }}" class="nav-link">
                <i class="fa-solid fa-house"></i>
                <span>Home</span>
            </a>
            
            <div class="dropdown">
                <a href="#" class="nav-link user-menu" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-regular fa-user"></i>
                    <span>{{ Auth::user()->name }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-3" aria-labelledby="userDropdown">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold">{{ Auth::user()->name }}</div>
                        <div class="small text-muted">{{ Auth::user()->email }}</div>
                    </li>
                    <li><a class="dropdown-item py-2" href="{{ url('/profile') }} text-dark"><i class="bi bi-person me-2"></i> Profile</a></li>
                    <li><a class="dropdown-item py-2" href="{{ url('/orders/track') }} text-dark"><i class="bi bi-briefcase me-2"></i> My Orders</a></li>
                    <li><a class="dropdown-item py-2" href="{{ url('/wishlist') }} text-dark"><i class="bi bi-heart me-2"></i> Wishlist</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item py-2 text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</button>
                        </form>
                    </li>
                </ul>
            </div>

            <a href="{{ url('/cart') }}" class="cart-icon">
                <i class="fa-solid fa-cart-shopping"></i>
                <span>Cart</span>
                <span class="cart-badge" id="cartBadgeCount">{{ $wishlists->count() > 0 ? $wishlists->count() : 0 }}</span>
            </a>
        </div>
    </header>

    <div class="container flex-grow-1">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fa-solid fa-heart text-danger"></i> 
                My Wishlist
            </h1>
            <p class="page-subtitle">{{ $wishlists->count() }} items saved in your list</p>
        </div>

        @if($wishlists->isEmpty())
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fa-regular fa-heart"></i>
                </div>
                <h3>Your wishlist is empty</h3>
                <p>Seems like you haven't added anything yet. Explore our products and save your favorites!</p>
                <a href="{{ route('buyer.dashboard') }}" class="btn-shop">Explore Products</a>
            </div>
        @else
            <div class="wishlist-grid px-0">
                @foreach($wishlists as $wishlist)
                    @php $product = $wishlist->product; @endphp
                    <div class="wishlist-card" id="wishlist-item-{{ $product->id }}">
                        <button class="remove-btn remove-wishlist" data-product-id="{{ $product->id }}" title="Remove from wishlist">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                        
                        <div class="img-container">
                            <a href="{{ route('product.details', $product->id) }}">
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="product-img">
                                @else
                                    <div class="product-img d-flex align-items-center justify-content-center bg-light">
                                        <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                @endif
                            </a>
                        </div>
                        
                        <span class="category-badge">{{ $product->category->name ?? 'Product' }}</span>
                        
                        <a href="{{ route('product.details', $product->id) }}" class="product-name">
                            {{ $product->name }}
                        </a>
                        
                        <div class="price-container">
                            <span class="current-price">₹{{ number_format($product->price, 0) }}</span>
                            @if($product->discount > 0)
                                <span class="discount-badge">{{ $product->discount }}% OFF</span>
                            @endif
                        </div>
                        
                        <div class="card-actions">
                            <button class="btn-add-cart move-to-cart" data-product-id="{{ $product->id }}">
                                <i class="fa-solid fa-cart-plus"></i>
                                <span>ADD</span>
                            </button>
                            <a href="{{ route('product.details', $product->id) }}" class="btn-view" title="View Details">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <footer class="site-footer">
        <div class="container">
            <div class="row g-4">
                <!-- Brand Section -->
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand">
                        <i class="bi bi-bag-check-fill"></i>
                        GrabBaskets
                    </div>
                    <p class="footer-tagline">
                        Your trusted partner for lightning-fast grocery delivery. 
                        Fresh products delivered to your doorstep in just 10 minutes!
                    </p>
                    <div class="social-links">

    <a href="https://wa.me/918300504230" class="social-link" aria-label="WhatsApp" target="_blank" rel="noopener noreferrer">
        <i class="bi bi-whatsapp"></i>
    </a>

    <a href="https://www.facebook.com/p/Swivel-Education-61573324123476/" class="social-link" aria-label="Facebook" target="_blank"rel="noopener noreferrer">
        <i class="bi bi-facebook"></i>
    </a>

    <a href="https://www.instagram.com/grab_baskets/"class="social-link" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
        <i class="bi bi-instagram"></i>
    </a>

    <a href="https://youtube.com/@swivel-training?si=6AhKUo6pd7lpBNCw" class="social-link" aria-label="YouTube" target="_blank" rel="noopener noreferrer">
        <i class="bi bi-youtube"></i>
    </a>

    <a href="https://www.linkedin.com/in/jey-groups-2557933a3/"class="social-link" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer">
        <i class="bi bi-linkedin"></i>
    </a>

</div>

                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6">
                    <h5 class="footer-section-title">Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="{{ route('home') }}"><i class="bi bi-chevron-right"></i> Home</a></li>
                        <li><a href="/tenmins"><i class="bi bi-chevron-right"></i> Ten-Mins-Delivery</a></li>
                        <li><a href="{{ route('categories.index') }}"><i class="bi bi-chevron-right"></i> Card</a></li>
                        <li><a href="{{ route('customer.food.index') }}"><i class="bi bi-chevron-right"></i> Food Delivery</a></li>
                        <li><a href="/joinus"><i class="bi bi-chevron-right"></i> Join With Us</a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div class="col-lg-3 col-md-6">
                    <h5 class="footer-section-title">Customer Service</h5>
                    <ul class="footer-links">
                        <li><a href="#"><i class="bi bi-chevron-right"></i> Help Center</a></li>
                        <li><a href="#"><i class="bi bi-chevron-right"></i> Track Order</a></li>
                        <li><a href="#"><i class="bi bi-chevron-right"></i> Returns & Refunds</a></li>
                        <li><a href="#"><i class="bi bi-chevron-right"></i> Shipping Info</a></li>
                        <li><a href="#"><i class="bi bi-chevron-right"></i> FAQs</a></li>
                    </ul>
                </div>

                <!-- Contact & Download -->
                <div class="col-lg-3 col-md-6">
                    <h5 class="footer-section-title">Get In Touch</h5>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>DLF IT Park,<br>Mount Poonamallee Road, Porur,<br>
                        Chennai, Tamil Nadu, 600116.</span>
                        </div>
                        <div class="contact-item">
                            <i class="bi bi-telephone-fill"></i>
                            <a href="tel:+911234567890">+91-830 050 4230</a>
                        </div>
                        <div class="contact-item">
                            <i class="bi bi-envelope-fill"></i>
                            <a href="mailto:admin@swivel.co.in">admin@swivel.co.in</a>
                        </div>
                    </div>
                    <div class="download-badges mt-4">
                        <a href="#" class="app-badge">
                            <i class="bi bi-google-play"></i>
                            <div class="app-badge-text">
                                <small>GET IT ON</small>
                                <strong>Google Play</strong>
                            </div>
                        </a>
                        <a href="#" class="app-badge">
                            <i class="bi bi-apple"></i>
                            <div class="app-badge-text">
                                <small>Download on the</small>
                                <strong>App Store</strong>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <div class="copyright">
                        © 2025 GrabBaskets. All rights reserved.
                    </div>
                    <div class="footer-legal-links">
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Service</a>
                        <a href="#">Cookie Policy</a>
                        <a href="#">Sitemap</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Remove from wishlist
        document.querySelectorAll('.remove-wishlist').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const card = document.getElementById(`wishlist-item-${productId}`);

                if (confirm('Remove this item from your wishlist?')) {
                    fetch('{{ route("wishlist.remove") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ product_id: productId })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                card.style.opacity = '0';
                                card.style.transform = 'scale(0.8)';
                                setTimeout(() => {
                                    card.remove();
                                    location.reload(); // Simple way to update head title and count
                                }, 300);
                            } else {
                                alert(data.message || 'Failed to remove item');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred');
                        });
                }
            });
        });

        // Move to cart
        document.querySelectorAll('.move-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const btn = this;
                const originalContent = btn.innerHTML;
                
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                btn.disabled = true;

                fetch('{{ route("wishlist.moveToCart") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ product_id: productId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            btn.innerHTML = '<i class="fa-solid fa-check"></i>';
                            btn.style.background = '#065f46';
                            setTimeout(() => {
                                location.reload();
                            }, 800);
                        } else {
                            btn.innerHTML = originalContent;
                            btn.disabled = false;
                            alert(data.message || 'Failed to move item to cart');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        btn.innerHTML = originalContent;
                        btn.disabled = false;
                        alert('An error occurred');
                    });
            });
        });
    </script>
</body>

</html>