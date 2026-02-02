<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Orders</title>
    <link rel="icon" type="image/png" href="{{ asset('asset/images/grabbasket.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }

        .content {
            margin-left: 230px;
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 0;
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

        .filter-controls {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }

        .pagination .page-link {
            color: #2c3e50;
            border: 1px solid #ddd;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .pagination .page-link:hover {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }

        .pagination .page-item.active .page-link {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }

        /* Table */
        .table thead th {
            background-color: #2c3e50;
            color: white;
            font-weight: 600;
            text-align: center;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .payment-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .payment-badge.paypal { background-color: #003087; color: white; }
        .payment-badge.credit-card { background-color: #2c3e50; color: white; }
        .payment-badge.cod { background-color: #e67e22; color: white; }

        /* Sidebar */
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
            overflow: hidden;
            height: 100vh;
        }

        .sidebar .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100px;
            margin-top: -20px;
        }

        .sidebar .logo img {
            width: 120px;
            height: auto;
            object-fit: contain;
        }

        .sidebar-content {
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0 15px 20px;
            height: calc(10;100vh - 140px);
            margin-top: 30px;
        }

        .sidebar .nav-link {
            color: #bdc3c7;
            margin: 6px 0;
            padding: 10px 15px;
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

        @media (max-width: 768px) {
            .sidebar { left: -250px; }
            .sidebar.show { left: 0; }
        }

        /* Scrollbar */
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

        /* Small button tweak */
        .filter-controls .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
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
                <img src="{{ asset('asset/images/grabbasket.png') }}" alt="Logo">
            </div>
        </div>
        <div class="sidebar-content">
            <ul class="nav nav-pills flex-column">
                <li><a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a class="nav-link" href="{{ route('admin.products') }}"><i class="bi bi-box-seam"></i> Products</a></li>
                <li><a class="nav-link {{ $activeType === 'standard' ? 'active' : '' }}" href="{{ route('admin.orders') }}"><i class="bi bi-cart-check"></i> Standard Orders</a></li>
                <li><a class="nav-link {{ $activeType === 'food' ? 'active' : '' }}" href="{{ route('admin.orders.food') }}"><i class="bi bi-egg-fried"></i> Food Orders</a></li>
                <li><a class="nav-link {{ $activeType === 'express' ? 'active' : '' }}" href="{{ route('admin.orders.express') }}"><i class="bi bi-lightning-charge"></i> Express Orders</a></li>
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
                <li><a class="nav-link text-danger" href="{{ route('admin.logout') }}"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <h2 class="mb-4">
                <i class="bi bi-cart-check"></i> 
                @if($activeType === 'food') Food Delivery Orders 
                @elseif($activeType === 'express') 10-Min Express Orders 
                @else Orders Dashboard @endif
            </h2>

            {{-- 🔍 Filter Controls --}}
            <div class="filter-controls">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}"
                            placeholder="Customer or Product...">
                    </div>
                    @if($activeType === 'all')
                    <div class="col-md-2">
                        <label class="form-label small">Order Type</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="all">All Types</option>
                            <option value="standard" {{ request('type') == 'standard' ? 'selected' : '' }}>Standard</option>
                            <option value="food" {{ request('type') == 'food' ? 'selected' : '' }}>Food</option>
                            <option value="express" {{ request('type') == 'express' ? 'selected' : '' }}>Express (10-min)</option>
                        </select>
                    </div>
                    @endif
                    <div class="col-md-1">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="all">All</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                    {{ $status }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">From</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">To</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100 d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        @php
                            $resetRoute = match($activeType) {
                                'food' => 'admin.orders.food',
                                'express' => 'admin.orders.express',
                                default => 'admin.orders'
                            };
                        @endphp
                        <a href="{{ route($resetRoute) }}" class="btn btn-sm btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-arrow-repeat"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            {{-- 📋 Orders Table --}}
            <div class="card shadow-lg border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-cart-check"></i> Orders List</h5>
                    <span class="badge bg-light text-dark">{{ $orders->total() }} Orders</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover table-bordered align-middle text-center">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Customer</th>
                                <th>Items / Product</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tracking / Partner</th>
                                <th>Payment</th>
                                <th>Ordered At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                <tr>
                                    <td class="fw-bold">{{ $order->id }}</td>
                                    <td>
                                        @php
                                            $typeLabel = $order->type ?? 'standard';
                                            $badgeClass = match ($typeLabel) {
                                                'food' => 'bg-success',
                                                'express' => 'bg-info',
                                                'mixed' => 'bg-warning',
                                                default => 'bg-primary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }} py-1 px-2">{{ ucfirst($typeLabel) }}</span>
                                    </td>
                                    <td>
                                        <i class="bi bi-person-circle text-primary"></i>
                                        @if($typeLabel === 'standard')
                                            {{ $order->buyerUser->name ?? 'Unknown' }}
                                        @elseif($typeLabel === 'food')
                                            {{ $order->customer_name ?? 'Customer' }}
                                        @else
                                            {{ $order->user->name ?? $order->customer_name ?? 'User' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($typeLabel === 'standard')
                                            <i class="bi bi-box-seam text-success"></i> {{ $order->product->name ?? '-' }}
                                            (x{{ $order->quantity ?? 1 }})
                                        @else
                                            <div class="text-start">
                                                @foreach($order->items ?? [] as $item)
                                                    <small class="d-block">•
                                                        {{ $item->product->name ?? $item->foodItem->name ?? 'Item' }}
                                                        (x{{ $item->quantity }})</small>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="fw-semibold text-success">₹{{ number_format($order->amount ?? $order->total_amount ?? $order->order_total ?? 0, 2) }}</td>
                                    <td>
                                        @if($typeLabel === 'standard')
                                            <form action="{{ route('admin.updateOrderStatus', $order->id) }}" method="POST"
                                                class="d-flex align-items-center justify-content-center gap-2">
                                                @csrf
                                                <input type="hidden" name="order_type" value="{{ $typeLabel }}">
                                                <select name="status" class="form-select form-select-sm w-auto">
                                                    <option value="Pending" {{ strtolower($order->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                                                    <option value="Preparing" {{ in_array(strtolower($order->status), ['preparing', 'confirmed']) ? 'selected' : '' }}>Preparing</option>
                                                    <option value="Shipped" {{ in_array(strtolower($order->status), ['shipped', 'out for delivery']) ? 'selected' : '' }}>Shipped</option>
                                                    <option value="Delivered" {{ strtolower($order->status) == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                                    <option value="Cancelled" {{ strtolower($order->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-outline-primary shadow-sm">
                                                    <i class="bi bi-check2"></i>
                                                </button>
                                            </form>
                                        @else
                                            @php
                                                $status = strtolower($order->status ?? 'pending');
                                                $variant = match($status) {
                                                    'pending', 'placed' => 'warning',
                                                    'preparing', 'accepted', 'confirmed' => 'info',
                                                    'ready', 'shipped', 'out for delivery', 'picked_up' => 'primary',
                                                    'delivered', 'completed' => 'success',
                                                    'cancelled', 'rejected' => 'danger',
                                                    default => 'secondary'
                                                };
                                            @endphp
                                            <span class="badge rounded-pill bg-{{ $variant }} px-2 py-1 text-uppercase" style="font-size: 0.7rem;">
                                                {{ $order->status }}
                                            </span>
                                            <div class="mt-1 small text-muted">Managed by Seller</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($typeLabel === 'standard')
                                            <form action="{{ route('admin.updateTracking', $order->id) }}" method="POST"
                                                class="d-flex flex-column gap-1">
                                                @csrf
                                                <div class="d-flex gap-1">
                                                    <input type="text" name="tracking_number"
                                                        value="{{ $order->tracking_number }}"
                                                        class="form-control form-control-sm" placeholder="Tracking #">
                                                    <select name="courier_name" class="form-select form-select-sm">
                                                        <option value="">Courier</option>
                                                        <option value="Delhivery" {{ $order->courier_name == 'Delhivery' ? 'selected' : '' }}>Delhivery</option>
                                                        <option value="Blue Dart" {{ $order->courier_name == 'Blue Dart' ? 'selected' : '' }}>Blue Dart</option>
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-outline-success">Update</button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.assignDeliveryPartner', $order->id) }}" method="POST"
                                                class="d-flex flex-column gap-1">
                                                @csrf
                                                <input type="hidden" name="order_type" value="{{ $typeLabel }}">
                                                <select name="delivery_partner_id" class="form-select form-select-sm">
                                                    <option value="">Assign Partner</option>
                                                    @foreach($partners as $partner)
                                                        <option value="{{ $partner->id }}" 
                                                            {{ $order->delivery_partner_id == $partner->id ? 'selected' : '' }}
                                                            {{ (!$partner->is_online || !$partner->is_available || $partner->current_order_id) && $order->delivery_partner_id != $partner->id ? 'disabled' : '' }}>
                                                            {{ $partner->name }}
                                                            @if(!$partner->is_online) (Offline)
                                                            @elseif(!$partner->is_available || $partner->current_order_id) (Busy)
                                                            @else (Available) @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-outline-info">Assign</button>
                                            </form>
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->payment_method === 'Online')
                                            <span class="badge payment-badge paypal">Online</span>
                                        @elseif($order->payment_method === 'COD')
                                            <span class="badge payment-badge cod">COD</span>
                                        @else
                                            <span class="badge payment-badge credit-card">{{ $order->payment_method }}</span>
                                        @endif
                                    </td>
                                    <td><i class="bi bi-calendar-event"></i> {{ $order->created_at->format('d M Y, h:i A') }}</td>
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

                    <div class="d-flex justify-content-center mt-3">
                        {{ $orders->links() }}
                    </div>
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
        });
    </script>
</body>

</html>