@if($availableOrders && $availableOrders->count() > 0)
    @foreach($availableOrders as $order)
        <div class="order-card" onclick="viewOrder({{ $order->id }}, '{{ $order->type ?? 'standard' }}')">
            <div class="order-header">
                <div>
                    <div class="order-number">
                        {{ $order->normalized_order_number ?? $order->order_number ?? 'ORD-' . $order->id }}
                        <span class="badge bg-{{ ($order->type ?? '') == 'food' ? 'danger' : (($order->type ?? '') == 'ten_min' ? 'warning' : 'primary') }} ms-2">
                            {{ ($order->type ?? '') == 'food' ? 'Food' : (($order->type ?? '') == 'ten_min' ? '10-Min' : 'Standard') }}
                        </span>
                    </div>
                    <small class="text-muted">{{ $order->customer_name_display ?? $order->user->name ?? 'Customer' }}</small>
                </div>
                <div class="text-end">
                    <div class="fw-bold">₹{{ number_format($order->total_amount_display ?? $order->total_amount ?? 0, 0) }}</div>
                    <small class="text-muted">{{ isset($order->created_at_display) ? $order->created_at_display->diffForHumans() : $order->created_at->diffForHumans() }}</small>
                </div>
            </div>
            <div class="mb-2">
                <small class="text-success fw-bold d-block">
                    <i class="fas fa-store me-1"></i> Pickup: {{ $order->pickup_name_display ?? 'Shop' }}
                </small>
                <small class="text-muted d-block ps-3" style="line-height: 1.2;">
                    {{ Str::limit($order->pickup_address_display ?? 'Address not found', 45) }}
                </small>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="fas fa-map-marker-alt me-1 text-primary"></i>
                    Delivery: {{ Str::limit($order->delivery_address_display ?? $order->delivery_address ?? 'Address not available', 30) }}
                </small>
                <button class="btn btn-primary btn-sm" onclick="acceptOrder({{ $order->normalized_id ?? $order->id }}, '{{ $order->type ?? 'standard' }}'); event.stopPropagation();">
                    Accept
                </button>
            </div>
        </div>
    @endforeach
@else
    <div class="text-center py-4">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <p class="text-muted mb-2">No available orders</p>
        <small class="text-muted">
            @if(isset($partner) && !$partner->isAvailableForDelivery())
                Go online to see available orders
            @else
                Check back later for new orders
            @endif
        </small>
    </div>
@endif
