<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Orders</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f1f3f6;
        }

        .filter-box {
            background: #fff;
            padding: 20px;
            border-radius: 6px;
        }

        .order-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .order-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
        }

        .shop-box {
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 10px;
            border-radius: 6px;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 6px 10px;
        }
    </style>
</head>


<body>

    <div class="container mt-4">
        <div class="row">

            <!-- LEFT FILTER -->
            <div class="col-lg-3 mb-3">
                <div class="filter-box">
                    <h6 class="fw-bold mb-3">Filters</h6>

                    <p class="fw-semibold mb-1">Order Status</p>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox">
                        <label class="form-check-label">On the way</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox">
                        <label class="form-check-label">Delivered</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox">
                        <label class="form-check-label">Cancelled</label>
                    </div>
                </div>
            </div>

            <!-- RIGHT CONTENT -->
            <div class="col-lg-9">

                <!-- Search -->
                <div class="input-group mb-3">
                    <input class="form-control" placeholder="Search Orders">
                    <button class="btn btn-dark">Search</button>
                </div>

                @if($orders->count() > 0)

                <p class="fw-semibold">{{ $orders->count() }} Orders Found</p>

                @foreach($orders as $order)
                <div class="order-card mb-4">

                    <!-- ORDER HEADER -->
                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <strong>Order #{{ $order->id }}</strong><br>
                            <small class="text-muted">
                                Ordered on {{ $order->created_at->format('M d, Y') }}
                            </small>
                        </div>

                        <span class="badge status-badge
                            @if($order->status == 'Delivered') bg-success
                            @elseif($order->status == 'Cancelled') bg-danger
                            @else bg-warning text-dark @endif">
                            {{ $order->status }}
                        </span>
                    </div>

                    <!-- SHOP INFO -->
                    <hr class="w-100">

                    <div class="shop-box mb-3 p-2 rounded">
                        <div class="fw-semibold text-dark">
                            🏪 {{ $order->shop_name ?? 'Shop Name' }}
                        </div>
                        <div class="small text-muted">
                            📍 {{ $order->shop_address ?? 'Shop Address' }}
                        </div>
                    </div>
        

                    <hr>

                    <!-- ORDER ITEMS -->
                    @foreach($order->items as $item)
                    <div class="d-flex align-items-center mb-3">
                        <img src="{{ $item->image ?? 'https://via.placeholder.com/80' }}"
                            class="order-img me-3">

                        <div>
                            <h6 class="mb-1">{{ $item->food_name }}</h6>
                            <small class="text-muted">{{ $item->category }}</small><br>
                            <strong>₹{{ $item->price }}</strong>
                        </div>
                    </div>
                    @endforeach

                    <div class="text-end">
                        <a href="#" class="text-primary fw-semibold small">
                            View Order Details →
                        </a>
                    </div>

                </div>
                @endforeach

                <div class="text-center text-muted my-3">
                    — No More Results —
                </div>

                @else
                <!-- EMPTY STATE -->
                <div class="text-center mt-5">
                    <img src="https://cdn-icons-png.flaticon.com/512/2038/2038854.png"
                        width="160" class="mb-3">

                    <h5 class="fw-bold text-secondary">You Have No Orders</h5>
                    <p class="text-muted small">
                        Start shopping to see your orders here.
                    </p>

                    <a href="/food/customer" class="btn btn-primary px-4 py-2">
                        Shop Now
                    </a>
                </div>
                @endif

            </div>
        </div>
    </div>

</body>

</html>