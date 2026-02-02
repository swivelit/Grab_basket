<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>GrabBaskets — Food Delivery</title>
    <!-- Google Fonts: Inter & Outfit -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF6B00;
            --primary-dark: #E65A00;
            --secondary: #282C3F;
            --text-main: #3D4152;
            --text-light: #686B78;
            --bg-gray: #F1F4F6;
            --rating: #48C479;
            --white: #FFFFFF;
            --border-light: #E9E9EB;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --sticky-offset: 70px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--white);
            color: var(--text-main);
            margin: 0;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* --- NAVBAR --- */
        .swiggy-nav {
            background: var(--white);
            position: sticky;
            top: 0;
            z-index: 1100;
            box-shadow: 0 15px 40px -20px rgba(40, 44, 63, 0.15);
            padding: 0 20px;
            height: 80px;
            display: flex;
            align-items: center;
        }

        .nav-content {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .left-nav {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .logo-box {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            transition: transform 0.2s;
        }

        .logo-box:hover {
            transform: scale(1.02);
        }

        .logo-box i {
            color: var(--primary);
            font-size: 2rem;
        }

        .logo-text {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary);
            letter-spacing: -0.5px;
        }

        .location-box {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: var(--text-main);
            border-left: 2px solid var(--text-main);
            padding-left: 30px;
            cursor: pointer;
        }

        .location-bold {
            font-weight: 700;
            border-bottom: 2px solid var(--text-main);
            margin-right: 5px;
        }

        .location-box:hover {
            color: var(--primary);
        }

        .location-box:hover .location-bold {
            border-bottom-color: var(--primary);
        }

        .right-nav {
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .nav-link-item {
            text-decoration: none;
            color: var(--text-main);
            font-weight: 500;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: color 0.2s;
        }

        .nav-link-item:hover {
            color: var(--primary);
        }

        .nav-link-item i {
            font-size: 1.2rem;
        }


        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .swiggy-nav {
                height: 60px;
                padding: 0 15px;
            }

            .location-box {
                display: none;
            }

            .right-nav {
                display: none;
            }

            /* Hide desktop top-right nav on mobile */
            .logo-text {
                font-size: 1.2rem;
            }
        }

        /* --- MOBILE BOTTOM NAV --- */
        .mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: var(--white);
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 2000;
            padding: 10px 0;
            justify-content: space-around;
            align-items: center;
        }

        .mobile-nav-item {
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--text-light);
            font-size: 0.75rem;
            gap: 4px;
            transition: color 0.2s;
            position: relative;
        }

        .mobile-nav-item i {
            font-size: 1.4rem;
        }

        .mobile-nav-item.active {
            color: var(--primary);
            font-weight: 600;
        }

        .cart-badge-mobile {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #D12939;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.65rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        @media (max-width: 768px) {
            .mobile-bottom-nav {
                display: flex;
            }

            body {
                padding-bottom: 70px;
            }

            /* Add padding for bottom nav */
        }

        /* --- SEARCH BOX --- */
        .search-section {
            padding: 30px 0;
            background: var(--white);
            position: sticky;
            top: 80px;
            z-index: 1050;
        }

        .search-container {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }

        .swiggy-search-input {
            width: 100%;
            padding: 14px 20px 14px 50px;
            border: 1px solid var(--border-light);
            border-radius: 12px;
            font-size: 1rem;
            color: var(--text-main);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s;
        }

        .swiggy-search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(255, 107, 0, 0.15);
        }

        .search-icon-inside {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .search-section {
                top: 60px;
                padding: 15px 15px;
            }

            .swiggy-search-input {
                padding: 10px 15px 10px 45px;
                font-size: 0.9rem;
            }
        }

        /* --- CATEGORIES --- */
        .section-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .section-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 25px;
            color: var(--secondary);
        }

        .categories-carousel {
            display: flex;
            gap: 25px;
            overflow-x: auto;
            padding-bottom: 20px;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .categories-carousel::-webkit-scrollbar {
            display: none;
        }

        .cat-item {
            min-width: 140px;
            text-align: center;
            text-decoration: none;
            transition: transform 0.3s;
        }

        .cat-item:hover {
            transform: translateY(-5px);
        }

        .cat-image-wrapper {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            overflow: hidden;
            background: var(--bg-gray);
            margin-bottom: 12px;
            box-shadow: var(--shadow-md);
            border: 2px solid transparent;
            transition: border-color 0.3s;
        }

        .cat-item.active .cat-image-wrapper {
            border-color: var(--primary);
        }

        .cat-image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cat-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-main);
        }

        @media (max-width: 768px) {
            .section-container {
                padding: 0 15px;
                margin: 15px auto;
            }

            .categories-carousel {
                gap: 15px;
                padding: 0 0 15px;
                margin: 0 -5px;
            }

            .cat-item {
                min-width: 80px;
                flex-shrink: 0;
            }

            .cat-image-wrapper {
                width: 80px;
                height: 80px;
                margin-bottom: 8px;
            }

            .cat-name {
                font-size: 0.75rem;
            }

            .section-title {
                font-size: 1.1rem;
                margin-bottom: 12px;
                padding: 0;
            }
        }

        /* --- FILTER BAR --- */
        .filter-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            position: sticky;
            top: 170px;
            background: var(--white);
            padding: 10px 0;
            z-index: 1040;
            border-bottom: 1px solid var(--border-light);
        }

        .filter-pill {
            padding: 8px 18px;
            border: 1px solid var(--border-light);
            border-radius: 20px;
            background: var(--white);
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-main);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-pill:hover,
        .filter-pill.active {
            background: var(--bg-gray);
            border-color: var(--text-main);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .filter-pill select {
            border: none;
            background: transparent;
            outline: none;
            font-weight: 600;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .filter-bar {
                top: 120px;
                padding: 10px 15px;
                margin: 0 -15px;
                overflow-x: auto;
                flex-wrap: nowrap;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
            }

            .filter-bar::-webkit-scrollbar {
                display: none;
            }

            .filter-pill {
                white-space: nowrap;
                flex-shrink: 0;
            }
        }

        /* --- FOOD CARDS --- */
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }

        .premium-food-card {
            text-decoration: none;
            color: inherit;
            transition: transform 0.3s;
            display: flex;
            flex-direction: column;
        }

        .premium-food-card:hover {
            transform: scale(0.96);
        }

        .card-img-container {
            position: relative;
            width: 100%;
            height: 180px;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 12px;
            box-shadow: var(--shadow-md);
        }

        .card-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .img-overlay-gradient {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60%;
            background: linear-gradient(0deg, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0) 100%);
        }

        .discount-tag {
            position: absolute;
            bottom: 12px;
            left: 12px;
            color: var(--white);
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.1rem;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }

        .food-info {
            padding: 0 4px;
        }

        .food-name-h {
            font-weight: 700;
            font-size: 1.15rem;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text-main);
        }

        .meta-row {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 6px;
        }

        .rating-box {
            display: flex;
            align-items: center;
            gap: 4px;
            background: var(--rating);
            color: var(--white);
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 0.85rem;
        }

        .delivery-time {
            color: var(--text-main);
        }

        .dot-sep {
            width: 4px;
            height: 4px;
            background: var(--text-light);
            border-radius: 50%;
            opacity: 0.5;
        }

        .food-details-text {
            font-size: 0.9rem;
            color: var(--text-light);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 4px;
        }

        .price-tag {
            font-weight: 600;
            color: var(--text-main);
        }

        .veg-nonveg-indicator {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(255, 255, 255, 0.9);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #ddd;
        }

        .veg-indicator {
            color: var(--rating);
            border-color: var(--rating);
        }

        .nonveg-indicator {
            color: #D12939;
            border-color: #D12939;
        }

        @media (max-width: 576px) {
            .section-container {
                padding: 0 12px;
            }

            .items-grid { 
                grid-template-columns: repeat(2, 1fr);
                gap: 16px 12px; /* Increased row gap */
                padding: 0;
                margin: 0;
                align-items: stretch; /* Force same height in row */
            }
            
            .premium-food-card {
                margin-bottom: 0;
                width: 100%;
                border-radius: 12px;
                background: #fff;
                display: flex;
                flex-direction: column;
                height: 100%;
            }
            
            .card-img-container { 
                width: 100%;
                height: auto;
                aspect-ratio: 4 / 3;
                border-radius: 12px;
                margin-bottom: 6px;
                box-shadow: none;
                border: 1px solid var(--border-light);
                position: relative;
                overflow: hidden;
            }
            
            .food-info {
                padding: 0 2px 4px;
                flex: 1;
                display: flex;
                flex-direction: column;
            }
            
            .food-name-h { 
                font-size: 0.95rem;
                margin-bottom: 2px;
                font-weight: 600;
                height: 2.4em; /* Fixed height for 2 lines */
                line-height: 1.2;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                white-space: normal;
                text-overflow: ellipsis;
            }
            
            .discount-tag { 
                font-size: 0.65rem;
                bottom: 8px;
                left: 8px;
                font-weight: 700;
                width: calc(100% - 16px);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .meta-row { 
                gap: 4px;
                font-size: 0.75rem;
                margin-bottom: 4px;
                align-items: center;
            }
            
            .rating-box { 
                font-size: 0.7rem; 
                padding: 1px 4px;
                min-width: 32px;
                justify-content: center;
            }
            
            .food-details-text {
                font-size: 0.75rem;
                margin-bottom: 4px;
                color: var(--text-light);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .price-tag {
                font-size: 0.8rem;
                font-weight: 600;
                margin-top: auto; /* Push to bottom */
            }
            
            .veg-nonveg-indicator {
                top: 6px;
                right: 6px;
                padding: 1px 4px;
                font-size: 0.5rem;
                z-index: 2;
            }
            
            .delivery-time {
                white-space: nowrap;
            }

            .section-title {
                font-size: 1.2rem;
                margin-bottom: 12px;
                padding-left: 0;
            }
        }

        /* --- BOTTOM CATEGORIES GRID --- */
        .categories-bottom-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .category-card {
            text-decoration: none;
            border-radius: 16px;
            overflow: hidden;
            background: var(--white);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            position: relative;
            height: 280px;
            display: flex;
            flex-direction: column;
        }

        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .category-card.active {
            border: 3px solid var(--primary);
        }

        .category-card-image {
            position: relative;
            width: 100%;
            height: 180px;
            overflow: hidden;
        }

        .category-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .category-card:hover .category-card-image img {
            transform: scale(1.1);
        }

        .category-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: linear-gradient(0deg, rgba(0, 0, 0, 0.5) 0%, rgba(0, 0, 0, 0) 70%);
            opacity: 0.7;
            transition: opacity 0.3s;
        }

        .category-card:hover .category-overlay {
            opacity: 0.9;
        }

        .category-card-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--white);
        }

        .category-card-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--secondary);
            margin: 0 0 8px 0;
            transition: color 0.2s;
        }

        .category-card:hover .category-card-title {
            color: var(--primary);
        }

        .category-card-subtitle {
            font-size: 0.9rem;
            color: var(--text-light);
            margin: 0;
        }

        @media (max-width: 768px) {
            .categories-bottom-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                padding: 0;
            }

            .category-card {
                height: 200px;
            }

            .category-card-image {
                height: 120px;
            }

            .category-card-content {
                padding: 10px;
            }

            .category-card-title {
                font-size: 0.95rem;
                margin-bottom: 4px;
            }

            .category-card-subtitle {
                font-size: 0.75rem;
            }
        }

        /* --- FOOTER SPACING --- */
        .footer-spacer {
            height: 100px;
        }
    </style>
</head>

<body>

    <!-- STICKY NAVBAR -->
    <nav class="swiggy-nav">
        <div class="nav-content">
            <div class="left-nav">
                <a href="{{ url('/') }}" class="logo-box">
                    <i class="fa-solid fa-basket-shopping"></i>
                    <span class="logo-text">GrabBaskets</span>
                </a>
                <div class="location-box">
                    <span class="location-bold">Other</span>
                    <span>Bengaluru, Karnataka, India</span>
                    <i class="fa-solid fa-chevron-down ms-2 text-primary"></i>
                </div>
            </div>


            <div class="right-nav">
                <a href="{{ route('customer.food.cart') }}" class="nav-link-item position-relative">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span>Cart</span>
                    @if(session('cart_count', 0) > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            style="font-size: 0.6rem;">
                            {{ session('cart_count') }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('food.restaurants') }}" class="nav-link-item">
                    <i class="fa-solid fa-utensils"></i>
                    <span>Restaurants</span>
                </a>
                @auth
                    <div class="dropdown">
                        <a href="#" class="nav-link-item dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-user"></i>
                            <span>{{ Auth::user()->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><a class="dropdown-item py-2" href="{{ url('/profile') }}"><i
                                        class="fa-solid fa-user-circle me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item py-2" href="/food/my-orders"><i
                                        class="fa-solid fa-bag-shopping me-2"></i> Orders</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger py-2">
                                        <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="nav-link-item">
                        <i class="fa-solid fa-sign-in-alt"></i>
                        <span>Login</span>
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- STICKY SEARCH BAR -->
    <div class="search-section">
        <div class="search-container px-3">
            <form method="GET" action="{{ route('customer.food.index') }}">
                <i class="fa-solid fa-magnifying-glass search-icon-inside"></i>
                <input name="search" class="swiggy-search-input" placeholder="Search for restaurant, cuisine or a dish"
                    value="{{ request('search') }}" autocomplete="off" />
                <input type="hidden" name="category" value="{{ request('category') }}">
                <input type="hidden" name="veg" value="{{ request('veg') }}">
                <input type="hidden" name="sort" value="{{ request('sort') }}">
            </form>
        </div>
    </div>

    <div class="section-container">
        <!-- WHAT'S ON YOUR MIND (CATEGORIES) -->
        <h2 class="section-title">What's on your mind?</h2>
        <div class="categories-carousel">
            @foreach($foodCategories as $cat)
                @php
                    $categoryName = strtolower($cat['id']);
                    $isActive = request('category') === $cat['id'];
                    $url = route('customer.food.index', ['category' => $cat['id'], 'veg' => request('veg'), 'sort' => request('sort')]);

                    $categoryImages = [
                        'dessert' => asset('images/categories/dessert.jpeg'),
                        'beverage' => asset('images/categories/beverage.jpeg'),
                        'appetizer' => asset('images/categories/appetizer.jpeg'),
                        'main_course' => asset('images/categories/main_course.jpeg'),
                        'snack' => asset('images/categories/snack.jpeg'),
                        'salad' => asset('images/categories/salad.jpeg'),
                        'soup' => asset('images/categories/soup.jpeg'),
                        'staters' => asset('images/categories/staters.jpeg'),
                        'rice' => asset('images/categories/rice.jpeg'),
                        'seafood' => asset('images/categories/seafood.jpeg'),
                        'chicken' => asset('images/categories/chicken.jpeg'),
                        'mutton' => asset('images/categories/mutton.jpeg'),
                        'burger' => asset('images/categories/burger.jpeg'),
                        'pizza' => asset('images/categories/pizza.jpeg'),
                        'briyani' => asset('images/categories/briyani.jpeg'),
                    ];
                    $image = $categoryImages[$categoryName] ?? asset('images/categories/default.png');
                @endphp

                <a href="{{ $url }}" class="cat-item {{ $isActive ? 'active' : '' }}">
                    <div class="cat-image-wrapper">
                        <img src="{{ $image }}" alt="{{ $cat['name'] }}" />
                    </div>
                    <div class="cat-name">{{ ucwords($cat['name']) }}</div>
                </a>
            @endforeach
        </div>

        <hr class="my-5 opacity-10">

        <!-- FILTER BAR -->
        <div class="filter-bar">
            <a href="{{ route('customer.food.index') }}"
                class="filter-pill {{ !request('category') && !request('search') ? 'active' : '' }}">
                <i class="fa-solid fa-list"></i> All
            </a>

            <form method="GET" style="display:inline;">
                <input type="hidden" name="search" value="{{ request('search') }}" />
                <input type="hidden" name="category" value="{{ request('category') }}" />
                <input type="hidden" name="sort" value="{{ request('sort') }}" />
                <label class="filter-pill {{ request('veg') === '1' ? 'active' : '' }}">
                    <i class="fa-solid fa-leaf text-success"></i>
                    <select name="veg" onchange="this.form.submit()" style="font-size: inherit; color: inherit;">
                        <option value="">Dietary</option>
                        <option value="1" {{ request('veg') === '1' ? 'selected' : '' }}>Veg Only</option>
                        <option value="0" {{ request('veg') === '0' ? 'selected' : '' }}>Non-Veg</option>
                    </select>
                </label>
            </form>

            <form method="GET" style="display:inline;">
                <input type="hidden" name="search" value="{{ request('search') }}" />
                <input type="hidden" name="category" value="{{ request('category') }}" />
                <input type="hidden" name="veg" value="{{ request('veg') }}" />
                <label class="filter-pill {{ request('sort') ? 'active' : '' }}">
                    <i class="fa-solid fa-sort"></i>
                    <select name="sort" onchange="this.form.submit()" style="font-size: inherit; color: inherit;">
                        <option value="">Sort By</option>
                        <option value="costLow" {{ request('sort') === 'costLow' ? 'selected' : '' }}>Price: Low to High
                        </option>
                        <option value="costHigh" {{ request('sort') === 'costHigh' ? 'selected' : '' }}>Price: High to Low
                        </option>
                        <option value="ratingHigh" {{ request('sort') === 'ratingHigh' ? 'selected' : '' }}>Ratings: High
                            to Low</option>
                    </select>
                </label>
            </form>

            @if(request('category'))
                <div class="filter-pill active">
                    <i class="fa-solid fa-tag"></i> {{ ucwords(request('category')) }}
                </div>
            @endif
        </div>

        <!-- ITEMS SECTION -->
        <h2 class="section-title">
            @if(request('search'))
                Results for "{{ request('search') }}"
            @elseif(request('category'))
                {{ ucwords(str_replace('_', ' ', request('category'))) }} Specialists
            @else
                Top Restaurants for you
            @endif
        </h2>

        <div class="items-grid">
            @forelse($foods as $food)
                <a href="{{ route('customer.food.details', $food->id) }}" class="premium-food-card">
                    <div class="card-img-container">
                        <img src="{{ $food->first_image_url ?: 'https://via.placeholder.com/480x300?text=' . urlencode($food->name) }}"
                            alt="{{ $food->name }}"
                            onerror="this.onerror=null;this.src='https://via.placeholder.com/480x300?text=No+Image';">

                        <div class="img-overlay-gradient"></div>

                        @php $discount = rand(10, 60); @endphp
                        <div class="discount-tag">{{ $discount }}% OFF UPTO ₹120</div>

                        <div
                            class="veg-nonveg-indicator {{ $food->food_type === 'veg' ? 'veg-indicator' : 'nonveg-indicator' }}">
                            <i class="fa-solid fa-circle" style="font-size: 0.6rem;"></i>
                            {{ $food->food_type }}
                        </div>
                    </div>

                    <div class="food-info">
                        <div class="food-name-h">{{ $food->name }}</div>
                        <div class="meta-row">
                            <div class="rating-box">
                                <i class="fa-solid fa-star"></i>
                                {{ number_format($food->rating ?? 4.0, 1) }}
                            </div>
                            <div class="dot-sep"></div>
                            <div class="delivery-time">{{ rand(20, 45) }} mins</div>
                        </div>
                        <div class="food-details-text">
                            {{ $food->hotelOwner ? $food->hotelOwner->name : 'Gourmet Kitchen' }}
                        </div>
                        <div class="price-tag">
                            ₹{{ number_format($food->discounted_price ?? $food->price, 0) }} for one
                        </div>
                    </div>
                </a>
            @empty
                <div class="col-12 text-center py-5">
                    <img src="https://res.cloudinary.com/swiggy/image/upload/fl_lossy,f_auto,q_auto/2x_empty_cart_ybi7ss"
                        alt="Empty" style="width: 200px; opacity: 0.5;">
                    <h5 class="mt-4 text-muted">No items found matching your criteria</h5>
                </div>
            @endforelse
        </div>

        <!-- Food Categories Section at Bottom -->
        <hr class="my-5 opacity-10">

        <h2 class="section-title">Browse All Food Categories</h2>

        <div class="categories-bottom-grid">
            @foreach($foodCategories as $cat)
                @php
                    $categoryName = strtolower($cat['id']);
                    $isActive = request('category') === $cat['id'];
                    $url = route('customer.food.index', ['category' => $cat['id']]);

                    $categoryImages = [
                        'dessert' => asset('images/categories/dessert.jpeg'),
                        'beverage' => asset('images/categories/beverage.jpeg'),
                        'appetizer' => asset('images/categories/appetizer.jpeg'),
                        'main_course' => asset('images/categories/main_course.jpeg'),
                        'snack' => asset('images/categories/snack.jpeg'),
                        'salad' => asset('images/categories/salad.jpeg'),
                        'soup' => asset('images/categories/soup.jpeg'),
                        'staters' => asset('images/categories/staters.jpeg'),
                        'rice' => asset('images/categories/rice.jpeg'),
                        'seafood' => asset('images/categories/seafood.jpeg'),
                        'chicken' => asset('images/categories/chicken.jpeg'),
                        'mutton' => asset('images/categories/mutton.jpeg'),
                        'burger' => asset('images/categories/burger.jpeg'),
                        'pizza' => asset('images/categories/pizza.jpeg'),
                        'briyani' => asset('images/categories/briyani.jpeg'),
                    ];
                    $image = $categoryImages[$categoryName] ?? asset('images/categories/default.png');
                @endphp

                <a href="{{ $url }}" class="category-card {{ $isActive ? 'active' : '' }}">
                    <div class="category-card-image">
                        <img src="{{ $image }}" alt="{{ $cat['name'] }}" />
                        <div class="category-overlay"></div>
                    </div>
                    <div class="category-card-content">
                        <h3 class="category-card-title">{{ ucwords($cat['name']) }}</h3>
                        <p class="category-card-subtitle">Explore delicious {{ strtolower($cat['name']) }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="footer-spacer"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- MOBILE BOTTOM NAVIGATION -->
    <div class="mobile-bottom-nav">
        <a href="{{ route('customer.food.index') }}" class="mobile-nav-item {{ Route::is('customer.food.index') ? 'active' : '' }}">
            <i class="fa-solid fa-bowl-food"></i>
            <span>Food</span>
        </a>
        <a href="{{ route('food.restaurants') }}" class="mobile-nav-item {{ Route::is('food.restaurants') ? 'active' : '' }}">
            <i class="fa-solid fa-utensils"></i>
            <span>Dining</span>
        </a>
        <a href="{{ route('customer.food.cart') }}" class="mobile-nav-item {{ Route::is('customer.food.cart') ? 'active' : '' }}">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>Cart</span>
            @if(session('cart_count', 0) > 0)
                <span class="cart-badge-mobile">{{ session('cart_count') }}</span>
            @endif
        </a>
        @auth
            <a href="{{ url('/profile') }}" class="mobile-nav-item {{ Request::is('profile*') ? 'active' : '' }}">
                <i class="fa-solid fa-user"></i>
                <span>Profile</span>
            </a>
        @else
            <a href="{{ route('login') }}" class="mobile-nav-item">
                <i class="fa-solid fa-user"></i>
                <span>Login</span>
            </a>
        @endauth
    </div>

</body>
</html>