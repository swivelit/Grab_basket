@extends('admin.layouts.main')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">📦 Product Details</h1>
            <p class="text-muted mb-0">{{ $warehouseProduct->product->name ?? 'Product ID: ' . $warehouseProduct->product_id }}</p>
        </div>
        <div>
            <a href="{{ url('/admin/warehouse/inventory') }}" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> Back to Inventory
            </a>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStockModal">
                <i class="fas fa-plus"></i> Add Stock
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Product Information -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Product Information</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ url('/admin/warehouse/product/' . $warehouseProduct->id) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Product Name</label>
                                    <div class="form-control-plaintext">{{ $warehouseProduct->product->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">SKU</label>
                                    <div class="form-control-plaintext">{{ $warehouseProduct->product->sku ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Stock Information -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="stock_quantity" class="form-label">Current Stock *</label>
                                    <input type="number" class="form-control" id="stock_quantity" 
                                           name="stock_quantity" value="{{ $warehouseProduct->stock_quantity }}" 
                                           min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="minimum_stock_level" class="form-label">Minimum Stock Level *</label>
                                    <input type="number" class="form-control" id="minimum_stock_level" 
                                           name="minimum_stock_level" value="{{ $warehouseProduct->minimum_stock_level }}" 
                                           min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="maximum_stock_level" class="form-label">Maximum Stock Level *</label>
                                    <input type="number" class="form-control" id="maximum_stock_level" 
                                           name="maximum_stock_level" value="{{ $warehouseProduct->maximum_stock_level }}" 
                                           min="0" required>
                                </div>
                            </div>
                        </div>

                        <!-- Location Information -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="aisle" class="form-label">Aisle</label>
                                    <input type="text" class="form-control" id="aisle" 
                                           name="aisle" value="{{ $warehouseProduct->aisle }}" 
                                           placeholder="A, B, C...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="rack" class="form-label">Rack</label>
                                    <input type="text" class="form-control" id="rack" 
                                           name="rack" value="{{ $warehouseProduct->rack }}" 
                                           placeholder="1, 2, 3...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="shelf" class="form-label">Shelf</label>
                                    <input type="text" class="form-control" id="shelf" 
                                           name="shelf" value="{{ $warehouseProduct->shelf }}" 
                                           placeholder="A, B, C...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="location_code" class="form-label">Location Code</label>
                                    <input type="text" class="form-control" id="location_code" 
                                           name="location_code" value="{{ $warehouseProduct->location_code }}" 
                                           placeholder="A1A, B2C...">
                                </div>
                            </div>
                        </div>

                        <!-- Product Condition -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="condition" class="form-label">Condition *</label>
                                    <select class="form-select" id="condition" name="condition" required>
                                        <option value="excellent" {{ $warehouseProduct->condition === 'excellent' ? 'selected' : '' }}>Excellent</option>
                                        <option value="good" {{ $warehouseProduct->condition === 'good' ? 'selected' : '' }}>Good</option>
                                        <option value="fair" {{ $warehouseProduct->condition === 'fair' ? 'selected' : '' }}>Fair</option>
                                        <option value="damaged" {{ $warehouseProduct->condition === 'damaged' ? 'selected' : '' }}>Damaged</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="expiry_date" class="form-label">Expiry Date</label>
                                    <input type="date" class="form-control" id="expiry_date" 
                                           name="expiry_date" 
                                           value="{{ $warehouseProduct->expiry_date ? \Carbon\Carbon::parse($warehouseProduct->expiry_date)->format('Y-m-d') : '' }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="fragility" class="form-label">Fragility *</label>
                                    <select class="form-select" id="fragility" name="fragility" required>
                                        <option value="low" {{ $warehouseProduct->fragility === 'low' ? 'selected' : '' }}>Low</option>
                                        <option value="medium" {{ $warehouseProduct->fragility === 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="high" {{ $warehouseProduct->fragility === 'high' ? 'selected' : '' }}>High</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cost_price" class="form-label">Cost Price *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" id="cost_price" 
                                               name="cost_price" value="{{ $warehouseProduct->cost_price }}" 
                                               step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="selling_price" class="form-label">Selling Price *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" id="selling_price" 
                                               name="selling_price" value="{{ $warehouseProduct->selling_price }}" 
                                               step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Details -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="supplier" class="form-label">Supplier</label>
                                    <input type="text" class="form-control" id="supplier" 
                                           name="supplier" value="{{ $warehouseProduct->supplier }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="batch_number" class="form-label">Batch Number</label>
                                    <input type="text" class="form-control" id="batch_number" 
                                           name="batch_number" value="{{ $warehouseProduct->batch_number }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="weight_grams" class="form-label">Weight (grams)</label>
                                    <input type="number" class="form-control" id="weight_grams" 
                                           name="weight_grams" value="{{ $warehouseProduct->weight_grams }}" 
                                           min="0">
                                </div>
                            </div>
                        </div>

                        <!-- Checkboxes -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_available_for_quick_delivery" 
                                           name="is_available_for_quick_delivery" value="1"
                                           {{ $warehouseProduct->is_available_for_quick_delivery ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_available_for_quick_delivery">
                                        Available for Quick Delivery
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="requires_cold_storage" 
                                           name="requires_cold_storage" value="1"
                                           {{ $warehouseProduct->requires_cold_storage ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_cold_storage">
                                        Requires Cold Storage
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Handling Notes -->
                        <div class="mb-3">
                            <label for="handling_notes" class="form-label">Handling Notes</label>
                            <textarea class="form-control" id="handling_notes" name="handling_notes" rows="3">{{ $warehouseProduct->handling_notes }}</textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Stats & Quick Info -->
        <div class="col-lg-4">
            <!-- Current Status -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Current Status</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-right">
                                <h4 class="text-success">{{ $warehouseProduct->available_quantity }}</h4>
                                <p class="text-muted mb-0">Available</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-warning">{{ $warehouseProduct->reserved_quantity }}</h4>
                            <p class="text-muted mb-0">Reserved</p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Status:</span>
                        <span class="badge bg-{{ $warehouseProduct->stock_status_color }}">
                            {{ ucfirst(str_replace('_', ' ', $warehouseProduct->stock_status)) }}
                        </span>
                    </div>
                    
                    @if($warehouseProduct->expiry_date)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Days to Expiry:</span>
                        <span class="{{ $warehouseProduct->days_until_expiry <= 7 ? 'text-warning' : 'text-muted' }}">
                            {{ $warehouseProduct->days_until_expiry > 0 ? $warehouseProduct->days_until_expiry : 'Expired' }}
                        </span>
                    </div>
                    @endif
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Quick Delivery:</span>
                        <span class="text-{{ $warehouseProduct->is_available_for_quick_delivery ? 'success' : 'muted' }}">
                            {{ $warehouseProduct->is_available_for_quick_delivery ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Margin:</span>
                        <span class="text-success">
                            ₹{{ number_format($warehouseProduct->selling_price - $warehouseProduct->cost_price, 2) }}
                            ({{ number_format((($warehouseProduct->selling_price - $warehouseProduct->cost_price) / $warehouseProduct->cost_price) * 100, 1) }}%)
                        </span>
                    </div>
                </div>
            </div>

            <!-- Movement Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Movement Stats (30 Days)</h6>
                </div>
                <div class="card-body">
                    @if($movementStats->isNotEmpty())
                        @foreach($movementStats as $type => $stats)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>{{ ucfirst(str_replace('_', ' ', $type)) }}:</span>
                                <div class="text-end">
                                    <div class="fw-bold">{{ $stats->count }} times</div>
                                    <small class="text-muted">{{ $stats->total_quantity }} units</small>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center">No movements in last 30 days</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Movements -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Stock Movements</h6>
        </div>
        <div class="card-body">
            @if($recentMovements->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Before/After</th>
                                <th>Reason</th>
                                <th>Performed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentMovements as $movement)
                            <tr>
                                <td>{{ $movement->created_at->format('M j, Y g:i A') }}</td>
                                <td>
                                    <span class="badge bg-{{ $movement->movement_type_color }}">
                                        <i class="fas {{ $movement->movement_type_icon }}"></i>
                                        {{ $movement->movement_type_display }}
                                    </span>
                                </td>
                                <td>
                                    <span class="{{ $movement->quantity_changed >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $movement->quantity_change_display }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $movement->quantity_before }} → {{ $movement->quantity_after }}
                                    </small>
                                </td>
                                <td>
                                    <div>{{ $movement->reason }}</div>
                                    @if($movement->notes)
                                        <small class="text-muted">{{ $movement->notes }}</small>
                                    @endif
                                </td>
                                <td>{{ $movement->performed_by }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-3">
                    <a href="{{ url('/admin/warehouse/stock-movements?search=' . urlencode($warehouseProduct->product->name ?? '')) }}" 
                       class="btn btn-outline-primary">
                        View All Movements for this Product
                    </a>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-exchange-alt fa-2x text-muted mb-3"></i>
                    <h6 class="text-muted">No movements recorded yet</h6>
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
                    <input type="hidden" name="warehouse_product_id" value="{{ $warehouseProduct->id }}">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Product</label>
                        <div class="text-muted">{{ $warehouseProduct->product->name ?? 'N/A' }}</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity *</label>
                                <input type="number" class="form-control" id="quantity" 
                                       name="quantity" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="unit_cost" class="form-label">Unit Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="unit_cost" 
                                           name="unit_cost" step="0.01" min="0" 
                                           value="{{ $warehouseProduct->cost_price }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="supplier_name" class="form-label">Supplier</label>
                                <input type="text" class="form-control" id="supplier_name" 
                                       name="supplier_name" value="{{ $warehouseProduct->supplier }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reference_number" class="form-label">Reference No.</label>
                                <input type="text" class="form-control" id="reference_number" name="reference_number">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason *</label>
                        <select class="form-select" id="reason" name="reason" required>
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
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
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

    // Auto-calculate margin when prices change
    $('#cost_price, #selling_price').on('input', function() {
        const costPrice = parseFloat($('#cost_price').val()) || 0;
        const sellingPrice = parseFloat($('#selling_price').val()) || 0;
        
        if (costPrice > 0 && sellingPrice > 0) {
            const margin = sellingPrice - costPrice;
            const marginPercent = (margin / costPrice) * 100;
            
            $('#margin-display').html(`
                ₹${margin.toFixed(2)} (${marginPercent.toFixed(1)}%)
            `);
        }
    });
});
</script>
@endpush