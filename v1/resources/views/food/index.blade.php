<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Delivery - grabbaskets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #FF6B35;
            --primary-hover: #E55A2B;
            --text-dark: #1C1C1C;
            --bg-light: #F7F7F7;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background-color: var(--bg-light);
        }
        
        .food-hero {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
        }
        
        .food-hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .food-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .restaurant-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        
        .restaurant-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        
        .restaurant-image {
            height: 200px;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }
        
        .restaurant-info {
            padding: 1.5rem;
        }
        
        .restaurant-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .restaurant-cuisine {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .restaurant-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.9rem;
            color: #666;
        }
        
        .btn-order {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            width: 100%;
            transition: background 0.3s ease;
        }
        
        .btn-order:hover {
            background: var(--primary-hover);
            color: white;
        }
        
        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 16px rgba(0,0,0,0.05);
            height: 100%;
        }
        
        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .partner-section {
            background: white;
            padding: 4rem 0;
        }
        
        .partner-card {
            background: var(--bg-light);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            height: 100%;
        }
        
        .stats-section {
            background: var(--primary-color);
            color: white;
            padding: 3rem 0;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .stat-label {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">
                <strong style="color: var(--primary-color);">grabbaskets</strong>
                <span class="badge" style="background: var(--primary-color);">Food</span>
            </a>
            
            <div class="ms-auto">
                <a href="{{ route('home') }}" class="btn btn-outline-primary me-2">
                    <i class="bi bi-arrow-left me-1"></i>Back to Shop
                </a>
                <a href="{{ route('hotel-owner.login') }}" class="btn btn-primary">
                    <i class="bi bi-shop me-1"></i>Restaurant Login
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="food-hero">
        <div class="container">
            <h1><i class="bi bi-cup-hot-fill me-3"></i>Food Delivery</h1>
            <p>Delicious meals delivered fast from your favorite restaurants</p>
            <div class="mt-4">
                <span class="badge bg-light text-dark me-3" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                    <i class="bi bi-lightning-fill me-1"></i>30 min delivery
                </span>
                <span class="badge bg-light text-dark me-3" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                    <i class="bi bi-truck me-1"></i>Free delivery on ₹299+
                </span>
                <span class="badge bg-light text-dark" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                    <i class="bi bi-star-fill me-1"></i>Top rated restaurants
                </span>
            </div>
        </div>
        
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </div>
                        <h5>Fast Delivery</h5>
                        <p class="text-muted">Get your food delivered in 30 minutes or less from nearby restaurants.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <h5>Quality Food</h5>
                        <p class="text-muted">Only partnered with top-rated restaurants that maintain high quality standards.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check-fill"></i>
                        </div>
                        <h5>Safe & Hygienic</h5>
                        <p class="text-muted">All our partner restaurants follow strict hygiene and safety protocols.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Demo Restaurants Section -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Popular Restaurants</h2>
                <p class="text-muted">Discover amazing restaurants near you</p>
            </div>
            
            <div class="row g-4">
                <!-- Demo Restaurant 1 -->
                <div class="col-md-4">
                    <div class="restaurant-card">
                        <div class="restaurant-image">
                            <i class="bi bi-cup-hot"></i>
                        </div>
                        <div class="restaurant-info">
                            <div class="restaurant-name">Spice Kitchen</div>
                            <div class="restaurant-cuisine">North Indian • South Indian</div>
                            <div class="restaurant-stats">
                                <div class="stat">
                                    <i class="bi bi-star-fill" style="color: #FFD700;"></i>
                                    <span>4.5</span>
                                </div>
                                <div class="stat">
                                    <i class="bi bi-clock"></i>
                                    <span>25 mins</span>
                                </div>
                                <div class="stat">
                                    <i class="bi bi-truck"></i>
                                    <span>₹30</span>
                                </div>
                            </div>
                            <button class="btn-order" onclick="showComingSoon()">
                                Order Now
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Demo Restaurant 2 -->
                <div class="col-md-4">
                    <div class="restaurant-card">
                        <div class="restaurant-image">
                            <i class="bi bi-egg-fried"></i>
                        </div>
                        <div class="restaurant-info">
                            <div class="restaurant-name">Pizza Corner</div>
                            <div class="restaurant-cuisine">Italian • Fast Food</div>
                            <div class="restaurant-stats">
                                <div class="stat">
                                    <i class="bi bi-star-fill" style="color: #FFD700;"></i>
                                    <span>4.3</span>
                                </div>
                                <div class="stat">
                                    <i class="bi bi-clock"></i>
                                    <span>20 mins</span>
                                </div>
                                <div class="stat">
                                    <i class="bi bi-truck"></i>
                                    <span>₹25</span>
                                </div>
                            </div>
                            <button class="btn-order" onclick="showComingSoon()">
                                Order Now
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Demo Restaurant 3 -->
                <div class="col-md-4">
                    <div class="restaurant-card">
                        <div class="restaurant-image">
                            <i class="bi bi-cup-straw"></i>
                        </div>
                        <div class="restaurant-info">
                            <div class="restaurant-name">Burger Hub</div>
                            <div class="restaurant-cuisine">American • Fast Food</div>
                            <div class="restaurant-stats">
                                <div class="stat">
                                    <i class="bi bi-star-fill" style="color: #FFD700;"></i>
                                    <span>4.7</span>
                                </div>
                                <div class="stat">
                                    <i class="bi bi-clock"></i>
                                    <span>15 mins</span>
                                </div>
                                <div class="stat">
                                    <i class="bi bi-truck"></i>
                                    <span>₹20</span>
                                </div>
                            </div>
                            <button class="btn-order" onclick="showComingSoon()">
                                Order Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Partner Restaurants</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">10,000+</div>
                        <div class="stat-label">Happy Customers</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">50,000+</div>
                        <div class="stat-label">Orders Delivered</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number">25</div>
                        <div class="stat-label">Minutes Avg Delivery</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Partner Section -->
    <section class="partner-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Partner with Us</h2>
                <p class="text-muted">Join grabbaskets and grow your restaurant business</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="partner-card">
                        <div class="feature-icon">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <h5>Increase Sales</h5>
                        <p class="text-muted">Reach more customers and increase your restaurant's revenue with our platform.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="partner-card">
                        <div class="feature-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h5>Easy Management</h5>
                        <p class="text-muted">Manage orders, menu, and analytics through our easy-to-use dashboard.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="partner-card">
                        <div class="feature-icon">
                            <i class="bi bi-headset"></i>
                        </div>
                        <h5>24/7 Support</h5>
                        <p class="text-muted">Get dedicated support from our team to help you succeed on our platform.</p>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="{{ route('hotel-owner.register') }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-shop me-2"></i>Register Your Restaurant
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>grabbaskets Food Delivery</h5>
                    <p class="text-muted">Delicious meals delivered fast</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="{{ route('home') }}" class="text-white me-3">Home</a>
                    <a href="{{ route('hotel-owner.login') }}" class="text-white me-3">Restaurant Login</a>
                    <a href="{{ route('hotel-owner.register') }}" class="text-white">Partner with Us</a>
                </div>
            </div>
            <hr class="my-3">
            <div class="text-center text-muted">
                <small>&copy; 2025 grabbaskets. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showComingSoon() {
            alert('🚀 Coming Soon!\n\nFood ordering will be available once restaurants register and add their menus.\n\nRestaurant owners can register now to get started!');
        }
    </script>
</body>
</html>