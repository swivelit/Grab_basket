<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Search Products - {{ config('app.name', 'GrabBaskets') }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('asset/images/grabbasket.jpg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        /* Modern Search Interface */
        .search-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 3rem 0;
            margin-bottom: 2rem;
        }

        .search-box {
            background: white;
            border-radius: 50px;
            padding: 1rem 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
            font-size: 1.1rem;
        }

        .search-box:focus {
            outline: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .search-btn {
            background: linear-gradient(45deg, #ff6b6b, #ffa500);
            border: none;
            border-radius: 50px;
            padding: 1rem 2rem;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.3);
            color: white;
        }

        /* Category Filters */
        .category-filter {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .category-btn {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            color: #6c757d;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .category-btn:hover,
        .category-btn.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-color: #667eea;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }

        /* Product Cards */
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            height: 200px;
            object-fit: cover;
            border-radius: 15px 15px 0 0;
        }

        .product-price {
            color: #28a745;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .product-discount {
            background: linear-gradient(45deg, #ff6b6b, #ffa500);
            color: white;
            border-radius: 15px;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Loading Animation */
        .loading {
            text-align: center;
            padding: 3rem;
        }

        .spinner {
            width: 3rem;
            height: 3rem;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .no-results i {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .search-container {
                padding: 2rem 0;
            }

            .search-box {
                margin-bottom: 1rem;
            }

            .category-btn {
                font-size: 0.9rem;
                padding: 0.4rem 0.8rem;
            }
        }

        /* Navbar */
        .navbar-brand-modern {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
        }

        .nav-icon-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white !important;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-icon-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            color: white !important;
        }
    </style>
</head>

<body>
    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark"
        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container">
            <a class="navbar-brand-modern" href="{{ url('/') }}">
                <i class="bi bi-bag-check-fill"></i>
                <span class="d-none d-md-inline">GrabBaskets</span>
                <span class="d-md-none">GB</span>
            </a>

            <div class="d-flex align-items-center">
                @auth
                    <a href="{{ route('cart.index') }}" class="nav-icon-btn me-2">
                        <i class="bi bi-cart3"></i>
                        <span class="d-none d-md-inline">Cart</span>
                    </a>
                    <a href="{{ route('profile.index') }}" class="nav-icon-btn me-2">
                        <i class="bi bi-person-circle"></i>
                        <span class="d-none d-md-inline">Profile</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="nav-icon-btn">
                            <i class="bi bi-box-arrow-right"></i>
                            <span class="d-none d-md-inline">Logout</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="nav-icon-btn me-2">
                        <i class="bi bi-person-circle"></i>
                        <span class="d-none d-md-inline">Login</span>
                    </a>
                    <a href="{{ route('register') }}" class="nav-icon-btn">
                        <i class="bi bi-person-plus"></i>
                        <span class="d-none d-md-inline">Register</span>
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Modern Search Header -->
    <div class="search-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb-4">
                        <h1 class="text-white fw-bold mb-3">Find Your Perfect Products</h1>
                        <p class="text-white-50">Search through thousands of products from trusted sellers</p>
                    </div>

                    <div class="d-flex gap-2">
                        <input type="text" id="searchInput" class="form-control search-box flex-grow-1"
                            placeholder="Search for products, brands, categories..." value="{{ request('q') }}">
                        <button type="button" id="searchBtn" class="btn search-btn">
                            <i class="bi bi-search"></i>
                            <span class="d-none d-md-inline ms-2">Search</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Category Filters -->
        <div class="category-filter">
            <h5 class="mb-3"><i class="bi bi-funnel"></i> Quick Filters</h5>
            <div id="categoryFilters">
                <a href="#" class="category-btn active" data-category="">
                    <i class="bi bi-grid"></i> All Products
                </a>
                <!-- Categories will be loaded dynamically -->
            </div>
        </div>

        <!-- Search Results -->
        <div id="searchResults">
            <!-- Loading State -->
            <div id="loadingState" class="loading" style="display: none;">
                <div class="spinner"></div>
                <h5>Searching products...</h5>
                <p class="text-muted">Please wait while we find the best matches for you</p>
            </div>

            <!-- No Results -->
            <div id="noResults" class="no-results" style="display: none;">
                <i class="bi bi-search"></i>
                <h4>No products found</h4>
                <p class="text-muted">Try adjusting your search terms or browse our categories</p>
                <a href="{{ url('/') }}" class="btn btn-primary">
                    <i class="bi bi-house"></i> Browse Homepage
                </a>
            </div>

            <!-- Products Grid -->
            <div id="productsGrid" class="row">
                <!-- Products will be loaded here via AJAX -->
            </div>
        </div>

        <!-- Load More Button -->
        <div class="text-center mt-4" id="loadMoreContainer" style="display: none;">
            <button type="button" id="loadMoreBtn" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-plus-circle"></i> Load More Products
            </button>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p class="mb-0">&copy; 2025 GrabBaskets. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        class ProductSearch {
            constructor() {
                this.currentQuery = '';
                this.currentCategory = '';
                this.currentPage = 1;
                this.isLoading = false;
                this.hasMoreResults = true;

                this.initializeElements();
                this.bindEvents();
                this.loadInitialResults();
            }

            initializeElements() {
                this.searchInput = document.getElementById('searchInput');
                this.searchBtn = document.getElementById('searchBtn');
                this.categoryFilters = document.getElementById('categoryFilters');
                this.searchResults = document.getElementById('searchResults');
                this.loadingState = document.getElementById('loadingState');
                this.noResults = document.getElementById('noResults');
                this.productsGrid = document.getElementById('productsGrid');
                this.loadMoreContainer = document.getElementById('loadMoreContainer');
                this.loadMoreBtn = document.getElementById('loadMoreBtn');
            }

            bindEvents() {
                // Search input events
                this.searchInput.addEventListener('input', this.debounce(() => {
                    this.performSearch();
                }, 500));

                this.searchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.performSearch();
                    }
                });

                // Search button
                this.searchBtn.addEventListener('click', () => {
                    this.performSearch();
                });

                // Category filters
                this.categoryFilters.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (e.target.classList.contains('category-btn')) {
                        // Update active state
                        this.categoryFilters.querySelectorAll('.category-btn').forEach(btn => {
                            btn.classList.remove('active');
                        });
                        e.target.classList.add('active');

                        // Set category and search
                        this.currentCategory = e.target.getAttribute('data-category');
                        this.performSearch();
                    }
                });

                // Load more button
                this.loadMoreBtn.addEventListener('click', () => {
                    this.loadMoreResults();
                });
            }

            debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }

            async performSearch(append = false) {
                if (this.isLoading) return;

                this.currentQuery = this.searchInput.value.trim();

                if (!append) {
                    this.currentPage = 1;
                    this.hasMoreResults = true;
                }

                this.showLoading();
                this.isLoading = true;

                try {
                    const response = await fetch(`/api/search/instant?q=${encodeURIComponent(this.currentQuery)}&category=${this.currentCategory}&page=${this.currentPage}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();

                    if (!append) {
                        this.productsGrid.innerHTML = '';
                    }

                    if (data.products && data.products.length > 0) {
                        this.renderProducts(data.products, append);

                        // Update pagination info
                        if (data.pagination) {
                            this.showLoadMoreButton(data.pagination.has_more);
                        } else {
                            this.showLoadMoreButton(data.products.length >= 12);
                        }
                    } else if (!append) {
                        this.showNoResults();
                    }

                    // Render categories on first load
                    if (!append && data.categories) {
                        this.renderCategories(data.categories);
                    }

                } catch (error) {
                    console.error('Search error:', error);
                    this.showError();
                } finally {
                    this.hideLoading();
                    this.isLoading = false;
                }
            }

            renderProducts(products, append = false) {
                const productHTML = products.map(product => `
                    <div class="col-lg-3 col-md-4 col-6 mb-4">
                        <div class="card product-card h-100">
                            <div class="position-relative">
                                <img src="${product.image || '/images/no-image.png'}" 
                                     alt="${product.name}" 
                                     class="card-img-top product-image"
                                     onerror="this.src='/images/no-image.png'">
                                ${product.discount > 0 ? `<span class="position-absolute top-0 end-0 m-2 product-discount">${product.discount}% OFF</span>` : ''}
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title" title="${product.name}">${product.name.length > 50 ? product.name.substring(0, 50) + '...' : product.name}</h6>
                                ${product.category ? `<small class="text-muted mb-2"><i class="bi bi-tag"></i> ${product.category}</small>` : ''}
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="product-price">₹${product.price}</span>
                                        <button class="btn btn-primary btn-sm" onclick="addToCart(${product.id})">
                                            <i class="bi bi-cart-plus"></i>
                                        </button>
                                    </div>
                                    ${product.stock_quantity <= 5 && product.stock_quantity > 0 ? `<small class="text-warning"><i class="bi bi-exclamation-triangle"></i> Only ${product.stock_quantity} left!</small>` : ''}
                                    ${product.stock_quantity === 0 ? `<small class="text-danger"><i class="bi bi-x-circle"></i> Out of stock</small>` : ''}
                                    ${product.seller ? `<small class="text-muted d-block"><i class="bi bi-shop"></i> ${product.seller}</small>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');

                if (append) {
                    this.productsGrid.insertAdjacentHTML('beforeend', productHTML);
                } else {
                    this.productsGrid.innerHTML = productHTML;
                }

                this.hideLoading();
                this.hideNoResults();
            }

            renderCategories(categories) {
                if (!categories || categories.length === 0) return;

                // Clear existing dynamic categories (keep "All Products")
                const existingButtons = this.categoryFilters.querySelectorAll('.category-btn:not(.active)');
                existingButtons.forEach(btn => btn.remove());

                // Add new categories
                categories.forEach(category => {
                    if (category.product_count > 0) {
                        const categoryBtn = document.createElement('a');
                        categoryBtn.href = '#';
                        categoryBtn.className = 'category-btn';
                        categoryBtn.setAttribute('data-category', category.id);
                        categoryBtn.innerHTML = `
                            ${category.emoji ? category.emoji + ' ' : '<i class="bi bi-tag"></i> '}
                            ${category.name}
                            <span class="badge bg-light text-dark ms-1">${category.product_count}</span>
                        `;
                        this.categoryFilters.appendChild(categoryBtn);
                    }
                });
            }

            loadMoreResults() {
                this.currentPage++;
                this.performSearch(true);
            }

            showLoadMoreButton(show) {
                this.loadMoreContainer.style.display = show ? 'block' : 'none';
            }

            showLoading() {
                this.loadingState.style.display = 'block';
                this.noResults.style.display = 'none';
            }

            hideLoading() {
                this.loadingState.style.display = 'none';
            }

            showNoResults() {
                this.noResults.style.display = 'block';
                this.productsGrid.innerHTML = '';
                this.showLoadMoreButton(false);
            }

            hideNoResults() {
                this.noResults.style.display = 'none';
            }

            showError() {
                this.productsGrid.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-danger text-center">
                            <i class="bi bi-exclamation-triangle"></i>
                            <h5>Oops! Something went wrong</h5>
                            <p>Please try again or <a href="/">return to homepage</a></p>
                        </div>
                    </div>
                `;
                this.showLoadMoreButton(false);
            }

            loadInitialResults() {
                // Load initial results if there's a search query
                if (this.searchInput.value.trim()) {
                    this.performSearch();
                } else {
                    // Load popular products
                    this.performSearch();
                }
            }
        }

        // Initialize search functionality
        document.addEventListener('DOMContentLoaded', () => {
            new ProductSearch();
        });

        // Add to cart functionality
        async function addToCart(productId) {
            try {
                const response = await fetch('/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Show success message
                    const toast = document.createElement('div');
                    toast.className = 'toast-notification';
                    toast.innerHTML = `
                        <div class="alert alert-success alert-dismissible fade show position-fixed" 
                             style="top: 20px; right: 20px; z-index: 9999;">
                            <i class="bi bi-check-circle"></i> Product added to cart!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                    document.body.appendChild(toast);

                    // Auto remove after 3 seconds
                    setTimeout(() => {
                        toast.remove();
                    }, 3000);
                } else {
                    alert('Failed to add product to cart. Please try again.');
                }
            } catch (error) {
                console.error('Add to cart error:', error);
                alert('Please login to add products to cart.');
            }
        }
    </script>
</body>

</html>