<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Warehouse Management') - Quick Delivery System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom Warehouse Styles -->
    <style>
        :root {
            --warehouse-primary: #667eea;
            --warehouse-secondary: #764ba2;
            --warehouse-success: #28a745;
            --warehouse-warning: #ffc107;
            --warehouse-danger: #dc3545;
            --warehouse-info: #17a2b8;
            --warehouse-light: #f8f9fa;
            --warehouse-dark: #343a40;
        }

        body {
            background-color: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-warehouse {
            background: linear-gradient(135deg, var(--warehouse-primary) 0%, var(--warehouse-secondary) 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
        }

        .sidebar {
            background: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            min-height: calc(100vh - 76px);
            position: sticky;
            top: 76px;
        }

        .sidebar .nav-link {
            color: #6c757d;
            border-radius: 8px;
            margin: 2px 0;
            padding: 12px 16px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: var(--warehouse-primary);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 12px;
            text-align: center;
        }

        .main-content {
            padding: 2rem 0;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--warehouse-primary) 0%, var(--warehouse-secondary) 100%);
            border: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--warehouse-primary) 0%, var(--warehouse-secondary) 100%) !important;
        }

        .table th {
            font-weight: 600;
            color: var(--warehouse-dark);
            border-top: none;
        }

        .badge {
            border-radius: 6px;
            font-weight: 500;
        }

        .alert {
            border: none;
            border-radius: 10px;
            border-left: 4px solid;
        }

        .alert-success { border-left-color: var(--warehouse-success); }
        .alert-warning { border-left-color: var(--warehouse-warning); }
        .alert-danger { border-left-color: var(--warehouse-danger); }
        .alert-info { border-left-color: var(--warehouse-info); }

        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            border-radius: 10px;
        }

        .dropdown-item:hover {
            background-color: var(--warehouse-light);
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--warehouse-primary);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .breadcrumb {
            background: transparent;
            padding: 0;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            font-size: 1.2rem;
            color: #6c757d;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--warehouse-primary) 0%, var(--warehouse-secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--warehouse-danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: static;
                min-height: auto;
            }
            
            .main-content {
                padding: 1rem 0;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-warehouse fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('warehouse.dashboard') }}">
                <i class="bi bi-house-gear me-2"></i>
                Warehouse System
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('warehouse.dashboard') }}">
                            <i class="bi bi-speedometer2 me-1"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('warehouse.inventory') }}">
                            <i class="bi bi-boxes me-1"></i>
                            Inventory
                        </a>
                    </li>
                    @if(auth('warehouse')->user()->hasPermission('view_reports'))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('warehouse.reports') }}">
                            <i class="bi bi-graph-up me-1"></i>
                            Reports
                        </a>
                    </li>
                    @endif
                </ul>

                <ul class="navbar-nav">
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <span class="notification-badge">3</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <li><a class="dropdown-item" href="#">
                                <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                                5 products low on stock
                            </a></li>
                            <li><a class="dropdown-item" href="#">
                                <i class="bi bi-x-circle text-danger me-2"></i>
                                2 products out of stock
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="#">View All</a></li>
                        </ul>
                    </li>

                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <div class="user-avatar me-2">
                                {{ substr(auth('warehouse')->user()->name, 0, 1) }}
                            </div>
                            <div class="d-none d-lg-block">
                                <div class="fw-semibold">{{ auth('warehouse')->user()->name }}</div>
                                <div class="small opacity-75">{{ auth('warehouse')->user()->role_display }}</div>
                            </div>
                            <i class="bi bi-chevron-down ms-2"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('warehouse.profile') }}">
                                <i class="bi bi-person me-2"></i>Profile
                            </a></li>
                            @if(auth('warehouse')->user()->hasPermission('manage_users'))
                            <li><a class="dropdown-item" href="{{ route('warehouse.users') }}">
                                <i class="bi bi-people me-2"></i>Manage Users
                            </a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('warehouse.logout') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid" style="padding-top: 76px;">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ Request::routeIs('warehouse.dashboard') ? 'active' : '' }}" 
                               href="{{ route('warehouse.dashboard') }}">
                                <i class="bi bi-speedometer2"></i>
                                Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link {{ Request::routeIs('warehouse.inventory*') ? 'active' : '' }}" 
                               href="{{ route('warehouse.inventory') }}">
                                <i class="bi bi-boxes"></i>
                                Inventory Management
                            </a>
                        </li>

                        @if(auth('warehouse')->user()->hasPermission('add_stock'))
                        <li class="nav-item">
                            <a class="nav-link {{ Request::routeIs('warehouse.inventory.add') ? 'active' : '' }}" 
                               href="{{ route('warehouse.inventory.add') }}">
                                <i class="bi bi-plus-circle"></i>
                                Add Stock
                            </a>
                        </li>
                        @endif

                        @if(auth('warehouse')->user()->hasPermission('adjust_stock'))
                        <li class="nav-item">
                            <a class="nav-link {{ Request::routeIs('warehouse.inventory.adjust') ? 'active' : '' }}" 
                               href="{{ route('warehouse.inventory.adjust') }}">
                                <i class="bi bi-arrow-repeat"></i>
                                Adjust Stock
                            </a>
                        </li>
                        @endif

                        <li class="nav-item">
                            <a class="nav-link {{ Request::routeIs('warehouse.stock-movements*') ? 'active' : '' }}" 
                               href="{{ route('warehouse.stock-movements') }}">
                                <i class="bi bi-clock-history"></i>
                                Stock Movements
                            </a>
                        </li>

                        @if(auth('warehouse')->user()->hasPermission('manage_quick_delivery'))
                        <li class="nav-item">
                            <a class="nav-link {{ Request::routeIs('warehouse.quick-delivery*') ? 'active' : '' }}" 
                               href="{{ route('warehouse.quick-delivery') }}">
                                <i class="bi bi-lightning"></i>
                                Quick Delivery
                            </a>
                        </li>
                        @endif

                        @if(auth('warehouse')->user()->hasPermission('view_reports'))
                        <li class="nav-item">
                            <a class="nav-link {{ Request::routeIs('warehouse.reports*') ? 'active' : '' }}" 
                               href="{{ route('warehouse.reports') }}">
                                <i class="bi bi-graph-up"></i>
                                Reports & Analytics
                            </a>
                        </li>
                        @endif

                        @if(auth('warehouse')->user()->hasPermission('manage_locations'))
                        <li class="nav-item">
                            <a class="nav-link {{ Request::routeIs('warehouse.locations*') ? 'active' : '' }}" 
                               href="{{ route('warehouse.locations') }}">
                                <i class="bi bi-geo-alt"></i>
                                Locations
                            </a>
                        </li>
                        @endif

                        <hr>

                        <li class="nav-item">
                            <a class="nav-link {{ Request::routeIs('warehouse.profile') ? 'active' : '' }}" 
                               href="{{ route('warehouse.profile') }}">
                                <i class="bi bi-person"></i>
                                My Profile
                            </a>
                        </li>

                        @if(auth('warehouse')->user()->hasPermission('manage_users'))
                        <li class="nav-item">
                            <a class="nav-link {{ Request::routeIs('warehouse.users*') ? 'active' : '' }}" 
                               href="{{ route('warehouse.users') }}">
                                <i class="bi bi-people"></i>
                                User Management
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Breadcrumb -->
                @if(!Request::routeIs('warehouse.dashboard'))
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('warehouse.dashboard') }}">Dashboard</a>
                        </li>
                        @yield('breadcrumb')
                    </ol>
                </nav>
                @endif

                <!-- Flash Messages -->
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                <!-- Page Content -->
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // CSRF token for AJAX requests
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}'
        };

        // Set CSRF token for all AJAX requests
        if (window.jQuery) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        }
    </script>
    
    @stack('scripts')
    @yield('scripts')
</body>
</html>