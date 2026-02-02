<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $product->name }} | {{ config('app.name', 'GrabBaskets') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary: #3C096C;
            --primary-light: #5A189A;
            --secondary: #FF6B00;
            --accent: #FFD700;
            --bg-body: #f8f9fa;
            --bg-white: #ffffff;
            --text-main: #212529;
            --text-muted: #6c757d;
            --border-light: #e9ecef;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            padding-bottom: 80px; /* Space for mobile sticky CTA */
        }

        .navbar-gradient {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            padding: 12px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .brand-logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .product-card-main {
            background: white;
            border-radius: 24px;
            padding: 30px;
            box-shadow: var(--shadow-md);
            margin-top: 30px;
        }

        .product-image-container {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 400px;
            position: relative;
        }

        .product-image-container img {
            max-width: 100%;
            max-height: 450px;
            object-fit: contain;
        }

        .badge-category {
            background: rgba(60, 9, 108, 0.1);
            color: var(--primary);
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
        }

        .product-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--text-main);
            margin-top: 15px;
            margin-bottom: 10px;
        }

        .price-container {
            margin: 20px 0;
            padding: 15px;
            background: #fff;
            border-radius: 16px;
        }

        .current-price {
            font-size: 2.4rem;
            font-weight: 800;
            color: var(--primary);
        }

        .original-price {
            text-decoration: line-through;
            color: var(--text-muted);
            font-size: 1.2rem;
            margin-left: 10px;
        }

        .discount-tag {
            color: #d90429;
            font-weight: 700;
            font-size: 1.1rem;
            margin-left: 10px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-cart-main {
            background: var(--primary);
            color: white;
            border: none;
            padding: 15px 30px;
            font-weight: 700;
            border-radius: 12px;
            flex: 1;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(60, 9, 108, 0.2);
        }

        .btn-cart-main:hover {
            transform: translateY(-3px);
            background: var(--primary-light);
            box-shadow: 0 12px 25px rgba(60, 9, 108, 0.3);
            color: white;
        }

        .btn-wishlist-toggle {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            border: 2px solid var(--border-light);
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-wishlist-toggle:hover {
            border-color: #ff4757;
            background: #fffafa;
        }

        .tab-navigation {
            border-bottom: 2px solid var(--border-light);
            margin-top: 50px;
            margin-bottom: 25px;
        }

        .nav-link-custom {
            color: var(--text-muted);
            font-weight: 700;
            padding: 12px 20px;
            text-decoration: none;
            position: relative;
            display: inline-block;
        }

        .nav-link-custom.active {
            color: var(--primary);
        }

        .nav-link-custom.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
        }

        /* Review Styles */
        .review-item {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: var(--shadow-sm);
        }

        .rating-stars {
            color: #FFD700;
        }

        /* Related Products */
        .related-card {
            background: white;
            border-radius: 16px;
            padding: 15px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            height: 100%;
            display: block;
            border: 1px solid var(--border-light);
        }

        .related-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .related-img {
            height: 150px;
            width: 100%;
            object-fit: contain;
            margin-bottom: 10px;
        }

        /* Mobile Sticky Footer */
        @media (max-width: 767px) {
            .mobile-bottom-cta {
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                background: white;
                padding: 15px 20px;
                box-shadow: 0 -10px 30px rgba(0,0,0,0.08);
                display: flex;
                gap: 12px;
                z-index: 1050;
            }
            .product-card-main {
                padding: 20px;
                border-radius: 0;
                box-shadow: none;
                background: transparent;
            }
            .product-image-container {
                min-height: 300px;
            }
            .product-title {
                font-size: 1.6rem;
            }
            .desktop-buttons {
                display: none;
            }
            .desktop-header {
                display: none !important;
            }
        }

        @media (min-width: 768px) {
            .mobile-bottom-cta {
                display: none;
            }
        }
    </style>
</head>
<body>
    
    <!-- Navbar (Same as Home Page for consistency) -->
    <nav class="navbar-gradient desktop-header">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="{{ url('/') }}" class="brand-logo">
                <i class="bi bi-bag-check-fill"></i> GrabBaskets
            </a>
            <div class="d-flex align-items-center gap-3">
                @auth
                  <a href="{{ url('/profile') }}" class="text-white text-decoration-none fw-bold small">Hello, {{ Auth::user()->name }}</a>
                @endauth
                <a href="{{ url('/cart') }}" class="btn btn-light btn-sm rounded-pill px-3 fw-bold text-primary">
                    <i class="bi bi-cart3"></i> Cart
                </a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="product-card-main">
            <a href="javascript:history.back()" class="text-decoration-none text-muted mb-4 d-inline-block fw-bold">
                <i class="bi bi-arrow-left"></i> Back to Products
            </a>
            
            <div class="row g-5">
                <!-- Product Image -->
                <div class="col-md-6">
                    <div class="product-image-container">
                        @if($product->image || $product->image_data)
                          <img src="{{ $product->original_image_url }}" alt="{{ $product->name }}" id="mainProductImage"
                               onerror="this.src='{{ asset('images/no-image.png') }}'">
                        @else
                          <img src="{{ asset('images/no-image.png') }}" alt="No image">
                        @endif
                    </div>
                </div>

                <!-- Product Content -->
                <div class="col-md-6">
                    <div class="d-flex gap-2 mb-2">
                        <span class="badge-category">{{ optional($product->category)->name }}</span>
                        @if(optional($product->subcategory)->name)
                          <span class="badge-category">{{ $product->subcategory->name }}</span>
                        @endif
                    </div>
                    
                    <h1 class="product-title">{{ $product->name }}</h1>
                    
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rating-stars">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-half"></i>
                        </div>
                        <span class="text-muted small fw-bold">(4.5 / 5)</span>
                    </div>

                    <div class="price-container">
                        <div class="d-flex align-items-baseline">
                            @if($product->discount > 0)
                              <span class="current-price">₹{{ number_format($product->price * (1 - $product->discount / 100), 2) }}</span>
                              <span class="original-price">₹{{ number_format($product->price, 2) }}</span>
                              <span class="discount-tag">{{ $product->discount }}% OFF</span>
                            @else
                              <span class="current-price">₹{{ number_format($product->price, 2) }}</span>
                            @endif
                        </div>
                        <p class="text-muted mt-2 fw-semibold">
                            <i class="bi bi-truck"></i> 
                            {{ $product->delivery_charge ? 'Delivery: ₹' . number_format($product->delivery_charge, 0) : 'Free Delivery' }}
                        </p>
                    </div>

                    <div class="mb-4">
                        <p class="text-muted small fw-bold text-uppercase mb-2">Availability</p>
                        @if($product->stock > 0)
                          <span class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> In Stock ({{ $product->stock }} units)</span>
                        @else
                          <span class="text-danger fw-bold"><i class="bi bi-x-circle-fill"></i> Out of Stock</span>
                        @endif
                    </div>

                    <!-- Quantity and Desktop Buttons -->
                    <div class="desktop-buttons mt-4">
                        @auth
                          <form method="POST" action="{{ route('cart.add') }}" class="w-100">
                              @csrf
                              <input type="hidden" name="product_id" value="{{ $product->id }}">
                              <div class="d-flex align-items-center gap-3 mb-4">
                                  <div class="input-group" style="max-width: 140px;">
                                      <button type="button" class="btn btn-outline-secondary rounded-start-pill" onclick="decrementQty()">-</button>
                                      <input type="number" id="cartQtyDesktop" name="quantity" value="1" min="1" max="{{ $product->stock }}" class="form-control text-center">
                                      <button type="button" class="btn btn-outline-secondary rounded-end-pill" onclick="incrementQty()">+</button>
                                  </div>
                              </div>
                              <div class="action-buttons">
                                  <button type="submit" class="btn btn-cart-main">
                                      <i class="bi bi-cart-plus-fill me-2"></i> Add to Cart
                                  </button>
                          </form>
                                  <form method="POST" action="{{ route('wishlist.toggle') }}" id="wishlist-form">
                                      @csrf
                                      <input type="hidden" name="product_id" value="{{ $product->id }}">
                                      <button type="submit" class="btn-wishlist-toggle" id="wishlist-btn">
                                          <i class="bi bi-heart{{ $product->isWishlistedBy(auth()->user()) ? '-fill text-danger' : '' }}" style="font-size: 1.4rem;"></i>
                                      </button>
                                  </form>
                              </div>
                        @else
                          <a href="{{ route('login') }}" class="btn btn-cart-main w-100 py-3">Login to Add to Cart</a>
                        @endauth
                    </div>

                    <!-- Share Section -->
                    <div class="mt-5 pt-4 border-top">
                        <p class="small fw-bold text-uppercase text-muted mb-3">Share With Friends</p>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="shareOnWhatsApp()"><i class="bi bi-whatsapp"></i></button>
                            <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="copyLink()"><i class="bi bi-link-45deg"></i></button>
                            <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="shareOnFacebook()"><i class="bi bi-facebook"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description / Reviews Tabs -->
        <div class="tab-navigation d-flex gap-4">
            <a href="javascript:void(0)" class="nav-link-custom active" onclick="switchTab(this, 'desc-pane')">Description</a>
            <a href="javascript:void(0)" class="nav-link-custom" onclick="switchTab(this, 'reviews-pane')">Reviews ({{ $reviews->count() }})</a>
            <a href="javascript:void(0)" class="nav-link-custom" onclick="switchTab(this, 'store-pane')">Store Info</a>
        </div>

        <div id="tab-content-area">
            <!-- Description Pane -->
            <div id="desc-pane" class="tab-pane-content">
                <div class="card border-0 shadow-sm p-4 rounded-4">
                    <p class="lh-lg text-muted">{{ $product->description ?? 'No detailed description available for this product.' }}</p>
                </div>
            </div>

            <!-- Reviews Pane -->
            <div id="reviews-pane" class="tab-pane-content d-none">
                <div class="row">
                    <div class="col-md-6">
                        @auth
                          <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
                              <h5 class="fw-bold mb-3">Add a Review</h5>
                              <form method="POST" action="{{ route('product.addReview', $product->id) }}">
                                  @csrf
                                  <div class="mb-3">
                                      <label class="form-label small fw-bold text-muted">Rating</label>
                                      <select name="rating" class="form-select rounded-3">
                                          <option value="5">5 Stars - Excellent</option>
                                          <option value="4">4 Stars - Very Good</option>
                                          <option value="3">3 Stars - Good</option>
                                          <option value="2">2 Stars - Fair</option>
                                          <option value="1">1 Star - Poor</option>
                                      </select>
                                  </div>
                                  <div class="mb-3">
                                      <label class="form-label small fw-bold text-muted">Comment</label>
                                      <textarea name="comment" class="form-control rounded-3" rows="3" placeholder="Describe your experience..."></textarea>
                                  </div>
                                  <button class="btn btn-primary rounded-3 w-100 fw-bold">Submit Review</button>
                              </form>
                          </div>
                        @endauth
                    </div>
                    <div class="col-md-12">
                        @forelse($reviews as $review)
                          <div class="review-item">
                              <div class="d-flex justify-content-between align-items-center mb-2">
                                  <span class="fw-bold">{{ $review->user->name }}</span>
                                  <div class="rating-stars small">
                                      @for($i = 1; $i <= 5; $i++)
                                        <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>
                                      @endfor
                                  </div>
                              </div>
                              <p class="mb-1 text-muted">{{ $review->comment }}</p>
                              <small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                          </div>
                        @empty
                          <div class="py-5 text-center text-muted">
                              <i class="bi bi-chat-left-text display-4 mb-2 d-block"></i>
                              No reviews yet.
                          </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Store Pane -->
            <div id="store-pane" class="tab-pane-content d-none">
                <div class="card border-0 shadow-sm p-4 rounded-4">
                    @if($seller)
                      <h4 class="fw-bold text-primary mb-3">{{ $seller->store_name }}</h4>
                      <div class="row g-3">
                          <div class="col-md-4">
                              <p class="mb-1 small fw-bold text-muted text-uppercase">Address</p>
                              <p class="fw-semibold">{{ $seller->store_address ?? 'Address not listed' }}</p>
                          </div>
                          <div class="col-md-4">
                              <p class="mb-1 small fw-bold text-muted text-uppercase">Contact</p>
                              <p class="fw-semibold">{{ $seller->store_contact ?? 'No contact info' }}</p>
                          </div>
                          <div class="col-md-4">
                              <a href="{{ route('store.products', $seller->id) }}" class="btn btn-outline-primary fw-bold px-4 rounded-pill">Visit Store</a>
                          </div>
                      </div>
                    @else
                      <div class="alert alert-info rounded-4">
                          Store information is currently unavailable.
                      </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <div class="mt-5">
            <h4 class="fw-bold mb-4">More from this store</h4>
            <div class="row g-4 overflow-x-auto flex-nowrap pb-3" style="scrollbar-width: thin;">
                @forelse($otherProducts as $op)
                  <div class="col-6 col-md-3 flex-shrink-0">
                      <a href="{{ route('product.details', $op->id) }}" class="related-card">
                          <img src="{{ $op->image_url ?? asset('images/no-image.png') }}" alt="{{ $op->name }}" class="related-img" onerror="this.src='{{ asset('images/no-image.png') }}'">
                          <h6 class="fw-bold truncate-1 mb-1">{{ $op->name }}</h6>
                          <div class="fw-bold text-primary">₹{{ number_format($op->price, 0) }}</div>
                      </a>
                  </div>
                @empty
                  <p class="text-muted ps-3">No other products found.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Mobile Sticky CTA -->
    <div class="mobile-bottom-cta">
        @auth
          <div class="input-group" style="width: 100px;">
              <button class="btn btn-outline-secondary btn-sm" onclick="decrementQty()">-</button>
              <input type="number" id="cartQtyMobile" value="1" min="1" max="{{ $product->stock }}" class="form-control form-control-sm text-center">
              <button class="btn btn-outline-secondary btn-sm" onclick="incrementQty()">+</button>
          </div>
          <button class="btn btn-cart-main py-2" onclick="submitMainCartForm()">
              Add to Cart
          </button>
        @else
          <a href="{{ route('login') }}" class="btn btn-cart-main py-2">Login to Purchase</a>
        @endauth
    </div>

    <!-- Desktop Footer (Hidden on Mobile) -->
  <footer class="footer bg-dark text-white pt-5 pb-4 mt-5">
    <div class="container">
      <div class="row">

        <!-- About -->
        <div class="col-md-2 col-6 mb-3">
          <h6 class="fw-bold text-uppercase">About</h6>
          <ul class="list-unstyled small">
            <li><a href="#" class="text-white-50 text-decoration-none">Contact Us</a></li>
            <li><a href="#" class="text-white-50 text-decoration-none">About Us</a></li>
            <li><a href="#" class="text-white-50 text-decoration-none">Careers</a></li>
            <li><a href="#" class="text-white-50 text-decoration-none">Grabbasket Stories</a></li>
            <li><a href="#" class="text-white-50 text-decoration-none">Corporate Info</a></li>
          </ul>
        </div>

        <!-- Group Companies -->
        <div class="col-md-2 col-6 mb-3">
          <h6 class="fw-bold text-uppercase">Quick Links</h6>
          <ul class="list-unstyled small">
            <li><a href="/cart" class="text-white-50 text-decoration-none">Cart</a></li>
            <li><a href="/wishlist" class="text-white-50 text-decoration-none">Wishlist</a></li>
            <li><a href="/orders/track" class="text-white-50 text-decoration-none">Orders</a></li>
          </ul>
        </div>

        <!-- Help -->
        <div class="col-md-2 col-6 mb-3">
          <h6 class="fw-bold text-uppercase">Help</h6>
          <ul class="list-unstyled small">
            <li><a href="#" class="text-white-50 text-decoration-none">Payments</a></li>
            <li><a href="#" class="text-white-50 text-decoration-none">Shipping</a></li>
            <li><a href="#" class="text-white-50 text-decoration-none">Cancellation & Returns</a></li>
            <li><a href="#" class="text-white-50 text-decoration-none">FAQ</a></li>
          </ul>
        </div>

        <!-- Policy -->
        <div class="col-md-2 col-6 mb-3">
          <h6 class="fw-bold text-uppercase">Consumer Policy</h6>
          <ul class="list-unstyled small">
            <li><a href="#" class="text-white-50 text-decoration-none">Return Policy</a></li>
            <li><a href="#" class="text-white-50 text-decoration-none">Terms of Use</a></li>
            <li><a href="#" class="text-white-50 text-decoration-none">Security</a></li>
            <li><a href="#" class="text-white-50 text-decoration-none">Privacy</a></li>
            <li><a href="#" class="text-white-50 text-decoration-none">Sitemap</a></li>
          </ul>
        </div>

        <!-- Address -->
        <div class="col-md-4 col-12 mb-3">
          <h6 class="fw-bold text-uppercase">Registered Office Address</h6>
          <p class="text-white-50 small mb-1">
            Swivel IT and Training Institute<br>
            Mahatma Gandhi Nagar Rd, near Annai Therasa English School,<br>
            MRR Nagar, Palani Chettipatti,,<br>
            Theni, 625531, TamilNadu, India.
          </p>
          <!-- <p class="text-white-50 small mb-0">CIN: U51109KA2012PTC066107</p> -->
          <p class="text-white-50 small mb-0">Contact us: <a href="tel:+91 8300504230" class="text-white-50 text-decoration-none">+91 8300504230</a></p>
        </div>
      </div>

      <hr class="border-secondary">

      <!-- Bottom Row -->
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-center text-white-50 small">
        <div class="mb-2 mb-md-0">
          © 2025 grabbaskets.com
        </div>
        <div class="social-icons">
          <a href="#" class="text-white-50 me-3"><i class="bi bi-facebook"></i></a>
          <a href="#" class="text-white-50 me-3"><i class="bi bi-twitter"></i></a>
          <a href="#" class="text-white-50 me-3"><i class="bi bi-youtube"></i></a>
          <a href="https://www.instagram.com/grab_baskets/" class="text-white-50"><i class="bi bi-instagram"></i></a>
        </div>
      </div>
    </div>
  </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function incrementQty() {
            const dq = document.getElementById('cartQtyDesktop');
            const mq = document.getElementById('cartQtyMobile');
            const max = {{ $product->stock }};
            if (dq && parseInt(dq.value) < max) dq.value = parseInt(dq.value) + 1;
            if (mq && parseInt(mq.value) < max) mq.value = parseInt(mq.value) + 1;
        }

        function decrementQty() {
            const dq = document.getElementById('cartQtyDesktop');
            const mq = document.getElementById('cartQtyMobile');
            if (dq && parseInt(dq.value) > 1) dq.value = parseInt(dq.value) - 1;
            if (mq && parseInt(mq.value) > 1) mq.value = parseInt(mq.value) - 1;
        }

        function submitMainCartForm() {
            // Mobile button triggers desktop form but uses mobile qty
            const form = document.querySelector('form[action="{{ route('cart.add') }}"]');
            const mobileQty = document.getElementById('cartQtyMobile').value;
            form.querySelector('input[name="quantity"]').value = mobileQty;
            form.submit();
        }

        function switchTab(el, targetId) {
            document.querySelectorAll('.nav-link-custom').forEach(link => link.classList.remove('active'));
            el.classList.add('active');
            
            document.querySelectorAll('.tab-pane-content').forEach(pane => pane.classList.add('d-none'));
            document.getElementById(targetId).classList.remove('d-none');
        }

        // Keep existing share scripts
        function shareOnWhatsApp() {
            const url = window.location.href;
            const text = `Check out this amazing product: {{ $product->name }} on GrabBaskets!`;
            window.open(`https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`, '_blank');
        }

        function copyLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('Product link copied to clipboard!');
            });
        }

        function shareOnFacebook() {
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`, '_blank', 'width=600,height=400');
        }

        // AJAX Wishlist Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const wishlistForm = document.getElementById('wishlist-form');
            if (wishlistForm) {
                wishlistForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const button = this.querySelector('#wishlist-btn');
                    const icon = button.querySelector('i');

                    fetch(this.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': formData.get('_token'),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ product_id: formData.get('product_id') })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (data.in_wishlist) {
                                icon.className = 'bi bi-heart-fill text-danger';
                            } else {
                                icon.className = 'bi bi-heart';
                            }
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>