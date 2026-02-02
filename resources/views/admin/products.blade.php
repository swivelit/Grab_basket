<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Products</title>
    <link rel="icon" type="image/png" href="{{ asset('asset/images/grabbasket.png') }}">

    {{-- Bootstrap & Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f4f6f9;
        }

        .sidebar .logo img {
            margin-top: 60px;
            position: relative;
            left: 30px;
            transition: transform 0.2s;
        }

        /* === SCROLLABLE SIDEBAR CONTENT === */
        .sidebar {
            /* Ensure the sidebar itself doesn't scroll, only its content */
            overflow: hidden;
            /* Keep your existing height */
            height: 100vh;
        }

        .sidebar-content {
            /* This is the key: make only this part scrollable */
            overflow-y: auto;
            overflow-x: hidden;
            /* Add some padding at the bottom so the logout button isn't stuck to the edge */
            padding-bottom: 20px;
            /* This ensures the scrollbar appears inside the sidebar */
            height: calc(100vh - 180px);
            /* Adjust '180px' if your logo/header height changes */
            padding-left: 15px;
            padding-right: 15px;
        }

        /* Optional: Style the scrollbar for WebKit browsers (Chrome, Edge, Safari) */
        .sidebar-content::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-content::-webkit-scrollbar-track {
            background: #2d2d40;
            border-radius: 10px;
        }

        .sidebar-content::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 10px;
        }

        .sidebar-content::-webkit-scrollbar-thumb:hover {
            background: #777;
        }

        /* === SIDEBAR LOGO BOX === */
        .sidebar .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 10px;
            border-radius: 6px;
            width: 100%;
            box-sizing: border-box;
            height: 100px;
        }

        .sidebar .logo img {
            width: 150px;
            height: 200px;
            object-fit: cover;
            margin-top: -3px;
            /* Pull up slightly to counter height increase */
            margin-bottom: -50px;
        }

        /* ===== OLD-STYLE SIDEBAR (FLAT DARK) ===== */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: 250px;
            background-color: #1e1e2f;
            color: #fff;
            padding-top: 20px;
            z-index: 1000;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        .sidebar .logo {
            margin-top: -40px
        }




        .sidebar .nav-link {
            color: #bdc3c7;
            margin: 8px 15px;
            padding: 12px 20px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #2d2d40;
            color: #fff;
        }

        .sidebar .nav-link.active {
            background-color: #007bff;
            color: white;
            border-left: 4px solid #0056b3;
        }

        .sidebar .nav-link i {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .sidebar .nav-link.text-danger {
            color: #ff6b6b;
        }

        .sidebar .nav-link.text-danger:hover {
            color: #ff4757;
            background-color: #2d2d40;
        }

        /* ===== MOBILE SIDEBAR TOGGLE ===== */
        @media (max-width: 768px) {
            .sidebar {
                left: -250px;
            }

            .sidebar.show {
                left: 0;
            }
        }

        /* ===== MENU TOGGLE BUTTON ===== */
        .menu-toggle {
            position: fixed;
            top: 15px;
            left: 15px;
            font-size: 1.8rem;
            cursor: pointer;
            color: #1e1e2f;
            z-index: 1200;
            background: white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .menu-toggle:hover {
            background: #007bff;
            color: white;
            transform: rotate(90deg);
        }

        .content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
            min-height: 100vh;
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                padding: 20px 15px;
            }
        }


        .menu-toggle {
            position: fixed;
            top: 10px;
            left: 15px;
            font-size: 1.8rem;
            cursor: pointer;
            color: #212529;
            z-index: 1200;
        }

        .img {
            position: relative;
            margin-top: -40px;
            margin-left: -50px;
        }





        .filter-controls {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .filter-controls .form-control,
        .filter-controls .form-select {
            height: calc(1.5em + 0.75rem + 2px);
        }

        .nav-pills {
            margin-top: 50px;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
            min-height: 100vh;
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                padding: 20px 15px;
            }
        }
    </style>
</head>

<body>
    <div class="menu-toggle d-md-none">
        <i class="bi bi-list"></i>
    </div>
    <div class="sidebar" id="sidebarMenu">
        <div class="sidebar-header">
            <div class="logo">
                <img src="{{ asset('asset/images/grabbasket.png') }}" alt="Logo" width="150px">
            </div>
        </div>
        <div class="sidebar-content">
            <ul class="nav nav-pills flex-column">
                <li><a class="nav-link " href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i>
                        Dashboard</a></li>
                <li><a class="nav-link active " href="{{ route('admin.products') }}"><i class="bi bi-box-seam"></i>
                        Products</a></li>
                <li><a class="nav-link" href="{{ route('admin.orders') }}"><i class="bi bi-cart-check"></i> Orders</a></li>
                <li><a class="nav-link" href="{{ route('tracking.form') }}"><i class="bi bi-truck"></i> Track Package</a></li>
                <li><a class="nav-link" href="{{ route('admin.manageuser') }}"><i class="bi bi-people"></i> Users</a></li>
                <li><a class="nav-link" href="{{ route('admin.banners.index') }}"><i class="bi bi-images"></i> Banner Management</a></li>
                <li><a class="nav-link" href="{{ route('admin.index-editor.index') }}"><i class="bi bi-house-gear-fill"></i> Index Page Editor</a></li>
                <li><a class="nav-link" href="{{ route('admin.category-emojis.index') }}"><i class="bi bi-emoji-smile-fill"></i> Category Emojis</a></li>
                <li><a class="nav-link" href="{{ route('admin.promotional.form') }}"><i class="bi bi-bell-fill"></i> Promotional Notifications</a></li>
                <li><a class="nav-link" href="{{ route('admin.sms.dashboard') }}"><i class="bi bi-chat-dots"></i> SMS Management</a></li>
                <li><a class="nav-link" href="{{ route('admin.bulkProductUpload') }}"><i class="bi bi-upload"></i> Bulk Product Upload</a></li>
                <li><a class="nav-link" href="{{ route('admin.warehouse.dashboard') }}"><i class="bi bi-shop"></i> Warehouse Management</a></li>
                <li><a class="nav-link" href="{{ route('admin.delivery-partners.dashboard') }}"><i class="bi bi-bicycle"></i> Delivery Partners</a></li>
                <li><a class="nav-link text-danger" href="{{ route('admin.logout') }}">
                        <i class="bi bi-box-arrow-right"></i> Logout</a>
                </li>
            </ul>
        </div>
    </div>


    {{-- Main Content --}}
    <div class="content">
        <div class="container-fluid">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
            <h1 class="mb-4"><i class="bi bi-box-seam"></i> Products Dashboard</h1>

            {{-- Search & Filter Controls --}}
            <div class="filter-controls">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search"
                            value="{{ request('search') }}" placeholder="Product name...">
                    </div>
                    <div class="col-md-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="all">All Categories</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                                {{ $cat }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('admin.products') }}" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-repeat"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            {{-- Products Table --}}
            <div class="card shadow-lg border-0">
                <div
                    class="card-header bg-gradient bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-box-seam"></i> Products List</h5>
                    <span class="badge bg-light text-dark">{{ $products->total() }} Products</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover table-bordered align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Category</th>
                                <th>Subcategory</th>
                                <th>Seller</th>
                                <th>Actions</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                            <tr>
                                <td class="fw-bold">{{ $product->id }}</td>
                                <td>
                                    @if($product->image || $product->image_data)
                                    <a href="{{ route('product.details', $product->id) }}">
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                            width="50" class="rounded" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">
                                    </a>
                                    @else
                                    <i class="bi bi-image text-muted fs-4"></i>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('product.details', $product->id) }}" class="text-decoration-none text-dark fw-semibold">
                                        {{ $product->name }}
                                    </a>
                                </td>
                                <td class="fw-semibold text-success">₹{{ number_format($product->price, 2) }}</td>
                                <td>
                                    @if($product->stock > 10)
                                    <span class="badge bg-success">{{ $product->stock }}</span>
                                    @elseif($product->stock > 0)
                                    <span class="badge bg-warning text-dark">{{ $product->stock }}</span>
                                    @else
                                    <span class="badge bg-danger">Out of Stock</span>
                                    @endif
                                </td>
                                <td>{{ $product->category->name ?? 'Uncategorized' }}</td>
                                <td>{{ $product->subcategory->name ?? '—' }}</td>
                                <td>
                                    <form action="{{ route('admin.products.updateSeller', $product->id) }}" method="POST" class="d-flex align-items-center" style="gap: 4px;">
                                        @csrf
                                        <select name="seller_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="">— Select Seller —</option>
                                            @foreach($sellers as $id => $name)
                                            <option value="{{ $id }}" {{ $product->seller_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>

                                {{-- Actions Column --}}
                                <td>
                                    <button type="button"
                                        class="btn btn-sm {{ $product->is_active ? 'btn-warning' : 'btn-success' }} me-1"
                                        data-product-id="{{ $product->id }}"
                                        data-is-active="{{ $product->is_active ? 'true' : 'false' }}"
                                        onclick="toggleProductStatus(this)">
                                        @if($product->is_active)
                                        <i class="bi bi-pause-circle"></i> Suspend
                                        @else
                                        <i class="bi bi-play-circle"></i> Restore
                                        @endif
                                    </button>

                                    <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST"
                                        style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Delete this product?')">
                                            Delete
                                        </button>
                                    </form>
                                </td>

                                {{-- Status Column --}}
                                <td>
                                    @if($product->is_active)
                                    <span class="badge bg-success">Active</span>
                                    @else
                                    <span class="badge bg-secondary">Suspended</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-muted py-3">
                                    <i class="bi bi-inbox fs-4"></i> No products found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>


                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3">
                        <!-- <div class="text-secondary small mb-2 mb-md-0 text-center text-md-start">
                            Showing <strong>{{ $products->firstItem() }}</strong> to
                            <strong>{{ $products->lastItem() }}</strong> of
                            <strong>{{ $products->total() }}</strong> results
                        </div> -->

                        <div class="d-flex justify-content-center mt-3">
                            {{ $products->links('pagination::bootstrap-5') }}
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- JavaScript --}}
    <script>
        function toggleProductStatus(button) {
            const productId = button.dataset.productId;
            const isActive = button.dataset.isActive === 'true';

            const message = isActive ?
                'Suspend this product? It will be hidden from customers.' :
                'Restore this product? It will be visible again.';

            if (!confirm(message)) return;

            button.disabled = true;
            button.innerHTML = isActive ?
                '<i class="bi bi-hourglass"></i> Suspending...' :
                '<i class="bi bi-hourglass"></i> Restoring...';

            fetch(`/admin/products/${productId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Operation failed: ' + (data.message || 'Unknown error'));
                        button.disabled = false;
                        button.innerHTML = isActive ?
                            '<i class="bi bi-pause-circle"></i> Suspend' :
                            '<i class="bi bi-play-circle"></i> Restore';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Something went wrong. Check browser console for details.');
                    button.disabled = false;
                    button.innerHTML = isActive ?
                        '<i class="bi bi-pause-circle"></i> Suspend' :
                        '<i class="bi bi-play-circle"></i> Restore';
                });
        }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Mobile Sidebar Toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.getElementById('sidebarMenu');

            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
                    const isClickInsideSidebar = sidebar.contains(event.target) || menuToggle.contains(event.target);
                    if (!isClickInsideSidebar) {
                        sidebar.classList.remove('show');
                    }
                }
            });
        });
    </script>
</body>

</html>