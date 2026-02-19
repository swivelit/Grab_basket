<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Discover Food - GrabBaskets</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary: #6C63FF;
            --primary-dark: #4B46E5;
            --accent: #FF6584;
            --bg-body: #F3F4F6;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow-soft: 0 10px 40px -10px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 20px 50px -10px rgba(108, 99, 255, 0.2);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-body);
            color: #2D3748;
            overflow-x: hidden;
        }

        /* Animated Background Mesh */
        .bg-mesh {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            background:
                radial-gradient(at 0% 0%, hsla(253, 16%, 7%, 1) 0, transparent 50%),
                radial-gradient(at 50% 0%, hsla(225, 39%, 30%, 1) 0, transparent 50%),
                radial-gradient(at 100% 0%, hsla(339, 49%, 30%, 1) 0, transparent 50%);
            background-size: cover;
            opacity: 0.05;
        }

        /* Glass Navbar */
        .navbar-glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            padding: 1rem 0;
            transition: all 0.3s ease;
        }

        .navbar-glass.scrolled {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .brand-logo {
            font-weight: 800;
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Hero Section */
        .hero-section {
            padding: 80px 0 60px;
            position: relative;
        }

        .hero-card {
            background: linear-gradient(135deg, #6C63FF 0%, #3F3D56 100%);
            border-radius: 30px;
            padding: 60px 40px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 60px -20px rgba(108, 99, 255, 0.4);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .hero-content {
            z-index: 2;
            max-width: 600px;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 20px;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 30px;
            font-weight: 300;
        }

        .hero-btn {
            background: white;
            color: var(--primary);
            padding: 15px 35px;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .hero-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            color: var(--primary);
        }

        /* Floating Shapes */
        .floating-shape {
            position: absolute;
            opacity: 0.1;
            z-index: 1;
            animation: float 20s infinite ease-in-out;
        }

        .shape-1 {
            top: -20%;
            right: -10%;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: white;
        }

        .shape-2 {
            bottom: -10%;
            left: -5%;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: var(--accent);
            animation-delay: -5s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translate(0, 0) rotate(0deg);
            }

            33% {
                transform: translate(30px, -50px) rotate(10deg);
            }

            66% {
                transform: translate(-20px, 20px) rotate(-5deg);
            }
        }

        /* Categories Section */
        .category-scroll {
            display: flex;
            gap: 15px;
            overflow-x: auto;
            padding: 20px 5px;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .category-scroll::-webkit-scrollbar {
            display: none;
        }

        .cat-pill {
            background: white;
            padding: 10px 25px;
            border-radius: 50px;
            color: var(--text-main);
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: 1px solid transparent;
        }

        .cat-pill:hover,
        .cat-pill.active {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(108, 99, 255, 0.2);
        }

        /* Shop Grid */
        .shop-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            padding-bottom: 60px;
        }

        .shop-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            position: relative;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
            color: inherit;
            border: 1px solid rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .shop-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--shadow-hover);
            z-index: 10;
        }

        .shop-img-container {
            height: 220px;
            position: relative;
            overflow: hidden;
        }

        .shop-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .shop-card:hover .shop-img {
            transform: scale(1.1);
        }

        .shop-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0) 50%, rgba(0, 0, 0, 0.7) 100%);
            opacity: 0.6;
            transition: opacity 0.3s;
        }

        .shop-card:hover .shop-overlay {
            opacity: 0.4;
        }

        .status-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(4px);
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #10B981;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #10B981;
            border-radius: 50%;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
            animation: pulse-green 2s infinite;
        }

        @keyframes pulse-green {
            0% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
            }

            70% {
                box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        .shop-details {
            padding: 24px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .shop-name {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .shop-tags {
            font-size: 0.9rem;
            color: #64748B;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #F1F5F9;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #475569;
        }

        .info-icon {
            color: var(--primary);
            font-size: 1.1rem;
        }

        .rating-pill {
            background: #DEF7EC;
            color: #03543F;
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .hero-card {
                padding: 40px 20px;
                text-align: center;
                flex-direction: column;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-content {
                margin-bottom: 30px;
            }

            .shop-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="bg-mesh"></div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-glass sticky-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand brand-logo" href="{{ route('home') }}">
                <i class="bi bi-rocket-takeoff-fill"></i> GrabBaskets
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                data-bs-target="#navContent">
                <i class="bi bi-list fs-1 text-dark"></i>
            </button>

            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-3"><a href="/food/customer" class="nav-link fw-bold text-dark">Home</a>
                    </li>
                    <li class="nav-item me-3"><a href="#" class="nav-link fw-bold text-primary">Restaurants</a></li>
                    @auth
                        <li class="nav-item">
                            <a href="{{ route('customer.food.cart') }}" class="btn btn-dark rounded-pill px-4 fw-bold">
                                <i class="bi bi-bag-fill me-2"></i>Cart
                            </a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a href="{{ route('login') }}" class="btn btn-outline-dark rounded-pill px-4 fw-bold">Login</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">

        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-card">
                <div class="floating-shape shape-1"></div>
                <div class="floating-shape shape-2"></div>

                <div class="hero-content">
                    <h1 class="hero-title">Hungry?<br>We got you.</h1>
                    <p class="hero-subtitle">Discover the best food from top-rated restaurants near you, delivered at
                        lightning speed.</p>
                    <a href="#shops" class="hero-btn">
                        Explore Now <i class="bi bi-arrow-right-circle-fill fs-5"></i>
                    </a>
                </div>

                <div class="d-none d-md-block" style="z-index: 2;">
                    <!-- 3D-ish Illustration Placeholder (CSS can only do so much) -->
                    <img src="https://cdn3d.iconscout.com/3d/premium/thumb/burger-5590924-4652927.png" alt="Burger 3D"
                        style="width: 300px; filter: drop-shadow(0 20px 30px rgba(0,0,0,0.3)); transform: rotate(-15deg);">
                </div>
            </div>
        </section>

       

        <!-- Shops Grid -->
        <div id="shops">
            <h3 class="fw-bold mb-4 ms-2">Popular Spots</h3>

            <div class="shop-grid">
                @forelse($shops as $shop)
                    <a href="{{ route('food.shops.show', $shop->id) }}" class="shop-card">
                        <div class="shop-img-container">
                            <div class="status-badge">Open Now</div>
                            <div class="shop-overlay"></div>
                            @php
                                $image = $shop->logo
                                    ? (Str::startsWith($shop->logo, 'http') ? $shop->logo : asset('storage/' . $shop->logo))
                                    : ($shop->restaurant_images && count($shop->restaurant_images) > 0
                                        ? (Str::startsWith($shop->restaurant_images[0], 'http') ? $shop->restaurant_images[0] : asset('storage/' . $shop->restaurant_images[0]))
                                        : 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80');
                            @endphp
                            <img src="{{ $image }}" class="shop-img" alt="{{ $shop->name }}"
                                onerror="this.src='https://via.placeholder.com/400x300?text=Restaurant'">
                        </div>

                        <div class="shop-details">
                            <h4 class="shop-name">{{ $shop->restaurant_name ?? $shop->name }}</h4>
                            <div class="shop-tags">
                                <i class="bi bi-tag-fill me-1 text-secondary"></i>
                                {{ $shop->cuisine_type ?? 'Multi-Cuisine, Fast Food' }}
                            </div>

                            <div class="info-row">
                                <div class="rating-pill">
                                    <i class="bi bi-star-fill" style="font-size: 0.7rem;"></i> {{ $shop->rating ?? '4.5' }}
                                </div>
                                <div class="info-item">
                                    <i class="bi bi-clock-fill info-icon"></i> {{ $shop->delivery_time ?? '25-35' }} min
                                </div>
                                <div class="info-item ms-auto text-primary">
                                    <i class="bi bi-arrow-right-circle-fill fs-5"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-12 py-5 text-center">
                        <div class="bg-white rounded-5 p-5 shadow-sm d-inline-block">
                            <img src="https://cdn-icons-png.flaticon.com/512/11329/11329060.png" width="100"
                                class="mb-4 opacity-75">
                            <h4>No restaurants found</h4>
                            <p class="text-muted">We're expanding to your area soon!</p>
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="d-flex justify-content-center mt-5 mb-5">
                {{ $shops->links() }}
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer class="text-center py-4 text-muted border-top mt-5 bg-white">
        <p class="mb-0">&copy; {{ date('Y') }} GrabBaskets. Crafted with <i class="bi bi-heart-fill text-danger"></i>
            for foodies.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar Scrolled Effect
        window.addEventListener('scroll', function () {
            if (window.scrollY > 50) {
                document.getElementById('mainNav').classList.add('scrolled');
            } else {
                document.getElementById('mainNav').classList.remove('scrolled');
            }
        });
    </script>
</body>

</html>