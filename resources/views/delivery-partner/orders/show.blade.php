@extends('delivery-partner.layouts.dashboard')

@section('title', 'Order Details')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Order #{{ $order->normalized_order_number ?? $order->order_number ?? $order->id }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('delivery-partner.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Order Details</li>
                </ol>
            </nav>
        </div>
        <div>
            <span class="badge bg-{{ $order->type == 'food' ? 'danger' : ($order->type == 'ten_min' ? 'warning' : 'primary') }} fs-6">
                {{ $order->type == 'food' ? 'Food Delivery' : ($order->type == 'ten_min' ? '10-Min Grocery' : 'Standard Delivery') }}
            </span>
        </div>
    </div>

    <div class="row">
        <!-- Order Stats & Customer Info -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Order Items</h5>
                    <span class="badge bg-{{ 
                        in_array($order->delivery_status ?? $order->status, ['delivered', 'completed']) ? 'success' : 
                        (in_array($order->delivery_status ?? $order->status, ['pending', 'assigned', 'ready']) ? 'warning' : 'primary') 
                    }}">
                        {{ ucfirst($order->delivery_status ?? $order->status) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($order->type == 'food')
                                    @foreach($order->items as $item)
                                        <tr>
                                            <td>{{ $item->foodItem->name ?? 'Unknown item' }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td class="text-end">₹{{ number_format($item->price, 2) }}</td>
                                            <td class="text-end">₹{{ number_format($item->price * $item->quantity, 2) }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    @php
                                        $items = $order->type == 'ten_min' ? $order->items : $order->orderItems;
                                    @endphp
                                    @foreach($items as $item)
                                        <tr>
                                            <td>{{ $item->product->name ?? 'Unknown product' }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td class="text-end">₹{{ number_format($item->price, 2) }}</td>
                                            <td class="text-end">₹{{ number_format($item->price * $item->quantity, 2) }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Subtotal</th>
                                    <td class="text-end">₹{{ number_format($order->order_total ?? $order->food_total ?? $order->amount ?? ($order->type == 'food' ? $order->items->sum(fn($i) => $i->price * $i->quantity) : 0), 2) }}</td>
                                </tr>
                                @if(isset($order->delivery_fee))
                                    <tr>
                                        <th colspan="3" class="text-end">Delivery Fee</th>
                                        <td class="text-end">₹{{ number_format($order->delivery_fee, 2) }}</td>
                                    </tr>
                                @endif
                                @if(isset($order->tax) && $order->tax > 0)
                                    <tr>
                                        <th colspan="3" class="text-end">Tax</th>
                                        <td class="text-end">₹{{ number_format($order->tax, 2) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th colspan="3" class="text-end">Grand Total</th>
                                    <th class="text-end">₹{{ number_format($order->total_amount ?? $order->amount ?? 0, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Delivery Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Delivery Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small d-block">Customer Name</label>
                            <strong>{{ $order->customer_name_display ?? $order->customer_name ?? $order->user->name ?? 'Customer' }}</strong>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small d-block">Phone Number</label>
                            <strong>{{ $order->customer_phone ?? $order->user->phone ?? 'Not provided' }}</strong>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="text-primary small d-block fw-bold">
                                <i class="fas fa-map-marker-alt me-1"></i> Delivery Address
                            </label>
                            <div class="p-3 bg-light rounded mt-2 border-start border-primary border-4">
                                <strong>{{ $order->delivery_address_display ?? $order->delivery_address }}</strong>
                            </div>
                        </div>
                        
                        <div class="col-12 mb-0">
                            <hr>
                            <label class="text-success small d-block fw-bold">
                                <i class="fas fa-store me-1"></i> Pickup From
                            </label>
                            <div class="p-3 bg-light rounded mt-2 border-start border-success border-4">
                                <h6 class="mb-1 fw-bold text-success">{{ $order->pickup_name_display ?? 'Pickup Point' }}</h6>
                                <p class="mb-0 small text-muted">{{ $order->pickup_address_display ?? 'Address not available' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Actions -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    @php
                        $status = $order->delivery_status ?? $order->status;
                    @endphp

                    @if($order->delivery_partner_id == $partner->id)
                        @if(in_array($status, ['assigned', 'confirmed', 'accepted', 'ready', 'paid', 'pending']))
                            <form action="{{ route('delivery-partner.orders.pickup', $order->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="type" value="{{ $order->type }}">
                                <button type="submit" class="btn btn-primary w-100 py-3 mb-3">
                                    <i class="fas fa-box me-2"></i>Mark as Picked Up
                                </button>
                            </form>
                        @elseif(in_array($status, ['picked_up', 'in_transit']))
                            <form action="{{ route('delivery-partner.orders.deliver', $order->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="type" value="{{ $order->type }}">
                                <button type="submit" class="btn btn-success w-100 py-3 mb-3">
                                    <i class="fas fa-check-circle me-2"></i>Mark as Delivered
                                </button>
                            </form>
                        @endif

                        @if(!in_array($status, ['delivered', 'completed', 'cancelled']))
                            <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                <i class="fas fa-times me-2"></i>Cancel Delivery
                            </button>
                        @endif
                    @else
                        <div class="alert alert-info py-2">
                             This order is not assigned to you or is available for pickup.
                        </div>
                        <form action="{{ route('delivery-partner.orders.accept', $order->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="{{ $order->type }}">
                            <button type="submit" class="btn btn-primary w-100 py-3">
                                <i class="fas fa-hand-holding me-2"></i>Accept Order
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Payment:</span>
                            <span class="badge bg-light text-dark">{{ strtoupper($order->payment_method) }}</span>
                        </li>
                        <li class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Amount:</span>
                            <span class="fw-bold text-primary">₹{{ number_format($order->total_amount, 2) }}</span>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span class="text-muted">Placed At:</span>
                            <span>{{ $order->created_at->format('d M, h:i A') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('delivery-partner.orders.cancel', $order->id) }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="{{ $order->type }}">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Delivery</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this delivery? Please provide a reason.</p>
                    <textarea name="reason" class="form-control" rows="3" required placeholder="Reason for cancellation..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
