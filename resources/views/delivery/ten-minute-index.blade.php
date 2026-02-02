<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>GrabBaskets - 10 Minute Express Delivery | Quick Grocery Delivery</title>
    <meta name="description" content="Get groceries delivered in 10 minutes! GrabBaskets 10-minute express delivery with fresh products from nearby shops within 5km radius.">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        /* Root Colors */
        :root {
            --primary-color: #0C831F;
            --primary-hover: #0A6B19;
            --secondary-color: #F8CB46;
            --text-dark: #1C1C1C;
            --text-light: #666;
            --bg-light: #F7F7F7;
            --bg-white: #FFFFFF;
            --border-color: #E5E5E5;
            --success-color: #0C831F;
            --danger-color: #E74C3C;
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
            line-height: 1.6;
            padding-top: 60px;
        }

        /* Navbar */
        .navbar-10min {
            background: var(--bg-white);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 12px 16px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            width: 100%;
            box-sizing: border-box;
        }

        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .delivery-mode-badge {
            background: linear-gradient(135deg, #0C831F 0%, #14A02E 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .location-display {
            font-size: 0.85rem;
            color: var(--text-light);
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .search-box {
            flex: 1;
            position: relative;
            max-width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 8px rgba(12, 131, 31, 0.1);
        }

        .cart-icon {
            background: var(--bg-light);
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
        }

        .cart-icon:hover {
            background: var(--primary-color);
            color: white;
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
        }

        /* Hero Banner */
        .hero-banner-10min {
            background: linear-gradient(135deg, #0C831F 0%, #14A02E 100%);
            color: white;
            padding: 24px 16px;
            text-align: center;
            margin-bottom: 20px;
        }

        .hero-banner-10min h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .hero-banner-10min p {
            font-size: 0.95rem;
            opacity: 0.95;
            margin: 0;
        }

        .timer-display {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-top: 12px;
            font-weight: 600;
        }

        /* Categories Section */
        .categories-section {
            background: white;
            padding: 16px;
            margin-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 60px;
            z-index: 99;
        }

        .categories-scroll {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .categories-scroll::-webkit-scrollbar {
            display: none;
        }

        .category-btn {
            flex-shrink: 0;
            padding: 10px 16px;
            background: var(--bg-light);
            border: none;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .category-btn:hover,
        .category-btn.active {
            background: var(--primary-color);
            color: white;
            transform: scale(1.05);
        }

        /* Products Grid */
        .products-section {
            padding: 16px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        @media (min-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 16px;
            }
        }

        @media (min-width: 1024px) {
            .products-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Product Card */
        .product-card-10min {
            background: white;
            border-radius: 12px;
            padding: 12px;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .product-card-10min:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border-color: var(--primary-color);
        }

        .product-image {
            width: 100%;
            aspect-ratio: 1;
            object-fit: contain;
            background: var(--bg-light);
            border-radius: 8px;
            margin-bottom: 8px;
            padding: 8px;
        }

        .product-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: var(--danger-color);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .product-name {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text-dark);
            min-height: 1.8em;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-qty {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 8px;
        }

        .product-price-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .product-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .product-original-price {
            font-size: 0.8rem;
            color: var(--text-light);
            text-decoration: line-through;
        }

        .add-to-cart-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .add-to-cart-btn:hover {
            background: var(--primary-hover);
            transform: scale(1.02);
        }

        /* Nearby Stores Section */
        .stores-section {
            padding: 16px;
            margin-top: 20px;
        }

        .stores-section h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--text-dark);
        }

        .store-card {
            background: white;
            border-radius: 12px;
            padding: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 12px;
            display: flex;
            gap: 12px;
            transition: all 0.3s ease;
        }

        .store-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .store-icon {
            width: 60px;
            height: 60px;
            background: var(--bg-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            flex-shrink: 0;
        }

        .store-info {
            flex: 1;
        }

        .store-name {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .store-distance {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .store-delivery-time {
            font-size: 0.8rem;
            color: var(--success-color);
            font-weight: 600;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 16px;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state-text {
            color: var(--text-light);
            margin-bottom: 20px;
        }

        /* Mobile Optimization */
        @media (max-width: 576px) {
            .navbar-content {
                gap: 8px;
            }

            .search-box {
                display: none;
            }

            .hero-banner-10min h2 {
                font-size: 1.4rem;
            }

            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .navbar-10min {
                padding: 10px 12px;
            }
        }

        /* Loading animation */
        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }
            100% {
                background-position: 1000px 0;
            }
        }

        .loading-card {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 1000px 100%;
            animation: shimmer 2s infinite;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar-10min">
        <div class="navbar-content">
            <a href="{{ route('home') }}" class="delivery-mode-badge" style="text-decoration: none; color: var(--text-dark);">
                <i class="bi bi-lightning-charge-fill"></i>
                10 Min
            </a>
            
            <div class="search-box">
                <input type="text" placeholder="Search products..." class="search-input">
            </div>
            
            <div style="display: flex; gap: 10px; align-items: center;">
                @auth
                    <a href="{{ route('cart.index') }}" class="cart-icon" style="text-decoration: none; color: var(--text-dark);">
                        <i class="bi bi-bag"></i>
                        <span class="cart-badge" id="cart-count">0</span>
                    </a>
                    <a href="{{ route('profile.show') }}" style="text-decoration: none; color: var(--text-dark); font-weight: 600;">
                        <i class="bi bi-person-circle"></i>
                    </a>
                @else
                    <button class="cart-icon">
                        <i class="bi bi-bag"></i>
                        <span class="cart-badge" id="cart-count">0</span>
                    </button>
                    <a href="{{ route('login') }}" style="text-decoration: none; color: white; background: var(--primary-color); padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 0.85rem;">
                        Login
                    </a>
                @endauth
                
                <!-- Delivery Partner Login -->
                <a href="{{ route('admin.login') }}" style="text-decoration: none; color: var(--primary-color); background: transparent; border: 2px solid var(--primary-color); padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; display: inline-block;">
                    Partner
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Banner -->
    <div class="hero-banner-10min">
        <h2>{{ $settings['hero_title'] ?? '10-Minute Express' }}</h2>
        <p>{{ $settings['hero_subtitle'] ?? 'Get everything you need in 10 minutes!' }}</p>
        <div class="timer-display">
            ⏱️ <span id="delivery-timer">10:00</span> minutes
        </div>
    </div>

    <!-- Categories -->
    <div class="categories-section">
        <div class="categories-scroll">
            @forelse($categories as $category)
                <button class="category-btn" data-category-id="{{ $category->id }}">
                    {{ $category->name }}
                </button>
            @empty
                <span style="color: var(--text-light);">Loading categories...</span>
            @endforelse
        </div>
    </div>

    <!-- Products Section -->
    <div class="products-section">
        <div class="products-grid" id="products-grid">
            @forelse($products as $product)
                <div class="product-card-10min" data-product-id="{{ $product->id }}">
                    @if($product->discount)
                        <div class="product-badge">
                            {{ $product->discount }}% OFF
                        </div>
                    @endif
                    
                    <img src="{{ $product->image_url ?? asset('asset/images/placeholder.png') }}" 
                         alt="{{ $product->name }}" 
                         class="product-image">
                    
                    <div class="product-name">{{ $product->name }}</div>
                    
                    @if($product->quantity)
                        <div class="product-qty">{{ $product->quantity }}</div>
                    @endif
                    
                    <div class="product-price-section">
                        <div>
                            <div class="product-price">₹{{ number_format($product->price, 0) }}</div>
                            @if($product->original_price)
                                <div class="product-original-price">₹{{ number_format($product->original_price, 0) }}</div>
                            @endif
                        </div>
                    </div>
                    
                    <button class="add-to-cart-btn" data-product-id="{{ $product->id }}">
                        <i class="bi bi-plus-circle"></i> Add
                    </button>
                </div>
            @empty
                <div class="empty-state" style="grid-column: 1/-1;">
                    <div class="empty-state-icon">📦</div>
                    <p class="empty-state-text">No products available in your area yet</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Nearby Stores Section -->
    @if($stores && count($stores) > 0)
    <div class="stores-section">
        <h3>🏪 Available Shops Nearby</h3>
        @foreach($stores as $store)
            <div class="store-card">
                <div class="store-icon">
                    {{ substr($store->name, 0, 1) }}
                </div>
                <div class="store-info">
                    <div class="store-name">{{ $store->name }}</div>
                    @if(isset($store->distance))
                        <div class="store-distance">
                            📍 {{ number_format($store->distance, 1) }} km away
                        </div>
                    @endif
                    <div class="store-delivery-time">
                        ⏱️ 10-15 minutes delivery
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Switch to Normal Delivery -->
    <div style="padding: 16px; text-align: center; border-top: 1px solid var(--border-color);">
        <p style="color: var(--text-light); margin-bottom: 12px;">Need more options including food?</p>
        <a href="/normal-delivery" class="btn btn-outline-primary" style="width: 100%; border-color: var(--primary-color); color: var(--primary-color);">
            Browse All Products
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Countdown timer
        let timeLeft = 10 * 60; // 10 minutes in seconds

        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('delivery-timer').textContent = 
                `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft > 0) {
                timeLeft--;
            } else {
                clearInterval(timerInterval);
            }
        }

        const timerInterval = setInterval(updateTimer, 1000);

        // Add to cart functionality
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                addToCart(productId);
            });
        });

        function addToCart(productId) {
            fetch('{{ route("cart.add") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartCount();
                    showToast('Added to cart!');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function updateCartCount() {
            fetch('{{ route("cart.count") }}')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cart-count').textContent = data.count || 0;
                });
        }

        function showToast(message) {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: var(--primary-color);
                color: white;
                padding: 12px 24px;
                border-radius: 8px;
                z-index: 9999;
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
        }

        // Initialize cart count
        updateCartCount();

        // Category filter
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const categoryId = this.getAttribute('data-category-id');
                // Filter products by category
                filterByCategory(categoryId);
            });
        });

        function filterByCategory(categoryId) {
            // Implement category filtering
            console.log('Filter by category:', categoryId);
        }
    </script>
</body>
</html>
