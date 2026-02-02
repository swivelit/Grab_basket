@extends('admin.layouts.main')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">🏭 Warehouse Dashboard</h1>
            <p class="text-muted mb-0">10-Minute Delivery Inventory Management</p>
        </div>
        <div>
            <a href="{{ url('/admin/warehouse/inventory') }}" class="btn btn-primary">
                <i class="fas fa-boxes"></i> View Full Inventory
            </a>
        </div>
    </div>

    <!-- Alert Cards Row -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Low Stock Alert</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $lowStockCount }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Expiring Soon</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $expiringCount }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Expired Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $expiredCount }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ban fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Needs Reorder</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $reorderNeeded }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Stats Row -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Products</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalProducts }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cube fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Stock Units</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalStock) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Available Stock</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($availableStock) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Reserved Stock</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($reservedStock) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-lock fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Delivery Stats -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">⚡ Quick Delivery Ready</h6>
                    <a href="{{ url('/admin/warehouse/quick-delivery') }}" class="btn btn-sm btn-primary">Optimize</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-primary">{{ $quickDeliveryProducts }}</h4>
                                <p class="text-muted mb-0">Products Available</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-success">{{ number_format($quickDeliveryStock) }}</h4>
                                <p class="text-muted mb-0">Units Ready</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">📊 Movement Types (30 Days)</h6>
                </div>
                <div class="card-body">
                    @if($movementStats->isNotEmpty())
                        @foreach($movementStats->take(4) as $type => $stats)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-sm">{{ ucfirst(str_replace('_', ' ', $type)) }}</span>
                                <span class="badge badge-primary">{{ $stats->count }}</span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center">No movements recorded yet</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Movements & Alerts -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">📝 Recent Stock Movements</h6>
                    <a href="{{ url('/admin/warehouse/stock-movements') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    @if($recentMovements->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Type</th>
                                        <th>Qty</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentMovements as $movement)
                                        <tr>
                                            <td>
                                                <div class="text-sm font-weight-bold">{{ $movement->product->name ?? 'N/A' }}</div>
                                                <div class="text-xs text-muted">{{ $movement->reason ?? '' }}</div>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $movement->movement_type_color }}">
                                                    {{ $movement->movement_type_display }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="{{ $movement->quantity_changed >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $movement->quantity_change_display }}
                                                </span>
                                            </td>
                                            <td class="text-xs text-muted">
                                                {{ $movement->created_at->format('M j, g:i A') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center">No recent movements</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <!-- Low Stock Alerts -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">⚠️ Low Stock Alerts</h6>
                </div>
                <div class="card-body">
                    @if($lowStockProducts->isNotEmpty())
                        @foreach($lowStockProducts as $product)
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                <div>
                                    <div class="text-sm font-weight-bold">{{ $product->product->name ?? 'N/A' }}</div>
                                    <div class="text-xs text-muted">{{ $product->location_full }}</div>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-danger">{{ $product->stock_quantity }} left</span>
                                    <div class="text-xs text-muted">Min: {{ $product->minimum_stock_level }}</div>
                                </div>
                            </div>
                        @endforeach
                        <a href="{{ url('/admin/warehouse/inventory?status=low_stock') }}" class="btn btn-sm btn-outline-danger btn-block mt-2">
                            View All Low Stock Items
                        </a>
                    @else
                        <p class="text-muted text-center">All products are well-stocked! 🎉</p>
                    @endif
                </div>
            </div>

            <!-- Expiring Products -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">⏰ Expiring Soon (7 Days)</h6>
                </div>
                <div class="card-body">
                    @if($expiringProducts->isNotEmpty())
                        @foreach($expiringProducts as $product)
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                <div>
                                    <div class="text-sm font-weight-bold">{{ $product->product->name ?? 'N/A' }}</div>
                                    <div class="text-xs text-muted">{{ $product->location_full }}</div>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-warning">{{ $product->days_until_expiry }} days</span>
                                    <div class="text-xs text-muted">{{ \Carbon\Carbon::parse($product->expiry_date)->format('M j') }}</div>
                                </div>
                            </div>
                        @endforeach
                        <a href="{{ url('/admin/warehouse/inventory?status=expiring_soon') }}" class="btn btn-sm btn-outline-warning btn-block mt-2">
                            View All Expiring Items
                        </a>
                    @else
                        <p class="text-muted text-center">No products expiring soon! ✅</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">🚀 Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="{{ url('/admin/warehouse/inventory') }}" class="btn btn-outline-primary btn-block">
                                <i class="fas fa-boxes"></i><br>
                                <small>View Inventory</small>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ url('/admin/warehouse/stock-movements') }}" class="btn btn-outline-info btn-block">
                                <i class="fas fa-exchange-alt"></i><br>
                                <small>Stock Movements</small>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ url('/admin/warehouse/quick-delivery') }}" class="btn btn-outline-success btn-block">
                                <i class="fas fa-bolt"></i><br>
                                <small>Quick Delivery</small>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ url('/admin/warehouse/export-inventory') }}" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-download"></i><br>
                                <small>Export Data</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}
</style>
@endpush