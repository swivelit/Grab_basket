<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Product Upload - Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('asset/images/grabbasket.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: 220px;
            background: #212529;
            color: #fff;
            padding-top: 60px;
            transition: all 0.3s;
            z-index: 1000;
        }

        .sidebar .nav-link {
            color: #adb5bd;
            margin: 6px 0;
            border-radius: 6px;
        }

        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: #0d6efd;
            color: #fff;
        }

        .sidebar .nav-link i {
            margin-right: 8px;
        }

        /* Content */
        .content {
            margin-left: 220px;
            padding: 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }

            .sidebar {
                left: -220px;
            }

            .sidebar.show {
                left: 0;
            }

            .content {
                margin-left: 0;
                padding: 20px 15px;
            }
        }

        .menu-toggle {
            position: fixed;
            top: 15px;
            left: 15px;
            font-size: 1.8rem;
            cursor: pointer;
            color: #212529;
            z-index: 1101;
            display: none;
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <div class="menu-toggle d-md-none">
        <i class="bi bi-list"></i>
    </div>

    <!-- Sidebar with Logo -->
    <div class="sidebar d-flex flex-column p-3" id="sidebarMenu">
        <!-- Logo -->
        <div class="text-center mb-4">
            <img src="{{ asset('asset/images/grabbasket.png') }}" 
                 alt="Grab Basket Admin Panel" 
                 class="img-fluid"
                 style="max-height: 60px;">
        </div>

        <!-- Navigation -->
        <ul class="nav nav-pills flex-column">
            <li>
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
                   href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('admin.products') ? 'active' : '' }}" 
                   href="{{ route('admin.products') }}">
                    <i class="bi bi-box-seam"></i> Products
                </a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('admin.orders') ? 'active' : '' }}" 
                   href="{{ route('admin.orders') }}">
                    <i class="bi bi-cart-check"></i> Orders
                </a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('admin.manageuser') ? 'active' : '' }}" 
                   href="{{ route('admin.manageuser') }}">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('admin.bulkProductUpload') ? 'active' : '' }}" 
                   href="{{ route('admin.bulkProductUpload') }}">
                    <i class="bi bi-upload"></i> Bulk Product Upload
                </a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('admin.warehouse.dashboard') ? 'active' : '' }}" 
                   href="{{ route('admin.warehouse.dashboard') }}">
                    <i class="bi bi-shop"></i> Warehouse Management
                </a>
            </li>
            <li>
                <a class="nav-link {{ request()->routeIs('admin.delivery-partners.dashboard') ? 'active' : '' }}" 
                   href="{{ route('admin.delivery-partners.dashboard') }}">
                    <i class="bi bi-bicycle"></i> Delivery Partners
                </a>
            </li>
            <li>
                <a class="nav-link text-danger" href="{{ route('admin.logout') }}">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="container-fluid">
            <h2 class="mb-4"><i class="bi bi-upload"></i> Bulk Product Upload</h2>
            <form method="POST" action="{{ route('admin.bulkProductUpload.post') }}" enctype="multipart/form-data" class="card p-4 shadow-sm">
                @csrf
                <div class="mb-3">
                    <label for="seller_email" class="form-label">Seller Email</label>
                    <select name="seller_email" id="seller_email" class="form-select" required>
                        <option value="">-- Select Seller Email --</option>
                        @foreach($sellers as $email)
                            <option value="{{ $email }}">{{ $email }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="products_file" class="form-label">Products CSV/Excel File</label>
                    <input type="file" name="products_file" id="products_file" class="form-control" accept=".csv,.xlsx,.xls" required>
                    <div class="form-text">
                        Accepted columns: name, unique_id, category_id, subcategory_id, image, description, price, discount, delivery_charge, gift_option, stock
                    </div>
                </div>
                <div class="mb-3">
                    <label for="images_zip" class="form-label">Images ZIP (optional)</label>
                    <input class="form-control" type="file" id="images_zip" name="images_zip" accept=".zip">
                    <div class="form-text">
                        Place product images inside the ZIP. Filenames should match the Excel image column or the product unique_id (case-insensitive). Example: ABC123.jpg matches unique_id ABC123.
                    </div>
                </div>
                <div class="mb-3">
                    <label for="images" class="form-label">Or select individual images (optional)</label>
                    <input class="form-control" type="file" id="images" name="images[]" accept="image/*" multiple webkitdirectory directory>
                </div>
                <button type="submit" class="btn btn-primary">Upload Products</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.querySelector('.menu-toggle');
            const sidebar = document.getElementById('sidebarMenu');

            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('show');
                });
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>