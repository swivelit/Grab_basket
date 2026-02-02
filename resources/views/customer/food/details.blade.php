<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $food->name }} | GrabBasket</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #FF6B00;
            --primary-current: #FF6B00;
            --text-dark: #212121;
            --text-light: #757575;
            --bg-body: #f7f8fa;
            --white: #ffffff;
            --border-color: #e0e0e0;
        }

        body {
            font-family: "Poppins", sans-serif;
            background-color: var(--bg-body);
            color: var(--text-dark);
            margin: 0;
            padding-bottom: 80px; /* Mobile sticky bar space */
        }

        /* --- Navbar --- */
        .navbar-ecommerce {
            background-color: var(--white);
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .navbar-brand-custom {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* --- Breadcrumbs --- */
        .breadcrumb-custom {
            font-size: 0.85rem;
            color: var(--text-light);
            background: transparent;
            padding: 15px 0;
            margin-bottom: 0;
        }
        .breadcrumb-custom a {
            color: var(--text-light);
            text-decoration: none;
        }
        .breadcrumb-custom a:hover { color: var(--primary); }
        .breadcrumb-custom .active { color: var(--text-dark); font-weight: 500; }

        /* --- Product Layout --- */
        .product-container {
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 30px;
        }

        /* Left Column: Image */
        .product-gallery {
            text-align: center;
        }

        .main-image-wrapper {
            width: 100%;
            height: 0;
            padding-bottom: 75%; /* 4:3 Aspect Ratio */
            position: relative;
            background: #f9f9f9;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .main-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        .main-image:hover { transform: scale(1.02); }

        /* Right Column: Details */
        .product-details {
            padding-left: 10px;
        }

        .hotel-link {
            font-size: 0.9rem;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            display: inline-block;
        }

        .product-title {
            font-size: 1.8rem;
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: 12px;
            color: #000;
        }

        .rating-badge {
            background: #388e3c;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            vertical-align: middle;
        }

        .rating-count {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-left: 8px;
            vertical-align: middle;
        }

        .price-wrapper {
            margin: 20px 0;
            display: flex;
            align-items: baseline;
            gap: 10px;
        }

        .current-price {
            font-size: 2rem;
            font-weight: 700;
            color: #212121;
        }

        .tax-note {
            font-size: 0.75rem;
            color: var(--text-light);
        }

        .feature-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 24px;
        }

        .feature-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #f1f3f5;
            border-radius: 4px;
            font-size: 0.85rem;
            color: #555;
            font-weight: 500;
        }
        
        .veg-icon { color: #198754; }
        .nonveg-icon { color: #dc3545; }

        .product-desc {
            font-size: 0.95rem;
            line-height: 1.6;
            color: var(--text-dark);
            margin-bottom: 30px;
        }

        /* Actions */
        .action-row {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn-add-cart {
            background: #ff9f00; /* Flipkart-ish Orange */
            color: white;
            border: none;
            padding: 12px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: 0.2s;
            flex-grow: 1;
            max-width: 300px;
        }
        
        .btn-add-cart:hover {
            background: #f39000;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .btn-wishlist {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 1px solid var(--border-color);
            background: white;
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: 0.2s;
            text-decoration: none;
        }
        .btn-wishlist:hover {
            background: #fdfdfd;
            color: #e53935;
            border-color: #e53935;
        }

        /* --- Related Products --- */
        .related-section {
            background: var(--white);
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .section-header {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-dark);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }

        .related-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            display: block;
            text-decoration: none;
            color: inherit;
            transition: 0.2s;
            height: 100%;
        }
        
        .related-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .related-img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-bottom: 1px solid #f0f0f0;
        }

        .related-body { padding: 12px; }

        .related-title {
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .related-price {
            font-size: 1rem;
            font-weight: 600;
            color: #212121;
        }

        /* --- Mobile Action Bar --- */
        .mobile-action-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: white;
            padding: 10px 15px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 1030;
            display: none;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 767px) {
            .mobile-action-bar { display: flex; }
            .desktop-actions { display: none; }
            .product-details { padding-left: 0; padding-top: 20px; }
            .breadcrumb-custom { display: none; }
            .product-title { font-size: 1.4rem; }
            .current-price { font-size: 1.6rem; }
            body { background: white; } /* Seamless mobile look */
            .product-container { box-shadow: none; padding: 0 15px; }
            .related-section { box-shadow: none; padding: 20px 15px; }
        }

        @media (min-width: 768px) {
            body { padding-bottom: 0; }
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-ecommerce">
        <div class="container-lg d-flex justify-content-between align-items-center">
            <a href="{{ route('customer.food.index') }}" class="navbar-brand-custom">
                <i class="fa-solid fa-basket-shopping"></i> GrabBasket
            </a>
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('customer.food.index') }}" class="text-decoration-none text-dark fw-500 d-none d-md-block">Menu</a>
                <a href="{{ route('customer.food.cart') }}" class="btn btn-outline-secondary position-relative border-0">
                    <i class="fa-solid fa-cart-shopping fs-5"></i>
                    @if(session('cart_count', 0) > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                        {{ session('cart_count') }}
                    </span>
                    @endif
                </a>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb (Desktop) -->
    <div class="container-lg d-none d-md-block">
        <nav class="breadcrumb-custom">
            <a href="{{ route('customer.food.index') }}">Home</a> <span class="mx-2">/</span>
            <a href="{{ route('customer.food.index') }}">Food</a> <span class="mx-2">/</span>
            <span class="active text-truncate" style="max-width: 300px; display:inline-block; vertical-align:bottom;">{{ $food->name }}</span>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="container-lg mt-md-0 mt-3">
        <div class="product-container">
            <div class="row">
                
                <!-- Left: Image -->
                <div class="col-md-5 product-gallery">
                    <div class="main-image-wrapper">
                        <img src="{{ $food->first_image_url ?: 'https://via.placeholder.com/600x400?text=' . urlencode($food->name) }}" 
                             class="main-image" 
                             alt="{{ $food->name }}"
                             onerror="this.onerror=null;this.src='https://via.placeholder.com/600x400?text=No+Image';">
                    </div>
                    <!-- Thumbnails for multiple images -->
                    @if(!empty($food->image_urls) && count($food->image_urls) > 1)
                    <div class="d-flex gap-2 mt-3 justify-content-center">
                         @foreach($food->image_urls as $img)
                            <img src="{{ $img }}" style="width:60px; height:60px; border-radius:4px; border:1px solid #ddd; cursor:pointer; object-fit:cover;">
                         @endforeach
                    </div>
                    @endif
                </div>

                <!-- Right: Details -->
                <div class="col-md-7 product-details">
                    <a href="#" class="hotel-link">{{ $food->hotelOwner ? $food->hotelOwner->name : 'GrabBasket Exclusive' }}</a>
                    
                    <h1 class="product-title">{{ $food->name }}</h1>

                    <div class="mb-3">
                        <span class="rating-badge">
                            {{ number_format($food->rating ?? 4.0, 1) }} <i class="fa-solid fa-star" style="font-size: 0.7rem;"></i>
                        </span>
                        <span class="rating-count">88 Ratings & 12 Reviews</span>
                    </div>

                    <div class="price-wrapper">
                        <span class="current-price">₹{{ number_format($food->getFinalPrice(), 0) }}</span>
                        <!-- Mocking original price logic if needed -->
                        <!-- <span class="text-decoration-line-through text-muted small">₹999</span> -->
                        <!-- <span class="text-success small fw-bold">10% off</span> -->
                    </div>

                    <div class="feature-badges">
                        <span class="feature-pill">
                            @if($food->food_type === 'veg')
                                <i class="fa-solid fa-circle veg-icon fs-6"></i> Pure Veg
                            @else
                                <i class="fa-solid fa-play nonveg-icon fs-6" style="transform: rotate(-90deg);"></i> Non-Veg
                            @endif
                        </span>
                        <span class="feature-pill">
                            <i class="fa-regular fa-clock text-primary"></i> {{ $food->preparation_time ?? '25' }} mins
                        </span>
                        <span class="feature-pill">
                            <i class="fa-solid fa-truck-fast text-primary"></i> Fast Delivery
                        </span>
                    </div>

                    <h5 class="fw-bold fs-6 text-uppercase text-muted mb-2">Description</h5>
                    <p class="product-desc">
                        {{ $food->description ?: 'Freshly prepared with high-quality ingredients. This dish aims to provide a delightful culinary experience, perfect for any meal of the day.' }}
                    </p>

                    <!-- Desktop Actions -->
                    <div class="desktop-actions mt-4 action-row">
                        <form action="{{ route('customer.food.cart.add') }}" method="POST" class="flex-grow-1" style="max-width: 300px;">
                            @csrf
                            <input type="hidden" name="food_id" value="{{ $food->id }}">
                            <button type="submit" class="btn-add-cart w-100">
                                <i class="fa-solid fa-cart-plus me-2"></i> ADD TO CART
                            </button>
                        </form>
                        <a href="#" class="btn-wishlist" title="Add to Wishlist"><i class="fa-regular fa-heart"></i></a>
                    </div>

                </div>
            </div>
        </div>

        <!-- Related Products -->
        <div class="related-section mb-5">
            <div class="section-header">Similar Items</div>
            <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-5 g-3">
                @php
                    $recommended = $food->hotelOwner
                        ? $food->hotelOwner->foodItems()
                            ->where('id', '!=', $food->id)
                            ->where('is_available', 1)
                            ->whereNotNull('image')
                            ->where('image', '!=', '')
                            ->take(5)
                            ->get()
                        : collect();
                @endphp

                @forelse($recommended as $item)
                <div class="col">
                    <a href="{{ route('customer.food.details', $item->id) }}" class="related-card">
                        <img src="{{ $item->first_image_url ?: 'https://via.placeholder.com/300x200?text=' . urlencode($item->name) }}" 
                             class="related-img" 
                             alt="{{ $item->name }}"
                             onerror="this.onerror=null;this.src='https://via.placeholder.com/300x200?text=No+Image';">
                        <div class="related-body">
                            <div class="related-title">{{ $item->name }}</div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <div class="related-price">₹{{ number_format($item->getFinalPrice(), 0) }}</div>
                                <div class="badge bg-success" style="font-size: 0.65rem;">
                                    {{ number_format($item->rating ?? 4.0, 1) }} <i class="fa-solid fa-star"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                @empty
                <div class="col-12 py-3 text-muted">No similar items found currently.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Mobile Sticky Footer -->
    <div class="mobile-action-bar">
        <div class="d-flex flex-column flex-grow-1">
            <span class="text-muted" style="font-size: 0.75rem;">Total Price</span>
            <span class="fw-bold fs-5">₹{{ number_format($food->getFinalPrice(), 0) }}</span>
        </div>
        <form action="{{ route('customer.food.cart.add') }}" method="POST" class="m-0 flex-grow-1">
            @csrf
            <input type="hidden" name="food_id" value="{{ $food->id }}">
            <button type="submit" class="btn-add-cart w-100 py-2 fs-6">Add to Cart</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>