@extends('admin.layouts.main')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">📦 Inventory Management</h1>
            <p class="text-muted mb-0">Manage warehouse products and stock levels</p>
        </div>
        <div>
            <a href="{{ url('/admin/warehouse/dashboard') }}" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
            <a href="{{ url('/admin/warehouse/export-inventory') }}" class="btn btn-success">
                <i class="fas fa-download"></i> Export
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ url('/admin/warehouse/inventory') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search Products</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Product name, SKU, location...">
                </div>
                
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="in_stock" {{ request('status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                        <option value="low_stock" {{ request('status') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                        <option value="out_of_stock" {{ request('status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                        <option value="expiring_soon" {{ request('status') === 'expiring_soon' ? 'selected' : '' }}>Expiring Soon</option>
                        <option value="needs_reorder" {{ request('status') === 'needs_reorder' ? 'selected' : '' }}>Needs Reorder</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="quick_delivery" class="form-label">Quick Delivery</label>
                    <select class="form-select" id="quick_delivery" name="quick_delivery">
                        <option value="">All Products</option>
                        <option value="1" {{ request('quick_delivery') === '1' ? 'selected' : '' }}>Enabled</option>
                        <option value="0" {{ request('quick_delivery') === '0' ? 'selected' : '' }}>Disabled</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="location" class="form-label">Aisle</label>
                    <input type="text" class="form-control" id="location" name="location" 
                           value="{{ request('location') }}" placeholder="A, B, C...">
                </div>

                <div class="col-md-2">
                    <label for="sort_by" class="form-label">Sort By</label>
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="updated_at" {{ request('sort_by') === 'updated_at' ? 'selected' : '' }}>Last Updated</option>
                        <option value="product_name" {{ request('sort_by') === 'product_name' ? 'selected' : '' }}>Product Name</option>
                        <option value="stock_quantity" {{ request('sort_by') === 'stock_quantity' ? 'selected' : '' }}>Stock Quantity</option>
                        <option value="expiry_date" {{ request('sort_by') === 'expiry_date' ? 'selected' : '' }}>Expiry Date</option>
                    </select>
                </div>

                <div class="col-md-1">
                    <label for="sort_order" class="form-label">Order</label>
                    <select class="form-select" id="sort_order" name="sort_order">
                        <option value="desc" {{ request('sort_order') === 'desc' ? 'selected' : '' }}>DESC</option>
                        <option value="asc" {{ request('sort_order') === 'asc' ? 'selected' : '' }}>ASC</option>
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="{{ url('/admin/warehouse/inventory') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Products ({{ $products->total() }} items)
            </h6>
            
            @if($products->count() > 0)
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                        id="bulkActions" data-bs-toggle="dropdown">
                    Bulk Actions
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="bulkOperation('enable_quick_delivery')">
                        <i class="fas fa-bolt text-success"></i> Enable Quick Delivery</a></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkOperation('disable_quick_delivery')">
                        <i class="fas fa-ban text-danger"></i> Disable Quick Delivery</a></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkOperation('mark_reorder')">
                        <i class="fas fa-shopping-cart text-info"></i> Mark for Reorder</a></li>
                </ul>
            </div>
            @endif
        </div>

        <div class="card-body">
            @if($products->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="30">
                                    <input type="checkbox" id="select-all" class="form-check-input">
                                </th>
                                <th>Product</th>
                                <th>Stock</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Quick Delivery</th>
                                <th>Expiry</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $wp)
                            <tr>
                                <td>
                                    <input type="checkbox" name="product_ids[]" value="{{ $wp->id }}" 
                                           class="form-check-input product-checkbox">
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-bold">{{ $wp->product->name ?? 'N/A' }}</div>
                                        <small class="text-muted">
                                            SKU: {{ $wp->product->sku ?? 'N/A' }} | 
                                            ID: {{ $wp->product_id }}
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="fw-bold {{ $wp->stock_quantity <= $wp->minimum_stock_level ? 'text-danger' : 'text-success' }}">
                                            {{ $wp->stock_quantity }}
                                        </span> 
                                        / {{ $wp->maximum_stock_level }}
                                    </div>
                                    <small class="text-muted">
                                        Available: {{ $wp->available_quantity }} | 
                                        Reserved: {{ $wp->reserved_quantity }}
                                    </small>
                                </td>
                                <td>
                                    <div>{{ $wp->location_full }}</div>
                                    @if($wp->location_code)
                                        <small class="text-muted">Code: {{ $wp->location_code }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $wp->stock_status_color }}">
                                        {{ ucfirst(str_replace('_', ' ', $wp->stock_status)) }}
                                    </span>
                                    @if($wp->condition !== 'excellent')
                                        <br><small class="text-muted">{{ ucfirst($wp->condition) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input quick-delivery-toggle" 
                                               type="checkbox" 
                                               data-product-id="{{ $wp->id }}"
                                               {{ $wp->is_available_for_quick_delivery ? 'checked' : '' }}>
                                        <label class="form-check-label">
                                            {{ $wp->is_available_for_quick_delivery ? 'Enabled' : 'Disabled' }}
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    @if($wp->expiry_date)
                                        <div class="{{ $wp->days_until_expiry <= 7 ? 'text-warning' : '' }}">
                                            {{ \Carbon\Carbon::parse($wp->expiry_date)->format('M j, Y') }}
                                        </div>
                                        <small class="text-muted">
                                            {{ $wp->days_until_expiry > 0 ? $wp->days_until_expiry . ' days left' : 'Expired' }}
                                        </small>
                                    @else
                                        <span class="text-muted">No expiry</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ url('/admin/warehouse/product/' . $wp->id) }}" 
                                           class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-success add-stock-btn" 
                                                data-product-id="{{ $wp->id }}"
                                                data-product-name="{{ $wp->product->name ?? 'N/A' }}"
                                                title="Add Stock">
                                            <i class="fas fa-plus"></i>
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
                        Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} 
                        of {{ $products->total() }} results
                    </div>
                    {{ $products->links() }}
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No products found</h5>
                    <p class="text-muted">Try adjusting your search filters.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Add Stock Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addStockForm">
                <div class="modal-header">
                    <h5 class="modal-title">Add Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="add-stock-product-id" name="warehouse_product_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Product</label>
                        <div id="add-stock-product-name" class="text-muted"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add-quantity" class="form-label">Quantity *</label>
                                <input type="number" class="form-control" id="add-quantity" 
                                       name="quantity" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add-unit-cost" class="form-label">Unit Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="add-unit-cost" 
                                           name="unit_cost" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add-supplier" class="form-label">Supplier</label>
                                <input type="text" class="form-control" id="add-supplier" name="supplier_name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="add-reference" class="form-label">Reference No.</label>
                                <input type="text" class="form-control" id="add-reference" name="reference_number">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="add-reason" class="form-label">Reason *</label>
                        <select class="form-select" id="add-reason" name="reason" required>
                            <option value="">Select reason...</option>
                            <option value="New stock received">New stock received</option>
                            <option value="Supplier delivery">Supplier delivery</option>
                            <option value="Stock return">Stock return</option>
                            <option value="Transfer from another location">Transfer from another location</option>
                            <option value="Manual correction">Manual correction</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="add-notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="add-notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Select all checkbox
    $('#select-all').change(function() {
        $('.product-checkbox').prop('checked', $(this).prop('checked'));
    });

    // Quick delivery toggle
    $('.quick-delivery-toggle').change(function() {
        const productId = $(this).data('product-id');
        const isEnabled = $(this).prop('checked');
        
        $.post(`/admin/warehouse/product/${productId}/toggle-quick-delivery`, {
            _token: '{{ csrf_token() }}'
        })
        .done(function(response) {
            if (response.success) {
                toastr.success(response.message);
            } else {
                toastr.error(response.message || 'Failed to update');
                // Revert the toggle
                $(this).prop('checked', !isEnabled);
            }
        })
        .fail(function() {
            toastr.error('Failed to update quick delivery status');
            // Revert the toggle
            $(this).prop('checked', !isEnabled);
        });
    });

    // Add stock modal
    $('.add-stock-btn').click(function() {
        const productId = $(this).data('product-id');
        const productName = $(this).data('product-name');
        
        $('#add-stock-product-id').val(productId);
        $('#add-stock-product-name').text(productName);
        $('#addStockModal').modal('show');
    });

    // Add stock form submission
    $('#addStockForm').submit(function(e) {
        e.preventDefault();
        
        $.post('/admin/warehouse/add-stock', $(this).serialize() + '&_token={{ csrf_token() }}')
        .done(function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#addStockModal').modal('hide');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to add stock');
            }
        })
        .fail(function() {
            toastr.error('Failed to add stock');
        });
    });
});

// Bulk operations
function bulkOperation(operation) {
    const selectedIds = $('.product-checkbox:checked').map(function() {
        return $(this).val();
    }).get();

    if (selectedIds.length === 0) {
        toastr.warning('Please select products first');
        return;
    }

    if (!confirm(`Are you sure you want to perform this action on ${selectedIds.length} products?`)) {
        return;
    }

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
</script>
@endpush