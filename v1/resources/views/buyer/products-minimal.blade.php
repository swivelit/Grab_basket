<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Search Products - GrabBaskets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .search-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .search-box {
            background: white;
            border-radius: 50px;
            padding: 1rem 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: none;
            font-size: 1.1rem;
        }
        .search-btn {
            background: linear-gradient(45deg, #ff6b6b, #ffa500);
            border: none;
            border-radius: 50px;
            padding: 1rem 2rem;
            color: white;
            font-weight: 600;
        }
        .category-filter {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
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
        .category-btn:hover, .category-btn.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-color: #667eea;
            color: white;
            text-decoration: none;
        }
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
        }
        .product-price {
            color: #28a745;
            font-weight: 700;
            font-size: 1.2rem;
        }
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
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Simple Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                <i class="bi bi-bag-check-fill"></i> GrabBaskets
            </a>
            <div class="d-flex">
                <a href="/" class="btn btn-outline-light">
                    <i class="bi bi-house"></i> Home
                </a>
            </div>
        </div>
    </nav>

    <!-- Search Header -->
    <div class="search-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb-4">
                        <h1 class="text-white fw-bold mb-3">Find Your Perfect Products</h1>
                        <p class="text-white-50">Search through thousands of products from trusted sellers</p>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <input type="text" 
                               id="searchInput" 
                               class="form-control search-box flex-grow-1" 
                               placeholder="Search for products, brands, categories..."
                               value="">
                        <button type="button" id="searchBtn" class="btn search-btn">
                            <i class="bi bi-search"></i> Search
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
            </div>
        </div>

        <!-- Search Results -->
        <div id="searchResults">
            <!-- Loading State -->
            <div id="loadingState" class="loading" style="display: none;">
                <div class="spinner"></div>
                <h5>Searching products...</h5>
            </div>

            <!-- No Results -->
            <div id="noResults" class="text-center py-5" style="display: none;">
                <i class="bi bi-search fs-1 text-muted"></i>
                <h4>No products found</h4>
                <p class="text-muted">Try adjusting your search terms</p>
            </div>

            <!-- Products Grid -->
            <div id="productsGrid" class="row">
                <!-- Products will be loaded here -->
            </div>
        </div>

        <!-- Load More -->
        <div class="text-center mt-4" id="loadMoreContainer" style="display: none;">
            <button type="button" id="loadMoreBtn" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-plus-circle"></i> Load More Products
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        class ProductSearch {
            constructor() {
                this.currentQuery = '';
                this.currentCategory = '';
                this.currentPage = 1;
                this.isLoading = false;
                
                this.initializeElements();
                this.bindEvents();
                this.loadInitialResults();
            }

            initializeElements() {
                this.searchInput = document.getElementById('searchInput');
                this.searchBtn = document.getElementById('searchBtn');
                this.categoryFilters = document.getElementById('categoryFilters');
                this.loadingState = document.getElementById('loadingState');
                this.noResults = document.getElementById('noResults');
                this.productsGrid = document.getElementById('productsGrid');
                this.loadMoreContainer = document.getElementById('loadMoreContainer');
                this.loadMoreBtn = document.getElementById('loadMoreBtn');
            }

            bindEvents() {
                this.searchInput.addEventListener('input', this.debounce(() => {
                    this.performSearch();
                }, 500));

                this.searchBtn.addEventListener('click', () => {
                    this.performSearch();
                });

                this.categoryFilters.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (e.target.classList.contains('category-btn')) {
                        this.categoryFilters.querySelectorAll('.category-btn').forEach(btn => {
                            btn.classList.remove('active');
                        });
                        e.target.classList.add('active');
                        this.currentCategory = e.target.getAttribute('data-category');
                        this.performSearch();
                    }
                });

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
                }

                this.showLoading();
                this.isLoading = true;

                try {
                    const response = await fetch(`/api/search/instant?q=${encodeURIComponent(this.currentQuery)}&category=${this.currentCategory}&page=${this.currentPage}`);
                    const data = await response.json();
                    
                    if (!append) {
                        this.productsGrid.innerHTML = '';
                    }

                    if (data.products && data.products.length > 0) {
                        this.renderProducts(data.products, append);
                        this.showLoadMoreButton(data.pagination?.has_more || false);
                    } else if (!append) {
                        this.showNoResults();
                    }

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
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card product-card h-100">
                            <img src="${product.image || '/images/no-image.png'}" 
                                 alt="${product.name}" 
                                 class="card-img-top product-image"
                                 onerror="this.src='/images/no-image.png'">
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title">${product.name}</h6>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="product-price">₹${product.price}</span>
                                        <button class="btn btn-primary btn-sm">
                                            <i class="bi bi-cart-plus"></i>
                                        </button>
                                    </div>
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

                const existingButtons = this.categoryFilters.querySelectorAll('.category-btn:not(.active)');
                existingButtons.forEach(btn => btn.remove());

                categories.forEach(category => {
                    if (category.product_count > 0) {
                        const categoryBtn = document.createElement('a');
                        categoryBtn.href = '#';
                        categoryBtn.className = 'category-btn';
                        categoryBtn.setAttribute('data-category', category.id);
                        categoryBtn.innerHTML = `${category.name} (${category.product_count})`;
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
                this.performSearch();
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            new ProductSearch();
        });
    </script>
</body>
</html>