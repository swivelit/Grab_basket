@extends('warehouse.layouts.app')

@section('title', 'Location Management')

@section('breadcrumb')
<li class="breadcrumb-item active">Location Management</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="text-primary mb-2">
                        <i class="bi bi-geo-alt display-4"></i>
                    </div>
                    <h4>{{ $locationStats['active_locations'] }}</h4>
                    <p class="text-muted mb-0">Active Locations</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="text-success mb-2">
                        <i class="bi bi-check-circle display-4"></i>
                    </div>
                    <h4>{{ $locationStats['assigned_products'] }}</h4>
                    <p class="text-muted mb-0">Assigned Products</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="text-warning mb-2">
                        <i class="bi bi-exclamation-triangle display-4"></i>
                    </div>
                    <h4>{{ $locationStats['unassigned_products'] }}</h4>
                    <p class="text-muted mb-0">Unassigned Products</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="text-info mb-2">
                        <i class="bi bi-lightning display-4"></i>
                    </div>
                    <h4>{{ $locationStats['quick_delivery_locations'] }}</h4>
                    <p class="text-muted mb-0">Quick Delivery Locations</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Location Management Controls -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-tools me-2"></i>
                        Location Management
                    </h5>
                    <div>
                        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                            <i class="bi bi-plus-circle me-1"></i>Add Location
                        </button>
                        <button class="btn btn-primary me-2" onclick="optimizeQuickDelivery()">
                            <i class="bi bi-lightning me-1"></i>Optimize Quick Delivery
                        </button>
                        <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#moveProductsModal">
                            <i class="bi bi-arrow-left-right me-1"></i>Move Products
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search locations..." 
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="aisle" class="form-select">
                                <option value="">All Aisles</option>
                                @foreach(['A', 'B', 'C', 'D', 'E', 'F'] as $aisle)
                                    <option value="{{ $aisle }}" {{ request('aisle') === $aisle ? 'selected' : '' }}>
                                        Aisle {{ $aisle }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-search me-1"></i>Filter
                            </button>
                            <a href="{{ route('warehouse.locations') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i>Reset
                            </a>
                        </div>
                    </form>

                    <!-- Locations Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Location</th>
                                    <th>Aisle</th>
                                    <th>Products</th>
                                    <th>Total Stock</th>
                                    <th>In Stock</th>
                                    <th>Out of Stock</th>
                                    <th>Quick Delivery</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($locations as $location)
                                <tr>
                                    <td>
                                        <strong>{{ $location->location }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $location->aisle }}</span>
                                    </td>
                                    <td>{{ number_format($location->product_count) }}</td>
                                    <td>{{ number_format($location->total_stock) }}</td>
                                    <td>
                                        <span class="badge bg-success">{{ $location->in_stock_count }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">{{ $location->out_stock_count }}</span>
                                    </td>
                                    <td>
                                        @if($location->quick_delivery_ratio > 0.5)
                                            <span class="badge bg-warning">
                                                <i class="bi bi-lightning me-1"></i>Quick
                                            </span>
                                        @else
                                            <span class="badge bg-light text-dark">Standard</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" 
                                                    onclick="viewLocationProducts('{{ $location->location }}')"
                                                    title="View Products">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-warning" 
                                                    onclick="editLocation('{{ $location->location }}')"
                                                    title="Edit Location">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-outline-info" 
                                                    onclick="showMoveModal('{{ $location->location }}')"
                                                    title="Move Products">
                                                <i class="bi bi-arrow-left-right"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox display-1 mb-3"></i>
                                        <p>No locations found</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($locations->hasPages())
                        <div class="d-flex justify-content-center">
                            {{ $locations->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Location Modal -->
<div class="modal fade" id="addLocationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>Add New Location
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addLocationForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Aisle *</label>
                                <select name="aisle" class="form-select" required>
                                    <option value="">Select Aisle</option>
                                    @foreach(['A', 'B', 'C', 'D', 'E', 'F'] as $aisle)
                                        <option value="{{ $aisle }}">Aisle {{ $aisle }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Rack</label>
                                <input type="text" name="rack" class="form-control" placeholder="e.g., 1, 2, 3">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Shelf</label>
                                <input type="text" name="shelf" class="form-control" placeholder="e.g., 1, 2, 3">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Bin</label>
                                <input type="text" name="bin" class="form-control" placeholder="e.g., A, B, C">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional description"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_quick_delivery" id="isQuickDelivery">
                                <label class="form-check-label" for="isQuickDelivery">
                                    Quick Delivery Location
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                                <label class="form-check-label" for="isActive">
                                    Active Location
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>Create Location
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Move Products Modal -->
<div class="modal fade" id="moveProductsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-arrow-left-right me-2"></i>Move Products
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="moveProductsForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">From Location</label>
                                <select name="from_location" class="form-select">
                                    <option value="">Any Location</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->location }}">{{ $location->location }} ({{ $location->product_count }} products)</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">To Location *</label>
                                <input type="text" name="to_location" class="form-control" placeholder="e.g., A-1-2-A" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Products to Move</label>
                        <div id="productSelectionArea" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                            <p class="text-muted">Select a location to see products</p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Move</label>
                        <input type="text" name="reason" class="form-control" placeholder="Optional reason for moving products">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-arrow-left-right me-1"></i>Move Products
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Location Products Modal -->
<div class="modal fade" id="locationProductsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-boxes me-2"></i>Products in Location: <span id="modalLocationName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="locationProductsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border" role="status"></div>
                        <p class="mt-2">Loading products...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Add location form submission
    $('#addLocationForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("warehouse.locations.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#addLocationModal').modal('hide');
                    showAlert('success', response.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('danger', response.message || 'Failed to create location');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors || {};
                let errorMessage = 'Failed to create location';
                
                if (Object.keys(errors).length > 0) {
                    errorMessage = Object.values(errors).flat().join('\n');
                }
                
                showAlert('danger', errorMessage);
            }
        });
    });

    // Move products form submission
    $('#moveProductsForm').on('submit', function(e) {
        e.preventDefault();
        
        const selectedProducts = $('input[name="product_ids[]"]:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selectedProducts.length === 0) {
            showAlert('warning', 'Please select at least one product to move');
            return;
        }
        
        const formData = new FormData(this);
        selectedProducts.forEach(id => {
            formData.append('product_ids[]', id);
        });
        
        $.ajax({
            url: '{{ route("warehouse.locations") }}/move-products',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#moveProductsModal').modal('hide');
                    showAlert('success', response.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('danger', response.message || 'Failed to move products');
                }
            },
            error: function(xhr) {
                showAlert('danger', 'Failed to move products. Please try again.');
            }
        });
    });

    // Load products when from_location changes
    $('select[name="from_location"]').on('change', function() {
        const location = $(this).val();
        if (location) {
            loadLocationProducts(location, '#productSelectionArea');
        } else {
            $('#productSelectionArea').html('<p class="text-muted">Select a location to see products</p>');
        }
    });
});

function viewLocationProducts(location) {
    $('#modalLocationName').text(location);
    $('#locationProductsModal').modal('show');
    loadLocationProducts(location, '#locationProductsContent', true);
}

function loadLocationProducts(location, container, detailed = false) {
    $(container).html(`
        <div class="text-center py-4">
            <div class="spinner-border" role="status"></div>
            <p class="mt-2">Loading products...</p>
        </div>
    `);
    
    $.ajax({
        url: `/warehouse/locations/${encodeURIComponent(location)}/products`,
        method: 'GET',
        success: function(response) {
            if (response.success && response.products.length > 0) {
                let html = '';
                
                if (detailed) {
                    html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Product</th><th>SKU</th><th>Stock</th><th>Status</th></tr></thead><tbody>';
                    
                    response.products.forEach(function(product) {
                        html += `
                            <tr>
                                <td>${product.product?.name || 'Unknown Product'}</td>
                                <td>${product.product?.sku || 'No SKU'}</td>
                                <td>${product.current_stock}</td>
                                <td>
                                    <span class="badge ${product.current_stock > 0 ? 'bg-success' : 'bg-danger'}">
                                        ${product.current_stock > 0 ? 'In Stock' : 'Out of Stock'}
                                    </span>
                                </td>
                            </tr>
                        `;
                    });
                    
                    html += '</tbody></table></div>';
                } else {
                    response.products.forEach(function(product) {
                        html += `
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="product_ids[]" value="${product.id}" id="product_${product.id}">
                                <label class="form-check-label" for="product_${product.id}">
                                    <strong>${product.product?.name || 'Unknown Product'}</strong><br>
                                    <small class="text-muted">SKU: ${product.product?.sku || 'No SKU'} | Stock: ${product.current_stock}</small>
                                </label>
                            </div>
                        `;
                    });
                }
                
                $(container).html(html);
            } else {
                $(container).html('<p class="text-muted text-center">No products found in this location</p>');
            }
        },
        error: function() {
            $(container).html('<p class="text-danger text-center">Error loading products</p>');
        }
    });
}

function optimizeQuickDelivery() {
    if (!confirm('This will move quick delivery products to optimized locations. Continue?')) {
        return;
    }
    
    $.ajax({
        url: '{{ route("warehouse.locations") }}/optimize-quick-delivery',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                setTimeout(() => location.reload(), 2000);
            } else {
                showAlert('danger', response.message || 'Optimization failed');
            }
        },
        error: function() {
            showAlert('danger', 'Optimization failed. Please try again.');
        }
    });
}

function editLocation(location) {
    // Implementation for editing location properties
    showAlert('info', 'Edit location feature coming soon!');
}

function showMoveModal(fromLocation) {
    $('select[name="from_location"]').val(fromLocation).trigger('change');
    $('#moveProductsModal').modal('show');
}

function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('main').prepend(alertHtml);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endpush