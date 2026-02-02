<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="icon" type="image/png" href="{{ asset('asset/images/grabbasket.png') }}">
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
            margin-top: 50px;
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

        .card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background-color: #0d6efd;
            color: white;
            font-weight: 500;
            padding: 12px 20px;
        }

        .table th,
        .table td {
            vertical-align: middle;
            text-align: center;
        }

        .action-btns .btn {
            margin: 0 2px;
            padding: 5px 10px;
            font-size: 0.85rem;
        }

        .btn-suspend {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }

        .btn-suspend:hover {
            background-color: #e0a800;
            border-color: #e0a800;
        }

        .btn-delete {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }

        .badge-status {
            padding: 5px 10px;
            font-size: 0.8rem;
            border-radius: 20px;
        }

        .badge-active {
            background-color: #28a745;
            color: white;
        }

        .badge-suspended {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-admin {
            background-color: #6c757d;
            color: white;
        }

        /* Improve pagination alignment under table */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 0;
        }

        .pagination .page-item {
            margin: 0;
        }

        .pagination .page-link {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #007bff;
            border: 1px solid #dee2e6;
        }

        .pagination .page-link:hover {
            background-color: #e9ecef;
            color: #0056b3;
        }

        .pagination .active .page-link {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }
    </style>
</head>

<body>
    <!-- Mobile Menu Toggle -->
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
                <li><a class="nav-link " href="{{ route('admin.products') }}"><i class="bi bi-box-seam"></i>
                        Products</a></li>
                <li><a class="nav-link" href="{{ route('admin.orders') }}"><i class="bi bi-cart-check"></i> Orders</a>
                </li>
                <li><a class="nav-link" href="{{ route('tracking.form') }}"><i class="bi bi-truck"></i> Track
                        Package</a></li>
                <li><a class="nav-link active " href="{{ route('admin.manageuser') }}"><i class="bi bi-people"></i>
                        Users</a></li>
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

    <!-- Main Content -->
    <div class="content" id="mainContent">
        <h2 class="mb-4"><i class="bi bi-people"></i> Manage Users</h2>

        <!-- Filter Section -->
        <form method="GET" class="mb-4">
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or email..."
                        value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="all">All Roles</option>
                        <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="seller" {{ request('role') == 'seller' ? 'selected' : '' }}>Seller</option>
                        <option value="buyer" {{ request('role') == 'buyer' ? 'selected' : '' }}>Buyer</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>

        <!-- User List Card -->
        <div class="card shadow-sm">
            <div class="card-header bg-gradient bg-dark text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-people me-2"></i> Users List</span>
                <span class="badge bg-secondary">{{ $users->total() }} Users</span>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ ucfirst($user->role) }}</td>
                                <td>{{ $user->phone ?? '-' }}</td>
                                <td>
                                    @if($user->role === 'admin')
                                        <span class="badge badge-admin">Admin</span>
                                    @elseif($user->is_suspended ?? false)
                                        <span class="badge badge-suspended">Suspended</span>
                                    @else
                                        <span class="badge badge-active">Active</span>
                                    @endif
                                </td>
                                <td class="action-btns">
                                    <!-- Suspend Button -->
                                    <form action="{{ route('admin.users.suspend', $user->id) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-suspend btn-sm">
                                            <i class="bi bi-person-x"></i> {{ $user->is_suspended ? 'Restore' : 'Suspend' }}
                                        </button>
                                    </form>

                                    <!-- Delete -->
                                    <form action="{{ route('admin.users.delete', $user->id) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Are you sure?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-delete btn-sm">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted text-center py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                    <!-- Left side: pagination info -->
                    <div class="text-secondary small mb-2 mb-md-0">
                        Showing <strong>{{ $users->firstItem() }}</strong> to
                        <strong>{{ $users->lastItem() }}</strong> of
                        <strong>{{ $users->total() }}</strong> results
                    </div>

                    <!-- Right side: pagination links -->
                    <div>
                        {{ $users->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>


            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.getElementById('sidebarMenu');
            const mainContent = document.getElementById('mainContent');

            if (menuToggle) {
                menuToggle.addEventListener('click', function () {
                    sidebar.classList.toggle('show');
                    mainContent.classList.toggle('shifted');
                });
            }
        });
    </script>
</body>

</html>