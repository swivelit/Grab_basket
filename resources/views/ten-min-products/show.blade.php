<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product->name }} - GrabBasket</title>
    
    <!-- Fonts & Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --brand-primary: #9333ea;     /* Vibrant Purple */
            --brand-secondary: #0c831f;   /* Green for Actions */
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--bg-body); color: var(--text-dark); font-family: 'Inter', sans-serif; padding-bottom: 80px; -webkit-font-smoothing: antialiased; }

        /* HEADER - Glassmorphism & Gradient */
        header {
            background: linear-gradient(135deg, rgba(109, 40, 217, 0.95), rgba(147, 51, 234, 0.95));
            padding: 16px 24px;
            position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 4px 20px rgba(109, 40, 217, 0.2);
            display: flex; align-items: center; justify-content: space-between;
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .logo { 
            font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.6rem; color: #fff; 
            letter-spacing: -0.5px; text-decoration: none; display: flex; align-items: center; gap: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .logo:hover { color: #f3e8ff; transform: scale(1.01); transition: 0.3s; }

        .search-container { flex: 1; margin: 0 2rem; max-width: 600px; position: relative; }
        .search-box {
            background: rgba(255, 255, 255, 0.95); border-radius: 99px; padding: 12px 24px;
            display: flex; align-items: center; gap: 12px; color: var(--text-gray); 
            border: 2px solid transparent; transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .search-box:focus-within { border-color: rgba(255,255,255,0.4); box-shadow: 0 0 0 4px rgba(255,255,255,0.2); }
        .search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 0.95rem; color: #333; }

        .nav-actions { display: flex; gap: 24px; align-items: center; }
        .nav-link { 
            color: rgba(255,255,255,0.9); text-decoration: none; font-weight: 500; font-size: 0.95rem; 
            transition: all 0.3s ease; display: flex; align-items: center; gap: 6px;
            padding: 6px 10px; border-radius: 20px;
        }
        .nav-link:hover { color: #fff; background: rgba(255,255,255,0.1); }
        
        .user-menu { 
            background: rgba(255,255,255,0.2); padding: 6px 10px; border-radius: 20px; 
            color: #fff; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; 
            cursor: default; transition: all 0.3s ease; gap: 6px;
        }
        .user-menu:hover { background: rgba(255,255,255,0.3); }
        
        .cart-icon { 
            position: relative; font-size: 1.3rem; color: #fff; text-decoration: none; 
            padding: 6px 10px; transition: all 0.3s ease; display: flex; align-items: center; border-radius: 20px; gap: 4px;
        }
        .cart-icon:hover { background: rgba(255,255,255,0.1); }
        .cart-badge {
            position: absolute; top: -2px; right: 0px; background: #ef4444; color: white;
            font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 50%; 
            display: none; border: 2px solid #9333ea;
        }
        
        .nav-text { display: inline-block; } /* Visible by default on desktop for Home/User */
        .cart-icon .nav-text { display: none; } /* Hide 'Cart' text on Desktop */

        /* MAIN LAYOUT */
        .main-wrapper {
            max-width: 1280px; margin: 3rem auto; padding: 0 1.5rem;
            display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: start;
        }
        /* LEFT: IMAGE */
        .left-column { display: flex; flex-direction: column; gap: 1.25rem; }
        .product-image-container {
            background: var(--surface-white); border-radius: var(--radius-lg); 
            border: 1px solid var(--border-color);
            height: 420px; display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden;
            box-shadow: var(--shadow-lg);
        }
        .product-img { max-width: 90%; max-height: 90%; object-fit: contain; transition: transform 0.5s cubic-bezier(0.2, 0.8, 0.2, 1); }
        .product-img:hover { transform: scale(1.08); }

        /* RIGHT: INFO */
        .product-info { display: flex; flex-direction: column; gap: 0.85rem; }
        
        .breadcrumb { font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.25rem; }
        .breadcrumb a { color: var(--brand-primary); text-decoration: none; font-weight: 500; }
        
        .product-title { 
            font-family: 'Outfit', sans-serif; 
            font-size: clamp(1.5rem, 4vw, 2.25rem); /* Fluid Typography */
            font-weight: 700; 
            line-height: 1.2; letter-spacing: -0.02em; color: var(--text-dark); 
        }
        
        .time-badge {
            background: #ecfccb; border: 1px solid #d9f99d; border-radius: 8px; color: #4d7c0f;
            padding: 8px 12px; display: inline-flex; align-items: center; gap: 8px; 
            font-size: 0.8rem; font-weight: 700; width: fit-content; margin-top: 0.5rem;
        }
        .time-badge i { font-size: 1rem; color: #65a30d; }

        .price-section { 
            margin-top: 1rem; border-top: 1px dashed var(--border-color); padding-top: 1rem; 
        }
        .unit-label { font-size: 0.9rem; color: var(--text-gray); margin-bottom: 0.5rem; font-weight: 500; }
        
        .price-row { display: flex; align-items: center; justify-content: space-between; }
        .price-display { display: flex; align-items: baseline; gap: 12px; }
        .current-price { font-family: 'Outfit', sans-serif; font-size: 2.5rem; font-weight: 700; color: var(--text-dark); }
        .mrp-price { font-size: 1.1rem; color: var(--text-gray); text-decoration: line-through; }
        .tax-text { font-size: 0.75rem; color: #9ca3af; display: block; margin-top: 4px; }

        /* ACTION BUTTONS */
        .action-actions { display: flex; align-items: center; gap: 16px; min-width: 180px; justify-content: flex-end; }
        
        .add-btn {
            background: var(--surface-white);
            border: 2px solid var(--brand-secondary);
            color: var(--brand-secondary);
            padding: 14px 40px;
            font-size: 1.1rem;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px rgba(12, 131, 31, 0.05);
            letter-spacing: 0.5px;
        }
        .add-btn:hover { 
            background: var(--brand-secondary); color: #fff; 
            box-shadow: 0 8px 16px rgba(12, 131, 31, 0.25); transform: translateY(-2px);
        }
        .add-btn:disabled { border-color: #e5e7eb; color: #9ca3af; cursor: not-allowed; background: #f3f4f6; transform: none; box-shadow: none; }

        /* QTY CONTROL */
        .qty-control {
            background: var(--brand-secondary);
            color: #fff;
            padding: 8px 16px;
            border-radius: 12px;
            display: none; 
            align-items: center; flex: 1;
            gap: 16px;
            box-shadow: 0 4px 12px rgba(12, 131, 31, 0.3);
            justify-content: space-between;
            min-height: 50px;
        }
        .qty-btn-action { background: rgba(255,255,255,0.1); border-radius: 6px; width: 32px; height: 32px; border: none; color: #fff; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s;}
        .qty-btn-action:hover { background: rgba(255,255,255,0.3); }
        .qty-text { font-weight: 700; font-size: 1.2rem; min-width: 24px; text-align: center; font-family: 'Outfit', sans-serif; }

        /* SELLER & DETAILS */
        .features-section { margin-top: 3rem; }
        .features-title { font-weight: 700; font-size: 1.1rem; margin-bottom: 1.2rem; color: var(--text-dark); }
        .feature-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); /* Flexible Grid */
            gap: 1rem; margin-bottom: 2rem; 
        }
        .feature-card { 
            background: var(--surface-white); padding: 1.5rem; border-radius: 16px; 
            border: 1px solid var(--border-color); text-align: center; 
            transition: all 0.3s ease; box-shadow: var(--shadow-sm);
        }
        .feature-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: rgba(147, 51, 234, 0.2); }
        .feature-icon-box { 
            width: 50px; height: 50px; background: #f3e8ff; border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;
            color: var(--brand-primary); font-size: 1.25rem;
        }
        .feature-text { font-size: 0.9rem; font-weight: 600; color: var(--text-dark); }

        .info-card { 
            padding: 1.5rem; background: var(--surface-white); border-radius: 16px; 
            border: 1px solid var(--border-color); margin-top: 1rem; 
            box-shadow: var(--shadow-sm);
        }
        .info-card strong { color: var(--text-dark); display: block; margin-bottom: 6px; font-size: 1rem; font-family: 'Outfit', sans-serif; }
        .info-text { font-size: 0.95rem; color: var(--text-gray); line-height: 1.6; }

        /* MOBILE STICKY BAR */
        .mobile-bar { display: none; }

        @media (max-width: 900px) {
            .main-wrapper { grid-template-columns: 1fr; gap: 1.5rem; margin: 1rem auto; padding: 0 12px; } /* Tighter mobile layout */
            .product-image-container { height: auto; aspect-ratio: 1/1; max-height: 35vh; } /* Increased height slightly for visibility */
            .header-search { display: none; } 
            
            /* Mobile Navbar - Reveal Strategy */
            .nav-actions { gap: 8px; }
            .nav-text { 
                max-width: 0; opacity: 0; overflow: hidden; white-space: nowrap; transition: all 0.3s ease; font-size: 0.8rem;
            }
            /* Restore Cart text display for mobile trigger */
            .cart-icon .nav-text { display: inline-block; }
            
            .nav-link:hover .nav-text, 
            .user-menu:hover .nav-text,
            .cart-icon:hover .nav-text {
                max-width: 100px; opacity: 1; margin-left: 4px;
            }
             /* Ensure circular and centered icons on mobile default */
             .user-menu, .nav-link, .cart-icon { 
                 padding: 0; 
                 width: 36px; height: 36px; 
                 display: flex; align-items: center; justify-content: center; 
                 border-radius: 50%;
             }
             /* On hover these will expand width naturally if needed or keeps circle if text is separate? 
                Actually flex container with width fixed might clip text. Use min-width or auto.
             */
             .user-menu:hover, .nav-link:hover, .cart-icon:hover {
                 width: auto; padding: 0 12px; border-radius: 20px;
             } 
            .product-title { font-size: 1.75rem; }
            
            .mobile-bar {
                display: flex; position: fixed; bottom: 0; left: 0; right: 0;
                background: var(--surface-white); padding: 16px 20px; 
                box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
                justify-content: space-between; align-items: center; z-index: 1000;
                border-radius: 20px 20px 0 0;
            }
            .mobile-price { font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.4rem; color: var(--text-dark); }
            
            /* Mobile Buttons */
             .add-btn, .qty-control { margin: 0; padding: 12px 28px; font-size: 1rem; width: auto; min-width: 140px; }
             .action-actions { display: block; } /* Reset */

            body { padding-bottom: 120px; }
        }

        /* Small Mobile Devices */
        /* Small Mobile Devices (iPhone SE, etc) */
        @media (max-width: 400px) {
            header { padding: 12px 12px; }
            .logo { font-size: 1.25rem; }
            .nav-actions { gap: 6px; }
            
            /* Navbar icon tweaks for tight spaces */
            .user-menu, .nav-link, .cart-icon { width: 32px; height: 32px; }
            .user-menu:hover, .nav-link:hover, .cart-icon:hover { padding: 0 8px; }
            .nav-link:hover .nav-text, 
            .user-menu:hover .nav-text,
            .cart-icon:hover .nav-text {
                font-size: 0.75rem; max-width: 80px;
            }

            .product-title { font-size: 1.4rem; line-height: 1.3; }
            .product-image-container { max-height: 40vh; } 
            .feature-grid { grid-template-columns: 1fr; gap: 1rem; }
            
            .add-btn, .qty-control { padding: 10px 16px; font-size: 0.95rem; min-width: 100px; }
            .mobile-price { font-size: 1.25rem; }
            .mobile-bar { padding: 12px 16px; }
        }

        /* Very Small Screens (Galaxy Fold, Old Devices) */
        @media (max-width: 350px) {
            .logo { font-size: 1.1rem; gap: 5px; }
            .nav-actions { gap: 2px; }
            
            .product-title { font-size: 1.25rem; }
            .current-price { font-size: 2rem; }
            
            .mobile-bar { padding: 10px 12px; }
            .mobile-bar .add-btn { padding: 8px 20px; min-width: auto; font-size: 0.9rem; }
            .mobile-bar .add-btn { padding: 8px 20px; min-width: auto; font-size: 0.9rem; }
            .mobile-price { font-size: 1.1rem; }
        }

        /* Ultra Small (Active refinement for 300px) */
        @media (max-width: 320px) {
            .logo { font-size: 1rem; } 
            .logo i { font-size: 1.1rem; } 
            .nav-actions { gap: 0; }
            
            /* Crucial overflow fix */
            .main-wrapper, .product-info { padding-left: 8px; padding-right: 8px; overflow-x: hidden; }
            .add-btn, .qty-control { width: 100%; min-width: 0; }
        }
    </style>
    <style>
        /* Global safe-guard against horizontal scroll */
        html, body { max-width: 100%; overflow-x: hidden; }
    </style>
    </style>
</head>
<body>

    <!-- HEADER -->
    <!-- HEADER -->
    <header>
        <a href="/" class="logo"><i class="fa-solid fa-bag-shopping" style="color: var(--brand-yellow);"></i> GrabBaskets</a>
        
        <div class="search-container header-search">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass text-secondary"></i>
                <input type="text" placeholder="Search for 'milk', 'chips', 'bread'..." />
            </div>
        </div>

        <div class="nav-actions">
            <a href="/" class="nav-link"><i class="fa-solid fa-house"></i> <span class="nav-text">Home</span></a>
            
            <!-- Auth Logic -->
            @auth
                <div class="user-menu">
                    <i class="fa-regular fa-user"></i> <span class="nav-text">{{ auth()->user()->name }}</span>
                </div>
            @else
                <a href="{{ route('login') }}" class="nav-link"><i class="fa-solid fa-arrow-right-to-bracket"></i> <span class="nav-text">Login</span></a>
            @endauth

            <a href="{{ route('tenmin.cart.view') }}" class="cart-icon">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="nav-text">Cart</span>
                <span class="cart-badge" id="badge">0</span>
            </a>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <div class="main-wrapper">
        
        <!-- LEFT -->
        <!-- LEFT -->
        <div class="left-column">
            <div class="product-image-container">
                <img id="productImage" src="{{ $product->image_url ?? 'https://via.placeholder.com/600' }}" class="product-img" alt="{{ $product->name }}">
            </div>

            @if($product->description)
            <div class="info-card">
                <strong>Product Details</strong>
                <p class="info-text">{{ $product->description }}</p>
            </div>
            @endif
        </div>

        <!-- RIGHT -->
        <div class="product-info">
            <div class="breadcrumb">
                <a href="/">Home</a> <span class="mx-1">/</span> 
                {{ $product->subcategory ? $product->subcategory->name : 'Products' }} <span class="mx-1">/</span> 
                <span style="color: var(--text-dark);">{{ $product->name }}</span>
            </div>

            <h1 class="product-title">{{ $product->name }}</h1>
            
            <div class="time-badge">
                <i class="fa-solid fa-bolt"></i> 10 MINS SUPERFAST
            </div>

            <div class="price-section">
                <div class="unit-label">1 unit</div> 
                
                <div class="price-row">
                    <div>
                        <div class="price-display">
                            <span class="current-price price-btn">₹{{ number_format($product->price, 0) }}</span>
                            @if($product->discount > 0)
                                <span class="mrp-price">₹{{ number_format($product->price * 1.2, 0) }}</span>
                            @endif
                        </div>
                        <span class="tax-text">(Inclusive of all taxes)</span>
                    </div>

                    <!-- DESKTOP ACTIONS -->
                    <div class="action-actions d-none d-md-block">
                        <button class="add-btn" id="addBtn" @if($product->stock <= 0) disabled @endif>
                            {{ $product->stock > 0 ? 'ADD' : 'OUT OF STOCK' }}
                        </button>
                        
                        <div class="qty-control" id="qtyBox">
                            <button class="qty-btn-action" id="minus"><i class="fa-solid fa-minus"></i></button>
                            <span class="qty-text" id="qty">1</span>
                            <button class="qty-btn-action" id="plus"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="features-section">
                <h3 class="features-title">Why shop from GrabBaskets?</h3>
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon-box"><i class="fa-solid fa-truck-fast"></i></div>
                        <div class="feature-text">Superfast Delivery</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon-box"><i class="fa-solid fa-tag"></i></div>
                        <div class="feature-text">Best Prices & Offers</div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon-box"><i class="fa-solid fa-shield-cat"></i></div>
                        <div class="feature-text">Genuine Products</div>
                    </div>
                </div>
            </div>



            <div class="info-card">
                <strong>Seller & Support</strong>
                
                <div style="margin-bottom:12px;">
                     <span style="background:#fef2f2; color:#dc2626; border:1px solid #fee2e2; padding:4px 8px; border-radius:6px; font-size:0.75rem; font-weight:600; display:inline-block; margin-right:6px;"><i class="fa-solid fa-ban"></i> No Return</span>
                     <span style="background:#f0fdf4; color:#16a34a; border:1px solid #dcfce7; padding:4px 8px; border-radius:6px; font-size:0.75rem; font-weight:600; display:inline-block;"><i class="fa-solid fa-gauge-high"></i> Fast Delivery</span>
                </div>

                <div style="background:#f9fafb; padding:10px; border-radius:8px; margin-bottom:12px;">
                    <div style="font-weight:700; font-size:0.85rem; margin-bottom:2px;">Customer Care</div>
                    <div style="font-size:0.8rem; color:#555;">In case of any issue, contact us</div>
                    <div style="font-size:0.85rem; font-weight:600; margin-top:2px;">
                        <i class="fa-solid fa-envelope" style="color:#9ca3af; margin-right:4px;"></i> grabbaskets@gmail.com
                    </div>
                </div>

                <div class="info-text">
                    <strong>Seller Information</strong>
                    <div class="seller-grid" style="display:grid; grid-template-columns: 70px 1fr; gap:6px; font-size:0.85rem;">
                        <span style="color:#6b7280;">Seller:</span>
                        <span style="font-weight:600; color:#111827;">{{ $product->seller->name ?? 'GrabBasket Partner' }}</span>
                        
                        <span style="color:#6b7280;">Email:</span>
                        <span style="font-weight:600; color:#111827; word-break: break-all;">{{ $product->seller->email ?? 'N/A' }}</span>
                        
                        <span style="color:#6b7280;">Address:</span>
                        <span style="font-weight:600; color:#111827;">{{ $product->seller->billing_address ?? $product->seller->default_address ?? 'N/A' }}</span>
                        
                        <span style="color:#6b7280;">State:</span>
                        <span style="font-weight:600; color:#111827;">{{ $product->seller->state ?? 'N/A' }}</span>
                        
                        <span style="color:#6b7280;">Country:</span>
                        <span style="font-weight:600; color:#111827;">{{ $product->seller->country ?? 'India' }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- MOBILE STICKY BAR -->
    <div class="mobile-bar d-md-none">
          <div style="display: flex; flex-direction: column;">
             <div class="mobile-price">₹{{ number_format($product->price, 0) }}</div>
             <div style="font-size:0.75rem; color:#666; font-weight:500;">View Detail Bill</div>
          </div>
          <div>
            <button class="add-btn" id="mobileAddBtn" style="background: var(--brand-secondary); color: white; border:none; box-shadow: 0 4px 15px rgba(12,131,31,0.4);" onclick="document.getElementById('addBtn').click()">ADD</button>
          </div>
    </div>

    <!-- SCRIPT -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        let qty = 1;
        const addBtn = document.getElementById("addBtn");
        const qtyBox = document.getElementById("qtyBox");
        const badge = document.getElementById("badge");
        const qtyText = document.getElementById("qty");
        const csrfToken = '{{ csrf_token() }}';
        const productId = {{ $product->id }};
        const priceBtn = document.querySelector('.price-btn');
        const mobileAddBtn = document.getElementById('mobileAddBtn'); // Mobile Trigger

        function updateCartBadge() {
            const cartCount = parseInt(badge.innerText) || 0;
            badge.style.display = cartCount > 0 ? 'inline-flex' : 'none';
        }
        updateCartBadge();

        function fetchProductDetails() {
            fetch(`/api/product/${productId}`)
                .then(response => {
                    if (!response.ok) {
                        if (priceBtn) priceBtn.textContent = 'Unavailable';
                        if (addBtn) {
                            addBtn.disabled = true;
                            addBtn.textContent = 'OUT OF STOCK';
                        }
                        if(mobileAddBtn) {
                             mobileAddBtn.disabled = true;
                             mobileAddBtn.textContent = 'OUT OF STOCK';
                        }
                        throw new Error('Unavailable');
                    }
                    return response.json();
                })
                .then(data => {
                    if (priceBtn) {
                        priceBtn.textContent = `₹${parseFloat(data.price).toFixed(0)}`;
                    }
                    const inStock = data.stock > 0;
                    if (addBtn) {
                        addBtn.disabled = !inStock;
                        if(addBtn.innerText !== 'ADDING...') {
                             addBtn.textContent = inStock ? 'ADD' : 'OUT OF STOCK';
                        }
                    }
                     if (mobileAddBtn) {
                        mobileAddBtn.disabled = !inStock;
                         if(mobileAddBtn.innerText !== 'ADDING...') {
                             mobileAddBtn.textContent = inStock ? 'ADD' : 'OUT OF STOCK';
                        }
                    }
                })
                .catch(err => console.warn('Fetch failed:', err));
        }

        fetchProductDetails();
        const interval = setInterval(fetchProductDetails, 15000);
        window.addEventListener('beforeunload', () => clearInterval(interval));

        addBtn.onclick = () => {
            if (addBtn.disabled) return;

            const originalText = addBtn.innerText;
            addBtn.innerText = "ADDING...";
            if(mobileAddBtn) mobileAddBtn.innerText = "ADDING...";
            addBtn.disabled = true;

            fetch("{{ route('tenmin.cart.add') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: qty
                })
            })
            .then(async response => {
                const isJson = response.headers.get('content-type')?.includes('application/json');
                const data = isJson ? await response.json() : null;

                if (!response.ok) {
                    const errorMsg = (data && (data.error || data.message)) || 'Failed to add to cart';
                    throw new Error(errorMsg);
                }
                return data;
            })
            .then(data => {
                if (data && data.success) {
                    badge.innerText = data.cart_count;
                    updateCartBadge();
                    
                    addBtn.style.display = 'none';
                    if(mobileAddBtn) mobileAddBtn.style.display = 'none'; 
                    qtyBox.style.display = 'flex';
                    
                    setTimeout(() => {
                         window.location.href = "{{ route('tenmin.cart.view') }}";
                    }, 800);
                } else {
                    throw new Error((data && data.error) || 'Failed to add');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert(err.message);
                addBtn.innerText = originalText;
                if(mobileAddBtn) mobileAddBtn.innerText = originalText;
                addBtn.disabled = false;
            });
        };

        document.getElementById("plus").onclick = () => {
            qty++;
            qtyText.innerText = qty;
        };

        document.getElementById("minus").onclick = () => {
            if (qty > 1) {
                qty--;
                qtyText.innerText = qty;
            }
        };
    });
    </script>
</body>
</html>