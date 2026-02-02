<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $seller->store_name ?? $seller->name }} - Products</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #eef2f3, #dfe9f3);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* 🌈 Glassmorphism Navbar */
        .glass-nav {
            background: linear-gradient(90deg, rgba(10, 26, 63, 0.85), rgba(57, 73, 171, 0.85));
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            z-index: 1000;
        }

        /* ✨ Glow line */
        .navbar-glow {
            height: 3px;
            background: linear-gradient(90deg, #00c6ff, #0072ff, #00c6ff);
            background-size: 200% auto;
            animation: glowMove 4s linear infinite;
        }

        @keyframes glowMove {
            from {
                background-position: 0% center;
            }

            to {
                background-position: 200% center;
            }
        }

        /* 🛍️ Brand text animation */
        .navbar-brand .brand-text {
            transition: all 0.3s ease;
        }

        .navbar-brand:hover .brand-text {
            color: #ffd700;
            text-shadow: 0 0 10px #ffd700;
            transform: scale(1.05);
        }

        /* 💫 Center Store Info */
        .store-info h5 {
            letter-spacing: 0.8px;
            animation: fadeInUp 0.8s ease-in-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }



        .animate-text {
            background: linear-gradient(90deg, #ff9a9e, #fad0c4, #a1c4fd);
            -webkit-background-clip: text;

            -webkit-text-fill-color: transparent;
        }

        /* 🧑‍💼 Profile Avatar */
        .navbar .nav-link img {
            border: 2px solid #fff;
            transition: transform 0.3s ease;
        }

        .navbar .nav-link:hover img {
            transform: scale(1.1);
        }

        /* 🔽 Dropdown Menu */
        .dropdown-menu {
            animation: fadeIn 0.25s ease-in-out;
            min-width: 190px;
            border-radius: 12px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-item {
            font-weight: 500;
            color: #333;
            transition: all 0.25s ease;
        }

        .dropdown-item:hover {
            background: linear-gradient(90deg, #eef2ff, #e0e7ff);
            transform: translateX(5px);
        }

        /* 🔍 Search Bar Styling */
        .search-bar .form-control {
            border: 1px solid rgba(10, 26, 63, 0.3);
            backdrop-filter: blur(8px);
            background: rgba(255, 255, 255, 0.15);
            color: black;
        }

        .search-bar .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-bar .btn {
            background: linear-gradient(45deg, #b2b5baff, #3949ab);
            border: none;
            color: black;
        }

        .search-bar .btn:hover {
            background: linear-gradient(45deg, #3949ab, #0a1a3f);
        }

        /* Product Card */
        .product-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            /* background: #fff; */
            transition: all 0.1s ease;
            position: relative;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .product-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

       

        .product-card:hover .product-img {
            transform: scale(1.1);
            filter: brightness(0.9);
        }

        .card-body {
            text-align: center;
            padding: 1.5rem;
        }

        .product-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.6rem;
            color: #0a1a3f;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2e3d74;
        }

        .btn-view {
            margin-top: 0.8rem;
            background: linear-gradient(45deg, #0a1a3f, #3949ab);
            border: none;
            color: #fff;
            border-radius: 50px;
            padding: 0.5rem 1.4rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-view:hover {
            background: linear-gradient(45deg, #3949ab, #0a1a3f);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transform: scale(1.05);
        }



        /* Increase store info text size */
        .store-info h5.sellername {
            font-size: 25px;
            /* Increase main store name size */
        }

        .store-info small {
            font-size: 1.1rem;
        }


        .pagination .page-item.active .page-link {
            background: linear-gradient(45deg, #0a1a3f, #3949ab);
            color: #fff;
        }

        /* 🛍️ Product Card Uniform Layout */
        .product-card {
            border: none;
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            /* Make all cards same height */
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            background: #fff;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        /* 🛍️ Product Card Layout */
        .product-card {
            border: none;
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            background: #fff;
        }

        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        /* 🖼️ Image Styling */
        .product-img {
            width: 100%;
            height: 180px;
            /* Rectangle shape for desktop */
            object-fit: contain;
            /* fills area without white space */
            transition: transform 0.3s ease, filter 0.3s ease;
        }

        .product-card:hover .product-img {
            transform: scale(1.05);
            filter: brightness(0.95);
        }

        /* Card body content */
        .card-body {
            text-align: center;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Product title and price */
        .product-title {
            font-size: 1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
            line-height: 1.2;
        }

        .product-price {
            font-size: 1rem;
            font-weight: 700;
            color: #ff5722;
            margin-bottom: 0.75rem;
        }

        /* Buy Now button */
        .btn-view {
            background-color: #ff5722;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .btn-view:hover {
            background-color: #e64a19;
        }

        /* 📱 Responsive Adjustments */
        @media (max-width: 576px) {

            /* 2 cards per row */
            .col-6 {
                flex: 0 0 50%;
                max-width: 50%;
            }

            /* Rectangle image smaller on mobile */
            .product-img {
                height: 150px;
                object-fit: cover;
            }

            .product-title {
                font-size: 0.9rem;
            }

            .product-price {
                font-size: 0.85rem;
            }

            .btn-view {
                font-size: 0.8rem;
                padding: 6px 10px;
            }
        }

        /* Mobile Optimization */
        @media (max-width: 768px) {
            .store-info h5 {
                font-size: 1rem;
            }

            .navbar-brand {
                font-size: 1.2rem;
            }
        }
    </style>
</head>

<body>

    <!-- 🌟 Navbar -->
    <nav class="navbar navbar-expand-lg py-3 glass-nav">
        <div class="container-fluid px-4">

            <!-- Left: Brand -->
            <a class="navbar-brand d-flex align-items-center text-white fw-bold fs-4" href="{{ route('home') }}">
                <div class="logo">
                    <img src="{{ asset('asset/images/logo-image.png') }}" alt="Logo" width="150px">
                </div>
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler text-white border-0" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false"
                aria-label="Toggle navigation">
                <i class="bi bi-list fs-2"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">

                <!-- Center: Store Info -->
                <div class="mx-auto text-center text-white store-info">
                    <h5 class="fw-bold mb-1 animate-text sellername">{{ $seller->store_name ?? $seller->name }}</h5>
                    <small class="text-light opacity-75">This deal is too good to last. Grab yours before it's gone✨</small>
                </div>

                <!-- Right: Profile Dropdown -->
                <ul class="navbar-nav ms-auto d-flex align-items-center">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center" href="#"
                            id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Profile"
                                class="rounded-circle me-2" width="35" height="35">
                            <span class="fw-semibold">Profile</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3"
                            aria-labelledby="profileDropdown">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-heart-fill text-danger me-2"></i>
                                    Wishlist</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-bag-check-fill text-primary me-2"></i>
                                    Orders</a></li>
                            <li><a class="dropdown-item" href="{{ route('cart.index') }}"><i
                                        class="bi bi-cart-fill text-success me-2"></i> Cart</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger fw-semibold" href="{{ route('logout') }}"><i
                                        class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Glowing bottom border -->
        <div class="navbar-glow"></div>
    </nav>
    <!-- 🔍 Search Bar -->
    <div class="container-fluid py-3 search-bar">
        <div class="d-flex justify-content-center">
            <div class="input-group" style="max-width: 500px;">
                <input type="text" id="productSearch" class="form-control rounded-start" placeholder="Search products..."
                    aria-label="Search">
                <button class="btn rounded-end" type="button">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 🛒 Main Products Section -->
    <!-- 🛒 Main Products Section -->
    <div class="container py-5">
        @if($products->count())
        
        <!-- Display products grouped by category -->
        @foreach($productsByCategory as $categoryId => $categoryProducts)
            @php
                $category = $categoryProducts->first()->category;
            @endphp
            <div class="mb-5">
                <h3 class="fw-bold mb-4 text-dark" style="border-bottom: 3px solid #ff5722; padding-bottom: 10px; display: inline-block;">
                    📦 {{ $category->name ?? 'Uncategorized' }}
                </h3>
                <div class="row g-3">
                    @foreach($categoryProducts as $p)
                    <div class="col-6 col-md-4 col-lg-3 product-card-wrapper" data-product-name="{{ strtolower($p->name) }}">
                        <div class="card product-card h-100">
                            @if($p->image || $p->image_data)
                            <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="product-img">
                            @endif

                            <div class="card-body d-flex flex-column justify-content-between">
                                <div>
                                    <h5 class="product-title">{{ $p->name }}</h5>
                                    <div class="product-price">₹{{ number_format($p->price, 2) }}</div>
                                </div>
                                <a href="{{ route('product.details', $p->id) }}" class="btn btn-view mt-auto">
                                    <i class="bi bi-bag-heart-fill"></i> Buy Now
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        @else
        <div class="text-center py-5">
            <h4 class="text-muted">No products found for this store.</h4>
        </div>
        @endif
    </div>


    <!-- ✨ Live Search Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('productSearch');
            const productCards = document.querySelectorAll('.product-card-wrapper');

            searchInput.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();

                productCards.forEach(card => {
                    const productName = card.getAttribute('data-product-name');
                    if (productName && productName.includes(query)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    </script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>