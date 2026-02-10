@extends('delivery-partner.layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Welcome back, {{ $partner->name }}! 👋</h2>
        <p class="text-muted mb-0">
            {{ $partner->status === 'approved' ? 'Ready to deliver?' : 'Your account is ' . $partner->status }}
        </p>
    </div>
    <div class="d-flex gap-2">
        @if($partner->status === 'approved')
            <button class="btn btn-primary" onclick="goOnline()" id="onlineBtn">
                <i class="fas fa-power-off me-2"></i>
                {{ $partner->is_online ? 'Go Offline' : 'Go Online' }}
            </button>
        @endif
    </div>
</div>

<!-- Status Alert -->
@if($partner->status !== 'approved')
    <div class="alert alert-{{ $partner->status === 'pending' ? 'warning' : 'danger' }} mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-{{ $partner->status === 'pending' ? 'clock' : 'times-circle' }} me-3 fa-2x"></i>
            <div>
                <h6 class="mb-1">Account Status: {{ ucfirst($partner->status) }}</h6>
                <p class="mb-0">
                    @if($partner->status === 'pending')
                        Your account is under review. You'll receive an email once approved.
                    @elseif($partner->status === 'rejected')
                        Your account has been rejected. Please contact support for more information.
                    @else
                        Please contact support to resolve your account status.
                    @endif
                </p>
            </div>
        </div>
    </div>
@endif

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card">
            <div class="position-relative">
                <div class="stat-value">₹{{ number_format($stats['today_earnings'], 0) }}</div>
                <div class="stat-label">Today's Earnings</div>
                <i class="fas fa-rupee-sign stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card success">
            <div class="position-relative">
                <div class="stat-value">{{ $stats['today_deliveries'] }}</div>
                <div class="stat-label">Today's Deliveries</div>
                <i class="fas fa-truck stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card warning">
            <div class="position-relative">
                <div class="stat-value">{{ $stats['pending_orders'] }}</div>
                <div class="stat-label">Pending Orders</div>
                <i class="fas fa-clock stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stat-card info">
            <div class="position-relative">
                <div class="stat-value">{{ number_format($stats['rating'], 1) }}</div>
                <div class="stat-label">Your Rating</div>
                <i class="fas fa-star stat-icon"></i>
            </div>
        </div>
    </div>
</div>

<!-- Active Order Section -->
@if($activeOrder)
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-primary border-2 shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-truck-loading me-2"></i>Active Order</h5>
                <span class="badge bg-light text-primary">Assigned</span>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center mb-3">
                            <h4 class="mb-0 me-3">{{ $activeOrder->normalized_order_number }}</h4>
                            <span class="badge bg-{{ $activeOrder->type == 'food' ? 'danger' : ($activeOrder->type == 'ten_min' ? 'warning' : 'primary') }}">
                                {{ ucfirst($activeOrder->type == 'ten_min' ? '10-Min Grocery' : $activeOrder->type) }}
                            </span>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 text-muted">Customer:</div>
                            <div class="col-sm-8 fw-bold">{{ $activeOrder->customer_name_display }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 text-muted">Delivery Address:</div>
                            <div class="col-sm-8">{{ $activeOrder->delivery_address_display }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4 text-muted">Amount:</div>
                            <div class="col-sm-8 fw-bold text-success">₹{{ number_format($activeOrder->total_amount, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary btn-lg" onclick="viewOrder({{ $activeOrder->id }}, '{{ $activeOrder->type }}')">
                                <i class="fas fa-eye me-2"></i>View Details & Actions
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Monthly Performance -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Monthly Performance</h5>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary active">This Month</button>
                    <button type="button" class="btn btn-outline-primary">Last Month</button>
                </div>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="border-end">
                            <h3 class="text-primary mb-1">{{ $stats['month_deliveries'] }}</h3>
                            <small class="text-muted">Deliveries</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border-end">
                            <h3 class="text-success mb-1">₹{{ number_format($stats['this_month_earnings'], 0) }}</h3>
                            <small class="text-muted">Earnings</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <h3 class="text-warning mb-1">{{ number_format($stats['completion_rate'], 1) }}%</h3>
                        <small class="text-muted">Success Rate</small>
                    </div>
                </div>
                <div class="mt-4">
                    <canvas id="performanceChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Notifications</h5>
            </div>
            <div class="card-body p-0">
                @if(count($notifications) > 0)
                    @foreach($notifications as $notification)
                        <div class="notification-item">
                            <div class="notification-icon {{ $notification['type'] }}">
                                <i class="{{ $notification['icon'] }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $notification['title'] }}</h6>
                                <p class="mb-1 text-muted small">{{ $notification['message'] }}</p>
                                <small class="text-muted">{{ $notification['time'] }}</small>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No notifications</p>
                    </div>
                @endif
            </div>
            @if(count($notifications) > 0)
                <div class="card-footer text-center">
                    <a href="{{ route('delivery-partner.notifications') }}" class="btn btn-link btn-sm">
                        View All Notifications
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>



<!-- Orders Section -->
<div class="row mb-4">
    <!-- Available Orders -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-search me-2"></i>Available Orders</h5>
                <a href="{{ route('delivery-partner.orders.available') }}" class="btn btn-outline-primary btn-sm">
                    View All
                </a>
            </div>
            <div class="card-body" id="available-orders-container">
                @include('delivery-partner.dashboard.partials.available-orders')
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Orders</h5>
                <a href="{{ route('delivery-partner.orders.index') }}" class="btn btn-outline-primary btn-sm">
                    View All
                </a>
            </div>
            <div class="card-body">
                @if($recentOrders && $recentOrders->count() > 0)
                    @foreach($recentOrders->take(3) as $order)
                        <div class="order-card" onclick="viewOrder({{ $order->id }}, '{{ $order->type ?? 'standard' }}')">
                            <div class="order-header">
                                <div>
                                    <div class="order-number">
                                        {{ $order->normalized_order_number ?? $order->order_number ?? 'ORD-' . $order->id }}
                                        <span class="badge bg-{{ ($order->type ?? '') == 'food' ? 'danger' : (($order->type ?? '') == 'ten_min' ? 'warning' : 'primary') }} ms-2">
                                            {{ ($order->type ?? '') == 'food' ? 'Food' : (($order->type ?? '') == 'ten_min' ? '10-Min' : 'Standard') }}
                                        </span>
                                    </div>
                                    <small class="text-muted">{{ $order->customer_name_display ?? $order->user->name ?? 'Customer' }}</small>
                                </div>
                                <div class="text-end">
                                    <span class="order-status status-{{ $order->delivery_status_display ?? $order->delivery_status ?? 'pending' }}">
                                        {{ ucfirst($order->delivery_status_display ?? $order->delivery_status ?? 'Pending') }}
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-rupee-sign me-1"></i>₹{{ number_format($order->total_amount_display ?? $order->total_amount ?? 0, 0) }}
                                </small>
                                <small class="text-muted">{{ isset($order->created_at_display) ? $order->created_at_display->diffForHumans() : $order->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-2">No recent orders</p>
                        <small class="text-muted">Your completed orders will appear here</small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-3">
                <button class="btn btn-outline-primary w-100 py-3" onclick="updateLocation()">
                    <i class="fas fa-map-marker-alt fa-2x mb-2 d-block"></i>
                    Update Location
                </button>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <a href="{{ route('delivery-partner.earnings.index') }}" class="btn btn-outline-success w-100 py-3 text-decoration-none">
                    <i class="fas fa-chart-bar fa-2x mb-2 d-block"></i>
                    View Earnings
                </a>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <a href="{{ route('delivery-partner.profile') }}" class="btn btn-outline-info w-100 py-3 text-decoration-none">
                    <i class="fas fa-user-edit fa-2x mb-2 d-block"></i>
                    Edit Profile
                </a>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <a href="{{ route('delivery-partner.support') }}" class="btn btn-outline-warning w-100 py-3 text-decoration-none">
                    <i class="fas fa-headset fa-2x mb-2 d-block"></i>
                    Get Support
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Go online/offline
    function goOnline() {
        const btn = document.getElementById('onlineBtn');
        const isOnline = btn.textContent.includes('Go Offline');
        const newStatus = isOnline ? 'offline' : 'online';
        
        btn.innerHTML = '<span class="spinner"></span> Updating...';
        btn.disabled = true;
        
        $.ajax({
            url: '{{ route("delivery-partner.toggle-online") }}',
            method: 'POST',
            data: { status: newStatus },
            success: function(response) {
                if (response.success) {
                    btn.innerHTML = `<i class="fas fa-power-off me-2"></i>${isOnline ? 'Go Online' : 'Go Offline'}`;
                    showToast(response.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(response.message || 'Failed to update status', 'error');
                }
            },
            error: function() {
                showToast('Failed to update status', 'error');
            },
            complete: function() {
                btn.disabled = false;
            }
        });
    }

    // Accept order
    function acceptOrder(orderId, type = 'standard') {
        if (!confirm('Are you sure you want to accept this order?')) {
            return;
        }
        
        $.ajax({
            url: `/delivery-partner/orders/${orderId}/accept`,
            method: 'POST',
            data: { type: type, _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    showToast('Order accepted successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(response.message || 'Failed to accept order', 'error');
                }
            },
            error: function() {
                showToast('Failed to accept order', 'error');
            }
        });
    }

    // View order details
    function viewOrder(orderId, type = 'standard') {
        window.location.href = `/delivery-partner/orders/${orderId}?type=${type}`;
    }

    // Update location
    function updateLocation() {
        if (!navigator.geolocation) {
            showToast('Geolocation is not supported by this browser', 'error');
            return;
        }

        showToast('Getting your location...', 'info');

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                $.ajax({
                    url: '{{ route("delivery-partner.update-location") }}',
                    method: 'POST',
                    data: {
                        latitude: lat,
                        longitude: lng
                    },
                    success: function(response) {
                        if (response.success) {
                            showToast('Location updated successfully!', 'success');
                        } else {
                            showToast(response.message || 'Failed to update location', 'error');
                        }
                    },
                    error: function() {
                        showToast('Failed to update location', 'error');
                    }
                });
            },
            function(error) {
                let message = 'Failed to get location';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        message = 'Location access denied by user';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = 'Location information is unavailable';
                        break;
                    case error.TIMEOUT:
                        message = 'Location request timed out';
                        break;
                }
                showToast(message, 'error');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            }
        );
    }

    // Performance Chart
    const ctx = document.getElementById('performanceChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Deliveries',
                    data: [12, 19, 15, 25], // This would come from backend
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#e5e7eb'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Auto-update location every 5 minutes if online
    @if($partner->is_online)
        setInterval(function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        $.ajax({
                            url: '{{ route("delivery-partner.update-location") }}',
                            method: 'POST',
                            data: {
                                latitude: position.coords.latitude,
                                longitude: position.coords.longitude
                            }
                        });
                    },
                    function() {}, // Ignore errors in auto-update
                    { enableHighAccuracy: false, timeout: 5000, maximumAge: 300000 }
                );
            }
        }, 300000); // 5 minutes
    @endif

    // Page visibility handling
    // Page visibility handling
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            // Refresh data when user returns to page
            location.reload();
        }
    });

    // Real-time polling for new orders (every 10 seconds)
    @if($partner->is_online)
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                $.ajax({
                    url: '{{ route("delivery-partner.orders.refresh") }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.html) {
                            $('#available-orders-container').html(response.html);
                        }
                    },
                    error: function(xhr) {
                        // console.log('Refresh failed:', xhr.status);
                    }
                });
            }
        }, 10000); // 10 seconds
    @endif
</script>
@endpush