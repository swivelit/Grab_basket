@extends('admin.layouts.main')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">📝 Stock Movements</h1>
            <p class="text-muted mb-0">Track all inventory transactions and changes</p>
        </div>
        <div>
            <a href="{{ url('/admin/warehouse/dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ url('/admin/warehouse/stock-movements') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search Products</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Product name or SKU...">
                </div>
                
                <div class="col-md-2">
                    <label for="movement_type" class="form-label">Movement Type</label>
                    <select class="form-select" id="movement_type" name="movement_type">
                        <option value="">All Types</option>
                        @foreach($movementTypes as $value => $label)
                            <option value="{{ $value }}" {{ request('movement_type') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="{{ request('start_date', now()->subDays(30)->format('Y-m-d')) }}">
                </div>

                <div class="col-md-2">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="{{ request('end_date', now()->format('Y-m-d')) }}">
                </div>

                <div class="col-md-3">
                    <label for="performed_by" class="form-label">Performed By</label>
                    <input type="text" class="form-control" id="performed_by" name="performed_by" 
                           value="{{ request('performed_by') }}" placeholder="Staff name...">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="{{ url('/admin/warehouse/stock-movements') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Movements Table -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Stock Movements ({{ $movements->total() }} records)
            </h6>
        </div>

        <div class="card-body">
            @if($movements->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Product</th>
                                <th>Movement Type</th>
                                <th>Quantity Change</th>
                                <th>Before/After</th>
                                <th>Reason</th>
                                <th>Value</th>
                                <th>Performed By</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($movements as $movement)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $movement->created_at->format('M j, Y') }}</div>
                                    <small class="text-muted">{{ $movement->created_at->format('g:i A') }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $movement->product->name ?? 'N/A' }}</div>
                                    <small class="text-muted">
                                        SKU: {{ $movement->product->sku ?? 'N/A' }}
                                        @if($movement->warehouseProduct?->location_code)
                                            | {{ $movement->warehouseProduct->location_code }}
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $movement->movement_type_color }}">
                                        <i class="fas {{ $movement->movement_type_icon }}"></i>
                                        {{ $movement->movement_type_display }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold {{ $movement->quantity_changed >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $movement->quantity_change_display }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $movement->quantity_before }} → {{ $movement->quantity_after }}
                                    </small>
                                </td>
                                <td>
                                    <div>{{ $movement->reason ?? 'N/A' }}</div>
                                    @if($movement->notes)
                                        <small class="text-muted">{{ Str::limit($movement->notes, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($movement->total_value)
                                        <div class="fw-bold">₹{{ number_format($movement->total_value, 2) }}</div>
                                        @if($movement->unit_cost)
                                            <small class="text-muted">@₹{{ number_format($movement->unit_cost, 2) }} each</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $movement->performed_by }}</div>
                                    @if($movement->approved_by)
                                        <small class="text-muted">Approved by: {{ $movement->approved_by }}</small>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info view-movement-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#movementModal"
                                            data-movement="{{ htmlspecialchars(json_encode($movement), ENT_QUOTES, 'UTF-8') }}"
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing {{ $movements->firstItem() }} to {{ $movements->lastItem() }} 
                        of {{ $movements->total() }} results
                    </div>
                    {{ $movements->links() }}
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No movements found</h5>
                    <p class="text-muted">Try adjusting your search filters or date range.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Movement Details Modal -->
<div class="modal fade" id="movementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Stock Movement Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="movement-details">
                    <!-- Details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.view-movement-btn').click(function() {
        const movementData = $(this).data('movement');
        showMovementDetails(movementData);
    });
});

function showMovementDetails(movement) {
    const details = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold">Movement Information</h6>
                <table class="table table-sm">
                    <tr><td class="fw-bold">Product:</td><td>${movement.product?.name || 'N/A'}</td></tr>
                    <tr><td class="fw-bold">SKU:</td><td>${movement.product?.sku || 'N/A'}</td></tr>
                    <tr><td class="fw-bold">Movement Type:</td><td><span class="badge bg-${movement.movement_type_color}">${movement.movement_type_display}</span></td></tr>
                    <tr><td class="fw-bold">Date:</td><td>${new Date(movement.created_at).toLocaleString()}</td></tr>
                    <tr><td class="fw-bold">Performed By:</td><td>${movement.performed_by}</td></tr>
                    ${movement.approved_by ? `<tr><td class="fw-bold">Approved By:</td><td>${movement.approved_by}</td></tr>` : ''}
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold">Quantity & Financial</h6>
                <table class="table table-sm">
                    <tr><td class="fw-bold">Quantity Before:</td><td>${movement.quantity_before}</td></tr>
                    <tr><td class="fw-bold">Quantity Changed:</td><td class="${movement.quantity_changed >= 0 ? 'text-success' : 'text-danger'}">${movement.quantity_changed >= 0 ? '+' : ''}${movement.quantity_changed}</td></tr>
                    <tr><td class="fw-bold">Quantity After:</td><td>${movement.quantity_after}</td></tr>
                    ${movement.unit_cost ? `<tr><td class="fw-bold">Unit Cost:</td><td>₹${parseFloat(movement.unit_cost).toFixed(2)}</td></tr>` : ''}
                    ${movement.total_value ? `<tr><td class="fw-bold">Total Value:</td><td>₹${parseFloat(movement.total_value).toFixed(2)}</td></tr>` : ''}
                </table>
            </div>
        </div>
        
        ${movement.reason ? `
            <div class="row mt-3">
                <div class="col-12">
                    <h6 class="fw-bold">Reason</h6>
                    <div class="bg-light p-2 rounded">${movement.reason}</div>
                </div>
            </div>
        ` : ''}
        
        ${movement.notes ? `
            <div class="row mt-3">
                <div class="col-12">
                    <h6 class="fw-bold">Notes</h6>
                    <div class="bg-light p-2 rounded">${movement.notes}</div>
                </div>
            </div>
        ` : ''}
        
        ${movement.reference_number || movement.supplier_name || movement.customer_name ? `
            <div class="row mt-3">
                <div class="col-12">
                    <h6 class="fw-bold">Additional Information</h6>
                    <table class="table table-sm">
                        ${movement.reference_number ? `<tr><td class="fw-bold">Reference Number:</td><td>${movement.reference_number}</td></tr>` : ''}
                        ${movement.supplier_name ? `<tr><td class="fw-bold">Supplier:</td><td>${movement.supplier_name}</td></tr>` : ''}
                        ${movement.customer_name ? `<tr><td class="fw-bold">Customer:</td><td>${movement.customer_name}</td></tr>` : ''}
                        ${movement.order_id ? `<tr><td class="fw-bold">Order ID:</td><td>${movement.order_id}</td></tr>` : ''}
                    </table>
                </div>
            </div>
        ` : ''}
    `;
    
    document.getElementById('movement-details').innerHTML = details;
}
</script>
@endpush