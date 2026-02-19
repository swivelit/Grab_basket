<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ strlen($searchQuery ?? '') ? 'Search: ' . e($searchQuery) : 'Products' }} - GrabBaskets</title>
  <link rel="icon" type="image/jpeg" href="{{ asset('asset/images/grabbaskets.jpg') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --flipkart-blue: #2874F0;
      --flipkart-blue-dark: #1E5FCC;
      --flipkart-yellow: #FFE500;
      --text-primary: #212121;
      --text-secondary: #878787;
      --text-muted: #388E3C;
      --bg-white: #FFFFFF;
      --bg-light: #F1F3F6;
      --border-color: #E0E0E0;
      --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.1);
      --shadow-md: 0 2px 4px 0 rgba(0, 0, 0, 0.1);
      --shadow-lg: 0 4px 8px 0 rgba(0, 0, 0, 0.12);
    }

    body {
      font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background-color: var(--bg-light);
      color: var(--text-primary);
      line-height: 1.5;
    }

    /* Flipkart-style Header */
    .flipkart-header {
      background: var(--flipkart-blue);
      color: white;
      padding: 12px 0;
      box-shadow: var(--shadow-md);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .header-content {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
    }

    .logo-section {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      color: white;
    }

    .logo-section h4 {
      margin: 0;
      font-weight: 700;
      font-size: 1.5rem;
    }

    .search-container {
      flex: 1;
      max-width: 600px;
      position: relative;
    }

    .search-box {
      width: 100%;
      padding: 10px 45px 10px 16px;
      border: none;
      border-radius: 2px;
      font-size: 14px;
      outline: none;
      box-shadow: var(--shadow-sm);
    }

    .search-icon {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--flipkart-blue);
      font-size: 20px;
      cursor: pointer;
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .header-btn {
      background: white;
      color: var(--flipkart-blue);
      border: none;
      padding: 8px 20px;
      border-radius: 2px;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }

    .header-btn:hover {
      background: #f0f0f0;
    }

    /* Main Container */
    .main-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 20px;
      display: flex;
      gap: 20px;
    }

    /* Left Sidebar - Flipkart Style */
    .filter-sidebar {
      width: 280px;
      background: var(--bg-white);
      border-radius: 2px;
      box-shadow: var(--shadow-sm);
      padding: 16px;
      height: fit-content;
      position: sticky;
      top: 80px;
      max-height: calc(100vh - 100px);
      overflow-y: auto;
    }

    .filter-title {
      font-size: 16px;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 16px;
      padding-bottom: 12px;
      border-bottom: 1px solid var(--border-color);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .filter-section {
      margin-bottom: 24px;
    }

    .filter-section-title {
      font-size: 14px;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .category-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 0;
      text-decoration: none;
      color: var(--text-primary);
      font-size: 14px;
      transition: color 0.2s;
      border-bottom: 1px solid #f0f0f0;
    }

    .category-item:hover {
      color: var(--flipkart-blue);
    }

    .category-item.active {
      color: var(--flipkart-blue);
      font-weight: 600;
    }

    .category-emoji {
      font-size: 18px;
    }

    .subcategory-item {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 6px 0 6px 20px;
      text-decoration: none;
      color: var(--text-secondary);
      font-size: 13px;
      transition: color 0.2s;
    }

    .subcategory-item:hover {
      color: var(--flipkart-blue);
    }

    .subcategory-item.active {
      color: var(--flipkart-blue);
      font-weight: 500;
    }

    /* Products Grid */
    .products-section {
      flex: 1;
      min-width: 0;
    }

    .results-header {
      background: var(--bg-white);
      padding: 16px;
      border-radius: 2px;
      box-shadow: var(--shadow-sm);
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
    }

    .results-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--text-primary);
    }

    .results-count {
      font-size: 14px;
      color: var(--text-secondary);
    }

    /* Product Card - Flipkart Style */
    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 32px;
    }

    .product-card {
      background: var(--bg-white);
      border-radius: 2px;
      box-shadow: var(--shadow-sm);
      overflow: hidden;
      transition: all 0.3s ease;
      cursor: pointer;
      text-decoration: none;
      color: inherit;
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .product-card:hover {
      box-shadow: var(--shadow-lg);
      transform: translateY(-2px);
    }

    .product-image-wrapper {
      position: relative;
      width: 100%;
      height: 200px;
      background: #f8f9fa;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }

    .product-image {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      mix-blend-mode: multiply;
    }

    .discount-badge {
      position: absolute;
      top: 8px;
      left: 8px;
      background: var(--text-muted);
      color: white;
      padding: 4px 8px;
      border-radius: 2px;
      font-size: 11px;
      font-weight: 600;
      z-index: 10;
    }

    .product-info {
      padding: 12px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .product-title {
      font-size: 14px;
      font-weight: 500;
      color: var(--text-primary);
      margin-bottom: 8px;
      line-height: 1.4;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      min-height: 40px;
    }

    .product-rating {
      display: flex;
      align-items: center;
      gap: 4px;
      margin-bottom: 8px;
      font-size: 12px;
    }

    .rating-stars {
      color: #FFA500;
    }

    .product-price {
      margin-top: auto;
    }

    .price-row {
      display: flex;
      align-items: baseline;
      gap: 8px;
      margin-bottom: 4px;
    }

    .current-price {
      font-size: 18px;
      font-weight: 600;
      color: var(--text-primary);
    }

    .original-price {
      font-size: 14px;
      color: var(--text-secondary);
      text-decoration: line-through;
    }

    .discount-text {
      font-size: 13px;
      color: var(--text-muted);
      font-weight: 500;
    }

    .add-to-cart-btn {
      width: 100%;
      margin-top: 12px;
      padding: 10px;
      background: var(--flipkart-blue);
      color: white;
      border: none;
      border-radius: 2px;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      transition: background 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .add-to-cart-btn:hover {
      background: var(--flipkart-blue-dark);
    }

    .add-to-cart-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    /* Mobile Categories */
    .mobile-categories {
      display: none;
      overflow-x: auto;
      padding: 12px 0;
      margin-bottom: 16px;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none;
    }

    .mobile-categories::-webkit-scrollbar {
      display: none;
    }

    .mobile-category-btn {
      padding: 8px 16px;
      background: white;
      border: 1px solid var(--border-color);
      border-radius: 20px;
      font-size: 13px;
      white-space: nowrap;
      text-decoration: none;
      color: var(--text-primary);
      margin-right: 8px;
      transition: all 0.2s;
    }

    .mobile-category-btn.active {
      background: var(--flipkart-blue);
      color: white;
      border-color: var(--flipkart-blue);
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background: var(--bg-white);
      border-radius: 2px;
      box-shadow: var(--shadow-sm);
    }

    .empty-icon {
      font-size: 64px;
      color: var(--text-secondary);
      margin-bottom: 16px;
    }

    .empty-title {
      font-size: 20px;
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 8px;
    }

    .empty-text {
      font-size: 14px;
      color: var(--text-secondary);
      margin-bottom: 24px;
    }

    /* Pagination */
    .pagination-wrapper {
      display: flex;
      justify-content: center;
      margin-top: 32px;
    }

    /* Responsive */
    @media (max-width: 991px) {
      .main-container {
        flex-direction: column;
        padding: 12px;
      }

      .filter-sidebar {
        width: 100%;
        position: static;
        max-height: none;
      }

      .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 12px;
      }

      .mobile-categories {
        display: flex;
      }

      .header-content {
        flex-wrap: wrap;
      }

      .search-container {
        order: 3;
        width: 100%;
        max-width: 100%;
      }
    }

    @media (max-width: 576px) {
      .products-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .product-image-wrapper {
        height: 150px;
      }
    }
  </style>
</head>

<body>
  <!-- Flipkart-style Header -->
  <header class="flipkart-header">
    <div class="container">
      <div class="header-content">
        <a href="{{ route('home') }}" class="logo-section">
          <i class="bi bi-bag-check-fill" style="font-size: 24px;"></i>
          <h4>GrabBaskets</h4>
        </a>

        <form action="{{ route('products.index') }}" method="GET" class="search-container">
          <input type="text" name="q" class="search-box" placeholder="Search for products, brands and more"
            value="{{ request('q') }}">
          <i class="bi bi-search search-icon"></i>
        </form>

        <div class="header-actions">
          <a href="{{ route('food.shops.index') }}" class="header-btn">
            <i class="bi bi-shop"></i> Restaurants
          </a>
          @auth
            <a href="{{ route('cart.index') }}" class="header-btn">
              <i class="bi bi-cart3"></i> Cart
            </a>
            <a href="{{ route('profile.show') }}" class="header-btn">
              <i class="bi bi-person"></i> {{ Auth::user()->name }}
            </a>
          @else
            <a href="{{ route('login') }}" class="header-btn">Login</a>
          @endauth
        </div>
      </div>
    </div>
  </header>

  <!-- Main Container -->
  <div class="main-container">
    <!-- Left Sidebar - Filters -->
    <aside class="filter-sidebar d-none d-md-block">
      <div class="filter-title">Filters</div>

      <div class="filter-section">
        <div class="filter-section-title">
          <i class="bi bi-grid"></i> Categories
        </div>
        <div class="category-list">
          @foreach($categories ?? [] as $cat)
            <div>
              <a href="{{ route('products.index', array_merge(request()->except('subcategory_id'), ['category_id' => $cat->id])) }}"
                class="category-item {{ request()->input('category_id') == $cat->id ? 'active' : '' }}">
                <span class="category-emoji">{!! $cat->emoji ?? '📦' !!}</span>
                <span>{{ $cat->name }}</span>
                @if(($cat->subcategories ?? collect())->count() > 0)
                  <span style="margin-left: auto; color: var(--text-secondary); font-size: 12px;">
                    ({{ ($cat->subcategories ?? collect())->count() }})
                  </span>
                @endif
              </a>

              @if(request()->input('category_id') == $cat->id && ($cat->subcategories ?? collect())->count() > 0)
                <div style="margin-left: 20px; margin-top: 4px;">
                  <a href="{{ route('products.index', request()->except(['category_id', 'subcategory_id'])) }}"
                    class="subcategory-item {{ !request()->input('subcategory_id') ? 'active' : '' }}">
                    All {{ $cat->name }}
                  </a>
                  @foreach($cat->subcategories as $sub)
                    <a href="{{ route('products.index', array_merge(request()->except('subcategory_id'), ['category_id' => $cat->id, 'subcategory_id' => $sub->id])) }}"
                      class="subcategory-item {{ request()->input('subcategory_id') == $sub->id ? 'active' : '' }}">
                      {!! $sub->emoji ?? '📦' !!} {{ $sub->name }}
                    </a>
                  @endforeach
                </div>
              @endif
            </div>
          @endforeach
        </div>
      </div>
    </aside>

    <!-- Products Section -->
    <main class="products-section">
      <!-- Mobile Categories -->
      <div class="mobile-categories d-md-none">
        @foreach($categories ?? [] as $cat)
          <a href="{{ route('products.index', array_merge(request()->except('subcategory_id'), ['category_id' => $cat->id])) }}"
            class="mobile-category-btn {{ request()->input('category_id') == $cat->id ? 'active' : '' }}">
            {!! $cat->emoji ?? '📦' !!} {{ Str::limit($cat->name, 12) }}
          </a>
        @endforeach
      </div>

      <!-- Results Header -->
      <div class="results-header">
        <div>
          <h2 class="results-title">
            @if(strlen($searchQuery ?? ''))
              Search Results for "{{ e($searchQuery) }}"
            @elseif(request()->input('category_id'))
              {{ collect($categories ?? [])->firstWhere('id', request()->input('category_id'))->name ?? 'Products' }}
            @else
              All Products
            @endif
          </h2>
          @if(isset($totalResults))
            <div class="results-count">{{ number_format($totalResults) }} products found</div>
          @endif
        </div>
      </div>

      <!-- Products Grid -->
      <div class="products-grid">
        @forelse($products as $product)
          @php
            $image = $product->image_url ?? $product->image ?? '/images/placeholder.png';
            $discount = $product->discount ? round($product->discount) : 0;
            $originalPrice = $discount > 0 ? $product->price / (1 - $discount / 100) : $product->price;
          @endphp
          <div class="product-card" onclick="window.location.href='{{ route('product.details', $product->id) }}'">
            <div class="product-image-wrapper">
              @if($discount > 0)
                <span class="discount-badge">{{ $discount }}% OFF</span>
              @endif
              <img src="{{ $image }}" onerror="this.src='/images/placeholder.png'" class="product-image"
                alt="{{ $product->name }}">
            </div>
            <div class="product-info">
              <div class="product-title">{{ $product->name }}</div>

              <div class="product-price">
                <div class="price-row">
                  <span class="current-price">₹{{ number_format($product->price, 0) }}</span>
                  @if($discount > 0)
                    <span class="original-price">₹{{ number_format($originalPrice, 0) }}</span>
                    <span class="discount-text">{{ $discount }}% off</span>
                  @endif
                </div>
              </div>

              @if(auth()->check())
                <button class="add-to-cart-btn" data-product-id="{{ $product->id }}"
                  onclick="event.stopPropagation(); addToCart({{ $product->id }});">
                  <i class="bi bi-cart-plus"></i> Add to Cart
                </button>
              @else
                <a href="{{ route('login') }}" class="add-to-cart-btn" onclick="event.stopPropagation();">
                  <i class="bi bi-box-arrow-in-right"></i> Login to Buy
                </a>
              @endif
            </div>
          </div>
        @empty
          <div class="empty-state" style="grid-column: 1 / -1;">
            <div class="empty-icon">
              <i class="bi bi-search"></i>
            </div>
            <h3 class="empty-title">No products found</h3>
            @if(strlen($searchQuery ?? ''))
              <p class="empty-text">We couldn't find any products matching "<strong>{{ e($searchQuery) }}</strong>"</p>
              @if(isset($relatedProducts) && $relatedProducts->count() > 0)
                <div style="margin-top: 32px;">
                  <h5 style="margin-bottom: 20px; color: var(--text-primary);">You might also like:</h5>
                  <div class="products-grid">
                    @foreach($relatedProducts as $related)
                      <div class="product-card" onclick="window.location.href='{{ route('product.details', $related->id) }}'">
                        <div class="product-image-wrapper">
                          <img src="{{ $related->image_url ?? '/images/placeholder.png' }}"
                            onerror="this.src='/images/placeholder.png'" class="product-image" alt="{{ $related->name }}">
                        </div>
                        <div class="product-info">
                          <div class="product-title">{{ Str::limit($related->name, 50) }}</div>
                          <div class="product-price">
                            <span class="current-price">₹{{ number_format($related->price, 0) }}</span>
                          </div>
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>
              @endif
            @else
              <p class="empty-text">Try adjusting your filters or browse categories</p>
            @endif
            <a href="{{ route('home') }}" class="header-btn" style="margin-top: 20px;">
              <i class="bi bi-house"></i> Back to Home
            </a>
          </div>
        @endforelse
      </div>

      <!-- Pagination -->
      @if($products->hasPages())
        <div class="pagination-wrapper">
          {{ $products->links() }}
        </div>
      @endif
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    async function addToCart(productId) {
      try {
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const button = document.querySelector(`[data-product-id="${productId}"]`);

        if (!button) return;

        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="bi bi-hourglass-split"></i> Adding...';
        button.disabled = true;

        const res = await fetch('/cart/add', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            product_id: productId,
            quantity: 1,
            delivery_type: 'standard'
          })
        });

        const data = await res.json();

        if (res.ok) {
          button.innerHTML = '<i class="bi bi-check-circle"></i> Added!';
          button.style.background = '#388E3C';
          showMessage('✅ Added to cart successfully!', 'success');

          setTimeout(() => {
            button.innerHTML = originalHTML;
            button.style.background = '';
            button.disabled = false;
          }, 2000);
        } else {
          button.innerHTML = originalHTML;
          button.disabled = false;
          showMessage('❌ ' + (data.message || 'Failed to add to cart'), 'error');
        }
      } catch (err) {
        console.error('Cart error:', err);
        showMessage('❌ Network error. Please try again.', 'error');
        const button = document.querySelector(`[data-product-id="${productId}"]`);
        if (button) {
          button.innerHTML = '<i class="bi bi-cart-plus"></i> Add to Cart';
          button.disabled = false;
        }
      }
    }

    function showMessage(message, type) {
      const existingAlert = document.querySelector('.alert-custom');
      if (existingAlert) {
        existingAlert.remove();
      }

      const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
      const alertDiv = document.createElement('div');
      alertDiv.className = `alert ${alertClass} alert-dismissible fade show alert-custom position-fixed`;
      alertDiv.style.cssText = 'top: 80px; right: 20px; z-index: 1060; min-width: 300px; max-width: 400px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
      alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;

      document.body.appendChild(alertDiv);

      setTimeout(() => {
        if (alertDiv) {
          alertDiv.remove();
        }
      }, 4000);
    }
  </script>
</body>

</html>