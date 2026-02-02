@extends('layouts.minimal')

@section('title', 'Orders Management')

@php
    $hotelOwner = \Illuminate\Support\Facades\Auth::guard('hotel_owner')->user();
    $pendingCount = \App\Models\FoodOrder::where('hotel_owner_id', $hotelOwner->id)->where('status', 'pending')->count();
@endphp

@section('content')
<div class="container-fluid">
    <div class="row g-0">
        <!-- Sidebar (Reused from Dashboard) -->
               <aside class="col-12 col-md-3 col-lg-2 bg-white border-end vh-100 position-fixed d-none d-md-block" style="padding-top: 1.75rem;">
            <div class="text-center mb-4 px-3">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center" 
                     style="width:72px;height:72px;background:#E23744;color:#fff;font-size:28px;">
                    <i class="fas fa-utensils"></i>
                </div>
                <h5 class="mt-3 mb-0">{{ $hotelOwner->restaurant_name ?? 'My Restaurant' }}</h5>
                <small class="text-muted">{{ $hotelOwner->name }}</small>
            </div>

            <nav class="nav flex-column px-2">
                <a class="nav-link py-2 mb-1 rounded {{ request()->routeIs('hotel-owner.dashboard') ? 'bg-light' : '' }}" href="{{ route('hotel-owner.dashboard') }}">
                    <i class="fas fa-home me-2 text-secondary"></i> Dashboard
                </a>
                <a class="nav-link py-2 mb-1 rounded" href="{{ route('hotel-owner.food-items.index') }}">
                    <i class="fas fa-utensils me-2 text-secondary"></i> Menu
                </a>
              <a class="nav-link py-2 mb-1 rounded" href="{{ route('hotel-owner.orders.index') }}">
                  <i class="fas fa-concierge-bell me-2 text-secondary"></i> Orders
                  <span class="badge bg-warning text-dark ms-2">{{ $stats['pending_orders'] ?? 0 }}</span>
              </a>

                <a class="nav-link py-2 mb-1 rounded" href="{{ \Illuminate\Support\Facades\Route::has('hotel-owner.earnings.index') ? route('hotel-owner.earnings.index') : '#' }}">
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
        <div class="d-md-none bg-white border-bottom py-2 px-3 sticky-top">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong style="color:#E23744;">Orders</strong>
                </div>
                <div>
                    <a href="{{ route('hotel-owner.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <main class="col-12 col-md-9 offset-md-3 col-lg-10 offset-lg-2 px-3 py-4 bg-light min-vh-100">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h2 class="mb-1" style="color: #333; font-weight: 700;">Orders Management</h2>
                    <p class="text-muted mb-0">Track and manage your restaurant's incoming orders.</p>
                </div>
                
                <!-- Status Filter -->
                <div class="d-flex align-items-center bg-white p-2 rounded shadow-sm border">
                    <label for="status-filter" class="me-2 text-muted fw-bold small text-nowrap"><i class="fas fa-filter me-1"></i> Filter by:</label>
                    <select id="status-filter" class="form-select form-select-sm border-0 bg-transparent fw-bold text-dark" style="cursor: pointer; min-width: 120px;">
                        <option value="">All Orders</option>
                        @foreach(['pending', 'accepted', 'preparing', 'ready',  'cancelled'] as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert" style="background-color: #d1e7dd; color: #0f5132;">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($orders->count() > 0)
                <div class="row g-4">
                    @foreach($orders as $order)
                    <div class="col-12 col-xl-6">
                        <div class="card h-100 border-0 shadow-sm hover-shadow transition-all" style="border-radius: 12px; overflow: hidden;">
                            <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold text-dark h5 mb-0">#{{ $order->id }}</span>
                                    <span class="text-muted small ms-2">
                                        <i class="far fa-clock me-1"></i>{{ $order->created_at->format('M d, h:i A') }}
                                    </span>
                                </div>
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'accepted' => 'info',
                                        'preparing' => 'primary',
                                        'ready' => 'success',
                                        'delivered' => 'secondary',
                                        'cancelled' => 'danger'
                                    ];
                                    $color = $statusColors[$order->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }} bg-opacity-10 text-{{ $color }} px-3 py-2 rounded-pill border border-{{ $color }} border-opacity-25">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </div>
                            
                            <div class="card-body px-4 pt-4 pb-0">
                                <div class="row mb-3">
                                    <div class="col-7">
                                        <h6 class="text-uppercase text-muted small fw-bold mb-2">Customer</h6>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded-circle p-2 me-2 text-secondary">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">{{ $order->customer_name }}</div>
                                                <div class="small text-muted text-truncate" style="max-width: 150px;">{{ Str::limit($order->delivery_address ?? 'Pickup', 25) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-5 text-end">
                                        <h6 class="text-uppercase text-muted small fw-bold mb-2">Amount</h6>
                                        <div class="h5 fw-bold text-dark mb-0">₹{{ number_format($order->total_amount, 2) }}</div>
                                        <div class="small text-muted">{{ $order->items->count() }} Items</div>
                                    </div>
                                </div>

                                <div class="bg-light rounded p-3 mb-3">
                                    <h6 class="text-muted small fw-bold mb-2">Order Summary</h6>
                                    <ul class="list-unstyled mb-0 small">
                                        @foreach($order->items->take(3) as $item)
                                            <li class="d-flex justify-content-between mb-1">
                                                <span><span class="fw-bold">{{ $item->quantity }}x</span> {{ $item->food_name ?? $item->foodItem->name }}</span>
                                                <span class="text-muted">₹{{ number_format($item->price * $item->quantity, 2) }}</span>
                                            </li>
                                        @endforeach
                                        @if($order->items->count() > 3)
                                            <li class="text-center text-muted fst-italic mt-1">+{{ $order->items->count() - 3 }} more items...</li>
                                        @endif
                                    </ul>
                                </div>
                            </div>

                            <div class="card-footer bg-white border-top-0 px-4 pb-4 pt-0 d-flex justify-content-between align-items-center gap-2">
                                <a href="{{ route('hotel-owner.orders.show', $order) }}" class="btn btn-light text-primary fw-medium flex-grow-1 border-0" style="background: #eef2ff;">
                                    View Details
                                </a>
                                
                                @if(in_array($order->status, ['pending', 'accepted', 'preparing', 'ready']))
                                    <div class="flex-grow-1" style="max-width: 200px;">
                                        <form action="{{ route('hotel-owner.orders.update-status', $order) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <select name="status" class="form-select form-select-sm border-primary text-primary fw-bold" onchange="this.form.submit()" style="background-color: #f8fbff;">
                                                <option value="" disabled selected>Update Status</option>
                                                <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="accepted" {{ $order->status == 'accepted' ? 'selected' : '' }}>Accepted</option>
                                                <option value="preparing" {{ $order->status == 'preparing' ? 'selected' : '' }}>Preparing</option>
                                                <option value="ready" {{ $order->status == 'ready' ? 'selected' : '' }}>Ready</option>
                                                <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancel</option>
                                            </select>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <div class="mt-5 d-flex justify-content-center">
                    {{ $orders->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>

            @else
                <div class="text-center py-5">
                    <div class="mb-4">
                        <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 120px; height: 120px;">
                            <i class="fas fa-clipboard-list fa-3x text-muted opacity-50"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold text-dark">No orders found</h4>
                    <p class="text-muted">There are no orders matching your current filter.</p>
                    <a href="{{ route('hotel-owner.orders.index') }}" class="btn btn-outline-primary px-4 rounded-pill">Clear Filters</a>
                </div>
            @endif
        </main>
    </div>
</div>

<style>
    /* Custom Scrollbar for Sidebar */
    aside::-webkit-scrollbar { width: 4px; }
    aside::-webkit-scrollbar-thumb { background: #eee; border-radius: 4px; }
    
    .nav-link { color: #555; font-weight: 500; transition: all 0.2s; }
    .nav-link:hover { background: #f8f9fa; color: #E23744; }
    
    .hover-shadow { transition: transform 0.2s, box-shadow 0.2s; }
    .hover-shadow:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.08)!important; }
    
    .transition-all { transition: all 0.3s ease; }
    
    .badge.bg-warning { background-color: #ffc107 !important; color: #000 !important; }
</style>

<script>
    document.getElementById('status-filter').addEventListener('change', function() {
        const status = this.value;
        const url = new URL(window.location);
        if (status) {
            url.searchParams.set('status', status);
            url.searchParams.delete('page'); // Reset to page 1 on filter change
        } else {
            url.searchParams.delete('status');
        }
        window.location.href = url.toString();
    });
</script>
@endsection