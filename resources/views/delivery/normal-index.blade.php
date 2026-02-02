<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>GrabBaskets - Shop Groceries, Food & More | Online Delivery</title>
    <meta name="description" content="Shop groceries, fresh food, vegetables and daily essentials online at GrabBaskets. Get quick delivery with best prices.">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        /* Root Colors */
        :root {
            --primary-color: #FF6B00;
            --primary-hover: #E55A00;
            --secondary-color: #FFD700;
            --text-dark: #1C1C1C;
            --text-light: #666;
            --bg-light: #F7F7F7;
            --bg-white: #FFFFFF;
            --border-color: #E5E5E5;
            --success-color: #0C831F;
            --danger-color: #E74C3C;
            --food-color: #FF6B00;
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
        .navbar-normal {
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

        .navbar-brand {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            white-space: nowrap;
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
            box-shadow: 0 0 8px rgba(255, 107, 0, 0.1);
        }

        .nav-actions {
            display: flex;
            gap: 8px;
        }

        .nav-btn {
            background: var(--bg-light);
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        .nav-btn:hover {
            background: var(--primary-color);
            color: white;
        }

        .nav-badge {
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
            font-size: 0.65rem;
            font-weight: 700;
        }

        /* Hero Banner */
        .hero-banner {
            background: linear-gradient(135deg, #FF6B00 0%, #FF9900 100%);
            color: white;
            padding: 24px 16px;
            text-align: center;
            margin-bottom: 20px;
        }

        .hero-banner h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .hero-banner p {
            font-size: 0.95rem;
            opacity: 0.95;
            margin: 0;
        }

        .delivery-options {
            display: flex;
            gap: 12px;
            margin-top: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .delivery-option {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .delivery-option:hover,
        .delivery-option.active {
            background: rgba(255, 255, 255, 0.9);
            color: var(--primary-color);
            border-color: white;
        }

        /* Categories */
        .categories-section {
            background: white;
            padding: 16px;
            margin-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 60px;
            z-index: 99;
            overflow-x: auto;
        }

        .categories-scroll {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            white-space: nowrap;
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

        /* Section Headers */
        .section-header {
            padding: 16px 16px 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0;
        }

        .see-all-link {
            font-size: 0.9rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .see-all-link:hover {
            text-decoration: underline;
        }

        /* Products Section */
        .products-section {
            padding: 0 16px 16px 16px;
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
        .product-card {
            background: white;
            border-radius: 12px;
            padding: 12px;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .product-card:hover {
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

        /* Food Section Special */
        .food-section {
            background: linear-gradient(135deg, rgba(255, 107, 0, 0.05) 0%, rgba(255, 153, 0, 0.05) 100%);
            padding: 20px 16px;
            margin: 20px 0;
            border-radius: 12px;
            border: 2px solid var(--food-color);
            border-opacity: 0.2;
        }

        .food-section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .food-section-header h3 {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0;
            color: var(--food-color);
        }

        .food-badge {
            background: var(--food-color);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }

        /* Mobile Optimization */
        @media (max-width: 576px) {
            .navbar-content {
                gap: 8px;
            }

            .search-box {
                display: none;
            }

            .hero-banner h2 {
                font-size: 1.4rem;
            }

            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .navbar-normal {
                padding: 10px 12px;
            }

            .delivery-options {
                margin-top: 12px;
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
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar-normal">
        <div class="navbar-content">
            <a href="{{ route('home') }}" class="navbar-brand" style="text-decoration: none; color: var(--text-dark);">
                <i class="bi bi-bag-check"></i> GrabBaskets
            </a>
            
            <div class="search-box">
                <input type="text" placeholder="Search products..." class="search-input">
            </div>
            
            <div class="nav-actions" style="display: flex; gap: 10px; align-items: center;">
                @auth
                    <a href="{{ route('wishlist.index') }}" class="nav-btn" style="text-decoration: none; color: var(--text-dark);">
                        <i class="bi bi-heart"></i>
                    </a>
                    <a href="{{ route('cart.index') }}" class="nav-btn cart-btn" style="text-decoration: none; color: var(--text-dark);">
                        <i class="bi bi-bag"></i>
                        <span class="nav-badge" id="cart-count">0</span>
                    </a>
                    <a href="{{ route('profile.show') }}" style="text-decoration: none; color: var(--text-dark); font-weight: 600;">
                        <i class="bi bi-person-circle"></i>
                    </a>
                @else
                    <button class="nav-btn" onclick="window.location.href='{{ route('wishlist.index') }}'">
                        <i class="bi bi-heart"></i>
                    </button>
                    <button class="nav-btn cart-btn">
                        <i class="bi bi-bag"></i>
                        <span class="nav-badge" id="cart-count">0</span>
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

    <!-- Hero Banner with Delivery Options -->
    <div class="hero-banner">
        <h2>{{ $settings['hero_title'] ?? 'Shop Everything' }}</h2>
        <p>{{ $settings['hero_subtitle'] ?? 'Groceries, Food & More' }}</p>
        
        <div class="delivery-options">
            <div class="delivery-option active" data-mode="normal">
                📦 Standard Delivery
            </div>
            <div class="delivery-option" data-mode="10-minute">
                ⚡ 10-Minute Express
            </div>
        </div>
    </div>

    <!-- Categories -->
    <div class="categories-section">
        <div class="categories-scroll">
            <button class="category-btn active" data-category="all">All</button>
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
    <div class="section-header">
        <h3>🛒 Featured Products</h3>
        <a href="/products" class="see-all-link">See All →</a>
    </div>
    <div class="products-section">
        <div class="products-grid" id="products-grid">
            @forelse($products as $product)
                <div class="product-card" data-product-id="{{ $product->id }}">
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
                    <p class="empty-state-text">No products available</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Trending Section -->
    @if($trending && count($trending) > 0)
    <div class="section-header" style="margin-top: 20px;">
        <h3>🔥 Trending Now</h3>
        <a href="/trending" class="see-all-link">See All →</a>
    </div>
    <div class="products-section">
        <div class="products-grid">
            @foreach($trending as $product)
                <div class="product-card" data-product-id="{{ $product->id }}">
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
            @endforeach
        </div>
    </div>
    @endif

    <!-- Food Section -->
    @if($settings['show_food_section'] ?? false)
    <div class="food-section">
        <div class="food-section-header">
            <h3>🍕 Food & Restaurants</h3>
            <span class="food-badge">NEW</span>
        </div>
        <div class="products-grid">
            @forelse($foodProducts as $product)
                <div class="product-card" data-product-id="{{ $product->id }}">
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
                    <div class="empty-state-icon">🍕</div>
                    <p class="empty-state-text">Coming soon!</p>
                </div>
            @endforelse
        </div>
    </div>
    @endif

    <div style="height: 20px;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Delivery mode switcher
        document.querySelectorAll('.delivery-option').forEach(option => {
            option.addEventListener('click', function() {
                const mode = this.getAttribute('data-mode');
                if (mode === '10-minute') {
                    window.location.href = '/10-minute-delivery';
                }
                // Otherwise stay on normal delivery
                document.querySelectorAll('.delivery-option').forEach(opt => {
                    opt.classList.remove('active');
                });
                this.classList.add('active');
            });
        });

        // Add to cart functionality
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
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
                    showToast('✓ Added to cart!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to add to cart');
            });
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

        // Initialize
        updateCartCount();

        // Category filtering
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const categoryId = this.getAttribute('data-category-id');
                if (categoryId) {
                    filterByCategory(categoryId);
                }
            });
        });

        function filterByCategory(categoryId) {
            console.log('Filter by category:', categoryId);
            // Implement category filtering
        }

        // Cart button
        document.querySelector('.cart-btn').addEventListener('click', function() {
            window.location.href = '/cart';
        });
    </script>
</body>
</html>
