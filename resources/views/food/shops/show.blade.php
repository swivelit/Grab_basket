<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $shop->restaurant_name ?? $shop->name }} - GrabBaskets</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Proxima+Nova:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary: #fc8019; /* Swiggy Orange */
            --text-dark: #282c3f;
            --text-gray: #686b78;
            --border-light: #e9e9eb;
            --bg-body: #ffffff;
        }

        body {
            font-family: sans-serif;
            background-color: var(--bg-body);
            color: var(--text-dark);
        }

        /* Navbar */
        .navbar-minimal {
            background: white;
            box-shadow: 0 15px 40px -20px rgba(40,44,63,.15);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-back {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Restaurant Header */
        .res-header {
            background: #171a29;
            color: white;
            padding: 40px 0;
            margin-bottom: 20px;
        }

        .res-img {
            width: 250px;
            height: 160px;
            object-fit: cover;
            border-radius: 4px;
        }

        .res-name {
            font-size: 2rem;
            font-weight: 300;
            margin-bottom: 5px;
        }

        .res-cuisine {
            font-size: 0.9rem;
            color: #dcdcdc;
            opacity: 0.8;
        }

        .res-meta-box {
            border: 1px solid white;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            min-width: 80px;
        }

        .meta-val { font-weight: 700; font-size: 1.1rem; }
        .meta-lbl { font-size: 0.7rem; text-transform: uppercase; opacity: 0.8; }

        /* Offer Stripe */
        .offer-stripe {
            border: 1px solid #dcdcdc;
            border-radius: 4px;
            padding: 10px 15px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            color: white;
            margin-top: 20px;
            background: rgba(255,255,255,0.1);
        }

        /* Main Layout */
        .menu-container {
            display: flex;
            gap: 40px;
            padding-top: 30px;
            align-items: flex-start;
        }

        /* Sidebar Categories */
        .category-sidebar {
            width: 280px;
            position: sticky;
            top: 100px;
            border-right: 1px solid var(--border-light);
            padding-right: 20px;
            max-height: calc(100vh - 120px);
            overflow-y: auto;
        }

        .cat-link {
            display: block;
            padding: 12px 10px;
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            text-align: right;
            border-right: 3px solid transparent;
            transition: all 0.2s;
            cursor: pointer;
        }

        .cat-link:hover, .cat-link.active {
            color: var(--primary);
            font-weight: 700;
            border-right-color: var(--primary);
        }

        /* Menu Items */
        .menu-products {
            flex: 1;
            padding-bottom: 100px;
        }

        .category-header {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 20px;
            padding-top: 20px;
            border-bottom: 1px solid var(--border-light); /* Optional */
            padding-bottom: 10px;
        }

        .item-card {
            display: flex;
            justify-content: space-between;
            padding: 25px 0;
            border-bottom: 0.5px solid #d4d5d9;
        }

        .item-info {
            flex: 1;
            padding-right: 20px;
        }

        .badge-icon { font-size: 12px; margin-right: 5px; }
        .veg { color: #0f8a65; }
        .non-veg { color: #e43b4f; }

        .item-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 5px 0;
            color: var(--text-dark);
        }

        .item-price {
            font-size: 0.95rem;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .item-desc {
            font-size: 0.85rem;
            color: #93959f;
            line-height: 1.5;
            max-width: 80%;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .item-photo-container {
            position: relative;
            width: 140px;
            height: 120px;
            border-radius: 10px;
            background: #fbfbfb;
        }

        .item-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }

        .add-btn-wrapper {
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border: 1px solid #d4d5d9;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
            border-radius: 4px;
            color: #60b246;
            font-weight: 700;
            width: 100px;
            text-align: center;
            overflow: hidden;
            cursor: pointer;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .add-btn-wrapper:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .add-action-btn:active {
            transform: scale(0.95);
        }

        /* Cart Notification */
        .cart-float {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #60b246;
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 700;
            box-shadow: 0 5px 15px rgba(96, 178, 70, 0.4);
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 1050;
            display: none; /* Hidden by default */
            cursor: pointer;
            min-width: 300px;
            justify-content: space-between;
        }

        .cart-float a { color: white; text-decoration: none; }

        @media (max-width: 768px) {
            .menu-container { flex-direction: column; padding: 0 15px; }
            .category-sidebar { display: none; } /* Hide sidebar on mobile */
            .res-img { display: none; }
            .res-name { font-size: 1.5rem; }
            .res-header { padding: 20px 15px; }
            .item-photo-container { width: 120px; height: 100px; }
            .add-btn-wrapper { width: 90px; }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar-minimal">
        <div class="container d-flex justify-content-between align-items-center px-3">
            <a href="{{ route('food.shops.index') }}" class="nav-back">
                <i class="bi bi-arrow-left"></i> Food Home
            </a>
            <a href="{{ route('home') }}" class="nav-back text-muted" style="font-size: 0.9rem;">
                <i class="bi bi-house"></i>
            </a>
        </div>
    </nav>

    <!-- Header info -->
    <div class="res-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="res-name">{{ $shop->restaurant_name ?? $shop->name }}</h1>
                    <div class="res-cuisine">{{ $shop->cuisine_type ?? 'Multi-Cuisine, Fast Food' }}</div>
                    <div class="res-cuisine mt-1">{{ $shop->restaurant_address ?? $shop->city }}</div>
                    
                    <div class="d-flex gap-3 mt-4">
                       
                        <div class="res-meta-box">
                            <div class="meta-val">{{ $shop->delivery_time ?? '10' }}</div>
                            <div class="meta-lbl">Mins</div>
                        </div>
                        <div class="res-meta-box">
                            <div class="meta-val">90</div> <!-- Placeholder -->
                            <div class="meta-lbl">Minimum Order</div>
                        </div>
                    </div>
                </div>
                <!-- Optional Image on right (desktop) -->
                 @php
                    $image = $shop->logo 
                        ? (Str::startsWith($shop->logo, 'http') ? $shop->logo : asset('storage/' . $shop->logo))
                        : 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80';
                @endphp
                <img src="{{ $image }}" class="res-img d-none d-md-block" alt="Shop Image">
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container menu-container">
        
        <!-- Sidebar Navigation (Desktop) -->
        <aside class="category-sidebar d-none d-md-block">
            <div class="text-end fw-bold mb-3 pe-2">Menu</div>
            @foreach($groupedItems as $category => $items)
                <a class="cat-link" onclick="scrollToCategory('{{ Str::slug($category) }}')">{{ $category }}</a>
            @endforeach
        </aside>

        <!-- Menu Items List -->
        <main class="menu-products">
            
            @forelse($groupedItems as $category => $items)
                <div id="cat-{{ Str::slug($category) }}" class="category-section mb-5">
                    <h3 class="category-header">{{ $category }} ({{ count($items) }})</h3>
                    
                    @foreach($items as $item)
                        <div class="item-card">
                            <div class="item-info">
                                <div>
                                    @if(strtolower($item->food_type) === 'veg')
                                        <i class="bi bi-caret-up-square-fill badge-icon veg"></i>
                                    @else
                                        <i class="bi bi-caret-up-square-fill badge-icon non-veg"></i>
                                    @endif
                                    @if($item->is_popular)
                                        <span class="text-warning fw-bold fs-7 ms-2"><i class="bi bi-star-fill"></i> Bestseller</span>
                                    @endif
                                </div>
                                <h4 class="item-title">{{ $item->name }}</h4>
                                <div class="item-price">₹{{ number_format($item->getFinalPrice(), 0) }}</div>
                                <div class="item-desc">{{ $item->description }}</div>
                            </div>
                            
                            <div class="item-photo-container">
                                @php
                                    $itemImage = $item->first_image_url;
                                    
                                    // Fallback or explicit construction if first_image_url is not a full URL
                                    // Using the specific cloud domain provided by user
                                    if ($item->image && !Str::startsWith($item->image, 'http')) {
                                        $baseCloudUrl = 'https://fls-a00f1665-d58e-4a6d-a69d-0dc4be26102f.laravel.cloud';
                                        
                                        // If the image path in DB doesn't include the folder structure, we might need to add it
                                        // But assuming standard storage, use the cloud URL + path
                                        if (!Str::startsWith($itemImage, 'http')) {
                                             $itemImage = $baseCloudUrl . '/' . ltrim($item->image, '/');
                                        }
                                    }
                                    
                                    $itemImage = $itemImage ?? 'https://via.placeholder.com/150';
                                @endphp
                                <img src="{{ $itemImage }}" class="item-photo" alt="{{ $item->name }}" onerror="this.src='/images/placeholder-food.png'">
                                
                                <!-- Add Button -->
                                <button class="add-btn-wrapper add-action-btn border-0" onclick="addToCart({{ $item->id }}, '{{ addslashes($item->name) }}')">
                                    ADD <i class="bi bi-plus-lg fst-normal ms-1" style="font-size: 0.8rem;"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="text-center py-5">
                    <h4 class="text-muted">No items available currently.</h4>
                </div>
            @endforelse

        </main>
    </div>

    <!-- Floating Cart Notification -->
    <!-- Floating Cart Notification -->
    <div class="cart-float" id="cartFloat" onclick="window.location.href='{{ route('customer.food.cart') }}'">
        <div>
            <span id="cartCount">1</span> Item | View Cart
        </div>
        <i class="bi bi-bag-check-fill fs-5"></i>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth Scroll
        function scrollToCategory(id) {
            const element = document.getElementById('cat-' + id);
            if (element) {
                const headerOffset = 140;
                const elementPosition = element.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: "smooth"
                });
            }
        }

        // Add to Cart
        async function addToCart(id, name) {
            const btn = event.currentTarget;
            const originalContent = btn.innerHTML;
            
            btn.innerHTML = '...';
            btn.disabled = true;

            try {
                const response = await fetch(`/food/cart/add/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    btn.innerHTML = 'ADDED';
                    btn.style.color = 'white';
                    btn.style.background = '#60b246';
                    
                    showCartFloat();
                    
                    setTimeout(() => {
                        // Reset simple button state after a while or handle quantity state logic
                    }, 2000);
                } else {
                     if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        alert('Failed to add item');
                        btn.innerHTML = originalContent;
                        btn.disabled = false;
                    }
                }
            } catch (error) {
                console.error(error);
                btn.innerHTML = originalContent;
                 btn.disabled = false;
            }
        }

        function showCartFloat() {
            const float = document.getElementById('cartFloat');
            float.style.display = 'flex';
            // In a real app, update count dynamically
        }

        // Highlight active category on scroll
        window.addEventListener('scroll', () => {
            let current = '';
            const sections = document.querySelectorAll('.category-section');
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (pageYOffset >= sectionTop - 200) {
                    current = section.getAttribute('id').replace('cat-', '');
                }
            });

            // Update sidebar links (if implementing active state visual)
             document.querySelectorAll('.cat-link').forEach(li => {
                li.classList.remove('active');
                // Logic to match slug would go here
             });
        });
    </script>
</body>
</html>