<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Delivery Partner</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --success-color: #16a34a;
            --warning-color: #ca8a04;
            --danger-color: #dc2626;
            --dark-color: #1f2937;
            --light-gray: #f8fafc;
            --border-color: #e5e7eb;
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            line-height: 1.6;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color), var(--primary-dark));
            color: white;
            z-index: 1000;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            backdrop-filter: blur(10px);
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 0;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .main-content.sidebar-open {
            margin-left: var(--sidebar-width);
        }

        /* Top Navigation */
        .topbar {
            background: white;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .topbar-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--dark-color);
            cursor: pointer;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .status-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--danger-color);
        }

        .status-indicator.online {
            background: var(--success-color);
        }

        .status-indicator.busy {
            background: var(--warning-color);
        }

        /* Content Area */
        .content {
            padding: 2rem 1.5rem;
        }

        /* Cards */
        .card {
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            text-align: center;
            padding: 1.5rem;
        }

        .stat-card.success {
            background: linear-gradient(135deg, var(--success-color), #15803d);
        }

        .stat-card.warning {
            background: linear-gradient(135deg, var(--warning-color), #a16207);
        }

        .stat-card.info {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .stat-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 2rem;
            opacity: 0.3;
        }

        /* Order Cards */
        .order-card {
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .order-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .order-number {
            font-weight: 600;
            color: var(--primary-color);
        }

        .order-status {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: rgba(202, 138, 4, 0.1);
            color: var(--warning-color);
        }

        .status-assigned {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
        }

        .status-picked_up {
            background: rgba(139, 69, 19, 0.1);
            color: #8b4513;
        }

        .status-in_transit {
            background: rgba(255, 165, 0, 0.1);
            color: #ffa500;
        }

        .status-delivered {
            background: rgba(22, 163, 74, 0.1);
            color: var(--success-color);
        }

        /* Notifications */
        .notification-item {
            display: flex;
            align-items: flex-start;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1rem;
        }

        .notification-icon.success {
            background: rgba(22, 163, 74, 0.1);
            color: var(--success-color);
        }

        .notification-icon.warning {
            background: rgba(202, 138, 4, 0.1);
            color: var(--warning-color);
        }

        .notification-icon.danger {
            background: rgba(220, 38, 38, 0.1);
            color: var(--danger-color);
        }

        .notification-icon.info {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
            }

            .main-content.sidebar-open {
                margin-left: 0;
            }

            .content {
                padding: 1rem;
            }

            .stat-value {
                font-size: 2rem;
            }

            .topbar {
                padding: 0.75rem 1rem;
            }
        }

        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Floating Action Button */
        .fab {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            font-size: 1.5rem;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .fab:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(37, 99, 235, 0.5);
        }

        /* Custom Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #15803d);
            border: none;
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #a16207);
            border: none;
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('delivery-partner.dashboard') }}" class="sidebar-brand">
                <i class="fas fa-shipping-fast me-2"></i>DeliveryHub
            </a>
        </div>
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="{{ route('delivery-partner.dashboard') }}" class="nav-link {{ request()->routeIs('delivery-partner.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('delivery-partner.orders.index') }}" class="nav-link {{ request()->routeIs('delivery-partner.orders.*') ? 'active' : '' }}">
                        <i class="fas fa-shopping-bag"></i>My Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('delivery-partner.orders.available') }}" class="nav-link">
                        <i class="fas fa-search"></i>Available Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('delivery-partner.earnings.index') }}" class="nav-link {{ request()->routeIs('delivery-partner.earnings.*') ? 'active' : '' }}">
                        <i class="fas fa-rupee-sign"></i>Earnings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('delivery-partner.profile') }}" class="nav-link {{ request()->routeIs('delivery-partner.profile') ? 'active' : '' }}">
                        <i class="fas fa-user"></i>Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('delivery-partner.notifications') }}" class="nav-link {{ request()->routeIs('delivery-partner.notifications') ? 'active' : '' }}">
                        <i class="fas fa-bell"></i>Notifications
                        @if(isset($unreadCount) && $unreadCount > 0)
                            <span class="badge bg-danger ms-auto">{{ $unreadCount }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('delivery-partner.support') }}" class="nav-link {{ request()->routeIs('delivery-partner.support') ? 'active' : '' }}">
                        <i class="fas fa-headset"></i>Support
                    </a>
                </li>
            </ul>
        </nav>
        <div class="mt-auto p-3">
            <form method="POST" action="{{ route('delivery-partner.logout') }}">
                @csrf
                <button type="submit" class="nav-link w-100 text-start border-0 bg-transparent">
                    <i class="fas fa-sign-out-alt"></i>Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navigation -->
        <div class="topbar">
            <div class="topbar-content">
                <div class="topbar-left">
                    <button class="sidebar-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                <div class="topbar-right">
                    <div class="status-toggle">
                        <div class="status-indicator {{ Auth::guard('delivery_partner')->user()->is_online ? (Auth::guard('delivery_partner')->user()->is_available ? 'online' : 'busy') : '' }}" id="statusIndicator"></div>
                        <select class="form-select form-select-sm" id="statusSelect" onchange="updateStatus()">
                            <option value="offline" {{ !Auth::guard('delivery_partner')->user()->is_online ? 'selected' : '' }}>Offline</option>
                            <option value="online" {{ Auth::guard('delivery_partner')->user()->is_online && !Auth::guard('delivery_partner')->user()->is_available ? 'selected' : '' }}>Online</option>
                            <option value="available" {{ Auth::guard('delivery_partner')->user()->is_available ? 'selected' : '' }}>Available</option>
                        </select>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <img src="{{ Auth::guard('delivery_partner')->user()->profile_photo_url }}" 
                                 alt="Profile" class="rounded-circle" width="32" height="32">
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">{{ Auth::guard('delivery_partner')->user()->name }}</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('delivery-partner.profile') }}">
                                <i class="fas fa-user me-2"></i>Profile
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('delivery-partner.earnings.index') }}">
                                <i class="fas fa-rupee-sign me-2"></i>Earnings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('delivery-partner.logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="fab" onclick="toggleAvailability()" title="Quick Toggle Availability">
        <i class="fas fa-motorcycle" id="fabIcon"></i>
    </button>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // CSRF Token setup
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('sidebar-open');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target) &&
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                document.getElementById('mainContent').classList.remove('sidebar-open');
            }
        });

        // Status update
        function updateStatus() {
            const select = document.getElementById('statusSelect');
            const status = select.value;
            const indicator = document.getElementById('statusIndicator');
            
            $.ajax({
                url: '{{ route("delivery-partner.dashboard") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: status
                },
                success: function(response) {
                    if (response.success) {
                        // Update indicator
                        indicator.className = 'status-indicator';
                        if (status === 'available') {
                            indicator.classList.add('online');
                        } else if (status === 'online') {
                            indicator.classList.add('busy');
                        }
                        
                        // Show success message
                        showToast('Status updated successfully', 'success');
                    } else {
                        showToast(response.message || 'Failed to update status', 'error');
                        // Revert select value
                        select.value = select.getAttribute('data-original');
                    }
                },
                error: function() {
                    showToast('Failed to update status', 'error');
                    select.value = select.getAttribute('data-original');
                }
            });
        }

        // Quick availability toggle
        function toggleAvailability() {
            const select = document.getElementById('statusSelect');
            const currentStatus = select.value;
            
            let newStatus;
            if (currentStatus === 'offline') {
                newStatus = 'available';
            } else if (currentStatus === 'available') {
                newStatus = 'online';
            } else {
                newStatus = 'available';
            }
            
            select.value = newStatus;
            updateStatus();
        }

        // Toast notifications
        function showToast(message, type = 'info') {
            const toastContainer = getOrCreateToastContainer();
            const toastId = 'toast-' + Date.now();
            
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'primary'} border-0" 
                     role="alert" aria-live="assertive" aria-atomic="true" id="${toastId}">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            const toast = new bootstrap.Toast(document.getElementById(toastId));
            toast.show();
            
            // Remove toast element after it's hidden
            document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
                this.remove();
            });
        }

        function getOrCreateToastContainer() {
            let container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                container.style.zIndex = '1100';
                document.body.appendChild(container);
            }
            return container;
        }

        // Auto-refresh status
        setInterval(function() {
            // Refresh page data every 30 seconds
            if (window.location.pathname.includes('/dashboard')) {
                $.get('{{ route("delivery-partner.dashboard") }}', function(data) {
                    // Update dynamic content without full page reload
                }).catch(function() {
                    // Handle errors silently
                });
            }
        }, 30000);

        // Service Worker for offline support
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/delivery-partner-sw.js')
                .then(function(registration) {
                    console.log('Service Worker registered');
                })
                .catch(function(error) {
                    console.log('Service Worker registration failed');
                });
        }

        // Store original status for revert
        document.getElementById('statusSelect').setAttribute('data-original', document.getElementById('statusSelect').value);
    </script>

    @stack('scripts')
</body>
</html>