@extends('warehouse.layouts.app')

@section('title', 'Warehouse Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Welcome Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <h1 class="h3 mb-0">
                                <i class="bi bi-house-gear me-2"></i>
                                Welcome back, {{ auth('warehouse')->user()->name }}!
                            </h1>
                            <p class="mb-0 opacity-75">
                                {{ auth('warehouse')->user()->role_display }} • 
                                {{ now()->format('l, F j, Y') }}
                            </p>
                        </div>
                        <div class="col-auto">
                            <div class="text-end">
                                <div class="h4 mb-0">{{ now()->format('g:i A') }}</div>
                                <small class="opacity-75">Current Time</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-primary mb-2">
                        <i class="bi bi-boxes display-4"></i>
                    </div>
                    <h3 class="h4 mb-1">{{ number_format($stats['total_products']) }}</h3>
                    <p class="text-muted mb-0">Total Products</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-success mb-2">
                        <i class="bi bi-check-circle display-4"></i>
                    </div>
                    <h3 class="h4 mb-1">{{ number_format($stats['in_stock']) }}</h3>
                    <p class="text-muted mb-0">In Stock</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-warning mb-2">
                        <i class="bi bi-exclamation-triangle display-4"></i>
                    </div>
                    <h3 class="h4 mb-1">{{ number_format($stats['low_stock']) }}</h3>
                    <p class="text-muted mb-0">Low Stock</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-danger mb-2">
                        <i class="bi bi-x-circle display-4"></i>
                    </div>
                    <h3 class="h4 mb-1">{{ number_format($stats['out_of_stock']) }}</h3>
                    <p class="text-muted mb-0">Out of Stock</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-lightning me-2"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if(auth('warehouse')->user()->hasPermission('add_stock'))
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('warehouse.inventory.add') }}" class="btn btn-outline-primary btn-lg w-100 d-flex flex-column align-items-center py-3">
                                <i class="bi bi-plus-circle fs-1 mb-2"></i>
                                <span>Add Stock</span>
                            </a>
                        </div>
                        @endif
                        
                        @if(auth('warehouse')->user()->hasPermission('adjust_stock'))
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('warehouse.inventory.adjust') }}" class="btn btn-outline-warning btn-lg w-100 d-flex flex-column align-items-center py-3">
                                <i class="bi bi-arrow-repeat fs-1 mb-2"></i>
                                <span>Adjust Stock</span>
                            </a>
                        </div>
                        @endif
                        
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('warehouse.inventory') }}" class="btn btn-outline-info btn-lg w-100 d-flex flex-column align-items-center py-3">
                                <i class="bi bi-search fs-1 mb-2"></i>
                                <span>Search Products</span>
                            </a>
                        </div>
                        
                        @if(auth('warehouse')->user()->hasPermission('view_reports'))
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('warehouse.reports') }}" class="btn btn-outline-success btn-lg w-100 d-flex flex-column align-items-center py-3">
                                <i class="bi bi-graph-up fs-1 mb-2"></i>
                                <span>View Reports</span>
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Activity -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Recent Activity
                    </h5>
                    <a href="{{ route('warehouse.stock-movements') }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    @if($recentMovements->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentMovements as $movement)
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            @php
                                                $iconClass = match($movement->movement_type) {
                                                    'stock_in' => 'bi-arrow-down-circle text-success',
                                                    'stock_out' => 'bi-arrow-up-circle text-danger',
                                                    'adjustment' => 'bi-arrow-repeat text-warning',
                                                    'transfer' => 'bi-arrow-left-right text-info',
                                                    default => 'bi-circle text-secondary'
                                                };
                                            @endphp
                                            <i class="bi {{ $iconClass }} me-2"></i>
                                            <h6 class="mb-0">{{ $movement->warehouseProduct->product->name ?? 'Unknown Product' }}</h6>
                                        </div>
                                        <p class="mb-1 text-muted small">
                                            {{ ucfirst(str_replace('_', ' ', $movement->movement_type)) }} • 
                                            Qty: {{ number_format(abs($movement->quantity_changed)) }} •
                                            By: {{ $movement->performed_by }}
                                        </p>
                                        @if($movement->reason)
                                            <p class="mb-0 small text-secondary">{{ $movement->reason }}</p>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ $movement->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox display-1 mb-3"></i>
                            <p>No recent activity found</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Alerts & Notifications -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-bell me-2"></i>
                        Alerts & Notifications
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Low Stock Alerts -->
                    @if($lowStockProducts->count() > 0)
                        <div class="alert alert-warning border-0 mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Low Stock Alert</strong>
                            </div>
                            <p class="mb-2">{{ $lowStockProducts->count() }} products are running low on stock:</p>
                            <ul class="mb-0 small">
                                @foreach($lowStockProducts->take(3) as $product)
                                    <li>{{ $product->product->name }} ({{ $product->current_stock }} left)</li>
                                @endforeach
                                @if($lowStockProducts->count() > 3)
                                    <li><em>and {{ $lowStockProducts->count() - 3 }} more...</em></li>
                                @endif
                            </ul>
                        </div>
                    @endif

                    <!-- Out of Stock Alerts -->
                    @if($outOfStockProducts->count() > 0)
                        <div class="alert alert-danger border-0 mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-x-circle me-2"></i>
                                <strong>Out of Stock</strong>
                            </div>
                            <p class="mb-2">{{ $outOfStockProducts->count() }} products are out of stock:</p>
                            <ul class="mb-0 small">
                                @foreach($outOfStockProducts->take(3) as $product)
                                    <li>{{ $product->product->name }}</li>
                                @endforeach
                                @if($outOfStockProducts->count() > 3)
                                    <li><em>and {{ $outOfStockProducts->count() - 3 }} more...</em></li>
                                @endif
                            </ul>
                        </div>
                    @endif

                    <!-- System Notifications -->
                    <div class="alert alert-info border-0 mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>System Update</strong>
                        </div>
                        <p class="mb-0 small">New quick delivery optimization features are now available. Check the inventory management section for more details.</p>
                    </div>

                    <!-- Activity Summary -->
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <h6 class="card-title">Your Activity Summary</h6>
                            @php $activity = auth('warehouse')->user()->getActivitySummary(7); @endphp
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="h5 mb-0 text-primary">{{ $activity['total_movements'] }}</div>
                                    <small class="text-muted">Movements</small>
                                </div>
                                <div class="col-6">
                                    <div class="h5 mb-0 text-success">{{ number_format($activity['stock_added']) }}</div>
                                    <small class="text-muted">Items Added</small>
                                </div>
                            </div>
                            <hr>
                            <div class="text-center">
                                <small class="text-muted">Last 7 days</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Auto-refresh dashboard every 5 minutes
    setTimeout(function() {
        location.reload();
    }, 300000);

    // Update current time every second
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        document.querySelector('.h4.mb-0').textContent = timeString;
    }
    
    setInterval(updateTime, 1000);
</script>
@endsection