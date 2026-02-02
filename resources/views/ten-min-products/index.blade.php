<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GrabBasket — 10-Minute Products</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Gilroy:wght@300;400;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Bootstrap Override for compatibility */
        a {
            text-decoration: none;
        }

        ul {
            padding-left: 0;
            margin-bottom: 0;
        }

        /* ZEPTO-INSPIRED DESIGN SYSTEM */
        /* ZEPTO-INSPIRED DESIGN SYSTEM */
        :root {
            --primary: #0c0c0c;
            --brand: #2f7a2f;
            --brand-light: #e6f7e6;
            --accent: #ff3269;
            --surface: #f3f4f6;
            --white: #ffffff;
            --border: #e5e7eb;
            --text-sec: #6b7280;
            --radius: 16px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #fcfcfc;
            color: var(--primary);
            overflow-x: hidden;
            padding-bottom: 80px;
            /* Space for bottom nav on mobile */
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button {
            font-family: inherit;
        }

        /* ========== HEADER ========== */
        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 12px 0;
        }

        .nav-container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #2f7a2f, #16a34a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            flex-shrink: 0;
        }

        .search-bar {
            flex: 1;
            max-width: 600px;
            position: relative;
            margin-left: 20px;
        }

        .search-input {
            width: 100%;
            background: #f3f4f6;
            border: 1px solid transparent;
            padding: 14px 20px 14px 48px;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.2s;
            color: #1f2937;
        }

        .search-input:focus {
            background: #fff;
            border-color: var(--brand);
            box-shadow: 0 4px 12px rgba(47, 122, 47, 0.1);
            outline: none;
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-left: auto;
        }

        .nav-btn {
            font-weight: 600;
            font-size: 14px;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .nav-btn:hover {
            background: #f3f4f6;
        }

        .cart-btn {
            background: var(--brand);
            color: white !important;
            padding: 10px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(47, 122, 47, 0.25);
            transition: transform 0.2s;
        }

        .cart-btn:active {
            transform: scale(0.96);
        }

        .logout-btn {
            background: linear-gradient(135deg, #ff416c, #ff4b2b);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
        }

        /* ========== LAYOUT ========== */
        .main-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            max-width: 1300px;
            margin: 30px auto;
            padding: 0 20px;
            gap: 32px;
            align-items: start;
        }

        /* ========== SIDEBAR (Categories) ========== */
        .sidebar {
            position: sticky;
            top: 100px;
            max-height: calc(100vh - 120px);
            overflow-y: auto;
            padding-right: 10px;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 10px;
        }

        .cat-label {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            color: #9ca3af;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
            padding-left: 12px;
        }

        .cat-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            margin-bottom: 4px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            border: 1px solid transparent;
        }

        .cat-item:hover {
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            transform: translateX(2px);
        }

        .cat-item.active {
            background: #e9f5e9;
            border-color: rgba(47, 122, 47, 0.1);
            color: var(--brand);
            font-weight: 700;
        }

        .cat-img {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            object-fit: cover;
            background: #e5e7eb;
        }

        /* ========== CONTENT AREA ========== */
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .content-title {
            font-size: 28px;
            font-weight: 800;
            color: #111;
        }

        .subcategories {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 12px;
            margin-bottom: 20px;
            scrollbar-width: none;
        }

        .subcategories::-webkit-scrollbar {
            display: none;
        }

        .sub-pill {
            padding: 8px 16px;
            border-radius: 99px;
            background: #fff;
            border: 1px solid #e5e7eb;
            font-size: 14px;
            font-weight: 600;
            color: #4b5563;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s;
        }

        .sub-pill:hover {
            border-color: #d1d5db;
            background: #f9fafb;
        }

        .sub-pill.active {
            background: #111;
            color: #fff;
            border-color: #111;
        }

        /* ========== GRID ========== */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: #fff;
            border-radius: 16px;
            padding: 16px;
            border: 1px solid transparent;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        .product-card:hover {
            box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.12);
            transform: translateY(-4px);
            border-color: #f3f4f6;
        }

        .p-img-box {
            width: 100%;
            aspect-ratio: 1;
            margin-bottom: 14px;
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            background: #f8f8f8;
        }

        .p-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .p-img {
            transform: scale(1.05);
        }

        .discount-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background: #ffecf0;
            color: #d1004b;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 8px;
            border-radius: 6px;
            z-index: 10;
        }

        .p-title {
            font-size: 15px;
            font-weight: 600;
            line-height: 1.4;
            color: #1f2937;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 42px;
        }

        .p-weight {
            font-size: 13px;
            color: #9ca3af;
            margin-bottom: 12px;
        }

        .p-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }

        .p-price {
            display: flex;
            flex-direction: column;
        }

        .current-price {
            font-size: 16px;
            font-weight: 700;
            color: #111;
        }

        .old-price {
            font-size: 12px;
            text-decoration: line-through;
            color: #9ca3af;
            margin-top: 2px;
        }

        .add-btn-sm {
            background: #fff;
            color: var(--brand);
            border: 1px solid var(--brand);
            padding: 8px 24px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .add-btn-sm:hover {
            background: #e6f7e6;
        }

        .add-btn-sm:active {
            background: var(--brand);
            color: #fff;
            transform: scale(0.95);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 0;
            color: #6b7280;
            grid-column: 1 / -1;
        }

        /* ========== MOBILE BOTTOM NAV ========== */
        .bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 8px 0 calc(8px + env(safe-area-inset-bottom));
            z-index: 1000;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }

        .bottom-nav-content {
            display: flex;
            justify-content: space-around;
            align-items: center;
            max-width: 600px;
            margin: 0 auto;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 8px 16px;
            color: #6b7280;
            font-size: 11px;
            font-weight: 600;
            transition: all 0.2s;
            border-radius: 8px;
        }

        .nav-item.active {
            color: var(--brand);
        }

        .nav-item i {
            font-size: 20px;
        }

        /* ========== MOBILE OVERRIDES ========== */
        @media (max-width: 900px) {
            body {
                padding-bottom: 70px;
            }

            .main-layout {
                grid-template-columns: 1fr;
                padding: 0 16px;
                margin: 20px auto;
                gap: 20px;
            }

            .sidebar {
                display: none;
            }

            /* Mobile Categories - Horizontal Scroll */
            .mobile-cats {
                display: flex;
                gap: 12px;
                overflow-x: auto;
                padding: 12px 16px;
                background: #fff;
                margin: 0 -16px 20px -16px;
                scrollbar-width: none;
            }

            .mobile-cats::-webkit-scrollbar {
                display: none;
            }

            .m-cat-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                min-width: 72px;
                flex-shrink: 0;
                gap: 6px;
                padding: 8px;
                border-radius: 12px;
                transition: background 0.2s;
            }

            .m-cat-item.active {
                background: #e9f5e9;
            }

            .m-cat-img {
                width: 56px;
                height: 56px;
                border-radius: 50%;
                object-fit: cover;
                background: #f3f4f6;
                border: 2px solid transparent;
            }

            .m-cat-item.active .m-cat-img {
                border-color: var(--brand);
            }

            .m-cat-name {
                font-size: 11px;
                text-align: center;
                font-weight: 600;
                color: #4b5563;
                line-height: 1.2;
            }

            .nav-container {
                padding: 0 16px;
                gap: 12px;
            }

            .search-bar {
                display: none;
            }

            .logo {
                font-size: 20px;
            }

            /* Mobile Search */
            .mobile-search {
                display: block;
                padding: 10px 16px;
                background: #fff;
                margin: 0 -16px 16px -16px;
            }

            .mobile-search .search-input {
                width: 100%;
                padding: 12px 12px 12px 42px;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                background: #f9fafb;
            }

            .product-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 10px !important;
            }

            .product-card {
                padding: 12px;
                border-radius: 12px;
            }

            .add-btn-sm {
                padding: 6px 14px;
                font-size: 12px;
            }

            .content-title {
                font-size: 22px;
            }

            .bottom-nav {
                display: block;
            }

            .nav-right .cart-btn {
                display: none;
                /* Hide desktop cart, show in bottom nav */
            }
        }

        @media (min-width: 901px) {

            .mobile-cats,
            .mobile-search,
            .bottom-nav {
                display: none;
            }
        }
    </style>
</head>

<body>

    <!-- Header -->
    <header>
        <div class="nav-container">
            <a href="/" class="logo">GB 10Min</a>

            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input id="globalSearch" class="search-input" placeholder="Search for 'milk', 'chips', 'soap'..." />
            </div>

            <div class="nav-right">
                @auth
                    <div class="dropdown">
                        <button class="nav-btn border-0 bg-transparent dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-circle-user fa-lg text-secondary"></i>
                            <span class="d-none d-md-inline ms-1">Hi, {{ auth()->user()->name }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2"
                            style="border-radius: 12px; overflow: hidden;">
                            <li>
                                <a class="dropdown-item py-2 px-3 fw-medium" href="{{ url('/profile') }}">
                                    <i class="fa-solid fa-user me-2 text-muted"></i> Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 px-3 fw-medium" href="{{ route('orders.index') }}">
                                    <i class="fa-solid fa-bag-shopping me-2 text-muted"></i> Orders
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item py-2 px-3 text-danger fw-semibold">
                                        <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="nav-btn text-decoration-none">
                        <i class="fa-regular fa-user"></i>&nbsp;Login
                    </a>
                @endauth

                <a href="{{ route('tenmin.cart.view') }}" class="nav-btn cart-btn text-decoration-none">
                    <i class="fa-solid fa-cart-shopping"></i>&nbsp;
                    <span
                        id="cartCountBadge">{{ \App\Models\TenMinGroceryCartItem::where('user_id', auth()->id())->sum('quantity') }}</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Mobile Search -->
    <div class="mobile-search">
        <div style="position:relative;">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input class="search-input" placeholder="Search..." id="mobileSearchInput">
        </div>
    </div>

    <!-- Mobile Categories -->
    <div class="mobile-cats">
        @foreach($categories as $cat)
            @php
                $keyword = urlencode($cat->name);
                $imgUrl = "https://loremflickr.com/100/100/{$keyword},grocery";
                $isActive = isset($activeCategory) && $activeCategory->id == $cat->id;
            @endphp
            <div class="m-cat-item {{ $isActive ? 'active' : '' }}"
                onclick="window.location.href='{{ route('ten.min.products', ['category' => $cat->id]) }}'">
                <img src="{{ $imgUrl }}" class="m-cat-img" alt="{{ $cat->name }}">
                <span class="m-cat-name">{{ $cat->name }}</span>
            </div>
        @endforeach
    </div>

    <!-- Main Layout -->
    <div class="main-layout">

        <!-- Desktop Sidebar -->
        <aside class="sidebar">
            <div class="cat-label">Shop by Category</div>

            @foreach($categories as $cat)
                @php
                    $isActive = isset($activeCategory) && $activeCategory->id == $cat->id;
                    $keyword = urlencode($cat->name);
                    $imgUrl = "https://loremflickr.com/80/80/{$keyword},food?lock={$cat->id}";
                @endphp
                <div class="cat-item {{ $isActive ? 'active' : '' }}"
                    onclick="window.location.href='{{ route('ten.min.products', ['category' => $cat->id]) }}'">
                    <img src="{{ $imgUrl }}" class="cat-img" alt="">
                    <span>{{ $cat->name }}</span>
                    @if($isActive) <i class="fa-solid fa-chevron-right" style="margin-left:auto;font-size:12px;"></i> @endif
                </div>
            @endforeach
        </aside>

        <!-- Content -->
        <main>
            @php
                $displayActive = $activeCategory ?? null;
                $displayProducts = $displayActive ? $displayActive->tenMinProducts : ($commonProducts ?? collect());
                $title = $displayActive ? $displayActive->name : 'Top Picks for You';
                $count = $displayActive ? $displayActive->tenMinProducts->count() : null;
            @endphp

            <div class="content-header">
                <div>
                    <h1 class="content-title">{{ $title }}</h1>
                    <div style="color:#6b7280; margin-top:4px;">
                        @if($count !== null)
                            {{ $count }} items available
                        @else
                            Curated selection from all categories
                        @endif
                    </div>
                </div>
            </div>

            <!-- Subcategories -->
            @if($displayActive && $displayActive->filteredSubcategories->isNotEmpty())
                <div class="subcategories" id="subCats">
                    <div class="sub-pill active" data-sub="All">All</div>
                    @foreach($displayActive->filteredSubcategories as $sub)
                        <div class="sub-pill" data-sub="{{ $sub->name }}">{{ $sub->name }}</div>
                    @endforeach
                </div>
            @endif

            <!-- Products -->
            <div class="product-grid" id="productGrid">
                @foreach($displayProducts as $product)
                    <div class="product-card" data-subcat="{{ $product->subcategory?->name ?? 'Other' }}"
                        onclick="window.location.href='/product/{{ $product->id }}'">

                        @if($product->discount > 0)
                            <div class="discount-badge" style="z-index: 10;">{{ $product->discount }}% OFF</div>
                        @endif

                        <div class="p-img-box">
                            <img src="{{ $product->image_url ?? 'https://via.placeholder.com/300' }}" class="p-img"
                                alt="{{ $product->name }}">
                        </div>

                        <div>
                            <div class="p-title">{{ $product->name }}</div>
                            <div class="p-weight">
                                @if(!$displayActive)
                                    {{ $product->category?->name ?? 'Category' }} •
                                @endif
                                {{ $product->subcategory?->name ?? 'Standard' }}
                            </div>
                        </div>

                        <div class="p-footer">
                            <div class="p-price">
                                <span class="current-price">₹{{ $product->price }}</span>
                                @if($product->discount > 0)
                                    <span
                                        class="old-price">₹{{ number_format($product->price / (1 - $product->discount / 100), 2) }}</span>
                                @endif
                            </div>
                            <button class="add-btn-sm"
                                onclick="event.stopPropagation(); addToCart({{ $product->id }}, this)">
                                ADD
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div id="noResults" class="empty-state" style="display:none;">
                <i class="fa-regular fa-face-frown" style="font-size:48px;margin-bottom:16px;"></i>
                <h3>No products found</h3>
                <p>Try searching for something else.</p>
            </div>
        </main>

    </div>

    <!-- Mobile Bottom Navigation -->
    <nav class="bottom-nav">
        <div class="bottom-nav-content">
            <a href="{{ route('home') }}" class="nav-item">
                <i class="fa-solid fa-house"></i>
                <span>Home</span>
            </a>
            <a href="{{ route('ten.min.products') }}" class="nav-item active">
                <i class="fa-solid fa-bolt"></i>
                <span>10 Min</span>
            </a>
            <a href="{{ route('tenmin.cart.view') }}" class="nav-item">
                <i class="fa-solid fa-cart-shopping"></i>
                <span>Cart</span>
            </a>
            @auth
                <a href="{{ route('profile.show') }}" class="nav-item">
                    <i class="fa-solid fa-user"></i>
                    <span>Profile</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="nav-item">
                    <i class="fa-solid fa-user"></i>
                    <span>Login</span>
                </a>
            @endauth
        </div>
    </nav>

    <script>
        // ========== SEARCH ==========
        const searchInputs = [document.getElementById('globalSearch'), document.getElementById('mobileSearchInput')];
        const jsCategories = @json($jsCategories);
        const productGrid = document.getElementById('productGrid');
        const contentTitle = document.querySelector('.content-title');
        const contentHeaderInfo = document.querySelector('.content-header div div');
        const subCatsContainer = document.getElementById('subCats');
        const defaultCategoryName = "{{ $activeCategory->name ?? 'Top Picks for You' }}";
        const defaultCategoryItemsCount = "{{ $activeCategory ? $activeCategory->tenMinProducts->count() : ($commonProducts->count() ?? 0) }}";
        const isCommonView = @json(!isset($activeCategory));

        // Store original view products to restore later
        const originalGridHTML = productGrid.innerHTML;
        const originalSubCatsHTML = subCatsContainer ? subCatsContainer.innerHTML : '';

        searchInputs.forEach(input => {
            if (!input) return;
            input.addEventListener('input', (e) => {
                const term = e.target.value.toLowerCase().trim();

                if (term.length === 0) {
                    // Restore original view
                    productGrid.innerHTML = originalGridHTML;
                    if (subCatsContainer) {
                        subCatsContainer.style.display = 'flex';
                        subCatsContainer.innerHTML = originalSubCatsHTML;
                        attachSubcategoryListeners(); // Re-attach listeners to restored pills
                    }
                    if (contentTitle) contentTitle.innerText = defaultCategoryName;
                    if (contentHeaderInfo) {
                        contentHeaderInfo.innerText = isCommonView ? "Curated selection from all categories" : `${defaultCategoryItemsCount} items available`;
                    }
                    noResults.style.display = 'none';
                    return;
                }

                // Global Search across all categories
                let matches = [];
                jsCategories.forEach(cat => {
                    cat.products.forEach(p => {
                        if (p.name.toLowerCase().includes(term) || p.subcategory.toLowerCase().includes(term)) {
                            matches.push({ ...p, categoryName: cat.name });
                        }
                    });
                });

                // Update UI for Search Results
                if (contentTitle) contentTitle.innerText = `Search Results for "${term}"`;
                if (contentHeaderInfo) contentHeaderInfo.innerText = `${matches.length} items found across all categories`;
                if (subCatsContainer) subCatsContainer.style.display = 'none';

                if (matches.length === 0) {
                    productGrid.innerHTML = '';
                    noResults.style.display = 'block';
                } else {
                    noResults.style.display = 'none';
                    productGrid.innerHTML = matches.map(p => `
                        <div class="product-card" onclick="window.location.href='/product/${p.id}'">
                            ${p.discount > 0 ? `<div class="discount-badge">${p.discount}% OFF</div>` : ''}
                            <div class="p-img-box">
                                <img src="${p.img || 'https://via.placeholder.com/300'}" class="p-img" alt="${p.name}">
                            </div>
                            <div>
                                <div class="p-title">${p.name}</div>
                                <div class="p-weight">${p.categoryName} • ${p.subcategory}</div>
                            </div>
                            <div class="p-footer">
                                <div class="p-price">
                                    <span class="current-price">₹${p.price}</span>
                                    ${p.discount > 0 ? `<span class="old-price">₹${(p.price / (1 - p.discount / 100)).toFixed(2)}</span>` : ''}
                                </div>
                                <button class="add-btn-sm" onclick="event.stopPropagation(); addToCart(${p.id}, this)">
                                    ADD
                                </button>
                            </div>
                        </div>
                    `).join('');
                }
            });
        });

        function attachSubcategoryListeners() {
            const subPills = document.querySelectorAll('.sub-pill');
            subPills.forEach(pill => {
                pill.replaceWith(pill.cloneNode(true)); // Clear existing listeners to avoid duplicates
            });

            document.querySelectorAll('.sub-pill').forEach(pill => {
                pill.addEventListener('click', () => {
                    document.querySelectorAll('.sub-pill').forEach(p => p.classList.remove('active'));
                    pill.classList.add('active');

                    const selectedSub = pill.dataset.sub;
                    let visibleCount = 0;
                    const currentCards = document.querySelectorAll('.product-card');

                    currentCards.forEach(card => {
                        if (selectedSub === 'All' || card.dataset.subcat === selectedSub) {
                            card.style.display = 'flex';
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    noResults.style.display = visibleCount === 0 ? 'block' : 'none';
                });
            });
        }

        // Initialize listeners
        attachSubcategoryListeners();

        // ========== ADD TO CART ==========
        async function addToCart(productId, btn) {
            if (btn.disabled) return;

            const originalText = btn.innerText;
            btn.innerText = "•";
            btn.disabled = true;
            btn.style.width = "60px";

            try {
                const res = await fetch("{{ route('tenmin.cart.add') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ product_id: productId, quantity: 1 })
                });

                const data = await res.json();

                if (data.success) {
                    const badge = document.getElementById('cartCountBadge');
                    if (badge) badge.innerText = data.cart_count;

                    btn.style.background = "#2f7a2f";
                    btn.style.color = "#fff";
                    btn.innerText = "✓";

                    setTimeout(() => {
                        btn.innerText = "ADD";
                        btn.style.background = "";
                        btn.style.color = "";
                        btn.disabled = false;
                        btn.style.width = "";
                    }, 1500);
                } else {
                    throw new Error(data.error || data.message || 'Error');
                }
            } catch (err) {
                alert(err.message);
                btn.innerText = originalText;
                btn.disabled = false;
                btn.style.width = "";
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>