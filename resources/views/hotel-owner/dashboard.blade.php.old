@extends('layouts.app')

@section('title', 'Hotel Owner Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3">
                <div class="text-center mb-4">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                         style="width: 60px; height: 60px;">
                        <i class="fas fa-utensils fa-2x"></i>
                    </div>
                    <h6 class="mt-2 mb-0">{{ $hotelOwner->restaurant_name ?? 'Restaurant' }}</h6>
                    <small class="text-muted">{{ $hotelOwner->name }}</small>
                </div>

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('hotel-owner.dashboard') }}">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('hotel-owner.food-items.index') }}">
                            <i class="fas fa-hamburger me-2"></i>
                            Food Items
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-shopping-bag me-2"></i>
                            Orders
                            <span class="badge bg-warning ms-2">{{ $stats['pending_orders'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('hotel-owner.profile') }}">
                            <i class="fas fa-user me-2"></i>
                            Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-chart-bar me-2"></i>
                            Analytics
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-calendar-alt me-1"></i>
                            This week
                        </button>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        Add Food Item
                    </button>
                </div>
            </div>

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

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Food Items</h6>
                                    <h3 class="mb-0">{{ $stats['total_food_items'] }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-hamburger fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Active Items</h6>
                                    <h3 class="mb-0">{{ $stats['active_food_items'] }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Orders</h6>
                                    <h3 class="mb-0">{{ $stats['total_orders'] }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-shopping-bag fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Revenue</h6>
                                    <h3 class="mb-0">₹{{ number_format($stats['total_revenue'], 2) }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-rupee-sign fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Restaurant Status -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-store me-2"></i>Restaurant Status
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <strong>Status:</strong>
                                    @if($hotelOwner->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </div>
                                <div class="col-6">
                                    <strong>Currently:</strong>
                                    @if($hotelOwner->isOpen())
                                        <span class="badge bg-success">Open</span>
                                    @else
                                        <span class="badge bg-secondary">Closed</span>
                                    @endif
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Opening Time:</small><br>
                                    <strong>{{ $hotelOwner->opening_time ?? 'Not set' }}</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Closing Time:</small><br>
                                    <strong>{{ $hotelOwner->closing_time ?? 'Not set' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2"></i>Quick Stats
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <h4 class="text-primary">{{ $stats['today_orders'] }}</h4>
                                    <small class="text-muted">Today's Orders</small>
                                </div>
                                <div class="col-4">
                                    <h4 class="text-success">₹{{ number_format($stats['this_month_revenue'], 2) }}</h4>
                                    <small class="text-muted">This Month</small>
                                </div>
                                <div class="col-4">
                                    <h4 class="text-warning">{{ $stats['pending_orders'] }}</h4>
                                    <small class="text-muted">Pending</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            @if($recentOrders->count() > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock me-2"></i>Recent Orders
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentOrders as $order)
                                <tr>
                                    <td>#{{ $order->id }}</td>
                                    <td>{{ $order->user->name ?? 'N/A' }}</td>
                                    <td>{{ $order->orderItems->count() }} items</td>
                                    <td>₹{{ number_format($order->total_amount, 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $order->status == 'completed' ? 'success' : ($order->status == 'pending' ? 'warning' : 'info') }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">View</button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @else
            <div class="card mb-4">
                <div class="card-body text-center py-5">
                    <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Orders Yet</h5>
                    <p class="text-muted">Orders will appear here once customers start placing them.</p>
                </div>
            </div>
            @endif

            <!-- Popular Food Items -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-star me-2"></i>Your Food Items
                    </h5>
                </div>
                <div class="card-body">
                    @if($popularItems->count() > 0)
                        <div class="row">
                            @foreach($popularItems as $item)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100">
                                    @if($item->image)
                                        <img src="{{ asset('storage/' . $item->image) }}" class="card-img-top" style="height: 200px; object-fit: cover;" alt="{{ $item->name }}">
                                    @else
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                            <i class="fas fa-image fa-3x text-muted"></i>
                                        </div>
                                    @endif
                                    <div class="card-body">
                                        <h6 class="card-title">{{ $item->name }}</h6>
                                        <p class="card-text">
                                            <strong>₹{{ number_format($item->price, 2) }}</strong>
                                            @if($item->discount_price)
                                                <small class="text-muted"><del>₹{{ number_format($item->original_price, 2) }}</del></small>
                                            @endif
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-{{ $item->is_available ? 'success' : 'secondary' }}">
                                                {{ $item->is_available ? 'Available' : 'Unavailable' }}
                                            </span>
                                            <a href="{{ route('hotel-owner.food-items.edit', $item) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="{{ route('hotel-owner.food-items.index') }}" class="btn btn-primary">
                                <i class="fas fa-list me-2"></i>View All Food Items
                            </a>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-hamburger fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Food Items Added</h5>
                            <p class="text-muted">Start adding food items to your menu to get started.</p>
                            <a href="{{ route('hotel-owner.food-items.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add Your First Food Item
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}

@media (max-width: 767.98px) {
    .sidebar {
        top: 5rem;
    }
}

.sidebar .nav-link {
    font-weight: 500;
    color: #333;
}

.sidebar .nav-link.active {
    color: #007bff;
}

.sidebar .nav-link:hover {
    color: #007bff;
}

.opacity-75 {
    opacity: 0.75;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}
</style>
@endsection