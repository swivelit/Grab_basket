@extends('layouts.minimal')

@section('title', 'Restaurant Dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row g-0">
            <!-- Sidebar -->
            <aside class="col-12 col-md-3 col-lg-2 bg-white border-end vh-100 position-fixed d-none d-md-block"
                style="padding-top: 1.75rem;">
                <div class="text-center mb-4 px-3">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width:72px;height:72px;background:#E23744;color:#fff;font-size:28px;">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h5 class="mt-3 mb-0">{{ $hotelOwner->restaurant_name ?? 'My Restaurant' }}</h5>
                    <small class="text-muted">{{ $hotelOwner->name }}</small>
                </div>

                <nav class="nav flex-column px-2">
                    <a class="nav-link py-2 mb-1 rounded {{ request()->routeIs('hotel-owner.dashboard') ? 'bg-light' : '' }}"
                        href="{{ route('hotel-owner.dashboard') }}">
                        <i class="fas fa-home me-2 text-secondary"></i> Dashboard
                    </a>
                    <a class="nav-link py-2 mb-1 rounded" href="{{ route('hotel-owner.food-items.index') }}">
                        <i class="fas fa-utensils me-2 text-secondary"></i> Menu
                    </a>
                    <a class="nav-link py-2 mb-1 rounded" href="{{ route('hotel-owner.orders.index') }}">
                        <i class="fas fa-concierge-bell me-2 text-secondary"></i> Orders
                        <span class="badge bg-warning text-dark ms-2">{{ $stats['pending_orders'] ?? 0 }}</span>
                    </a>

                    <a class="nav-link py-2 mb-1 rounded"
                        href="{{ \Illuminate\Support\Facades\Route::has('hotel-owner.earnings.index') ? route('hotel-owner.earnings.index') : '#' }}">
                        <i class="fas fa-wallet me-2 text-secondary"></i> Earnings
                    </a>
                    <a class="nav-link py-2 mb-1 rounded" href="{{ route('hotel-owner.profile') }}">
                        <i class="fas fa-user me-2 text-secondary"></i> Profile
                    </a>

                    <!-- Logout -->
                    <form action="{{ route('hotel-owner.logout') }}" method="POST" class="m-0 p-0">
                        @csrf
                        <button type="submit" class="nav-link py-2 mb-1 rounded border-0 bg-transparent w-100 text-start">
                            <i class="fas fa-sign-out-alt me-2 text-secondary"></i> Logout
                        </button>
                    </form>
                </nav>
            </aside>

            <!-- Top bar (mobile) -->
            <div class="d-md-none bg-white border-bottom py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong style="color:#E23744;">grabbaskets</strong>
                    </div>
                    <div>
                        <a href="{{ route('hotel-owner.food-items.create') }}" class="btn btn-sm btn-danger">+ Add Item</a>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-12 col-md-9 offset-md-3 col-lg-10 offset-lg-2 px-3 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0">Overview</h2>
                        <small class="text-muted">Quick insights into your restaurant</small>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end">
                            <small class="text-muted">Status</small>
                            <div>
                                @if($hotelOwner->is_active ?? false)
                                    <span class="badge" style="background:#1BAD40;color:#fff;">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <a href="{{ route('hotel-owner.food-items.create') }}" class="btn btn-danger">Add Item</a>
                        </div>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @include('hotel-owner._dashboard-styles')

                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="card h-100 shadow-sm" style="border-left:4px solid #E23744;">
                            <div class="card-body">
                                <small class="text-muted">Menu Items</small>
                                <h4 class="mt-2">{{ $stats['total_food_items'] ?? 0 }}</h4>
                                <small class="text-success">Active: {{ $stats['active_food_items'] ?? 0 }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card h-100 shadow-sm" style="border-left:4px solid #FF8C00;">
                            <div class="card-body">
                                <small class="text-muted">Orders (Total)</small>
                                <h4 class="mt-2">{{ $stats['total_orders'] ?? 0 }}</h4>
                                <small class="text-warning">Pending: {{ $stats['pending_orders'] ?? 0 }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card h-100 shadow-sm" style="border-left:4px solid #1E88E5;">
                            <div class="card-body">
                                <small class="text-muted">Revenue (This month)</small>
                                <h4 class="mt-2">₹{{ number_format($stats['this_month_revenue'] ?? 0, 2) }}</h4>
                                <small class="text-muted">Total:
                                    ₹{{ number_format($stats['total_revenue'] ?? 0, 2) }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card h-100 shadow-sm" style="border-left:4px solid:#1BAD40;">
                            <div class="card-body">
                                <small class="text-muted">Today's Orders</small>
                                <h4 class="mt-2">{{ $stats['today_orders'] ?? 0 }}</h4>
                                <small class="text-muted">Del on time: --</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-7">
                        <!-- Recent Orders -->
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Orders</h5>
                                <a href="#" class="small">View all</a>
                            </div>
                            <div class="card-body p-0">
                                @if($recentOrders->count() > 0)
                                    <ul class="list-group list-group-flush">
                                        @foreach($recentOrders as $order)
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>#{{ $order->id }}</strong> — {{ $order->user->name ?? 'Guest' }}
                                                    <div><small class="text-muted">{{ $order->orderItems->count() ?? 0 }} items •
                                                            ₹{{ number_format($order->total_amount, 2) }}</small></div>
                                                </div>
                                                <div class="text-end">
                                                    <span
                                                        class="badge rounded-pill {{ $order->status == 'completed' ? 'bg-success' : ($order->status == 'pending' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ ucfirst($order->status) }}</span>
                                                    <div class="mt-2"><a class="btn btn-sm btn-outline-primary">View</a></div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="p-4 text-center text-muted">
                                        <i class="fas fa-box-open fa-2x mb-2"></i>
                                        <div>No recent orders</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Earnings chart -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">Earnings (Last 7 days)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="dashboardEarningsChart" height="120"></canvas>
                            </div>
                        </div>

                        <!-- Menu Highlights -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Menu Highlights</h5>
                                <a href="{{ route('hotel-owner.food-items.index') }}" class="small">Manage menu</a>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    @forelse($popularItems as $item)
                                        <div class="col-6 col-md-4">
                                            <div class="card h-100">
                                                @if($item->image)
                                                    <img src="{{ $item->first_image_url }}" class="img-fluid rounded shadow"
                                                        style="height:140px; object-fit:cover;" alt="{{ $item->name }}">
                                                @else
                                                    <div style="height:140px;background:#f7f7f7;display:flex;align-items:center;justify-content:center;"
                                                        class="rounded shadow">
                                                        <i class="fas fa-image text-muted fa-2x"></i>
                                                    </div>
                                                @endif

                                                <div class="card-body p-2">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong>{{ $item->name }}</strong>
                                                            <div class="small text-muted">₹{{ number_format($item->price, 2) }}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <span class="badge"
                                                                style="background: {{ $item->is_available ? '#1BAD40' : '#6c757d' }}; color:#fff;">{{ $item->is_available ? 'Available' : 'Unavailable' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12 text-center text-muted py-4">
                                            <i class="fas fa-hamburger fa-2x mb-2"></i>
                                            <div>No items yet. Add your first dish!</div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right column -->
                    <div class="col-lg-5">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">Earnings Overview</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h4 class="mb-0">₹{{ number_format($stats['this_month_revenue'] ?? 0, 2) }}</h4>
                                    <small class="text-muted">This month</small>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <small class="text-muted">Total Revenue</small>
                                        <div><strong>₹{{ number_format($stats['total_revenue'] ?? 0, 2) }}</strong></div>
                                    </div>
                                    <div>
                                        <small class="text-muted">Pending</small>
                                        <div><strong>{{ $stats['pending_orders'] ?? 0 }} orders</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Restaurant Status</h5>
                                <a href="{{ route('hotel-owner.profile') }}" class="small">Settings</a>
                            </div>
                            <div class="card-body">
                                <p><strong>Opening:</strong> {{ $hotelOwner->opening_time ?? 'Not set' }} &nbsp;
                                    <strong>Closing:</strong> {{ $hotelOwner->closing_time ?? 'Not set' }}</p>
                                <p>
                                    @if($hotelOwner->isOpen() ?? false)
                                        <span class="badge" style="background:#1BAD40;color:#fff;">Open</span>
                                    @else
                                        <span class="badge bg-secondary">Closed</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body d-flex flex-column gap-2">
                                <a href="{{ route('hotel-owner.food-items.create') }}" class="btn btn-danger">+ Add Food
                                    Item</a>
                                <a href="{{ route('hotel-owner.food-items.index') }}"
                                    class="btn btn-outline-secondary">Manage Menu</a>
                                <a href="#" class="btn btn-outline-secondary">View Orders</a>
                                <a href="{{ \Illuminate\Support\Facades\Route::has('hotel-owner.earnings.index') ? route('hotel-owner.earnings.index') : '#' }}"
                                    class="btn btn-outline-secondary">Withdrawals</a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Internal CSS -->
    <style>
        :root {
            --z-red: #E23744;
            --z-green: #1BAD40;
            --z-orange: #FF8C00;
            --z-blue: #1E88E5;
        }

        .nav-link {
            color: #444;
        }

        .nav-link:hover {
            background: #f8f9fa;
        }

        .card {
            border-radius: 8px;
        }

        .vh-100 {
            height: 100vh;
        }

        @media (max-width: 767.98px) {
            main {
                padding: 1rem;
            }
        }

        .disabled-link {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            (function () {
                const ctx = document.getElementById('dashboardEarningsChart');
                if (!ctx) return;

                const labels = [
                    @php
                        for ($i = 6; $i >= 0; $i--) {
                            echo "'" . \Carbon\Carbon::now()->subDays($i)->format('D') . "',";
                        }
                    @endphp
                ];

                const data = {
                    labels: labels,
                    datasets: [{
                        label: 'Earnings',
                        data: {!! json_encode($earnings_last_7 ?? []) !!},
                        borderColor: '#E23744',
                        backgroundColor: 'rgba(226,55,68,0.08)',
                        tension: 0.3,
                        fill: true
                    }]
                };

                new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: data,
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            })();
        </script>
    @endpush


@endsection