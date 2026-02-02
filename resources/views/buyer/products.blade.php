<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($category) ? $category->name : (isset($subcategory) ? $subcategory->name : 'Products') }} |
        {{ config('app.name', 'GrabBaskets') }}
    </title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary: #3C096C;
            --primary-light: #5A189A;
            --secondary: #FF6B00;
            --accent: #FFD700;
            --bg-body: #f5f5f5;
            --bg-white: #ffffff;
            --text-main: #212529;
            --text-muted: #6c757d;
            --border-light: #e9ecef;
            --header-height-mobile: 130px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            -webkit-tap-highlight-color: transparent;
        }

        /* Navbar & Header */
        .navbar-gradient {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .brand-logo {
            font-size: 1.6rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Desktop Filter Sidebar */
        .sidebar {
            position: sticky;
            top: 100px;
            max-height: calc(100vh - 120px);
            overflow-y: auto;
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .filter-section-title {
            font-weight: 800;
            font-size: 0.9rem;
            text-uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 15px;
            display: block;
        }

        .category-link {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            border-radius: 12px;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
            margin-bottom: 5px;
        }

        .category-link:hover {
            background: #f8f9fa;
            color: var(--primary);
            transform: translateX(5px);
        }

        .category-link.active {
            background: rgba(60, 9, 108, 0.1);
            color: var(--primary);
        }

        .sub-link {
            padding-left: 45px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Product Grid & Cards */
        .page-header {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .product-card {
            background: white;
            border: 1px solid var(--border-light);
            border-radius: 18px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            cursor: pointer;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-light);
        }

        .pc-image-box {
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            background: #fdfdfd;
            border-radius: 14px;
            padding: 10px;
        }

        .pc-image {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }

        .pc-title {
            font-weight: 700;
            font-size: 0.95rem;
            line-height: 1.4;
            height: 42px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            margin-bottom: 12px;
        }

        .price-section {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-top: auto;
        }

        .current-price {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
        }

        .old-price {
            font-size: 0.9rem;
            text-decoration: line-through;
            color: var(--text-muted);
        }

        .discount-tag {
            position: absolute;
            top: 15px;
            left: 15px;
            background: #ef233c;
            color: white;
            font-size: 0.75rem;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 10px;
            z-index: 10;
        }

        .add-btn {
            background: white;
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 8px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            width: 100%;
            margin-top: 15px;
            transition: all 0.2s;
        }

        .add-btn:hover {
            background: var(--primary);
            color: white;
        }

        /* Mobile Adjustments */
        @media (max-width: 991px) {
            .navbar-container {
                padding: 10px 15px;
            }

            .mobile-filter-bar {
                background: white;
                padding: 10px 15px;
                display: flex;
                overflow-x: auto;
                gap: 10px;
                scrollbar-width: none;
                margin-bottom: 15px;
                border-bottom: 1px solid var(--border-light);
                position: sticky;
                top: 70px;
                /* Below navbar */
                z-index: 999;
            }

            .filter-chip {
                white-space: nowrap;
                padding: 6px 16px;
                border-radius: 50px;
                background: #f0f0f0;
                color: var(--text-main);
                font-size: 0.85rem;
                font-weight: 600;
                text-decoration: none;
            }

            .filter-chip.active {
                background: var(--primary);
                color: white;
            }

            .product-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                padding: 0 12px;
            }

            .pc-image-box {
                height: 140px;
            }

            .pc-title {
                font-size: 0.85rem;
                height: 38px;
            }

            .current-price {
                font-size: 1.1rem;
            }

            .sidebar {
                display: none;
            }

            .footer {
                display: none !important;
                /* Specific user request: remove footer in mobile */
            }

            .desktop-header-actions {
                display: none !important;
            }

            .navbar-brand img {
                width: 120px;
            }
        }

        @media (min-width: 992px) {
            .mobile-filter-bar {
                display: none;
            }

            .product-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 25px;
            }
        }

        /* Search input styling for listing page */
        .listing-search {
            background: rgba(255, 255, 255, 0.15) !important;
            border: none !important;
            border-radius: 50px !important;
            padding: 8px 15px 8px 40px !important;
            color: white !important;
            font-size: 0.9rem;
            width: 300px;
            transition: all 0.3s;
        }

        .listing-search::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .listing-search:focus {
            width: 350px;
            background: white !important;
            color: black !important;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar-gradient">
        <div class="container d-flex align-items-center justify-content-between px-3">
            <a href="{{ url('/') }}" class="brand-logo">
                <i class="bi bi-bag-check-fill"></i> GrabBaskets
            </a>

            <!-- Desktop Search -->
            <form action="{{ url()->current() }}" method="GET" class="position-relative d-none d-lg-block">
                @foreach(request()->except(['q', 'page']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <i class="bi bi-search position-absolute text-white"
                    style="left: 15px; top: 50%; transform: translateY(-50%);"></i>
                <input type="text" name="q" class="listing-search" placeholder="Search in this category..."
                    value="{{ request('q') }}">
            </form>

            <div class="d-flex align-items-center gap-3 desktop-header-actions">
                @auth
                    <a href="{{ url('/profile') }}" class="text-white text-decoration-none fw-bold small">Hello,
                        {{ Auth::user()->name }}</a>
                @endauth
                <a href="{{ url('/cart') }}" class="btn btn-light btn-sm rounded-pill px-3 fw-bold text-primary">
                    <i class="bi bi-cart3"></i> Cart
                </a>
            </div>

            <!-- Mobile Search Icon (triggers bar below on scroll or stays hidden) -->
            <a href="{{ url('/cart') }}" class="d-lg-none text-white fs-4">
                <i class="bi bi-cart3"></i>
            </a>
        </div>
    </nav>

    <!-- Mobile Subcategory Chips -->
    <div class="mobile-filter-bar">
        <a href="{{ route('buyer.dashboard') }}" class="filter-chip"><i class="bi bi-house"></i> All</a>
        @if(isset($category))
            <a href="{{ route('buyer.productsByCategory', $category->id) }}"
                class="filter-chip {{ !isset($subcategory) ? 'active' : '' }}">All {{ $category->name }}</a>
            @foreach($category->subcategories as $sub)
                <a href="{{ route('buyer.productsBySubcategory', $sub->id) }}"
                    class="filter-chip {{ (isset($activeSubcategoryId) && $activeSubcategoryId == $sub->id) ? 'active' : '' }}">{{ $sub->name }}</a>
            @endforeach
        @elseif(isset($subcategory))
            @php $parentCat = $subcategory->category; @endphp
            <a href="{{ route('buyer.productsByCategory', $parentCat->id) }}" class="filter-chip">All
                {{ $parentCat->name }}</a>
            @foreach($parentCat->subcategories as $sub)
                <a href="{{ route('buyer.productsBySubcategory', $sub->id) }}"
                    class="filter-chip {{ $activeSubcategoryId == $sub->id ? 'active' : '' }}">{{ $sub->name }}</a>
            @endforeach
        @endif
    </div>

    <div class="container py-4">
        <div class="row g-4">
            <!-- Sidebar (Desktop) -->
            <aside class="col-lg-3 d-none d-lg-block">
                <div class="sidebar">
                    <span class="filter-section-title">Categories</span>
                    @foreach($categories as $cat)
                        <a href="{{ route('buyer.productsByCategory', $cat->id) }}"
                            class="category-link {{ $activeCategoryId == $cat->id ? 'active' : '' }}">
                            <span>{{ $cat->emoji ?? '📦' }}</span> <span class="ms-2">{{ $cat->name }}</span>
                        </a>
                        @if($activeCategoryId == $cat->id)
                            <div class="ms-1 mb-2">
                                @foreach($cat->subcategories as $sub)
                                    <a href="{{ route('buyer.productsBySubcategory', $sub->id) }}"
                                        class="category-link sub-link {{ $activeSubcategoryId == $sub->id ? 'active' : '' }}">
                                        {{ $sub->name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    @endforeach

                    <div class="mt-4 pt-4 border-top">
                        <span class="filter-section-title">Price Range</span>
                        <form action="{{ url()->current() }}" method="GET">
                            @foreach(request()->except(['price_min', 'price_max', 'page']) as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <div class="d-flex gap-2 mb-3">
                                <input type="number" name="price_min" placeholder="Min"
                                    class="form-control form-control-sm rounded-3" value="{{ request('price_min') }}">
                                <input type="number" name="price_max" placeholder="Max"
                                    class="form-control form-control-sm rounded-3" value="{{ request('price_max') }}">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100 rounded-3 fw-bold">Apply
                                Filter</button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- Product Listing -->
            <main class="col-lg-9">
                <div class="page-header">
                    <div>
                        <h2 class="fw-800 m-0" style="font-size: 1.5rem;">
                            {{ isset($subcategory) ? $subcategory->name : (isset($category) ? $category->name : 'All Products') }}
                        </h2>
                        <p class="text-muted small m-0 fw-600">{{ $products->total() }} items found</p>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small d-none d-md-block">Sort:</span>
                        <form action="{{ url()->current() }}" method="GET">
                            @foreach(request()->except(['sort', 'page']) as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <select name="sort" class="form-select form-select-sm rounded-pill border-light"
                                style="width: 140px;" onchange="this.form.submit()">
                                <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest</option>
                                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price:
                                    Low-High</option>
                                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price:
                                    High-Low</option>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="product-grid">
                    @forelse($products as $product)
                        <div class="product-card"
                            onclick="window.location.href='{{ route('product.details', $product->id) }}'">
                            @if($product->discount > 0)
                                <div class="discount-tag">{{ $product->discount }}% OFF</div>
                            @endif
                            <div class="pc-image-box">
                                <img src="{{ $product->image_url ?? asset('images/no-image.png') }}"
                                    alt="{{ $product->name }}" class="pc-image"
                                    onerror="this.src='{{ asset('images/no-image.png') }}'">
                            </div>
                            <div class="pc-title">{{ $product->name }}</div>
                            <div class="price-section">
                                <span
                                    class="current-price">₹{{ number_format($product->discount > 0 ? $product->price * (1 - $product->discount / 100) : $product->price, 0) }}</span>
                                @if($product->discount > 0)
                                    <span class="old-price">₹{{ number_format($product->price, 0) }}</span>
                                @endif
                            </div>
                            @auth
                                <button class="add-btn"
                                    onclick="event.stopPropagation(); addToCart({{ $product->id }})">ADD</button>
                            @else
                                <a href="{{ route('login') }}" class="add-btn text-center text-decoration-none"
                                    onclick="event.stopPropagation()">ADD</a>
                            @endauth
                        </div>
                    @empty
                        <div class="col-12 text-center py-5">
                            <i class="bi bi-search display-1 text-muted opacity-25"></i>
                            <h4 class="mt-3 text-muted">No products found for this criteria</h4>
                            <a href="{{ url()->current() }}" class="btn btn-primary mt-2">Clear Filters</a>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                <div class="mt-5 d-flex justify-content-center">
                    {{ $products->links('pagination::bootstrap-5') }}
                </div>
            </main>
        </div>
    </div>

    <!-- Desktop Footer -->
    <footer class="bg-dark text-white pt-5 pb-4 mt-5 d-none d-lg-block">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <h5 class="fw-bold mb-4">GrabBaskets</h5>
                    <p class="text-white-50 small pe-5">Delivering quality products across Tamil Nadu with a focus on
                        speed and reliability.</p>
                </div>
                <div class="col-md-2">
                    <h6 class="fw-bold text-uppercase mb-4">Quick Links</h6>
                    <ul class="list-unstyled small d-grid gap-2">
                        <li><a href="{{ url('/cart') }}" class="text-white-50 text-decoration-none">Cart</a></li>
                        <li><a href="{{ url('/wishlist') }}" class="text-white-50 text-decoration-none">Wishlist</a>
                        </li>
                        <li><a href="{{ url('/profile') }}" class="text-white-50 text-decoration-none">Account</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6 class="fw-bold text-uppercase mb-4">Help & Support</h6>
                    <ul class="list-unstyled small d-grid gap-2">
                        <li><a href="#" class="text-white-50 text-decoration-none">Contact Us</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Cancellation & Returns</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Shipping Policy</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6 class="fw-bold text-uppercase mb-4">Newsletter</h6>
                    <p class="text-white-50 small">Subscribe for the latest offers!</p>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control border-0" placeholder="Email Address">
                        <button class="btn btn-primary" type="button">Join</button>
                    </div>
                </div>
            </div>
            <hr class="border-secondary mt-5">
            <p class="text-center text-white-50 small mb-0">© 2025 GrabBaskets. All rights reserved.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addToCart(productId) {
            fetch('{{ route('cart.add') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ product_id: productId, quantity: 1 })
            })
                .then(res => {
                    if (res.ok) window.location.href = '{{ route('cart.index') }}';
                    else window.location.href = '{{ route('login') }}';
                });
        }
    </script>
</body>

</html>