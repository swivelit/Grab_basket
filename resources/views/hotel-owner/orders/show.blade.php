@extends('layouts.minimal')

@section('title', 'Order Details #' . $order->id)

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Order #{{ $order->id }} Details</h1>
            <a href="{{ route('hotel-owner.orders.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Orders
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <!-- Order Information -->
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Items Ordered</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($item->foodItem && $item->foodItem->image)
                                                        <img src="{{ $item->foodItem->first_image_url }}"
                                                            alt="{{ $item->food_name }}" class="rounded me-3"
                                                            style="width: 48px; height: 48px; object-fit: cover;">
                                                    @endif
                                                    <div>
                                                        <h6 class="mb-0">
                                                            {{ $item->food_name ?? ($item->foodItem->name ?? 'Unknown Item') }}
                                                        </h6>
                                                        <small class="text-muted">{{ $item->food_type ?? '' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>₹{{ number_format($item->price, 2) }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>₹{{ number_format($item->price * $item->quantity, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Subtotal</td>
                                        <td>₹{{ number_format($order->subtotal, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Delivery Fee</td>
                                        <td>₹{{ number_format($order->delivery_fee, 2) }}</td>
                                    </tr>
                                    <tr class="table-active">
                                        <td colspan="3" class="text-end fw-bold h5 mb-0">Total Amount</td>
                                        <td class="fw-bold h5 mb-0">₹{{ number_format($order->total_amount, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer & Status -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Order Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small text-uppercase fw-bold">Current Status</label>
                            <div>
                                <span class="badge bg-{{ [
        'pending' => 'warning',
        'accepted' => 'info',
        'preparing' => 'primary',
        'ready' => 'success',
        'delivered' => 'secondary',
        'cancelled' => 'danger'
    ][$order->status] ?? 'secondary' }} fs-6 px-3 py-2 rounded-pill d-block text-center">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </div>
                        </div>

                        @if(in_array($order->status, ['pending', 'accepted', 'assigned', 'preparing', 'ready']))
                            <hr>
                            <form action="{{ route('hotel-owner.orders.update-status', $order) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="mb-3">
                                    <label for="status" class="form-label">Update Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending
                                        </option>
                                        <option value="accepted" {{ $order->status == 'accepted' ? 'selected' : '' }}>Accepted</option>
                                        <option value="assigned" {{ $order->status == 'assigned' ? 'selected' : '' }}>Assigned</option>
                                        <option value="preparing" {{ $order->status == 'preparing' ? 'selected' : '' }}>Preparing</option>
                                        <option value="ready" {{ $order->status == 'ready' ? 'selected' : '' }}>Ready</option>
                                        <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>Delivered
                                        </option>
                                        <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancel
                                            Order</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Update Status</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Customer Details</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Name:</strong> {{ $order->customer_name }}</p>
                        <p class="mb-1"><strong>Phone:</strong> {{ $order->customer_phone ?? 'N/A' }}</p>
                        <hr>
                        <h6 class="small text-muted text-uppercase fw-bold">Delivery Address</h6>
                        <p class="mb-0 text-break">{{ $order->delivery_address }}</p>
                    </div>
                </div>

                @if($order->deliveryPartner)
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Delivery Partner</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="{{ $order->deliveryPartner->profile_photo_url }}"
                                    alt="{{ $order->deliveryPartner->name }}" class="rounded-circle me-3"
                                    style="width: 50px; height: 50px; object-fit: cover;">
                                <div>
                                    <h6 class="mb-0 fw-bold">{{ $order->deliveryPartner->name }}</h6>
                                    <span class="badge bg-success">Verified Partner</span>
                                </div>
                            </div>
                            <p class="mb-2"><i class="fas fa-phone-alt me-2 text-muted"></i>
                                {{ $order->deliveryPartner->phone }}</p>
                            <p class="mb-0"><i class="fas fa-motorcycle me-2 text-muted"></i>
                                {{ $order->deliveryPartner->vehicle_number ?? 'Vehicle Not Listed' }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection