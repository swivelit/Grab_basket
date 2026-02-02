@extends('delivery-partner.layouts.dashboard')

@section('title', 'My Orders')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">My Orders</h1>
            <p class="text-muted small mb-0">Track your delivery history and active assignments</p>
        </div>
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-menu-item" href="#">All Orders</a></li>
                <li><a class="dropdown-menu-item" href="#">Active</a></li>
                <li><a class="dropdown-menu-item" href="#">Completed</a></li>
            </ul>
        </div>
    </div>
    
    @if(isset($orders) && count($orders) > 0)
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Order ID</th>
                            <th>Type</th>
                            <th>Route (Pickup & Delivery)</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td class="ps-4 fw-bold">
                                    {{ $order->normalized_order_number ?? $order->order_number ?? 'ORD-' . $order->id }}
                                </td>
                                <td>
                                    <span class="badge bg-{{ ($order->type ?? '') == 'food' ? 'danger' : (($order->type ?? '') == 'ten_min' ? 'warning' : 'primary') }} rounded-pill">
                                        {{ ($order->type ?? '') == 'food' ? 'Food' : (($order->type ?? '') == 'ten_min' ? '10-Min' : 'Standard') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="pickup-info mb-1 d-flex align-items-center">
                                        <i class="fas fa-store text-success me-2" style="width: 14px;"></i>
                                        <div class="small">
                                            <span class="text-success fw-bold">{{ $order->pickup_name_display ?? 'Shop' }}</span>
                                            <div class="text-muted text-truncate" style="max-width: 180px; font-size: 0.75rem;">
                                                {{ $order->pickup_address_display ?? 'Address not found' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="delivery-info d-flex align-items-center">
                                        <i class="fas fa-map-marker-alt text-primary me-2" style="width: 14px;"></i>
                                        <div class="small">
                                            <span class="text-primary fw-bold">{{ $order->customer_name_display ?? 'Customer' }}</span>
                                            <div class="text-muted text-truncate" style="max-width: 180px; font-size: 0.75rem;">
                                                {{ $order->delivery_address_display ?? 'Address not available' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-bold">{{ $order->created_at->format('d M, Y') }}</div>
                                    <div class="text-muted small">{{ $order->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="fw-bold text-dark">
                                    ₹{{ number_format($order->total_amount_display ?? $order->total_amount ?? 0, 0) }}
                                </td>
                                <td>
                                    @php
                                        $status = $order->status ?? $order->delivery_status ?? 'pending';
                                        $badgeClass = match($status) {
                                            'delivered', 'completed' => 'success',
                                            'picked_up', 'out_for_delivery' => 'info',
                                            'assigned', 'accepted', 'confirmed' => 'primary',
                                            'cancelled', 'failed' => 'danger',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badgeClass }}-soft text-{{ $badgeClass }} border border-{{ $badgeClass }} px-2 py-1">
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('delivery-partner.orders.show', $order->normalized_id ?? $order->id) }}?type={{ $order->type ?? 'standard' }}" 
                                       class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        Details
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="text-center py-5 bg-white rounded shadow-sm">
            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
            <h4>No Orders Yet</h4>
            <p class="text-muted px-4">You haven't accepted any delivery tasks yet. Head over to the Available Orders page to find work!</p>
            <a href="{{ route('delivery-partner.orders.available') }}" class="btn btn-primary px-4 mt-2">
                View Available Orders
            </a>
        </div>
    @endif
</div>

<style>
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1) !important; }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1) !important; }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1) !important; }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1) !important; }
    .bg-secondary-soft { background-color: rgba(108, 117, 125, 0.1) !important; }
    
    .table thead th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-bottom: none;
    }
    
    .table tbody td {
        padding-top: 1rem;
        padding-bottom: 1rem;
        border-color: #f8f9fa;
    }
</style>
@endsection
