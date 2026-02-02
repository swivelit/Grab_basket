<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrabBaskets - Your Shopping Partner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 80px 0; }
        .category-card { transition: transform 0.3s; border-radius: 15px; }
        .category-card:hover { transform: translateY(-5px); }
        .navbar-brand { font-weight: bold; color: #FF6B00 !important; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="/"><i class="bi bi-bag-check-fill"></i> GrabBaskets</a>
            <div class="d-flex">
                <a href="/login" class="btn btn-outline-primary me-2"><i class="bi bi-person"></i> Login</a>
                <a href="/register" class="btn btn-primary"><i class="bi bi-person-plus"></i> Register</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">🛍️ Welcome to GrabBaskets</h1>
            <p class="lead mb-4">Your one-stop shop for everything you need</p>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="input-group input-group-lg">
                        <input type="text" class="form-control" placeholder="Search products..." id="searchInput">
                        <button class="btn btn-warning" onclick="performSearch()"><i class="bi bi-search"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Categories -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Shop by Category</h2>
            <div class="row g-4" id="categoriesContainer">
                <div class="col-md-3 col-6">
                    <div class="card category-card h-100 text-center p-4">
                        <i class="bi bi-laptop display-4 text-primary mb-3"></i>
                        <h5>Electronics</h5>
                        <p class="text-muted">Phones, Laptops & More</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card category-card h-100 text-center p-4">
                        <i class="bi bi-heart-fill display-4 text-danger mb-3"></i>
                        <h5>Fashion</h5>
                        <p class="text-muted">Clothing & Accessories</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card category-card h-100 text-center p-4">
                        <i class="bi bi-house-fill display-4 text-success mb-3"></i>
                        <h5>Home & Garden</h5>
                        <p class="text-muted">Furniture & Decor</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card category-card h-100 text-center p-4">
                        <i class="bi bi-star-fill display-4 text-warning mb-3"></i>
                        <h5>Beauty</h5>
                        <p class="text-muted">Cosmetics & Care</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Actions -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="card border-0 bg-transparent">
                        <div class="card-body">
                            <i class="bi bi-truck display-4 text-primary mb-3"></i>
                            <h5>Delivery Partner</h5>
                            <p>Join as a delivery partner</p>
                            <a href="/delivery/login" class="btn btn-outline-primary">Login</a>
                            <a href="/delivery/register" class="btn btn-primary">Register</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card border-0 bg-transparent">
                        <div class="card-body">
                            <i class="bi bi-shop display-4 text-success mb-3"></i>
                            <h5>Restaurant Owner</h5>
                            <p>Manage your restaurant</p>
                            <a href="/restaurant/login" class="btn btn-outline-success">Login</a>
                            <a href="/restaurant/register" class="btn btn-success">Register</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card border-0 bg-transparent">
                        <div class="card-body">
                            <i class="bi bi-person-badge display-4 text-info mb-3"></i>
                            <h5>Seller Dashboard</h5>
                            <p>Sell your products</p>
                            <a href="/seller/login" class="btn btn-outline-info">Login</a>
                            <a href="/seller/register" class="btn btn-info">Register</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; 2025 GrabBaskets. All rights reserved.</p>
            <p><small>Emergency Mode - Full site functionality being restored</small></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function performSearch() {
            const query = document.getElementById("searchInput").value;
            if (query.trim()) {
                window.location.href = "/products?q=" + encodeURIComponent(query);
            }
        }
        
        document.getElementById("searchInput").addEventListener("keypress", function(e) {
            if (e.key === "Enter") performSearch();
        });
    </script>
</body>
</html>