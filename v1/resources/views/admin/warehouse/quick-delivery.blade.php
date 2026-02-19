@extends('admin.layouts.main')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">⚡ Quick Delivery Optimization</h1>
            <p class="text-muted mb-0">Manage 10-minute delivery inventory and optimize stock placement</p>
        </div>
        <div>
            <a href="{{ url('/admin/warehouse/dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Quick Delivery Products
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_products'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bolt fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Stock Ready
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total_stock']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-primary"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Low Stock Items
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['low_stock_products'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                High Demand (7d)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ count($stats['high_demand_products']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- High Demand Products -->
    @if(count($stats['high_demand_products']) > 0)
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">🔥 High Demand Products (Last 7 Days)</h6>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($stats['high_demand_products'] as $demand)
                    <div class="col-md-4 mb-3">
                        <div class="bg-light p-3 rounded">
                            <h6 class="mb-1">Product ID: {{ $demand->product_id }}</h6>
                            <p class="text-muted mb-1">Demand: {{ $demand->demand }} units</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-success">Ensure adequate stock for 10-min delivery</small>
                                <a href="{{ url('/admin/warehouse/product/' . $demand->product_id) }}" class="btn btn-sm btn-outline-primary">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Quick Delivery Products -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">⚡ Products Available for Quick Delivery</h6>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                        id="quickDeliveryActions" data-bs-toggle="dropdown">
                    Bulk Actions
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="bulkToggleQuickDelivery(false)">
                        <i class="fas fa-ban text-danger"></i> Disable Selected</a></li>
                    <li><a class="dropdown-item" href="#" onclick="optimizeStockPlacement()">
                        <i class="fas fa-magic text-info"></i> Optimize Placement</a></li>
                </ul>
            </div>
        </div>
        <div class="card-body">
            @if($quickDeliveryProducts->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="30">
                                    <input type="checkbox" id="select-all-quick" class="form-check-input">
                                </th>
                                <th>Product</th>
                                <th>Stock</th>
                                <th>Location</th>
                                <th>Condition</th>
                                <th>Fragility</th>
                                <th>Cold Storage</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($quickDeliveryProducts as $wp)
                            <tr>
                                <td>
                                    <input type="checkbox" name="quick_product_ids[]" value="{{ $wp->id }}" 
                                           class="form-check-input quick-product-checkbox">
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-bold">{{ $wp->product->name ?? 'N/A' }}</div>
                                        <small class="text-muted">SKU: {{ $wp->product->sku ?? 'N/A' }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="fw-bold {{ $wp->stock_quantity <= $wp->minimum_stock_level ? 'text-warning' : 'text-success' }}">
                                            {{ $wp->available_quantity }}
                                        </span> available
                                    </div>
                                    <small class="text-muted">
                                        Total: {{ $wp->stock_quantity }} | Reserved: {{ $wp->reserved_quantity }}
                                    </small>
                                </td>
                                <td>
                                    <div>{{ $wp->location_full }}</div>
                                    @if($wp->location_code)
                                        <span class="badge badge-secondary">{{ $wp->location_code }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ 
                                        $wp->condition === 'excellent' ? 'success' : 
                                        ($wp->condition === 'good' ? 'primary' : 
                                        ($wp->condition === 'fair' ? 'warning' : 'danger'))
                                    }}">
                                        {{ ucfirst($wp->condition) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ 
                                        $wp->fragility === 'low' ? 'success' : 
                                        ($wp->fragility === 'medium' ? 'warning' : 'danger')
                                    }}">
                                        {{ ucfirst($wp->fragility) }}
                                    </span>
                                </td>
                                <td>
                                    @if($wp->requires_cold_storage)
                                        <i class="fas fa-snowflake text-info" title="Requires Cold Storage"></i>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ url('/admin/warehouse/product/' . $wp->id) }}" 
                                           class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger toggle-quick-delivery-btn" 
                                                data-product-id="{{ $wp->id }}"
                                                title="Disable Quick Delivery">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing {{ $quickDeliveryProducts->firstItem() }} to {{ $quickDeliveryProducts->lastItem() }} 
                        of {{ $quickDeliveryProducts->total() }} products
                    </div>
                    {{ $quickDeliveryProducts->links() }}
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-bolt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No products available for quick delivery</h5>
                    <p class="text-muted">Enable products below to get started.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Potential Quick Delivery Products -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-warning">💡 Potential Quick Delivery Products</h6>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-warning dropdown-toggle" type="button" 
                        id="potentialActions" data-bs-toggle="dropdown">
                    Bulk Actions
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="bulkToggleQuickDelivery(true)">
                        <i class="fas fa-bolt text-success"></i> Enable Selected</a></li>
                </ul>
            </div>
        </div>
        <div class="card-body">
            @if($potentialProducts->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="30">
                                    <input type="checkbox" id="select-all-potential" class="form-check-input">
                                </th>
                                <th>Product</th>
                                <th>Stock</th>
                                <th>Location</th>
                                <th>Condition</th>
                                <th>Why Not Enabled?</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($potentialProducts as $wp)
                            <tr>
                                <td>
                                    <input type="checkbox" name="potential_product_ids[]" value="{{ $wp->id }}" 
                                           class="form-check-input potential-product-checkbox">
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-bold">{{ $wp->product->name ?? 'N/A' }}</div>
                                        <small class="text-muted">SKU: {{ $wp->product->sku ?? 'N/A' }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-success">{{ $wp->available_quantity }}</span> available
                                    <br><small class="text-muted">Total: {{ $wp->stock_quantity }}</small>
                                </td>
                                <td>{{ $wp->location_full ?: 'Not set' }}</td>
                                <td>
                                    <span class="badge bg-{{ 
                                        $wp->condition === 'excellent' ? 'success' : 
                                        ($wp->condition === 'good' ? 'primary' : 
                                        ($wp->condition === 'fair' ? 'warning' : 'danger'))
                                    }}">
                                        {{ ucfirst($wp->condition) }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $reasons = [];
                                        if ($wp->condition === 'damaged') $reasons[] = 'Damaged condition';
                                        if ($wp->is_expired) $reasons[] = 'Expired';
                                        if (!$wp->location_code) $reasons[] = 'No location set';
                                        if (empty($reasons)) $reasons[] = 'Manually disabled';
                                    @endphp
                                    <small class="text-muted">{{ implode(', ', $reasons) }}</small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ url('/admin/warehouse/product/' . $wp->id) }}" 
                                           class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($wp->condition !== 'damaged' && !$wp->is_expired)
                                        <button class="btn btn-sm btn-outline-success toggle-quick-delivery-btn" 
                                                data-product-id="{{ $wp->id }}"
                                                title="Enable Quick Delivery">
                                            <i class="fas fa-bolt"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-lightbulb"></i>
                    <strong>Optimization Tip:</strong> Products with good stock levels and proper location codes are ideal candidates for quick delivery. 
                    Consider enabling products that are frequently ordered and have short expiry dates.
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5 class="text-success">All suitable products are enabled!</h5>
                    <p class="text-muted">All products that can be used for quick delivery are already enabled.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Select all checkboxes
    $('#select-all-quick').change(function() {
        $('.quick-product-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    $('#select-all-potential').change(function() {
        $('.potential-product-checkbox').prop('checked', $(this).prop('checked'));
    });

    // Toggle quick delivery for individual products
    $('.toggle-quick-delivery-btn').click(function() {
        const productId = $(this).data('product-id');
        
        $.post(`/admin/warehouse/product/${productId}/toggle-quick-delivery`, {
            _token: '{{ csrf_token() }}'
        })
        .done(function(response) {
            if (response.success) {
                toastr.success(response.message);
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to update');
            }
        })
        .fail(function() {
            toastr.error('Failed to update quick delivery status');
        });
    });
});

// Bulk toggle quick delivery
function bulkToggleQuickDelivery(enable) {
    const checkboxClass = enable ? '.potential-product-checkbox' : '.quick-product-checkbox';
    const selectedIds = $(checkboxClass + ':checked').map(function() {
        return $(this).val();
    }).get();

    if (selectedIds.length === 0) {
        toastr.warning('Please select products first');
        return;
    }

    const action = enable ? 'enable' : 'disable';
    if (!confirm(`Are you sure you want to ${action} quick delivery for ${selectedIds.length} products?`)) {
        return;
    }

    const operation = enable ? 'enable_quick_delivery' : 'disable_quick_delivery';

    $.post('/admin/warehouse/bulk-operation', {
        operation: operation,
        product_ids: selectedIds,
        _token: '{{ csrf_token() }}'
    })
    .done(function(response) {
        if (response.success) {
            toastr.success(response.message);
            location.reload();
        } else {
            toastr.error('Bulk operation failed');
        }
    })
    .fail(function() {
        toastr.error('Bulk operation failed');
    });
}

// Optimize stock placement (placeholder for future enhancement)
function optimizeStockPlacement() {
    const selectedIds = $('.quick-product-checkbox:checked').map(function() {
        return $(this).val();
    }).get();

    if (selectedIds.length === 0) {
        toastr.warning('Please select products first');
        return;
    }

    toastr.info('Stock placement optimization is coming soon! This will automatically organize products by demand and fragility.');
}
</script>
@endpush

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
</style>
@endpush