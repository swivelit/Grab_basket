<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="icon" type="image/png" href="{{ asset('asset/images/grabbasket.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        /* ===== MAIN CONTENT AREA ===== */
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

        /* ===== STATS CARDS ===== */
        .stat-card {
            border-radius: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-card .icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stat-card h6 {
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .stat-card .display-6 {
            font-weight: 700;
            color: #2c3e50;
        }

        /* ===== TABLE STYLING ===== */
        .table thead th {
            background-color: #2d2d40;
            color: white;
            font-weight: 600;
            border: none;
            text-align: center;
            padding: 12px;
        }

        .table tbody td {
            vertical-align: middle;
            text-align: center;
            padding: 12px;
        }

        .table tbody tr:hover {
            background-color: #f1f3f5;
        }

        .badge {
            font-weight: 600;
            padding: 6px 12px;
        }

        /* Ensure Bootstrap badge colors work */
        .badge.bg-success {
            background-color: #28a745 !important;
        }

        .badge.bg-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }

        .badge.bg-danger {
            background-color: #dc3545 !important;
        }

        .badge.bg-secondary {
            background-color: #6c757d !important;
        }

        .badge.bg-info {
            background-color: #17a2b8 !important;
        }

        .badge.bg-primary {
            background-color: #007bff !important;
        }

        .badge.bg-dark {
            background-color: #343a40 !important;
        }

        .nav-pills {
            margin-top: 50px;
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
                <li><a class="nav-link active" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i>
                        Dashboard</a></li>
                <li><a class="nav-link " href="{{ route('admin.products') }}"><i class="bi bi-box-seam"></i>
                        Products</a></li>
                <li><a class="nav-link" href="{{ route('admin.orders') }}"><i class="bi bi-cart-check"></i> Orders</a>
                </li>
                <li><a class="nav-link" href="{{ route('tracking.form') }}"><i class="bi bi-truck"></i> Track
                        Package</a></li>
                <li><a class="nav-link" href="{{ route('admin.manageuser') }}"><i class="bi bi-people"></i> Users</a>
                </li>
                <li><a class="nav-link" href="{{ route('admin.banners.index') }}"><i class="bi bi-images"></i> Banner
                        Management</a></li>
                <li><a class="nav-link" href="{{ route('admin.index-editor.index') }}"><i
                            class="bi bi-house-gear-fill"></i> Index Page Editor</a></li>
                <li><a class="nav-link" href="{{ route('admin.category-emojis.index') }}"><i
                            class="bi bi-emoji-smile-fill"></i> Category Emojis</a></li>
                <li><a class="nav-link" href="{{ route('admin.promotional.form') }}"><i class="bi bi-bell-fill"></i>
                        Promotional Notifications</a></li>
                <li><a class="nav-link" href="{{ route('admin.sms.dashboard') }}"><i class="bi bi-chat-dots"></i> SMS
                        Management</a></li>
                <li><a class="nav-link" href="{{ route('admin.bulkProductUpload') }}"><i class="bi bi-upload"></i> Bulk
                        Product Upload</a></li>
                <li><a class="nav-link" href="{{ route('admin.warehouse.dashboard') }}"><i class="bi bi-shop"></i>
                        Warehouse Management</a></li>
                <li><a class="nav-link" href="{{ route('admin.delivery-partners.dashboard') }}"><i
                            class="bi bi-bicycle"></i> Delivery Partners</a></li>
                <li><a class="nav-link text-danger" href="{{ route('admin.logout') }}">
                        <i class="bi bi-box-arrow-right"></i> Logout</a>
                </li>
            </ul>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <h2 class="mb-4"><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>

            {{-- Stats Cards --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3 bg-light">
                        <div class="text-primary icon"><i class="bi bi-box-seam"></i></div>
                        <h6 class="mt-2">Products</h6>
                        <p class="display-6 fw-bold">{{ $products['count'] ?? 0 }}</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card text-center p-3 bg-light">
                        <div class="text-success icon"><i class="bi bi-cart-check"></i></div>
                        <h6 class="mt-2">Orders</h6>
                        <p class="display-6 fw-bold">{{ $ordersCount->count() }}</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card text-center p-3 bg-light">
                        <div class="text-warning icon"><i class="bi bi-person-badge"></i></div>
                        <h6 class="mt-2">Sellers</h6>
                        <p class="display-6 fw-bold">{{ $sellersCount }}</p>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card text-center p-3 bg-light">
                        <div class="text-danger icon"><i class="bi bi-people"></i></div>
                        <h6 class="mt-2">Buyers</h6>
                        <p class="display-6 fw-bold">{{ $buyersCount }}</p>
                    </div>
                </div>
            </div>

            {{-- Delivery Partners Row --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card stat-card text-center p-3 bg-light">
                        <div class="text-info icon"><i class="bi bi-truck"></i></div>
                        <h6 class="mt-2">Total Delivery Partners</h6>
                        <p class="display-6 fw-bold">{{ $deliveryPartnersCount ?? 0 }}</p>
                        <a href="{{ route('admin.delivery-partners.index') }}" class="btn btn-sm btn-info mt-2">
                            <i class="bi bi-eye"></i> View All
                        </a>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card stat-card text-center p-3 bg-light">
                        <div class="text-success icon"><i class="bi bi-check-circle"></i></div>
                        <h6 class="mt-2">Active Delivery Partners</h6>
                        <p class="display-6 fw-bold">{{ $activeDeliveryPartnersCount ?? 0 }}</p>
                        <a href="{{ route('admin.delivery-partners.dashboard') }}" class="btn btn-sm btn-success mt-2">
                            <i class="bi bi-graph-up"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0"
                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="card-body text-white">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-2"><i class="bi bi-bell-fill"></i> Send Promotional Notifications</h5>
                                    <p class="mb-3 opacity-75">Send Amazon-style promotional emails and notifications to
                                        your customers. Boost sales with targeted campaigns!</p>
                                    <a href="{{ route('admin.promotional.form') }}" class="btn btn-light btn-lg">
                                        <i class="bi bi-megaphone"></i> Start Campaign
                                    </a>
                                </div>
                                <div class="col-md-4 text-center">
                                    <i class="bi bi-envelope-heart" style="font-size: 4rem; opacity: 0.7;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Orders Table --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-cart-check"></i> Recent Orders</h5>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-light text-dark">{{ $ordersCount->count() }} Orders</span>
                        <button class="btn btn-sm btn-outline-light" id="refreshOrdersBtn" title="Refresh orders">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Ordered At</th>
                                <th>Courier Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ordersCount as $order)
                                <tr>
                                    <td class="fw-bold">{{ $order->id }}</td>
                                    <td>
                                        <i class="bi bi-person-circle text-primary"></i>
                                        {{ $order->buyerUser->name ?? 'Unknown' }}
                                    </td>
                                    <td>
                                        <i class="bi bi-box-seam text-success"></i>
                                        {{ $order->product->name ?? '-' }}
                                    </td>
                                    <td><span class="badge bg-info">{{ $order->quantity }}</span></td>
                                    <td class="fw-semibold text-success">₹{{ number_format($order->amount, 2) }}</td>
                                    <td>
                                        @if($order->status === 'Delivered')
                                            <span class="badge bg-success">{{ $order->status }}</span>
                                        @elseif($order->status === 'Pending')
                                            <span class="badge bg-warning">{{ $order->status }}</span>
                                        @elseif($order->status === 'Cancelled')
                                            <span class="badge bg-danger">{{ $order->status }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $order->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->payment_method === 'Online')
                                            <span class="badge bg-primary"><i class="bi bi-credit-card"></i> Online</span>
                                        @elseif($order->payment_method === 'COD')
                                            <span class="badge bg-secondary"><i class="bi bi-cash"></i> COD</span>
                                        @else
                                            <span class="badge bg-dark">{{ $order->payment_method }}</span>
                                        @endif
                                    </td>
                                    <td><i class="bi bi-calendar-event"></i>
                                        {{ $order->created_at->format('d M Y, h:i A') }}
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.updateTracking', $order->id) }}"
                                            class="d-flex gap-2 align-items-center">
                                            @csrf
                                            <input type="text" name="tracking_number" class="form-control form-control-sm"
                                                placeholder="Tracking #" value="{{ $order->tracking_number }}">
                                            <button type="submit" class="btn btn-sm btn-outline-success">Save</button>
                                        </form>
                                        @if($order->tracking_number)
                                            <div class="mt-1 text-success small">Tracking #:
                                                <strong>{{ $order->tracking_number }}</strong>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-muted py-3">
                                        <i class="bi bi-inbox fs-4"></i> No orders found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Mobile Sidebar Toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.getElementById('sidebarMenu');

            if (menuToggle) {
                menuToggle.addEventListener('click', function () {
                    sidebar.classList.toggle('show');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function (event) {
                if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
                    const isClickInsideSidebar = sidebar.contains(event.target) || menuToggle.contains(event.target);
                    if (!isClickInsideSidebar) {
                        sidebar.classList.remove('show');
                    }
                }
            });

            // Auto-refresh orders every 30 seconds
            setInterval(function () {
                location.reload();
            }, 30000); // 30 seconds

            // Manual refresh button
            const refreshBtn = document.getElementById('refreshOrdersBtn');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function () {
                    this.disabled = true;
                    this.innerHTML = '<i class="bi bi-hourglass-split"></i> Loading...';
                    location.reload();
                });
            }
        });
    </script>
</body>

</html>