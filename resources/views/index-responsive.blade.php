<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>GrabBaskets - Express & Normal Delivery</title>
    <meta name="description" content="Shop groceries and essentials online. Get 10-minute delivery or standard 2-5 day delivery. Fast, reliable service with fresh products.">
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-10min: #0C831F;
            --secondary-10min: #F8CB46;
            --primary-normal: #FF6B00;
            --secondary-normal: #FFD700;
            --text-dark: #1C1C1C;
            --text-light: #666;
            --bg-light: #F7F7F7;
            --border-color: #E5E5E5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        /* ============================================
           RESPONSIVE LAYOUT CONTROL
           ============================================ */
        .mobile-view,
        .desktop-view {
            display: none;
        }

        /* Mobile: ≤768px */
        @media (max-width: 768px) {
            .mobile-view {
                display: block;
            }
            .desktop-view {
                display: none;
            }
            
            /* Hide desktop elements */
            .desktop-only {
                display: none !important;
            }
            
            /* Show mobile elements */
            .mobile-only {
                display: block !important;
            }

            body {
                padding-bottom: 80px; /* Space for mobile bottom nav */
            }
        }

        /* Desktop: >768px */
        @media (min-width: 769px) {
            .mobile-view {
                display: none;
            }
            .desktop-view {
                display: block;
            }
            
            /* Hide mobile elements */
            .mobile-only {
                display: none !important;
            }
            
            /* Show desktop elements */
            .desktop-only {
                display: block !important;
            }
        }

        /* ============================================
           NAVBAR
           ============================================ */
        .navbar-modern {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand-modern {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-10min);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .delivery-mode-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .delivery-mode-badge.ten-min {
            background: linear-gradient(135deg, var(--primary-10min) 0%, #0A6B19 100%);
            color: white;
        }

        .delivery-mode-badge.normal {
            background: linear-gradient(135deg, var(--primary-normal) 0%, #E55A00 100%);
            color: white;
        }

        /* ============================================
           10-MINUTE DELIVERY VIEW (MOBILE)
           ============================================ */
        .ten-min-header {
            background: linear-gradient(135deg, var(--primary-10min) 0%, #0A6B19 100%);
            color: white;
            padding: 16px;
            margin-bottom: 12px;
        }

        .ten-min-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0 0 8px 0;
        }

        .ten-min-timer {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            font-weight: 600;
        }

        .ten-min-categories {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 12px 0;
            margin-bottom: 16px;
            scrollbar-width: none;
        }

        .ten-min-categories::-webkit-scrollbar {
            display: none;
        }

        .category-chip {
            flex-shrink: 0;
            padding: 10px 16px;
            background: white;
            border: 2px solid transparent;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .category-chip:hover,
        .category-chip.active {
            background: var(--primary-10min);
            color: white;
            border-color: var(--primary-10min);
        }

        /* ============================================
           NORMAL DELIVERY VIEW (DESKTOP)
           ============================================ */
        .normal-header {
            background: linear-gradient(135deg, var(--primary-normal) 0%, #E55A00 100%);
            color: white;
            padding: 20px;
            margin-bottom: 16px;
            border-radius: 12px;
        }

        .normal-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 12px 0;
        }

        .normal-categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .category-card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            border-color: var(--primary-normal);
        }

        .category-emoji {
            font-size: 2.5rem;
            margin-bottom: 8px;
            display: block;
        }

        .category-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-dark);
        }

        /* ============================================
           PRODUCT GRID
           ============================================ */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
        }

        @media (min-width: 1200px) {
            .products-grid {
                grid-template-columns: repeat(6, 1fr);
            }
        }

        .product-card {
            background: white;
            border-radius: 12px;
            padding: 12px;
            position: relative;
            transition: all 0.3s;
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        .product-image {
            width: 100%;
            aspect-ratio: 1;
            object-fit: contain;
            border-radius: 8px;
            margin-bottom: 10px;
            background: var(--bg-light);
            padding: 8px;
        }

        .product-title {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 8px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            margin-top: auto;
        }

        .current-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .original-price {
            font-size: 0.85rem;
            color: var(--text-light);
            text-decoration: line-through;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 10px;
            background: white;
            border: 2px solid var(--primary-10min);
            color: var(--primary-10min);
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .add-to-cart-btn:hover {
            background: var(--primary-10min);
            color: white;
        }

        .add-to-cart-btn.normal {
            border-color: var(--primary-normal);
            color: var(--primary-normal);
        }

        .add-to-cart-btn.normal:hover {
            background: var(--primary-normal);
            color: white;
        }

        /* ============================================
           MOBILE BOTTOM NAVIGATION
           ============================================ */
        .mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -2px 16px rgba(0,0,0,0.1);
            z-index: 1000;
            padding: 12px 0 12px 0;
        }

        @media (max-width: 768px) {
            .mobile-bottom-nav {
                display: flex;
                justify-content: space-around;
            }
        }

        .nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 8px;
            color: var(--text-light);
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .nav-item:hover,
        .nav-item.active {
            color: var(--primary-10min);
        }

        .nav-item i {
            font-size: 1.5rem;
        }

        /* ============================================
           SECTION HEADERS
           ============================================ */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border-color);
        }

        .section-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .view-all-link {
            color: var(--primary-10min);
            font-weight: 600;
            text-decoration: none;
            font-size: 0.9rem;
        }

        /* ============================================
           ANIMATIONS
           ============================================ */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeInUp 0.3s ease forwards;
        }

        /* ============================================
           UTILITIES
           ============================================ */
        .container {
            padding: 0 16px;
        }

        @media (min-width: 768px) {
            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 20px;
            }
        }

        .mt-4 {
            margin-top: 24px;
        }

        .mb-4 {
            margin-bottom: 24px;
        }
    </style>
</head>

<body>
    <!-- Responsive Layout Based on Viewport -->
    
    <!-- MOBILE VIEW: 10-MINUTE DELIVERY (≤768px) -->
    <div class="mobile-view">
        <!-- Navigation -->
        <nav class="navbar-modern">
            <div class="container d-flex align-items-center justify-content-between">
                <a href="{{ route('home') }}" class="navbar-brand-modern">
                    <i class="bi bi-bag-check-fill"></i>
                    GrabBaskets
                </a>
                <div class="delivery-mode-badge ten-min">
                    <i class="bi bi-lightning-charge-fill"></i>
                    10 mins
                </div>
            </div>
        </nav>

        <!-- Header -->
        <div class="ten-min-header">
            <h1>Get Groceries in 10 Minutes!</h1>
            <div class="ten-min-timer">
                <i class="bi bi-hourglass-split"></i>
                <span>Ultra-fast delivery within 5km</span>
            </div>
        </div>

        <!-- Categories Scroll -->
        <div class="container">
            <div class="ten-min-categories">
                <div class="category-chip active">All</div>
                @foreach($ten_min_categories ?? [] as $category)
                    <div class="category-chip" onclick="this.parentElement.querySelector('.active').classList.remove('active'); this.classList.add('active');">
                        {{ $category->emoji ?? '📦' }} {{ $category->name }}
                    </div>
                @endforeach
            </div>

            <!-- Products Grid -->
            <div class="section-header">
                <h2>Featured Products</h2>
                <a href="{{ route('10-minute-delivery') }}" class="view-all-link">View All</a>
            </div>

            <div class="products-grid">
                @forelse($ten_min_products ?? [] as $product)
                    <div class="product-card animate-fade-in">
                        <img src="{{ $product->image_url ?? asset('images/no-image.png') }}" 
                             alt="{{ $product->name }}" 
                             class="product-image"
                             onerror="this.src='{{ asset('images/no-image.png') }}'">
                        
                        <div class="product-title">{{ $product->name }}</div>
                        
                        <div class="product-price">
                            <span class="current-price">₹{{ number_format($product->price, 2) }}</span>
                        </div>

                        @auth
                            <button class="add-to-cart-btn" onclick="addToCart({{ $product->id }})">
                                <i class="bi bi-cart-plus"></i> Add
                            </button>
                        @else
                            <a href="{{ route('login') }}" class="add-to-cart-btn" style="text-align: center; text-decoration: none;">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                        @endauth
                    </div>
                @empty
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px 0; color: var(--text-light);">
                        <p>No products available in your area right now. Try normal delivery!</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Mobile Bottom Navigation -->
        <div class="mobile-bottom-nav">
            <a href="{{ route('home') }}" class="nav-item active">
                <i class="bi bi-house-fill"></i>
                Home
            </a>
            <a href="{{ route('10-minute-delivery') }}" class="nav-item">
                <i class="bi bi-lightning-charge"></i>
                Express
            </a>
            <a href="{{ route('normal-delivery') }}" class="nav-item">
                <i class="bi bi-bag-check"></i>
                Shop
            </a>
            @auth
                <a href="{{ route('cart.index') }}" class="nav-item">
                    <i class="bi bi-cart3"></i>
                    Cart
                </a>
                <a href="{{ route('profile.show') }}" class="nav-item">
                    <i class="bi bi-person-circle"></i>
                    Account
                </a>
            @else
                <a href="{{ route('login') }}" class="nav-item">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Login
                </a>
            @endauth
        </div>
    </div>

    <!-- DESKTOP VIEW: NORMAL DELIVERY (>768px) -->
    <div class="desktop-view">
        <!-- Navigation -->
        <nav class="navbar-modern">
            <div class="container d-flex align-items-center justify-content-between">
                <a href="{{ route('home') }}" class="navbar-brand-modern">
                    <i class="bi bi-bag-check-fill"></i>
                    GrabBaskets
                </a>
                <div class="delivery-mode-badge normal">
                    <i class="bi bi-truck"></i>
                    2-5 Days
                </div>
                @auth
                    <a href="{{ route('cart.index') }}" class="ms-auto me-3" style="text-decoration: none; color: var(--text-dark); font-weight: 600;">
                        <i class="bi bi-cart3"></i> Cart
                    </a>
                    <a href="{{ route('profile.show') }}" style="text-decoration: none; color: var(--text-dark); font-weight: 600;">
                        <i class="bi bi-person-circle"></i> Account
                    </a>
                @else
                    <a href="{{ route('login') }}" class="ms-auto" style="text-decoration: none; color: white; background: var(--primary-normal); padding: 8px 16px; border-radius: 20px; font-weight: 600;">
                        Login
                    </a>
                @endauth
            </div>
        </nav>

        <!-- Header -->
        <div class="normal-header">
            <h1>Shop Everything You Need</h1>
            <p style="margin: 0; opacity: 0.9;">2-5 days delivery across India. Lowest prices on all products.</p>
        </div>

        <!-- Categories Grid -->
        <div class="container">
            <div class="section-header">
                <h2>Shop by Category</h2>
                <a href="{{ route('buyer.dashboard') }}" class="view-all-link">View All</a>
            </div>

            <div class="normal-categories">
                @forelse($categories ?? [] as $category)
                    <div class="category-card" onclick="window.location.href='{{ route('buyer.productsByCategory', $category->id) }}'">
                        <span class="category-emoji">{{ $category->emoji ?? '📦' }}</span>
                        <div class="category-name">{{ $category->name }}</div>
                    </div>
                @empty
                    <p style="grid-column: 1/-1; color: var(--text-light);">No categories available</p>
                @endforelse
            </div>

            <!-- Products Grid -->
            <div class="section-header">
                <h2>Featured Products</h2>
                <a href="{{ route('products.index') }}" class="view-all-link">View All</a>
            </div>

            <div class="products-grid">
                @forelse($all_products ?? [] as $product)
                    <div class="product-card animate-fade-in" onclick="window.location.href='{{ route('product.details', $product->id) }}'">
                        <img src="{{ $product->image_url ?? asset('images/no-image.png') }}" 
                             alt="{{ $product->name }}" 
                             class="product-image"
                             onerror="this.src='{{ asset('images/no-image.png') }}'">
                        
                        <div class="product-title">{{ $product->name }}</div>
                        
                        <div class="product-price">
                            @if($product->discount > 0)
                                <span class="current-price">₹{{ number_format($product->price * (1 - $product->discount / 100), 2) }}</span>
                                <span class="original-price">₹{{ number_format($product->price, 2) }}</span>
                            @else
                                <span class="current-price">₹{{ number_format($product->price, 2) }}</span>
                            @endif
                        </div>

                        @auth
                            <button class="add-to-cart-btn normal" onclick="event.stopPropagation(); addToCart({{ $product->id }})">
                                <i class="bi bi-cart-plus"></i> Add to Cart
                            </button>
                        @else
                            <a href="{{ route('login') }}" class="add-to-cart-btn normal" style="text-align: center; text-decoration: none;" onclick="event.stopPropagation();">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                        @endauth
                    </div>
                @empty
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px 0; color: var(--text-light);">
                        <p>No products available at the moment.</p>
                    </div>
                @endforelse
            </div>

            <!-- Food Section (Desktop Only) -->
            <div class="mt-4">
                <div class="section-header">
                    <h2 style="color: var(--primary-normal);">🍕 Order Food</h2>
                    <a href="{{ route('food.index') }}" class="view-all-link" style="color: var(--primary-normal);">View All</a>
                </div>
                
                <div style="background: linear-gradient(135deg, var(--primary-normal) 0%, #E55A00 100%); color: white; padding: 40px; border-radius: 16px; text-align: center;">
                    <h3 style="margin-bottom: 12px;">Order Food Online</h3>
                    <p style="opacity: 0.9; margin-bottom: 20px;">Fast delivery of your favorite meals</p>
                    <a href="{{ route('food.index') }}" class="btn btn-light" style="color: var(--primary-normal); font-weight: 600; border-radius: 20px;">
                        <i class="bi bi-cup-hot-fill"></i> Browse Restaurants
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function addToCart(productId) {
            @auth
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('cart.add') }}';
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                form.innerHTML = `
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="product_id" value="${productId}">
                    <input type="hidden" name="quantity" value="1">
                `;
                
                document.body.appendChild(form);
                form.submit();
            @else
                window.location.href = '{{ route('login') }}';
            @endauth
        }

        // Detect if user is on mobile/desktop and load appropriate content
        function initializeView() {
            const isMobile = window.innerWidth <= 768;
            
            if (isMobile) {
                // Load 10-minute delivery data
                fetch('{{ route('10-minute-delivery') }}')
                    .then(r => r.text())
                    .catch(e => console.log('10-min delivery not available'));
            } else {
                // Load normal delivery data
                fetch('{{ route('normal-delivery') }}')
                    .then(r => r.text())
                    .catch(e => console.log('Normal delivery not available'));
            }
        }

        // Initialize on load
        window.addEventListener('load', initializeView);
        
        // Reinitialize on resize
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(initializeView, 250);
        });
    </script>
</body>

</html>
