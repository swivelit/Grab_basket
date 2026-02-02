@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">My Orders (Seller)</h2>
    <div class="card">
        <div class="card-body">
            @if($orders->isEmpty())
                <div class="alert alert-info">No orders found for you as a seller.</div>
            @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Buyer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Tracking #</th>
                            <th>Placed At</th>
                        </tr>
             
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>{{ $order->product->name ?? '-' }}</td>
                            <td>{{ $order->buyerUser->name ?? '-' }}</td>
                            <td>₹{{ number_format($order->amount, 2) }}</td>
                            <td>{{ ucfirst($order->status) }}</td>
                            <td>
                                @if($order->status === 'placed' || $order->status === 'shipped')
                                    <form action="{{ route('orders.updateTracking', $order->id) }}" method="POST" class="d-flex align-items-center">
                                        @csrf
                                        <input type="text" name="tracking_number" value="{{ $order->tracking_number }}" class="form-control form-control-sm me-2" placeholder="Enter tracking #" required>
                                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                    </form>
                                @else
                                    {{ $order->tracking_number ?? '-' }}
                                @endif
                            </td>
                            <td>{{ $order->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
