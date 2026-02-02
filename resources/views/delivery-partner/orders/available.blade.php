@extends('delivery-partner.layouts.dashboard')

@section('title', 'Available Orders')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Available Orders</h1>
            <p class="text-muted small mb-0">Discover and accept new delivery tasks</p>
        </div>
        <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
            <i class="fas fa-sync-alt me-1"></i> Refresh
        </button>
    </div>
    
    @if(isset($partner) && !$partner->isAvailableForDelivery())
        <div class="card border-0 shadow-sm bg-light mb-4">
            <div class="card-body py-4 text-center">
                <div class="mb-3">
                    <span class="bg-warning-soft text-warning p-3 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </span>
                </div>
                <h5>You are currently {{ !$partner->is_online ? 'Offline' : 'Busy/Unavailable' }}</h5>
                <p class="text-muted mb-3">
                    {{ !$partner->is_online ? 'You need to go online to see and accept available orders.' : 'Finish your current delivery or set your status to available to accept new orders.' }}
                </p>
                <div class="d-flex justify-content-center gap-2">
                    @if(!$partner->is_online)
                        <a href="{{ route('delivery-partner.dashboard') }}" class="btn btn-primary">
                            <i class="fas fa-power-off me-1"></i> Go Online on Dashboard
                        </a>
                    @endif
                    <button class="btn btn-outline-secondary" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-1"></i> Refresh Status
                    </button>
                </div>
            </div>
        </div>
        <style>
            .bg-warning-soft { background-color: rgba(255, 193, 7, 0.15) !important; }
        </style>
    @endif

    @if(isset($availableOrders) && count($availableOrders) > 0)
        <div class="row g-4">
            @foreach($availableOrders as $order)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0 border-top border-4 border-{{ ($order->type ?? '') == 'food' ? 'danger' : (($order->type ?? '') == 'ten_min' ? 'warning' : 'primary') }}" 
                         onclick="viewOrder({{ $order->normalized_id ?? $order->id }}, '{{ $order->type ?? 'standard' }}')"
                         style="cursor: pointer; transition: transform 0.2s;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title fw-bold mb-0">
                                        {{ $order->normalized_order_number ?? $order->order_number ?? 'ORD-' . $order->id }}
                                    </h5>
                                    <span class="badge bg-{{ ($order->type ?? '') == 'food' ? 'danger' : (($order->type ?? '') == 'ten_min' ? 'warning' : 'primary') }} mt-1">
                                        {{ ($order->type ?? '') == 'food' ? 'Food Order' : (($order->type ?? '') == 'ten_min' ? '10-Min Fast' : 'Standard') }}
                                    </span>
                                </div>
                                <div class="text-end">
                                    <div class="h5 fw-bold text-primary mb-0">₹{{ number_format($order->total_amount_display ?? $order->total_amount ?? 0, 0) }}</div>
                                    <small class="text-muted">{{ $order->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="pickup-section mb-2">
                                    <div class="d-flex align-items-start text-success small fw-bold">
                                        <i class="fas fa-store me-2 mt-1"></i>
                                        <span>Pickup: {{ $order->pickup_name_display ?? 'Shop' }}</span>
                                    </div>
                                    <div class="text-muted small ps-4" style="line-height: 1.2;">
                                        {{ $order->pickup_address_display ?? 'Address not found' }}
                                    </div>
                                </div>
                                
                                <div class="delivery-section">
                                    <div class="d-flex align-items-start text-primary small fw-bold">
                                        <i class="fas fa-map-marker-alt me-2 mt-1"></i>
                                        <span>Delivery: {{ $order->customer_name_display ?? 'Customer' }}</span>
                                    </div>
                                    <div class="text-muted small ps-4" style="line-height: 1.2;">
                                        {{ $order->delivery_address_display ?? $order->delivery_address ?? 'Address not available' }}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid mt-auto">
                                <button class="btn btn-primary" onclick="acceptOrder({{ $order->normalized_id ?? $order->id }}, '{{ $order->type ?? 'standard' }}'); event.stopPropagation();">
                                    <i class="fas fa-check me-1"></i> Accept Order
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-5 bg-white rounded shadow-sm">
            <div class="mb-4">
                <div class="bg-light d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px;">
                    <i class="fas fa-box-open fa-3x text-muted"></i>
                </div>
            </div>
            <h4>No Orders Available</h4>
            <p class="text-muted px-4">There are no orders ready for delivery in your vicinity right now. We'll alert you when a new task arrives!</p>
            <button class="btn btn-primary px-4 mt-2" onclick="location.reload()">
                Check for New Tasks
            </button>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Accept order
    function acceptOrder(orderId, type = 'standard') {
        if (!confirm('Are you sure you want to accept this order?')) return;

        $.ajax({
            url: '{{ route("delivery-partner.orders.accept", ":id") }}'.replace(':id', orderId),
            method: 'POST',
            data: {
                type: type,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Order accepted successfully!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = '{{ route("delivery-partner.dashboard") }}';
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to accept order', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Failed to accept order. Please try again.', 'error');
            }
        });
    }

    // View order details
    function viewOrder(orderId, type = 'standard') {
        window.location.href = `/delivery-partner/orders/${orderId}?type=${type}`;
    }

    // Card hover effect
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('mouseenter', () => card.style.transform = 'translateY(-5px)');
        card.addEventListener('mouseleave', () => card.style.transform = 'translateY(0)');
    });
</script>
@endpush
@endsection
